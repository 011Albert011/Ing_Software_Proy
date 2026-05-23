<?php
// Cargar la librería (Ajusta la ruta según tu carpeta)
require_once 'dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

function Crear_Ticket($items, $PagoTotal) {
    $dompdf = new Dompdf();
    $fecha = date('d/m/Y H:i:s');
    
    // 1. Creamos el diseño del Ticket en HTML/CSS
    $html = "
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; border-bottom: 2px solid #000; }
        .tabla { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla th { border-bottom: 1px solid #ddd; text-align: left; }
        .total { text-align: right; font-size: 18px; margin-top: 20px; font-weight: bold; }
    </style>
    <div class='header'>
        <h1>AUTOHUB</h1>
        <p>TICKET DE COMPRA</p>
        <p>Fecha: $fecha</p>
    </div>
    <table class='tabla'>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>";

    foreach($items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $html .= "
            <tr>
                <td>{$item['name']}</td>
                <td>{$item['quantity']}</td>
                <td>$".number_format($item['price'], 2)."</td>
                <td>$".number_format($subtotal, 2)."</td>
            </tr>";
    }

    $html .= "
        </tbody>
    </table>
    <div class='total'>TOTAL: $".number_format($PagoTotal, 2)."</div>
    <p style='text-align:center; margin-top:50px;'>¡Gracias por tu compra en AutoHub!</p>";

    // 2. Convertir HTML a PDF
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 3. Guardar el archivo
    $salida = $dompdf->output();
    $fecha_arch = date('Y-m-d_H-i-s');
    
    if(!file_exists('tickets')){
        mkdir('tickets', 0777, true);
    }

    $nombre_archivo = __DIR__."/tickets/ticket_$fecha_arch.pdf";
    
    if(file_put_contents($nombre_archivo, $salida)){
        return $nombre_archivo;
    } else {
        return false;
    }
}