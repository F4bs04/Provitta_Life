<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Provitta Life - Sistema de avaliação metabólica e geração de protocolos de suplementação personalizados.">
    <meta name="keywords" content="suplementação, metabolismo, saúde, protocolo personalizado, provitta life">
    <meta name="author" content="Fabian Araújo">
    <title>Provitta Life - Protocolo Personalizado</title>
    <link rel="icon" href="./assets/src/favicon.icon" type="image/x-icon">
    <link href="./assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased overflow-x-hidden min-h-screen bg-fixed">

    <!-- Dot Grid Background -->
    <div id="dot-grid" class="dot-grid"></div>
    <script src="assets/js/background.js"></script>

    <!-- Hero Section -->
    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4">
        
        <div class="max-w-2xl w-full text-center space-y-8">
            
            <!-- Logo Area -->
            <div class="relative inline-block">
                <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="relative h-16 md:h-20 w-auto mx-auto drop-shadow-2xl">
            </div>
            
            <h1 class="text-4xl md:text-6xl font-bold tracking-tight">
                A tecnologia cuida do <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">protocolo</span>.
                <br>
                <span class="text-2xl md:text-3xl font-light text-gray-400 mt-2 block">Você cuida de viver melhor.</span>
            </h1>
            
            <p class="text-lg text-gray-400 max-w-lg mx-auto leading-relaxed">
                Algoritmo avançado de análise metabólica para gerar sua suplementação ideal em segundos.
            </p>
            
            <div class="pt-8">
                <a href="form.php" onclick="document.getElementById('loader-overlay').classList.remove('hidden')" class="group relative inline-flex items-center justify-center px-8 py-4 font-bold text-background transition-all duration-200 bg-primary font-lg rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary hover:bg-secondary hover:text-white hover:shadow-[0_0_30px_rgba(102,252,241,0.5)]">
                    <span class="mr-2">INICIAR AVALIAÇÃO</span>
                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </a>
            </div>
            
            <p class="text-xs text-gray-600 mt-8">
                Tecnologia segura • LGPD Compliant • Baseado em evidências
            </p>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div id="loader-overlay" class="fixed inset-0 z-50 bg-background/90 backdrop-blur-sm flex items-center justify-center hidden">
        <div class="flex flex-col items-center gap-4">
            <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-12 w-auto mb-2">
            <div class="loader"></div>
            <div class="text-primary font-mono animate-pulse">Iniciando Protocolo...</div>
        </div>
    </div>

</body>
</html>
