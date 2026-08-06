<?php
/* ============================================================
   config.php — Configuració de la Base de Dades
   ============================================================
   Canvia les constants per les dades del teu servidor.
   IMPORTANT: Aquest fitxer NO ha d'estar accessible des del web.
              Afegeix-lo a .htaccess o col·loca'l fora del webroot.
   ============================================================ */

define('DB_HOST', 'localhost');
define('DB_NAME', 'vrgame_db');
define('DB_USER', 'vrgame_user');
define('DB_PASS', 'vrgame_secure_pass_2024');
define('DB_CHARSET', 'utf8mb4');

/* Contrasenya per a accions d'admin destructives (reset total) */
define('ADMIN_PASSWORD', 'admin_secure_pass_2024_change_me');

/* Mode debug temporal: posa a true per mostrar detalls d'excepcions en JSON */
define('DEBUG', false);
if (defined('DEBUG') && DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function errorResponse($msg, $e = null, $code = 500) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'null'));
    http_response_code($code);
    $payload = array('success' => false, 'error' => $msg);
    if (defined('DEBUG') && DEBUG && $e instanceof Exception) {
        $payload['error_detail'] = $e->getMessage();
        $payload['error_trace'] = $e->getTraceAsString();
    }
    echo json_encode($payload);
    exit;
}

function checkAdminSession() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        http_response_code(403);
        errorResponse('Admin authentication required', null, 403);
    }
}

/* ── Crear connexió PDO ─────────────────────────────────── */
function getDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    );
    // Comprovar que l'extensió PDO està disponible
    if (!class_exists('PDO')) {
        errorResponse('PDO extension not available');
    }
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        errorResponse('DB connection failed', $e);
    } catch (Exception $e) {
        errorResponse('Server error', $e);
    }
}

/* ── Helpers ─────────────────────────────────────────────── */
function jsonResponse($data) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'null'));
    echo json_encode($data);
    exit;
}

function getJsonBody() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : array();
}

function sanitizeName($name) {
    $name = trim($name);
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    return substr($name, 0, 24); // max 24 chars
}

/* ── Validación y utilidades adicionales ─────────────────────────── */
function validateInput($type, $value) {
    switch ($type) {
        case 'username':
            $v = trim($value);
            if (strlen($v) < 2 || strlen($v) > 24) return false;
            if (!preg_match('/^[\p{L}0-9 _\-]+$/u', $v)) return false;
            return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        case 'score':
            $n = intval($value);
            if ($n < 0 || $n > 9999999) return false;
            return $n;
        case 'points':
            $n = intval($value);
            if ($n <= 0 || $n > 9999999) return false;
            return $n;
        default:
            return false;
    }
}

function auditLog($action, $target, $result, $user = null) {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/audit.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
    $entry = sprintf("[%s] IP:%s User:%s Action:%s Target:%s Result:%s\n", $timestamp, $ip, $user ?: 'SYSTEM', $action, $target, $result);
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

function checkRateLimit($key, $maxRequests = 30, $window = 60) {
    $dir = sys_get_temp_dir() . '/vr_ratelimit';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $bucket = $dir . '/' . md5($key) . '.json';
    $now = time();
    $data = array('ts' => $now, 'count' => 0);
    if (file_exists($bucket)) {
        $raw = @file_get_contents($bucket);
        $d = @json_decode($raw, true);
        if (is_array($d)) $data = $d;
    }
    // Reset window
    if ($now - $data['ts'] >= $window) {
        $data['ts'] = $now;
        $data['count'] = 0;
    }
    $data['count'] = isset($data['count']) ? $data['count'] + 1 : 1;
    @file_put_contents($bucket, json_encode($data), LOCK_EX);
    return $data['count'] <= $maxRequests;
}

function getCache($key, $ttl = 60) {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/' . md5($key) . '.json';
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        $raw = @file_get_contents($file);
        return json_decode($raw, true);
    }
    return false;
}

function setCache($key, $data) {
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/' . md5($key) . '.json';
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

function getCsrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(24));
        }
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Enable gzip output when supported (best-effort)
if (!headers_sent() && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
    if (!ini_get('zlib.output_compression')) {
        @ob_start('ob_gzhandler');
    }
}
