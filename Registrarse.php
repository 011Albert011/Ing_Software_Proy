<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoHub - Registrarse</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="login-styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="register-page">
    <header class="header">
        <div class="container">
            <div class="logo ubicado">
                <h1>AutoHub</h1>
            </div>
        </div>
    </header>

    <div class="login-page">
        <div class="login-container">
            <div class="login-box">
                <div class="tabs">
                    <a href="Index.php" class="tab-btn">Iniciar Sesión</a>
                    <a href="Registrarse.php" class="tab-btn active">Registrarse</a>
                </div>
                <div class="wrapper">
                    <form action="" method="POST">
                        <h1>Registrarse</h1>
                        <div class="input-box">
                            <input type="text" name="Nombre" placeholder="Nombre" required="">
                            <i class='bx bxs-user'></i>
                        </div>
                        <div class="input-box">
                            <input type="email" name="Correo" placeholder="Correo electrónico" required>
                            <i class='bx bxs-envelope'></i>
                        </div>
                        <div class="input-box">
                            <input type="password" name="Password" placeholder="Contraseña" required minlength="6">
                            <i class='bx bxs-lock-alt'></i>
                        </div>
                        <button type="submit" class="btn">Crear Cuenta</button>
                        <div class="register-link">
                            <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar Sesión</a></p>
                        </div>
                    </form>
                </div>
            </div>

            <?php
                $error_msg = '';

                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $Nombre   = trim($_REQUEST['Nombre']   ?? '');
                    $Correo   = trim($_REQUEST['Correo']   ?? '');
                    $Password = $_REQUEST['Password'] ?? '';

                    $link = mysqli_connect("sql210.infinityfree.com", "if0_41997562", "pG52HDE7T6H", "if0_41997562_sistemasii");
                    if (!$link) {
                        die("Error de conexion: " . mysqli_connect_error());
                    }

                    // Validar formato de correo
                    $Correo = filter_var($Correo, FILTER_VALIDATE_EMAIL) ? $Correo : '';
                    if (!$Correo) {
                        $error_msg = "Ingresa un correo electrónico válido.";
                    }

                    // Verificar que el correo no esté ya registrado
                    if (!$error_msg) {
                        $revision = "SELECT Id_Usuario FROM Usuario WHERE Correo = ? LIMIT 1";
                        if ($stmt = mysqli_prepare($link, $revision)) {
                            mysqli_stmt_bind_param($stmt, "s", $Correo);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_store_result($stmt);
                            if (mysqli_stmt_num_rows($stmt) > 0) {
                                $error_msg = "El correo electrónico ya está registrado.";
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }

                    if (!$error_msg) {
                        $hashedPassword = password_hash($Password, PASSWORD_BCRYPT);

                        // El privilegio SIEMPRE se asigna aquí en el servidor:
                        // 1 = cliente normal. Para hacer admin, cambia este valor
                        // a 0 y registra al usuario, o actualiza directo en BD.
                        $Privilegio = 1;

                        $query = "INSERT INTO Usuario (Nombre, Correo, Password, Privilegio) VALUES (?, ?, ?, ?)";

                        if ($stmt = mysqli_prepare($link, $query)) {
                            mysqli_stmt_bind_param($stmt, "sssi",
                                $Nombre, $Correo, $hashedPassword, $Privilegio);

                            if (mysqli_stmt_execute($stmt)) {
                                mysqli_stmt_close($stmt);
                                mysqli_close($link);
                                header("Location: Index.php");
                                exit();
                            } else {
                                $error_msg = "Error al registrar: " . mysqli_error($link);
                            }
                            mysqli_stmt_close($stmt);
                        } else {
                            $error_msg = "Error interno de servidor.";
                        }
                    }

                    mysqli_close($link);
                }
            ?>

            <?php if (!empty($error_msg)): ?>
                <div class="mensaje-error">
                    <?php echo htmlspecialchars($error_msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

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