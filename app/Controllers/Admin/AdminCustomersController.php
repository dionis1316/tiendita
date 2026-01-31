<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Mailer;

class AdminCustomersController extends AdminBaseController
{
    private function parseDateFilters(): array
    {
        $start = trim($_GET['start'] ?? '');
        $end = trim($_GET['end'] ?? '');
        return [$start, $end];
    }

    private function buildDateWhere(string $field, ?string $start, ?string $end, array &$params): string
    {
        $where = '';
        if ($start !== '') {
            $where .= " AND {$field} >= ?";
            $params[] = $start . ' 00:00:00';
        }
        if ($end !== '') {
            $where .= " AND {$field} <= ?";
            $params[] = $end . ' 23:59:59';
        }
        return $where;
    }

    private function config() {
        return require __DIR__ . '/../../Config/config.php';
    }

    public function index(): void
    {
        $this->requireAdmin();
        $pdo = $this->pdo();
        $q = trim($_GET['q'] ?? '');
        $baseSql = "SELECT u.id, u.name, u.email, u.is_active,
            COUNT(o.id) AS orders_count,
            COALESCE(SUM(o.total), 0) AS total_spent,
            COALESCE(SUM(CASE WHEN o.payment_status = 'unpaid' AND o.payment_method = 'CREDIT' THEN (o.total - o.amount_paid) ELSE 0 END), 0) AS total_debt
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id
            WHERE u.role IN ('customer','admin')";
        $params = [];
        if ($q !== '') {
            $baseSql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $baseSql .= " GROUP BY u.id ORDER BY u.id DESC";
        if ($params) {
            $stmt = $pdo->prepare($baseSql);
            $stmt->execute($params);
        } else {
            $stmt = $pdo->query($baseSql);
        }
        $customers = $stmt->fetchAll();

        $this->view('customers/index', [
            'title' => 'Clientes',
            'customers' => $customers,
            'q' => $q,
        ]);
    }

    public function show($params): void
    {
        $this->requireAdmin();
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();

        $stmt = $pdo->prepare('SELECT id, name, email, is_active, created_at FROM users WHERE id = ? AND role IN ("customer","admin")');
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            http_response_code(404);
            echo 'Cliente no encontrado';
            return;
        }

        $ca = $pdo->prepare('SELECT credit_limit, balance, terms_days, status FROM credit_accounts WHERE user_id = ?');
        $ca->execute([$id]);
        $creditAccount = $ca->fetch();

        list($start, $end) = $this->parseDateFilters();

        $orderParams = [$id];
        $orderWhere = "user_id = ?" . $this->buildDateWhere('created_at', $start, $end, $orderParams);
        $ordersStmt = $pdo->prepare("SELECT id, total, payment_method, payment_status, amount_paid, due_date, receipt_path, created_at
            FROM orders WHERE {$orderWhere} ORDER BY created_at DESC");
        $ordersStmt->execute($orderParams);
        $orders = $ordersStmt->fetchAll();

        $orderIds = array_map(function($o){ return (int)$o['id']; }, $orders);
        $itemsByOrder = [];
        if (!empty($orderIds)) {
            $place = implode(',', array_fill(0, count($orderIds), '?'));
            $itemsStmt = $pdo->prepare("SELECT oi.order_id, p.name, oi.quantity, oi.price, oi.subtotal
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id IN ($place)");
            $itemsStmt->execute($orderIds);
            foreach ($itemsStmt->fetchAll() as $row) {
                $itemsByOrder[$row['order_id']][] = $row;
            }
        }

        $txParams = [$id];
        $txWhere = "user_id = ?" . $this->buildDateWhere('created_at', $start, $end, $txParams);
        $txStmt = $pdo->prepare("SELECT id, type, method, amount, reference, created_at FROM credit_transactions WHERE {$txWhere} ORDER BY created_at DESC");
        $txStmt->execute($txParams);
        $transactions = $txStmt->fetchAll();

        $unpaidStmt = $pdo->prepare("SELECT id, total, amount_paid FROM orders WHERE user_id = ? AND payment_status = 'unpaid' ORDER BY created_at DESC");
        $unpaidStmt->execute([$id]);
        $unpaidOrders = $unpaidStmt->fetchAll();

        $productsStmt = $pdo->query("SELECT id, name, price, stock, is_active FROM products WHERE is_active = 1 ORDER BY name ASC");
        $products = $productsStmt->fetchAll();

        $this->view('customers/show', [
            'title' => 'Estado de cuenta',
            'customer' => $customer,
            'creditAccount' => $creditAccount,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'transactions' => $transactions,
            'unpaidOrders' => $unpaidOrders,
            'products' => $products,
            'start' => $start,
            'end' => $end,
        ]);
    }


    public function createManualOrder($params): void
    {
        $this->requireAdmin();
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $redirect = BASE_URL . 'admin/customers' . ($id > 0 ? '/' . $id : '');

        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . $redirect);
            return;
        }

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Cliente invalido.';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $method = strtoupper(trim($_POST['payment_method'] ?? 'CASH'));
        if (!in_array($method, ['CASH', 'TRANSFER', 'CREDIT'], true)) {
            $_SESSION['flash_error'] = 'Metodo de pago invalido.';
            header('Location: ' . $redirect);
            return;
        }

        $rawItems = $_POST['items'] ?? [];
        $qtyById = [];
        foreach ($rawItems as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if ($pid > 0 && $qty > 0) {
                $qtyById[$pid] = $qty;
            }
        }
        if (empty($qtyById)) {
            $_SESSION['flash_error'] = 'Selecciona al menos un producto.';
            header('Location: ' . $redirect);
            return;
        }

        $pdo = $this->pdo();
        $custStmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? AND role IN ("customer","admin")');
        $custStmt->execute([$id]);
        $customer = $custStmt->fetch();
        if (!$customer) {
            $_SESSION['flash_error'] = 'Cliente no encontrado.';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        try {
            $pdo->beginTransaction();

            $ids = array_keys($qtyById);
            $place = implode(',', array_fill(0, count($ids), '?'));
            $lockStmt = $pdo->prepare("SELECT id, name, price, stock, is_active FROM products WHERE id IN ($place) FOR UPDATE");
            $lockStmt->execute($ids);
            $rows = $lockStmt->fetchAll();

            $found = [];
            $items = [];
            $total = 0.0;

            foreach ($rows as $row) {
                $pid = (int)$row['id'];
                $found[$pid] = true;
                $qty = (int)($qtyById[$pid] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                if ((int)$row['is_active'] !== 1) {
                    throw new \RuntimeException('Producto no disponible.');
                }
                if ((int)$row['stock'] < $qty) {
                    throw new \RuntimeException('Stock insuficiente para ' . ($row['name'] ?? 'producto') . '.');
                }
                $price = (float)$row['price'];
                $subtotal = $price * $qty;
                $items[] = [
                    'id' => $pid,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }

            foreach ($ids as $pid) {
                if (empty($found[$pid])) {
                    throw new \RuntimeException('Producto no encontrado.');
                }
            }

            if ($total <= 0 || empty($items)) {
                throw new \RuntimeException('Selecciona al menos un producto.');
            }

            $paymentStatus = $method === 'CREDIT' ? 'unpaid' : 'paid';
            $amountPaid = $paymentStatus === 'paid' ? $total : 0.00;
            $orderToken = 'admin_' . bin2hex(random_bytes(12));

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, payment_method, payment_status, amount_paid, receipt_path, order_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $total, $method, $paymentStatus, $amountPaid, null, $orderToken]);

            $orderId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $updStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            foreach ($items as $item) {
                $itemStmt->execute([$orderId, $item['id'], $item['qty'], $item['price'], $item['subtotal']]);
                $updStock->execute([$item['qty'], $item['id']]);
            }

            if ($method === 'CREDIT') {
                $stmt = $pdo->prepare("INSERT INTO credit_debts (user_id, order_id, amount) VALUES (?, ?, ?)");
                $stmt->execute([$id, $orderId, $total]);

                $pdo->prepare('INSERT INTO credit_transactions (user_id, order_id, type, method, amount) VALUES (?,?,?,?,?)')
                    ->execute([$id, $orderId, 'CHARGE', null, $total]);

                $pdo->prepare('INSERT INTO credit_accounts (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
                    ->execute([$id, $total]);
            }

            $pdo->commit();
            $_SESSION['flash_ok'] = 'Pedido manual registrado para ' . ($customer['name'] ?? 'el cliente') . '.';
        } catch (xception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = 'No se pudo registrar el pedido: ' . $e->getMessage();
        }

        header('Location: ' . $redirect);
    }

    public function addPayment($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $amount = (float)($_POST['amount'] ?? 0);
        $method = $_POST['method'] ?? 'CASH';
        $reference = trim($_POST['reference'] ?? '');
        $orderId = (int)($_POST['order_id'] ?? 0);
        $orderIds = $_POST['order_ids'] ?? [];
        $orderIds = array_values(array_unique(array_filter(array_map('intval', (array)$orderIds), function ($v) { return $v > 0; })));
        $payAll = ($_POST['pay_all'] ?? '') === '1';

        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Monto invalido.';
            header('Location: ' . BASE_URL . 'admin/customers/' . $id);
            return;
        }

        $method = strtoupper($method);
        if (!in_array($method, ['CASH', 'TRANSFER', 'ADJUSTMENT'], true)) {
            $_SESSION['flash_error'] = 'Metodo invalido.';
            header('Location: ' . BASE_URL . 'admin/customers/' . $id);
            return;
        }

        if (!$payAll && $amount <= 0) {
            $_SESSION['flash_error'] = 'Monto invalido.';
            header('Location: ' . BASE_URL . 'admin/customers/' . $id);
            return;
        }

        if (!empty($orderIds)) {
            $orderId = 0;
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role IN ("customer","admin")');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new \RuntimeException('Cliente no encontrado');
            }

            $applyAmount = $amount;
            if ($orderId > 0) {
                $orderStmt = $pdo->prepare('SELECT id, total, amount_paid FROM orders WHERE id = ? AND user_id = ?');
                $orderStmt->execute([$orderId, $id]);
                $order = $orderStmt->fetch();
                if (!$order) {
                    throw new \RuntimeException('Orden no encontrada');
                }
                $remaining = (float)$order['total'] - (float)$order['amount_paid'];
                if ($remaining <= 0) {
                    $applyAmount = 0;
                } else {
                    $applyAmount = min($amount, $remaining);
                }
                if ($applyAmount <= 0) {
                    throw new \RuntimeException('No hay saldo pendiente en la orden.');
                }
                $amount = $applyAmount;
            }

            $applyTotal = 0.0;
            if ($payAll || !empty($orderIds)) {
                if ($payAll) {
                    $ordersStmt = $pdo->prepare("SELECT id, total, amount_paid, created_at FROM orders WHERE user_id = ? AND payment_status = 'unpaid' ORDER BY created_at ASC");
                    $ordersStmt->execute([$id]);
                } else {
                    $place = implode(',', array_fill(0, count($orderIds), '?'));
                    $ordersStmt = $pdo->prepare("SELECT id, total, amount_paid, created_at FROM orders WHERE user_id = ? AND id IN ($place) ORDER BY created_at ASC");
                    $ordersStmt->execute(array_merge([$id], $orderIds));
                }
                $orders = $ordersStmt->fetchAll();
                $totalRemaining = 0.0;
                foreach ($orders as $o) {
                    $totalRemaining += max((float)$o['total'] - (float)$o['amount_paid'], 0);
                }
                if ($totalRemaining <= 0) {
                    throw new \RuntimeException('No hay saldo pendiente.');
                }
                if ($payAll || $amount <= 0) {
                    $amount = $totalRemaining;
                } else {
                    $amount = min($amount, $totalRemaining);
                }

                $txnStmt = $pdo->prepare('INSERT INTO credit_transactions (user_id, order_id, type, method, amount, reference) VALUES (?,?,?,?,?,?)');
                $txnStmt->execute([$id, null, 'PAYMENT', $method, $amount, $reference ?: null]);
                $txnId = (int)$pdo->lastInsertId();

                $payStmt = $pdo->prepare('INSERT INTO order_payments (order_id, credit_txn_id, amount) VALUES (?,?,?)');
                $upd = $pdo->prepare('UPDATE orders SET amount_paid = amount_paid + ? WHERE id = ?');
                foreach ($orders as $o) {
                    $remaining = (float)$o['total'] - (float)$o['amount_paid'];
                    if ($remaining <= 0) {
                        continue;
                    }
                    if ($amount - $applyTotal <= 0) {
                        break;
                    }
                    $apply = min($remaining, $amount - $applyTotal);
                    if ($apply <= 0) {
                        continue;
                    }
                    $payStmt->execute([(int)$o['id'], $txnId, $apply]);
                    $upd->execute([$apply, (int)$o['id']]);
                    $newPaid = (float)$o['amount_paid'] + $apply;
                    if ($newPaid >= (float)$o['total']) {
                        $pdo->prepare("UPDATE orders SET payment_status='paid', amount_paid=total WHERE id = ?")->execute([(int)$o['id']]);
                        $pdo->prepare('UPDATE credit_debts SET paid=1 WHERE order_id = ?')->execute([(int)$o['id']]);
                    } else {
                        $pdo->prepare("UPDATE orders SET payment_status='unpaid' WHERE id = ?")->execute([(int)$o['id']]);
                    }
                    $applyTotal += $apply;
                }
            } else {
                $txnStmt = $pdo->prepare('INSERT INTO credit_transactions (user_id, order_id, type, method, amount, reference) VALUES (?,?,?,?,?,?)');
                $txnStmt->execute([$id, $orderId > 0 ? $orderId : null, 'PAYMENT', $method, $amount, $reference ?: null]);
                $txnId = (int)$pdo->lastInsertId();

                if ($orderId > 0 && $applyAmount > 0) {
                    $payStmt = $pdo->prepare('INSERT INTO order_payments (order_id, credit_txn_id, amount) VALUES (?,?,?)');
                    $payStmt->execute([$orderId, $txnId, $applyAmount]);

                    $upd = $pdo->prepare('UPDATE orders SET amount_paid = amount_paid + ? WHERE id = ?');
                    $upd->execute([$applyAmount, $orderId]);

                    $statusStmt = $pdo->prepare('SELECT total, amount_paid FROM orders WHERE id = ?');
                    $statusStmt->execute([$orderId]);
                    $row = $statusStmt->fetch();
                    if ($row && (float)$row['amount_paid'] >= (float)$row['total']) {
                        $pdo->prepare("UPDATE orders SET payment_status='paid', amount_paid=total WHERE id = ?")->execute([$orderId]);
                        $pdo->prepare('UPDATE credit_debts SET paid=1 WHERE order_id = ?')->execute([$orderId]);
                    } else {
                        $pdo->prepare("UPDATE orders SET payment_status='unpaid' WHERE id = ?")->execute([$orderId]);
                    }
                }
                $applyTotal = $amount;
            }

            $pdo->prepare('INSERT INTO credit_accounts (user_id, balance) VALUES (?, 0) ON DUPLICATE KEY UPDATE balance = balance')->execute([$id]);
            $pdo->prepare('UPDATE credit_accounts SET balance = GREATEST(balance - ?, 0) WHERE user_id = ?')->execute([$applyTotal, $id]);

            $pdo->commit();
            $_SESSION['flash_ok'] = 'Pago registrado.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Error al registrar el pago.';
        }

        header('Location: ' . BASE_URL . 'admin/customers/' . $id);
    }

    public function deactivate($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Cliente invalido.';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = "customer"');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_ok'] = 'Cliente inactivado.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo inactivar el cliente.';
        }

        header('Location: ' . BASE_URL . 'admin/customers/' . $id);
    }

    public function activate($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Cliente invalido.';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND role = "customer"');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_ok'] = 'Cliente activado.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo activar el cliente.';
        }

        header('Location: ' . BASE_URL . 'admin/customers/' . $id);
    }

    public function exportCsv($params): void
    {
        $this->requireAdmin();
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();

        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role IN ("customer","admin")');
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            http_response_code(404);
            echo 'Cliente no encontrado';
            return;
        }

        list($start, $end) = $this->parseDateFilters();
        $params = [$id];
        $where = "user_id = ?" . $this->buildDateWhere('created_at', $start, $end, $params);
        $ordersStmt = $pdo->prepare("SELECT id, total, payment_method, payment_status, amount_paid, created_at FROM orders WHERE {$where} ORDER BY created_at DESC");
        $ordersStmt->execute($params);
        $orders = $ordersStmt->fetchAll();

        $pendingDebt = 0.0;
        foreach ($orders as $o) {
            if (($o['payment_status'] ?? '') === 'unpaid' && ($o['payment_method'] ?? '') === 'CREDIT') {
                $pendingDebt += ((float)$o['total'] - (float)$o['amount_paid']);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="estado_cuenta_' . $id . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Cliente', $customer['name'], $customer['email']]);
        fputcsv($out, ['Saldo impago', number_format($pendingDebt, 2)]);
        fputcsv($out, ['Orden', 'Fecha', 'Total', 'Metodo', 'Pagado', 'Estado']);
        foreach ($orders as $o) {
            fputcsv($out, [$o['id'], $o['created_at'], $o['total'], $o['payment_method'], $o['amount_paid'], $o['payment_status']]);
        }
        fclose($out);
    }

    private function outputPdf(array $lines, string $title): void
    {
        $y = 750;
        $content = "BT\n/F1 12 Tf\n50 $y Td\n";
        $escaped = function($text) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace('(', '\\(', $text);
            $text = str_replace(')', '\\)', $text);
            return $text;
        };
        $content .= '(' . $escaped($title) . ") Tj\nT*\n";
        foreach ($lines as $line) {
            $content .= '(' . $escaped($line) . ") Tj\nT*\n";
        }
        $content .= "ET";

        $len = strlen($content);
        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj";
        $objects[] = "4 0 obj << /Length $len >> stream\n$content\nendstream endobj";
        $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";

        $pdf = "%PDF-1.4\n";
        $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }
        foreach ($offsets as $off) {
            $xref .= str_pad((string)$off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $startxref = strlen($pdf);
        $pdf .= $xref;
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$startxref\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="estado_cuenta_' . date('Ymd') . '.pdf"');
        echo $pdf;
    }

    public function exportPdf($params): void
    {
        $this->requireAdmin();
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();

        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role IN ("customer","admin")');
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            http_response_code(404);
            echo 'Cliente no encontrado';
            return;
        }

        list($start, $end) = $this->parseDateFilters();
        $params = [$id];
        $where = "user_id = ?" . $this->buildDateWhere('created_at', $start, $end, $params);
        $ordersStmt = $pdo->prepare("SELECT id, total, payment_method, payment_status, amount_paid, created_at FROM orders WHERE {$where} ORDER BY created_at DESC");
        $ordersStmt->execute($params);
        $orders = $ordersStmt->fetchAll();

        $pendingDebt = 0.0;
        foreach ($orders as $o) {
            if (($o['payment_status'] ?? '') === 'unpaid' && ($o['payment_method'] ?? '') === 'CREDIT') {
                $pendingDebt += ((float)$o['total'] - (float)$o['amount_paid']);
            }
        }

        // Build and send statement email
        $lines = [];
        $lines[] = 'Cliente: ' . $customer['name'] . ' (' . $customer['email'] . ')';
        if ($start || $end) {
            $lines[] = 'Rango: ' . ($start ?: '-') . ' a ' . ($end ?: '-');
        }
        $lines[] = 'Saldo impago: $' . number_format($pendingDebt, 2);
        $lines[] = '---';
        foreach ($orders as $o) {
            $lines[] = '#' . $o['id'] . ' ' . $o['created_at'] . ' Total $' . $o['total'] . ' Pagado $' . $o['amount_paid'] . ' ' . $o['payment_status'];
        }

        $this->outputPdf($lines, 'Estado de cuenta');
    }

    public function sendStatementEmail($params): void
    {
        $this->requireAdmin();
        if (!Csrf::check($_POST['csrf'] ?? null)) {
            $_SESSION['flash_error'] = 'CSRF invalido';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role IN ("customer","admin")');
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            $_SESSION['flash_error'] = 'Cliente no encontrado.';
            header('Location: ' . BASE_URL . 'admin/customers');
            return;
        }

        list($start, $end) = $this->parseDateFilters();
        $params = [$id];
        $where = "user_id = ?" . $this->buildDateWhere('created_at', $start, $end, $params);
        $ordersStmt = $pdo->prepare("SELECT id, total, payment_method, payment_status, amount_paid, created_at FROM orders WHERE {$where} ORDER BY created_at DESC");
        $ordersStmt->execute($params);
        $orders = $ordersStmt->fetchAll();

        $pendingDebt = 0.0;
        foreach ($orders as $o) {
            if (($o['payment_status'] ?? '') === 'unpaid' && ($o['payment_method'] ?? '') === 'CREDIT') {
                $pendingDebt += ((float)$o['total'] - (float)$o['amount_paid']);
            }
        }

        // Build and send statement email
        $lines = [];
        $lines[] = 'Hola ' . $customer['name'] . ',';
        $lines[] = '';
        if ($start || $end) {
            $lines[] = 'Estado de cuenta (rango): ' . ($start ?: '-') . ' a ' . ($end ?: '-');
        } else {
            $lines[] = 'Estado de cuenta actualizado.';
        }
        $lines[] = 'Saldo pendiente: $' . number_format($pendingDebt, 2);
        $lines[] = '';
        $lines[] = 'Compras recientes:';
        foreach ($orders as $o) {
            $lines[] = '#' . $o['id'] . ' ' . $o['created_at'] . ' Total $' . number_format((float)$o['total'], 2) . ' Pagado $' . number_format((float)$o['amount_paid'], 2) . ' ' . $o['payment_status'];
        }
        $lines[] = '';
        $config = $this->config();
        $base = rtrim($config['app']['base_url_full'] ?? '', '/');
        if ($base !== '') {
            $lines[] = 'Puedes ver mas detalle en: ' . $base . '/account/statement';
        }
        $lines[] = '';
        $lines[] = 'Gracias.';

        $subject = 'Estado de cuenta - Tiendita';
        $body = implode("\n", $lines);
        $sent = Mailer::send($customer['email'], $subject, $body, $config['smtp'] ?? []);

        $_SESSION['flash_ok'] = $sent ? 'Estado de cuenta enviado.' : 'No se pudo enviar el correo.';
        header('Location: ' . BASE_URL . 'admin/customers/' . $id);
    }
}
