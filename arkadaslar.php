<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$sql = "
  SELECT m.message_id, m.message_text, m.send_date, m.send_status, m.email,
         p.price, p.purchase_date, p.purchase_id
  FROM messages m
  JOIN purchases p ON m.purchase_id = p.purchase_id
  WHERE p.user_id = ?
  ORDER BY m.send_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Arkadaşlar</title>
  <link rel="stylesheet" href="tr/arkadaslar.css" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom, #0f172a, #1e293b);
      color: white;
      min-height: 100vh;
      display: flex;
    }

    .side-menu {
      width: 200px;
      min-height: 100vh;
      background: transparent;
      padding: 30px 10px;
      border-right: 1px solid rgba(255,255,255,0.1);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      transition: left 0.3s ease;
    }

    .menu-title {
      font-size: 22px;
      font-weight: bold;
      color: #d61818;
      text-align: center;
      margin-bottom: 30px;
    }

    .side-menu a {
      color: #ddd;
      text-decoration: none;
      padding: 10px;
      margin: 6px 0;
      border-radius: 8px;
      transition: 0.2s;
    }

    .side-menu a:hover {
      background-color: rgba(255, 255, 255, 0.1);
      color: white;
    }

    .main-content {
      margin-left: 200px;
      padding: 40px;
      flex: 1;
    }

    .main-title {
      font-size: 28px;
      margin-bottom: 10px;
      margin-top: 80px;
      text-align: center;
    }

    .sub-title {
      font-size: 14px;
      color: #ccc;
      margin-bottom: 30px;
      text-align: center;
    }

    .card-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 24px;
    }

    .card {
      background-color: white;
      color: #111;
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 280px;
      text-align: left;
      position: relative;
    }

    .card h2 { margin-top: 0; }

    .card-buttons {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 20px;
    }

    .update-btn, .delete-btn, .start-btn, .save-btn {
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
    }

    .update-btn {
      background-color: #5a4bff;
      color: white;
      padding: 10px 18px;
      flex: 1;
      margin-right: 8px;
    }

    .delete-btn {
      background-color: #ff3c3c;
      color: white;
      padding: 10px 14px;
      font-size: 16px;
    }

    .add-card {
      background-color: #fff;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      text-align: center;
    }

    .start-btn {
      background-color: #d61818;
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      margin-top: 10px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      color: black;
      padding: 30px;
      border-radius: 16px;
      width: 320px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
      position: relative;
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 16px;
      font-size: 22px;
      cursor: pointer;
    }

    .delete-wrapper {
      position: relative;
      display: inline-block;
    }

    .delete-options {
      position: absolute;
      top: 35px;
      left: 0;
      background-color: white;
      border: 1px solid #ccc;
      padding: 6px;
      border-radius: 5px;
      box-shadow: 0 0 6px rgba(0,0,0,0.1);
      z-index: 999;
      display: none;
    }

    .delete-options form {
      margin: 4px 0;
    }

    .delete-options button {
      background-color: #f44336;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
      font-size: 0.9rem;
    }

    .menu-toggle {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1100;
      background-color: #d61818;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 20px;
    }

    @media screen and (max-width: 768px) {
      body { flex-direction: column; }

      .menu-toggle { display: block; }

      .side-menu {
        left: -220px;
        background-color: #1e293b;
      }

      .side-menu.open {
        left: 0;
      }

      .main-content {
        margin-left: 0;
        padding: 20px 10px;
      }

      .card-container {
        flex-direction: column;
        align-items: center;
      }

      .card {
        width: 90%;
      }
    }
  </style>
</head>
<body>

  <button class="menu-toggle" onclick="toggleMenu()">☰</button>

  <!-- Sol Menü -->
  <div class="side-menu" id="menu">
    <div class="menu-title">KALP ATIŞI</div>
    <a href="tr.php">Anasayfa</a>
    <a href="profil.php">Profil</a>
    <a href="not.php">Not Yaz</a>
    <a href="arkadaslar.php">Arkadaşlar</a>
    <a href="kurumlar.php">Kurumlar</a>
    <a href="hakkimizda.php">Hakkımızda</a>
  </div>

  <!-- Ana İçerik -->
  <div class="main-content">
    <h1 class="main-title">Yakınlarınıza Özel Günlerde Hislerinizi Ulaştırın</h1>
    <p class="sub-title">Notlarınızı annenize, babanıza, sevgilinize veya kendinize gönderebilirsiniz.</p>

    <div class="card-container">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="card">
            <h2>Mesaj #<?= $row['message_id'] ?></h2>
            <p>E-posta: <?= htmlspecialchars($row['email'] ?? '') ?: '-' ?></p>
            <p>Gönderim Tarihi: <?= $row['send_date'] ?></p>
            <p>Notunuz: "<?= htmlspecialchars($row['message_text']) ?>"</p>
            <div class="card-buttons">
              <button class="update-btn"
              onclick="openModal('<?= $row['message_id'] ?>','Mesaj #<?= $row['message_id'] ?>','<?= htmlspecialchars($row['email'] ?? '') ?>','<?= htmlspecialchars($row['message_text']) ?>','<?= date('Y-m-d\TH:i', strtotime($row['send_date'])) ?>')">
                Güncelle
              </button>

              <div class="delete-wrapper">
                <button class="delete-btn" onclick="toggleOptions(this)">🗑️</button>
                <div class="delete-options">
                  <form method="POST" action="not_sil.php" onsubmit="return confirm('Bu mesaj sadece sizden silinecek. Emin misiniz?');">
                    <input type="hidden" name="message_id" value="<?= $row['message_id'] ?>">
                    <input type="hidden" name="sil" value="benden">
                    <button type="submit">Benden Sil</button>
                  </form>
                  <form method="POST" action="not_sil.php" onsubmit="return confirm('İşleminiz iptal edilince para iadesi olmayacaktır. Emin misiniz?');">
                    <input type="hidden" name="message_id" value="<?= $row['message_id'] ?>">
                    <input type="hidden" name="sil" value="iptal">
                    <button type="submit">Gönderimi İptal Et</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>Henüz hiç mesaj oluşturmadınız 💔</p>
      <?php endif; ?>

      <div class="card add-card" onclick="window.location.href='not.php'">
        <i class="fas fa-plus fa-2x"></i>
        <h3>Yeni Kişi Ekle</h3>
        <p>Doğum gününe özel not ekleyin</p>
        <button class="start-btn">Eklemeye Başla</button>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="modal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal()">&times;</span>
      <h2>Mesajı Güncelle</h2>
      <form method="POST" action="not_guncelle.php">
        <input type="hidden" id="message_id" name="message_id">
        <label>İsim</label>
        <input type="text" id="modalName" disabled />
        <label>E-posta</label>
        <input type="text" id="modalPhone" name="new_email" />
        <label>Not</label>
        <textarea id="modalNote" name="new_note" maxlength="50" required></textarea>
        <label>Gönderim Tarihi</label>
        <input type="datetime-local" id="modalDate" name="new_date" required />
        <button type="submit" class="save-btn">Kaydet</button>
      </form>
    </div>
  </div>

  <script>
    function openModal(messageId, name, email, note, date) {
      const modal = document.getElementById("modal");
      modal.style.display = "flex";
      document.getElementById("modalName").value = name;
      document.getElementById("modalPhone").value = email;
      document.getElementById("modalNote").value = note;
      document.getElementById("modalDate").value = date;
      document.getElementById("message_id").value = messageId;

      const now = new Date();
      document.getElementById("modalDate").min = now.toISOString().slice(0, 16);
    }

    function closeModal() {
      document.getElementById("modal").style.display = "none";
    }

    function toggleOptions(btn) {
      const wrapper = btn.closest('.delete-wrapper');
      const menu = wrapper.querySelector('.delete-options');
      document.querySelectorAll('.delete-options').forEach(opt => {
        if (opt !== menu) opt.style.display = 'none';
      });
      menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.delete-wrapper')) {
        document.querySelectorAll('.delete-options').forEach(opt => opt.style.display = 'none');
      }
    });

    function toggleMenu() {
      document.querySelector('.side-menu').classList.toggle('open');
    }
  </script>

</body>
</html>
