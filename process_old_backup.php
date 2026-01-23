<?php
session_start();

// 1. Injeção da Base (Obrigatórios)
$cart = [
    'NXCAP' => [
        'name' => 'NXCAP',
        'usage' => 'Uso Diário',
        'price' => 150.00 // Exemplo
    ],
    'Power Trimagnesio' => [
        'name' => 'Power Trimagnesio',
        'usage' => 'Uso Diário',
        'price' => 120.00 // Exemplo
    ]
];

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

// 2. Empilhamento dos Módulos (Lógica)

// Módulo Dor
if ($pain === 'yes') {
    $cart['Oleo SOFH'] = ['name' => 'Óleo SOFH', 'usage' => 'Ingestão', 'price' => 80.00];
    $cart['Omega 3'] = ['name' => 'Ômega 3', 'usage' => 'Ingestão', 'price' => 90.00];
    $cart['Gel Life Shii'] = ['name' => 'Gel Life Shii', 'usage' => 'Aplicação local', 'price' => 50.00];
}

// Módulo Pressão Alta
if ($pressure === 'yes') {
    $cart['Oleo SOFH'] = ['name' => 'Óleo SOFH', 'usage' => 'Ingestão', 'price' => 80.00];
    $_SESSION['alerts'][] = "Cuidado com estimulantes";
}

// Módulo Diabetes
if ($diabetes === 'yes') {
    // Adicionar produtos específicos para diabetes se houver
}

// Módulo Sono
if ($sleep === 'bad') {
    // Adicionar produtos para sono
}

// Módulo Emocional
if ($emotional === 'unstable') {
    $cart['Melatonina+CoQ10'] = ['name' => 'Melatonina+CoQ10', 'usage' => 'Noite', 'price' => 110.00];
    $cart['Polivitaminico'] = ['name' => 'Polivitamínico', 'usage' => 'Manhã', 'price' => 60.00];
    $cart['Sache Energetico'] = ['name' => 'Sachê Energético', 'usage' => 'Manhã', 'price' => 40.00];
}

// Módulo Intestino
if ($gut === 'constipated') {
    // Produtos para intestino preso
} elseif ($gut === 'loose') {
    // Produtos para intestino solto
}

// 3. Cálculo Financeiro
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'];
}

// Salvar no Banco de Dados
require_once 'db.php';

try {
    $stmt = $pdo->prepare("INSERT INTO leads (session_id, name, email, cpf, pain, pressure, diabetes, sleep, emotional, gut, observations, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $sessionId = session_id();
    if (empty($sessionId)) $sessionId = 'sess_' . uniqid();
    
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
        $total
    ]);
    
    $leadId = $pdo->lastInsertId();
    
    // Salvar itens do protocolo
    $stmtItem = $pdo->prepare("INSERT INTO protocol_items (lead_id, product_name, usage_instruction, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $stmtItem->execute([$leadId, $item['name'], $item['usage'], $item['price']]);
    }
    
} catch (Exception $e) {
    // Log error silently or handle it
    // error_log($e->getMessage());
}

// Salvar na sessão para a tela de resultado
$_SESSION['protocol'] = $cart;
$_SESSION['total'] = $total;
$_SESSION['observations'] = $observations;
$_SESSION['lead_name'] = $name;
$_SESSION['lead_email'] = $email;
$_SESSION['lead_cpf'] = $cpf;
$_SESSION['alerts'] = $_SESSION['alerts'] ?? [];

// Simular tempo de processamento (opcional)
// sleep(2);

// Redirecionar para o resultado
header('Location: result.php');
exit;
?>
