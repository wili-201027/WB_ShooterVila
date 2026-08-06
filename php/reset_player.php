<?php
/* ============================================================
   reset_player.php
   Posa a 0 la puntuació d'un jugador concret (manté el registre).
   ⚠️ ACCIÓ ADMIN - Requereix autenticació de sessió.

   POST JSON: { "player": "NomJugador" }
   Resposta:  { "success": true }
   ============================================================ */

require_once 'config.php';
checkAdminSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body = getJsonBody();
$name = isset($body['player']) ? sanitizeName($body['player']) : '';

if (!$name || strlen($name) < 2) {
    jsonResponse(array('success' => false, 'error' => 'Invalid player name'));
}

$db   = getDB();
$stmt = $db->prepare(
    'UPDATE scores SET score = 0, games_played = 0, updated_at = NOW()
     WHERE player_name = :name'
);
$stmt->execute(array(':name' => $name));

if ($stmt->rowCount() === 0) {
    jsonResponse(array('success' => false, 'error' => 'Player not found'));
}

jsonResponse(array('success' => true));