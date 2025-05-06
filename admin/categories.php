<?php
// admin/categories.php

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) İşlemleri yakala: ekle, düzenle, sil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verileri al
    $name      = trim($_POST['name']);
    $parent_id = $_POST['parent_id'] ?: null;

    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        // Yeni kategori ekle
        $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
        $stmt->execute([$name, $parent_id]);
        header('Location: categories.php');
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        // Var olan kategoriyi güncelle
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $parent_id, $id]);
        header('Location: categories.php');
        exit;
    }
}

if (isset($_GET['delete_id'])) {
    // Kategori sil
    $delId = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$delId]);
    header('Location: categories.php');
    exit;
}

// 2) Kayıtları çek
$stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id IS NULL DESC, parent_id, name");
$allCats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3) Düzenleme için tek bir kategori al (isteğe bağlı)
$editCat = null;
if (isset($_GET['edit_id'])) {
    $eid = (int) $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$eid]);
    $editCat = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="content p-4">
  <h2>Kategori Yönetimi</h2>

  <!-- Ekle / Düzenle Formu -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">
        <?= $editCat ? 'Kategoriyi Düzenle' : 'Yeni Kategori Ekle' ?>
      </h5>
      <form method="post">
        <?php if ($editCat): ?>
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="add">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Kategori Adı</label>
          <input type="text" name="name" class="form-control" required
                 value="<?= $editCat ? htmlspecialchars($editCat['name']) : '' ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Üst Kategori</label>
          <select name="parent_id" class="form-select">
            <option value="">— Yok —</option>
            <?php foreach ($allCats as $cat): 
              // kendisini ya da alt kategorisini seçmeyi engelle
              if ($editCat && ($cat['id'] === $editCat['id'] || $cat['parent_id'] === $editCat['id'])) {
                continue;
              }
            ?>
              <option value="<?= $cat['id'] ?>"
                <?= $editCat && $cat['id']==$editCat['parent_id'] ? 'selected' : '' ?>>
                <?= str_repeat('  ', $cat['parent_id'] ? 1 : 0) . htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button class="btn btn-success">
          <?= $editCat ? 'Güncelle' : 'Ekle' ?>
        </button>
        <?php if ($editCat): ?>
          <a href="categories.php" class="btn btn-secondary">İptal</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Kategori Listesi -->
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Mevcut Kategoriler</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Adı</th>
              <th>Üst Kategori</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allCats as $cat): ?>
            <tr>
              <td><?= $cat['id'] ?></td>
              <td><?= htmlspecialchars($cat['name']) ?></td>
              <td>
                <?php
                  if ($cat['parent_id']) {
                    // parent adını bul
                    foreach ($allCats as $p) {
                      if ($p['id']==$cat['parent_id']) {
                        echo htmlspecialchars($p['name']);
                        break;
                      }
                    }
                  } else {
                    echo '—';
                  }
                ?>
              </td>
              <td>
                <a href="?edit_id=<?= $cat['id'] ?>" class="btn btn-sm btn-primary">Düzenle</a>
                <a href="?delete_id=<?= $cat['id'] ?>"
                   onclick="return confirm('Silmek istediğinizden emin misiniz?')"
                   class="btn btn-sm btn-danger">Sil</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($allCats)): ?>
            <tr><td colspan="4" class="text-center">Henüz kategori yok.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php
require_once __DIR__ . '/includes/footer.php';
