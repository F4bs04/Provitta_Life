<?php
session_start();
require_once '../db.php';

$page_title = 'Analytics - Provitta Life';

// Calcular estatísticas
try {
    // Total de Leads
    $stmt = $pdo->query("SELECT COUNT(*) FROM leads");
    $total_leads = $stmt->fetchColumn();
    
    // Total de Orçamento (soma dos preços dos produtos nos protocolos gerados)
    $total_budget = $total_leads * 150; // Exemplo: R$ 150 por lead (simulado)
    
    // Ticket Médio
    $avg_ticket = $total_leads > 0 ? $total_budget / $total_leads : 0;
    
    // Leads por Status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
    $leads_by_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Leads por Dia (últimos 7 dias)
    $stmt = $pdo->query("
        SELECT date(created_at) as date, COUNT(*) as count 
        FROM leads 
        WHERE created_at >= date('now', '-7 days') 
        GROUP BY date(created_at)
        ORDER BY date ASC
    ");
    $leads_by_day = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Produtos mais usados (Top 5)
    $stmt = $pdo->query("
        SELECT p.name, COUNT(*) as usage_count
        FROM products p
        JOIN product_rules pr ON p.id = pr.product_id
        GROUP BY p.id, p.name
        ORDER BY usage_count DESC
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Erro ao carregar estatísticas: " . $e->getMessage();
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-6 lg:p-8" x-data="analyticsApp()">
        
        <!-- Top Bar -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-white">Analytics</h1>
            <div class="text-sm text-gray-400">Última atualização: <?php echo date('H:i'); ?></div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Leads -->
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Total de Cadastros</h3>
                    <div class="p-2 bg-primary/10 rounded-lg text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white"><?php echo number_format($total_leads, 0, ',', '.'); ?></p>
                <p class="text-xs text-green-400 mt-2 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    +12% vs mês anterior
                </p>
            </div>

            <!-- Total Budget -->
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Orçamento Estimado</h3>
                    <div class="p-2 bg-green-400/10 rounded-lg text-green-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white">R$ <?php echo number_format($total_budget, 2, ',', '.'); ?></p>
                <p class="text-xs text-green-400 mt-2 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    +5% vs mês anterior
                </p>
            </div>

            <!-- Average Ticket -->
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-gray-400 text-sm font-bold uppercase tracking-wider">Ticket Médio</h3>
                    <div class="p-2 bg-purple-400/10 rounded-lg text-purple-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white">R$ <?php echo number_format($avg_ticket, 2, ',', '.'); ?></p>
                <p class="text-xs text-gray-400 mt-2">Por cadastro</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <!-- Leads Chart -->
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6 xl:col-span-2">
                <h3 class="text-lg font-bold text-white mb-6">Cadastros nos Últimos 7 Dias</h3>
                <canvas id="leadsChart" height="150"></canvas>
            </div>

            <!-- Status Chart -->
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-white mb-6">Distribuição por Status</h3>
                <div class="h-[150px] flex justify-center">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-white mb-6">Produtos Mais Recomendados</h3>
            <div class="space-y-3">
                <?php if (!empty($top_products)): ?>
                    <?php foreach ($top_products as $index => $product): ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div>
                                    <p class="text-white font-medium"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo $product['usage_count']; ?> recomendações</p>
                                </div>
                            </div>
                            <div class="text-primary font-bold">
                                <?php echo number_format(($product['usage_count'] / max(array_sum(array_column($top_products, 'usage_count')), 1)) * 100, 1); ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-400 text-center py-8">Nenhum dado disponível ainda.</p>
                <?php endif; ?>
            </div>
        </div>

    <?php include 'includes/footer.php'; ?>
