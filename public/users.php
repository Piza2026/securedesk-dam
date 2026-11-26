<?php
require "../app/auth.php";
requireRole(["admin"]);

$pageTitle = "Usuarios";
require __DIR__ . '/../views/layout_start.php';
?>

<h2 class="page-title">Zona Usuarios</h2>
<div class="card">
    <p class="subtitle" style="margin:0">Sección de gestión de usuarios (en construcción).</p>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>
