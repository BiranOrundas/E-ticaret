<?php
// admin/products.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) Form işlemleri: Ekle / Düzenle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = (float) $_POST['price'];
    $stock       = (int) $_POST['stock'];
    $category_id = $_POST['category_id'] ?: null;

    // Resim yükleme
    $image_path = null;
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $filename = uniqid('prd_') . '.' . $ext;
            $target   = __DIR__ . '/../public/img/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = 'img/' . $filename;
            }
        }
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products
            (title, description, price, stock, category_id, image_path)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $price, $stock, $category_id, $image_path]);
    }

    if ($action === 'edit') {
        $id     = (int) $_POST['id'];
        $fields = ['title = ?', 'description = ?', 'price = ?', 'stock = ?', 'category_id = ?'];
        $params = [$title, $description, $price, $stock, $category_id];
        if ($image_path) {
            $fields[] = 'image_path = ?';
            $params[] = $image_path;
        }
        $params[] = $id;
        $sql = 'UPDATE products SET ' . implode(", ", $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    header('Location: products.php');
    exit;
}

// 2) Silme işlemi
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_id']]);
    header('Location: products.php');
    exit;
}

// 3) Kategorileri çek (dropdown)
$catStmt    = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Düzenleme için seçili ürünü al
$editProd = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $editProd = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 5) Ürünleri çek liste için
$prodStmt = $pdo->query(
    "SELECT p.*, c.name AS category_name
      FROM products p
      LEFT JOIN categories c ON p.category_id = c.id
      ORDER BY p.created_at DESC"
);
$allProds = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content p-4">
  <h2>Ürün Yönetimi</h2>

  <div class="row g-4">
    <!-- Ekle / Düzenle Formu -->
    <div class="col-sm-12 col-xl-6">
      <div class="bg-light rounded h-100 p-4">
        <h6 class="mb-4"><?= $editProd ? 'Ürünü Düzenle' : 'Yeni Ürün Ekle' ?></h6>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?= $editProd ? 'edit' : 'add' ?>">
          <?php if($editProd): ?>
          <input type="hidden" name="id" value="<?= $editProd['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Başlık</label>
            <input type="text" name="title" class="form-control" required
              value="<?= htmlspecialchars($editProd['title'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProd['description'] ?? '') ?></textarea>
          </div>

          <div class="row mb-3">
            <div class="col-sm-4">
              <label class="form-label">Fiyat</label>
              <input type="number" step="0.01" name="price" class="form-control" required
                value="<?= htmlspecialchars($editProd['price'] ?? '') ?>">
            </div>
            <div class="col-sm-4">
              <label class="form-label">Stok</label>
              <input type="number" name="stock" class="form-control" required
                value="<?= htmlspecialchars($editProd['stock'] ?? '') ?>">
            </div>
            <div class="col-sm-4">
              <label class="form-label">Kategori</label>
              <select name="category_id" class="form-select">
                <option value="">— Seçiniz —</option>
                <?php foreach($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= $editProd && $cat['id']==$editProd['category_id'] ? 'selected' : '' ?> >
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Ürün Resmi</label>
            <?php if(!empty($editProd['image_path'])): ?>
            <div class="mb-2">
              <img src="../public/<?= htmlspecialchars($editProd['image_path']) ?>" 
                   style="max-width:120px; height:auto;" alt="">
            </div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control">
          </div>

          <button type="submit" class="btn btn-primary">
            <?= $editProd ? 'Güncelle' : 'Ekle' ?>
          </button>
          <?php if($editProd): ?>
          <a href="products.php" class="btn btn-secondary">İptal</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Ürün Listesi -->
    <div class="col-sm-12 col-xl-6">
      <div class="bg-light rounded h-100 p-4">
        <h6 class="mb-4">Ürün Listesi</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Resim</th>
                <th>Başlık</th>
                <th>Kategori</th>
                <th>Fiyat</th>
                <th>Stok</th>
                <th>İşlem</th>
              </tr>
            </thead>
            <tbody>
              <?php if($allProds): foreach($allProds as $p): ?>
              <tr>
                <td><?= $p['id'] ?></td>
                <td>
                  <?php if(!empty($p['image_path'])): ?>
                  <img src="../public/<?= htmlspecialchars($p['image_path']) ?>" 
                       style="max-width:80px; height:auto;" alt="">
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td>₺<?= number_format($p['price'],2,',','.') ?></td>
                <td><?= $p['stock'] ?></td>
                <td>
                  <a href="?edit_id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                  <a href="?delete_id=<?= $p['id'] ?>" 
                     onclick="return confirm('Silmek istediğinize emin misiniz?')"
                     class="btn btn-sm btn-danger">Sil</a>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="7" class="text-center">Ürün bulunamadı.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
