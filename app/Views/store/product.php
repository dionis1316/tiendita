<?php include __DIR__.'/../partials/header.php'; ?>
<?php
// seguridad por si $product o $csrf no llegan
if (!isset($product) || !is_array($product)) { echo "<div class='alert alert-danger'>Producto no disponible.</div>"; include __DIR__.'/../partials/footer.php'; return; }
if (empty($csrf)) { $csrf = bin2hex(random_bytes(16)); $_SESSION['csrf'] = $csrf; }
?>
<div class="row g-3">
  <div class="col-12 col-md-5">
    <img src="<?= htmlspecialchars($product['image_url'] ? asset_url($product['image_url']) : 'https://via.placeholder.com/800x600?text=Producto') ?>"
         class="img-fluid rounded" alt="">
  </div>
  <div class="col-12 col-md-7">
   <div class="small text-muted mb-1"><?= htmlspecialchars($product['category'] ?? '') ?></div>
    <h1 class="h4"><?= htmlspecialchars($product['name']) ?></h1>
    <div class="fs-4 fw-bold">$<?= number_format((float)$product['price'],2) ?></div>
    <?php if (!empty($product['description'])): ?>
      <p class="mt-2"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    <?php endif; ?>
    <div class="text-muted small mb-2">Stock: <?= (int)$product['stock'] ?></div>

    <form method="post" action="<?= BASE_URL ?>cart/add" class="d-flex gap-2 align-items-center">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
      <input type="number" name="qty" min="1" value="1" class="form-control" style="max-width:110px" <?= ((int)$product['stock']<=0?'disabled':'') ?>>
      <button class="btn btn-primary" <?= ((int)$product['stock']<=0?'disabled':'') ?>>Agregar al carrito</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>">Seguir comprando</a>
    </form>
  </div>
</div>
<?php include __DIR__.'/../partials/footer.php'; ?>
