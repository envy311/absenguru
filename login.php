<?php
session_start();
include("config.php"); // Pastikan config.php pakai pg_connect()

$error = "";

// Jika sudah login sebelumnya, langsung arahkan
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit();
    } elseif ($_SESSION['role'] === 'guru') {
        header("Location: guru.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = pg_escape_string($conn, $_POST['username']);
    $password = pg_escape_string($conn, $_POST['password']);

    // 🔹 Cek apakah yang login admin
    $admin_query = "SELECT * FROM admin WHERE username='$username' AND password='$password' LIMIT 1";
    $admin_result = pg_query($conn, $admin_query);

    if ($admin_result && pg_num_rows($admin_result) === 1) {
        $_SESSION['login_user'] = $username;
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    }

    // 🔹 Jika bukan admin, cek apakah guru
    $guru_query = "SELECT * FROM guru WHERE username='$username' AND password='$password' LIMIT 1";
    $guru_result = pg_query($conn, $guru_query);

    if ($guru_result && pg_num_rows($guru_result) === 1) {
        $row = pg_fetch_assoc($guru_result);
        $_SESSION['login_user'] = $row['username'];
        $_SESSION['guru_id'] = $row['id'];
        $_SESSION['role'] = 'guru';
        $_SESSION['guru_logged_in'] = true;
        header("Location: guru.php");
        exit();
    }

    // 🔸 Jika gagal login
    $error = "Username atau password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - SDK FRATERAN 2 KEDIRI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex justify-center items-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md border border-cyan-300">
        <!-- Logo dan teks -->
        <div class="text-center mb-6">
            <img src="logo.png" alt="Logo Instansi" class="h-24 mx-auto mb-4">
            <h2 class="text-2xl font-bold">SDK FRATERAN 2 KEDIRI</h2>
        </div>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Username:</label>
                <input type="text" name="username" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Password:</label>
                <input type="password" name="password" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <input type="submit" value="Login" 
                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
            </div>
        </form>

        <?php if ($error): ?>
            <p class="mt-4 text-red-500 text-center"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
