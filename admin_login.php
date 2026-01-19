<?php
session_start();
require_once 'db.php';

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
    <link href="./assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Geologica:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Geologica', sans-serif; }
    </style>
</head>
<body class="bg-background bg-brand-gradient min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-[#1F2833]/50 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-12 w-auto mx-auto mb-4">
            <p class="text-gray-400 mt-2">Área Administrativa</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-4 rounded-xl mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2">Usuário</label>
                <input type="text" name="username" required class="w-full bg-black/30 border border-white/10 rounded-xl p-4 text-white focus:ring-2 focus:ring-[#66FCF1] outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Senha</label>
                <input type="password" name="password" required class="w-full bg-black/30 border border-white/10 rounded-xl p-4 text-white focus:ring-2 focus:ring-[#66FCF1] outline-none transition-all">
            </div>
            <button type="submit" class="w-full py-4 bg-[#66FCF1] text-[#0B0C10] font-bold rounded-xl hover:bg-[#45A29E] transition-all shadow-[0_0_20px_rgba(102,252,241,0.3)]">
                ENTRAR
            </button>
        </form>
    </div>
</body>
</html>
