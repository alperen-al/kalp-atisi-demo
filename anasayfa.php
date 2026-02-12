<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Ana Sayfa</title>
</head>
<body>
  <h2>Hoş geldin, <?php echo $_SESSION['username']; ?>!</h2>
  <p>Hesap tipi: <?php echo $_SESSION['account_type']; ?></p>
  <a href="logout.php">Çıkış Yap</a>
</body>
</html>
