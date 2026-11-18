<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function adalah_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function perlu_admin(bool $json = false): void
{
    if (adalah_admin()) return;
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'mesej' => 'Sila log masuk semula.']);
        exit;
    }
    header('Location: login.php');
    exit;
}
