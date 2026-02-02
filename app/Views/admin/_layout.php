<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Admin' ?> | Tiendita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { border-radius: 12px; }
        :root {
            --brand-blue: #c1ddf2;
            --brand-red: #ef4e53;
            --brand-yellow: #fce772;
            --brand-green: #2f9e44;
            --brand-ink: #1e1e1e;
        }
        .btn-brand-primary { background: var(--brand-blue); border-color: var(--brand-blue); color: var(--brand-ink); }
        .btn-brand-primary:hover { filter: brightness(0.95); color: var(--brand-ink); }
        .btn-brand-danger { background: var(--brand-red); border-color: var(--brand-red); color: #fff; }
        .btn-brand-danger:hover { filter: brightness(0.95); color: #fff; }
        .btn-brand-warn { background: var(--brand-yellow); border-color: var(--brand-yellow); color: var(--brand-ink); }
        .btn-brand-warn:hover { filter: brightness(0.95); color: var(--brand-ink); }
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
        @media (max-width: 768px) {
            table.table-responsive-stack thead { display: none; }
            table.table-responsive-stack tr { display: block; margin-bottom: 0.75rem; border: 1px solid #e9ecef; border-radius: 10px; }
            table.table-responsive-stack td { display: flex; justify-content: space-between; gap: 1rem; padding: 0.5rem 0.75rem; border: none; border-bottom: 1px solid #f1f3f5; }
            table.table-responsive-stack td:last-child { border-bottom: none; }
            table.table-responsive-stack td::before { content: attr(data-label); font-weight: 600; color: #6c757d; }
        }
    </style>
</head>
<body class="bg-light">
<?php
if (!function_exists('asset_url')) {
    function asset_url($path) {
        if ($path === '' || $path === null) return '';
        if (preg_match('#^https?://#i', $path)) return $path;
        if (strpos($path, '/') === 0) return $path;
        return BASE_URL . ltrim($path, '/');
    }
}
?>
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>admin">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/products">Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/customers">Clientes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/activity">Actividad</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>">Tienda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/logout">Salir</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php include __DIR__ . '/_flash.php'; ?>
        <?= $content ?? '' ?>
    </div>
    <footer class="text-center text-muted small py-4">
        Powered by DC SOLUTIONS · info@dcsolution.net
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
