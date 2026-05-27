<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
header('Content-Type: application/json');

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
if (!$slug) jsonError('slug kerak');

$comic = getComic($slug);
if (!$comic) jsonError('Komiks topilmadi', 404);

echo json_encode([
    'ok'       => true,
    'title'    => $comic['title'],
    'chapters' => $comic['chapters'] ?? [],
]);
