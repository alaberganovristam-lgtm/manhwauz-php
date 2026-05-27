<?php
/**
 * comments.php — Komiks izohlari API
 * GET  /api/comments/{slug}         → izohlar ro'yxati
 * POST /api/comments/{slug}         → {text} yangi izoh
 * DELETE /api/comments/{slug}/{id}  → o'z izohini o'chirish
 */

session_name('mu_sess');
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store');

$AURA = defined('AURA_PATH') ? AURA_PATH : dirname(__DIR__) . '/asuramanga2';
if (!is_dir($AURA . '/data')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Data directory topilmadi']); exit;
}
$COMMENTS_FILE = $AURA . '/data/comments.json';

$USERS_FILE = __DIR__ . '/users.json';

function cm_load(): array {
    global $COMMENTS_FILE;
    if (!is_file($COMMENTS_FILE)) { file_put_contents($COMMENTS_FILE, '{}'); }
    return json_decode(file_get_contents($COMMENTS_FILE), true) ?? [];
}
function cm_save(array $d): void {
    global $COMMENTS_FILE;
    file_put_contents($COMMENTS_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function cm_ok(mixed $data = null): void {
    echo json_encode(['ok' => true, 'data' => $data]); exit;
}
function cm_fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]); exit;
}
function cm_getUser(): ?array {
    global $USERS_FILE;
    if (empty($_SESSION['uid'])) return null;
    if (!is_file($USERS_FILE)) return null;
    $db = json_decode(file_get_contents($USERS_FILE), true) ?? ['users' => []];
    foreach ($db['users'] as $u) {
        if ($u['id'] === $_SESSION['uid']) return $u;
    }
    return null;
}

$method = $_SERVER['REQUEST_METHOD'];

// slug va id URL dan olamiz
$uriPath  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$slug     = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
$targetId = $_GET['id'] ?? '';

if (!$slug) cm_fail('Slug kiritilmagan');

// GET /api/comments/{slug}
if ($method === 'GET') {
    $db = cm_load();
    $comments = $db[$slug] ?? [];
    // Yangi → eski tartibda qaytarish
    usort($comments, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));
    cm_ok($comments);
}

// POST /api/comments/{slug}
if ($method === 'POST') {
    $user = cm_getUser();
    if (!$user) cm_fail('Kirish talab qilinadi', 401);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $text = trim($body['text'] ?? '');
    if (!$text) cm_fail('Izoh matni kiritilmagan');
    if (mb_strlen($text) > 1000) cm_fail('Izoh 1000 ta belgidan oshmasligi kerak');

    $entry = [
        'id'        => bin2hex(random_bytes(8)),
        'userId'    => $user['id'],
        'username'  => $user['name'],
        'avatar'    => $user['avatar'] ?? null,
        'text'      => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
        'createdAt' => date('Y-m-d H:i:s'),
    ];

    $db = cm_load();
    if (!isset($db[$slug])) $db[$slug] = [];
    array_unshift($db[$slug], $entry);
    // Har bir komiks uchun maksimal 200 ta izoh saqlash
    if (count($db[$slug]) > 200) $db[$slug] = array_slice($db[$slug], 0, 200);
    cm_save($db);
    cm_ok($entry);
}

// DELETE /api/comments/{slug}/{id}
if ($method === 'DELETE') {
    $user = cm_getUser();
    if (!$user) cm_fail('Kirish talab qilinadi', 401);
    if (!$targetId) cm_fail('ID kiritilmagan');

    $db       = cm_load();
    $comments = $db[$slug] ?? [];
    $found    = false;
    foreach ($comments as $c) {
        if ($c['id'] === $targetId) {
            if ($c['userId'] !== $user['id']) cm_fail('Ruxsat yo\'q', 403);
            $found = true; break;
        }
    }
    if (!$found) cm_fail('Izoh topilmadi', 404);

    $db[$slug] = array_values(array_filter($comments, fn($c) => $c['id'] !== $targetId));
    cm_save($db);
    cm_ok();
}

cm_fail('Noto\'g\'ri so\'rov', 405);
