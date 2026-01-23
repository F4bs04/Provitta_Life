<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$userId = $_SESSION['admin_user_id'];
$message = '';
$error = '';

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $cpf = $_POST['cpf'];
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, cpf = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $cpf, $hashedPassword, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, cpf = ? WHERE id = ?");
            $stmt->execute([$name, $cpf, $userId]);
        }
        $message = "Configurações atualizadas com sucesso!";
        
        // Atualizar dados na sessão
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $_SESSION['admin_user'] = $user['username'];
    } catch (Exception $e) {
        $error = "Erro ao atualizar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Provitta Life</title>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen">
    <div id="dot-grid" class="dot-grid"></div>
    <script src="../assets/js/background.js"></script>

    <header class="relative z-10 border-b border-white/10 bg-black/30 backdrop-blur-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="admin_dashboard.php"><img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 w-auto"></a>
                <h1 class="text-2xl font-bold text-white">Minha Conta</h1>
            </div>
            <a href="admin_dashboard.php" class="text-primary hover:text-secondary transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar ao Dashboard
            </a>
        </div>
    </header>

    <main class="relative z-10 container mx-auto px-6 py-8 max-w-2xl">
        
        <!-- Aviso Importante -->
        <div class="bg-yellow-400/10 border border-yellow-400/30 rounded-3xl p-6 mb-8 flex gap-4 items-start">
            <div class="p-3 bg-yellow-400/20 rounded-2xl text-yellow-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-yellow-400 font-bold mb-1">Aviso de Segurança</h3>
                <p class="text-yellow-400/80 text-sm leading-relaxed">
                    Esta conta é <strong>única por CPF e intransferível</strong>. O compartilhamento de credenciais ou a transferência de acesso para terceiros é estritamente proibido e pode resultar no bloqueio imediato da conta.
                </p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 p-4 rounded-2xl mb-6 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-2xl mb-6 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2 ml-1">Usuário (Login)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="w-full bg-black/20 border border-white/5 rounded-2xl p-4 text-gray-500 cursor-not-allowed outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2 ml-1">Papel</label>
                        <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled class="w-full bg-black/20 border border-white/5 rounded-2xl p-4 text-gray-500 cursor-not-allowed outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold uppercase mb-2 ml-1">Nome Completo</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white outline-none focus:ring-2 focus:ring-primary transition-all">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold uppercase mb-2 ml-1">CPF</label>
                    <input type="text" name="cpf" value="<?php echo htmlspecialchars($user['cpf'] ?? ''); ?>" required class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white outline-none focus:ring-2 focus:ring-primary transition-all">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold uppercase mb-2 ml-1">Nova Senha (deixe em branco para manter)</label>
                    <input type="password" name="password" class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white outline-none focus:ring-2 focus:ring-primary transition-all" placeholder="••••••••">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-4 bg-primary text-background font-black rounded-2xl hover:bg-secondary transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-primary/20">
                        SALVAR ALTERAÇÕES
                    </button>
                </div>
            </form>
        </div>

    </main>
</body>
</html>
