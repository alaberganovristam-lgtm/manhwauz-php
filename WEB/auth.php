<?php
/**
 * auth.php — Local authentication API
 * Endpoints:
 *   GET  /api/auth/me       → current user or null
 *   GET  /api/auth/logout   → destroy session
 *   POST /api/auth/login    → {email, password}
 *   POST /api/auth/register → {email, password, name?}
 */

session_name('mu_sess');
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store');

$USERS_FILE = __DIR__ . '/users.json';

/* ── helpers ── */
function loadUsers(string $f): array {
    if (!is_file($f)) { file_put_contents($f, '{"users":[]}'); }
    $d = json_decode(file_get_contents($f), true);
    return is_array($d) ? $d : ['users' => []];
}
function saveUsers(string $f, array $d): void {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function ok(array $extra = []): void {
    echo json_encode(array_merge(['ok' => true], $extra)); exit;
}
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]); exit;
}
function userPublic(array $u): array {
    return ['id' => $u['id'], 'email' => $u['email'], 'name' => $u['name'], 'avatar' => $u['avatar'] ?? null];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

/* ── GET /api/auth/me ── */
if ($action === 'me') {
    if (!empty($_SESSION['uid'])) {
        $db = loadUsers($USERS_FILE);
        foreach ($db['users'] as $u) {
            if ($u['id'] === $_SESSION['uid']) { ok(['user' => userPublic($u)]); }
        }
    }
    ok(['user' => null]);
}

/* ── GET /api/auth/logout ── */
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    ok();
}

/* ── POST body ── */
$body = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
}

/* ── POST /api/auth/login ── */
if ($action === 'login' && $method === 'POST') {
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';
    if (!$email || !$pass) fail('Email va parol kiritilishi shart');

    $db = loadUsers($USERS_FILE);
    foreach ($db['users'] as $u) {
        if ($u['email'] === $email && password_verify($pass, $u['password'])) {
            $_SESSION['uid'] = $u['id'];
            ok(['user' => userPublic($u)]);
        }
    }
    fail('Email yoki parol noto\'g\'ri', 401);
}

/* ── POST /api/auth/register ── */
if ($action === 'register' && $method === 'POST') {
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';
    $name  = trim($body['name'] ?? '');

    if (!$email || !$pass)               fail('Email va parol kiritilishi shart');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Noto\'g\'ri email manzil');
    if (strlen($pass) < 8)              fail('Parol kamida 8 ta belgi bo\'lishi kerak');

    $db = loadUsers($USERS_FILE);
    foreach ($db['users'] as $u) {
        if ($u['email'] === $email) fail('Bu email allaqachon ro\'yxatdan o\'tgan', 409);
    }

    if (!$name) $name = ucfirst(explode('@', $email)[0]);
    $id = bin2hex(random_bytes(8));

    $db['users'][] = [
        'id'        => $id,
        'email'     => $email,
        'name'      => $name,
        'password'  => password_hash($pass, PASSWORD_DEFAULT),
        'avatar'    => null,
        'createdAt' => date('Y-m-d'),
    ];
    saveUsers($USERS_FILE, $db);
    $_SESSION['uid'] = $id;
    ok(['user' => ['id' => $id, 'email' => $email, 'name' => $name, 'avatar' => null]]);
}

fail('Not found', 404);
