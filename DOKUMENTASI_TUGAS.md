# Dokumentasi Tugas — Integrasi Fonnte untuk Notifikasi Registrasi

## 1. Skenario Pengujian (Bagian P)

Isi kolom **Status** setelah pengujian nyata dilakukan (✓ / ✗) beserta
tanggal & nama penguji.

| No | Skenario                     | Input                          | Hasil yang Diharapkan                          | Status |
|----|-------------------------------|----------------------------------|--------------------------------------------------|--------|
| 1  | Registrasi normal             | Data valid (5 akun berbeda)      | Data tersimpan + WA terkirim                     |        |
| 2  | Email sudah terdaftar         | Email sama dengan user lain      | Registrasi ditolak, WA tidak dikirim              |        |
| 3  | Nomor sudah terdaftar         | Nomor sama dengan user lain      | Registrasi ditolak, WA tidak dikirim              |        |
| 4  | Nomor format 08                | 0812xxxxxxxx                     | Nomor dinormalisasi jadi 62812xxxxxxxx, WA terkirim |      |
| 5  | Nomor tidak valid              | Nomor asal-asalan / terlalu pendek | Sistem menolak & memberi pesan error             |        |
| 6  | Fonnte tidak tersedia / token salah | Token sengaja dikosongkan/diubah | Registrasi tetap tersimpan, status WA = "Gagal" |        |

### Rincian 5 akun pengujian (Bagian O)
| # | Nama  | Email            | No. HP        | Catatan                    |
|---|-------|------------------|---------------|-----------------------------|
| 1 | Budi  | budi@gmail.com   | 081234567890  | Pengujian normal            |
| 2 | Andi  | andi@gmail.com   | 082222222222  | Nomor WhatsApp berbeda      |
| 3 | Citra | citra@gmail.com  | 0812xxxxxxxx  | Format nomor 08              |
| 4 | Budi  | budi@gmail.com   | 089900000000  | Email duplikat → harus ditolak |
| 5 | Dian  | dian@gmail.com   | 123            | Nomor tidak valid → harus ditolak |

*(Lampirkan screenshot form, database, WhatsApp yang diterima — dengan
token disensor — dan hasil masing-masing pengujian di atas sesuai
Bagian T.)*

---

## 2. Jawaban Pertanyaan Analisis (Bagian Y)

**1. Apa yang dimaksud dengan API?**
API (Application Programming Interface) adalah perantara yang
memungkinkan dua aplikasi berbeda saling berkomunikasi dan bertukar
data, tanpa masing-masing perlu tahu detail implementasi internal
satu sama lain.

**2. Apa fungsi Fonnte dalam project ini?**
Fonnte berperan sebagai WhatsApp Gateway/service pihak ketiga yang
menerima permintaan (nomor tujuan + pesan) dari server project ini
lalu meneruskannya sebagai pesan WhatsApp ke nomor pengguna.

**3. Mengapa API Token tidak boleh diletakkan di JavaScript frontend?**
Karena kode JavaScript frontend berjalan dan dapat dilihat langsung
di browser pengguna (View Source/DevTools). Jika token diletakkan di
sana, siapa pun dapat mencurinya dan memakai kuota/perangkat WhatsApp
milik kita tanpa izin. Token harus disimpan di server (mis. `.env`)
yang tidak pernah dikirim ke browser.

**4. Apa perbedaan antara proses registrasi dan proses pengiriman WhatsApp?**
Registrasi adalah proses menyimpan data user baru ke database.
Pengiriman WhatsApp adalah proses terpisah yang memanggil API
eksternal (Fonnte) untuk mengirim notifikasi. Keduanya independen —
registrasi bisa berhasil walau pengiriman WhatsApp gagal, karena
keduanya disimpan/ditangani secara terpisah (lihat tabel `users` vs
`log_whatsapp`).

**5. Mengapa WhatsApp baru dikirim setelah data berhasil disimpan?**
Agar sistem tidak mengirim notifikasi "registrasi berhasil" padahal
datanya sebenarnya gagal tersimpan. Urutan ini mencegah notifikasi
palsu dan menjaga konsistensi data — WhatsApp hanya boleh dikirim
sebagai konfirmasi atas sesuatu yang benar-benar sudah terjadi.

**6. Apa yang terjadi jika API Fonnte mengalami gangguan?**
Request `kirimWhatsApp()` akan gagal (curl error, timeout, atau
response `status:false`). Pada implementasi ini, kegagalan tersebut
ditangkap, dicatat ke tabel `log_whatsapp` dengan status `failed`,
namun data registrasi user **tetap tersimpan** karena penyimpanan ke
database sudah selesai sebelum proses pengiriman WhatsApp dijalankan.

**7. Mengapa nomor 081234567890 perlu dinormalisasi?**
Karena API Fonnte (dan WhatsApp secara umum) membutuhkan format nomor
internasional dengan kode negara (mis. `6281234567890`), sedangkan
pengguna Indonesia terbiasa menulis nomor lokal yang diawali `0`.
Tanpa normalisasi, permintaan pengiriman bisa ditolak sebagai nomor
tidak valid.

**8. Apa fungsi HTTP method POST dalam integrasi API?**
POST digunakan untuk mengirim data (nomor tujuan, isi pesan, token)
ke server Fonnte agar server tersebut memproses dan mengirimkan pesan.
Berbeda dengan GET yang menampilkan/mengambil data, POST di sini
menyebabkan aksi/efek (pesan benar-benar terkirim) di sisi server
Fonnte.

**9. Apa fungsi response dari API Fonnte?**
Response memberi tahu aplikasi kita apakah permintaan pengiriman
berhasil diterima Fonnte atau tidak (`status: true/false`), beserta
detail seperti `id` antrian pesan atau alasan kegagalan (`reason`).
Response ini dipakai untuk menentukan status log (`success`/`failed`)
dan pesan yang ditampilkan ke pengguna.

**10. Bagaimana cara membuat sistem agar pesan WhatsApp dapat digunakan kembali pada fitur lain?**
Dengan membungkus logika pengiriman ke dalam satu function reusable,
yaitu `kirimWhatsApp($nomor, $pesan)` di `functions/fonnte.php`.
Function ini tidak terikat pada fitur registrasi saja — fitur lain
seperti pembayaran, reset password, atau pengumuman cukup memanggil
`kirimWhatsApp()` dengan nomor dan pesan yang sesuai, tanpa perlu
menulis ulang kode koneksi ke Fonnte.

---

## 3. Kriteria Penilaian (Bagian V) — untuk referensi self-check

| Komponen                               | Bobot |
|-----------------------------------------|-------|
| Form registrasi berjalan                 | 15    |
| Penyimpanan database                     | 15    |
| Integrasi API Fonnte                     | 25    |
| WhatsApp otomatis setelah registrasi     | 20    |
| Validasi & error handling                | 10    |
| Keamanan API Token                       | 5     |
| Dokumentasi                              | 5     |
| Kerapian kode                            | 5     |
| **Total**                                | **100** |
