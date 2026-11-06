<?php
// includes/db.php — PDO connection (SQLite by default, MySQL/MariaDB + SQL Server via config) + shared helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/config.php';
$driver = $config['db_driver'] ?? 'sqlite';

try {
    if ($driver === 'sqlite') {
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
    } elseif ($driver === 'mysql') {
        // MySQL / MariaDB (XAMPP, Homebrew MySQL, LAMP, ...)
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException(
                'The pdo_mysql PHP extension is not enabled. Enable "extension=pdo_mysql" in php.ini and restart Apache.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['db']['server'],
            (int) ($config['db']['port'] ?? 3306),
            $config['db']['database']
        );
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        // XAMPP ships MySQL with "root" and an empty password.
        $dbUser = $config['db']['user'] !== '' ? $config['db']['user'] : 'root';
        $conn = new PDO($dsn, $dbUser, $config['db']['password'], $pdoOptions);

        // First run: create and seed the tables automatically (same idea as SQLite mode).
        $needsSchema = true;
        try {
            $needsSchema = ((int) $conn->query('SELECT COUNT(*) FROM Admins')->fetchColumn()) === 0;
        } catch (PDOException $e) {
            $needsSchema = true; // table not created yet
        }
        if ($needsSchema) {
            mysql_import_schema($conn, dirname(__DIR__) . '/database/schema.mysql.sql');
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
} catch (RuntimeException $e) {
    die('Database Setup Failed: ' . htmlspecialchars($e->getMessage()));
}

// Existing databases predate the Reviews table (the schema file only runs when
// Admins is empty), so create it lazily on every request — idempotent.
if ($driver !== 'sqlsrv') {
    $conn->exec(
        $driver === 'mysql'
            ? 'CREATE TABLE IF NOT EXISTS Reviews (
                review_id INT NOT NULL AUTO_INCREMENT,
                product_id INT NOT NULL,
                reviewer_name VARCHAR(100) NOT NULL,
                comment TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'pending\',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (review_id),
                KEY idx_reviews_product (product_id),
                KEY idx_reviews_status (status),
                CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES Products (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            : 'CREATE TABLE IF NOT EXISTS Reviews (
                review_id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL REFERENCES Products(product_id),
                reviewer_name TEXT NOT NULL,
                comment TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'pending\',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
    );
}

/**
 * Run a SQL file one statement at a time (MySQL/MariaDB).
 * The file is split on ";", so keep ";" out of string literals in it.
 */
function mysql_import_schema(PDO $conn, string $schemaFile): void
{
    $sql = file_get_contents($schemaFile);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Could not read MySQL schema file: ' . $schemaFile);
    }
    $sql = preg_replace('/^--.*$/m', '', $sql); // drop "-- ..." comment lines
    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $conn->exec($statement);
        }
    }
}

/** SQL fragment that randomizes row order on the active driver. */
function db_random_order(): string
{
    global $config;
    return match ($config['db_driver'] ?? 'sqlite') {
        'mysql'  => 'RAND()',
        'sqlsrv' => 'NEWID()',
        default  => 'RANDOM()',
    };
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
