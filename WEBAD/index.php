<?php
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$comics  = loadComics();
$nComics = count($comics);
$nChaps  = array_sum(array_map(fn($c) => count($c['chapters'] ?? []), $comics));
$usersF  = dirname(__DIR__) . '/asurascans.com/users.json';
$nUsers  = 0;
if (is_file($usersF)) {
    $ud = json_decode(file_get_contents($usersF), true);
    $nUsers = count($ud['users'] ?? []);
}

// Activity log last 10 lines
$logFile   = DATA_DIR . '/activity.log';
$logLines  = [];
if (is_file($logFile)) {
    $all = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logLines = array_slice(array_reverse($all), 0, 10);
}

// Recent comics (last 5 by latestTs or createdAt)
usort($comics, fn($a,$b) => strcmp($b['createdAt']??'', $a['createdAt']??''));
$recent = array_slice($comics, 0, 5);

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
ob_start();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(145,63,226,.15)">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#913FE2" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
    </div>
    <div class="stat-value"><?= $nComics ?></div>
    <div class="stat-label">Komikslar</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(34,197,94,.12)">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <div class="stat-value"><?= $nChaps ?></div>
    <div class="stat-label">Jami boblar</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(234,179,8,.12)">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#eab308" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div class="stat-value"><?= $nUsers ?></div>
    <div class="stat-label">Foydalanuvchilar</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(239,68,68,.12)">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
    </div>
    <div class="stat-value"><?= is_dir(UPLOADS_DIR) ? count(glob(UPLOADS_DIR.'/*/*')) : 0 ?></div>
    <div class="stat-label">Yuklangan boblar</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">

  <!-- Recent comics -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">So'nggi komikslar</span>
      <a href="/comics" class="btn btn-sm btn-ghost">Barchasi</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Cover</th><th>Nomi</th><th>Boblar</th></tr></thead>
        <tbody>
        <?php foreach(array_slice($comics,0,5) as $c): ?>
        <tr>
          <td class="td-cover"><img src="<?= htmlspecialchars(coverUrl($c['cover']??'')) ?>" loading="lazy" onerror="this.style.background='#252230'"></td>
          <td><a href="/comics/edit?slug=<?= urlencode($c['slug']) ?>" style="color:var(--purple-l);text-decoration:none;font-weight:500" class="truncate" title="<?= htmlspecialchars($c['title']) ?>"><?= htmlspecialchars($c['title']) ?></a></td>
          <td><span class="badge badge-purple"><?= count($c['chapters']??[]) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activity log -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Oxirgi faollik</span>
      <a href="/logs" class="btn btn-sm btn-ghost">Hammasi</a>
    </div>
    <div class="card-body" style="padding:.5rem 0">
      <?php if(empty($logLines)): ?>
        <p class="text-sm text-muted" style="padding:.75rem 1.25rem">Hali faollik yo'q</p>
      <?php else: ?>
        <?php foreach($logLines as $line):
          $parts = explode(' | ', $line);
        ?>
        <div style="display:flex;align-items:flex-start;gap:.625rem;padding:.5rem 1.25rem;border-bottom:1px solid var(--border)">
          <div style="flex:1;min-width:0">
            <div class="text-sm truncate"><?= htmlspecialchars($parts[3]??'') ?></div>
            <div class="text-xs text-muted"><?= htmlspecialchars($parts[0]??'') ?> — <?= htmlspecialchars($parts[1]??'') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Quick actions -->
<div class="card mt-4">
  <div class="card-header"><span class="card-title">Tez harakatlar</span></div>
  <div class="card-body" style="display:flex;gap:.75rem;flex-wrap:wrap">
    <a href="/comics/create" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Yangi komiks
    </a>
    <a href="/chapters/upload" class="btn btn-ghost">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      Bob yuklash
    </a>
    <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-ghost">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      Saytni ko'rish
    </a>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
