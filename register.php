<?php
session_start();
include 'db.php';

// — Eşsiz referans kodu üret
function generateUniqueRefCode($conn, $length = 6) {
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  do {
    $ref_code = substr(str_shuffle(str_repeat($chars, 5)), 0, $length);
    $chk = $conn->prepare("SELECT COUNT(*) FROM users WHERE ref_code = ?");
    $chk->bind_param("s", $ref_code);
    $chk->execute();
    $chk->bind_result($count);
    $chk->fetch();
    $chk->close();
  } while ($count > 0);
  return $ref_code;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type     = $_POST['account_type'] ?? '';
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $ref_code = generateUniqueRefCode($conn);

  // Kullanıcı adı kontrolü
  if (!$username) {
    $errors[] = "Kullanıcı adı gerekli.";
  } else {
    $chk = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $chk->bind_param("s", $username);
    $chk->execute();
    $chk->bind_result($cnt);
    $chk->fetch();
    $chk->close();
    if ($cnt > 0) {
      $errors[] = "Bu kullanıcı adı zaten alınmış.";
    }
  }

  // Şifre kuralları
  if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
    $errors[] = "Şifre en az 8 karakter, 1 büyük harf, 1 küçük harf, 1 rakam ve 1 özel karakter içermeli.";
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);

  if ($type === 'bireysel') {
    $full_name  = $_POST['full_name']   ?? '';
    $phone      = $_POST['phone']       ?? '';
    $email      = trim($_POST['email'] ?? '');
    $gender     = $_POST['gender'] ?? '';

    $birth_date = $_POST['birth_date']  ?? '';
    $photo      = null;

    // KVKK onayı
    if (!isset($_POST['kvkk'])) {
      $errors[] = "KVKK onayı gerekli.";
    }

    // Profil fotoğrafı
    if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
      $photo = 'uploads/' . basename($_FILES['profile_photo']['name']);
      move_uploaded_file($_FILES['profile_photo']['tmp_name'], $photo);
    } else {
      $errors[] = "Profil fotoğrafı yüklenmeli.";
    }

    // — Bireysel e-posta boş mu / duplicate mı?
    if ($email === '') {
      $errors[] = "Lütfen e-posta adresi girin.";
    } else {
      $chkEmail = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
      $chkEmail->bind_param("s", $email);
      $chkEmail->execute();
      $chkEmail->bind_result($emailCount);
      $chkEmail->fetch();
      $chkEmail->close();
      if ($emailCount > 0) {
        $errors[] = "Bu e-posta zaten kayıtlı, lütfen başka bir adres deneyin.";
      }
    }

    if (empty($errors)) {
      $stmt = $conn->prepare("
  INSERT INTO users
    (username, password_hash, account_type, full_name, phone, email,gender, birth_date,  profile_photo, ref_code)
  VALUES (?, ?, 'bireysel', ?, ?, ?, ?, ?, ?, ?)
");

      $stmt->bind_param(
        "sssssssss",
        $username,
        $hash,
        $full_name,
        $phone,
        $email,
        $gender,
        $birth_date,
        $photo,
        $ref_code
      );
      if (!$stmt->execute()) {
        $errors[] = "Veritabanı hatası (users): " . $stmt->error;
      }
      $stmt->close();
    }

  } elseif ($type === 'sirket') {
    $owner_name      = $_POST['owner_name']      ?? '';
    $sector          = $_POST['sector']          ?? '';
    $company_name    = $_POST['company_name']    ?? '';
    $company_phone   = $_POST['company_phone']   ?? '';
    $company_email   = trim($_POST['company_email'] ?? '');
    $company_address = $_POST['company_address'] ?? '';
    $logo            = null;

    // KVKK onayı
    if (!isset($_POST['kvkk'])) {
      $errors[] = "KVKK onayı gerekli.";
    }

    // Kurum logosu
    if (!empty($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
      $logo = 'uploads/' . basename($_FILES['company_logo']['name']);
      move_uploaded_file($_FILES['company_logo']['tmp_name'], $logo);
    } else {
      $errors[] = "Kurum logosu yüklenmeli.";
    }

    // — Kurum e-posta boş mu / duplicate mı?
    if ($company_email === '') {
      $errors[] = "Lütfen kurum e-posta adresini girin.";
    } else {
      $chkComp = $conn->prepare("SELECT COUNT(*) FROM companies WHERE company_email = ?");
      $chkComp->bind_param("s", $company_email);
      $chkComp->execute();
      $chkComp->bind_result($compCount);
      $chkComp->fetch();
      $chkComp->close();
      if ($compCount > 0) {
        $errors[] = "Bu kurum e-posta zaten kayıtlı, lütfen başka bir adres deneyin.";
      }
    }

    if (empty($errors)) {
      // önce users tablosuna ekle
      $stmt1 = $conn->prepare("
        INSERT INTO users
          (username, password_hash, account_type, full_name, ref_code)
        VALUES (?, ?, 'sirket', ?, ?)
      ");
      $stmt1->bind_param(
        "ssss",
        $username,
        $hash,
        $owner_name,
        $ref_code
      );
      if (!$stmt1->execute()) {
        $errors[] = "Veritabanı hatası (users): " . $stmt1->error;
      }
      $user_id = $conn->insert_id;
      $stmt1->close();

      // sonra companies tablosuna ekle
      $stmt2 = $conn->prepare("
        INSERT INTO companies
          (user_id, company_name, company_phone, company_email, company_address, company_logo, sector)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt2->bind_param(
        "issssss",
        $user_id,
        $company_name,
        $company_phone,
        $company_email,
        $company_address,
        $logo,
        $sector
      );
      if (!$stmt2->execute()) {
        $errors[] = "Veritabanı hatası (companies): " . $stmt2->error;
      }
      $stmt2->close();
    }
  }

  // ✅ Başarılı kayıt sonrası login sayfasına yönlendir ve mesaj göster
  if (empty($errors)) {
    $_SESSION['success'] = "Kayıt başarılı! Giriş yapabilirsiniz.";
    header("Location: login.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kayıt Ol</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    .login-container {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding-top: 50px;
      min-height: 100vh;
      background: #f5f5f5;
      margin: 0;
      overflow-y: auto;
    }
    .login-card {
      background: #fff;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      width: 360px;
      text-align: center;
    }
    .login-card h2 { margin-bottom: 1rem; color: #333; }
    .login-card .input-group { text-align: left; margin: 0.5rem 0; }
    .login-card .input-group label { display: block; margin-bottom: 0.3rem; font-weight: 600; }
    .login-card .input-group input,
    .login-card .input-group select {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ccc;
      border-radius: 0.5rem;
      font-size: 1rem;
    }
    .login-card button.main {
      width: 100%;
      padding: 0.75rem;
      margin-top: 1rem;
      background: #007BFF;
      color: #fff;
      border: none;
      border-radius: 0.5rem;
      cursor: pointer;
      font-size: 1rem;
    }
    .login-card button.main:hover { background: #0056b3; }
    .or-text { margin: 1rem 0; color: #666; }
    .social-buttons a {
      display: block;
      text-decoration: none;
      color: #fff;
      padding: 0.5rem;
      margin: 0.3rem 0;
      border-radius: 0.5rem;
      font-size: 0.9rem;
    }
    .social-btn.google { background: #db4437; }
    .footer { margin-top: 1rem; font-size: 0.9rem; }
    .footer a { color: #007BFF; text-decoration: none; }

    /* --- Sektör listesi için eklenenler --- */
    .sector-list {
      max-height: 180px;
      overflow-y: auto;
      border: 1px solid #ccc;
      padding: 8px;
      margin: 0.5rem 0 1rem;
      text-align: left;
    }
    .sector-option {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
    }
    .sector-option:last-child { margin-bottom: 0; }
    .sector-option span {
      flex: 1;
    }
    .sector-divider {
      border: none;
      border-top: 1px solid #ddd;
      margin: 4px 0;
    }
    @media screen and (max-width: 480px) {
      .login-container {
        padding-top: 20px;
      }
      .login-card {
        width: 90%;
        padding: 1.5rem;
      }
      .login-card h2 {
        font-size: 1.2rem;
      }
      .login-card .input-group input,
      .login-card .input-group select {
        font-size: 0.9rem;
        padding: 0.6rem;
      }
      .login-card button.main {
        font-size: 0.9rem;
        padding: 0.6rem;
      }
      .sector-list {
        max-height: 140px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h2>Hesap Oluştur</h2>
      <?php if (!empty($errors)): ?>
        <div class="error" style="color:#e74c3c;margin-bottom:1rem">
          <ul style="list-style:none;padding:0">
            <?php foreach($errors as $e) echo "<li>• ".htmlspecialchars($e)."</li>"; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="input-group">
        <label><input type="radio" name="account_type" value="bireysel"
                      onclick="selectType('bireysel')" required> Bireysel</label>
        <label><input type="radio" name="account_type" value="sirket"
                      onclick="selectType('sirket')"> Kurumsal</label>
      </div>

      <!-- BİREYSEL -->
      <div id="bireysel" style="display:none; margin-top:20px;">
        <hr/>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="account_type" value="bireysel">
          <div class="input-group"><label>Kullanıcı Adı</label>
            <input type="text" name="username" required>
          </div>
          <div class="input-group"><label>Şifre</label>
            <input type="password" name="password" required>
          </div>
          <div class="input-group"><label>Ad Soyad</label>
            <input type="text" name="full_name" required>
          </div>
          <div class="input-group"><label>Telefon</label>
            <input type="tel" name="phone" required>
          </div>
          <div class="input-group"><label>E-posta</label>
            <input type="email" name="email" required>
          </div>
          <div class="input-group"><label>Cinsiyet</label>
  <select name="gender" required>
    <option value="">Seçiniz</option>
    <option value="erkek">Erkek</option>
    <option value="kız">Kız</option>
    <option value="belirtmek istemiyorum">Belirtmek İstemiyorum</option>
  </select>
</div>

          <div class="input-group"><label>Doğum Tarihi</label>
            <input type="date" name="birth_date" required id="birth_date">
            <script>
              document.getElementById("birth_date").max = new Date().toISOString().split('T')[0];
            </script>
          </div>
          <div class="input-group"><label>Profil Fotoğrafı</label>
            <input type="file" name="profile_photo" accept="image/*" required>
          </div>
          <div class="input-group">
            <label><input type="checkbox" name="kvkk" required> KVKK onayı</label>
          </div>
          <button type="submit" class="main">Kayıt Ol</button>
        </form>
        <div class="or-text">ya da</div>
        <div class="social-buttons">
          <a href="oauth.php?provider=google" class="social-btn google">
            <i class="fab fa-google" style="margin-right:8px"></i> Google ile Kayıt
          </a>
        </div>
      </div>

      <!-- KURUMSAL -->
      <div id="sirket" style="display:none; margin-top:20px;">
        <hr/>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="account_type" value="sirket">
          <div class="input-group"><label>Kullanıcı Adı</label>
            <input type="text" name="username" required>
          </div>
          <div class="input-group"><label>Şifre</label>
            <input type="password" name="password" required>
          </div>
          <div class="input-group"><label>Yetkili Kişi (Ad Soyad)</label>
            <input type="text" name="owner_name" required>
          </div>
          <div class="input-group"><label>Sektör</label>
            <div class="sector-list">
              <?php
                $sektorler = [
                  "Bilgi Teknolojileri (IT)",
                  "Finans / Bankacılık",
                  "Sağlık / Hekimlik",
                  "Eğitim / Öğretim",
                  "Üretim / İmalat",
                  "Perakende / E-Ticaret",
                  "Lojistik / Taşımacılık",
                  "Enerji / Elektrik",
                  "İnşaat / Gayrimenkul",
                  "Tarım / Gıda",
                  "Telekomünikasyon",
                  "Otomotiv",
                  "Turizm / Konaklama",
                  "Medya / İletişim",
                  "Danışmanlık / Profesyonel Hizmetler"
                ];
                foreach($sektorler as $s):
                  $label = $s === "Danışmanlık / Profesyonel Hizmetler"
                         ? "Danışmanlık / Profesyonel<br>Hizmetler"
                         : htmlspecialchars($s);
              ?>
                <div class="sector-option">
                  <span><?= $label ?></span>
                  <input type="radio" name="sector" value="<?= htmlspecialchars($s) ?>" required>
                </div>
                <hr class="sector-divider">
              <?php endforeach; ?>
            </div>
          </div>
          <div class="input-group"><label>Kurum Adı</label>
            <input type="text" name="company_name" required>
          </div>
          <div class="input-group"><label>Kurum Telefonu</label>
            <input type="tel" name="company_phone" required>
          </div>
          <div class="input-group"><label>Kurum E-posta</label>
            <input type="email" name="company_email" required>
          </div>
          <div class="input-group"><label>Kurum Adresi</label>
            <input type="text" name="company_address" required>
          </div>
          <div class="input-group"><label>Kurum Logosu</label>
            <input type="file" name="company_logo" accept="image/*" required>
          </div>
          <div class="input-group">
            <label><input type="checkbox" name="kvkk" required> KVKK onayı</label>
          </div>
          <button type="submit" class="main">Kurumsal Kayıt Ol</button>
        </form>
      </div>

      <div class="footer">
        Hesabın var mı? <a href="login.php">Giriş Yap</a>
      </div>
    </div>
  </div>

  <script>
    function selectType(t) {
      document.getElementById('bireysel').style.display = (t === 'bireysel') ? 'block' : 'none';
      document.getElementById('sirket').style.display   = (t === 'sirket')    ? 'block' : 'none';
    }
  </script>
</body>
</html>
