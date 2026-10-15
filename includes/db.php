<?php
// includes/db.php — PDO connection (SQLite by default, SQL Server via config) + shared helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/config.php';

try {
    if (($config['db_driver'] ?? 'sqlite') === 'sqlite') {
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $isNewDb = !file_exists($config['db']['sqlite_path']);
        $conn = new PDO('sqlite:' . $config['db']['sqlite_path'], null, null, $pdoOptions);
        $conn->exec('PRAGMA foreign_keys = ON');

        if ($isNewDb) {
            $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sqlite.sql');
            $conn->exec(str_replace('PRAGMA foreign_keys = ON;', '', $schema));
        }
    } else {
        $dsn = sprintf('sqlsrv:server=%s;Database=%s', $config['db']['server'], $config['db']['database']);

        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if ($config['db']['user'] !== '') {
            $conn = new PDO($dsn, $config['db']['user'], $config['db']['password'], $pdoOptions);
        } else {
            // Windows Authentication
            $conn = new PDO($dsn, null, null, $pdoOptions);
        }
    }
} catch (PDOException $e) {
    die('Database Connection Failed: ' . htmlspecialchars($e->getMessage()));
}

function currency(float|string $amount): string
{
    global $config;
    return $config['currency'] . ' ' . number_format((float) $amount, 2);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    if (!isset($_POST['csrf_token']) || !hash_equals(csrf_token(), (string) $_POST['csrf_token'])) {
        http_response_code(419);
        die('Invalid or expired form token. Go back and try again.');
    }
}
