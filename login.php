<?php
session_start();
include("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek apakah yang login adalah admin
    $admin_query = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $admin_result = mysqli_query($conn, $admin_query);
    $admin_count = mysqli_num_rows($admin_result);

    if ($admin_count == 1) {
        $_SESSION['login_user'] = $username;
        $_SESSION['admin'] = true; // Menandai bahwa yang login adalah admin
        header("location: admin.php");
    } else {
        // Jika bukan admin, cek apakah guru yang login
        $guru_query = "SELECT * FROM guru WHERE username='$username' AND password='$password'";
        $guru_result = mysqli_query($conn, $guru_query);
        $guru_count = mysqli_num_rows($guru_result);

        if ($guru_count == 1) {
            $row = mysqli_fetch_assoc($guru_result);
            $_SESSION['login_user'] = $username;
            $_SESSION['guru_id'] = $row['id'];
            header("location: guru.php");
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex justify-center items-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md border border-cyan-300">

        <div class="text-center mb-6">
            <img src="logo.png" alt="Logo Instansi" class="h-24 mx-auto mb-4">
            <h2 class="text-2xl font-bold">SDK FRATERAN 2 KEDIRI</h2>
        </div>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Username:</label>
                <input type="text" name="username" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Password:</label>
                <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <input type="submit" value="Login" class="w-full bg-cyan-400 hover:bg-cyan-600 text-white font-bold py-2 px-4 rounded">
            </div>
        </form>
        <?php if (isset($error)): ?>
            <p class="mt-4 text-red-500 text-center"><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</body>

</html>
