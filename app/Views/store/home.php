<?php include __DIR__.'/../partials/header.php'; ?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
  <h1 class="h4 mb-0">Productos</h1>
  <form method="get" class="d-flex gap-2" style="max-width: 420px; width: 100%;" onsubmit="return false;">
    <input type="search" id="storeSearch" name="q" class="form-control" placeholder="Buscar productos" autocomplete="off" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    <button class="btn btn-outline-primary" type="button">Buscar</button>
  </form>
</div>

<div class="row g-3">
  <?php if (empty($products)): ?>
    <div class="col-12"><div class="alert alert-warning">Sin productos disponibles.</div></div>
  <?php endif; ?>

  <?php foreach ($products as $p): ?>
    <?php $searchText = trim(($p['name'] ?? '') . ' ' . ($p['category'] ?? '')); ?>
    <div class="col-6 col-md-3 product-card" data-search="<?= htmlspecialchars(strtolower($searchText)) ?>">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('storeSearch');
  if (!input) return;
  var cards = Array.prototype.slice.call(document.querySelectorAll('.product-card'));
  var filter = function () {
    var q = (input.value || '').trim().toLowerCase();
    cards.forEach(function (card) {
      var hay = card.getAttribute('data-search') || '';
      card.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
    });
  };
  input.addEventListener('input', filter);
});
</script>
