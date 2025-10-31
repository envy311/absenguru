<?php
session_start();
include("config.php");

// Periksa apakah yang login adalah admin
if (!isset($_SESSION['login_user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "DELETE FROM daftar WHERE id = $id";
    mysqli_query($conn, $query);
}

// Setelah menghapus data, Anda bisa mengarahkan pengguna kembali ke halaman admin
header("location: daftar_guru.php");
?>
