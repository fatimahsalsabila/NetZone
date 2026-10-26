<?php
require_once __DIR__ . '/data.php';
require_login();
$active = 'history';
$title  = 'NetZone · Riwayat';

$uid  = (string) ($_SESSION['netzone_user']['id'] ?? '');
$mine = array_values(array_filter(load_bookings(), fn($b) => ($b['user_id'] ?? '') === $uid));
usort($mine, fn($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head">
    <h1 class="history-page-title">
      <span class="history-page-title__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" focusable="false">
          <path d="M3.5 12a8.5 8.5 0 1 0 2.2-5.7L3.5 8.5" />
          <path d="M3.5 3.5v5h5M12 7v5l3.2 2" />
        </svg>
      </span>
      Riwayat Booking
    </h1>
    <p>Semua booking yang pernah kamu lakukan.</p>
  </div>

  <?php if ($mine): ?>
    <div class="history-list is-server" id="booking-history">
      <?php foreach ($mine as $b): ?>
        <article class="history-item">
          <div class="history-item__top"><b><?= e((string) $b['code']) ?></b><span class="status <?= e(status_class((string) $b['status'])) ?>"><?= e((string) $b['status']) ?></span></div>
          <p>Lokasi: <b><?= isset(BOOKING_LOCATIONS[$b['location'] ?? '']) ? e(BOOKING_LOCATIONS[$b['location']]['name'] . ' · ' . BOOKING_LOCATIONS[$b['location']]['address']) : 'Belum ditentukan' ?></b></p>
          <p><?= e((string) $b['type']) ?> · <?= e(implode(', ', (array) ($b['pc_names'] ?? []))) ?></p>
          <p><?= e(date('d M Y', strtotime((string) $b['date']))) ?> · <?= e((string) $b['time']) ?> · <?= (int) $b['duration'] ?> jam</p>
          <p><?= (int) $b['people'] ?> orang · <?= e((string) $b['payment']) ?></p>
          <?php if (!empty($b['payment_status'])): ?>
            <p>Status pembayaran: <b><?= e((string) $b['payment_status']) ?></b></p>
          <?php endif; ?>
          <strong><?= rupiah((int) $b['total']) ?></strong>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="notice">Kamu belum punya booking. Yuk pilih komputer pertamamu.</div>
  <?php endif; ?>

  <a class="btn btn--primary" href="booking.php">Booking Lagi</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
