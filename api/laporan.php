<?php
/**
 * api/laporan.php
 *
 * Tantangan (Bagian X): Laporan Notifikasi WhatsApp.
 * Mengembalikan gabungan data users + log_whatsapp terakhir per user,
 * dengan kolom: No, Nama, Nomor WhatsApp, Tanggal, Pesan, Status.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

// Ambil log whatsapp TERBARU untuk masing-masing user (subquery MAX(id))
$sql = "
    SELECT
        u.id,
        u.nama,
        u.email,
        u.no_hp,
        u.created_at AS tanggal_registrasi,
        l.pesan,
        l.status AS status_whatsapp,
        l.created_at AS tanggal_kirim
    FROM users u
    LEFT JOIN log_whatsapp l
        ON l.id = (
            SELECT lw.id FROM log_whatsapp lw
            WHERE lw.user_id = u.id
            ORDER BY lw.id DESC
            LIMIT 1
        )
    ORDER BY u.id DESC
";

$rows = $pdo->query($sql)->fetchAll();

echo json_encode([
    'status' => true,
    'data'   => $rows,
]);
