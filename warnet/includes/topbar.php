<header class="nav">
    <a class="brand" href="<?= BASE_URL ?>/index.php">
        <img class="brand__logo" src="<?= BASE_URL ?>/assets/logo-netzone.png" alt="NetZone">
    </a>

    <nav class="nav__links" aria-label="Utama">
        <?php foreach ($navLinks as $key => [$label, $href, $icon]): ?>
            <a class="nav__link<?= $key === $active ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/<?= e($href) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="account">
        <a class="bell" href="<?= BASE_URL ?>/notifikasi.php" aria-label="Buka notifikasi" title="Notifikasi">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 17V11a6 6 0 1 1 12 0v6l2 2H4zM10 21h4"/>
            </svg>
            <i></i>
        </a>
        <?php if (!empty($_SESSION['netzone_user'])): ?>
            <a class="account-link" href="<?= BASE_URL ?>/profil.php">
                <span class="avatar<?= !empty($user['photo_url']) ? ' avatar--photo' : '' ?>">
                    <?php if (!empty($user['photo_url'])): ?>
                        <img src="<?= e($user['photo_url']) ?>" alt="">
                    <?php else: ?>
                        <?= e($user['initial'] ?? 'U') ?>
                    <?php endif; ?>
                </span>
                <span class="account__name"><?= e($user['name'] ?? 'User') ?></span>
            </a>
            <span class="account-role account-role--<?= is_admin() ? 'admin' : 'customer' ?>">
                <?= is_admin() ? 'Admin' : 'Customer' ?>
            </span>
            <?php if (is_admin()): ?>
                <a class="account-auth account-auth--admin" href="<?= BASE_URL ?>/admin/index.php">Dashboard Admin</a>
            <?php endif; ?>
            <a class="account-auth" href="<?= BASE_URL ?>/logout.php">Keluar</a>
        <?php else: ?>
            <a class="account-auth" href="<?= BASE_URL ?>/login.php">Login</a>
            <a class="account-auth account-auth--signup" href="<?= BASE_URL ?>/signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</header>
