document.addEventListener('DOMContentLoaded', function() {
    // Animacja liczników
    const counters = document.querySelectorAll('.counter');
    const speed = 200;

    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        let count = 0;
        const increment = target / speed;

        const updateCount = () => {
            if (count < target) {
                count += increment;
                counter.innerText = Math.ceil(count);
                requestAnimationFrame(updateCount);
            } else {
                counter.innerText = target;
            }
        };

        updateCount();
    };

    // Obserwator przewijania dla liczników
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.5
    });

    counters.forEach(counter => counterObserver.observe(counter));

    // Animacje kart
    const cards = document.querySelectorAll('.partner-card, .level-card');
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

    // Slider historii sukcesu
    const slider = document.querySelector('.stories-slider');
    const slides = document.querySelectorAll('.story-card');
    const prevButton = document.querySelector('.prev-slide');
    const nextButton = document.querySelector('.next-slide');
    let currentSlide = 0;

    function updateSlider() {
        slides.forEach((slide, index) => {
            if (index === currentSlide) {
                slide.style.display = 'grid';
                slide.style.opacity = '1';
            } else {
                slide.style.display = 'none';
                slide.style.opacity = '0';
            }
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlider();
    }

    if (prevButton && nextButton) {
        prevButton.addEventListener('click', prevSlide);
        nextButton.addEventListener('click', nextSlide);
    }

    // Automatyczne przewijanie slajdów
    let slideInterval = setInterval(nextSlide, 5000);

    // Zatrzymaj automatyczne przewijanie przy hover
    if (slider) {
        slider.addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });

        slider.addEventListener('mouseleave', () => {
            slideInterval = setInterval(nextSlide, 5000);
        });
    }

    // Inicjalizacja pierwszego slajdu
    updateSlider();

    // Obsługa FAQ
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Zamknij wszystkie aktywne elementy
            faqItems.forEach(faqItem => {
                faqItem.classList.remove('active');
                const answer = faqItem.querySelector('.faq-answer');
                answer.style.maxHeight = '0';
            });

            // Jeśli kliknięty element nie był aktywny, otwórz go
            if (!isActive) {
                item.classList.add('active');
                const answer = item.querySelector('.faq-answer');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });

    // Płynne przewijanie do sekcji
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Efekt paralaksy dla tła
    const heroSection = document.querySelector('.partners-hero');
    window.addEventListener('scroll', () => {
        if (heroSection) {
            const scrolled = window.pageYOffset;
            heroSection.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });

    // Animacje hover dla przycisków
    const buttons = document.querySelectorAll('.level-button, .hero-button');
    buttons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-5px)';
        });

        button.addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Obsługa formularza kontaktowego w sekcji dołącz
    const levelButtons = document.querySelectorAll('.level-button');
    levelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Zapisz wybrany poziom partnerstwa w sessionStorage
            const level = this.closest('.level-card').querySelector('h3').textContent;
            sessionStorage.setItem('selectedPartnershipLevel', level);
        });
    });
});
