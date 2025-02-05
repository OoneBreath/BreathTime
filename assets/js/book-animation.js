document.addEventListener('DOMContentLoaded', function() {
    const cover = document.querySelector('.ebook-cover');
    
    if (!cover) return;

    let isMoving = false;
    let currentX = 0;
    let currentY = 0;
    let targetX = 0;
    let targetY = 0;

    function lerp(start, end, factor) {
        return start + (end - start) * factor;
    }

    function updatePosition() {
        if (!isMoving) {
            targetX = 0;
            targetY = 0;
        }

        currentX = lerp(currentX, targetX, 0.1);
        currentY = lerp(currentY, targetY, 0.1);

        const rotateX = currentY * 15;
        const rotateY = currentX * 15;

        cover.style.transform = `
            perspective(1000px)
            rotateX(${-rotateX}deg)
            rotateY(${rotateY}deg)
            scale3d(1.05, 1.05, 1.05)
        `;

        requestAnimationFrame(updatePosition);
    }

    function handleMove(e) {
        isMoving = true;
        const rect = cover.getBoundingClientRect();
        
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;
        
        targetX = x;
        targetY = y;
    }

    function handleLeave() {
        isMoving = false;
    }

    cover.addEventListener('mousemove', handleMove);
    cover.addEventListener('mouseleave', handleLeave);
    cover.addEventListener('mouseenter', handleMove);

    updatePosition();
});
