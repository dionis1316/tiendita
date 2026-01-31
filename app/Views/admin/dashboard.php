<h1 class="mb-4">Bienvenido, <?= htmlspecialchars($user['name'] ?? 'Administrador') ?> 👋</h1>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Deuda total</div>
        <div class="fs-5 fw-bold">$<?= number_format((float)($chartData['total_debt'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Pagado total</div>
        <div class="fs-5 fw-bold">$<?= number_format((float)($chartData['total_paid'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Ticket promedio</div>
        <div class="fs-5 fw-bold">$<?= number_format((float)($chartData['avg_ticket'] ?? 0), 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Clientes</div>
        <div class="fs-5 fw-bold"><?= (int)($chartData['customers_total'] ?? 0) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Morosos vs Pagados</h2>
        <canvas id="ordersChart" height="180"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Clientes con deuda</h2>
        <canvas id="customersChart" height="180"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Ventas por mes</h2>
        <canvas id="monthlyChart" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Top clientes por consumo (semana actual)</h2>
        <canvas id="topRevenueChart" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Top productos mas comprados</h2>
        <canvas id="topQtyChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 mb-3">Ganancia mensual</h2>
        <canvas id="profitChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card text-bg-light h-100">
      <div class="card-body">
        <h5 class="card-title">Gestion de Productos</h5>
        <p class="card-text">Agrega, edita o elimina productos disponibles en la tienda.</p>
        <a href="<?= BASE_URL ?>admin/products" class="btn btn-primary">Ir a Productos</a>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card text-bg-light h-100">
      <div class="card-body">
        <h5 class="card-title">Gestion de Clientes</h5>
        <p class="card-text">Consulta y administra los clientes registrados en la plataforma.</p>
        <a href="<?= BASE_URL ?>admin/customers" class="btn btn-primary">Ir a Clientes</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ordersCtx = document.getElementById('ordersChart');
new Chart(ordersCtx, {
  type: 'doughnut',
  data: {
    labels: ['Morosos', 'Pagados'],
    datasets: [{
      data: [<?= (int)($chartData['unpaid'] ?? 0) ?>, <?= (int)($chartData['paid'] ?? 0) ?>],
      backgroundColor: ['#dc3545', '#198754']
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

const customersCtx = document.getElementById('customersChart');
new Chart(customersCtx, {
  type: 'bar',
  data: {
    labels: ['Con deuda', 'Sin deuda'],
    datasets: [{
      data: [<?= (int)($chartData['customers_with_debt'] ?? 0) ?>, <?= max(0, (int)($chartData['customers_total'] ?? 0) - (int)($chartData['customers_with_debt'] ?? 0)) ?>],
      backgroundColor: ['#ffc107', '#0d6efd']
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

const months = <?= json_encode($chartData['months'] ?? []) ?>;
const monthlyTotals = <?= json_encode($chartData['monthly_totals'] ?? []) ?>;
const monthlyCtx = document.getElementById('monthlyChart');
new Chart(monthlyCtx, {
  type: 'line',
  data: {
    labels: months,
    datasets: [{
      label: 'Ventas',
      data: monthlyTotals,
      borderColor: '#0d6efd',
      backgroundColor: 'rgba(13,110,253,0.2)',
      tension: 0.3
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

const topCustomerLabels = <?= json_encode($chartData['top_customer_labels'] ?? []) ?>;
const topCustomerTotals = <?= json_encode($chartData['top_customer_totals'] ?? []) ?>;
const topCustomerAvgs = <?= json_encode($chartData['top_customer_avgs'] ?? []) ?>;
const topRevenueCtx = document.getElementById('topRevenueChart');
const avgLabelPlugin = {
  id: 'avgLabel',
  afterDatasetsDraw(chart) {
    const {ctx} = chart;
    const meta = chart.getDatasetMeta(0);
    ctx.save();
    ctx.font = '12px sans-serif';
    ctx.fillStyle = '#6c757d';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'bottom';
    meta.data.forEach((bar, i) => {
      const val = Number(topCustomerAvgs[i] || 0);
      ctx.fillText('$' + val.toFixed(2), bar.x, bar.y - 4);
    });
    ctx.restore();
  }
};
new Chart(topRevenueCtx, {
  type: 'bar',
  data: {
    labels: topCustomerLabels,
    datasets: [{
      label: 'Total consumido',
      data: topCustomerTotals,
      backgroundColor: '#0d6efd'
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } },
  plugins: [avgLabelPlugin]
});

const topQtyLabels = <?= json_encode($chartData['top_qty_labels'] ?? []) ?>;
const topQtyValues = <?= json_encode($chartData['top_qty_values'] ?? []) ?>;
const topQtyCtx = document.getElementById('topQtyChart');
new Chart(topQtyCtx, {
  type: 'bar',
  data: {
    labels: topQtyLabels,
    datasets: [{
      label: 'Cantidad',
      data: topQtyValues,
      backgroundColor: '#20c997'
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});

const profitMonths = <?= json_encode($chartData['profit_months'] ?? []) ?>;
const profitTotals = <?= json_encode($chartData['profit_totals'] ?? []) ?>;
const profitCtx = document.getElementById('profitChart');
new Chart(profitCtx, {
  type: 'line',
  data: {
    labels: profitMonths,
    datasets: [{
      label: 'Ganancia',
      data: profitTotals,
      borderColor: '#6610f2',
      backgroundColor: 'rgba(102,16,242,0.2)',
      tension: 0.3
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>
