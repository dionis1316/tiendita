<?php
namespace App\Core;

class ActivityLogger {
    public static function log(\PDO $pdo, int $userId, string $type, ?int $productId = null, ?string $productName = null, ?int $quantity = null): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if ($ua !== null && strlen($ua) > 255) {
            $ua = substr($ua, 0, 255);
        }

        $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, type, product_id, product_name, quantity, ip, user_agent) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$userId, $type, $productId, $productName, $quantity, $ip, $ua]);
    }

    public static function markCartCompleted(\PDO $pdo, int $userId): void {
        $stmt = $pdo->prepare("UPDATE activity_logs SET completed_at = NOW() WHERE user_id = ? AND type = 'cart_add' AND completed_at IS NULL");
        $stmt->execute([$userId]);
    }
}
