<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

date_default_timezone_set('Asia/Jakarta');

// Folder project di Laragon (dipakai untuk redirect dan path aset di halaman admin).
const BASE_URL = '/warnet';

const BOOKING_TIMES    = [
  '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
  '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
  '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
  '18:00', '19:00', '20:00', '21:00', '22:00', '23:00',
];
const BOOKING_STATUSES = ['Menunggu', 'Disetujui', 'Selesai', 'Dibatalkan'];
// Status yang masih "memegang" komputer pada jadwalnya.
const BOOKING_ACTIVE   = ['Menunggu', 'Disetujui'];
const BOOKING_LOCATIONS = [
  'central' => ['name' => 'NetZone Central', 'address' => 'Jl. Gaming No. 10'],
  'depok' => ['name' => 'NetZone Depok', 'address' => 'Jl. Margonda Gaming No. 18, Depok'],
  'bogor' => ['name' => 'NetZone Bogor', 'address' => 'Jl. Gamer Hills No. 21, Bogor'],
  'tangerang' => ['name' => 'NetZone Tangerang', 'address' => 'Jl. Cyber Arena No. 8, Tangerang'],
  'bekasi' => ['name' => 'NetZone Bekasi', 'address' => 'Jl. Galaxy Utama No. 25, Bekasi'],
];
const PAYMENT_METHODS  = [
  'QRIS',
  'BCA Virtual Account',
  'BNI Virtual Account',
  'BRI Virtual Account',
  'Mandiri Virtual Account',
];

/* =========================
   AKUN
   ========================= */

function users_file_path(): string
{
  $storageFile = __DIR__ . '/storage/users.json';
  $legacyFile = __DIR__ . '/users.json';

  return is_file($storageFile) || !is_file($legacyFile) ? $storageFile : $legacyFile;
}

function db_connection(): mysqli
{
  static $connection = null;
  if ($connection === null) {
    $connection = require __DIR__ . '/config/database.php';
  }
  return $connection;
}

function ensure_db_column(mysqli $connection, string $table, string $column, string $definition): void
{
  $result = $connection->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
  if ($result->num_rows === 0) {
    $connection->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
  }
}

function ensure_database_storage(): void
{
  static $ready = false;
  if ($ready) {
    return;
  }

  $connection = db_connection();
  ensure_db_column($connection, 'bookings', 'people', 'INT NOT NULL DEFAULT 1 AFTER `duration`');
  ensure_db_column($connection, 'bookings', 'username_snapshot', 'VARCHAR(100) NULL AFTER `user_id`');
  ensure_db_column($connection, 'bookings', 'location_key', 'VARCHAR(30) NULL AFTER `username_snapshot`');
  ensure_db_column($connection, 'payments', 'payment_method_detail', 'VARCHAR(80) NULL AFTER `payment_method`');
  ensure_db_column($connection, 'computers', 'price_per_hour', 'DECIMAL(10,2) NULL AFTER `computer_name`');
  ensure_db_column($connection, 'booking_details', 'computer_name_snapshot', 'VARCHAR(100) NULL AFTER `computer_id`');
  ensure_db_column($connection, 'booking_details', 'category_name_snapshot', 'VARCHAR(50) NULL AFTER `computer_name_snapshot`');
  $connection->query(
    'UPDATE bookings b JOIN users u ON u.id = b.user_id
     SET b.username_snapshot = u.username WHERE b.username_snapshot IS NULL'
  );
  $connection->query(
    'UPDATE booking_details d
     JOIN computers c ON c.id = d.computer_id
     JOIN categories cat ON cat.id = c.category_id
     SET d.computer_name_snapshot = c.computer_name, d.category_name_snapshot = cat.name
     WHERE d.computer_name_snapshot IS NULL OR d.category_name_snapshot IS NULL'
  );

  $counts = [];
  foreach (['users', 'categories', 'computers', 'bookings', 'booking_details', 'payments'] as $table) {
    $counts[] = (int) $connection->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];
  }

  if (array_sum($counts) === 0) {
    import_legacy_data($connection);
  } else {
    seed_database_computers($connection);
  }
  $ready = true;
}

function seed_database_computers(mysqli $connection): void
{
  $categoryIds = [];
  $result = $connection->query('SELECT id, name FROM categories');
  while ($category = $result->fetch_assoc()) {
    $type = strtolower((string) $category['name']) === 'vip' ? 'vip' : 'regular';
    $categoryIds[$type] = (int) $category['id'];
  }

  if (!$categoryIds) {
    foreach (['regular' => 'Regular', 'vip' => 'VIP'] as $type => $name) {
      $price = $type === 'vip' ? 12000 : 7000;
      $stmt = $connection->prepare('INSERT INTO categories (name, price_per_hour) VALUES (?, ?)');
      $stmt->bind_param('si', $name, $price);
      $stmt->execute();
      $categoryIds[$type] = (int) $connection->insert_id;
    }
  } else {
    foreach (['regular' => 'Regular', 'vip' => 'VIP'] as $type => $name) {
      if (!isset($categoryIds[$type])) {
        $price = $type === 'vip' ? 12000 : 7000;
        $stmt = $connection->prepare('INSERT INTO categories (name, price_per_hour) VALUES (?, ?)');
        $stmt->bind_param('si', $name, $price);
        $stmt->execute();
        $categoryIds[$type] = (int) $connection->insert_id;
      }
    }
  }

  $existing = (int) $connection->query('SELECT COUNT(*) FROM computers')->fetch_row()[0];
  if ($existing > 0) {
    return;
  }

  $computers = json_read(computers_path(), default_computers());
  $stmt = $connection->prepare(
    'INSERT INTO computers (id, category_id, computer_code, computer_name, price_per_hour, status) VALUES (?, ?, ?, ?, ?, ?)'
  );
  foreach ($computers as $computer) {
    $id = (int) ($computer['id'] ?? 0);
    $type = ($computer['type'] ?? '') === 'vip' ? 'vip' : 'regular';
    if ($id < 1 || !isset($categoryIds[$type])) {
      continue;
    }
    $categoryId = $categoryIds[$type];
    $name = (string) ($computer['name'] ?? ('PC ' . $id));
    $code = strtoupper(substr($type, 0, 3)) . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    $price = (int) ($computer['price'] ?? ($type === 'vip' ? 12000 : 7000));
    $status = ($computer['status'] ?? '') === 'maintenance' ? 'maintenance' : 'available';
    $stmt->bind_param('iissis', $id, $categoryId, $code, $name, $price, $status);
    $stmt->execute();
  }
}

function import_legacy_data(mysqli $connection): void
{
  seed_database_computers($connection);
  $legacyUsers = json_read(users_file_path(), []);
  $userIds = [];
  $userInsert = $connection->prepare(
    'INSERT INTO users (username, email, phone, password, role, profile_photo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
  );

  foreach ($legacyUsers as $user) {
    $username = (string) ($user['username'] ?? '');
    $email = (string) ($user['email'] ?? '');
    $phone = (string) ($user['phone'] ?? '');
    $password = (string) ($user['password'] ?? '');
    $role = ($user['role'] ?? '') === 'admin' ? 'admin' : 'user';
    $photo = isset($user['profile_photo']) ? (string) $user['profile_photo'] : null;
    $createdAt = date('Y-m-d H:i:s', strtotime((string) ($user['created_at'] ?? 'now')) ?: time());
    if ($username === '' || $email === '' || $password === '') {
      continue;
    }
    $userInsert->bind_param('sssssss', $username, $email, $phone, $password, $role, $photo, $createdAt);
    $userInsert->execute();
    $userIds[(string) ($user['id'] ?? '')] = (int) $connection->insert_id;
  }

  $pcResult = $connection->query('SELECT id, computer_name FROM computers');
  $pcNames = [];
  while ($pc = $pcResult->fetch_assoc()) {
    $pcNames[(int) $pc['id']] = (string) $pc['computer_name'];
  }

  $bookingInsert = $connection->prepare(
    'INSERT INTO bookings (booking_code, user_id, username_snapshot, booking_date, start_time, duration, people, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
  );
  $detailInsert = $connection->prepare(
    'INSERT INTO booking_details (booking_id, computer_id, computer_name_snapshot, category_name_snapshot, price_per_hour, duration, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)'
  );
  $paymentInsert = $connection->prepare(
    'INSERT INTO payments (booking_id, payment_method, payment_method_detail, amount, payment_status, paid_at) VALUES (?, ?, ?, ?, ?, ?)'
  );
  foreach (json_read(bookings_path(), []) as $booking) {
    $legacyUserId = (string) ($booking['user_id'] ?? '');
    if (!isset($userIds[$legacyUserId])) {
      continue;
    }
    $userId = $userIds[$legacyUserId];
    $username = (string) ($booking['username'] ?? '');
    $code = (string) ($booking['code'] ?? '');
    $date = (string) ($booking['date'] ?? date('Y-m-d'));
    $time = (string) ($booking['time'] ?? '09:00:00');
    $duration = max(1, (int) ($booking['duration'] ?? 1));
    $people = max(1, (int) ($booking['people'] ?? 1));
    $total = (int) ($booking['total'] ?? 0);
    $status = match ((string) ($booking['status'] ?? '')) {
      'Disetujui' => 'confirmed',
      'Selesai' => 'completed',
      'Dibatalkan' => 'cancelled',
      default => 'pending',
    };
    $createdAt = date('Y-m-d H:i:s', strtotime((string) ($booking['created_at'] ?? 'now')) ?: time());
    if ($code === '') {
      continue;
    }
    $bookingInsert->bind_param('sisssiidss', $code, $userId, $username, $date, $time, $duration, $people, $total, $status, $createdAt);
    $bookingInsert->execute();
    $bookingId = (int) $connection->insert_id;

    $ids = (array) ($booking['pc_ids'] ?? []);
    $names = (array) ($booking['pc_names'] ?? []);
    $type = (string) ($booking['type'] ?? 'Regular');
    $count = max(1, count($ids));
    foreach ($ids as $index => $pcId) {
      $pcId = (int) $pcId;
      if (!isset($pcNames[$pcId])) {
        continue;
      }
      $computerName = (string) ($names[$index] ?? $pcNames[$pcId]);
      $pricePerHour = (int) round(((int) ($booking['total'] ?? 0)) / $duration / $count);
      $subtotal = $pricePerHour * $duration;
      $detailInsert->bind_param('iissiid', $bookingId, $pcId, $computerName, $type, $pricePerHour, $duration, $subtotal);
      $detailInsert->execute();
    }

    $detail = (string) ($booking['payment'] ?? 'QRIS');
    $method = $detail === 'QRIS' ? 'qris' : (str_contains($detail, 'Virtual Account') ? 'bank_transfer' : 'cash');
    $paymentStatus = ($booking['payment_status'] ?? '') === 'Simulasi Berhasil' ? 'paid' : 'pending';
    $paidAt = $paymentStatus === 'paid' ? $createdAt : null;
    $paymentInsert->bind_param('issdss', $bookingId, $method, $detail, $total, $paymentStatus, $paidAt);
    $paymentInsert->execute();
  }
}

function load_users(): array
{
  ensure_database_storage();
  $users = [];
  $result = db_connection()->query(
    'SELECT id, email, phone, username, password, role, profile_photo, created_at FROM users ORDER BY id'
  );
  while ($record = $result->fetch_assoc()) {
    $users[] = [
      'id' => (string) $record['id'],
      'email' => (string) $record['email'],
      'phone' => (string) ($record['phone'] ?? ''),
      'username' => (string) $record['username'],
      'password' => (string) $record['password'],
      'role' => (string) $record['role'],
      'profile_photo' => (string) ($record['profile_photo'] ?? ''),
      'created_at' => (string) $record['created_at'],
    ];
  }
  return $users;
}

function save_users(array $users): bool
{
  ensure_database_storage();
  $connection = db_connection();
  $connection->begin_transaction();
  try {
    $insert = $connection->prepare(
      'INSERT INTO users (username, email, phone, password, role, profile_photo) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $update = $connection->prepare(
      'UPDATE users SET username = ?, email = ?, phone = ?, password = ?, role = ?, profile_photo = ? WHERE id = ?'
    );
    foreach ($users as $user) {
      $username = (string) ($user['username'] ?? '');
      $email = (string) ($user['email'] ?? '');
      $phone = (string) ($user['phone'] ?? '');
      $password = (string) ($user['password'] ?? '');
      $role = ($user['role'] ?? '') === 'admin' ? 'admin' : 'user';
      $photo = isset($user['profile_photo']) && $user['profile_photo'] !== '' ? (string) $user['profile_photo'] : null;
      if ($username === '' || $email === '' || $password === '') {
        throw new UnexpectedValueException('Data akun tidak lengkap.');
      }
      if (ctype_digit((string) ($user['id'] ?? '')) && (int) $user['id'] > 0) {
        $id = (int) $user['id'];
        $update->bind_param('ssssssi', $username, $email, $phone, $password, $role, $photo, $id);
        $update->execute();
        if ($update->affected_rows === 0 && !user_id_exists($connection, $id)) {
          throw new RuntimeException('Akun yang akan diperbarui tidak ditemukan.');
        }
      } else {
        $insert->bind_param('ssssss', $username, $email, $phone, $password, $role, $photo);
        $insert->execute();
      }
    }
    $connection->commit();
    return true;
  } catch (mysqli_sql_exception | RuntimeException $error) {
    $connection->rollback();
    error_log('Database user save failed: ' . $error->getMessage());
    return false;
  }
}

function user_id_exists(mysqli $connection, int $id): bool
{
  $stmt = $connection->prepare('SELECT id FROM users WHERE id = ?');
  $stmt->bind_param('i', $id);
  $stmt->execute();
  return $stmt->get_result()->num_rows > 0;
}

function valid_password(string $password): bool
{
  return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password) === 1;
}

function require_login(): void
{
  if (empty($_SESSION['netzone_user'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
  }
}

/** Untuk tampilan saja (mis. menampilkan link Admin). Pengecekan akses memakai require_admin(). */
function is_admin(): bool
{
  return (($_SESSION['netzone_user']['role'] ?? '') === 'admin');
}

/**
 * Menjaga halaman admin. Role dicek ulang ke file akun (bukan hanya session),
 * jadi kalau role admin dicabut, aksesnya langsung hilang.
 */
function require_admin(): void
{
  require_login();

  $id = (string) ($_SESSION['netzone_user']['id'] ?? '');
  $ok = false;
  if ($id !== '') {
    foreach (load_users() as $record) {
      if (($record['id'] ?? '') === $id) {
        $ok = (($record['role'] ?? '') === 'admin');
        break;
      }
    }
  }

  if (!$ok) {
    http_response_code(403);
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Akses ditolak</title>'
      . '<body style="font-family:sans-serif;padding:48px;max-width:520px;margin:auto">'
      . '<h1>403 · Akses ditolak</h1><p>Halaman ini khusus admin NetZone.</p>'
      . '<p><a href="' . BASE_URL . '/beranda.php">Kembali ke Beranda</a></p></body>';
    exit;
  }
}

/* =========================
   CSRF
   ========================= */

function csrf_token(): string
{
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_field(): string
{
  return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
  $sent = $_POST['csrf'] ?? '';
  if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
    http_response_code(400);
    exit('Permintaan tidak valid (CSRF). Muat ulang halaman lalu coba lagi.');
  }
}

/* =========================
   PEMBACAAN DATA JSON LAMA UNTUK IMPOR
   ========================= */

function json_read(string $path, array $default = []): array
{
  if (!is_file($path)) {
    return $default;
  }
  $raw = file_get_contents($path);
  if ($raw === false || trim($raw) === '') {
    return $default;
  }
  $data = json_decode($raw, true);
  return is_array($data) ? $data : $default;
}

function bookings_path(): string  { return __DIR__ . '/storage/bookings.json'; }
function computers_path(): string { return __DIR__ . '/storage/computers.json'; }

function load_bookings(): array
{
  ensure_database_storage();
  $connection = db_connection();
  $result = $connection->query(
    "SELECT b.id, b.booking_code, b.user_id, COALESCE(b.username_snapshot, u.username) AS username, b.location_key, b.booking_date, b.start_time,
            b.duration, b.people, b.total_price, b.status, b.created_at, b.updated_at,
            p.payment_method_detail, p.payment_method, p.payment_status
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN payments p ON p.id = (
       SELECT MAX(p2.id) FROM payments p2 WHERE p2.booking_id = b.id
     )
     ORDER BY b.created_at DESC, b.id DESC"
  );
  $bookings = [];
  $byId = [];
  while ($row = $result->fetch_assoc()) {
    $status = [
      'pending' => 'Menunggu',
      'confirmed' => 'Disetujui',
      'completed' => 'Selesai',
      'cancelled' => 'Dibatalkan',
    ][$row['status']] ?? 'Menunggu';
    $paymentMethod = (string) ($row['payment_method_detail'] ?? '');
    if ($paymentMethod === '') {
      $paymentMethod = match ((string) ($row['payment_method'] ?? '')) {
        'qris' => 'QRIS',
        'bank_transfer' => 'Virtual Account',
        'ewallet' => 'E-Wallet',
        'cash' => 'Tunai',
        default => '',
      };
    }
    $createdAt = strtotime((string) $row['created_at']);
    $updatedAt = strtotime((string) $row['updated_at']);
    $booking = [
      'id' => (string) $row['id'],
      'code' => (string) $row['booking_code'],
      'user_id' => (string) $row['user_id'],
      'username' => (string) $row['username'],
      'location' => (string) ($row['location_key'] ?? ''),
      'type' => '',
      'pc_ids' => [],
      'pc_names' => [],
      'date' => (string) $row['booking_date'],
      'time' => substr((string) $row['start_time'], 0, 5),
      'duration' => (int) $row['duration'],
      'people' => (int) $row['people'],
      'payment' => $paymentMethod,
      'total' => (int) round((float) $row['total_price']),
      'status' => $status,
      'payment_status' => match ((string) ($row['payment_status'] ?? '')) {
        'paid' => 'Simulasi Berhasil',
        'failed' => 'Gagal',
        'pending' => 'Menunggu Pembayaran',
        default => '',
      },
      'created_at' => $createdAt ? date('c', $createdAt) : '',
      'updated_at' => $updatedAt ? date('c', $updatedAt) : '',
    ];
    $byId[(int) $row['id']] = count($bookings);
    $bookings[] = $booking;
  }

  if ($byId) {
    $details = $connection->query(
      'SELECT d.booking_id, d.computer_id,
              COALESCE(d.computer_name_snapshot, c.computer_name) AS computer_name,
              COALESCE(d.category_name_snapshot, cat.name) AS category_name
       FROM booking_details d
       JOIN computers c ON c.id = d.computer_id
       JOIN categories cat ON cat.id = c.category_id
       ORDER BY d.id'
    );
    while ($detail = $details->fetch_assoc()) {
      $bookingIndex = $byId[(int) $detail['booking_id']] ?? null;
      if ($bookingIndex === null) {
        continue;
      }
      $bookings[$bookingIndex]['pc_ids'][] = (int) $detail['computer_id'];
      $bookings[$bookingIndex]['pc_names'][] = (string) $detail['computer_name'];
      $bookings[$bookingIndex]['type'] = (string) $detail['category_name'];
    }
  }
  return $bookings;
}

/** Daftar komputer awal. Admin bisa mengubahnya dari halaman Komputer & Harga. */
function default_computers(): array
{
  $list = [];
  for ($i = 1; $i <= 8; $i++) {
    $list[] = ['id' => $i, 'name' => sprintf('Regular PC %02d', $i), 'type' => 'regular', 'price' => 7000, 'status' => 'aktif'];
  }
  for ($i = 1; $i <= 4; $i++) {
    $list[] = ['id' => 8 + $i, 'name' => sprintf('VIP PC %02d', $i), 'type' => 'vip', 'price' => 12000, 'status' => 'aktif'];
  }
  return $list;
}

function load_computers(): array
{
  ensure_database_storage();
  $result = db_connection()->query(
    "SELECT c.id, c.computer_name, c.price_per_hour AS computer_price,
            c.status, cat.name AS category_name, cat.price_per_hour AS category_price
     FROM computers c
     JOIN categories cat ON cat.id = c.category_id
     ORDER BY c.id"
  );
  $computers = [];
  while ($row = $result->fetch_assoc()) {
    $computers[] = [
      'id' => (int) $row['id'],
      'name' => (string) $row['computer_name'],
      'type' => strtolower((string) $row['category_name']) === 'vip' ? 'vip' : 'regular',
      'price' => (int) round((float) ($row['computer_price'] ?? $row['category_price'])),
      'status' => $row['status'] === 'maintenance' ? 'maintenance' : 'aktif',
    ];
  }
  return $computers;
}

function create_booking(array $booking, array $selected): bool|string
{
  ensure_database_storage();
  $connection = db_connection();
  $ids = array_values(array_unique(array_map(static fn($computer) => (int) $computer['id'], $selected)));
  if (!$ids || !ctype_digit((string) ($booking['user_id'] ?? ''))) {
    return 'save';
  }

  $connection->begin_transaction();
  try {
    $idList = implode(',', $ids);
    $locked = $connection->query(
      "SELECT id FROM computers WHERE status = 'available' AND id IN ($idList) ORDER BY id FOR UPDATE"
    );
    $lockedIds = array_map(static fn($row) => (int) $row['id'], $locked->fetch_all(MYSQLI_ASSOC));
    sort($ids);
    sort($lockedIds);
    if ($lockedIds !== $ids) {
      $connection->rollback();
      return 'computers';
    }

    $date = (string) $booking['date'];
    $time = (string) $booking['time'];
    $duration = (int) $booking['duration'];
    $conflict = $connection->prepare(
      "SELECT d.computer_id
       FROM booking_details d
       JOIN bookings b ON b.id = d.booking_id
       WHERE b.status IN ('pending', 'confirmed')
         AND d.computer_id IN ($idList)
         AND TIMESTAMP(b.booking_date, b.start_time) < DATE_ADD(TIMESTAMP(?, ?), INTERVAL ? HOUR)
         AND DATE_ADD(TIMESTAMP(b.booking_date, b.start_time), INTERVAL b.duration HOUR) > TIMESTAMP(?, ?)
       LIMIT 1 FOR UPDATE"
    );
    $conflict->bind_param('ssiss', $date, $time, $duration, $date, $time);
    $conflict->execute();
    if ($conflict->get_result()->num_rows > 0) {
      $connection->rollback();
      return 'taken';
    }

    $userId = (int) $booking['user_id'];
    $code = (string) $booking['code'];
    $people = (int) $booking['people'];
    $total = (int) $booking['total'];
    $location = (string) ($booking['location'] ?? '');
    if (!isset(BOOKING_LOCATIONS[$location])) {
      $connection->rollback();
      return 'location';
    }
    $status = 'confirmed';
    $stmt = $connection->prepare(
      'INSERT INTO bookings (booking_code, user_id, username_snapshot, location_key, booking_date, start_time, duration, people, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $username = (string) ($booking['username'] ?? '');
    $stmt->bind_param('sissssiids', $code, $userId, $username, $location, $date, $time, $duration, $people, $total, $status);
    $stmt->execute();
    $bookingId = (int) $connection->insert_id;

    $detailInsert = $connection->prepare(
      'INSERT INTO booking_details (booking_id, computer_id, computer_name_snapshot, category_name_snapshot, price_per_hour, duration, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($selected as $computer) {
      $computerId = (int) $computer['id'];
      $computerName = (string) $computer['name'];
      $categoryName = ($computer['type'] ?? '') === 'vip' ? 'VIP' : 'Regular';
      $price = (int) $computer['price'];
      $subtotal = $price * $duration;
      $detailInsert->bind_param('iissiid', $bookingId, $computerId, $computerName, $categoryName, $price, $duration, $subtotal);
      $detailInsert->execute();
    }

    $payment = (string) $booking['payment'];
    $method = $payment === 'QRIS' ? 'qris' : (str_contains($payment, 'Virtual Account') ? 'bank_transfer' : 'cash');
    $paymentStatus = 'paid';
    $paymentInsert = $connection->prepare(
      'INSERT INTO payments (booking_id, payment_method, payment_method_detail, amount, payment_status, paid_at) VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $paymentInsert->bind_param('issds', $bookingId, $method, $payment, $total, $paymentStatus);
    $paymentInsert->execute();
    $connection->commit();
    return true;
  } catch (mysqli_sql_exception $error) {
    $connection->rollback();
    error_log('Database booking save failed: ' . $error->getMessage());
    return 'save';
  }
}

function update_booking_status(string $id, array $allowedStatuses, string $newStatus): bool
{
  ensure_database_storage();
  if (!ctype_digit($id) || !$allowedStatuses) {
    return false;
  }
  $dbStatuses = array_map(static fn($status) => [
    'Menunggu' => 'pending',
    'Disetujui' => 'confirmed',
    'Selesai' => 'completed',
    'Dibatalkan' => 'cancelled',
  ][$status] ?? '', $allowedStatuses);
  $dbStatuses = array_values(array_filter($dbStatuses));
  $newDbStatus = [
    'Menunggu' => 'pending',
    'Disetujui' => 'confirmed',
    'Selesai' => 'completed',
    'Dibatalkan' => 'cancelled',
  ][$newStatus] ?? '';
  if (!$dbStatuses || $newDbStatus === '') {
    return false;
  }

  $placeholders = implode(',', array_fill(0, count($dbStatuses), '?'));
  try {
    $stmt = db_connection()->prepare(
      "UPDATE bookings SET status = ? WHERE id = ? AND status IN ($placeholders)"
    );
    $types = 'si' . str_repeat('s', count($dbStatuses));
    $params = array_merge([$newDbStatus, (int) $id], $dbStatuses);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->affected_rows === 1;
  } catch (mysqli_sql_exception $error) {
    error_log('Database booking status update failed: ' . $error->getMessage());
    return false;
  }
}

function save_computer(string $action, int $id, array $data): bool|string
{
  ensure_database_storage();
  $connection = db_connection();
  try {
    $type = ($data['type'] ?? '') === 'vip' ? 'vip' : 'regular';
    $category = $connection->prepare('SELECT id FROM categories WHERE LOWER(name) = ? LIMIT 1');
    $category->bind_param('s', $type);
    $category->execute();
    $categoryId = (int) ($category->get_result()->fetch_assoc()['id'] ?? 0);
    if ($categoryId < 1) {
      return 'Kategori komputer tidak ditemukan.';
    }

    if ($action === 'add') {
      $name = (string) $data['name'];
      $price = (int) $data['price'];
      $status = ($data['status'] ?? '') === 'maintenance' ? 'maintenance' : 'available';
      $code = strtoupper(substr($type, 0, 3)) . '-' . strtoupper(bin2hex(random_bytes(4)));
      $stmt = $connection->prepare(
        'INSERT INTO computers (category_id, computer_code, computer_name, price_per_hour, status) VALUES (?, ?, ?, ?, ?)'
      );
      $stmt->bind_param('issis', $categoryId, $code, $name, $price, $status);
      $stmt->execute();
      return true;
    }

    $name = (string) ($data['name'] ?? '');
    $price = (int) ($data['price'] ?? 0);
    $status = ($data['status'] ?? '') === 'maintenance' ? 'maintenance' : 'available';
    $stmt = $connection->prepare(
      'UPDATE computers SET category_id = ?, computer_name = ?, price_per_hour = ?, status = ? WHERE id = ?'
    );
    $stmt->bind_param('isisi', $categoryId, $name, $price, $status, $id);
    $stmt->execute();
    return $stmt->affected_rows === 1 || computer_id_exists($connection, $id);
  } catch (mysqli_sql_exception $error) {
    error_log('Database computer save failed: ' . $error->getMessage());
    return false;
  }
}

function computer_id_exists(mysqli $connection, int $id): bool
{
  $stmt = $connection->prepare('SELECT id FROM computers WHERE id = ?');
  $stmt->bind_param('i', $id);
  $stmt->execute();
  return $stmt->get_result()->num_rows > 0;
}

function delete_computer(int $id): bool|string
{
  ensure_database_storage();
  $connection = db_connection();
  $connection->begin_transaction();
  try {
    $lock = $connection->prepare('SELECT id FROM computers WHERE id = ? FOR UPDATE');
    $lock->bind_param('i', $id);
    $lock->execute();
    if ($lock->get_result()->num_rows === 0) {
      $connection->rollback();
      return false;
    }
    $check = $connection->prepare('SELECT COUNT(*) AS total FROM booking_details WHERE computer_id = ?');
    $check->bind_param('i', $id);
    $check->execute();
    if ((int) $check->get_result()->fetch_assoc()['total'] > 0) {
      $connection->rollback();
      return 'Komputer yang sudah tercatat pada riwayat booking tidak bisa dihapus.';
    }
    $delete = $connection->prepare('DELETE FROM computers WHERE id = ?');
    $delete->bind_param('i', $id);
    $delete->execute();
    $connection->commit();
    return $delete->affected_rows === 1;
  } catch (mysqli_sql_exception $error) {
    $connection->rollback();
    error_log('Database computer delete failed: ' . $error->getMessage());
    return false;
  }
}

function save_category_price(string $type, int $price): bool
{
  ensure_database_storage();
  $connection = db_connection();
  $connection->begin_transaction();
  try {
    $exists = $connection->prepare('SELECT id FROM categories WHERE LOWER(name) = ? LIMIT 1');
    $exists->bind_param('s', $type);
    $exists->execute();
    if ($exists->get_result()->num_rows === 0) {
      $connection->rollback();
      return false;
    }
    $category = $connection->prepare('UPDATE categories SET price_per_hour = ? WHERE LOWER(name) = ?');
    $category->bind_param('is', $price, $type);
    $category->execute();
    $computers = $connection->prepare(
      'UPDATE computers SET price_per_hour = NULL WHERE category_id = (SELECT id FROM categories WHERE LOWER(name) = ?)'
    );
    $computers->bind_param('s', $type);
    $computers->execute();
    $connection->commit();
    return true;
  } catch (mysqli_sql_exception $error) {
    $connection->rollback();
    error_log('Database category price update failed: ' . $error->getMessage());
    return false;
  }
}

function computers_by_id(array $computers): array
{
  $map = [];
  foreach ($computers as $c) {
    $map[(int) ($c['id'] ?? 0)] = $c;
  }
  return $map;
}

/** Harga termurah per jam untuk satu kategori (komputer aktif saja). Null kalau tidak ada. */
function type_min_price(string $type): ?int
{
  $prices = [];
  foreach (load_computers() as $c) {
    if (($c['type'] ?? '') === $type && ($c['status'] ?? '') === 'aktif') {
      $prices[] = (int) $c['price'];
    }
  }
  return $prices ? min($prices) : null;
}

function type_active_count(string $type): int
{
  $n = 0;
  foreach (load_computers() as $c) {
    if (($c['type'] ?? '') === $type && ($c['status'] ?? '') === 'aktif') {
      $n++;
    }
  }
  return $n;
}

/* =========================
   LOGIKA BOOKING
   ========================= */

function booking_params(array $src): array
{
  $location = trim((string) ($src['location'] ?? ''));
  if (!isset(BOOKING_LOCATIONS[$location])) {
    $location = '';
  }
  $type = strtolower((string) ($src['type'] ?? 'regular')) === 'vip' ? 'vip' : 'regular';
  $date = trim((string) ($src['date'] ?? ''));
  $time = trim((string) ($src['time'] ?? '09:00'));
  if (!in_array($time, BOOKING_TIMES, true)) {
    $time = '09:00';
  }
  $duration = max(1, min(5, (int) ($src['duration'] ?? 1)));
  $people   = max(1, min(8, (int) ($src['people'] ?? 1)));
  $count    = max(1, min(8, (int) ($src['computer_count'] ?? 1)));
  if ($count > $people) {
    $count = $people;
  }
  $pcs = $src['pcs'] ?? [];
  if (!is_array($pcs)) {
    $pcs = [];
  }
  $pcs = array_values(array_unique(array_filter(array_map('intval', $pcs), fn($n) => $n > 0)));

  return compact('location', 'type', 'date', 'time', 'duration', 'people', 'count', 'pcs');
}

function booking_date_ok(string $date): bool
{
  if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) !== 1) {
    return false;
  }
  return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) && $date >= date('Y-m-d');
}

function booking_time_ok(string $date, string $time): bool
{
  if (!in_array($time, BOOKING_TIMES, true)) {
    return false;
  }
  if ($date > date('Y-m-d')) {
    return true;
  }
  return $date === date('Y-m-d') && strtotime($date . ' ' . $time) > time();
}

function booking_window(string $date, string $time, int $duration): array
{
  $start = strtotime($date . ' ' . $time);
  return [$start, $start + $duration * 3600];
}

/** ID komputer yang sudah dipakai booking lain (Menunggu/Disetujui) pada rentang waktu itu. */
function booked_computer_ids(string $date, string $time, int $duration, ?array $bookings = null): array
{
  [$start, $end] = booking_window($date, $time, $duration);
  $taken = [];
  foreach ($bookings ?? load_bookings() as $b) {
    if (!in_array($b['status'] ?? '', BOOKING_ACTIVE, true)) {
      continue;
    }
    [$bStart, $bEnd] = booking_window((string) $b['date'], (string) $b['time'], (int) $b['duration']);
    if ($start < $bEnd && $bStart < $end) {
      foreach ((array) ($b['pc_ids'] ?? []) as $id) {
        $taken[(int) $id] = true;
      }
    }
  }
  return array_keys($taken);
}

/**
 * Cek pilihan komputer di sisi server.
 * Mengembalikan [daftar komputer terpilih, kode error|null]; error: 'computers' atau 'taken'.
 */
function resolve_booking_pcs(array $p, array $computers, ?array $bookings = null): array
{
  $map = computers_by_id($computers);
  $selected = [];
  foreach ($p['pcs'] as $id) {
    $c = $map[$id] ?? null;
    if (!$c || ($c['type'] ?? '') !== $p['type'] || ($c['status'] ?? '') !== 'aktif') {
      return [[], 'computers'];
    }
    $selected[] = $c;
  }
  if (count($selected) !== $p['count']) {
    return [[], 'computers'];
  }
  $taken = booked_computer_ids($p['date'], $p['time'], $p['duration'], $bookings);
  foreach ($selected as $c) {
    if (in_array((int) $c['id'], $taken, true)) {
      return [[], 'taken'];
    }
  }
  return [$selected, null];
}

function booking_total(array $selected, int $duration): int
{
  $sum = 0;
  foreach ($selected as $c) {
    $sum += (int) $c['price'];
  }
  return $sum * $duration;
}

function status_class(string $status): string
{
  return ['Menunggu' => 'status--pending', 'Disetujui' => 'status--ok', 'Selesai' => 'done', 'Dibatalkan' => 'status--cancel'][$status] ?? 'status--pending';
}

/* =========================
   TAMPILAN
   ========================= */

// Central content so pages stay free of repeated markup.
$user = ['name' => 'cakipau', 'initial' => 'C'];
if (isset($_SESSION['netzone_user']) && is_array($_SESSION['netzone_user'])) {
  $account = $_SESSION['netzone_user'];
  $username = (string) ($account['username'] ?? 'User');
  $user = [
    'id' => (string) ($account['id'] ?? ''),
    'name' => $username,
    'initial' => strtoupper(substr($username, 0, 1)),
    'email' => (string) ($account['email'] ?? ''),
    'phone' => (string) ($account['phone'] ?? ''),
    'profile_photo' => (string) ($account['profile_photo'] ?? ''),
    'photo_url' => profile_photo_url((string) ($account['profile_photo'] ?? '')),
  ];
}

$navLinks = [
  'home'    => ['Beranda', 'beranda.php', '<path d="M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10"/>'],
  'booking' => ['Booking', 'booking.php', '<rect x="3.5" y="5" width="17" height="16" rx="3"/><path d="M8 3v4M16 3v4M3.5 10h17m-12.5 4h3m-3 3h6"/><path d="m15 16 1.5 1.5L20 14"/>'],
  'history' => ['Riwayat', 'riwayat.php', '<path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l3 2"/>'],
  'profile' => ['Profile', 'profil.php',   '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>'],
];

$quickActions = [
  ['Booking',  'Pilih waktu mainmu', '<img src="assets/booking.png" alt="" width="52" height="52" style="width:80%;height:80%;max-width:100%;object-fit:contain;display:block">',  '#d9f6ff', 'booking.php'],
  ['Lokasi',   'Temukan NetZone',    '<img src="assets/lokasi.png" alt="" width="52" height="52" style="width:80%;height:80%;max-width:100%;object-fit:contain;display:block">',   '#ece1ff', 'lokasi.php'],
  ['Kategori', 'VIP atau Regular',   '<img src="assets/kategori.png" alt="" width="52" height="52" style="width:80%;height:80%;max-width:100%;object-fit:contain;display:block">', '#fff2bc', 'kategori.php'],
  ['Riwayat',  'Cek pesanan kamu',   '<img src="assets/riwayat.png" alt="" width="52" height="52" style="width:80%;height:80%;max-width:100%;object-fit:contain;display:block">',  '#ffe2ee', 'riwayat.php'],
];

$featured = [
  'type' => 'REGULAR', 'title' => 'Regular Komputer',
  'desc' => 'Nyaman untuk gaming, nugas, dan ngobrol',
  'rating' => 4.8, 'reviews' => 120, 'price' => type_min_price('regular') ?? 7000, 'link' => 'booking.php?type=regular',
];

function rupiah(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function profile_photo_url(string $filename): ?string
{
  if ($filename === '' || basename($filename) !== $filename) {
    return null;
  }
  $path = __DIR__ . '/storage/profile-photos/' . $filename;
  if (!is_file($path)) {
    return null;
  }
  return BASE_URL . '/profile-photo.php?v=' . (int) filemtime($path);
}

/* =========================
   FLASH MESSAGE (dipakai halaman admin)
   ========================= */

function flash_set(string $message, string $type = 'ok'): void
{
  $_SESSION['flash'] = ['m' => $message, 't' => $type];
}

function flash_get(): ?array
{
  $f = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  return is_array($f) ? $f : null;
}

function admin_badge(string $status): string
{
  $cls = ['Menunggu' => 'pending', 'Disetujui' => 'ok', 'Selesai' => 'done', 'Dibatalkan' => 'cancel'][$status] ?? 'pending';
  return '<span class="ad-badge ad-badge--' . $cls . '">' . e($status) . '</span>';
}
