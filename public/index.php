<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Router;
use App\Controllers\OrderController;
use App\Controllers\ProductController;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$migrateFlag = __DIR__ . '/../.migrated';
if (!file_exists($migrateFlag)) {
    require_once __DIR__ . '/../src/Database/migrate.php';
    touch($migrateFlag);
}

$router = new Router();
$productController = new ProductController();
$orderController = new OrderController();

$router->get('/products', fn () => $productController->index());
$router->get('/product/{id}', fn ($id) => $productController->show($id));
$router->get('/orders', fn () => $orderController->index());
$router->post('/order', fn () => $orderController->store());
$router->get('/order/{id}', fn ($id) => $orderController->show($id));

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
