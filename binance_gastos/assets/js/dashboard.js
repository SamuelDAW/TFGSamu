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
        document.getElementById('join-group-form').style.display = 'none';
        document.getElementById('joinGroupForm').reset();
        document.getElementById('edit-group-form').style.display = 'none';
        document.getElementById('groupForm').reset();
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

    // Evento para el botón de unirse a grupo
    document.getElementById('joinGroupBtn').addEventListener('click', function() {
        // Mostrar el formulario
        document.getElementById('join-group-form').style.display = 'block';
        // Ocultar el resto de formularios
        document.getElementById('create-group-form').style.display = 'none';
        document.getElementById('groupForm').reset();
        document.getElementById('edit-group-form').style.display = 'none';
        document.getElementById('joinGroupForm').reset();
    });

    // Evento para el botón de cancelar unión
    document.getElementById('cancelJoinGroupBtn').addEventListener('click', function() {
        // Ocultar y limpiar el formulario
        document.getElementById('join-group-form').style.display = 'none';
        document.getElementById('joinGroupForm').reset();
    });

    // Evento para el formulario de unirse a grupo
    document.getElementById('joinGroupForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const invitationCode = document.getElementById('invitationCodeInput').value.trim();
        if (invitationCode) {
            joinGroup(invitationCode);
        }
    });
     // Evento para el botón de cancelar edición
     document.getElementById('cancelEditGroupBtn').addEventListener('click', function() {
        // Ocultar y limpiar el formulario
        document.getElementById('edit-group-form').style.display = 'none';
        document.getElementById('editGroupForm').reset();
    });
    // Evento para el botón de ocultar miembros
    document.getElementById('hideMembersBtn').addEventListener('click', function() {
        document.getElementById('members-section').style.display = 'none';
    });

    // Evento para el formulario de edición de grupo
    document.getElementById('editGroupForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const groupId = document.getElementById('editGroupId').value;
        const newName = document.getElementById('editGroupNameInput').value.trim();
        if (groupId && newName) {
            updateGroup(groupId, newName);
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
        groupItem.classList.add('group-item');
        
        const groupLink = document.createElement('a');
        groupLink.textContent = group.nombre;
        groupLink.href = `group.html?groupId=${group.id}`;
        groupItem.appendChild(groupLink);
        
        if (group.rol === 'Administrador') {
            const editButton = document.createElement('button');
            editButton.textContent = 'Cambiar Nombre';
            editButton.onclick = () => showEditGroupForm(group.id, group.nombre);
            groupItem.appendChild(editButton);

            const deleteButton = document.createElement('button');
            deleteButton.textContent = 'Eliminar';
            deleteButton.onclick = () => deleteGroup(group.id);
            groupItem.appendChild(deleteButton);
        }

        const membersButton = document.createElement('button');
        membersButton.textContent = 'Miembros';
        membersButton.onclick = () => showGroupMembers(group.id, group.rol);
        groupItem.appendChild(membersButton);

        groupsList.appendChild(groupItem);
    });
}

// Función para mostrar los miembros del grupo
async function showGroupMembers(groupId, userRole) {
    const result = await fetchGroupMembers(groupId);
    if (result.success) {
        const isAdmin = userRole === 'Administrador';
        displayGroupMembers(result.data, isAdmin, groupId);
    } else {
        alert(result.message);
    }
}

// Función para obtener los miembros del grupo
async function fetchGroupMembers(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getGroupMembers', groupId })
    });
    return await response.json();
}

// Función para mostrar los miembros
function displayGroupMembers(members, isAdmin, groupId) {
    const membersContainer = document.getElementById('members-container');
    membersContainer.innerHTML = `<h3>Miembros del grupo</h3>`;

    // Mostrar lista de miembros
    members.forEach(member => {
        const memberItem = document.createElement('div');
        memberItem.textContent = `${member.nombre} (${member.rol})`;

        if (isAdmin && member.rol !== 'Administrador') {
            const expelButton = document.createElement('button');
            expelButton.textContent = 'Expulsar';
            expelButton.onclick = () => expelMember(groupId, member.id);
            memberItem.appendChild(expelButton);
        }

        membersContainer.appendChild(memberItem);
    });

    // Mostrar el contenedor de miembros
    document.getElementById('members-section').style.display = 'block';
}

// Función para expulsar a un miembro
async function expelMember(groupId, memberId) {
    if (confirm('¿Estás seguro de que deseas expulsar a este miembro?')) {
        const response = await fetch('../api/ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'expelMember', groupId, memberId })
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            // Actualizar la lista de miembros
            const membersResult = await fetchGroupMembers(groupId);
            if (membersResult.success) {
                const isAdmin = true; // Ya sabemos que es admin
                displayGroupMembers(membersResult.data, isAdmin, groupId);
            }
        } else {
            alert(result.message);
        }
    }
}

// Función para cerrar sesión
function logout() {
    fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    }).then(response => response.json())
      .then(result => {
          if (result.success) {
              window.location.href = 'login.html';
          } else {
              alert('Error al cerrar sesión');
          }
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

// Función para unirse a un grupo
async function joinGroup(invitationCode) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'joinGroup', invitationCode })
    });
    const result = await response.json();
    if (result.success) {
        alert(result.message);
        // Ocultar y limpiar el formulario
        document.getElementById('join-group-form').style.display = 'none';
        document.getElementById('joinGroupForm').reset();
        // Actualizar la lista de grupos
        const groups = await fetchGroupsByUser();
        if (groups.success) {
            displayGroups(groups.data);
        }
    } else {
        alert(result.message);
    }
}

// Función para mostrar el formulario de edición de grupo
function showEditGroupForm(groupId, currentName) {
    document.getElementById('editGroupId').value = groupId;
    document.getElementById('editGroupNameInput').value = currentName;
    document.getElementById('edit-group-form').style.display = 'block';
    document.getElementById('create-group-form').style.display = 'none';
    document.getElementById('join-group-form').style.display = 'none';
}

// Función para actualizar el nombre del grupo
async function updateGroup(groupId, newName) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'updateGroup', groupId, newName })
    });
    const result = await response.json();
    if (result.success) {
        alert(result.message);
        // Ocultar y limpiar el formulario
        document.getElementById('edit-group-form').style.display = 'none';
        document.getElementById('editGroupForm').reset();
        // Actualizar la lista de grupos
        const groups = await fetchGroupsByUser();
        if (groups.success) {
            displayGroups(groups.data);
        }
    } else {
        alert(result.message);
    }
}

// Función para eliminar un grupo
async function deleteGroup(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'deleteGroup', groupId })
    });
    const result = await response.json();
    if (result.success) {
        alert(result.message);
        // Actualizar la lista de grupos
        const groups = await fetchGroupsByUser();
        if (groups.success) {
            displayGroups(groups.data);
        }
    } else {
        alert(result.message);
    }
}
