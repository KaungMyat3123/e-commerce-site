<?php
// includes/config.php — application settings
// Copy values here or set matching environment variables on the server.

return [
    'app_name'    => getenv('APP_NAME') ?: 'MMart',
    'currency'    => getenv('CURRENCY') ?: 'USD',

    // 'sqlite' — zero-setup file database (default), created automatically on first run.
    // 'sqlsrv' — Microsoft SQL Server per the project specification (requires pdo_sqlsrv).
    'db_driver'   => getenv('DB_DRIVER') ?: 'sqlite',

    'db' => [
        'sqlite_path' => dirname(__DIR__) . '/database/ecommerce.db',
        'server'      => getenv('DB_SERVER')   ?: 'MISKMN-T14\SQLEXPRESS',
        'database'    => getenv('DB_DATABASE') ?: 'EcommerceDB',
        // Leave user/password empty to use Windows Authentication (sqlsrv only).
        'user'        => getenv('DB_USER')     ?: '',
        'password'    => getenv('DB_PASSWORD') ?: '',
    ],
];
