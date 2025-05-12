<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Cek ke tabel admin
  $q_admin = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
  if (mysqli_num_rows($q_admin) === 1) {
    $data = mysqli_fetch_assoc($q_admin);
    $_SESSION['user_id'] = $data['id'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = 'admin';
    header("Location: dashboard_admin.php");
    exit;
  }

  // Cek ke tabel guru
  $q_guru = mysqli_query($conn, "SELECT * FROM guru WHERE username='$username' AND password='$password'");
  if (mysqli_num_rows($q_guru) === 1) {
    $data = mysqli_fetch_assoc($q_guru);
    $_SESSION['id_guru'] = $data['id_guru'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = 'guru';
    header("Location: dashboard_guru.php");
    exit;
  }

  // Cek ke tabel siswa
  $q_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE username='$username' AND password='$password'");
  if (mysqli_num_rows($q_siswa) === 1) {
    $data = mysqli_fetch_assoc($q_siswa);
    $_SESSION['id_siswa'] = $data['id_siswa'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = 'siswa';
    header("Location: dashboard_siswa.php");
    exit;
  }

  // Jika tidak cocok semua
  echo "<script>alert('Login gagal! Username atau password salah.'); window.location='index.php';</script>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - EduKlas</title>
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="row w-100 m-0">
        <div class="content-wrapper full-page-wrapper d-flex align-items-center auth login-bg">
          <div class="card col-lg-4 mx-auto">
            <div class="card-body px-5 py-5">
              <h3 class="card-title text-left mb-3">Login EduKlas</h3>
              <form method="post" action="">
                <div class="form-group">
                  <label>Username atau Email *</label>
                  <input type="text" class="form-control p_input" name="username" required>
                </div>
                <div class="form-group">
                  <label>Password *</label>
                  <input type="password" class="form-control p_input" name="password" required>
                </div>
                <div class="form-group d-flex align-items-center justify-content-between">
                  <div class="form-check">
                    <label class="form-check-label">
                      <input type="checkbox" class="form-check-input"> Ingat saya
                    </label>
                  </div>
                  <a href="#" class="forgot-pass">Lupa password?</a>
                </div>
                <div class="text-center">
                  <button type="submit" class="btn btn-primary btn-block enter-btn">Login</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/hoverable-collapse.js"></script>
  <script src="assets/js/misc.js"></script>
</body>
</html>
