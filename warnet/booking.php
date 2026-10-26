<?php
require_once __DIR__ . '/data.php';
require_login();
$active='booking';
$selectedType = strtolower((string) ($_GET['type'] ?? 'regular')) === 'vip' ? 'vip' : 'regular';
$selectedLocation = (string) ($_GET['location'] ?? '');
if (!isset(BOOKING_LOCATIONS[$selectedLocation])) {
  $selectedLocation = '';
}
$bookingErrors = [
  'location' => 'Pilih lokasi NetZone terlebih dahulu agar pesanan tercatat di cabang yang benar.',
  'pastdate' => 'Tanggal booking sudah lewat atau tidak valid.',
  'pasttime' => 'Jam booking sudah lewat atau tidak valid.',
  'payment' => 'Metode pembayaran tidak valid. Silakan ulangi proses booking.',
  'computers' => 'Komputer yang dipilih sudah tidak tersedia. Silakan pilih ulang.',
  'taken' => 'Komputer tersebut baru saja dipesan pada waktu yang sama. Silakan pilih ulang.',
  'save' => 'Booking belum bisa disimpan ke database. Periksa koneksi database lalu coba lagi.',
];
$bookingError = $bookingErrors[(string) ($_GET['error'] ?? '')] ?? '';
$title='NetZone · Booking';
require __DIR__ . '/includes/header.php';
?>
<main class="page booking-page">
  <div class="page-head">
    <h1 class="booking-page-title">
      <span class="booking-page-title__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" focusable="false">
          <rect x="3.5" y="5" width="17" height="16" rx="3" />
          <path d="M8 3v4M16 3v4M3.5 10h17M8 14h3m-3 3h6" />
          <path d="m15 16 1.5 1.5L20 14" />
        </svg>
      </span>
      Booking Komputer
    </h1>
    <p>Pilih lokasi cabang terlebih dahulu, lalu tentukan detail booking.</p>
  </div>

  <?php if ($bookingError !== ''): ?><div class="form-error"><?= e($bookingError) ?></div><?php endif; ?>

  <form class="form-card" onsubmit="return goToComputers(event)">
    <h2 class="form-title">1. Pilih Lokasi</h2>
    <div class="field">
      <label for="location">Lokasi NetZone</label>
      <select id="location" required>
        <option value="">-- Pilih lokasi cabang --</option>
        <?php foreach (BOOKING_LOCATIONS as $key => $location): ?>
          <option value="<?= e($key) ?>"<?= $selectedLocation === $key ? ' selected' : '' ?>>
            <?= e($location['name'] . ' — ' . $location['address']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small>Pastikan memilih cabang tempat kamu akan bermain.</small>
    </div>

    <h2 class="form-title">2. Pilih Kategori</h2>
    <div class="option-row">
      <label class="option">
        <input type="radio" name="type" value="regular"<?= $selectedType === 'regular' ? ' checked' : '' ?>>
        <span class="option__icon option__icon--regular" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 8h10M7 11h6"/></svg>
        </span>
        <strong>Regular</strong>
        <small><?= ($p = type_min_price('regular')) !== null ? 'Mulai dari ' . rupiah($p) . ' / jam / komputer' : 'Belum tersedia' ?></small>
      </label>
      <label class="option">
        <input type="radio" name="type" value="vip"<?= $selectedType === 'vip' ? ' checked' : '' ?>>
        <span class="option__icon option__icon--vip" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false"><path d="m3 8 4.5 4L12 5l4.5 7L21 8l-2 11H5L3 8Z"/><path d="M6 22h12"/></svg>
        </span>
        <strong>VIP</strong>
        <small><?= ($p = type_min_price('vip')) !== null ? 'Mulai dari ' . rupiah($p) . ' / jam / komputer' : 'Belum tersedia' ?></small>
      </label>
    </div>

    <h2 class="form-title">3. Detail Booking</h2>
    <div class="form-grid">
      <div class="field">
        <label for="date">Tanggal</label>
        <input id="date" type="date" required>
        <small>Tanggal yang sudah lewat tidak bisa dipilih.</small>
      </div>
      <div class="field">
        <label for="time">Waktu Mulai</label>
        <select id="time" required>
          <?php foreach (BOOKING_TIMES as $time): ?>
            <option value="<?= e($time) ?>"><?= e($time) ?></option>
          <?php endforeach; ?>
        </select>
        <small>Jam yang sudah lewat ditandai dan tidak bisa dipilih.</small>
      </div>
      <div class="field">
        <label for="duration">Durasi</label>
        <select id="duration" required>
          <option value="1">1 Jam</option><option value="2">2 Jam</option>
          <option value="3">3 Jam</option><option value="4">4 Jam</option>
        </select>
      </div>
      <div class="field">
        <label for="people">Jumlah Orang</label>
        <select id="people" required>
          <option value="1">1 Orang</option><option value="2">2 Orang</option>
          <option value="3">3 Orang</option><option value="4">4 Orang</option>
          <option value="5">5 Orang</option><option value="6">6 Orang</option>
          <option value="7">7 Orang</option><option value="8">8 Orang</option>
        </select>
      </div>
      <div class="field">
        <label for="computer_count">Jumlah Komputer</label>
        <select id="computer_count" required>
          <option value="1">1 Komputer</option><option value="2">2 Komputer</option>
          <option value="3">3 Komputer</option><option value="4">4 Komputer</option>
          <option value="5">5 Komputer</option><option value="6">6 Komputer</option>
          <option value="7">7 Komputer</option><option value="8">8 Komputer</option>
        </select>
        <small>Jumlah komputer tidak boleh lebih banyak dari jumlah orang.</small>
      </div>
      <div class="field">
        <label for="name">Nama Pemesan</label>
        <input id="name" value="<?= e($user['name']) ?>" readonly required maxlength="80">
      </div>
    </div>

    <div class="notice">Setelah klik <b>Lanjut Pilih Komputer</b>, pilih komputer sesuai jumlah yang kamu butuhkan.</div>
    <button class="btn btn--primary" type="submit">Lanjut Pilih Komputer →</button>
  </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>