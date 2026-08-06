<?php
/* ============================================================
   add_points.php
   Afegeix punts a un jugador existent (o el crea si no existeix).
   ⚠️ ACCIÓ ADMIN - Requereix autenticació de sessió.
   Ús típic: correcció manual des del panel d'admin.

   POST JSON: { "player": "NomJugador", "points": 500 }
   Resposta:  { "success": true, "new_score": 2000 }
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
    jsonResponse(array('success' => false, 'error' => 'Points must be between 1 and 9999999'));
}

$db = getDB();

/* Upsert: crea el jugador si no existeix, afegeix punts si sí */
$stmt = $db->prepare(
    'INSERT INTO scores (player_name, score, games_played, updated_at)
     VALUES (:name, :pts, 0, NOW())
     ON DUPLICATE KEY UPDATE
       score      = score + :pts2,
       updated_at = NOW()'
);
$stmt->execute(array(':name' => $name, ':pts' => $points, ':pts2' => $points));

$row = $db->prepare('SELECT score FROM scores WHERE player_name = :name');
$row->execute(array(':name' => $name));
$newScore = (int) $row->fetchColumn();

jsonResponse(array('success' => true, 'new_score' => $newScore));