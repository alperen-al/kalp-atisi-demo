<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini çek
$stmt = $conn->prepare("
  SELECT full_name, phone, email, birth_date, profile_photo, ref_code, ref_use_count, account_type, gender
  FROM users
  WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $phone, $email, $birth_date, $photo, $ref_code, $ref_use_count, $account_type, $gender);
$stmt->fetch();
$stmt->close();

// Profil fotoğrafı yolu kontrolü ve düzenlemesi
$pp = $photo;
if (!$pp) {
  $pp = 'uploads/default.png'; // Varsayılan fotoğraf (mutlaka uploads içinde olmalı)
} elseif (strpos($pp, 'http') === 0) {
  // Eğer Google gibi dış bir URL ise doğrudan kullan
  $pp = $pp;
} else {
  // Aksi halde, sadece dosya adını al ve uploads klasörünü ekle
  $pp = 'uploads/' . basename($pp);
}

// İsim ve soyisim ayırma
$nameParts = explode(" ", $full_name);
$isim    = $nameParts[0] ?? '';
$soyisim = $nameParts[1] ?? '';

// Yaş hesaplama
$yas = '';
if ($birth_date) {
  $birth = new DateTime($birth_date);
  $now   = new DateTime();
  $yas   = $now->diff($birth)->y;
}

// Sertifika sayısı
$stmt = $conn->prepare("SELECT COUNT(*) FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cert_count);
$stmt->fetch();
$stmt->close();

// Referans kullanımı sayısı
$stmt = $conn->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($referral_count);
$stmt->fetch();
$stmt->close();

// Toplam hak edilen kazanç hesaplama
$sistem_kazanci = floor($ref_use_count / 1000) * 750;
$hak_edilen_kazanc = $sistem_kazanci * 0.10;

// Çekilen ve askıdaki kazançlar
$stmt = $conn->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'tamamlandi'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($withdrawn);
$stmt->fetch();
$stmt->close();
$withdrawn = $withdrawn ?? 0;

$stmt = $conn->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'bekliyor'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($askida);
$stmt->fetch();
$stmt->close();
$askida = $askida ?? 0;

// Bekleyen kazanç (çekilebilir)
$pending = $hak_edilen_kazanc - $withdrawn - $askida;

// Toplam kazanç (gösterilecek)
$total = $hak_edilen_kazanc;

// Kurumsal kullanıcı bilgileri varsa çek
if ($account_type === 'sirket') {
  $stmt = $conn->prepare("SELECT company_name, company_phone, company_email, company_address, company_logo FROM companies WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($company_name, $company_phone, $company_email, $company_address, $company_logo);
  $stmt->fetch();
  $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profil Paneli</title>
  <link rel="stylesheet" href="tr/profil.css" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    .profile-header img {
      border-radius: 50%;
      width: 120px;
      height: 120px;
      object-fit: cover;
    }
    .gecmis-kutu {
      margin-top: 15px;
      background: #f9f9f9;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 10px;
      box-shadow: 0 0 5px rgba(0,0,0,0.05);
    }
    .gecmis-kutu ul {
      list-style-type: none;
      padding: 0;
      margin: 0;
    }
    .gecmis-kutu li {
      padding: 8px 0;
      border-bottom: 1px dashed #ccc;
      font-size: 14px;
    }
    .gecmis-kutu li:last-child {
      border-bottom: none;
    }
    .hidden {
      display: none;
    }
    /* Sertifika Panel Stilleri */
    #sertifikaPanel {
      display: none;
      margin: 20px auto;
      max-width: 600px;
      padding: 20px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    #sertifikaPanel .cert-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }
    #sertifikaPanel .cert-item:last-child {
      border-bottom: none;
    }
    #sertifikaPanel .cert-item span {
      font-size: 14px;
      color: #333;
    }
    #sertifikaPanel .cert-item form button {
      background: none;
      border: none;
      font-size: 18px;
      color: #c0392b;
      cursor: pointer;
    }
    #sertifikaPanel .add-cert-btn {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 15px;
      background-color: #27ae60;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-size: 14px;
    }
    #shareOptions button,
#shareOptions a {
  padding: 6px 10px;
  border-radius: 5px;
  border: none;
  background-color: #1e3a5f;
  color: white;
  text-decoration: none;
  margin-right: 5px;
  font-size: 14px;
  display: inline-block;
}
#shareOptions a:hover,
#shareOptions button:hover {
  background-color: #2c5282;
}


  </style>
</head>
<body>



 <!-- Menü -->
 <div class="side-menu">
    <div class="menu-title">KALP ATIŞI</div>
    <a href="tr.php">Anasayfa</a>
    <a href="profil.php">Profil</a>
    <a href="/kalp_proje/not.php">Not Yaz</a>
    <a href="/kalp_proje/arkadaslar.php">Arkadaşlar</a>
    <a href="kurumlar.php">Kurumlar</a>
    <a href="hakkimizda.php">Hakkımızda</a>
    <!-- Ayarlar -->
    <div class="settings-dropdown">
      <button onclick="toggleSettingsMenu()">⚙️ Ayarlar</button>
      <div id="settings-options" class="settings-options hidden">
        <button onclick="window.location.href='profil_guncelle.php'">Bilgileri Güncelle</button>
        <button onclick="openDeleteModal()">Hesap Sil</button>
      </div>
    </div>
    <form action="logout.php" method="post" class="logout-form">
  <button type="submit">🚪 Çıkış Yap</button>
</form>

  </div>

  <!-- Çıkış Butonu -->
  

  <!-- Profil Bilgileri -->
  <div class="dashboard">
    <div class="row">
      <div class="card profile-header">
        <?php if ($account_type === 'sirket'): ?>
          <!-- Kurumsal Kullanıcı -->
          <img src="<?= htmlspecialchars($company_logo) ?>" alt="Firma Logosu" />
          <h2><?= htmlspecialchars($company_name) ?></h2>
          <p><strong>Yetkili:</strong> <?= htmlspecialchars($full_name) ?></p>
          <p><strong>Telefon:</strong> <?= htmlspecialchars($company_phone) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($company_email) ?></p>
          <p><strong>Adres:</strong> <?= htmlspecialchars($company_address) ?></p>
        <?php else: ?>
          <!-- Bireysel Kullanıcı -->
          <img src="<?= htmlspecialchars($pp) ?>" alt="Profil Foto" />
          <h2><?= htmlspecialchars($full_name) ?></h2>
          <p>İsim: <?= htmlspecialchars($isim) ?></p>
          <p>Soyisim: <?= htmlspecialchars($soyisim) ?></p>
          <p>Cinsiyet: 
  <?= $gender === 'Erkek' || $gender === 'Kadın' || $gender === 'Belirtmek istemiyorum' 
        ? htmlspecialchars($gender) 
        : 'Belirtilmedi' ?>
</p>

          <p>Yaş: <?= htmlspecialchars($yas) ?></p>
          <p>Telefon: <?= htmlspecialchars($phone) ?></p>
          <p>Email: <?= htmlspecialchars($email) ?></p>
        <?php endif; ?>
      </div>

      <div class="card">
        <p class="subtitle">🏅 Sertifikalar</p>
        <h3><?= $cert_count ?></h3>
        <button onclick="toggleSertifikaPanel()">Sertifika Bilgisi</button>
      </div>

      <div class="card">
        <p class="subtitle">🔗 Referans Kodum</p>
        <input type="text" id="refCodeInput" readonly value="<?= htmlspecialchars($ref_code) ?>" />
        <p>📈 Kullanım Sayısı: <?= $ref_use_count ?></p>
        <button onclick="toggleShareOptions()">🤝 Referansımı Paylaş</button>

        <div id="shareOptions" class="hidden" style="margin-top:10px;">
          <button onclick="copyRefCode()">📋 Kopyala</button>
          <a href="#" id="whatsappShare" target="_blank">🟢 WhatsApp</a>
          <button onclick="copyAndOpenInstagram()">📸 Instagram</button>
        </div>

        <p id="copyStatus" style="display:none; color:green; font-size:14px; margin-top:5px;">
          ✅ Referans kodu panoya kopyalandı!
        </p>
      </div>
    </div>

    <!-- Sertifika Paneli -->
    <div id="sertifikaPanel" style="display:none; max-width:600px; margin:20px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
      <?php
      $query = $conn->prepare("SELECT cert_id, cert_title, cert_file, upload_date FROM certificates WHERE user_id = ?");
      $query->bind_param("i", $user_id);
      $query->execute();
      $result = $query->get_result();
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo "<div class='cert-item'>
                  <span>
  <a href='certificates/{$row['cert_file']}' target='_blank' style='text-decoration:none; color:#2980b9;'>
    {$row['cert_title']} ({$row['upload_date']})
  </a>
</span>

                  <form method='post' action='sertifika_sil.php' onsubmit='return confirm(\"Emin misiniz?\")'>
                    <input type='hidden' name='cert_id' value='{$row['cert_id']}' />
                    <button type='submit'>🗑️</button>
                  </form>
                </div>";
        }
      } else {
        echo "<p>Henüz sertifika eklemediniz.</p>";
      }
      $query->close();
      ?>
      <div style="text-align:center; margin-top: 15px;">
        <a href="sertifika_ekle.php" class="add-cert-btn">Sertifika Ekle</a>
        <a href="sertifika_iste.php" class="add-cert-btn" style="margin-left:10px;">Sertifika İste</a>
      </div>
    </div>

    <div class="row" style="margin-top: 20px;">
      <div class="card stats red">
        ❤️ Toplam Kazanç ($)<br><strong>$<?= number_format($total, 2) ?></strong>
      </div>
      <div class="card stats yellow">
        ⏳ Bekleyen Kazanç ($)<br><strong>$<?= number_format($pending, 2) ?></strong>
      </div>
      <div class="card stats blue">
        🕓 Askıdaki Kazanç ($)<br><strong>$<?= number_format($askida, 2) ?></strong>
      </div>
      <div class="card stats green">
        💸 Çekilen Kazanç ($)<br><strong>$<?= number_format($withdrawn, 2) ?></strong>
      </div>
    </div>

    <div class="withdraw" style="margin-top: 20px;">
    <form method="POST" action="cekim_olustur.php" onsubmit="return validateIban()">
  <label>IBAN</label>
  <input type="text" id="ibanInput" name="iban" required placeholder="TR00 0000 0000 0000 0000 00" maxlength="26" />
  
  <label>IBAN Sahibinin Ad Soyad</label>
  <input type="text" id="ibanName" name="iban_name" required placeholder="Ad Soyad" />


  <button type="submit">Çekim Talebi Oluştur</button>

  <p id="ibanError" style="color:red; font-size: 14px; display:none;"></p>
</form>


      <button onclick="toggleCekimGecmisi()">📄 Çekim Geçmişi</button>

      <div id="cekimGecmisiKutusu" class="gecmis-kutu hidden" style="margin-top:10px;">
        <?php
        $stmt = $conn->prepare("SELECT iban, amount, request_date, status FROM withdrawals WHERE user_id = ? ORDER BY request_date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
          echo "<ul>";
          while ($row = $result->fetch_assoc()) {
            echo "<li><strong>{$row['request_date']}</strong> - {$row['amount']}$ → {$row['status']} (IBAN: {$row['iban']})</li>";
          }
          echo "</ul>";
        } else {
          echo "<p>Henüz çekim geçmişiniz yok.</p>";
        }
        $stmt->close();
        ?>
      </div>
    </div>
  </div>
  <?php
$mesaj = '';
$renk = 'red';

if (isset($_SESSION['cekim_durumu'])) {
  switch ($_SESSION['cekim_durumu']) {
    case 'yetersiz':
      $mesaj = '❌ Yetersiz bakiye!';
      break;
    case 'isim_uyusmuyor':
      $mesaj = '❌ IBAN sahibi adı sizin adınızla uyuşmuyor.';
      break;
    case 'gecersiz_iban':
      $mesaj = '❌ Geçersiz IBAN formatı.';
      break;
    case 'basarili':
      $mesaj = '✅ Çekim talebiniz başarıyla alındı.';
      $renk = 'green';
      break;
  }
  unset($_SESSION['cekim_durumu']); // mesaj sadece 1 kez gösterilsin
}
?>

<?php if ($mesaj): ?>
  <div style="margin: 10px auto; max-width: 600px; background: <?= $renk ?>; color: white; padding: 10px 15px; border-radius: 8px; font-weight: bold; text-align: center;">
    <?= $mesaj ?>
  </div>
<?php endif; ?>


<script>
function toggleSettingsMenu() {
  document.getElementById('settings-options').classList.toggle('hidden');
}
function toggleCekimGecmisi() {
  document.getElementById("cekimGecmisiKutusu").classList.toggle("hidden");
}
function toggleSertifikaPanel() {
  let panel = document.getElementById("sertifikaPanel");
  panel.style.display = (panel.style.display === "none" || panel.style.display === "") ? "block" : "none";
}
function toggleShareOptions() {
  const options = document.getElementById("shareOptions");
  options.classList.toggle("hidden");

  const ref = document.getElementById("refCodeInput").value;
  const link = ref; // sadece referans kodu

  document.getElementById("whatsappShare").href =
    `https://wa.me/?text=Referans%20Kodum:%20${encodeURIComponent(link)}`;
}
function copyRefCode() {
  const refInput = document.getElementById("refCodeInput");
  refInput.select();
  refInput.setSelectionRange(0, 99999);
  try {
    document.execCommand("copy");
    document.getElementById("copyStatus").style.display = "block";
    setTimeout(() => {
      document.getElementById("copyStatus").style.display = "none";
    }, 2000);
  } catch (err) {
    alert("❌ Kopyalama başarısız oldu.");
  }
}
function copyAndOpenInstagram() {
  const ref = document.getElementById("refCodeInput").value;
  navigator.clipboard.writeText(ref).then(() => {
    window.open("https://www.instagram.com/direct/inbox/", "_blank");
    alert("📋 Referans kodu kopyalandı.\nInstagram'da kişiye mesaj olarak yapıştırabilirsin.");
  }).catch(() => {
    alert("❌ Kopyalama başarısız oldu.");
  });
}
function openDeleteModal() {
  document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}
function validateIban() {
  const iban = document.getElementById("ibanInput").value.trim().toUpperCase();
  const ibanName = document.getElementById("ibanName").value.trim().toLowerCase();
  const realName = "<?= strtolower($full_name) ?>"; // PHP'den kullanıcı adı

  const error = document.getElementById("ibanError");

  // IBAN kontrolü
  const ibanRegex = /^TR\d{24}$/;
  if (!ibanRegex.test(iban)) {
    error.textContent = "❌ Geçerli bir Türkiye IBAN'ı girin (26 karakter, TR ile başlayan).";
    error.style.display = "block";
    return false;
  }

  // İsim karşılaştırması
  if (ibanName !== realName) {
    error.textContent = "❌ IBAN sahibinin adı, sizin adınızla eşleşmiyor.";
    error.style.display = "block";
    return false;
  }

  // Sorun yok
  error.style.display = "none";
  return true;
}
window.addEventListener('click', function(e) {
  const dropdown = document.getElementById("settings-options");
  if (!e.target.closest('.settings-dropdown')) {
    dropdown.classList.add('hidden');
  }
});

</script>

<!-- Hesap silme modalı -->
<div id="deleteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
  <div style="background:#fff; padding:30px; border-radius:10px; text-align:center; max-width:400px;">
    <h3>Hesabınızı kalıcı olarak silmek istediğinize emin misiniz?</h3>
    <p>Bu işlem geri alınamaz!</p>
    <form method="POST" action="hesap_sil.php">
      <input type="hidden" name="confirm_delete" value="1" />
      <button type="submit" style="background:red; color:white; padding:10px 20px; border:none; margin:10px; border-radius:6px;">Evet, Sil</button>
      <button type="button" onclick="closeDeleteModal()" style="background:gray; color:white; padding:10px 20px; border:none; border-radius:6px;">Hayır, Silme</button>
    </form>
  </div>
</div>

</body>
</html>