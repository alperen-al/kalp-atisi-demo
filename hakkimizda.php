<?php
session_start();
include 'db.php';

$loggedIn = isset($_SESSION['user_id']);
if ($loggedIn) {
  $uid = $_SESSION['user_id'];
  $conn->query("UPDATE users SET last_active = NOW() WHERE user_id = $uid");

  // Kullanıcı bilgilerini çek
  $info = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
  $info->bind_param("i", $uid);
  $info->execute();
  $result = $info->get_result();
  $userData = $result->fetch_assoc();
  $info->close();

  // Mesaj gönderildiyse
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destek_mesaj'])) {
    $mesaj = trim($_POST['destek_mesaj']);
    if ($mesaj !== '') {
      $stmt = $conn->prepare("INSERT INTO destek (user_id, ad_soyad, email, mesaj) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("isss", $uid, $userData['username'], $userData['email'], $mesaj);
      $stmt->execute();
      $stmt->close();
      $successMessage = "Mesajınız başarıyla gönderildi.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hakkımızda</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #fefefe;
      padding-left: 200px;
      box-sizing: border-box;
    }
    .side-menu.dark {
      position: fixed;
      top: 0;
      left: 0;
      width: 180px;
      height: 100vh;
      background: #2c2c2c;
      padding: 20px 10px;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 5px rgba(0,0,0,0.5);
      z-index: 1000;
    }
    .menu-title {
      font-size: 22px;
      font-weight: bold;
      color: #f1f1f1;
      text-align: center;
      margin-bottom: 20px;
    }
    .side-menu.dark a {
      color: #ccc;
      text-decoration: none;
      padding: 10px 8px;
      margin: 4px 0;
      border-radius: 6px;
      font-weight: bold;
      transition: background 0.2s, color 0.2s;
    }
    .side-menu.dark a:hover {
      background: #444;
      color: #fff;
    }
    .main-content {
      max-width: 800px;
      margin: auto;
      padding: 40px 20px;
    }
    .main-content h1 {
      font-size: 28px;
      color: #d61818;
      text-align: center;
      margin-bottom: 20px;
    }
    .main-content p {
      font-size: 16px;
      line-height: 1.6;
      color: #333;
      text-align: justify;
    }
    .destek-form {
      margin-top: 40px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .destek-form label {
      font-weight: bold;
      color: #d61818;
    }
    .destek-form textarea {
      width: 100%;
      height: 140px;
      padding: 12px;
      font-size: 15px;
      border-radius: 10px;
      border: 1px solid #ccc;
      resize: none;
    }
    .destek-form button {
      align-self: flex-end;
      background-color: #28a745;
      color: #fff;
      padding: 10px 20px;
      border: none;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
    }
    .destek-form button:hover {
      background-color: #218838;
    }
    @media (max-width: 768px) {
      body { padding-left: 0; }
      .side-menu.dark {
        position: relative;
        width: 100%;
        height: auto;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        border-right: none;
        border-bottom: 1px solid #ddd;
      }
      .side-menu.dark a { margin: 6px; font-size: 14px; }
      .main-content { padding: 20px; }
    }
  </style>
</head>
<body>

  <div class="side-menu dark">
    <div class="menu-title">KALP ATIŞI</div>
    <a href="tr.php">Anasayfa</a>
    <?php if($loggedIn): ?>
      <a href="profil.php">Profilim</a>
      <a href="arkadaslar.php">Arkadaşlar</a>
    <?php endif; ?>
    <a href="not.php">Not Yaz</a>
    <a href="kurumlar.php">Kurumlar</a>
    <a href="hakkimizda.php">Hakkımızda</a>
  </div>

  <div class="main-content">
    <h1>Hakkımızda</h1>
    <p>
      <strong>Dünya’nın Kalbi Atışı</strong>, dünyanın dört bir yanından insanların tek bir tıkla iyiliğe ortak olduğu dijital bir dayanışma hareketi!<br><br>
      Kalbine dokunan bir ritme katılıyor, bir butona basıyor ve attığın her kalp, kolektif bir etkiye dönüşüyor. Hem kendi izini bırakıyor, hem de bu iyilik zincirinin parçası oluyorsun.<br><br>
      Her atış haritada görünür, sayaçta yerini alır. Referans sistemiyle çevreni davet edebilir, katkını büyütebilirsin. İster birey ol ister marka, burada herkesin yeri var. Mesaj gönder, katkını paylaş, istersen sertifikanı duvara as.<br><br>
      Şeffaf, eğlenceli, anlamlı.<br>
      <strong>Dünya’nın kalbi birlikte atıyor.</strong><br><br>
      <strong>Sen de katıl, bir ritim de senden gelsin. ❤️🌍</strong>
    </p>

    <hr style="margin-top: 40px;">
    <h2 style="
      font-size: 22px;
      color: #d61818;
      text-decoration: underline;
      font-weight: bold;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin-top: 30px;
      text-align: left;
      text-shadow: 1px 1px 1px #f3c6c6;
    ">
      Bize Ulaşın:
    </h2>
    <p style="font-size: 17px; margin-top: 10px;">
      Mail: 
      <a href="mailto:dunyaninkalbiatiyor@gmail.com" style="
        color: #007bff;
        text-decoration: none;
        font-weight: bold;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      ">
        dunyaninkalbiatiyor@gmail.com
      </a>
    </p>

    <?php if ($loggedIn): ?>
      <hr style="margin-top: 50px;">
      <form class="destek-form" method="POST">
        <label for="destek_mesaj">Destek, Görüş ve Önerileriniz:</label>
        <textarea name="destek_mesaj" id="destek_mesaj" placeholder="Mesajınızı buraya yazın..." required></textarea>
        <button type="submit">Gönder</button>
        <?php if (isset($successMessage)): ?>
          <p style="color: green; font-weight: bold;"><?= $successMessage ?></p>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>

</body>
</html>
