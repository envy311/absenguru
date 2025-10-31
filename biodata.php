<?php
session_start();
include("config.php");

// Pastikan guru sudah login
if (!isset($_SESSION['login_user']) || !isset($_SESSION['guru_id'])) {
    header("location: login.php");
    exit();
}

$guru_id = $_SESSION['guru_id'];
$message = "";

// Helper: terjemahkan kode error upload
function upload_error_message($code) {
    $errors = [
        UPLOAD_ERR_OK => "Tidak ada error, file berhasil diunggah.",
        UPLOAD_ERR_INI_SIZE => "File melebihi upload_max_filesize di konfigurasi server.",
        UPLOAD_ERR_FORM_SIZE => "File melebihi MAX_FILE_SIZE yang ditentukan pada form.",
        UPLOAD_ERR_PARTIAL => "File hanya terupload sebagian.",
        UPLOAD_ERR_NO_FILE => "Tidak ada file yang dipilih untuk diunggah.",
        UPLOAD_ERR_NO_TMP_DIR => "Folder sementara tidak ditemukan di server.",
        UPLOAD_ERR_CANT_WRITE => "Gagal menulis file ke disk server.",
        UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh ekstensi PHP."
    ];
    return isset($errors[$code]) ? $errors[$code] : "Kesalahan upload tidak diketahui (kode: $code).";
}

// Jika guru upload foto baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    // Bila tidak ada file
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Tidak ada file yang dipilih.";
    } else {
        // Periksa error upload dulu
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "Upload gagal: " . upload_error_message($file['error']);
        } else {
            // Batasi ukuran client-side juga (server-side check)
            $maxBytes = 2 * 1024 * 1024; // 2MB
            if ($file['size'] > $maxBytes) {
                $message = "Ukuran file terlalu besar. Maksimal 2MB.";
            } else {
                // Cek apakah benar file upload dari client
                if (!is_uploaded_file($file['tmp_name'])) {
                    $message = "File tidak valid sebagai upload.";
                } else {
                    // Validasi tipe gambar dengan getimagesize (lebih aman)
                    $imgInfo = @getimagesize($file['tmp_name']);
                    if ($imgInfo === false) {
                        $message = "File bukan gambar yang valid.";
                    } else {
                        // Valid extension
                        $allowedExt = ['jpg','jpeg','png','gif'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExt)) {
                            $message = "Ekstensi file tidak diperbolehkan. Hanya JPG, PNG, GIF.";
                        } else {
                            // Pastikan folder uploads ada dan writable
                            $upload_dir = __DIR__ . "/uploads/";
                            if (!is_dir($upload_dir)) {
                                if (!mkdir($upload_dir, 0777, true)) {
                                    $message = "Gagal membuat folder upload. Periksa permission server.";
                                }
                            }

                            if (empty($message)) {
                                // Buat nama file aman
                                $new_name = sprintf("guru_%d_%s.%s", $guru_id, time(), $ext);
                                $target_path = $upload_dir . $new_name;

                                // Pindahkan file dari tmp ke folder uploads
                                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                    // Simpan path relatif ke DB (tanpa __DIR__)
                                    $db_path = "uploads/" . $new_name;
                                    $update_photo = "UPDATE daftar SET photo = '" . mysqli_real_escape_string($conn, $db_path) . "' WHERE id = '" . mysqli_real_escape_string($conn, $guru_id) . "'";
                                    if (mysqli_query($conn, $update_photo)) {
                                        $message = "Foto profil berhasil diperbarui.";
                                    } else {
                                        // Jika gagal simpan DB, hapus file yang sudah dipindah
                                        @unlink($target_path);
                                        $message = "Gagal menyimpan data foto ke database.";
                                    }
                                } else {
                                    $message = "Gagal memindahkan file ke folder uploads. Periksa permission (chmod) folder uploads.";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Ambil data biodata guru
$query = "SELECT * FROM daftar WHERE id = '" . mysqli_real_escape_string($conn, $guru_id) . "'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

// Untuk debugging singkat (bisa dihapus jika sudah beres)
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Biodata Guru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex bg-gray-50 min-h-screen">

    <!-- Sidebar -->
    <div class="w-64 bg-cyan-400 text-gray-800 min-h-screen shadow-lg">
        <div class="p-6">
            <img src="logo.png" alt="SDK Logo" class="w-12 h-12 mb-4">
            <h2 class="text-lg font-semibold mb-6 text-gray-900">SDK FRANTERAN 2 KEDIRI</h2>
            <ul class="space-y-1">
                <li><a href="biodata.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Biodata</a></li>
                <li><a href="guru.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Absen</a></li>
                <li><a href="logout.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">
            <div class="text-center mb-6">
                <!-- Foto profil bulat -->
                <img src="<?= !empty($data['photo']) ? htmlspecialchars($data['photo']) : 'uploads/orang.png'; ?>"
                     alt="Foto Guru"
                     class="w-32 h-32 mx-auto rounded-full border-4 border-gray-200 shadow-md object-cover">
                
                <h2 class="text-2xl font-semibold mt-4 text-gray-800"><?= htmlspecialchars($data['nama']); ?></h2>
                <p class="text-gray-500">Guru SDK FRANTERAN 2 Kediri</p>
            </div>

            <!-- Form upload foto -->
            <form method="post" enctype="multipart/form-data" class="text-center mb-6">
                <input type="file" name="photo" accept="image/*" class="border border-gray-300 rounded-md p-2 w-full mb-3 text-gray-700" required>
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md transition duration-200">Upload Foto Baru</button>
            </form>

            <?php if ($message): ?>
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded-md text-center mb-4"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="border-t border-gray-200 mt-4 pt-4 space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-700 font-medium">Nama</span>
                    <span class="text-gray-900"><?= htmlspecialchars($data['nama']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 font-medium">NIP</span>
                    <span class="text-gray-900"><?= htmlspecialchars($data['NIP']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 font-medium">Jenis Kelamin</span>
                    <span class="text-gray-900"><?= htmlspecialchars($data['Jenis_Kelamin']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 font-medium">Mata Pelajaran</span>
                    <span class="text-gray-900"><?= htmlspecialchars($data['mata_pelajaran']); ?></span>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="guru.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-md transition duration-200">Pergi ke Absen</a>
            </div>

            <!-- Debug info kecil (hapus jika sudah ok) -->
            <div class="mt-4 text-xs text-gray-500">
                <div>upload_max_filesize: <?= htmlspecialchars($upload_max_filesize); ?></div>
                <div>post_max_size: <?= htmlspecialchars($post_max_size); ?></div>
            </div>
        </div>
    </div>

</body>
</html>
