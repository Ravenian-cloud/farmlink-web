<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

// 1. Autoloading (Composer if present + native PSR-4 fallback for App\)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = ROOT_PATH . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// 2. Load .env variables
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(ROOT_PATH)->safeLoad();
} elseif (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$name] = $value;
        putenv("{$name}={$value}");
    }
}

// 3. Configure Timezone & Error Handling
$appConfig = require ROOT_PATH . '/app/Config/app.php';
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 4. Secure Session & CSRF Token Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 5. Global Translation & URL Helpers
function base_url(string $path = ''): string
{
    static $url = null;
    if ($url === null) {
        $cfg = require ROOT_PATH . '/app/Config/app.php';
        $url = $cfg['url'];
    }
    return $path === '' ? $url : $url . '/' . ltrim($path, '/');
}

function csrf_field(): string
{
    $token = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_token" value="' . $token . '">';
}

function __(string $key): string
{
    static $translations = null;
    $lang = $_SESSION['lang'] ?? 'en';
    if ($translations === null) {
        $langFile = ROOT_PATH . "/resources/lang/{$lang}.php";
        $translations = file_exists($langFile) ? require $langFile : [];
    }
    return $translations[$key] ?? $key;
}

// 6. Initialize Router & Define FarmLink Routes
use App\Core\Router;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\RoleMiddleware;
use App\Controllers\AuthController;
use App\Controllers\MarketplaceController;
use App\Controllers\BidController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Controllers\DashboardController;
use App\Controllers\AlertController;
use App\Controllers\CommunityController;
use App\Controllers\SyncController;
use App\Controllers\AdminController;

$router = new Router();

// Public & Marketplace Routes
$router->get('/', [MarketplaceController::class, 'index']);
$router->get('/marketplace', [MarketplaceController::class, 'index']);
$router->get('/marketplace/listing/{id}', [MarketplaceController::class, 'show']);
$router->get('/lang/{code}', [AuthController::class, 'switchLanguage']);

// Authentication & KYC Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/kyc', [AuthController::class, 'showKyc'], [AuthMiddleware::class]);
$router->post('/kyc/upload', [AuthController::class, 'uploadKyc'], [AuthMiddleware::class]);

// Role Dashboards & Analytics
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/analytics/trends', [DashboardController::class, 'priceTrendsJson'], [AuthMiddleware::class]);

// Listing Creation (Farmers & Input Suppliers)
$router->get('/listings/create', [MarketplaceController::class, 'create'], [[RoleMiddleware::class, 'farmer|supplier']]);
$router->post('/listings/store', [MarketplaceController::class, 'store'], [[RoleMiddleware::class, 'farmer|supplier']]);

// Bidding & Price Negotiation
$router->post('/bids/submit', [BidController::class, 'submit'], [AuthMiddleware::class]);
$router->post('/bids/{id}/respond', [BidController::class, 'respond'], [AuthMiddleware::class]);
$router->get('/api/bids/listing/{id}', [BidController::class, 'threadJson'], [AuthMiddleware::class]);

// Orders, Escrow & M-Pesa Payments
$router->post('/orders/create', [OrderController::class, 'create'], [AuthMiddleware::class]);
$router->get('/orders/{id}', [OrderController::class, 'show'], [AuthMiddleware::class]);
$router->post('/orders/{id}', [OrderController::class, 'show'], [AuthMiddleware::class]);
$router->post('/orders/{id}/confirm-delivery', [OrderController::class, 'confirmDelivery'], [AuthMiddleware::class]);
$router->post('/orders/{id}/review', [OrderController::class, 'submitReview'], [AuthMiddleware::class]);
$router->post('/payment/mpesa/stk-push', [PaymentController::class, 'initiateStkPush'], [AuthMiddleware::class]);
$router->post('/payment/mpesa/callback', [PaymentController::class, 'mpesaCallback']); // Public Safaricom webhook
$router->get('/api/payment/status/{orderId}', [PaymentController::class, 'checkStatus'], [AuthMiddleware::class]);

// Weather/Pest Alerts & Community Forum
$router->get('/alerts', [AlertController::class, 'index'], [AuthMiddleware::class]);
$router->get('/community', [CommunityController::class, 'index']);
$router->post('/community/post', [CommunityController::class, 'storePost'], [AuthMiddleware::class]);
$router->post('/community/comment', [CommunityController::class, 'storeComment'], [AuthMiddleware::class]);

// Offline PWA Sync Endpoint
$router->post('/api/sync/push', [SyncController::class, 'push'], [AuthMiddleware::class]);

// Admin Governance $router->post('/api/sync/push', [SyncController::class, 'push'], [AuthMiddleware::class]); KYC Routes
$router->get('/admin', [AdminController::class, 'index'], [[RoleMiddleware::class, 'admin']]);
$router->post('/admin/kyc/{id}', [AdminController::class, 'reviewKyc'], [[RoleMiddleware::class, 'admin']]);
$router->post('/admin/alerts', [AdminController::class, 'publishAlert'], [[RoleMiddleware::class, 'admin']]);

// Dispatch Request
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
