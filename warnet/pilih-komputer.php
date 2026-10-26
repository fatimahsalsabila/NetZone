<?php
require_once __DIR__ . '/data.php';
require_login();
$active = 'booking';

$p = booking_params($_GET);
if ($p['location'] === '') {
  header('Location: booking.php?error=location');
  exit;
}
if ($p['date'] === '') {
  header('Location: booking.php?type=' . urlencode($p['type']));
  exit;
}
if (!booking_date_ok($p['date'])) {
  header('Location: booking.php?error=pastdate');
  exit;
}
if (!booking_time_ok($p['date'], $p['time'])) {
  header('Location: booking.php?error=pasttime');
  exit;
}
$name = trim($_GET['name'] ?? $user['name']);

// Komputer dikelola admin; komputer yang sudah dibooking di jam yang sama ditandai.
$all    = array_values(array_filter(load_computers(), fn($c) => ($c['type'] ?? '') === $p['type']));
$taken  = booked_computer_ids($p['date'], $p['time'], $p['duration']);
$free   = 0;
foreach ($all as $c) {
  if (($c['status'] ?? '') === 'aktif' && !in_array((int) $c['id'], $taken, true)) {
    $free++;
  }
}
$notEnough = $free < $p['count'];

$title = 'NetZone · Pilih Komputer';
require __DIR__ . '/includes/header.php';
?>
<main class="page pc-selection-page pc-selection-page--<?= e($p['type']) ?>">
  <div class="page-head">
    <h1 class="booking-page-title">
      <span class="booking-page-title__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" focusable="false">
          <rect x="3" y="4" width="18" height="13" rx="2" />
          <path d="M8 21h8m-4-4v4M7 8h10m-10 3h6" />
        </svg>
      </span>
      Pilih Komputer
    </h1>
    <p>Lokasi <b><?= e(BOOKING_LOCATIONS[$p['location']]['name']) ?></b> · Kategori <b><?= e(ucfirst($p['type'])) ?></b> · pilih <?= $p['count'] ?> komputer untuk <?= $p['people'] ?> orang · <?= e(date('d M Y', strtotime($p['date']))) ?>, <?= e($p['time']) ?> (<?= $p['duration'] ?> jam).</p>
  </div>
  <form class="form-card pc-selection-card" action="konfirmasi.php" method="get" onsubmit="return validatePcSelection(event, <?= $p['count'] ?>)">
    <input type="hidden" name="location" value="<?= e($p['location']) ?>">
    <input type="hidden" name="type" value="<?= e($p['type']) ?>">
    <input type="hidden" name="date" value="<?= e($p['date']) ?>">
    <input type="hidden" name="time" value="<?= e($p['time']) ?>">
    <input type="hidden" name="duration" value="<?= $p['duration'] ?>">
    <input type="hidden" name="people" value="<?= $p['people'] ?>">
    <input type="hidden" name="computer_count" value="<?= $p['count'] ?>">
    <input type="hidden" name="name" value="<?= e($name) ?>">

    <?php if ($notEnough): ?>
      <div class="form-error">Komputer yang tersedia di jam ini hanya <?= $free ?>, sedangkan kamu butuh <?= $p['count'] ?>. Coba ganti jam, tanggal, atau kategori.</div>
    <?php else: ?>
      <div class="notice pc-selection-notice">Pilih tepat <?= $p['count'] ?> komputer. Komputer berwarna abu-abu sudah dipesan atau sedang maintenance.</div>
    <?php endif; ?>

    <div class="pc-grid pc-grid--select">
    <?php $displayNumber = 0; foreach ($all as $c):
      $displayNumber++;
      $id = (int) $c['id'];
      $isBooked = in_array($id, $taken, true);
      $isDown   = ($c['status'] ?? '') !== 'aktif';
      $disabled = $isBooked || $isDown;
    ?>
      <label class="pc-card pc-card--select<?= $disabled ? ' is-disabled' : '' ?>">
        <input class="pc-checkbox" type="checkbox" name="pcs[]" value="<?= $id ?>"<?= $disabled ? ' disabled' : '' ?>>
        <div class="pc-screen"><span><?= e(sprintf('PC %02d', $displayNumber)) ?></span></div>
        <div class="pc-body">
          <div>
            <?php if ($isDown): ?><span class="status-dot status-dot--off"></span> Maintenance
            <?php elseif ($isBooked): ?><span class="status-dot status-dot--off"></span> Sudah dipesan
            <?php else: ?><span class="status-dot"></span> Tersedia<?php endif; ?>
          </div>
          <h3><?= e((string) $c['name']) ?></h3>
          <p><?= rupiah((int) $c['price']) ?> / jam</p>
          <span class="pc-select-label"><?= $disabled ? 'Tidak tersedia' : 'Pilih komputer ini' ?></span>
        </div>
      </label>
    <?php endforeach; ?>
    <?php if (!$all): ?><p>Belum ada komputer untuk kategori ini.</p><?php endif; ?>
    </div>
    <button class="btn btn--primary" type="submit"<?= $notEnough ? ' disabled' : '' ?>>Lanjut ke Konfirmasi →</button>
  </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
