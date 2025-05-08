<?php
// admin/products.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) Form işlemleri: ekle / düzenle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action         = $_POST['action'];
    $title          = trim($_POST['title']);
    $description    = trim($_POST['description']);
    $price          = (float) $_POST['price'];
    $stock          = (int) $_POST['stock'];
    $category_id    = $_POST['category_id'] ?: null;
    $main_image_id  = $_POST['main_image_id'] ?: null;

    // Ürün ekle / güncelle
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products 
            (title, description, price, stock, category_id, main_image_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $price, $stock, $category_id, $main_image_id]);
        $prodId = $pdo->lastInsertId();
    } else {
        $prodId = (int) $_POST['id'];
        $fields = ['title = ?', 'description = ?', 'price = ?', 'stock = ?', 'category_id = ?', 'main_image_id = ?'];
        $params = [$title, $description, $price, $stock, $category_id, $main_image_id, $prodId];
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
    }

    // 2) Resim(ler) yükleme
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['error'] as $i => $err) {
            if ($err === UPLOAD_ERR_OK) {
                $tmp  = $_FILES['images']['tmp_name'][$i];
                $orig = $_FILES['images']['name'][$i];
                $ext  = pathinfo($orig, PATHINFO_EXTENSION);
                $name = uniqid('primg_') . "." . $ext;
                $dest = __DIR__ . '/../public/productimg/' . $name;
                move_uploaded_file($tmp, $dest);
                $path = 'productimg/' . $name;
                // Veritabanına kaydet
                $ins = $pdo->prepare("INSERT INTO product_images (product_id, image_path, created_at) VALUES (?, ?, NOW())");
                $ins->execute([$prodId, $path]);
            }
        }
    }

    header('Location: products.php');
    exit;
}

// 3) Silme işlemi (ürün + ilişkili resimler)
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    // Dosyaları sil
    $q = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $q->execute([$id]);
    while ($img = $q->fetch(PDO::FETCH_ASSOC)) {
        @unlink(__DIR__ . '/../public/' . $img['image_path']);
    }
    // Kayıtları sil
    $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header('Location: products.php');
    exit;
}

// 4) Kategorileri çek
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 5) Düzenleme için seçili ürün ve görsellerini al
$editProd = null;
$images   = [];
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $editProd = $stmt->fetch(PDO::FETCH_ASSOC);
    $imgStmt = $pdo->prepare("SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY created_at ASC");
    $imgStmt->execute([$editProd['id']]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 6) Ürünleri ve main_image önizlemesini çek
$allProds = $pdo->query("SELECT p.*, c.name AS category_name, pi.image_path AS thumb 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN product_images pi ON p.main_image_id = pi.id 
    ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content p-4">
  <h2>Ürün Yönetimi</h2>
  <div class="row g-4">

    <!-- Form -->
    <div class="col-lg-6">
      <div class="bg-light rounded p-4">
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
                  <option value="<?= $cat['id'] ?>" <?= $editProd && $cat['id']==$editProd['category_id']?'selected':'' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Çoklu görsel yükleme -->
          <div class="mb-3">
            <label class="form-label">Ürün Görselleri</label>
            <input type="file" name="images[]" class="form-control" multiple>
          </div>

          <!-- Vitrin resmi seçimi -->
          <?php if($editProd): ?>
            <div class="mb-3">
              <label class="form-label">Vitrin Resmi</label>
              <select name="main_image_id" class="form-select">
                <option value="">— Seçiniz —</option>
                <?php foreach($images as $img): ?>
                  <option value="<?= $img['id'] ?>" <?= $img['id']==$editProd['main_image_id']?'selected':'' ?>>
                    ID#<?= $img['id'] ?> - <?= htmlspecialchars($img['image_path']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary"><?= $editProd ? 'Güncelle' : 'Ekle' ?></button>
          <?php if($editProd): ?><a href="products.php" class="btn btn-secondary ms-2">İptal</a><?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Liste -->
    <div class="col-lg-10 mx-auto">
      <div class="bg-light rounded p-4">
        <h6 class="mb-4">Ürün Listesi</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th><th>Resim</th><th>Başlık</th><th>Kategori</th><th>Fiyat</th><th>Stok</th><th>İşlem</th>
              </tr>
            </thead>
            <tbody>
              <?php if($allProds): foreach($allProds as $p): ?>
              <tr>
                <td><?= $p['id'] ?></td>
                <td>
                  <?php if($p['thumb']): ?>
                    <img src="../public/<?= htmlspecialchars($p['thumb']) ?>" style="width:50px;height:auto;object-fit:cover;">
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td>₺<?= number_format($p['price'],2,',','.') ?></td>
                <td><?= $p['stock'] ?></td>
                <td>
                  <a href="?edit_id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                  <a href="?delete_id=<?= $p['id'] ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')" class="btn btn-sm btn-danger">Sil</a>
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
