<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

$adminsFile = DATA_DIR . '/admins.json';
$db         = is_file($adminsFile) ? (json_decode(file_get_contents($adminsFile), true) ?? []) : [];
$admins     = $db['admins'] ?? [];

// Find current admin
$myId    = $_SESSION['admin_id'] ?? '';
$myAdmin = null;
foreach ($admins as $a) {
    if ($a['id'] === $myId) { $myAdmin = $a; break; }
}

$pageTitle  = 'Sozlamalar';
$activePage = 'settings';
ob_start();
?>

<div style="max-width:860px;display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

<!-- ── Parol o'zgartirish ── -->
<div class="card">
  <div class="card-header"><span class="card-title">Parolni o'zgartirish</span></div>
  <form id="pwdForm" style="padding:1.5rem;display:flex;flex-direction:column;gap:.875rem">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="change_password">
    <div class="field">
      <label class="label">Joriy parol</label>
      <input class="inp" type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="field">
      <label class="label">Yangi parol</label>
      <input class="inp" type="password" name="new_password" required minlength="6" autocomplete="new-password"
             id="newPwd" oninput="checkPwd()">
    </div>
    <div class="field">
      <label class="label">Yangi parolni tasdiqlang</label>
      <input class="inp" type="password" name="confirm_password" required autocomplete="new-password"
             id="confPwd" oninput="checkPwd()">
      <span id="pwdMatch" style="font-size:.75rem;margin-top:.25rem;display:block"></span>
    </div>
    <button type="submit" class="btn btn-primary" id="pwdBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
      &nbsp;Saqlash
    </button>
    <div id="pwdStatus" style="font-size:.8125rem;min-height:1.25rem"></div>
  </form>
</div>

<!-- ── Profil ── -->
<div class="card">
  <div class="card-header"><span class="card-title">Profil ma'lumotlari</span></div>
  <div style="padding:1.5rem">
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
      <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#913FE2,#7B2FD1);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.4rem">
        <?= strtoupper(substr($myAdmin['username']??'A',0,1)) ?>
      </div>
      <div>
        <div class="font-semibold" style="font-size:1.05rem"><?= htmlspecialchars($myAdmin['username']??'') ?></div>
        <span class="badge badge-purple" style="margin-top:.25rem"><?= htmlspecialchars($myAdmin['role']??'admin') ?></span>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:.5rem">
      <div style="display:flex;justify-content:space-between;padding:.625rem 0;border-bottom:1px solid var(--border)">
        <span class="text-sm text-muted">ID</span>
        <span class="text-sm font-semibold" style="font-family:monospace"><?= htmlspecialchars(substr($myAdmin['id']??'',0,12)) ?>…</span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:.625rem 0;border-bottom:1px solid var(--border)">
        <span class="text-sm text-muted">Yaratilgan</span>
        <span class="text-sm"><?= htmlspecialchars($myAdmin['createdAt']??'—') ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:.625rem 0">
        <span class="text-sm text-muted">Session</span>
        <span class="text-sm badge badge-gray">2 soat timeout</span>
      </div>
    </div>
  </div>
</div>

<!-- ── Adminlar ro'yxati ── -->
<div class="card" style="grid-column:1/-1">
  <div class="card-header">
    <span class="card-title">Adminlar</span>
    <span class="badge badge-purple"><?= count($admins) ?> ta</span>
  </div>

  <!-- Add admin form -->
  <form id="addAdminForm" style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="add_admin">
    <div class="field" style="margin:0;flex:1;min-width:160px">
      <label class="label">Username</label>
      <input class="inp" type="text" name="username" required pattern="[a-zA-Z0-9_]{3,20}" placeholder="foydalanuvchi">
    </div>
    <div class="field" style="margin:0;flex:1;min-width:160px">
      <label class="label">Parol</label>
      <input class="inp" type="password" name="password" required minlength="6" placeholder="kamida 6 belgi">
    </div>
    <div class="field" style="margin:0">
      <label class="label">Rol</label>
      <select class="inp" name="role">
        <option value="admin">admin</option>
        <option value="superadmin">superadmin</option>
      </select>
    </div>
    <button type="submit" class="btn btn-success" style="margin-top:1.375rem">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      &nbsp;Qo'shish
    </button>
    <div id="addStatus" style="font-size:.8125rem;align-self:flex-end;padding-bottom:.4rem"></div>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Username</th><th>Rol</th><th>Yaratilgan</th><th style="width:80px">Amallar</th></tr></thead>
      <tbody>
      <?php foreach($admins as $a): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.625rem">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#913FE2,#7B2FD1);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0">
              <?= strtoupper(substr($a['username'],0,1)) ?>
            </div>
            <span class="font-semibold"><?= htmlspecialchars($a['username']) ?></span>
            <?php if($a['id']===$myId): ?><span class="badge badge-gray" style="font-size:.65rem">Sen</span><?php endif; ?>
          </div>
        </td>
        <td><span class="badge <?= $a['role']==='superadmin'?'badge-purple':'badge-gray' ?>"><?= htmlspecialchars($a['role']) ?></span></td>
        <td class="text-sm text-muted"><?= htmlspecialchars($a['createdAt']??'—') ?></td>
        <td>
          <?php if($a['id'] !== $myId): ?>
          <form method="POST" action="/api/admin-save"
                onsubmit="return confirm('<?= htmlspecialchars($a['username']) ?>ni o\'chirasizmi?')">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="delete_admin">
            <input type="hidden" name="id" value="<?= htmlspecialchars($a['id']) ?>">
            <button type="submit" class="btn btn-sm btn-danger" title="O'chirish">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </form>
          <?php else: ?>
          <span class="text-xs text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div><!-- /grid -->

<style>
@media(max-width:680px){
  div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important}
  div[style*="grid-column:1/-1"]{grid-column:auto!important}
}
</style>

<script>
function checkPwd() {
  var n = document.getElementById('newPwd').value;
  var c = document.getElementById('confPwd').value;
  var s = document.getElementById('pwdMatch');
  if (!c) { s.textContent=''; return; }
  if (n === c) { s.textContent='✓ Mos keladi'; s.style.color='#22c55e'; }
  else { s.textContent='✗ Mos kelmaydi'; s.style.color='#ef4444'; }
}

function submitJson(form, statusEl, btn, originalText) {
  btn.disabled = true; btn.textContent = 'Saqlanmoqda...';
  statusEl.textContent = '';
  fetch('/api/admin-save', { method:'POST', body: new FormData(form) })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        statusEl.textContent = '✓ ' + (d.msg || 'Saqlandi');
        statusEl.style.color = '#22c55e';
        if (d.reload) setTimeout(() => location.reload(), 800);
      } else {
        statusEl.textContent = '✗ ' + (d.error || 'Xato');
        statusEl.style.color = '#ef4444';
      }
    })
    .catch(() => { statusEl.textContent = '✗ Tarmoq xatosi'; statusEl.style.color='#ef4444'; })
    .finally(() => { btn.disabled=false; btn.textContent=originalText; });
}

document.getElementById('pwdForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var n = document.getElementById('newPwd').value;
  var c = document.getElementById('confPwd').value;
  if (n !== c) { alert('Parollar mos kelmaydi!'); return; }
  submitJson(this, document.getElementById('pwdStatus'),
             document.getElementById('pwdBtn'), 'Saqlash');
});

document.getElementById('addAdminForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var btn = this.querySelector('button[type=submit]');
  submitJson(this, document.getElementById('addStatus'), btn, "Qo'shish");
  this.reset();
});
</script>

<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
