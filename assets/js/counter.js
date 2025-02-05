// Funkcja formatująca liczby
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

// Funkcja aktualizująca licznik
async function updateCounter() {
    try {
        // Najpierw zwiększ licznik
        const incrementResponse = await fetch('counter.php', {
            method: 'POST'
        });
        const incrementData = await incrementResponse.json();
        
        // Pobierz aktualną wartość
        const getResponse = await fetch('counter.php');
        const getData = await getResponse.json();
        
        // Zaktualizuj tekst w pasku informacyjnym
        const newsContent = document.querySelector('.news-ticker-content');
        if (newsContent) {
            const visitorCount = formatNumber(getData.count);
            const originalText = newsContent.textContent;
            newsContent.innerHTML = `${originalText} Odwiedziło nas już ${visitorCount} osób!`;
        }
    } catch (error) {
        console.error('Błąd podczas aktualizacji licznika:', error);
    }
}

// Wywołaj aktualizację przy załadowaniu strony
document.addEventListener('DOMContentLoaded', updateCounter);
