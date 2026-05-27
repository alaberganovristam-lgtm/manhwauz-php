<?php
/**
 * Admin layout wrapper
 * Usage:
 *   $pageTitle   = 'Page Title';
 *   $activePage  = 'comics';   // sidebar highlight
 *   ob_start(); ... page content ... $content = ob_get_clean();
 *   require ROOT . '/includes/layout.php';
 */
$admin = currentAdmin();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — Manhwa UZ Admin</title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23913FE2'/><text x='50%25' y='58%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-weight='800' font-size='11' font-family='sans-serif'>ADM</text></svg>">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">MU</div>
    <div>
      <div class="logo-name">Manhwa UZ</div>
      <div class="logo-sub">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Bosh sahifa</div>
    <a href="/" class="nav-item <?= ($activePage??'')==='dashboard' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <div class="nav-section">Kontent</div>
    <a href="/comics" class="nav-item <?= ($activePage??'')==='comics' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      Komikslar
    </a>
    <a href="/chapters" class="nav-item <?= ($activePage??'')==='chapters' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h7"/></svg>
      Boblar
    </a>
    <a href="/chapters/upload" class="nav-item <?= ($activePage??'')==='upload' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      Bob yuklash
    </a>

    <div class="nav-section">Foydalanuvchilar</div>
    <a href="/users" class="nav-item <?= ($activePage??'')==='users' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Foydalanuvchilar
    </a>

    <div class="nav-section">Tizim</div>
    <a href="/logs" class="nav-item <?= ($activePage??'')==='logs' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Activity Log
    </a>
    <a href="/settings" class="nav-item <?= ($activePage??'')==='settings' ? 'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
      Sozlamalar
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar"><?= strtoupper(substr($admin['username']??'A',0,1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($admin['username']??'Admin') ?></div>
        <div class="admin-role"><?= htmlspecialchars($admin['role']??'admin') ?></div>
      </div>
    </div>
    <a href="/logout" class="btn-logout" title="Chiqish">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
    </a>
  </div>
</aside>

<!-- Main content -->
<div class="main-wrap">
  <!-- Top bar -->
  <header class="topbar">
    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    </button>
    <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
    <div class="topbar-right">
      <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-sm btn-ghost">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        Saytni ko'rish
      </a>
    </div>
  </header>

  <!-- Flash messages -->
  <?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash flash-<?= $_SESSION['flash']['type'] ?>">
    <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    <button onclick="this.parentElement.remove()" class="flash-close">×</button>
  </div>
  <?php unset($_SESSION['flash']); endif; ?>

  <main class="content">
    <?= $content ?? '' ?>
  </main>
</div>

<script src="/assets/app.js"></script>
</body>
</html>
