# SMK Child Theme (OJS 3.5) — Kisi Berkelanjutan

Child theme berbasis **Default Theme** untuk **OJS 3.5** yang menyatukan seluruh kustomisasi tampilan jurnal ke dalam satu folder plugin (`plugins/themes/smkTheme`).

## Arsitektur Pengamanan Deployment (*Fail-Closed*)

Repository ini dilengkapi proteksi berlapis terhadap modifikasi tidak sah maupun penyisipan kode (*webshell*):

1. **Manifest Integritas `SHA256SUMS`**: Menyimpan sidik jari SHA-256 seluruh file yang di-deploy ke `public_html/plugins/themes/smkTheme`.
2. **Gatekeeper `.cpanel.yml` (`scripts/verify-deploy.sh`)**: Memeriksa *whitelist* file PHP, memindai pola *backdoor/webshell*, memverifikasi keutuhan `.htaccess` subfolder, dan mencocokkan `SHA256SUMS` **sebelum** serta **sesudah** menyalin file ke `public_html`. Jika ada 1 byte saja berbeda atau muncul file asing, proses deploy otomatis dibatalkan (`exit 1`).
3. **Proteksi `.htaccess` Subfolder**: Memblokir eksekusi skrip sisi server pada `assets/`, `js/`, `styles/`, serta menolak akses HTTP langsung ke `locale/` dan `templates/`.
4. **GitHub Actions (`Security & Integrity Gate`)**: Memvalidasi sintaks PHP 8.3, `version.xml`, dan menjalankan `scripts/verify-deploy.sh` otomatis pada setiap *push* maupun *Pull Request*.

### Alur Update Kode Lokal (Sebelum `git commit`)
Setiap kali selesai mengubah file tema di komputer lokal, jalankan skrip berikut untuk menormalisasi *line ending* LF dan memperbarui `SHA256SUMS`:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\update-hashes.ps1
```
