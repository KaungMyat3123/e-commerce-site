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
        header('Location: login.php');
        exit;
    }
}
