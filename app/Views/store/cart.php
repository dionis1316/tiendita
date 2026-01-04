<?php include __DIR__.'/../partials/header.php'; ?>
<h1 class="h4 mb-3">Carrito</h1>

<?php if (empty($items)): ?>
  <div class="alert alert-info">Tu carrito esta vacio.</div>
  <a class="btn btn-primary" href="<?= BASE_URL ?>">Ver productos</a>
<?php else: ?>
  <form method="post" action="<?= BASE_URL ?>cart/update" class="table-responsive">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <table class="table align-middle table-responsive-stack">
      <thead>
        <tr>
          <th>Producto</th>
          <th style="width:120px">Cantidad</th>
          <th>Precio</th>
          <th>Subtotal</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $total = 0.0; foreach ($items as $it): $total += (float)$it['subtotal']; ?>
          <tr>
            <td data-label="Producto">
              <div class="d-flex align-items-center">
                <img src="<?= htmlspecialchars($it['image_url'] ? asset_url($it['image_url']) : 'https://via.placeholder.com/120x80?text=Prod') ?>"
                     style="width:80px;height:60px;object-fit:cover" class="rounded me-2" alt="">
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($it['name']) ?></div>
                  <div class="text-muted small">Stock: <?= (int)$it['stock'] ?></div>
                </div>
              </div>
            </td>
            <td data-label="Cantidad">
              <input type="number" name="qty[<?= (int)$it['id'] ?>]" min="1"
                     value="<?= (int)$it['qty'] ?>" class="form-control">
            </td>
            <td data-label="Precio">$<?= number_format((float)$it['price'],2) ?></td>
            <td data-label="Subtotal" class="fw-semibold">$<?= number_format((float)$it['subtotal'],2) ?></td>
            <td data-label="Acciones">
              <form method="post" action="<?= BASE_URL ?>cart/remove">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Quitar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2"></td>
          <td class="text-end fw-bold">Total</td>
          <td class="fw-bold">$<?= number_format($total,2) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-outline-secondary">Actualizar cantidades</button>
      <a class="btn btn-primary" href="<?= BASE_URL ?>">Seguir comprando</a>
      <a class="btn btn-success ms-auto" href="<?= BASE_URL ?>checkout">Ir a pagar</a>
    </div>
  </form>
<?php endif; ?>

<?php include __DIR__.'/../partials/footer.php'; ?>
