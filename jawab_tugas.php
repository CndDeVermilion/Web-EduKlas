<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'siswa' || !isset($_SESSION['id_siswa']) || !isset($_SESSION['nama'])) {
  header('Location: index.php');
  exit;
}
$nama = $_SESSION['nama'];
$id_siswa = $_SESSION['id_siswa'];

$conn = new mysqli('localhost', 'root', '', 'db_eduklas');
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

$id_tugas = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : 0;
$tugas = $conn->query("SELECT * FROM tugas WHERE id = '$id_tugas'")->fetch_assoc();
if (!$tugas) {
  die("Tugas tidak ditemukan.");
}

// Validasi deadline
$now = new DateTime();
$deadline = new DateTime($tugas['deadline']);
if ($deadline < $now) {
  die("Maaf, tugas ini telah ditutup karena melewati deadline.");
}

// Proses pengiriman semua jawaban
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_tugas'])) {
  if (!isset($_POST['jawaban']) || !is_array($_POST['jawaban'])) {
    $message = "<div class='alert alert-danger'>Harap jawab semua soal sebelum mengirim!</div>";
  } else {
    $jawaban = $_POST['jawaban'];
    $success = true;
    $conn->begin_transaction();
    try {
      foreach ($jawaban as $id_soal => $id_jawaban) {
        $id_soal = $conn->real_escape_string($id_soal);
        $id_jawaban = $conn->real_escape_string($id_jawaban);
        
        // Cek apakah siswa sudah menjawab soal ini
        $check = $conn->query("SELECT * FROM jawaban_siswa WHERE id_siswa = '$id_siswa' AND id_soal = '$id_soal'");
        if ($check->num_rows > 0) {
          continue; // Lewati jika sudah dijawab
        }
        
        $stmt = $conn->prepare("INSERT INTO jawaban_siswa (id_siswa, id_soal, id_jawaban, submitted_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iii", $id_siswa, $id_soal, $id_jawaban);
        if (!$stmt->execute()) {
          $success = false;
          $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
          break;
        }
        $stmt->close();
      }
      if ($success) {
        $conn->commit();
        $message = "<div class='alert alert-success'>Semua jawaban berhasil dikirim!</div>";
      } else {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>Gagal mengirim jawaban. Silakan coba lagi.</div>";
      }
    } catch (Exception $e) {
      $conn->rollback();
      $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
  }
}

// Ambil soal untuk tugas
$soal_result = $conn->query("SELECT * FROM soal WHERE id_tugas = '$id_tugas' ORDER BY id ASC");

// Cek apakah semua soal sudah dijawab
$all_answered = true;
$soal_ids = [];
if ($soal_result->num_rows > 0) {
  while ($soal = $soal_result->fetch_assoc()) {
    $soal_ids[] = $soal['id'];
    $check = $conn->query("SELECT * FROM jawaban_siswa WHERE id_siswa = '$id_siswa' AND id_soal = '" . $soal['id'] . "'");
    if ($check->num_rows == 0) {
      $all_answered = false;
    }
  }
  $soal_result->data_seek(0); // Reset pointer untuk digunakan kembali
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Jawab Tugas - EduKlas</title>
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <style>
    .alert {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 4px;
    }
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
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
                  <h4 class="card-title">Jawab Tugas: <?php echo htmlspecialchars($tugas['judul']); ?></h4>
                  <p><?php echo htmlspecialchars($tugas['deskripsi']); ?></p>
                  <?php if ($message): ?>
                    <?php echo $message; ?>
                  <?php endif; ?>
                  <hr>
                  <?php if ($soal_result->num_rows == 0): ?>
                    <p>Belum ada soal untuk tugas ini.</p>
                  <?php elseif ($all_answered): ?>
                    <p class='text-success'>Anda sudah menjawab semua soal untuk tugas ini.</p>
                  <?php else: ?>
                    <form method="POST">
                      <?php
                      $no = 1;
                      while ($soal = $soal_result->fetch_assoc()) {
                        $answered = $conn->query("SELECT * FROM jawaban_siswa WHERE id_siswa = '$id_siswa' AND id_soal = '" . $soal['id'] . "'")->num_rows > 0;
                        echo "<div class='mb-4'>";
                        echo "<h5>$no. " . htmlspecialchars($soal['pertanyaan']) . "</h5>";
                        $jawaban_result = $conn->query("SELECT * FROM jawaban WHERE id_soal = '" . $soal['id'] . "'");
                        while ($jawaban = $jawaban_result->fetch_assoc()) {
                          echo "<div class='form-check'>";
                          echo "<input class='form-check-input' type='radio' name='jawaban[" . $soal['id'] . "]' value='" . $jawaban['id'] . "' " . ($answered ? "disabled" : "required") . ">";
                          echo "<label class='form-check-label'>" . htmlspecialchars($jawaban['teks_jawaban']) . "</label>";
                          echo "</div>";
                        }
                        if ($answered) {
                          echo "<p class='text-success'>Soal ini sudah dijawab.</p>";
                        }
                        echo "</div>";
                        $no++;
                      }
                      ?>
                      <button type="submit" class="btn btn-primary mt-3" name="kirim_tugas" <?php echo $all_answered ? "disabled" : ""; ?>>Kirim Tugas</button>
                    </form>
                  <?php endif; ?>
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