<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireAuth();

$isNew    = !isset($_GET['slug']) || $_GET['slug'] === '';
$slug     = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
$comic    = [];
$isHttrack = false;

if (!$isNew) {
    $comic = getComic($slug);
    if (!$comic) {
        // Check if it's an HTTrack static comic
        $staticPattern = SITE_ROOT . '/../asurascans.com/comics/' . $slug . '-????????.html';
        $staticFiles   = glob($staticPattern);
        if ($staticFiles) {
            $html = file_get_contents($staticFiles[0]);

            // Title
            $title = $slug;
            if (preg_match('/<title>([^<|]+)/', $html, $tm)) {
                $title = trim(preg_replace('/\s*\|\s*Asura Scans.*$/i', '', $tm[1]));
            }

            // Cover (CDN URL)
            $cdnCover = '';
            if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $cm)) {
                $cdnCover = $cm[1];
            }

            // Description
            $description = '';
            if (preg_match('/<meta property="og:description" content="([^"]+)"/', $html, $dm)) {
                $description = html_entity_decode($dm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Genres from JSON-LD
            $genres = [];
            if (preg_match('/"genre"\s*:\s*\[([^\]]+)\]/s', $html, $gm)) {
                preg_match_all('/"([^"]+)"/', $gm[1], $gms);
                $genres = $gms[1] ?? [];
            }

            // Rating
            $rating = '';
            if (preg_match('/"ratingValue"\s*:\s*"?([0-9.]+)"?/', $html, $rm)) {
                $rating = $rm[1];
            }

            // Author
            $author = '';
            if (preg_match('/"author"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"/', $html, $am)) {
                $author = $am[1];
            }

            $comic = [
                'slug'        => $slug,
                'title'       => $title,
                'cover'       => $cdnCover,
                'description' => $description,
                'genres'      => $genres,
                'rating'      => $rating,
                'author'      => $author,
                'status'      => 'Ongoing',
                'type'        => 'Manhwa',
            ];
            $isHttrack = true;
            $isNew     = true; // treat save as new entry
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Komiks topilmadi'];
            header('Location: /comics');
            exit;
        }
    }
}

// Cover preview URL
$coverPreviewUrl = '';
if ($isHttrack) {
    $coverPreviewUrl = $comic['cover'] ?? '';  // CDN URL used directly
} elseif (!empty($comic['cover'])) {
    $coverPreviewUrl = coverUrl($comic['cover']);
}

// Genre list for checkboxes
$allGenres = [
    'Action','Adventure','Comedy','Drama','Fantasy','Horror','Mystery',
    'Romance','Sci-Fi','Slice of Life','Sports','Supernatural','Thriller',
    'Isekai','Martial Arts','School Life','Historical','Psychological',
    'Mecha','Music','Cooking','Harem','Ecchi','Shounen','Shoujo',
    'Seinen','Josei','Manhwa','Manhua',
];

$comicGenres = $comic['genres'] ?? [];

$pageTitle  = $isHttrack ? 'Import: ' . ($comic['title'] ?? $slug)
           : ($isNew    ? 'Yangi komiks' : 'Tahrirlash: ' . ($comic['title'] ?? ''));
$activePage = 'comics';
ob_start();
?>

<div style="max-width:900px">

<!-- Back link -->
<a href="/comics" class="btn btn-ghost btn-sm" style="margin-bottom:1.25rem;display:inline-flex">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7-7l-7 7 7 7"/></svg>
  &nbsp;Orqaga
</a>

<?php if ($isHttrack): ?>
<div style="background:rgba(145,63,226,.12);border:1px solid rgba(145,63,226,.35);border-radius:10px;padding:.875rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem">
  <svg viewBox="0 0 24 24" fill="none" stroke="#913FE2" stroke-width="2" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  <span style="font-size:.875rem">Bu <b>HTTrack</b> comici. Ma'lumotlar statik fayldan olindi. Saqlashda <code style="background:#1D1B22;padding:.1rem .4rem;border-radius:4px;font-size:.8rem"><?= htmlspecialchars($slug) ?></code> comics.json ga import qilinadi.</span>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title"><?= $isHttrack ? 'Import &amp; tahrirlash: ' . htmlspecialchars($comic['title']) : ($isNew ? 'Yangi komiks qo\'shish' : htmlspecialchars($comic['title'])) ?></span>
  </div>

  <form id="comicForm" style="padding:1.5rem">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <input type="hidden" name="oldSlug" value="<?= $isHttrack ? '' : htmlspecialchars($comic['slug'] ?? '') ?>">
    <?php if ($isHttrack): ?>
    <input type="hidden" name="cdnCover" value="<?= htmlspecialchars($comic['cover'] ?? '') ?>">
    <?php endif; ?>

    <div class="edit-grid">

      <!-- LEFT column -->
      <div class="edit-left">

        <!-- Cover upload -->
        <div class="cover-upload-wrap">
          <div class="cover-preview" id="coverPreview">
            <?php if ($coverPreviewUrl): ?>
            <img src="<?= htmlspecialchars($coverPreviewUrl) ?>" id="coverImg" alt="cover">
            <?php else: ?>
            <div class="cover-placeholder" id="coverPlaceholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40"><rect x="3" y="3" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M3 9l4-4 4 4 4-6 4 6"/><circle cx="8.5" cy="8.5" r="1.5"/></svg>
              <span>Cover rasm</span>
            </div>
            <?php endif; ?>
          </div>
          <label class="btn btn-ghost btn-sm" style="cursor:pointer;text-align:center;margin-top:.75rem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            &nbsp;Cover yuklash
            <input type="file" id="coverFile" accept="image/*" style="display:none">
          </label>
          <div class="text-xs text-muted" style="margin-top:.375rem;text-align:center">JPG, PNG, WebP · Max 5 MB</div>
          <div id="coverStatus" class="text-xs" style="margin-top:.375rem;text-align:center;min-height:1rem"></div>
        </div>

        <!-- Rating / Views -->
        <div class="field" style="margin-top:1.25rem">
          <label class="label">Reyting</label>
          <input class="inp" type="number" name="rating" min="0" max="10" step="0.1"
                 value="<?= htmlspecialchars($comic['rating'] ?? '') ?>" placeholder="0.0 – 10.0">
        </div>
        <div class="field">
          <label class="label">Ko'rishlar soni</label>
          <input class="inp" type="number" name="viewCount" min="0"
                 value="<?= htmlspecialchars($comic['viewCount'] ?? '0') ?>">
        </div>

      </div><!-- /left -->

      <!-- RIGHT column -->
      <div class="edit-right">

        <div class="field">
          <label class="label">Sarlavha <span style="color:#e25555">*</span></label>
          <input class="inp" type="text" name="title" required
                 value="<?= htmlspecialchars($comic['title'] ?? '') ?>"
                 placeholder="Komiks nomi" oninput="autoSlug(this)">
        </div>

        <div class="field">
          <label class="label">Slug (URL)</label>
          <div style="display:flex;gap:.5rem">
            <input class="inp" type="text" name="slug" id="slugInput" pattern="[a-z0-9-]+"
                   value="<?= htmlspecialchars($comic['slug'] ?? '') ?>"
                   placeholder="auto-generated"
                   <?= ($isHttrack || !$isNew) ? 'readonly style="opacity:.6"' : '' ?>>
            <?php if ($isNew && !$isHttrack): ?>
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('slugInput').readOnly=false;this.remove()">Tahrirlash</button>
            <?php endif; ?>
          </div>
          <span class="text-xs text-muted">Faqat kichik harf, raqam va defis</span>
        </div>

        <div class="field">
          <label class="label">Muallif</label>
          <input class="inp" type="text" name="author"
                 value="<?= htmlspecialchars($comic['author'] ?? '') ?>"
                 placeholder="Muallif ismi">
        </div>

        <div class="field">
          <label class="label">Rasm muallifi / Artist</label>
          <input class="inp" type="text" name="illustrator"
                 value="<?= htmlspecialchars($comic['illustrator'] ?? $comic['artist'] ?? '') ?>"
                 placeholder="Artist ismi">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="field">
            <label class="label">Status</label>
            <select class="inp" name="status">
              <?php foreach(['Ongoing','Completed','Hiatus','Dropped'] as $st): ?>
              <option value="<?= $st ?>" <?= ($comic['status']??'Ongoing')===$st?'selected':'' ?>><?= $st ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="label">Tur</label>
            <select class="inp" name="type">
              <?php foreach(['Manhwa','Manga','Manhua','Webtoon','OEL Manga'] as $tp): ?>
              <option value="<?= $tp ?>" <?= ($comic['type']??'')===$tp?'selected':'' ?>><?= $tp ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label class="label">Tavsif</label>
          <textarea class="inp" name="description" rows="5"
                    placeholder="Komiks haqida..."><?= htmlspecialchars($comic['description'] ?? '') ?></textarea>
        </div>

        <div class="field">
          <label class="label">Nashriyot yili</label>
          <input class="inp" type="number" name="year" min="1990" max="<?= date('Y') ?>"
                 value="<?= htmlspecialchars($comic['year'] ?? date('Y')) ?>">
        </div>

        <!-- Genres -->
        <div class="field">
          <label class="label">Janrlar</label>
          <div class="genres-grid">
            <?php foreach($allGenres as $g): ?>
            <label class="genre-chip <?= in_array($g, $comicGenres)?'active':'' ?>">
              <input type="checkbox" name="genres[]" value="<?= $g ?>"
                     <?= in_array($g, $comicGenres)?'checked':'' ?>>
              <?= $g ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

      </div><!-- /right -->
    </div><!-- /grid -->

    <!-- Footer -->
    <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border)">
      <a href="/comics" class="btn btn-ghost">Bekor qilish</a>
      <button type="submit" class="btn btn-primary" id="saveBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        &nbsp;<?= $isHttrack ? 'Import qilish' : ($isNew ? 'Saqlash' : 'Yangilash') ?>
      </button>
    </div>

  </form>
</div><!-- /card -->
</div><!-- /max-width -->

<style>
.edit-grid  { display:grid; grid-template-columns:220px 1fr; gap:1.75rem; }
@media(max-width:680px){ .edit-grid{ grid-template-columns:1fr; } }
.edit-left  { display:flex; flex-direction:column; align-items:center; }
.cover-upload-wrap { width:100%; }
.cover-preview {
  width:100%; aspect-ratio:2/3; border-radius:10px;
  background:var(--surface2); border:2px dashed var(--border);
  overflow:hidden; display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:border-color .2s;
}
.cover-preview:hover { border-color:var(--purple); }
.cover-preview img  { width:100%; height:100%; object-fit:cover; }
.cover-placeholder  { display:flex; flex-direction:column; align-items:center;
  gap:.625rem; color:var(--text3); }
.cover-placeholder span { font-size:.8125rem; }
.field { margin-bottom:1rem; }
.label { display:block; font-size:.8125rem; font-weight:600;
  color:var(--text2); margin-bottom:.375rem; }
.genres-grid { display:flex; flex-wrap:wrap; gap:.5rem; }
.genre-chip {
  display:inline-flex; align-items:center; gap:.3rem; cursor:pointer;
  padding:.25rem .625rem; border-radius:999px; font-size:.75rem; font-weight:600;
  background:var(--surface2); color:var(--text2); border:1px solid var(--border);
  transition:background .15s, color .15s, border-color .15s; user-select:none;
}
.genre-chip input { display:none; }
.genre-chip:hover  { border-color:var(--purple); color:var(--purple); }
.genre-chip.active { background:rgba(145,63,226,.15); color:var(--purple); border-color:var(--purple); }
</style>

<script>
(function(){
  var isNew    = <?= $isNew ? 'true' : 'false' ?>;
  var isHttrack = <?= $isHttrack ? 'true' : 'false' ?>;

  // Auto-generate slug from title (only for new non-httrack comics)
  window.autoSlug = function(inp) {
    if (!isNew || isHttrack) return;
    var sl = document.getElementById('slugInput');
    if (!sl || sl.readOnly === false) return; // user editing manually
    sl.value = inp.value.toLowerCase()
      .replace(/[^a-z0-9\s-]/g,'').trim()
      .replace(/[\s]+/g,'-').replace(/-+/g,'-');
  };

  // Genre chip toggle
  document.querySelectorAll('.genre-chip').forEach(function(chip){
    chip.addEventListener('click', function(){
      var cb = this.querySelector('input[type=checkbox]');
      // click event fires after checkbox state changes natively
      this.classList.toggle('active', cb.checked);
    });
  });

  // Cover preview
  var coverFile = document.getElementById('coverFile');
  var coverPreview = document.getElementById('coverPreview');
  var coverStatus  = document.getElementById('coverStatus');
  var pendingCoverUrl = null; // after upload, store returned path

  if (coverFile) {
    coverFile.addEventListener('change', function(){
      var file = this.files[0];
      if (!file) return;
      if (file.size > 5 * 1024 * 1024) {
        coverStatus.textContent = 'Fayl 5 MB dan katta!';
        coverStatus.style.color = '#e25555';
        return;
      }
      // Show local preview immediately
      var reader = new FileReader();
      reader.onload = function(e){
        coverPreview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
      };
      reader.readAsDataURL(file);
      // Upload cover
      uploadCover(file);
    });
  }

  // Click on preview triggers file input
  coverPreview.addEventListener('click', function(){
    coverFile.click();
  });

  function uploadCover(file) {
    coverStatus.textContent = 'Yuklanmoqda...';
    coverStatus.style.color = 'var(--text3)';

    var fd = new FormData();
    fd.append('csrf', document.querySelector('[name=csrf]').value);
    fd.append('cover', file);
    var slugVal = document.querySelector('[name=oldSlug]').value ||
                  document.getElementById('slugInput').value;
    if (slugVal) fd.append('slug', slugVal);

    fetch('/api/upload-cover', { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.ok) {
          pendingCoverUrl = d.path;
          coverStatus.textContent = '✓ Yuklandi: ' + d.filename;
          coverStatus.style.color = '#22c55e';
        } else {
          coverStatus.textContent = 'Xato: ' + (d.error || 'Noma\'lum');
          coverStatus.style.color = '#e25555';
        }
      })
      .catch(function(){ coverStatus.textContent = 'Tarmoq xatosi'; coverStatus.style.color='#e25555'; });
  }

  // Form submit → /api/comic-save
  document.getElementById('comicForm').addEventListener('submit', function(e){
    e.preventDefault();
    var btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Saqlanmoqda...';

    var fd = new FormData(this);
    if (pendingCoverUrl) fd.set('coverPath', pendingCoverUrl);

    // Collect checked genres
    var genres = [];
    document.querySelectorAll('[name="genres[]"]:checked').forEach(function(cb){
      genres.push(cb.value);
    });
    fd.delete('genres[]');
    genres.forEach(function(g){ fd.append('genres[]', g); });

    fetch('/api/comic-save', { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.ok) {
          window.location.href = '/comics?saved=1';
        } else {
          alert('Xato: ' + (d.error || 'Noma\'lum xato'));
          btn.disabled = false;
          btn.textContent = '<?= $isHttrack ? 'Import qilish' : ($isNew ? 'Saqlash' : 'Yangilash') ?>';
        }
      })
      .catch(function(){
        alert('Server bilan bog\'lanishda xato');
        btn.disabled = false;
        btn.textContent = '<?= $isNew ? 'Saqlash' : 'Yangilash' ?>';
      });
  });

})();
</script>

<?php
$content = ob_get_clean();
require ROOT . '/includes/layout.php';
