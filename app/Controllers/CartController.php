<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\ActivityLogger;

class CartController {

    private function pdo() {
        return require __DIR__ . '/../Config/db.php';
    }

    private function &cartRef() {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        return $_SESSION['cart']; // array [product_id => qty]
    }

    public function view(): void {
        $title = 'Carrito';
        $cart = $this->cartRef();

        $items = [];
        if (!empty($cart)) {
            $ids = array_map('intval', array_keys($cart));
            $place = implode(',', array_fill(0, count($ids), '?'));
            $pdo = $this->pdo();
            $stmt = $pdo->prepare("SELECT id, name, price, image_url, stock FROM products WHERE id IN ($place)");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) {
                $pid = (int)$r['id'];
                $qty = (int)($cart[$pid] ?? 0);
                if ($qty > 0) {
                    $r['qty'] = $qty;
                    $r['subtotal'] = $qty * (float)$r['price'];
                    $items[] = $r;
                }
            }
        }
        $csrf = Auth::csrfToken();
        include __DIR__ . '/../Views/store/cart.php';
    }

    public function add(): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) { http_response_code(400); echo 'CSRF'; return; }

        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if ($pid <= 0 || $qty <= 0) { header('Location: '.BASE_URL); return; }

        $pdo = $this->pdo();
        $st = $pdo->prepare("SELECT name, stock FROM products WHERE id=? AND is_active=1");
        $st->execute([$pid]);
        $row = $st->fetch();
        if (!$row) { header('Location: '.BASE_URL); return; }

        $cart = &$this->cartRef();
        $current = (int)($cart[$pid] ?? 0);
        $newQty = min($current + $qty, (int)$row['stock']);
        if ($newQty <= 0) { unset($cart[$pid]); }
        else { $cart[$pid] = $newQty; }

        if (Auth::check()) {
            ActivityLogger::log(
                $pdo,
                (int)Auth::userId(),
                'cart_add',
                $pid,
                (string)$row['name'],
                $qty
            );
        }

        header('Location: '.BASE_URL.'cart');
    }

    public function update(): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) { http_response_code(400); echo 'CSRF'; return; }

        $quantities = $_POST['qty'] ?? [];
        $cart = &$this->cartRef();
        $pdo = $this->pdo();

        foreach ($quantities as $pid => $q) {
            $pid = (int)$pid; $q = (int)$q;
            if ($pid <= 0) continue;
            if ($q <= 0) { unset($cart[$pid]); continue; }
            $st = $pdo->prepare("SELECT stock FROM products WHERE id=? AND is_active=1");
            $st->execute([$pid]);
            $row = $st->fetch();
            if (!$row) { unset($cart[$pid]); continue; }
            $cart[$pid] = min($q, (int)$row['stock']);
        }
        header('Location: '.BASE_URL.'cart');
    }

    public function remove(): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) { http_response_code(400); echo 'CSRF'; return; }

        $pid = (int)($_POST['product_id'] ?? 0);
        $cart = &$this->cartRef();
        unset($cart[$pid]);
        header('Location: '.BASE_URL.'cart');
    }

    public function checkout(): void {
        if (!Auth::check()) { header('Location: '.BASE_URL.'login?next=cart'); return; }

        $cart = $this->cartRef();
        if (empty($cart)) { header('Location: '.BASE_URL.'cart'); return; }

        $title = 'Finalizar compra';
        $csrf = Auth::csrfToken();
        include __DIR__ . '/../Views/store/checkout.php';
    }

    public function placeOrder(): void {
        if (!Auth::verifyCsrfToken($_POST['csrf'] ?? '')) { http_response_code(400); echo 'CSRF'; return; }

        if (!Auth::check()) { header('Location: '.BASE_URL.'login'); return; }

        $user_id = Auth::userId();
        $method = $_POST['payment_method'] ?? '';
        $allowed = ['cash', 'yappi', 'credit'];
        if (!in_array($method, $allowed, true)) { http_response_code(400); echo 'Metodo invalido'; return; }

        $cart = $this->cartRef();
        if (empty($cart)) { header('Location: '.BASE_URL.'cart'); return; }

        $pdo = $this->pdo();
        $pdo->beginTransaction();
        $ids = array_keys($cart);
        $place = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($place) FOR UPDATE");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();

        $total = 0;
        $items = [];
        foreach ($products as $p) {
            $pid = (int)$p['id'];
            $qty = (int)($cart[$pid] ?? 0);
            $stock = (int)$p['stock'];
            $price = (float)$p['price'];
            if ($qty <= 0 || $stock < $qty) {
                $pdo->rollBack();
                echo "Stock insuficiente para: " . htmlspecialchars($p['name']);
                return;
            }
            $subtotal = $qty * $price;
            $total += $subtotal;
            $items[] = ['id' => $pid, 'qty' => $qty, 'price' => $price, 'subtotal' => $subtotal];
        }

        $payment_status = $method === 'credit' ? 'unpaid' : 'paid';
        $ins = $pdo->prepare("INSERT INTO orders (user_id, payment_method, payment_status, total, created_at) VALUES (?,?,?,?,NOW())");
        $ins->execute([$user_id, $method, $payment_status, $total]);

        $order_id = (int)$pdo->lastInsertId();

        $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?,?,?,?,?)");
        $upd_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($items as $i) {
            $ins_item->execute([$order_id, $i['id'], $i['qty'], $i['price'], $i['subtotal']]);
            $upd_stock->execute([$i['qty'], $i['id']]);
        }

        $pdo->commit();
        $_SESSION['cart'] = [];
        header('Location: '.BASE_URL.'order/confirmation');
    }
}
