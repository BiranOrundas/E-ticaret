<?php
// admin/dashboard.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) Veritabanından KPI değerlerini çek
$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayOrders = (int)$stmt->fetchColumn();

$totalOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayRevenue = (float)$stmt->fetchColumn();

$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();
?>

<div class="content">
  <!-- KPI Kartları -->
  <div class="container-fluid pt-4 px-4">
    <div class="row g-4">
      <!-- Today Orders -->
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-shopping-cart fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Bugünkü Sipariş</p>
            <h6 class="mb-0"><?= $todayOrders ?></h6>
          </div>
        </div>
      </div>
      <!-- Total Orders -->
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-list fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Toplam Sipariş</p>
            <h6 class="mb-0"><?= $totalOrders ?></h6>
          </div>
        </div>
      </div>
      <!-- Today Revenue -->
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-dollar-sign fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Bugünkü Gelir</p>
            <h6 class="mb-0">₺<?= number_format($todayRevenue,2,',','.') ?></h6>
          </div>
        </div>
      </div>
      <!-- Total Revenue -->
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-chart-line fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Toplam Gelir</p>
            <h6 class="mb-0">₺<?= number_format($totalRevenue,2,',','.') ?></h6>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2) Son 5 siparişi çek -->
  <?php
  $recentStmt = $pdo->query("
    SELECT 
      o.id, DATE_FORMAT(o.created_at, '%d.%m.%Y %H:%i') AS created_at,
      u.name AS customer_name, o.total_amount, o.status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
  ");
  $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
  ?>

  <!-- Son 5 Sipariş Tablosu -->
  <div class="container-fluid pt-4 px-4">
    <div class="card">
      <div class="card-header">Son 5 Sipariş</div>
      <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Tarih</th>
              <th>Müşteri</th>
              <th>Toplam</th>
              <th>Durum</th>
              <th>Detay</th>
            </tr>
          </thead>
          <tbody>
            <?php if($recentOrders): ?>
              <?php foreach($recentOrders as $o): ?>
              <tr>
                <td><?= $o['id'] ?></td>
                <td><?= $o['created_at'] ?></td>
                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                <td>₺<?= number_format($o['total_amount'],2,',','.') ?></td>
                <td><?= htmlspecialchars(ucfirst($o['status'])) ?></td>
                <td>
                  <a href="order_items.php?order_id=<?= $o['id'] ?>"
                     class="btn btn-sm btn-info">Detay</a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center">Hiç sipariş bulunamadı.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- … Diğer paneller (grafikler vs.) … -->

</div>

<?php
require_once __DIR__ . '/includes/footer.php';
