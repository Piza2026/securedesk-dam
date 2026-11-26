<?php
/**
 * Carga datos de demostración en la base de datos.
 * Uso (desde la carpeta del proyecto):  php db/seed_demo.php
 *
 * ATENCIÓN: borra los tickets, comentarios, historial, adjuntos y
 * registros de auditoría existentes. Los usuarios se mantienen.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se puede ejecutar desde la línea de comandos.");
}

require_once __DIR__ . '/config.php';

// Asegura los usuarios de prueba (admin / tecnico / lector, contraseña 1234)
ob_start();
require __DIR__ . '/seed_users.php';
ob_end_clean();

$ids = [];
foreach ($db->query("SELECT id, username FROM users") as $u) {
    $ids[$u["username"]] = (int)$u["id"];
}
$admin = $ids["admin"];
$tec   = $ids["tecnico"];

$db->beginTransaction();

foreach (["comments", "ticket_history", "ticket_attachments", "attachments", "tickets", "audit_logs", "login_attempts"] as $t) {
    $db->exec("DELETE FROM $t");
    $db->exec("DELETE FROM sqlite_sequence WHERE name = '$t'");
}

// [título, descripción, estado, prioridad, categoría, creado_por, asignado, hace_dias]
$tickets = [
    ["No arranca el portátil de recepción", "El equipo de recepción se queda en la pantalla del logo al encender. Probado a reiniciar varias veces.", "en_proceso", "alta", "Hardware", $admin, $tec, 6],
    ["Alta de usuario para nueva incorporación", "Crear cuenta de correo y acceso a la intranet para la persona que se incorpora el lunes en Administración.", "resuelto", "media", "Cuentas", $admin, $tec, 12],
    ["La impresora de la 2ª planta no imprime", "Los trabajos se quedan en cola. El panel muestra 'Error de comunicación'.", "nuevo", "media", "Hardware", $tec, null, 1],
    ["VPN se desconecta cada pocos minutos", "Varios usuarios en teletrabajo reportan cortes de la VPN desde la actualización del cliente.", "en_proceso", "alta", "Red", $admin, $tec, 3],
    ["Solicitud de licencia de software de diseño", "El departamento de Marketing solicita una licencia adicional para la nueva diseñadora.", "nuevo", "baja", "Software", $admin, null, 2],
    ["Correo sospechoso recibido por varios empleados", "Phishing que suplanta al banco. Revisar si alguien ha hecho clic y bloquear el remitente.", "resuelto", "alta", "Seguridad", $admin, $tec, 9],
    ["Pantalla parpadea en sala de reuniones", "El monitor de la sala B parpadea al conectar portátiles por HDMI.", "nuevo", "baja", "Hardware", $tec, $tec, 0],
    ["Copia de seguridad nocturna fallida", "El job de backup del servidor de ficheros terminó con error anoche. Revisar espacio en disco.", "en_proceso", "media", "Sistemas", $admin, $tec, 1],
];

$ins = $db->prepare("
    INSERT INTO tickets (title, description, status, priority, category, created_by, assigned_to, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now', ?), datetime('now', ?))
");
foreach ($tickets as $t) {
    [$title, $desc, $status, $prio, $cat, $by, $to, $days] = $t;
    $ago = "-$days days";
    $ins->execute([$title, $desc, $status, $prio, $cat, $by, $to, $ago, $ago]);
}

$com = $db->prepare("INSERT INTO comments (ticket_id, user_id, comment, created_at) VALUES (?, ?, ?, datetime('now', ?))");
$com->execute([1, $tec, "Revisado: el disco da errores SMART. Pido uno de reemplazo.", "-5 days"]);
$com->execute([1, $admin, "Aprobado el pedido del disco. Avisad cuando llegue.", "-5 days"]);
$com->execute([4, $tec, "Reproducido con la versión 5.2 del cliente. Probando a volver a la 5.1.", "-2 days"]);
$com->execute([6, $tec, "Remitente bloqueado en el filtro de correo. Nadie introdujo credenciales.", "-8 days"]);
$com->execute([8, $tec, "El volumen de destino estaba al 98%. Liberando espacio antes del próximo backup.", "-0 days"]);

$his = $db->prepare("INSERT INTO ticket_history (ticket_id, user_id, field_changed, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, datetime('now', ?))");
$his->execute([1, $tec, "status", "nuevo", "en_proceso", "-5 days"]);
$his->execute([2, $tec, "status", "en_proceso", "resuelto", "-11 days"]);
$his->execute([6, $tec, "status", "en_proceso", "resuelto", "-8 days"]);

$db->commit();

echo "Datos de demostración cargados: " . count($tickets) . " tickets.\n";
