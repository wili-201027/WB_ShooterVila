<?php
/* ============================================================
   admin_login.php
   Autenticación para el panel de admin.

   POST JSON: { "username": "admin", "password": "secret" }

   Busca usuarios en `php/admin_users.json` (formato { "users": { "name": "hash" } }).
   Si no existe el archivo, hace fallback a la constante `ADMIN_PASSWORD` definida en `config.php`.
   ============================================================ */

require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body = getJsonBody();
$username = isset($body['username']) ? trim($body['username']) : '';
$password = isset($body['password']) ? $body['password'] : '';

if (!$username || !$password) {
    http_response_code(400);
    jsonResponse(array('success' => false, 'error' => 'Missing credentials'));
}

$usersFile = __DIR__ . '/admin_users.json';
$authenticated = false;

if (file_exists($usersFile)) {
    $raw = @file_get_contents($usersFile);
    $data = $raw ? json_decode($raw, true) : null;
    if ($data && isset($data['users']) && isset($data['users'][$username])) {
        $hash = $data['users'][$username];
        if (password_verify($password, $hash)) {
            $authenticated = true;
        }
    }
}

// Fallback: si no hay archivo de usuarios, permitir acceso con ADMIN_PASSWORD (usuario 'admin')
if (!$authenticated) {
    if (defined('ADMIN_PASSWORD') && ADMIN_PASSWORD !== '' && $username === 'admin' && $password === ADMIN_PASSWORD) {
        $authenticated = true;
    }
}

if (!$authenticated) {
    http_response_code(403);
    jsonResponse(array('success' => false, 'error' => 'Invalid credentials'));
}

// Login OK
session_regenerate_id(true);
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user'] = $username;

jsonResponse(array('success' => true));
