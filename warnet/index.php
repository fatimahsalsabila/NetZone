<?php
require_once __DIR__ . '/data.php';

// Landing page ini untuk pengunjung yang belum login.
if (is_admin()) {
  header('Location: admin/index.php');
  exit;
}
if (!empty($_SESSION['netzone_user'])) {
  header('Location: beranda.php');
  exit;
}

$title    = 'NetZone · Booking PC untuk Gaming, Nugas, Ngobrol';
$hideNav  = true;
$extraCss = [BASE_URL . '/css/landing.css?v=3'];

$regPrice = type_min_price('regular');
$vipPrice = type_min_price('vip');
$regCount = type_active_count('regular');
$vipCount = type_active_count('vip');

require __DIR__ . '/includes/header.php';
?>
<div class="lp">

  <section class="lp-hero">
    <header class="lp-nav">
      <a class="lp-nav__brand" href="index.php" aria-label="NetZone">
        <img src="<?= BASE_URL ?>/assets/logo-netzone.png" alt="NetZone">
      </a>
      <nav class="lp-nav__links" aria-label="Bagian halaman">
        <a href="#cara">Cara booking</a>
        <a href="#kategori">Kategori</a>
        <a href="#lokasi">Lokasi</a>
      </nav>
      <div class="lp-nav__auth">
        <a class="lp-nav__login" href="login.php">Login</a>
        <a class="lp-btn lp-btn--yellow lp-btn--sm" href="signup.php">Sign Up</a>
      </div>
    </header>

    <div class="lp-hero__copy">
      <h1>Ready to Play?
          Welcome to NetZone</h1>
      <p class="lp-hero__lead">Booking PC NetZone dari mana saja. Pilih kategori, jam, dan komputernya, lalu datang dan langsung main.</p>
      <div class="lp-cta">
        <a class="lp-btn lp-btn--yellow" href="signup.php">Booking Sekarang</a>
        <a class="lp-btn lp-btn--ghost" href="login.php">Sudah punya akun? Login</a>
      </div>
      <p class="lp-hero__note">
        <strong>Main Kapan Aja, Gas Terus!</strong><br>
        Buka 24 jam setiap hari — Regular mulai <?= $regPrice !== null ? e(str_replace('Rp ', 'Rp', rupiah($regPrice))) : 'Segera' ?>/jam, VIP mulai <?= $vipPrice !== null ? e(str_replace('Rp ', 'Rp', rupiah($vipPrice))) : 'Segera' ?>/jam.
      </p>
    </div>
  </section>

  <section class="lp-section" id="cara">
    <div class="lp-wrap">
      <h2 class="lp-title">Booking dalam empat langkah</h2>
      <ol class="lp-steps">
        <li>
          <span class="lp-steps__no">1</span>
          <h3>Buat akun atau login</h3>
          <p>Cukup email, nomor telepon, dan username. Kamu juga bisa login pakai salah satunya.</p>
        </li>
        <li>
          <span class="lp-steps__no">2</span>
          <h3>Atur jadwalmu</h3>
          <p>Pilih Regular atau VIP, lalu tanggal, jam mulai, durasi, dan jumlah orang.</p>
        </li>
        <li>
          <span class="lp-steps__no">3</span>
          <h3>Pilih komputer</h3>
          <p>Komputer yang sudah dipesan di jam yang sama langsung ditandai, jadi tidak ada yang rebutan.</p>
        </li>
        <li>
          <span class="lp-steps__no">4</span>
          <h3>Konfirmasi</h3>
          <p>Konfirmasi pembayaran demo dan booking langsung dikonfirmasi. Tidak ada transaksi uang sungguhan.</p>
        </li>
      </ol>
    </div>
  </section>

  <section class="lp-section lp-section--tint" id="kategori">
    <div class="lp-wrap">
      <h2 class="lp-title lp-title--category">Pilih sesuai gayamu main</h2>
      <div class="lp-tiers">
        <article class="lp-tier">
          <h3>Regular</h3>
          <p>Main nyaman, harga bersahabat.</p>
          <p class="lp-tier__price">
            <?= $regPrice !== null ? e(rupiah($regPrice)) : 'Segera' ?>
            <?php if ($regPrice !== null): ?><span>/ jam</span><?php endif; ?>
          </p>
          <p class="lp-tier__meta"><?= $regCount ?> komputer siap dipesan</p>
          <a class="lp-btn lp-btn--solid" href="login.php">Pilih Regular</a>
        </article>
        <article class="lp-tier lp-tier--vip">
          <h3>VIP</h3>
          <p>Performa lebih tinggi, gaming makin nyaman.</p>
          <p class="lp-tier__price">
            <?= $vipPrice !== null ? e(rupiah($vipPrice)) : 'Segera' ?>
            <?php if ($vipPrice !== null): ?><span>/ jam</span><?php endif; ?>
          </p>
          <p class="lp-tier__meta"><?= $vipCount ?> komputer siap dipesan</p>
          <a class="lp-btn lp-btn--yellow" href="login.php">Pilih VIP</a>
        </article>
      </div>
    </div>
  </section>

  <section class="lp-section" id="lokasi">
    <div class="lp-wrap lp-place">
      <div>
        <h2 class="lp-title">NetZone Central</h2>
        <p class="lp-place__text">Dekat area kampus dan mudah dijangkau, baik dengan kendaraan umum maupun pribadi.</p>
      </div>
      <dl class="lp-place__facts">
        <div><dt>Alamat</dt><dd>Jl. Gaming No. 10</dd></div>
        <div><dt>Jam buka</dt><dd>Senin - Minggu, 24 jam</dd></div>
      </dl>
    </div>
  </section>

  <section class="lp-section lp-section--last">
    <div class="lp-wrap">
      <div class="lp-final">
        <h2>Siap main hari ini?</h2>
        <p>Daftar gratis dan amankan komputermu sebelum keduluan.</p>
        <a class="lp-btn lp-btn--yellow" href="signup.php">Buat Akun</a>
      </div>
    </div>
  </section>

</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
