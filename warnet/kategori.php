<?php
require_once __DIR__ . '/data.php';
$active='booking'; $title='NetZone · Kategori';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head"><h1>Pilih Kategori</h1><p>Pilih komputer yang sesuai dengan kebutuhanmu.</p></div>
  <div class="cards cards--categories">
    <article class="info-card category-card">
      <div class="category-card__visual">
        <img class="category-card__image" src="<?= BASE_URL ?>/assets/regular.png" alt="Ilustrasi kategori komputer Regular">
        <span class="category-card__badge">REGULAR</span>
      </div>
      <h3>Regular Computer</h3><p>Main nyaman, harga bersahabat.</p>
      <strong><?= ($p = type_min_price('regular')) !== null ? rupiah($p) . ' / jam' : 'Belum tersedia' ?></strong>
      <a class="btn btn--primary" href="booking.php?type=regular">Pilih Regular</a>
    </article>
    <article class="info-card category-card">
      <div class="category-card__visual">
        <img class="category-card__image" src="<?= BASE_URL ?>/assets/vip.png" alt="Ilustrasi kategori komputer VIP">
        <span class="category-card__badge category-card__badge--vip">VIP</span>
      </div>
      <h3>VIP Computer</h3><p>Performa lebih tinggi, gaming makin nyaman.</p>
      <strong><?= ($p = type_min_price('vip')) !== null ? rupiah($p) . ' / jam' : 'Belum tersedia' ?></strong>
      <a class="btn btn--primary" href="booking.php?type=vip">Pilih VIP</a>
    </article>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>