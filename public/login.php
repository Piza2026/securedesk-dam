<?php
session_start();

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/audit_helper.php';
require_once __DIR__ . '/../includes/error_handler.php';

$error = "";
$maxIntentos = 5;      // fallos permitidos antes de bloquear
$minutosBloqueo = 15;  // duración del bloqueo

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

        // 1. Consultar los intentos fallidos de este usuario
        $stmt = $db->prepare("
            SELECT attempts,
                   blocked_until > datetime('now') AS bloqueado,
                   blocked_until IS NOT NULL      AS tuvo_bloqueo
            FROM login_attempts
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $intento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($intento && $intento["bloqueado"]) {

            // 2. Usuario bloqueado: no se comprueba ni la contraseña
            logAudit($db, null, "LOGIN_BLOCKED", "user", null, $username);
            $error = "Demasiados intentos fallidos. Inténtalo de nuevo en $minutosBloqueo minutos.";

        } else {

            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password_hash"])) {

                // 3. Login correcto: nueva sesión y contador a cero
                session_regenerate_id(true);

                $_SESSION["user"] = [
                    "id" => $user["id"],
                    "username" => $user["username"],
                    "role" => $user["role"]
                ];
                $_SESSION["last_activity"] = time();

                $stmt = $db->prepare("DELETE FROM login_attempts WHERE username = ?");
                $stmt->execute([$username]);

                logAudit($db, $user["id"], "LOGIN_OK", "user", $user["id"], $ip);

                header("Location: dashboard.php");
                exit();

            } else {

                // 4. Login fallido: sumar un intento (o empezar de 1 si ya pasó un bloqueo)
                $intentos = ($intento && !$intento["tuvo_bloqueo"]) ? $intento["attempts"] + 1 : 1;
                $bloquear = $intentos >= $maxIntentos ? 1 : 0;

                if ($intento) {
                    $stmt = $db->prepare("
                        UPDATE login_attempts
                        SET attempts = ?, ip_address = ?, last_attempt = datetime('now'),
                        blocked_until = CASE WHEN CAST(? AS INTEGER) = 1 THEN datetime('now', ?) ELSE NULL END
                        WHERE username = ?
                    ");
                    $stmt->execute([$intentos, $ip, $bloquear, "+$minutosBloqueo minutes", $username]);
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO login_attempts (username, ip_address, attempts, last_attempt)
                        VALUES (?, ?, 1, datetime('now'))
                    ");
                    $stmt->execute([$username, $ip]);
                }

                logAudit($db, null, "LOGIN_FAIL", "user", null, $username);

                $error = $bloquear
                    ? "Demasiados intentos fallidos. Inténtalo de nuevo en $minutosBloqueo minutos."
                    : "Credenciales incorrectas";
            }
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