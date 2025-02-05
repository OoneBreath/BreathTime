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

        // Zmienne do śledzenia myszki
        let mouseX = 0;
        let mouseY = 0;
        let targetRotationX = 0;
        let targetRotationY = 0;
        let currentRotationX = 0;
        let currentRotationY = 0;

        // Nasłuchiwanie ruchu myszki
        container.addEventListener('mousemove', function(event) {
            // Obliczanie pozycji myszki względem środka kontenera
            const rect = container.getBoundingClientRect();
            mouseX = ((event.clientX - rect.left) / container.clientWidth) * 2 - 1;
            mouseY = -((event.clientY - rect.top) / container.clientHeight) * 2 + 1;
            
            // Obliczanie docelowej rotacji
            targetRotationY = mouseX * Math.PI * 0.5;
            targetRotationX = mouseY * Math.PI * 0.25;
        });

        // Dodanie efektu inercji do obrotu
        function updateRotation() {
            // Płynne przejście do docelowej rotacji
            currentRotationX += (targetRotationX - currentRotationX) * 0.05;
            currentRotationY += (targetRotationY - currentRotationY) * 0.05;

            // Zastosowanie rotacji do kuli
            globe.rotation.x = currentRotationX;
            globe.rotation.y += 0.002; // Bazowa rotacja
            globe.rotation.y += (targetRotationY - globe.rotation.y) * 0.05;
        }
        
        // Animacja
        function animate() {
            requestAnimationFrame(animate);
            updateRotation();
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

        // Reset rotacji gdy myszka opuszcza kontener
        container.addEventListener('mouseleave', function() {
            targetRotationX = 0;
            targetRotationY = 0;
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
