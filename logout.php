<?php
session_start();
session_unset(); // Hapus semua variabel sesi
session_destroy(); // Hapus sesi dari server

header("Location: index.php"); // Arahkan ke halaman login
exit;
?>
