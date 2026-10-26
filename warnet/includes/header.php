<?php
require_once __DIR__ . '/../data.php';

$active = $active ?? 'home';
$title  = $title ?? 'NetZone';

// Cek apakah halaman login / signup
$hideNav = $hideNav ?? false;
$extraCss = $extraCss ?? [];
$isAuthPage = $hideNav || in_array(basename($_SERVER['PHP_SELF']), [
    'login.php',
    'signup.php'
]);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Orelega+One&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css?v=22">
<?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= e($css) ?>">
<?php endforeach; ?>
</head>

<body>

<?php if (!$isAuthPage): ?>
<?php require __DIR__ . '/topbar.php'; ?>
<?php endif; ?>