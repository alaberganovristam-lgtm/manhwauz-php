<?php
/**
 * api/upload-cover.php
 * Uploads and converts a cover image to WebP.
 * POST params:
 *   csrf, cover (file), slug (optional — used to name the file)
 * Returns: { ok, path, filename }
 */
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
verifyCsrf();

header('Content-Type: application/json');

if (empty($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Cover fayl yuklanmadi');
}

if (!is_uploaded_file($_FILES['cover']['tmp_name'])) {
    jsonError('Noto\'g\'ri fayl');
}

$file    = $_FILES['cover'];
$tmpPath = $file['tmp_name'];
$size    = $file['size'];

// 5 MB limit
if ($size > 5 * 1024 * 1024) {
    jsonError('Fayl hajmi 5 MB dan oshmasligi kerak');
}

// Validate it's a real image
$info = @getimagesize($tmpPath);
if (!$info) {
    jsonError('Fayl rasm emas yoki buzilgan');
}

$allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF, IMAGETYPE_BMP];
if (!in_array($info[2], $allowedTypes)) {
    jsonError('Faqat JPG, PNG, WebP, GIF, BMP qabul qilinadi');
}

// Determine filename
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug'] ?? ''));
if (!$slug) {
    // Generate from original filename
    $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
    $slug = trim(preg_replace('/-+/', '-', $slug), '-') ?: 'cover-' . time();
}

$coversDir = COVERS_DIR;

// Create directory if needed
if (!is_dir($coversDir)) {
    if (!mkdir($coversDir, 0755, true)) {
        jsonError('Cover papkasi yaratib bo\'lmadi');
    }
}

$filename = $slug . '.webp';
$destPath = $coversDir . '/' . $filename;

// Convert to WebP
$ok = convertImage($tmpPath, $destPath, 'webp', 90);

if (!$ok) {
    // GD failed — try copying original as fallback
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    $filename = $slug . '.' . $ext;
    $destPath = $coversDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $destPath)) {
        jsonError('Faylni saqlash mumkin bo\'lmadi');
    }
}

// Public path relative to site root
// COVERS_DIR = SITE_ROOT/public/images/covers
// Public URL  = /public/images/covers/{filename}  (matches comics.json format)
$publicPath = '/public/images/covers/' . $filename;

logActivity('cover.upload', $slug . ' → ' . $filename);

jsonOk([
    'path'     => $publicPath,
    'filename' => $filename,
]);
