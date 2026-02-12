<?php
session_start();
include '../db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

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

// Satın alımları kullanıcı bilgileri ile birlikte çek
$purchases = [];
$sql = "SELECT p.*, u.full_name, u.email, u.profile_photo, u.account_type 
        FROM purchases p
        JOIN users u ON p.user_id = u.user_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $purchases[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Satın Alımlar - Admin Panel</title>
    <link rel="stylesheet" href="../tr/profil.css">
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
        }
        .sidebar {
            width: 200px;
            background-color: #1e3a5f;
            color: white;
            height: 100vh;
            padding: 20px 10px;
        }
        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 8px 10px;
            margin: 5px 0;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #2c5282;
        }
        .sidebar .db-link {
            background-color: red;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
            display: block;
            padding: 10px;
        }
        .content {
            flex-grow: 1;
            padding: 20px;
        }
        .card {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .info {
            display: flex;
            align-items: center;
        }
        .photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #ccc;
        }
        .details {
            font-size: 15px;
        }
        .actions {
            text-align: right;
        }
        .actions a {
            display: inline-block;
            margin-left: 10px;
            font-size: 18px;
            text-decoration: none;
            color: #555;
        }
        .actions a:hover {
            color: #d61818;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>ADMİN PANEL</h2>
        <a href="adminpanel.php">Panelim</a>
        <a href="kullanicilar.php">Kullanıcılar</a>
        <a href="sirketler.php">Şirketler</a>
        <a href="sertifikalar.php">Sertifikalar</a>
        <a href="mesajlar.php">Mesajlar</a>
        <a href="satin_alimlar.php">Satın Alımlar</a>
        <a href="cekim_talepleri.php">Çekim Talepleri</a>
        <a href="destek.php">Destek</a>
        <a class="db-link" href="http://localhost/phpmyadmin" target="_blank">ANA DATABASE SİSTEMİ</a>
    </div>

    <div class="content">
        <h2>Tüm Satın Alımlar</h2>

        <?php foreach ($purchases as $item): ?>
            <div class="card">
                <div class="info">
                    <?php
                        $photo = $item['profile_photo'];
                        $photoPath = $photo ? "../uploads/{$photo}" : "../uploads/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" class="photo" alt="Profil Fotoğrafı">
                    <div class="details">
                        <strong>Ad Soyad:</strong> <?= htmlspecialchars($item['full_name']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($item['email']) ?><br>
                        <strong>Hesap Türü:</strong> <?= htmlspecialchars($item['account_type']) ?><br>
                        <strong>Satın Alma Tarihi:</strong> <?= htmlspecialchars($item['purchase_date']) ?><br>
                        <strong>Fiyat:</strong> $<?= htmlspecialchars($item['price']) ?><br>
                        <strong>Durum:</strong> <?= htmlspecialchars($item['status']) ?><br>
                        <strong>Referans Kodu:</strong> <?= htmlspecialchars($item['ref_code_used']) ?><br>
                        <strong>Konum:</strong> <?= $item['latitude'] ?>, <?= $item['longitude'] ?>
                    </div>
                </div>
                <div class="actions">
                    <a href="#" onclick="confirmDelete(<?= $item['purchase_id'] ?>)" title="Sil">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function confirmDelete(id) {
        if (confirm("Bu satın alımı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.")) {
            window.location.href = "ayarlar/satin_alim_sil.php?purchase_id=" + id;
        }
    }
    </script>
</body>
</html>
