<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container py-4 d-flex justify-content-center">
  <div class="card p-4" style="max-width: 420px; width: 100%;">
    <h1 class="h4 mb-3 text-center">Recuperar contrasena</h1>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>forgot">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">Correo electronico</label>
        <input type="email" name="email" id="email" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>login">Volver al login</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
