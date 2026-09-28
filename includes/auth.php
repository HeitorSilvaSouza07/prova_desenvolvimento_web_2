<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}


if (empty($_SESSION['user_id']) && !empty($_COOKIE['prosiga_token'])) {
    try {
        $hash  = hash('sha256', (string) $_COOKIE['prosiga_token']);
        $stmt  = db()->prepare('SELECT id FROM usuarios WHERE token = :token AND ativo = 1 LIMIT 1');
        $stmt->execute([':token' => $hash]);
        $row = $stmt->fetch();
        if ($row) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $row['id'];
        }
    } catch (Throwable $e) {
        // banco indisponível: segue sem sessão
    }
}


function current_user(): ?array
{
    static $cache = null;
    static $loaded = false;

    if ($loaded) {
        return $cache;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        $cache = null;
        return null;
    }

    try {
        $stmt = db()->prepare('SELECT * FROM usuarios WHERE id = :id AND ativo = 1');
        $stmt->execute([':id' => (int) $_SESSION['user_id']]);
        $cache = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        $cache = null;
    }

    return $cache;
}

function require_login(): void
{
    if (!current_user()) {
        flash_set('info', 'Faça acesso para continuar.');
        redirect(app_url('entrar.php'));
    }
}

function require_role(array $roles): void
{
    $user = current_user();

    if (!$user) {
        flash_set('info', 'Faça acesso para continuar.');
        redirect(app_url('entrar.php'));
    }

    if (!in_array($user['tipo'], $roles, true)) {
        flash_set('error', 'Você não tem permissão para acessar esta área.');
        redirect(app_url('dashboard/index.php'));
    }
}
