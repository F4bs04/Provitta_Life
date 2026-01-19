<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

require_once 'db.php';

// Fetch leads
$stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC");
$leads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Provitta Life</title>
    <link href="./assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Geologica:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Geologica', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-background bg-brand-gradient min-h-screen" x-data="{ selectedLead: null }">
    <nav class="border-b border-white/5 bg-black/20 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 w-auto">
                    <span class="text-xs text-gray-500 font-bold uppercase tracking-widest ml-2">Admin</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-400">Olá, <?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
                    <a href="admin_logout.php" class="text-sm text-red-400 hover:text-red-300">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">Leads e Protocolos</h1>
                <p class="text-gray-400">Gerencie os contatos e avaliações geradas.</p>
            </div>
        </div>

        <div class="bg-[#1F2833]/30 border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-black/20 text-xs uppercase tracking-wider text-gray-400">
                            <th class="px-6 py-4 font-medium">Data</th>
                            <th class="px-6 py-4 font-medium">Nome</th>
                            <th class="px-6 py-4 font-medium">E-mail</th>
                            <th class="px-6 py-4 font-medium">CPF</th>
                            <th class="px-6 py-4 font-medium">Total</th>
                            <th class="px-6 py-4 font-medium">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($leads as $lead): 
                            // Fetch items for this lead
                            $stmtItems = $pdo->prepare("SELECT * FROM protocol_items WHERE lead_id = ?");
                            $stmtItems->execute([$lead['id']]);
                            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                            $leadJson = json_encode(array_merge($lead, ['items' => $items]));
                        ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 text-sm"><?php echo date('d/m/Y H:i', strtotime($lead['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-white"><?php echo htmlspecialchars($lead['name']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($lead['email']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($lead['cpf']); ?></td>
                            <td class="px-6 py-4 text-sm text-[#66FCF1]">R$ <?php echo number_format($lead['total_price'], 2, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <button @click='selectedLead = <?php echo htmlspecialchars($leadJson, ENT_QUOTES, 'UTF-8'); ?>' class="text-[#66FCF1] hover:underline">Ver Detalhes</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">Nenhum lead encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Details Modal -->
    <div x-show="selectedLead" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        
        <div class="bg-[#1F2833] border border-white/10 rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl"
             @click.away="selectedLead = null">
            
            <div class="p-8 border-b border-white/5 flex justify-between items-center sticky top-0 bg-[#1F2833] z-10">
                <h2 class="text-2xl font-bold text-white">Detalhes do Protocolo</h2>
                <button @click="selectedLead = null" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-8 space-y-8">
                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="text-xs uppercase tracking-widest text-gray-500 font-bold">Nome</label>
                        <p class="text-white text-lg" x-text="selectedLead?.name"></p>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-widest text-gray-500 font-bold">CPF</label>
                        <p class="text-white text-lg" x-text="selectedLead?.cpf"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-xs uppercase tracking-widest text-gray-500 font-bold">E-mail</label>
                        <p class="text-white text-lg" x-text="selectedLead?.email"></p>
                    </div>
                </div>

                <!-- Anamnese -->
                <div class="bg-black/20 rounded-2xl p-6 space-y-4">
                    <h3 class="text-sm font-bold text-[#66FCF1] uppercase tracking-widest">Resumo da Anamnese</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Dores:</span>
                            <span class="text-white" x-text="selectedLead?.pain === 'yes' ? 'Sim' : 'Não'"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Pressão Alta:</span>
                            <span class="text-white" x-text="selectedLead?.pressure === 'yes' ? 'Sim' : 'Não'"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Diabetes:</span>
                            <span class="text-white" x-text="selectedLead?.diabetes === 'yes' ? 'Sim' : 'Não'"></span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-gray-400">Sono:</span>
                            <span class="text-white" x-text="selectedLead?.sleep === 'good' ? 'Bom' : 'Ruim'"></span>
                        </div>
                    </div>
                    <div class="pt-2">
                        <span class="text-gray-400 text-sm">Observações:</span>
                        <p class="text-white text-sm mt-1 italic" x-text="selectedLead?.observations || 'Nenhuma observação.'"></p>
                    </div>
                </div>

                <!-- Protocol Items -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-[#66FCF1] uppercase tracking-widest">Itens do Protocolo</h3>
                    <template x-for="item in selectedLead?.items" :key="item.id">
                        <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl border border-white/5">
                            <div>
                                <p class="text-white font-bold" x-text="item.product_name"></p>
                                <p class="text-xs text-gray-400" x-text="item.usage_instruction"></p>
                            </div>
                            <p class="text-[#66FCF1] font-mono" x-text="'R$ ' + parseFloat(item.price).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></p>
                        </div>
                    </template>
                </div>

                <!-- Total -->
                <div class="pt-6 border-t border-white/10 flex justify-between items-center">
                    <span class="text-xl font-bold text-white">Total do Kit</span>
                    <span class="text-3xl font-bold text-[#66FCF1]" x-text="'R$ ' + parseFloat(selectedLead?.total_price).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
