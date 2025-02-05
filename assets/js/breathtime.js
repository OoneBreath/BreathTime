document.addEventListener('DOMContentLoaded', function() {
    // Odliczanie do następnego Breath Time
    function updateCountdown() {
        const now = new Date();
        const nextBreathTime = new Date();
        
        // Ustawienie następnego Breath Time na 12:00
        nextBreathTime.setHours(12, 0, 0, 0);
        
        // Jeśli już po 12:00, ustaw na jutro
        if (now > nextBreathTime) {
            nextBreathTime.setDate(nextBreathTime.getDate() + 1);
        }
        
        const diff = nextBreathTime - now;
        
        // Obliczanie godzin, minut i sekund
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        // Aktualizacja wyświetlania
        document.querySelector('.countdown-item:nth-child(1) .number').textContent = 
            hours.toString().padStart(2, '0');
        document.querySelector('.countdown-item:nth-child(2) .number').textContent = 
            minutes.toString().padStart(2, '0');
        document.querySelector('.countdown-item:nth-child(3) .number').textContent = 
            seconds.toString().padStart(2, '0');
    }
    
    // Aktualizacja co sekundę
    setInterval(updateCountdown, 1000);
    updateCountdown(); // Pierwsza aktualizacja
    
    // Inicjalizacja mapy Three.js
    const container = document.getElementById('world-map');
    if (container) {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(
            75, 
            container.clientWidth / container.clientHeight, 
            0.1, 
            1000
        );
        
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        container.appendChild(renderer.domElement);
        
        // Tworzenie kuli ziemskiej
        const geometry = new THREE.SphereGeometry(5, 32, 32);
        const material = new THREE.MeshBasicMaterial({
            color: 0x00a8ff,
            wireframe: true,
            transparent: true,
            opacity: 0.3
        });
        const globe = new THREE.Mesh(geometry, material);
        scene.add(globe);
        
        camera.position.z = 10;

        // Fizyka kuli
        const physics = {
            velocity: { x: 0, y: 0 },
            target: { x: 0, y: 0 },
            momentum: 0.98,              // Wysokie momentum dla płynności
            sensitivity: 0.12,           // Jeszcze większa czułość
            attraction: 0.15,            // Większa siła przyciągania
            baseSpeed: 0.0003,
            isMouseMoving: false,
            mouseTimer: null,
            lastDelta: { x: 0, y: 0 },
            autoRotation: 0             // Stopień automatycznej rotacji (0-1)
        };

        // Śledzenie myszki
        container.addEventListener('mousemove', function(event) {
            const rect = container.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            physics.isMouseMoving = true;
            physics.autoRotation = 0;    // Natychmiast wyłącz auto-rotację
            clearTimeout(physics.mouseTimer);
            
            // Zwiększony czas oczekiwania do 1.5 sekundy
            physics.mouseTimer = setTimeout(() => {
                physics.isMouseMoving = false;
            }, 1500);
            
            const targetX = ((event.clientX - centerX) / (rect.width / 2)) * Math.PI * 2.0;  // Zwiększony mnożnik X
            const targetY = ((event.clientY - centerY) / (rect.height / 2)) * Math.PI * 1.0; // Zwiększony mnożnik Y
            
            physics.lastDelta.x = targetX - physics.target.x;
            physics.lastDelta.y = targetY - physics.target.y;
            
            physics.target.x = targetX;
            physics.target.y = targetY;
        });

        container.addEventListener('mouseleave', function() {
            physics.target.x = 0;
            physics.target.y = 0;
            physics.lastDelta.x = 0;
            physics.lastDelta.y = 0;
            physics.isMouseMoving = false;
            physics.autoRotation = 0;    // Reset auto-rotacji
            clearTimeout(physics.mouseTimer);
        });
        
        // Animacja
        function animate() {
            requestAnimationFrame(animate);

            if (physics.isMouseMoving) {
                // Podczas ruchu myszki - tylko śledzenie z inercją
                let deltaX = physics.target.x - globe.rotation.y;
                deltaX = ((deltaX + Math.PI) % (Math.PI * 2)) - Math.PI;
                
                physics.velocity.x = physics.velocity.x * physics.momentum + 
                                   (deltaX * physics.attraction) +
                                   (physics.lastDelta.x * 0.1);
                
                physics.velocity.y = physics.velocity.y * physics.momentum + 
                                   (physics.target.y - globe.rotation.x) * physics.attraction +
                                   (physics.lastDelta.y * 0.1);
                
                globe.rotation.y += physics.velocity.x * physics.sensitivity;
                globe.rotation.x += physics.velocity.y * physics.sensitivity;
                
                physics.lastDelta.x *= 0.95;
                physics.lastDelta.y *= 0.95;
            } else {
                // Płynne przejście do automatycznej rotacji
                if (physics.autoRotation < 1) {
                    physics.autoRotation += 0.005; // Bardzo powolne włączanie auto-rotacji
                }
                
                // Aplikuj bazową rotację z płynnym przejściem
                globe.rotation.y += physics.baseSpeed * physics.autoRotation;
                globe.rotation.x *= 0.95;
            }
            
            renderer.render(scene, camera);
        }
        animate();
        
        // Responsywność
        window.addEventListener('resize', function() {
            const width = container.clientWidth;
            const height = container.clientHeight;
            
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        });
    }
    
    // Obsługa formularza powiadomień
    const notificationForm = document.querySelector('.notification-form');
    if (notificationForm) {
        notificationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const timezone = this.querySelector('select').value;
            
            // TODO: Wysłanie danych do API
            console.log('Zapisano do powiadomień:', { email, timezone });
            
            // Potwierdzenie dla użytkownika
            const button = this.querySelector('button');
            const originalText = button.textContent;
            button.textContent = 'Zapisano!';
            button.style.backgroundColor = '#4CAF50';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.backgroundColor = '';
            }, 3000);
        });
    }
    
    // Animacje przy przewijaniu
    const stepCards = document.querySelectorAll('.step-card');
    stepCards.forEach((card, index) => {
        card.style.setProperty('--animation-order', index);
    });
    
    // Obserwator przewijania
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
    
    stepCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        observer.observe(card);
    });
});
