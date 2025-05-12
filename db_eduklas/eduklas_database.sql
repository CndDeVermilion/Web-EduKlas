
-- Tabel Admin
CREATE TABLE admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Guru
CREATE TABLE guru (
    id_guru INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Siswa
CREATE TABLE siswa (
    id_siswa INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Materi
CREATE TABLE materi (
    id_materi INT AUTO_INCREMENT PRIMARY KEY,
    id_guru INT,
    judul VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    jenis_file ENUM('dokumen', 'ppt', 'video') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru) ON DELETE CASCADE
);

-- Tabel Tugas
CREATE TABLE tugas (
    id_tugas INT AUTO_INCREMENT PRIMARY KEY,
    id_guru INT,
    judul VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    file_path VARCHAR(255),
    tanggal_dibuat DATE DEFAULT CURRENT_DATE,
    deadline DATE,
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru) ON DELETE CASCADE
);

-- Tabel Pengumpulan Tugas
CREATE TABLE pengumpulan_tugas (
    id_pengumpulan INT AUTO_INCREMENT PRIMARY KEY,
    id_tugas INT,
    id_siswa INT,
    file_path VARCHAR(255) NOT NULL,
    waktu_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tugas) REFERENCES tugas(id_tugas) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
);

-- Tabel Absensi
CREATE TABLE absensi (
    id_absensi INT AUTO_INCREMENT PRIMARY KEY,
    id_guru INT,
    tanggal DATE NOT NULL,
    topik VARCHAR(100),
    FOREIGN KEY (id_guru) REFERENCES guru(id_guru) ON DELETE CASCADE
);

-- Tabel Absensi Siswa
CREATE TABLE absensi_siswa (
    id_absensi_siswa INT AUTO_INCREMENT PRIMARY KEY,
    id_absensi INT,
    id_siswa INT,
    status ENUM('hadir', 'izin', 'alpa') NOT NULL,
    FOREIGN KEY (id_absensi) REFERENCES absensi(id_absensi) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
);
