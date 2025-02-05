// Obsługa nawigacji
document.addEventListener('DOMContentLoaded', function() {
    // Sprawdź, czy jesteśmy na stronie głównej
    if (window === window.top) {
        // Jesteśmy na stronie głównej
        const iframe = document.getElementById('content-frame');
        const activeTab = document.getElementById('active-tab');
        const motto = document.querySelector('.main-text');

        // Obsługa kliknięć w menu
        document.querySelectorAll('.menu-item, .book-tab').forEach(link => {
            link.addEventListener('click', function(e) {
                const title = this.textContent.trim();
                if (activeTab) {
                    activeTab.textContent = title;
                }
                if (iframe) {
                    iframe.style.opacity = '1';
                }
                if (motto) {
                    motto.style.opacity = '0';
                    motto.style.visibility = 'hidden';
                }
            });
        });

        // Obsługa powrotu do strony głównej
        document.querySelector('.logo').addEventListener('click', function() {
            if (activeTab) {
                activeTab.textContent = '';
            }
            if (iframe) {
                iframe.style.opacity = '0';
            }
            if (motto) {
                motto.style.opacity = '1';
                motto.style.visibility = 'visible';
            }
        });

        // Nasłuchiwanie na wiadomości z iframe
        window.addEventListener('message', function(event) {
            if (event.data.type === 'pageTitle') {
                if (activeTab) {
                    activeTab.textContent = event.data.title;
                }
            }
        });
    } else {
        // Jesteśmy w iframe
        const currentTitle = document.title.split(' - ')[0];
        window.parent.postMessage({
            type: 'pageTitle',
            title: currentTitle
        }, '*');
    }
});
