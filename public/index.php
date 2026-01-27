<?php
declare(strict_types=1);
session_start();

// 1) Config y BASE_URL
require __DIR__ . '/../app/Core/helpers.php';
$config = require __DIR__ . '/../app/Config/config.php';
// ✅ Mostrar errores solo en desarrollo
$isDev = ($config['app']['env'] ?? 'prod') === 'dev';
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('display_startup_errors', $isDev ? '1' : '0');
error_reporting($isDev ? E_ALL : 0);
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($config['app']['base_url'] ?? '/', '/') . '/');
}

// 2) Autoload basico App\*
spl_autoload_register(function($class){
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

// 3) Normalizar REQUEST_URI para rutear bajo subcarpeta
$_SERVER['REQUEST_URI'] = preg_replace(
    '#^' . preg_quote(parse_url(BASE_URL, PHP_URL_PATH), '#') . '#',
    '/',
    $_SERVER['REQUEST_URI'] ?? '/'
);

// 3.5) Guard de autenticacion
use App\Core\Auth as AuthCore;

$path   = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$public = [
  'GET'  => ['/login','/register', '/forgot', '/reset', '/admin/login', '/admin/ping'],
  'POST' => ['/login','/register', '/forgot', '/reset', '/admin/login'],
];

$isPublic = in_array($path, $public[$method] ?? [], true) || strpos($path, '/reset/') === 0;
$isAsset  = (strpos($path, '/assets/') === 0);

if (!AuthCore::check() && !$isPublic && !$isAsset) {
    $qs    = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']!=='' ? ('?'.$_SERVER['QUERY_STRING']) : '';
    $next  = $path . $qs;
    header('Location: '.BASE_URL.'login?next='.urlencode($next), true, 302);
    exit;
}

// 4) Conexion BD
$pdo = require __DIR__ . '/../app/Config/db.php';

// 5) Router y rutas
use App\Core\Router;
use App\Controllers\StoreController;
use App\Controllers\CartController;
use App\Controllers\AuthController;
use App\Controllers\CheckoutController;
use App\Controllers\AccountController;

$router = new Router();

// Store
$router->get('/', [StoreController::class, 'home']);
$router->get('/product/{id}', [StoreController::class, 'show']);

// Auth
$router->get('/login',    [AuthController::class, 'loginForm']);
$router->post('/login',   [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register',[AuthController::class, 'register']);
$router->get('/forgot',   [AuthController::class, 'forgotForm']);
$router->post('/forgot',  [AuthController::class, 'sendReset']);
$router->get('/reset/{token}', [AuthController::class, 'resetForm']);
$router->post('/reset/{token}', [AuthController::class, 'resetSubmit']);
$router->post('/logout',  [AuthController::class, 'logout']);

// Cart
$router->get('/cart',           [CartController::class, 'view']);
$router->post('/cart/add',      [CartController::class, 'add']);
$router->post('/cart/update',   [CartController::class, 'update']);
$router->post('/cart/remove',   [CartController::class, 'remove']);

// Checkout
$router->get('/checkout',         [CheckoutController::class, 'checkoutForm']);
$router->post('/checkout/submit', [CheckoutController::class, 'submitOrder']);
$router->get('/checkout/success', [CheckoutController::class, 'checkoutSuccess']);

// Account
$router->get('/account/statement', [AccountController::class, 'statement']);

// Admin
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminProductsController;
use App\Controllers\Admin\AdminCustomersController;
use App\Controllers\Admin\AdminActivityController;

$router->get('/admin/login',  [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);

$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/ping',   function () { echo 'admin ok'; });

$router->get('/admin/products', [AdminProductsController::class, 'index']);
$router->get('/admin/products/create', [AdminProductsController::class, 'createForm']);
$router->post('/admin/products/create', [AdminProductsController::class, 'create']);
$router->get('/admin/products/{id}/edit', [AdminProductsController::class, 'editForm']);
$router->post('/admin/products/{id}/edit', [AdminProductsController::class, 'update']);
$router->post('/admin/products/{id}/deactivate', [AdminProductsController::class, 'deactivate']);
$router->post('/admin/products/{id}/activate', [AdminProductsController::class, 'activate']);

$router->get('/admin/customers', [AdminCustomersController::class, 'index']);
$router->get('/admin/customers/{id}', [AdminCustomersController::class, 'show']);
$router->post('/admin/customers/{id}/payments', [AdminCustomersController::class, 'addPayment']);
$router->post('/admin/customers/{id}/deactivate', [AdminCustomersController::class, 'deactivate']);
$router->post('/admin/customers/{id}/activate', [AdminCustomersController::class, 'activate']);
$router->post('/admin/customers/{id}/send', [AdminCustomersController::class, 'sendStatementEmail']);
$router->get('/admin/customers/{id}/export/csv', [AdminCustomersController::class, 'exportCsv']);
$router->get('/admin/customers/{id}/export/pdf', [AdminCustomersController::class, 'exportPdf']);
$router->get('/admin/activity', [AdminActivityController::class, 'index']);

// 6) Despacho
$router->dispatch();
