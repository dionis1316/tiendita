<h1 class="h4 mb-3">Actividad de usuarios</h1>

<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-md-3">
    <label class="form-label">Tipo</label>
    <select name="type" class="form-select">
      <option value="all" <?= ($filters['type'] ?? '') === 'all' ? 'selected' : '' ?>>Todos</option>
      <option value="login" <?= ($filters['type'] ?? '') === 'login' ? 'selected' : '' ?>>Logins</option>
      <option value="cart" <?= ($filters['type'] ?? '') === 'cart' ? 'selected' : '' ?>>Carrito</option>
      <option value="abandoned" <?= ($filters['type'] ?? '') === 'abandoned' ? 'selected' : '' ?>>Carritos pendientes</option>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Desde</label>
    <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">Hasta</label>
    <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Buscar</label>
    <input type="text" name="q" class="form-control" placeholder="Nombre, email o producto" value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Cliente</label>
    <input type="text" name="customer" class="form-control" placeholder="Nombre o email" value="<?= htmlspecialchars($filters['customer'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Producto</label>
    <input type="text" name="product" class="form-control" placeholder="Nombre del producto" value="<?= htmlspecialchars($filters['product'] ?? '') ?>">
  </div>
  <div class="col-md-2 d-flex gap-2">
    <button class="btn btn-primary">Filtrar</button>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>admin/activity">Limpiar</a>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-bordered align-middle table-responsive-stack">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Usuario</th>
        <th>Tipo</th>
        <th>Producto(s)</th>
        <th>Cantidad</th>
        <th>IP</th>
        <th>User Agent</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="8" class="text-center text-muted">Sin registros.</td></tr>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <?php
            $typeLabel = $log['type'] === 'login_success' ? 'Login' : ($log['type'] === 'cart_add' ? 'Carrito' : $log['type']);
            $userLabel = trim(($log['name'] ?? '') . ' ' . ($log['email'] ?? ''));
            $status = '';
            if ($log['type'] === 'cart_add') {
                $status = $log['completed_at'] ? 'Completado' : 'Pendiente';
            }
          ?>
          <tr>
            <td data-label="Fecha"><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
            <td data-label="Usuario"><?= htmlspecialchars($userLabel ?: '—') ?></td>
            <td data-label="Tipo"><?= htmlspecialchars($typeLabel) ?></td>
            <td data-label="Producto(s)"><?= htmlspecialchars($log['product_name'] ?? '—') ?></td>
            <td data-label="Cantidad"><?= isset($log['quantity']) ? (int)$log['quantity'] : '—' ?></td>
            <td data-label="IP"><?= htmlspecialchars($log['ip'] ?? '—') ?></td>
            <td data-label="User Agent" style="max-width:260px;">
              <div class="text-truncate" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>"><?= htmlspecialchars($log['user_agent'] ?? '—') ?></div>
            </td>
            <td data-label="Estado"><?= htmlspecialchars($status ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
