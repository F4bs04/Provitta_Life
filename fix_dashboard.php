<?php
// Script to remove header from dashboard
$file = 'admin/admin_dashboard.php';
$content = file_get_contents($file);

// Remove header block
$content = preg_replace('/    <!-- Header -->.*?<\/header>\s+/s', '', $content);

// Fix main tag
$content = str_replace('container mx-auto px-6 py-8', 'p-6', $content);

// Add closing wrapper before </body>
$content = str_replace('</main>', '</main>' . "\n\n</div><!-- End content-wrapper -->", $content);

file_put_contents($file, $content);
echo "Dashboard header removed successfully\n";
?>
