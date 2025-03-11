<?php
// Incluye el archivo de coordenadas
include 'coords.php';
?>
<footer>
    <p>&copy; 2023 Mi Proyecto</p>
</footer>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // Inicializa el mapa y lo centra en la primera ubicación
    const mapa = L.map('map').setView([<?= $marcadores[0]['latitud'] ?>, <?= $marcadores[0]['longitud'] ?>], 17);

    // Define las capas de mapas
    const capas = {
        "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }),
        "OpenTopoMap": L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a>'
        }),
        "CyclOSM": L.tileLayer('https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }),
        "Satélite (ESRI)": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        })
    };

    // Añade la capa por defecto (OpenStreetMap)
    capas["OpenStreetMap"].addTo(mapa);

    // Añade un control para cambiar entre capas
    L.control.layers(capas).addTo(mapa);

    // Añade los marcadores dinámicamente
    <?php foreach ($marcadores as $marcador): ?>
        L.marker([<?= $marcador['latitud'] ?>, <?= $marcador['longitud'] ?>]).addTo(mapa)
            .bindPopup(`
                <h3><?= $marcador['nombre'] ?></h3>
                <?php if ($marcador['imagen']): ?>
                    <img src="<?= $marcador['imagen'] ?>" alt="<?= $marcador['nombre'] ?>" style="width: 100%;">
                <?php endif; ?>
                <p><?= $marcador['descripcion'] ?></p>
            `);
    <?php endforeach; ?>
</script>
</body>
</html>