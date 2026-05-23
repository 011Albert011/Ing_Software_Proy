<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoHub - Iniciar Sesión</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login-styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo ubicado">
                <h1>AutoHub</h1>
            </div>
        </div>
    </header>

<?php
    session_start();
    $error_msg = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $Nombre   = trim($_REQUEST['Nombre']   ?? '');
        $Password = $_REQUEST['Password'] ?? '';

        $link = mysqli_connect("sql210.infinityfree.com", "if0_41997562", "pG52HDE7T6H", "if0_41997562_sistemasii");
        if (!$link) {
            die("Error de conexion: " . mysqli_connect_error());
        }

        // Traemos también el Privilegio en la consulta
        $consulta = "SELECT * FROM Usuario WHERE Nombre = ? LIMIT 1";
        if ($stmt = mysqli_prepare($link, $consulta)) {
            mysqli_stmt_bind_param($stmt, "s", $Nombre);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if ($resultado && mysqli_num_rows($resultado) > 0) {
                $Usuario        = mysqli_fetch_array($resultado, MYSQLI_ASSOC);
                $hashedPassword = $Usuario['Password'];
                $privilegio     = intval($Usuario['Privilegio'] ?? 1); // por defecto cliente

                $autenticado = false;

                if (password_verify($Password, $hashedPassword)) {
                    $autenticado = true;
                } elseif ($Password === $hashedPassword) {
                    // Caso legacy: migrar contraseña plana a bcrypt
                    $newHash    = password_hash($Password, PASSWORD_BCRYPT);
                    $updateStmt = mysqli_prepare($link,
                        "UPDATE Usuario SET Password = ? WHERE Id_Usuario = ?");
                    if ($updateStmt) {
                        mysqli_stmt_bind_param($updateStmt, "si", $newHash, $Usuario['Id_Usuario']);
                        mysqli_stmt_execute($updateStmt);
                        mysqli_stmt_close($updateStmt);
                    }
                    $autenticado = true;
                }

                if ($autenticado) {
                    session_regenerate_id(true);
                    $_SESSION["k_username"]  = $Usuario['Nombre'];
                    $_SESSION["id_usuario"]  = $Usuario['Id_Usuario'];
                    $_SESSION["usuario"]     = $Usuario['Nombre'];
                    $_SESSION["correo"]      = $Usuario['Correo'];
                    $_SESSION["privilegio"]  = $privilegio; // guardamos el rol en sesion

                    // Redirigir según el rol
                    if ($privilegio === 0) {
                        header("Location: IndexAdmin.php");
                    } else {
                        header("Location: IndexPrincipal.php");
                    }
                    exit();
                } else {
                    $error_msg = "Usuario o contraseña incorrectos";
                }
            } else {
                $error_msg = "Usuario o contraseña incorrectos";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Error interno de servidor.";
        }

        mysqli_close($link);
    }
?>

    <div class="login-page">
        <div class="login-container">
            <div class="login-box">
                <div class="tabs">
                    <a href="Index.php" class="tab-btn active">Iniciar Sesión</a>
                    <a href="Registrarse.php" class="tab-btn">Registrarse</a>
                </div>
                <div class="wrapper">
                    <form action="" method="POST">
                        <h1>Iniciar Sesión</h1>
                        <?php if ($error_msg != ""): ?>
                            <div class="mensaje-error">
                                <?php echo htmlspecialchars($error_msg); ?>
                            </div>
                        <?php endif; ?>
                        <div class="input-box">
                            <input type="text" name="Nombre" placeholder="Usuario" required>
                            <i class='bx bxs-user'></i>
                        </div>
                        <div class="input-box">
                            <input type="password" name="Password" placeholder="Contraseña" required>
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                        <button type="submit" class="btn">Iniciar Sesión</button>
                        <div class="register-link">
                            <p>¿No tienes cuenta? <a href="Registrarse.php">Registrarte</a></p>
                        </div>
                    </form>
                </div>
            </div>

            <div class="benefits-section">
                <div class="benefit-item">
                    <div class="benefit-icon">🚚</div>
                    <h4>Envío Rápido</h4>
                    <p>Entrega en 24-48 horas</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">💳</div>
                    <h4>Pago Seguro</h4>
                    <p>Múltiples formas de pago</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">🔄</div>
                    <h4>Cambios Fáciles</h4>
                    <p>Hasta 30 días de garantía</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">💬</div>
                    <h4>Soporte 24/7</h4>
                    <p>Estamos aquí para ayudarte</p>
                </div>
            </div>
        </div>
    </div>

    <script src="login-script.js"></script>
</body>
</html>