<?php
/* ============================================================
   add_score.php
   Afegeix una partida acabada. Si el jugador ja existeix,
   actualitza la puntuació màxima i incrementa games_played.
   
   POST JSON: { "player": "NomJugador", "score": 1500 }
   Resposta:  { "success": true, "best_score": 1500 }
   ============================================================ */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body  = getJsonBody();
$name  = isset($body['player']) ? sanitizeName($body['player']) : '';
$score = isset($body['score'])  ? intval($body['score'])        : -1;

if (!$name || strlen($name) < 2) {
    jsonResponse(array('success' => false, 'error' => 'Invalid player name'));
}
if ($score < 0 || $score > 9999999) {
    jsonResponse(array('success' => false, 'error' => 'Invalid score'));
}

$db = getDB();
try {
    $stmt = $db->prepare(
        'INSERT INTO scores (player_name, score, games_played, created_at, updated_at)
         VALUES (:name, :score, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
           score = GREATEST(score, :score),
           games_played = games_played + 1,
           updated_at = NOW()'
    );
    $stmt->execute(array(':name' => $name, ':score' => $score));

    $row = $db->prepare('SELECT score FROM scores WHERE player_name = :name');
    $row->execute(array(':name' => $name));
    $best = (int) $row->fetchColumn();

    jsonResponse(array('success' => true, 'best_score' => $best));
} catch (Exception $e) {
    errorResponse('Server error', $e);
}