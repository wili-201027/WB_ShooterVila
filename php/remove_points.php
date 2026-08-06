<?php
/* ============================================================
   remove_points.php
   Resta punts a un jugador. La puntuació mínima és 0.
   ⚠️ ACCIÓ ADMIN - Requereix autenticació de sessió.

   POST JSON: { "player": "NomJugador", "points": 200 }
   Resposta:  { "success": true, "new_score": 800 }
   ============================================================ */

require_once 'config.php';
checkAdminSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body   = getJsonBody();
$name   = isset($body['player']) ? sanitizeName($body['player']) : '';
$points = isset($body['points']) ? intval($body['points'])       : 0;

if (!$name || strlen($name) < 2) {
    jsonResponse(array('success' => false, 'error' => 'Invalid player name'));
}
if ($points <= 0 || $points > 9999999) {
    jsonResponse(array('success' => false, 'error' => 'Points must be a positive number'));
}

$db = getDB();

/* Comprova que el jugador existeix */
$check = $db->prepare('SELECT score FROM scores WHERE player_name = :name');
$check->execute(array(':name' => $name));
$current = $check->fetchColumn();

if ($current === false) {
    jsonResponse(array('success' => false, 'error' => 'Player not found'));
}

/* Resta punts sense baixar de 0 */
$newScore = max(0, (int)$current - $points);
$stmt = $db->prepare(
    'UPDATE scores SET score = :score, updated_at = NOW() WHERE player_name = :name'
);
$stmt->execute(array(':score' => $newScore, ':name' => $name));

jsonResponse(array('success' => true, 'new_score' => $newScore));