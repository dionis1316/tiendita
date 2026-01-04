public function login(): void {
    if (!\App\Core\Csrf::check($_POST['csrf'] ?? null)) {
        $_SESSION['flash_error'] = 'CSRF inválido, recarga la página.';
        header('Location: /admin/login');
        return;
    }

    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    $pdo = $this->pdo();
    $stmt = $pdo->prepare('SELECT id, name, email, password, role, is_active FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($pass, $u['password'])) {
        $_SESSION['flash_error'] = 'Credenciales inválidas';
        header('Location: /admin/login');
        return;
    }

    if (($u['role'] ?? 'customer') !== 'admin' || (int)$u['is_active'] !== 1) {
        $_SESSION['flash_error'] = 'No autorizado para el panel de administración';
        header('Location: /admin/login');
        return;
    }

    // 🔒 Asegura que el middleware tenga todo lo que necesita
    // Si tu App\Core\Auth::login() guarda todo el arreglo, perfecto.
    // Si no, al menos garantizamos en $_SESSION['user'] esos campos.
    \App\Core\Auth::login([
        'id'        => (int)$u['id'],
        'name'      => (string)$u['name'],
        'email'     => (string)$u['email'],
        'role'      => (string)$u['role'],
        'is_active' => (int)$u['is_active'],
    ]);

    // Redirección al dashboard
    header('Location: /admin');
}