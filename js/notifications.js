function updateNotificationBadge() {
    fetch('/ajax/notifications_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'block' : 'none';
            }
        });
}

function loadNotifications() {
    fetch('/ajax/notifications.php')
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector('.notifications-dropdown');
            if (container) {
                container.innerHTML = '';
                
                if (data.notifications.length === 0) {
                    container.innerHTML = '<div class="no-notifications">Brak powiadomień</div>';
                    return;
                }

                data.notifications.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = `notification-item ${notification.read_at ? '' : 'unread'}`;
                    
                    if (notification.link) {
                        item.innerHTML = `<a href="${notification.link}" class="notification-link">`;
                    }
                    
                    item.innerHTML += `
                        <div class="notification-content">
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${notification.created_at}</div>
                        </div>
                    `;
                    
                    if (notification.link) {
                        item.innerHTML += '</a>';
                    }
                    
                    container.appendChild(item);
                });
            }
        });
}

// Aktualizuj co 30 sekund
setInterval(updateNotificationBadge, 30000);

// Inicjalizacja
document.addEventListener('DOMContentLoaded', () => {
    updateNotificationBadge();
    
    // Obsługa kliknięcia w dzwonek
    const bell = document.querySelector('.notifications-bell');
    if (bell) {
        bell.addEventListener('click', (e) => {
            e.preventDefault();
            loadNotifications();
        });
    }
});
