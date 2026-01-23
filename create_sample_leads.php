<?php
require_once 'db.php';

echo "Criando leads de exemplo para teste do Kanban...\n\n";

$sampleLeads = [
    [
        'name' => 'Maria Silva',
        'email' => 'maria.silva@email.com',
        'cpf' => '123.456.789-00',
        'pain' => 'yes',
        'pressure' => 'no',
        'diabetes' => 'no',
        'sleep' => 'bad',
        'emotional' => 'unstable',
        'gut' => 'normal',
        'observations' => 'Dores nas costas frequentes, principalmente após exercícios.',
        'total_price' => 450.00,
        'status' => 'orcamento_gerado'
    ],
    [
        'name' => 'João Santos',
        'email' => 'joao.santos@email.com',
        'cpf' => '987.654.321-00',
        'pain' => 'no',
        'pressure' => 'yes',
        'diabetes' => 'yes',
        'sleep' => 'good',
        'emotional' => 'stable',
        'gut' => 'constipated',
        'observations' => 'Controle de diabetes e pressão arterial.',
        'total_price' => 520.00,
        'status' => 'compra_confirmada'
    ],
    [
        'name' => 'Ana Costa',
        'email' => 'ana.costa@email.com',
        'cpf' => '456.789.123-00',
        'pain' => 'yes',
        'pressure' => 'no',
        'diabetes' => 'no',
        'sleep' => 'bad',
        'emotional' => 'unstable',
        'gut' => 'loose',
        'observations' => 'Ansiedade e problemas digestivos.',
        'total_price' => 380.00,
        'status' => 'produto_comprado'
    ],
    [
        'name' => 'Carlos Oliveira',
        'email' => 'carlos.oliveira@email.com',
        'cpf' => '321.654.987-00',
        'pain' => 'no',
        'pressure' => 'no',
        'diabetes' => 'no',
        'sleep' => 'good',
        'emotional' => 'stable',
        'gut' => 'normal',
        'observations' => 'Cliente satisfeito, retornando para recompra.',
        'total_price' => 450.00,
        'status' => 'recompra'
    ],
    [
        'name' => 'Paula Mendes',
        'email' => 'paula.mendes@email.com',
        'cpf' => '789.123.456-00',
        'pain' => 'yes',
        'pressure' => 'yes',
        'diabetes' => 'no',
        'sleep' => 'bad',
        'emotional' => 'unstable',
        'gut' => 'normal',
        'observations' => 'Estresse no trabalho, dores de cabeça constantes.',
        'total_price' => 590.00,
        'status' => 'orcamento_gerado'
    ]
];

$products = [
    ['name' => 'NXCAP', 'usage' => 'Uso Diário', 'price' => 150.00],
    ['name' => 'Power Trimagnesio', 'usage' => 'Uso Diário', 'price' => 120.00],
    ['name' => 'Óleo SOFH', 'usage' => 'Ingestão', 'price' => 80.00],
    ['name' => 'Ômega 3', 'usage' => 'Ingestão', 'price' => 90.00],
    ['name' => 'Melatonina+CoQ10', 'usage' => 'Noite', 'price' => 110.00]
];

try {
    foreach ($sampleLeads as $lead) {
        $stmt = $pdo->prepare("
            INSERT INTO leads (session_id, name, email, cpf, pain, pressure, diabetes, sleep, emotional, gut, observations, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $sessionId = 'test_' . uniqid();
        
        $stmt->execute([
            $sessionId,
            $lead['name'],
            $lead['email'],
            $lead['cpf'],
            $lead['pain'],
            $lead['pressure'],
            $lead['diabetes'],
            $lead['sleep'],
            $lead['emotional'],
            $lead['gut'],
            $lead['observations'],
            $lead['total_price'],
            $lead['status']
        ]);
        
        $leadId = $pdo->lastInsertId();
        
        // Adicionar 2-3 produtos aleatórios
        $numProducts = rand(2, 3);
        $selectedProducts = array_rand($products, $numProducts);
        
        if (!is_array($selectedProducts)) {
            $selectedProducts = [$selectedProducts];
        }
        
        $stmtItem = $pdo->prepare("INSERT INTO protocol_items (lead_id, product_name, usage_instruction, price) VALUES (?, ?, ?, ?)");
        
        foreach ($selectedProducts as $idx) {
            $product = $products[$idx];
            $stmtItem->execute([$leadId, $product['name'], $product['usage'], $product['price']]);
        }
        
        echo "✓ Lead criado: {$lead['name']} ({$lead['status']})\n";
    }
    
    echo "\n✓ {$count} leads de exemplo criados com sucesso!\n";
    echo "\nAcesse o dashboard em: http://localhost:8000/admin/admin_login.php\n";
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>
