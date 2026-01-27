<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin - Provitta Life'; ?></title>
    <link rel="icon" href="../assets/src/favicon.icon" type="image/x-icon">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased">
    <div class="relative min-h-screen md:flex" x-data="{ sidebarOpen: false }">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 min-w-0 flex flex-col overflow-hidden">
            <?php include 'mobile_header.php'; ?>
            <div class="fixed inset-0 bg-black/50 md:hidden" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak></div>
