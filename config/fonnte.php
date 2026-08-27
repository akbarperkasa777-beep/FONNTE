<?php
/**
 * Tahap G - File Konfigurasi Fonnte
 *
 * PENTING (Tahap E & Bagian S): Token TIDAK ditulis langsung di sini.
 * Token diambil dari environment variable (.env) agar tidak pernah
 * terekspos ke HTML/JavaScript/GitHub publik.
 *
 * Kalau notifikasi WhatsApp tidak pernah terkirim, penyebab paling sering
 * adalah baris "token" di bawah ini kosong karena file .env belum dibuat
 * atau salah lokasi. Jalankan test/cek-koneksi.php untuk memastikan.
 */

require_once __DIR__ . '/env.php';

return [
    // Ambil dari dashboard Fonnte -> Device -> klik device kalian -> copy token.
    'token'   => trim((string) env('FONNTE_TOKEN', '')),
    'api_url' => trim((string) env('FONNTE_API_URL', 'https://api.fonnte.com/send')),
];
