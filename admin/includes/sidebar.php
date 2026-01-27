<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_master = ($_SESSION['admin_role'] ?? 'consultant') === 'master';

$nav_items = [
    ['href' => 'admin_dashboard.php', 'label' => 'Dashboard', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>'],
    ['href' => 'admin_analytics.php', 'label' => 'Analytics', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>'],
    ['href' => 'products.php', 'label' => 'Produtos', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>'],
    ['href' => 'invite_codes.php', 'label' => 'Convites', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>'],
    ['href' => 'admin_users.php', 'label' => 'Usuários', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>', 'master_only' => true],
    ['href' => 'admin_settings.php', 'label' => 'Configurações', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>'],
];
?>

<aside 
    x-data="{ expanded: false }"
    class="fixed inset-y-0 left-0 z-40 px-4 py-6 bg-surface/95 backdrop-blur-xl border-r border-white/10 md:static md:relative md:inset-auto md:h-screen md:flex-shrink-0 flex flex-col justify-between transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0 md:transform-none md:w-20 md:z-auto"
    :class="{ 'translate-x-0': sidebarOpen, 'md:w-64': expanded, 'md:w-20': !expanded }"
    @mouseenter="expanded = true"
    @mouseleave="expanded = false"
    @click.away="sidebarOpen = false"
>
    <div>
        <div class="flex items-center justify-between">
            <a href="admin_dashboard.php" class="flex items-center gap-2 text-white font-bold text-xl transition-opacity duration-200 whitespace-nowrap overflow-hidden">
                <img src="../assets/src/favicon.icon" alt="Provitta Life" class="h-8 w-auto flex-shrink-0">
            </a>
        </div>
        <nav class="mt-10 space-y-2">
            <?php foreach ($nav_items as $item): ?>
                <?php if (isset($item['master_only']) && $item['master_only'] && !$is_master) continue; ?>
                <a href="<?php echo $item['href']; ?>" 
                   class="flex items-center gap-4 p-3 rounded-2xl transition-colors duration-200 <?php echo $current_page === $item['href'] ? 'bg-primary text-background shadow-lg shadow-primary/30' : 'text-gray-300 hover:bg-white/5 hover:text-white'; ?>"
                   title="<?php echo $item['label']; ?>">
                    <?php echo $item['icon']; ?>
                    <span class="font-medium whitespace-nowrap md:hidden"><?php echo $item['label']; ?></span>
                    <span class="font-medium whitespace-nowrap hidden md:inline" x-show="expanded"><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="space-y-2">
        <a href="admin_logout.php" 
           class="flex items-center gap-4 p-3 rounded-2xl text-red-400 hover:bg-red-500/10 transition-colors duration-200"
           title="Sair">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span class="font-medium whitespace-nowrap md:hidden">Sair</span>
            <span class="font-medium whitespace-nowrap hidden md:inline" x-show="expanded">Sair</span>
        </a>
    </div>
</aside>
<!-- Main Content -->
<!-- Header -->
<!-- Content -->
