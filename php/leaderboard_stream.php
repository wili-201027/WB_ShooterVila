<?php
require_once 'config.php';
/* Server-Sent Events endpoint for live leaderboard updates. */
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Allow long-running
@set_time_limit(0);

$last = 0;
while (true) {
    try {
        $db = getDB();
        $modified = $db->query('SELECT MAX(UNIX_TIMESTAMP(updated_at)) FROM scores')->fetchColumn();
        $modified = intval($modified);
        if ($modified > $last) {
            $stmt = $db->query('SELECT player_name, score, games_played, updated_at FROM scores ORDER BY score DESC, updated_at ASC LIMIT 100');
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "data: " . json_encode(array('players' => $data)) . "\n\n";
            @ob_flush(); @flush();
            $last = $modified;
        }
    } catch (Exception $e) {
        echo "event: error\n";
        echo "data: {\"error\":\"server\"}\n\n";
        @ob_flush(); @flush();
    }
    sleep(2);
}
