<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container py-4 d-flex justify-content-center">
  <div class="card p-4" style="max-width: 420px; width: 100%;">
    <h1 class="h4 mb-3 text-center">Restablecer contrasena</h1>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>reset/<?= htmlspecialchars($token ?? '') ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="mb-3">
        <label for="password" class="form-label">Nueva contrasena</label>
        <input type="password" name="password" id="password" class="form-control" required minlength="6">
      </div>

      <div class="mb-3">
        <label for="password_confirm" class="form-label">Confirmar contrasena</label>
        <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="6">
      </div>

      <button type="submit" class="btn btn-success w-100">Actualizar contrasena</button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>login">Volver al login</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
