<?php
// Incluye el archivo de coordenadas
include 'coords.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Interactivo</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="./assets/css/map.css">
</head>
<body>

<input type="text" id="search" placeholder="Buscar lugar...">
<div id="suggestions"></div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    // Inicializa el mapa y lo centra en la primera ubicación
    const mapa = L.map('map').setView([<?= $marcadores[0]['latitud'] ?>, <?= $marcadores[0]['longitud'] ?>], 17);

    // Define las capas de mapas
    const capas = {
        "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }),
        "OpenTopoMap": L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }),
        "CyclOSM": L.tileLayer('https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }),
        "Satélite (ESRI)": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, USDA, USGS, AEX, GeoEye, IGN, and others'
        })
    };

    capas["OpenStreetMap"].addTo(mapa);
    L.control.layers(capas).addTo(mapa);

    // Almacena los marcadores en un array
    const marcadores = [
        <?php foreach ($marcadores as $marcador): ?>
            {
                nombre: "<?= $marcador['nombre'] ?>",
                latitud: <?= $marcador['latitud'] ?>,
                longitud: <?= $marcador['longitud'] ?>,
                imagen: "<?= $marcador['imagen'] ?>",
                descripcion: "<?= $marcador['descripcion'] ?>"
            },
        <?php endforeach; ?>
    ];

    // Añade los marcadores al mapa
    const layerMarcadores = L.layerGroup();
    marcadores.forEach(marcador => {
        const marker = L.marker([marcador.latitud, marcador.longitud])
            .bindPopup(`
                <h3>${marcador.nombre}</h3>
                ${marcador.imagen ? `<img src="${marcador.imagen}" alt="${marcador.nombre}" style="width: 100%;">` : ''}
                <p>${marcador.descripcion}</p>
            `);
        layerMarcadores.addLayer(marker);
    });
    layerMarcadores.addTo(mapa);

    // Función para buscar lugares y mostrar sugerencias
    function buscarLugar() {
        const texto = document.getElementById('search').value.toLowerCase();
        const resultados = marcadores.filter(marcador => 
            marcador.nombre.toLowerCase().includes(texto)
        );

        const suggestions = document.getElementById('suggestions');
        suggestions.innerHTML = ''; // Limpia las sugerencias anteriores

        if (texto && resultados.length > 0) {
            resultados.forEach(marcador => {
                const div = document.createElement('div');
                div.textContent = marcador.nombre;
                div.addEventListener('click', () => {
                    // Centra el mapa en el marcador seleccionado
                    mapa.setView([marcador.latitud, marcador.longitud], 17);
                    // Abre el popup del marcador
                    const marker = layerMarcadores.getLayers().find(layer => 
                        layer.getLatLng().lat === marcador.latitud && 
                        layer.getLatLng().lng === marcador.longitud
                    );
                    if (marker) {
                        marker.openPopup();
                    }
                    // Limpia el campo de búsqueda y las sugerencias
                    document.getElementById('search').value = '';
                    suggestions.style.display = 'none';
                });
                suggestions.appendChild(div);
            });
            suggestions.style.display = 'block';
        } else {
            suggestions.style.display = 'none';
        }
    }

    // Escucha el evento de entrada en el campo de búsqueda
    document.getElementById('search').addEventListener('input', buscarLugar);

    // Oculta las sugerencias al hacer clic fuera del campo de búsqueda
    document.addEventListener('click', (e) => {
        if (e.target.id !== 'search') {
            document.getElementById('suggestions').style.display = 'none';
        }
    });

    let puntoA = null;
    let puntoB = null;
    let controlRuta = null;
    let rutaORS = null;

    // Añade un selector para el modo de transporte
    const selectorTransporte = L.control({position: 'topright'});
    selectorTransporte.onAdd = function () {
        const div = L.DomUtil.create('div', 'info legend');
        div.innerHTML = `
            <select id="modoTransporte">
                <option value="driving">Vehículo</option>
                <option value="walking">Peatón</option>
            </select>
        `;
        return div;
    };
    selectorTransporte.addTo(mapa);

    // Variables para almacenar los marcadores de los puntos A y B
    let markerPuntoA = null;
    let markerPuntoB = null;

    // Función para limpiar los puntos y la ruta
    function limpiarMapa() {
        if (controlRuta) {
            mapa.removeControl(controlRuta);
            controlRuta = null;
        }
        if (rutaORS) {
            mapa.removeLayer(rutaORS);
            rutaORS = null;
        }
        if (markerPuntoA) {
            mapa.removeLayer(markerPuntoA);
            markerPuntoA = null;
        }
        if (markerPuntoB) {
            mapa.removeLayer(markerPuntoB);
            markerPuntoB = null;
        }
    }

    // Función para limpiar la ruta
    function limpiarRuta() {
        if (controlRuta) {
            mapa.removeControl(controlRuta);
            controlRuta = null;
        }
        if (rutaORS) {
            mapa.removeLayer(rutaORS);
            rutaORS = null;
        }
    }

    // Función para mostrar el menú contextual
    function mostrarMenuContextual(e) {
        const menu = L.popup()
            .setLatLng(e.latlng)
            .setContent(`
                <div class="menu-contextual">
                    <button onclick="seleccionarPuntoA(${e.latlng.lat}, ${e.latlng.lng})">Punto A</button>
                    <button onclick="seleccionarPuntoB(${e.latlng.lat}, ${e.latlng.lng})">Punto B</button>
                </div>
            `)
            .openOn(mapa);
    }

    // Función para seleccionar el punto A
    function seleccionarPuntoA(lat, lng) {
        limpiarRuta();
        puntoA = L.latLng(lat, lng);
        if (markerPuntoA) {
            mapa.removeLayer(markerPuntoA);
        }
        markerPuntoA = L.marker(puntoA).addTo(mapa).bindPopup("Punto A (Origen)").openPopup();
        if (puntoB) {
            calcularRuta();
        }
    }

    // Función para seleccionar el punto B
    function seleccionarPuntoB(lat, lng) {
        limpiarRuta();
        puntoB = L.latLng(lat, lng);
        if (markerPuntoB) {
            mapa.removeLayer(markerPuntoB);
        }
        markerPuntoB = L.marker(puntoB).addTo(mapa).bindPopup("Punto B (Destino)").openPopup();
        if (puntoA) {
            calcularRuta();
        }
    }

    // Función para calcular la ruta
    function calcularRuta() {
        const modoTransporte = document.getElementById('modoTransporte').value;

        if (modoTransporte === 'walking') {
            obtenerRutaOpenRouteService(puntoA, puntoB);
        } else {
            controlRuta = L.Routing.control({
                waypoints: [puntoA, puntoB],
                routeWhileDragging: true,
                show: true,
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                router: L.Routing.osrmv1({
                    serviceUrl: "https://router.project-osrm.org/route/v1",
                    profile: "driving"
                })
            }).addTo(mapa);
        }
    }

    // Función para obtener la ruta desde OpenRouteService
    function obtenerRutaOpenRouteService(puntoA, puntoB) {
        const apiKey = '5b3ce3597851110001cf62489581e06f9b72467e9439db10cfffedf9';
        const url = `https://api.openrouteservice.org/v2/directions/foot-walking?api_key=${apiKey}&start=${puntoA.lng},${puntoA.lat}&end=${puntoB.lng},${puntoB.lat}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const coordenadas = data.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                rutaORS = L.polyline(coordenadas, {color: 'blue'}).addTo(mapa);
                mapa.fitBounds(rutaORS.getBounds());
            })
            .catch(error => console.error("Error obteniendo la ruta:", error));
    }

    // Escucha el evento de clic en el mapa
    mapa.on('click', mostrarMenuContextual);
</script>

</body>
</html>