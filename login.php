<?php
session_start();
include 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Her kullanıcı tipini al: admin, bireysel, sirket
    $stmt = $conn->prepare("
        SELECT user_id, password_hash, account_type 
        FROM users 
        WHERE (username = ? OR email = ?) 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $hash, $account_type);

    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['account_type'] = $account_type;
        
        if ($account_type === 'admin') {
            header('Location: admin/adminpanel.php');
        } else {
            header('Location: tr.php');
        }
        exit();
    } else {
        $error = 'Kullanıcı adı veya şifre yanlış.';
    }
    $stmt->close();
}
?>

?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Giriş Yap</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" 
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <style>
    .login-container { display: flex; justify-content: center; align-items: center;
                       height: 100vh; background: #f5f5f5; }
    .login-card { background: #fff; padding: 2rem; border-radius: 1rem;
                  box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 320px;
                  text-align: center; }
    .login-card h2 { margin-bottom: 1rem; color: #333; }
    .login-card input { width: 100%; padding: 0.75rem; margin: 0.5rem 0;
                        border: 1px solid #ccc; border-radius: 0.5rem; }
    .login-card button.login-btn { width: 100%; padding: 0.75rem;
                                   background: #007BFF; color: #fff; border: none;
                                   border-radius: 0.5rem; cursor: pointer;
                                   font-size: 1rem; }
    .login-card button.login-btn:hover { background: #0056b3; }
    .social-login { margin-top: 1rem; }
    .social-btn { display: block; text-decoration: none; color: #fff;
                  padding: 0.5rem; margin: 0.5rem 0; border-radius: 0.5rem;
                  font-size: 0.9rem; }
    .social-btn.google { background: #DB4437; }
    .social-btn i { margin-right: 0.5rem; }
    .error { color: #e74c3c; margin-bottom: 1rem; }
    .register-link { margin-top: 1rem; font-size: 0.9rem; }
    .register-link a { color: #007BFF; text-decoration: none; }
    .register-link a:hover { text-decoration: underline; }
      /* === Mobil uyumluluk için ekleme === */
  @media screen and (max-width: 480px) {
    .login-container {
      padding: 20px 10px;
    }
    .login-card {
      width: 100%;
      max-width: 320px;
      padding: 1.5rem;
    }
    .login-card h2 {
      font-size: 1.2rem;
    }
    .login-card input {
      font-size: 0.9rem;
      padding: 0.6rem;
    }
    .login-card button.login-btn {
      font-size: 0.9rem;
      padding: 0.6rem;
    }
    .social-login {
      margin-top: 0.75rem;
    }
    .register-link {
      font-size: 0.85rem;
    }
  }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h2>Giriş Yap</h2>
      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="login.php">
        <input type="text" name="username" placeholder="Kullanıcı adı veya e-posta" required />
        <input type="password" name="password" placeholder="Şifre" required />
        <button type="submit" class="login-btn">Giriş Yap</button>
      </form>
      <div class="social-login">
        <div>ya da</div>
        <a href="oauth.php?provider=google" class="social-btn google">
          <i class="fab fa-google"></i>Google ile Giriş
        </a>
      </div>
      <div class="register-link">
        Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
      </div>
    </div>
  </div>
</body>
</html>
