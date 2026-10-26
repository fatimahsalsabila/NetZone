<?php
require_once __DIR__ . '/data.php';
require_login();

$error = '';
$success = '';
$users = load_users();
$currentId = (string) ($_SESSION['netzone_user']['id'] ?? '');
$accountIndex = null;
foreach ($users as $index => $account) {
  if (($account['id'] ?? '') === $currentId) {
    $accountIndex = $index;
    break;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = (string) ($_POST['action'] ?? '');

  if ($accountIndex === null) {
    $error = 'Akun tidak ditemukan. Silakan login kembali.';
  } elseif ($action === 'profile') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Email tidak valid.';
    } elseif (!preg_match('/^[0-9+() -]{8,20}$/', $phone)) {
      $error = 'Nomor telepon tidak valid.';
    } else {
      foreach ($users as $index => $account) {
        if ($index !== $accountIndex && (
          strcasecmp($account['email'] ?? '', $email) === 0
          || ($account['phone'] ?? '') === $phone
          || strcasecmp($account['username'] ?? '', $username) === 0
        )) {
          $error = 'Email, nomor telepon, atau username sudah digunakan akun lain.';
          break;
        }
      }
    }

    if (!$error) {
      $users[$accountIndex]['email'] = $email;
      $users[$accountIndex]['phone'] = $phone;
      $users[$accountIndex]['username'] = $username;
      if (save_users($users)) {
        $_SESSION['netzone_user'] = $users[$accountIndex];
        $success = 'Informasi profile berhasil disimpan.';
      } else {
        $error = 'Perubahan profil belum bisa disimpan ke database. Periksa koneksi database lalu coba lagi.';
      }
    }
  } elseif ($action === 'password') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!valid_password($newPassword)) {
      $error = 'Password baru minimal 8 karakter dan harus memiliki 1 huruf kapital, 1 angka, serta 1 simbol.';
    } elseif ($newPassword !== $confirmPassword) {
      $error = 'Ulangi password belum sama.';
    } else {
      $users[$accountIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
      if (save_users($users)) {
        $_SESSION['netzone_user'] = $users[$accountIndex];
        $success = 'Password berhasil diganti.';
      } else {
        $error = 'Password belum bisa disimpan ke database. Periksa koneksi database lalu coba lagi.';
      }
    }
  } elseif ($action === 'photo_upload') {
    $photo = $_FILES['photo'] ?? null;
    $maxPhotoBytes = 5 * 1024 * 1024;

    if (!$photo || ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $error = 'Pilih foto yang valid untuk diunggah.';
    } elseif ((int) $photo['size'] > $maxPhotoBytes) {
      $error = 'Ukuran foto maksimal 5 MB.';
    } elseif (!is_uploaded_file($photo['tmp_name'])) {
      $error = 'File foto tidak valid.';
    } else {
      $imageInfo = @getimagesize($photo['tmp_name']);
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
      $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

      if (!$imageInfo || !isset($extensions[$mime]) || $imageInfo[0] * $imageInfo[1] > 20000000) {
        $error = 'Gunakan foto JPG, PNG, atau WebP dengan dimensi yang wajar.';
      } else {
        $directory = __DIR__ . '/storage/profile-photos';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
          $error = 'Folder penyimpanan foto belum bisa dibuat.';
        } else {
          $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
          $destination = $directory . '/' . $filename;
          if (!move_uploaded_file($photo['tmp_name'], $destination)) {
            $error = 'Foto belum bisa disimpan. Coba lagi.';
          } else {
            $oldPhoto = (string) ($users[$accountIndex]['profile_photo'] ?? '');
            $users[$accountIndex]['profile_photo'] = $filename;
            if (save_users($users)) {
              $_SESSION['netzone_user'] = $users[$accountIndex];
              if ($oldPhoto !== '' && basename($oldPhoto) === $oldPhoto) {
                @unlink($directory . '/' . $oldPhoto);
              }
              $success = 'Foto profil berhasil diperbarui.';
            } else {
              @unlink($destination);
              $error = 'Foto belum bisa disimpan. Pastikan folder storage bisa ditulis oleh Laragon.';
            }
          }
        }
      }
    }
  } elseif ($action === 'photo_delete') {
    $oldPhoto = (string) ($users[$accountIndex]['profile_photo'] ?? '');
    unset($users[$accountIndex]['profile_photo']);
    if (save_users($users)) {
      $_SESSION['netzone_user'] = $users[$accountIndex];
      if ($oldPhoto !== '' && basename($oldPhoto) === $oldPhoto) {
        @unlink(__DIR__ . '/storage/profile-photos/' . $oldPhoto);
      }
      $success = 'Foto profil berhasil dihapus.';
    } else {
      $error = 'Foto profil belum bisa dihapus. Pastikan folder storage bisa ditulis oleh Laragon.';
    }
  }
}

$account = $_SESSION['netzone_user'];
$username = (string) ($account['username'] ?? 'User');
$user = [
  'id' => (string) ($account['id'] ?? ''),
  'name' => $username,
  'initial' => strtoupper(substr($username, 0, 1)),
  'email' => (string) ($account['email'] ?? ''),
  'phone' => (string) ($account['phone'] ?? ''),
  'profile_photo' => (string) ($account['profile_photo'] ?? ''),
  'photo_url' => profile_photo_url((string) ($account['profile_photo'] ?? '')),
];
$active = 'profile';
$title = 'NetZone · Profile';
require __DIR__ . '/includes/header.php';
?>
<main class="page">
  <div class="page-head">
    <h1 class="profile-page-title">
      <span class="profile-page-title__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" focusable="false">
          <circle cx="12" cy="8" r="3.5" />
          <path d="M5 20c.7-3.3 3.2-5 7-5s6.3 1.7 7 5" />
        </svg>
      </span>
      Profile
    </h1>
    <p>Informasi akun kamu di NetZone.</p>
  </div>

  <div class="form-card">
    <div class="profile-head">
      <button
        class="profile-avatar-button"
        id="manage-profile-photo"
        type="button"
        data-has-photo="<?= $user['photo_url'] ? 'true' : 'false' ?>"
        aria-label="<?= $user['photo_url'] ? 'Kelola foto profil' : 'Tambah foto profil' ?>"
      >
        <span class="avatar avatar--large<?= $user['photo_url'] ? ' avatar--photo' : '' ?>">
          <?php if ($user['photo_url']): ?>
            <img src="<?= e($user['photo_url']) ?>" alt="Foto profil <?= e($user['name']) ?>">
          <?php else: ?>
            <?= e($user['initial']) ?>
          <?php endif; ?>
        </span>
        <span class="profile-avatar-camera" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 7.5h3l1.5-2h7l1.5 2h3v11H4z" />
            <circle cx="12" cy="13" r="3.5" />
          </svg>
        </span>
      </button>
      <div>
        <h2><?= e($user['name']) ?></h2>
        <p>Member NetZone</p>
      </div>
    </div>

    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="notice"><?= e($success) ?></div><?php endif; ?>

    <form id="profile-photo-form" class="profile-photo-form" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="photo_upload">
      <input id="profile-photo-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
    </form>

    <dialog id="profile-photo-dialog" class="profile-photo-dialog" aria-labelledby="profile-photo-title">
      <h2 id="profile-photo-title">Foto profil</h2>
      <p id="profile-photo-help">Ambil foto dengan kamera atau pilih dari perangkat.</p>
      <div class="profile-photo-actions" id="profile-photo-actions">
        <button class="btn btn--primary" id="open-profile-camera" type="button">Ambil foto</button>
        <button class="btn btn--secondary" id="change-profile-photo" type="button">Pilih dari perangkat</button>
        <?php if ($user['photo_url']): ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="photo_delete">
            <button class="btn btn--danger" type="submit">Hapus foto</button>
          </form>
        <?php endif; ?>
        <button class="btn btn--secondary" id="cancel-profile-photo" type="button">Batal</button>
      </div>
      <div class="profile-camera-panel" id="profile-camera-panel" hidden>
        <video class="profile-camera-preview" id="profile-camera-preview" autoplay playsinline muted></video>
        <p id="profile-camera-error" role="alert" hidden></p>
        <div class="profile-photo-actions">
          <button class="btn btn--primary" id="capture-profile-photo" type="button" disabled>Jepret foto</button>
          <button class="btn btn--secondary" id="switch-profile-camera" type="button">Pakai kamera belakang</button>
          <button class="btn btn--secondary" id="camera-profile-photo-fallback" type="button">Pilih dari perangkat</button>
          <button class="btn btn--secondary" id="back-profile-photo-actions" type="button">Kembali</button>
        </div>
      </div>
      <div class="profile-crop-panel" id="profile-crop-panel" hidden>
        <p>Geser foto untuk mengatur bagian yang terlihat di avatar.</p>
        <div class="profile-crop-stage" id="profile-crop-stage">
          <img id="profile-crop-image" alt="Pratinjau potongan foto profil" draggable="false">
        </div>
        <label class="profile-crop-zoom" for="profile-crop-zoom">
          Zoom
          <input id="profile-crop-zoom" type="range" min="1" max="3" step="0.01" value="1">
        </label>
        <p id="profile-crop-error" role="alert" hidden></p>
        <div class="profile-photo-actions">
          <button class="btn btn--primary" id="use-profile-crop" type="button">Pakai foto</button>
          <button class="btn btn--secondary" id="cancel-profile-crop" type="button">Kembali</button>
        </div>
      </div>
    </dialog>

    <form id="profile-details-form" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">
      <div class="form-grid">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" value="<?= e($user['name']) ?>" minlength="3" maxlength="24" required>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" type="email" name="email" value="<?= e($user['email']) ?>" required>
        </div>
        <div class="field full">
          <label for="phone">Nomor Telepon</label>
          <input id="phone" name="phone" value="<?= e($user['phone']) ?>" required>
        </div>
      </div>
    </form>

    <section class="profile-password">
      <h2>Ganti Password</h2>
      <form method="post" class="auth-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <div class="field">
          <label for="new_password">Password Baru</label>
          <div class="password-wrap">
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" required>
            <button type="button" class="toggle-password" data-target="new_password" aria-label="Tampilkan password baru">👁</button>
          </div>
          <small class="password-rules">Minimal 8 karakter, 1 huruf kapital, 1 angka, dan 1 simbol.</small>
        </div>
        <div class="field">
          <label for="confirm_password">Ulangi Password</label>
          <div class="password-wrap">
            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
            <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Tampilkan ulangi password">👁</button>
          </div>
        </div>
        <button class="btn btn--primary" type="submit">Ganti Password</button>
      </form>
    </section>
    <button class="btn btn--primary" type="submit" form="profile-details-form">Simpan Perubahan</button>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>