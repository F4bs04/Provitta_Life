<?php
session_start();
require_once '../db.php';

$page_title = 'Códigos de Convite - Provitta Life';
include 'includes/header.php';

$success = '';
$error = '';
$userRole = $_SESSION['admin_role'] ?? 'consultant';
$userId = $_SESSION['admin_user_id'];

// Se for assinante, verificar limite de convites
if ($userRole === 'subscriber') {
    $stmtLimit = $pdo->prepare("SELECT invite_limit, (SELECT COUNT(*) FROM invite_codes WHERE created_by = ?) as generated_count FROM users WHERE id = ?");
    $stmtLimit->execute([$userId, $userId]);
    $limitInfo = $stmtLimit->fetch();
    $remainingInvites = max(0, $limitInfo['invite_limit'] - $limitInfo['generated_count']);
}

// Gerar novo código de convite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
    $expires_days = intval($_POST['expires_days'] ?? 30);
    $max_uses = intval($_POST['max_uses'] ?? 1);
    $max_uses = max(1, min(5, $max_uses));
    $guest_name = trim($_POST['guest_name'] ?? '');
    $is_exceptional = isset($_POST['is_exceptional']) ? 1 : 0;
    
    $expires_at = $expires_days > 0 ? date('Y-m-d H:i:s', strtotime("+$expires_days days")) : null;
    
    // Gerar código único
    $random_part = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    if (!empty($guest_name)) {
        // Limpar o nome para usar no código (apenas letras e números)
        $clean_name = preg_replace('/[^A-Za-z0-9]/', '', $guest_name);
        $prefix = strtoupper(substr($clean_name, 0, 8));
        $code = $prefix . '-' . $random_part;
    } else {
        $code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        $code = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
    }
    
    try {
        // Validação de limite para assinantes
        if ($userRole === 'subscriber' && $remainingInvites <= 0) {
            throw new Exception("Você atingiu o seu limite de convites ($limitInfo[invite_limit]). Entre em contato com o administrador.");
        }

        // Assinantes só podem gerar convites de 1 uso (conforme regra de negócio)
        if ($userRole === 'subscriber') {
            $max_uses = 1;
        }

        $stmt = $pdo->prepare("INSERT INTO invite_codes (code, created_by, expires_at, max_uses, is_exceptional, guest_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $_SESSION['admin_user_id'], $expires_at, $max_uses, $is_exceptional, $guest_name]);
        $msg_exceptional = $is_exceptional ? " (Excepcional - Sem limites)" : " (Válido para $max_uses usos)";
        $success = "Código gerado com sucesso: <strong>$code</strong>" . $msg_exceptional;
        
        // Atualizar contagem restante se for assinante
        if ($userRole === 'subscriber') {
            $remainingInvites--;
        }
    } catch (Exception $e) {
        $error = "Erro ao gerar código: " . $e->getMessage();
    }
}

// Revogar código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_code'])) {
    $code_id = intval($_POST['code_id']);
    $stmt = $pdo->prepare("UPDATE invite_codes SET is_active = 0 WHERE id = ?");
    $stmt->execute([$code_id]);
    $success = "Código revogado com sucesso!";
}

// Buscar todos os códigos (Filtrar se não for master)
$queryCodes = "
    SELECT ic.*, 
           u1.name as created_by_name
    FROM invite_codes ic
    LEFT JOIN users u1 ON ic.created_by = u1.id
";

if ($userRole !== 'master') {
    $queryCodes .= " WHERE ic.created_by = :user_id ";
}

$queryCodes .= " ORDER BY ic.created_at DESC ";

$stmt = $pdo->prepare($queryCodes);
if ($userRole !== 'master') {
    $stmt->bindValue(':user_id', $userId);
}
$stmt->execute();
$codes = $stmt->fetchAll();

// Organizar códigos por status
$active_codes = array_filter($codes, function($c) { 
    return $c['is_active'] && $c['usage_count'] < $c['max_uses'] && (!$c['expires_at'] || strtotime($c['expires_at']) > time()); 
});
$used_codes = array_filter($codes, function($c) { return $c['usage_count'] >= $c['max_uses']; });
$expired_codes = array_filter($codes, function($c) { 
    return $c['is_active'] && $c['usage_count'] < $c['max_uses'] && $c['expires_at'] && strtotime($c['expires_at']) <= time(); 
});
$revoked_codes = array_filter($codes, function($c) { return !$c['is_active']; });
?>

<main class="flex-1 p-4 md:p-6 lg:p-8" x-data="{ tab: 'active' }">
        
        <!-- Top Bar (Desktop) -->
        <div class="hidden lg:flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-white">Códigos de Convite</h1>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-2xl">
            <p class="text-green-400 text-sm"><?php echo $success; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl">
            <p class="text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <!-- Generate New Code -->
        <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <svg class="w-7 h-7 text-primary" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Gerar Novo Código de Convite
                <?php if ($userRole === 'subscriber'): ?>
                    <span class="text-xs font-normal text-primary bg-primary/10 px-3 py-1 rounded-full uppercase tracking-widest">
                        Restantes: <?php echo $remainingInvites; ?> / <?php echo $limitInfo['invite_limit']; ?>
                    </span>
                <?php endif; ?>
            </h2>
            
            <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2">Nome do Convidado</label>
                    <input type="text" name="guest_name" placeholder="Ex: João Silva" 
                           class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    <p class="text-xs text-gray-500 mt-1">Opcional: será usado no código</p>
                </div>

                <div class="w-32">
                    <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2">Validade (dias)</label>
                    <input type="number" name="expires_days" value="30" min="0" max="365" 
                           class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white focus:ring-2 focus:ring-primary outline-none transition-all">
                    <p class="text-xs text-gray-500 mt-1">0 = sem expiração</p>
                </div>

                <div class="flex-1">
                    <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2">Limite de Usos</label>
                    <select name="max_uses" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white focus:ring-2 focus:ring-primary outline-none transition-all">
                        <option value="1">1 uso</option>
                        <option value="2">2 usos</option>
                        <option value="3">3 usos</option>
                        <option value="4">4 usos</option>
                        <option value="5">5 usos</option>
                    </select>
                </div>
                
                <div class="flex-1">
                    <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2">Opções</label>
                    <label class="flex items-center gap-3 cursor-pointer group h-[50px] border border-transparent">
                        <div class="relative">
                            <input type="checkbox" name="is_exceptional" class="sr-only peer">
                            <div class="w-12 h-6 bg-background/50 border border-white/10 rounded-full peer peer-checked:bg-primary transition-all"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-gray-400 rounded-full peer-checked:translate-x-6 peer-checked:bg-background transition-all"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Código Excepcional (Sem limites)</span>
                    </label>
                </div>
                
                <button type="submit" name="generate_code" 
                        class="px-8 py-3 bg-primary text-background font-bold rounded-xl hover:bg-secondary hover:shadow-[0_0_30px_rgba(102,252,241,0.4)] transition-all">
                    Gerar Código
                </button>
            </form>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-primary/10 border border-primary/20 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wider">Ativos</p>
                <p class="text-2xl font-bold text-primary mt-1"><?php echo count($active_codes); ?></p>
            </div>
            <div class="bg-green-400/10 border border-green-400/20 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wider">Usados</p>
                <p class="text-2xl font-bold text-green-400 mt-1"><?php echo count($used_codes); ?></p>
            </div>
            <div class="bg-yellow-400/10 border border-yellow-400/20 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wider">Expirados</p>
                <p class="text-2xl font-bold text-yellow-400 mt-1"><?php echo count($expired_codes); ?></p>
            </div>
            <div class="bg-red-400/10 border border-red-400/20 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wider">Revogados</p>
                <p class="text-2xl font-bold text-red-400 mt-1"><?php echo count($revoked_codes); ?></p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 bg-surface/50 rounded-xl p-1 border border-white/10">
            <button @click="tab = 'active'" :class="tab === 'active' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'" 
                    class="flex-1 px-4 py-3 rounded-lg transition-all font-medium text-sm">
                Ativos (<?php echo count($active_codes); ?>)
            </button>
            <button @click="tab = 'used'" :class="tab === 'used' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'" 
                    class="flex-1 px-4 py-3 rounded-lg transition-all font-medium text-sm">
                Usados (<?php echo count($used_codes); ?>)
            </button>
            <button @click="tab = 'expired'" :class="tab === 'expired' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'" 
                    class="flex-1 px-4 py-3 rounded-lg transition-all font-medium text-sm">
                Expirados (<?php echo count($expired_codes); ?>)
            </button>
            <button @click="tab = 'revoked'" :class="tab === 'revoked' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'" 
                    class="flex-1 px-4 py-3 rounded-lg transition-all font-medium text-sm">
                Revogados (<?php echo count($revoked_codes); ?>)
            </button>
        </div>

        <!-- Active Codes -->
        <div x-show="tab === 'active'" x-cloak class="space-y-3">
            <?php if (empty($active_codes)): ?>
                <p class="text-gray-400 text-center py-8">Nenhum código ativo</p>
            <?php else: ?>
                <?php foreach ($active_codes as $code): ?>
                <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <code class="text-lg font-mono font-bold text-primary bg-primary/10 px-3 py-1 rounded-lg"><?php echo htmlspecialchars($code['code']); ?></code>
                            <span class="px-2 py-1 bg-green-400/20 text-green-400 text-xs font-medium rounded-full">Ativo</span>
                            <span class="px-2 py-1 bg-blue-400/20 text-blue-400 text-xs font-medium rounded-full">
                                <?php echo $code['usage_count'] . '/' . $code['max_uses']; ?> usos
                            </span>
                            <?php if ($code['is_exceptional']): ?>
                            <span class="px-2 py-1 bg-purple-400/20 text-purple-400 text-xs font-medium rounded-full">Excepcional</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 space-y-1">
                            <?php if ($code['guest_name']): ?>
                            <p>Convidado: <span class="text-white font-bold"><?php echo htmlspecialchars($code['guest_name']); ?></span></p>
                            <?php endif; ?>
                            <p>Criado por: <span class="text-gray-300"><?php echo htmlspecialchars($code['created_by_name']); ?></span></p>
                            <p>Criado em: <?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?></p>
                            <?php if ($code['expires_at']): ?>
                            <p>Expira em: <?php echo date('d/m/Y H:i', strtotime($code['expires_at'])); ?></p>
                            <?php else: ?>
                            <p>Sem expiração</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja revogar este código?')">
                        <input type="hidden" name="code_id" value="<?php echo $code['id']; ?>">
                        <button type="submit" name="revoke_code" 
                                class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors text-sm font-medium">
                            Revogar
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Used Codes -->
        <div x-show="tab === 'used'" x-cloak class="space-y-3">
            <?php if (empty($used_codes)): ?>
                <p class="text-gray-400 text-center py-8">Nenhum código usado</p>
            <?php else: ?>
                <?php foreach ($used_codes as $code): ?>
                <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <code class="text-lg font-mono font-bold text-gray-400 bg-gray-400/10 px-3 py-1 rounded-lg"><?php echo htmlspecialchars($code['code']); ?></code>
                        <span class="px-2 py-1 bg-gray-400/20 text-gray-400 text-xs font-medium rounded-full">Usado</span>
                    </div>
                    <div class="text-xs text-gray-400 space-y-1">
                        <?php if ($code['guest_name']): ?>
                        <p>Convidado: <span class="text-white font-bold"><?php echo htmlspecialchars($code['guest_name']); ?></span></p>
                        <?php endif; ?>
                        <p>Criado por: <span class="text-gray-300"><?php echo htmlspecialchars($code['created_by_name']); ?></span></p>
                        <p>Usado por: <span class="text-green-400"><?php echo htmlspecialchars($code['used_by_name'] ?? 'Usuário Convidado'); ?></span></p>
                        <p>Usado em: <?php echo date('d/m/Y H:i', strtotime($code['used_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Expired Codes -->
        <div x-show="tab === 'expired'" x-cloak class="space-y-3">
            <?php if (empty($expired_codes)): ?>
                <p class="text-gray-400 text-center py-8">Nenhum código expirado</p>
            <?php else: ?>
                <?php foreach ($expired_codes as $code): ?>
                <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <code class="text-lg font-mono font-bold text-yellow-400 bg-yellow-400/10 px-3 py-1 rounded-lg"><?php echo htmlspecialchars($code['code']); ?></code>
                        <span class="px-2 py-1 bg-yellow-400/20 text-yellow-400 text-xs font-medium rounded-full">Expirado</span>
                    </div>
                    <div class="text-xs text-gray-400 space-y-1">
                        <p>Criado em: <?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?></p>
                        <p>Expirou em: <?php echo date('d/m/Y H:i', strtotime($code['expires_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Revoked Codes -->
        <div x-show="tab === 'revoked'" x-cloak class="space-y-3">
            <?php if (empty($revoked_codes)): ?>
                <p class="text-gray-400 text-center py-8">Nenhum código revogado</p>
            <?php else: ?>
                <?php foreach ($revoked_codes as $code): ?>
                <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <code class="text-lg font-mono font-bold text-red-400 bg-red-400/10 px-3 py-1 rounded-lg"><?php echo htmlspecialchars($code['code']); ?></code>
                        <span class="px-2 py-1 bg-red-400/20 text-red-400 text-xs font-medium rounded-full">Revogado</span>
                    </div>
                    <div class="text-xs text-gray-400 space-y-1">
                        <p>Criado em: <?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php include 'includes/footer.php'; ?>
