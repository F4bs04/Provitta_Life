<?php
require_once 'db.php';

echo "Atualizando estrutura do banco de dados...\n\n";

try {
    // Verificar se a coluna status já existe
    $stmt = $pdo->query("PRAGMA table_info(leads)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasStatus = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'status') {
            $hasStatus = true;
            break;
        }
    }
    
    if (!$hasStatus) {
        echo "✓ Adicionando coluna 'status' à tabela leads...\n";
        $pdo->exec("ALTER TABLE leads ADD COLUMN status TEXT DEFAULT 'orcamento_gerado'");
        echo "✓ Coluna adicionada com sucesso!\n";
    } else {
        echo "✓ Coluna 'status' já existe.\n";
    }
    
    echo "\n✓ Banco de dados atualizado com sucesso!\n";
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>
