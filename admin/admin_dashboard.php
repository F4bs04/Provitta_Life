<?php
session_start();
require_once '../db.php';

$page_title = 'Dashboard Admin - Provitta Life';

// Fetch and process leads
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_user_id'])) {
    header('Location: admin_login.php');
    exit;
}
$userId = $_SESSION['admin_user_id'];
$userRole = $_SESSION['admin_role'] ?? 'consultant';


// Filter Parameters
$dateStart = $_GET['date_start'] ?? date('Y-m-01');
$dateEnd = $_GET['date_end'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? '';
$showArchived = $_GET['show_archived'] ?? '0';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;


// Check if invite_code_id column exists (backward compatibility)
try {
    $columnCheck = $pdo->query("SHOW COLUMNS FROM leads LIKE 'invite_code_id'");
    $hasInviteTracking = $columnCheck->rowCount() > 0;
} catch (Exception $e) {
    // If check fails, assume column doesn't exist
    $hasInviteTracking = false;
    error_log("Invite tracking check failed: " . $e->getMessage());
}

// Build query based on whether invite tracking is available
if ($hasInviteTracking) {
    $query = "SELECT l.*, 
        GROUP_CONCAT(pi.product_name || ' (' || pi.usage_instruction || ')' || ' - R$ ' || pi.price, '; ') as products,
        ic.code as invite_code,
        ic.guest_name as invite_guest_name
        FROM leads l 
        LEFT JOIN protocol_items pi ON l.id = pi.lead_id
        LEFT JOIN invite_codes ic ON l.invite_code_id = ic.id";
} else {
    $query = "SELECT l.*, 
        GROUP_CONCAT(pi.product_name || ' (' || pi.usage_instruction || ')' || ' - R$ ' || pi.price, '; ') as products,
        NULL as invite_code,
        NULL as invite_guest_name
        FROM leads l 
        LEFT JOIN protocol_items pi ON l.id = pi.lead_id";
}

$whereClauses = [];
$params = [];

// Role-based filtering
if ($hasInviteTracking && $userRole === 'subscriber') {
    // Subscribers see leads generated from their invite codes
    $whereClauses[] = "(ic.created_by = :user_id OR l.user_id = :user_id)";
    $params[':user_id'] = $userId;
} elseif ($userRole === 'consultant') {
    // Consultants see only their assigned leads
    $whereClauses[] = "l.user_id = :user_id";
    $params[':user_id'] = $userId;
} elseif (!$hasInviteTracking && $userRole === 'subscriber') {
    // Fallback for subscribers without invite tracking
    $whereClauses[] = "l.user_id = :user_id";
    $params[':user_id'] = $userId;
}
// Masters see all leads (no filter)

// Archived filter (check if column exists)
try {
    $archivedCheck = $pdo->query("SHOW COLUMNS FROM leads LIKE 'archived'");
    if ($archivedCheck->rowCount() > 0) {
        if ($showArchived === '1') {
            // Show only archived
            $whereClauses[] = "l.archived = 1";
        } else {
            // Show only non-archived (default)
            $whereClauses[] = "(l.archived IS NULL OR l.archived = 0)";
        }
    }
} catch (Exception $e) {
    // Column doesn't exist yet, ignore
}

if ($dateStart && $dateEnd) {
    $whereClauses[] = "l.created_at BETWEEN :date_start AND :date_end";
    $params[':date_start'] = $dateStart . ' 00:00:00';
    $params[':date_end'] = $dateEnd . ' 23:59:59';
}

if ($statusFilter) {
    $whereClauses[] = "l.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(' AND ', $whereClauses);
}

$query .= " GROUP BY l.id ORDER BY l.created_at DESC ";

try {
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $leads = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    error_log("Query: " . $query);
    die("Erro ao carregar leads. Por favor, contate o administrador. Detalhes: " . $e->getMessage());
}

$kanban = ['orcamento_gerado' => [], 'compra_confirmada' => [], 'produto_comprado' => [], 'recompra' => []];
foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'orcamento_gerado';
    $kanban[$status][] = $lead;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $leadId = $_POST['lead_id'];
    $newStatus = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $leadId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_lead'])) {
    $leadId = $_POST['lead_id'];
    $archived = $_POST['archived'] ?? 1;
    
    try {
        $stmt = $pdo->prepare("UPDATE leads SET archived = ? WHERE id = ?");
        $stmt->execute([$archived, $leadId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-6 lg:p-8" x-data="dashboardApp()">
        
        <!-- Page Header with View Toggle -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-white">Dashboard</h1>
            
            <!-- View Toggle - Desktop Only -->
            <div class="hidden md:flex items-center gap-2 bg-surface/80 rounded-xl p-1 border border-white/10">
                <button 
                    @click="viewMode = 'kanban'"
                    :class="viewMode === 'kanban' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg transition-all font-medium text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                    </svg>
                    Kanban
                </button>
                <button 
                    @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg transition-all font-medium text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    Lista
                </button>
            </div>
        </div>

        <!-- Filtros de Tempo -->
        <div class="mb-6">
            <form method="GET" class="flex flex-wrap items-end gap-4 bg-surface/50 p-4 rounded-xl border border-white/5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Data Início</label>
                    <input type="date" name="date_start" value="<?php echo htmlspecialchars($dateStart); ?>" 
                           class="bg-background border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Data Fim</label>
                    <input type="date" name="date_end" value="<?php echo htmlspecialchars($dateEnd); ?>" 
                           class="bg-background border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Status</label>
                    <select name="status" class="bg-background border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-primary">
                        <option value="">Todos</option>
                        <option value="orcamento_gerado" <?php echo $statusFilter === 'orcamento_gerado' ? 'selected' : ''; ?>>Orçamento Gerado</option>
                        <option value="compra_confirmada" <?php echo $statusFilter === 'compra_confirmada' ? 'selected' : ''; ?>>Compra Confirmada</option>
                        <option value="produto_comprado" <?php echo $statusFilter === 'produto_comprado' ? 'selected' : ''; ?>>Produto Comprado</option>
                        <option value="recompra" <?php echo $statusFilter === 'recompra' ? 'selected' : ''; ?>>Recompra</option>
                    </select>
                </div>
                <button type="submit" class="bg-primary text-background px-4 py-2 rounded-lg font-bold text-sm hover:bg-primary-light transition-colors h-[38px]">
                    Filtrar
                </button>
                <?php if (isset($_GET['date_start']) || isset($_GET['status'])): ?>
                    <a href="admin_dashboard.php" class="text-gray-400 text-sm hover:text-white underline h-[38px] flex items-center">Limpar Filtros</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="pv-card p-6">
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

            <div class="pv-card p-6">
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

            <div class="pv-card p-6">
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

            <div class="pv-card p-6">
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
        <div x-show="viewMode === 'kanban'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Coluna: Orçamento Gerado -->
            <div class="kanban-column">
                <div class="pv-card p-4 mb-4 border border-primary/20">
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
                <div class="pv-card p-4 mb-4 border border-yellow-400/20">
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
                <div class="pv-card p-4 mb-4 border border-green-400/20">
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
                <div class="pv-card p-4 mb-4 border border-secondary/20">
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
        <div x-show="viewMode === 'list'" x-cloak class="space-y-3 md:space-y-4">
            <?php foreach ($leads as $lead): ?>
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden hover:shadow-lg hover:shadow-primary/5 transition-all"
                 x-data="{ expanded: false }">
                
                <!-- Card Header (sempre visível) -->
                <div class="p-4 cursor-pointer" @click="expanded = !expanded">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-bold text-white text-base md:text-lg truncate"><?php echo htmlspecialchars($lead['name'] ?? 'Sem nome'); ?></h4>
                                <?php
                                $statusColors = [
                                    'orcamento_gerado' => ['bg' => 'bg-primary/20', 'text' => 'text-primary', 'label' => 'Orçamento'],
                                    'compra_confirmada' => ['bg' => 'bg-yellow-400/20', 'text' => 'text-yellow-400', 'label' => 'Confirmada'],
                                    'produto_comprado' => ['bg' => 'bg-green-400/20', 'text' => 'text-green-400', 'label' => 'Comprado'],
                                    'recompra' => ['bg' => 'bg-secondary/20', 'text' => 'text-secondary', 'label' => 'Recompra']
                                ];
                                $statusInfo = $statusColors[$lead['status']] ?? $statusColors['orcamento_gerado'];
                                ?>
                                <span class="hidden md:inline-flex px-2 py-1 rounded-full text-xs font-medium <?php echo $statusInfo['bg'] . ' ' . $statusInfo['text']; ?>">
                                    <?php echo $statusInfo['label']; ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span class="truncate"><?php echo htmlspecialchars($lead['email'] ?? 'Sem email'); ?></span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                    <?php echo htmlspecialchars($lead['cpf'] ?? 'N/A'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="text-sm md:text-base font-bold text-primary">R$ <?php echo number_format($lead['total_price'], 2, ',', '.'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($lead['created_at'])); ?></div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 transition-transform flex-shrink-0" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Mobile Status Badge -->
                    <div class="md:hidden mt-2">
                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium <?php echo $statusInfo['bg'] . ' ' . $statusInfo['text']; ?>">
                            <?php echo $statusInfo['label']; ?>
                        </span>
                    </div>
                </div>

                <!-- Card Body (expansível) -->
                <div x-show="expanded" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="border-t border-white/10 p-4 space-y-4">
                    
                    <!-- Anamnese -->
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Anamnese</h5>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                            <!-- Pressão -->
                            <div class="flex items-center gap-2">
                                <?php if ($lead['pressure'] === 'yes'): ?>
                                    <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                    <span class="text-red-400">Pressão alta</span>
                                <?php else: ?>
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-green-400">Pressão OK</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Diabetes -->
                            <div class="flex items-center gap-2">
                                <?php if ($lead['diabetes'] === 'yes'): ?>
                                    <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                    <span class="text-red-400">Diabetes</span>
                                <?php else: ?>
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-green-400">Sem diabetes</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Sono -->
                            <div class="flex items-center gap-2">
                                <?php if ($lead['sleep'] === 'bad'): ?>
                                    <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                                    <span class="text-yellow-400">Sono ruim</span>
                                <?php else: ?>
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-green-400">Sono bom</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Emocional -->
                            <div class="flex items-center gap-2">
                                <?php if ($lead['emotional'] === 'unstable'): ?>
                                    <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                                    <span class="text-yellow-400">Emocional instável</span>
                                <?php else: ?>
                                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                    <span class="text-green-400">Emocional estável</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Intestino -->
                            <div class="flex items-center gap-2">
                                <?php 
                                $gutColor = $lead['gut'] === 'normal' ? 'green' : 'yellow';
                                $gutText = [
                                    'normal' => 'Intestino normal',
                                    'constipated' => 'Intestino preso',
                                    'loose' => 'Intestino solto'
                                ][$lead['gut']] ?? 'N/A';
                                ?>
                                <span class="w-2 h-2 bg-<?php echo $gutColor; ?>-400 rounded-full"></span>
                                <span class="text-<?php echo $gutColor; ?>-400"><?php echo $gutText; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Produtos do Protocolo -->
                    <?php if (!empty($lead['products'])): ?>
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Protocolo</h5>
                        <div class="space-y-1 text-xs text-gray-300">
                            <?php 
                            $products = explode('; ', $lead['products']);
                            foreach ($products as $product): 
                                if (trim($product)):
                            ?>
                                <div class="flex items-start gap-2">
                                    <span class="text-primary mt-0.5 flex-shrink-0">•</span>
                                    <span class="break-words"><?php echo htmlspecialchars($product); ?></span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Observações -->
                    <?php if (!empty($lead['observations'])): ?>
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Observações</h5>
                        <p class="text-sm text-gray-300 italic break-words">"<?php echo htmlspecialchars($lead['observations']); ?>"</p>
                    </div>
                    <?php endif; ?>

                    <!-- Ações -->
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Ações</h5>
                        
                        <!-- Download PDF Button -->
                        <a href="generate_lead_pdf.php?id=<?php echo $lead['id']; ?>" 
                           target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2 mb-3 text-xs font-medium bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition-colors border border-blue-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Baixar PDF
                        </a>
                        
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-4">Alterar Status</h5>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $statuses = [
                                'orcamento_gerado' => ['label' => 'Orçamento', 'color' => 'primary'],
                                'compra_confirmada' => ['label' => 'Confirmada', 'color' => 'yellow-400'],
                                'produto_comprado' => ['label' => 'Comprado', 'color' => 'green-400'],
                                'recompra' => ['label' => 'Recompra', 'color' => 'secondary']
                            ];
                            
                            foreach ($statuses as $statusKey => $statusInfo):
                                if ($statusKey !== $lead['status']):
                            ?>
                                <button 
                                    @click="updateStatus(<?php echo $lead['id']; ?>, '<?php echo $statusKey; ?>')"
                                    class="px-3 py-2 text-xs font-medium bg-<?php echo $statusInfo['color']; ?>/20 text-<?php echo $statusInfo['color']; ?> rounded-lg hover:bg-<?php echo $statusInfo['color']; ?>/30 transition-colors border border-<?php echo $statusInfo['color']; ?>/20">
                                    <?php echo $statusInfo['label']; ?>
                                </button>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <!-- Archive Button -->
                        <div class="mt-3 pt-3 border-t border-white/10">
                            <button 
                                @click="if(confirm('Tem certeza que deseja arquivar este lead?')) archiveLead(<?php echo $lead['id']; ?>)"
                                class="w-full px-3 py-2 text-xs font-medium bg-gray-500/20 text-gray-400 rounded-lg hover:bg-red-500/20 hover:text-red-400 transition-colors border border-gray-500/20 hover:border-red-500/20 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                                Arquivar Lead
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <script>
      function dashboardApp() {
        return {
          viewMode: 'kanban',
          expandedCard: null,
          init() {
            this.$nextTick(() => {
              const columns = document.querySelectorAll('.sortable-column');
              const isMobile = window.innerWidth < 768;
              
              columns.forEach(col => {
                new Sortable(col, {
                  group: 'kanban',
                  animation: 150,
                  ghostClass: 'opacity-40',
                  handle: isMobile ? '.drag-handle' : null, // Use handle only on mobile
                  onEnd: (evt) => {
                    const leadId = evt.item?.dataset?.leadId;
                    const newStatus = evt.to?.dataset?.status;
                    if (leadId && newStatus) this.updateStatus(leadId, newStatus);
                  }
                });
              });
            });
          },
          async updateStatus(leadId, newStatus) {
            try {
              const form = new FormData();
              form.append('update_status', '1');
              form.append('lead_id', leadId);
              form.append('new_status', newStatus);
              const res = await fetch('admin_dashboard.php', { method: 'POST', body: form });
              if (!res.ok) throw new Error('HTTP ' + res.status);
              setTimeout(() => window.location.reload(), 200);
            } catch (e) {
              alert('Erro ao atualizar status.');
              console.error(e);
            }
          },
          async archiveLead(leadId) {
            try {
              const form = new FormData();
              form.append('archive_lead', '1');
              form.append('lead_id', leadId);
              form.append('archived', '1');
              const res = await fetch('admin_dashboard.php', { method: 'POST', body: form });
              if (!res.ok) throw new Error('HTTP ' + res.status);
              const data = await res.json();
              if (data.success) {
                setTimeout(() => window.location.reload(), 200);
              } else {
                alert('Erro ao arquivar lead: ' + (data.error || 'Desconhecido'));
              }
            } catch (e) {
              alert('Erro ao arquivar lead.');
              console.error(e);
            }
          }
        }
      }
    </script>

    <?php include 'includes/footer.php'; ?>
