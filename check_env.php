<?php
echo "PHP Version: " . phpversion() . "\n";
echo "Configuration File (php.ini) Path: " . php_ini_loaded_file() . "\n";
echo "Extension Directory: " . ini_get('extension_dir') . "\n";
echo "mbstring extension: " . (extension_loaded('mbstring') ? 'Loaded' : 'Not Loaded') . "\n";
echo "gd extension: " . (extension_loaded('gd') ? 'Loaded' : 'Not Loaded') . "\n";
echo "mysqli extension: " . (extension_loaded('mysqli') ? 'Loaded' : 'Not Loaded') . "\n";
?>
