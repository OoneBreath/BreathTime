<?php
require_once 'includes/tech_layout.php';
?>

<div class="container mt-4 mb-4">
    <div class="content-box">
        <div class="terms-content">
            <?php include 'terms.html'; ?>
        </div>
        <div class="text-center mt-4">
            <button onclick="window.close();" class="btn btn-primary btn-lg">Zamknij</button>
        </div>
    </div>
</div>

<style>
body {
    background-color: #1f2937;
}

.content-box {
    background-color: #1f2937;
    border-radius: 0.5rem;
    padding: 2rem;
    color: #e5e7eb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.terms-content {
    color: #e5e7eb;
    line-height: 1.6;
}

.terms-content h3 {
    color: #60a5fa;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
}

.terms-content p {
    margin-bottom: 1rem;
}

.terms-content ul {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.terms-content li {
    margin-bottom: 0.5rem;
}

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
}
</style>
