<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Mailer;

class AuthController {

    private function pdo() {
        return require __DIR__ . '/../Config/db.php';
    }

    private function config() {
        return require __DIR__ . '/../Config/config.php';
    }

    // ---- Vistas ----
    public function loginForm(): void {
        $title = 'Iniciar Sesion';
        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['flash_ok'] ?? '';
        unset($_SESSION['error'], $_SESSION['flash_ok']);
        $next = $_GET['next'] ?? '';
        $csrf = Auth::csrfToken();
        include __DIR__ . '/../Views/auth/login.php';
    }

    public function registerForm() {
        $title = 'Crear cuenta';
        $csrf  = Auth::csrfToken();
        $error = $_GET['error'] ?? null;
        include __DIR__.'/../Views/auth/register.php';
    }

    public function forgotForm(): void {
        $title = 'Recuperar contrasena';
        $csrf = Auth::csrfToken();
        $success = $_SESSION['flash_ok'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['flash_ok'], $_SESSION['error']);
        include __DIR__ . '/../Views/auth/forgot.php';
    }

    public function sendReset(): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalido.';
            header('Location: ' . BASE_URL . 'forgot');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $_SESSION['error'] = 'Email invalido.';
            header('Location: ' . BASE_URL . 'forgot');
            exit;
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expires = (new \DateTime('+1 hour'))->format('Y-m-d H:i:s');
            $ins = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)');
            $ins->execute([(int)$user['id'], $hash, $expires]);

            $config = $this->config();
            $base = rtrim($config['app']['base_url_full'] ?? '', '/');
            $link = $base . '/reset/' . $token;
            $subject = 'Recuperacion de contrasena';
            $body = "Hola,\n\nPara restablecer tu contrasena ingresa al siguiente enlace:\n$link\n\nEste enlace expira en 1 hora.\n";
            Mailer::send($email, $subject, $body, $config['smtp'] ?? []);
        }

        $_SESSION['flash_ok'] = 'Si el correo existe, recibirás un enlace para restablecer tu contrasena.';
        header('Location: ' . BASE_URL . 'forgot');
        exit;
    }

    public function resetForm($params): void {
        $token = is_array($params) ? ($params['token'] ?? '') : (string)$params;
        $token = trim($token);
        if ($token === '') {
            http_response_code(404);
            echo 'Token invalido';
            return;
        }
        $title = 'Restablecer contrasena';
        $csrf = Auth::csrfToken();
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);
        include __DIR__ . '/../Views/auth/reset.php';
    }

    public function resetSubmit($params): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalido.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $token = is_array($params) ? ($params['token'] ?? '') : (string)$params;
        $token = trim($token);
        $pass = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password_confirm'] ?? '');

        if ($token === '' || $pass === '' || $pass !== $pass2 || strlen($pass) < 6) {
            $_SESSION['error'] = 'Datos invalidos.';
            header('Location: ' . BASE_URL . 'reset/' . urlencode($token));
            exit;
        }

        $hash = hash('sha256', $token);
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at FROM password_resets WHERE token_hash = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row || $row['used_at'] !== null || strtotime($row['expires_at']) < time()) {
            $_SESSION['error'] = 'Token invalido o expirado.';
            header('Location: ' . BASE_URL . 'reset/' . urlencode($token));
            exit;
        }

        $newHash = password_hash($pass, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, (int)$row['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$row['id']]);

        $_SESSION['flash_ok'] = 'Contrasena actualizada. Ya puedes iniciar sesion.';
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    // ---- Acciones ----
    public function login(): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) {
            $_SESSION['error'] = 'CSRF invalido.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $pdo = $this->pdo();

        $email    = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            $_SESSION['error'] = 'Credenciales invalidas.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        if (($user['is_active'] ?? 0) != 1) {
            $_SESSION['error'] = 'Cuenta inactiva.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        Auth::login([
            'id'        => (int)$user['id'],
            'name'      => (string)$user['name'],
            'email'     => (string)$user['email'],
            'role'      => (string)$user['role'],
            'is_active' => (int)$user['is_active'],
        ]);

        if ($user['role'] === 'admin') {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        $next = $_POST['next'] ?? '';
        if ($next && strpos($next, '/') === 0 && strpos($next, '//') !== 0) {
            header('Location: ' . BASE_URL . ltrim($next, '/'));
        } else {
            header('Location: ' . BASE_URL);
        }
        exit;
    }

    public function register() {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) {
            http_response_code(400); echo 'CSRF invalido'; return;
        }

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password_confirm'] ?? '');

        if ($name === '' || $email === '' || $pass === '' || $pass !== $pass2) {
            header('Location: '.BASE_URL.'register?error=Datos+invalidos'); return;
        }

        $pdo = $this->pdo();
        $c = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $c->execute([$email]);
        if ($c->fetch()) {
            header('Location: '.BASE_URL.'register?error=Email+ya+registrado'); return;
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,is_active) VALUES (?,?,?,"customer",1)');
        $ins->execute([$name,$email,$hash]);

        $id = (int)$pdo->lastInsertId();
        Auth::login(['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'customer','is_active'=>1]);
        header('Location: ' . BASE_URL);
        exit;
    }

    public function logout() {
        Auth::logout();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}
