<?php
require_once __DIR__ . '/../app/auth.php';

requireLogin();

$pageTitle = "Mi Cuenta";
$containerClass = "narrow";
require __DIR__ . '/../views/layout_start.php';
?>

<div class="card">
    <h2>Mi Cuenta</h2>
    <dl class="details">
        <dt>Usuario</dt><dd><?= htmlspecialchars($_SESSION["user"]["username"]) ?></dd>
        <dt>Rol</dt><dd><span class="badge"><?= htmlspecialchars($_SESSION["user"]["role"]) ?></span></dd>
    </dl>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>
