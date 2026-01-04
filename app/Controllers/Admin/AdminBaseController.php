<?php
namespace App\Controllers\Admin;

use App\Core\Auth;

abstract class AdminBaseController {
    protected function pdo(): \PDO {
        return require __DIR__ . '/../../Config/db.php';
    }

    protected function requireAdmin(): array {
        $user = Auth::user();
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        return $user;
    }

    protected function view(string $tpl, array $data = []): void {
        if (!function_exists('asset_url')) {
            function asset_url($path) {
                if ($path === '' || $path === null) return '';
                if (preg_match('#^https?://#i', $path)) return $path;
                if (strpos($path, '/') === 0) return $path;
                return BASE_URL . ltrim($path, '/');
            }
        }
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../../Views/admin/' . $tpl . '.php';
        $layout   = __DIR__ . '/../../Views/admin/_layout.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo "Vista no encontrada: {$tpl}";
            return;
        }
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        include $layout;
    }
}
