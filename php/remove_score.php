<?php
/* ============================================================
   remove_score.php
   Elimina completament un jugador de la taula (acció admin).
   ⚠️ ACCIÓ ADMIN - Requereix autenticació de sessió.

   POST JSON: { "player": "NomJugador" }
   Resposta:  { "success": true, "deleted": 1 }
   ============================================================ */

require_once 'config.php';
checkAdminSession();

// CSRF protection
$csrf = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
if (!verifyCsrfToken($csrf)) {
    http_response_code(403);
    jsonResponse(array('success' => false, 'error' => 'Invalid CSRF token'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body = getJsonBody();
$name = isset($body['player']) ? sanitizeName($body['player']) : '';

if (!$name || strlen($name) < 2) {
    jsonResponse(array('success' => false, 'error' => 'Invalid player name'));
}

try {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM scores WHERE player_name = :name');
    $stmt->execute(array(':name' => $name));
    $deleted = $stmt->rowCount();

    if ($deleted === 0) jsonResponse(array('success' => false, 'error' => 'Player not found'));

    jsonResponse(array('success' => true, 'deleted' => $deleted));
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(array('success' => false, 'error' => 'Server error'));
}
