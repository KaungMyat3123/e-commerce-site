<?php
// includes/config.php — application settings
// Copy values here or set matching environment variables on the server.

return [
    'app_name'    => getenv('APP_NAME') ?: 'Glow & Grace',
    'currency'    =>  'MMK',

    // Secret admin login route: the filename inside /admin/ that serves the login
    // form. If you rename that file, update this value to match. Keep it long and
    // hard to guess so bots probing /admin/login.php find nothing.
    'admin_login' => 'secure-access-7f3a.php',

    // 'sqlite' — zero-setup file database (default), created automatically on first run.
    // 'mysql'  — MySQL / MariaDB (XAMPP, Homebrew, LAMP); created on first run if the
    //            database is empty. See SETUP_MACOS_XAMPP_MYSQL.md.
    // 'sqlsrv' — Microsoft SQL Server per the project specification (requires pdo_sqlsrv).
    'db_driver'   => 'sqlite',

    'db' => [
        'sqlite_path' => dirname(__DIR__) . '/database/ecommerce.db',
        'server'      => '127.0.0.1',
        'port'        => 3308,
        'database'    => 'ecommerce',
        'user'        => 'root',
        'password'    => '',
        
    ],
];
