<?php
session_start();
include '../db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Admin değilse erişimi engelle
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

// Sertifikaları kullanıcı bilgileri ile birlikte al
$certificates = [];
$sql = "SELECT c.*, u.full_name, u.username, u.profile_photo
        FROM certificates c
        JOIN users u ON c.user_id = u.user_id
        ORDER BY c.upload_date DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $certificates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sertifikalar - Admin Panel</title>
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
            color: white;
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
        .user-card {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #ccc;
        }
        .user-details {
            font-size: 15px;
        }
        .user-actions {
            text-align: right;
        }
        .user-actions a {
            display: inline-block;
            margin-left: 10px;
            font-size: 18px;
            text-decoration: none;
            color: #555;
        }
        .user-actions a:hover {
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
        <h2>Tüm Sertifikalar</h2>

        <?php foreach ($certificates as $cert): ?>
            <div class="user-card">
                <div class="user-info">
                    <?php
                        $photo = $cert['profile_photo'];
                        $photoPath = $photo ? "../uploads/{$photo}" : "../uploads/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" class="user-photo" alt="Profil Fotoğrafı">
                    <div class="user-details">
                        <strong>Ad Soyad:</strong> <?= htmlspecialchars($cert['full_name']) ?><br>
                        <strong>Kullanıcı Adı:</strong> <?= htmlspecialchars($cert['username']) ?><br>
                        <strong>Sertifika Başlığı:</strong> <?= htmlspecialchars($cert['cert_title']) ?><br>
                        <strong>Yüklenme Tarihi:</strong> <?= htmlspecialchars($cert['upload_date']) ?><br>
                        <a href="../certificates/<?= htmlspecialchars($cert['cert_file']) ?>" target="_blank">📄 Sertifikayı Görüntüle</a>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="ayarlar/sertifika_sil.php?cert_id=<?= $cert['cert_id'] ?>" title="Sil" onclick="return confirm('Bu sertifikayı silmek istediğinize emin misiniz?');">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
