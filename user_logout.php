<?php
session_start();

// Limpar sessão do usuário
$session_id = session_id();
$ip_address = $_SERVER['REMOTE_ADDR'];

require_once 'db.php';

// Remover sessão do banco de dados
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND ip_address = ?");
    $stmt->execute([$_SESSION['user_id'], $ip_address]);
} else {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ? AND ip_address = ?");
    $stmt->execute([$session_id, $ip_address]);
}

// Destruir sessão
session_destroy();

// Redirecionar para a página inicial
header('Location: index.php');
exit;
?>
