# DEPLOY-AI.md — AI agent uchun avtomatik deploy qo'llanma

> **Bu fayl Replit AI, Cursor, Cline, Claude Code va shu kabi AI deploy agentlari uchun yozilgan.**
> Agentga to'g'ridan-to'g'ri bu faylni bering va u butun deploy jarayonini avtomatik bajaradi.

## Loyiha haqida

- **Nomi**: Manhwa UZ
- **Til/stack**: PHP 8.1+, vanilla JavaScript, Tailwind CSS (oldindan kompilyatsiya qilingan)
- **Database**: yo'q — barcha ma'lumotlar JSON fayllarda
- **Composer**: kerak emas (vendor dependency'lar yo'q)
- **Build step**: kerak emas (statik fayllar + PHP)

## Struktura

```
manhwauz.com/
├── WEB/      ← asosiy sayt (webroot ga deploy qilinadi)
├── WEBAD/    ← admin panel (alohida webroot YOKI WEB/admin/ ga)
└── DATA/     ← ma'lumotlar (webroot dan TASHQARIDA bo'lishi kerak)
```

## Agent uchun aniq topshiriqlar

### A. Replit (eng oson — bitta Repl bilan)

1. **Yangi Repl yarat**: type = PHP Web Server.
2. **Fayllarni yukla**: `WEB/*`, `WEBAD/*`, `DATA/*` — Repl ildiziga (root).
3. **`.replit` faylini tahrirla**:
   ```toml
   run = "cd WEB && php -S 0.0.0.0:8080 router.php"
   modules = ["php-8.2"]
   [env]
   AURA_PATH = "/home/runner/$REPL_SLUG/DATA"
   [[ports]]
   localPort = 8080
   externalPort = 80
   [deployment]
   run = ["sh", "-c", "cd WEB && php -S 0.0.0.0:8080 router.php"]
   deploymentTarget = "cloudrun"
   ```
4. **Admin panel uchun**: WEB/router.php `/admin` ni avtomatik WEBAD/router.php ga proxy qiladi (`AURA_PATH` env'i bilan).
5. **Run tugmasini bos** — sayt 8080-portda ishga tushadi.
6. **Birinchi admin parolni ol**: Console'ga `cat DATA/data/admin_setup.txt` yozing yoki Files'da oching.

### B. Replit (production — ikkita alohida Repl)

**Repl #1 — Asosiy sayt**
- Fayllarni yukla: `WEB/*` + `DATA/*` (root'ga)
- `.replit`:
  ```toml
  run = "php -S 0.0.0.0:8080 router.php"
  modules = ["php-8.2"]
  [env]
  AURA_PATH = "/home/runner/$REPL_SLUG/DATA"
  ```

**Repl #2 — Admin**
- Fayllarni yukla: `WEBAD/*` (root'ga) + Repl #1 ning DATA papkasiga external URL berilgan bo'lsa, AURA_PATH'ni shunga sozlang
- `.replit`:
  ```toml
  run = "php -S 0.0.0.0:8080 router.php"
  modules = ["php-8.2"]
  ```

### C. Docker (Cloud Run, Railway, Fly.io, DigitalOcean)

**Bitta image**:
```bash
docker build -t manhwauz .
docker run -d -p 80:80 \
  -v $(pwd)/DATA:/var/www/DATA \
  -e AURA_PATH=/var/www/DATA \
  manhwauz
```

`Dockerfile` ildizda — PHP 8.2 + Apache + mod_rewrite tayyor.

**Railway**:
1. `railway init`
2. `railway link`
3. `railway up`
4. Variables: `AURA_PATH=/var/www/DATA`

**Fly.io**:
```bash
fly launch
fly volumes create data --size 1
fly deploy
```

### D. An'anaviy shared hosting (cPanel, DirectAdmin)

1. FTP/SFTP bilan ulang.
2. `public_html/` ichiga `WEB/*` ichidagilarni yukla.
3. `public_html` BILAN YONMA-YON (yuqori darajada) `DATA/` papkasini yukla:
   ```
   /home/USER/
   ├── public_html/   ← WEB ichidagilar
   ├── DATA/          ← DATA ichidagilar
   ```
4. Admin uchun subdomain yoki sub-folder:
   - **Subdomain (admin.manhwauz.com)**: cPanel'da subdomain yarating → webroot = `/home/USER/admin_public_html` → `WEBAD/*` ni shu yerga yukla
   - **Sub-folder (manhwauz.com/admin)**: `WEBAD/*` ni `public_html/admin/` ichiga yukla
5. `WEB/config.php` va `WEBAD/config.php` da yo'llarni to'g'irlang (kerak bo'lsa).
6. PHP 8.1+ tanlanganligini hosting cPanel'da tekshiring.
7. `mod_rewrite` yoqilganligini tekshiring (Apache).

### E. Vercel (faqat demo uchun, production EMAS)

`vercel.json` mavjud. Lekin Vercel serverless = fayl tizimi vaqtinchalik. Bookmarks, comments saqlanmaydi. Faqat read-only demo uchun yaroqli.

## Muhim eslatmalar

### Fayl ruxsatlari (Unix/Linux)
```bash
chmod -R 755 WEB/ WEBAD/
chmod -R 775 DATA/      # web server yozish uchun
chown -R www-data:www-data DATA/
```

### Birinchi ishga tushishda
- `DATA/data/admins.json` mavjud bo'lmasa, admin panel uni avtomatik yaratadi.
- Tasodifiy parol `WEBAD/data/admin_setup.txt` ga yoziladi. **Parolni saqlang va faylni o'chiring!**
- Login: `manhwauz`.

### Environment variables

| O'zgaruvchi | Standart | Tavsifi |
|------------|----------|---------|
| `AURA_PATH` | `../DATA` | Ma'lumotlar papkasi (comics.json, covers) |
| `SITE_URL` | `https://manhwauz.com` | Asosiy sayt URL |
| `ADMIN_URL` | `https://admin.manhwauz.com` | Admin panel URL |
| `DEBUG_MODE` | `0` | `1` qilsa, xatolarni ko'rsatadi |
| `TZ` | `Asia/Tashkent` | Timezone |

### HTTPS (production'da SHART)

Let's Encrypt:
```bash
sudo certbot --apache -d manhwauz.com -d www.manhwauz.com -d admin.manhwauz.com
```

### .htaccess

- Apache'da `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires` yoqilgan bo'lishi kerak.
- Hosting `AllowOverride All` ruxsatini berishi kerak (`.htaccess` ishlashi uchun).

## Tekshirish checklist

Deploy'dan keyin tekshiring:

- [ ] `https://yourdomain.com` — bosh sahifa ochiladi
- [ ] `/browse` — komikslar ro'yxati ko'rinadi
- [ ] `/ranking` — reyting sahifasi styled chiqadi (CSS yuklangan)
- [ ] `/bookmarks` — login yo'q bo'lsa "Kirish" tugmasi ko'rsatadi
- [ ] `/login` — login formasi ishlaydi
- [ ] `/register` — yangi user qo'shish ishlaydi
- [ ] `/admin` (yoki admin subdomain) — login sahifasi ochiladi
- [ ] Mobile (telefon brauzer) — hamburger menyu drawer ochadi
- [ ] `/api/comics.json` — JSON qaytaradi
- [ ] Service Worker faol (`navigator.serviceWorker` console'da)

## Xatolarni topish

**500 Internal Server Error**
- PHP versiyasini tekshiring (8.1+)
- `error_log` faylini ko'ring (hosting cPanel'da odatda `~/logs/`)
- `WEB/config.php` da `DEBUG_MODE = true` qilib qayta yuklang

**404 — sahifalar ochilmaydi**
- `mod_rewrite` yoqilganmi?
- `.htaccess` faylini ko'ring (hidden fayllarni ko'rsatish kerak)
- `AllowOverride All` hosting'da yoqilganmi?

**Admin parolini bilmayman**
- `DATA/data/admin_setup.txt` faylini oching (yoki `WEBAD/data/admin_setup.txt`)
- Agar fayl yo'q bo'lsa, `data/admins.json` ni o'chiring va admin panelga qayta kiring — yangisi yaratiladi

**Kover rasmlar yuklanmaydi**
- `DATA/public/images/covers/` papkasi mavjudligini va o'qilishini tekshiring
- Apache'da hotlink himoyasi `.htaccess` da yoqilgan — local test uchun uni o'chirib qo'ying

**Mobile drawer ochilmaydi**
- `_fix.js` yuklanyaptimi? (Network tab'da tekshiring)
- Browser console'da xato bormi?

---

## Agent uchun yakuniy buyruq

Agar siz Replit AI yoki Cline kabi agent bo'lsangiz:

1. Ushbu papkani (`manhwauz.com/`) butunlay nusxa qiling.
2. Yuqoridagi **A** (Replit, eng oson) usulini bajaring.
3. Birinchi admin parolni ko'rsating va "saqlab oling, faylni o'chiring" deyish.
4. Deploy URL ni ulashing.

Hech qanday qo'shimcha o'zgartirish kerak emas — kod tayyor.
