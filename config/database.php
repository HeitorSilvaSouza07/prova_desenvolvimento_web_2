<?php
/**
 * Conexão com o banco de dados (MySQL / MariaDB via PDO).
 * Ajuste as constantes abaixo ou defina as variáveis de ambiente
 * DB_HOST, DB_NAME, DB_USER e DB_PASS.
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'prosiga');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('<h1>Erro de conexão</h1><p>Não foi possível conectar ao MySQL. '
                . 'Confira <code>config/database.php</code> e importe o arquivo '
                . '<code>database.sql</code>.<br><br>Detalhe: '
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>');
        }
    }

    return $pdo;
}
