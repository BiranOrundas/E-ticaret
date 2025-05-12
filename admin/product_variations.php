<?php
// admin/product_variations.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 0) product_id zorunlu
if (empty($_GET['product_id'])) {
    header('Location: products.php');
    exit;
}
$productId = (int)$_GET['product_id'];

// 1) Ürünü çek (başlık vs)
$stmt = $pdo->prepare("SELECT id, title FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo "<div class='alert alert-danger'>Ürün bulunamadı.</div>";
    require __DIR__ . '/includes/footer.php';
    exit;
}

// 2) POST ile ekle veya güncelle
if ($_SERVER['REQUEST_METHOD']==='POST') {
    // Distinguish add vs edit by hidden field:
    $action = $_POST['action'] ?? 'add';

    $name  = trim($_POST['variation_name']);
    $value = trim($_POST['variation_value']);
    $price = (float) $_POST['additional_price'];
    $stock = (int)   $_POST['stock'];

    if ($action==='add') {
        $ins = $pdo->prepare("
          INSERT INTO product_variations
            (product_id, variation_name, variation_value, additional_price, stock, created_at)
          VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$productId, $name, $value, $price, $stock]);
    }
    elseif ($action==='edit' && !empty($_POST['id'])) {
        $vid = (int)$_POST['id'];
        $upd = $pdo->prepare("
          UPDATE product_variations
             SET variation_name=?, variation_value=?, additional_price=?, stock=?
           WHERE id = ? AND product_id = ?
        ");
        $upd->execute([$name, $value, $price, $stock, $vid, $productId]);
    }

    header("Location: product_variations.php?product_id={$productId}");
    exit;
}

// 3) Silme işlemi
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM product_variations WHERE id = ? AND product_id = ?")
        ->execute([$delId, $productId]);
    header("Location: product_variations.php?product_id={$productId}");
    exit;
}

// 4) Düzenleme için tek varyasyon
$editVar = null;
if (isset($_GET['edit'])) {
    $ev = (int)$_GET['edit'];
    $vstmt = $pdo->prepare("SELECT * FROM product_variations WHERE id=? AND product_id=?");
    $vstmt->execute([$ev,$productId]);
    $editVar = $vstmt->fetch(PDO::FETCH_ASSOC);
}

// 5) Tüm varyasyonları çek
$vars = $pdo->prepare("
  SELECT id, variation_name, variation_value, additional_price, stock, created_at
    FROM product_variations
   WHERE product_id = ?
   ORDER BY created_at DESC
");
$vars->execute([$productId]);
$variations = $vars->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content p-4">
  <h2>“<?= htmlspecialchars($product['title']) ?>” Varyasyonları</h2>

  <!-- Liste -->
  <div class="card mb-4">
    <div class="card-header">Mevcut Varyasyonlar</div>
    <div class="card-body table-responsive p-0">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Özellik Adı</th>
            <th>Özellik Değeri</th>
            <th>Ek Fiyat</th>
            <th>Stok</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
        <?php if($variations): foreach($variations as $v): ?>
          <tr>
            <td><?= $v['id'] ?></td>
            <td><?= htmlspecialchars($v['variation_name']) ?></td>
            <td><?= htmlspecialchars($v['variation_value']) ?></td>
            <td>₺<?= number_format($v['additional_price'],2,',','.') ?></td>
            <td><?= $v['stock'] ?></td>
            <td>
              <a href="?product_id=<?= $productId ?>&edit=<?= $v['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
              <a href="?product_id=<?= $productId ?>&delete=<?= $v['id'] ?>"
                 onclick="return confirm('Silmek istediğinize emin misiniz?')"
                 class="btn btn-sm btn-danger">Sil</a>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center">Varyasyon bulunamadı.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ekle / Düzenle Formu -->
  <div class="card">
    <div class="card-header"><?= $editVar ? 'Varyasyonu Düzenle' : 'Yeni Varyasyon Ekle' ?></div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="action" value="<?= $editVar ? 'edit' : 'add' ?>">
        <?php if($editVar): ?>
          <input type="hidden" name="id" value="<?= $editVar['id'] ?>">
        <?php endif; ?>

        <div class="col-md-3">
          <label class="form-label">Özellik Adı</label>
          <input type="text" name="variation_name" class="form-control"
                 value="<?= htmlspecialchars($editVar['variation_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Özellik Değeri</label>
          <input type="text" name="variation_value" class="form-control"
                 value="<?= htmlspecialchars($editVar['variation_value'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Ek Fiyat</label>
          <input type="number" step="0.01" name="additional_price" class="form-control"
                 value="<?= htmlspecialchars($editVar['additional_price'] ?? '0.00') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Stok</label>
          <input type="number" name="stock" class="form-control"
                 value="<?= htmlspecialchars($editVar['stock'] ?? '0') ?>" min="0" required>
        </div>
        <div class="col-md-2 align-self-end">
          <button type="submit" class="btn btn-success w-100">
            <?= $editVar ? 'Güncelle' : 'Ekle' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
