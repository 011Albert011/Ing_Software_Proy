<?php
// Recibimos el nombre del archivo que ya se creo anteriormente
$pdf = $_GET['archivo'] ?? '';

// Verificamos que el archivo realmente exista por seguridad del SO
if ($pdf && !file_exists($pdf)) {
    die("Error: El ticket físico no se encontró en el servidor.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimiendo Ticket - AutoHub</title>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>
    <?php if ($pdf): ?>
        <iframe src="<?php echo htmlspecialchars($pdf); ?>" id="ticketFrame"></iframe>
    <?php else: ?>
        <p>No se especificó ningún ticket para imprimir.</p>
    <?php endif; ?>

    <script>
        const frame = document.getElementById('ticketFrame');

        function iniciarImpresion() {
            if (!frame) return;
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (e) {
                window.print();
            }
        }

        if (frame) {
            frame.onload = function() {
                setTimeout(iniciarImpresion, 1000);
            };
        }
    </script>
</body>
</html>