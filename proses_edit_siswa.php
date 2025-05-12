<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['error'] = 'Metode tidak diizinkan!';
    header('Location: data_siswa.php');
    exit;
}

$id_siswa = isset($_POST['id_siswa']) ? intval($_POST['id_siswa']) : 0;
$nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($id_siswa <= 0 || empty($nama) || empty($username)) {
    $_SESSION['error'] = 'Data tidak lengkap!';
    header('Location: edit_siswa.php?id=' . $id_siswa);
    exit;
}

// Periksa apakah username sudah digunakan oleh siswa lain
$stmt = $conn->prepare("SELECT id_siswa FROM siswa WHERE username = ? AND id_siswa != ?");
$stmt->bind_param("si", $username, $id_siswa);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = 'Username sudah digunakan!';
    header('Location: edit_siswa.php?id=' . $id_siswa);
    $stmt->close();
    exit;
}
$stmt->close();

// Siapkan query update
if (empty($password)) {
    // Update tanpa password
    $stmt = $conn->prepare("UPDATE siswa SET nama = ?, username = ? WHERE id_siswa = ?");
    $stmt->bind_param("ssi", $nama, $username, $id_siswa);
} else {
    // Update dengan password (plain text)
    $stmt = $conn->prepare("UPDATE siswa SET nama = ?, username = ?, password = ? WHERE id_siswa = ?");
    $stmt->bind_param("sssi", $nama, $username, $password, $id_siswa);
}

if ($stmt->execute()) {
    $_SESSION['success'] = 'Data siswa berhasil diperbarui!';
    header('Location: data_siswa.php');
} else {
    $_SESSION['error'] = 'Gagal memperbarui data: ' . $stmt->error;
    header('Location: edit_siswa.php?id=' . $id_siswa);
}
$stmt->close();
$conn->close();
?>