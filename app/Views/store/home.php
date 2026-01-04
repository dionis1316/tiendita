<?php include __DIR__.'/../partials/header.php'; ?>
<h1 class="h4 mb-3">Productos</h1>

<div class="row g-3">
  <?php if (empty($products)): ?>
    <div class="col-12"><div class="alert alert-warning">Sin productos disponibles.</div></div>
  <?php endif; ?>

  <?php foreach ($products as $p): ?>
    <div class="col-6 col-md-3">
      <div class="card h-100">
        <img src="<?= htmlspecialchars($p['image_url'] ? asset_url($p['image_url']) : 'https://via.placeholder.com/600x400?text=Producto') ?>" class="card-img-top" alt="">
        <div class="card-body">
          <div class="small text-muted"><?= htmlspecialchars($p['category'] ?: '') ?></div>
          <h2 class="h6 mb-1"><?= htmlspecialchars($p['name']) ?></h2>
          <div class="fw-bold">$<?= number_format((float)$p['price'], 2) ?></div>
          <a class="btn btn-sm btn-primary w-100 mt-2" href="<?= BASE_URL ?>product/<?= (int)$p['id'] ?>">Ver</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__.'/../partials/footer.php'; ?>
