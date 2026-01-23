<?php
echo "Loaded Extensions:\n";
print_r(get_loaded_extensions());
echo "\nPDO Drivers:\n";
print_r(PDO::getAvailableDrivers());
?>
