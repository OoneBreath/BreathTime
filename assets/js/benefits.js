document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja efektu Tilt dla kart
    VanillaTilt.init(document.querySelectorAll(".benefit-card"), {
        max: 15,
        speed: 400,
        glare: true,
        "max-glare": 0.2,
    });

    // Animacja statystyk
    const stats = document.querySelectorAll('.stat');
    
    const animateStats = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const stat = entry.target;
                const targetValue = parseInt(stat.dataset.value);
                const numberElement = stat.querySelector('.stat-number');
                let currentValue = 0;
                
                const updateValue = () => {
                    const increment = targetValue / 50; // 50 kroków animacji
                    currentValue += increment;
                    
                    if (currentValue >= targetValue) {
                        currentValue = targetValue;
                        numberElement.textContent = targetValue.toLocaleString();
                        observer.unobserve(stat);
                    } else {
                        numberElement.textContent = Math.floor(currentValue).toLocaleString();
                        requestAnimationFrame(updateValue);
                    }
                };
                
                updateValue();
            }
        });
    };

    const statsObserver = new IntersectionObserver(animateStats, {
        threshold: 0.5
    });

    stats.forEach(stat => statsObserver.observe(stat));

    // Efekt parallax dla contentu
    const parallaxContainer = document.querySelector('.parallax-container');
    const contentBox = document.querySelector('.content-box');

    parallaxContainer.addEventListener('scroll', () => {
        const scrolled = parallaxContainer.scrollTop;
        contentBox.style.transform = `translateZ(-${scrolled * 0.1}px)`;
    });

    // Animacja wejścia kart
    const cards = document.querySelectorAll('.benefit-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 200 * (index + 1));
    });
});
