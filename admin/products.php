<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

require_once '../db.php';

// Buscar todos os produtos com suas regras
$stmt = $pdo->query("
    SELECT p.*, 
           COUNT(DISTINCT pr.id) as rule_count,
           COUNT(DISTINCT pa.id) as alert_count
    FROM products p
    LEFT JOIN product_rules pr ON p.id = pr.product_id
    LEFT JOIN product_alerts pa ON p.id = pa.product_id
    GROUP BY p.id
    ORDER BY p.is_base DESC, p.name ASC
");
$products = $stmt->fetchAll();

// Estatísticas
$totalProducts = count($products);
$activeProducts = count(array_filter($products, fn($p) => $p['is_active'] == 1));
$baseProducts = count(array_filter($products, fn($p) => $p['is_base'] == 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - Provitta Life</title>
    <link rel="icon" href="../assets/src/favicon.icon" type="image/x-icon">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
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
                <img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 w-auto">
                <h1 class="text-2xl font-bold text-white">Gerenciar Produtos</h1>
            </div>
            <div class="flex items-center gap-4">
                <a href="admin_dashboard.php" class="px-4 py-2 bg-surface/80 hover:bg-surface rounded-lg transition-all text-gray-300 hover:text-white flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="admin_logout.php" class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 rounded-lg transition-all text-red-300 hover:text-red-200">
                    Sair
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 container mx-auto px-6 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total de Produtos</p>
                        <p class="text-3xl font-bold text-white"><?php echo $totalProducts; ?></p>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Produtos Ativos</p>
                        <p class="text-3xl font-bold text-green-400"><?php echo $activeProducts; ?></p>
                    </div>
                    <div class="p-3 bg-green-500/10 rounded-xl">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Produtos Base</p>
                        <p class="text-3xl font-bold text-secondary"><?php echo $baseProducts; ?></p>
                    </div>
                    <div class="p-3 bg-secondary/10 rounded-xl">
                        <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <input type="text" id="searchInput" placeholder="Buscar produtos..." 
                    class="px-4 py-2 bg-surface/80 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary transition-all"
                    onkeyup="filterProducts()">
                <select id="filterStatus" onchange="filterProducts()" 
                    class="px-4 py-2 bg-surface/80 border border-white/10 rounded-lg text-white focus:outline-none focus:border-primary transition-all">
                    <option value="all">Todos</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                    <option value="base">Base</option>
                </select>
            </div>
            <a href="product_add.php" class="px-6 py-3 bg-primary hover:bg-secondary text-background font-bold rounded-lg transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Adicionar Produto
            </a>
        </div>

        <!-- Products Table -->
        <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden">
            <table class="w-full" id="productsTable">
                <thead class="bg-black/30 border-b border-white/10">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Produto</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Instrução</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Preço</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Regras</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($products as $product): ?>
                    <tr class="product-row hover:bg-white/5 transition-colors" 
                        data-name="<?php echo strtolower($product['name']); ?>"
                        data-status="<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>"
                        data-base="<?php echo $product['is_base'] ? 'base' : 'conditional'; ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <?php if ($product['is_base']): ?>
                                <span class="flex-shrink-0 w-2 h-2 bg-secondary rounded-full" title="Produto Base"></span>
                                <?php endif; ?>
                                <span class="font-semibold text-white"><?php echo htmlspecialchars($product['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-sm"><?php echo htmlspecialchars($product['usage_instruction']); ?></td>
                        <td class="px-6 py-4 text-primary font-mono font-semibold">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($product['is_base']): ?>
                            <span class="px-3 py-1 bg-secondary/20 text-secondary rounded-full text-xs font-semibold">Base</span>
                            <?php else: ?>
                            <span class="px-3 py-1 bg-primary/20 text-primary rounded-full text-xs font-semibold">Condicional</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="product_rules.php?id=<?php echo $product['id']; ?>" class="text-gray-400 hover:text-primary transition-colors">
                                <?php echo $product['rule_count']; ?> regra(s)
                            </a>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="toggleStatus(<?php echo $product['id']; ?>, <?php echo $product['is_active']; ?>)" 
                                class="px-3 py-1 rounded-full text-xs font-semibold transition-all <?php echo $product['is_active'] ? 'bg-green-500/20 text-green-400 hover:bg-green-500/30' : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'; ?>">
                                <?php echo $product['is_active'] ? 'Ativo' : 'Inativo'; ?>
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="product_edit.php?id=<?php echo $product['id']; ?>" 
                                    class="px-3 py-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-lg transition-all flex items-center gap-2 text-sm font-semibold" title="Editar produto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar
                                </a>
                                <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', <?php echo $product['is_base']; ?>)" 
                                    class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-all flex items-center gap-2 text-sm font-semibold" title="Deletar produto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Deletar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-surface border border-white/10 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 bg-red-500/20 rounded-xl">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                    <p class="text-sm text-gray-400">Esta ação não pode ser desfeita</p>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-gray-300 mb-2">Você está prestes a deletar o produto:</p>
                <p class="text-lg font-bold text-white bg-background/50 p-3 rounded-lg" id="productNameToDelete"></p>
                <p class="text-sm text-gray-400 mt-3">
                    ⚠️ Isso irá remover permanentemente:
                </p>
                <ul class="text-sm text-gray-400 mt-2 space-y-1 ml-4">
                    <li>• O produto</li>
                    <li>• Todas as regras associadas</li>
                    <li>• Todos os alertas</li>
                    <li>• A imagem do produto (se houver)</li>
                </ul>
            </div>

            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-3 bg-surface/80 hover:bg-surface rounded-lg transition-all text-gray-300 hover:text-white font-semibold">
                    Cancelar
                </button>
                <button onclick="executeDelete()" class="flex-1 px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-all font-bold shadow-lg">
                    Sim, Deletar
                </button>
            </div>
        </div>
    </div>

    <script>
        let productToDelete = null;

        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filterStatus = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('.product-row');

            rows.forEach(row => {
                const name = row.dataset.name;
                const status = row.dataset.status;
                const base = row.dataset.base;

                let matchSearch = name.includes(searchTerm);
                let matchFilter = true;

                if (filterStatus === 'active') matchFilter = status === 'active';
                else if (filterStatus === 'inactive') matchFilter = status === 'inactive';
                else if (filterStatus === 'base') matchFilter = base === 'base';

                row.style.display = (matchSearch && matchFilter) ? '' : 'none';
            });
        }

        function toggleStatus(productId, currentStatus) {
            if (confirm('Deseja alterar o status deste produto?')) {
                fetch('product_toggle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${productId}&current_status=${currentStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao alterar status');
                    }
                });
            }
        }

        function confirmDelete(productId, productName, isBase) {
            if (isBase == 1) {
                alert('❌ Produtos base não podem ser deletados!\n\nEstes produtos são obrigatórios no sistema.');
                return;
            }

            productToDelete = productId;
            document.getElementById('productNameToDelete').textContent = productName;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            productToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function executeDelete() {
            if (!productToDelete) return;

            const deleteButton = event.target;
            deleteButton.disabled = true;
            deleteButton.textContent = 'Deletando...';

            fetch('product_delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productToDelete}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDeleteModal();
                    location.reload();
                } else {
                    alert('❌ Erro ao deletar produto:\n\n' + (data.message || 'Erro desconhecido'));
                    deleteButton.disabled = false;
                    deleteButton.textContent = 'Sim, Deletar';
                }
            })
            .catch(error => {
                alert('❌ Erro de conexão:\n\n' + error.message);
                deleteButton.disabled = false;
                deleteButton.textContent = 'Sim, Deletar';
            });
        }

        // Fechar modal ao clicar fora
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>

</body>
</html>
