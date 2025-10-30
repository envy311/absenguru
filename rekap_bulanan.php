<?php
include("config.php");
session_start();

// 🔒 Proteksi agar hanya admin login yang bisa akses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ✅ Query aman menggunakan agregasi PostgreSQL
$query = "
    SELECT 
        d.nama AS nama_guru,
        EXTRACT(MONTH FROM a.waktu_absen) AS bulan,
        SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN a.status = 'tidak hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
        SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) AS sakit,
        SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) AS izin
    FROM absensi a
    INNER JOIN daftar d ON a.guru_id = d.id
    GROUP BY d.id, bulan
    ORDER BY d.id ASC, bulan DESC
";

$result = pg_query($conn, $query);

// Fungsi ubah angka bulan → nama bulan
function namaBulan($bulan) {
    $nama = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $nama[(int)$bulan] ?? '-';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi Bulanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tambahkan pustaka untuk export Excel -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Mode print: hanya tampil tabel */
        @media print {
            button, .sidebar { display: none !important; }
            table { border-collapse: collapse; width: 100%; }
            thead { background: #000 !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>

<body class="flex bg-gray-50 min-h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-white text-gray-800 min-h-screen shadow-lg sidebar">
        <div class="p-6">
            <img src="logo.png" alt="SDK Logo" class="w-12 h-12 mb-4">
            <h2 class="text-lg font-semibold mb-6 text-gray-900">SDK FRATERAN 2 KEDIRI</h2>
            <ul class="space-y-1">
                <li><a href="admin.php" class="block py-3 px-4 text-gray-700 hover:bg-gray-100 rounded-md transition">Dashboard</a></li>
                <li><a href="rekap_bulanan.php" class="block py-3 px-4 bg-gray-100 text-gray-900 rounded-md font-semibold">Rekap Bulanan</a></li>
                <li><a href="daftar_guru.php" class="block py-3 px-4 text-gray-700 hover:bg-gray-100 rounded-md transition">Daftar Guru</a></li>
                <li><a href="logout.php" class="block py-3 px-4 text-gray-700 hover:bg-gray-100 rounded-md transition">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <h3 class="text-2xl font-semibold mb-6 text-gray-900">Rekap Absensi Bulanan</h3>

        <!-- Tabel -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table id="tabelRekap" class="w-full text-sm text-gray-700">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama Guru</th>
                        <th class="px-4 py-3 text-left">Bulan</th>
                        <th class="px-4 py-3 text-center">Hadir</th>
                        <th class="px-4 py-3 text-center">Tidak Hadir</th>
                        <th class="px-4 py-3 text-center">Sakit</th>
                        <th class="px-4 py-3 text-center">Izin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (pg_num_rows($result) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($result)): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= namaBulan($row['bulan']) ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['hadir']) ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['tidak_hadir']) ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['sakit']) ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['izin']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-center text-gray-500">Tidak ada data rekap bulanan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tombol Print & Export -->
        <div class="flex gap-3 mt-6">
            <button onclick="window.print();" 
                class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition">
                🖨️ Print
            </button>

            <button onclick="exportExcel();" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition">
                📤 Export Excel
            </button>
        </div>
    </div>

    <!-- Script Export Excel -->
    <script>
        function exportExcel() {
            const table = document.getElementById("tabelRekap");
            const workbook = XLSX.utils.table_to_book(table, { sheet: "Rekap Bulanan" });
            XLSX.writeFile(workbook, "Rekap_Absensi_Bulanan.xlsx");
        }
    </script>
</body>
</html>
