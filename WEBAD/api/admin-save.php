<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();
verifyCsrf();

header('Content-Type: application/json');

$adminsFile = DATA_DIR . '/admins.json';
$db         = is_file($adminsFile) ? (json_decode(file_get_contents($adminsFile), true) ?? []) : [];
$admins     = &$db['admins'];
$myId       = $_SESSION['admin_id'] ?? '';
$action     = $_POST['action'] ?? '';

// ── Parol o'zgartirish ────────────────────────────────────────
if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) jsonError('Barcha maydonlarni to\'ldiring');
    if (strlen($new) < 6) jsonError('Yangi parol kamida 6 belgi bo\'lishi kerak');
    if ($new !== $confirm) jsonError('Parollar mos kelmaydi');

    $found = false;
    foreach ($admins as &$a) {
        if ($a['id'] !== $myId) continue;
        if (!password_verify($current, $a['password'])) jsonError('Joriy parol noto\'g\'ri');
        $a['password'] = password_hash($new, PASSWORD_DEFAULT);
        $found = true;
        break;
    }
    unset($a);
    if (!$found) jsonError('Admin topilmadi', 404);

    file_put_contents($adminsFile, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    logActivity('admin.password', 'Parol o\'zgartirildi');
    jsonOk(['msg' => 'Parol muvaffaqiyatli o\'zgartirildi']);
}

// ── Admin qo'shish ────────────────────────────────────────────
if ($action === 'add_admin') {
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['admin','superadmin']) ? $_POST['role'] : 'admin';

    if (!$username || strlen($username) < 3) jsonError('Username kamida 3 belgi');
    if (strlen($password) < 6) jsonError('Parol kamida 6 belgi');

    foreach ($admins as $a) {
        if (strtolower($a['username']) === strtolower($username)) {
            jsonError('Bu username allaqachon mavjud');
        }
    }

    $admins[] = [
        'id'        => bin2hex(random_bytes(8)),
        'username'  => $username,
        'password'  => password_hash($password, PASSWORD_DEFAULT),
        'role'      => $role,
        'createdAt' => date('Y-m-d'),
    ];

    file_put_contents($adminsFile, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    logActivity('admin.add', $username . ' (' . $role . ')');
    jsonOk(['msg' => $username . ' qo\'shildi', 'reload' => true]);
}

// ── Admin o'chirish ───────────────────────────────────────────
if ($action === 'delete_admin') {
    $targetId = trim($_POST['id'] ?? '');
    if ($targetId === $myId) jsonError('O\'zingizni o\'chira olmaysiz');
    if (!$targetId) jsonError('ID ko\'rsatilmagan');

    $before = count($admins);
    $delName = '';
    $admins = array_values(array_filter($admins, function($a) use ($targetId, &$delName) {
        if ($a['id'] === $targetId) { $delName = $a['username']; return false; }
        return true;
    }));
    $db['admins'] = $admins;

    if (count($admins) === $before) jsonError('Admin topilmadi', 404);
    if (count($admins) === 0) jsonError('Kamida bitta admin bo\'lishi kerak');

    file_put_contents($adminsFile, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    logActivity('admin.delete', $delName);

    // Redirect for form submit (non-AJAX)
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $delName . ' o\'chirildi'];
    header('Location: /settings');
    exit;
}

jsonError('Noma\'lum harakat', 400);
