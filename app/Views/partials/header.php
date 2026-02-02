<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Tiendita de Evy') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media (max-width: 768px) {
      table.table-responsive-stack thead { display: none; }
      table.table-responsive-stack tr { display: block; margin-bottom: 0.75rem; border: 1px solid #e9ecef; border-radius: 10px; }
      table.table-responsive-stack td { display: flex; justify-content: space-between; gap: 1rem; padding: 0.5rem 0.75rem; border: none; border-bottom: 1px solid #f1f3f5; }
      table.table-responsive-stack td:last-child { border-bottom: none; }
      table.table-responsive-stack td::before { content: attr(data-label); font-weight: 600; color: #6c757d; }
    }
    .brand-logo { height: 40px; width: auto; max-width: 160px; }
    .login-logo { display: block; margin: 0 auto; width: 70%; max-width: 220px; height: auto; }
    :root {
      --brand-blue: #c1ddf2;
      --brand-red: #ef4e53;
      --brand-yellow: #fce772;
      --brand-green: #2f9e44;
      --brand-ink: #1e1e1e;
    }
    .btn-primary { background: var(--brand-blue); border-color: var(--brand-blue); color: var(--brand-ink); }
    .btn-primary:hover { filter: brightness(0.95); color: var(--brand-ink); }
    .btn-success { background: var(--brand-green); border-color: var(--brand-green); color: #fff; }
    .btn-success:hover { filter: brightness(0.95); color: #fff; }
    .btn-danger { background: var(--brand-red); border-color: var(--brand-red); color: #fff; }
    .btn-danger:hover { filter: brightness(0.95); color: #fff; }
    .btn-warning { background: var(--brand-yellow); border-color: var(--brand-yellow); color: var(--brand-ink); }
    .btn-warning:hover { filter: brightness(0.95); color: var(--brand-ink); }
    .btn-outline-primary { border-color: var(--brand-blue); color: var(--brand-ink); }
    .btn-outline-primary:hover { background: var(--brand-blue); color: var(--brand-ink); }
    .btn-outline-danger { border-color: var(--brand-red); color: var(--brand-red); }
    .btn-outline-danger:hover { background: var(--brand-red); color: #fff; }
    .btn-outline-success { border-color: var(--brand-green); color: var(--brand-green); }
    .btn-outline-success:hover { background: var(--brand-green); color: #fff; }
    .btn-outline-secondary { border-color: var(--brand-ink); color: var(--brand-ink); }
    .btn-outline-secondary:hover { background: var(--brand-ink); color: #fff; }
    @media (max-width: 576px) {
      .brand-logo { height: 32px; max-width: 130px; }
      .login-logo { width: 80%; max-width: 200px; }
    }
  </style>
</head>
<body class="bg-light">

<?php
use App\Core\Auth;

// Mostrar deuda pendiente si esta logueado
$pendingDebt = 0;
if (Auth::check()) {
    $pdo = require __DIR__ . '/../../Config/db.php';
    $st = $pdo->prepare("SELECT SUM(total - amount_paid) as debt
                         FROM orders 
                         WHERE user_id = ? AND payment_method = 'CREDIT' AND payment_status = 'unpaid'");
    $st->execute([Auth::userId()]);
    $pendingDebt = (float)($st->fetchColumn() ?? 0);

if (!function_exists('asset_url')) {
    function asset_url($path) {
        if ($path === '' || $path === null) return '';
        if (preg_match('#^https?://#i', $path)) return $path;
        if (strpos($path, '/') === 0) return $path;
        return BASE_URL . ltrim($path, '/');
    }
}
}
?>

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
      <img src="<?= BASE_URL ?>assets/logo-evy.jpeg" alt="Evy's Store" class="brand-logo">
      <span>Tiendita de Evy</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php if (!empty($_SESSION['user'])): ?>
          <li class="nav-item me-2"><span class="nav-link">Hola, <?= htmlspecialchars($_SESSION['user']['name']) ?></span></li>
          <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <li class="nav-item"><a class="btn btn-sm btn-warning me-2" href="<?= BASE_URL ?>admin">Volver al admin</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>account/statement">Mi estado de cuenta</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>cart">Carrito</a></li>
          <li class="nav-item">
            <form method="post" action="<?= BASE_URL ?>logout" class="d-inline">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(App\Core\Auth::csrfToken()) ?>">
              <button class="btn btn-sm btn-outline-secondary">Salir</button>
            </form>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-sm btn-success ms-2" href="<?= BASE_URL ?>login">Ingresar</a></li>
          <li class="nav-item"><a class="btn btn-sm btn-primary ms-2" href="<?= BASE_URL ?>register">Crear cuenta</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

</nav>

<?php if ($pendingDebt > 0): ?>
  <div class="alert alert-warning text-center mb-0 rounded-0">
    <strong>Saldo impago:</strong> $<?= number_format($pendingDebt, 2) ?>
  </div>
<?php endif; ?>

<main class="container my-3">
