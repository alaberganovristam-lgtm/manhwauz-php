# Manhwa UZ — Deploy paketi

Bu papka **production deploy uchun tayyor** kod paketi. Hech qanday qo'shimcha build / compile kerak emas.

## Tarkib

```
manhwauz.com/
├── WEB/             ← Asosiy sayt (manhwauz.com)
│   ├── router.php
│   ├── config.php       ← deploy oldidan tekshiring
│   ├── _fix.js
│   ├── .htaccess
│   ├── _astro/          ← CSS
│   ├── index.html, browse.html, ...
│   └── (boshqa PHP va HTML fayllar)
│
├── WEBAD/           ← Admin panel (admin.manhwauz.com YOKI manhwauz.com/admin)
│   ├── router.php
│   ├── config.php
│   ├── includes/, assets/, api/, comics/, chapters/, users/, ...
│   └── (admin PHP fayllari)
│
├── DATA/            ← Ma'lumotlar (WEB va WEBAD dan TASHQARIDA)
│   ├── data/
│   │   ├── comics.json      ← 48 ta komiks
│   │   ├── comments.json
│   │   └── bookmarks.json
│   ├── public/
│   │   └── images/covers/   ← komiks kover rasmlari
│   └── uploads/             ← bob rasmlari
│
├── Dockerfile           ← Docker / Cloud Run / Railway uchun
├── .replit              ← Replit konfiguratsiyasi
├── replit.nix           ← Replit Nix package'lari
├── vercel.json          ← Vercel (demo only)
├── DEPLOY-AI.md         ← AI agent uchun avtomatik deploy qo'llanma
└── README.md            ← shu fayl
```

## Tezkor deploy (3 variant)

### 1. An'anaviy hosting (cPanel / DirectAdmin / shared hosting) — eng oson

**1-qadam.** Hostingdagi `public_html` ichiga `WEB/*` ichidagi hamma narsalarni yuklang.
**2-qadam.** `public_html` BILAN YONMA-YON (bir daraja yuqori) `DATA` papkasini yuklang.
**3-qadam.** Subdomain `admin.manhwauz.com` yarating, uning webroot'iga `WEBAD/*` ichidagilarni yuklang.
**4-qadam.** Brauzerda `https://yourdomain.com` ochiladi. Admin: `https://admin.yourdomain.com`.

Struktura serverda shunday bo'lishi kerak:
```
/home/USER/
├── public_html/          ← WEB ichidagi fayllar
├── admin_public_html/    ← WEBAD ichidagi fayllar (subdomain webroot)
└── DATA/                 ← ma'lumotlar (web'dan ko'rinmaydi)
```

### 2. Replit (cloud, 1 klik)

1. Bu papkani (`manhwauz.com/`) ZIP qilib Replit'ga import qiling.
2. `Run` tugmasini bosing.
3. `.replit` avtomatik `WEB/router.php` ni 8080-portda ishga tushiradi.
4. Admin panel uchun ikkinchi Repl yarating va `WEBAD/` ni yuklang — `.replit` ni o'shanga ham nusxa oling.

Batafsil: [DEPLOY-AI.md](DEPLOY-AI.md)

### 3. Docker (VPS, Cloud Run, Railway, Fly.io)

```bash
# Asosiy sayt
docker build -t manhwauz-web -f Dockerfile --build-arg TARGET=WEB .
docker run -d -p 80:80 -v $(pwd)/DATA:/var/www/DATA -e AURA_PATH=/var/www/DATA manhwauz-web

# Admin panel
docker build -t manhwauz-admin -f Dockerfile --build-arg TARGET=WEBAD .
docker run -d -p 8080:80 -v $(pwd)/DATA:/var/www/DATA -e AURA_PATH=/var/www/DATA manhwauz-admin
```

## Birinchi login

Admin panelga birinchi marta kirsangiz, tasodifiy parol generatsiya qilinadi va `WEBAD/data/admin_setup.txt` fayliga yoziladi.

- **Login**: `manhwauz`
- **Parol**: `WEBAD/data/admin_setup.txt` faylidan oling.
- Parolni saqlab oling va o'sha faylni darhol o'chiring.

## Texnologiyalar

- **PHP 8.1+** (PHP 8.2 tavsiya etiladi)
- **Apache** + `mod_rewrite` (yoki PHP built-in server)
- **Database**: yo'q (hamma narsa JSON fayllarda saqlanadi)
- **Composer**: kerak emas (vendor dependency'lar yo'q)

## Funksiyalar — barchasi ishlaydi

- ✅ Bosh sahifa (trending, latest updates, popular)
- ✅ Browse — filtrlar, qidiruv, paginatsiya
- ✅ Komiks sahifasi — bob ro'yxati, sharhlar, emoji reaksiyalar
- ✅ Reader — bob o'qish, sahifa orasida o'tish
- ✅ Login / Register — bcrypt parol hash
- ✅ Bookmarks — server'da saqlanadi
- ✅ Comments — komiks bo'yicha sharhlar
- ✅ Ranking — reyting / ko'rishlar / boblar bo'yicha tartiblash
- ✅ PWA — offline ishlash, manifest.json + service worker
- ✅ Admin panel — komiks yuklash, tahrirlash, foydalanuvchilar boshqaruvi
- ✅ Mobile responsive — drawer menyu, 44px tap target'lar
- ✅ Xavfsizlik — bcrypt, brute-force himoyasi, CSP, HSTS, XSS himoyasi

## Domain sozlash

DNS A-record (yoki A va AAAA):
```
@               → server IP
www             → server IP
admin           → server IP   (admin panel uchun)
```

Apache virtualhost:
```apache
<VirtualHost *:80>
    ServerName manhwauz.com
    DocumentRoot /home/USER/public_html
    ServerAlias www.manhwauz.com
</VirtualHost>

<VirtualHost *:80>
    ServerName admin.manhwauz.com
    DocumentRoot /home/USER/admin_public_html
</VirtualHost>
```

HTTPS (Let's Encrypt):
```bash
sudo certbot --apache -d manhwauz.com -d www.manhwauz.com -d admin.manhwauz.com
```

## Yordam

- Texnik savollar: kod ichidagi izohlarni o'qing
- Deploy bilan muammo: `DEPLOY-AI.md` ni AI agent (Replit AI, Cursor, Cline) ga bering
- Birinchi tahrir qilinishi kerak bo'lgan fayllar: `WEB/config.php`, `WEBAD/config.php`

---

*Manhwa UZ — Manga va manhwa o'qish platformasi. PHP + JSON, database kerak emas.*
