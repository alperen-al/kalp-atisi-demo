<?php
session_start();
include '../../db.php';

// Admin girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// ID kontrolü
if (!isset($_GET['user_id'])) {
    die('Geçersiz kullanıcı ID');
}

$user_id = (int)$_GET['user_id'];

// Önce kullanıcıya bağlı şirketi de silelim (eğer varsa)
$conn->query("DELETE FROM companies WHERE user_id = $user_id");

// Mesajları vs. de silmek istersen ekle: (opsiyonel)
// $conn->query("DELETE FROM messages WHERE user_id = $user_id");

$conn->query("DELETE FROM users WHERE user_id = $user_id");

header('Location: ../kullanicilar.php');
exit;
