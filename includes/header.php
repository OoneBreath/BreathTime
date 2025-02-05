<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="main-header">
    <div class="header-content">
        <div class="logo-container">
            <a href="index.php" class="logo-link">
                <span class="logo-text">Breath<span class="time-text">Time</span></span>
            </a>
        </div>
        
        <nav class="main-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my-petitions.php">Podpisane petycje</a>
                <a href="profile.php">Profil</a>
                <a href="logout.php">Wyloguj</a>
            <?php else: ?>
                <a href="login.php">Zaloguj</a>
                <a href="register.php">Zarejestruj</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<style>
.main-header {
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255, 200, 0, 0.1);
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo-container {
    display: flex;
    align-items: center;
}

.logo-link {
    text-decoration: none;
    color: white;
}

.logo-text {
    font-size: 2em;
    font-weight: bold;
    color: #fff;
}

.time-text {
    color: #ffd700;
}

.main-nav {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.main-nav a {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.main-nav a:hover {
    background: rgba(255, 200, 0, 0.1);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .main-nav {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/role-form.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="assets/js/counter.js"></script>
<script src="assets/js/role-form.js"></script>
