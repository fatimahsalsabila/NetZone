<?php
require_once __DIR__ . '/../data.php';
require_admin();

/* ---------- aksi (setujui / batalkan / selesai) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $rules = [
    'approve' => [['Menunggu'], 'Disetujui'],
    'cancel'  => [['Menunggu', 'Disetujui'], 'Dibatalkan'],
    'finish'  => [['Disetujui'], 'Selesai'],
  ];
  $action = (string) ($_POST['action'] ?? '');
  $id     = (string) ($_POST['id'] ?? '');
  $return = (string) ($_POST['return'] ?? 'bookings.php');
  if (!preg_match('/^(index\.php|bookings\.php(\?[A-Za-z0-9=&%_.+-]*)?)$/', $return)) {
    $return = 'bookings.php';
  }

  if (!isset($rules[$action]) || $id === '') {
    flash_set('Aksi tidak dikenal.', 'err');
  } else {
    [$from, $to] = $rules[$action];
    if (update_booking_status($id, $from, $to)) {
      flash_set('Booking diubah menjadi ' . $to . '.');
    } else {
      flash_set('Booking tidak ditemukan atau statusnya sudah berubah. Muat ulang halaman.', 'err');
    }
  }
  header('Location: ' . $return);
  exit;
}

/* ---------- daftar + filter ---------- */
$all = load_bookings();
usort($all, fn($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

$counts = array_fill_keys(BOOKING_STATUSES, 0);
foreach ($all as $b) {
  if (isset($counts[$b['status'] ?? ''])) {
    $counts[$b['status']]++;
  }
}

$adminStatuses = array_values(array_diff(BOOKING_STATUSES, ['Menunggu']));
$status = (string) ($_GET['status'] ?? '');
if (!in_array($status, $adminStatuses, true)) {
  $status = '';
}
$q    = trim((string) ($_GET['q'] ?? ''));
$date = trim((string) ($_GET['date'] ?? ''));
if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  $date = '';
}

$rows = array_values(array_filter($all, function ($b) use ($status, $q, $date) {
  if ($status !== '' && ($b['status'] ?? '') !== $status) {
    return false;
  }
  if ($date !== '' && ($b['date'] ?? '') !== $date) {
    return false;
  }
  if ($q !== '') {
    $hay = strtolower(($b['code'] ?? '') . ' ' . ($b['username'] ?? '') . ' ' . implode(' ', (array) ($b['pc_names'] ?? [])));
    if (strpos($hay, strtolower($q)) === false) {
      return false;
    }
  }
  return true;
}));

$qs = array_filter(['status' => $status, 'q' => $q, 'date' => $date], fn($v) => $v !== '');
$self = 'bookings.php' . ($qs ? '?' . http_build_query($qs) : '');

$adminActive = 'bookings';
$adminTitle  = 'Booking';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="ad-head">
  <div>
    <h1>Kelola Booking</h1>
    <p>Booking baru otomatis dikonfirmasi setelah simulasi pembayaran. Booking yang disetujui dapat ditandai selesai atau dibatalkan.</p>
  </div>
</div>

<nav class="ad-tabs" aria-label="Filter status">
  <a class="ad-tab<?= $status === '' ? ' is-active' : '' ?>" href="bookings.php">Semua<b><?= count($all) ?></b></a>
  <?php foreach ($adminStatuses as $s): ?>
    <a class="ad-tab<?= $status === $s ? ' is-active' : '' ?>" href="bookings.php?status=<?= urlencode($s) ?>"><?= e($s) ?><b><?= $counts[$s] ?></b></a>
  <?php endforeach; ?>
</nav>

<form class="ad-filter" method="get">
  <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <label class="ad-field">Cari kode, pemesan, atau komputer<input type="search" name="q" value="<?= e($q) ?>" placeholder="mis. NZ-1A2B3C"></label>
  <label class="ad-field">Tanggal main<input type="date" name="date" value="<?= e($date) ?>"></label>
  <button class="ad-btn" type="submit">Terapkan</button>
  <?php if ($qs && ($q !== '' || $date !== '')): ?><a class="ad-btn ad-btn--ghost" href="bookings.php<?= $status !== '' ? '?status=' . urlencode($status) : '' ?>">Reset</a><?php endif; ?>
</form>

<div class="ad-table-wrap">
  <table class="ad-table">
    <thead><tr><th>Kode</th><th>Pemesan</th><th>Lokasi</th><th>Komputer</th><th>Jadwal</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $b): ?>
      <tr>
        <td><b><?= e((string) $b['code']) ?></b><small><?= e(date('d M H:i', strtotime((string) $b['created_at']))) ?></small></td>
        <td><?= e((string) $b['username']) ?><small><?= (int) $b['people'] ?> orang</small></td>
        <td><?= isset(BOOKING_LOCATIONS[$b['location'] ?? '']) ? e(BOOKING_LOCATIONS[$b['location']]['name']) . '<small>' . e(BOOKING_LOCATIONS[$b['location']]['address']) . '</small>' : 'Belum ditentukan' ?></td>
        <td><?= e((string) $b['type']) ?><small><?= e(implode(', ', (array) $b['pc_names'])) ?></small></td>
        <td><?= e(date('d M Y', strtotime((string) $b['date']))) ?><small><?= e((string) $b['time']) ?> · <?= (int) $b['duration'] ?> jam</small></td>
        <td><?= e(rupiah((int) $b['total'])) ?><small><?= e((string) $b['payment']) ?><?= !empty($b['payment_status']) ? ' · ' . e((string) $b['payment_status']) : '' ?></small></td>
        <td><?= admin_badge((string) $b['status']) ?></td>
        <td><?= booking_actions($b, $self) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="8" class="ad-empty"><?= $all ? 'Tidak ada booking yang cocok dengan filter.' : 'Belum ada booking masuk.' ?></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
