<?php
$active='home'; $title='NetZone · Lokasi';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head"><h1>Lokasi NetZone 📍</h1><p>Temukan lokasi dan jam operasional NetZone.</p></div>
  <div class="cards">
    <article class="info-card">
      <div class="icon-big"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></div><h3>NetZone Central</h3>
      <p>Lokasi utama NetZone untuk gaming, nugas, dan ngobrol.</p>
      <div class="location-detail"><b>Jl. Gaming No. 10</b><span>24 Jam · Setiap Hari</span></div>
      <a class="btn btn--primary" href="booking.php?location=central">Booking di sini</a>
    </article>
    <article class="info-card">
      <div class="icon-big"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></div><h3>NetZone Depok</h3>
      <p>Tempat gaming, nugas, dan berkumpul di kawasan Depok.</p>
      <div class="location-detail"><b>Jl. Margonda Gaming No. 18, Depok</b><span>24 Jam · Setiap Hari</span></div>
      <a class="btn btn--primary" href="booking.php?location=depok">Booking di sini</a>
    </article>
    <article class="info-card">
      <div class="icon-big"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></div><h3>NetZone Bogor</h3>
      <p>Tempat gaming, nugas, dan berkumpul di kawasan Bogor.</p>
      <div class="location-detail"><b>Jl. Gamer Hills No. 21, Bogor</b><span>24 Jam · Setiap Hari</span></div>
      <a class="btn btn--primary" href="booking.php?location=bogor">Booking di sini</a>
    </article>
    <article class="info-card">
      <div class="icon-big"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></div><h3>NetZone Tangerang</h3>
      <p>Tempat gaming, nugas, dan berkumpul di kawasan Tangerang.</p>
      <div class="location-detail"><b>Jl. Cyber Arena No. 8, Tangerang</b><span>24 Jam · Setiap Hari</span></div>
      <a class="btn btn--primary" href="booking.php?location=tangerang">Booking di sini</a>
    </article>
    <article class="info-card">
      <div class="icon-big"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></div><h3>NetZone Bekasi</h3>
      <p>Tempat gaming, nugas, dan berkumpul di kawasan Bekasi.</p>
      <div class="location-detail"><b>Jl. Galaxy Utama No. 25, Bekasi</b><span>24 Jam · Setiap Hari</span></div>
      <a class="btn btn--primary" href="booking.php?location=bekasi">Booking di sini</a>
    </article>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>