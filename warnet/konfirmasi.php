<?php
require_once __DIR__ . '/data.php';
require_login();
$active = 'booking';

$p = booking_params($_GET);
if ($p['location'] === '') {
  header('Location: booking.php?error=location');
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
[$selected, $err] = resolve_booking_pcs($p, load_computers());
if ($err !== null) {
  header('Location: booking.php?error=' . $err);
  exit;
}

$total     = booking_total($selected, $p['duration']);
$typeLabel = $p['type'] === 'vip' ? 'VIP' : 'Regular';
$pcLabel   = implode(', ', array_map(fn($c) => (string) $c['name'], $selected));
$name      = $user['name'];

$title = 'NetZone · Konfirmasi';
require __DIR__ . '/includes/header.php';
?>
<main class="page confirmation-page">
  <div class="page-head">
    <h1 class="booking-page-title">
      <span class="booking-page-title__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" focusable="false">
          <rect x="3.5" y="5" width="17" height="16" rx="3" />
          <path d="M8 3v4M16 3v4M3.5 10h17M8 14h3m-3 3h4" />
          <path d="m15 16 1.5 1.5L20 14" />
        </svg>
      </span>
      Konfirmasi Booking
    </h1>
    <p>Cek semua detail sebelum mengonfirmasi pembayaran.</p>
  </div>
  <div class="form-card confirmation">
    <div class="summary-row"><span>Nama Pemesan</span><b><?= e($name) ?></b></div>
    <div class="summary-row"><span>Lokasi</span><b><?= e(BOOKING_LOCATIONS[$p['location']]['name']) ?> · <?= e(BOOKING_LOCATIONS[$p['location']]['address']) ?></b></div>
    <div class="summary-row"><span>Kategori</span><b><?= e($typeLabel) ?></b></div>
    <div class="summary-row"><span>Tanggal</span><b><?= e(date('d M Y', strtotime($p['date']))) ?></b></div>
    <div class="summary-row"><span>Waktu Mulai</span><b><?= e($p['time']) ?></b></div>
    <div class="summary-row"><span>Durasi</span><b><?= $p['duration'] ?> jam</b></div>
    <div class="summary-row"><span>Jumlah Orang</span><b><?= $p['people'] ?> orang</b></div>
    <div class="summary-row"><span>Jumlah Komputer</span><b><?= $p['count'] ?> komputer</b></div>
    <div class="summary-row"><span>Komputer Dipilih</span><b><?= e($pcLabel) ?></b></div>
    <?php foreach ($selected as $c): ?>
      <div class="summary-row"><span><?= e((string) $c['name']) ?> / jam</span><b><?= rupiah((int) $c['price']) ?></b></div>
    <?php endforeach; ?>
    <div class="summary-row summary-total"><span>Total Pembayaran</span><b><?= rupiah($total) ?></b></div>

    <div class="field payment-field">
      <label for="payment">Metode Pembayaran</label>
      <select id="payment" required>
        <option value="">-- Pilih metode pembayaran --</option>
        <?php foreach (PAYMENT_METHODS as $method): ?>
          <option value="<?= e($method) ?>"><?= e($method) ?></option>
        <?php endforeach; ?>
      </select>
      <small id="payment-guide" class="payment-guide" aria-live="polite">Pilih metode untuk melihat detail pembayaran.</small>
    </div>
    <section id="payment-details" class="payment-details" aria-live="polite" hidden>
      <div id="qris-demo" class="payment-demo" hidden>
        <h2>QRIS</h2>
        <img class="fake-qr" src="<?= BASE_URL ?>/assets/qr%20qris.jpg" alt="Gambar QRIS untuk keperluan demo proyek">
    
      </div>
      <div id="va-demo" class="payment-demo" hidden>
        <h2 id="va-bank">Virtual Account</h2>
        <p>Nomor Virtual Account contoh</p>
        <strong id="va-number" class="va-number"></strong>
      </div>
      <div class="payment-demo-total">Total pembayaran <b><?= rupiah($total) ?></b></div>
    </section>
    <div class="notice">Pembayaran akan otomatis berhasil dan booking langsung dikonfirmasi.</div>
    <button class="btn btn--primary" id="open-confirm-modal" type="button">Konfirmasi Pembayaran</button>
    <a class="btn btn--secondary" href="javascript:history.back()">← Kembali / Edit</a>
  </div>
</main>

<form id="booking-form" action="simpan-booking.php" method="post" hidden>
  <?= csrf_field() ?>
  <input type="hidden" name="location" value="<?= e($p['location']) ?>">
  <input type="hidden" name="type" value="<?= e($p['type']) ?>">
  <input type="hidden" name="date" value="<?= e($p['date']) ?>">
  <input type="hidden" name="time" value="<?= e($p['time']) ?>">
  <input type="hidden" name="duration" value="<?= $p['duration'] ?>">
  <input type="hidden" name="people" value="<?= $p['people'] ?>">
  <input type="hidden" name="computer_count" value="<?= $p['count'] ?>">
  <?php foreach ($p['pcs'] as $id): ?><input type="hidden" name="pcs[]" value="<?= (int) $id ?>"><?php endforeach; ?>
  <input type="hidden" name="payment" value="">
</form>

<?php require __DIR__ . '/includes/booking-confirm-modal.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
