# SMK Child Theme (OJS 3.5) — Kisi Berkelanjutan

Child theme resmi berbasis **Default Theme** untuk **OJS 3.5** yang menyatukan seluruh kustomisasi tampilan jurnal ke dalam satu folder plugin (`plugins/themes/smkTheme/`).

## Struktur Folder
- `index.php`, `version.xml`, `SmkThemePlugin.php` — Plugin & backend data OJS 3.5 (menggunakan PHP namespace modern, mengambil data statistik COUNTER R5 nyata & dependent images XML langsung dari backend OJS dengan server cache 30 menit).
- `locale/en/locale.po`, `locale/id/locale.po` — Label pengaturan tema di Dashboard OJS.
- `styles/custom.css` — Master stylesheet hasil deduplikasi `Custom/styleSheet.css` & `Custom/footer.tpl`.
- `js/kisi-theme.js` — Script frontend terpisah dengan proteksi XSS (escaping DOM & sanitasi URL/JATS XML), tanpa panggilan eksternal `api.ipify.org`, `flagcdn.com`, atau `sdgs.un.org`.
- `assets/img/sdg/` — Ikon SVG SDG 1–17 lokal (bebas ketergantungan server luar & kompatibel CSP).
- `templates/frontend/pages/indexJournal.tpl` — Beranda kustom dengan perbaikan `PKPApplication::ROUTE_PAGE`, dukungan fitur bawaan OJS 3.5, serta pengaturan Hero dari Dashboard OJS.
- `templates/frontend/components/footer.tpl` — Footer bersih (mewarisi struktur asli OJS 3.5 tanpa inline `<style>` / `<script>` eksekusi).
- `.cpanel.yml` — Konfigurasi *auto-deploy* untuk **cPanel Git™ Version Control**.

---

## Cara Deploy Otomatis ke Root cPanel (Git™ Version Control)

### 1. Sesuaikan Path pada `.cpanel.yml`
File `.cpanel.yml` sudah memakai `$HOME/public_html/plugins/themes/smkTheme/` agar username cPanel tidak tertulis eksplisit. Ubah `public_html` pada file tersebut hanya jika direktori instalasi OJS Anda di cPanel berada di subfolder lain.

### 2. Push Folder `smkTheme` ke Git (GitHub/GitLab/cPanel Remote)
Jadikan isi folder `smkTheme/` sebagai repositori Git tersendiri (agar folder `Original/` dan `Custom/` tetap tersimpan rapi di luar repo deploy):

```bash
cd smkTheme
git init
git add .
git commit -m "Initial SMK Child Theme for OJS 3.5"
```

Lalu hubungkan ke repositori di **cPanel > Git™ Version Control > Create** (atau push ke GitHub/GitLab lalu clone di cPanel), dan klik **Update from Remote** + **Deploy HEAD Commit** pada menu *Manage*.

> Setiap kali Anda mengubah file di laptop (misal edit `custom.css`, `kisi-theme.js`, atau `indexJournal.tpl`), cukup jalankan `git commit` dan `git push`, lalu cPanel otomatis menyinkronkan seluruh perubahan ke `plugins/themes/smkTheme/` tanpa membuka file satu per satu.

---

## Langkah Migrasi Aman dari Setup Lama di cPanel

1. **Deploy folder `smkTheme`** ke `plugins/themes/smkTheme/` di root OJS cPanel (pastikan izin folder `755` dan file `644`).
2. **Kembalikan 2 file core OJS ke versi asli (`Original/`):**
   - Upload ulang `Original/indexJournal.tpl` ke `/templates/frontend/pages/indexJournal.tpl`
   - Upload ulang `Original/footer.tpl` ke `/lib/pkp/templates/frontend/components/footer.tpl`
3. **Kosongkan/hapus `styleSheet.css` lama** di **OJS Dashboard > Settings > Website > Appearance > Setup > Journal Style Sheet** agar CSS tidak dimuat ganda bersama `styles/custom.css`.
4. **Aktifkan Child Theme di Dashboard OJS:**
   - Masuk ke **Settings > Website > Plugins**, cari **SMK Child Theme (Kisi Berkelanjutan)** dan pastikan statusnya aktif (*Enabled*). *(Jika versi plugin belum terdaftar di database, jalankan di Terminal cPanel/SSH: `php lib/pkp/tools/installPluginVersion.php plugins/themes/smkTheme/version.xml`)*
   - Masuk ke **Settings > Website > Appearance > Theme**, pilih **SMK Child Theme (Kisi Berkelanjutan)**, sesuaikan teks & metrik Hero sesuai kebutuhan, lalu klik **Save**.
5. **Bersihkan Cache OJS:**
   - Buka **Administration > Clear Template Cache** dan **Clear Data Caches** (penting setiap kali selesai memperbarui file `.tpl`).
