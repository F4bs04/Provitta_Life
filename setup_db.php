<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without database selected
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS provitta_life");
    echo "Database 'provitta_life' created or already exists.<br>";

    // Select Database
    $pdo->exec("USE provitta_life");

    // Read schema.sql
    $sql = file_get_contents('schema.sql');
    
    // Execute Schema
    $pdo->exec($sql);
    echo "Tables created successfully.<br>";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
