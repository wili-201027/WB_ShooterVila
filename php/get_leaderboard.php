<?php
/* ============================================================
   get_leaderboard.php
   Retorna la classificació ordenada per puntuació.

   GET parameters:
     - limit (optional): nombre màxim de jugadors a retornar (0 = tots)

   Resposta: { "success": true, "players": [ { player_name, score, games_played, updated_at }, ... ] }
   ============================================================ */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(array('success' => false, 'error' => 'Method not allowed'));
}

// Rate limiting (basic)
if (!checkRateLimit((isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'unknown') . '_get_leaderboard', 60, 60)) {
    http_response_code(429);
    jsonResponse(array('success' => false, 'error' => 'Too many requests'));
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
$perPage = max(1, min($perPage, 500)); // cap
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();

    $cacheKey = 'leaderboard_p' . $page . '_pp' . $perPage;
    $cached = getCache($cacheKey, 30); // 30s cache
    if ($cached !== false) {
        jsonResponse(array('success' => true, 'players' => $cached, 'page' => $page, 'per_page' => $perPage));
    }

    $stmt = $db->prepare('SELECT player_name, score, games_played, updated_at FROM scores ORDER BY score DESC, updated_at ASC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // total count for pagination
    $total = (int) $db->query('SELECT COUNT(*) FROM scores')->fetchColumn();
    $pages = (int) ceil($total / $perPage);

    setCache($cacheKey, $players);

    jsonResponse(array('success' => true, 'players' => $players, 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => $pages));
} catch (Exception $e) {
    errorResponse('Server error', $e);
}