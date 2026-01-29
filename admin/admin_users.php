<?php
session_start();
require_once '../db.php';

$page_title = 'Gerenciar Usuários - Provitta Life';
include 'includes/header.php';

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
        $classification = $_POST['classification'] ?? 'Não Pagante';
        $invite_limit = intval($_POST['invite_limit'] ?? 0);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, cpf, role, classification, invite_limit) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $name, $cpf, $role, $classification, $invite_limit]);
            $message = "Usuário adicionado com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao adicionar usuário: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $username = $_POST['username'];
        $name = $_POST['name'];
        $cpf = $_POST['cpf'];
        $role = $_POST['role'];
        $classification = $_POST['classification'] ?? 'Não Pagante';
        $invite_limit = intval($_POST['invite_limit'] ?? 0);
        $new_password = $_POST['password'] ?? '';

        try {
            // Se uma nova senha foi fornecida, atualiza com senha
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, name = ?, cpf = ?, role = ?, classification = ?, invite_limit = ? WHERE id = ?");
                $stmt->execute([$username, $password_hash, $name, $cpf, $role, $classification, $invite_limit, $id]);
            } else {
                // Caso contrário, atualiza sem alterar a senha
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, cpf = ?, role = ?, classification = ?, invite_limit = ? WHERE id = ?");
                $stmt->execute([$username, $name, $cpf, $role, $classification, $invite_limit, $id]);
            }
            $message = "Usuário atualizado com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao atualizar usuário: " . $e->getMessage();
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

<main class="flex-1 p-4 md:p-6 lg:p-8" x-data="{ showAddModal: false, showEditModal: false, editUser: {}, role: 'consultant' }">
        
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
            <div class="overflow-x-auto">
                <table class="w-full">
                <thead class="bg-black/30 border-b border-white/10">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Nome / Usuário</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">CPF</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Papel</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Classificação</th>
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
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                echo match($user['role']) {
                                    'master' => 'bg-primary/20 text-primary',
                                    'subscriber' => 'bg-purple-500/20 text-purple-400',
                                    default => 'bg-secondary/20 text-secondary'
                                };
                            ?>">
                                <?php 
                                echo match($user['role']) {
                                    'master' => 'Master',
                                    'subscriber' => 'Assinante/Empresa',
                                    default => 'Consultor'
                                };
                                ?>
                                <?php if ($user['role'] === 'subscriber'): ?>
                                    <span class="ml-1 opacity-70">(Limite: <?php echo $user['invite_limit']; ?>)</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                echo match($user['classification'] ?? 'Não Pagante') {
                                    'Assinante anual' => 'bg-green-500/20 text-green-400',
                                    'Assinante mensal' => 'bg-blue-500/20 text-blue-400',
                                    'Vitalicio' => 'bg-purple-500/20 text-purple-400',
                                    default => 'bg-gray-500/20 text-gray-400'
                                };
                            ?>">
                                <?php echo htmlspecialchars($user['classification'] ?? 'Não Pagante'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <!-- Botão Editar -->
                                <button 
                                    @click="showEditModal = true; editUser = <?php echo htmlspecialchars(json_encode($user)); ?>"
                                    class="text-blue-400 hover:text-blue-300 transition-colors"
                                    title="Editar usuário">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                
                                <!-- Botão Excluir -->
                                <?php if ($user['id'] != $_SESSION['admin_user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="text-red-400 hover:text-red-300 transition-colors" title="Excluir usuário">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
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
                        <select name="role" x-model="role" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                            <option value="consultant">Consultor (Acesso aos seus leads)</option>
                            <option value="subscriber">Assinante/Empresa (Acesso aos seus leads e convites)</option>
                            <option value="master">Master (Acesso total)</option>
                        </select>
                    </div>
                    <div x-show="role === 'subscriber'">
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Limite de Convites</label>
                        <input type="number" name="invite_limit" value="0" min="0" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-[10px] text-gray-500 mt-1">Quantidade de convites que este usuário pode gerar.</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Classificação</label>
                        <select name="classification" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                            <option value="Não Pagante">Não Pagante</option>
                            <option value="Assinante mensal">Assinante mensal</option>
                            <option value="Assinante anual">Assinante anual</option>
                            <option value="Vitalicio">Vitalicio</option>
                        </select>
                    </div>
                    <div class="flex gap-4 mt-8">
                        <button type="button" @click="showAddModal = false" class="flex-1 py-3 bg-white/5 text-white rounded-xl hover:bg-white/10 transition-colors">Cancelar</button>
                        <button type="submit" name="add_user" class="flex-1 py-3 bg-primary text-background font-bold rounded-xl hover:bg-secondary transition-colors">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Editar Usuário -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div @click.away="showEditModal = false" class="bg-surface border border-white/10 rounded-3xl p-8 w-full max-w-md shadow-2xl">
                <h3 class="text-2xl font-bold text-white mb-6">Editar Usuário</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="user_id" x-model="editUser.id">
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Nome Completo</label>
                        <input type="text" name="name" x-model="editUser.name" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Usuário (Login)</label>
                        <input type="text" name="username" x-model="editUser.username" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">CPF</label>
                        <input type="text" name="cpf" x-model="editUser.cpf" required class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Nova Senha (deixe em branco para manter a atual)</label>
                        <input type="password" name="password" placeholder="••••••••" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Preencha apenas se desejar alterar a senha</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Papel</label>
                        <select name="role" x-model="editUser.role" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                            <option value="consultant">Consultor (Acesso aos seus leads)</option>
                            <option value="subscriber">Assinante/Empresa (Acesso aos seus leads e convites)</option>
                            <option value="master">Master (Acesso total)</option>
                        </select>
                    </div>
                    <div x-show="editUser.role === 'subscriber'">
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Limite de Convites</label>
                        <input type="number" name="invite_limit" x-model="editUser.invite_limit" min="0" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-[10px] text-gray-500 mt-1">Quantidade de convites que este usuário pode gerar.</p>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Classificação</label>
                        <select name="classification" x-model="editUser.classification" class="w-full bg-background/50 border border-white/10 rounded-xl p-3 text-white outline-none focus:ring-2 focus:ring-primary">
                            <option value="Não Pagante">Não Pagante</option>
                            <option value="Assinante mensal">Assinante mensal</option>
                            <option value="Assinante anual">Assinante anual</option>
                            <option value="Vitalicio">Vitalicio</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-4 mt-8">
                        <button type="button" @click="showEditModal = false" class="flex-1 py-3 bg-white/5 text-white rounded-xl hover:bg-white/10 transition-colors">Cancelar</button>
                        <button type="submit" name="edit_user" class="flex-1 py-3 bg-primary text-background font-bold rounded-xl hover:bg-secondary transition-colors">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>

    <?php include 'includes/footer.php'; ?>
