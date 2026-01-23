<?php
require_once 'db.php';

try {
    echo "Iniciando migração...\n";

    // Adicionar colunas na tabela users
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN name TEXT");
        echo "Coluna 'name' adicionada em 'users'.\n";
    } catch (Exception $e) { echo "Coluna 'name' já existe ou erro: " . $e->getMessage() . "\n"; }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN cpf TEXT");
        echo "Coluna 'cpf' adicionada em 'users'.\n";
    } catch (Exception $e) { echo "Coluna 'cpf' já existe ou erro: " . $e->getMessage() . "\n"; }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'consultant'");
        echo "Coluna 'role' adicionada em 'users'.\n";
    } catch (Exception $e) { echo "Coluna 'role' já existe ou erro: " . $e->getMessage() . "\n"; }

    // Atualizar admin existente para master
    $pdo->exec("UPDATE users SET role = 'master' WHERE username = 'admin'");
    echo "Usuário 'admin' atualizado para role 'master'.\n";

    // Adicionar coluna na tabela leads
    try {
        $pdo->exec("ALTER TABLE leads ADD COLUMN user_id INTEGER");
        echo "Coluna 'user_id' adicionada em 'leads'.\n";
    } catch (Exception $e) { echo "Coluna 'user_id' já existe ou erro: " . $e->getMessage() . "\n"; }

    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    die("Erro na migração: " . $e->getMessage());
}
?>
