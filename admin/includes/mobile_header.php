<header class="md:hidden flex items-center justify-between px-4 py-3 bg-surface/95 backdrop-blur-xl border-b border-white/10 sticky top-0 z-30">
    <a href="admin_dashboard.php">
        <img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 w-auto">
    </a>
    <button @click.stop="sidebarOpen = !sidebarOpen" class="p-2 text-gray-400 hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>
</header>
