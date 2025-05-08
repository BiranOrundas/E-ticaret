<?php
// admin/products.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) Form işlemleri: ekle / düzenle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... Mevcut ekleme/güncelleme ve görsel yükleme kodu buraya eklenir ...
    // (Kısa tutmak için ayrıntıyı koruyun)
}

// 2) Silme işlemi
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    // Eğer sipariş kalemlerinde kullanıldıysa silme
    $chk = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
    $chk->execute([$id]);
    if ((int)$chk->fetchColumn() > 0) {
        $_SESSION['error'] = 'Bu ürün daha önceki siparişlerde kullanıldığı için silinemez.';
    } else {
        // Görselleri sil
        $q = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $q->execute([$id]);
        while ($img = $q->fetch(PDO::FETCH_ASSOC)) {
            @unlink(__DIR__ . '/../public/' . $img['image_path']);
        }
        // Veritabanından sil
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $_SESSION['success'] = 'Ürün başarıyla silindi.';
    }
    header('Location: products.php');
    exit;
}

// 3) Kategorileri çek
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 4) Düzenleme için seçili ürün ve görsellerini al
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

// 5) Pagination ve filtre ayarları
$page    = isset($_GET['page']) && (int)$_GET['page']>0 ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10,20,50]) ? (int)$_GET['per_page'] : 10;
$sort    = $_GET['sort'] ?? 'date_desc';

switch ($sort) {
    case 'price_asc':  $orderBy = 'p.price ASC'; break;
    case 'price_desc': $orderBy = 'p.price DESC'; break;
    case 'alpha':      $orderBy = 'p.title ASC'; break;
    default:           $orderBy = 'p.created_at DESC'; break;
}

// Toplam kayıt ve sayfa sayısı
$totalCount  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPages  = (int)ceil($totalCount/$perPage);
$offset      = ($page-1)*$perPage;

// 6) Ürünleri çek
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name,
      (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY id LIMIT 1) AS thumb
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$allProds = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content p-4">
  <h2>Ürün Yönetimi</h2>
  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <!-- Filtre ve Pagination -->
  <div class="d-flex mb-3 align-items-center">
    <form method="get" class="d-flex align-items-center">
      <label class="me-2 mb-0">Göster:</label>
      <select name="per_page" class="form-select form-select-sm me-3" style="width:auto" onchange="this.form.submit()">
        <?php foreach([10,20,50] as $n): ?>
          <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?> / sayfa</option>
        <?php endforeach; ?>
      </select>
      <label class="me-2 mb-0">Sırala:</label>
      <select name="sort" class="form-select form-select-sm me-3" style="width:auto" onchange="this.form.submit()">
        <option value="date_desc" <?= $sort==='date_desc'?'selected':'' ?>>Son eklenen</option>
        <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Fiyat ↑</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Fiyat ↓</option>
        <option value="alpha" <?= $sort==='alpha'?'selected':'' ?>>A → Z</option>
      </select>
      <!-- hidden page =1 -->
      <input type="hidden" name="page" value="1">
    </form>
  </div>

  <!-- Ürün Listesi -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover mb-0">
      <thead>
        <tr>
          <th>#</th><th>Resim</th><th>Başlık</th><th>Kategori</th><th>Fiyat</th><th>Stok</th><th>İşlem</th>
        </tr>
      </thead>
      <tbody>
        <?php if($allProds): foreach($allProds as $idx=>$p): ?>
        <tr>
          <td><?= $offset + $idx + 1 ?></td>
          <td>
            <?php if($p['thumb']): ?>
              <img src="../public/<?= htmlspecialchars($p['thumb']) ?>" style="width:50px;height:auto;object-fit:cover;">
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?= htmlspecialchars($p['title']) ?></td>
          <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
          <td>₺<?= number_format($p['price'],2,',','.') ?></td>
          <td><?= $p['stock'] ?></td>
          <td>
            <a href="?edit_id=<?= $p['id'] ?>&page=<?= $page ?>&per_page=<?= $perPage ?>&sort=<?= $sort ?>" class="btn btn-sm btn-warning">Düzenle</a>
            <a href="?delete_id=<?= $p['id'] ?>&page=<?= $page ?>&per_page=<?= $perPage ?>&sort=<?= $sort ?>"
               onclick="return confirm('Silmek istediğinize emin misiniz?')" class="btn btn-sm btn-danger">Sil</a>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" class="text-center">Ürün bulunamadı.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Sayfalama -->
  <nav class="mt-3">
    <ul class="pagination justify-content-center mb-0">
      <li class="page-item <?= $page<=1?'disabled':'' ?>">
        <a class="page-link" href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?>&sort=<?= $sort ?>">«</a>
      </li>
      <?php for($p=1; $p<=$totalPages; $p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>">
          <a class="page-link" href="?page=<?= $p ?>&per_page=<?= $perPage ?>&sort=<?= $sort ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
        <a class="page-link" href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?>&sort=<?= $sort ?>">»</a>
      </li>
    </ul>
  </nav>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
