document.getElementById('loginForm').addEventListener('submit', async function(event) {
    event.preventDefault();

    const email = document.getElementById('email').value;
    const contrasena = document.getElementById('contrasena').value;

    try {
        const response = await fetch('../api/ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'login',
                email: email,
                contrasena: contrasena
            })
        });

        const result = await response.json();
        document.getElementById('mensaje').textContent = result.message;

        // Si el login es exitoso, redirige a la página de dashboard
        if (result.success) {
            window.location.href = "dashboard.html";
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
    }
});
