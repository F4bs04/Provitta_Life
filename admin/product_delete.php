<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de produto inválido']);
        exit;
    }

    try {
        // Verificar se produto existe e se é produto base
        $stmt = $pdo->prepare("SELECT id, is_base, image_url FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
            exit;
        }
        
        if ($product['is_base'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Produtos base não podem ser deletados']);
            exit;
        }

        // Deletar imagem se existir
        if (!empty($product['image_url'])) {
            $imagePath = '../' . $product['image_url'];
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        // Deletar regras associadas
        $stmt = $pdo->prepare("DELETE FROM product_rules WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        // Deletar alertas associados
        $stmt = $pdo->prepare("DELETE FROM product_alerts WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        // Deletar produto
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        echo json_encode(['success' => true, 'message' => 'Produto deletado com sucesso']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
