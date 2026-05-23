<?php 
    // Esta parte nos ayuda a verificar si el usuario tiene permiso de estar en esta página
    session_start(); 
    if(!isset($_SESSION["k_username"])) {
        header("Location: index.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoHub - Venta de Autos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header class="header ampliado">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>AutoHub</h1>
                </div>
                <div class="abajo">
                    <nav class="nav">
                        <a href="#inicio">Inicio</a>
                        <a href="#productos">Autos</a>
                        <a href="#marcas">Marcas</a>
                        <a href="#financiacion">Financiación</a>
                        <a href="#contacto">Contacto</a>
                        <?php 
                            // CORRECCIÓN: Si es una ruta absoluta del servidor, extraemos solo el nombre del archivo
                            $urlTicket = '#';
                            if (isset($_SESSION['ultimo_ticket']) && $_SESSION['ultimo_ticket'] !== '#') {
                                $nombreArchivo = basename($_SESSION['ultimo_ticket']);
                                $urlTicket = "tickets/" . $nombreArchivo;
                            }
                        ?>
                        <a href="Imprimir.php?archivo=<?php echo urlencode($urlTicket); ?>" 
                           target="_blank" 
                           style="<?php echo ($urlTicket === '#') ? 'display:none;' : ''; ?>">
                            Reimprimir Ticket
                        </a>
                        <a href="Logout.php">Cerrar Sesión</a>
                    </nav>
                    <div class="cart-icon" onclick="toggleCart()">
                        <span>🛒</span>
                        <span class="cart-count" id="cartCount">0</span>
                    </div>
                </div>
            </div>
        </div>
    </header> 

    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h2>Mi Carrito</h2>
            <button onclick="toggleCart()" class="close-btn">&times;</button>
        </div>
        <div class="cart-items" id="cartItems">
            <p class="empty-cart">El carrito está vacío</p>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <strong>Total: $<span id="cartTotal">0.00</span></strong>
            </div>
            <button class="checkout-btn" onclick="checkout()">Comprar</button>
        </div>
    </div>

    <section class="hero margen-header" id="inicio">
        <div class="hero-content">
            <h2>Encuentra tu próximo auto con AutoHub</h2>
            <p>Vehículos certificados, financiación flexible y entrega rápida. Compra con confianza.</p>
            <button class="cta-btn" onclick="scrollToProducts()">Ver Autos</button>
        </div>
    </section>

    <section class="products-section" id="productos">
        <div class="container">
            <h2 class="section-title">Nuestros Productos</h2>
            <div class="products-grid" id="productsGrid">
                <?php 
                    // Conexión y consulta a la BD utilizando 'carro' en minúsculas conforme al .sql
                    $link = mysqli_connect("sql210.infinityfree.com", "if0_41997562", "pG52HDE7T6H", "if0_41997562_sistemasii");
                    mysqli_set_charset($link, "utf8mb4");

                    if (!$link) {
                        die("Error de conexión: " . mysqli_connect_error());
                    }

                    $resultado = mysqli_query($link, "SELECT * FROM carro WHERE Stock > 0");
                    
                    while($fila = mysqli_fetch_array($resultado)) {
                        $id = $fila['Id_Carro'];
                        $nombre = $fila['Nombre_C'];
                        $categoria = $fila['Categoria'];
                        $precio = $fila['Precio'];
                        $imagen = $fila['Imagen'];
                        $descripcion = $fila['Descripcion'];
                        $stock = $fila['Stock'];
                ?>
                    <div class="product-card" data-category="<?php echo strtolower($categoria); ?>">
                        <div class="product-image"><img src="ImagenesProductos/<?php echo $imagen; ?>" alt="<?php echo $nombre; ?>"></div>
                        <div class="product-info">
                            <div class="product-category"><?php echo strtoupper($categoria); ?></div>
                            <div class="product-name"><?php echo $nombre; ?></div>
                            <div class="product-price" data-precio-real="<?php echo $precio; ?>">$<?php echo number_format($precio, 2); ?></div>
                            <div class="product-description"><?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="product-inventario">Inventario: <?php echo $stock; ?></div>
                            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $id; ?>, '<?php echo addslashes($nombre); ?>', <?php echo $precio; ?>)">
                                Agregar al Carrito
                            </button>
                        </div>
                    </div>
                <?php
                    } // <-- CORRECCIÓN: Cerramos correctamente el ciclo de PHP aquí
                    mysqli_close($link);
                ?>
            </div>
        </div>
    </section>

    <section class="offers-section" id="ofertas">
        <div class="container">
            <h2 class="section-title">Ofertas Especiales</h2>
            <div class="offers-grid">
                <div class="offer-card">
                    <div class="offer-image">💰</div>
                    <h3>Financiamiento 0%</h3>
                    <p>Planes sin intereses por tiempo limitado</p>
                </div>
                <div class="offer-card">
                    <div class="offer-image">🔧</div>
                    <h3>Revisión Gratis</h3>
                    <p>Chequeo completo al comprar tu auto</p>
                </div>
                <div class="offer-card">
                    <div class="offer-image">🛡️</div>
                    <h3>Garantía Extendida</h3>
                    <p>Protección adicional disponible</p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section" id="contacto">
        <div class="container">
            <h2 class="section-title">Contacto</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="info-item">
                        <span class="icon">📞</span>
                        <div>
                            <h4>Teléfono</h4>
                            <p>+34 123 456 789</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="icon">📧</span>
                        <div>
                            <h4>Email</h4>
                            <p>info@autohub.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="icon">📍</span>
                        <div>
                            <h4>Ubicación</h4>
                            <p>Calle Principal 123, Madrid</p>
                        </div>
                    </div>
                </div>
                <form class="contact-form" onsubmit="handleSubmit(event)">
                    <input type="text" name="nombre" id="nombre" placeholder="Tu nombre" required>
                    <input type="email" name="email" id="email" placeholder="Tu email" required>
                    <textarea name="mensaje" id="mensaje" placeholder="Tu mensaje" rows="5" required></textarea>
                    <button type="submit" class="submit-btn">Enviar Mensaje</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Sobre Nosotros</h4>
                    <p>AutoHub es tu tienda online de confianza con los mejores modelos de vehículos.</p>
                </div>
                <div class="footer-section">
                    <h4>Enlaces</h4>
                    <ul>
                        <li><a href="#productos">Productos</a></li>
                        <li><a href="#ofertas">Ofertas</a></li>
                        <li><a href="#contacto">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Síguenos</h4>
                    <div class="social-links">
                        <a href="#">Facebook</a>
                        <a href="#">Instagram</a>
                        <a href="#">Twitter</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 AutoHub. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>