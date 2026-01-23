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
        

        tile.appendChild(dot);
        grid.appendChild(tile);
    }
});
