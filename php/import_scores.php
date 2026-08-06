<?php
/* ============================================================
   import_scores.php
   Importació massiva de puntuacions des d'un JSON.
   ⚠️ ACCIÓ ADMIN - Requereix autenticació de sessió.

   POST JSON: { "players": [ { "player": "Name", "score": 1200, "games_played": 1 }, ... ] }

   Lògica: per cada entrada, si el jugador NO existeix -> s'insereix.
          si existeix -> s'actualitza la puntuació només si la nova és millor; sempre s'afegeix games_played.

   Resposta resum: { success: true, inserted: n, updated_better: m, updated_not_better: k, skipped: x }
   ============================================================ */

require_once 'config.php';
checkAdminSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

$body = getJsonBody();
$players = array();

if (isset($body['players']) && is_array($body['players'])) {
    $players = $body['players'];
} elseif (is_array($body) && count($body) && isset($body[0])) {
    // raw array
    $players = $body;
} elseif (isset($body['player']) && isset($body['score'])) {
    // single entry
    $players = array($body);
} else {
    jsonResponse(array('success' => false, 'error' => 'Invalid payload'));
}

$db = getDB();
$inserted = $updated_better = $updated_not_better = $skipped = 0;

try {
    $db->beginTransaction();

    $select = $db->prepare('SELECT score FROM scores WHERE player_name = :name LIMIT 1');
    $insert = $db->prepare('INSERT INTO scores (player_name, score, games_played, created_at, updated_at) VALUES (:name, :score, :games_played, NOW(), NOW())');
    $update_better = $db->prepare('UPDATE scores SET score = :score, games_played = games_played + :games_played, updated_at = NOW() WHERE player_name = :name');
    $update_not_better = $db->prepare('UPDATE scores SET games_played = games_played + :games_played, updated_at = NOW() WHERE player_name = :name');

    foreach ($players as $p) {
        $name = '';
        if (isset($p['player'])) $name = sanitizeName($p['player']);
        if (isset($p['player_name']) && !$name) $name = sanitizeName($p['player_name']);

        $score = isset($p['score']) ? intval($p['score']) : null;
        $games = isset($p['games_played']) ? intval($p['games_played']) : 1;

        if (!$name || strlen($name) < 2 || !is_int($score) || $score < 0) { $skipped++; continue; }

        $select->execute(array(':name' => $name));
        $existing = $select->fetchColumn();

        if ($existing === false) {
            $insert->execute(array(':name' => $name, ':score' => $score, ':games_played' => max(1, $games)));
            $inserted++;
        } else {
            $existing = (int)$existing;
            if ($score > $existing) {
                $update_better->execute(array(':score' => $score, ':games_played' => max(0, $games), ':name' => $name));
                $updated_better++;
            } else {
                // no millora la puntuació, però s'afegeix games_played
                $update_not_better->execute(array(':games_played' => max(0, $games), ':name' => $name));
                $updated_not_better++;
            }
        }
    }

    $db->commit();
    jsonResponse(array(
        'success' => true,
        'inserted' => $inserted,
        'updated_better' => $updated_better,
        'updated_not_better' => $updated_not_better,
        'skipped' => $skipped
    ));
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    jsonResponse(array('success' => false, 'error' => 'Server error'));
}
