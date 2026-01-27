<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\ActivityLogger;

class CheckoutController {

    private function pdo() {
        return require __DIR__ . '/../Config/db.php';
    }

    private function getCartItems() {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0;

        if (!empty($cart)) {
            $ids = array_map('intval', array_keys($cart));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo()->prepare("SELECT id, name, price, stock, is_active FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                $pid = (int)$row['id'];
                $qty = (int)($cart[$pid] ?? 0);
                if ($qty > 0) {
                    $row['qty'] = $qty;
                    $row['subtotal'] = $qty * (float)$row['price'];
                    $items[] = $row;
                    $total += $row['subtotal'];
                }
            }
        }

        return [$items, $total];
    }

    public function checkoutForm() {
        if (empty($_SESSION['user'])) {
            header('Location: '.BASE_URL.'login');
            exit;
        }

        list($items, $total) = $this->getCartItems();
        if (empty($_SESSION['checkout_token'])) {
            $_SESSION['checkout_token'] = bin2hex(random_bytes(16));
        }
        $checkoutToken = $_SESSION['checkout_token'];
        $csrf = Auth::csrfToken();
        include __DIR__ . '/../Views/store/checkout.php';
    }

    public function submitOrder() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user'])) {
                http_response_code(403);
                exit('No autorizado.');
            }

            $csrf = $_POST['csrf'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                exit('Token CSRF invalido.');
            }

            $orderToken = $_POST['order_token'] ?? '';
            if (empty($orderToken) || empty($_SESSION['checkout_token']) || !hash_equals($_SESSION['checkout_token'], $orderToken)) {
                http_response_code(400);
                exit('Token de pedido invalido.');
            }

            list($items, $total) = $this->getCartItems();
            if (empty($items)) {
                exit('Carrito vacio.');
            }

            $userId = $_SESSION['user']['id'];
            $method = $_POST['payment_method'] ?? 'cash';
            $methodMap = [
                'cash' => 'CASH',
                'credit' => 'CREDIT',
                'yappi' => 'TRANSFER',
                'transfer' => 'TRANSFER',
            ];
            if (!isset($methodMap[$method])) {
                http_response_code(400);
                exit('Metodo de pago invalido.');
            }
            $paymentMethod = $methodMap[$method];
            $paymentStatus = $paymentMethod === 'CREDIT' ? 'unpaid' : 'paid';
            $amountPaid = $paymentStatus === 'paid' ? $total : 0.00;

            $pdo = $this->pdo();

            $dupStmt = $pdo->prepare("SELECT id FROM orders WHERE order_token = ? LIMIT 1");
            $dupStmt->execute([$orderToken]);
            if ($dupStmt->fetch()) {
                unset($_SESSION['cart'], $_SESSION['checkout_token']);
                header('Location: ' . BASE_URL . 'checkout/success');
                exit;
            }

            $receiptPath = null;
            $needsReceipt = in_array($method, ['yappi', 'transfer', 'cash'], true);
            if ($needsReceipt) {
                if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    exit('Debes adjuntar el comprobante o la foto del pago.');
                }

                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['receipt']['tmp_name']);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                ];
                if (!isset($allowed[$mime])) {
                    http_response_code(400);
                    exit('Formato de imagen invalido.');
                }

                if (!is_uploaded_file($_FILES['receipt']['tmp_name'])) {
                    http_response_code(400);
                    exit('Archivo invalido.');
                }

                $ext = $allowed[$mime];
                $prefix = $method === 'cash' ? 'cash_' : 'transfer_';
                $receiptName = uniqid($prefix, true) . '.' . $ext;
                $uploadDir = __DIR__ . '/../../public/uploads/receipts/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadDir . $receiptName);
                $receiptPath = 'uploads/receipts/' . $receiptName;
            }

            $pdo->beginTransaction();

            // Lock stock rows to prevent oversell
            $ids = array_map(function($it){ return (int)$it['id']; }, $items);
            $place = implode(',', array_fill(0, count($ids), '?'));
            $lockStmt = $pdo->prepare("SELECT id, stock, is_active FROM products WHERE id IN ($place) FOR UPDATE");
            $lockStmt->execute($ids);
            $locked = [];
            foreach ($lockStmt->fetchAll() as $row) {
                $locked[(int)$row['id']] = $row;
            }

            foreach ($items as $it) {
                $pid = (int)$it['id'];
                $qty = (int)$it['qty'];
                $row = $locked[$pid] ?? null;
                if (!$row || (int)$row['is_active'] !== 1) {
                    $pdo->rollBack();
                    exit('Producto no disponible.');
                }
                if ((int)$row['stock'] < $qty) {
                    $pdo->rollBack();
                    exit('Stock insuficiente.');
                }
            }

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, payment_method, payment_status, amount_paid, receipt_path, order_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$userId, $total, $paymentMethod, $paymentStatus, $amountPaid, $receiptPath, $orderToken]);
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') {
                    $pdo->rollBack();
                    unset($_SESSION['cart'], $_SESSION['checkout_token']);
                    header('Location: ' . BASE_URL . 'checkout/success');
                    exit;
                }
                throw $e;
            }

            $orderId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $updStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            foreach ($items as $item) {
                $subtotal = $item['qty'] * $item['price'];
                $itemStmt->execute([$orderId, $item['id'], $item['qty'], $item['price'], $subtotal]);
                $updStock->execute([$item['qty'], $item['id']]);
            }

            if ($paymentMethod === 'CREDIT') {
                $stmt = $pdo->prepare("INSERT INTO credit_debts (user_id, order_id, amount) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $orderId, $total]);

                $pdo->prepare('INSERT INTO credit_transactions (user_id, order_id, type, method, amount) VALUES (?,?,?,?,?)')
                    ->execute([$userId, $orderId, 'CHARGE', null, $total]);

                $pdo->prepare('INSERT INTO credit_accounts (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
                    ->execute([$userId, $total]);
            }

            ActivityLogger::markCartCompleted($pdo, (int)$userId);

            $pdo->commit();
            unset($_SESSION['cart'], $_SESSION['checkout_token']);

            header('Location: ' . BASE_URL . 'checkout/success');
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo "Error interno: " . $e->getMessage();
        }
    }

    public function checkoutSuccess() {
        if (empty($_SESSION['user'])) {
            header('Location: '.BASE_URL.'login');
            exit;
        }

        include __DIR__ . '/../Views/store/checkout_success.php';
    }
}
