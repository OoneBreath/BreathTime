document.addEventListener('DOMContentLoaded', () => {
    const menuItems = document.querySelectorAll('.wave-menu .menu-item');
    const subpageContent = document.getElementById('subpage-content');

    menuItems.forEach(item => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            const page = item.getAttribute('href');

            fetch(page)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const textContent = doc.body.innerText;
                    subpageContent.innerText = textContent;
                })
                .catch(error => console.error('Error loading page:', error));
        });
    });
});
