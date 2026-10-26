<?php
require_once __DIR__ . '/data.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $account = null;

    foreach (load_users() as $userRecord) {
        if (
            strcasecmp($userRecord['email'] ?? '', $identity) === 0
            || strcasecmp($userRecord['username'] ?? '', $identity) === 0
            || ($userRecord['phone'] ?? '') === $identity
        ) {
            $account = $userRecord;
            break;
        }
    }

    if ($account && password_verify($password, $account['password'] ?? '')) {
        session_regenerate_id(true);
        unset($account['password']);
        $_SESSION['netzone_user'] = $account;
        header('Location: ' . (($account['role'] ?? '') === 'admin' ? 'admin/index.php' : 'beranda.php'));
        exit;
    }

    $error = 'Email, nomor telepon, username, atau password salah.';
}

$title = 'NetZone · Login';

require __DIR__ . '/includes/header.php';
?>

<main class="page auth-page">

    <div class="auth-layout">

        <section class="auth-visual">

            <img
                src="<?= BASE_URL ?>/assets/logo-netzone.png"
                alt="NetZone"
                class="auth-visual-logo"
            >

            <img
                src="<?= BASE_URL ?>/assets/logo-visual.png"
                alt="NetZone Gaming"
                class="auth-visual-image"
            >

            <div class="auth-visual-text">
                <h2>Your Gaming<br>Session Starts Here.</h2>

                <p>
                    Book your favorite computer and enjoy<br>
                    your gaming time at NetZone.
                </p>
            </div>

        </section>

        <section class="auth-card">

            <div class="auth-brand">
                <img
                    src="<?= BASE_URL ?>/assets/logo-netzone.png"
                    alt="NetZone"
                >
            </div>

            <h1>Welcome Back!</h1>

            <p>Login to continue to your NetZone account.</p>

            <?php if (!empty($_GET['registered'])): ?>
                <div class="notice">
                    Akun berhasil dibuat. Silakan login.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="form-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">

                <div class="field">
                    <label for="identity">Email, Nomor Telepon, atau Username</label>

                    <input
                        type="text"
                        id="identity"
                        name="identity"
                        placeholder="Masukkan email, nomor telepon, atau username"
                        value="<?= e($_POST['identity'] ?? '') ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <div class="password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password"
                            aria-label="Tampilkan password"
                        >
                            👁
                        </button>
                    </div>
                </div>

                <div class="auth-extra">
                    <a href="forgot-password.php">Lupa Password?</a>
                </div>

                <button type="submit" class="btn btn--primary">
                    Login
                </button>

            </form>

            <p class="auth-bottom">
                Belum punya akun?
                <a href="signup.php">Sign Up</a>
            </p>

        </section>

    </div>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
