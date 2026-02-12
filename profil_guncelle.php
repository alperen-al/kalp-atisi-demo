<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$stmt = $conn->prepare("SELECT account_type, username, full_name, email, location, profile_photo, gender FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($account_type, $username, $full_name, $email, $location, $profile_photo, $gender);
$stmt->fetch();
$stmt->close();

// Kurumsal ise şirket bilgilerini al
if ($account_type === 'sirket') {
    $stmt = $conn->prepare("
      SELECT company_name, company_phone, company_email, company_address, company_logo
        FROM companies
       WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($company_name, $company_phone, $company_email, $company_address, $company_logo);
    $stmt->fetch();
    $stmt->close();
}

// Form gönderildiyse
if (isset($_POST['update'])) {
    if ($account_type === 'bireysel') {
        // Bireysel güncelle
        $username = $_POST['username'];
        $email    = $_POST['email'];
        $location = $_POST['location'];
        $gender   = $_POST['gender'];

        $photo_path = $profile_photo;
        if (!empty($_FILES['profile_photo']['name'])) {
            $photo_name  = uniqid() . '_' . basename($_FILES['profile_photo']['name']);
            $target_path = "uploads/" . $photo_name;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
                $photo_path = $target_path;
            }
        }

        $stmt = $conn->prepare("
          UPDATE users
             SET username = ?, email = ?, location = ?, profile_photo = ?, gender = ?
           WHERE user_id = ?
        ");
        $stmt->bind_param("sssssi", $username, $email, $location, $photo_path, $gender, $user_id);
        $stmt->execute();
        $stmt->close();

    } else {
        // Kurumsal güncelle
        $owner_name      = $_POST['owner_name'];
        $company_name    = $_POST['company_name'];
        $company_phone   = $_POST['company_phone'];
        $company_email   = $_POST['company_email'];
        $company_address = $_POST['company_address'];

        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->bind_param("si", $owner_name, $user_id);
        $stmt->execute();
        $stmt->close();

        $logo_path = $company_logo;
        if (!empty($_FILES['company_logo']['name'])) {
            $logo_name   = uniqid() . '_' . basename($_FILES['company_logo']['name']);
            $logo_target = "uploads/" . $logo_name;
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $logo_target)) {
                $logo_path = $logo_target;
            }
        }

        $stmt = $conn->prepare("
          UPDATE companies
             SET company_name = ?, company_phone = ?, company_email = ?, company_address = ?, company_logo = ?
           WHERE user_id = ?
        ");
        $stmt->bind_param("sssssi", $company_name, $company_phone, $company_email, $company_address, $logo_path, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: profil.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Profil Güncelle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background-color: #f2f2f2;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 500px;
      margin: 60px auto;
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.08);
    }
    h2 {
      text-align: center;
      color: #d61818;
      margin-bottom: 30px;
    }
    label {
      display: block;
      margin: 15px 0 5px;
      font-weight: bold;
    }
    input[type="text"],
    input[type="email"],
    input[type="file"],
    select {
      width: 100%;
      padding: 12px;
      font-size: 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      box-sizing: border-box;
      transition: border-color 0.2s;
    }
    input:focus, select:focus {
      border-color: #d61818;
      outline: none;
    }
    button {
      width: 100%;
      background-color: #d61818;
      color: white;
      padding: 12px;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 10px;
      margin-top: 25px;
      cursor: pointer;
    }
    button:hover {
      background-color: #b31212;
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      text-decoration: none;
      color: #007BFF;
      font-weight: bold;
    }
    .back-link:hover {
      text-decoration: underline;
    }
    @media screen and (max-width: 600px) {
      .container {
        margin: 30px 15px;
        padding: 25px 20px;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Profil Bilgilerini Güncelle</h2>
    <form action="" method="POST" enctype="multipart/form-data">
      <?php if ($account_type === 'bireysel'): ?>
        <label>Kullanıcı Adı:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>

        <label>E-posta:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label>Konum:</label>
        <input type="text" name="location" value="<?= htmlspecialchars($location) ?>" required>

        <label>Cinsiyet:</label>
        <select name="gender" required>
          <option value="">Seçiniz</option>
          <option value="Erkek" <?= $gender === 'Erkek' ? 'selected' : '' ?>>Erkek</option>
          <option value="Kadın" <?= $gender === 'Kadın' ? 'selected' : '' ?>>Kadın</option>
          <option value="Belirtmek istemiyorum" <?= $gender === 'Belirtmek istemiyorum' ? 'selected' : '' ?>>Belirtmek istemiyorum</option>
        </select>

        <label>Yeni Profil Fotoğrafı:</label>
        <input type="file" name="profile_photo" accept="image/*">

      <?php else: ?>
        <label>Firma Adı:</label>
        <input type="text" name="company_name" value="<?= htmlspecialchars($company_name) ?>" required>

        <label>Yetkili Kişi (Ad Soyad):</label>
        <input type="text" name="owner_name" value="<?= htmlspecialchars($full_name) ?>" required>

        <label>Firma Telefonu:</label>
        <input type="text" name="company_phone" value="<?= htmlspecialchars($company_phone) ?>" required>

        <label>Firma E-posta:</label>
        <input type="email" name="company_email" value="<?= htmlspecialchars($company_email) ?>" required>

        <label>Firma Adresi:</label>
        <input type="text" name="company_address" value="<?= htmlspecialchars($company_address) ?>" required>

        <label>Yeni Firma Logosu:</label>
        <input type="file" name="company_logo" accept="image/*">
      <?php endif; ?>

      <button type="submit" name="update">Güncelle</button>
    </form>

    <a class="back-link" href="profil.php">← Geri Dön</a>
  </div>

</body>
</html>
