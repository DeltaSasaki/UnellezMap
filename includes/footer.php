<footer>
    <p>&copy; 2023 Mi Proyecto</p>
</footer>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // Inicializa el mapa
    var map = L.map('map').setView([51.505, -0.09], 13); // Coordenadas iniciales y zoom

    // Añade la capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Añade un marcador
    L.marker([51.5, -0.09]).addTo(map)
        .bindPopup('¡Hola! Este es un marcador.')
        .openPopup();
</script>
</body>
</html>