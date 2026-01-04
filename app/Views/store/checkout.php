<?php include __DIR__.'/../partials/header.php'; ?>

<h1 class="h4 mb-3">Finalizar compra</h1>

<?php if (empty($items)): ?>
  <div class="alert alert-warning">Tu carrito esta vacio.</div>
  <a href="<?= BASE_URL ?>" class="btn btn-primary">Volver a la tienda</a>
<?php else: ?>
<form method="post" action="<?= BASE_URL ?>checkout/submit" enctype="multipart/form-data" class="card p-3" style="max-width:600px">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

  <h5 class="mb-3">Resumen del pedido</h5>
  <div class="table-responsive">
    <table class="table table-sm align-middle table-responsive-stack">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Cantidad</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td data-label="Producto"><?= htmlspecialchars($item['name']) ?></td>
            <td data-label="Cantidad"><?= (int)$item['qty'] ?></td>
            <td data-label="Subtotal">$<?= number_format($item['subtotal'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td></td>
          <td class="text-end fw-bold">Total</td>
          <td class="fw-bold">$<?= number_format($total, 2) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="mb-3">
    <label class="form-label">Metodo de pago</label>
    <select name="payment_method" class="form-select" required onchange="toggleVoucher(this.value)">
      <option value="cash">Efectivo</option>
      <option value="credit">Credito</option>
      <option value="yappi">Yappi</option>
    </select>
  </div>

  <div id="voucherField" style="display:none" class="mb-3">
    <label class="form-label">Adjuntar comprobante (Yappi)</label>
    <input type="file" name="receipt" accept="image/*" class="form-control">
  </div>

  <button class="btn btn-success w-100">Confirmar pedido</button>
</form>

<script>
function toggleVoucher(value) {
  document.getElementById('voucherField').style.display = value === 'yappi' ? 'block' : 'none';
}
</script>

<?php endif; ?>

<?php include __DIR__.'/../partials/footer.php'; ?>
