<h1 class="h4 mb-3"><?= htmlspecialchars($title ?? 'Producto') ?></h1>

<form method="post" action="<?= htmlspecialchars($action) ?>" enctype="multipart/form-data" class="card p-4" style="max-width:720px">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="row">
        <div class="col-md-8 mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control" required
                   value="<?= htmlspecialchars($product['name'] ?? '') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Categoria</label>
            <select name="category_id" class="form-select">
                <option value="">Sin categoria</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= isset($product['category_id']) && (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Precio</label>
            <input type="number" step="0.01" min="0" name="price" class="form-control" required
                   value="<?= htmlspecialchars($product['price'] ?? '0.00') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Costo</label>
            <input type="number" step="0.01" min="0" name="cost" class="form-control" required
                   value="<?= htmlspecialchars($product['cost'] ?? '0.00') ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Stock</label>
            <input type="number" min="0" name="stock" class="form-control" required
                   value="<?= htmlspecialchars($product['stock'] ?? '0') ?>">
        </div>
        <div class="col-md-4 mb-3 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" <?= !isset($product) || (int)($product['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Activo</label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Imagen (URL)</label>
        <input type="text" name="image_url" class="form-control" value="<?= htmlspecialchars($product['image_url'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Subir imagen</label>
        <input type="file" name="image_file" accept="image/*" class="form-control">
        <?php if (!empty($product['image_url'])): ?>
            <div class="mt-2">
                <img src="<?= htmlspecialchars(asset_url($product['image_url'])) ?>" alt="" style="max-width:200px" class="img-thumbnail">
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label">Descripcion</label>
        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>admin/products">Cancelar</a>
    </div>
</form>
