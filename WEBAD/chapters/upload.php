<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

$comics     = loadComics();
$preSlug    = $_GET['slug'] ?? '';
$preComic   = $preSlug ? getComic($preSlug) : null;

$pageTitle  = 'Bob yuklash';
$activePage = 'upload';
ob_start();
?>
<meta name="csrf" content="<?= csrfToken() ?>">

<div style="display:grid;grid-template-columns:340px 1fr;gap:1.25rem;align-items:start">

<!-- LEFT: Settings panel -->
<div>
  <div class="card mb-4">
    <div class="card-header"><span class="card-title">Bob sozlamalari</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:.875rem">

      <!-- Comic select -->
      <div class="form-group">
        <label class="lbl">Komiks</label>
        <select class="inp" id="sel-comic">
          <option value="">— Tanlang —</option>
          <?php foreach($comics as $c): ?>
          <option value="<?= htmlspecialchars($c['slug']) ?>"
            <?= $c['slug']===$preSlug?'selected':'' ?>>
            <?= htmlspecialchars($c['title']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Chapter number -->
      <div class="form-group">
        <label class="lbl">Bob raqami</label>
        <input class="inp" type="number" id="inp-chapter" min="1" step="1" placeholder="Masalan: 15">
      </div>

      <!-- Output format -->
      <div class="form-group">
        <label class="lbl">Saqlash formati</label>
        <div class="format-tabs" id="fmt-tabs">
          <div class="format-tab active" data-fmt="webp">WebP <span style="font-size:.7rem;opacity:.6">(tavsiya)</span></div>
          <div class="format-tab" data-fmt="jpg">JPG</div>
          <div class="format-tab" data-fmt="pdf">PDF</div>
        </div>
        <input type="hidden" id="inp-format" value="webp">
      </div>

      <!-- Quality (hidden for PDF) -->
      <div class="form-group" id="quality-wrap">
        <label class="lbl">Sifat: <span id="quality-val">85</span>%</label>
        <input type="range" id="inp-quality" min="50" max="100" value="85"
               oninput="document.getElementById('quality-val').textContent=this.value">
      </div>

      <!-- PDF note -->
      <div id="pdf-note" class="hidden" style="background:rgba(145,63,226,.1);border:1px solid rgba(145,63,226,.2);border-radius:.5rem;padding:.625rem .875rem;font-size:.8125rem;color:var(--purple-l)">
        📄 PDF rejimda barcha rasmlar bitta .pdf faylga birlashtiriladi.
      </div>

      <!-- Auto-number -->
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.875rem;color:var(--text2)">
          <input type="checkbox" id="chk-autonumber" checked style="accent-color:var(--purple)">
          Rasmlarni tartib raqami bilan saqlash (001.webp, 002.webp…)
        </label>
      </div>

    </div>
  </div>

  <!-- Existing chapters of selected comic -->
  <div class="card" id="existing-card" style="display:none">
    <div class="card-header">
      <span class="card-title">Mavjud boblar</span>
      <span class="badge badge-purple" id="existing-count">0</span>
    </div>
    <div id="existing-list" style="max-height:200px;overflow-y:auto;padding:.5rem 0"></div>
  </div>
</div>

<!-- RIGHT: Upload zone + file grid -->
<div>
  <div class="card">
    <div class="card-header">
      <span class="card-title">Rasmlar</span>
      <div style="display:flex;align-items:center;gap:.75rem">
        <span id="file-count-badge" class="badge badge-gray hidden">0 fayl</span>
        <button class="btn btn-sm btn-ghost" id="btn-clear" onclick="clearFiles()" style="display:none">
          Tozalash
        </button>
      </div>
    </div>
    <div class="card-body">

      <!-- Drop zone -->
      <div class="drop-zone" id="drop-zone">
        <input type="file" id="file-input" multiple
               accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp">
        <div class="drop-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        </div>
        <div class="drop-title">Rasmlarni shu yerga tashlang</div>
        <div class="drop-sub">yoki bosib fayllarni tanlang<br>
          <strong style="color:var(--purple-l)">Bir vaqtda 300 tagacha rasm</strong><br>
          <span style="font-size:.75rem">JPG, PNG, GIF, WebP, AVIF, BMP</span>
        </div>
      </div>

      <!-- File grid preview -->
      <div class="file-grid" id="file-grid"></div>

      <!-- Upload button -->
      <div id="upload-actions" style="display:none;margin-top:1rem;display:none">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
          <button class="btn btn-primary" id="btn-upload" onclick="startUpload()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Yuklashni boshlash
          </button>
          <span class="text-sm text-muted">Jami: <strong id="total-size">0 MB</strong></span>
        </div>
      </div>

      <!-- Progress -->
      <div class="progress-wrap" id="progress-wrap">
        <div class="progress-info">
          <span id="progress-text">Tayyorlanmoqda…</span>
          <span id="progress-pct">0%</span>
        </div>
        <div class="progress-bar-bg"><div class="progress-bar" id="progress-bar"></div></div>
        <div class="progress-log" id="progress-log"></div>
      </div>

      <!-- Result -->
      <div class="upload-result" id="upload-result"></div>

    </div>
  </div>
</div>
</div>

<script>
(function(){
'use strict';

/* ── State ─────────────────────────────── */
var files = [];           // ordered file list
var dragSrcIdx = null;

/* ── DOM refs ──────────────────────────── */
var dropZone   = document.getElementById('drop-zone');
var fileInput  = document.getElementById('file-input');
var fileGrid   = document.getElementById('file-grid');
var fmtTabs    = document.querySelectorAll('.format-tab');
var inpFormat  = document.getElementById('inp-format');
var inpQuality = document.getElementById('inp-quality');
var selComic   = document.getElementById('sel-comic');
var inpChapter = document.getElementById('inp-chapter');
var btnUpload  = document.getElementById('btn-upload');
var btnClear   = document.getElementById('btn-clear');
var uploadAct  = document.getElementById('upload-actions');
var countBadge = document.getElementById('file-count-badge');
var totalSizeEl= document.getElementById('total-size');
var progressWr = document.getElementById('progress-wrap');
var progressBar= document.getElementById('progress-bar');
var progressTxt= document.getElementById('progress-text');
var progressPct= document.getElementById('progress-pct');
var progressLog= document.getElementById('progress-log');
var uploadRes  = document.getElementById('upload-result');
var qWrap      = document.getElementById('quality-wrap');
var pdfNote    = document.getElementById('pdf-note');
var existCard  = document.getElementById('existing-card');
var existList  = document.getElementById('existing-list');
var existCount = document.getElementById('existing-count');

/* ── Format tabs ───────────────────────── */
fmtTabs.forEach(function(tab){
  tab.addEventListener('click', function(){
    fmtTabs.forEach(function(t){ t.classList.remove('active'); });
    tab.classList.add('active');
    var fmt = tab.dataset.fmt;
    inpFormat.value = fmt;
    qWrap.style.display   = fmt === 'pdf' ? 'none' : '';
    pdfNote.style.display = fmt === 'pdf' ? 'block' : 'none';
  });
});

/* ── Comic select → load existing chapters ─ */
selComic.addEventListener('change', loadExisting);
if(selComic.value) loadExisting();

function loadExisting(){
  var slug = selComic.value;
  if(!slug){ existCard.style.display='none'; return; }
  // Suggest next chapter number
  fetch('/api/stats?slug=' + encodeURIComponent(slug), {credentials:'same-origin'})
    .then(function(r){ return r.json(); })
    .then(function(d){
      if(!d.ok) return;
      existCard.style.display = 'block';
      existCount.textContent  = d.chapters.length + ' ta';
      var maxCh = d.chapters.reduce(function(m,c){ return Math.max(m, c.number); }, 0);
      if(!inpChapter.value) inpChapter.value = maxCh + 1;
      existList.innerHTML = d.chapters.slice(0,20).map(function(c){
        return '<div style="display:flex;align-items:center;justify-content:space-between;padding:.4rem 1rem;border-bottom:1px solid var(--border);font-size:.8125rem">'
          + '<span>Bob ' + c.number + '</span>'
          + '<span style="color:var(--text3)">' + (c.date||'') + '</span>'
          + '</div>';
      }).join('') + (d.chapters.length > 20 ? '<div style="padding:.5rem 1rem;font-size:.75rem;color:var(--text3)">…va ' + (d.chapters.length-20) + ' ta ko\'proq</div>' : '');
    }).catch(function(){});
}

/* ── Drop zone ─────────────────────────── */
dropZone.addEventListener('dragover', function(e){
  e.preventDefault(); dropZone.classList.add('drag-over');
});
dropZone.addEventListener('dragleave', function(){
  dropZone.classList.remove('drag-over');
});
dropZone.addEventListener('drop', function(e){
  e.preventDefault(); dropZone.classList.remove('drag-over');
  addFiles(Array.from(e.dataTransfer.files));
});
fileInput.addEventListener('change', function(){
  addFiles(Array.from(this.files));
  this.value = '';
});

/* ── Add files ─────────────────────────── */
function addFiles(newFiles){
  var imgTypes = ['image/jpeg','image/png','image/gif','image/webp','image/avif','image/bmp'];
  newFiles = newFiles.filter(function(f){ return imgTypes.indexOf(f.type) !== -1; });
  newFiles = newFiles.slice(0, 300 - files.length); // max 300
  files = files.concat(newFiles);
  renderGrid();
}

function clearFiles(){
  files = [];
  renderGrid();
  uploadRes.classList.remove('show','has-errors');
  uploadRes.innerHTML = '';
  progressWr.classList.remove('show');
}
window.clearFiles = clearFiles;

/* ── Render file grid ──────────────────── */
function renderGrid(){
  var totalBytes = files.reduce(function(s,f){ return s+f.size; }, 0);

  if(files.length === 0){
    fileGrid.innerHTML = '';
    uploadAct.style.display = 'none';
    btnClear.style.display  = 'none';
    countBadge.classList.add('hidden');
    return;
  }

  countBadge.textContent = files.length + ' fayl';
  countBadge.classList.remove('hidden');
  totalSizeEl.textContent = formatBytes(totalBytes);
  uploadAct.style.display = 'flex';
  btnClear.style.display  = 'inline-flex';

  fileGrid.innerHTML = '';
  files.forEach(function(f, i){
    var item = document.createElement('div');
    item.className = 'file-item';
    item.draggable = true;
    item.dataset.idx = i;

    var num = document.createElement('div');
    num.className = 'file-item-num';
    num.textContent = i + 1;

    var img = document.createElement('img');
    img.loading = 'lazy';
    img.alt = f.name;
    var url = URL.createObjectURL(f);
    img.src = url;
    img.onload = function(){ URL.revokeObjectURL(url); };

    var del = document.createElement('button');
    del.className = 'file-item-del';
    del.textContent = '×';
    del.title = 'O\'chirish';
    del.addEventListener('click', function(e){
      e.stopPropagation();
      files.splice(i, 1);
      renderGrid();
    });

    var name = document.createElement('div');
    name.className = 'file-item-name';
    name.textContent = f.name;

    item.appendChild(num);
    item.appendChild(img);
    item.appendChild(del);
    item.appendChild(name);

    // ── Drag-to-reorder ──
    item.addEventListener('dragstart', function(e){
      dragSrcIdx = i;
      item.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    item.addEventListener('dragend', function(){
      item.classList.remove('dragging');
      document.querySelectorAll('.file-item').forEach(function(el){
        el.classList.remove('drag-target');
      });
    });
    item.addEventListener('dragover', function(e){
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      document.querySelectorAll('.file-item').forEach(function(el){ el.classList.remove('drag-target'); });
      item.classList.add('drag-target');
    });
    item.addEventListener('drop', function(e){
      e.preventDefault();
      if(dragSrcIdx === null || dragSrcIdx === i) return;
      var moved = files.splice(dragSrcIdx, 1)[0];
      files.splice(i, 0, moved);
      dragSrcIdx = null;
      renderGrid();
    });

    fileGrid.appendChild(item);
  });
}

/* ── Format bytes ──────────────────────── */
function formatBytes(b){
  if(b >= 1073741824) return (b/1073741824).toFixed(1) + ' GB';
  if(b >= 1048576)    return (b/1048576).toFixed(1) + ' MB';
  if(b >= 1024)       return (b/1024).toFixed(1) + ' KB';
  return b + ' B';
}

/* ── Log helper ────────────────────────── */
function log(msg, cls){
  var line = document.createElement('div');
  line.className = cls || '';
  line.textContent = msg;
  progressLog.appendChild(line);
  progressLog.scrollTop = progressLog.scrollHeight;
}

/* ── Upload ─────────────────────────────── */
window.startUpload = function(){
  var slug    = selComic.value;
  var chapter = parseInt(inpChapter.value, 10);
  var fmt     = inpFormat.value;
  var quality = parseInt(inpQuality.value, 10);
  var autoNum = document.getElementById('chk-autonumber').checked;

  if(!slug)         { alert('Komikni tanlang'); return; }
  if(!chapter || chapter < 1) { alert('Bob raqamini kiriting'); return; }
  if(files.length === 0)      { alert('Rasmlarni tanlang'); return; }

  btnUpload.disabled = true;
  btnUpload.textContent = 'Yuklanmoqda…';
  progressWr.classList.add('show');
  progressLog.innerHTML = '';
  uploadRes.classList.remove('show','has-errors');
  uploadRes.innerHTML = '';

  log('🚀 ' + files.length + ' ta fayl yuklash boshlandi (format: ' + fmt.toUpperCase() + ')', 'log-info');

  var BATCH = 20;   // files per chunk
  var total  = files.length;
  var done   = 0;
  var errors = 0;
  var batches= [];

  for(var i = 0; i < total; i += BATCH){
    batches.push({ files: files.slice(i, i+BATCH), startIdx: i });
  }

  function updateProgress(){
    var pct = Math.round(done / total * 100);
    progressBar.style.width = pct + '%';
    progressPct.textContent = pct + '%';
    progressTxt.textContent = done + ' / ' + total + ' ta rasm yuklandi';
  }

  function uploadBatch(batchIdx){
    if(batchIdx >= batches.length){
      // All done
      btnUpload.disabled = false;
      btnUpload.textContent = 'Yuklashni boshlash';
      var msg = '✅ ' + (done - errors) + ' ta muvaffaqiyatli, ' + errors + ' ta xato';
      log(msg, errors ? 'log-err' : 'log-ok');

      uploadRes.classList.add('show');
      if(errors) uploadRes.classList.add('has-errors');
      uploadRes.innerHTML = '<strong>' + (done-errors) + '/' + total + '</strong> ta rasm saqlandi → '
        + '<code style="font-size:.8125rem">/uploads/' + slug + '/chapter-' + chapter + '/</code>';

      // Reload existing list
      loadExisting();
      return;
    }

    var batch = batches[batchIdx];
    var fd    = new FormData();
    fd.append('csrf',    window.CSRF || document.querySelector('meta[name=csrf]').content);
    fd.append('slug',    slug);
    fd.append('chapter', chapter);
    fd.append('format',  fmt);
    fd.append('quality', quality);
    fd.append('autoNum', autoNum ? '1' : '0');
    fd.append('startIdx', batch.startIdx);

    batch.files.forEach(function(f, i){
      fd.append('images[]', f);
    });

    log('📦 Paket ' + (batchIdx+1) + '/' + batches.length + ' yuborilmoqda (' + batch.files.length + ' fayl)…', 'log-info');

    fetch('/api/upload-chunk', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if(d.results){
        d.results.forEach(function(r){
          done++;
          if(r.ok){
            log('  ✓ ' + r.file, 'log-ok');
          } else {
            errors++;
            log('  ✗ ' + r.file + ': ' + r.error, 'log-err');
          }
        });
      } else if(!d.ok){
        errors += batch.files.length;
        done   += batch.files.length;
        log('  ✗ Paket xatosi: ' + (d.error||'Noma\'lum xato'), 'log-err');
      }
      updateProgress();
      uploadBatch(batchIdx + 1);
    })
    .catch(function(err){
      errors += batch.files.length;
      done   += batch.files.length;
      log('  ✗ Server xatosi: ' + err.message, 'log-err');
      updateProgress();
      uploadBatch(batchIdx + 1);
    });
  }

  updateProgress();
  uploadBatch(0);
};

})();
</script>

<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
