// Obsługa nawigacji
document.addEventListener('DOMContentLoaded', function() {
    const isMainWindow = window === window.top;
    
    if (isMainWindow) {
        // Główne elementy nawigacji
        const iframe = document.getElementById('content-frame');
        const activeTab = document.getElementById('active-tab');
        const motto = document.querySelector('.main-text');

        // Obsługa kliknięć w menu
        document.querySelectorAll('.menu-item, .book-tab').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                if (href.includes('breathtime.html') || href.includes('benefits.html')) {
                    if (activeTab) {
                        activeTab.textContent = '';
                    }
                } else {
                    const title = this.textContent.trim();
                    if (activeTab) {
                        activeTab.textContent = title;
                    }
                }

                if (iframe) {
                    iframe.classList.remove('visible', 'slide-in');
                    iframe.style.opacity = '0';
                    iframe.style.transform = 'translateY(50px)';
                    
                    iframe.src = href;
                    iframe.onload = () => {
                        setTimeout(() => {
                            iframe.style.opacity = '1';
                            iframe.style.transform = 'translateY(0)';
                            iframe.classList.add('slide-in');
                        }, 100);
                    };
                }
                
                if (motto) {
                    motto.style.opacity = '0';
                    motto.style.visibility = 'hidden';
                }
            });
        });

        // Obsługa powrotu do strony głównej
        document.querySelector('.logo').addEventListener('click', function(e) {
            e.preventDefault();
            if (iframe) {
                iframe.src = '';
                iframe.classList.remove('visible');
            }
            if (motto) {
                motto.style.opacity = '1';
                motto.style.visibility = 'visible';
            }
            if (activeTab) {
                activeTab.textContent = '';
            }
        });

        // Funkcja do otwierania formularza roli w nowym oknie
        window.loadRoleForm = function(role, type) {
            const width = Math.min(600, window.screen.availWidth * 0.9);
            const height = Math.min(800, window.screen.availHeight * 0.9);
            const left = (screen.width - width) / 2;
            const top = (screen.height - height) / 2;

            const roleWindow = window.open(
                `role-form.php?role=${role}&type=${type}`,
                'roleForm',
                `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
            );

            if (roleWindow) {
                roleWindow.focus();
            }
        };

        // Nasłuchiwanie na wiadomości z iframe
        window.addEventListener('message', function(event) {
            if (event.data.type === 'pageTitle') {
                if (!event.data.isBreathTime) {
                    if (activeTab) {
                        activeTab.textContent = event.data.title;
                    }
                }
            }
        });
    } else {
        // Jesteśmy w iframe
        const currentTitle = document.title.split(' - ')[0];
        const isBreathTime = window.location.href.includes('breathtime.html') || 
                            window.location.href.includes('benefits.html');

        // Wyślij tytuł strony do rodzica
        window.parent.postMessage({
            type: 'pageTitle',
            title: currentTitle,
            isBreathTime: isBreathTime
        }, '*');
    }
});
