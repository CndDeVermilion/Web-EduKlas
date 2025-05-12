<?php
session_start();

// Catat data sesi untuk debugging
file_put_contents('session_debug.log', print_r($_SESSION, true));

// Periksa apakah sesi siswa valid
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'siswa' || !isset($_SESSION['id_siswa'])) {
    header('Location: index.php?error=sesi_tidak_valid');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Siswa'; // Nama default jika tidak ada

// Koneksi ke database
require_once 'koneksi.php';

// Pastikan charset UTF-8
if (!$conn->set_charset('utf8mb4')) {
    $error = "Gagal mengatur charset: " . $conn->error;
    file_put_contents('db_debug.log', "Charset error: " . $conn->error . "\n", FILE_APPEND);
}

// Set header untuk tampilan inline
header('Content-Disposition: inline');

// Query untuk mengambil daftar materi
$query = "
    SELECT m.id_materi, m.judul, m.deskripsi, m.jenis_file, m.file_path, m.created_at
    FROM materi m
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    $error = "Kesalahan database: " . $conn->error;
    file_put_contents('db_debug.log', "Gagal menyiapkan query: " . $conn->error . "\n", FILE_APPEND);
} else {
    if (!$stmt->execute()) {
        $error = "Gagal menjalankan query: " . $stmt->error;
        file_put_contents('db_debug.log', "Gagal menjalankan query: " . $stmt->error . "\n", FILE_APPEND);
    } else {
        $result = $stmt->get_result();
        if (!$result) {
            $error = "Gagal mengambil hasil query: " . $conn->error;
            file_put_contents('db_debug.log', "Gagal mengambil hasil: " . $conn->error . "\n", FILE_APPEND);
        } else {
            // Catat jumlah baris untuk debugging
            $row_count = $result->num_rows;
            file_put_contents('db_debug.log', "Jumlah materi ditemukan: " . $row_count . "\n", FILE_APPEND);
        }
    }
}

// URL dasar untuk akses file
$base_url = 'http://localhost/Web%20EduKlas/'; // Sesuaikan dengan URL server Anda

// Fungsi untuk menghasilkan HTML pratinjau
function getPreviewHTML($filePath, $id_materi, $judul) {
    global $base_url;
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $escapedPath = htmlspecialchars($filePath, ENT_QUOTES);
    $fullUrl = $escapedPath; // URL relatif untuk PDF.js dan video
    $absoluteUrl = $base_url . $escapedPath; // URL absolut untuk Office/Google Docs Viewer

    // Debug: Log preview URLs dan aksesibilitas file
    $fileAccessible = file_exists($filePath) ? 'exists' : 'not found';
    file_put_contents('preview_debug.log', "Preview: id_materi=$id_materi, extension=$extension, fullUrl=$fullUrl, absoluteUrl=$absoluteUrl, file=$fileAccessible\n", FILE_APPEND);

    if ($extension === 'pdf') {
        return <<<HTML
        <div class="preview-container">
            <canvas id="pdf-preview-{$id_materi}" class="preview-embed"></canvas>
            <script>
                pdfjsLib.getDocument('{$fullUrl}').promise.then(function(pdf) {
                    pdf.getPage(1).then(function(page) {
                        var canvas = document.getElementById('pdf-preview-{$id_materi}');
                        var context = canvas.getContext('2d');
                        var viewport = page.getViewport({scale: 0.5});
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        page.render({
                            canvasContext: context,
                            viewport: viewport
                        });
                    });
                }).catch(function(error) {
                    console.error('PDF.js Error for id_materi={$id_materi}: ', error);
                    document.getElementById('pdf-preview-{$id_materi}').outerHTML = '<p style="color: red;">Gagal memuat pratinjau PDF. <a href="{$fullUrl}" target="_blank">Lihat file</a></p>';
                });
            </script>
        </div>
HTML;
    } elseif (in_array($extension, ['ppt', 'pptx'])) {
        $officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($absoluteUrl);
        $googleUrl = 'https://docs.google.com/viewer?url=' . urlencode($absoluteUrl) . '&embedded=true';
        return <<<HTML
        <iframe src="{$officeUrl}" width="300" height="200" class="preview-embed" frameborder="0" allowfullscreen
                onerror="console.error('Office Viewer failed for {$absoluteUrl}'); this.src='{$googleUrl}';"></iframe>
        <br><a href="{$escapedPath}" target="_blank">Lihat file (jika pratinjau gagal)</a>
HTML;
    } elseif (in_array($extension, ['doc', 'docx'])) {
        $googleUrl = 'https://docs.google.com/viewer?url=' . urlencode($absoluteUrl) . '&embedded=true';
        return <<<HTML
        <iframe src="{$googleUrl}" width="300" height="200" class="preview-embed" frameborder="0"
                onerror="console.error('Google Docs Viewer failed for {$absoluteUrl}');"></iframe>
        <br><a href="{$escapedPath}" target="_blank">Lihat file (jika pratinjau gagal)</a>
HTML;
    } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
        $escapedJudul = htmlspecialchars($judul, ENT_QUOTES);
        return <<<HTML
        <video width="300" height="200" controls class="preview-embed">
            <source src="{$escapedPath}" type="video/{$extension}">
            Browser Anda tidak mendukung video.
        </video>
HTML;
    } else {
        return "<a href=\"{$escapedPath}\" target=\"_blank\">Lihat file</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Materi Siswa - EduKlas</title>
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.16.105/build/pdf.min.js"></script>
    <style>
        .preview-container {
            max-width: 300px;
            height: 200px;
            overflow: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .preview-embed {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .pdf-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .pdf-controls button {
            padding: 8px 15px;
            font-size: 14px;
        }
        .pdf-controls span {
            font-size: 14px;
            font-weight: bold;
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
                <a class="navbar-brand brand-logo-mini" href="dashboard-siswa.php"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
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
                                <h4 class="card-title">Daftar Materi</h4>
                                <?php if (isset($error)): ?>
                                    <p style="color: red;"><?php echo htmlspecialchars($error); ?> Pastikan tabel 'materi' ada di database.</p>
                                <?php elseif ($result->num_rows == 0): ?>
                                    <p style="color: blue;">Belum ada materi yang tersedia.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Judul</th>
                                                    <th>Deskripsi</th>
                                                    <th>Preview</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $no = 1;
                                                while ($materi = $result->fetch_assoc()) {
                                                    $file_path = $materi['file_path'];
                                                    $full_url = $base_url . $file_path;
                                                    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                                    // Catat URL file untuk debugging
                                                    file_put_contents('preview_debug.log', "Materi: id={$materi['id_materi']}, file_path=$file_path, full_url=$full_url, extension=$extension\n", FILE_APPEND);
                                                    echo "<tr>";
                                                    echo "<td>" . $no++ . "</td>";
                                                    echo "<td>" . htmlspecialchars($materi['judul']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($materi['deskripsi'] ?? '-') . "</td>";
                                                    echo "<td>" . getPreviewHTML($materi['file_path'], $materi['id_materi'], $materi['judul']) . "</td>";
                                                    echo "<td class='action-buttons'>";
                                                    if ($extension === 'pdf') {
                                                        echo "<button class='btn btn-primary btn-sm' onclick=\"bukaMateri('" . htmlspecialchars($file_path) . "', '" . htmlspecialchars($materi['judul']) . "', {$materi['id_materi']}, '" . htmlspecialchars($file_path) . "')\">Baca</button>";
                                                    } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
                                                        echo "<button class='btn btn-primary btn-sm' onclick=\"bukaMateri('" . htmlspecialchars($file_path) . "', '" . htmlspecialchars($materi['judul']) . "', {$materi['id_materi']}, '" . htmlspecialchars($file_path) . "')\">Tonton</button>";
                                                    }
                                                    echo "<a href='" . htmlspecialchars($file_path) . "' download><button class='btn btn-success btn-sm'>Download</button></a>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                                $stmt->close();
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
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

<!-- Modal Baca/Tonton Materi -->
<div class="modal fade" id="modalMateri" tabindex="-1" role="dialog" aria-labelledby="materiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Materi: <span id="materiTitle"></span></h5>
                <button type="button" class="close btn-danger" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Kontainer untuk PDF -->
                <div id="pdfContainer" style="display: none;">
                    <canvas id="pdfCanvas" class="preview-embed"></canvas>
                    <div class="pdf-controls">
                        <button id="prevPage" class="btn btn-secondary">Previous</button>
                        <span id="pageInfo">Halaman 1 dari 1</span>
                        <button id="nextPage" class="btn btn-secondary">Next</button>
                    </div>
                </div>
                <!-- Kontainer untuk video -->
                <video id="materiVideo" class="preview-embed" controls style="display: none;">
                    <source id="videoSource" src="" type="video/mp4">
                    Browser Anda tidak mendukung video.
                </video>
                <div class="progress mt-3">
                    <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <p class="mt-2 text-muted">Baca atau tonton materi sampai 100% untuk membuka tugas.</p>
            </div>
        </div>
    </div>
</div>

<!-- Script JavaScript -->
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/hoverable-collapse.js"></script>
<script src="assets/js/misc.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaMateri(materiUrl, materiTitle, materiId, materiFile) {
    $('#materiTitle').text(materiTitle);
    const pdfContainer = document.getElementById('pdfContainer');
    const pdfCanvas = document.getElementById('pdfCanvas');
    const video = document.getElementById('materiVideo');
    const videoSource = document.getElementById('videoSource');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');
    const progressBar = document.getElementById('progressBar');

    // Reset tampilan
    pdfContainer.style.display = 'none';
    video.style.display = 'none';
    videoSource.src = '';
    video.load();
    progressBar.style.width = '0%';
    progressBar.innerText = '0%';
    progressBar.setAttribute('aria-valuenow', 0);

    const ext = materiUrl.split('.').pop().toLowerCase();

    if (ext === 'pdf') {
        pdfContainer.style.display = 'block';
        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 1;
        let highestPage = 1; // Track the highest page viewed

        pdfjsLib.getDocument(materiUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            updateProgress(highestPage, totalPages);
            pageInfo.textContent = `Halaman ${currentPage} dari ${totalPages}`;
            renderPage(currentPage);

            // Aktifkan/nonaktifkan tombol
            prevPageBtn.disabled = currentPage <= 1;
            nextPageBtn.disabled = currentPage >= totalPages;
        }).catch(function(error) {
            console.error('Kesalahan PDF.js untuk materiId=' + materiId + ': ', error);
            pdfContainer.innerHTML = '<p style="color: red;">Gagal memuat pratinjau PDF. <a href="' + materiUrl + '" target="_blank">Lihat file</a></p>';
        });

        function renderPage(pageNum) {
            pdfDoc.getPage(pageNum).then(function(page) {
                const viewport = page.getViewport({ scale: 1.5 });
                pdfCanvas.height = viewport.height;
                pdfCanvas.width = viewport.width;
                page.render({
                    canvasContext: pdfCanvas.getContext('2d'),
                    viewport: viewport
                });
                pageInfo.textContent = `Halaman ${pageNum} dari ${totalPages}`;
                prevPageBtn.disabled = pageNum <= 1;
                nextPageBtn.disabled = pageNum >= totalPages;

                // Update highest page viewed and progress
                if (pageNum > highestPage) {
                    highestPage = pageNum;
                    updateProgress(highestPage, totalPages);
                }
            });
        }

        function updateProgress(current, total) {
            const progress = Math.min(Math.round((current / total) * 100), 100);
            progressBar.style.width = progress + '%';
            progressBar.innerText = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);

            if (progress >= 100) {
                fetch('update_progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ materiId: materiId, materiFile: materiFile })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Progres materi telah mencapai 100%! Tugas terkait materi ini sekarang tersedia di halaman Tugas.");
                        window.location.href = 'tugas_siswa.php'; // Arahkan ke halaman tugas
                    } else {
                        alert("Gagal menyimpan progres: " + (data.error || "Kesalahan tidak diketahui"));
                    }
                })
                .catch(error => {
                    alert("Kesalahan saat menyimpan progres: " + error.message);
                });
            }
        }

        prevPageBtn.onclick = function() {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        };

        nextPageBtn.onclick = function() {
            if (currentPage < totalPages) {
                currentPage++;
                renderPage(currentPage);
            }
        };
    } else if (ext === 'mp4' || ext === 'avi' || ext === 'mov') {
        videoSource.src = materiUrl;
        videoSource.type = 'video/' + ext;
        video.load();
        video.style.display = 'block';

        // Progres bar untuk video (tetap menggunakan logika asli)
        let progress = 0;
        const interval = setInterval(() => {
            if (progress < 100) {
                progress += 20;
                progressBar.style.width = progress + '%';
                progressBar.innerText = progress + '%';
                progressBar.setAttribute('aria-valuenow', progress);
            }
            if (progress >= 100) {
                clearInterval(interval);
                fetch('update_progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ materiId: materiId, materiFile: materiFile })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Progres materi telah mencapai 100%! Tugas terkait materi ini sekarang tersedia di halaman Tugas.");
                        window.location.href = 'tugas.php'; // Arahkan ke halaman tugas
                    } else {
                        alert("Gagal menyimpan progres: " + (data.error || "Kesalahan tidak diketahui"));
                    }
                })
                .catch(error => {
                    alert("Kesalahan saat menyimpan progres: " + error.message);
                });
            }
        }, 5000); // 5 detik per langkah, total 25 detik untuk 100%
    } else {
        alert("Tipe file tidak didukung.");
        return;
    }

    $('#modalMateri').modal('show');
}

function handleIframeError() {
    alert("Gagal memuat materi. Periksa koneksi atau file.");
    $('#modalMateri').modal('hide');
}
</script>
</body>
</html>