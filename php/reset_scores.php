<?php
/* ============================================================
   reset_scores.php
   Esborra TOTES les puntuacions. Requereix contrasenya d'admin.
   Acció irreversible — usar amb molta precaució.

   POST JSON: { "admin_password": "la_teva_password" }
   Resposta:  { "success": true, "deleted": 42 }
   ============================================================ */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

session_start();
$body = getJsonBody();

// Preferencia: usar sesión autenticada (método seguro) OR contraseña hardcodeada (fallback)
$isAuthed = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
$pass = isset($body['admin_password']) ? $body['admin_password'] : '';

if (!$isAuthed && $pass !== ADMIN_PASSWORD) {
    http_response_code(403);
    jsonResponse(array('success' => false, 'error' => 'Unauthorized'));
}

$db      = getDB();
$deleted = (int) $db->query('SELECT COUNT(*) FROM scores')->fetchColumn();
$db->exec('TRUNCATE TABLE scores');

jsonResponse(array('success' => true, 'deleted' => $deleted));
