<?php
session_start();
include("config.php");

//  Proteksi agar hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['login_user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Filter Periode
$periode = $_GET['periode'] ?? 'bulan';
$tahun   = $_GET['tahun'] ?? date('Y');
$bulan   = $_GET['bulan'] ?? date('m');
$minggu  = $_GET['minggu'] ?? date('W');

// Query Data Absensi
$query = "SELECT a.id AS absensi_id, d.nama AS nama_guru, d.NIP, a.status, a.waktu_absen, a.photo
          FROM absensi AS a
          INNER JOIN daftar AS d ON a.guru_id = d.id";

if ($periode == 'bulan') {
    $query .= " WHERE YEAR(a.waktu_absen) = '$tahun' AND MONTH(a.waktu_absen) = '$bulan'";
} elseif ($periode == 'minggu') {
    $query .= " WHERE YEARWEEK(a.waktu_absen, 1) = YEARWEEK('$tahun-$bulan-$minggu', 1)";
} elseif ($periode == 'tahun') {
    $query .= " WHERE YEAR(a.waktu_absen) = '$tahun'";
}

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search = mysqli_real_escape_string($conn, $_GET['q']);
    $query .= " AND (d.nama LIKE '%$search%' OR d.NIP LIKE '%$search%' OR a.status LIKE '%$search%')";
}

$result = mysqli_query($conn, $query);

// Query untuk Chart
$chart_query = "SELECT a.status, COUNT(*) as count FROM absensi AS a";
if ($periode == 'bulan') {
    $chart_query .= " WHERE YEAR(a.waktu_absen) = '$tahun' AND MONTH(a.waktu_absen) = '$bulan'";
} elseif ($periode == 'minggu') {
    $chart_query .= " WHERE YEARWEEK(a.waktu_absen, 1) = YEARWEEK('$tahun-$bulan-$minggu', 1)";
} elseif ($periode == 'tahun') {
    $chart_query .= " WHERE YEAR(a.waktu_absen) = '$tahun'";
}
$chart_query .= " GROUP BY a.status";
$chart_result = mysqli_query($conn, $chart_query);

$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_data[$row['status']] = $row['count'];
}

$hadir = $chart_data['hadir'] ?? 0;
$tidak_hadir = $chart_data['tidak hadir'] ?? 0;
$sakit = $chart_data['sakit'] ?? 0;
$izin = $chart_data['izin'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Saat print hanya tabel */
        @media print {
            body * { visibility: hidden; }
            #tabelAbsensi, #tabelAbsensi * { visibility: visible; }
            #tabelAbsensi { position: absolute; left: 0; top: 0; width: 100%; }
            thead {
                background: #000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="flex bg-gray-50 min-h-screen">

    <!-- Sidebar -->
    <div class="w-64 bg-cyan-400 text-gray-800 min-h-screen shadow-lg">
        <div class="p-6">
            <img src="logo.png" alt="SDK Logo" class="w-12 h-12 mb-4">
            <h2 class="text-lg font-semibold mb-6 text-gray-900">SDK FRANTERAN 2 KEDIRI</h2>
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
        <h3 class="text-2xl font-semibold mb-6 text-gray-900">Data Guru yang Sudah Absen</h3>

        <!-- Filter Periode -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm">
            <form method="get" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Periode:</label>
                    <select name="periode" class="border border-gray-300 rounded-md px-3 py-2" onchange="toggleInputs()">
                        <option value="bulan" <?= $periode == 'bulan' ? 'selected' : '' ?>>Bulan</option>
                        <option value="minggu" <?= $periode == 'minggu' ? 'selected' : '' ?>>Minggu</option>
                        <option value="tahun" <?= $periode == 'tahun' ? 'selected' : '' ?>>Tahun</option>
                    </select>
                </div>
                <div id="tahunInput">
                    <label class="block text-gray-700 font-medium mb-2">Tahun:</label>
                    <input type="number" name="tahun" value="<?= $tahun ?>" class="border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div id="bulanInput" style="display: <?= ($periode == 'bulan' || $periode == 'minggu') ? 'block' : 'none' ?>;">
                    <label class="block text-gray-700 font-medium mb-2">Bulan:</label>
                    <select name="bulan" class="border border-gray-300 rounded-md px-3 py-2">
                        <?php for ($i = 1; $i <= 12; $i++) { ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $bulan == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div id="mingguInput" style="display: <?= $periode == 'minggu' ? 'block' : 'none' ?>;">
                    <label class="block text-gray-700 font-medium mb-2">Minggu:</label>
                    <input type="number" name="minggu" min="1" max="53" value="<?= $minggu ?>" class="border border-gray-300 rounded-md px-3 py-2">
                </div>
                <button type="submit" class="bg-cyan-400     hover:bg-cyan-600 text-white px-4 py-2 rounded-md transition">Filter</button>
            </form>
        </div>

        <!-- Diagram Chart -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm">
            <h4 class="text-lg font-semibold mb-4">Diagram Absensi</h4>
            <div class="flex justify-center">
                <div class="relative" style="width: 100%; max-width: 800px; height: 500px;">
                    <canvas id="absensiChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pencarian -->
        <div class="mb-6 flex">
            <input type="text" class="border border-gray-300 rounded-l-md px-4 py-2 flex-1" id="searchInput" placeholder="Cari Guru" value="<?= $_GET['q'] ?? '' ?>">
            <button class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-r-md" id="searchButton">Cari</button>
        </div>

        <!-- Tabel Data -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table id="tabelAbsensi" class="w-full">
                <thead class="bg-cyan-400 text-white">
                    <tr>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Waktu Absen</th>
                        <th class="px-4 py-3">Foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['NIP']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['status']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['waktu_absen']) ?></td>
                                <td class="px-4 py-3">
                                    <a href="#" onclick="openPhoto('<?= htmlspecialchars($row['photo']) ?>')" class="text-cyan-600 hover:underline">Lihat</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-gray-500 py-3">Tidak ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tombol Print dan Export -->
        <div class="flex gap-3 mt-6">
            <button onclick="window.print()" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">Print</button>
            <button onclick="exportExcel()" class="bg-cyan-400 hover:bg-cyan-600 text-white px-4 py-2 rounded-md">Export Excel</button>
        </div>
    </div>

    <!-- Modal Foto -->
    <div id="photoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-4 border-b">
                <h5 class="text-lg font-semibold">Foto Guru</h5>
                <button onclick="closePhoto()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <div class="p-4">
                <img id="modalPhoto" src="" alt="Foto Guru" class="w-full h-auto rounded-md">
            </div>
        </div>
    </div>

    <script>
        // Cari data
        document.getElementById("searchButton").addEventListener("click", function () {
            const q = document.getElementById("searchInput").value;
            const url = new URL(window.location);
            url.searchParams.set('q', q);
            window.location.href = url;
        });

        // Modal Foto
        function openPhoto(photoSrc) {
            document.getElementById('modalPhoto').src = photoSrc;
            document.getElementById('photoModal').classList.remove('hidden');
        }
        function closePhoto() {
            document.getElementById('photoModal').classList.add('hidden');
        }
        document.getElementById('photoModal').addEventListener('click', e => { if (e.target === e.currentTarget) closePhoto(); });

        // Chart.js
        const ctx = document.getElementById('absensiChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Tidak Hadir', 'Sakit', 'Izin'],
                datasets: [{
                    label: 'Jumlah Kehadiran',
                    data: [<?= $hadir ?>, <?= $tidak_hadir ?>, <?= $sakit ?>, <?= $izin ?>],
                    backgroundColor: ['#10B981', '#EF4444', '#F59E0B', '#3B82F6']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Export Excel
        function exportExcel() {
            const table = document.getElementById("tabelAbsensi");
            const wb = XLSX.utils.table_to_book(table, { sheet: "Rekap Absensi" });
            XLSX.writeFile(wb, "Rekap_Absensi_Guru.xlsx");
        }
    </script>
</body>
</html>
