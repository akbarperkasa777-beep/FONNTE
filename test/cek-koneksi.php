<?php
/**
 * test/cek-koneksi.php
 *
 * Script diagnostik berdiri sendiri. Jalankan lewat browser:
 *   http://localhost/nama-folder-project/test/cek-koneksi.php
 *
 * Tujuannya menunjukkan PERSIS di titik mana proses kirim WhatsApp gagal,
 * tanpa perlu melakukan registrasi dulu.
 *
 * HAPUS atau lindungi file ini setelah selesai testing, karena file ini
 * bisa dipakai siapa saja untuk mengirim WA test lewat device kalian
 * jika diakses publik.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../functions/fonnte.php';

header('Content-Type: text/html; charset=utf-8');

// Nomor tujuan untuk test dikirim dari form di bawah / query string ?nomor=
$nomorTest = trim($_GET['nomor'] ?? $_POST['nomor'] ?? '');

echo "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'>
<title>Diagnostik Fonnte</title>
<style>
body{font-family:monospace;background:#111;color:#ddd;padding:24px;line-height:1.6;}
.ok{color:#4caf50;font-weight:bold;}
.fail{color:#ff5252;font-weight:bold;}
.box{background:#1c1c1c;border:1px solid #333;border-radius:8px;padding:16px;margin:14px 0;}
h1{color:#fff;} h2{color:#9ad;margin-bottom:6px;}
input{padding:8px;width:260px;font-family:monospace;}
button{padding:8px 16px;font-family:monospace;background:#128C7E;color:#fff;border:none;border-radius:4px;cursor:pointer;}
pre{white-space:pre-wrap;word-break:break-all;}
</style></head><body>";

echo "<h1>🔍 Diagnostik Koneksi Fonnte</h1>";

// ------------------------------------------------------------
// LANGKAH 1: Cek file .env terbaca atau tidak
// ------------------------------------------------------------
$envInfo = env_debug_info();
echo "<div class='box'><h2>1. Pembacaan file .env</h2>";
if ($envInfo['ditemukan_di']) {
    echo "<span class='ok'>✔ File .env ditemukan</span> di: <code>{$envInfo['ditemukan_di']}</code><br>";
    echo "Jumlah baris konfigurasi terbaca: {$envInfo['jumlah_key']}";
} else {
    echo "<span class='fail'>✘ File .env TIDAK ditemukan.</span><br>";
    echo "Lokasi yang sudah dicoba:<pre>" . implode("\n", $envInfo['path_dicoba']) . "</pre>";
    echo "<strong>Solusi:</strong> copy <code>.env.example</code> menjadi <code>.env</code> tepat di folder root project (sejajar dengan folder config/, api/, index.html), lalu isi FONNTE_TOKEN.";
}
echo "</div>";

// ------------------------------------------------------------
// LANGKAH 2: Cek isi token
// ------------------------------------------------------------
$config = require __DIR__ . '/../config/fonnte.php';
echo "<div class='box'><h2>2. Token Fonnte</h2>";
if ($config['token'] === '') {
    echo "<span class='fail'>✘ FONNTE_TOKEN kosong.</span><br>";
    echo "Buka file .env, pastikan ada baris seperti:<br><code>FONNTE_TOKEN=abcd1234xxxx</code> (tanpa spasi/kutip, token asli dari dashboard Fonnte).";
} elseif ($config['token'] === 'TOKEN_FONNTE_KALIAN') {
    echo "<span class='fail'>✘ FONNTE_TOKEN masih placeholder.</span> Ganti dengan token asli dari dashboard Fonnte.";
} else {
    $tokenTersamar = substr($config['token'], 0, 4) . str_repeat('*', max(0, strlen($config['token']) - 8)) . substr($config['token'], -4);
    echo "<span class='ok'>✔ Token terbaca:</span> <code>{$tokenTersamar}</code> (panjang: " . strlen($config['token']) . " karakter)";
}
echo "<br>API URL: <code>{$config['api_url']}</code>";
echo "</div>";

// ------------------------------------------------------------
// LANGKAH 3: Cek ekstensi cURL
// ------------------------------------------------------------
echo "<div class='box'><h2>3. Ekstensi cURL PHP</h2>";
if (function_exists('curl_init')) {
    echo "<span class='ok'>✔ cURL aktif.</span>";
} else {
    echo "<span class='fail'>✘ cURL TIDAK aktif.</span> Aktifkan extension=curl di php.ini lalu restart Apache.";
}
echo "</div>";

// ------------------------------------------------------------
// LANGKAH 4: Cek koneksi internet server ke api.fonnte.com
// ------------------------------------------------------------
echo "<div class='box'><h2>4. Koneksi ke server Fonnte</h2>";
$ch = curl_init('https://api.fonnte.com');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_NOBODY => true,
]);
$pingOk = curl_exec($ch);
$pingErr = curl_error($ch);
curl_close($ch);

if ($pingErr) {
    echo "<span class='fail'>✘ Server tidak bisa menjangkau api.fonnte.com.</span><br>Error: <code>{$pingErr}</code><br>Kemungkinan: firewall/antivirus lokal, atau tidak ada koneksi internet keluar dari server.";
} else {
    echo "<span class='ok'>✔ Server berhasil menjangkau api.fonnte.com.</span>";
}
echo "</div>";

// ------------------------------------------------------------
// LANGKAH 5: Form untuk kirim WA test langsung
// ------------------------------------------------------------
echo "<div class='box'><h2>5. Kirim WhatsApp Percobaan</h2>";
echo "<form method='get'>
  <label>Nomor WhatsApp tujuan (format bebas, mis. 081234567890):</label><br>
  <input type='text' name='nomor' value='" . htmlspecialchars($nomorTest) . "' placeholder='081234567890'>
  <button type='submit'>Kirim Test</button>
</form>";

if ($nomorTest !== '') {
    echo "<hr><strong>Hasil percobaan kirim ke: " . htmlspecialchars($nomorTest) . "</strong><br>";

    if ($config['token'] === '') {
        echo "<span class='fail'>✘ Tidak dicoba dikirim, karena token masih kosong (perbaiki Langkah 1 & 2 dulu).</span>";
    } else {
        $hasil = kirimWhatsApp($nomorTest, "Test koneksi dari script diagnostik project-api-whatsapp ✅");

        if ($hasil['status']) {
            echo "<span class='ok'>✔ BERHASIL! Fonnte melaporkan pesan masuk antrian.</span><br>";
        } else {
            echo "<span class='fail'>✘ GAGAL.</span><br>";
        }
        echo "Target ternormalisasi: <code>{$hasil['target']}</code><br>";
        echo "Response mentah dari Fonnte:<pre>" . htmlspecialchars($hasil['response']) . "</pre>";

        echo "<div class='box' style='border-color:#555;'><strong>Cara membaca reason di atas:</strong><ul>
            <li><code>token invalid</code> → token salah/expired, copy ulang dari dashboard Fonnte.</li>
            <li><code>device not connected</code> / status false tanpa reason jelas → buka dashboard Fonnte, scan ulang QR code WhatsApp di menu Device.</li>
            <li><code>target invalid</code> → format nomor tidak dikenali, cek isi kolom 'Nomor tujuan' di atas.</li>
            <li><code>insufficient quota</code> → kuota/paket Fonnte habis.</li>
        </ul></div>";
    }
}
echo "</div>";

echo "</body></html>";
