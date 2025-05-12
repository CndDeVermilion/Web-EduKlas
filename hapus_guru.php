<?php
include 'koneksi.php';
$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM guru WHERE id_guru = $id");
header("Location: data_guru.php");
