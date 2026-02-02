<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;

class AdminProductsController extends AdminBaseController
{
    private function handleImageUpload(): ?string
{
        if (empty($_FILES['image_file']) || !isset($_FILES['image_file']['error'])) {
            return null;
        }
        if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $name = $_FILES['image_file']['name'] ?? '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
            return null;
        }

        return 'uploads/products/' . $filename;
    }


    private function normalizeImageUrl(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') return '';
        if (preg_match('#^https?://#i', $imageUrl)) return $imageUrl;
        if (strpos($imageUrl, '/') === 0) return $imageUrl;
        if (strpos($imageUrl, '/') !== false) return $imageUrl;
        return 'uploads/products/' . ltrim($imageUrl, '/');
    }



    public function index(): void
    {
        $this->requireAdmin();
        $pdo = $this->pdo();
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?
                ORDER BY p.id DESC");
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC");
        }
        $products = $stmt->fetchAll();

        $this->view('products/index', [
            'title' => 'Productos',
            'products' => $products,
            'q' => $q,
        ]);
    }

    public function createForm(): void
    {
        $this->requireAdmin();
        $pdo = $this->pdo();
        $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
        $csrf = Csrf::token();

        $this->view('products/form', [
            'title' => 'Nuevo producto',
            'product' => null,
            'categories' => $categories,
            'csrf' => $csrf,
            'action' => BASE_URL . 'admin/products/create',
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/products');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cost = (float)($_POST['cost'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $categoryId = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $price < 0 || $cost < 0 || $stock < 0) {
            $_SESSION['flash_error'] = 'Datos invalidos.';
            header('Location: ' . BASE_URL . 'admin/products/create');
            return;
        }

        $uploaded = $this->handleImageUpload();
        if ($uploaded) {
            $imageUrl = $uploaded;
        } else {
            $imageUrl = $this->normalizeImageUrl($imageUrl);
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price, cost, stock, image_url, is_active) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$categoryId, $name, $description, $price, $cost, $stock, $imageUrl ?: null, $isActive]);

        $_SESSION['flash_ok'] = 'Producto creado.';
        header('Location: ' . BASE_URL . 'admin/products');
    }

    public function editForm($params): void
    {
        $this->requireAdmin();
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            http_response_code(404);
            echo 'Producto no encontrado';
            return;
        }

        $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
        $csrf = Csrf::token();

        $this->view('products/form', [
            'title' => 'Editar producto',
            'product' => $product,
            'categories' => $categories,
            'csrf' => $csrf,
            'action' => BASE_URL . 'admin/products/' . $id . '/edit',
        ]);
    }

    public function update($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/products');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cost = (float)($_POST['cost'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $categoryId = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || $name === '' || $price < 0 || $cost < 0 || $stock < 0) {
            $_SESSION['flash_error'] = 'Datos invalidos.';
            header('Location: ' . BASE_URL . 'admin/products');
            return;
        }

        $uploaded = $this->handleImageUpload();
        if ($uploaded) {
            $imageUrl = $uploaded;
        } else {
            $imageUrl = $this->normalizeImageUrl($imageUrl);
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, cost=?, stock=?, image_url=?, is_active=? WHERE id=?');
        $stmt->execute([$categoryId, $name, $description, $price, $cost, $stock, $imageUrl ?: null, $isActive, $id]);

        $_SESSION['flash_ok'] = 'Producto actualizado.';
        header('Location: ' . BASE_URL . 'admin/products');
    }

    public function deactivate($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/products');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('UPDATE products SET is_active=0 WHERE id=?');
        $stmt->execute([$id]);

        $_SESSION['flash_ok'] = 'Producto desactivado.';
        header('Location: ' . BASE_URL . 'admin/products');
    }

    public function activate($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/products');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('UPDATE products SET is_active=1 WHERE id=?');
        $stmt->execute([$id]);

        $_SESSION['flash_ok'] = 'Producto activado.';
        header('Location: ' . BASE_URL . 'admin/products');
    }
}
