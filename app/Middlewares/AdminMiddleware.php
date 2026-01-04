<?php
namespace App\Middlewares;

use App\Core\Auth;

class AdminMiddleware {
    public static function handle(): void {
        if (!Auth::check()) {
            header('Location: /admin/login');
            exit;
        }
        $user = Auth::user();
        if (($user['role'] ?? 'customer') !== 'admin' || ($user['is_active'] ?? 0) != 1) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }
}