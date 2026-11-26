<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

// Genera el HTML del informe (ticket_report.php comprueba sesión y ticket)
$forPdf = true;
ob_start();
include __DIR__ . '/ticket_report.php';
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ticket_" . (int)$ticket["id"] . ".pdf", ["Attachment" => true]);
