<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db/config.php';
require "../app/auth.php";
require "../app/audit_helper.php";
require_once __DIR__ . '/../includes/error_handler.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        showError(403, "Token CSRF inválido");
    }

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Usuario y contraseña obligatorios";
    } else {

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {

            $_SESSION["user"] = [
                "id" => $user["id"],
                "username" => $user["username"],
                "role" => $user["role"]
            ];
            $_SESSION["last_activity"] = time();

            // limpiar intentos
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE username = ?");
            $stmt->execute([$username]);

            logAudit($db, $user["id"], "LOGIN_OK", "user", $user["id"], $ip);

            header("Location: dashboard.php");
            exit();

        } else {

            logAudit($db, null, "LOGIN_FAIL", "user", null, $username);

            $error = "Credenciales incorrectas";
        }
    }
}
?>

<?php
$pageTitle = "Login";
$containerClass = "narrow";
require __DIR__ . '/../views/layout_start.php';
?>

<div class="card">
    <h2>Iniciar sesión</h2>

    <?php if (isset($_GET["error"]) && $_GET["error"] === "timeout"): ?>
        <div class="alert alert-error">Sesión expirada por inactividad</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

        <label for="username">Usuario</label>
        <input type="text" id="username" name="username" autocomplete="username" value="<?= htmlspecialchars($_POST["username"] ?? "") ?>" autofocus>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" autocomplete="current-password">

        <button type="submit" class="btn-block">Entrar</button>
    </form>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>