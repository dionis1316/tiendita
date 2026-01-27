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
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small">Credito</div>
            <?php if (!empty($creditAccount)): ?>
                <div class="fw-semibold">Limite: $<?= number_format((float)$creditAccount['credit_limit'], 2) ?></div>
                <div class="text-muted small">Saldo: $<?= number_format((float)$creditAccount['balance'], 2) ?></div>
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
            if (($o['payment_status'] ?? '') === 'unpaid') {
                $totalDebt += ((float)$o['total'] - (float)$o['amount_paid']);
            }
        }
        ?>
        <div class="card p-3">
            <div class="text-muted small">Saldo pendiente</div>
            <div class="fw-semibold">$<?= number_format($totalDebt, 2) ?></div>
        </div>
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
            <select name="order_ids[]" class="form-select" multiple size="4">
                <?php foreach ($unpaidOrders as $uo): ?>
                    <?php $remaining = (float)$uo['total'] - (float)$uo['amount_paid']; ?>
                    <option value="<?= (int)$uo['id'] ?>" data-remaining="<?= htmlspecialchars(number_format($remaining, 2, '.', '')) ?>">
                        #<?= (int)$uo['id'] ?> · $<?= number_format($remaining, 2) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
  const select = form.querySelector('select[name=\"order_ids[]\"]');
  if (!payAll || !amount || !select) return;

  function sumSelected() {
    let total = 0;
    Array.from(select.selectedOptions).forEach(function (opt) {
      const val = parseFloat(opt.getAttribute('data-remaining') || '0');
      if (!isNaN(val)) total += val;
    });
    return total;
  }

  function setAmount(val) {
    amount.value = (Math.round(val * 100) / 100).toFixed(2);
  }

  payAll.addEventListener('change', function () {
    if (payAll.checked) {
      Array.from(select.options).forEach(function (opt) { opt.selected = true; });
      const totalDebt = parseFloat(form.getAttribute('data-total-debt') || '0');
      setAmount(totalDebt);
    } else {
      setAmount(sumSelected());
    }
  });

  select.addEventListener('change', function () {
    if (payAll.checked) return;
    setAmount(sumSelected());
  });
});
</script>
