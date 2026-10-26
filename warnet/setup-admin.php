<?php
/**
 * Membuat akun admin pertama.
 * - Hanya bisa dibuka dari komputer yang sama (localhost).
 * - Otomatis terkunci begitu sudah ada satu akun admin.
 * Setelah selesai, file ini boleh dihapus.
 */
require_once __DIR__ . '/data.php';

$local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$users = load_users();
$hasAdmin = false;
foreach ($users as $u) {
  if (($u['role'] ?? '') === 'admin') {
    $hasAdmin = true;
    break;
  }
}

$error = '';
$done  = false;

if ($local && !$hasAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $email    = trim($_POST['email'] ?? '');
  $phone    = trim($_POST['phone'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Masukkan alamat email yang valid.';
  } elseif (!preg_match('/^[0-9+() -]{8,20}$/', $phone)) {
    $error = 'Masukkan nomor telepon yang valid.';
  } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,24}$/', $username)) {
    $error = 'Username 3 sampai 24 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.';
  } elseif (!valid_password($password)) {
    $error = 'Password minimal 8 karakter, dengan 1 huruf kapital, 1 angka, dan 1 simbol.';
  } else {
    foreach ($users as $u) {
      if (strcasecmp($u['email'] ?? '', $email) === 0 || ($u['phone'] ?? '') === $phone || strcasecmp($u['username'] ?? '', $username) === 0) {
        $error = 'Email, nomor telepon, atau username itu sudah dipakai akun lain.';
        break;
      }
    }
  }

  if ($error === '') {
    $users[] = [
      'id'         => bin2hex(random_bytes(8)),
      'email'      => $email,
      'phone'      => $phone,
      'username'   => $username,
      'password'   => password_hash($password, PASSWORD_DEFAULT),
      'role'       => 'admin',
      'created_at' => date('c'),
    ];
    if (save_users($users)) {
      $done = true;
      $hasAdmin = true;
    } else {
      $error = 'Akun admin belum bisa disimpan ke database. Periksa koneksi database lalu coba lagi.';
    }
  }
}

$title    = 'NetZone · Setup Admin';
$hideNav  = true;
require __DIR__ . '/includes/header.php';
?>
<main class="page auth-page">
  <div class="auth-layout" style="grid-template-columns:1fr;max-width:520px;margin:0 auto">
    <section class="auth-card">
      <h1>Setup Admin</h1>

      <?php if (!$local): ?>
        <div class="form-error">Halaman ini hanya bisa dibuka dari komputer server (localhost).</div>
      <?php elseif ($done): ?>
        <div class="notice">Akun admin dibuat. Silakan <a href="login.php"><b>login</b></a>; kamu akan langsung masuk ke dashboard admin. File <code>setup-admin.php</code> boleh dihapus sekarang.</div>
      <?php elseif ($hasAdmin): ?>
        <div class="notice">Akun admin sudah ada, jadi halaman ini terkunci. <a href="login.php"><b>Login di sini</b></a>.</div>
      <?php else: ?>
        <p>Buat akun admin pertama untuk mengelola booking, komputer, dan harga.</p>
        <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" class="auth-form">
          <?= csrf_field() ?>
          <div class="field"><label for="username">Username</label><input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autocomplete="username"></div>
          <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
          <div class="field"><label for="phone">Nomor Telepon</label><input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required></div>
          <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="new-password"><small>Minimal 8 karakter, 1 huruf kapital, 1 angka, 1 simbol.</small></div>
          <button type="submit" class="btn btn--primary">Buat Admin</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
