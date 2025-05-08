<?php
// admin/products.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 0) Silme işlemi: ürün sil
if (isset($_GET['delete_id'])) {
    $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $del->execute([(int)$_GET['delete_id']]);
    header('Location: products.php');
    exit;
}

// 0b) Silme işlemi: ürün fotoğrafı sil
if (isset($_GET['delete_img'], $_GET['edit_id'])) {
    $imgId    = (int)$_GET['delete_img'];
    $productId= (int)$_GET['edit_id'];
    // Dosyayı diskte de silebilirsiniz:
    $p = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $p->execute([$imgId]);
    if ($path = $p->fetchColumn()) {
        @unlink(__DIR__ . '/../public/' . $path);
    }
    $del = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
    $del->execute([$imgId]);
    header("Location: products.php?edit_id={$productId}");
    exit;
}

// 1) Form işlemleri: ekle / düzenle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = (float) $_POST['price'];
    $stock       = (int) $_POST['stock'];
    $category_id = $_POST['category_id'] ?: null;

    if ($action === 'add') {
        // Ürünü ekle
        $stmt = $pdo->prepare("
          INSERT INTO products (title, description, price, stock, category_id)
          VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $price, $stock, $category_id]);
        $productId = $pdo->lastInsertId();
    } else {
        // Ürünü güncelle
        $productId = (int) $_POST['id'];
        $stmt = $pdo->prepare("
          UPDATE products
          SET title = ?, description = ?, price = ?, stock = ?, category_id = ?
          WHERE id = ?
        ");
        $stmt->execute([$title, $description, $price, $stock, $category_id, $productId]);
    }

    // 1.b) Fotoğrafları yükle
    if (!empty($_FILES['images']['tmp_name'])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if ($tmp && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $ext      = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid('prd_') . '.' . $ext;
                move_uploaded_file($tmp, __DIR__ . '/../public/img/' . $filename);
                $pi = $pdo->prepare("
                  INSERT INTO product_images (product_id, image_path)
                  VALUES (?, ?)
                ");
                $pi->execute([$productId, 'img/' . $filename]);
            }
        }
    }

    header('Location: products.php');
    exit;
}

// 2) Kategorileri çek (dropdown için)
$categories = $pdo
    ->query("SELECT id, name FROM categories ORDER BY name")
    ->fetchAll(PDO::FETCH_ASSOC);

// 3) Düzenleme için seçili ürünü al
$editProd = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $editProd = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4) Tüm ürünleri çek (kategori adıyla JOIN’leyerek)
$allProds = $pdo
    ->query("
      SELECT p.*, c.name AS category_name
      FROM products p
      LEFT JOIN categories c ON p.category_id = c.id
      ORDER BY p.created_at DESC
    ")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content p-4">
  <h2>Ürün Yönetimi</h2>

  <!-- Ekle / Düzenle Formu -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= $editProd ? 'Ürünü Düzenle' : 'Yeni Ürün Ekle' ?></h5>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editProd ? 'edit' : 'add' ?>">
        <?php if ($editProd): ?>
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
          <div class="col-md-4">
            <label class="form-label">Fiyat</label>
            <input type="number" step="0.01" name="price" class="form-control" required
                   value="<?= htmlspecialchars($editProd['price'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stok</label>
            <input type="number" name="stock" class="form-control" required
                   value="<?= htmlspecialchars($editProd['stock'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Kategori</label>
            <select name="category_id" class="form-select">
              <option value="">— Seçiniz —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= ($editProd && $cat['id']==$editProd['category_id'])?'selected':''?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Ürün Fotoğrafları</label>
          <?php if ($editProd): 
            // mevcut fotoğrafları çek
            $imgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
            $imgs->execute([$editProd['id']]);
            $images = $imgs->fetchAll(PDO::FETCH_ASSOC);
          ?>
            <div class="mb-2 d-flex flex-wrap">
              <?php foreach ($images as $img): ?>
                <div class="position-relative me-2 mb-2">
                  <img src="../public/<?= htmlspecialchars($img['image_path']) ?>" style="max-width:100px">
                  <a href="?edit_id=<?= $editProd['id'] ?>&delete_img=<?= $img['id'] ?>"
                     class="btn btn-sm btn-danger position-absolute top-0 end-0"
                     onclick="return confirm('Fotoğrafı silmek istediğinize emin misiniz?')">
                    &times;
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <input type="file" name="images[]" class="form-control" multiple>
          
        </div>

        <button class="btn btn-success"><?= $editProd ? 'Güncelle' : 'Ekle' ?></button>
        <?php if ($editProd): ?>
          <a href="products.php" class="btn btn-secondary ms-2">İptal</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Ürün Listesi -->
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Mevcut Ürünler</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Başlık</th>
              <th>Resim</th>
              <th>Kategori</th>
              <th>Fiyat</th>
              <th>Stok</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($allProds): foreach ($allProds as $p): ?>
            <?php
              // ilk fotoğraf thumbnail
              $t = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? LIMIT 1");
              $t->execute([$p['id']]);
              $thumb = $t->fetchColumn();
            ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['title']) ?></td>
              <td>
                <?php if ($thumb): ?>
                  <img src="../public/<?= htmlspecialchars($thumb) ?>" style="max-width:50px">
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
              <td><?= number_format($p['price'],2,',','.') ?></td>
              <td><?= $p['stock'] ?></td>
              <td>
                <a href="?edit_id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                <a href="?delete_id=<?= $p['id'] ?>"
                   onclick="return confirm('Silmek istediğinize emin misiniz?')"
                   class="btn btn-sm btn-danger">Sil</a>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center">Henüz ürün yok.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
