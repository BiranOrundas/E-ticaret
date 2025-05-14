<?php
// admin/signup.php
require_once __DIR__ . '/config.php';


include'/includes/header.php';
include'/includes/sidebar.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Basit validasyon
    if (!$name)   $errors[] = 'İsim (name) alanı gerekli.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
    if (strlen($password) < 6) $errors[] = 'Şifre en az 6 karakter olmalı.';

    // E-posta benzersiz mi?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Bu e-posta zaten kayıtlı.';
    }

    if (empty($errors)) {
        // Kullanıcı ekle (name column var)
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
           INSERT INTO users (name, email, password_hash, role)
        VALUES (?, ?, ?, 'admin')
        ");
        $stmt->execute([$name, $email, $hash]);
        header('Location: login.php?registered=1');
        exit;
    }
}
?>

<div class="content p-4">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-sm-8 col-md-6 col-lg-5">
        <div class="bg-light rounded p-4 p-sm-5 my-4 mx-3">
          <h3 class="mb-4">Admin Kaydı Oluştur</h3>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post">
            <div class="form-floating mb-3">
              <input type="text" name="name" class="form-control" id="floatingName" placeholder="John Doe"
                     value="<?= htmlspecialchars($name ?? '') ?>">
              <label for="floatingName">Ad Soyad</label>
            </div>
            <div class="form-floating mb-3">
              <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="name@example.com"
                     value="<?= htmlspecialchars($email ?? '') ?>">
              <label for="floatingEmail">E-posta</label>
            </div>
            <div class="form-floating mb-4">
              <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password">
              <label for="floatingPassword">Şifre</label>
            </div>
            <button class="btn btn-primary py-3 w-100 mb-3" type="submit">Kaydol</button>
            <p class="text-center mb-0">Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
