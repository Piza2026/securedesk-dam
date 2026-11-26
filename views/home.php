<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION["user"] ?? null;

$pageTitle = "Inicio";
require __DIR__ . '/layout_start.php';
?>

<div class="card" style="text-align:center; padding:36px 20px">
    <h2 class="page-title">Gestión de incidencias segura</h2>
    <p class="subtitle">SecureDesk DAM es una aplicación para crear, seguir y auditar tickets de soporte, con control de acceso por roles.</p>
    <?php if ($user): ?>
        <p>Has iniciado sesión como <strong><?= htmlspecialchars($user["username"]) ?></strong> (<?= htmlspecialchars($user["role"]) ?>).</p>
        <a class="btn" href="dashboard.php">Ir al panel</a>
    <?php else: ?>
        <a class="btn" href="login.php">Iniciar sesión</a>
    <?php endif; ?>
</div>

<div class="grid">
    <div class="card">
        <h3>🎫 Tickets</h3>
        <p>Crea incidencias, adjunta archivos y consulta su estado y detalle.</p>
    </div>
    <div class="card">
        <h3>👥 Roles</h3>
        <p>Permisos distintos para administrador, técnico y lector.</p>
    </div>
    <div class="card">
        <h3>📋 Auditoría</h3>
        <p>Registro de accesos y acciones, y exportación a CSV y PDF.</p>
    </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
