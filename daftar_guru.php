<?php
session_start();
include("config.php");

// Proteksi akses halaman
if (!isset($_SESSION['login_user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Proses tambah & edit data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Amankan input
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $nip = mysqli_real_escape_string($conn, trim($_POST['nip']));
    $jenis_kelamin = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin']));
    $mata_pelajaran = mysqli_real_escape_string($conn, trim($_POST['mata_pelajaran']));

    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        //  Update data guru
        $id = (int) $_POST['edit_id'];
        $query = "UPDATE daftar 
                  SET nama='$nama', 
                      nip='$nip', 
                      jenis_kelamin='$jenis_kelamin', 
                      mata_pelajaran='$mata_pelajaran' 
                  WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $success = "Data guru berhasil diperbarui.";
        } else {
            $error = "Gagal memperbarui data guru: " . mysqli_error($conn);
        }
    } else {
        //  Tambah data baru
        $query = "INSERT INTO daftar (nama, nip, jenis_kelamin, mata_pelajaran) 
                  VALUES ('$nama', '$nip', '$jenis_kelamin', '$mata_pelajaran')";
        if (mysqli_query($conn, $query)) {
            $success = "Data guru berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan data guru: " . mysqli_error($conn);
        }
    }
}

// Ambil semua data guru urut dari ID terkecil ke terbesar
$result = mysqli_query($conn, "SELECT * FROM daftar ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Guru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>

<body class="flex bg-gray-50 min-h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-cyan-400 text-gray-800 min-h-screen shadow-lg">
        <div class="p-6">
            <img src="logo.png" alt="SDK Logo" class="w-12 h-12 mb-4">
            <h2 class="text-lg font-semibold mb-6 text-gray-900">SDK FRATERAN 2 KEDIRI</h2>
            <ul class="space-y-1">
                <li><a href="admin.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Dashboard</a></li>
                <li><a href="rekap_bulanan.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Rekap Bulanan</a></li>
                <li><a href="daftar_guru.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Daftar Guru</a></li>
                <li><a href="logout.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-semibold text-gray-900">Data Daftar Guru</h3>
            <button onclick="openInputModal()" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md transition">Tambah Data Guru</button>
        </div>

        <!-- Notifikasi -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-md mb-4"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-md mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Tabel Data -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="w-full text-sm text-gray-700">
                <thead class="bg-cyan-400 text-white">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Jenis Kelamin</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= htmlspecialchars($row['id']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['NIP']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['Jenis_Kelamin']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['mata_pelajaran']) ?></td>
                                <td class="px-4 py-3 space-x-2">
                                    <button onclick='openEditModal(<?= json_encode($row) ?>)' class='bg-yellow-500 hover:bg-yellow-400 text-white px-3 py-1 rounded-md'>Edit</button>
                                    <a href="hapus_daftar_guru.php?id=<?= $row['id'] ?>" class="bg-red-500 hover:bg-red-400 text-white px-3 py-1 rounded-md" onclick="return confirm('Apakah Anda yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-gray-500">Belum ada data guru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div id="inputModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-4 border-b">
                <h5 class="text-lg font-semibold">Input Data Guru</h5>
                <button onclick="closeInputModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form method="post" class="p-4">
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">Nama:</label>
                    <input type="text" name="nama" class="w-full border rounded-md px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">NIP:</label>
                    <input type="text" name="nip" class="w-full border rounded-md px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">Jenis Kelamin:</label>
                    <label><input type="radio" name="jenis_kelamin" value="Laki-laki" required> Laki-laki</label>
                    <label class="ml-4"><input type="radio" name="jenis_kelamin" value="Perempuan" required> Perempuan</label>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">Mata Pelajaran:</label>
                    <input type="text" name="mata_pelajaran" class="w-full border rounded-md px-3 py-2" required>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeInputModal()" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-4 border-b">
                <h5 class="text-lg font-semibold">Edit Data Guru</h5>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form method="post" class="p-4">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">Nama:</label>
                    <input type="text" id="edit_nama" name="nama" class="w-full border rounded-md px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">NIP:</label>
                    <input type="text" id="edit_nip" name="nip" class="w-full border rounded-md px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">Jenis Kelamin:</label>
                    <label><input type="radio" id="edit_laki" name="jenis_kelamin" value="Laki-laki"> Laki-laki</label>
                    <label class="ml-4"><input type="radio" id="edit_perempuan" name="jenis_kelamin" value="Perempuan"> Perempuan</label>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 mb-2">Mata Pelajaran:</label>
                    <input type="text" id="edit_mapel" name="mata_pelajaran" class="w-full border rounded-md px-3 py-2" required>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeEditModal()" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">Batal</button>
                    <button type="submit" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal handler
        function openInputModal() { document.getElementById('inputModal').classList.remove('hidden'); }
        function closeInputModal() { document.getElementById('inputModal').classList.add('hidden'); }

        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama;
            document.getElementById('edit_nip').value = data.nip;
            document.getElementById('edit_mapel').value = data.mata_pelajaran;
            if (data.jenis_kelamin === 'Laki-laki') document.getElementById('edit_laki').checked = true;
            else document.getElementById('edit_perempuan').checked = true;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
    </script>
</body>
</html>
