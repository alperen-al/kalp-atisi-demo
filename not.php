<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
  $uid = $_SESSION['user_id'];
  // Son aktif zamanı güncelle
  $conn->query("UPDATE users SET last_active = NOW() WHERE user_id = $uid");
}
$loggedIn = isset($_SESSION['user_id']);


// Giriş kontrolü


// E-posta maskeleme işlemi
$targetEmail = $_GET['targetEmail'] ?? '';
$maskedEmail = '';
if ($targetEmail !== '') {
  $atPos = strpos($targetEmail, '@');
  if ($atPos !== false) {
    $maskedEmail = substr($targetEmail, 0, 1) . str_repeat('*', $atPos - 1) . substr($targetEmail, $atPos);
  } else {
    $maskedEmail = '********';
  }
}

// Form gönderildiğinde kayıt işlemi
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!$loggedIn) {
    echo "<p style='color:red; text-align:center; font-weight:bold;'>Not göndermek için önce giriş yapmalısınız.</p>";
    exit();
  }

  $note      = trim($_POST['note']     ?? '');
  $email     = trim($_POST['email']    ?? '');
  $refCode   = trim($_POST['refCode']  ?? '');
  $datetime  = trim($_POST['date']     ?? '');
  $latitude  = trim($_POST['latitude'] ?? '') ?: null;
  $longitude = trim($_POST['longitude']?? '') ?: null;
  $user_id   = $_SESSION['user_id'];

  if ($note && $email && $datetime) {
    $price = 0.75;
    $pinColor = 'kirmizi';

    $stmt = $conn->prepare("
      INSERT INTO purchases 
        (user_id, purchase_date, price, status, pin_color, latitude, longitude, ref_code_used)
      VALUES (?, NOW(), ?, 'bekliyor', ?, ?, ?, ?)
    ");
    $stmt->bind_param("idsdds", $user_id, $price, $pinColor, $latitude, $longitude, $refCode);
    $stmt->execute();
    $purchase_id = $stmt->insert_id;
    $stmt->close();

    $stmt2 = $conn->prepare("
      INSERT INTO messages 
        (purchase_id, message_text, send_date, send_status, email)
      VALUES (?, ?, ?, 'bekliyor', ?)
    ");
    $stmt2->bind_param("isss", $purchase_id, $note, $datetime, $email);
    $stmt2->execute();
    $stmt2->close();

    if (!empty($refCode)) {
      $u = $conn->prepare("
        UPDATE users 
           SET ref_use_count = IFNULL(ref_use_count, 0) + 1 
         WHERE ref_code = ?
      ");
      $u->bind_param("s", $refCode);
      $u->execute();
      $u->close();
    }

    header("Location: arkadaslar.php");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kalp Atışı Oluştur</title>
  <style>
  /* === GENEL === */
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    min-height: 100vh;
    background-color: #ffeef1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-top: 20px;
    padding-bottom: 20px;
  }

  /* === Menü === */
  .side-menu {
    position: fixed;
    top: 0;
    left: 0;
    width: 180px;
    height: 100vh;
    background-color: #fff;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    padding: 20px 10px;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
    z-index: 1000;
  }

  .menu-title {
    font-size: 22px;
    font-weight: bold;
    color: red;
    margin-bottom: 20px;
    text-align: center;
  }

  .side-menu a {
    text-decoration: none;
    color: #333;
    padding: 10px 8px;
    margin: 4px 0;
    font-weight: bold;
    border-radius: 6px;
    transition: background 0.2s;
  }

  .side-menu a:hover {
    background-color: #f0f0f0;
  }

  /* === Form Konteyner === */
  .form-container {
    background-color: #fff;
    width: 90%;
    max-width: 600px;
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    position: relative;
    z-index: 1001;
    margin: 0 auto;
  }

  /* Başlık */
  h2 {
    text-align: center;
    color: #d61818;
    margin-bottom: 25px;
  }

  /* Form Elemanları */
  label {
    display: block;
    margin-top: 18px;
    font-weight: bold;
  }

  textarea,
  input[type="tel"],
  input[type="text"],
  input[type="datetime-local"],
  input[type="email"] {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box;
    transition: border 0.2s;
  }

  textarea {
    resize: none;
    height: 80px;
  }

  input:focus,
  textarea:focus {
    border-color: #d61818;
    outline: none;
  }

  .char-count {
    text-align: right;
    font-size: 12px;
    color: #888;
    margin-top: 4px;
  }

  .checkbox-container {
    margin-top: 20px;
    display: flex;
    align-items: center;
  }

  .checkbox-container input {
    margin-right: 8px;
  }

  /* Buton */
  button {
    background-color: #d61818;
    color: #fff;
    font-weight: bold;
    border: none;
    width: 100%;
    padding: 14px;
    margin-top: 28px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
  }

  button:hover {
    background-color: #b31212;
  }

  /* === Mobil Uyumluluk === */
  @media screen and (max-width: 768px) {
    .side-menu {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: auto !important;
      display: flex !important;
      flex-direction: row !important;
      overflow-x: auto !important;
      padding: 10px 0 !important;
      border-bottom: 1px solid #ddd !important;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
      z-index: 1000 !important;
    }

    body {
      padding-left: 0 !important;
    }

    .side-menu a,
    .side-menu button {
      flex: 0 0 auto !important;
      text-align: center !important;
      padding: 10px 15px !important;
      margin: 0 5px !important;
      white-space: nowrap !important;
    }

    .form-container {
      margin-top: 80px !important;
      padding: 20px 15px !important;
      width: calc(100% - 30px) !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
    }
  }
  </style>
</head>
<body>

  <div class="side-menu">
    <div class="menu-title">KALP ATIŞI</div>
    <a href="tr.php">Anasayfa</a>
    <a href="profil.php">Profil</a>
    <a href="not.php">Not Yaz</a>
    <a href="arkadaslar.php">Arkadaşlar</a>
    <a href="kurumlar.php">Kurumlar</a>
    <a href="hakkimizda.php">Hakkımızda</a>
  </div>

  <div class="form-container">
    <h2>Kalp Atışı Oluştur</h2>
    <?php if (!$loggedIn): ?>
      <div style="padding: 20px; background-color: #f9dede; border: 1px solid #d61818; border-radius: 10px; color: #b31212; text-align: center; margin-bottom: 20px;">
        Not gönderebilmek için <a href="login.php" style="color: #d61818; font-weight: bold;">giriş yapmalısınız</a>.
      </div>
    <?php endif; ?>

    <form id="heartbeatForm" action="shopify_gateway.php" method="POST">
      <label for="note">Notunuzu Yazın</label>
      <textarea id="note" name="note" maxlength="2500" placeholder="Bugün seni düşünüyorum ❤️" required></textarea>
      <div class="char-count"><span id="charCount">0</span>/2500 karakter</div>

      <label for="email">Email Adresi</label>
      <?php if ($targetEmail): ?>
        <input type="hidden" name="email" value="<?= htmlspecialchars($targetEmail) ?>">
        <div style="padding: 12px; background-color: #f9f9f9; border: 1px solid #ccc; border-radius: 8px; margin-top: 6px;">
          <?= htmlspecialchars($maskedEmail) ?>
        </div>
      <?php else: ?>
        <input 
          type="email" 
          id="email" 
          name="email" 
          placeholder="ornek@mail.com"
          required
        >
      <?php endif; ?>

      <label for="refCode">Referans Kodu (Varsa)</label>
      <input type="text" id="refCode" name="refCode" placeholder="Varsa referans kodunuz">

      <label for="date">Gönderim Tarihi</label>
      <input type="datetime-local" id="date" name="date" required>

      <div class="checkbox-container">
        <input type="checkbox" id="contract" required>
        <label for="contract">Sözleşmeyi kabul ediyorum</label>
      </div>

      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">

      <button type="submit">Notu Planla ( <b>0.75$</b> )</button>
    </form>
  </div>

  <script>
    const textarea = document.getElementById("note");
    const charCount = document.getElementById("charCount");
    textarea.addEventListener("input", () => {
      charCount.textContent = textarea.value.length;
    });

    window.onload = function() {
      const now = new Date();
      document.getElementById("date").min = now.toISOString().slice(0, 16);
    };

    const form = document.getElementById('heartbeatForm');
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      if (!navigator.geolocation) {
        return form.submit();
      }
      navigator.geolocation.getCurrentPosition(
        function(pos) {
          document.getElementById('latitude').value  = pos.coords.latitude;
          document.getElementById('longitude').value = pos.coords.longitude;
          form.submit();
        },
        function(err) {
          if (confirm('Konum izni verilmediği için pin haritada görünmeyecek.\nYine de devam etmek istiyor musunuz?')) {
            form.submit();
          }
        }
      );
    });
  </script>

</body>
</html>
