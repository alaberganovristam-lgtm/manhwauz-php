<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

$usersFile = dirname(__DIR__, 2) . '/asurascans.com/users.json';
$users = [];
if (is_file($usersFile)) {
    $ud    = json_decode(file_get_contents($usersFile), true) ?? [];
    $users = $ud['users'] ?? [];
}

$pageTitle  = 'Foydalanuvchilar';
$activePage = 'users';
ob_start();
?>
<div class="card">
  <div class="card-header">
    <span class="card-title">Foydalanuvchilar ro'yxati</span>
    <span class="badge badge-purple"><?= count($users) ?> ta</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ism</th><th>Email</th><th>Sana</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.625rem">
            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#913FE2,#7B2FD1);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8125rem;flex-shrink:0">
              <?= strtoupper(substr($u['name']??'U',0,1)) ?>
            </div>
            <span class="font-semibold"><?= htmlspecialchars($u['name']??'—') ?></span>
          </div>
        </td>
        <td class="text-sm text-muted"><?= htmlspecialchars($u['email']??'') ?></td>
        <td class="text-sm text-muted"><?= htmlspecialchars($u['createdAt']??'') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($users)): ?>
      <tr><td colspan="3" style="text-align:center;padding:3rem;color:var(--text3)">Hali foydalanuvchi yo'q</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
