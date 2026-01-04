<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container py-4 d-flex justify-content-center">
  <div class="card p-4" style="max-width: 520px; width: 100%;">
    <h1 class="h4 mb-3 text-center">Crear cuenta</h1>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>register">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="mb-3">
        <label for="name" class="form-label">Nombre</label>
        <input type="text" name="name" id="name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" name="email" id="email" class="form-control" required>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="password" class="form-label">Contraseña</label>
          <input type="password" name="password" id="password" class="form-control" required minlength="6">
        </div>
        <div class="col-md-6 mb-3">
          <label for="password_confirm" class="form-label">Confirmar contraseña</label>
          <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="6">
        </div>
      </div>

      <button type="submit" class="btn btn-success w-100">Registrar</button>

      <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>login">¿Ya tienes cuenta? Inicia sesión</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
