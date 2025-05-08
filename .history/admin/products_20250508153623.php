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

  // 3.a) Eğer bu ürüne ait order_items varsa, silme
  $chk = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
  $chk->execute([$id]);
  if ((int)$chk->fetchColumn() > 0) {
      // Mesajı session ile taşıyıp ekranda gösterebilirsiniz
      $_SESSION['error'] = 'Bu ürün daha önceki siparişlerde kullanıldığı için silinemez.';
  } else {
      // 3.b) Dosyaları sil
      $q = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
      $q->execute([$id]);
      while ($img = $q->fetch(PDO::FETCH_ASSOC)) {
          @unlink(__DIR__ . '/../public/' . $img['image_path']);
      }
      // 3.c) Kayıtları sil
      $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
      $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
      $_SESSION['success'] = 'Ürün başarıyla silindi.';
  }

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
  <?php if(!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>
<?php if(!empty($_SESSION['success'])): ?>
  <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="mt-5 bg-light rounded p-4">
  <h6 class="mb-4">Toplu Ürün Yükleme (XML veya CSV)</h6>
  <form method="post" enctype="multipart/form-data" action="import_xml.php">
    <div class="mb-3">
      <input 
        type="file" 
        name="upload_file" 
        accept=".xml,.csv" 
        class="form-control" 
        required>
    </div>
    <button type="submit" class="btn btn-success">Yükle ve İçe Aktar</button>
  </form>
</div>


    <!-- Form -->
    <div class="col-lg-112 mx-auto">
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

    <?php
// Sayfa numarası ve sayfa başına kayıt
$page    = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10,20,50])
           ? (int)$_GET['per_page'] : 10;

// Toplam ürün adedini al
$totalStmt  = $pdo->query("SELECT COUNT(*) FROM products");
$totalCount = (int)$totalStmt->fetchColumn();

// Toplam sayfa
$totalPages = (int)ceil($totalCount / $perPage);

// Offset hesapla
$offset = ($page - 1) * $perPage;

// Ürünleri limit+offset ile çek
$prodStmt = $pdo->prepare("
  SELECT 
    p.*, 
    c.name AS category_name,
    (SELECT image_path 
       FROM product_images 
       WHERE product_id = p.id 
       ORDER BY id 
       LIMIT 1
    ) AS thumb
  FROM products p
  LEFT JOIN categories c ON p.category_id = c.id
  ORDER BY p.created_at DESC
  LIMIT :limit OFFSET :offset
");
$prodStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$prodStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$prodStmt->execute();
$allProds = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
?>


    <!-- Liste -->
    <!-- SAYFA BAŞINA SEÇ -->
<form method="get" class="d-flex align-items-center mb-3">
  <label for="perPageSelect" class="me-2 mb-0">Göster:</label>
  <select id="perPageSelect" name="per_page" class="form-select form-select-sm me-3" 
          style="width:auto" onchange="this.form.submit()">
    <?php foreach([10,20,50] as $n): ?>
      <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>>
        <?= $n ?> / sayfa
      </option>
    <?php endforeach; ?>
  </select>
  <!-- sayfa numarasını koru -->
  <input type="hidden" name="page" value="1">
</form>

<!-- TABLO BURADA -->
<div class="table-responsive">
  <table class="table table-bordered table-hover mb-0">
    <!-- ... thead ... -->
    <tbody>
      <?php if($allProds): foreach($allProds as $p): ?>
      <tr>
        <!-- ... ürün satırları ... -->
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="7" class="text-center">Ürün bulunamadı.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- SAYFALAMA -->
<nav class="mt-3">
  <ul class="pagination justify-content-center mb-0">
    <!-- Önceki sayfa -->
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?>">«</a>
    </li>
    <!-- Sayfa numaraları -->
    <?php for($p=1; $p<=$totalPages; $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $p ?>&per_page=<?= $perPage ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
    <!-- Sonraki sayfa -->
    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?>">»</a>
    </li>
  </ul>
</nav>


  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
