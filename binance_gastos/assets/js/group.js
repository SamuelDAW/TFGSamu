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
            displayParticipants(participants.data);
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
    document.getElementById('expenseForm').addEventListener('submit', async function(event) {
        event.preventDefault();
        await addExpense(groupId);
    });

    // Evento para el botón de compartir
    document.getElementById('shareBtn').addEventListener('click', function() {
        document.getElementById('share-form').style.display = 'block';
    });

    document.getElementById('cancelShareBtn').addEventListener('click', function() {
        document.getElementById('share-form').style.display = 'none';
        document.getElementById('shareInvitationForm').reset();
    });

    document.getElementById('shareInvitationForm').addEventListener('submit', async function(event) {
        event.preventDefault();
        const email = document.getElementById('email').value.trim();
        const invitationCode = document.getElementById('invitation-code').textContent.trim();
        if (email && invitationCode) {
            await shareInvitation(email, invitationCode);
        }
    });

    // Eventos para cambiar entre secciones
    document.getElementById('gastosBtn').addEventListener('click', function() {
        showSection('gastos');
    });

    document.getElementById('tusGastosBtn').addEventListener('click', async function() {
        showSection('tus-gastos');
        const tusGastosResult = await fetchTusGastos(groupId);
        if (tusGastosResult.success) {
            displayTusGastos(tusGastosResult.data);
        } else {
            alert('Error al obtener tus gastos');
        }
    });

    document.getElementById('balancesBtn').addEventListener('click', async function() {
        showSection('balances');
        const balancesResult = await fetchBalances(groupId);
        if (balancesResult.success) {
            displayBalancesChart(balancesResult.data);
        } else {
            alert('Error al obtener los balances');
        }
    });

    function showSection(section) {
        document.getElementById('gastos-section').style.display = 'none';
        document.getElementById('tus-gastos-section').style.display = 'none';
        document.getElementById('balances-section').style.display = 'none';

        document.getElementById(`${section}-section`).style.display = 'block';
    }
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

async function fetchGroupMembers(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getGroupMembers', groupId })
    });
    return await response.json();
}

function displayParticipants(participants) {
    const payerSelect = document.getElementById('payer-select');
    const participantsList = document.getElementById('participants-list');
    payerSelect.innerHTML = '';
    participantsList.innerHTML = '';

    participants.forEach(participant => {
        const payerOption = document.createElement('option');
        payerOption.value = participant.id;
        payerOption.textContent = participant.nombre;
        payerSelect.appendChild(payerOption);

        const participantCheckbox = document.createElement('input');
        participantCheckbox.type = 'checkbox';
        participantCheckbox.name = 'participants';
        participantCheckbox.value = participant.id;
        participantCheckbox.id = `participant-${participant.id}`;

        const participantLabel = document.createElement('label');
        participantLabel.htmlFor = `participant-${participant.id}`;
        participantLabel.textContent = participant.nombre;

        participantsList.appendChild(participantCheckbox);
        participantsList.appendChild(participantLabel);
    });
}

async function addExpense(groupId) {
    console.log('Función addExpense llamada con groupId:', groupId);
    const name = document.getElementById('expenseName').value.trim();
    const amount = parseFloat(document.getElementById('expenseAmount').value);
    const payer = document.getElementById('payer-select').value;
    const checkboxes = document.querySelectorAll('input[name="participants"]:checked');
    const participantIds = Array.from(checkboxes).map(cb => cb.value);

    if (name && amount > 0 && payer && participantIds.length > 0) {
        console.log('Enviando datos al servidor...');
        const response = await fetch('../api/ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'addExpense',
                groupId,
                name,
                amount,
                payer,
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
            // Actualizar la lista de gastos
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

async function fetchGroupExpenses(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getGroupExpenses', groupId })
    });
    return await response.json();
}

function displayTotalExpenses(totalGroupExpense, userExpense) {
    const expensesContainer = document.getElementById('expenses-container');
    const totalExpensesElement = document.getElementById('total-expenses');

    if (totalExpensesElement) {
        totalExpensesElement.remove();
    }

    const newTotalExpensesElement = document.createElement('div');
    newTotalExpensesElement.id = 'total-expenses';
    newTotalExpensesElement.innerHTML = `
        <p><strong>Gasto Total del Grupo:</strong> ${totalGroupExpense}€</p>
        <p><strong>Tu Gasto:</strong> ${userExpense}€</p>
    `;
    expensesContainer.insertBefore(newTotalExpensesElement, expensesContainer.firstChild);
}

function displayExpenses(expenses, userId = null) {
    const expensesList = document.getElementById('expenses-list');
    expensesList.innerHTML = '';

    if (expenses.length === 0) {
        expensesList.innerHTML = '<p>No hay gastos en este grupo.</p>';
        return;
    }

    expenses.forEach(expense => {
        if (userId === null || expense.id_usuario === userId) {
            const expenseItem = document.createElement('div');
            expenseItem.classList.add('expense-item');

            expenseItem.innerHTML = `
                <p><strong>${expense.nombre}</strong> - ${expense.cantidad}€</p>
                <p>Pagado por: ${expense.usuario_nombre}</p>
                <p>Fecha: ${expense.fecha}</p>
            `;

            expensesList.appendChild(expenseItem);
        }
    });
}

async function fetchTusGastos(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getTusGastos', groupId })
    });
    return await response.json();
}

function displayTusGastos(gastos) {
    const tusGastosList = document.getElementById('tus-gastos-list');
    tusGastosList.innerHTML = '';

    if (gastos.length === 0) {
        tusGastosList.innerHTML = '<p>No tienes gastos en este grupo.</p>';
        return;
    }

    gastos.forEach(gasto => {
        const gastoItem = document.createElement('div');
        gastoItem.classList.add('gasto-item');

        gastoItem.innerHTML = `
            <p><strong>${gasto.nombre}</strong> - ${gasto.cantidad}€</p>
            <p>Fecha: ${gasto.fecha}</p>
        `;

        tusGastosList.appendChild(gastoItem);
    });
}



async function shareInvitation(email, invitationCode) {
    try {
        const response = await fetch('../api/share_invitation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, invitationCode })
        });
        const text = await response.text();
        console.log('Respuesta del servidor:', text);
        const result = JSON.parse(text);
        if (result.success) {
            alert('Correo enviado exitosamente');
            document.getElementById('share-form').style.display = 'none';
            document.getElementById('shareInvitationForm').reset();
        } else {
            alert('Error al enviar el correo: ' + result.message);
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        alert('Error al enviar el correo');
    }
}

function displayBalancesChart(balances) {
    const ctx = document.getElementById('balances-chart').getContext('2d');
    const labels = balances.map(balance => balance.nombre);
    const positiveBalances = balances.map(balance => balance.cantidad >= 0 ? balance.cantidad : 0);
    const negativeBalances = balances.map(balance => balance.cantidad < 0 ? balance.cantidad : 0);

    const config = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Saldos Positivos',
                    data: positiveBalances,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Saldos Negativos',
                    data: negativeBalances,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            indexAxis: 'y',
            elements: {
                bar: {
                    borderWidth: 2,
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            aspectRatio: 2,
            plugins: {
                legend: {
                    position: 'right',
                },
                title: {
                    display: true,
                    text: 'Saldos de Usuarios'
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    };

    new Chart(ctx, config);
}

async function fetchBalances(groupId) {
    const response = await fetch('../api/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getBalances', groupId })
    });

    if (!response.ok) {
        throw new Error('Error al obtener los balances');
    }

    const result = await response.json();
    return result;
}