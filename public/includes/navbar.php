<header id="header">
  <nav class="primary-nav">
    <ul>
      <li><a href="index.php">Anasayfa</a></li>
      <?php foreach(getCategories() as $cat): ?>
        <li><a href="shop.php?cat=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
      <?php endforeach; ?>
      <li><a href="cart.php">Sepetim</a></li>
      <li><a href="login.php">Giriş</a></li>
    </ul>
  </nav>
</header>
