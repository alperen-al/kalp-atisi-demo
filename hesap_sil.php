<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_POST['confirm_delete'] != 1) {
    header("Location: profil.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Kullanıcıya bağlı tüm verileri sil (istersen buraları özelleştir)
$conn->query("DELETE FROM withdrawals WHERE user_id = $user_id");
$conn->query("DELETE FROM referrals WHERE referrer_user_id = $user_id");
$conn->query("DELETE FROM certificates WHERE user_id = $user_id");
$conn->query("DELETE FROM purchases WHERE user_id = $user_id");
$conn->query("DELETE FROM messages WHERE purchase_id IN (SELECT purchase_id FROM purchases WHERE user_id = $user_id)");
$conn->query("DELETE FROM users WHERE user_id = $user_id");

// 2. Oturumu bitir
session_destroy();

// 3. Ana sayfaya yönlendir
header("Location: login.php");
exit;
