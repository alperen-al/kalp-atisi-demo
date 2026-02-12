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

// Filtre parametrelerini al
$adFilter = $_GET['ad'] ?? '';
$genderFilter = $_GET['cinsiyet'] ?? '';
$startDate = $_GET['tarih1'] ?? '';
$endDate = $_GET['tarih2'] ?? '';

// SQL Sorgusu: filtrelerle birlikte
$sql = "SELECT m.*, u.full_name, u.email, u.profile_photo, u.gender
        FROM messages m
        JOIN purchases p ON m.purchase_id = p.purchase_id
        JOIN users u ON p.user_id = u.user_id
        WHERE 1=1";

if (!empty($adFilter)) {
    $adFilter = $conn->real_escape_string($adFilter);
    $sql .= " AND u.full_name LIKE '%$adFilter%'";
}

if (!empty($genderFilter)) {
    $genderFilter = $conn->real_escape_string($genderFilter);
    $sql .= " AND u.gender = '$genderFilter'";
}

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND DATE(m.send_date) BETWEEN '$startDate' AND '$endDate'";
}

$result = $conn->query($sql);
$mesajlar = [];
while ($row = $result->fetch_assoc()) {
    $mesajlar[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mesajlar - Admin Panel</title>
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

        .filter-form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .filter-form input, .filter-form select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .filter-form button {
            padding: 8px 12px;
            border: none;
            background-color: #1e3a5f;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #2c5282;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="text-align:center;">ADMİN PANEL</h2>
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
        <h2>Tüm Mesajlar</h2>

        <form method="GET" class="filter-form">
            <input type="text" name="ad" placeholder="İsme göre filtrele" value="<?= htmlspecialchars($_GET['ad'] ?? '') ?>">
            <select name="cinsiyet">
                <option value="">Cinsiyet Seç</option>
                <option value="Erkek" <?= (($_GET['cinsiyet'] ?? '') === 'Erkek') ? 'selected' : '' ?>>Erkek</option>
                <option value="Kadın" <?= (($_GET['cinsiyet'] ?? '') === 'Kadın') ? 'selected' : '' ?>>Kadın</option>
            </select>
            <input type="date" name="tarih1" value="<?= htmlspecialchars($_GET['tarih1'] ?? '') ?>">
            <input type="date" name="tarih2" value="<?= htmlspecialchars($_GET['tarih2'] ?? '') ?>">
            <button type="submit">Filtrele</button>
        </form>

        <?php foreach ($mesajlar as $mesaj): ?>
            <div class="user-card">
                <div class="user-info">
                    <?php
                        $photo = $mesaj['profile_photo'];
                        $photoPath = $photo ? "../uploads/{$photo}" : "../uploads/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" class="user-photo" alt="Profil Fotoğrafı">
                    <div class="user-details">
                        <strong>Ad Soyad:</strong> <?= htmlspecialchars($mesaj['full_name']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($mesaj['email']) ?><br>
                        <strong>Cinsiyet:</strong> <?= htmlspecialchars($mesaj['gender']) ?><br>
                        <strong>Mesaj:</strong> <?= htmlspecialchars($mesaj['message_text']) ?><br>
                        <strong>Gönderim Tarihi:</strong> <?= htmlspecialchars($mesaj['send_date']) ?><br>
                        <strong>Durum:</strong> <?= htmlspecialchars($mesaj['send_status']) ?>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="ayarlar/mesaj_guncelle.php?id=<?= $mesaj['message_id'] ?>" title="Ayarlar">⚙️</a>
                    <a href="#" onclick="confirmDelete(<?= $mesaj['message_id'] ?>)" title="Sil">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function confirmDelete(id) {
        if (confirm("Bu mesajı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.")) {
            window.location.href = "ayarlar/mesaj_sil.php?id=" + id;
        }
    }
    </script>
</body>
</html>
