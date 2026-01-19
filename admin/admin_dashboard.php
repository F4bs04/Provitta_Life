<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

require_once '../db.php';

// Buscar todos os leads com seus itens de protocolo
$stmt = $pdo->query("
    SELECT l.*, 
           GROUP_CONCAT(pi.product_name || ' (' || pi.usage_instruction || ')' || ' - R$ ' || pi.price, '; ') as products
    FROM leads l
    LEFT JOIN protocol_items pi ON l.id = pi.lead_id
    GROUP BY l.id
    ORDER BY l.created_at DESC
");
$leads = $stmt->fetchAll();

// Organizar leads por status
$kanban = [
    'orcamento_gerado' => [],
    'compra_confirmada' => [],
    'produto_comprado' => [],
    'recompra' => []
];

foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'orcamento_gerado';
    $kanban[$status][] = $lead;
}

// Atualizar status via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $leadId = $_POST['lead_id'];
    $newStatus = $_POST['new_status'];
    
    $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $leadId]);
    
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Provitta Life</title>
    <link rel="icon" href="../assets/src/favicon.icon" type="image/x-icon">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .kanban-column { min-height: 400px; }
        .lead-card { transition: all 0.3s ease; }
        .lead-card:hover { transform: translateY(-2px); }
        .sortable-ghost { opacity: 0.4; }
        .sortable-drag { cursor: grabbing !important; }
    </style>
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen">

    <!-- Dot Grid Background -->
    <div id="dot-grid" class="dot-grid"></div>
    <script src="../assets/js/background.js"></script>

    <!-- Header -->
    <header class="relative z-10 border-b border-white/10 bg-black/30 backdrop-blur-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 md:h-10 w-auto">
                <h1 class="text-2xl font-bold text-white">Dashboard Administrativo</h1>
            </div>
            <div class="flex items-center gap-4">
                <!-- View Toggle -->
                <div class="flex items-center gap-2 bg-surface/80 rounded-xl p-1 border border-white/10" x-data="{ view: 'kanban' }">
                    <button 
                        @click="view = 'kanban'; $dispatch('view-changed', 'kanban')"
                        :class="view === 'kanban' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'"
                        class="px-4 py-2 rounded-lg transition-all font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                        Kanban
                    </button>
                    <button 
                        @click="view = 'list'; $dispatch('view-changed', 'list')"
                        :class="view === 'list' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'"
                        class="px-4 py-2 rounded-lg transition-all font-medium text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        Lista
                    </button>
                </div>
                
                <span class="text-sm text-gray-400">👤 <?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'Admin'); ?></span>
                <a href="admin_logout.php" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors">Sair</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 container mx-auto px-6 py-8" x-data="dashboardApp()">
        
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm font-medium">Orçamentos Gerados</p>
                        <p class="text-3xl font-bold text-primary mt-2"><?php echo count($kanban['orcamento_gerado']); ?></p>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm font-medium">Compras Confirmadas</p>
                        <p class="text-3xl font-bold text-yellow-400 mt-2"><?php echo count($kanban['compra_confirmada']); ?></p>
                    </div>
                    <div class="p-3 bg-yellow-400/10 rounded-xl">
                        <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm font-medium">Produtos Comprados</p>
                        <p class="text-3xl font-bold text-green-400 mt-2"><?php echo count($kanban['produto_comprado']); ?></p>
                    </div>
                    <div class="p-3 bg-green-400/10 rounded-xl">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm font-medium">Recompras</p>
                        <p class="text-3xl font-bold text-secondary mt-2"><?php echo count($kanban['recompra']); ?></p>
                    </div>
                    <div class="p-3 bg-secondary/10 rounded-xl">
                        <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban View -->
        <div x-show="viewMode === 'kanban'" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Coluna: Orçamento Gerado -->
            <div class="kanban-column">
                <div class="bg-primary/10 border border-primary/20 rounded-2xl p-4 mb-4">
                    <h3 class="font-bold text-primary flex items-center gap-2">
                        <span class="w-3 h-3 bg-primary rounded-full"></span>
                        Orçamento Gerado
                        <span class="ml-auto text-sm"><?php echo count($kanban['orcamento_gerado']); ?></span>
                    </h3>
                </div>
                <div class="space-y-4 sortable-column" data-status="orcamento_gerado">
                    <?php foreach ($kanban['orcamento_gerado'] as $lead): ?>
                        <?php include 'lead_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Coluna: Compra Confirmada -->
            <div class="kanban-column">
                <div class="bg-yellow-400/10 border border-yellow-400/20 rounded-2xl p-4 mb-4">
                    <h3 class="font-bold text-yellow-400 flex items-center gap-2">
                        <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                        Compra Confirmada
                        <span class="ml-auto text-sm"><?php echo count($kanban['compra_confirmada']); ?></span>
                    </h3>
                </div>
                <div class="space-y-4 sortable-column" data-status="compra_confirmada">
                    <?php foreach ($kanban['compra_confirmada'] as $lead): ?>
                        <?php include 'lead_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Coluna: Produto Comprado -->
            <div class="kanban-column">
                <div class="bg-green-400/10 border border-green-400/20 rounded-2xl p-4 mb-4">
                    <h3 class="font-bold text-green-400 flex items-center gap-2">
                        <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                        Produto Comprado
                        <span class="ml-auto text-sm"><?php echo count($kanban['produto_comprado']); ?></span>
                    </h3>
                </div>
                <div class="space-y-4 sortable-column" data-status="produto_comprado">
                    <?php foreach ($kanban['produto_comprado'] as $lead): ?>
                        <?php include 'lead_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Coluna: Recompra -->
            <div class="kanban-column">
                <div class="bg-secondary/10 border border-secondary/20 rounded-2xl p-4 mb-4">
                    <h3 class="font-bold text-secondary flex items-center gap-2">
                        <span class="w-3 h-3 bg-secondary rounded-full"></span>
                        Recompra
                        <span class="ml-auto text-sm"><?php echo count($kanban['recompra']); ?></span>
                    </h3>
                </div>
                <div class="space-y-4 sortable-column" data-status="recompra">
                    <?php foreach ($kanban['recompra'] as $lead): ?>
                        <?php include 'lead_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- List View -->
        <div x-show="viewMode === 'list'" x-cloak class="space-y-4">
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-black/30 border-b border-white/10">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Contato</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($leads as $lead): ?>
                            <tr class="hover:bg-white/5 transition-colors cursor-pointer" @click="toggleCard(<?php echo $lead['id']; ?>)">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white"><?php echo htmlspecialchars($lead['name'] ?? 'Sem nome'); ?></div>
                                    <div class="text-sm text-gray-400"><?php echo htmlspecialchars($lead['cpf'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-300"><?php echo htmlspecialchars($lead['email'] ?? 'Sem email'); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusColors = [
                                        'orcamento_gerado' => ['bg' => 'bg-primary/20', 'text' => 'text-primary', 'label' => 'Orçamento'],
                                        'compra_confirmada' => ['bg' => 'bg-yellow-400/20', 'text' => 'text-yellow-400', 'label' => 'Confirmada'],
                                        'produto_comprado' => ['bg' => 'bg-green-400/20', 'text' => 'text-green-400', 'label' => 'Comprado'],
                                        'recompra' => ['bg' => 'bg-secondary/20', 'text' => 'text-secondary', 'label' => 'Recompra']
                                    ];
                                    $statusInfo = $statusColors[$lead['status']] ?? $statusColors['orcamento_gerado'];
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusInfo['bg'] . ' ' . $statusInfo['text']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-primary">R$ <?php echo number_format($lead['total_price'], 2, ',', '.'); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-400"><?php echo date('d/m/Y', strtotime($lead['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <button @click.stop="toggleCard(<?php echo $lead['id']; ?>)" class="text-primary hover:text-secondary transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function dashboardApp() {
            return {
                viewMode: 'kanban',
                expandedCard: null,
                
                init() {
                    // Listen for view changes
                    window.addEventListener('view-changed', (e) => {
                        this.viewMode = e.detail;
                    });
                    
                    // Initialize Sortable for Kanban columns
                    this.initSortable();
                },
                
                initSortable() {
                    const columns = document.querySelectorAll('.sortable-column');
                    columns.forEach(column => {
                        new Sortable(column, {
                            group: 'kanban',
                            animation: 150,
                            ghostClass: 'sortable-ghost',
                            dragClass: 'sortable-drag',
                            onEnd: (evt) => {
                                const leadId = evt.item.dataset.leadId;
                                const newStatus = evt.to.dataset.status;
                                
                                if (leadId && newStatus) {
                                    this.updateStatus(leadId, newStatus);
                                }
                            }
                        });
                    });
                },
                
                toggleCard(id) {
                    this.expandedCard = this.expandedCard === id ? null : id;
                },
                
                async updateStatus(leadId, newStatus) {
                    try {
                        const formData = new FormData();
                        formData.append('update_status', '1');
                        formData.append('lead_id', leadId);
                        formData.append('new_status', newStatus);
                        
                        const response = await fetch('admin_dashboard.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (response.ok) {
                            // Show success notification (optional)
                            setTimeout(() => window.location.reload(), 500);
                        }
                    } catch (error) {
                        console.error('Erro ao atualizar status:', error);
                        alert('Erro ao atualizar status. Tente novamente.');
                    }
                }
            }
        }
    </script>
</body>
</html>
