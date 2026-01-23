<div class="lead-card bg-surface/90 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden hover:shadow-lg hover:shadow-primary/5 transition-all cursor-move"
     data-lead-id="<?php echo $lead['id']; ?>"
     x-data="{ expanded: false }">
    
    <!-- Card Header (sempre visível) -->
    <div class="p-4 cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
                <h4 class="font-bold text-white text-lg"><?php echo htmlspecialchars($lead['name'] ?? 'Sem nome'); ?></h4>
                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($lead['email'] ?? 'Sem email'); ?></p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        
        <div class="flex items-center gap-2 text-xs">
            <span class="px-2 py-1 bg-primary/20 text-primary rounded-lg font-medium">
                R$ <?php echo number_format($lead['total_price'], 2, ',', '.'); ?>
            </span>
            <span class="text-gray-500">
                <?php echo date('d/m/Y', strtotime($lead['created_at'])); ?>
            </span>
        </div>
    </div>

    <!-- Card Body (expansível) -->
    <div x-show="expanded" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="border-t border-white/10 p-4 space-y-4">
        
        <!-- Informações Pessoais -->
        <div>
            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Informações</h5>
            <div class="space-y-1 text-sm">
                <p class="text-gray-300"><span class="text-gray-500">CPF:</span> <?php echo htmlspecialchars($lead['cpf'] ?? 'Não informado'); ?></p>
                <p class="text-gray-300"><span class="text-gray-500">Sessão:</span> <?php echo substr($lead['session_id'], 0, 12); ?>...</p>
            </div>
        </div>

        <!-- Anamnese -->
        <div>
            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Anamnese</h5>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="flex items-center gap-2">
                    <?php if ($lead['pain'] === 'yes'): ?>
                        <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                        <span class="text-red-400">Dores</span>
                    <?php else: ?>
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        <span class="text-green-400">Sem dores</span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-2">
                    <?php if ($lead['pressure'] === 'yes'): ?>
                        <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                        <span class="text-red-400">Pressão alta</span>
                    <?php else: ?>
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        <span class="text-green-400">Pressão OK</span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-2">
                    <?php if ($lead['diabetes'] === 'yes'): ?>
                        <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                        <span class="text-red-400">Diabetes</span>
                    <?php else: ?>
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        <span class="text-green-400">Sem diabetes</span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-2">
                    <?php if ($lead['sleep'] === 'bad'): ?>
                        <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                        <span class="text-yellow-400">Sono ruim</span>
                    <?php else: ?>
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        <span class="text-green-400">Sono bom</span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-2">
                    <?php if ($lead['emotional'] === 'unstable'): ?>
                        <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                        <span class="text-yellow-400">Emocional instável</span>
                    <?php else: ?>
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        <span class="text-green-400">Emocional estável</span>
                    <?php endif; ?>
                </div>
                
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
                        <span class="text-primary mt-0.5">•</span>
                        <span><?php echo htmlspecialchars($product); ?></span>
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
            <p class="text-sm text-gray-300 italic">"<?php echo htmlspecialchars($lead['observations']); ?>"</p>
        </div>
        <?php endif; ?>

        <!-- Ações: Mover para outro status -->
        <div>
            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Mover para</h5>
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
                        @click="$root.updateStatus(<?php echo $lead['id']; ?>, '<?php echo $statusKey; ?>')"
                        class="px-3 py-1 text-xs bg-<?php echo $statusInfo['color']; ?>/20 text-<?php echo $statusInfo['color']; ?> rounded-lg hover:bg-<?php echo $statusInfo['color']; ?>/30 transition-colors">
                        <?php echo $statusInfo['label']; ?>
                    </button>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
</div>
