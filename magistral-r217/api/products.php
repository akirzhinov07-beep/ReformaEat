<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$dataFile = __DIR__ . '/../data/products.json';

function loadProducts(string $file): array {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveProducts(string $file, array $products): void {
    file_put_contents($file, json_encode(array_values($products), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function respond(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        respond(loadProducts($dataFile));

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['name'])) {
            respond(['error' => 'name is required'], 400);
        }
        $products = loadProducts($dataFile);
        $product = [
            'id'       => uniqid('p_', true),
            'name'     => trim($body['name']),
            'category' => trim($body['category'] ?? ''),
            'price'    => trim($body['price'] ?? ''),
            'specs'    => trim($body['specs'] ?? ''),
            'image'    => trim($body['image'] ?? ''),
            'created'  => date('c'),
        ];
        $products[] = $product;
        saveProducts($dataFile, $products);
        respond($product, 201);

    case 'PUT':
        if (!$id) respond(['error' => 'id required'], 400);
        $body = json_decode(file_get_contents('php://input'), true);
        $products = loadProducts($dataFile);
        $found = false;
        foreach ($products as &$p) {
            if ($p['id'] === $id) {
                if (isset($body['name']))     $p['name']     = trim($body['name']);
                if (isset($body['category'])) $p['category'] = trim($body['category']);
                if (isset($body['price']))    $p['price']    = trim($body['price']);
                if (isset($body['specs']))    $p['specs']    = trim($body['specs']);
                if (isset($body['image']))    $p['image']    = trim($body['image']);
                $p['updated'] = date('c');
                $found = true;
                respond($p);
            }
        }
        if (!$found) respond(['error' => 'not found'], 404);
        saveProducts($dataFile, $products);
        break;

    case 'DELETE':
        if (!$id) respond(['error' => 'id required'], 400);
        $products = loadProducts($dataFile);
        $filtered = array_filter($products, fn($p) => $p['id'] !== $id);
        if (count($filtered) === count($products)) respond(['error' => 'not found'], 404);
        foreach ($products as $p) {
            if ($p['id'] === $id && !empty($p['image'])) {
                $imgPath = __DIR__ . '/../' . ltrim($p['image'], '/');
                if (file_exists($imgPath)) @unlink($imgPath);
            }
        }
        saveProducts($dataFile, $filtered);
        respond(['ok' => true]);

    default:
        respond(['error' => 'method not allowed'], 405);
}
