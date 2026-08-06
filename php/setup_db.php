<?php
/* ============================================================
   setup_db.php
   Script helper para crear la base de datos, el usuario y
   aplicar db/schema.sql cuando la instalación es nueva.

   USO (CLI):
     php setup_db.php [root_user] [root_pass] [new_user_pass]

   USO (HTTP POST JSON):
     { "admin_password": "...", "root_user": "root", "root_pass": "", "new_user_pass": "..." }
   El endpoint requiere `admin_password` igual a `ADMIN_PASSWORD`.

   Nota: Script pensado para entornos de desarrollo (XAMPP). Usa con cuidado.
   ============================================================ */

require_once 'config.php';

function isCli() {
    return (php_sapi_name() === 'cli' || defined('STDIN'));
}

$options = array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
);

// Primero comprobamos si ya podemos conectar con las credenciales actuales
try {
    $dsn_check = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $test = new PDO($dsn_check, DB_USER, DB_PASS, $options);
    jsonResponse(array('success' => true, 'message' => 'Database already configured and reachable with current credentials.'));
} catch (PDOException $e) {
    // Seguimos con el proceso de creación
}

// Recolectar credenciales (CLI o HTTP)
if (isCli()) {
    global $argv;
    $root_user     = isset($argv[1]) ? $argv[1] : 'root';
    $root_pass     = isset($argv[2]) ? $argv[2] : '';
    $new_user_pass = isset($argv[3]) ? $argv[3] : 'vrgame_pass';
} else {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method not allowed', null, 405);
    }
    $body = getJsonBody();
    if (!isset($body['admin_password']) || $body['admin_password'] !== ADMIN_PASSWORD) {
        errorResponse('Unauthorized', null, 403);
    }
    $root_user     = isset($body['root_user']) ? $body['root_user'] : 'root';
    $root_pass     = isset($body['root_pass']) ? $body['root_pass'] : '';
    $new_user_pass = isset($body['new_user_pass']) ? $body['new_user_pass'] : 'vrgame_pass';
}

// Conectamos como root (o el usuario administrador que nos pasen)
$dsn_root = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
try {
    $rootPdo = new PDO($dsn_root, $root_user, $root_pass, $options);
} catch (PDOException $e) {
    errorResponse('Could not connect as root/admin user: ' . $e->getMessage(), $e);
}

try {
    // 1) Crear la base de datos si no existe
    $collate = DB_CHARSET . '_unicode_ci';
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . $collate);

    // 2) Crear el usuario de la aplicación y conceder permisos
    $quotedUser = $rootPdo->quote(DB_USER); // incluye comillas
    $quotedPass = $rootPdo->quote($new_user_pass);

    // CREATE USER IF NOT EXISTS (MySQL 5.7+); si falla, seguimos intentando GRANT que crea el usuario en versiones antiguas
    try {
        $rootPdo->exec("CREATE USER IF NOT EXISTS " . $quotedUser . "@'localhost' IDENTIFIED BY " . $quotedPass);
    } catch (Exception $inner) {
        // Ignorar; intentaremos GRANT
    }

    $rootPdo->exec("GRANT ALL PRIVILEGES ON `" . DB_NAME . "`.* TO " . $quotedUser . "@'localhost'");
    $rootPdo->exec('FLUSH PRIVILEGES');

    // 3) Importar esquema desde db/schema.sql
    $schemaPath = __DIR__ . '/../db/schema.sql';
    if (!file_exists($schemaPath)) {
        errorResponse('Schema file not found: ' . $schemaPath);
    }
    $sql = file_get_contents($schemaPath);

    // Limpiar comentarios de línea y dividir por ';'
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $lines = explode("\n", $sql);
    $clean = '';
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || strpos($t, '--') === 0 || strpos($t, '#') === 0) continue;
        $clean .= $line . "\n";
    }
    $parts = explode(';', $clean);
    foreach ($parts as $part) {
        $stmt = trim($part);
        if (!$stmt) continue;
        // Ejecutar cada instrucción; algunas son CREATE DATABASE o USE
        $rootPdo->exec($stmt);
    }

    jsonResponse(array('success' => true, 'message' => 'Database and user created, schema applied.'));
} catch (Exception $e) {
    errorResponse('Setup failed: ' . $e->getMessage(), $e);
}
