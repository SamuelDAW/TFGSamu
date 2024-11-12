document.addEventListener('DOMContentLoaded', async function() {
    // Cargar la información del usuario
    const userInfo = await fetchUserInfo();
    if (userInfo.success) {
        document.getElementById('userEmail').textContent = `Email: ${userInfo.email}`;
    } else {
        window.location.href = 'login.html'; // Redirige al login si no hay usuario
    }

    // Configura el botón de logout
    document.getElementById('logoutBtn').addEventListener('click', function() {
        logout();
    });
});

// Función para obtener la información del usuario
async function fetchUserInfo() {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getUserInfo' })
    });
    return await response.json();
}

// Función para cerrar sesión
function logout() {
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    }).then(() => {
        window.location.href = 'login.html'; // Redirige a login después de cerrar sesión
    });
}

