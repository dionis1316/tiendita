<?php
namespace App\Controllers;

use App\Core\Auth;

class AccountController {
    private function pdo() {
        return require __DIR__ . '/../Config/db.php';
    }

    private function parseDateFilters(): array {
        $start = trim($_GET['start'] ?? '');
        $end = trim($_GET['end'] ?? '');
        return [$start, $end];
    }

    private function buildDateWhere(string $field, ?string $start, ?string $end, array &$params): string {
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

    public function statement(): void {
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $userId = Auth::userId();
        $pdo = $this->pdo();

        list($start, $end) = $this->parseDateFilters();
        $params = [$userId];
        $where = "o.user_id = ?" . $this->buildDateWhere('o.created_at', $start, $end, $params);

        $ordersStmt = $pdo->prepare("SELECT o.id, o.total, o.payment_method, o.payment_status, o.amount_paid, o.created_at
            FROM orders o WHERE {$where} ORDER BY o.created_at DESC");
        $ordersStmt->execute($params);
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

        $topParams = [$userId];
        $topWhere = "o.user_id = ?" . $this->buildDateWhere('o.created_at', $start, $end, $topParams);
        $topStmt = $pdo->prepare("SELECT p.name, SUM(oi.quantity) AS qty
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            WHERE {$topWhere}
            GROUP BY oi.product_id
            ORDER BY qty DESC
            LIMIT 5");
        $topStmt->execute($topParams);
        $topRows = $topStmt->fetchAll();
        $topLabels = [];
        $topQty = [];
        foreach ($topRows as $row) {
            $topLabels[] = $row['name'];
            $topQty[] = (int)$row['qty'];
        }

        $pendingDebt = 0.0;
        $debtStmt = $pdo->prepare("SELECT COALESCE(SUM(total - amount_paid),0) FROM orders WHERE user_id = ? AND payment_status = 'unpaid' AND payment_method = 'CREDIT'");
        $favorStmt = $pdo->prepare("SELECT COALESCE(favor_balance,0) FROM credit_accounts WHERE user_id = ?");
        $favorStmt->execute([$userId]);
        $favorBalance = (float)($favorStmt->fetchColumn() ?? 0);

        $debtStmt->execute([$userId]);
        $pendingDebt = (float)($debtStmt->fetchColumn() ?? 0);

        $title = 'Mi estado de cuenta';
        include __DIR__ . '/../Views/account/statement.php';
    }
}
