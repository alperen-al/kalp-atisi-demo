<?php
session_start();
include '../db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

// Sadece admin erişebilir
$stmt = $conn->prepare("SELECT account_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($type);
$stmt->fetch();
$stmt->close();

if ($type !== 'admin') {
  echo "Bu sayfaya sadece admin erişebilir.";
  exit;
}

// İstatistik verileri
$totalSales        = $conn->query("SELECT COUNT(*) FROM purchases")->fetch_row()[0];
$totalEarnings     = $conn->query("SELECT IFNULL(SUM(price),0) FROM purchases")->fetch_row()[0];
$totalUsers        = $conn->query("SELECT COUNT(*) FROM users WHERE account_type != 'admin'")->fetch_row()[0];
$totalIndiv        = $conn->query("SELECT COUNT(*) FROM users WHERE account_type = 'bireysel'")->fetch_row()[0];
$totalCorp         = $conn->query("SELECT COUNT(*) FROM users WHERE account_type = 'sirket'")->fetch_row()[0];

$pendingMails      = $conn->query("SELECT COUNT(*) FROM messages WHERE send_status = 0")->fetch_row()[0];
$sentMails         = $conn->query("SELECT COUNT(*) FROM messages WHERE send_status = 1")->fetch_row()[0];

// Kadın ve erkek kullanıcıların yaptığı satışlar
$kadinSales = $conn->query("
  SELECT COUNT(*) 
  FROM purchases p
  JOIN users u ON p.user_id = u.user_id
  WHERE u.gender = 'Kadın'
")->fetch_row()[0];

$erkekSales = $conn->query("
  SELECT COUNT(*) 
  FROM purchases p
  JOIN users u ON p.user_id = u.user_id
  WHERE u.gender = 'Erkek'
")->fetch_row()[0];

// Grafik için veri (satışları tarihe göre grupla)
$salesData = [];
$res = $conn->query("SELECT DATE(purchase_date) as date, SUM(price) as total FROM purchases GROUP BY DATE(purchase_date) ORDER BY date ASC");
while ($row = $res->fetch_assoc()) {
  $salesData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Yönetim Paneli</title>
  <style>
    body {
      margin: 0;
      font-family: Arial;
      background-color: #f2f2f2;
      display: flex;
    }
    .sidebar {
      width: 220px;
      background: #343a40;
      height: 100vh;
      color: white;
      padding-top: 30px;
    }
    .sidebar a {
      display: block;
      color: white;
      padding: 14px 20px;
      text-decoration: none;
      border-bottom: 1px solid #495057;
    }
    .sidebar a:hover {
      background-color: #495057;
    }
    .content {
      flex-grow: 1;
      padding: 30px;
    }
    h2 {
      color: #d61818;
    }
    .cards {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }
    .card {
      flex: 1 1 250px;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: #333;
    }
    .card .value {
      font-size: 24px;
      font-weight: bold;
      color: #d61818;
    }
    canvas {
      margin-top: 30px;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    .db-link {
      display: block;
      margin-top: 30px;
      padding: 14px;
      background: darkred;
      color: white;
      text-align: center;
      font-weight: bold;
      border-radius: 8px;
      text-decoration: none;
    }
    .db-link:hover {
      background: crimson;
    }
    .logout-button {
      position: absolute;
      top: 20px;
      right: 30px;
    }
    .logout-button a {
      background-color: #d61818;
      color: white;
      padding: 10px 15px;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
    .logout-button a:hover {
      background-color: #a00;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="logout-button">
  <a href="../logout.php">Çıkış Yap</a>
</div>

<div class="sidebar">
  <h2 style="text-align:center;">ADMİN PANEL</h2>
  <a href="adminpanel.php">Panelim</a>
  <a href="kullanicilar.php">Kullanıcılar</a>
  <a href="sirketler.php">Şirketler</a>
  <a href="sertifikalar.php">Sertifikalar</a>
  <a href="mesajlar.php">Mesajlar</a>
  <a href="iletisim.php">İletişim</a>
  <a href="cekim_talepleri.php">Çekim Talepleri</a>
  <a href="destek.php">Destek</a>
  <a class="db-link" href="http://localhost/phpmyadmin" target="_blank">ANA DATABASE SİSTEMİ</a>
</div>

<div class="content">
  <h2>Yönetim Paneli</h2>

  <div class="cards">
    <div class="card"><h3>Toplam Satış</h3><div class="value"><?= $totalSales ?></div></div>
    <div class="card"><h3>Toplam Kazanç</h3><div class="value"><?= number_format($totalEarnings, 2) ?> $</div></div>
    <div class="card"><h3>Toplam Üye</h3><div class="value"><?= $totalUsers ?></div></div>
    <div class="card"><h3>Bireysel Üye</h3><div class="value"><?= $totalIndiv ?></div></div>
    <div class="card"><h3>Kurumsal Üye</h3><div class="value"><?= $totalCorp ?></div></div>
    <div class="card"><h3>Bekleyen Mailler</h3><div class="value"><?= $pendingMails ?></div></div>
    <div class="card"><h3>Gönderilen Mailler</h3><div class="value"><?= $sentMails ?></div></div>
    <div class="card"><h3>Kadın Kullanıcı Satışları</h3><div class="value"><?= $kadinSales ?></div></div>
    <div class="card"><h3>Erkek Kullanıcı Satışları</h3><div class="value"><?= $erkekSales ?></div></div>
  </div>

  <canvas id="earningsChart" width="800" height="300"></canvas>
</div>

<script>
  const ctx = document.getElementById('earningsChart').getContext('2d');
  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($salesData, 'date')) ?>,
      datasets: [{
        label: 'Kazanç (₺)',
        data: <?= json_encode(array_map('floatval', array_column($salesData, 'total'))) ?>,
        fill: true,
        borderColor: 'crimson',
        backgroundColor: 'rgba(220, 53, 69, 0.2)',
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>

</body>
</html>
