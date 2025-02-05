document.addEventListener('DOMContentLoaded', function() {
    // Animacje kart
    const cards = document.querySelectorAll('.featured-post, .post-card');
    cards.forEach((card, index) => {
        card.style.setProperty('--animation-order', index);
    });

    // Obserwator przewijania dla animacji
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1
    });

    cards.forEach(card => observer.observe(card));

    // Filtrowanie kategorii
    const categoryButtons = document.querySelectorAll('.category-button');
    const posts = document.querySelectorAll('.post-card');

    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Usuń aktywną klasę ze wszystkich przycisków
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            // Dodaj aktywną klasę do klikniętego przycisku
            this.classList.add('active');

            const category = this.dataset.category;

            posts.forEach(post => {
                if (category === 'all' || post.dataset.category === category) {
                    post.style.display = 'block';
                    setTimeout(() => {
                        post.style.opacity = '1';
                        post.style.transform = 'translateY(0)';
                    }, 100);
                } else {
                    post.style.opacity = '0';
                    post.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        post.style.display = 'none';
                    }, 300);
                }
            });
        });
    });

    // Wyszukiwarka
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.toLowerCase();

            posts.forEach(post => {
                const title = post.querySelector('h3').textContent.toLowerCase();
                const excerpt = post.querySelector('.post-excerpt')?.textContent.toLowerCase() || '';
                const category = post.dataset.category.toLowerCase();

                if (title.includes(searchTerm) || 
                    excerpt.includes(searchTerm) || 
                    category.includes(searchTerm)) {
                    post.style.display = 'block';
                    setTimeout(() => {
                        post.style.opacity = '1';
                        post.style.transform = 'translateY(0)';
                    }, 100);
                } else {
                    post.style.opacity = '0';
                    post.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        post.style.display = 'none';
                    }, 300);
                }
            });
        }, 300);
    });

    // Ładowanie więcej artykułów
    const loadMoreButton = document.querySelector('.load-more');
    let page = 1;

    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', function() {
            this.textContent = 'Ładowanie...';
            this.disabled = true;

            // Symulacja ładowania nowych artykułów
            setTimeout(() => {
                // Tu można dodać prawdziwe ładowanie z API
                const newPosts = generateMorePosts();
                const postsGrid = document.querySelector('.posts-grid');
                
                newPosts.forEach(post => {
                    postsGrid.appendChild(post);
                    observer.observe(post);
                });

                page++;
                
                // Przywróć przycisk do normalnego stanu
                this.textContent = 'Załaduj więcej artykułów';
                this.disabled = false;

                // Jeśli to ostatnia strona, ukryj przycisk
                if (page >= 3) {
                    this.style.display = 'none';
                }
            }, 1000);
        });
    }

    // Funkcja generująca nowe artykuły (przykład)
    function generateMorePosts() {
        const categories = ['breathing', 'meditation', 'health', 'lifestyle', 'science'];
        const posts = [];

        for (let i = 0; i < 3; i++) {
            const article = document.createElement('article');
            article.className = 'post-card';
            article.dataset.category = categories[Math.floor(Math.random() * categories.length)];
            article.innerHTML = `
                <div class="post-image">
                    <img src="images/blog/post-${Math.floor(Math.random() * 3) + 1}.jpg" alt="Artykuł">
                    <div class="post-category">${article.dataset.category}</div>
                </div>
                <div class="post-content">
                    <h3>Przykładowy tytuł artykułu ${page * 3 + i + 1}</h3>
                    <p class="post-excerpt">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
                        Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </p>
                    <div class="post-meta">
                        <span class="post-date">${new Date().toLocaleDateString()}</span>
                        <span class="post-read-time">${Math.floor(Math.random() * 5 + 3)} min czytania</span>
                    </div>
                    <a href="#" class="read-more">Czytaj więcej →</a>
                </div>
            `;
            posts.push(article);
        }

        return posts;
    }

    // Newsletter
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const email = this.querySelector('input[type="email"]').value;
            const button = this.querySelector('button');
            const originalText = button.innerHTML;

            button.innerHTML = 'Zapisywanie...';
            button.disabled = true;

            // Symulacja wysyłania
            setTimeout(() => {
                button.innerHTML = 'Zapisano! ✓';
                button.style.backgroundColor = '#4CAF50';

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                    this.reset();
                }, 2000);
            }, 1500);
        });
    }

    // Efekt paralaksy dla tła
    const heroSection = document.querySelector('.blog-hero');
    window.addEventListener('scroll', () => {
        if (heroSection) {
            const scrolled = window.pageYOffset;
            heroSection.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });

    // Animacje tagów
    const tags = document.querySelectorAll('.tag');
    tags.forEach(tag => {
        tag.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-5px)';
        });

        tag.addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
