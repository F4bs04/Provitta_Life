<?php
require_once 'db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();

    if (!$user) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, permissions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password, 'Administrador', 'admin', 'view_leads,manage_leads,manage_products,manage_users']);
        echo "Admin user created successfully.\n";
    } else {
        echo "Admin user already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
