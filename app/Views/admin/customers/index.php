<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4">Clientes</h1>
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
                <tr>
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
