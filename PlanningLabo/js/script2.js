function toggleForm() {
	document.querySelector('.create-user').classList.toggle('open');
}



document.addEventListener("DOMContentLoaded", function () {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth() + 1; // Les mois commencent à 0 en JavaScript, donc on ajoute 1.

    // Premier jour du mois
    const firstDay = `${year}-${month.toString().padStart(2, '0')}-01`;

    // Dernier jour du mois
    const lastDayDate = new Date(year, month, 0); // Jour 0 du mois suivant donne le dernier jour du mois actuel
    const lastDay = `${year}-${month.toString().padStart(2, '0')}-${lastDayDate.getDate().toString().padStart(2, '0')}`;

    document.getElementById('start-date').value = firstDay;
    document.getElementById('end-date').value = lastDay;

    fetchHours();
});


function fetchHours() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    if (!startDate || !endDate) {
        alert("Veuillez sélectionner une plage de dates");
        return;
    }
    fetch(`fetch_hours.php?start=${startDate}&end=${endDate}`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('employee-hours');
            tableBody.innerHTML = '';
 // Tri par nom de famille (partie en MAJUSCULE)
 function getNomFamille(nomComplet) {
    const parts = nomComplet.trim().split(' ');
    for (let i = parts.length - 1; i >= 0; i--) {
        if (parts[i].toUpperCase() === parts[i] && parts[i].length > 1) {
            return parts[i];
        }
    }
    return parts[parts.length - 1];
}

data.sort((a, b) => {
    const nomA = getNomFamille(a.nom);
    const nomB = getNomFamille(b.nom);
    return nomA.localeCompare(nomB, 'fr', { sensitivity: 'base' });
});
            data.forEach(employee => {
                const row = `
                    <tr>
                        <td class="col-nom" style="font-weight:bold;">${employee.nom}</td>
                        <td>${Number(employee.heures).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
                        <td>${Number(employee.heures_supp_25).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
                        <td>${Number(employee.heures_supp_50).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
                        <td>${Number(employee.heures_dimanche).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
                        <td>${Number(employee.heures_feries).toLocaleString('fr-FR', { minimumFractionDigits: 2 })}</td>
                        <td class="col-diff">${employee.difference_total}</td>

                           
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        })
        .catch(error => console.error('Erreur lors de la récupération des données:', error));
}
	


