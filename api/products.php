<?php
// api/products.php
// PenPoint API - Retrieve active slots and product inventory

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';

$productModel = new ProductModel();
$products = $productModel->getAllProducts();

echo json_encode([
    'success' => true,
    'machine_id' => $_GET['machine_id'] ?? DEFAULT_MACHINE_ID,
    'total_slots' => count($products),
    'currency' => CURRENCY_SYMBOL,
    'products' => $products
]);
