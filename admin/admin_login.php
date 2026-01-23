<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user['username'];
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Provitta Life</title>
    <link rel="icon" href="../assets/src/favicon.icon" type="image/x-icon">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen flex items-center justify-center p-4">
    
    <!-- Dot Grid Background -->
    <div id="dot-grid" class="dot-grid"></div>
    <script src="../assets/js/background.js"></script>

    <div class="relative z-10 w-full max-w-md bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-12 md:h-14 w-auto mx-auto mb-4 drop-shadow-2xl">
            <h1 class="text-2xl font-bold text-white mb-2">Área Administrativa</h1>
            <p class="text-gray-400 text-sm">Acesse o dashboard de gestão</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-2xl mb-6 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1">Usuário</label>
                <input 
                    type="text" 
                    name="username" 
                    required 
                    class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                    placeholder="Digite seu usuário"
                    autocomplete="username">
            </div>
            
            <div>
                <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1">Senha</label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                    placeholder="Digite sua senha"
                    autocomplete="current-password">
            </div>
            
            <button 
                type="submit" 
                class="w-full py-4 bg-primary text-background font-black rounded-2xl hover:bg-secondary hover:shadow-[0_0_30px_rgba(102,252,241,0.4)] transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2">
                ENTRAR
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-center">
            <p class="text-xs text-gray-500">
                Credenciais padrão: <span class="text-primary font-mono">admin</span> / <span class="text-primary font-mono">admin123</span>
            </p>
        </div>
    </div>
</body>
</html>
