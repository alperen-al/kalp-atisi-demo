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

// Filtre alma
$filterSector = $_GET['sector'] ?? '';
$filterQuery = "SELECT * FROM companies";
if ($filterSector !== '') {
    $stmt = $conn->prepare("SELECT * FROM companies WHERE sector = ?");
    $stmt->bind_param("s", $filterSector);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($filterQuery);
}

$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şirketler - Admin Panel</title>
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
        .company-card {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .company-info {
            display: flex;
            align-items: center;
        }
        .company-logo {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #ccc;
        }
        .company-details {
            font-size: 15px;
        }
        .company-actions a {
            display: inline-block;
            margin-left: 10px;
            font-size: 18px;
            text-decoration: none;
            color: #555;
        }
        .company-actions a:hover {
            color: #d61818;
        }

        .filter-form {
            margin-bottom: 20px;
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
    <h2>Tüm Şirketler</h2>

    <form method="get" class="filter-form">
        <label for="sector">Sektöre Göre Filtrele:</label>
        <select name="sector" id="sector" onchange="this.form.submit()">
            <option value="">Tüm Sektörler</option>
            <?php
            $sectors = $conn->query("SELECT DISTINCT sector FROM companies");
            while ($sec = $sectors->fetch_assoc()) {
                $selected = $sec['sector'] == $filterSector ? 'selected' : '';
                echo "<option value='{$sec['sector']}' $selected>{$sec['sector']}</option>";
            }
            ?>
        </select>
    </form>

    <?php foreach ($companies as $c): ?>
        <div class="company-card">
            <div class="company-info">
                <?php
                $logo = $c['company_logo'] ?? 'default.png';
                $path = "../uploads/$logo";
                ?>
                <img src="<?= htmlspecialchars($path) ?>" class="company-logo" alt="Logo">
                <div class="company-details">
                    <strong>Firma Adı:</strong> <?= htmlspecialchars($c['company_name']) ?><br>
                    <strong>Sektör:</strong> <?= htmlspecialchars($c['sector']) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($c['company_email']) ?><br>
                    <strong>Telefon:</strong> <?= htmlspecialchars($c['company_phone']) ?><br>
                    <strong>Adres:</strong> <?= htmlspecialchars($c['company_address']) ?><br>
                    <strong>Hesap Türü:</strong> şirket
                </div>
            </div>
            <div class="company-actions">
                <a href="ayarlar/sirket_guncelle.php?user_id=<?= $c['user_id'] ?>" title="Ayarlar">⚙️</a>
                <a href="ayarlar/kullanici_sil.php?user_id=<?= $c['user_id'] ?>" title="Sil" onclick="return confirm('Bu şirketi silmek istediğinize emin misiniz?')">🗑️</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
