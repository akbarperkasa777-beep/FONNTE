<?php
/**
 * functions/fonnte.php
 *
 * Berisi function reusable (Tahap H) yang bisa dipakai ulang untuk
 * fitur lain: Registrasi, Pembayaran, Reset Password, Notifikasi, dll.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Tahap M - Normalisasi Nomor WhatsApp
 *
 * Mengubah nomor lokal (0812xxxx) menjadi format internasional (62812xxxx)
 * yang dibutuhkan API Fonnte.
 *
 * Contoh:
 *   081234567890  -> 6281234567890
 *   6281234567890 -> 6281234567890 (dibiarkan, sudah benar)
 *   +6281234567890 -> 6281234567890
 */
function formatNomor(string $nomor): string
{
    // 1. Buang semua karakter selain angka
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    // 2. Jika diawali 0, ganti dengan kode negara 62
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    // 3. Jika ternyata belum ada kode negara sama sekali (misal user
    //    hanya mengetik 812xxxxxxx tanpa 0 di depan), tambahkan 62.
    if (substr($nomor, 0, 2) !== '62') {
        $nomor = '62' . $nomor;
    }

    return $nomor;
}

/**
 * Validasi sederhana apakah nomor (setelah dinormalisasi) masuk akal
 * sebagai nomor WhatsApp Indonesia. (Tahap N - Menangani Kesalahan,
 * Pengujian 5 - nomor tidak valid)
 */
function isNomorValid(string $nomorTernormalisasi): bool
{
    // Nomor Indonesia setelah normalisasi: 62 + 9-13 digit berikutnya
    return (bool) preg_match('/^62[0-9]{8,13}$/', $nomorTernormalisasi);
}

/**
 * Tahap H & I - Function Kirim WhatsApp via API Fonnte (HTTP POST / cURL)
 *
 * @param string $nomor Nomor WhatsApp tujuan (boleh format 08xxx atau 62xxx)
 * @param string $pesan Isi pesan yang akan dikirim
 * @return array{status: bool, response: string, target: string}
 */
function kirimWhatsApp(string $nomor, string $pesan): array
{
    $config = require __DIR__ . '/../config/fonnte.php';

    $target = formatNomor($nomor);

    // Tahap N - jangan sampai request dikirim dengan nomor yang jelas tidak valid
    if (!isNomorValid($target)) {
        return [
            'status'   => false,
            'response' => json_encode(['reason' => 'nomor tidak valid (gagal validasi lokal)']),
            'target'   => $target,
        ];
    }

    if (empty($config['token'])) {
        return [
            'status'   => false,
            'response' => json_encode(['reason' => 'FONNTE_TOKEN belum dikonfigurasi di .env']),
            'target'   => $target,
        ];
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $config['api_url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,          // Tahap N - jangan biarkan request menggantung selamanya
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => [
            'target'  => $target,
            'message' => $pesan,
            'delay'   => '2',
            'typing'  => false,
        ],
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . $config['token'],
        ],
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Tahap N - Menangani Kesalahan: gagal koneksi / Fonnte tidak tersedia
    if ($curlError) {
        return [
            'status'   => false,
            'response' => json_encode(['reason' => 'curl error: ' . $curlError]),
            'target'   => $target,
        ];
    }

    $decoded = json_decode($response, true);

    // Fonnte mengembalikan status:true jika pesan berhasil masuk antrian
    $status = is_array($decoded) && !empty($decoded['status']) && $httpCode === 200;

    return [
        'status'   => $status,
        'response' => $response ?: json_encode(['reason' => 'tidak ada response dari server']),
        'target'   => $target,
    ];
}

/**
 * Fitur Tambahan Q.2 - Log Pengiriman WhatsApp
 * Menyimpan setiap percobaan pengiriman ke tabel log_whatsapp,
 * dipakai juga oleh Tantangan X (status Terkirim/Gagal/Pending).
 */
function logWhatsApp(?int $userId, string $noTujuan, string $pesan, string $status, string $response): int
{
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'INSERT INTO log_whatsapp (user_id, no_tujuan, pesan, status, response)
         VALUES (:user_id, :no_tujuan, :pesan, :status, :response)'
    );

    $stmt->execute([
        ':user_id'   => $userId,
        ':no_tujuan' => $noTujuan,
        ':pesan'     => $pesan,
        ':status'    => $status,
        ':response'  => $response,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Tahap K - Membuat Pesan Dinamis (Fitur Tambahan Q.1 - Template Pesan)
 */
function buatPesanRegistrasi(string $nama, string $email, string $noHp): string
{
    return "🎉 REGISTRASI BERHASIL\n\n"
         . "Halo, {$nama}!\n"
         . "Selamat, akun Anda telah berhasil dibuat.\n\n"
         . "📧 Email: {$email}\n"
         . "📱 WhatsApp: {$noHp}\n\n"
         . "Terima kasih telah melakukan registrasi.";
}
