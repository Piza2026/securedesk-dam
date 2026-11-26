<?php

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../includes/error_handler.php';

// solo admin
requireRole(["admin"]);

$user_filter = $_GET["user_id"] ?? "";
$action_filter = $_GET["action"] ?? "";

$sql = "SELECT audit_logs.*, users.username 
        FROM audit_logs
        LEFT JOIN users ON audit_logs.user_id = users.id
        WHERE 1=1";

$params = [];

if ($user_filter !== "") {
    $sql .= " AND audit_logs.user_id = ?";
    $params[] = $user_filter;
}

if ($action_filter !== "") {
    $sql .= " AND audit_logs.action LIKE ?";
    $params[] = "%$action_filter%";
}

$sql .= " ORDER BY audit_logs.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// usuarios para filtro
$users = $db->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$pageTitle = "Auditoría";
require __DIR__ . '/../views/layout_start.php';
?>

<h2 class="page-title">Panel de Auditoría</h2>
<p class="subtitle"><?= count($logs) ?> registro(s)</p>

<div class="card">
    <form method="GET" class="filters">
        <select name="user_id">
            <option value="">Todos los usuarios</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u["id"] ?>" <?= ($user_filter == $u["id"]) ? "selected" : "" ?>>
                    <?= htmlspecialchars($u["username"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="action" placeholder="Acción (ej. LOGIN)" value="<?= htmlspecialchars($action_filter) ?>">

        <button type="submit">Filtrar</button>
    </form>
</div>

<div class="card table-wrap" style="padding:0">
<table>
<tr>
    <th>Fecha</th>
    <th>Usuario</th>
    <th>Acción</th>
    <th>Entidad</th>
    <th>ID</th>
</tr>

<?php if ($logs): ?>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= htmlspecialchars($log["created_at"]) ?></td>
            <td><?= htmlspecialchars($log["username"] ?? "Sistema") ?></td>
            <td><?= htmlspecialchars($log["action"]) ?></td>
            <td><?= htmlspecialchars($log["entity"] ?? "-") ?></td>
            <td><?= htmlspecialchars($log["entity_id"] ?? "-") ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="5">No hay registros</td></tr>
<?php endif; ?>
</table>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>
