document.addEventListener('DOMContentLoaded', function() {
    // Animacje kart
    const cards = document.querySelectorAll('.volunteer-card, .support-card, .partnership-card');
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

    // Obsługa przycisków kwot
    const amountButtons = document.querySelectorAll('.amount-btn');
    amountButtons.forEach(button => {
        button.addEventListener('click', function() {
            amountButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            if (this.classList.contains('custom')) {
                const amount = prompt('Wprowadź kwotę wsparcia (PLN):', '');
                if (amount) {
                    this.textContent = amount + ' PLN';
                }
            }
        });
    });

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

    // Obsługa formularza darowizny
    const donateButtons = document.querySelectorAll('.donate-btn');
    donateButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Przykładowa walidacja i przygotowanie danych
            const card = this.closest('.support-card');
            let amount = '0';
            let type = 'one-time';

            if (card.querySelector('.amount-buttons')) {
                const activeBtn = card.querySelector('.amount-btn.active');
                amount = activeBtn ? activeBtn.textContent.replace(' PLN', '') : '0';
                type = 'one-time';
            } else {
                const selectedOption = card.querySelector('input[name="subscription"]:checked');
                type = selectedOption ? selectedOption.value : 'monthly';
                amount = '20'; // Domyślna kwota dla subskrypcji
            }

            // Tutaj można dodać integrację z systemem płatności
            console.log('Przygotowanie płatności:', { amount, type });
            
            // Animacja przycisku
            this.textContent = 'Przetwarzanie...';
            this.disabled = true;
            
            setTimeout(() => {
                alert('Dziękujemy za chęć wsparcia! Ta funkcja będzie dostępna wkrótce.');
                this.textContent = type === 'one-time' ? 'Wesprzyj teraz' : 'Rozpocznij wsparcie';
                this.disabled = false;
            }, 2000);
        });
    });

    // Efekt paralaksy dla tła
    const heroSection = document.querySelector('.help-hero');
    window.addEventListener('scroll', () => {
        if (heroSection) {
            const scrolled = window.pageYOffset;
            heroSection.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });

    // Animacja przycisków
    const buttons = document.querySelectorAll('.action-button, .partner-button, .hero-button');
    buttons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-5px)';
        });

        button.addEventListener('mouseout', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
