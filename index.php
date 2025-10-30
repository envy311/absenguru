<?php
session_start();

// Fungsi untuk mengecek apakah pengguna sudah login sebagai admin
function isAdminLoggedIn() {
    return isset($_SESSION['login_user']) && isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// Fungsi untuk mengecek apakah pengguna sudah login sebagai guru
function isGuruLoggedIn() {
    return isset($_SESSION['login_user']) && isset($_SESSION['guru_id']);
}

// Jika admin sudah login, arahkan ke halaman admin.php
if (isAdminLoggedIn()) {
    header("location: admin.php");
    exit();
}

// Jika guru sudah login, arahkan ke halaman guru.php
if (isGuruLoggedIn()) {
    header("location: guru.php");
    exit();
}

// Jika belum ada yang login, arahkan ke halaman login.php
header("location: login.php");
?>
