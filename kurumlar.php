<?php
session_start();
include 'db.php';

// Son aktif zamanı güncelle
if (isset($_SESSION['user_id'])) {
  $uid = $_SESSION['user_id'];
  $conn->query("UPDATE users SET last_active = NOW() WHERE user_id = $uid");
}

$loggedIn = isset($_SESSION['user_id']);

// Sabit sektör listesi (filtre dropdown için)
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

// Tüm kurumları çek
$companies = [];
$res = $conn->query("SELECT * FROM companies");
while ($row = $res->fetch_assoc()) {
  $companies[] = $row;
}
$res->free();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kurumlar</title>
  <style>
    .side-menu.dark {
      position: fixed; top: 0; left: 0;
      width: 180px; height: 100vh;
      background: #2c2c2c; padding: 20px 10px;
      display: flex; flex-direction: column;
      box-shadow: 2px 0 5px rgba(0,0,0,0.5);
      z-index: 1000;
    }
    .side-menu.dark .menu-title {
      font-size:22px; font-weight:bold; color:#f1f1f1;
      text-align:center; margin-bottom:20px;
    }
    .side-menu.dark a {
      color:#ccc; text-decoration:none;
      padding:10px 8px; margin:4px 0;
      border-radius:6px; font-weight:bold;
      transition:background 0.2s,color 0.2s;
    }
    .side-menu.dark a:hover {
      background:#444; color:#fff;
    }
    .main-content {
      margin-left:200px;
      padding:20px;
    }
    .filters {
      display:flex; flex-wrap:wrap; gap:10px;
      margin-bottom:20px;
    }
    .filters input, .filters select {
      padding:8px; font-size:14px;
      border:1px solid #ccc; border-radius:4px;
    }
    .cards-container {
      display:flex; flex-direction:column; gap:15px;
    }
    .company-card {
      background:#fff; border:1px solid #ddd;
      border-radius:8px; padding:15px;
      display:flex; align-items:center; gap:15px;
      box-shadow:0 2px 6px rgba(0,0,0,0.05);
    }
    .company-logo {
      width:60px; height:60px;
      object-fit:cover; border-radius:6px;
    }
    .company-name {
      font-size:18px; font-weight:bold; color:#333; flex:1;
    }
    .details-btn {
      padding:6px 10px; background:#337ab7;
      color:#fff; border:none; border-radius:4px;
      cursor:pointer; font-size:14px;
    }
    .company-details { display:none; }
    #modalOverlay {
      display:none; position:fixed; top:0; left:0;
      width:100%; height:100%; background:rgba(0,0,0,0.5);
      justify-content:center; align-items:center; z-index:2000;
    }
    #modalContent {
      background:#fff; padding:20px; border-radius:8px;
      width:90%; max-width:500px; position:relative;
      box-shadow:0 4px 12px rgba(0,0,0,0.15);
    }
    #modalClose {
      position:absolute; top:10px; right:10px;
      cursor:pointer; font-size:18px; color:#666;
    }
    @media screen and (max-width: 768px) {
      .side-menu.dark {
        position: relative;
        width: 100%;
        height: auto;
        flex-direction: row;
        overflow-x: auto;
        padding: 10px;
      }
      .side-menu.dark a {
        flex: 0 0 auto;
        margin-right: 12px;
      }
      .main-content {
        margin-left: 0;
        padding: 15px 10px;
      }
      .filters {
        flex-direction: column;
        gap: 8px;
      }
      .filters input, .filters select {
        width: 100%;
        max-width: none;
      }
      .cards-container {
        flex-direction: column;
        gap: 15px;
      }
      .company-card {
        flex-direction: column;
        align-items: flex-start;
      }
      .company-logo {
        margin-bottom: 10px;
      }
      #modalContent {
        width: 90%;
        max-width: 320px;
        padding: 15px;
      }
    }
  </style>
</head>
<body>


    <!-- Yan Menü -->
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

  <!-- Ana İçerik -->
  <div class="main-content">
    <!-- Filtreler -->
    <div class="filters">
      <input type="text" id="nameFilter" placeholder="Kuruma Göre Ara…">
      <select id="sectorFilter">
        <option value="">Tüm Sektörler</option>
        <?php foreach($sektorler as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Kartlar -->
    <div class="cards-container">
      <?php foreach($companies as $c): ?>
        <?php
          $logoPath = strpos($c['company_logo'], 'uploads/') === 0
                    ? $c['company_logo']
                    : 'uploads/' . $c['company_logo'];
        ?>
        <div class="company-card"
             data-name="<?= htmlspecialchars(strtolower($c['company_name'])) ?>"
             data-sector="<?= htmlspecialchars($c['sector']) ?>">
          <img src="<?= htmlspecialchars($logoPath) ?>"
               alt="Logo" class="company-logo">
          <div class="company-name"><?= htmlspecialchars($c['company_name']) ?></div>
          <button class="details-btn">Detayı Gör</button>

          <!-- Gizli detaylar -->
          <div class="company-details">
            <p><strong>Sektör:</strong> <?= htmlspecialchars($c['sector']) ?></p>
            <?php
  $address = trim($c['company_address']);
  $firstWord = explode(' ', $address)[0]; // İlk kelime
  $visible = mb_substr($firstWord, 0, 2); // İlk 2 harf (Türkçe karakter desteği)
  $masked = str_repeat('*', max(0, mb_strlen($firstWord) - 2));
  $hiddenAddress = $visible . $masked;
?>
<p><strong>Adres:</strong> <?= htmlspecialchars($hiddenAddress) ?></p>

            <?php
              $phone = $c['company_phone'];
              $hiddenPhone = strlen($phone) > 2 ? str_repeat('*', strlen($phone) - 2) . substr($phone, -2) : $phone;

              $email = $c['company_email'];
              $emailParts = explode('@', $email);
              $namePart = $emailParts[0];
              $domainPart = $emailParts[1] ?? '';
              $hiddenEmail = substr($namePart, 0, 2) . str_repeat('*', max(0, strlen($namePart) - 2)) . '@' . $domainPart;
            ?>
            <p><strong>Telefon:</strong> <?= htmlspecialchars($hiddenPhone) ?></p>
            <p><strong>E-posta:</strong> <?= htmlspecialchars($hiddenEmail) ?></p>

            <?php
              $stmtCert = $conn->prepare("SELECT cert_title FROM certificates WHERE user_id = ?");
              $stmtCert->bind_param("i", $c['user_id']);
              $stmtCert->execute();
              $resCerts = $stmtCert->get_result();
            ?>
            <?php if($resCerts->num_rows > 0): ?>
              <p><strong>Sertifikalar:</strong></p>
              <ul>
                <?php while($cert = $resCerts->fetch_assoc()): ?>
                  <li><?= htmlspecialchars($cert['cert_title']) ?></li>
                <?php endwhile; ?>
              </ul>
            <?php else: ?>
              <p>Henüz sertifika eklenmedi.</p>
            <?php endif; ?>
            <?php $stmtCert->close(); ?>

            <!-- Not Yaz Butonu -->
            <form action="not.php" method="GET" style="margin-top: 10px;">
  <input type="hidden" name="targetEmail" value="<?= urlencode($c['company_email']) ?>">
  <button type="submit" class="details-btn" style="background:#28a745;">Not Yaz</button>
</form>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Modal -->
  <div id="modalOverlay">
    <div id="modalContent">
      <span id="modalClose">×</span>
      <div id="modalBody"></div>
    </div>
  </div>

  <script>
    // Filtreleme
    const nameFilter   = document.getElementById('nameFilter');
    const sectorFilter = document.getElementById('sectorFilter');
    const cards        = document.querySelectorAll('.company-card');
    function filterCards() {
      const nv = nameFilter.value.toLowerCase();
      const sv = sectorFilter.value;
      cards.forEach(c=>{
        const ok1 = c.dataset.name.includes(nv);
        const ok2 = !sv || c.dataset.sector === sv;
        c.style.display = (ok1 && ok2) ? 'flex' : 'none';
      });
    }
    nameFilter.addEventListener('input', filterCards);
    sectorFilter.addEventListener('change', filterCards);

    // Modal aç/kapa
    const overlay = document.getElementById('modalOverlay');
    const body    = document.getElementById('modalBody');
    document.querySelectorAll('.details-btn').forEach(btn=>{
      btn.onclick = ()=> {
        const card = btn.closest('.company-card');
        body.innerHTML = card.querySelector('.company-details').innerHTML;
        overlay.style.display = 'flex';
      };
    });
    document.getElementById('modalClose').onclick = ()=> {
      overlay.style.display = 'none';
    };
    overlay.onclick = e=>{
      if(e.target === overlay) overlay.style.display = 'none';
    };
  </script>

</body>
</html>
