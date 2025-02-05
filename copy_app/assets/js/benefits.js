document.addEventListener('DOMContentLoaded', function() {
    // Animacje kart korzyści
    const benefitCards = document.querySelectorAll('.benefit-card');
    benefitCards.forEach((card, index) => {
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

    // Obserwuj karty korzyści
    benefitCards.forEach(card => observer.observe(card));

    // Animacja liczników
    const stats = document.querySelectorAll('.stat .number');
    stats.forEach(stat => {
        const target = parseInt(stat.textContent);
        const suffix = stat.textContent.replace(/[0-9]/g, '');
        let current = 0;
        const increment = target / 50; // 50 kroków animacji
        const updateCount = () => {
            if (current < target) {
                current += increment;
                if (current > target) current = target;
                stat.textContent = Math.floor(current) + suffix;
                requestAnimationFrame(updateCount);
            }
        };
        
        const statObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCount();
                    statObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        statObserver.observe(stat);
    });

    // Animacja timeline
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = index % 2 === 0 ? 'translateX(-50px)' : 'translateX(50px)';
        
        const timelineObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateX(0)';
                    entry.target.style.transition = 'all 0.6s ease-out';
                }
            });
        }, { threshold: 0.1 });
        
        timelineObserver.observe(item);
    });

    // Paralaksa dla tła
    const heroSection = document.querySelector('.benefits-hero');
    const joinSection = document.querySelector('.join-movement');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        if (heroSection) {
            heroSection.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
        
        if (joinSection) {
            joinSection.style.backgroundPositionY = (scrolled - joinSection.offsetTop) * 0.5 + 'px';
        }
    });

    // Efekt hover dla przycisków CTA
    const ctaButtons = document.querySelectorAll('.cta-button');
    ctaButtons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0, 168, 255, 0.3)';
        });

        button.addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 5px 15px rgba(0, 168, 255, 0.3)';
        });
    });
});
