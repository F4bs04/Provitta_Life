<?php
session_start();
require_once '../db.php';

// Verificar se é Master Admin
if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['admin_role'] ?? '') !== 'master') {
    header('Location: admin_dashboard.php');
    exit;
}

$message = '';
$error = '';

// Ações de Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name = $_POST['name'];
        $cpf = $_POST['cpf'];
        $role = $_POST['role'];

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, cpf, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $name, $cpf, $role]);
            $message = "Usuário adicionado com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao adicionar usuário: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        if ($id != $_SESSION['admin_user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Usuário excluído com sucesso!";
        } else {
            $error = "Você não pode excluir sua própria conta master.";
        }
    }
}

// Listar usuários
$stmt = $pdo->query("SELECT * FROM users ORDER BY role DESC, name ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Provitta Life</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen">
    <div id="dot-grid" class="dot-grid"></div>
    <script src="../assets/js/background.js"></script>

    <header class="relative z-10 border-b border-white/10 bg-black/30 backdrop-blur-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="admin_dashboard.php"><img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 w-auto"></a>
                <h1 class="text-2xl font-bold text-white">Gerenciar Usuários</h1>
            </div>
            <a href="admin_dashboard.php" class="text-primary hover:text-secondary transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar ao Dashboard
            </a>
        </div>
    </header>

    <main class="relative z-10 container mx-auto px-6 py-8" x-data="{ showAddModal: false }">
        
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

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-bold text-white">Usuários Cadastrados</h2>
            <button @click="showAddModal = true" class="px-6 py-3 bg-primary text-background font-black rounded-xl hover:bg-secondary transition-all transform hover:scale-105">
                + NOVO USUÁRIO
            </button>
        </div>

        <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            <table class="w-full">
                <thead class="bg-black/30 border-b border-white/10">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Nome / Usuário</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">CPF</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Papel</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-white"><?php echo htmlspecialchars($user['name'] ?? 'Sem nome'); ?></div>
                            <div class="text-sm text-gray-400">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </td>
                        <td class="px-6 py-4 text-gray-300"><?php echo htmlspecialchars($user['cpf'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $user['role'] === 'master' ? 'bg-primary/20 text-primary' : 'bg-secondary/20 text-secondary'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($user['id'] != $_SESSION['admin_user_id']): ?>
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?')">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="text-red-400 hover:text-red-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Adicionar Usuário -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div @click.away="showAddModal = false" class="bg-surface border border-white/10 rounded-3xl p-8 w-full max-w-md shadow-2xl">
                <h3 class="text-2xl font-bold text-white mb-6">Novo Usuário</h3>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Nome Completo</label>
                        <input type="text" name="name" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Usuário (Login)</label>
                        <input type="text" name="username" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">CPF</label>
                        <input type="text" name="cpf" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Senha</label>
                        <input type="password" name="password" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Papel</label>
                        <select name="role" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                            <option value="consultant">Consultor (Acesso aos seus leads)</option>
                            <option value="master">Master (Acesso total)</option>
                        </select>
                    </div>
                    <div class="flex gap-4 mt-8">
                        <button type="button" @click="showAddModal = false" class="flex-1 py-3 bg-white/5 text-white rounded-xl hover:bg-white/10 transition-colors">Cancelar</button>
                        <button type="submit" name="add_user" class="flex-1 py-3 bg-primary text-background font-bold rounded-xl hover:bg-secondary transition-colors">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</body>
</html>
