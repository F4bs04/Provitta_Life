<?php
// Teste simples do servidor PHP
echo "✅ Servidor PHP funcionando!\n";
echo "PHP Version: " . phpversion() . "\n";
echo "SQLite disponível: " . (extension_loaded('pdo_sqlite') ? 'Sim' : 'Não') . "\n";
echo "\n";
echo "Para iniciar o servidor, execute:\n";
echo "php -S localhost:8000 -c php.ini\n";
echo "\n";
echo "Depois acesse:\n";
echo "- Landing Page: http://localhost:8000\n";
echo "- Admin: http://localhost:8000/admin/admin_login.php\n";
?>
