<?php
session_start();
if (!isset($_SESSION["k_username"]) || ($_SESSION["privilegio"] ?? 1) != 0) {
    header("Location: Index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoHub - Panel Administrador</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Solo estilos estructurales (posición, tamaño, grid) sin definir colores.
           Los colores vienen de las clases existentes en styles.css */
        .admin-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .search-admin {
            padding: 0.6rem 1rem;
            border-radius: 40px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.5);
            color: var(--text-color);
            width: 250px;
        }
        .btn-admin-add {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .product-card .admin-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-edit-card, .btn-delete-card {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-edit-card {
            background: #2c7da0;
            color: white;
        }
        .btn-delete-card {
            background: #c44536;
            color: white;
        }
        /* Modal sin clases nuevas de color */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-container {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .modal-container h3 {
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        .modal-container input, .modal-container select, .modal-container textarea {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 1rem;
            background: #0B0C10;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            color: white;
        }
        .modal-container label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
        .modal-buttons button {
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            border: none;
            cursor: pointer;
        }
        .btn-save {
            background: var(--accent-color);
            color: white;
        }
        .btn-cancel {
            background: #4a5b6e;
            color: white;
        }
        .stock-badge {
            font-weight: bold;
            background: rgba(0,0,0,0.5);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            display: inline-block;
        }
        .low-stock { color: #ffaa66; }
        .out-stock { color: #ff6b6b; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>AutoHub Admin</h1>
                </div>
                <div class="abajo">
                    <nav class="nav">
                        <a href="IndexPrincipal.php">Tienda</a>
                        <a href="IndexAdmin.php">Gestión</a>
                        <a href="process_manager.php">Procesos</a>
                        <a href="Logout.php">Cerrar Sesión</a>
                    </nav>
                    <div class="cart-icon">
                        <span>🛒</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 2rem;">
        <div class="admin-toolbar">
            <input type="text" id="searchAdmin" class="search-admin" placeholder="Buscar por nombre o categoría..." onkeyup="filterAdminCars()">
            <button class="btn-admin-add" onclick="openAddModal()">+ Nuevo Vehículo</button>
        </div>

        <div class="products-grid" id="adminCarsGrid">
            <?php
            $link = mysqli_connect("localhost", "root", "", "sistemasii");
            $result = mysqli_query($link, "SELECT * FROM Carro ORDER BY Id_Carro");
            while ($car = mysqli_fetch_assoc($result)):
                $stockClass = ($car['Stock'] <= 0) ? 'out-stock' : (($car['Stock'] < 5) ? 'low-stock' : '');
            ?>
            <div class="product-card" data-id="<?= $car['Id_Carro'] ?>">
                <div class="product-image">
                    <img src="ImagenesProductos/<?= htmlspecialchars($car['Imagen']) ?>" alt="<?= htmlspecialchars($car['Nombre_C']) ?>" onerror="this.src='ImagenesProductos/default.jpg'">
                </div>
                <div class="product-info">
                    <div class="product-category"><?= htmlspecialchars($car['Categoria']) ?></div>
                    <div class="product-name"><?= htmlspecialchars($car['Nombre_C']) ?></div>
                    <div class="product-price">$<?= number_format($car['Precio'], 2) ?></div>
                    <div class="product-description"><?= htmlspecialchars($car['Descripcion']) ?></div>
                    <div>Stock: <span class="stock-badge <?= $stockClass ?>"><?= $car['Stock'] ?></span></div>
                    <div class="admin-buttons">
                        <button class="btn-edit-card" onclick="editCar(<?= $car['Id_Carro'] ?>)">Editar</button>
                        <button class="btn-delete-card" onclick="deleteCar(<?= $car['Id_Carro'] ?>)">Eliminar</button>
                    </div>
                </div>
            </div>
            <?php endwhile; mysqli_close($link); ?>
        </div>
    </div>

    <!-- Modal para agregar/editar -->
    <div id="adminModal" class="modal-overlay">
        <div class="modal-container">
            <h3 id="modalTitle">Agregar Vehículo</h3>
            <form id="carForm" enctype="multipart/form-data">
                <input type="hidden" id="carId" name="id" value="">
                <label>Nombre</label>
                <input type="text" id="carName" name="nombre" required>
                <label>Categoría</label>
                <select id="carCategory" name="categoria" required>
                    <option value="suv">SUV</option>
                    <option value="sedan">Sedán</option>
                    <option value="electrico">Eléctrico</option>
                    <option value="deportivo">Deportivo</option>
                    <option value="hypercar">Hypercar</option>
                </select>
                <label>Precio (USD)</label>
                <input type="number" step="0.01" id="carPrice" name="precio" required>
                <label>Stock</label>
                <input type="number" id="carStock" name="stock" required>
                <label>Descripción</label>
                <textarea id="carDesc" name="descripcion" rows="3" required></textarea>
                <label>Imagen (solo para nuevos o para cambiar)</label>
                <input type="file" id="carImage" name="imagen" accept="image/*">
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterAdminCars() {
            let filter = document.getElementById('searchAdmin').value.toLowerCase();
            let cards = document.querySelectorAll('#adminCarsGrid .product-card');
            cards.forEach(card => {
                let name = card.querySelector('.product-name').innerText.toLowerCase();
                let cat = card.querySelector('.product-category').innerText.toLowerCase();
                if (name.includes(filter) || cat.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Agregar Vehículo';
            document.getElementById('carId').value = '';
            document.getElementById('carForm').reset();
            document.getElementById('adminModal').style.display = 'flex';
        }

        function editCar(id) {
            fetch(`admin_actions.php?action=get&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').innerText = 'Editar Vehículo';
                        document.getElementById('carId').value = data.car.Id_Carro;
                        document.getElementById('carName').value = data.car.Nombre_C;
                        document.getElementById('carCategory').value = data.car.Categoria;
                        document.getElementById('carPrice').value = data.car.Precio;
                        document.getElementById('carStock').value = data.car.Stock;
                        document.getElementById('carDesc').value = data.car.Descripcion;
                        document.getElementById('adminModal').style.display = 'flex';
                    } else {
                        alert('Error al cargar los datos');
                    }
                });
        }

        function deleteCar(id) {
            if (confirm('¿Eliminar este vehículo permanentemente?')) {
                fetch('admin_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        document.getElementById('carForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let id = document.getElementById('carId').value;
            formData.append('action', id ? 'update' : 'create');

            fetch('admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        function closeModal() {
            document.getElementById('adminModal').style.display = 'none';
        }

        window.onclick = function(event) {
            let modal = document.getElementById('adminModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>