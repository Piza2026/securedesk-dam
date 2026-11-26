<?php
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';

requireLogin();

$id = $_GET["id"] ?? null;

if (!$id) {
    notFound("Archivo no encontrado");
}

$stmt = $db->prepare("SELECT * FROM ticket_attachments WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    notFound("Archivo no encontrado");
}

$path = $file["file_path"];

if (!file_exists($path)) {
    notFound("El archivo ya no existe en el servidor");
}

header("Content-Type: " . ($file["mime_type"] ?: "application/octet-stream"));
header("Content-Disposition: attachment; filename=\"" . basename($file["file_name"]) . "\"");
header("Content-Length: " . filesize($path));

readfile($path);
exit();