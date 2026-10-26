<aside class="ad-side">
  <a class="ad-brand" href="<?= BASE_URL ?>/admin/index.php" aria-label="NetZone Admin">
    <img src="<?= BASE_URL ?>/assets/logo-netzone.png" alt="NetZone">
    <span>Admin</span>
  </a>
  <nav class="ad-nav" aria-label="Menu admin">
    <?php foreach ($adminNav as $key => [$label, $href, $icon]): ?>
      <a class="ad-nav__link<?= $key === $adminActive ? ' is-active' : '' ?>" href="<?= BASE_URL ?>/admin/<?= e($href) ?>"<?= $key === $adminActive ? ' aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg><span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="ad-side__foot">
    <p class="ad-side__who"><?= e($adminName) ?></p>
    <a href="<?= BASE_URL ?>/beranda.php">Lihat situs</a>
    <a href="<?= BASE_URL ?>/logout.php">Keluar</a>
  </div>
</aside>
