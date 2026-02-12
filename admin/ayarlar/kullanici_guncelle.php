<?php
session_start();
include '../../db.php';

// Admin girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

if (!isset($_GET['user_id'])) {
    echo "Kullanıcı ID belirtilmedi.";
    exit;
}

$user_id = intval($_GET['user_id']);

// Kullanıcı verisini çek
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

// Form güncelleme isteği
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $account_type = $_POST['account_type'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, birth_date=?, gender=?, account_type=? WHERE user_id=?");
    $stmt->bind_param("ssssssi", $full_name, $email, $phone, $birth_date, $gender, $account_type, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../kullanicilar.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcıyı Güncelle</title>
    <link rel="stylesheet" href="../../tr/profil.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #1e3a5f;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            margin-top: 25px;
            width: 100%;
            padding: 12px;
            background-color: #1e3a5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        button:hover {
            background-color: #274b73;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Kullanıcı Bilgilerini Güncelle</h2>
        <form method="POST">
            <label>Ad Soyad</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <label>Telefon</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

            <label>Doğum Tarihi</label>
            <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date']) ?>">

            <label>Cinsiyet</label>
            <select name="gender" required>
                <option value="erkek" <?= $user['gender'] === 'erkek' ? 'selected' : '' ?>>Erkek</option>
                <option value="kiz" <?= $user['gender'] === 'kiz' ? 'selected' : '' ?>>Kız</option>
                <option value="belirtmek istemiyorum" <?= $user['gender'] === 'belirtmek istemiyorum' ? 'selected' : '' ?>>Belirtmek istemiyorum</option>
            </select>

            <label>Hesap Türü</label>
            <select name="account_type">
                <option value="bireysel" <?= $user['account_type'] === 'bireysel' ? 'selected' : '' ?>>Bireysel</option>
                <option value="sirket" <?= $user['account_type'] === 'sirket' ? 'selected' : '' ?>>Şirket</option>
            </select>

            <button type="submit">Kaydet</button>
        </form>
    </div>
</body>
</html>