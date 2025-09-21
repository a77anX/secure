<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (!isset($_GET['id'])) { die("Missing file id."); }
$fileId = (int) $_GET['id'];

// Admins can download any file; users only their own files
if ($_SESSION['role'] === 'admin') {
    $stmt = $mysqli->prepare("SELECT filename, encrypted_name, iv, tag FROM files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
} else {
    $stmt = $mysqli->prepare("SELECT filename, encrypted_name, iv, tag FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fileId, $_SESSION['user_id']);
}

$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    die("File not found or access denied.");
}
$stmt->bind_result($filename, $encName, $iv_b64, $tag_b64);
$stmt->fetch();
$stmt->close();

// make sure uploads folder exists
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
if (!is_dir($uploadDir)) {
    die("Uploads folder missing. Path: $uploadDir");
}

$encPath = $uploadDir . DIRECTORY_SEPARATOR . $encName;
if (!file_exists($encPath)) {
    die("Encrypted file missing on server. Path: $encPath");
}

// read ciphertext
$ciphertext = file_get_contents($encPath);
if ($ciphertext === false) die("Failed to read encrypted file.");

// decode iv and tag
$iv = base64_decode($iv_b64);
$tag = base64_decode($tag_b64);

// decrypt
$plaintext = openssl_decrypt(
    $ciphertext,
    'aes-256-gcm',
    MASTER_KEY,
    OPENSSL_RAW_DATA,
    $iv,
    $tag
);

if ($plaintext === false) {
    die("Decryption failed: authentication failed or data corrupted.");
}

// send file to client
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header("Content-Length: " . strlen($plaintext));
echo $plaintext;
exit;
