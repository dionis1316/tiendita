<?php use App\Core\Csrf; ?>
<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="col-md-5">
    <div class="card shadow">
      <div class="card-body p-4">
        <h1 class="h4 mb-4 text-center">Login Administracion</h1>

        <?php include __DIR__.'/_flash.php'; ?>

      <form method="post" action="<?= BASE_URL ?>admin/login">

          <input type="hidden" name="csrf" value="<?= Csrf::token() ?>">

          <div class="mb-3">
            <label for="email" class="form-label">Correo electronico</label>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="admin@tiendita.local" required>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Contrasena</label>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="••••••••" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
      </div>
    </div>
  </div>
</div>
