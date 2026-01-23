<?php
/**
 * Script de Migração de Produtos
 * Migra produtos hardcoded do process.php para o banco de dados
 * Execute este script apenas UMA VEZ após criar as tabelas
 */

require_once 'db.php';

echo "=== Iniciando Migração de Produtos ===\n\n";

try {
    // Verificar se já existem produtos
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "⚠️  AVISO: Já existem {$count} produtos no banco de dados.\n";
        echo "Deseja continuar e adicionar mais produtos? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 's') {
            echo "Migração cancelada.\n";
            exit;
        }
        fclose($handle);
    }

    // 1. PRODUTOS BASE (Obrigatórios)
    echo "1. Inserindo produtos base...\n";
    
    $baseProducts = [
        ['name' => 'NXCAP', 'usage' => 'Uso Diário', 'price' => 150.00],
        ['name' => 'Power Trimagnesio', 'usage' => 'Uso Diário', 'price' => 120.00]
    ];

    $stmt = $pdo->prepare("INSERT INTO products (name, usage_instruction, price, is_base, is_active) VALUES (?, ?, ?, 1, 1)");
    
    foreach ($baseProducts as $product) {
        $stmt->execute([$product['name'], $product['usage'], $product['price']]);
        echo "   ✓ {$product['name']} - R$ {$product['price']}\n";
    }

    // 2. PRODUTOS CONDICIONAIS
    echo "\n2. Inserindo produtos condicionais...\n";
    
    $conditionalProducts = [
        // Produtos para DOR
        ['name' => 'Óleo SOFH', 'usage' => 'Ingestão', 'price' => 80.00],
        ['name' => 'Ômega 3', 'usage' => 'Ingestão', 'price' => 90.00],
        ['name' => 'Gel Life Shii', 'usage' => 'Aplicação local', 'price' => 50.00],
        
        // Produtos para EMOCIONAL
        ['name' => 'Melatonina+CoQ10', 'usage' => 'Noite', 'price' => 110.00],
        ['name' => 'Polivitamínico', 'usage' => 'Manhã', 'price' => 60.00],
        ['name' => 'Sachê Energético', 'usage' => 'Manhã', 'price' => 40.00]
    ];

    $stmt = $pdo->prepare("INSERT INTO products (name, usage_instruction, price, is_base, is_active) VALUES (?, ?, ?, 0, 1)");
    
    $productIds = [];
    foreach ($conditionalProducts as $product) {
        $stmt->execute([$product['name'], $product['usage'], $product['price']]);
        $productIds[$product['name']] = $pdo->lastInsertId();
        echo "   ✓ {$product['name']} - R$ {$product['price']}\n";
    }

    // 3. REGRAS DE PRODUTOS
    echo "\n3. Inserindo regras de produtos...\n";
    
    $rules = [
        // Regras para DOR
        ['product' => 'Óleo SOFH', 'condition_type' => 'pain', 'condition_value' => 'yes', 'priority' => 10],
        ['product' => 'Ômega 3', 'condition_type' => 'pain', 'condition_value' => 'yes', 'priority' => 9],
        ['product' => 'Gel Life Shii', 'condition_type' => 'pain', 'condition_value' => 'yes', 'priority' => 8],
        
        // Regras para PRESSÃO ALTA
        ['product' => 'Óleo SOFH', 'condition_type' => 'pressure', 'condition_value' => 'yes', 'priority' => 10],
        
        // Regras para EMOCIONAL
        ['product' => 'Melatonina+CoQ10', 'condition_type' => 'emotional', 'condition_value' => 'unstable', 'priority' => 10],
        ['product' => 'Polivitamínico', 'condition_type' => 'emotional', 'condition_value' => 'unstable', 'priority' => 9],
        ['product' => 'Sachê Energético', 'condition_type' => 'emotional', 'condition_value' => 'unstable', 'priority' => 8]
    ];

    $stmt = $pdo->prepare("INSERT INTO product_rules (product_id, condition_type, condition_value, priority) VALUES (?, ?, ?, ?)");
    
    foreach ($rules as $rule) {
        if (isset($productIds[$rule['product']])) {
            $stmt->execute([
                $productIds[$rule['product']],
                $rule['condition_type'],
                $rule['condition_value'],
                $rule['priority']
            ]);
            echo "   ✓ Regra: {$rule['product']} → {$rule['condition_type']} = {$rule['condition_value']}\n";
        }
    }

    // 4. ALERTAS
    echo "\n4. Inserindo alertas...\n";
    
    $alerts = [
        ['product' => 'Óleo SOFH', 'message' => 'Cuidado com estimulantes']
    ];

    $stmt = $pdo->prepare("INSERT INTO product_alerts (product_id, alert_message) VALUES (?, ?)");
    
    foreach ($alerts as $alert) {
        if (isset($productIds[$alert['product']])) {
            $stmt->execute([
                $productIds[$alert['product']],
                $alert['message']
            ]);
            echo "   ✓ Alerta: {$alert['product']} → {$alert['message']}\n";
        }
    }

    // RESUMO
    echo "\n=== Migração Concluída com Sucesso! ===\n\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_rules");
    $totalRules = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_alerts");
    $totalAlerts = $stmt->fetchColumn();
    
    echo "📊 Estatísticas:\n";
    echo "   • Produtos: {$totalProducts}\n";
    echo "   • Regras: {$totalRules}\n";
    echo "   • Alertas: {$totalAlerts}\n\n";
    
    echo "✅ Agora você pode:\n";
    echo "   1. Acessar o admin em: http://localhost:8000/admin/products.php\n";
    echo "   2. Gerenciar produtos pela interface\n";
    echo "   3. O process.php usará automaticamente os produtos do banco\n\n";

} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
