<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
include 'db.php';

if ($loggedIn) {
  $uid = $_SESSION['user_id'];
  $conn->query("UPDATE users SET last_active = NOW() WHERE user_id = $uid");
}

$isAdmin = false;
if ($loggedIn) {
  // Yeni: account_type kontrolü
  $stmt = $conn->prepare("SELECT account_type FROM users WHERE user_id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $stmt->bind_result($accType);
  if ($stmt->fetch()) {
    $isAdmin = ($accType === 'admin');
  }
  $stmt->close();
}

// — Toplam onaylı satış adedi (kalp atışı)
$res = $conn->query("
  SELECT COUNT(*) 
    FROM purchases 
   WHERE status = 'bekliyor'
");
$totalBeats = $res->fetch_row()[0] ?? 0;
$res->free();

// — Aktif kullanıcılar (son 5 dakikada aktif olmuşlar)
$res = $conn->query("
  SELECT COUNT(*) 
    FROM users 
   WHERE last_active >= NOW() - INTERVAL 5 MINUTE
");
$activeUsers = $res->fetch_row()[0] ?? 0;
$res->free();

// — Günlük kazanç verisi (onaylı satışlar)
$earningsData = [];
$cumulative = 0;
$res = $conn->query("
  SELECT DATE(purchase_date) AS d, SUM(price) AS tot
    FROM purchases
   WHERE status = 'bekliyor'
   GROUP BY d
   ORDER BY d ASC
");
while ($row = $res->fetch_assoc()) {
  $cumulative += floatval($row['tot']);
  $earningsData[] = [
    'date'     => $row['d'],
    'earnings' => $cumulative
  ];
}

$res->free();

// — Toplam kazanç
$res = $conn->query("
  SELECT SUM(price) 
    FROM purchases 
   WHERE status = 'bekliyor'
");
$totalEarnings = floatval($res->fetch_row()[0] ?? 0);
$res->free();

// — Harita pin’leri + mesaj
// — Harita pin’leri + mesaj
$pins = [];
$res = $conn->query("
  SELECT 
  p.latitude, 
  p.longitude, 
  CASE 
    WHEN u.account_type = 'bireysel' THEN 'kirmizi'
    WHEN u.account_type = 'kurumsal' THEN 'yesil'
    ELSE 'gri'
  END AS pin_color,
  m.message_text,
  CASE 
    WHEN u.email IS NOT NULL AND u.email != '' THEN u.email 
    ELSE c.company_email 
  END AS email,
  u.account_type, 
  COALESCE(u.full_name, c.company_name) AS sender_name
FROM purchases p
LEFT JOIN messages m ON p.purchase_id = m.purchase_id
LEFT JOIN users u ON p.user_id = u.user_id
LEFT JOIN companies c ON u.user_id = c.user_id
WHERE p.latitude IS NOT NULL 
  AND p.longitude IS NOT NULL


");
while ($r = $res->fetch_assoc()) $pins[] = $r;
$res->free();



?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Geleceğe Dokunun</title>
  <link rel="stylesheet" href="style.css" />

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
  <!-- Chart.js -->
  <style>/* Ödül kutusu beyaz ve şık olsun */
  
.reward-content {
  background-color: white !important;
  color: #333 !important;
  padding: 15px 20px !important;
  border-radius: 12px !important;
  box-shadow: 0 4px 15px rgba(0,0,0,0.25) !important;
  font-weight: 600 !important;
  font-size: 16px !important;
  max-width: 320px;
  line-height: 1.4;
}

/* Liste stili */
.reward-content ul {
  padding-left: 20px !important;
  margin: 10px 0 !important;
}

.reward-content ul li {
  margin-bottom: 8px !important;
  font-weight: 500 !important;
  font-size: 15px !important;
  color: #111 !important;
}

/* Başlık rengi */
.reward-content strong {
  color: #d61818 !important;
  font-size: 18px !important;
}
#cityFilter {
    display: none !important;
  }
</style>
</head>
<body>
<header>
  <div id="menu-toggle">&#9776;</div>
  <div class="main-title">Geleceğe Dokunun : Dünyanın Kalbi Atsın</div>

  <!-- ÖDÜL BİLGİLERİ Dropdown -->
  <div class="dropdown reward-dropdown">
    <button onclick="toggleRewardDropdown()" class="dropbtn">🎁 Ödül</button>
    <div id="reward-dropdown" class="dropdown-content reward-content">
      <strong style="color:#d61818;font-size:16px;">🎁 Ödül Bilgileri</strong><br><br>
      <ul style="padding-left: 18px; margin: 0;">
        <li>Her 1000 referans kodu kullanımına <strong>$75</strong></li>
        <li>10,000. satın alımda toplam kazancın <strong>%5</strong>’i</li>
        <li>100,000. satın alımda toplam kazancın <strong>%10</strong>’u</li>
        <li>1,000,000. satın alımda toplam kazancın <strong>%15</strong>’i</li>
      </ul>
    </div>
  </div>

  <!-- LANGUAGE Dropdown -->
  <div class="dropdown">
    <button onclick="toggleDropdown()" class="dropbtn">Language ▼</button>
    <div id="dropdown-content" class="dropdown-content">
      <a href="tr.php">Türkçe</a>
      <a href="eng/eng.php">English</a>
      <a href="esp/esp.php">Español</a>
      <a href="deutch/deutch.php">Deutsch</a>
      <a href="china/china.php">中國人</a>
    </div>
  </div>
</header>

<!-- AÇILIR MENÜ -->
<div id="popup-menu" class="popup-hidden">
  <a href="tr.php">Anasayfa</a>
  <?php if ($loggedIn): ?>
    <a href="profil.php">Profil</a>
    <a href="/kalp_proje/arkadaslar.php">Arkadaşlar</a>
  <?php endif; ?>
  <a href="/kalp_proje/not.php">Not Yaz</a>
  <a href="kurumlar.php">Kurumlar</a>
  <a href="hakkimizda.php">Hakkımızda</a>
  <hr>
  <?php if ($isAdmin): ?>
    <a href="/kalp_proje/admin/adminpanel.php" style="color: gold; font-weight: bold;">Admin Paneli</a>
  <?php endif; ?>
  <?php if (!$loggedIn): ?>
    <a href="login.php">Giriş Yap / Kayıt Ol</a>
  <?php else: ?>
    <a href="logout.php" style="color: red;">Çıkış Yap</a>
  <?php endif; ?>
</div>

<!-- === HARİTA === -->
<div class="map-container">
  <div id="map"></div>
</div>

<!-- === GRAFİK & KAZANÇ === -->
<div class="earnings">

  <!-- Ülke/şehir/tarih seçim alanı -->
  <div class="selectors-container">
    <div class="selectors">
      <select id="countryFilter" class="short-select">
        <option value="">Ülke Seç</option>
      </select>
      <select id="cityFilter" class="short-select">
        <option value="">Şehir Seç</option>
      </select>
      <div class="date-range">
        <input type="date" id="startDate" />
        <input type="date" id="endDate" />
      </div>
    </div>
  </div>

  <!-- Tüm kutular birlikte yan yana -->
  <div class="earnings-summary">
    <!-- Kazanç -->
    <div class="stat-box">
      <div class="stat-box-title">Kazanç</div>
      <div class="stat-box-value" id="earningsValue">$0.00</div>
    </div>

    <!-- Toplam Kalp Atışı -->
    <div class="stat-box">
  <div class="stat-box-title">Toplam Kalp Atışı</div>
  <div class="stat-box-value" id="beatsValue"><?= $totalBeats ?></div>
</div>


    <!-- Aktif Kullanıcılar -->
    <div class="stat-box">
      <div class="stat-box-title">Aktif Kullanıcılar</div>
      <div class="stat-box-value" id="activeUsersValue">0</div>
    </div>

    <!-- Güncel Toplam Kazanç -->
    <div class="stat-box">
      <div class="stat-box-title">
        Güncel Toplam <br /> Kazanç
        <span class="live-dot" title="Aktif"></span>
      </div>
      <div class="stat-box-value">
        $<?= number_format($totalBeats * 0.75, 2) ?>
      </div>
    </div>

    <!-- SATIN AL -->
    <div class="price-box">
  <div class="price-value">$0.75</div>
  <small>Her Bir Kalp Atışı İçin Geçerli</small><br />
  <a href="not.php" class="rounded-button" style="display:inline-block; text-align:center; padding:10px 20px; background:orange; color:white; border-radius:12px; font-weight:bold; text-decoration:none;">
    SATIN AL
  </a>
</div>

  </div>

</div>




<!-- Not Yaz için Gizli Form -->
<form id="hiddenNoteForm" method="POST" action="not.php" style="display: none;">
  <input type="hidden" name="targetEmail" id="hiddenTargetEmail" />
</form>



<script>
  // PHP’den gelen veriler
  const mapPins      = <?= json_encode($pins) ?>;
  const earningsData = <?= json_encode($earningsData) ?>;
  let worldData = {};

  // Leaflet harita oluştur
  let map = L.map('map').setView([39,35],5);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'© OSM' }).addTo(map);

  // Pinleri haritaya ekleyen fonksiyon
  function displayPins(pins) {
    // Önce eski pinleri temizle
    map.eachLayer(layer => {
      if (layer instanceof L.CircleMarker) {
        map.removeLayer(layer);
      }
    });

    // Yeni pinleri ekle
    pins.forEach(p => {
      const marker = L.circleMarker([p.latitude, p.longitude], {
        radius: 6,
        fillColor: p.pin_color === 'kirmizi' ? 'red' : 'green',
        color: '#000',
        weight: 1,
        opacity: 1,
        fillOpacity: 0.8
      })
      .bindPopup(p.message_text || "Mesaj yok")
      .addTo(map);

      marker.on('mouseover', function () {
        this.openPopup();
      });

      marker.on('click', function () {
        let name   = p.sender_name || "Anonim";
        const msg  = p.message_text || "Mesaj yok";
        const email = p.email || "";

        if (typeof name === 'string' && name.toLowerCase().includes("mahallesi")) {
          const firstWord = name.split(' ')[0];
          const visiblePart = firstWord.slice(0, 2);
          const hiddenPart = '*'.repeat(Math.max(0, firstWord.length - 2));
          name = visiblePart + hiddenPart;
        }

        const fullContent = `
          <div style="text-align:center;">
            <strong style="font-size:16px;">${name}</strong><br>
            <div style="margin:8px 0;">${msg}</div>
            <button onclick="gotoNote('${email}')" 
                    style="background:#d61818;color:white;
                           border:none;padding:6px 12px;
                           border-radius:8px;cursor:pointer;
                           font-weight:bold;">
              Not Yaz
            </button>
          </div>
        `;
        this.setPopupContent(fullContent);
      });
    });
  }

  // Başlangıçta tüm pinleri göster
  displayPins(mapPins);

  // Not.php sayfasına yönlendir (email parametresi ile)
  function gotoNote(email) {
    const url = new URL('/kalp_proje/not.php', window.location.origin);
    url.searchParams.set('targetEmail', email);
    window.location.href = url.toString();
  }

  // Menü aç/kapa
  document.getElementById('menu-toggle').onclick = () => {
    let m = document.getElementById('popup-menu');
    m.style.display = m.style.display === 'flex' ? 'none' : 'flex';
  };

  // Dil dropdown aç/kapa
  function toggleDropdown(){
    let d = document.getElementById('dropdown-content');
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
  }

  // Ödül dropdown aç/kapa
  function toggleRewardDropdown() {
    const d = document.getElementById('reward-dropdown');
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
  }

  // Dropdown dışında tıklanırsa dropdownlar kapanır
  window.onclick = function(e) {
    if (!e.target.matches('.dropbtn')) {
      document.querySelectorAll('.dropdown-content').forEach(d => d.style.display = 'none');
    }
  };

  // Ülkeler JSON’dan yükleme
  fetch('countries.json')
    .then(res => res.json())
    .then(data => {
      worldData = data;
      const countrySelect = document.getElementById('countryFilter');
      const citySelect = document.getElementById('cityFilter');

      // Ülke dropdown doldur
      countrySelect.innerHTML = '<option value="">Ülke Seç</option>';
      Object.keys(worldData).forEach(country => {
        const opt = document.createElement('option');
        opt.value = country;
        opt.textContent = country;
        countrySelect.appendChild(opt);
      });

      // Ülke seçildiğinde zoom yap ve şehir dropdown'u boşalt
      countrySelect.addEventListener('change', () => {
        const selectedCountry = countrySelect.value;
        citySelect.innerHTML = '<option value="">Şehir Seç</option>'; // boşalt

        if (worldData[selectedCountry]) {
          const coord = worldData[selectedCountry];
          map.setView([coord.lat, coord.lng], coord.zoom || 6);
        } else {
          map.setView([39, 35], 5);
          displayPins(mapPins);
        }
      });

      // Şehir seçme kısmı şu an kullanılmıyor veya gizlenmiş olabilir
      // Eğer kullanılacaksa eklenebilir, ama şu an şehir seçme yok
    });

  // Pinleri filtreleyen fonksiyon (ülke/şehir bazlı, şehir yoksa sadece ülke filtresi çalışır)
  function filterPins(country, city) {
    let filtered = mapPins;
    if (country) {
      filtered = filtered.filter(p => p.country === country);
    }
    if (city) {
      filtered = filtered.filter(p => p.city === city);
    }
    displayPins(filtered);
  }

  // Kazanç özetini getir ve güncelle
  function updateSummary() {
    const start = document.getElementById('startDate').value;
    const end   = document.getElementById('endDate').value;

    const params = new URLSearchParams();
    if (start && end) {
      params.append('start', start);
      params.append('end', end);
    }

    fetch('get_summary.php?' + params.toString())
      .then(res => res.json())
      .then(data => {
        document.getElementById('beatsValue').textContent = data.beats;
        document.getElementById('earningsValue').textContent = `$${data.earnings}`;
      })
      .catch(err => console.error("get_summary.php hatası:", err));
  }

  // Aktif kullanıcı sayısını getir ve güncelle
  function updateActiveUsers() {
    fetch('get_active_users.php')
      .then(res => res.text())
      .then(count => {
        document.getElementById('activeUsersValue').textContent = count;
      });
  }

  // Sayfa yüklendiğinde özet ve aktif kullanıcı sayısını güncelle
  window.onload = () => {
    updateSummary();
    updateActiveUsers();
    document.getElementById('startDate').addEventListener('change', updateSummary);
    document.getElementById('endDate').addEventListener('change', updateSummary);
  };

  // Aktif kullanıcı sayısını her 30 saniyede bir güncelle
  setInterval(updateActiveUsers, 30000);
</script>





</body>
</html>