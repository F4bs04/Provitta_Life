<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop and recreate for dev
    $pdo->exec("DROP DATABASE IF EXISTS provitta_life");
    $pdo->exec("CREATE DATABASE provitta_life");
    $pdo->exec("USE provitta_life");

    $sql = file_get_contents('schema.sql');
    $pdo->exec($sql);
    echo "Database and tables reset successfully.\n";

    // Create default admin
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$username, $password]);
    echo "Default admin created: admin / admin123\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
