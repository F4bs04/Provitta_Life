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

// Verificar se o arquivo foi enviado
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload do arquivo']);
    exit;
}

$file = $_FILES['image'];

// Validar tamanho (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máx: 5MB)']);
    exit;
}

// Validar tipo
$allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Formato de arquivo inválido']);
    exit;
}

// Buscar informações do produto
$stmt = $pdo->prepare("SELECT name, image_url FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
    exit;
}

// Criar diretório se não existir
$uploadDir = '../assets/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Gerar nome único para o arquivo
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $product['name']) . '_' . time() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// Mover arquivo
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo']);
    exit;
}

// Remover imagem antiga se existir
if ($product['image_url'] && file_exists('../' . $product['image_url'])) {
    unlink('../' . $product['image_url']);
}

// Atualizar banco de dados
$imageUrl = 'assets/products/' . $fileName;
$stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
$stmt->execute([$imageUrl, $product_id]);

echo json_encode([
    'success' => true,
    'message' => 'Imagem enviada com sucesso',
    'image_url' => $imageUrl
]);
