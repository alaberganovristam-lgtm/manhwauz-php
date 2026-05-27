<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) { header('Location: /'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    if (checkBruteForce($ip)) {
        $error = 'Juda ko\'p urinish. 15 daqiqa kuting.';
    } else {
        $ok = doLogin(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
        if ($ok) { header('Location: /'); exit; }
        else $error = 'Login yoki parol noto\'g\'ri.';
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Manhwa UZ</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="/assets/style.css">
<style>
body{margin:0;background:var(--bg)}
.login-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#913FE2,#7B2FD1);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;margin:0 auto .875rem">MU</div>
      <div class="login-title">Admin Panel</div>
      <div class="login-sub">Manhwa UZ boshqaruv tizimi</div>
    </div>

    <?php if($error): ?>
    <div class="flash flash-error" style="border-radius:.5rem;margin-bottom:1rem"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
      <div class="form-group" style="margin-bottom:.875rem">
        <label class="lbl">Login</label>
        <input class="inp" type="text" name="username" placeholder="manhwauz" required autofocus autocomplete="username">
      </div>
      <div class="form-group" style="margin-bottom:1.25rem">
        <label class="lbl">Parol</label>
        <input class="inp" type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button class="btn btn-primary w-full" style="justify-content:center;padding:.7rem">Kirish</button>
    </form>

    <div style="margin-top:1rem;padding-top:.875rem;border-top:1px solid var(--border);font-size:.75rem;color:var(--text3);text-align:center">
      manhwauz.com Admin Panel
    </div>
  </div>
</div>
</body>
</html>
