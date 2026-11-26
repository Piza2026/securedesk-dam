<?php

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/audit_helper.php';
require_once __DIR__ . '/../includes/error_handler.php';

// proteger acceso
requireLogin();

$user_id = $_SESSION["user"]["id"];

$status = $_GET["status"] ?? "";
$priority = $_GET["priority"] ?? "";
$filtro = $_GET["filtro"] ?? "";
$buscar = $_GET["buscar"] ?? "";

// log búsqueda
if (!empty($buscar)) {
    logAudit($db, $user_id, "SEARCH_TICKETS", "tickets", null, $buscar);
}

// query base
$sql = "SELECT t.*, u.username AS assigned_name
        FROM tickets t
        LEFT JOIN users u ON u.id = t.assigned_to
        WHERE 1=1";
$params = [];

// filtros
if ($status !== "") {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if ($priority !== "") {
    $sql .= " AND priority = ?";
    $params[] = $priority;
}

if ($filtro === "criticos") {
    $sql .= " AND priority = 'alta'";
}

if ($filtro === "sin_asignar") {
    $sql .= " AND assigned_to IS NULL";
}

if ($buscar !== "") {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$pageTitle = "Tickets";
require __DIR__ . '/../views/layout_start.php';
?>

<h2 class="page-title">Listado de Tickets</h2>
<p class="subtitle"><?= count($tickets) ?> ticket(s)</p>

<div class="actions">
    <a class="btn btn-light" href="?filtro=criticos">Críticos</a>
    <a class="btn btn-light" href="?filtro=sin_asignar">Sin asignar</a>
    <a class="btn btn-light" href="tickets.php">Todos</a>
    <a class="btn btn-light" href="export_csv.php">Export CSV</a>
</div>

<div class="card">
    <form method="GET" class="filters">
        <input type="text" name="buscar" placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>">

        <select name="status">
            <option value="">Estado</option>
            <option value="nuevo" <?= $status=="nuevo"?"selected":"" ?>>Nuevo</option>
            <option value="en_proceso" <?= $status=="en_proceso"?"selected":"" ?>>En proceso</option>
            <option value="resuelto" <?= $status=="resuelto"?"selected":"" ?>>Resuelto</option>
        </select>

        <select name="priority">
            <option value="">Prioridad</option>
            <option value="baja" <?= $priority=="baja"?"selected":"" ?>>Baja</option>
            <option value="media" <?= $priority=="media"?"selected":"" ?>>Media</option>
            <option value="alta" <?= $priority=="alta"?"selected":"" ?>>Alta</option>
        </select>

        <button type="submit">Filtrar</button>
    </form>
</div>

<div class="card table-wrap" style="padding:0">
<table>
<tr>
    <th>Título</th>
    <th>Estado</th>
    <th>Prioridad</th>
    <th>Asignado</th>
    <th>Fecha</th>
    <th>Acciones</th>
</tr>

<?php if ($tickets): ?>
    <?php foreach ($tickets as $t): ?>
    <tr>
        <td><?= htmlspecialchars($t["title"]) ?></td>
        <td><span class="badge badge-<?= htmlspecialchars($t["status"]) ?>"><?= htmlspecialchars($t["status"]) ?></span></td>
        <td><span class="badge badge-<?= htmlspecialchars($t["priority"]) ?>"><?= htmlspecialchars($t["priority"]) ?></span></td>
        <td><?= htmlspecialchars($t["assigned_name"] ?? "Sin asignar") ?></td>
        <td><?= htmlspecialchars($t["created_at"]) ?></td>
        <td><a href="ticket_detail.php?id=<?= $t["id"] ?>">Ver</a></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="6">No hay tickets</td>
</tr>
<?php endif; ?>
</table>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>
