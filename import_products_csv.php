<?php
require 'db.php';

// Configuração de Imagens
$sourceDir = 'Data-base/Produtos/imgs/';
$targetDir = 'assets/products/';

// Criar diretório de destino se não existir
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Mapeamento Manual de Imagens (Nome do Produto -> Nome do Arquivo)
$imageMap = [
    'BORYSLIM' => 'BorySlim.png',
    'DREAMBLISS' => 'DreamBliss.png',
    'LUMINOUS VITA' => 'Luminous_Vitta.png',
    'NXCAP OZON' => 'NXN CAp.png',
    'ÔMEGA 3' => 'OM3 Omega 3.png',
    'TRI MAGNÉSIO' => 'Power Tri Magnesio.png',
    'VIRTUOUS CAPS' => 'Virtous Caps.png',
    'VITA OZON PLUS' => 'Vita OZON Plus.png',
    'K2MK7 e D3' => 'Vitamina_K2.png',
    'XBOOSTER' => 'XBR.png',
    
    // Novos mapeamentos
    'Amyno Ozon' => 'Amyno Ozon.png',
    'Nano Blend' => 'Nano Blend.png',
    
    // Mapeamentos adicionais baseados em similaridade
    'MELATOZON' => 'Prancheta1.png', // Verificar se é este
];

// Ler o arquivo CSV
$csvFile = 'Data-base/Produtos/provittalife_produtos_completos_db.csv';
if (!file_exists($csvFile)) {
    die("Arquivo CSV não encontrado: $csvFile");
}

$file = fopen($csvFile, 'r');
$header = fgetcsv($file); // Ler cabeçalho

echo "Iniciando importação de produtos...\n";

$count = 0;
while (($row = fgetcsv($file)) !== false) {
    // Mapear colunas
    // 0: Categoria, 1: Produto, 2: Tag, 3: Descricao, 4: Preco_Final
    $categoria = $row[0];
    $nome = $row[1];
    $tag = $row[2];
    $descricao = $row[3];
    $preco = floatval(str_replace(',', '.', $row[4])); // Garantir formato numérico
    
    // Determinar Imagem
    $imageUrl = null;
    $imageFile = null;
    
    // 1. Tentar mapeamento direto
    if (isset($imageMap[$nome])) {
        $imageFile = $imageMap[$nome];
    } 
    // 2. Tentar encontrar arquivo com nome similar (opcional, mas arriscado sem fuzzy logic robusta)
    
    if ($imageFile && file_exists($sourceDir . $imageFile)) {
        // Copiar imagem
        $targetFile = $targetDir . $imageFile;
        if (copy($sourceDir . $imageFile, $targetFile)) {
            $imageUrl = $targetFile;
            echo "Imagem vinculada: $imageFile para $nome\n";
        } else {
            echo "Erro ao copiar imagem: $imageFile\n";
        }
    }
    
    // Verificar se produto já existe
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->execute([$nome]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Atualizar
        $sql = "UPDATE products SET description = ?, price = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$descricao, $preco];
        
        if ($imageUrl) {
            $sql .= ", image_url = ?";
            $params[] = $imageUrl;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $existing['id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $productId = $existing['id'];
        echo "Atualizado: $nome\n";
    } else {
        // Inserir
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, is_active, is_base, image_url) VALUES (?, ?, ?, 1, 0, ?)");
        $stmt->execute([$nome, $descricao, $preco, $imageUrl]);
        $productId = $pdo->lastInsertId();
        echo "Inserido: $nome\n";
    }
    
    // Gerar Regras Baseadas na Categoria/Tag/Nome
    // Limpar regras antigas deste produto
    $stmt = $pdo->prepare("DELETE FROM product_rules WHERE product_id = ?");
    $stmt->execute([$productId]);
    
    $rules = [];
    
    // Regras por Categoria
    if ($categoria === 'Nutracêuticos') {
        // Regras específicas baseadas no nome
        if (stripos($nome, 'Melatozon') !== false || stripos($nome, 'DREAMBLISS') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'sono'];
        }
        if (stripos($nome, 'XBOOSTER') !== false || stripos($nome, 'VITA OZON') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'energia'];
            $rules[] = ['type' => 'symptom', 'value' => 'cansaco'];
        }
        if (stripos($nome, 'ÔMEGA 3') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'foco'];
            $rules[] = ['type' => 'symptom', 'value' => 'memoria'];
            $rules[] = ['type' => 'symptom', 'value' => 'inflamacao'];
        }
        if (stripos($nome, 'BORYSLIM') !== false) {
            $rules[] = ['type' => 'goal', 'value' => 'emagrecimento'];
        }
        if (stripos($nome, 'Imune') !== false || stripos($nome, 'VITA') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'imunidade'];
        }
        if (stripos($nome, 'K2MK7') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'ossos'];
            $rules[] = ['type' => 'symptom', 'value' => 'coracao'];
        }
    }
    
    if ($categoria === 'Bem Estar') {
        if (stripos($nome, 'SABONETE') !== false || stripos($nome, 'HIDRATANTE') !== false || stripos($nome, 'SÉRUM') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'pele'];
        }
        if (stripos($nome, 'DOR') !== false || stripos($nome, 'MASSAGEM') !== false || stripos($nome, 'PURE HOT') !== false) {
            $rules[] = ['type' => 'symptom', 'value' => 'dores'];
        }
    }
    
    if ($categoria === 'Linha Capilar') {
        $rules[] = ['type' => 'symptom', 'value' => 'cabelo'];
        $rules[] = ['type' => 'symptom', 'value' => 'queda_cabelo'];
    }
    
    // Inserir Regras
    foreach ($rules as $rule) {
        $stmt = $pdo->prepare("INSERT INTO product_rules (product_id, condition_type, condition_value, priority) VALUES (?, ?, ?, 1)");
        $stmt->execute([$productId, $rule['type'], $rule['value']]);
    }
    
    $count++;
}

fclose($file);
echo "\nImportação concluída! $count produtos processados.\n";
?>
