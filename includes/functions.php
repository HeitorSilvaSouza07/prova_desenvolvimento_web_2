<?php

if (!defined('PROSIGA_START')) {
    define('PROSIGA_START', microtime(true));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}


function app_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
        $dir    = str_replace('\\', '/', dirname($script));
        $dir    = rtrim($dir, '/');
        if (substr($dir, -10) === '/dashboard') {
            $dir = substr($dir, 0, -10);
        }
        $base = $dir;
    }

    return $base . '/' . ltrim($path, '/');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function base_url(string $path = ''): string
{
    return ltrim($path, '/');
}


function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_show(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';

    return '<div class="flash flash--' . $type . '" role="status">'
        . '<span aria-hidden="true">' . ($type === 'success' ? '&#10003;' : ($type === 'error' ? '!' : 'i')) . '</span>'
        . '<span>' . e($flash['message']) . '</span></div>';
}

/* ------------------------------- CSRF ---------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}


function turmas_visiveis(PDO $pdo, array $user): array
{
    if ($user['tipo'] === 'aluno') {
        $stmt = $pdo->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos,
                    (SELECT COUNT(*) FROM turma_professores tp WHERE tp.turma_id = t.id) AS total_professores
             FROM turmas t
             INNER JOIN turma_alunos ta ON ta.turma_id = t.id
             WHERE ta.aluno_id = :aluno
             ORDER BY t.nome'
        );
        $stmt->execute([':aluno' => $user['id']]);
        return $stmt->fetchAll();
    }

    $stmt = $pdo->query(
        'SELECT t.*,
                (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos,
                (SELECT COUNT(*) FROM turma_professores tp WHERE tp.turma_id = t.id) AS total_professores
         FROM turmas t ORDER BY t.nome'
    );
    return $stmt->fetchAll();
}

function pode_gerenciar(array $user): bool
{
    return in_array($user['tipo'], ['admin', 'professor'], true);
}

function tipo_label(string $tipo): string
{
    return ['admin' => 'Administrador', 'professor' => 'Professor', 'aluno' => 'Aluno'][$tipo] ?? $tipo;
}

function formatar_data(string $data): string
{
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : $data;
}

function formatar_hora(?string $hora): string
{
    if (!$hora) {
        return '';
    }
    return substr($hora, 0, 5);
}
