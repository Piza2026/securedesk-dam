<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION["user"]["role"] ?? null;
$current = basename($_SERVER["SCRIPT_NAME"] ?? "");

if (!function_exists("navLink")) {
    function navLink($href, $text, $current, $class = "") {
        $classes = trim(($href === $current ? "active " : "") . $class);
        $attr = $classes ? ' class="' . $classes . '"' : "";
        echo '<a href="' . $href . '"' . $attr . '>' . $text . '</a>';
    }
}
?>
<header class="site-header">
    <h1><a href="index.php">SecureDesk DAM</a></h1>
</header>
<nav class="site-nav">
<?php if ($role): ?>
    <?php navLink("dashboard.php", "Inicio", $current); ?>
    <?php navLink("tickets.php", "Ver Tickets", $current); ?>
    <?php if ($role == "admin" || $role == "tecnico") navLink("ticket_create.php", "Crear Ticket", $current); ?>
    <?php if ($role == "admin"): ?>
        <?php navLink("users.php", "Usuarios", $current); ?>
        <?php navLink("audit.php", "Auditoría", $current); ?>
        <?php navLink("export_csv.php", "Export CSV", $current); ?>
    <?php endif; ?>
    <?php navLink("mi_cuenta.php", "Mi Cuenta", $current); ?>
    <?php navLink("logout.php", "Cerrar Sesión", $current, "logout"); ?>
<?php else: ?>
    <?php navLink("index.php", "Home", $current); ?>
    <?php navLink("login.php", "Login", $current); ?>
    
<?php endif; ?>
</nav>
