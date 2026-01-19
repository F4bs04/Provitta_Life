<?php
echo "Testing database connection...\n\n";

require_once 'db.php';

try {
    echo "✓ Database connected successfully!\n";
    echo "Database type: " . (strpos($dsn, 'sqlite') !== false ? 'SQLite' : 'MySQL') . "\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Users table exists. Count: " . $result['count'] . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM leads");
    $result = $stmt->fetch();
    echo "✓ Leads table exists. Count: " . $result['count'] . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM protocol_items");
    $result = $stmt->fetch();
    echo "✓ Protocol items table exists. Count: " . $result['count'] . "\n";
    
    echo "\n✓ All tests passed!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
