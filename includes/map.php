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

<!-- Menú de capas personalizado -->
<div id="gearControl" class="leaflet-control leaflet-bar" title="Opciones de mapa">
  <img src="assets/img/gear.png" alt="Opciones" style="width: 24px; height: 24px;">
</div>

<div id="mapLayersMenu" class="map-layers-menu">
  <label><input type="radio" name="mapa" value="osm" checked> OpenStreetMap</label>
  <label><input type="radio" name="mapa" value="topo"> OpenTopoMap</label>
  <label><input type="radio" name="mapa" value="satelite"> Satelital</label>
  <label><input type="radio" name="mapa" value="oscuro"> Modo Oscuro</label>

    <hr style="margin: 8px 0;">

  <label for="modoTransporte"><strong>Modo de transporte</strong></label>
  <select id="modoTransporte" style="margin-top: 4px; width: 100%;">
    <option value="walking">Peatón</option>
    <option value="driving">Vehículo</option>
  </select>
</div>

<!-- Panel tipo tarjeta -->
<div id="infoPanel" class="info-panel">
  <button id="closePanel">&times;</button>
  <h3 id="infoTitulo"></h3>
  <img id="infoImagen" src="" alt="Imagen" />
  <p id="infoDescripcion"></p>
  <button id="btnIr" disabled>Quiero ir ahí</button>
</div>


<!-- Botón AR añadido -->
<div id="btnAR" class="leaflet-control leaflet-bar" title="Guía AR">
  <img src="assets/img/virtual-reality.png" alt="AR" style="width: 26px; height: 26px;">
</div>

<!-- Panel deslizable para selección de punto -->
<div id="panelMarcador" class="panel-marcador" style="display: none; position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); background: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); z-index: 1001; text-align: center;">
  <p style="margin-bottom: 10px; font-weight: bold;">¿Qué punto deseas marcar?</p>
  <div style="display: flex; justify-content: center; gap: 10px;">
    <button onclick="seleccionarPuntoA(latTmp, lngTmp)">📍 Punto A</button>
    <button onclick="seleccionarPuntoB(latTmp, lngTmp)">🏁 Punto B</button>
  </div>
</div>

<button id="btnLimpiar" title="Limpiar ruta">&#x274C;</button>

<!-- GIF que aparece en el lugar del clic -->
<img id="gifMarcador" src="assets/img/Marcador_tocar.gif" alt="Indicador" style="display: none; position: absolute; width: 40px; height: 40px; z-index: 1002;">

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    // Inicializa el mapa y lo centra en la primera ubicación
    const mapa = L.map('map', {
        zoomControl: false // Desactiva los controles de zoom
    }).setView([<?= $marcadores[0]['latitud'] ?>, <?= $marcadores[0]['longitud'] ?>], 17);

    // Define las capas de mapas
const capas = {
    "osm": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }),
    "topo": L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: &copy; OpenStreetMap contributors'
    }),
    "satelite": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    }),
    "oscuro": L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CartoDB'
    })
};

let capaActual = capas["osm"];
capaActual.addTo(mapa);

 

document.getElementById("gearControl").addEventListener("click", () => {
    const menu = document.getElementById("mapLayersMenu");
    const panel = document.getElementById("infoPanel");
    const panelMarcador = document.getElementById("panelMarcador");

    const abierto = menu.style.display === "block";

    // Si se va a abrir el menú, cierra el panel si está abierto
    if (!abierto && panel.classList.contains("show")) {
        panel.classList.remove("show");
        document.getElementById("btnIr").disabled = true;
    }

    // Cierra el panel de selección de punto si está abierto
    if (!abierto && panelMarcador.style.display === "block") {
        panelMarcador.style.display = "none";
    }

    // Alterna visibilidad del menú
    menu.style.display = abierto ? "none" : "block";
});


document.querySelectorAll('input[name="mapa"]').forEach(input => {
    input.addEventListener("change", (e) => {
        const valor = e.target.value;
        if (capas[valor]) {
            mapa.removeLayer(capaActual);
            capaActual = capas[valor];
            capaActual.addTo(mapa);
        }
        document.getElementById("mapLayersMenu").style.display = "none";
    });
});


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

    // Ícono para puntos del sistema (coords.php)
const iconCoords = L.icon({
    iconUrl: 'assets/img/icon_coords.png',
    iconSize: [32, 32],
    iconAnchor: [16, 32]
});

// Ícono para punto A (origen)
const iconPuntoA = L.icon({
    iconUrl: 'assets/img/iconPuntoA.png',
    iconSize: [34, 34],
    iconAnchor: [17, 34]
});

// Ícono para punto B (destino)
const iconPuntoB = L.icon({
    iconUrl: 'assets/img/IconPuntoB.png',
    iconSize: [34, 34],
    iconAnchor: [17, 34]
});

// Ícono del usuario (posición actual)
const iconUsuario = L.icon({
    iconUrl: 'assets/img/IconUsuario.png',
    iconSize: [30, 30],
    iconAnchor: [15, 30]
});

// Añade los marcadores al mapa
const layerMarcadores = L.layerGroup();
marcadores.forEach(marcador => {
    const marker = L.marker([marcador.latitud, marcador.longitud], { icon: iconCoords }) // ← Usa iconCoords aquí
        .on('click', () => {
            // Cierra el menú de capas si está abierto
            const menu = document.getElementById("mapLayersMenu");
            if (menu.style.display === "block") {
                menu.style.display = "none";
            }

            // Muestra el panel con info
            document.getElementById("infoTitulo").textContent = marcador.nombre;
            document.getElementById("infoImagen").src = marcador.imagen;
            document.getElementById("infoDescripcion").textContent = marcador.descripcion;
            document.getElementById("btnIr").disabled = false;
            document.getElementById("btnIr").onclick = () => {
                irA(marcador.latitud, marcador.longitud);
            };
            document.getElementById("infoPanel").classList.add("show");
        });
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

    // Mostrar la información del marcador
    document.getElementById("infoTitulo").textContent = marcador.nombre;
    document.getElementById("infoImagen").src = marcador.imagen;
    document.getElementById("infoDescripcion").textContent = marcador.descripcion;
    document.getElementById("btnIr").disabled = false;
    document.getElementById("btnIr").onclick = () => {
        irA(marcador.latitud, marcador.longitud);
    };
    document.getElementById("infoPanel").classList.add("show");

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



    // Variables para almacenar los marcadores de los puntos A y B
    let markerPuntoA = null;
    let markerPuntoB = null;

    // Función para limpiar los puntos y la ruta
function limpiarRuta() {
    if (controlRuta) {
        mapa.removeControl(controlRuta);
        controlRuta = null;
    }
    if (rutaORS) {
        mapa.removeLayer(rutaORS);
        rutaORS = null;
    }
    document.getElementById("btnLimpiar").style.display = "none";
}

function limpiarMapa() {
    limpiarRuta(); // Ya limpia el botón
    if (markerPuntoA) {
        mapa.removeLayer(markerPuntoA);
        markerPuntoA = null;
    }
    if (markerPuntoB) {
        mapa.removeLayer(markerPuntoB);
        markerPuntoB = null;
    }
    puntoA = null;
    puntoB = null;
}

// Variables temporales
let latTmp = 0, lngTmp = 0;
let marcadorTemporalGIF = null;

// Reemplaza popup por panel moderno
function mostrarMenuContextual(e) {
    latTmp = e.latlng.lat;
    lngTmp = e.latlng.lng;

    // Muestra el panel flotante
    const panel = document.getElementById("panelMarcador");
    panel.style.display = "block";

    // Si ya existe un marcador temporal, lo elimina
    if (marcadorTemporalGIF) {
        mapa.removeLayer(marcadorTemporalGIF);
    }

    // Crea un nuevo ícono con el GIF
const iconoGIF = L.icon({
    iconUrl: 'assets/img/Marcador_tocar.gif',
    iconSize: [40, 40],        // Escalado visual (puedes ajustar este tamaño)
    iconAnchor: [20, 20]       // Centro exacto del ícono
});

    // Agrega el marcador GIF en la ubicación clickeada
    marcadorTemporalGIF = L.marker(e.latlng, { icon: iconoGIF }).addTo(mapa);
}


    // Función para seleccionar el punto A
 function seleccionarPuntoA(lat, lng) {
    document.getElementById("panelMarcador").style.display = "none";
    if (marcadorTemporalGIF) {
        mapa.removeLayer(marcadorTemporalGIF);
        marcadorTemporalGIF = null;
    }
    limpiarRuta();
    puntoA = L.latLng(lat, lng);
    if (markerPuntoA) {
        mapa.removeLayer(markerPuntoA);
    }
    markerPuntoA = L.marker(puntoA, { icon: iconPuntoA }).addTo(mapa);
    if (puntoB) {
        calcularRuta();
    }
}

function seleccionarPuntoB(lat, lng) {
    document.getElementById("panelMarcador").style.display = "none";
    if (marcadorTemporalGIF) {
        mapa.removeLayer(marcadorTemporalGIF);
        marcadorTemporalGIF = null;
    }
    limpiarRuta();
    puntoB = L.latLng(lat, lng);
    if (markerPuntoB) {
        mapa.removeLayer(markerPuntoB);
    }
    markerPuntoB = L.marker(puntoB, { icon: iconPuntoB }).addTo(mapa);
    if (puntoA) {
        calcularRuta();
    }
}

    // Función para calcular la ruta usando OpenRouteService
    function calcularRuta() {
        const modoTransporte = document.getElementById('modoTransporte').value;

        // Limpia la ruta actual antes de recalcular
        limpiarRuta();

        if (puntoA && puntoB) {
            let perfil = '';
            if (modoTransporte === 'walking') {
                perfil = 'foot-walking'; // Perfil para peatones
            } else if (modoTransporte === 'driving') {
                perfil = 'driving-car'; // Perfil para vehículos
            }

            if (perfil) {
                obtenerRutaOpenRouteService(puntoA, puntoB, perfil);
            }
        }
    }

    // Función para obtener la ruta desde OpenRouteService
function obtenerRutaOpenRouteService(puntoA, puntoB, perfil) {
    const url = `includes/ors_proxy.php?perfil=${perfil}&start=${puntoA.lng},${puntoA.lat}&end=${puntoB.lng},${puntoB.lat}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error("Error al obtener la ruta desde el servidor.");
            }
            return response.json();
        })
        .then(data => {
            if (!data.features) {
                throw new Error("Respuesta inválida de OpenRouteService");
            }

            const coordenadas = data.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
            rutaORS = L.polyline(coordenadas, { color: 'blue' }).addTo(mapa);
            mapa.fitBounds(rutaORS.getBounds());

            document.getElementById("btnLimpiar").style.display = "block";
            localStorage.setItem("ruta_AR", JSON.stringify(coordenadas));

            const panel = document.getElementById("infoPanel");
            if (panel.classList.contains("show")) {
                panel.classList.remove("show");
                document.getElementById("btnIr").disabled = true;
            }

            const btnAR = document.getElementById("btnAR");
            btnAR.style.display = "flex";
            setTimeout(() => {
                btnAR.style.opacity = "1";
            }, 10);
        })
        .catch(error => {
            console.warn("⚠️ No se pudo obtener la ruta:", error.message);
            alert("No se pudo calcular la ruta. Verifica tu conexión o intenta más tarde.");
        });
}



    // Variable para almacenar el marcador de la posición actual
    let marcadorUsuario = null;

    // Función para habilitar la geolocalización en tiempo real
    function habilitarGeolocalizacion() {
        if (!navigator.geolocation) {
            alert("La geolocalización no está soportada por tu navegador.");
            return;
        }

        // Observa la posición del usuario en tiempo real
        navigator.geolocation.watchPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Si ya existe un marcador, actualiza su posición
                if (marcadorUsuario) {
                    marcadorUsuario.setLatLng([lat, lng]);
                } else {
                    // Si no existe, crea un nuevo marcador
                    marcadorUsuario = L.marker([lat, lng], { icon: iconUsuario }) // ← Usa iconUsuario
                        .bindPopup("Estás aquí").addTo(mapa);
                }

                // Elimina esta línea para evitar que la cámara se enfoque automáticamente
                // mapa.setView([lat, lng], 17);
            },
            (error) => {
                console.error("Error obteniendo la ubicación en tiempo real:", error);
               // alert("No se pudo obtener tu ubicación en tiempo real. Por favor, verifica los permisos de geolocalización.");
            },
            {
                enableHighAccuracy: true, // Usa la mayor precisión posible
                maximumAge: 0, // No usa caché
                timeout: 10000 // Tiempo máximo de espera
            }
        );
    }

    // Llama a la función para habilitar la geolocalización en tiempo real
    habilitarGeolocalizacion();

    // Escucha el evento de clic en el mapa
 mapa.on('click', function (e) {
    const target = e.originalEvent.target;

    // Ignorar clics sobre el select, el botón de AR, y otros controles interactivos
    const isControl = target.closest('#modoTransporte') || target.closest('#btnAR') || target.closest('.leaflet-control');
    
    if (!isControl) {
        mostrarMenuContextual(e);
    }
});


// Cierra el panel de selección de punto si se mueve el mapa
mapa.on('movestart', function () {
    const panelMarcador = document.getElementById("panelMarcador");
    if (panelMarcador.style.display === "block") {
        panelMarcador.style.display = "none";
    }
});

// Cierra el panel de selección de punto al usar el buscador
document.getElementById('search').addEventListener('input', function () {
    const panelMarcador = document.getElementById("panelMarcador");
    if (panelMarcador.style.display === "block") {
        panelMarcador.style.display = "none";
    }
});

    // Función para redirigir a AR
document.getElementById("btnAR").addEventListener("click", () => {
    if (!puntoA || !puntoB) {
        console.warn("Ruta no definida para guía AR");
        return;
    }
    window.open(`includes/ar.php`, "_blank");
});

// Cierre del panel
document.getElementById("closePanel").addEventListener("click", () => {
    document.getElementById("infoPanel").classList.remove("show");
    document.getElementById("btnIr").disabled = true;
});

document.addEventListener('click', function(e) {
    const gear = document.getElementById("gearControl");
    const menu = document.getElementById("mapLayersMenu");
    if (!gear.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = "none";
    }
});

document.getElementById("btnLimpiar").addEventListener("click", limpiarMapa);


</script>
</body>
</html>