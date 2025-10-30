<?php
// === KONFIGURASI SUPABASE ===
$host = "db.uudchlikynbrhchuisar.supabase.co";
$port = "5432";
$database = "postgres";
$user = "postgres";
$password = "Notenvy311";

// === KONEKSI MENGGUNAKAN pg_connect ===
$conn = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");

if (!$conn) {
    die("❌ Koneksi ke database Supabase gagal: " . pg_last_error());
} else {
    // echo "✅ Koneksi berhasil ke Supabase PostgreSQL!";
}
?>
