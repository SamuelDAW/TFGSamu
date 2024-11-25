document.addEventListener('DOMContentLoaded', async function() {
    // Cargar la información del usuario
    const userInfo = await fetchUserInfo();
    if (userInfo.success) {
        document.getElementById('userName').textContent = `Nombre: ${userInfo.nombre}`;
    } else {
        window.location.href = 'login.html'; // Redirige al login si no hay usuario
    }

    // Cargar los grupos del usuario
    const groups = await fetchGroupsByUser();
    if (groups.success) {
        displayGroups(groups.data);
    }

    // Configura el botón de logout
    document.getElementById('logoutBtn').addEventListener('click', function() {
        logout();
    });

    // Evento para el botón de crear grupo
    document.getElementById('createGroupBtn').addEventListener('click', function() {
        // Mostrar el formulario
        document.getElementById('create-group-form').style.display = 'block';
    });

    // Evento para el botón de cancelar
    document.getElementById('cancelGroupBtn').addEventListener('click', function() {
        // Ocultar y limpiar el formulario
        document.getElementById('create-group-form').style.display = 'none';
        document.getElementById('groupForm').reset();
    });

    // Evento para el formulario de creación de grupo
    document.getElementById('groupForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const groupName = document.getElementById('groupNameInput').value.trim();
        if (groupName) {
            createGroup(groupName);
        }
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

// Función para obtener los grupos del usuario
async function fetchGroupsByUser() {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getGroupsByUser' })
    });
    return await response.json();
}

// Función para mostrar los grupos en el dashboard
function displayGroups(groups) {
    const groupsList = document.getElementById('groups-list');
    groupsList.innerHTML = '';
    groups.forEach(group => {
        const groupItem = document.createElement('div');
        groupItem.textContent = group.nombre;
        groupsList.appendChild(groupItem);
    });
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

// Función para crear un grupo
async function createGroup(groupName) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'createGroup', groupName })
    });
    const result = await response.json();
    if (result.success) {
        alert(result.message);
        // Ocultar y limpiar el formulario
        document.getElementById('create-group-form').style.display = 'none';
        document.getElementById('groupForm').reset();
        // Actualizar la lista de grupos
        const groups = await fetchGroupsByUser();
        if (groups.success) {
            displayGroups(groups.data);
        }
    } else {
        alert(result.message);
    }
}
