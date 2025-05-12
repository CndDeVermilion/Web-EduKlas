<?php
session_start();

// Debug: Log session data to a file for inspection
file_put_contents('session_debug.log', print_r($_SESSION, true));

// Check if session variables are set for guru
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'guru' || !isset($_SESSION['id_guru'])) {
    header('Location: index.php?error=session_invalid');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Guru';
$id_guru = $_SESSION['id_guru']; // Fallback if nama not set
require_once 'koneksi.php';

// Set headers to ensure inline display
header('Content-Disposition: inline');

// Custom sanitization function
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Proses upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $judul = sanitizeInput($_POST['judul'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $id_guru = $_SESSION['id_guru'];

    // Bagian upload file
    $targetDir = "Uploads/materi/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES['file']['name']);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Map file extensions to jenis_file enum
    $allowedTypes = [
        'pdf' => 'dokumen',
        'ppt' => 'ppt',
        'pptx' => 'ppt',
        'mp4' => 'video',
        'avi' => 'video',
        'mov' => 'video'
    ];

    if (array_key_exists($fileType, $allowedTypes)) {
        // Add file size limit (100MB)
        $maxFileSize = 100 * 1024 * 1024; // 100MB
        if ($_FILES['file']['size'] > $maxFileSize) {
            $error = "Ukuran file terlalu besar. Maksimum 100MB.";
        } elseif (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
            $jenis_file = $allowedTypes[$fileType];
            $stmt = $conn->prepare("INSERT INTO materi (id_guru, judul, deskripsi, jenis_file, file_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $id_guru, $judul, $deskripsi, $jenis_file, $targetFilePath);
            if ($stmt->execute()) {
                $success = "Materi berhasil diupload!";
                // Debug: Log inserted data
                file_put_contents('upload_debug.log', "Inserted: id_guru=$id_guru, judul=$judul, deskripsi=$deskripsi, jenis_file=$jenis_file, file_path=$targetFilePath\n", FILE_APPEND);
            } else {
                $error = "Gagal menyimpan data ke database: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Gagal mengunggah file.";
        }
    } else {
        $error = "Format file tidak diperbolehkan. Gunakan PDF, PPT, PPTX, MP4, AVI, atau MOV.";
    }
}

// Proses hapus materi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id_materi = (int)($_POST['id_materi'] ?? 0);
    if ($id_materi > 0) {
        // Fetch file path to delete the file
        $stmt = $conn->prepare("SELECT file_path FROM materi WHERE id_materi = ? AND id_guru = ?");
        $stmt->bind_param("ii", $id_materi, $_SESSION['id_guru']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $filePath = $row['file_path'];
            // Delete from database
            $deleteStmt = $conn->prepare("DELETE FROM materi WHERE id_materi = ? AND id_guru = ?");
            $deleteStmt->bind_param("ii", $id_materi, $_SESSION['id_guru']);
            if ($deleteStmt->execute()) {
                // Delete file from server
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $success = "Materi berhasil dihapus!";
                // Debug: Log deletion
                file_put_contents('delete_debug.log', "Deleted: id_materi=$id_materi, id_guru={$_SESSION['id_guru']}, file_path=$filePath\n", FILE_APPEND);
            } else {
                $error = "Gagal menghapus materi dari database: " . $conn->error;
            }
            $deleteStmt->close();
        } else {
            $error = "Materi tidak ditemukan atau Anda tidak memiliki izin.";
        }
        $stmt->close();
    } else {
        $error = "ID materi tidak valid.";
    }
}

// Fetch materials for the guru
$result = $conn->query("SELECT * FROM materi WHERE id_guru = " . (int)$_SESSION['id_guru']);

// Updated getPreviewHTML to prioritize Microsoft Office Viewer for PPT/PPTX
function getPreviewHTML($filePath, $id_materi) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $escapedPath = htmlspecialchars($filePath, ENT_QUOTES);
    // Use relative path for local testing
    $fullUrl = $escapedPath; // Relative path, e.g., Uploads/materi/1234567890_sample.pdf
    // For Office/Google Docs Viewer, use absolute URL
    $baseUrl = 'http://localhost/Web%20EduKlas/'; // Replace with your actual server URL (e.g., ngrok URL)
    $absoluteUrl = $baseUrl . $escapedPath;

    // Debug: Log preview URLs and file accessibility
    $fileAccessible = file_exists($filePath) ? 'exists' : 'not found';
    file_put_contents('preview_debug.log', "Preview: extension=$extension, fullUrl=$fullUrl, absoluteUrl=$absoluteUrl, file=$fileAccessible\n", FILE_APPEND);

    if ($extension === 'pdf') {
        // Use PDF.js for PDF preview with inline display
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
        // Use Microsoft Office Online Viewer for PPT, PPTX
        $officeUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($absoluteUrl);
        $googleUrl = 'https://docs.google.com/viewer?url=' . urlencode($absoluteUrl) . '&embedded=true';
        return <<<HTML
        <iframe src="{$officeUrl}" width="300" height="200" class="preview-embed" frameborder="0" allowfullscreen
                onerror="console.error('Office Viewer failed for {$absoluteUrl}'); this.src='{$googleUrl}';"></iframe>
        <br><a href="{$escapedPath}" target="_blank">Lihat file (jika pratinjau gagal)</a>
HTML;
    } elseif (in_array($extension, ['doc', 'docx'])) {
        // Use Google Docs Viewer for DOC, DOCX
        $googleUrl = 'https://docs.google.com/viewer?url=' . urlencode($absoluteUrl) . '&embedded=true';
        return <<<HTML
        <iframe src="{$googleUrl}" width="300" height="200" class="preview-embed" frameborder="0"
                onerror="console.error('Google Docs Viewer failed for {$absoluteUrl}');"></iframe>
        <br><a href="{$escapedPath}" target="_blank">Lihat file (jika pratinjau gagal)</a>
HTML;
    } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
        // Video preview
        return "<video width=\"300\" height=\"200\" controls class=\"preview-embed\"><source src=\"{$escapedPath}\" type=\"video/{$extension}\">Your browser does not support the video tag.</video>";
    } else {
        return "<a href=\"{$escapedPath}\" target=\"_blank\">Lihat file</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Materi Guru - EduKlas</title>
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.16.105/build/pdf.min.js"></script>
    <style>
        .preview-embed {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
        .preview-container {
            max-width: 300px;
            height: 200px;
            overflow: auto;
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
                <a class="navbar-brand brand-logo-mini" href="dashboard-guru.php"><img src="assets/images/logo-mini.svg" alt="logo" /></a>
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
                                <h4 class="card-title">Upload Materi</h4>
                                <?php if (isset($success)): ?>
                                    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
                                <?php elseif (isset($error)): ?>
                                    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
                                <?php endif; ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="upload">
                                    <div class="form-group">
                                        <label for="judul">Judul:</label>
                                        <input type="text" class="form-control" id="judul" name="judul" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="deskripsi">Deskripsi:</label>
                                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="file">Upload File (PDF, DOC, DOCX, PPT, PPTX, MP4, AVI, MOV):</label>
                                        <input type="file" class="form-control" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,video/mp4,video/avi,video/quicktime" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 grid-margin">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Daftar Materi yang Telah Diupload</h4>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Deskripsi</th>
                                            <th>Jenis File</th>
                                            <th>File</th>
                                            <th>Preview</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                                <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['jenis_file']); ?></td>
                                                <td><a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank"><button type="button" class="btn btn-primary btn-sm">Lihat File</button></a></td>
                                                <td><?php echo getPreviewHTML($row['file_path'], $row['id_materi']); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_materi" value="<?php echo $row['id_materi']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
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
<script src="assets/js/settings.js"></script>
<script src="assets/js/todolist.js"></script>
</body>
</html>