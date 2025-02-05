// Globalne zmienne
let scene, camera, renderer, earth, stars, pulsars, atmosphere, maintenanceSprite;
let dayTexture, nightTexture;
let currentHour = new Date().getHours();
let targetRotationX = 0;
let targetRotationY = 0;
let mouseX = 0;
let mouseY = 0;
let windowHalfX = window.innerWidth / 2;
let windowHalfY = window.innerHeight / 2;
let lastMouseX = 0;
let lastMouseY = 0;
let mouseTimeout = null;
let isMouseMoving = false;
const autoRotationSpeed = 0.0002; // Prędkość automatycznej rotacji
const mouseSensitivity = 0.001; // Czułość na ruch myszki
let heartbeatTime = 0;

// Globalne zmienne dla geolokalizacji
let userLatitude = 0;
let userLongitude = 0;
let userTimezone = 'UTC';

// Inicjalizacja Three.js
function init() {
    // Scena
    scene = new THREE.Scene();

    // Kamera
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 11; // Zwiększona odległość kamery

    // Renderer
    renderer = new THREE.WebGLRenderer({ alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x000000, 0);
    document.getElementById('earth-container').appendChild(renderer.domElement);

    // Światło
    const light = new THREE.DirectionalLight(0xffffff, 1.5);
    light.position.set(5, 3, 5);
    scene.add(light);

    // Ambient Light dla lepszego oświetlenia nocą
    const ambientLight = new THREE.AmbientLight(0x404040);
    scene.add(ambientLight);

    // Tworzenie gwiazd
    const starsGeometry = new THREE.BufferGeometry();
    const starsMaterial = new THREE.PointsMaterial({
        color: 0xFFFFFF,
        size: 0.05,
        transparent: true,
        opacity: 0.8
    });

    const starsVertices = [];
    for(let i = 0; i < 10000; i++) {
        const x = (Math.random() - 0.5) * 2000;
        const y = (Math.random() - 0.5) * 2000;
        const z = -Math.random() * 2000;
        starsVertices.push(x, y, z);
    }

    starsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starsVertices, 3));
    stars = new THREE.Points(starsGeometry, starsMaterial);
    scene.add(stars);

    // Tworzenie pulsarów
    const pulsarsGeometry = new THREE.BufferGeometry();
    const pulsarsMaterial = new THREE.PointsMaterial({
        color: 0x00a8ff,
        size: 0.2,
        transparent: true,
        opacity: 0.8
    });

    const pulsarsVertices = [];
    for(let i = 0; i < 50; i++) {
        const x = (Math.random() - 0.5) * 1000;
        const y = (Math.random() - 0.5) * 1000;
        const z = -Math.random() * 1000;
        pulsarsVertices.push(x, y, z);
    }

    pulsarsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(pulsarsVertices, 3));
    pulsars = new THREE.Points(pulsarsGeometry, pulsarsMaterial);
    scene.add(pulsars);

    // Ładowanie tekstur Ziemi
    const textureLoader = new THREE.TextureLoader();
    dayTexture = textureLoader.load('images/earth-texture.jpg');
    nightTexture = textureLoader.load('images/earth-night-texture.jpg');

    // Tworzenie kuli ziemskiej
    const geometry = new THREE.SphereGeometry(5, 64, 64); // Większa kula
    const material = new THREE.MeshPhongMaterial({
        map: isNightTime() ? nightTexture : dayTexture,
        shininess: 5,
        color: 0xffffff // Biały kolor
    });
    earth = new THREE.Mesh(geometry, material);

    // Tworzenie atmosfery
    const atmosphereGeometry = new THREE.SphereGeometry(5.25, 64, 64); // Dostosowana atmosfera
    const atmosphereMaterial = new THREE.ShaderMaterial({
        transparent: true,
        side: THREE.BackSide,
        vertexShader: `
            varying vec3 vNormal;
            void main() {
                vNormal = normalize(normalMatrix * normal);
                gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
            }
        `,
        fragmentShader: `
            varying vec3 vNormal;
            void main() {
                float intensity = pow(0.7 - dot(vNormal, vec3(0.0, 0.0, 1.0)), 2.0);
                gl_FragColor = vec4(1.0, 1.0, 1.0, intensity * 0.3); // Biała atmosfera
            }
        `
    });
    atmosphere = new THREE.Mesh(atmosphereGeometry, atmosphereMaterial);
    earth.add(atmosphere);

    scene.add(earth);

    // Tworzenie tekstu maintenance
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    canvas.width = 512;
    canvas.height = 256; // Zwiększona wysokość dla dwóch linii tekstu
    
    // Bez tła
    context.clearRect(0, 0, canvas.width, canvas.height);
    
    context.font = 'bold 32px Arial';
    context.textAlign = 'center';
    context.fillStyle = '#ff3333';
    context.fillText('strona w rozbudowie', canvas.width/2, 70);
    context.font = 'bold 24px Arial';
    context.fillText('wkrótce dołączysz do inicjatywy', canvas.width/2, 120);
    
    const maintenanceTexture = new THREE.CanvasTexture(canvas);
    const maintenanceMaterial = new THREE.SpriteMaterial({
        map: maintenanceTexture,
        transparent: true,
        opacity: 0.9
    });
    
    maintenanceSprite = new THREE.Sprite(maintenanceMaterial);
    maintenanceSprite.scale.set(5, 2, 1); // Szerszy tekst
    maintenanceSprite.position.set(0, -2, 5.1); // Przesunięty bardziej w dół
    scene.add(maintenanceSprite);

    // Obsługa zmiany rozmiaru okna
    window.addEventListener('resize', onWindowResize, false);

    // Obsługa ruchu myszki
    document.addEventListener('mousemove', onDocumentMouseMove, false);

    // Sprawdzanie pory dnia co minutę
    setInterval(updateEarthTexture, 60000);
}

// Funkcja efektu bicia serca
function heartbeatEffect(time) {
    const beat = time % 1000; // 1 sekunda na pełne bicie
    if (beat < 100) { // Pierwsze uderzenie
        return Math.sin(beat / 100 * Math.PI);
    } else if (beat < 200) { // Drugie uderzenie
        return Math.sin((beat - 100) / 100 * Math.PI) * 0.8;
    }
    return 0; // Przerwa między biciami
}

// Obsługa ruchu myszki
function onDocumentMouseMove(event) {
    isMouseMoving = true;
    mouseX = (event.clientX - windowHalfX);
    mouseY = (event.clientY - windowHalfY);

    // Resetuj timer
    clearTimeout(mouseTimeout);
    mouseTimeout = setTimeout(() => {
        isMouseMoving = false;
        lastMouseX = mouseX;
        lastMouseY = mouseY;
    }, 100); // Czekaj 100ms bez ruchu myszki
}

// Sprawdzanie czy jest noc w lokalizacji użytkownika
function isNightTime() {
    const date = new Date();
    const userTime = new Date(date.toLocaleString('en-US', { timeZone: userTimezone }));
    const hours = userTime.getHours();
    return hours >= 20 || hours < 6;
}

// Aktualizacja tekstury Ziemi
function updateEarthTexture() {
    if (earth && earth.material) {
        earth.material.map = isNightTime() ? nightTexture : dayTexture;
        earth.material.needsUpdate = true;
    }
}

// Animacja
function animate() {
    requestAnimationFrame(animate);

    heartbeatTime += 16; // Około 60fps

    if (isMouseMoving) {
        // Gdy myszka się porusza, śledź jej ruch
        targetRotationY = mouseX * mouseSensitivity;
        targetRotationX = mouseY * mouseSensitivity;
        
        earth.rotation.x += (targetRotationX - earth.rotation.x) * 0.05;
        earth.rotation.y += (targetRotationY - (earth.rotation.y % (Math.PI * 2))) * 0.05;
        
        // Obracaj tekst w przeciwnym kierunku, aby zawsze był widoczny
        maintenanceSprite.rotation.y = -earth.rotation.y;
        maintenanceSprite.rotation.x = -earth.rotation.x;
    } else {
        // Gdy myszka się nie porusza, kontynuuj rotację z ostatniej pozycji
        earth.rotation.y += autoRotationSpeed;
        maintenanceSprite.rotation.y = -earth.rotation.y;
    }
    
    // Efekt bicia serca dla tekstu
    const heartbeat = heartbeatEffect(heartbeatTime);
    maintenanceSprite.material.opacity = 0.7 + heartbeat * 0.3;
    maintenanceSprite.scale.set(5 + heartbeat * 0.2, 1 + heartbeat * 0.2, 1);

    // Pulsowanie tekstu
    // maintenanceSprite.material.opacity = 0.7 + Math.sin(Date.now() * 0.002) * 0.3;

    // Animacja gwiazd (znacznie wolniejsza)
    stars.rotation.y += 0.00002;
    stars.rotation.z += 0.00002;

    // Animacja pulsarów
    if (pulsars.material.size > 0.3) {
        pulsars.material.size = 0.2;
    } else {
        pulsars.material.size += 0.001;
    }
    if (pulsars.material.opacity > 0.8) {
        pulsars.material.opacity = 0.3;
    } else {
        pulsars.material.opacity += 0.005;
    }
    
    renderer.render(scene, camera);
}

// Dostosowanie rozmiaru przy zmianie okna
function onWindowResize() {
    windowHalfX = window.innerWidth / 2;
    windowHalfY = window.innerHeight / 2;
    
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    
    // Dostosuj pozycję kamery w zależności od rozmiaru ekranu
    camera.position.z = Math.max(11, window.innerWidth / window.innerHeight * 7);
    
    renderer.setSize(window.innerWidth, window.innerHeight);
}

// Inicjalizacja geolokalizacji
function initGeolocation() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            position => {
                userLatitude = position.coords.latitude;
                userLongitude = position.coords.longitude;
                // Pobierz strefę czasową użytkownika
                getUserTimezone(userLatitude, userLongitude);
                // Pobierz pogodę dla lokalizacji użytkownika
                getWeatherData();
            },
            error => {
                console.error('Błąd geolokalizacji:', error);
                // Fallback do IP geolokalizacji
                getLocationByIP();
            }
        );
    } else {
        // Fallback do IP geolokalizacji
        getLocationByIP();
    }
}

// Pobieranie lokalizacji przez IP
async function getLocationByIP() {
    try {
        const response = await fetch('https://ipapi.co/json/');
        const data = await response.json();
        userLatitude = data.latitude;
        userLongitude = data.longitude;
        userTimezone = data.timezone;
        getWeatherData();
    } catch (error) {
        console.error('Błąd pobierania lokalizacji IP:', error);
    }
}

// Pobieranie strefy czasowej
async function getUserTimezone(lat, lon) {
    try {
        const response = await fetch(`https://api.timezonedb.com/v2.1/get-time-zone?key=YOUR_TIMEZONE_API_KEY&format=json&by=position&lat=${lat}&lng=${lon}`);
        const data = await response.json();
        userTimezone = data.zoneName;
        updateEarthTexture(); // Aktualizuj teksturę po otrzymaniu strefy czasowej
    } catch (error) {
        console.error('Błąd pobierania strefy czasowej:', error);
        // Użyj lokalnej strefy czasowej jako fallback
        userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    }
}

// Pobieranie danych pogodowych
async function getWeatherData() {
    try {
        const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${userLatitude}&lon=${userLongitude}&units=metric&appid=f05de1429ed826788fea773ca705ea1e&lang=pl`);
        const data = await response.json();
        
        // Aktualizacja widgetu
        document.querySelector('.weather-info .temp').textContent = `${Math.round(data.main.temp)}°C`;
        document.querySelector('.weather-info .desc').textContent = data.weather[0].description;
        document.querySelector('.weather-info .humidity').textContent = `Wilgotność: ${data.main.humidity}%`;
        document.querySelector('.weather-info .feels-like').textContent = `Odczuwalna: ${Math.round(data.main.feels_like)}°C`;
        
        // Dodaj nazwę miejscowości
        const locationName = data.name;
        document.querySelector('.weather-info .location').textContent = locationName;
    } catch (error) {
        console.error('Błąd podczas pobierania danych pogodowych:', error);
        document.querySelector('.weather-info .desc').textContent = 'Błąd pobierania danych';
    }
}

// Dynamiczne teksty
const texts = [
    "Czy jeden człowiek może zmienić świat?",
    "Historia pokazała, że jest to możliwe.",
    "Ale, aby jeden człowiek mógł zmienić świat, musi mieć poparcie innych ludzi...",
    "Jeden dzień może zmienić świat",
    "Dołącz do inicjatywy BreathTime",
    "Razem możemy więcej",
    "Oddaj jeden dzień Ziemi",
    "12 dni w roku dla lepszego jutra",
    "Zróbmy to razem"
];

let currentTextIndex = 0;

function updateText() {
    const mainText = document.querySelector('.main-text');
    if (mainText) {
        const text = texts[currentTextIndex];
        const h1 = document.createElement('h1');
        h1.textContent = text;
        
        // Usuń poprzedni tekst
        while (mainText.firstChild) {
            mainText.firstChild.remove();
        }
        
        mainText.appendChild(h1);
        currentTextIndex = (currentTextIndex + 1) % texts.length;
    }
}

// Obsługa e-booka
const chapters = {
    1: {
        title: "Rozdział 1: Myśl, która zmienia świat",
        content: `
            <h1>Rozdział 1: Myśl, która zmienia świat</h1>
            <p>Czy jeden człowiek może zmienić świat? Wydaje się to trudne, ale historia pokazuje, że wielkie zmiany zaczynały się od prostych idei. „BreathTime" to właśnie jedna z takich myśli – pomysł, który ma szansę wpłynąć na przyszłość naszej planety i społeczeństwa.</p>
            
            <div class="chapter-divider"></div>
            
            <h2>Dlaczego teraz?</h2>
            <p>Zmiany klimatyczne, rosnące emisje gazów cieplarnianych i coraz bardziej zanieczyszczone środowisko to wyzwania, przed którymi stoimy wszyscy. Jednak często wydaje nam się, że pojedyncze działania są niewystarczające. Tymczasem to właśnie małe kroki, podejmowane wspólnie przez miliony ludzi, mogą przynieść największy efekt.</p>
            
            <div class="chapter-divider"></div>
            
            <h2>Dlaczego ja? Dlaczego Ty?</h2>
            <p>Każdy z nas ma wpływ na otaczający świat – przez to, jak żyjemy, co wybieramy i jakie decyzje podejmujemy. „BreathTime" nie wymaga wielkich poświęceń ani skomplikowanych działań. To idea, która daje każdemu możliwość przyczynienia się do zmian, zaczynając od jednego dnia w miesiącu.</p>
            
            <div class="chapter-divider"></div>
            
            <h2>Idea „BreathTime"</h2>
            <p>Wyobraźmy sobie świat, w którym raz w miesiącu zatrzymujemy się na chwilę, by dać naszej planecie oddech. Ograniczamy ruch samochodowy, zmniejszamy produkcję przemysłową i zużycie energii. To niewielki wysiłek, ale jego efekty mogą być ogromne – od redukcji emisji gazów cieplarnianych po poprawę jakości powietrza, którym wszyscy oddychamy.</p>
            
            <p>„BreathTime" to coś więcej niż tylko zmniejszenie emisji. To symbol, że każdy z nas może być częścią rozwiązania, a nie problemu. To szansa na stworzenie nowej kultury – kultury odpowiedzialności za świat, który dzielimy.</p>`
    }
    // Pozostałe rozdziały zostaną dodane później
};

document.addEventListener('DOMContentLoaded', () => {
    const chapterItems = document.querySelectorAll('.chapter-item');
    const chapterContent = document.querySelector('.chapter-content');

    // Obsługa kliknięć w rozdziały
    chapterItems.forEach(item => {
        item.addEventListener('click', () => {
            // Usuń klasę active ze wszystkich rozdziałów
            chapterItems.forEach(ch => ch.classList.remove('active'));
            
            // Dodaj klasę active do klikniętego rozdziału
            item.classList.add('active');
            
            // Pobierz numer rozdziału
            const chapterNum = item.dataset.chapter;
            
            // Jeśli mamy treść dla tego rozdziału, wyświetl ją
            if (chapters[chapterNum]) {
                chapterContent.innerHTML = chapters[chapterNum].content;
                
                // Płynne przewinięcie do góry treści
                chapterContent.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Dodaj efekt przewijania z animacją dla linków wewnętrznych
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});

// Inicjalizacja po załadowaniu strony
document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja Three.js
    if (document.getElementById('earth-container')) {
        init();
        animate();
    }

    // Inicjalizacja geolokalizacji i widgetu pogodowego
    if (document.querySelector('.weather-widget')) {
        initGeolocation();
        // Aktualizuj pogodę co 5 minut
        setInterval(() => {
            navigator.geolocation.getCurrentPosition(
                position => {
                    userLatitude = position.coords.latitude;
                    userLongitude = position.coords.longitude;
                    getWeatherData();
                },
                error => console.error('Błąd aktualizacji geolokalizacji:', error)
            );
        }, 300000);
    }

    // Aktualizacja tekstu na stronie głównej
    updateText();
    setInterval(updateText, 5000);

    // Obsługa menu i nawigacji
    const menuItems = document.querySelectorAll('.menu-item');
    const contentContainers = document.querySelectorAll('.content-container');

    // Funkcja pokazująca wybraną sekcję
    const showSection = (sectionId) => {
        contentContainers.forEach(container => {
            if (container.id === sectionId) {
                container.classList.add('active');
            } else {
                container.classList.remove('active');
            }
        });
    };

    // Obsługa kliknięć w menu
    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = item.getAttribute('href').substring(1);
            showSection(targetId);

            // Dodaj klasę active do wybranego elementu menu
            menuItems.forEach(menuItem => menuItem.classList.remove('active'));
            item.classList.add('active');
        });
    });

    // Obsługa hash w URL
    const handleHash = () => {
        const hash = window.location.hash.substring(1) || 'ebook';
        showSection(hash);
        menuItems.forEach(item => {
            const itemHash = item.getAttribute('href').substring(1);
            if (itemHash === hash) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    };

    // Nasłuchuj zmian hash
    window.addEventListener('hashchange', handleHash);
    
    // Pokaż domyślną sekcję
    handleHash();

    // Obsługa komunikacji między stronami
    window.addEventListener('message', function(event) {
        if (event.data.type === 'pageTitle') {
            document.getElementById('active-tab').textContent = event.data.title;
        }
    });
});
