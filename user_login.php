<?php
session_start();

// Se já estiver logado, redirecionar para o formulário
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: form.php');
    exit;
}

require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Verificar se é um código excepcional para pular restrições
    $is_exceptional = false;
    $invite_code_val = trim($_POST['invite_code'] ?? '');
    
    if ($login_type === 'invite_code' && !empty($invite_code_val)) {
        $stmt = $pdo->prepare("SELECT is_exceptional FROM invite_codes WHERE code = ? AND is_active = 1");
        $stmt->execute([$invite_code_val]);
        $is_exceptional = (bool)$stmt->fetchColumn();
    }
    
    $can_proceed = true;
    if (!$is_exceptional) {
        // Verificar se já existe uma sessão ativa deste IP (apenas para logins não-excepcionais)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_sessions WHERE ip_address = ? AND last_activity > datetime('now', '-1 hour')");
        $stmt->execute([$ip_address]);
        $active_sessions = $stmt->fetchColumn();
        
        if ($active_sessions > 0) {
            $error = 'Já existe uma sessão ativa neste IP. Por favor, aguarde ou faça logout da sessão anterior.';
            $can_proceed = false;
        }
    }
    
    if ($can_proceed) {
        if ($login_type === 'invite_code') {
            // Login com código de convite
            $invite_code = $invite_code_val;
            
            if (empty($invite_code)) {
                $error = 'Por favor, insira um código de convite.';
            } else {
                // Verificar se o código existe e está ativo
                $stmt = $pdo->prepare("SELECT * FROM invite_codes WHERE code = ? AND is_active = 1");
                $stmt->execute([$invite_code]);
                $code = $stmt->fetch();
                
                if (!$code) {
                    $error = 'Código de convite inválido.';
                } else {
                    // Verificar se o código atingiu o limite de usos (pular se for excepcional)
                    if (!$code['is_exceptional'] && $code['usage_count'] >= $code['max_uses']) {
                        $error = 'Este código de convite já atingiu o limite de usos.';
                    } 
                    // Verificar se o código expirou (pular se for excepcional)
                    elseif (!$code['is_exceptional'] && $code['expires_at'] && strtotime($code['expires_at']) < time()) {
                        $error = 'Este código de convite expirou.';
                    } else {
                        // Código válido - criar sessão
                        $_SESSION['user_authenticated'] = true;
                        $_SESSION['user_type'] = 'invite';
                        $_SESSION['invite_code'] = $invite_code;
                        $_SESSION['user_ip'] = $ip_address;
                        
                        $session_id = session_id();
                        
                        // Registrar sessão no banco
                        $stmt = $pdo->prepare("INSERT INTO user_sessions (session_id, ip_address, invite_code) VALUES (?, ?, ?)");
                        $stmt->execute([$session_id, $ip_address, $invite_code]);
                        
                        // Incrementar contador de uso
                        $stmt = $pdo->prepare("UPDATE invite_codes SET usage_count = usage_count + 1, used_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$code['id']]);
                        
                        header('Location: form.php');
                        exit;
                    }
                }
            }
        } elseif ($login_type === 'user_login') {
            // Login com usuário e senha
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Por favor, preencha usuário e senha.';
            } else {
                // Verificar credenciais
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($password, $user['password'])) {
                    $error = 'Usuário ou senha incorretos.';
                } else {
                    // Login bem-sucedido
                    $_SESSION['user_authenticated'] = true;
                    $_SESSION['user_type'] = 'registered';
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_ip'] = $ip_address;
                    
                    $session_id = session_id();
                    
                    // Registrar sessão no banco
                    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $session_id, $ip_address]);
                    
                    header('Location: form.php');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login - Provitta Life">
    <title>Login - Provitta Life</title>
    <link rel="icon" href="./assets/src/favicon.icon" type="image/x-icon">
    <link href="./assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen flex flex-col bg-fixed">

    <!-- Header -->
    <header class="relative z-10 border-b border-white/5 bg-black/20 backdrop-blur-md">
        <div class="container mx-auto px-4 md:px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="transition-opacity hover:opacity-80">
                <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-6 md:h-8 w-auto">
            </a>
            <div class="text-xs text-gray-400 font-mono">Autenticação</div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-grow container mx-auto px-4 py-12 max-w-md flex flex-col justify-center" x-data="{ loginType: 'invite_code' }">
        
        <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl">
            
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-12 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-white">Bem-vindo</h1>
                <p class="text-gray-400 text-sm mt-2">Faça login para iniciar sua avaliação</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl">
                <p class="text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <!-- Login Type Toggle -->
            <div class="flex items-center gap-2 bg-background/50 rounded-2xl p-1 mb-8 border border-white/10">
                <button 
                    @click="loginType = 'invite_code'"
                    :class="loginType === 'invite_code' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'"
                    class="flex-1 px-4 py-3 rounded-xl transition-all font-medium text-sm">
                    Código de Convite
                </button>
                <button 
                    @click="loginType = 'user_login'"
                    :class="loginType === 'user_login' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'"
                    class="flex-1 px-4 py-3 rounded-xl transition-all font-medium text-sm">
                    Login de Usuário
                </button>
            </div>

            <!-- Forms -->
            
            <!-- Invite Code Form -->
            <form action="user_login.php" method="POST" x-show="loginType === 'invite_code'" x-cloak>
                <input type="hidden" name="login_type" value="invite_code">
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1">Código de Convite</label>
                        <input 
                            type="text" 
                            name="invite_code" 
                            required 
                            class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white focus:ring-2 focus:ring-primary outline-none transition-all uppercase tracking-wider text-center text-lg font-mono" 
                            placeholder="XXXX-XXXX-XXXX"
                            maxlength="20">
                    </div>
                    
                    <button 
                        type="submit"
                        class="w-full px-8 py-4 bg-primary text-background font-black rounded-2xl hover:bg-secondary hover:shadow-[0_0_30px_rgba(102,252,241,0.4)] transition-all transform hover:scale-105 active:scale-95">
                        ACESSAR COM CÓDIGO
                    </button>
                </div>
            </form>

            <!-- User Login Form -->
            <form action="user_login.php" method="POST" x-show="loginType === 'user_login'" x-cloak>
                <input type="hidden" name="login_type" value="user_login">
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1">Usuário</label>
                        <input 
                            type="text" 
                            name="username" 
                            required 
                            class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white focus:ring-2 focus:ring-primary outline-none transition-all" 
                            placeholder="Digite seu usuário">
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1">Senha</label>
                        <input 
                            type="password" 
                            name="password" 
                            required 
                            class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white focus:ring-2 focus:ring-primary outline-none transition-all" 
                            placeholder="Digite sua senha">
                    </div>
                    
                    <button 
                        type="submit"
                        class="w-full px-8 py-4 bg-primary text-background font-black rounded-2xl hover:bg-secondary hover:shadow-[0_0_30px_rgba(102,252,241,0.4)] transition-all transform hover:scale-105 active:scale-95">
                        FAZER LOGIN
                    </button>
                </div>
            </form>

            <!-- Back to Home -->
            <div class="mt-8 text-center">
                <a href="index.php" class="text-gray-400 hover:text-primary text-sm transition-colors">
                    ← Voltar para o início
                </a>
            </div>
        </div>

        <p class="text-xs text-gray-600 mt-8 text-center">
            Tecnologia segura • LGPD Compliant • Baseado em evidências
        </p>
    </main>

</body>
</html>
