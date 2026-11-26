<?php
session_start();

require "../app/auth.php";
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../includes/error_handler.php';


requireRole(["admin", "tecnico"]);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        forbidden("Token inválido");
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $priority = $_POST["priority"] ?? "";
    $category = trim($_POST["category"] ?? "");
    $assigned_to = $_POST["assigned_to"] !== "" ? (int)$_POST["assigned_to"] : null;

    if ($title === "") $errors[] = "Título obligatorio";
    if ($priority === "") $errors[] = "Prioridad obligatoria";

    $valid = ["baja", "media", "alta"];
    if (!in_array($priority, $valid)) {
        $errors[] = "Prioridad inválida";
    }

    if (empty($errors)) {

        $stmt = $db->prepare("
            INSERT INTO tickets
            (title, description, status, priority, category, created_by, assigned_to, created_at, updated_at)
            VALUES (?, ?, 'nuevo', ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");

        $stmt->execute([
            $title,
            $description,
            $priority,
            $category,
            $_SESSION["user"]["id"],
            $assigned_to
        ]);

        header("Location: tickets.php");
        exit();
    }
}

// usuarios técnicos
$stmtUsers = $db->prepare("SELECT id, username FROM users WHERE role='tecnico'");
$stmtUsers->execute();
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$pageTitle = "Crear Ticket";
require __DIR__ . '/../views/layout_start.php';
?>

<h2 class="page-title">Crear Ticket</h2>
<p class="subtitle">Registra una nueva incidencia.</p>

<div class="card" style="max-width:700px">
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

        <label for="title">Título</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($_POST["title"] ?? "") ?>">

        <label for="description">Descripción</label>
        <textarea id="description" name="description"><?= htmlspecialchars($_POST["description"] ?? "") ?></textarea>

        <label for="category">Categoría</label>
        <input type="text" id="category" name="category" value="<?= htmlspecialchars($_POST["category"] ?? "") ?>">

        <label for="priority">Prioridad</label>
        <select id="priority" name="priority">
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
        </select>

        <label for="assigned_to">Asignado a</label>
        <select id="assigned_to" name="assigned_to">
            <option value="">Nadie</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u["id"] ?>"><?= htmlspecialchars($u["username"]) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-block">Crear ticket</button>
    </form>
</div>

<?php require __DIR__ . '/../views/layout_end.php'; ?>
