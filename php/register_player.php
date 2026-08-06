<?php
/* ============================================================
   register_player.php
   Registra o crea un jugador nou si no existeix.

   POST JSON: { "player": "NomJugador" }
   Resposta:  { "success": true, "created": true|false, "player": { ... } }
   ============================================================ */

require_once 'config.php';

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

    $stmt = $db->prepare('INSERT IGNORE INTO scores (player_name, score, games_played, created_at, updated_at) VALUES (:name, 0, 0, NOW(), NOW())');
    $stmt->execute(array(':name' => $name));
    $created = $stmt->rowCount() > 0;

    $select = $db->prepare('SELECT player_name, score, games_played, created_at, updated_at FROM scores WHERE player_name = :name LIMIT 1');
    $select->execute(array(':name' => $name));
    $player = $select->fetch(PDO::FETCH_ASSOC);

    jsonResponse(array('success' => true, 'created' => $created, 'player' => $player));
} catch (Exception $e) {
    errorResponse('Server error', $e);
}
