<?php
session_start();
include 'db.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Başlık kontrolü
  $title = trim($_POST['cert_title'] ?? '');
  if (empty($title)) {
    $errors[] = "Lütfen bir sertifika başlığı girin.";
  }

  // Dosya kontrolü
  if (!isset($_FILES['cert_file']) || $_FILES['cert_file']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Lütfen yüklemek için bir dosya seçin.";
  } else {
    $allowed = ['pdf','jpg','jpeg','png'];
    $ext = strtolower(pathinfo($_FILES['cert_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
      $errors[] = "Sadece PDF, JPG veya PNG uzantılı dosyalara izin veriliyor.";
    }
  }

  // Hata yoksa yükle ve veritabanına kaydet
  if (empty($errors)) {
    // Dosyayı server'a taşı
    $uploadDir = __DIR__ . '/uploads/certificates/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = time() . "_{$user_id}." . $ext;
    $dest = $uploadDir . $filename;
    if (move_uploaded_file($_FILES['cert_file']['tmp_name'], $dest)) {
      // Veritabanına kaydet
      $stmt = $conn->prepare("INSERT INTO certificates (user_id, cert_title, cert_file, upload_date) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $user_id, $title, $filename);
      if ($stmt->execute()) {
        $success = true;
      } else {
        $errors[] = "Veritabanına kaydedilemedi: " . $stmt->error;
        @unlink($dest);
      }
      $stmt->close();
    } else {
      $errors[] = "Dosya yükleme sırasında bir hata oluştu.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sertifika Ekle</title>
  <link rel="stylesheet" href="tr/profil.css" />
  <style>
    .form-container {
      max-width: 500px;
      margin: 50px auto;
      padding: 20px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .form-container h2 {
      margin-bottom: 15px;
      font-size: 20px;
    }
    .form-container label { display: block; margin-top: 10px; font-weight: bold; }
    .form-container input[type="text"],
    .form-container input[type="file"] {
      width: 100%; padding: 8px; margin-top: 5px;
      border: 1px solid #ccc; border-radius: 4px;
    }
    .form-container button {
      margin-top: 15px;
      padding: 10px 15px;
      background-color: #27ae60;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .form-container .errors p {
      color: #c0392b;
      margin: 5px 0;
    }
    .form-container .success {
      color: #27ae60;
      margin-top: 10px;
    }
    @media screen and (max-width: 480px) {
  .form-container {
    margin: 20px 10px;    /* yan boşlukları azalt */
    padding: 15px;        /* iç boşlukları azalt */
  }
  .form-container h2 {
    font-size: 18px;      /* başlığı küçült */
  }
  .form-container button {
    padding: 8px 12px;    /* butonu küçült */
    font-size: 14px;
  }
}

  </style>
</head>
<body>

  <div class="form-container">
    <h2>Yeni Sertifika Ekle</h2>

    <?php if ($success): ?>
      <p class="success">Sertifikan başarıyla yüklendi! <a href="profil.php">Profiline dön</a></p>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="errors">
          <?php foreach ($errors as $e): ?>
            <p>• <?= htmlspecialchars($e) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <label for="cert_title">Sertifika Başlığı</label>
        <input type="text" name="cert_title" id="cert_title" value="<?= htmlspecialchars($_POST['cert_title'] ?? '') ?>" required>

        <label for="cert_file">Sertifika Dosyası</label>
        <input type="file" name="cert_file" id="cert_file" accept=".pdf,.jpg,.jpeg,.png" required>

        <button type="submit">Yükle ve Kaydet</button>
      </form>
    <?php endif; ?>
  </div>

</body>
</html>
