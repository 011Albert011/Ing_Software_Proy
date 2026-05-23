<?php
ob_start();
session_start();

require_once 'Generar_Ticket.php';
require_once 'Mandar_Correo.php';

$json = file_get_contents('php://input'); 
$data = json_decode($json, true);

// Check if this is a post-process completion call
$processId = $data['processId'] ?? null;

$link = mysqli_connect("sql210.infinityfree.com", "if0_41997562", "pG52HDE7T6H", "if0_41997562_sistemasii");
if(!$link){
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error en conexion con la BD']);
    exit;
}

$items = $data['items'] ?? [];
$PagoTotal = $data['total'] ?? 0;
$UsuarioID = $_SESSION['id_usuario'] ?? null; 
$Correo_Usuario = $_SESSION['correo'] ?? null;
$Nombre_Usuario = $_SESSION['usuario'] ?? null;

if (!$UsuarioID) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Sesion no encontrada. Vuelve a iniciar sesion.']);
    mysqli_close($link);
    exit;
}
// Validaciones previas
if (empty($items)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'No hay items en la compra.']);
    mysqli_close($link);
    exit;
}

$PagoTotal = floatval($PagoTotal);
if ($PagoTotal <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'El total de pago debe ser mayor a 0.']);
    mysqli_close($link);
    exit;
}mysqli_begin_transaction($link);

// prepara la consulta para insertar el ticket maestro
$ticketStmt = mysqli_prepare($link, "INSERT INTO ticket (Id_Usuario, fecha, Total_Pago) VALUES (?, NOW(), ?)");
if (!$ticketStmt) {
    mysqli_rollback($link);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error interno al crear el ticket.']);
    mysqli_close($link);
    exit;
}
// enlaza los parametros: id usuario, total pago
mysqli_stmt_bind_param($ticketStmt, "id", $UsuarioID, $PagoTotal);
if (!mysqli_stmt_execute($ticketStmt)) {
    mysqli_stmt_close($ticketStmt);
    mysqli_rollback($link);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error al crear el ticket maestro.']);
    mysqli_close($link);
    exit;
}
$Id_Ticket = mysqli_insert_id($link);
mysqli_stmt_close($ticketStmt);

// Solo preparamos los statements que NO devuelven resultado con bind_result
$updateStockStmt = mysqli_prepare($link, "UPDATE carro SET Stock = ? WHERE Id_Carro = ?");
$detallesStmt    = mysqli_prepare($link, "INSERT INTO detalles_t (Id_Ticket, Id_Carro, Precio_Unitario, Cantidad) VALUES (?, ?, ?, ?)");

if (!$updateStockStmt || !$detallesStmt) {
    mysqli_rollback($link);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error interno al preparar las consultas.']);
    mysqli_close($link);
    exit;
}

$TodoBien = true;

foreach ($items as $item) {
    $Id_Producto = intval($item['id'] ?? 0);
    $Precio      = floatval($item['price'] ?? 0);
    $Cantidad    = intval($item['quantity'] ?? 0);

    if ($Id_Producto <= 0 || $Cantidad <= 0 || $Precio < 0) {
        $TodoBien = false;
        break;
    }

    // ── CAMBIO CLAVE: usamos get_result() en lugar de bind_result ──
    // Se prepara, ejecuta y libera el statement de stock en cada iteración
    $stockStmt = mysqli_prepare($link, "SELECT Stock FROM carro WHERE Id_Carro = ? LIMIT 1");
    if (!$stockStmt) { $TodoBien = false; break; }

    mysqli_stmt_bind_param($stockStmt, "i", $Id_Producto);
    mysqli_stmt_execute($stockStmt);

    $stockResult     = mysqli_stmt_get_result($stockStmt);  // <-- get_result, no bind_result
    $stockRow        = mysqli_fetch_assoc($stockResult);
    $stockDisponible = $stockRow['Stock'] ?? null;

    mysqli_stmt_close($stockStmt);   // <-- cerramos inmediatamente, sin dejar cursores abiertos

    if ($stockDisponible === null || $stockDisponible < $Cantidad) {
        $TodoBien = false;
        break;
    }

    $nuevoStock = $stockDisponible - $Cantidad;
    mysqli_stmt_bind_param($updateStockStmt, "ii", $nuevoStock, $Id_Producto);
    if (!mysqli_stmt_execute($updateStockStmt)) {
        $TodoBien = false;
        break;
    }

    mysqli_stmt_bind_param($detallesStmt, "iidi", $Id_Ticket, $Id_Producto, $Precio, $Cantidad);
    if (!mysqli_stmt_execute($detallesStmt)) {
        $TodoBien = false;
        break;
    }
}

mysqli_stmt_close($updateStockStmt);
mysqli_stmt_close($detallesStmt);

if (!$TodoBien) {
    mysqli_rollback($link);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Stock insuficiente o error interno al procesar la compra.']);
    mysqli_close($link);
    exit;
}

// Intenta crear el ticket DENTRO de la transacción
$nombre_archivo = Crear_Ticket($items, $PagoTotal);
if (!$nombre_archivo) {
    mysqli_rollback($link);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error al crear el archivo físico del Ticket.']);
    mysqli_close($link);
    exit;
}

// confirma la transaccion si todo salio bien
mysqli_commit($link);

$Mandar_Correo = enviarTicketPorCorreo($Correo_Usuario, $Nombre_Usuario, $nombre_archivo);
$soloNombre = basename($nombre_archivo);
$_SESSION['ultimo_ticket'] = 'tickets/' . $soloNombre;

ob_clean();
if ($Mandar_Correo) {
    echo json_encode([
        'success' => true,
        'message' => 'Compra exitosa, ticket enviado e imprimiendo...',
        'ruta' => 'tickets/' . $soloNombre
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Compra guardada pero falló el envío del correo. Iniciando impresión...',
        'ruta' => 'tickets/' . $soloNombre
    ]);
}

mysqli_close($link);

