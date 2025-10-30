<?php
session_start();
include("config.php");

// 🔒 Proteksi agar hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Filter Periode
$periode = $_GET['periode'] ?? 'bulan';
$tahun   = $_GET['tahun'] ?? date('Y');
$bulan   = $_GET['bulan'] ?? date('m');
$minggu  = $_GET['minggu'] ?? date('W');
$search  = $_GET['q'] ?? '';

// ===========================
// Query Data Absensi
// ===========================
$query = "
    SELECT a.id AS absensi_id, d.nama AS nama_guru, d.nip, a.status, a.waktu_absen, a.photo
    FROM absensi AS a
    INNER JOIN daftar AS d ON a.guru_id = d.id
";

$conditions = [];
$params = [];

// Filter berdasarkan periode
if ($periode == 'bulan') {
    $conditions[] = "EXTRACT(YEAR FROM a.waktu_absen) = $1 AND EXTRACT(MONTH FROM a.waktu_absen) = $2";
    $params = [$tahun, $bulan];
} elseif ($periode == 'minggu') {
    $conditions[] = "EXTRACT(YEAR FROM a.waktu_absen) = $1 AND EXTRACT(WEEK FROM a.waktu_absen) = $2";
    $params = [$tahun, $minggu];
} elseif ($periode == 'tahun') {
    $conditions[] = "EXTRACT(YEAR FROM a.waktu_absen) = $1";
    $params = [$tahun];
}

// Filter pencarian
if (!empty($search)) {
    $searchParam = "%" . $search . "%";
    $conditions[] = "(d.nama ILIKE $" . (count($params) + 1) . " OR d.nip ILIKE $" . (count($params) + 2) . " OR a.status ILIKE $" . (count($params) + 3) . ")";
    array_push($params, $searchParam, $searchParam, $searchParam);
}

// Gabungkan semua kondisi
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Urutkan dari ID absensi terkecil
$query .= " ORDER BY a.id ASC";

$result = pg_query_params($conn, $query, $params);

// ===========================
// Query untuk Chart
// ===========================
$chart_query = "SELECT a.status, COUNT(*) AS count FROM absensi AS a";
$chart_conditions = [];
$chart_params = [];

if ($periode == 'bulan') {
    $chart_conditions[] = "EXTRACT(YEAR FROM a.waktu_absen) = $1 AND EXTRACT(MONTH FROM a.waktu_absen) = $2";
    $chart_params = [$tahun, $bulan];
} elseif ($periode == 'minggu') {
    $chart_conditions[] = "EXTRACT(YEAR FROM a.waktu_absen) = $1 AND EXTRACT(WEEK FROM a.waktu_absen) = $2";
    $chart_params = [$tahun, $minggu];
} elseif ($periode == 'tahun') {
    $chart_conditions[] = "EXTRACT(YEAR FROM a.waktu_absen) = $1";
    $chart_params = [$tahun];
}

if ($chart_conditions) {
    $chart_query .= " WHERE " . implode(" AND ", $chart_conditions);
}
$chart_query .= " GROUP BY a.status";

$chart_result = pg_query_params($conn, $chart_query, $chart_params);

$chart_data = [];
while ($row = pg_fetch_assoc($chart_result)) {
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
    <div class="w-64 bg-white text-gray-800 min-h-screen shadow-lg">
        <div class="p-6">
            <img src="logo.png" alt="SDK Logo" class="w-12 h-12 mb-4">
            <h2 class="text-lg font-semibold mb-6 text-gray-900">SDK FRATERAN 2 KEDIRI</h2>
            <ul class="space-y-1">
                <li><a href="admin.php" class="block py-3 px-4 bg-gray-100 text-gray-900 font-medium rounded-md">Dashboard</a></li>
                <li><a href="rekap_bulanan.php" class="block py-3 px-4 text-gray-700 hover:bg-gray-100 rounded-md">Rekap Bulanan</a></li>
                <li><a href="daftar_guru.php" class="block py-3 px-4 text-gray-700 hover:bg-gray-100 rounded-md">Daftar Guru</a></li>
                <li><a href="logout.php" class="block py-3 px-4 text-gray-700 hover:bg-gray-100 rounded-md">Logout</a></li>
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
                    <input type="number" name="tahun" value="<?= htmlspecialchars($tahun) ?>" class="border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div id="bulanInput" style="display: <?= ($periode == 'bulan' || $periode == 'minggu') ? 'block' : 'none' ?>;">
                    <label class="block text-gray-700 font-medium mb-2">Bulan:</label>
                    <select name="bulan" class="border border-gray-300 rounded-md px-3 py-2">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $bulan == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div id="mingguInput" style="display: <?= $periode == 'minggu' ? 'block' : 'none' ?>;">
                    <label class="block text-gray-700 font-medium mb-2">Minggu:</label>
                    <input type="number" name="minggu" min="1" max="53" value="<?= htmlspecialchars($minggu) ?>" class="border border-gray-300 rounded-md px-3 py-2">
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition">Filter</button>
            </form>
        </div>

        <!-- Chart -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm">
            <h4 class="text-lg font-semibold mb-4">Diagram Absensi</h4>
            <div class="flex justify-center">
                <canvas id="absensiChart" style="max-width: 800px; height: 400px;"></canvas>
            </div>
        </div>

        <!-- Pencarian -->
        <div class="mb-6 flex">
            <input type="text" class="border border-gray-300 rounded-l-md px-4 py-2 flex-1" id="searchInput" placeholder="Cari Guru" value="<?= htmlspecialchars($search) ?>">
            <button class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-r-md" id="searchButton">Cari</button>
        </div>

        <!-- Tabel Data -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table id="tabelAbsensi" class="w-full">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Waktu Absen</th>
                        <th class="px-4 py-3">Foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (pg_num_rows($result) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($result)): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_guru']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nip']) ?></td>
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

        <!-- Tombol Print & Export -->
        <div class="flex gap-3 mt-6">
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-md">🖨️ Print</button>
            <button onclick="exportExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">📤 Export Excel</button>
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
        // Cari
        document.getElementById("searchButton").addEventListener("click", () => {
            const q = document.getElementById("searchInput").value;
            const url = new URL(window.location);
            url.searchParams.set('q', q);
            window.location.href = url;
        });

        // Modal Foto
        function openPhoto(src) {
            document.getElementById("modalPhoto").src = src;
            document.getElementById("photoModal").classList.remove("hidden");
        }
        function closePhoto() {
            document.getElementById("photoModal").classList.add("hidden");
        }

        // Chart.js
        new Chart(document.getElementById('absensiChart'), {
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
