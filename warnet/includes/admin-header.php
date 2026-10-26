<?php
// Dipakai semua halaman admin. Halaman harus memanggil require_admin() lebih dulu.
require_once __DIR__ . '/../data.php';

$adminActive = $adminActive ?? 'dashboard';
$adminTitle  = $adminTitle ?? 'Admin';
$adminName   = (string) ($_SESSION['netzone_user']['username'] ?? 'Admin');

$adminNav = [
  'dashboard' => ['Dashboard', 'index.php', '<path d="M3 12l9-8 9 8M5 10v10h14V10"/>'],
  'bookings'  => ['Booking', 'bookings.php', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>'],
  'computers' => ['Komputer & Harga', 'computers.php', '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>'],
];

/** Tombol aksi booking sesuai status saat ini. */
function booking_actions(array $b, string $return): string
{
  $status = (string) ($b['status'] ?? '');
  $buttons = [];
  if ($status === 'Menunggu') {
    $buttons[] = ['approve', 'Setujui', 'ad-btn--ok', ''];
  }
  if ($status === 'Disetujui') {
    $buttons[] = ['finish', 'Selesai', 'ad-btn--ghost', ''];
  }
  if ($status === 'Menunggu' || $status === 'Disetujui') {
    $buttons[] = ['cancel', 'Batalkan', 'ad-btn--danger', "return confirm('Batalkan booking ini?')"];
  }
  if (!$buttons) {
    return '<span class="ad-muted">-</span>';
  }
  $html = '<form method="post" action="' . BASE_URL . '/admin/bookings.php" class="ad-inline">'
    . csrf_field()
    . '<input type="hidden" name="id" value="' . e((string) $b['id']) . '">'
    . '<input type="hidden" name="return" value="' . e($return) . '">';
  foreach ($buttons as [$action, $label, $cls, $confirm]) {
    $html .= '<button class="ad-btn ad-btn--sm ' . $cls . '" name="action" value="' . $action . '"'
      . ($confirm ? ' onclick="' . e($confirm) . '"' : '') . '>' . e($label) . '</button>';
  }
  return $html . '</form>';
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>NetZone Admin · <?= e($adminTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orelega+One&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/admin.css?v=1">
</head>
<body>
<div class="ad-shell">
  <?php require __DIR__ . '/admin-sidebar.php'; ?>
  <main class="ad-main">
    <?php if ($flash): ?>
      <div class="ad-flash ad-flash--<?= $flash['t'] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($flash['m']) ?></div>
    <?php endif; ?>
