<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container py-4 d-flex justify-content-center">
  <div class="card p-4" style="max-width: 420px; width: 100%;">
    <h1 class="h4 mb-3 text-center">Iniciar Sesion</h1>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>login">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="next" value="<?= htmlspecialchars($next ?? '') ?>">

      <div class="mb-3">
        <label for="email" class="form-label">Correo electronico</label>
        <input type="email" name="email" id="email" class="form-control" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="mb-2">
        <label for="password" class="form-label">Contrasena</label>
        <input type="password" name="password" id="password" class="form-control" required>
      </div>

      <div class="mb-3 text-end">
        <a href="<?= BASE_URL ?>forgot" class="small">¿Olvidaste tu contrasena?</a>
      </div>

      <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>register<?= isset($next) && $next ? '?next=' . urlencode($next) : '' ?>">¿No tienes cuenta? Registrate</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
