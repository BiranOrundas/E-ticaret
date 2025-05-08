<?php
// admin/orders.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) POST: Sipariş durumu güncelle ve müşteri bilgilendir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    // 1.a) Veritabanında güncelle
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // 1.b) Müşteriye e-posta bildirimi
    // Kullanıcının e-postasını çekelim
    $uStmt = $pdo->prepare("
      SELECT u.email, u.name 
      FROM orders o 
      JOIN users u ON o.user_id = u.id 
      WHERE o.id = ?
    ");
    $uStmt->execute([$orderId]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $to      = $user['email'];
        $subject = "Sipariş #{$orderId} Durum Güncellemesi";
        $message = "Merhaba {$user['name']},\n\n"
                 . "Siparişinizin durumu “{$newStatus}” olarak güncellenmiştir.\n"
                 . "Teşekkürler.";
        $headers = "From: no-reply@yourshop.com\r\n";
        // gerçek projede mail() değil bir kütüphane kullanın
        @mail($to, $subject, $message, $headers);
    }

    header('Location: orders.php?updated=1');
    exit;
}

// 2) GET parametre: bildirim sonucu
$notice = '';
if (isset($_GET['updated'])) {
    $notice = 'Sipariş durumu başarıyla güncellendi ve müşteriye bildirildi.';
}

// 3) Veritabanından tüm siparişleri çek
$stmt = $pdo->query("
  SELECT 
    o.id, o.created_at, o.total_amount, o.status,
    u.name AS customer_name, u.email AS customer_email
  FROM orders o
  JOIN users u ON o.user_id = u.id
  ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content p-4">
  <h2>Sipariş Yönetimi</h2>

  <?php if($notice): ?>
    <div class="alert alert-success"><?= htmlspecialchars($notice) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Tarih</th>
              <th>Müşteri</th>
              <th>E-posta</th>
              <th>Toplam</th>
              <th>Durum</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php if($orders): foreach($orders as $o): ?>
            <tr>
              <td><?= $o['id'] ?></td>
              <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['customer_email']) ?></td>
              <td>₺<?= number_format($o['total_amount'],2,',','.') ?></td>
              <td>
                <form method="post" class="d-flex align-items-center">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <select name="status" class="form-select form-select-sm me-2">
                    <?php 
                      $states = ['pending'=>'Hazırlanıyor','processing'=>'Kargoya Verildi','completed'=>'Tamamlandı','Tamamlandı'=>'İptal'];
                      foreach($states as $code=>$label): 
                    ?>
                      <option value="<?= $code ?>"
                        <?= $o['status']===$code?'selected':'' ?>>
                        <?= $label ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm btn-primary">Güncelle</button>
                </form>
              </td>
              <td>
                <a href="order_items.php?order_id=<?= $o['id'] ?>"
                   class="btn btn-sm btn-info">Detay</a>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center">Hiç sipariş bulunamadı.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
