<?php

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/audit_helper.php';

// proteger acceso
requireLogin();

$user = $_SESSION["user"];
$user_id = $user["id"];
$role = $user["role"];

// auditoría
logAudit($db, $user_id, "ACCESS_DASHBOARD", "dashboard", null);

// total tickets
$totalTickets = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

// categorías
$resultCategorias = $db->query("
    SELECT category, COUNT(*) as total 
    FROM tickets 
    GROUP BY category
");

$categorias = [];
while ($row = $resultCategorias->fetch(PDO::FETCH_ASSOC)) {
    $categorias[$row['category']] = $row['total'];
}

// estados
$resultEstados = $db->query("
    SELECT status, COUNT(*) as total
    FROM tickets
    GROUP BY status
");

$estados = [
    'nuevo' => 0,
    'en_proceso' => 0,
    'resuelto' => 0
];

while ($row = $resultEstados->fetch(PDO::FETCH_ASSOC)) {
    $estados[$row['status']] = $row['total'];
}

// prioridades
$resultPrioridades = $db->query("
    SELECT priority, COUNT(*) as total
    FROM tickets
    GROUP BY priority
");

$prioridades = [
    'baja' => 0,
    'media' => 0,
    'alta' => 0,
    'critica' => 0
];

while ($row = $resultPrioridades->fetch(PDO::FETCH_ASSOC)) {
    $prioridades[$row['priority']] = $row['total'];
}

$fechaActual = date("d/m/Y H:i:s");
?>

<?php
$pageTitle = "Panel";
require __DIR__ . '/../views/layout_start.php';
?>

<h2 class="page-title">Bienvenido, <?= htmlspecialchars($user["username"]) ?></h2>
<p class="subtitle">Rol: <span class="badge"><?= htmlspecialchars($role) ?></span> · Última actualización: <?= $fechaActual ?></p>

<div class="actions">
    <?php if ($role !== "lector"): ?>
        <a class="btn" href="ticket_create.php">+ Crear Ticket</a>
    <?php endif; ?>
    <a class="btn btn-light" href="tickets.php">Ver Tickets</a>
    <a class="btn btn-light" href="tickets.php?filtro=criticos">Tickets críticos</a>
    <a class="btn btn-light" href="tickets.php?filtro=sin_asignar">Sin asignar</a>
    <a class="btn btn-light" href="export_csv.php">Exportar CSV</a>
    <?php if ($role === "admin"): ?>
        <a class="btn btn-light" href="users.php">Gestionar Usuarios</a>
        <a class="btn btn-light" href="audit.php">Ver Auditoría</a>
    <?php endif; ?>
</div>

<div class="grid">
    <div class="card stat"><span class="num"><?= $totalTickets ?></span><span class="label">Total tickets</span></div>
    <div class="card stat"><span class="num"><?= $estados['nuevo'] ?></span><span class="label">Nuevos</span></div>
    <div class="card stat"><span class="num"><?= $estados['en_proceso'] ?></span><span class="label">En proceso</span></div>
    <div class="card stat"><span class="num"><?= $estados['resuelto'] ?></span><span class="label">Resueltos</span></div>
</div>

<div class="grid">
    <div class="card">
        <h3>Por categoría</h3>
        <div class="table-wrap">
        <table>
            <tr><th>Categoría</th><th>Total</th></tr>
            <?php foreach ($categorias as $cat => $total): ?>
            <tr>
                <td><?= htmlspecialchars($cat ?: "Sin categoría") ?></td>
                <td><?= $total ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>

    <div class="card">
        <h3>Por estado</h3>
        <div class="table-wrap">
        <table>
            <tr><th>Estado</th><th>Total</th></tr>
            <?php foreach ($estados as $estado => $total): ?>
            <tr>
                <td><span class="badge badge-<?= htmlspecialchars($estado) ?>"><?= htmlspecialchars($estado) ?></span></td>
                <td><?= $total ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>

    <div class="card">
        <h3>Por prioridad</h3>
        <div class="table-wrap">
        <table>
            <tr><th>Prioridad</th><th>Total</th></tr>
            <?php foreach ($prioridades as $p => $total): ?>
            <tr>
                <td><span class="badge badge-<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></span></td>
                <td><?= $total ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>
