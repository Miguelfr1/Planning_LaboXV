function updateBadgeuse() {
    let selectedDate = document.getElementById("date-selector").value;
    window.location.href = `badgeuse.php?date=${selectedDate}`;
}

document.addEventListener("DOMContentLoaded", function () {
    const btnEntree = document.getElementById("btn-entree");
    const btnSortie = document.getElementById("btn-sortie");

    if (btnEntree && btnEntree.disabled) {
        btnEntree.classList.add("disabled");
    }

    if (btnSortie && btnSortie.disabled) {
        btnSortie.classList.add("disabled");
    }

    // Ajouter bouton Export PDF
    const exportBtn = document.createElement("button");
    exportBtn.classList.add("export-btn");
    exportBtn.onclick = exportToPDF;
    const searchInput = document.getElementById('search-user');
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                updateFilters();
            }
        });
    }});

function openModal(userId, dayOfWeek, startTime = '', endTime = '', roles = [], breakDuration = 0) {
    document.getElementById('modal-user-id').value = userId;
    document.getElementById('modal-day').value = dayOfWeek;
    document.getElementById('modal-start-time').value = startTime;
    document.getElementById('modal-end-time').value = endTime;
    document.getElementById('modal-break-duration').value = breakDuration;
    
    // 🔥 on stocke la pause dans un attribut custom pour la relire plus tard
    document.getElementById('modal-break-duration').setAttribute('data-break', breakDuration);

    const roleSelect = document.getElementById('modal-role');
    roleSelect.innerHTML = '';

    roles.forEach(role => {
        const option = document.createElement('option');
        option.value = role;
        option.textContent = role;
        roleSelect.appendChild(option);
    });

    if (roles.length > 0) {
        roleSelect.value = roles[0];
    }

    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal').style.display = 'block';
}



function updateRoleSelected() {
    const roleSelect = document.getElementById('modal-role');
    const roleSelected = document.getElementById('role-selected');
    roleSelected.value = roleSelect.value;
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal').style.display = 'none';
}

function updateFilters() {
    const dateInput = document.getElementById('week-start-selector');
    const labInput = document.getElementById('lab-selector');
    const searchInput = document.getElementById('search-user');

    const selectedDate = new Date(dateInput.value);
    const search = searchInput ? searchInput.value.trim() : '';

    const selectedLab = labInput.value;
    if (!isNaN(selectedDate)) {
        const formattedDate = selectedDate.toISOString().split('T')[0];
        let url = `?week_start=${formattedDate}&laboratory=${selectedLab}`;
        if (search !== '') {
            url += `&search=${encodeURIComponent(search)}`;
        }
        window.location.href = url;    }
}
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
// Navigation sidebar
const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');
allSideMenu.forEach(item => {
    const li = item.parentElement;
    item.addEventListener('click', function () {
        allSideMenu.forEach(i => i.parentElement.classList.remove('active'));
        li.classList.add('active');
    });
});

// ---- TOGGLE SIDEBAR avec sauvegarde/restauration ----
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

// RESTAURATION ÉTAT
if (localStorage.getItem('sidebarState') === 'open') {
    sidebar.classList.remove('hide');
} else {
    sidebar.classList.add('hide');
}

// SAUVEGARDE ÉTAT
if (menuBar) {
    menuBar.addEventListener('click', function () {
        sidebar.classList.toggle('hide');
        if (sidebar.classList.contains('hide')) {
            localStorage.setItem('sidebarState', 'closed');
        } else {
            localStorage.setItem('sidebarState', 'open');
        }
    });
}






// Recherche responsive
const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', function (e) {
    if (window.innerWidth < 576) {
        e.preventDefault();
        searchForm.classList.toggle('show');
        if (searchForm.classList.contains('show')) {
            searchButtonIcon.classList.replace('bx-search', 'bx-x');
        } else {
            searchButtonIcon.classList.replace('bx-x', 'bx-search');
        }
    }
});

if (window.innerWidth < 768) {
    sidebar.classList.add('hide');
} else if (window.innerWidth > 576) {
    searchButtonIcon.classList.replace('bx-x', 'bx-search');
    searchForm.classList.remove('show');
}

window.addEventListener('resize', function () {
    if (this.innerWidth > 576) {
        searchButtonIcon.classList.replace('bx-x', 'bx-search');
        searchForm.classList.remove('show');
    }
});

// Dark mode
const switchMode = document.getElementById('switch-mode');
switchMode.addEventListener('change', function () {
    if (this.checked) {
        document.body.classList.add('dark');
    } else {
        document.body.classList.remove('dark');
    }
});

// Export JPG
// Export JPG (multi-page, A4 paysage)
function exportToJPG1() {
    const table = document.querySelector(".table-data");
    let weekLabel = document.querySelector(".week-selector p")?.innerText.trim() || "Semaine du :";
    let weekDate = document.getElementById("week-start-selector")?.value;
    let labElement = document.getElementById("lab-selector");
    let labName = labElement ? labElement.options[labElement.selectedIndex].text.trim() : "Inconnu";
    let options = { day: "numeric", month: "long", year: "numeric" };
    let formattedWeek = weekDate ? new Date(weekDate).toLocaleDateString("fr-FR", options) : "Inconnue";

    // Format A4 paysage (300dpi)
    const A4_WIDTH = 2480;
    const A4_HEIGHT = 1754;
    const HEADER_HEIGHT = 150; // Pour le titre

    html2canvas(table, { scale: 3, useCORS: true }).then(canvas => {
        const pageContentHeight = A4_HEIGHT - HEADER_HEIGHT;
        const scaleX = A4_WIDTH / canvas.width;
        const numPages = Math.ceil(canvas.height * scaleX / pageContentHeight);

        for (let i = 0; i < numPages; i++) {
            let newCanvas = document.createElement("canvas");
            newCanvas.width = A4_WIDTH;
            newCanvas.height = A4_HEIGHT;
            let ctx = newCanvas.getContext("2d");
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);

            // Titre
            ctx.fillStyle = "black";
            ctx.font = "bold 60px Arial";
            ctx.textAlign = "center";
            let infoText = `${weekLabel} ${formattedWeek} | Laboratoire : ${labName}`;
            ctx.fillText(infoText, newCanvas.width / 2, 100);

            // Découpe : zone à capturer dans le canvas source
            let sourceY = (i * pageContentHeight) / scaleX;
            let sourceHeight = Math.min(pageContentHeight / scaleX, canvas.height - sourceY);

            ctx.drawImage(
                canvas,
                0, sourceY, canvas.width, sourceHeight, // source (dans le canvas d'origine)
                0, HEADER_HEIGHT, A4_WIDTH, pageContentHeight // destination : tout l'espace dispo
            );

            // Génère et télécharge le JPG pour cette page
            let imgData = newCanvas.toDataURL("image/jpeg", 1.0);
            let fileName = `Planning_${labName.replace(/\s+/g, "")}_${formattedWeek.replace(/\s+/g, "")}_p${i + 1}.jpg`;

            let link = document.createElement("a");
            link.href = imgData;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
}

// Export JPG
function exportToJPG2() {
    const table = document.querySelector(".table-data");

    let weekLabel = document.querySelector(".week-selector p")?.innerText.trim() || "Semaine du :";
    let weekDate = document.getElementById("week-start-selector")?.value;
    let labElement = document.getElementById("lab-selector");
    let labName = labElement ? labElement.options[labElement.selectedIndex].text.trim() : "Inconnu";

    let options = { day: "numeric", month: "long", year: "numeric" };
    let formattedWeek = weekDate ? new Date(weekDate).toLocaleDateString("fr-FR", options) : "Inconnue";

    html2canvas(table, { scale: 3, useCORS: true }).then(canvas => {
        let canvasWidth = canvas.width;
        let canvasHeight = canvas.height;
        let extraHeight = 150;
        let newCanvas = document.createElement("canvas");
        let ctx = newCanvas.getContext("2d");

        newCanvas.width = canvasWidth;
        newCanvas.height = canvasHeight + extraHeight;
        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);
        ctx.fillStyle = "black";
        ctx.font = "bold 80px Arial";
        ctx.textAlign = "center";
        let infoText = `${weekLabel} ${formattedWeek} | Laboratoire : ${labName}`;
        ctx.fillText(infoText, newCanvas.width / 2, 100);
        ctx.drawImage(canvas, 0, extraHeight);

        let imgData = newCanvas.toDataURL("image/jpeg", 1.0);
        let fileName = `Planning_${labName.replace(/\s+/g, "")}_${formattedWeek.replace(/\s+/g, "")}.jpg`;

        let link = document.createElement("a");
        link.href = imgData;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}


// Export PDF
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF("p", "mm", "a4");

    let planningTitle = document.querySelector(".head-title h1")?.innerText.trim() || "Planning";
    let weekLabel = document.querySelector(".week-selector p")?.innerText.trim() || "Semaine du :";
    let weekDate = document.getElementById("week-start-selector")?.value;
    let labElement = document.getElementById("lab-selector");
    let labName = labElement ? labElement.options[labElement.selectedIndex].text.trim() : "Inconnu";

    let options = { day: "numeric", month: "long", year: "numeric" };
    let formattedWeek = weekDate ? new Date(weekDate).toLocaleDateString("fr-FR", options) : "Inconnue";

    let pageWidth = doc.internal.pageSize.width;
    let centerX = pageWidth / 2;

    doc.setFont("helvetica", "bold");
    doc.setFontSize(18);
    doc.text(planningTitle, centerX, 20, { align: "center" });

    doc.setFont("helvetica", "normal");
    doc.setFontSize(12);
    let infoText = `${weekLabel} ${formattedWeek} | Laboratoire : ${labName}`;
    doc.text(infoText, centerX, 30, { align: "center" });

    let table = document.querySelector(".table-data");

    html2canvas(table, { scale: 3, useCORS: true }).then(canvas => {
        let imgData = canvas.toDataURL("image/png");
        let imgWidth = pageWidth - 2;
        let imgHeight = (canvas.height * imgWidth) / canvas.width;
        let pageHeight = 277;
        let y = 40;

        if (imgHeight > pageHeight - y) {
            let totalPages = Math.ceil(imgHeight / (pageHeight - 1));
            for (let i = 0; i < totalPages; i++) {
                let cropY = i * (pageHeight - 1);
                let cropHeight = (pageHeight - 1);

                let croppedCanvas = document.createElement("canvas");
                let croppedContext = croppedCanvas.getContext("2d");
                croppedCanvas.width = canvas.width;
                croppedCanvas.height = cropHeight * (canvas.width / imgWidth);

                croppedContext.drawImage(
                    canvas,
                    0, cropY,
                    canvas.width, cropHeight * (canvas.width / imgWidth),
                    0, 0,
                    canvas.width, cropHeight * (canvas.width / imgWidth)
                );

                let croppedImgData = croppedCanvas.toDataURL("image/png");
                doc.addImage(croppedImgData, "PNG", 1, y, imgWidth, cropHeight);

                if (i < totalPages - 1) doc.addPage();
            }
        } else {
            doc.addImage(imgData, "PNG", 1, y, imgWidth, imgHeight);
        }

        let fileName = `Planning_${labName.replace(/\s+/g, "")}_${formattedWeek.replace(/\s+/g, "")}.pdf`;
        doc.save(fileName);
    });
}





