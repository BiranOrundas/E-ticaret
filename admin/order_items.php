<?php
// admin/order_items.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 0) order_id kontrolü
if (!isset($_GET['order_id'])) {
    header('Location: orders.php');
    exit;
}




$orderId = (int)$_GET['order_id'];

// 0.b) Kalem silme işlemi
if (isset($_GET['delete_item_id'])) {
    $delId = (int)$_GET['delete_item_id'];
    // 1) Kalemi sil
    $del = $pdo->prepare(
        "DELETE FROM order_items 
         WHERE id = ? AND order_id = ?"
    );
    $del->execute([$delId, $orderId]);

    // 2) Sipariş toplamını yeniden hesapla
    $upd = $pdo->prepare("
        UPDATE orders
           SET total_amount = (
             SELECT IFNULL(SUM(qty * unit_price),0)
               FROM order_items
              WHERE order_id = ?
           )
         WHERE id = ?
    ");
    $upd->execute([$orderId, $orderId]);

    // Yeniden yükle
    header("Location: order_items.php?order_id={$orderId}");
    exit;
}

// 1) Sipariş bilgilerini çek (müşteri adı + toplam)
$stmt = $pdo->prepare("
    SELECT o.total_amount, u.name AS customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo "<div class='alert alert-danger'>Sipariş bulunamadı.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 2) Ürün listesini çek (dropdown için)
$prodStmt = $pdo->query("SELECT id, title, price FROM products ORDER BY title");
$products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

// 3) POST ile yeni kalem ekleme
// 3) POST ile yeni kalem ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3.a) POST verilerini al
    $prodId    = (int) ($_POST['product_id']  ?? 0);
    $quantity  = (int) ($_POST['quantity']    ?? 0);
    $unitPrice = (float) ($_POST['unit_price'] ?? 0);

    // Basit doğrulama
    if ($prodId > 0 && $quantity > 0 && $unitPrice > 0) {
        // Kalemi ekle (variation_id sütunu yok, onu çıkardık)
        $ins = $pdo->prepare("
            INSERT INTO order_items
                (order_id, product_id, qty, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        $ins->execute([
            $orderId,
            $prodId,
            $quantity,
            $unitPrice
        ]);

        // Sipariş toplamını yeniden hesapla
        $upd = $pdo->prepare("
            UPDATE orders
               SET total_amount = (
                 SELECT SUM(qty * unit_price)
                   FROM order_items
                  WHERE order_id = ?
               )
             WHERE id = ?
        ");
        $upd->execute([$orderId, $orderId]);

        // Yeniden yükle
        header("Location: order_items.php?order_id=$orderId");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Lütfen tüm alanları doğru doldurun.</div>";
    }
}


// 4) Mevcut kalemleri çek
$itemsStmt = $pdo->prepare("
    SELECT 
      oi.id,
      p.title,
      oi.qty           AS quantity,        -- burada
      oi.unit_price,
      (oi.qty * oi.unit_price) AS line_total  -- burada
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="content p-4">
  <h2>Siparişle #<?= $orderId ?> Kalemleri</h2>
  <p>
    <strong>Müşteri:</strong> <?= htmlspecialchars($order['customer_name']) ?> &nbsp; 
    <strong>Toplam:</strong> ₺<?= number_format($order['total_amount'],2,',','.') ?>
  </p>

  <div class="card mb-4">
  <div class="card-header">Yeni Kalem Ekle</div>
  <div class="card-body">
    <form method="post" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Ürün</label>
        <select name="product_id" class="form-select" required>
          <option value="">— Seçiniz —</option>
          <?php foreach($products as $p): ?>
            <option 
              value="<?= $p['id'] ?>" 
              data-price="<?= $p['price'] ?>"
            >
              <?= htmlspecialchars($p['title']) ?> (₺<?= number_format($p['price'],2,',','.') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Adet</label>
        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Birim Fiyat</label>
        <input 
          type="number" 
          step="0.01" 
          name="unit_price" 
          class="form-control" 
          placeholder="₺" 
          readonly
        >
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-success w-100">Ekle</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const select  = document.querySelector('select[name="product_id"]');
  const priceIn = document.querySelector('input[name="unit_price"]');

  function updatePrice() {
    const opt = select.options[select.selectedIndex];
    priceIn.value = opt.dataset.price || '';
  }

  select.addEventListener('change', updatePrice);
  // Sayfa yüklendiğinde ilk seçiliye de uygula
  updatePrice();
});
</script>


  <div class="card">
    <div class="card-header">Siparişin Kalemleri</div>
    <div class="card-body table-responsive p-0">
      <table class="table table-bordered mb-0">
      <thead class="table-light">
  <tr>
    <th>ID</th>
    <th>Ürün</th>
    <th>Adet</th>
    <th>Birim Fiyat</th>
    <th>Tutar</th>
    <th>İşlemler</th> <!-- burası eklendi -->
  </tr>
</thead>

        <tbody>
        <?php if($orderItems): foreach($orderItems as $it): ?>
        <tr>
            <td><?= $it['id'] ?></td>
            <td><?= htmlspecialchars($it['title']) ?></td>
            <td><?= $it['quantity'] ?></td>
            <td>₺<?= number_format($it['unit_price'],2,',','.') ?></td>
            <td>₺<?= number_format($it['line_total'],2,',','.') ?></td>
            <td>
            <a
                href="?order_id=<?= $orderId ?>&delete_item_id=<?= $it['id'] ?>"
                class="btn btn-sm btn-danger"
                onclick="return confirm('Bu kalemi silmek istediğinize emin misiniz?')"
            >Sil</a>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" class="text-center">Kalem bulunamadı.</td></tr>
        <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
