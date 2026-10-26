<?php
require_once __DIR__ . '/data.php';
require_login();

$active = '';
$title  = 'NetZone · Notifikasi';
$uid    = (string) ($_SESSION['netzone_user']['id'] ?? '');
$notifications = array_values(array_filter(
  load_bookings(),
  fn($booking) => (string) ($booking['user_id'] ?? '') === $uid
));

usort($notifications, function ($a, $b) {
  $aTime = strtotime((string) ($a['updated_at'] ?? $a['created_at'] ?? '')) ?: 0;
  $bTime = strtotime((string) ($b['updated_at'] ?? $b['created_at'] ?? '')) ?: 0;
  return $bTime <=> $aTime;
});

$messages = [
  'Menunggu'   => 'Booking kamu masih tertunda. Silakan hubungi NetZone jika perlu bantuan.',
  'Disetujui'  => 'Pembayaran simulasi berhasil dan booking kamu sudah dikonfirmasi. Tidak ada uang sungguhan yang ditransfer.',
  'Dibatalkan' => 'Booking kamu dibatalkan. Lihat riwayat untuk detailnya.',
  'Selesai'    => 'Booking kamu telah selesai. Terima kasih sudah bermain di NetZone!',
];

require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head">
    <h1>Notifikasi 🔔</h1>
    <p>Informasi terbaru tentang booking kamu.</p>
  </div>

  <?php if ($notifications): ?>
    <section class="notification-list" aria-label="Daftar notifikasi">
      <?php foreach ($notifications as $booking): ?>
        <?php
          $status = (string) ($booking['status'] ?? 'Menunggu');
          $timestamp = strtotime((string) ($booking['updated_at'] ?? $booking['created_at'] ?? ''));
          $when = $timestamp ? date('d M Y · H:i', $timestamp) : 'Waktu tidak diketahui';
        ?>
        <article class="notification-item">
          <span class="notification-item__icon" aria-hidden="true">🔔</span>
          <div class="notification-item__content">
            <div class="notification-item__top">
              <h2>Booking <?= e((string) ($booking['code'] ?? '-')) ?></h2>
              <span class="status <?= e(status_class($status)) ?>"><?= e($status) ?></span>
            </div>
            <p><?= e($messages[$status] ?? 'Ada pembaruan pada booking kamu.') ?></p>
            <time><?= e($when) ?></time>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
    <a class="btn btn--primary notification-history-link" href="<?= BASE_URL ?>/riwayat.php">Lihat Riwayat Booking</a>
  <?php else: ?>
    <div class="notification-empty">
      <span aria-hidden="true">🔔</span>
      <h2>Belum ada notifikasi</h2>
      <p>Notifikasi booking kamu akan muncul di sini.</p>
      <a class="btn btn--primary" href="<?= BASE_URL ?>/booking.php">Booking Sekarang</a>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
