<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Estado de cuenta</h1>
        <div class="text-muted"><?= htmlspecialchars($customer['name']) ?> · <?= htmlspecialchars($customer['email']) ?></div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>admin/customers">Volver</a>
</div>

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
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>">Limpiar</a>
        <a class="btn btn-outline-success" href="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/export/csv?start=<?= urlencode($start ?? '') ?>&end=<?= urlencode($end ?? '') ?>">CSV</a>
        <a class="btn btn-outline-danger" href="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/export/pdf?start=<?= urlencode($start ?? '') ?>&end=<?= urlencode($end ?? '') ?>">PDF</a>
        <form method="post" action="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/send" class="d-inline">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
            <input type="hidden" name="start" value="<?= htmlspecialchars($start ?? '') ?>">
            <input type="hidden" name="end" value="<?= htmlspecialchars($end ?? '') ?>">
            <button class="btn btn-outline-primary">Enviar por correo</button>
        </form>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small">Cliente</div>
            <div class="fw-semibold"><?= htmlspecialchars($customer['name']) ?></div>
            <div class="text-muted small">Alta: <?= htmlspecialchars($customer['created_at']) ?></div>
            <div class="text-muted small">Estado: <?= ((int)($customer['is_active'] ?? 1) === 1) ? 'Activo' : 'Inactivo' ?></div>
            <div class="mt-2">
                <?php if ((int)($customer['is_active'] ?? 1) === 1): ?>
                    <form method="post" action="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/deactivate" onsubmit="return confirm('¿Inactivar este cliente?');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
                        <button class="btn btn-sm btn-outline-danger">Inactivar</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/activate">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
                        <button class="btn btn-sm btn-outline-success">Activar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small">Credito</div>
            <?php if (!empty($creditAccount)): ?>
                <div class="fw-semibold">Limite: $<?= number_format((float)$creditAccount['credit_limit'], 2) ?></div>
                <div class="text-muted small">Saldo: $<?= number_format($totalDebt, 2) ?></div>
                <div class="text-muted small">Estado: <?= htmlspecialchars($creditAccount['status']) ?></div>
            <?php else: ?>
                <div class="text-muted">Sin cuenta de credito.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <?php
        $totalDebt = 0.0;
        foreach ($orders as $o) {
            if (($o['payment_status'] ?? '') === 'unpaid' && ($o['payment_method'] ?? '') === 'CREDIT') {
                $totalDebt += ((float)$o['total'] - (float)$o['amount_paid']);
            }
        }
        ?>
        <div class="card p-3">
            <div class="text-muted small">Saldo pendiente</div>
            <div class="text-muted small">$<?= number_format($totalDebt, 2) ?></div>
        </div>
    </div>
</div>


<div class="card p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h6 mb-1">Pedido manual</h2>
            <div class="text-muted small">Registrar un pedido para este cliente.</div>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualOrderModal">Registrar pedido</button>
    </div>
</div>

<div class="card p-3 mb-4">
    <h2 class="h6">Registrar pago</h2>
    <form method="post" action="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/payments" class="row g-2 align-items-end" data-total-debt="<?= htmlspecialchars(number_format($totalDebt, 2, '.', '')) ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
        <div class="col-md-3">
            <label class="form-label">Monto</label>
            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Metodo</label>
            <select name="method" class="form-select">
                <option value="CASH">Efectivo</option>
                <option value="TRANSFER">Transferencia</option>
                <option value="ADJUSTMENT">Ajuste</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ordenes en credito (opcional)</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="orderDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Seleccionar ordenes
                </button>
                <div class="dropdown-menu p-2 w-100" style="max-height:220px; overflow:auto;">
                    <?php if (empty($unpaidOrders)): ?>
                        <div class="text-muted small">Sin ordenes en credito.</div>
                    <?php else: ?>
                        <?php foreach ($unpaidOrders as $uo): ?>
                            <?php $remaining = (float)$uo['total'] - (float)$uo['amount_paid']; ?>
                            <div class="form-check">
                                <input class="form-check-input order-check" type="checkbox" name="order_ids[]" value="<?= (int)$uo['id'] ?>" data-remaining="<?= htmlspecialchars(number_format($remaining, 2, '.', '')) ?>" id="orderCheck<?= (int)$uo['id'] ?>">
                                <label class="form-check-label" for="orderCheck<?= (int)$uo['id'] ?>">
                                    #<?= (int)$uo['id'] ?> · $<?= number_format($remaining, 2) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-text">Selecciona varias si deseas pagar en un solo movimiento.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Referencia</label>
            <input type="text" name="reference" class="form-control">
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="pay_all" value="1" id="payAllCheck">
                <label class="form-check-label" for="payAllCheck">Pagar todo el saldo pendiente</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-success">Guardar pago</button>
        </div>
    </form>
</div>

<h2 class="h6 mb-2">Compras</h2>
<div class="table-responsive mb-4">
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
                <th>Comprobante</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="7" class="text-center text-muted">Sin compras.</td></tr>
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
                    <td data-label="Comprobante">
                        <?php if (!empty($o['receipt_path'])): ?>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= asset_url($o['receipt_path']) ?>" alt="Comprobante" style="width:48px;height:48px;object-fit:cover" class="rounded border">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receiptModal" data-receipt="<?= asset_url($o['receipt_path']) ?>">Ver</button>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($itemsByOrder[(int)$o['id']])): ?>
                    <tr>
                        <td colspan="8">
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

<h2 class="h6 mb-2">Movimientos de credito</h2>
<div class="table-responsive">
    <table class="table table-sm table-striped table-responsive-stack">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Metodo</th>
                <th>Monto</th>
                <th>Referencia</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($transactions)): ?>
            <tr><td colspan="6" class="text-center text-muted">Sin movimientos.</td></tr>
        <?php else: ?>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td data-label="ID"><?= (int)$t['id'] ?></td>
                    <td data-label="Fecha"><?= htmlspecialchars($t['created_at']) ?></td>
                    <td data-label="Tipo"><?= htmlspecialchars($t['type']) ?></td>
                    <td data-label="Metodo"><?= htmlspecialchars($t['method'] ?? '-') ?></td>
                    <td data-label="Monto">$<?= number_format((float)$t['amount'], 2) ?></td>
                    <td data-label="Referencia"><?= htmlspecialchars($t['reference'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>




<style>
#manualOrderModal .modal-footer { position: sticky; bottom: 0; background: #fff; z-index: 2; }
</style>

<div class="modal fade" id="manualOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?= BASE_URL ?>admin/customers/<?= (int)$customer['id'] ?>/orders/manual">
        <div class="modal-header">
          <h5 class="modal-title">Pedido manual · <?= htmlspecialchars($customer['name']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Cliente</label>
              <div class="form-control-plaintext"><?= htmlspecialchars($customer['name']) ?> · <?= htmlspecialchars($customer['email']) ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Metodo de pago</label>
              <select name="payment_method" class="form-select">
                <option value="CASH">Efectivo</option>
                <option value="TRANSFER">Transferencia</option>
                <option value="CREDIT">Credito</option>
              </select>
            </div>
          </div>
          <hr>
          <div class="table-responsive" style="max-height: 360px;">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th>Producto</th>
                  <th>Precio</th>
                  <th>Stock</th>
                  <th style="width:120px;">Cantidad</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($products)): ?>
                <tr><td colspan="4" class="text-center text-muted">Sin productos activos.</td></tr>
              <?php else: ?>
                <?php foreach ($products as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td>$<?= number_format((float)$p['price'], 2) ?></td>
                    <td><?= (int)$p['stock'] ?></td>
                    <td>
                      <input type="number" min="0" max="<?= (int)$p['stock'] ?>" step="1" name="items[<?= (int)$p['id'] ?>]" class="form-control form-control-sm manual-order-qty" data-price="<?= htmlspecialchars(number_format((float)$p['price'], 2, '.', '')) ?>" value="0">
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="fw-semibold">Total: $<span id="manualOrderTotal">0.00</span></div>
            <div class="text-muted small">Se registrara como pedido normal para el cliente.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success" id="manualOrderSubmit">Confirmar pedido</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Comprobante</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="receiptModalImg" src="" alt="Comprobante" class="img-fluid rounded">
      </div>
      <div class="modal-footer">
        <a id="receiptModalLink" href="#" target="_blank" class="btn btn-outline-secondary">Abrir en nueva pestaña</a>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-receipt]');
  if (!btn) return;
  const url = btn.getAttribute('data-receipt');
  const img = document.getElementById('receiptModalImg');
  const link = document.getElementById('receiptModalLink');
  if (img) img.src = url;
  if (link) link.href = url;
});

document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[action*=\"/payments\"]');
  if (!form) return;
  const payAll = document.getElementById('payAllCheck');
  const amount = form.querySelector('input[name=\"amount\"]');
  const checks = form.querySelectorAll('input[name=\"order_ids[]\"]');
  const dropdownBtn = document.getElementById('orderDropdown');
  if (!payAll || !amount || !checks.length || !dropdownBtn) return;

  function sumSelected() {
    let total = 0;
    checks.forEach(function (chk) {
      if (!chk.checked) return;
      const val = parseFloat(chk.getAttribute('data-remaining') || '0');
      if (!isNaN(val)) total += val;
    });
    return total;
  }

  function setAmount(val) {
    amount.value = (Math.round(val * 100) / 100).toFixed(2);
  }

  function updateDropdownLabel() {
    const selected = Array.from(checks).filter(function (c) { return c.checked; });
    if (selected.length === 0) {
      dropdownBtn.textContent = 'Seleccionar ordenes';
      return;
    }
    dropdownBtn.textContent = 'Ordenes seleccionadas: ' + selected.length;
  }

  payAll.addEventListener('change', function () {
    if (payAll.checked) {
      checks.forEach(function (chk) { chk.checked = true; });
      const totalDebt = parseFloat(form.getAttribute('data-total-debt') || '0');
      setAmount(totalDebt);
      updateDropdownLabel();
    } else {
      setAmount(sumSelected());
      updateDropdownLabel();
    }
  });

  checks.forEach(function (chk) {
    chk.addEventListener('change', function () {
      if (payAll.checked) return;
      setAmount(sumSelected());
      updateDropdownLabel();
    });
  });

  updateDropdownLabel();
});


  const manualModal = document.getElementById('manualOrderModal');
  if (manualModal) {
    const qtyInputs = manualModal.querySelectorAll('.manual-order-qty');
    const totalEl = document.getElementById('manualOrderTotal');
    const submitBtn = document.getElementById('manualOrderSubmit');

    function calcManualTotal() {
      let total = 0;
      qtyInputs.forEach(function (input) {
        const qty = parseInt(input.value || '0', 10);
        const price = parseFloat(input.getAttribute('data-price') || '0');
        if (!isNaN(qty) && !isNaN(price)) {
          total += qty * price;
        }
      });
      if (totalEl) totalEl.textContent = total.toFixed(2);
      if (submitBtn) submitBtn.disabled = total <= 0;
    }

    qtyInputs.forEach(function (input) {
      input.addEventListener('input', calcManualTotal);
    });

    manualModal.addEventListener('shown.bs.modal', calcManualTotal);
    calcManualTotal();

  }

  window.addEventListener('load', function () {
    try {
      var params = new URLSearchParams(window.location.search || '');
      if (params.get('order') === 'manual') {
        var modalEl = document.getElementById('manualOrderModal');
        if (modalEl && window.bootstrap && typeof bootstrap.Modal === 'function') {
          var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.show();
        }
      }
    } catch (e) {}
  });

</script>
