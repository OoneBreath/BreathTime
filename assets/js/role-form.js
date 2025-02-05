// Funkcja do ładowania formularza
function loadRoleForm(role, type) {
    const overlay = document.createElement('div');
    overlay.className = 'role-overlay';
    document.body.appendChild(overlay);

    // Ładowanie zawartości formularza
    fetch(`role-form-content.php?role=${role}&type=${type}`)
        .then(response => response.text())
        .then(html => {
            overlay.innerHTML = html;
        });
}

// Funkcja zamykająca formularz
function closeRoleForm() {
    const overlay = document.querySelector('.role-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Funkcja wysyłająca formularz
function submitRoleForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('register-role.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Twoja prośba o rolę została przyjęta. Powiadomimy Cię o decyzji mailowo.');
            closeRoleForm();
        } else {
            alert(data.message || 'Wystąpił błąd podczas wysyłania formularza');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas wysyłania formularza');
    });
}

// Zamykanie formularza po kliknięciu poza nim
document.addEventListener('click', function(event) {
    const overlay = document.querySelector('.role-overlay');
    const container = document.querySelector('.role-container');
    if (overlay && event.target === overlay && !container.contains(event.target)) {
        closeRoleForm();
    }
});

// Sprawdź czy powinniśmy otworzyć formularz roli
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get('role');
    const type = urlParams.get('type');
    if (role) {
        loadRoleForm(role, type);
    }
});
