<?php
// includes/auth.php — admin session guard
require_once __DIR__ . '/db.php';

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_is_logged_in()) {
        global $config;
        header('Location: ' . ($config['admin_login'] ?? 'login.php'));
        exit;
    }

    // Guarded pages must never be cached or restored from browser back/forward cache.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
