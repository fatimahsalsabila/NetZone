<?php
require_once __DIR__ . '/../data.php';
require_admin();

/** Validasi input komputer. Mengembalikan [data|null, pesan error|null]. */
function read_computer_input(array $in): array
{
  $name   = trim((string) ($in['name'] ?? ''));
  $type   = (string) ($in['type'] ?? '');
  $price  = filter_var($in['price'] ?? '', FILTER_VALIDATE_INT);
  $status = (string) ($in['status'] ?? 'aktif');

  if ($name === '' || mb_strlen($name) > 40) {
    return [null, 'Nama komputer wajib diisi (maksimal 40 karakter).'];
  }
  if (!in_array($type, ['regular', 'vip'], true)) {
    return [null, 'Kategori harus Regular atau VIP.'];
  }
  if ($price === false || $price < 1000 || $price > 1000000) {
    return [null, 'Harga per jam harus berupa angka antara Rp 1.000 dan Rp 1.000.000.'];
  }
  if (!in_array($status, ['aktif', 'maintenance'], true)) {
    return [null, 'Status tidak valid.'];
  }
  return [['name' => $name, 'type' => $type, 'price' => $price, 'status' => $status], null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = (string) ($_POST['action'] ?? '');
  $id     = (int) ($_POST['id'] ?? 0);
  $fail   = null;
  $okMsg  = '';

  if ($action === 'add' || $action === 'update') {
    [$data, $err] = read_computer_input($_POST);
    if ($err) {
      $fail = $err;
    } else {
      $res = save_computer($action, $id, $data);
      if ($res === true) {
        $okMsg = $action === 'add' ? 'Komputer ' . $data['name'] . ' ditambahkan.' : 'Perubahan ' . $data['name'] . ' disimpan.';
      } else {
        $fail = is_string($res) ? $res : 'Komputer belum bisa disimpan ke database.';
      }
    }
  } elseif ($action === 'delete') {
    // Komputer yang masih punya booking aktif di hari ini atau setelahnya tidak boleh dihapus.
    $today = date('Y-m-d');
    $blocked = false;
    foreach (load_bookings() as $b) {
      if (in_array($b['status'] ?? '', BOOKING_ACTIVE, true) && ($b['date'] ?? '') >= $today && in_array($id, array_map('intval', (array) ($b['pc_ids'] ?? [])), true)) {
        $blocked = true;
        break;
      }
    }
    if ($blocked) {
      $fail = 'Komputer ini masih punya booking aktif. Batalkan atau selesaikan booking itu dulu, atau ubah statusnya menjadi maintenance.';
    } else {
      $res = delete_computer($id);
      if ($res === true) {
        $okMsg = 'Komputer dihapus.';
      } else {
        $fail = is_string($res) ? $res : 'Komputer tidak ditemukan atau belum bisa dihapus dari database.';
      }
    }
  } elseif ($action === 'type_price') {
    $type  = (string) ($_POST['type'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_INT);
    if (!in_array($type, ['regular', 'vip'], true) || $price === false || $price < 1000 || $price > 1000000) {
      $fail = 'Harga per jam harus berupa angka antara Rp 1.000 dan Rp 1.000.000.';
    } else {
      $res = save_category_price($type, $price);
      if ($res === true) {
        $okMsg = 'Harga semua komputer ' . ($type === 'vip' ? 'VIP' : 'Regular') . ' diubah menjadi ' . rupiah($price) . ' per jam.';
      } else {
        $fail = 'Harga belum bisa disimpan ke database.';
      }
    }
  } else {
    $fail = 'Aksi tidak dikenal.';
  }

  if ($fail !== null) {
    flash_set($fail, 'err');
  } else {
    flash_set($okMsg);
  }
  header('Location: computers.php');
  exit;
}

$computers = load_computers();
usort($computers, fn($a, $b) => [$a['type'], (int) $a['id']] <=> [$b['type'], (int) $b['id']]);

$adminActive = 'computers';
$adminTitle  = 'Komputer & Harga';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="ad-head">
  <div>
    <h1>Komputer &amp; Harga</h1>
  </div>
</div>

<div class="ad-grid ad-grid--2" style="margin-bottom:20px">
  <section class="ad-card">
    <h2>Tambah komputer</h2>
    <form method="post" class="ad-form-row">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <label class="ad-field">Nama<input name="name" maxlength="40" required placeholder="mis. VIP PC 05"></label>
      <label class="ad-field">Kategori<select name="type"><option value="regular">Regular</option><option value="vip">VIP</option></select></label>
      <label class="ad-field">Harga / jam<input name="price" type="number" min="1000" max="1000000" step="500" required placeholder="7000"></label>
      <label class="ad-field">Status<select name="status"><option value="aktif">Aktif</option><option value="maintenance">Maintenance</option></select></label>
      <button class="ad-btn" type="submit">Tambah</button>
    </form>
  </section>

  <section class="ad-card">
    <h2>Ubah harga satu kategori</h2>
    <?php foreach (['regular' => 'Regular', 'vip' => 'VIP'] as $t => $label): ?>
      <form method="post" class="ad-inline" style="align-items:end;margin-bottom:12px">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="type_price">
        <input type="hidden" name="type" value="<?= $t ?>">
        <label class="ad-field" style="flex:1;min-width:140px"><?= $label ?>, harga / jam<input name="price" type="number" min="1000" max="1000000" step="500" required value="<?= (int) (type_min_price($t) ?? 0) ?: '' ?>"></label>
        <button class="ad-btn ad-btn--ghost" type="submit" onclick="return confirm('Ubah harga semua komputer <?= $label ?>?')">Terapkan ke semua</button>
      </form>
    <?php endforeach; ?>
  </section>
</div>

<div class="ad-table-wrap">
  <table class="ad-table">
    <thead><tr><th>ID</th><th>Nama</th><th>Kategori</th><th>Harga / jam</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($computers as $c): $fid = 'pc-' . (int) $c['id']; ?>
      <tr>
        <td><?= (int) $c['id'] ?></td>
        <td><input form="<?= $fid ?>" name="name" value="<?= e((string) $c['name']) ?>" maxlength="40" required aria-label="Nama komputer <?= (int) $c['id'] ?>"></td>
        <td>
          <select form="<?= $fid ?>" name="type" aria-label="Kategori komputer <?= (int) $c['id'] ?>">
            <option value="regular"<?= $c['type'] === 'regular' ? ' selected' : '' ?>>Regular</option>
            <option value="vip"<?= $c['type'] === 'vip' ? ' selected' : '' ?>>VIP</option>
          </select>
        </td>
        <td><input form="<?= $fid ?>" name="price" type="number" min="1000" max="1000000" step="500" value="<?= (int) $c['price'] ?>" required aria-label="Harga per jam komputer <?= (int) $c['id'] ?>"></td>
        <td>
          <select form="<?= $fid ?>" name="status" aria-label="Status komputer <?= (int) $c['id'] ?>">
            <option value="aktif"<?= $c['status'] === 'aktif' ? ' selected' : '' ?>>Aktif</option>
            <option value="maintenance"<?= $c['status'] === 'maintenance' ? ' selected' : '' ?>>Maintenance</option>
          </select>
        </td>
        <td>
          <form id="<?= $fid ?>" method="post" class="ad-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button class="ad-btn ad-btn--sm" name="action" value="update">Simpan</button>
            <button class="ad-btn ad-btn--sm ad-btn--danger" name="action" value="delete" onclick="return confirm('Hapus komputer ini?')">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$computers): ?><tr><td colspan="6" class="ad-empty">Belum ada komputer. Tambahkan lewat form di atas.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
