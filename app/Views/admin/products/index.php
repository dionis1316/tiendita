
<style>
@media (max-width: 768px) {
  .admin-products-mobile td { word-break: break-word; }
  .admin-products-mobile td[data-label="Acciones"] { display: block; text-align: left !important; }
  .admin-products-mobile td[data-label="Acciones"] .btn { display: block; width: 100%; margin: 0 0 0.5rem 0; }
  .admin-products-mobile td[data-label="Acciones"] .btn:last-child { margin-bottom: 0; }
}
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <h1 class="h4 mb-0">Productos</h1>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <form method="get" class="d-flex gap-2" onsubmit="return false;">
            <input type="search" id="adminProductsSearch" name="q" class="form-control" placeholder="Buscar productos" autocomplete="off" value="<?= htmlspecialchars($q ?? ($_GET['q'] ?? '')) ?>">
            <button class="btn btn-outline-primary" type="button">Buscar</button>
        </form>
        <a class="btn btn-primary" href="<?= BASE_URL ?>admin/products/create">Nuevo producto</a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle table-responsive-stack admin-products-mobile">
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Categoria</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
            <tr><td colspan="7" class="text-center text-muted">Sin productos.</td></tr>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
                <?php $searchText = trim(($p['name'] ?? '') . ' ' . ($p['category_name'] ?? '')); ?>
                <tr class="product-row" data-search="<?= htmlspecialchars(strtolower($searchText)) ?>">
                    <td data-label="ID"><?= (int)$p['id'] ?></td>
                    <td data-label="Producto">
                        <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($p['image_url'] ?? '') ?></div>
                    </td>
                    <td data-label="Categoria"><?= htmlspecialchars($p['category_name'] ?? 'Sin categoria') ?></td>
                    <td data-label="Precio">$<?= number_format((float)$p['price'], 2) ?></td>
                    <td data-label="Stock"><?= (int)$p['stock'] ?></td>
                    <td data-label="Estado">
                        <?php if ((int)$p['is_active'] === 1): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Acciones" class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/products/<?= (int)$p['id'] ?>/edit">Editar</a>
                        <?php if ((int)$p['is_active'] === 1): ?>
                            <form method="post" action="<?= BASE_URL ?>admin/products/<?= (int)$p['id'] ?>/deactivate" class="d-inline">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
                                <button class="btn btn-sm btn-outline-danger">Desactivar</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= BASE_URL ?>admin/products/<?= (int)$p['id'] ?>/activate" class="d-inline">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
                                <button class="btn btn-sm btn-outline-success">Activar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('adminProductsSearch');
  if (!input) return;
  var rows = Array.prototype.slice.call(document.querySelectorAll('.product-row'));
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
