<?php
$dbPath = __DIR__ . "/securedesk.sqlite";

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    die("Error de conexión a la base de datos");
}

date_default_timezone_set('Europe/Madrid');

