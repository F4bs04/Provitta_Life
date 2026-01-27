<?php
// Configuração: altere para 'mysql' quando for para a Hostinger
$db_type = 'sqlite'; // 'mysql' ou 'sqlite'

if ($db_type === 'mysql') {
    $host = 'localhost';
    $db   = 'u573831812_Prolife_banco';
    $user = 'u573831812_prolife';
    $pass = 'Prolife_2K25***';
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
            total_price DECIMAL(10, 2) DEFAULT 0.00,
            status TEXT DEFAULT 'orcamento_gerado',
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
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
            name TEXT,
            cpf TEXT,
            role TEXT DEFAULT 'consultant',
            permissions TEXT DEFAULT 'view_leads,manage_leads,manage_products'
        )");

        // Criar tabela de produtos
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            usage_instruction TEXT,
            price DECIMAL(10, 2) NOT NULL,
            is_base INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            image_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Adicionar colunas se não existirem (para bancos existentes)
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN image_url TEXT");
        } catch (Exception $e) {
            // Coluna já existe, ignorar erro
        }
        
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN description TEXT");
        } catch (Exception $e) {
            // Coluna já existe, ignorar erro
        }

        // Criar tabela de regras de produtos
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            condition_type TEXT NOT NULL,
            condition_value TEXT NOT NULL,
            priority INTEGER DEFAULT 0,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");

        // Criar tabela de alertas de produtos
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_alerts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            alert_message TEXT NOT NULL,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");

        // Criar tabela de códigos de convite
        $pdo->exec("CREATE TABLE IF NOT EXISTS invite_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE NOT NULL,
            created_by INTEGER NOT NULL,
            used_by INTEGER, -- Mantido para compatibilidade, mas agora usamos usage_count
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            used_at DATETIME,
            is_active INTEGER DEFAULT 1,
            expires_at DATETIME,
            max_uses INTEGER DEFAULT 1,
            usage_count INTEGER DEFAULT 0,
            is_exceptional INTEGER DEFAULT 0,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (used_by) REFERENCES users(id)
        )");

        // Adicionar colunas novas se não existirem
        try {
            $pdo->exec("ALTER TABLE invite_codes ADD COLUMN max_uses INTEGER DEFAULT 1");
        } catch (Exception $e) {}
        
        try {
            $pdo->exec("ALTER TABLE invite_codes ADD COLUMN usage_count INTEGER DEFAULT 0");
        } catch (Exception $e) {}

        try {
            $pdo->exec("ALTER TABLE invite_codes ADD COLUMN is_exceptional INTEGER DEFAULT 0");
        } catch (Exception $e) {}

        // Criar tabela de sessões de usuário
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            session_id TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            invite_code TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Adicionar coluna is_invite_user na tabela users se não existir
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_invite_user INTEGER DEFAULT 0");
        } catch (Exception $e) {
            // Coluna já existe, ignorar erro
        }

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT 'view_leads,manage_leads,manage_products'");
        } catch (Exception $e) {
            // Coluna já existe, ignorar erro
        }

        // Criar usuários master padrão se não existirem
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $password = '@acessoPro#123';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'master';
            $permissions = 'view_leads,manage_leads,manage_products';
            
            $master_users = [
                ['admin', 'Administrador Master'],
                ['leandro', 'Leandro - Diretor CEO'],
                ['valbrito', 'Val Brito - Diretor Comercial'],
                ['lucio', 'Lúcio - Diretor Tecnológico'],
                ['laudimar', 'Laudimar - Financeiro'],
                ['sol', 'Sol - Financeiro - Nutricionista'],
                ['mariahercilina', 'Maria Hercilina - Terapeuta']
            ];
            
            foreach ($master_users as $u) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, permissions) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$u[0], $hashed_password, $u[1], $role, $permissions]);
            }
        }
    }
} catch (\PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>
