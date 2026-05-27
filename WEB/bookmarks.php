<?php
/**
 * bookmarks.php — Foydalanuvchi bookmarklari API
 * GET  /api/bookmarks          → foydalanuvchi bookmarklari ro'yxati
 * POST /api/bookmarks          → {slug} qo'shish
 * DELETE /api/bookmarks/{slug} → o'chirish
 */

session_name('mu_sess');
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store');

$USERS_FILE = __DIR__ . '/users.json';

function bm_loadUsers(string $f): array {
    if (!is_file($f)) return ['users' => []];
    return json_decode(file_get_contents($f), true) ?? ['users' => []];
}
function bm_saveUsers(string $f, array $d): void {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function bm_ok(mixed $data = null): void {
    echo json_encode(['ok' => true, 'data' => $data]); exit;
}
function bm_fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]); exit;
}
function bm_requireAuth(): string {
    if (empty($_SESSION['uid'])) bm_fail('Kirish talab qilinadi', 401);
    return $_SESSION['uid'];
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// DELETE /api/bookmarks/{slug}
if ($method === 'DELETE') {
    $uid  = bm_requireAuth();
    $slug = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
    if (!$slug) bm_fail('Slug kiritilmagan');

    $db  = bm_loadUsers($USERS_FILE);
    $idx = null;
    foreach ($db['users'] as $i => $u) {
        if ($u['id'] === $uid) { $idx = $i; break; }
    }
    if ($idx === null) bm_fail('Foydalanuvchi topilmadi', 404);

    $bm = $db['users'][$idx]['bookmarks'] ?? [];
    $db['users'][$idx]['bookmarks'] = array_values(array_filter($bm, fn($b) => $b['slug'] !== $slug));
    bm_saveUsers($USERS_FILE, $db);
    bm_ok();
}

// GET /api/bookmarks
if ($method === 'GET') {
    $uid = bm_requireAuth();
    $db  = bm_loadUsers($USERS_FILE);
    foreach ($db['users'] as $u) {
        if ($u['id'] === $uid) { bm_ok($u['bookmarks'] ?? []); }
    }
    bm_ok([]);
}

// POST /api/bookmarks
if ($method === 'POST') {
    $uid  = bm_requireAuth();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $slug = preg_replace('/[^a-z0-9-]/', '', $body['slug'] ?? '');
    if (!$slug) bm_fail('Slug kiritilmagan');

    $AURA       = defined('AURA_PATH') ? AURA_PATH : dirname(__DIR__) . '/asuramanga2';
    $comicsFile = $AURA . '/data/comics.json';
    if (!is_file($comicsFile)) bm_fail('Comics bazasi topilmadi', 500);
    $comics = json_decode(file_get_contents($comicsFile), true) ?? [];
    $comic  = null;
    foreach ($comics as $c) { if ($c['slug'] === $slug) { $comic = $c; break; } }
    if (!$comic) bm_fail('Komiks topilmadi', 404);

    $db  = bm_loadUsers($USERS_FILE);
    $idx = null;
    foreach ($db['users'] as $i => $u) {
        if ($u['id'] === $uid) { $idx = $i; break; }
    }
    if ($idx === null) bm_fail('Foydalanuvchi topilmadi', 404);

    $bm = $db['users'][$idx]['bookmarks'] ?? [];
    foreach ($bm as $b) {
        if ($b['slug'] === $slug) bm_ok($b); // allaqachon qo'shilgan
    }

    $entry = [
        'slug'      => $slug,
        'title'     => $comic['title'] ?? '',
        'cover'     => $comic['cover'] ?? '',
        'status'    => $comic['status'] ?? 'Ongoing',
        'addedAt'   => date('Y-m-d H:i:s'),
    ];
    $db['users'][$idx]['bookmarks'][] = $entry;
    bm_saveUsers($USERS_FILE, $db);
    bm_ok($entry);
}

bm_fail('Noto\'g\'ri so\'rov', 405);
