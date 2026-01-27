<?php
namespace App\Controllers\Admin;

class AdminActivityController extends AdminBaseController
{
    public function index(): void
    {
        $user = $this->requireAdmin();
        $pdo = $this->pdo();

        $type = $_GET['type'] ?? 'all';
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        $q = trim($_GET['q'] ?? '');
        $customer = trim($_GET['customer'] ?? '');
        $product = trim($_GET['product'] ?? '');

        $where = ['1=1'];
        $params = [];

        if ($type === 'login') {
            $where[] = 'al.type = ?';
            $params[] = 'login_success';
        } elseif ($type === 'cart') {
            $where[] = 'al.type = ?';
            $params[] = 'cart_add';
        } elseif ($type === 'abandoned') {
            $where[] = "al.type = 'cart_add' AND al.completed_at IS NULL";
        }

        if ($from !== '') {
            $where[] = 'al.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'al.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR al.product_name LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($customer !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $customer . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($product !== '') {
            $where[] = 'al.product_name LIKE ?';
            $params[] = '%' . $product . '%';
        }

        $sql = "SELECT al.*, u.name, u.email
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY al.id DESC
                LIMIT 200";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $this->view('activity/index', [
            'title' => 'Actividad de usuarios',
            'user' => $user,
            'logs' => $logs,
            'filters' => [
                'type' => $type,
                'from' => $from,
                'to' => $to,
                'q' => $q,
                'customer' => $customer,
                'product' => $product,
            ],
        ]);
    }
}
