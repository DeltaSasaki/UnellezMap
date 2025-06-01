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
    menu.style.display = menu.style.display === "block" ? "none" : "block";
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

    // Añade los marcadores al mapa
const layerMarcadores = L.layerGroup();
marcadores.forEach(marcador => {
    const marker = L.marker([marcador.latitud, marcador.longitud])
        .on('click', () => {
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
        const apiKey = '5b3ce3597851110001cf62489581e06f9b72467e9439db10cfffedf9';
        const url = `https://api.openrouteservice.org/v2/directions/${perfil}?api_key=${apiKey}&start=${puntoA.lng},${puntoA.lat}&end=${puntoB.lng},${puntoB.lat}`;

        fetch(url)
    .then(response => response.json())
    .then(data => {
        const coordenadas = data.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
        rutaORS = L.polyline(coordenadas, { color: 'blue' }).addTo(mapa);
        mapa.fitBounds(rutaORS.getBounds());

        // ✅ Guarda la ruta en localStorage
        localStorage.setItem("ruta_AR", JSON.stringify(coordenadas));

        // Muestra el botón de AR
const btnAR = document.getElementById("btnAR");
btnAR.style.display = "flex";
setTimeout(() => {
    btnAR.style.opacity = "1";
}, 10);
    })
    .catch(error => console.error("Error obteniendo la ruta:", error));
    }

    // Escucha el cambio en el selector de modo de transporte
    document.getElementById('modoTransporte').addEventListener('change', calcularRuta);

    // Función para obtener la posición actual del usuario y calcular la ruta
    function irA(destLat, destLng) {
        if (!navigator.geolocation) {
            alert("La geolocalización no está soportada por tu navegador.");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                // Establece el punto A como la posición actual del usuario
                seleccionarPuntoA(userLat, userLng);

                // Establece el punto B como el destino seleccionado
                seleccionarPuntoB(destLat, destLng);
            },
            (error) => {
                alert("No se pudo obtener tu ubicación. Por favor, verifica los permisos de geolocalización.");
                console.error("Error de geolocalización:", error);
            }
        );
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
                    marcadorUsuario = L.marker([lat, lng], {
                        icon: L.icon({
                            iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png', // Ícono personalizado
                            iconSize: [25, 25]
                        })
                    }).bindPopup("Estás aquí").addTo(mapa);
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
</script>
</body>
</html>