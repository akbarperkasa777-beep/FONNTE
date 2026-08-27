<?php
/**
 * Tahap G - Loader konfigurasi dari file .env
 *
 * File ini membaca .env di root project dan menaruh nilainya ke $_ENV
 * agar token Fonnte & kredensial database TIDAK ditulis langsung
 * di source code (lihat Tahap E - PERHATIAN & Bagian S - Aturan Keamanan).
 *
 * Versi ini lebih toleran terhadap kesalahan umum:
 * - Mencari .env di beberapa lokasi (root project, satu level di atas config/)
 * - Memberi tahu secara eksplisit lewat env_debug_info() jika file tidak ketemu
 */

define('ENV_LOAD_INFO', []);
$GLOBALS['__env_load_info'] = [
    'path_dicoba' => [],
    'ditemukan_di' => null,
    'jumlah_key' => 0,
];

function loadEnv(string $path): void
{
    $GLOBALS['__env_load_info']['path_dicoba'][] = $path;

    if (!file_exists($path)) {
        return;
    }

    $GLOBALS['__env_load_info']['ditemukan_di'] = $path;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // lewati komentar
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // buang tanda kutip jika ada
        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        putenv("$key=$value");
        $GLOBALS['__env_load_info']['jumlah_key']++;
    }
}

// Coba beberapa kemungkinan lokasi .env supaya tidak gagal hanya karena
// struktur folder sedikit berbeda (misal dijalankan dari subfolder XAMPP htdocs).
$kandidatPath = [
    __DIR__ . '/../.env',
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
];

foreach ($kandidatPath as $path) {
    loadEnv($path);
    if ($GLOBALS['__env_load_info']['ditemukan_di'] !== null) {
        break;
    }
}

/**
 * Helper ambil env dengan default value.
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Info diagnostik: dipakai oleh test/cek-koneksi.php untuk menunjukkan
 * persis file .env mana yang terbaca (atau tidak terbaca sama sekali).
 */
function env_debug_info(): array
{
    return $GLOBALS['__env_load_info'];
}
