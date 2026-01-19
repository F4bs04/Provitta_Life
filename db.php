<?php
// Configuração: altere para 'mysql' quando for para a Hostinger
$db_type = 'sqlite'; // 'mysql' ou 'sqlite'

if ($db_type === 'mysql') {
    $host = 'localhost';
    $db   = 'provitta_life';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
} else {
    $db_path = __DIR__ . '/database.sqlite';
    $dsn = "sqlite:$db_path";
    $user = null;
    $pass = null;
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Se for SQLite, garantir que as tabelas existam
    if ($db_type === 'sqlite') {
        // Criar tabelas se não existirem (SQLite syntax)
        $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            name TEXT,
            email TEXT,
            cpf TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            pain TEXT DEFAULT 'no',
            pressure TEXT DEFAULT 'no',
            diabetes TEXT DEFAULT 'no',
            sleep TEXT DEFAULT 'good',
            emotional TEXT DEFAULT 'stable',
            gut TEXT DEFAULT 'normal',
            observations TEXT,
            total_price DECIMAL(10, 2) DEFAULT 0.00
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS protocol_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            usage_instruction TEXT,
            price DECIMAL(10, 2),
            FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin'
        )");

        // Criar usuário admin padrão se não existir
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$password', 'admin')");
        }
    }
} catch (\PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>
