<?php
// admin/dashboard.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) Veritabanından KPI değerlerini çek
$today = date('Y-m-d');

// Bugünkü sipariş sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayOrders = (int)$stmt->fetchColumn();

// Toplam sipariş sayısı
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Bugünkü gelir
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayRevenue = (float)$stmt->fetchColumn();

// Toplam gelir
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();

// Son 7 günde tamamlanan siparişlerden top 5 ürünü getir
$topProdStmt = $pdo->prepare("
  SELECT 
    p.id,
    p.title,
    SUM(oi.qty * oi.unit_price) AS revenue,
    SUM(oi.qty)               AS total_qty
  FROM order_items oi
  JOIN orders o   ON oi.order_id    = o.id
  JOIN products p ON oi.product_id  = p.id
  WHERE o.status = 'completed'
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY p.id, p.title
  ORDER BY revenue DESC
  LIMIT 5
");
$topProdStmt->execute();
$topProducts = $topProdStmt->fetchAll(PDO::FETCH_ASSOC);


// 2) Son 6 aylık aylık veri (grafik için)
$months = [];
$orderCounts = [];
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("first day of -$i months");
    $label = $dt->format('Y-m');
    $months[] = $label;
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt, IFNULL(SUM(total_amount),0) AS rev
         FROM orders
         WHERE DATE_FORMAT(created_at, '%Y-%m') = ?"
    );
    $stmt->execute([$label]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $orderCounts[] = (int)$row['cnt'];
    $revenueData[] = (float)$row['rev'];
}
?>

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

<div class="content p-4">
  <h2>Dashboard</h2>

  <!-- KPI Kartları -->
  <div class="container-fluid pt-4 px-4">
    <div class="row g-4">
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-shopping-cart fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Bugün Sipariş</p>
            <h6 class="mb-0"><?php echo $todayOrders; ?></h6>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-list fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Toplam Sipariş</p>
            <h6 class="mb-0"><?php echo $totalOrders; ?></h6>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-dollar-sign fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Bugünkü Gelir</p>
            <h6 class="mb-0">₺<?php echo number_format($todayRevenue,2,',','.'); ?></h6>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-chart-line fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Toplam Gelir</p>
            <h6 class="mb-0">₺<?php echo number_format($totalRevenue,2,',','.'); ?></h6>
          </div>
        </div>
      </div>
    </div>
  </div>
  

  <!-- Grafikler -->
  <div class="container-fluid pt-4 px-4">
    <div class="row g-4">
      <div class="col-sm-12 col-xl-6">
        <div class="bg-light text-center rounded p-4">
          <h6 class="mb-3">Aylık Sipariş Trend (Son 6 Ay)</h6>
          <canvas id="ordersChart"></canvas>
        </div>
      </div>
      <div class="col-sm-12 col-xl-6">
        <div class="bg-light text-center rounded p-4">
          <h6 class="mb-3">Aylık Gelir Trend (Son 6 Ay)</h6>
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="container-fluid pt-4 px-4">
  <div class="bg-light rounded p-4">
    <h6 class="mb-4">Son 7 Günde En Çok Satılan 5 Ürün</h6>
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Ürün</th>
            <th>Satılan Adet</th>
            <th>Toplam Gelir</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($topProducts): foreach ($topProducts as $i => $tp): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($tp['title']) ?></td>
            <td><?= $tp['total_qty'] ?></td>
            <td>₺<?= number_format($tp['revenue'],2,',','.') ?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr>
            <td colspan="4" class="text-center">Henüz veri yok.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


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



</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const months = <?php echo json_encode($months); ?>;
const orderCounts = <?php echo json_encode($orderCounts); ?>;
const revenueData = <?php echo json_encode($revenueData); ?>;

new Chart(document.getElementById('ordersChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Sipariş Adedi',
            data: orderCounts,
            fill: false,
            tension: 0.1
        }]
    }
});

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Gelir (₺)',
            data: revenueData,
            fill: false,
            tension: 0.1
        }]
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
