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

// Filtre: referans kodu sayısına göre, cinsiyete göre
$filters = [];
if (!empty($_GET['gender'])) {
    $gender = $_GET['gender'];
    $filters[] = "u.gender = '" . $conn->real_escape_string($gender) . "'";
}
if (!empty($_GET['ref_count'])) {
    $filters[] = "u.user_id IN (
        SELECT user_id FROM referrals
        GROUP BY user_id HAVING COUNT(*) >= " . intval($_GET['ref_count']) . "
    )";
}
$filterSQL = $filters ? "WHERE " . implode(" AND ", $filters) : "";

// Çekim talepleri sorgusu
$withdrawals = [];
$sql = "SELECT w.*, u.full_name, u.email, u.profile_photo, u.account_type, u.gender 
        FROM withdrawals w 
        JOIN users u ON w.user_id = u.user_id 
        $filterSQL
        ORDER BY w.request_date DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $withdrawals[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çekim Talepleri - Admin Panel</title>
    <link rel="stylesheet" href="../tr/profil.css">
    <style>
        body { display: flex; margin: 0; font-family: Arial, sans-serif; background-color: #f3f4f6; }
        .sidebar {
            width: 200px;
            background-color: #1e3a5f;
            color: white;
            height: 100vh;
            padding: 20px 10px;
        }
        .sidebar h2 { font-size: 20px; margin-bottom: 20px; }
        .sidebar a {
            display: block; color: white; padding: 8px 10px; margin: 5px 0; text-decoration: none;
        }
        .sidebar a:hover { background-color: #2c5282; }
        .sidebar .db-link {
            background-color: red; font-weight: bold; text-align: center;
            margin-top: 20px; display: block; padding: 10px;
        }

        .content { flex-grow: 1; padding: 20px; }
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
        .info { display: flex; align-items: center; }
        .photo {
            width: 70px; height: 70px; border-radius: 50%;
            object-fit: cover; margin-right: 20px; border: 2px solid #ccc;
        }
        .details { font-size: 15px; }
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
        .actions a:hover { color: #d61818; }

        .filter-bar {
            margin-bottom: 20px;
        }
        .filter-bar form {
            display: flex;
            gap: 10px;
            align-items: center;
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
        <h2>Çekim Talepleri</h2>

        <div class="filter-bar">
            <form method="GET">
                <label>Cinsiyet:</label>
                <select name="gender">
                    <option value="">Tümü</option>
                    <option value="male" <?= isset($_GET['gender']) && $_GET['gender'] === 'male' ? 'selected' : '' ?>>Erkek</option>
                    <option value="female" <?= isset($_GET['gender']) && $_GET['gender'] === 'female' ? 'selected' : '' ?>>Kadın</option>
                </select>

                <label>Min. Referans:</label>
                <input type="number" name="ref_count" value="<?= htmlspecialchars($_GET['ref_count'] ?? '') ?>" min="1">

                <button type="submit">Filtrele</button>
            </form>
        </div>

        <?php foreach ($withdrawals as $w): ?>
            <div class="card">
                <div class="info">
                    <?php
                        $photo = $w['profile_photo'];
                        $photoPath = $photo ? "../uploads/{$photo}" : "../uploads/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" class="photo" alt="Profil Fotoğrafı">
                    <div class="details">
                        <strong>Ad Soyad:</strong> <?= htmlspecialchars($w['full_name']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($w['email']) ?><br>
                        <strong>Hesap Türü:</strong> <?= htmlspecialchars($w['account_type']) ?><br>
                        <strong>Cinsiyet:</strong> <?= htmlspecialchars($w['gender']) ?><br>
                        <strong>IBAN:</strong> <?= htmlspecialchars($w['iban']) ?><br>
                        <strong>Tutar:</strong> $<?= htmlspecialchars($w['amount']) ?><br>
                        <strong>Tarih:</strong> <?= htmlspecialchars($w['request_date']) ?><br>
                        <strong>Durum:</strong> <?= htmlspecialchars($w['status']) ?>
                    </div>
                </div>
                <div class="actions">
                    <a href="ayarlar/cekim_talebi_guncelle.php?withdrawal_id=<?= $w['withdrawal_id'] ?>" title="Ayarlar">⚙️</a>
                    <a href="#" onclick="confirmDelete(<?= $w['withdrawal_id'] ?>)" title="Sil">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function confirmDelete(id) {
        if (confirm("Bu çekim talebini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.")) {
            window.location.href = "ayarlar/cekim_talebi_sil.php?withdrawal_id=" + id;
        }
    }
    </script>
</body>
</html>
