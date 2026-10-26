<?php
require_once __DIR__ . '/data.php';
require_login();

$filename = (string) ($_SESSION['netzone_user']['profile_photo'] ?? '');
if ($filename === '' || basename($filename) !== $filename) {
  http_response_code(404);
  exit;
}

$path = __DIR__ . '/storage/profile-photos/' . $filename;
if (!is_file($path)) {
  http_response_code(404);
  exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
  http_response_code(404);
  exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($path);
