<?php
session_start();
include '../db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Sadece admin erişebilsin
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

// FİLTRELEME
$filterConditions = [];

if (!empty($_GET['filter_id'])) {
    $id = intval($_GET['filter_id']);
    $filterConditions[] = "user_id = $id";
}
if (!empty($_GET['filter_account_type'])) {
    $accType = $conn->real_escape_string($_GET['filter_account_type']);
    $filterConditions[] = "account_type = '$accType'";
}
if (!empty($_GET['filter_gender'])) {
    $gender = $conn->real_escape_string($_GET['filter_gender']);
    $filterConditions[] = "gender = '$gender'";
}

$where = '';
if (!empty($filterConditions)) {
    $where = "WHERE " . implode(' AND ', $filterConditions);
}

// Kullanıcıları çek (admin hariç)
$users = [];
$sql = "SELECT * FROM users WHERE account_type != 'admin'";
if (!empty($where)) {
    $sql = "SELECT * FROM users WHERE account_type != 'admin' AND " . implode(' AND ', $filterConditions);
}
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcılar - Admin Panel</title>
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
        .filter-form {
            margin-bottom: 25px;
        }
        .filter-form label {
            margin-right: 5px;
        }
        .filter-form select,
        .filter-form input {
            margin-right: 15px;
            padding: 4px 6px;
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
        <h2>Tüm Kullanıcılar</h2>

        <!-- FİLTRE FORMU -->
        <form method="GET" class="filter-form">
            <label for="filter_id">Kullanıcı ID:</label>
            <input type="text" name="filter_id" id="filter_id" value="<?= htmlspecialchars($_GET['filter_id'] ?? '') ?>">

            <label for="filter_account_type">Hesap Türü:</label>
            <select name="filter_account_type" id="filter_account_type">
                <option value="">Tümü</option>
                <option value="bireysel" <?= ($_GET['filter_account_type'] ?? '') === 'bireysel' ? 'selected' : '' ?>>Bireysel</option>
                <option value="sirket" <?= ($_GET['filter_account_type'] ?? '') === 'sirket' ? 'selected' : '' ?>>Şirket</option>
            </select>

            <label for="filter_gender">Cinsiyet:</label>
            <select name="filter_gender" id="filter_gender">
                <option value="">Tümü</option>
                <option value="erkek" <?= ($_GET['filter_gender'] ?? '') === 'erkek' ? 'selected' : '' ?>>Erkek</option>
                <option value="kiz" <?= ($_GET['filter_gender'] ?? '') === 'kiz' ? 'selected' : '' ?>>Kız</option>
                <option value="belirtmek istemiyorum" <?= ($_GET['filter_gender'] ?? '') === 'belirtmek istemiyorum' ? 'selected' : '' ?>>Belirtmek İstemiyorum</option>
            </select>

            <button type="submit">Filtrele</button>
        </form>

        <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="user-info">
                    <?php
                        $photo = $user['profile_photo'];
                        $photoPath = $photo ? "../uploads/{$photo}" : "../uploads/default.png";
                    ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" class="user-photo" alt="Profil Fotoğrafı">
                    <div class="user-details">
                        <strong>Ad Soyad:</strong> <?= htmlspecialchars($user['full_name']) ?><br>
                        <strong>Kullanıcı Adı:</strong> <?= htmlspecialchars($user['username']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?><br>
                        <strong>Telefon:</strong> <?= htmlspecialchars($user['phone']) ?><br>
                        <strong>Doğum Tarihi:</strong> <?= htmlspecialchars($user['birth_date']) ?><br>
                        <strong>Cinsiyet:</strong> <?= htmlspecialchars($user['gender']) ?><br>
                        <strong>Hesap Türü:</strong> <?= htmlspecialchars($user['account_type']) ?><br>
                        <strong>Son Aktif:</strong> <?= htmlspecialchars($user['last_active']) ?>
                    </div>
                </div>
                <div class="user-actions">
                <a href="ayarlar/kullanici_guncelle.php?user_id=<?= $user['user_id'] ?>" title="Ayarlar">⚙️</a>

                <a href="ayarlar/kullanici_sil.php?user_id=<?= $user['user_id'] ?>" title="Sil" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">🗑️</a>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
