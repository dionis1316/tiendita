<?php
namespace App\Controllers\Admin;

class AdminDashboardController extends AdminBaseController
{
    public function index(): void
    {
        $user = $this->requireAdmin();
        $pdo = $this->pdo();

        $totals = $pdo->query("SELECT
            COALESCE(SUM(CASE WHEN payment_status = 'unpaid' AND payment_method = 'CREDIT' THEN (total - amount_paid) ELSE 0 END), 0) AS total_debt,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) AS total_paid,
            COUNT(CASE WHEN payment_status = 'unpaid' AND payment_method = 'CREDIT' THEN 1 END) AS unpaid_orders,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) AS paid_orders,
            COALESCE(AVG(total), 0) AS avg_ticket
            FROM orders")->fetch();

        $customers = $pdo->query("SELECT
            COUNT(*) AS total_customers,
            COALESCE((SELECT COUNT(DISTINCT user_id) FROM orders WHERE payment_status = 'unpaid' AND payment_method = 'CREDIT'), 0) AS customers_with_debt
            FROM users u
            WHERE u.role = 'customer'")->fetch();

        $monthlyRows = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, SUM(total) AS total
            FROM orders
            GROUP BY ym
            ORDER BY ym DESC
            LIMIT 6")->fetchAll();
        $monthlyRows = array_reverse($monthlyRows ?: []);
        $months = [];
        $monthlyTotals = [];
        foreach ($monthlyRows as $r) {
            $months[] = $r['ym'];
            $monthlyTotals[] = (float)$r['total'];
        }

        $topCustomerRows = $pdo->query("SELECT u.name, SUM(o.total) AS total_spent, COUNT(o.id) AS orders_count, AVG(o.total) AS avg_spent
            FROM orders o
            JOIN users u ON u.id = o.user_id
            WHERE u.role = 'customer' AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY o.user_id
            ORDER BY total_spent DESC
            LIMIT 5")->fetchAll();
        $topCustomerLabels = [];
        $topCustomerTotals = [];
        $topCustomerAvgs = [];
        foreach ($topCustomerRows as $r) {
            $topCustomerLabels[] = $r['name'];
            $topCustomerTotals[] = (float)$r['total_spent'];
            $topCustomerAvgs[] = (float)$r['avg_spent'];
        }

        $topQtyRows = $pdo->query("SELECT p.name, SUM(oi.quantity) AS qty
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            GROUP BY oi.product_id
            ORDER BY qty DESC
            LIMIT 5")->fetchAll();
        $topQtyLabels = [];
        $topQtyValues = [];
        foreach ($topQtyRows as $r) {
            $topQtyLabels[] = $r['name'];
            $topQtyValues[] = (int)$r['qty'];
        }

        $inventoryRows = $pdo->query("SELECT name, stock
            FROM products
            WHERE is_active = 1
            ORDER BY stock DESC
            LIMIT 10")->fetchAll();
        $inventoryLabels = [];
        $inventoryStocks = [];
        foreach ($inventoryRows as $r) {
            $inventoryLabels[] = $r['name'];
            $inventoryStocks[] = (int)$r['stock'];
        }

        $profitRows = $pdo->query("SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS ym,
            COALESCE(SUM(oi.quantity * (oi.price - COALESCE(p.cost, 0))), 0) AS profit
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            GROUP BY ym
            ORDER BY ym DESC
            LIMIT 6")->fetchAll();
        $profitRows = array_reverse($profitRows ?: []);
        $profitMonths = [];
        $profitTotals = [];
        foreach ($profitRows as $r) {
            $profitMonths[] = $r['ym'];
            $profitTotals[] = (float)$r['profit'];
        }

        $chartData = [
            'paid' => (int)($totals['paid_orders'] ?? 0),
            'unpaid' => (int)($totals['unpaid_orders'] ?? 0),
            'total_debt' => (float)($totals['total_debt'] ?? 0),
            'total_paid' => (float)($totals['total_paid'] ?? 0),
            'avg_ticket' => (float)($totals['avg_ticket'] ?? 0),
            'customers_total' => (int)($customers['total_customers'] ?? 0),
            'customers_with_debt' => (int)($customers['customers_with_debt'] ?? 0),
            'months' => $months,
            'monthly_totals' => $monthlyTotals,
            'top_customer_labels' => $topCustomerLabels,
            'top_customer_totals' => $topCustomerTotals,
            'top_customer_avgs' => $topCustomerAvgs,
            'top_qty_labels' => $topQtyLabels,
            'top_qty_values' => $topQtyValues,
            'inventory_labels' => $inventoryLabels,
            'inventory_stocks' => $inventoryStocks,
            'profit_months' => $profitMonths,
            'profit_totals' => $profitTotals,
        ];

        $this->view('dashboard', [
            'title' => 'Panel de Administracion',
            'user' => $user,
            'chartData' => $chartData,
        ]);
    }
}
