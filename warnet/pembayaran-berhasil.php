<?php
require_once __DIR__ . '/data.php';
require_login();

$code = trim((string) ($_GET['code'] ?? ''));
if ($code === '') {
  header('Location: riwayat.php');
  exit;
}

$active = 'history';
$title = 'NetZone · Pembayaran Berhasil';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="modal-backdrop is-open payment-success-modal" id="payment-success-modal" aria-hidden="false">
    <section class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="payment-success-title">
      <div class="payment-success-icon" aria-hidden="true">✓</div>
      <h1 id="payment-success-title">Pembayaran Berhasil!</h1>
      <p>Booking <b><?= e($code) ?></b> sudah dikonfirmasi. Pesanan kamu sudah tersimpan.</p>
      <a class="btn btn--primary" href="riwayat.php">Lihat Pesanan</a>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
