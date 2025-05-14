<?php
// public/index.php
session_start();

// 1) DB bağlantısı ve fonksiyonları yükle
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/functions.php';

// 2) Dinamik verileri al
$categories     = getCategories();
$featured       = getFeaturedProducts(8);
$allProducts    = getAllProducts();           // "All" tab için
$byCategory     = [];
foreach ($categories as $cat) {
    $byCategory[$cat['id']] = getProductsByCategory($cat['id'], 8);
}
?>
<!DOCTYPE html>
<html lang="tr">
<body>

  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Öne Çıkan Ürünler -->
  <section id="featured-products" class="product-store padding-large">
    <div class="container">
      <div class="section-header d-flex align-items-center justify-content-between">
        <h2 class="section-title">Öne Çıkan Ürünler</h2>
        <a href="shop.php" class="btn-wrap">Tüm Ürünleri Gör <i class="icon icon-arrow-io"></i></a>
      </div>
      <div class="swiper product-swiper overflow-hidden">
        <div class="swiper-wrapper">
          <?php foreach($featured as $p): ?>
          <div class="swiper-slide">
            <div class="product-item">
              <div class="image-holder">
                <img src="<?= htmlspecialchars($p['thumb'] ?? 'productimg/no_image.png') ?>"
                     alt="<?= htmlspecialchars($p['title']) ?>"
                     class="product-image"
                     onerror="this.src='productimg/no_image.png'">
              </div>
              <div class="product-detail">
                <h3 class="product-title">
                  <a href="single-product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a>
                </h3>
                <span class="item-price text-primary">
                  ₺<?= number_format($p['price'],2,',','.') ?>
                </span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="swiper-pagination"></div>
      </div>
    </div>
  </section>

  <!-- En Çok Satanlar (Kategoriye Göre Sekmeler) -->
  <section id="selling-products" class="product-store bg-light-grey padding-large">
    <div class="container">

      <div class="section-header">
        <h2 class="section-title">Kategorilere Göre</h2>
      </div>

      <!-- Tab başlıkları -->
      <ul class="tabs list-unstyled">
        <li data-tab-target="#all" class="active tab">Tümü</li>
        <?php foreach($categories as $cat): ?>
          <li data-tab-target="#cat-<?= $cat['id'] ?>" class="tab">
            <?= htmlspecialchars($cat['name']) ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Tab içerikleri -->
      <div class="tab-content">
        <!-- “Tümü” sekmesi -->
        <div id="all" data-tab-content class="active">
          <div class="row d-flex flex-wrap">
            <?php foreach($allProducts as $p): ?>
            <div class="product-item col-lg-3 col-md-6 col-sm-6">
              <div class="image-holder">
                <img src="<?= htmlspecialchars($p['thumb'] ?? 'productimg/no_image.png') ?>"
                     alt="<?= htmlspecialchars($p['title']) ?>"
                     class="product-image"
                     onerror="this.src='productimg/no_image.png'">
              </div>
              <div class="product-detail">
                <h3 class="product-title">
                  <a href="single-product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a>
                </h3>
                <div class="item-price text-primary">
                  ₺<?= number_format($p['price'],2,',','.') ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Her bir kategori için ayrı sekme -->
        <?php foreach($categories as $cat): ?>
        <div id="cat-<?= $cat['id'] ?>" data-tab-content>
          <div class="row d-flex flex-wrap">
            <?php if (!empty($byCategory[$cat['id']])): ?>
              <?php foreach($byCategory[$cat['id']] as $p): ?>
              <div class="product-item col-lg-3 col-md-6 col-sm-6">
                <div class="image-holder">
                  <img src="<?= htmlspecialchars($p['thumb'] ?? 'productimg/no_image.png') ?>"
                       alt="<?= htmlspecialchars($p['title']) ?>"
                       class="product-image"
                       onerror="this.src='productimg/no_image.png'">
                </div>
                <div class="product-detail">
                  <h3 class="product-title">
                    <a href="single-product.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a>
                  </h3>
                  <div class="item-price text-primary">
                    ₺<?= number_format($p['price'],2,',','.') ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-center">Bu kategoride henüz ürün yok.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>
  </section>

  <?php include __DIR__ . '/footer.php'; ?>

  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
</body>
</html>
