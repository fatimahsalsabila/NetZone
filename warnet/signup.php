<?php
require_once __DIR__ . '/data.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $users = load_users();

    /* =========================
       VALIDASI
       ========================= */

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Masukkan alamat email yang valid.';

    } elseif (!preg_match('/^[0-9+() -]{8,20}$/', $phone)) {

        $error = 'Masukkan nomor telepon yang valid.';

    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,24}$/', $username)) {

        $error = 'Username harus 3–24 karakter dan hanya boleh memakai huruf, angka, titik, garis bawah, atau tanda hubung.';

    } elseif (!valid_password($password)) {

        $error = 'Password minimal 8 karakter dan harus memiliki 1 huruf kapital, 1 angka, serta 1 simbol.';

    } elseif ($password !== $confirm) {

        $error = 'Konfirmasi password belum sama.';

    } else {

        foreach ($users as $u) {

            if (strcasecmp($u['email'] ?? '', $email) === 0) {

                $error = 'Email sudah terdaftar.';
                break;

            }

            if (($u['phone'] ?? '') === $phone) {

                $error = 'Nomor telepon sudah terdaftar.';
                break;

            }

            if (strcasecmp($u['username'] ?? '', $username) === 0) {

                $error = 'Username sudah digunakan.';
                break;
            }
        }
    }


    /* =========================
       SIMPAN AKUN
       ========================= */

    if (!$error) {

        $account = [
            'id'         => bin2hex(random_bytes(8)),
            'email'      => $email,
            'phone'      => $phone,
            'username'   => $username,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('c')
        ];

        $users[] = $account;

        if (save_users($users)) {

            /*
             * Tidak langsung login.
             * Redirect ke halaman login.
             */
            header('Location: login.php?registered=1');
            exit;
        }

        $error = 'Akun belum bisa disimpan ke database. Periksa koneksi database lalu coba lagi.';
    }
}

$title = 'NetZone · Sign Up';

require __DIR__ . '/includes/header.php';
?>


<main class="page auth-page">

    <div class="auth-layout">

        <!-- =========================
             LEFT SIDE
             ========================= -->
        <section class="auth-visual">

            <!-- LOGO -->
            <img
                src="<?= BASE_URL ?>/assets/logo-netzone.png"
                alt="NetZone"
                class="auth-visual-logo"
            >

            <!-- GAMBAR -->
            <img
                src="<?= BASE_URL ?>/assets/logo-visual.png"
                alt="NetZone Gaming"
                class="auth-visual-image"
            >

            <!-- TEXT -->
            <div class="auth-visual-text">

                <h2>
                    Join NetZone<br>
                    & Start Gaming.
                </h2>

                <p>
                    Create your account and book<br>
                    your favorite computer easily.
                </p>

            </div>

        </section>


        <!-- =========================
             RIGHT SIDE
             ========================= -->
        <section class="auth-card">

            <!-- LOGO -->
            <div class="auth-brand">

                <img
                    src="<?= BASE_URL ?>/assets/logo-netzone.png"
                    alt="NetZone"
                >

            </div>


            <!-- TITLE -->
            <h1>
                Create Account
            </h1>

            <p>
                Sign up to start booking your gaming session.
            </p>


            <!-- ERROR -->
            <?php if ($error): ?>

                <div class="form-error">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <!-- FORM -->
            <form
                method="POST"
                class="auth-form"
            >

                <!-- =========================
                     EMAIL + PHONE
                     ========================= -->
                <div class="form-grid">

                    <!-- EMAIL -->
                    <div class="field">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="nama@email.com"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <!-- PHONE -->
                    <div class="field">

                        <label for="phone">
                            Nomor Telepon
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="08xxxxxxxxxx"
                            value="<?= e($_POST['phone'] ?? '') ?>"
                            autocomplete="tel"
                            required
                        >

                    </div>


                    <!-- USERNAME -->
                    <div class="field full">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Buat username"
                            value="<?= e($_POST['username'] ?? '') ?>"
                            minlength="3"
                            maxlength="24"
                            autocomplete="username"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->
                    <div class="field full">

                        <label for="password">
                            Password
                        </label>

                        <div class="password-wrap">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Buat password kuat"
                                minlength="8"
                                autocomplete="new-password"
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


                        <!-- PASSWORD RULES -->
                        <small class="password-rules">
                            Password harus memiliki minimal 8 karakter,
                            1 huruf kapital, 1 angka, dan 1 simbol.
                        </small>


                        <!-- PASSWORD CHECKLIST -->
                        <div
                            id="password-checklist"
                            class="password-checklist"
                        >

                            <span data-rule="length">
                                ○ Minimal 8 karakter
                            </span>

                            <span data-rule="upper">
                                ○ 1 huruf kapital
                            </span>

                            <span data-rule="number">
                                ○ 1 angka
                            </span>

                            <span data-rule="symbol">
                                ○ 1 simbol
                            </span>

                        </div>

                    </div>


                    <!-- CONFIRM PASSWORD -->
                    <div class="field full">

                        <label for="confirm_password">
                            Konfirmasi Password
                        </label>

                        <div class="password-wrap">

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Ulangi password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                data-target="confirm_password"
                                aria-label="Tampilkan password"
                            >
                                👁
                            </button>

                        </div>

                    </div>

                </div>


                <!-- SUBMIT -->
                <button
                    type="submit"
                    class="btn btn--primary"
                >
                    Create Account
                </button>

            </form>


            <!-- LOGIN -->
            <p class="auth-bottom">

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </p>

        </section>

    </div>

</main>


<script>
/* =========================
   PASSWORD CHECKLIST
   ========================= */

const passwordInput = document.getElementById('password');

if (passwordInput) {

    passwordInput.addEventListener('input', function () {

        const value = this.value;

        const rules = {

            length: value.length >= 8,

            upper: /[A-Z]/.test(value),

            number: /[0-9]/.test(value),

            symbol: /[^A-Za-z0-9]/.test(value)

        };


        Object.keys(rules).forEach(rule => {

            const element =
                document.querySelector(
                    `[data-rule="${rule}"]`
                );

            if (!element) return;


            if (rules[rule]) {

                element.textContent =
                    '✓ ' + element.textContent.replace(/^○ |^✓ /, '');

                element.classList.add('is-valid');

            } else {

                element.textContent =
                    '○ ' + element.textContent.replace(/^○ |^✓ /, '');

                element.classList.remove('is-valid');

            }

        });

    });

}

</script>


<?php require __DIR__ . '/includes/footer.php'; ?>