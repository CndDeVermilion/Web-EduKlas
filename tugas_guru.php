<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'guru' || !isset($_SESSION['nama']) || !isset($_SESSION['id_guru'])) {
  header('Location: index.php');
  exit;
}
$nama = $_SESSION['nama'];
$id_guru = $_SESSION['id_guru'];

// Koneksi ke database
$conn = new mysqli('localhost', 'root', '', 'db_eduklas');
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Proses penambahan tugas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_tugas'])) {
  $judul = $conn->real_escape_string($_POST['judul']);
  $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
  $deadline = $conn->real_escape_string($_POST['deadline']);
  
  $stmt = $conn->prepare("INSERT INTO tugas (id_guru, judul, deskripsi, deadline, created_at) VALUES (?, ?, ?, ?, NOW())");
  $stmt->bind_param("isss", $id_guru, $judul, $deskripsi, $deadline);
  if ($stmt->execute()) {
    echo "<script>alert('Tugas berhasil ditambahkan!');</script>";
  } else {
    echo "<script>alert('Error: " . $conn->error . "');</script>";
  }
  $stmt->close();
}

// Proses penambahan soal
$soal_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_soal'])) {
  $id_tugas = $conn->real_escape_string($_POST['id_tugas']);
  $pertanyaan = $conn->real_escape_string($_POST['pertanyaan']);
  $jawaban = $_POST['jawaban'];
  $is_correct = $_POST['is_correct'];
  
  // Validasi jumlah soal (maksimum 10 per tugas)
  $stmt = $conn->prepare("SELECT COUNT(*) FROM soal WHERE id_tugas = ?");
  $stmt->bind_param("i", $id_tugas);
  $stmt->execute();
  $soal_count = $stmt->get_result()->fetch_row()[0];
  $stmt->close();
  
  if ($soal_count >= 10) {
    $soal_message = "<div class='alert alert-danger'>Batas maksimum 10 soal per tugas telah tercapai!</div>";
  } else {
    $stmt = $conn->prepare("INSERT INTO soal (id_tugas, pertanyaan, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $id_tugas, $pertanyaan);
    if ($stmt->execute()) {
      $id_soal = $conn->insert_id;
      $stmt_jawaban = $conn->prepare("INSERT INTO jawaban (id_soal, teks_jawaban, is_correct) VALUES (?, ?, ?)");
      for ($i = 0; $i < count($jawaban); $i++) {
        $teks_jawaban = $conn->real_escape_string($jawaban[$i]);
        $correct = ($is_correct == $i) ? 1 : 0;
        $stmt_jawaban->bind_param("isi", $id_soal, $teks_jawaban, $correct);
        $stmt_jawaban->execute();
      }
      $stmt_jawaban->close();
      $soal_message = "<div class='alert alert-success'>Soal berhasil ditambahkan! Silakan tambah soal lagi atau tutup modal.</div>";
    } else {
      $soal_message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
    $stmt->close();
  }
}

// Ambil data tugas
$stmt = $conn->prepare("SELECT t.*, (SELECT COUNT(*) FROM soal s WHERE s.id_tugas = t.id) as jumlah_soal FROM tugas t WHERE t.id_guru = ? ORDER BY t.deadline ASC");
$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
  die("Error query: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Tugas Guru - EduKlas</title>
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
        <a class="sidebar-brand brand-logo" href="dashboard_guru.php">
          <h2 class="text-white font-weight-bold ml-2">EduKlas</h2>
        </a>
      </div>
      <ul class="nav">
        <li class="nav-item nav-category">
          <span class="nav-link">Menu Guru</span>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="dashboard_guru.php">
            <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
            <span class="menu-title">Beranda</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="upload_materi_guru.php">
            <span class="menu-icon"><i class="mdi mdi-book-open-variant"></i></span>
            <span class="menu-title">Materi</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="tugas_guru.php">
            <span class="menu-icon"><i class="mdi mdi-clipboard-text"></i></span>
            <span class="menu-title">Tugas</span>
          </a>
        </li>
        <li class="nav-item menu-items">
          <a class="nav-link" href="absensi.php">
            <span class="menu-icon"><i class="mdi mdi-calendar-check"></i></span>
            <span class="menu-title">Absensi</span>
          </a>
        </li>
      </ul>
    </nav>
    <div class="container-fluid page-body-wrapper">
      <nav class="navbar p-0 fixed-top d-flex flex-row">
        <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
          <a class="navbar-brand brand-logo-mini" href="dashboard_guru.php"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
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
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Daftar Tugas</h4>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tambahTugasModal">
                      Tambah Tugas
                    </button>
                  </div>
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Judul Tugas</th>
                        <th>Deskripsi</th>
                        <th>Deadline</th>
                        <th>Jumlah Soal</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $no = 1;
                      while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>$no</td>";
                        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['deskripsi']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['deadline']) . "</td>";
                        echo "<td>" . $row['jumlah_soal'] . "</td>";
                        echo "<td><button type='button' class='btn btn-sm btn-primary' data-toggle='modal' data-target='#tambahSoalModal' data-id='" . $row['id'] . "'>Tambah Soal</button></td>";
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
        <!-- Modal Tambah Tugas -->
        <div class="modal fade" id="tambahTugasModal" tabindex="-1" role="dialog" aria-labelledby="tambahTugasModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="tambahTugasModalLabel">Tambah Tugas Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
              </div>
              <form method="POST">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="judul">Judul Tugas</label>
                    <input type="text" class="form-control" id="judul" name="judul" required>
                  </div>
                  <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary" name="tambah_tugas">Simpan Tugas</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <!-- Modal Tambah Soal -->
        <div class="modal fade" id="tambahSoalModal" tabindex="-1" role="dialog" aria-labelledby="tambahSoalModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="tambahSoalModalLabel">Tambah Soal Pilihan Ganda</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
              </div>
              <form method="POST" id="tambahSoalForm">
                <div class="modal-body">
                  <input type="hidden" id="id_tugas" name="id_tugas">
                  <?php if ($soal_message): ?>
                    <?php echo $soal_message; ?>
                  <?php endif; ?>
                  <div class="form-group">
                    <label for="pertanyaan">Pertanyaan</label>
                    <textarea class="form-control" id="pertanyaan" name="pertanyaan" rows="3" required></textarea>
                  </div>
                  <div class="form-group">
                    <label>Opsi Jawaban</label>
                    <div class="form-check">
                      <input type="text" class="form-control mb-2" name="jawaban[]" required placeholder="Opsi 1">
                      <input type="radio" class="form-check-input" name="is_correct" value="0" required> Benar
                    </div>
                    <div class="form-check">
                      <input type="text" class="form-control mb-2" name="jawaban[]" required placeholder="Opsi 2">
                      <input type="radio" class="form-check-input" name="is_correct" value="1"> Benar
                    </div>
                    <div class="form-check">
                      <input type="text" class="form-control mb-2" name="jawaban[]" required placeholder="Opsi 3">
                      <input type="radio" class="form-check-input" name="is_correct" value="2"> Benar
                    </div>
                    <div class="form-check">
                      <input type="text" class="form-control mb-2" name="jawaban[]" required placeholder="Opsi 4">
                      <input type="radio" class="form-check-input" name="is_correct" value="3"> Benar
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Selesai</button>
                  <button type="submit" class="btn btn-primary" name="tambah_soal">Simpan Soal</button>
                </div>
              </form>
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
  <script>
    $('#tambahSoalModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var id_tugas = button.data('id');
      var modal = $(this);
      modal.find('.modal-body #id_tugas').val(id_tugas);
      // Reset form saat modal dibuka
      modal.find('#tambahSoalForm')[0].reset();
      modal.find('.alert').remove(); // Hapus pesan sebelumnya
    });

    // Reset form setelah submit berhasil
    $('#tambahSoalForm').on('submit', function (e) {
      // Form akan submit via POST, tetapi kita pastikan modal tetap terbuka
      setTimeout(function() {
        // Reset form setelah 1 detik untuk memastikan pesan sukses muncul
        $('#tambahSoalForm')[0].reset();
      }, 1000);
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>