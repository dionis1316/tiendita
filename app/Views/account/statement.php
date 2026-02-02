<?php include __DIR__ . '/../partials/header.php'; ?>

<h1 class="h4 mb-2">Mi estado de cuenta</h1>
<p class="text-muted">Aqui puedes ver tus compras y saldo pendiente.</p>

<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-md-3">
    <label class="form-label">Desde</label>
    <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($start ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Hasta</label>
    <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($end ?? '') ?>">
  </div>
  <div class="col-md-6">
    <button class="btn btn-primary">Filtrar</button>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>account/statement">Limpiar</a>
  </div>
</form>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted small">Saldo pendiente</div>
      <div class="text-muted small">$<?= number_format($pendingDebt, 2) ?></div>
      <div class="text-muted small">Saldo a favor: $<?= number_format((float)($favorBalance ?? 0), 2) ?></div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3 h-100">
      <h2 class="h6 mb-3">Top productos mas comprados</h2>
      <canvas id="topClientChart" height="140"></canvas>
    </div>
  </div>
</div>

<div class="table-responsive">
  <table class="table table-bordered align-middle table-responsive-stack">
    <thead>
      <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Total</th>
        <th>Metodo</th>
        <th>Pagado</th>
        <th>Estado</th>
        <th>Saldo</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($orders)): ?>
      <tr><td colspan="7" class="text-center text-muted">No tienes compras aun.</td></tr>
    <?php else: ?>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td data-label="ID">#<?= (int)$o['id'] ?></td>
          <td data-label="Fecha"><?= htmlspecialchars($o['created_at']) ?></td>
          <td data-label="Total">$<?= number_format((float)$o['total'], 2) ?></td>
          <td data-label="Metodo"><?= htmlspecialchars($o['payment_method']) ?></td>
          <td data-label="Pagado">$<?= number_format((float)$o['amount_paid'], 2) ?></td>
          <td data-label="Estado"><?= htmlspecialchars($o['payment_status']) ?></td>
          <td data-label="Saldo">$<?= number_format((float)$o['total'] - (float)$o['amount_paid'], 2) ?></td>
        </tr>
        <?php if (!empty($itemsByOrder[(int)$o['id']])): ?>
          <tr>
            <td colspan="7">
              <div class="small text-muted">Detalle</div>
              <ul class="mb-0">
                <?php foreach ($itemsByOrder[(int)$o['id']] as $it): ?>
                  <li><?= htmlspecialchars($it['name']) ?> · <?= (int)$it['quantity'] ?> x $<?= number_format((float)$it['price'], 2) ?></li>
                <?php endforeach; ?>
              </ul>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const topLabels = <?= json_encode($topLabels ?? []) ?>;
const topQty = <?= json_encode($topQty ?? []) ?>;
const ctx = document.getElementById('topClientChart');
const valueLabelPlugin = {
  id: 'valueLabelPlugin',
  afterDatasetsDraw(chart) {
    const { ctx } = chart;
    ctx.save();
    ctx.fillStyle = '#495057';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'bottom';
    ctx.font = '12px sans-serif';
    chart.data.datasets.forEach((dataset, datasetIndex) => {
      const meta = chart.getDatasetMeta(datasetIndex);
      meta.data.forEach((bar, index) => {
        const value = dataset.data[index];
        ctx.fillText(value, bar.x, bar.y - 4);
      });
    });
  }
};
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: topLabels,
      datasets: [{
        label: 'Cantidad',
        data: topQty,
        backgroundColor: '#0d6efd'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    },
    plugins: [valueLabelPlugin]
  });
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
