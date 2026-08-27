# Project API WhatsApp - Fonnte

## Deskripsi
Project ini merupakan pengembangan API registrasi dengan penambahan fitur
notifikasi WhatsApp otomatis menggunakan **Fonnte** setelah proses
registrasi berhasil disimpan ke database.

## Teknologi
- HTML
- CSS
- PHP
- MySQL
- REST API
- Fonnte

## Fitur
- Registrasi pengguna
- Penyimpanan database
- Validasi data (nama, email, nomor WhatsApp, password)
- Notifikasi WhatsApp otomatis setelah registrasi berhasil
- Integrasi API Fonnte (function `kirimWhatsApp()` yang reusable)
- Normalisasi nomor WhatsApp (0812xxxx → 62812xxxx)
- Log pengiriman WhatsApp (tabel `log_whatsapp`)
- Halaman **Laporan Notifikasi WhatsApp** untuk admin (`admin/laporan.html`)
- Password disimpan dengan hashing (`password_hash`)
- Token Fonnte disimpan di `.env`, tidak pernah muncul di frontend

## Alur Sistem
```
User → Form Registrasi → Validasi → Database
                                        │
                                 (jika berhasil)
                                        ▼
                              Buat Pesan Dinamis
                                        │
                                        ▼
                                  API Fonnte
                                        │
                                        ▼
                                   WhatsApp User
                                        │
                                        ▼
                         Log status (Terkirim/Gagal) ke database
```

## Struktur Project
```
project-api-whatsapp/
│
├── config/
│   ├── env.php         # loader file .env
│   ├── database.php    # koneksi PDO ke MySQL
│   └── fonnte.php      # konfigurasi token & url Fonnte (dari .env)
│
├── api/
│   ├── register.php    # proses registrasi + trigger kirim WhatsApp
│   └── laporan.php     # data JSON untuk halaman laporan admin
│
├── functions/
│   └── fonnte.php      # kirimWhatsApp(), formatNomor(), logWhatsApp()
│
├── admin/
│   └── laporan.html    # halaman "Laporan Notifikasi WhatsApp"
│
├── database/
│   └── schema.sql      # struktur tabel users & log_whatsapp
│
├── index.html           # form registrasi
├── .env.example         # contoh file environment (copy → .env)
└── README.md
```

## Cara Menjalankan
1. Import `database/schema.sql` ke MySQL (phpMyAdmin / CLI).
2. Copy `.env.example` menjadi `.env`, lalu isi `DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASS`.
3. Buat akun & device di [fonnte.com](https://fonnte.com/), hubungkan
   nomor WhatsApp pengirim, lalu salin token perangkat ke `FONNTE_TOKEN`
   pada file `.env`.
4. Jalankan server, contoh dengan PHP built-in server dari root project:
   ```
   php -S localhost:8000
   ```
5. Buka `http://localhost:8000/index.html` untuk halaman registrasi.
6. Buka `http://localhost:8000/admin/laporan.html` untuk melihat laporan
   status notifikasi WhatsApp.
7. **Sebelum uji coba registrasi**, buka
   `http://localhost:8000/test/cek-koneksi.php` untuk memastikan koneksi
   ke Fonnte sudah benar (file .env terbaca, token valid, cURL aktif,
   server bisa menjangkau api.fonnte.com). Halaman ini juga punya form
   untuk mengirim WA percobaan langsung tanpa perlu registrasi dulu.
   **Hapus atau kunci akses file ini setelah selesai testing**, karena
   siapa pun yang mengaksesnya bisa memicu pengiriman WA lewat device
   kalian.
8. Lakukan pengujian sesuai `DOKUMENTASI_TUGAS.md`.

## Troubleshooting: Notifikasi WhatsApp tidak terkirim
Jika setelah registrasi WhatsApp tidak diterima, cek tabel `log_whatsapp`
kolom `response`, atau langsung jalankan `test/cek-koneksi.php`. Penyebab
paling umum:

| Reason di `response` | Penyebab | Solusi |
|---|---|---|
| `FONNTE_TOKEN belum dikonfigurasi di .env` | File `.env` belum dibuat, salah lokasi, atau nama filenya jadi `.env.txt` | Copy `.env.example` → `.env` tepat di root project, isi `FONNTE_TOKEN` |
| `token invalid` | Token salah/kadaluarsa | Copy ulang token dari dashboard Fonnte → Device |
| `device not connected` / status false tanpa reason | Perangkat WhatsApp di Fonnte terputus | Login dashboard Fonnte, scan ulang QR code |
| `target invalid` | Format nomor tidak dikenali | Cek input nomor & fungsi `formatNomor()` |
| `insufficient quota` | Kuota/paket Fonnte habis | Isi ulang kuota di dashboard Fonnte |
| `curl error: ...` | Server tidak bisa akses internet keluar | Cek firewall/antivirus, ekstensi cURL aktif |

## Catatan Keamanan
- API Token **tidak** disertakan dalam repository publik (`.env` masuk
  `.gitignore`).
- Semua request ke Fonnte dilakukan dari server (PHP/cURL), bukan
  langsung dari browser, agar token tidak pernah terlihat pengguna.
- Registrasi tetap tersimpan meskipun pengiriman WhatsApp gagal — kedua
  proses (database & Fonnte) ditangani secara terpisah.

## Referensi
Endpoint, header, dan parameter mengikuti dokumentasi resmi Fonnte
terbaru: https://docs.fonnte.com/api-send-message/ — cek dokumentasi ini
saat implementasi karena spesifikasi API dapat berubah sewaktu-waktu.
