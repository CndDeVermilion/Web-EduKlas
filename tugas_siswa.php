<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'siswa') {
  header('Location: index.php');
  exit;
}
$nama = $_SESSION['nama'];
$id_siswa = $_SESSION['id_siswa'];

// Koneksi ke database
$conn = new mysqli('localhost', 'root', '', 'db_eduklas');
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil daftar tugas
$result = $conn->query("SELECT * FROM tugas ORDER BY deadline ASC");
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tugas Siswa - EduKlas</title>
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
          <a class="sidebar-brand brand-logo" href="dashboard_siswa.php">
            <h2 class="text-white font-weight-bold ml-2">EduKlas</h2>
          </a>
        </div>
        <ul class="nav">
          <li class="nav-item nav-category">
            <span class="nav-link">Menu Siswa</span>
          </li>
          <li class="nav-item menu-items">
            <a class="nav-link" href="dashboard_siswa.php">
              <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
              <span class="menu-title">Beranda</span>
            </a>
          </li>
          <li class="nav-item menu-items">
            <a class="nav-link" href="materi.php">
              <span class="menu-icon"><i class="mdi mdi-book-open-variant"></i></span>
              <span class="menu-title">Materi</span>
            </a>
          </li>
          <li class="nav-item menu-items">
            <a class="nav-link" href="tugas_siswa.php">
              <span class="menu-icon"><i class="mdi mdi-clipboard-text"></i></span>
              <span class="menu-title">Tugas</span>
            </a>
          </li>
          <li class="nav-item menu-items">
            <a class="nav-link" href="absensi_siswa.php">
              <span class="menu-icon"><i class="mdi mdi-calendar-check"></i></span>
              <span class="menu-title">Absensi</span>
            </a>
          </li>
        </ul>
      </nav>
      <div class="container-fluid page-body-wrapper">
        <nav class="navbar p-0 fixed-top d-flex flex-row">
          <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
            <a class="navbar-brand brand-logo-mini" href="dashboard_siswa.php"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
          </div>
          <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
            <ul class="navbar-nav navbar-nav-right">
              <li class="nav-item dropdown">
                <a class="nav-link" id="profileDropdown" href="#" data-toggle="dropdown">
                  <div class="navbar-profile">
                    <img class="img-xs rounded-circle" src="assets/images/faces/face15.jpg" alt="">
                    <p class="mb-0 d-none d-sm-block navbar-profile-name"><?php echo htmlspecialchars($nama); ?></p>
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
                      <p class="preview-subject mb-1">Keluar</p>
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
                    <h4 class="card-title">Daftar Tugas</h4>
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>No</th>
                          <th>Judul Tugas</th>
                          <th>Deskripsi</th>
                          <th>Deadline</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $no = 1;
                        $now = new DateTime();
                        while ($row = $result->fetch_assoc()) {
                          $deadline = new DateTime($row['deadline']);
                          echo "<tr>";
                          echo "<td>$no</td>";
                          echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['deskripsi']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['deadline']) . "</td>";
                          echo "<td>";
                          if ($deadline < $now) {
                            echo "<span class='text-muted'>Tugas telah ditutup</span>";
                          } else {
                            echo "<a href='jawab_tugas.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'>Jawab Tugas</a>";
                          }
                          echo "</td>";
                          echo "</tr>";
                          $no++;
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">© 2025 EduKlas. Hak cipta dilindungi.</span>
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
<?php $conn->close(); ?>