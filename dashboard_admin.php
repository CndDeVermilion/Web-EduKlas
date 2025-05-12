<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
  header('Location: index.php');
  exit;
}
$nama = $_SESSION['nama'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard Admin - EduKlas</title>
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
          <a class="sidebar-brand brand-logo" href="dashboard_admin.php">
          <h2 class="text-white font-weight-bold ml-2">EduKlas</h2>
          </a>
        </div>
        <ul class="nav">
          <li class="nav-item nav-category">
            <span class="nav-link">Menu Admin</span>
          </li>
          <li class="nav-item menu-items">
            <a class="nav-link" href="dashboard_admin.php">
              <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
              <span class="menu-title">Beranda</span>
            </a>
          </li>
          <li class="nav-item menu-items">
            <a class="nav-link" href="tambah_akun_guru.php">
            <span class="menu-icon"><i class="mdi mdi-account-plus"></i></span>
              <span class="menu-title">Tambah Akun Guru</span>
            </a>
        </li>
        <li class="nav-item menu-items">
            <a class="nav-link" href="tambah_akun_siswa.php">
            <span class="menu-icon"><i class="mdi mdi-account-multiple-plus"></i></span>
              <span class="menu-title">Tambah Akun Siswa</span>
            </a>
        </li>
        <li class="nav-item menu-items">
            <a class="nav-link" href="data_siswa.php">
            <span class="menu-icon"><i class="mdi mdi-account-multiple"></i></span>
                <span class="menu-title">Akun Siswa</span>
             </a>
        </li>
        <li class="nav-item menu-items">
            <a class="nav-link" href="data_guru.php">
            <span class="menu-icon"><i class="mdi mdi-account"></i></span>
                <span class="menu-title">Akun Guru</span>
            </a>
        </li>

        </ul>
      </nav>
      <div class="container-fluid page-body-wrapper">
        <nav class="navbar p-0 fixed-top d-flex flex-row">
          <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
            <a class="navbar-brand brand-logo-mini" href="dashboard-siswa.php"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
          </div>
          <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
            <ul class="navbar-nav navbar-nav-right">
              <li class="nav-item dropdown">
                <a class="nav-link" id="profileDropdown" href="#" data-toggle="dropdown">
                  <div class="navbar-profile">
                    <img class="img-xs rounded-circle" src="assets/images/faces/face15.jpg" alt="">
                    <p class="mb-0 d-none d-sm-block navbar-profile-name"><?php echo $nama; ?></p>
                    <i class="mdi mdi-menu-down d-none d-sm-block"></i>
                  </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="profileDropdown">
                  <a class="dropdown-item preview-item" href="logout.php">
                    <div class="preview-thumbnail">
                      <div class="preview-icon bg-dark rounded-circle">
                        <i class="mdi mdi-logout text-danger"></i>
                      </div>
                    </div>
                    <div class="preview-item-content">
                      <p class="preview-subject mb-1">Logout</p>
                    </div>
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </nav>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-md-12 grid-margin">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Selamat Datang <?php echo $nama; ?> di EduKlas</h4>
                    <p class="card-description"><marque>Berikut adalah ringkasan informasi untuk kamu</marque></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">© 2025 EduKlas. All rights reserved.</span>
            </div>
          </footer>
        </div>
      </div>
    </div>
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/hoverable-collapse.js"></script>
    <script src="assets/js/misc.js"></script>
  </body>
</html>
