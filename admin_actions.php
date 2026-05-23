<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["k_username"]) || ($_SESSION["privilegio"] ?? 1) != 0) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$link = mysqli_connect("sql210.infinityfree.com", "if0_41997562", "pG52HDE7T6H", "if0_41997562_sistemasii");
if (!$link) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Obtener un auto por ID (para editar)
if ($action === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($link, "SELECT * FROM Carro WHERE Id_Carro = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $car = mysqli_fetch_assoc($result);
    if ($car) {
        echo json_encode(['success' => true, 'car' => $car]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No encontrado']);
    }
    exit;
}

// Crear nuevo auto
if ($action === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Manejo de imagen
    $imagen = 'default.jpg';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreImagen = uniqid() . '.' . $ext;
        $ruta = 'ImagenesProductos/' . $nombreImagen;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
            $imagen = $nombreImagen;
        }
    }
    
    $stmt = mysqli_prepare($link, "INSERT INTO Carro (Nombre_C, Categoria, Imagen, Descripcion, Precio, Stock) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssdi", $nombre, $categoria, $imagen, $descripcion, $precio, $stock);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
    }
    exit;
}

// Actualizar auto existente
if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Obtener imagen actual
    $stmtImg = mysqli_prepare($link, "SELECT Imagen FROM Carro WHERE Id_Carro = ?");
    mysqli_stmt_bind_param($stmtImg, "i", $id);
    mysqli_stmt_execute($stmtImg);
    $resImg = mysqli_stmt_get_result($stmtImg);
    $row = mysqli_fetch_assoc($resImg);
    $imagen = $row['Imagen'] ?? 'default.jpg';
    
    // Si suben nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreImagen = uniqid() . '.' . $ext;
        $ruta = 'ImagenesProductos/' . $nombreImagen;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
            $imagen = $nombreImagen;
        }
    }
    
    $stmt = mysqli_prepare($link, "UPDATE Carro SET Nombre_C=?, Categoria=?, Imagen=?, Descripcion=?, Precio=?, Stock=? WHERE Id_Carro=?");
    mysqli_stmt_bind_param($stmt, "ssssdii", $nombre, $categoria, $imagen, $descripcion, $precio, $stock, $id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
    }
    exit;
}

// Eliminar auto
if ($action === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = mysqli_prepare($link, "DELETE FROM Carro WHERE Id_Carro = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($link)]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>
