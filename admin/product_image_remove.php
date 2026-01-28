<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'ID do produto não fornecido']);
    exit;
}

// Buscar informações do produto
$stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
    exit;
}

// Remover arquivo físico se existir
if ($product['image_url'] && file_exists('../' . $product['image_url'])) {
    unlink('../' . $product['image_url']);
}

// Atualizar banco de dados
$stmt = $pdo->prepare("UPDATE products SET image_url = NULL WHERE id = ?");
$stmt->execute([$product_id]);

echo json_encode([
    'success' => true,
    'message' => 'Imagem removida com sucesso'
]);
