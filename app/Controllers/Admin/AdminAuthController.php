<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;

class AdminAuthController extends AdminBaseController
{
    public function loginForm(): void
    {
        $title = 'Login Administracion';
        include __DIR__ . '/../../Views/admin/login.php';
    }

    public function login(): void
    {
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido, recarga la pagina.';
            header('Location: ' . BASE_URL . 'admin/login');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, is_active FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($pass, $u['password_hash'] ?? '')) {
            $_SESSION['flash_error'] = 'Credenciales invalidas';
            header('Location: ' . BASE_URL . 'admin/login');
            return;
        }

        if (($u['role'] ?? 'customer') !== 'admin' || (int)$u['is_active'] !== 1) {
            $_SESSION['flash_error'] = 'No autorizado para el panel de administracion';
            header('Location: ' . BASE_URL . 'admin/login');
            return;
        }

        Auth::login([
            'id'        => (int)$u['id'],
            'name'      => (string)$u['name'],
            'email'     => (string)$u['email'],
            'role'      => (string)$u['role'],
            'is_active' => (int)$u['is_active'],
        ]);

        header('Location: ' . BASE_URL . 'admin');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . BASE_URL . 'admin/login');
    }
}
