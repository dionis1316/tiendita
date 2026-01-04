<?php
namespace App\Controllers;

use App\Core\Auth;

class StoreController {

    private function pdo() {
        return require __DIR__ . '/../Config/db.php';
    }

    public function home(): void {
        $pdo = $this->pdo();
        $stmt = $pdo->query("SELECT p.id, p.name, p.price, p.image_url, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active=1 ORDER BY p.created_at DESC");
        $products = $stmt->fetchAll();
        $title = 'Inicio';
        include __DIR__ . '/../Views/store/home.php';
    }

    public function show($params): void {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;

        if ($id <= 0) {
            http_response_code(404);
            echo "Producto no valido";
            return;
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo "Producto no encontrado";
            return;
        }

        $csrf = \App\Core\Auth::csrfToken();
        $title = $product['name'] ?? 'Producto';
        include __DIR__ . '/../Views/store/product.php';
    }
}
