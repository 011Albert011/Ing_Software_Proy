// Carrito (el contenido de productos viene desde PHP)

let cart = [];
const stockWorker = new Worker('worker.js');

// Escuchar respuestas del worker
stockWorker.onmessage = function(e) {
    const { action } = e.data;
    if (action === 'stockValidated') {
        const { productId, productName, productPrice, isValid, newStock } = e.data;
        if (isValid) {
            // Procede a agregar al carrito
            addToCartInternal(productId, productName, productPrice);
        } else {
            showNotification('Stock insuficiente');
        }
    } else if (action === 'paymentProcessed') {
        const { success, message } = e.data;
        if (success) {
            // Continúa con el fetch a PHP
            proceedWithPayment();
        } else {
            alert(message);
        }
    }
};

// Agregar al carrito, primero validamos el stock con el worker
function addToCart(productId, productName, productPrice) {
    // primero, obtenemos el stock actual de BD 
    fetch('getStock.php?productId=' + productId)
    .then(response => response.json())
    .then(stockData => {
        if (!stockData.success) {
            showNotification('Error al validar stock');
            return;
        }

        stockWorker.postMessage({
            action: 'validateStock',
            data: {
                productId,
                productName,
                productPrice,
                requestedQuantity: 1,
                availableStock: stockData.stock
            }
        });
    })
    .catch(error => {
        console.error('Error al consultar stock:', error);
        showNotification('No se pudo validar el stock');
    });
}

//esta funcion solo se llama si el worker valida que hay stock suficiente, para evitar duplicar logica de validacion
function addToCartInternal(productId, productName, productPrice) {
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            quantity: 1
        });
    }
    updateCart();
    showNotification(`${productName} agregado al carrito`);
}

// Actualizar carrito
function updateCart() {
    const cartItemsDiv = document.getElementById("cartItems");
    const cartCount = document.getElementById("cartCount");
    const cartTotal = document.getElementById("cartTotal");

    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p class="empty-cart">El carrito está vacío</p>';
        cartCount.textContent = "0";
        cartTotal.textContent = "0.00";
        return;
    }

    cartItemsDiv.innerHTML = "";
    let total = 0;
    let count = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        count += item.quantity;

        const cartItem = document.createElement("div");
        cartItem.className = "cart-item";
        cartItem.innerHTML = `
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div>Cantidad: ${item.quantity}</div>
                <div class="cart-item-price">$${itemTotal.toFixed(2)}</div>
            </div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.id})">🗑️</button>
        `;
        cartItemsDiv.appendChild(cartItem);
    });

    cartCount.textContent = count;
    cartTotal.textContent = total.toFixed(2);
}

// Eliminar del carrito
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
}

// Toggle carrito
function toggleCart() {
    document.getElementById("cartSidebar").classList.toggle("active");
}

// para fetch real
function proceedWithPayment() {
    const totalPagar = cart.reduce((sum, it) => sum + it.price * it.quantity, 0);

    fetch('Procesar_pago.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items: cart, total: totalPagar })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            // Resto del código...
        }
    });
}

// Checkout
function checkout() {
    if (cart.length === 0) {
        alert("El carrito está vacío");
        return;
    }

    const burstTime = Math.floor(Math.random() * 10) + 30;
    const totalPagar = cart.reduce((sum, it) => sum + it.price * it.quantity, 0);
    const processData = {
        id: `TICKET_${Date.now()}`,
        items: cart,
        total: totalPagar,
        timestamp: Date.now(),
        originalBurstTime: burstTime,
        remainingTime: burstTime,
        status: 'LISTO'
    };

    // Store process data in localStorage for the process manager (support multiple pending processes)
    let pendingProcesses = JSON.parse(localStorage.getItem('pendingProcesses') || '[]');
    pendingProcesses.push(processData);
    localStorage.setItem('pendingProcesses', JSON.stringify(pendingProcesses));

    // Clear cart immediately (UI feedback)
    cart = [];
    updateCart();
    toggleCart();

    // Redirect to process manager
    window.location.href = 'process_manager.php';
}


// Notificación
function showNotification(message) {
    const notification = document.createElement("div");
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ff6b6b, #4ecdc4);
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        z-index: 999;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = "slideOut 0.3s ease";
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Animaciones CSS
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Scroll suave
function scrollToProducts() {
    document.getElementById("productos").scrollIntoView({ behavior: "smooth" });
}

// Manejo de formulario de contacto
function handleSubmit(event) {
    event.preventDefault();
    showNotification("¡Mensaje enviado! Nos pondremos en contacto pronto.");
    event.target.reset();
}

// Inicializar
document.addEventListener("DOMContentLoaded", () => {
    updateCart();
});

// Cerrar carrito al hacer clic fuera
document.addEventListener("click", (e) => {
    const cartSidebar = document.getElementById("cartSidebar");
    const cartIcon = document.querySelector(".cart-icon");
    
    if (!cartSidebar.contains(e.target) && !cartIcon.contains(e.target)) {
        cartSidebar.classList.remove("active");
    }
});