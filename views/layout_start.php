<?php
// Uso: $pageTitle = "..."; require __DIR__ . '/../views/layout_start.php';
// Opcional: $containerClass = "narrow";
$pageTitle = $pageTitle ?? "SecureDesk DAM";
$containerClass = $containerClass ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> · SecureDesk DAM</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>
<main class="container <?= htmlspecialchars($containerClass) ?>">
