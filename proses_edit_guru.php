<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['error'] = 'Metode tidak diizinkan!';
    header('Location: data_guru.php');
    exit;
}

$id_guru = isset($_POST['id_guru']) ? intval($_POST['id_guru']) : 0;
$nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($id_guru <= 0 || empty($nama) || empty($username)) {
    $_SESSION['error'] = 'Data tidak lengkap!';
    header('Location: edit_guru.php?id=' . $id_guru);
    exit;
}

// Periksa apakah username sudah digunakan oleh guru lain
$stmt = $conn->prepare("SELECT id_guru FROM guru WHERE username = ? AND id_guru != ?");
$stmt->bind_param("si", $username, $id_guru);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = 'Username sudah digunakan!';
    header('Location: edit_guru.php?id=' . $id_guru);
    $stmt->close();
    exit;
}
$stmt->close();

// Siapkan query update
if (empty($password)) {
    // Update tanpa password
    $stmt = $conn->prepare("UPDATE guru SET nama = ?, username = ? WHERE id_guru = ?");
    $stmt->bind_param("ssi", $nama, $username, $id_guru);
} else {
    // Update dengan password (hash)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE guru SET nama = ?, username = ?, password = ? WHERE id_guru = ?");
    $stmt->bind_param("sssi", $nama, $username, $hashed_password, $id_guru);
}

if ($stmt->execute()) {
    $_SESSION['success'] = 'Data guru berhasil diperbarui!';
    header('Location: data_guru.php');
} else {
    $_SESSION['error'] = 'Gagal memperbarui data: ' . $stmt->error;
    header('Location: edit_guru.php?id=' . $id_guru);
}
$stmt->close();
$conn->close();
?>