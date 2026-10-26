<?php
require_once __DIR__.'/data.php';
$error=''; $message='';
if($_SERVER['REQUEST_METHOD']==='POST') {
  $step=$_POST['step']??'request';
  if($step==='request') {
    $identity=trim($_POST['identity']??''); $users=load_users(); $found=null;
    foreach($users as $i=>$u) if(strcasecmp($u['email'],$identity)===0 || $u['phone']===$identity) { $found=$i; break; }
    if($found===null) $error='Akun tidak ditemukan. Periksa email atau nomor telepon yang terdaftar.';
    else {
      // Local demo only: code is stored in session and shown on screen because no email/SMS provider is configured.
      $_SESSION['reset_user_index']=$found; $_SESSION['reset_code']=(string)random_int(100000,999999); $_SESSION['reset_expires']=time()+600;
      $message='Mode demo lokal: kode verifikasi kamu adalah '.$_SESSION['reset_code'].'. Kode berlaku 10 menit. Untuk penggunaan sungguhan, hubungkan layanan email/SMS.';
    }
  } elseif($step==='reset') {
    $code=trim($_POST['code']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
    if(empty($_SESSION['reset_code']) || time()>($_SESSION['reset_expires']??0) || !hash_equals((string)$_SESSION['reset_code'],$code)) $error='Kode verifikasi salah atau sudah kedaluwarsa. Ulangi prosesnya.';
    elseif(!valid_password($password)) $error='Password minimal 8 karakter dan harus memiliki 1 huruf kapital, 1 angka, serta 1 simbol.';
    elseif($password!==$confirm) $error='Konfirmasi password belum sama.';
    else {
      $users=load_users(); $idx=(int)($_SESSION['reset_user_index']??-1);
      if(!isset($users[$idx])) $error='Akun tidak ditemukan.';
      else {
        $users[$idx]['password']=password_hash($password,PASSWORD_DEFAULT);
        if(save_users($users)) {
          unset($_SESSION['reset_code'],$_SESSION['reset_expires'],$_SESSION['reset_user_index']);
          $message='Password berhasil diganti. Silakan login dengan password baru.';
        } else {
          $error='Password baru belum bisa disimpan ke database. Periksa koneksi database lalu coba lagi.';
        }
      }
    }
  }
}
$title='NetZone · Lupa Password'; require __DIR__.'/includes/header.php';
?>
<main class="page auth-page"><section class="auth-card"><div class="auth-brand">🔐 NETZONE</div><h1>Lupa Password?</h1><p>Verifikasi akun dengan email atau nomor telepon yang terdaftar.</p>
<?php if($error): ?><div class="form-error"><?=e($error)?></div><?php endif; ?><?php if($message): ?><div class="notice"><?=e($message)?></div><?php endif; ?>
<?php if(!empty($_SESSION['reset_code']) && time()<=($_SESSION['reset_expires']??0)): ?><form method="post" class="auth-form"><input type="hidden" name="step" value="reset"><div class="field"><label for="code">Kode Verifikasi</label><input id="code" name="code" inputmode="numeric" maxlength="6" required placeholder="6 digit kode"></div><div class="field"><label for="password">Password Baru</label><div class="password-wrap"><input id="password" name="password" type="password" required minlength="8"><button type="button" class="toggle-password" data-target="password">👁</button></div><small class="password-rules">Minimal 8 karakter, 1 huruf kapital, 1 angka, dan 1 simbol.</small></div><div class="field"><label for="confirm_password">Ulangi Password Baru</label><div class="password-wrap"><input id="confirm_password" name="confirm_password" type="password" required><button type="button" class="toggle-password" data-target="confirm_password">👁</button></div></div><button class="btn btn--primary" type="submit">Ganti Password</button></form><?php else: ?><form method="post" class="auth-form"><input type="hidden" name="step" value="request"><div class="field"><label for="identity">Email atau Nomor Telepon</label><input id="identity" name="identity" required placeholder="Email / nomor telepon terdaftar"></div><button class="btn btn--primary" type="submit">Kirim Kode Verifikasi</button></form><?php endif; ?><p class="auth-bottom"><a href="login.php">← Kembali ke Login</a></p></section></main>
<?php require __DIR__.'/includes/footer.php'; ?>