<?php
/* ============================================================
   set_points.php
   Estableix la puntuació exacta d'un jugador (crea si no existeix).
   ⚠️ ACCIÓ ADMIN - Requereix autenticació de sessió.

   POST JSON: { "player": "NomJugador", "score": 1500 }
   Resposta:  { "success": true, "new_score": 1500 }
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

$body  = getJsonBody();
$name  = isset($body['player']) ? sanitizeName($body['player']) : '';
$score = isset($body['score'])  ? intval($body['score'])        : null;

if (!$name || strlen($name) < 2) {
    jsonResponse(array('success' => false, 'error' => 'Invalid player name'));
}
if (!is_int($score) || $score < 0 || $score > 9999999) {
    jsonResponse(array('success' => false, 'error' => 'Invalid score'));
}

try {
    $db = getDB();

    $stmt = $db->prepare(
        'INSERT INTO scores (player_name, score, games_played, created_at, updated_at)
         VALUES (:name, :score, 0, NOW(), NOW())
         ON DUPLICATE KEY UPDATE score = :score, updated_at = NOW()'
    );
    $stmt->execute(array(':name' => $name, ':score' => $score));

    $row = $db->prepare('SELECT score FROM scores WHERE player_name = :name');
    $row->execute(array(':name' => $name));
    $newScore = (int) $row->fetchColumn();

    jsonResponse(array('success' => true, 'new_score' => $newScore));
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(array('success' => false, 'error' => 'Server error'));
}
