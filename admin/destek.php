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

// Filtreleme için verileri al
$filter_name     = $_GET['name'] ?? '';
$filter_account  = $_GET['account_type'] ?? '';
$filter_company  = $_GET['company_name'] ?? '';
$filter_gender   = $_GET['gender'] ?? '';
$filter_id       = $_GET['user_id'] ?? '';

$sql = "SELECT d.*, u.full_name, u.email, u.profile_photo, u.account_type, u.gender, c.company_name
        FROM destek d
        JOIN users u ON d.user_id = u.user_id
        LEFT JOIN companies c ON u.user_id = c.user_id
        WHERE 1=1";

if ($filter_name !== '')     $sql .= " AND u.full_name LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
if ($filter_account !== '')  $sql .= " AND u.account_type = '" . $conn->real_escape_string($filter_account) . "'";
if ($filter_company !== '')  $sql .= " AND c.company_name LIKE '%" . $conn->real_escape_string($filter_company) . "%'";
if ($filter_gender !== '')   $sql .= " AND u.gender = '" . $conn->real_escape_string($filter_gender) . "'";
if ($filter_id !== '')       $sql .= " AND u.user_id = '" . $conn->real_escape_string($filter_id) . "'";

$destekler = [];
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $destekler[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Destek - Admin Panel</title>
    <link rel="stylesheet" href="../tr/profil.css">
    <style>
        body { display: flex; margin: 0; font-family: Arial, sans-serif; background-color: #f3f4f6; }
        .sidebar {
            width: 200px; background-color: #1e3a5f; color: white; height: 100vh; padding: 20px 10px;
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
        .filter-box input, .filter-box select {
            margin: 5px 5px 10px 0;
            padding: 6px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .card {
            background-color: white; padding: 15px; border-radius: 10px;
            margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: space-between;
        }
        .info { display: flex; align-items: center; }
        .photo {
            width: 70px; height: 70px; border-radius: 50%;
            object-fit: cover; margin-right: 20px; border: 2px solid #ccc;
        }
        .details { font-size: 15px; }
        .actions a {
            display: inline-block; margin-left: 10px;
            font-size: 18px; text-decoration: none; color: #555;
        }
        .actions a:hover { color: #d61818; }
    </style>
    <script>
        function confirmDelete(url) {
            if (confirm("Bu destek kaydını silmek istediğinize emin misiniz?")) {
                window.location.href = url;
            }
        }
    </script>
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
        <h2>Destek Mesajları</h2>

        <form method="get" class="filter-box">
            <input type="text" name="name" placeholder="İsim" value="<?= htmlspecialchars($filter_name) ?>">
            <select name="account_type">
                <option value="">Hesap Türü</option>
                <option value="bireysel" <?= $filter_account == 'bireysel' ? 'selected' : '' ?>>Bireysel</option>
                <option value="sirket" <?= $filter_account == 'sirket' ? 'selected' : '' ?>>Şirket</option>
            </select>
            <select name="gender">
                <option value="">Cinsiyet</option>
                <option value="Erkek" <?= $filter_gender == 'Erkek' ? 'selected' : '' ?>>Erkek</option>
                <option value="Kadın" <?= $filter_gender == 'Kadın' ? 'selected' : '' ?>>Kadın</option>
            </select>
            <input type="text" name="company_name" placeholder="Şirket Adı" value="<?= htmlspecialchars($filter_company) ?>">
            <input type="number" name="user_id" placeholder="ID" value="<?= htmlspecialchars($filter_id) ?>">
            <input type="submit" value="Filtrele">
        </form>

        <?php foreach ($destekler as $d): ?>
            <div class="card">
                <div class="info">
                    <?php
                        $photo = $d['profile_photo'];
                        $photoPath = $photo ? "../uploads/{$photo}" : "../uploads/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" class="photo" alt="Profil Fotoğrafı">
                    <div class="details">
                        <strong>Ad Soyad:</strong> <?= htmlspecialchars($d['full_name']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($d['email']) ?><br>
                        <strong>Hesap Türü:</strong> <?= htmlspecialchars($d['account_type']) ?><br>
                        <strong>Cinsiyet:</strong> <?= htmlspecialchars($d['gender']) ?><br>
                        <?php if ($d['account_type'] === 'sirket'): ?>
                            <strong>Şirket Adı:</strong> <?= htmlspecialchars($d['company_name']) ?><br>
                        <?php endif; ?>
                        <strong>Mesaj:</strong> <?= htmlspecialchars($d['mesaj']) ?><br>
                        <strong>Tarih:</strong> <?= htmlspecialchars($d['tarih']) ?>
                    </div>
                </div>
                <div class="actions">
                    <a href="javascript:void(0);" onclick="confirmDelete('ayarlar/destek_sil.php?id=<?= $d['id'] ?>')" title="Sil">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
