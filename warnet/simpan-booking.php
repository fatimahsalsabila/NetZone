<?php
require_once __DIR__ . '/data.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: booking.php');
  exit;
}
csrf_check();

$p = booking_params($_POST);
if ($p['location'] === '') {
  header('Location: booking.php?error=location');
  exit;
}
if (!booking_date_ok($p['date'])) {
  header('Location: booking.php?error=pastdate');
  exit;
}
if (!booking_time_ok($p['date'], $p['time'])) {
  header('Location: booking.php?error=pasttime');
  exit;
}

$payment  = (string) ($_POST['payment'] ?? '');
if (!in_array($payment, PAYMENT_METHODS, true)) {
  header('Location: booking.php?error=payment');
  exit;
}

$computers = load_computers();
$account   = $_SESSION['netzone_user'];
$code      = 'NZ-' . strtoupper(bin2hex(random_bytes(5)));

[$selected, $err] = resolve_booking_pcs($p, $computers);
if ($err !== null) {
  header('Location: booking.php?error=' . $err);
  exit;
}

$total = booking_total($selected, $p['duration']);

$result = create_booking([
  'code' => $code,
  'user_id' => (string) ($account['id'] ?? ''),
  'username' => (string) ($account['username'] ?? ''),
  'location' => $p['location'],
  'date' => $p['date'],
  'time' => $p['time'],
  'duration' => $p['duration'],
  'people' => $p['people'],
  'payment' => $payment,
  'total' => $total,
], $selected);

if ($result === true) {
  header('Location: pembayaran-berhasil.php?code=' . urlencode($code));
  exit;
}
if ($result !== 'save') {
  header('Location: booking.php?error=' . urlencode($result));
  exit;
}
header('Location: booking.php?error=save');
