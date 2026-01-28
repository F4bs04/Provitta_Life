<?php
/**
 * Importador de produtos a partir do CSV oficial
 *
 * Fonte: Data-base/Produtos/provittalife_produtos_completos_db.csv
 * Atualiza nome, descrição e preço na tabela products
 * - Faz correspondência por nome (case-insensitive)
 * - Atualiza produtos existentes
 * - Insere novos produtos como condicionais ativas
 */

require_once __DIR__ . '/db.php';

$csvPath = __DIR__ . '/Data-base/Produtos/provittalife_produtos_completos_db.csv';

if (!file_exists($csvPath)) {
    echo "Arquivo CSV não encontrado em: {$csvPath}\n";
    exit(1);
}

if (!is_readable($csvPath)) {
    echo "Arquivo CSV não é legível: {$csvPath}\n";
    exit(1);
}

echo "=== Importação de Produtos a partir do CSV ===\n\n";

echo "Lendo: {$csvPath}\n\n";

$handle = fopen($csvPath, 'r');
if ($handle === false) {
    echo "Não foi possível abrir o arquivo CSV.\n";
    exit(1);
}

// Esperado: Categoria,Produto,Tag,Descricao,Preco_Final,Preco_Revendedor,Comissao_Pts,Pontos_Graduacao,Quantidade_Padrao
$header = fgetcsv($handle, 0, ',');
if ($header === false) {
    echo "CSV está vazio.\n";
    fclose($handle);
    exit(1);
}

// Mapear índices das colunas principais
$columns = array_map('trim', $header);
$idxProduto   = array_search('Produto', $columns);
$idxDescricao = array_search('Descricao', $columns);
$idxPreco     = array_search('Preco_Final', $columns);

if ($idxProduto === false || $idxPreco === false) {
    echo "CSV não contém as colunas esperadas 'Produto' e 'Preco_Final'.\n";
    fclose($handle);
    exit(1);
}

$idxCategoria = array_search('Categoria', $columns);

$updated = 0;
$inserted = 0;
$skipped = 0;

// Preparar statements
$selectStmt = $pdo->prepare("SELECT * FROM products WHERE UPPER(name) = UPPER(?) LIMIT 1");
$updateStmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
$insertStmt = $pdo->prepare("INSERT INTO products (name, description, usage_instruction, price, is_base, is_active, image_url) VALUES (?, ?, ?, ?, 0, 1, NULL)");

while (($row = fgetcsv($handle, 0, ',')) !== false) {
    // Ignorar linhas vazias
    if (count($row) === 0) {
        continue;
    }

    $produtoNome = isset($row[$idxProduto]) ? trim($row[$idxProduto]) : '';
    if ($produtoNome === '') {
        $skipped++;
        continue;
    }

    $descricao = ($idxDescricao !== false && isset($row[$idxDescricao])) ? trim($row[$idxDescricao]) : '';
    $precoStr  = isset($row[$idxPreco]) ? trim($row[$idxPreco]) : '';

    if ($precoStr === '') {
        $skipped++;
        continue;
    }

    // Converter preço para float (ponto como separador decimal)
    $precoStr = str_replace(['R$', ' '], '', $precoStr);
    $precoStr = str_replace(',', '.', $precoStr);
    $preco = floatval($precoStr);

    if ($preco <= 0) {
        $skipped++;
        continue;
    }

    $categoria = ($idxCategoria !== false && isset($row[$idxCategoria])) ? trim($row[$idxCategoria]) : '';

    // Buscar produto existente por nome (case-insensitive)
    $selectStmt->execute([$produtoNome]);
    $existing = $selectStmt->fetch();

    // Regra presumida para usage_instruction: vazio por padrão (pode ser ajustado depois no admin)
    $usageInstruction = '';

    if ($existing) {
        // Atualizar campos principais
        $currentDescription = $existing['description'] ?? '';
        $newDescription = $descricao !== '' ? $descricao : $currentDescription;

        $updateStmt->execute([
            $produtoNome,
            $newDescription,
            $preco,
            $existing['id'],
        ]);

        echo "Atualizado: {$produtoNome} -> R$ {$preco}\n";
        $updated++;
    } else {
        // Inserir novo produto condicional, ativo
        $insertStmt->execute([
            $produtoNome,
            $descricao,
            $usageInstruction,
            $preco,
        ]);

        echo "Inserido: {$produtoNome} -> R$ {$preco}\n";
        $inserted++;
    }
}

fclose($handle);

echo "\n=== Resumo da Importação ===\n";
echo "Atualizados: {$updated}\n";
echo "Inseridos:  {$inserted}\n";
echo "Ignorados:  {$skipped}\n";

echo "\nConcluído.\n";

