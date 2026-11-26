<?php
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';

requireLogin();

// $forPdf lo define ticket_pdf.php cuando incluye esta plantilla
$forPdf = $forPdf ?? false;

$user_id = $_SESSION["user"]["id"];
$usuario = $_SESSION["user"]["username"];
$fecha_generacion = date("d/m/Y H:i");
$ticket_id = $_GET["id"] ?? null;

if (!$ticket_id) {
    notFound("Ticket no encontrado");
}

// Datos del ticket
$stmt = $db->prepare("
    SELECT t.*, u.username AS assigned_name
    FROM tickets t
    LEFT JOIN users u ON u.id = t.assigned_to
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    notFound("Ticket no encontrado");
}

// Registrar exportación en auditoría
logExport($db, $user_id, $forPdf ? "TICKET_PDF" : "TICKET_HTML");

// Comentarios del ticket
$stmt = $db->prepare("
    SELECT c.comment, c.created_at, u.username
    FROM comments c
    LEFT JOIN users u ON u.id = c.user_id
    WHERE c.ticket_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$ticket_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Historial de cambios
$stmt = $db->prepare("
    SELECT h.field_changed, h.old_value, h.new_value, h.created_at, u.username
    FROM ticket_history h
    LEFT JOIN users u ON u.id = h.user_id
    WHERE h.ticket_id = ?
    ORDER BY h.created_at ASC
");
$stmt->execute([$ticket_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Adjuntos
$stmt = $db->prepare("SELECT file_name, mime_type, created_at FROM ticket_attachments WHERE ticket_id = ?");
$stmt->execute([$ticket_id]);
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Ticket #<?= htmlspecialchars($ticket["id"]) ?></title>
    <style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 40px; 
        color: #333;
    }

    h1, h2 { margin-bottom: 5px; }

    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 20px; 
    }

    th, td { 
        border: 1px solid #ccc; 
        padding: 8px; 
    }

    th { background-color: #f0f0f0; }

    .section { margin-bottom: 30px; }

    /* 👇 NUEVO */
    .report-header {
        border-bottom: 2px solid #000;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }

    .report-header h1 {
        margin: 0;
        font-size: 28px;
    }

    .report-meta {
        margin-top: 10px;
        font-size: 14px;
    }

    .report-meta p {
        margin: 3px 0;
    }

    .footer {
        margin-top: 40px;
        font-size: 12px;
        color: #777;
        border-top: 1px solid #ccc;
        padding-top: 10px;
    }
</style>
</head>
<body>

<div class="report-header">
    <h1>SecureDesk DAM</h1>
    <p><strong>Informe de Ticket</strong></p>

    <div class="report-meta">
        <p><strong>Generado por:</strong> <?= htmlspecialchars($usuario) ?></p>
        <p><strong>Fecha:</strong> <?= $fecha_generacion ?></p>
        <p><strong>Ticket ID:</strong> #<?= htmlspecialchars($ticket["id"]) ?></p>
    </div>
</div>

<div class="section">
    <h2>Datos del Ticket</h2>
    <table>
        <tr><th>Título</th><td><?= htmlspecialchars($ticket["title"]) ?></td></tr>
        <tr><th>Estado</th><td><?= htmlspecialchars($ticket["status"]) ?></td></tr>
        <tr><th>Prioridad</th><td><?= htmlspecialchars($ticket["priority"]) ?></td></tr>
        <tr><th>Asignado a</th><td><?= htmlspecialchars($ticket["assigned_name"] ?? "Sin asignar") ?></td></tr>
        <tr><th>Creado</th><td><?= htmlspecialchars($ticket["created_at"]) ?></td></tr>
        <tr><th>Última actualización</th><td><?= htmlspecialchars($ticket["updated_at"] ?? "-") ?></td></tr>
    </table>
</div>

<div class="section">
    <h2>Comentarios</h2>
    <table>
        <tr>
            <th>Usuario</th>
            <th>Comentario</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($comments as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c["username"] ?? "Desconocido") ?></td>
            <td><?= htmlspecialchars($c["comment"]) ?></td>
            <td><?= htmlspecialchars($c["created_at"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="section">
    <h2>Historial de Cambios</h2>
    <table>
        <tr>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($history as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h["username"] ?? "Desconocido") ?></td>
            <td><?= htmlspecialchars($h["field_changed"] . ": " . ($h["old_value"] ?? "-") . " → " . ($h["new_value"] ?? "-")) ?></td>
            <td><?= htmlspecialchars($h["created_at"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="section">
    <h2>Archivos Adjuntos</h2>
    <table>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Subido</th>
        </tr>
        <?php foreach ($attachments as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a["file_name"]) ?></td>
            <td><?= htmlspecialchars($a["mime_type"] ?? "-") ?></td>
            <td><?= htmlspecialchars($a["created_at"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<div class="footer">
    Documento generado automáticamente por SecureDesk DAM
</div>
<?php if (!$forPdf): ?>
<button onclick="window.print()">Imprimir Informe</button>
<?php endif; ?>

</body>
</html>