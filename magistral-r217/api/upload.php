<?php
header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . '/../uploads/';
$maxSize   = 8 * 1024 * 1024;
$allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

if (empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'no file uploaded']);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'upload error: ' . $file['error']]);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'file too large (max 8 MB)']);
    exit;
}

$mime = mime_content_type($file['tmp_name']);
if (!in_array($mime, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported file type']);
    exit;
}

$ext  = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => 'jpg',
};

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$filename  = uniqid('img_', true) . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'failed to save file']);
    exit;
}

echo json_encode(['url' => 'uploads/' . $filename]);
