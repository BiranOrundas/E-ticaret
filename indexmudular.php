<?php
// public/index.php

// 1) PDO bağlantısı
require_once __DIR__ . '/../config/config.php';

// 2) Fonksiyonları yükle
require_once __DIR__ . '/includes/functions.php';

// 3) Header & Navbar
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';

// 4) Ana içerik
$featured = getFeaturedProducts(8);
?>

<main class="container row">
  <h1>Öne Çıkan Ürünler</h1>
  <div class="products-grid col-3">
    <?php foreach($featured as $p): ?>
      <div class="product-card">
        <a href="single.php?id=<?= $p['id'] ?>">
          <img src="./<?= htmlspecialchars($p['thumb'] ?? 'productimg/no_image.png') ?>"
               alt="<?= htmlspecialchars($p['title']) ?>">
          <h2><?= htmlspecialchars($p['title']) ?></h2>
          <p class="price">₺<?= number_format($p['price'],2,',','.') ?></p>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<?php
// 5) Footer
include __DIR__ . '/includes/footer.php';
