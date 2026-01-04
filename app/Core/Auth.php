<?php
namespace App\Core;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    public static function check() {
        return !empty($_SESSION['user']);
    }

    public static function user() {
        return $_SESSION['user'] ?? null;
    }

    public static function login($u) {
        $_SESSION['user'] = [
            'id'        => (int)($u['id'] ?? 0),
            'name'      => $u['name'] ?? '',
            'email'     => $u['email'] ?? '',
            'role'      => $u['role'] ?? 'customer',
            'is_active' => (int)($u['is_active'] ?? 1),
        ];
    }

    public static function logout() {
        $_SESSION = [];
        session_destroy();
        session_regenerate_id(true);
    }

    public static function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function userId(): ?int {
        return $_SESSION['user']['id'] ?? null;
    }
}
