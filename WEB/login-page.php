<?php
/**
 * login-page.php — Login & Register page for the local mirror
 * Served at /login and /register
 */
$mode = isset($_GET['mode']) && $_GET['mode'] === 'register' ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $mode === 'register' ? 'Register' : 'Log in' ?> — Manhwa UZ</title>
<meta name="theme-color" content="#913FE2">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='16' fill='%23913FE2'/><text x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-weight='800' font-size='13' font-family='sans-serif'>MU</text></svg>">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  background:#0D0B14;color:#fff;
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:2rem 1rem;position:relative;overflow-x:hidden;
}
/* Diagonal lines pattern */
body::before{
  content:'';position:fixed;inset:0;pointer-events:none;
  background-image:repeating-linear-gradient(-45deg,transparent,transparent 20px,rgba(255,255,255,.018) 20px,rgba(255,255,255,.018) 21px);
}
/* Purple frame corners */
.frame{position:fixed;inset:1rem;pointer-events:none;border:1px solid rgba(145,63,226,.25);border-radius:4px}
.frame::before,.frame::after{content:'';position:absolute;background:#913FE2}
.frame::before{top:-1px;left:50%;transform:translateX(-50%);width:60px;height:2px}
.frame::after{bottom:-1px;left:50%;transform:translateX(-50%);width:60px;height:2px}

a{color:#913FE2;text-decoration:none}
a:hover{text-decoration:underline}

.card{
  background:#1C1924;border:1px solid rgba(255,255,255,.08);
  border-radius:1rem;padding:2rem;width:100%;max-width:420px;
  position:relative;z-index:1;
}

/* Logo */
.logo-wrap{text-align:center;margin-bottom:1.75rem;position:relative;z-index:1}
.logo-circle{
  width:90px;height:90px;border-radius:50%;border:2px solid #913FE2;
  display:inline-flex;align-items:center;justify-content:center;
  background:rgba(145,63,226,.12);margin-bottom:1rem;
}
.logo-title{font-size:1.5rem;font-weight:800;letter-spacing:.04em;line-height:1.2;text-transform:uppercase}
.logo-title span{color:#913FE2;display:block}
.logo-sub{color:rgba(255,255,255,.5);font-size:.875rem;margin-top:.5rem}

/* Google button */
.btn-google{
  width:100%;display:flex;align-items:center;justify-content:center;gap:.75rem;
  background:#fff;color:#1a1a1a;border:none;border-radius:.625rem;
  padding:.75rem 1rem;font-size:.9375rem;font-weight:600;cursor:pointer;
  transition:box-shadow .15s,transform .1s;
}
.btn-google:hover{box-shadow:0 4px 16px rgba(0,0,0,.25);transform:translateY(-1px)}
.btn-google:active{transform:translateY(0)}

/* OR divider */
.or{
  display:flex;align-items:center;gap:.75rem;margin:1.25rem 0;
  color:rgba(255,255,255,.35);font-size:.8125rem;font-weight:500;
}
.or::before,.or::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.1)}

/* Form */
.field{margin-bottom:1rem}
.field label{display:block;font-size:.8125rem;font-weight:500;color:rgba(255,255,255,.7);margin-bottom:.4rem}
.input-wrap{
  display:flex;align-items:center;gap:.625rem;
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
  border-radius:.625rem;padding:.7rem .875rem;transition:border-color .15s;
}
.input-wrap:focus-within{border-color:#913FE2}
.input-wrap svg{flex-shrink:0;opacity:.4}
.input-wrap input{
  flex:1;background:none;border:none;outline:none;color:#fff;
  font-size:.9375rem;min-width:0;
}
.input-wrap input::placeholder{color:rgba(255,255,255,.3)}

/* Submit button */
.btn-submit{
  width:100%;background:#913FE2;color:#fff;border:none;border-radius:.625rem;
  padding:.8rem 1rem;font-size:.9375rem;font-weight:700;cursor:pointer;
  transition:background .15s,transform .1s;margin-top:.25rem;
}
.btn-submit:hover{background:#7c35c2}
.btn-submit:active{transform:scale(.99)}
.btn-submit:disabled{opacity:.6;cursor:not-allowed}

/* Switch link */
.switch{text-align:center;margin-top:1.25rem;font-size:.875rem;color:rgba(255,255,255,.5)}

/* Error / success messages */
.msg{
  border-radius:.5rem;padding:.625rem .875rem;font-size:.8125rem;
  margin-bottom:1rem;display:none;
}
.msg.error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.msg.success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);color:#86efac}

/* Tabs */
.tabs{display:flex;gap:.25rem;background:rgba(255,255,255,.05);border-radius:.5rem;padding:.25rem;margin-bottom:1.5rem}
.tab{
  flex:1;text-align:center;padding:.5rem;border-radius:.375rem;
  font-size:.875rem;font-weight:600;cursor:pointer;transition:background .15s,color .15s;
  color:rgba(255,255,255,.5);border:none;background:none;
}
.tab.active{background:#913FE2;color:#fff}
</style>
</head>
<body>
<div class="frame"></div>

<!-- Logo -->
<div class="logo-wrap">
  <div class="logo-circle">
    <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
      <circle cx="24" cy="16" r="10" fill="#913FE2" opacity=".9"/>
      <path d="M8 42c0-8.837 7.163-16 16-16s16 7.163 16 16" stroke="#913FE2" stroke-width="2.5" stroke-linecap="round"/>
      <circle cx="24" cy="16" r="6" fill="#1C1924"/>
      <path d="M20 14c0-2.21 1.79-4 4-4s4 1.79 4 4" stroke="#913FE2" stroke-width="1.5"/>
    </svg>
  </div>
  <div class="logo-title">Welcome to<span>Manhwa UZ</span></div>
  <div class="logo-sub">Log in or create an account to start reading.</div>
</div>

<!-- Card -->
<div class="card">
  <!-- Tabs -->
  <div class="tabs">
    <button class="tab <?= $mode === 'login'    ? 'active' : '' ?>" onclick="switchMode('login')">Log In</button>
    <button class="tab <?= $mode === 'register' ? 'active' : '' ?>" onclick="switchMode('register')">Register</button>
  </div>

  <!-- Google -->
  <button class="btn-google" onclick="googleClick()">
    <svg width="20" height="20" viewBox="0 0 48 48">
      <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
      <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
      <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
      <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
    </svg>
    Continue with Google
  </button>

  <div class="or">OR</div>

  <!-- Error/Success messages -->
  <div class="msg error" id="msg-error"></div>
  <div class="msg success" id="msg-ok"></div>

  <!-- Form -->
  <form id="auth-form" onsubmit="submitForm(event)">
    <div class="field">
      <label>Email address</label>
      <div class="input-wrap">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <input type="email" id="inp-email" placeholder="you@example.com" required autocomplete="email">
      </div>
    </div>

    <div class="field">
      <label id="lbl-pass">
        Password
        <a href="#" id="forgot-link" style="float:right;font-size:.8125rem" onclick="return false">Forgot password?</a>
      </label>
      <div class="input-wrap">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <input type="password" id="inp-pass" placeholder="Enter your password" required autocomplete="current-password" minlength="8">
      </div>
    </div>

    <!-- Register only: confirm password -->
    <div class="field" id="field-confirm" style="display:none">
      <label>Confirm password</label>
      <div class="input-wrap">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <input type="password" id="inp-confirm" placeholder="Confirm your password" autocomplete="new-password" minlength="8">
      </div>
    </div>

    <button type="submit" class="btn-submit" id="btn-submit">Log In</button>
  </form>

  <!-- Switch mode -->
  <div class="switch" id="switch-msg">
    Don't have an account? <a href="#" onclick="switchMode('register');return false">Sign up</a>
  </div>
</div>

<script>
var currentMode = '<?= $mode ?>';

function switchMode(m) {
  currentMode = m || (currentMode === 'login' ? 'register' : 'login');
  var isReg = currentMode === 'register';

  document.querySelectorAll('.tab').forEach(function(t, i) {
    t.classList.toggle('active', i === (isReg ? 1 : 0));
  });
  document.getElementById('field-confirm').style.display = isReg ? 'block' : 'none';
  document.getElementById('inp-confirm').required = isReg;
  document.getElementById('inp-pass').placeholder   = isReg ? 'Min 8 characters' : 'Enter your password';
  document.getElementById('inp-pass').autocomplete  = isReg ? 'new-password' : 'current-password';
  document.getElementById('lbl-pass').firstChild.textContent = 'Password';
  document.getElementById('forgot-link').style.display = isReg ? 'none' : 'inline';
  document.getElementById('btn-submit').textContent  = isReg ? 'Create Account' : 'Log In';
  document.getElementById('switch-msg').innerHTML    = isReg
    ? 'Already have an account? <a href="#" onclick="switchMode(\'login\');return false">Log in</a>'
    : 'Don\'t have an account? <a href="#" onclick="switchMode(\'register\');return false">Sign up</a>';
  clearMsg();
  history.replaceState(null, '', isReg ? '/register' : '/login');
}

// Run on load to set correct state
switchMode(currentMode);

function showError(msg) {
  var el = document.getElementById('msg-error');
  el.textContent = msg; el.style.display = 'block';
  document.getElementById('msg-ok').style.display = 'none';
}
function showSuccess(msg) {
  var el = document.getElementById('msg-ok');
  el.textContent = msg; el.style.display = 'block';
  document.getElementById('msg-error').style.display = 'none';
}
function clearMsg() {
  document.getElementById('msg-error').style.display = 'none';
  document.getElementById('msg-ok').style.display = 'none';
}

function submitForm(e) {
  e.preventDefault();
  clearMsg();
  var btn   = document.getElementById('btn-submit');
  var email = document.getElementById('inp-email').value.trim();
  var pass  = document.getElementById('inp-pass').value;
  var conf  = document.getElementById('inp-confirm').value;

  if (currentMode === 'register' && pass !== conf) {
    showError('Parollar mos kelmaydi'); return;
  }
  if (pass.length < 8) {
    showError('Parol kamida 8 ta belgi bo\'lishi kerak'); return;
  }

  btn.disabled = true;
  btn.textContent = currentMode === 'register' ? 'Ro\'yxatdan o\'tilmoqda...' : 'Kirilmoqda...';

  fetch('/api/auth/' + currentMode, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ email: email, password: pass }),
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    btn.disabled = false;
    btn.textContent = currentMode === 'register' ? 'Create Account' : 'Log In';
    if (d.ok) {
      showSuccess(currentMode === 'register' ? 'Muvaffaqiyatli ro\'yxatdan o\'tdingiz!' : 'Xush kelibsiz, ' + d.user.name + '!');
      setTimeout(function() { location.href = '/'; }, 900);
    } else {
      showError(d.error || 'Xatolik yuz berdi');
    }
  })
  .catch(function() {
    btn.disabled = false;
    btn.textContent = currentMode === 'register' ? 'Create Account' : 'Log In';
    showError('Server bilan bog\'lanib bo\'lmadi');
  });
}

function googleClick() {
  // Google OAuth requires real domain — redirect to asurascans.com login
  // OR show info
  var t = document.createElement('div');
  t.style.cssText='position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);z-index:9999;background:#1C1924;color:#fff;padding:.75rem 1.25rem;border-radius:.75rem;border:1px solid rgba(255,255,255,.12);box-shadow:0 8px 32px rgba(0,0,0,.5);font-size:.8125rem;white-space:nowrap;text-align:center';
  t.innerHTML = '<b style="color:#913FE2">Google login</b> faqat asurascans.com da ishlaydi.<br><a href="https://asurascans.com/login" target="_blank" style="color:#A78BFA">Haqiqiy saytga o\'tish →</a>';
  document.body.appendChild(t);
  setTimeout(function(){ t.style.opacity='0'; t.style.transition='opacity .3s'; setTimeout(function(){ t.remove(); },300); }, 4000);
}
</script>
</body>
</html>
