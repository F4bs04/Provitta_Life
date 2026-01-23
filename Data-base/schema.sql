

CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    email VARCHAR(255),
    cpf VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pain ENUM('yes', 'no') DEFAULT 'no',
    pressure ENUM('yes', 'no') DEFAULT 'no',
    diabetes ENUM('yes', 'no') DEFAULT 'no',
    sleep ENUM('good', 'bad') DEFAULT 'good',
    emotional ENUM('stable', 'unstable') DEFAULT 'stable',
    gut ENUM('normal', 'constipated', 'loose') DEFAULT 'normal',
    observations TEXT,
    total_price DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('orcamento_gerado', 'compra_confirmada', 'produto_comprado', 'recompra') DEFAULT 'orcamento_gerado',
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS protocol_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    usage_instruction VARCHAR(255),
    price DECIMAL(10, 2),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    cpf VARCHAR(20),
    role ENUM('master', 'consultant') DEFAULT 'consultant'
);
