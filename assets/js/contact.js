document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja mapy Google
    function initMap() {
        const location = { lat: 52.2297, lng: 21.0122 }; // Warszawa
        const map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
            center: location,
            styles: [
                {
                    "featureType": "all",
                    "elementType": "geometry",
                    "stylers": [{"color": "#242f3e"}]
                },
                {
                    "featureType": "all",
                    "elementType": "labels.text.stroke",
                    "stylers": [{"color": "#242f3e"}]
                },
                {
                    "featureType": "all",
                    "elementType": "labels.text.fill",
                    "stylers": [{"color": "#746855"}]
                }
            ]
        });

        const marker = new google.maps.Marker({
            position: location,
            map: map,
            title: 'BreathTime'
        });
    }

    // Inicjalizacja mapy jeśli API jest załadowane
    if (typeof google !== 'undefined') {
        initMap();
    }

    // Obsługa formularza kontaktowego
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Zbieranie danych z formularza
            const formData = new FormData(this);

            // Animacja przycisku
            const submitButton = this.querySelector('.submit-button');
            const buttonText = submitButton.querySelector('.button-text');
            const originalText = buttonText.textContent;
            
            submitButton.disabled = true;
            buttonText.textContent = 'Wysyłanie...';

            // Wysyłanie danych do serwera
            fetch('contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    buttonText.textContent = 'Wysłano!';
                    submitButton.style.backgroundColor = '#4CAF50';
                    contactForm.reset();
                } else {
                    buttonText.textContent = 'Błąd!';
                    submitButton.style.backgroundColor = '#f44336';
                    alert(data.message || 'Wystąpił błąd podczas wysyłania wiadomości');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                buttonText.textContent = 'Błąd!';
                submitButton.style.backgroundColor = '#f44336';
                alert('Wystąpił błąd podczas wysyłania wiadomości');
            })
            .finally(() => {
                // Reset po 2 sekundach
                setTimeout(() => {
                    buttonText.textContent = originalText;
                    submitButton.disabled = false;
                    submitButton.style.backgroundColor = '';
                }, 2000);
            });
        });

        // Obsługa pól formularza
        const formInputs = contactForm.querySelectorAll('input, textarea');
        formInputs.forEach(input => {
            // Dodaj placeholder do obsługi animacji label
            input.setAttribute('placeholder', ' ');

            // Animacja label przy fokusie
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });

            // Sprawdź czy pole ma wartość przy załadowaniu
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    }

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

    // Efekt paralaksy dla tła
    const heroSection = document.querySelector('.contact-hero');
    window.addEventListener('scroll', () => {
        if (heroSection) {
            const scrolled = window.pageYOffset;
            heroSection.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });

    // Animacje przy przewijaniu
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

    // Obserwuj elementy do animacji
    document.querySelectorAll('.contact-form-container, .info-section').forEach(
        element => observer.observe(element)
    );

    // Walidacja formularza
    function validateForm() {
        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const subject = document.getElementById('subject');
        const message = document.getElementById('message');

        let isValid = true;

        // Proste sprawdzenie emaila
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            showError(email, 'Wprowadź poprawny adres email');
            isValid = false;
        } else {
            removeError(email);
        }

        // Sprawdzenie długości wiadomości
        if (message.value.length < 10) {
            showError(message, 'Wiadomość musi mieć co najmniej 10 znaków');
            isValid = false;
        } else {
            removeError(message);
        }

        return isValid;
    }

    function showError(element, message) {
        const errorDiv = element.parentElement.querySelector('.error-message') || 
                        document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        if (!element.parentElement.querySelector('.error-message')) {
            element.parentElement.appendChild(errorDiv);
        }
        
        element.classList.add('error');
    }

    function removeError(element) {
        const errorDiv = element.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
        element.classList.remove('error');
    }
});
