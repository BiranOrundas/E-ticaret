<?php
// admin/dashboard.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1) KPI Verilerini Hesapla
// Bugünün sipariş sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$todayOrders = (int)$stmt->fetchColumn();
// Toplam sipariş sayısı
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
// Bugünün gelir miktarı
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$todayRevenue = (float)$stmt->fetchColumn();
// Toplam gelir
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();

// 2) Son 6 Ay için Aylık Grafik Verisi
$labels = [];
$orderData = [];
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("-{$i} months");
    $labels[] = $dt->format('M Y');
    $month = $dt->format('Y-m');
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
         FROM orders
         WHERE DATE_FORMAT(created_at, '%Y-%m') = ?"
    );
    $stmt->execute([$month]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $orderData[] = (int)$row['cnt'];
    $revenueData[] = (float)$row['rev'];
}

// 3) Son 5 sipariş
$stmt = $pdo->query(
    "SELECT o.id, o.created_at, o.total_amount, o.status, u.name AS customer
     FROM orders o
     JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content p-4">
  <!-- KPI Kartları -->
  <div class="container-fluid pt-4 px-4">
    <div class="row g-4">
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-shopping-cart fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Günlük Sipariş</p>
            <h6 class="mb-0"><?= $todayOrders ?></h6>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-list fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Toplam Siparişler</p>
            <h6 class="mb-0"><?= $totalOrders ?></h6>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-dollar-sign fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Günlük Kazanç</p>
            <h6 class="mb-0">₺<?= number_format($todayRevenue,2,',','.') ?></h6>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-xl-3">
        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
          <i class="fa fa-chart-line fa-3x text-primary"></i>
          <div class="ms-3">
            <p class="mb-2">Toplam Kazanç</p>
            <h6 class="mb-0">₺<?= number_format($totalRevenue,2,',','.') ?></h6>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grafikler -->
  <div class="container-fluid pt-4 px-4">
    <div class="row g-4">
      <div class="col-sm-12 col-xl-6">
        <div class="bg-light rounded p-4">
          <h6 class="mb-4">Aylık Sipariş</h6>
          <canvas id="ordersChart"></canvas>
        </div>
      </div>
      <div class="col-sm-12 col-xl-6">
        <div class="bg-light rounded p-4">
          <h6 class="mb-4">Aylık Kazanç</h6>
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Son Siparişler Tablosu -->
  <div class="container-fluid pt-4 px-4">
    <div class="bg-light text-center rounded p-4">
      <h6 class="mb-4">Güncel Siparişler</h6>
      <div class="table-responsive">
        <table class="table text-start align-middle table-bordered table-hover mb-0">
          <thead>
            <tr class="text-dark">
              <th>ID</th><th>Tarih</th><th>Customer</th><th>Amount</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $o): ?>
            <tr>
              <td><?= $o['id'] ?></td>
              <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
              <td><?= htmlspecialchars($o['customer']) ?></td>
              <td>₺<?= number_format($o['total_amount'],2,',','.') ?></td>
              <td><?= htmlspecialchars($o['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js Komutları -->
<script>
  const labels = <?= json_encode($labels) ?>;
  const orderData = <?= json_encode($orderData) ?>;
  const revenueData = <?= json_encode($revenueData) ?>;

  new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: { labels: labels, datasets: [{ label: 'Orders', data: orderData }] }
  });

  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: labels, datasets: [{ label: 'Revenue', data: revenueData, fill: true }] }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>