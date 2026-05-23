// Cambiar entre tabs
function switchTab(tabName) {
    // Ocultar todos los formularios
    const forms = document.querySelectorAll('.auth-form');
    forms.forEach(form => form.classList.remove('active'));

    // Desactivar todos los botones
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(btn => btn.classList.remove('active'));

    // Mostrar el formulario seleccionado
    if (tabName === 'login') {
        document.getElementById('loginForm').classList.add('active');
        tabs[0].classList.add('active');
    } else if (tabName === 'signup') {
        document.getElementById('signupForm').classList.add('active');
        tabs[1].classList.add('active');
    }
}

// Validar contraseña
function validatePassword(password) {
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');

    let strength = 0;

    // Al menos 8 caracteres
    if (password.length >= 8) strength++;

    // Contiene mayúsculas
    if (/[A-Z]/.test(password)) strength++;

    // Contiene números
    if (/[0-9]/.test(password)) strength++;

    // Contiene caracteres especiales
    if (/[!@#$%^&*]/.test(password)) strength++;

    const strengthLevel = ['Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'];
    const strengthColors = ['#ff6b6b', '#ffa500', '#ffd700', '#90EE90', '#00AA00'];

    strengthText.textContent = `Fortaleza: ${strengthLevel[strength]}`;
    strengthBar.style.width = `${(strength / 4) * 100}%`;
    strengthBar.style.backgroundColor = strengthColors[strength];
}

// Monitorear cambios de contraseña
document.addEventListener('DOMContentLoaded', () => {
    const signupPassword = document.getElementById('signup-password');
    if (signupPassword) {
        signupPassword.addEventListener('input', (e) => {
            validatePassword(e.target.value);
        });
    }
});

// Manejar Login
function handleLogin(event) {
    event.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Validaciones básicas
    if (!email || !password) {
        showAuthNotification('Por favor completa todos los campos', 'error');
        return;
    }

    // Simular validación
    if (email.length < 5 || password.length < 6) {
        showAuthNotification('Email o contraseña inválidos', 'error');
        return;
    }

    // Simular login exitoso
    showAuthNotification(`¡Bienvenido ${email}! Iniciando sesión...`, 'success');

    setTimeout(() => {
        // Guardar en localStorage
        localStorage.setItem('user', JSON.stringify({
            email: email,
            loginTime: new Date().toLocaleString()
        }));

        // Redirigir a la tienda
        window.location.href = 'IndexPrincipal.php';
    }, 1500);
}

// Manejar Signup
function handleSignup(event) {
    event.preventDefault();

    const fullname = document.getElementById('fullname').value;
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    // Validaciones
    if (!fullname || !email || !password || !confirmPassword) {
        showAuthNotification('Por favor completa todos los campos', 'error');
        return;
    }

    if (password !== confirmPassword) {
        showAuthNotification('Las contraseñas no coinciden', 'error');
        return;
    }

    if (password.length < 6) {
        showAuthNotification('La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }

    if (fullname.length < 3) {
        showAuthNotification('El nombre debe tener al menos 3 caracteres', 'error');
        return;
    }

    // Simular registro exitoso
    showAuthNotification(`¡Cuenta creada exitosamente! Bienvenido ${fullname}`, 'success');

    setTimeout(() => {
        // Guardar usuario
        localStorage.setItem('user', JSON.stringify({
            fullname: fullname,
            email: email,
            registrationTime: new Date().toLocaleString()
        }));

        // Redirigir a la tienda
        window.location.href = 'index.html';
    }, 1500);
}

// Notificación de autenticación
function showAuthNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 1rem 2rem;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 999;
        animation: slideIn 0.3s ease;
        max-width: 300px;
    `;

    if (type === 'error') {
        notification.style.background = '#ff6b6b';
        notification.innerHTML = `⚠️ ${message}`;
    } else if (type === 'success') {
        notification.style.background = '#4ecdc4';
        notification.innerHTML = `✓ ${message}`;
    } else {
        notification.style.background = '#3498db';
        notification.innerHTML = `ℹ️ ${message}`;
    }

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Agregar estilos de animación si no existen
const style = document.createElement('style');
if (!document.querySelector('style:contains("slideIn")')) {
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
}

// Inicializar tab activo
window.addEventListener('load', () => {
    switchTab('login');
});
