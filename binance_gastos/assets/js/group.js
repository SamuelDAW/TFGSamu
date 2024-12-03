document.addEventListener('DOMContentLoaded', async function() {
    // Obtener el groupId de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const groupId = urlParams.get('groupId');

    if (groupId) {
        // Obtener detalles del grupo
        const result = await fetchGroupDetails(groupId);
        if (result.success) {
            displayGroupDetails(result.data, result.isCreator);
        } else {
            alert(result.message);
            window.location.href = 'dashboard.html';
        }

        // Obtener y mostrar los gastos del grupo
        const expensesResult = await fetchGroupExpenses(groupId);
        if (expensesResult.success) {
            displayTotalExpenses(expensesResult.totalGroupExpense, expensesResult.userExpense);
            displayExpenses(expensesResult.data);
        } else {
            alert('Error al obtener los gastos del grupo');
        }
    } else {
        window.location.href = 'dashboard.html';
    }

    // Evento para el botón de volver
    document.getElementById('backBtn').addEventListener('click', function() {
        window.location.href = 'dashboard.html';
    });

    // Evento para el botón de añadir gasto
    document.getElementById('addExpenseBtn').addEventListener('click', async function() {
        console.log('Botón "Añadir Gasto" clickeado');
        // Mostrar el formulario
        document.getElementById('add-expense-form').style.display = 'block';
        // Obtener y mostrar la lista de participantes
        const participants = await fetchGroupMembers(groupId);
        if (participants.success) {
            displayParticipantsCheckboxes(participants.data);
        } else {
            alert('Error al obtener los participantes del grupo');
        }
    });

    // Evento para el botón de cancelar
    document.getElementById('cancelExpenseBtn').addEventListener('click', function() {
        // Ocultar el formulario y resetearlo
        document.getElementById('add-expense-form').style.display = 'none';
        document.getElementById('expenseForm').reset();
    });

    // Evento para el formulario de gastos
    document.getElementById('expenseForm').addEventListener('submit', function(event) {
        event.preventDefault();
        console.log('Formulario de gastos enviado');
        addExpense(groupId);
    });
});

async function fetchGroupDetails(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getGroupDetails', groupId })
    });
    return await response.json();
}

function displayGroupDetails(group, isCreator) {
    document.getElementById('group-name').textContent = group.nombre;
    document.getElementById('invitation-code').textContent = group.codigo_invitacion;
    if (isCreator) {
        document.getElementById('group-creator').textContent = 'Tú';
    } else {
        document.getElementById('group-creator').textContent = group.creador_nombre;
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

// Función para mostrar los participantes como checkboxes
function displayParticipantsCheckboxes(participants) {
    const participantsList = document.getElementById('participants-list');
    participantsList.innerHTML = '';
    participants.forEach(participant => {
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = `participant-${participant.id}`;
        checkbox.name = 'participants';
        checkbox.value = participant.id;
        checkbox.checked = true; // Opcional: marcar todos por defecto

        const label = document.createElement('label');
        label.htmlFor = `participant-${participant.id}`;
        label.textContent = participant.nombre;

        const div = document.createElement('div');
        div.appendChild(checkbox);
        div.appendChild(label);

        participantsList.appendChild(div);
    });
}

// Función para añadir el gasto
async function addExpense(groupId) {
    console.log('Función addExpense llamada con groupId:', groupId);
    const name = document.getElementById('expenseName').value.trim();
    const amount = parseFloat(document.getElementById('expenseAmount').value);
    const checkboxes = document.querySelectorAll('input[name="participants"]:checked');
    const participantIds = Array.from(checkboxes).map(cb => cb.value);

    if (name && amount > 0 && participantIds.length > 0) {
        console.log('Enviando datos al servidor...');
        const response = await fetch('../api/ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'addExpense',
                groupId,
                name,
                amount,
                participants: participantIds
            })
        });
        console.log('Respuesta del servidor recibida');
        const result = await response.json();
        console.log('Resultado:', result);

        if (result.success) {
            alert('Gasto agregado exitosamente');
            // Ocultar y resetear el formulario
            document.getElementById('add-expense-form').style.display = 'none';
            document.getElementById('expenseForm').reset();

            // Obtener y mostrar los gastos actualizados
            const expensesResult = await fetchGroupExpenses(groupId);
            if (expensesResult.success) {
                displayTotalExpenses(expensesResult.totalGroupExpense, expensesResult.userExpense);
                displayExpenses(expensesResult.data);
            }
        } else {
            alert('Error al agregar el gasto: ' + result.message);
        }
    } else {
        alert('Por favor, completa todos los campos y selecciona al menos un participante.');
    }
}

// Función para obtener los gastos del grupo
async function fetchGroupExpenses(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'getGroupExpenses', groupId })
    });
    return await response.json();
}

// Función para mostrar el gasto total del grupo y el gasto del usuario
function displayTotalExpenses(totalGroupExpense, userExpense) {
    const expensesContainer = document.getElementById('expenses-container');
    const totalExpensesElement = document.createElement('div');
    totalExpensesElement.innerHTML = `
        <p><strong>Gasto Total del Grupo:</strong> ${totalGroupExpense}€</p>
        <p><strong>Tu Gasto:</strong> ${userExpense}€</p>
    `;
    expensesContainer.insertBefore(totalExpensesElement, expensesContainer.firstChild);
}

// Función para mostrar los gastos en el DOM
function displayExpenses(expenses) {
    const expensesList = document.getElementById('expenses-list');
    expensesList.innerHTML = '';

    if (expenses.length === 0) {
        expensesList.innerHTML = '<p>No hay gastos en este grupo.</p>';
        return;
    }

    expenses.forEach(expense => {
        const expenseItem = document.createElement('div');
        expenseItem.classList.add('expense-item');

        expenseItem.innerHTML = `
            <p><strong>${expense.nombre}</strong> - ${expense.cantidad}€</p>
            <p>Pagado por: ${expense.usuario_nombre}</p>
            <p>Fecha: ${expense.fecha}</p>
        `;

        expensesList.appendChild(expenseItem);
    });
}