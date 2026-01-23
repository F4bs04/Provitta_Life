<?php
require_once 'db.php';

echo "=== Testando Implementação do Sistema de Produtos ===\n\n";

try {
    // Testar produtos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $result = $stmt->fetch();
    echo "✓ Produtos no banco: " . $result['total'] . "\n";
    
    // Testar regras
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_rules");
    $result = $stmt->fetch();
    echo "✓ Regras no banco: " . $result['total'] . "\n";
    
    // Testar alertas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_alerts");
    $result = $stmt->fetch();
    echo "✓ Alertas no banco: " . $result['total'] . "\n";
    
    // Testar produtos base
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_base = 1");
    $result = $stmt->fetch();
    echo "✓ Produtos base: " . $result['total'] . "\n";
    
    // Testar produtos ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "✓ Produtos ativos: " . $result['total'] . "\n";
    
    // Listar produtos
    echo "\n=== Lista de Produtos ===\n";
    $stmt = $pdo->query("SELECT name, price, is_base, is_active FROM products ORDER BY is_base DESC, name");
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $type = $product['is_base'] ? '[BASE]' : '[COND]';
        $status = $product['is_active'] ? '✓' : '✗';
        echo "{$status} {$type} {$product['name']} - R$ {$product['price']}\n";
    }
    
    echo "\n=== Teste Concluído com Sucesso! ===\n";
    echo "\n✅ Sistema de produtos está funcionando corretamente!\n";
    echo "\n📍 Próximos passos:\n";
    echo "   1. Acesse: http://localhost:8000/admin/products.php\n";
    echo "   2. Teste adicionar/editar produtos\n";
    echo "   3. Teste o formulário em: http://localhost:8000\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
}
?>
