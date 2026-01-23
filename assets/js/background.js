document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('dot-grid');
    if (!grid) return;

    // Calculate number of tiles needed
    const tileSize = 40;
    const cols = Math.ceil(window.innerWidth / tileSize);
    const rows = Math.ceil(window.innerHeight / tileSize);
    const totalTiles = cols * rows;

    for (let i = 0; i < totalTiles; i++) {
        const tile = document.createElement('div');
        tile.className = 'dot-tile';
        
        const dot = document.createElement('div');
        dot.className = 'dot';
        
        // Random Twinkle Animation
        if (Math.random() > 0.85) { // 15% of dots will twinkle
            dot.classList.add('dot-twinkle');
            dot.style.animationDelay = Math.random() * 5 + 's';
            dot.style.animationDuration = (2 + Math.random() * 4) + 's';
        }

        tile.appendChild(dot);
        grid.appendChild(tile);
    }
});
