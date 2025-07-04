function toggleForm() {
	document.querySelector('.create-user').classList.toggle('open');
}


document.addEventListener("DOMContentLoaded", function () {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth() + 1;

    // Premier et dernier jour du mois pour le filtrage
    const firstDay = `${year}-${month.toString().padStart(2, '0')}-01`;
    const lastDayDate = new Date(year, month, 0);
    const lastDay = `${year}-${month.toString().padStart(2, '0')}-${lastDayDate.getDate().toString().padStart(2, '0')}`;

    document.getElementById('start-date-filter').value = firstDay;
    document.getElementById('end-date-filter').value = lastDay;

    fetchConges();
    fetchEmployees();
});


function fetchConges() {
    const startDate = document.getElementById('start-date-filter').value;
    const endDate = document.getElementById('end-date-filter').value;
    
    if (!startDate || !endDate) {
        alert("Veuillez sélectionner une plage de dates");
        return;
    }

    fetch(`fetch_conges.php?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.text())
        .then(data => {
            const tableBody = document.querySelector('.table-data tbody');
            tableBody.innerHTML = data;
        })
        .catch(error => console.error('Erreur lors de la récupération des congés:', error));
}





function fetchEmployees() {
    fetch('fetch_employees.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('employee');
            select.innerHTML = '';
            data.forEach(employee => {
                const option = document.createElement('option');
                option.value = employee.id;
                option.textContent = employee.name;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Erreur lors de la récupération des employés:', error));
}
function deleteConges(id) {
    fetch('delete_conges.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
           
            fetchConges(); 
        } else {
        }
    })
    .catch(error => console.error('Erreur:', error));
}
