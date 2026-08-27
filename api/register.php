<?php
/**
 * api/register.php
 *
 * Urutan proses (Tahap L - Hubungkan dengan Proses Registrasi):
 * 1. User mengisi form               -> diterima di sini via $_POST
 * 2. Server menerima data
 * 3. Validasi data
 * 4. Cek email/nomor sudah terdaftar
 * 5. Simpan ke database
 * 6. Pastikan INSERT berhasil
 * 7. Buat pesan WhatsApp
 * 8. Kirim melalui Fonnte
 * 9. Tampilkan hasil registrasi
 *
 * ATURAN PENTING (Bagian L & N):
 * WhatsApp HANYA dikirim setelah data BERHASIL disimpan ke database.
 * Jika WhatsApp gagal terkirim, data user TETAP tersimpan.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/fonnte.php';

function jsonResponse(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => false, 'message' => 'Method tidak diizinkan.'], 405);
}

// ------------------------------------------------------------
// 2 & 3. Terima data & validasi (Bagian S.3 - Validasi Input)
// ------------------------------------------------------------
$nama     = trim($_POST['nama'] ?? '');
$email    = trim($_POST['email'] ?? '');
$noHpRaw  = trim($_POST['no_hp'] ?? '');
$password = (string) ($_POST['password'] ?? '');

$errors = [];

if ($nama === '' || mb_strlen($nama) < 3) {
    $errors[] = 'Nama minimal 3 karakter.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid.';
}

if ($noHpRaw === '' || !preg_match('/^[0-9+\-\s]{9,15}$/', $noHpRaw)) {
    $errors[] = 'Nomor WhatsApp tidak valid.';
}

if (strlen($password) < 6) {
    $errors[] = 'Password minimal 6 karakter.';
}

if (!empty($errors)) {
    jsonResponse([
        'status'  => false,
        'message' => 'Validasi gagal.',
        'errors'  => $errors,
    ], 422);
}

$noHp = formatNomor($noHpRaw);

if (!isNomorValid($noHp)) {
    jsonResponse([
        'status'  => false,
        'message' => 'Nomor WhatsApp tidak dapat diproses/tidak valid.',
    ], 422);
}

$pdo = getDbConnection();

// ------------------------------------------------------------
// 4. Cek apakah email / nomor WhatsApp sudah terdaftar
// ------------------------------------------------------------
$cek = $pdo->prepare('SELECT id, email, no_hp FROM users WHERE email = :email OR no_hp = :no_hp LIMIT 1');
$cek->execute([':email' => $email, ':no_hp' => $noHp]);
$existing = $cek->fetch();

if ($existing) {
    $pesanError = $existing['email'] === $email
        ? 'Email sudah terdaftar.'
        : 'Nomor WhatsApp sudah terdaftar.';

    jsonResponse([
        'status'  => false,
        'message' => $pesanError,
    ], 409);
}

// ------------------------------------------------------------
// 5 & 6. Simpan ke database (password di-hash, Bagian S.4)
// ------------------------------------------------------------
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insertBerhasil = false;
$userId = null;

try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (nama, email, no_hp, password) VALUES (:nama, :email, :no_hp, :password)'
    );

    $insertBerhasil = $stmt->execute([
        ':nama'     => $nama,
        ':email'    => $email,
        ':no_hp'    => $noHp,
        ':password' => $hashedPassword,
    ]);

    if ($insertBerhasil) {
        $userId = (int) $pdo->lastInsertId();
    }
} catch (PDOException $e) {
    $insertBerhasil = false;
}

if (!$insertBerhasil) {
    jsonResponse([
        'status'  => false,
        'message' => 'Registrasi gagal disimpan ke database.',
    ], 500);
}

// ------------------------------------------------------------
// 7 & 8. HANYA jika database berhasil -> buat pesan & kirim WhatsApp
// ------------------------------------------------------------
$pesan = buatPesanRegistrasi($nama, $email, $noHp);
$hasilWa = kirimWhatsApp($noHp, $pesan);

$statusLog = $hasilWa['status'] ? 'success' : 'failed';
logWhatsApp($userId, $hasilWa['target'], $pesan, $statusLog, $hasilWa['response']);

// ------------------------------------------------------------
// 9. Tampilkan hasil registrasi
//    (Registrasi TETAP berhasil walau notifikasi WhatsApp gagal)
// ------------------------------------------------------------
jsonResponse([
    'status'  => true,
    'message' => 'Registrasi berhasil.',
    'data'    => [
        'id'    => $userId,
        'nama'  => $nama,
        'email' => $email,
        'no_hp' => $noHp,
    ],
    'notifikasi_whatsapp' => [
        'status'  => $hasilWa['status'] ? 'terkirim' : 'gagal',
        'catatan' => $hasilWa['status']
            ? '✅ Notifikasi WhatsApp berhasil dikirim ke nomor terdaftar.'
            : '⚠️ Notifikasi WhatsApp gagal dikirim, namun data registrasi tetap tersimpan.',
    ],
]);