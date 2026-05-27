<?php
/**
 * Admin login / logout handler
 * Called by login.php (form POST) and logout.php
 */
require_once __DIR__ . '/includes/functions.php';

$adminsFile = DATA_DIR . '/admins.json';

// ── Seed default admin if file missing ────────────────────────
if (!is_file($adminsFile)) {
    // DATA_DIR mavjudligini tekshirish
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0700, true);
    }

    // Kuchli random parol generatsiya qilish (32 belgi hex)
    $generatedPassword = bin2hex(random_bytes(16));
    $setupFile = DATA_DIR . '/admin_setup.txt';

    $setupContent = implode("\n", [
        '=== MANHWA UZ ADMIN SETUP ===',
        'Ushbu fayl faqat bir marta yaratiladi.',
        'Parolni saqlang va bu faylni o\'chiring!',
        '',
        'Login:    manhwauz',
        'Parol:    ' . $generatedPassword,
        '',
        'Yaratilgan: ' . date('Y-m-d H:i:s'),
        '===========================',
    ]);
    file_put_contents($setupFile, $setupContent, LOCK_EX);

    // Unix da ruxsatlarni cheklash (Windows da chmod ta'sir qilmaydi, lekin xato ham chiqarmaydi)
    if (DIRECTORY_SEPARATOR === '/') {
        chmod($setupFile, 0600);
    }

    $default = [
        'admins' => [[
            'id'       => bin2hex(random_bytes(8)),
            'username' => 'manhwauz',
            'password' => password_hash($generatedPassword, PASSWORD_DEFAULT),
            'role'     => 'superadmin',
            'createdAt'=> date('Y-m-d'),
        ]]
    ];
    file_put_contents($adminsFile, json_encode($default, JSON_PRETTY_PRINT), LOCK_EX);
    if (DIRECTORY_SEPARATOR === '/') {
        chmod($adminsFile, 0600);
    }
}

function loadAdmins(): array {
    global $adminsFile;
    return json_decode(file_get_contents($adminsFile), true) ?? ['admins' => []];
}

// ── Login ─────────────────────────────────────────────────────
function doLogin(string $username, string $password): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    if (checkBruteForce($ip)) return false;

    $db = loadAdmins();
    foreach ($db['admins'] as $a) {
        if ($a['username'] === $username && password_verify($password, $a['password'])) {
            clearAttempts($ip);
            $_SESSION['admin_id'] = $a['id'];
            $_SESSION['admin']    = ['username' => $a['username'], 'role' => $a['role']];
            logActivity('login', 'Muvaffaqiyatli kirish');
            return true;
        }
    }
    recordFailedAttempt($ip);
    logActivity('login.fail', "Noto'g'ri parol: $username");
    return false;
}

// ── Logout ────────────────────────────────────────────────────
function doLogout(): void {
    logActivity('logout', '');
    $_SESSION = [];
    session_destroy();
}
