document.getElementById('registroForm').addEventListener('submit', async function(event) {
    event.preventDefault();

    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    const contrasena = document.getElementById('contrasena').value;

    try {
        const response = await fetch('../api/ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'register',
                nombre: nombre,
                email: email,
                contrasena: contrasena
            })
        });

        const result = await response.json();
        document.getElementById('mensaje').textContent = result.message;
    } catch (error) {
        console.error('Error en la solicitud:', error);
    }
});
