function formatNameLinebreak(nomComplet) {
    if (!nomComplet) return "";
    const parts = nomComplet.trim().split(' ');
    let prenom = [];
    let nom = [];

    // On considère tout mot en MAJUSCULE (au moins 2 lettres) comme partie du nom
    parts.forEach(part => {
        if (part.length > 1 && part === part.toUpperCase()) {
            nom.push(part);
        } else {
            prenom.push(part);
        }
    });

    if (prenom.length > 0 && nom.length > 0) {
        return `${prenom.join(' ')}<br><b>${nom.join(' ')}</b>`;
    }
    return nomComplet;
}



function toggleForm() {
	document.querySelector('.create-user').classList.toggle('open');
}

const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
const li = item.parentElement;

item.addEventListener('click', function () {
	allSideMenu.forEach(i=> {
		i.parentElement.classList.remove('active');
	})
	li.classList.add('active');
})
});


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
                        <td class="col-nom" style="font-weight:bold;">${formatNameLinebreak(employee.nom)}</td>
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
	


// TOGGLE SIDEBAR
// TOGGLE SIDEBAR avec sauvegarde localStorage
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

// ---- RESTAURATION au chargement ----
if (localStorage.getItem('sidebarState') === 'open') {
    sidebar.classList.remove('hide');
} else {
    sidebar.classList.add('hide');
}

// ---- SAUVEGARDE au clic ----
menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
    if (sidebar.classList.contains('hide')) {
        localStorage.setItem('sidebarState', 'closed');
    } else {
        localStorage.setItem('sidebarState', 'open');
    }
});








const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', function (e) {
if(window.innerWidth < 576) {
	e.preventDefault();
	searchForm.classList.toggle('show');
	if(searchForm.classList.contains('show')) {
		searchButtonIcon.classList.replace('bx-search', 'bx-x');
	} else {
		searchButtonIcon.classList.replace('bx-x', 'bx-search');
	}
}
})





if(window.innerWidth < 768) {
sidebar.classList.add('hide');
} else if(window.innerWidth > 576) {
searchButtonIcon.classList.replace('bx-x', 'bx-search');
searchForm.classList.remove('show');
}


window.addEventListener('resize', function () {
if(this.innerWidth > 576) {
	searchButtonIcon.classList.replace('bx-x', 'bx-search');
	searchForm.classList.remove('show');
}
})



const switchMode = document.getElementById('switch-mode');

switchMode.addEventListener('change', function () {
if(this.checked) {
	document.body.classList.add('dark');
} else {
	document.body.classList.remove('dark');
}
})


