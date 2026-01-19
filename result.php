<?php
session_start();

if (!isset($_SESSION['protocol'])) {
    header('Location: index.php');
    exit;
}

$protocol = $_SESSION['protocol'];
$total = $_SESSION['total'];
$alerts = $_SESSION['alerts'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Seu protocolo personalizado Provitta Life está pronto.">
    <meta name="author" content="Fabian Araújo">
    <title>Seu Protocolo - Provitta Life</title>
    <link rel="icon" href="./assets/src/favicon.icon" type="image/x-icon">
    <link href="./assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen py-10 px-4 bg-fixed">

    <!-- Background Effects -->
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="absolute top-[-20%] left-1/2 transform -translate-x-1/2 w-[800px] h-[800px] bg-primary/10 rounded-full blur-[120px] opacity-50"></div>
    </div>

    <div class="relative z-10 max-w-3xl w-full mx-auto">
        
        <!-- Card -->
        <div class="bg-surface/60 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl overflow-hidden">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-surface to-background p-8 text-center border-b border-white/5 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-secondary"></div>
                
                <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-20 w-auto mx-auto mb-6 drop-shadow-lg">
                <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">Protocolo Personalizado</h1>
                <p class="text-gray-400">Gerado com sucesso para o seu metabolismo.</p>
            </div>

            <!-- Alerts -->
            <?php if (!empty($alerts)): ?>
            <div class="mx-8 mt-8 bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-4 flex items-start gap-4">
                <svg class="h-6 w-6 text-yellow-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm text-yellow-200">
                    <?php foreach ($alerts as $alert): ?>
                        <span class="block"><?php echo htmlspecialchars($alert); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Protocol List -->
            <div class="p-8">
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Sua Lista de Suplementação
                </h2>
                
                <div class="space-y-4">
                    <?php foreach ($protocol as $item): ?>
                    <div class="group flex items-center justify-between p-5 bg-background/50 border border-white/5 rounded-xl hover:border-primary/30 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="h-12 w-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-lg"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($item['usage']); ?></p>
                            </div>
                        </div>
                        <div class="font-mono font-semibold text-primary text-lg">
                            R$ <?php echo number_format($item['price'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total -->
                <div class="mt-10 pt-8 border-t border-white/10 flex justify-between items-end">
                    <span class="text-gray-400 mb-1">Investimento Total Estimado</span>
                    <span class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">
                        R$ <?php echo number_format($total, 2, ',', '.'); ?>
                    </span>
                </div>

                <!-- Actions -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button class="flex items-center justify-center px-6 py-4 border border-white/10 bg-white/5 rounded-xl text-gray-300 hover:bg-white/10 hover:text-white transition font-medium">
                        📲 Enviar
                    </button>
                    <a href="generate_pdf.php" target="_blank" class="flex items-center justify-center px-6 py-4 border border-white/10 bg-white/5 rounded-xl text-gray-300 hover:bg-white/10 hover:text-white transition font-medium">
                        📄 Baixar PDF
                    </a>
                    <a href="index.php" class="flex items-center justify-center px-6 py-4 bg-primary text-background rounded-xl hover:bg-secondary hover:text-white font-bold shadow-lg shadow-primary/20 transition">
                        🔄 Refazer
                    </a>
                </div>

            </div>
        </div>
        
        <p class="mt-8 text-center text-gray-600 text-sm max-w-md mx-auto">
            * Sugestão de suplementação baseada nas suas respostas. Consulte sempre um profissional de saúde.
        </p>

    </div>

</body>
</html>
