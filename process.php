<?php
session_start();
require_once 'db.php';

// Coleta de dados do formulário
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$pain = $_POST['pain'] ?? 'no';
$pressure = $_POST['pressure'] ?? 'no';
$diabetes = $_POST['diabetes'] ?? 'no';
$sleep = $_POST['sleep'] ?? 'good';
$emotional = $_POST['emotional'] ?? 'stable';
$gut = $_POST['gut'] ?? 'normal';
$observations = $_POST['observations'] ?? '';

// Inicializar carrinho e alertas
$cart = [];
$_SESSION['alerts'] = [];

// 1. BUSCAR PRODUTOS BASE (Obrigatórios)
try {
    $stmt = $pdo->query("
        SELECT * FROM products 
        WHERE is_base = 1 AND is_active = 1
        ORDER BY name
    ");
    $baseProducts = $stmt->fetchAll();
    
    foreach ($baseProducts as $product) {
        $cart[$product['name']] = [
            'name' => $product['name'],
            'usage' => $product['usage_instruction'],
            'price' => $product['price'],
            'image' => $product['image_url']
        ];
    }

    // 2. BUSCAR PRODUTOS CONDICIONAIS BASEADOS NAS RESPOSTAS
    $conditions = [
        'pain' => $pain,
        'pressure' => $pressure,
        'diabetes' => $diabetes,
        'sleep' => $sleep,
        'emotional' => $emotional,
        'gut' => $gut
    ];

    // Para cada condição, buscar produtos que atendem a regra
    foreach ($conditions as $conditionType => $conditionValue) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, pr.priority
            FROM products p
            INNER JOIN product_rules pr ON p.id = pr.product_id
            WHERE pr.condition_type = ? 
            AND pr.condition_value = ? 
            AND p.is_active = 1
            ORDER BY pr.priority DESC
        ");
        $stmt->execute([$conditionType, $conditionValue]);
        $conditionalProducts = $stmt->fetchAll();
        
        foreach ($conditionalProducts as $product) {
            // Usar nome como chave para evitar duplicatas
            if (!isset($cart[$product['name']])) {
                $cart[$product['name']] = [
                    'name' => $product['name'],
                    'usage' => $product['usage_instruction'],
                    'price' => $product['price'],
                    'image' => $product['image_url']
                ];
            }
            
            // Buscar alertas associados a este produto
            $stmtAlert = $pdo->prepare("
                SELECT alert_message 
                FROM product_alerts 
                WHERE product_id = ?
            ");
            $stmtAlert->execute([$product['id']]);
            $alerts = $stmtAlert->fetchAll();
            
            foreach ($alerts as $alert) {
                if (!in_array($alert['alert_message'], $_SESSION['alerts'])) {
                    $_SESSION['alerts'][] = $alert['alert_message'];
                }
            }
        }
    }

    // 3. CÁLCULO FINANCEIRO
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'];
    }

    // 4. SALVAR NO BANCO DE DADOS
    $stmt = $pdo->prepare("
        INSERT INTO leads (
            session_id, name, email, cpf, pain, pressure, diabetes, 
            sleep, emotional, gut, observations, total_price, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $sessionId = session_id();
    if (empty($sessionId)) $sessionId = 'sess_' . uniqid();
    
    $refUserId = $_SESSION['ref_user_id'] ?? null;

    $stmt->execute([
        $sessionId,
        $name,
        $email,
        $cpf,
        $pain,
        $pressure,
        $diabetes,
        $sleep,
        $emotional,
        $gut,
        $observations,
        $total,
        $refUserId
    ]);
    
    $leadId = $pdo->lastInsertId();
    
    // Salvar itens do protocolo
    $stmtItem = $pdo->prepare("
        INSERT INTO protocol_items (lead_id, product_name, usage_instruction, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($cart as $item) {
        $stmtItem->execute([
            $leadId, 
            $item['name'], 
            $item['usage'], 
            $item['price']
        ]);
    }
    
    // Salvar na sessão para a tela de resultado
    $_SESSION['protocol'] = $cart;
    $_SESSION['total'] = $total;
    $_SESSION['observations'] = $observations;
    $_SESSION['lead_name'] = $name;
    $_SESSION['lead_email'] = $email;
    $_SESSION['lead_cpf'] = $cpf;

    // Redirecionar para o resultado
    header('Location: result.php');
    exit;

} catch (Exception $e) {
    // Em caso de erro, log e redireciona para página de erro
    error_log("Erro no process.php: " . $e->getMessage());
    die("Erro ao processar protocolo. Por favor, tente novamente.");
}
?>
