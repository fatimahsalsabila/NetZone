<?php
require_once __DIR__ . '/../data.php';
require_admin();

$bookings  = load_bookings();
$computers = load_computers();
$today     = date('Y-m-d');

$cancelledCount = 0;
$todayCount = 0;
$revenue = 0;
foreach ($bookings as $b) {
  $s = (string) ($b['status'] ?? '');
  if ($s === 'Dibatalkan') {
    $cancelledCount++;
  }
  if (($b['date'] ?? '') === $today && in_array($s, ['Disetujui', 'Selesai'], true)) {
    $todayCount++;
  }
  if ($s === 'Disetujui' || $s === 'Selesai') {
    $revenue += (int) ($b['total'] ?? 0);
  }
}
$pcActive = count(array_filter($computers, fn($c) => ($c['status'] ?? '') === 'aktif'));

// Jadwal aktif untuk 7 hari ke depan; booking tertunda dan dibatalkan tidak ditampilkan.
$days = [];
for ($i = 0; $i < 7; $i++) {
  $d = date('Y-m-d', strtotime("$today +$i day"));
  $n = 0;
  foreach ($bookings as $b) {
    if (($b['date'] ?? '') === $d && in_array($b['status'] ?? '', ['Disetujui', 'Selesai'], true)) {
      $n++;
    }
  }
  $days[] = ['date' => $d, 'n' => $n];
}
$maxN = max(1, max(array_column($days, 'n')));

$cancelledBookings = array_values(array_filter($bookings, fn($b) => ($b['status'] ?? '') === 'Dibatalkan'));
usort($cancelledBookings, fn($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
$cancelledBookings = array_slice($cancelledBookings, 0, 6);

$todayList = array_values(array_filter($bookings, fn($b) => ($b['date'] ?? '') === $today && in_array($b['status'] ?? '', ['Disetujui', 'Selesai'], true)));
usort($todayList, fn($a, $b) => strcmp($a['time'], $b['time']));

$adminActive = 'dashboard';
$adminTitle  = 'Dashboard';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="ad-head">
  <div>
    <h1>Dashboard</h1>
    <p><?= e(date('d M Y')) ?> · ringkasan booking NetZone</p>
  </div>
  <a class="ad-btn" href="bookings.php?status=Dibatalkan">Lihat booking dibatalkan</a>
</div>

<section class="ad-kpis" aria-label="Ringkasan">
  <div class="ad-kpi"><span>Booking dibatalkan</span><b><?= $cancelledCount ?></b><small>total booking berstatus dibatalkan</small></div>
  <div class="ad-kpi"><span>Booking hari ini</span><b><?= $todayCount ?></b><small>tidak termasuk yang dibatalkan</small></div>
  <div class="ad-kpi"><span>Pendapatan</span><b><?= e(rupiah($revenue)) ?></b><small>booking disetujui dan selesai (demo)</small></div>
  <div class="ad-kpi"><span>Komputer aktif</span><b><?= $pcActive ?> / <?= count($computers) ?></b><small>sisanya maintenance</small></div>
</section>

<div class="ad-grid ad-grid--2">
  <section class="ad-card">
    <h2>Booking yang dibatalkan</h2>
    <?php if ($cancelledBookings): ?>
      <div class="ad-table-wrap">
        <table class="ad-table">
          <thead><tr><th>Pemesan</th><th>Jadwal</th><th>Komputer</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($cancelledBookings as $b): ?>
            <tr>
              <td><b><?= e((string) $b['username']) ?></b><small><?= e((string) $b['code']) ?></small></td>
              <td><?= e(date('d M', strtotime((string) $b['date']))) ?>, <?= e((string) $b['time']) ?><small><?= (int) $b['duration'] ?> jam</small></td>
              <td><?= e(implode(', ', (array) $b['pc_names'])) ?></td>
              <td><?= e(rupiah((int) $b['total'])) ?></td>
              <td><?= admin_badge((string) $b['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="ad-empty">Belum ada booking yang dibatalkan.</p>
    <?php endif; ?>
  </section>

  <section class="ad-card">
    <h2>Booking 7 hari ke depan</h2>
    <div class="ad-bars" role="img" aria-label="Jumlah booking per hari selama 7 hari ke depan">
      <?php foreach ($days as $i => $d): ?>
        <div class="ad-bar<?= $i === 0 ? ' ad-bar--today' : '' ?>">
          <b><?= $d['n'] ?></b>
          <i style="height:<?= max(2, (int) round($d['n'] / $maxN * 100)) ?>%"></i>
          <span><?= $i === 0 ? 'Hari ini' : e(date('d/m', strtotime($d['date']))) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<section class="ad-card" style="margin-top:20px">
  <h2>Jadwal hari ini</h2>
  <?php if ($todayList): ?>
    <div class="ad-table-wrap">
      <table class="ad-table">
        <thead><tr><th>Jam</th><th>Pemesan</th><th>Komputer</th><th>Durasi</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($todayList as $b): ?>
          <tr>
            <td><b><?= e((string) $b['time']) ?></b></td>
            <td><?= e((string) $b['username']) ?><small><?= (int) $b['people'] ?> orang</small></td>
            <td><?= e(implode(', ', (array) $b['pc_names'])) ?></td>
            <td><?= (int) $b['duration'] ?> jam</td>
            <td><?= admin_badge((string) $b['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="ad-empty">Belum ada booking untuk hari ini.</p>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
