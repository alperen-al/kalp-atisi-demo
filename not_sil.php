<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id']) && isset($_POST['sil'])) {
  $message_id = $_POST['message_id'];
  $sil_tipi = $_POST['sil'];

  if ($sil_tipi === "benden") {
    // BENDEN SİL: şimdilik hiçbir şey silmeden geri dön
    // İleri düzeyde messages tablosuna "gizli" alanı ekleyip filtreleyebiliriz
    header("Location: arkadaslar.php");
    exit();
  }

  if ($sil_tipi === "iptal") {
    // GÖNDERİMİ İPTAL ET: tamamen sil
    $stmt = $conn->prepare("SELECT purchase_id FROM messages WHERE message_id = ? LIMIT 1");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->bind_result($purchase_id);
    $stmt->fetch();
    $stmt->close();

    if ($purchase_id) {
      $stmt1 = $conn->prepare("DELETE FROM messages WHERE message_id = ?");
      $stmt1->bind_param("i", $message_id);
      $stmt1->execute();

      $stmt2 = $conn->prepare("DELETE FROM purchases WHERE purchase_id = ?");
      $stmt2->bind_param("i", $purchase_id);
      $stmt2->execute();
    }

    header("Location: arkadaslar.php");
    exit();
  }
}

header("Location: arkadaslar.php");
exit();
?>
