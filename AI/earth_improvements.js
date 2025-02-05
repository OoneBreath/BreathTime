// Ulepszona struktura materiału ziemi z mapowaniem głębokości
function createEarthMaterial() {
    const material = new THREE.MeshPhongMaterial({
        map: dayTexture,
        bumpMap: dayTexture,
        bumpScale: 0.05,
        specularMap: dayTexture,
        specular: new THREE.Color('grey'),
        shininess: 5
    });
    return material;
}

// Kod dla bocznych zakładek
const bookTabs = `
<nav class="book-tabs">
    <a href="ebook.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">📚</span>E-book</span>
    </a>
    <a href="blog.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">📝</span>Blog</span>
    </a>
    <a href="support.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">💝</span>Wesprzyj</span>
    </a>
    <a href="faq.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">❓</span>FAQ</span>
    </a>
    <div class="book-divider"></div>
    <a href="anti-scam.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">🛡️</span>Anti-Scam</span>
    </a>
    <a href="terms.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">📋</span>Terms</span>
    </a>
    <a href="privacy.html" class="book-tab" target="content-frame">
        <span class="tab-content"><span class="emoji">🔒</span>Privacy</span>
    </a>
</nav>
`;

// Style CSS dla zakładek
const bookTabsCSS = `
.book-tabs {
    position: fixed;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 10px;
    background: rgba(0, 0, 0, 0.7);
    border-radius: 0 10px 10px 0;
    z-index: 1000;
}

.book-tab {
    display: flex;
    align-items: center;
    padding: 10px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 5px;
}

.book-tab:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
}

.tab-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.emoji {
    font-size: 1.2em;
}

.book-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 5px 0;
}
`;
