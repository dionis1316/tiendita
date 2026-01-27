<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <h1 class="h4 mb-0">Clientes</h1>
    <form method="get" class="d-flex gap-2" style="max-width: 420px; width: 100%;" onsubmit="return false;">
        <input type="search" id="adminCustomersSearch" name="q" class="form-control" placeholder="Buscar clientes" autocomplete="off" value="<?= htmlspecialchars($q ?? ($_GET['q'] ?? '')) ?>">
        <button class="btn btn-outline-primary" type="button">Buscar</button>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle table-responsive-stack">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Pedidos</th>
                <th>Total consumido</th>
                <th>Saldo pendiente</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($customers)): ?>
            <tr><td colspan="6" class="text-center text-muted">Sin clientes.</td></tr>
        <?php else: ?>
            <?php foreach ($customers as $c): ?>
                <?php $searchText = trim(($c['name'] ?? '') . ' ' . ($c['email'] ?? '')); ?>
                <tr class="customer-row" data-search="<?= htmlspecialchars(strtolower($searchText)) ?>">
                    <td data-label="ID"><?= (int)$c['id'] ?></td>
                    <td data-label="Cliente">
                        <div class="fw-semibold"><?= htmlspecialchars($c['name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($c['email']) ?></div>
                    </td>
                    <td data-label="Pedidos"><?= (int)$c['orders_count'] ?></td>
                    <td data-label="Total">$<?= number_format((float)$c['total_spent'], 2) ?></td>
                    <td data-label="Saldo">$<?= number_format((float)$c['total_debt'], 2) ?></td>
                    <td data-label="Acciones" class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/customers/<?= (int)$c['id'] ?>">Ver estado</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('adminCustomersSearch');
  if (!input) return;
  var rows = Array.prototype.slice.call(document.querySelectorAll('.customer-row'));
  var filter = function () {
    var q = (input.value || '').trim().toLowerCase();
    rows.forEach(function (row) {
      var hay = row.getAttribute('data-search') || '';
      row.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
    });
  };
  input.addEventListener('input', filter);
});
</script>
