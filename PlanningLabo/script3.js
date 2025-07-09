document.addEventListener('click', function(e) {
    // MODIFIER
    if (e.target.closest('.btn-edit')) {
        const btn = e.target.closest('.btn-edit');
        const absences = JSON.parse(btn.getAttribute('data-absences'));
        if (absences.length === 1) {
            // Un seul congé, redirige direct
            window.location.href = 'edit_conges.php?id=' + absences[0].id;
        } else {
            showAbsenceChoice(absences, 'edit');
        }
    }
    // SUPPRIMER
    if (e.target.closest('.btn-delete')) {
        const btn = e.target.closest('.btn-delete');
        const absences = JSON.parse(btn.getAttribute('data-absences'));
        if (absences.length === 1) {
            deleteConges(absences[0].id);
        } else {
            showAbsenceChoice(absences, 'delete');
        }
    }
});

// Petite fonction pour afficher la liste des congés à choisir
function showAbsenceChoice(absences, action) {
    document.getElementById('absence-choice-modal')?.remove();

    // Fonction pour formater une date ISO en jj/mm/aaaa
    function formatDateFr(isoDate) {
        const d = new Date(isoDate);
        const day = d.getDate().toString().padStart(2, '0');
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    let html = `<div id="absence-choice-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(0,0,0,0.2);display:flex;align-items:center;justify-content:center;">
    <div style="background:#fff;border:2px solid #3498db;border-radius:8px;padding:24px;min-width:320px;text-align:center">
        <h3>Choisissez le congé à ${action === 'edit' ? 'modifier' : 'supprimer'} :</h3>
        <ul style="list-style:none;padding:0;margin:0;">`;
    
    absences.forEach(abs => {
        const startFr = formatDateFr(abs.start);
        const endFr = formatDateFr(abs.end);
        html += `<li style="margin:10px 0">
            <button style="background:#eee;border:1px solid #bbb;border-radius:4px;padding:10px 18px;font-size:1em;cursor:pointer;"
                onclick="handleAbsenceAction('${abs.id}', '${action}')">
                Du ${startFr} au ${endFr}
            </button>
        </li>`;
    });

    html += `</ul>
        <button style="margin-top:16px;background:#eee;border:1px solid #bbb;border-radius:4px;padding:7px 20px;cursor:pointer" onclick="document.getElementById('absence-choice-modal').remove()">Annuler</button>
    </div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
}


// Handler JS global
function handleAbsenceAction(id, action) {
    document.getElementById('absence-choice-modal')?.remove();
    if (action === 'edit') {
        window.location.href = 'edit_conges.php?id=' + id;
    } else if (action === 'delete') {
        deleteConges(id);
    }
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
    const month = today.getMonth() + 1;

    // Premier et dernier jour du mois pour le filtrage
    const firstDay = `${year}-${month.toString().padStart(2, '0')}-01`;
    const lastDayDate = new Date(year, month, 0);
    const lastDay = `${year}-${month.toString().padStart(2, '0')}-${lastDayDate.getDate().toString().padStart(2, '0')}`;

    const startInput = document.getElementById('start-date-filter');
    const endInput = document.getElementById('end-date-filter');
    if (startInput && !startInput.value) startInput.value = firstDay;
    if (endInput && !endInput.value) endInput.value = lastDay;

    fetchConges();
    fetchEmployees();


    const labSelector = document.getElementById('lab-selector');
    const allLabsToggle = document.getElementById('all-labs-toggle');

    function updateLabSelectState() {
        if (!labSelector || !allLabsToggle) return;
        if (allLabsToggle.checked) {
            labSelector.disabled = true;
            labSelector.classList.add('select-disabled');
        } else {
            labSelector.disabled = false;
            labSelector.classList.remove('select-disabled');
        }
    }

    updateLabSelectState();
    if (labSelector) labSelector.addEventListener('change', updateFilters);
    if (allLabsToggle) allLabsToggle.addEventListener('change', () => { updateLabSelectState(); updateFilters(); });});

    function fetchConges() {
        const startDate = document.getElementById('start-date-filter').value;
        const endDate = document.getElementById('end-date-filter').value;
    
        if (!startDate || !endDate) {
            alert("Veuillez sélectionner une plage de dates");
            return;
        }
    
        const labSelector = document.getElementById('lab-selector');
        const allLabsToggle = document.getElementById('all-labs-toggle');
    
        let url = `fetch_conges.php?start_date=${startDate}&end_date=${endDate}`;
        if (allLabsToggle && allLabsToggle.checked) {
            url += `&all_labs=1`;
        } else if (labSelector) {
            url += `&laboratory=${labSelector.value}`;
        }
    
        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('grille-conges').innerHTML = data; // Remplace tout le tableau
            })
            .catch(error => console.error('Erreur lors de la récupération des congés:', error));
    }
    
function updateFilters() {
    const startDate = document.getElementById('start-date-filter').value;
    const endDate = document.getElementById('end-date-filter').value;
    const labSelector = document.getElementById('lab-selector');
    const allLabsToggle = document.getElementById('all-labs-toggle');

    let url = `?start_date=${startDate}&end_date=${endDate}`;
    if (allLabsToggle && allLabsToggle.checked) {
        url += `&all_labs=1`;
    } else if (labSelector) {
        url += `&laboratory=${labSelector.value}`;
    }

    window.history.replaceState(null, '', url);
    fetchConges();
}



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



