<?php
session_start();
if (!isset($_SESSION['login_user'])) {
    header("location: login.php");
    exit;
}

include("config.php");
date_default_timezone_set('Asia/Jakarta');
mysqli_query($conn, "SET time_zone = '+07:00'");

$guru_id = $_SESSION['guru_id'];
$current_date = date('Y-m-d');
$message = "";
$error = "";

// Cek apakah guru sudah absen hari ini
$check_query = "SELECT * FROM absensi WHERE guru_id='$guru_id' AND DATE(waktu_absen)='$current_date'";
$check_result = mysqli_query($conn, $check_query);
$already_absent = mysqli_num_rows($check_result) > 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$already_absent) {
    $status = $_POST['status'];
    $photo_data = $_POST['photo_data'];

    if (empty($photo_data)) {
        $error = "Harap ambil foto (capture) terlebih dahulu sebelum absen.";
    } else {
        $waktu_absen = date('Y-m-d H:i:s');
        $decoded_photo = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo_data));
        $photo_filename = "uploads/photo_" . time() . ".png";
        file_put_contents($photo_filename, $decoded_photo);

        $insert_query = "INSERT INTO absensi (guru_id, status, waktu_absen, photo) 
                         VALUES ('$guru_id', '$status', '$waktu_absen', '$photo_filename')";
        if (mysqli_query($conn, $insert_query)) {
            $message = "Absensi berhasil dilakukan.";
            $already_absent = true;
        } else {
            $error = "Gagal melakukan absensi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Absensi Guru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
                <li>
                    <a href="biodata.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Biodata</a>
                </li>
                <li>
                    <a href="guru.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Absen</a>
                </li>
                <li>
                    <a href="logout.php" class="block py-3 px-4 bg-cyan-400 hover:bg-cyan-500 text-gray-900 font-medium rounded-lg">Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <p class="text-xl font-medium text-gray-900 mb-6">Selamat datang <?php echo $_SESSION['login_user']; ?>!</p>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h3 class="text-2xl font-semibold mb-6 text-gray-900">Absensi Guru</h3>

            <?php if ($already_absent): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-md">
                    Anda sudah melakukan absensi hari ini (<?php echo $current_date; ?>).
                </div>
            <?php else: ?>
                <form method="post" id="absenForm">
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-3">Status:</label>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="radio" name="status" value="hadir" required class="mr-3"> Hadir
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="status" value="tidak hadir" class="mr-3"> Tidak Hadir
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="status" value="izin" class="mr-3"> Izin
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="status" value="sakit" class="mr-3"> Sakit
                            </label>
                        </div>
                    </div>

                    <p class="mb-6 text-gray-700">Waktu: <span id="real-time" class="font-mono text-gray-900"></span></p>

                    <video id="video" width="400" height="300"
                        class="border border-gray-300 mb-4 rounded-md bg-gray-100"></video><br>
                    <button type="button" id="capture"
                        class="bg-cyan-500 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-md mb-4 transition">Capture</button>

                    <input type="hidden" id="photo_data" name="photo_data" value="">

                    <button type="submit" id="submitBtn"
                        class="bg-cyan-500 hover:bg-cyan-700 text-white font-medium py-2 px-4 rounded-md transition"
                        disabled>Absen</button>
                </form>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md mt-6'>
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class='bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md mt-6'>
                    <?= $error; ?>  
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        //  Update waktu real-time
        function updateRealTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            document.getElementById('real-time').textContent = timeString;
        }
        setInterval(updateRealTime, 1000);
        updateRealTime();

        //  Akses kamera
        const video = document.getElementById('video');
        const captureBtn = document.getElementById('capture');
        const photoInput = document.getElementById('photo_data');
        const submitBtn = document.getElementById('submitBtn');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                video.play();
            })
            .catch(err => {
                alert("Gagal mengakses kamera. Pastikan izin kamera diaktifkan.");
            });

        //  Tombol Capture
        captureBtn.addEventListener('click', function() {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const dataURL = canvas.toDataURL('image/png');
            photoInput.value = dataURL;

            // Aktifkan tombol submit setelah capture
            submitBtn.disabled = false;
            alert("Foto berhasil diambil, silakan tekan tombol Absen.");
        });
    </script>
</body>
</html>
