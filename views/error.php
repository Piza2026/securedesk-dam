<?php
$pageTitle = "Error";
$containerClass = "narrow";
require __DIR__ . '/layout_start.php';
?>
<div class="card" style="text-align:center">
    <h2 style="color:var(--danger)">Error <?php echo http_response_code(); ?></h2>
    <p><?php echo htmlspecialchars($message); ?></p>
    <a class="btn" href="dashboard.php">Volver</a>
</div>
<?php require __DIR__ . '/layout_end.php'; ?>
