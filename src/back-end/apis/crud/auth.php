<?php
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: /back-end/authentication/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (empty($_SESSION['isAdmin'])) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function currentUserID(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}
