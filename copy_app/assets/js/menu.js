document.addEventListener('DOMContentLoaded', function() {
    // Pobierz wszystkie linki menu
    const menuItems = document.querySelectorAll('.menu-item');
    
    // Dodaj obsługę kliknięcia dla każdego elementu menu
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Usuń klasę active ze wszystkich elementów
            menuItems.forEach(i => i.classList.remove('active'));
            // Dodaj klasę active do klikniętego elementu
            this.classList.add('active');
        });
    });

    // Sprawdź aktualny URL i ustaw odpowiedni element jako aktywny
    const currentHash = window.location.hash;
    if (currentHash) {
        const activeItem = document.querySelector(`a[href="${currentHash}"]`);
        if (activeItem) {
            menuItems.forEach(i => i.classList.remove('active'));
            activeItem.classList.add('active');
        }
    }
});
