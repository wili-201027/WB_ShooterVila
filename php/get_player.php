<?php
/* ============================================================
   get_player.php
   Retorna informació d'un jugador concret.

   GET parameters: ?player=NomJugador
   Resposta: { "success": true, "player": { player_name, score, games_played, created_at, updated_at } }
   ============================================================ */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$name = isset($_GET['player']) ? sanitizeName($_GET['player']) : '';
if (!$name || strlen($name) < 2) {
    jsonResponse(array('success' => false, 'error' => 'Invalid player name'));
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT player_name, score, games_played, created_at, updated_at FROM scores WHERE player_name = :name LIMIT 1');
    $stmt->execute(array(':name' => $name));
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) jsonResponse(array('success' => false, 'error' => 'Player not found'));

    jsonResponse(array('success' => true, 'player' => $player));
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(array('success' => false, 'error' => 'Server error'));
}