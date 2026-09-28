<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_COOKIE['prosiga_token'])) {
    try {
        $hash = hash('sha256', (string) $_COOKIE['prosiga_token']);
        $stmt = db()->prepare('UPDATE usuarios SET token = NULL WHERE token = :token');
        $stmt->execute([':token' => $hash]);
    } catch (Throwable $e) {

    }
    setcookie('prosiga_token', '', time() - 42000, '/');
}

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['flash'] = ['type' => 'info', 'message' => 'Você saiu do sistema.'];

redirect(app_url('entrar.php'));
