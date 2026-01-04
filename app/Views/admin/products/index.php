<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Productos</h1>
    <a class="btn btn-primary" href="<?= BASE_URL ?>admin/products/create">Nuevo producto</a>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle table-responsive-stack">
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
                <tr>
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
