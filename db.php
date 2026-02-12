<?php
$servername = "localhost"; // Veritabanı sunucusunun adresini tanımlar (genellikle localhost)
$username   = "root";      // Veritabanı kullanıcı adını tanımlar
$password   = "";          // Veritabanı parolasını tanımlar (boş bırakılmış)
$dbname     = "kalp_atisi"; // Bağlanılacak veritabanı adını tanımlar

// Yeni bir MySQLi bağlantı nesnesi oluşturur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantı hatası varsa kontrol eder
if ($conn->connect_error) {
    // Hata varsa betiği sonlandırır ve hata mesajını gösterir
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}
?>

