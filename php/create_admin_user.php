<?php
/* ============================================================
   create_admin_user.php
   Crea o actualiza un usuario admin con contraseña hasheada.

   POST JSON (protegido por ADMIN_PASSWORD):
     { "admin_password": "<ADMIN_PASSWORD>", "username": "admin", "password": "secret" }

   Es recomendable eliminar o proteger este script tras usarlo.
   ============================================================ */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body = getJsonBody();
$bootstrap = isset($body['admin_password']) ? $body['admin_password'] : '';
$username  = isset($body['username']) ? trim($body['username']) : '';
$password  = isset($body['password']) ? $body['password'] : '';

if ($bootstrap !== ADMIN_PASSWORD) {
    http_response_code(403);
    jsonResponse(array('success' => false, 'error' => 'Unauthorized'));
}

if (!$username || !$password || strlen($username) < 2 || strlen($password) < 6) {
    http_response_code(400);
    jsonResponse(array('success' => false, 'error' => 'Invalid username or password (min 6 chars)'));
}

$file = __DIR__ . '/admin_users.json';
$data = array('users' => array());
if (file_exists($file)) {
    $raw = file_get_contents($file);
    $parsed = $raw ? json_decode($raw, true) : null;
    if ($parsed && isset($parsed['users'])) $data = $parsed;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$data['users'][$username] = $hash;

if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) === false) {
    http_response_code(500);
    jsonResponse(array('success' => false, 'error' => 'Could not write users file'));
}

jsonResponse(array('success' => true, 'message' => 'User created/updated', 'username' => $username));
