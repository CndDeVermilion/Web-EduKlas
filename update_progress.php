<?php
session_start();
header('Content-Type: application/json');

// Periksa sesi siswa
if (!isset($_SESSION['id_siswa']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    echo json_encode(['success' => false, 'error' => 'Sesi tidak valid']);
    exit;
}

require_once 'koneksi.php';

// Pastikan charset UTF-8
if (!$conn->set_charset('utf8mb4')) {
    echo json_encode(['success' => false, 'error' => 'Gagal mengatur charset: ' . $conn->error]);
    exit;
}

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);
$materiId = $input['materiId'] ?? null;
$materiFile = $input['materiFile'] ?? null;

if (!$materiId || !$materiFile) {
    echo json_encode(['success' => false, 'error' => 'Data materi tidak lengkap']);
    exit;
}

$id_siswa = $_SESSION['id_siswa'];

// Log data untuk debugging
file_put_contents('progress_debug.log', "id_siswa: $id_siswa, materiId: $materiId, materiFile: $materiFile\n", FILE_APPEND);

// Periksa apakah materi ada
$stmt = $conn->prepare("SELECT id_materi FROM materi WHERE id_materi = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Gagal menyiapkan query: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $materiId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Materi tidak ditemukan']);
    exit;
}
$stmt->close();

// Simpan atau perbarui progres
$stmt = $conn->prepare("
    INSERT INTO materi_progress (id_siswa, id_materi, progress, file_path, updated_at)
    VALUES (?, ?, 100, ?, NOW())
    ON DUPLICATE KEY UPDATE progress = 100, file_path = ?, updated_at = NOW()
");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Gagal menyiapkan query: ' . $conn->error]);
    exit;
}
$stmt->bind_param("iiss", $id_siswa, $materiId, $materiFile, $materiFile);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal menyimpan progres: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>