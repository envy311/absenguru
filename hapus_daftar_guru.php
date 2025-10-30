<?php
session_start();
include("config.php");

// 🔒 Proteksi agar hanya admin yang bisa menghapus
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 🗑️ Hapus data guru berdasarkan ID (gunakan query aman)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Gunakan pg_query_params agar aman dari SQL Injection
    $query = "DELETE FROM daftar WHERE id = $1";
    $result = pg_query_params($conn, $query, [$id]);

    if (!$result) {
        die("Gagal menghapus data: " . pg_last_error($conn));
    }
}

// ✅ Arahkan kembali ke halaman daftar guru
header("Location: daftar_guru.php");
exit();
?>
