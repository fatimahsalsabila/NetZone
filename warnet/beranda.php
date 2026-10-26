<?php
require_once __DIR__ . '/data.php';
require_login();
$active = 'home';
$title  = 'NetZone · Beranda';
require __DIR__ . '/includes/header.php';
?>
<main class="home">
  <section class="greeting">
    <div>
      <p>Selamat datang di NetZone!</p>
      <h1>Halo, Gamer! 👋🏻</h1>
    </div>
    <label class="search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
      <input type="search" placeholder="Cari Kebutuhanmu..." aria-label="Cari">
    </label>
  </section>

  <section class="banner">
    <div class="banner__copy">
      <h2>READY TO PLAY?</h2>
      <p>Saatnya pilih PC dan mulai keseruanmu!</p>
      <a class="btn btn--yellow" href="booking.php">Booking Sekarang</a>
    </div>
  </section>

  <section class="quick" aria-label="Pintasan">
    <?php foreach ($quickActions as [$name, $sub, $icon, $bg, $href]): ?>
      <a class="quick__item" href="<?= e($href) ?>">
        <span class="tile" style="--bg:<?= e($bg) ?>"><?= $icon ?></span>
        <span><b><?= e($name) ?></b><small><?= e($sub) ?></small></span>
      </a>
    <?php endforeach; ?>
  </section>

  <section id="pilih-komputer">
    <div class="section-head"><h2>Pilih Komputer</h2><a href="kategori.php">Lihat Semua &nbsp;→</a></div>
    <article class="featured">
      <div class="featured__img"><img src="<?= BASE_URL ?>/assets/komputer%20reg.png" alt="Area komputer Regular NetZone"></div>
      <div class="featured__info">
        <span class="chip"><?= e($featured['type']) ?></span>
        <h3><?= e($featured['title']) ?></h3>
        <p><?= e($featured['desc']) ?></p>
        <p class="rating">⭐ <?= number_format($featured['rating'], 1) ?> &nbsp;(<?= (int) $featured['reviews'] ?>)</p>
        <p class="price">Mulai dari <?= rupiah($featured['price']) ?> / Jam</p>
      </div>
      <a class="btn btn--primary" href="<?= e($featured['link']) ?>">Pilih Komputer</a>
    </article>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
