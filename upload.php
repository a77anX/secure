<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die("Upload failed. Error code: " . $_FILES['file']['error']);
}

$fileTmp = $_FILES['file']['tmp_name'];
$origFilename = basename($_FILES['file']['name']);
$data = file_get_contents($fileTmp);

$iv = random_bytes(12);
$encName = bin2hex(random_bytes(16)) . ".bin";
$tag = "";

$ciphertext = openssl_encrypt(
    $data,
    'aes-256-gcm',
    MASTER_KEY,
    OPENSSL_RAW_DATA,
    $iv,
    $tag
);

if ($ciphertext === false) {
    die("Encryption failed.");
}

// make sure uploads folder exists
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        die("Failed to create uploads folder. Please check permissions.");
    }
}

$savePath = $uploadDir . DIRECTORY_SEPARATOR . $encName;
if (file_put_contents($savePath, $ciphertext) === false) {
    die("Failed to save encrypted file. Path: $savePath");
}

$iv_b64 = base64_encode($iv);
$tag_b64 = base64_encode($tag);

$stmt = $mysqli->prepare("INSERT INTO files (user_id, filename, encrypted_name, iv, tag) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $_SESSION['user_id'], $origFilename, $encName, $iv_b64, $tag_b64);

if (!$stmt->execute()) {
    @unlink($savePath);
    die("Failed to store file metadata: " . $mysqli->error);
}

header("Location: dashboard.php");
exit;
