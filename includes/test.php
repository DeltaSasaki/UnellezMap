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
<link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="./assets/css/map.css">
</head>
<body>

<div class="search-wrapper">
<input type="search" id="search" name="q<?= rand(1000,9999) ?>" placeholder="Buscar lugar..." autocomplete="off" autocorrect="off" spellcheck="false">


  <div id="suggestions" class="suggestions-container"></div>
</div>

<div id="map"></div>

<!-- Menú de capas personalizado -->
<div id="gearControl" class="leaflet-control leaflet-bar" title="Opciones de mapa">
  <img src="assets/img/gear.png" alt="Opciones" style="width: 24px; height: 24px;">
</div>

<div id="mapLayersMenu" class="map-layers-menu">
  <label><input type="radio" name="mapa" value="osm"checked> OpenStreetMap</label>
  <label><input type="radio" name="mapa" value="topo"> OpenTopoMap</label>
  <label><input type="radio" name="mapa" value="satelite"> Satelital</label>
  <label><input type="radio" name="mapa" value="oscuro"> Modo Oscuro</label>
  <label><input type="radio" name="mapa" value="maptiler"> MapTiler HD</label>
<label><input type="radio" name="mapa" value="maptilerStreets"> MapTiler Streets</label>
<label><input type="radio" name="mapa" value="maptilerSatelite"> Satelital (MapTiler)</label>
<label><input type="radio" name="mapa" value="maptilerOutdoor" > MapTiler Outdoor</label>

    


    <hr style="margin: 8px 0;">

  <label for="modoTransporte"><strong>Modo de transporte</strong></label>
  <select id="modoTransporte" style="margin-top: 4px; width: 100%;">
    <option value="walking">Peatón</option>
    <option value="driving">Vehículo</option>
  </select>
</div>
<!-- Alerta personalizada -->
<div id="alertaRuta" class="alerta-mapa">
  El sistema de ruta solo funciona dentro de la instalación.
</div>

<!-- Botón de filtro debajo del engranaje -->
<div id="gearFilterControl" class="leaflet-control leaflet-bar" title="Filtrar por tipo">
  <img src="assets/img/filter.png" alt="Filtrar" style="width: 24px; height: 24px;">
</div>

<div id="filtroTipos" class="filter-panel" style="display: none;">
  <label><input type="checkbox" value="salon" checked> Salones</label><br>
  <label><input type="checkbox" value="cubiculo" checked> Cubículos</label><br>
  <label><input type="checkbox" value="laboratorio" checked> Laboratorios</label><br>
  <label><input type="checkbox" value="oficina" checked> Oficinas</label><br>
  <label><input type="checkbox" value="cafetin" checked> Cafetines</label><br>
  <label><input type="checkbox" value="cabaña" checked> Cabañas</label><br>
  <label><input type="checkbox" value="bano" checked> Baños</label><br>
  <label><input type="checkbox" value="recreacion" checked> Zonas Recreativas</label><br>
  <label><input type="checkbox" value="fotocopiadora" checked> Fotocopiadoras</label><br>
  
  <hr style="margin: 10px 0;">
  <button id="btnReiniciarFiltros" style="width: 100%; padding: 5px;">Reiniciar filtros</button>
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

<!-- Modal para imagen ampliada -->
<div id="modalImagen" class="modal-imagen" style="display: none;">
  <div class="modal-overlay"></div>
  <div class="modal-contenido">
    <span id="cerrarModal" class="modal-cerrar">&times;</span>
    <img id="imagenAmpliada" src="" alt="Imagen ampliada">
  </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    let ultimoMarcadorAnimado = null;
    // Inicializa el mapa y lo centra en la primera ubicación
const mapa = L.map('map', {
  zoomControl: false,

}).setView([<?= $marcadores[0]['latitud'] ?>, <?= $marcadores[0]['longitud'] ?>], 19);





    // 👇 AÑADE ESTO
document.getElementById("btnLimpiar").style.display = "none";
document.getElementById("btnAR").style.display = "none";

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
    }),
    
    "maptiler": L.tileLayer('https://api.maptiler.com/maps/basic/{z}/{x}/{y}.png?key=YQx7NLZ5DojZGUKvI0ZN', {
    tileSize: 512,
    zoomOffset: -1,
    minZoom: 0,
    maxZoom: 22,
    attribution: '&copy; MapTiler & OpenStreetMap contributors'
}),

"maptilerStreets": L.tileLayer('https://api.maptiler.com/maps/streets/{z}/{x}/{y}.png?key=YQx7NLZ5DojZGUKvI0ZN', {
    tileSize: 512,
    zoomOffset: -1,
    minZoom: 0,
    maxZoom: 22,
    attribution: '&copy; MapTiler & OpenStreetMap contributors'
}),

"maptilerSatelite": L.tileLayer('https://api.maptiler.com/maps/hybrid/{z}/{x}/{y}.jpg?key=YQx7NLZ5DojZGUKvI0ZN', {
    tileSize: 512,
    zoomOffset: -1,
    minZoom: 0,
    maxZoom: 22,
    attribution: '&copy; MapTiler, OpenStreetMap contributors'
}),

"maptilerOutdoor": L.tileLayer('https://api.maptiler.com/maps/outdoor/{z}/{x}/{y}.png?key=YQx7NLZ5DojZGUKvI0ZN', {
    tileSize: 512,
    zoomOffset: -1,
    minZoom: 0,
    maxZoom: 22,
    attribution: '&copy; MapTiler & OpenStreetMap contributors'
}),

};

let capaActual = capas["osm"];
capaActual.addTo(mapa);

 

// Botón del menú de capas
document.getElementById("gearControl").addEventListener("click", () => {
    const menuCapas = document.getElementById("mapLayersMenu");
    const menuFiltros = document.getElementById("filtroTipos");
    const gearIcon = document.querySelector("#gearControl img");

    const abierto = menuCapas.style.display === "block";

    // Cierra el otro menú
    menuFiltros.style.display = "none";

    // Cierra el infoPanel si está abierto
const infoPanel = document.getElementById("infoPanel");
if (infoPanel.classList.contains("show")) {
    infoPanel.classList.remove("show");
    document.getElementById("btnIr").disabled = true;
    restaurarVisibilidadMarcadores();

    // Elimina marcadores temporales
    Object.values(referenciaMarcadores).forEach(marcador => {
        if (marcador._temporario) {
            mapa.removeLayer(marcador);
            delete marcador._temporario;
        }
    });
}

    menuCapas.style.display = abierto ? "none" : "block";

    // Reinicia la animación SIEMPRE
    gearIcon.classList.remove("spin-animation");
    void gearIcon.offsetWidth; // ← fuerza reflow
    gearIcon.classList.add("spin-animation");
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
        descripcion: "<?= $marcador['descripcion'] ?>",
        tipo: "<?= strtolower($marcador['tipo'] ?? 'otro') ?>"
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
const capasPorTipo = {};
const referenciaMarcadores = {};
marcadores.forEach(marcador => {
const marker = L.marker([marcador.latitud, marcador.longitud], { icon: obtenerIconoPorTipo(marcador.tipo) })
 // ← Usa iconCoords aquí
        .on('click', () => {
            const btnIr = document.getElementById("btnIr");
            // Cierra el menú de capas si está abierto
            const menu = document.getElementById("mapLayersMenu");
            if (menu.style.display === "block") {
                menu.style.display = "none";
            }

            
if (marcadorTemporalGIF) {
    mapa.removeLayer(marcadorTemporalGIF);
    marcadorTemporalGIF = null;
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
            resaltarMarcador(marcador.nombre);
            // Asegura que el marcador de ese tipo esté visible temporalmente
const marcadorSeleccionado = referenciaMarcadores[marcador.nombre];
if (marcadorSeleccionado && !mapa.hasLayer(marcadorSeleccionado)) {
  marcadorSeleccionado.addTo(mapa);
  marcadorSeleccionado._temporario = true; // lo marcamos como temporal
}


        });
        referenciaMarcadores[marcador.nombre] = marker;
if (!capasPorTipo[marcador.tipo]) {
    capasPorTipo[marcador.tipo] = L.layerGroup().addTo(mapa);
}

capasPorTipo[marcador.tipo].addLayer(marker);
});
layerMarcadores.addTo(mapa);

// Función para buscar lugares y mostrar sugerencias
function buscarLugar() {
  const normalizar = str => str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  const texto = normalizar(document.getElementById('search').value); // ← Aquí aplicas normalización

  const resultados = marcadores.filter(marcador =>
    normalizar(marcador.nombre).includes(texto)
  );

  const suggestions = document.getElementById('suggestions');
  suggestions.innerHTML = '';

  if (texto && resultados.length > 0) {
    resultados.slice(0, 6).forEach(marcador => {
      const div = document.createElement('div');
      div.className = 'suggestion-item';

      const img = document.createElement('img');
      img.src = marcador.imagen;

      const span = document.createElement('span');
      span.textContent = marcador.nombre;

      div.appendChild(img);
      div.appendChild(span);

div.addEventListener('click', () => {
  // Mostrar marcador temporal si está oculto
  const checkbox = document.querySelector(`#filtroTipos input[value="${marcador.tipo}"]`);
if (checkbox && !checkbox.checked) {
  const marcadorSeleccionado = referenciaMarcadores[marcador.nombre];
  if (marcadorSeleccionado && !mapa.hasLayer(marcadorSeleccionado)) {
    marcadorSeleccionado.addTo(mapa);
    marcadorSeleccionado._temporario = true;
  }
}

  // Esperar a que el marcador esté visible antes de resaltarlo
  setTimeout(() => {
    resaltarMarcador(marcador.nombre);
    mapa.setView([marcador.latitud, marcador.longitud], 17);
    document.getElementById("infoTitulo").textContent = marcador.nombre;
    document.getElementById("infoImagen").src = marcador.imagen;
    document.getElementById("infoDescripcion").textContent = marcador.descripcion;
    document.getElementById("btnIr").disabled = false;
    document.getElementById("btnIr").onclick = () => {
      irA(marcador.latitud, marcador.longitud);
    };
    document.getElementById("infoPanel").classList.add("show");
  }, 50); // espera breve para asegurar render

  document.getElementById('search').value = '';
  document.getElementById('suggestions').style.display = 'none';
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

// 👉 Al cambiar el modo de transporte, recalcula la ruta si ya hay puntos definidos
document.getElementById('modoTransporte').addEventListener('change', () => {
    if (puntoA && puntoB) {
        calcularRuta();
    }
});

    // Variables para almacenar los marcadores de los puntos A y B
    let markerPuntoA = null;
    let markerPuntoB = null;

    // Función para limpiar los puntos y la ruta
function limpiarRuta() {
    rutaActiva = false;
    if (controlRuta) {
        mapa.removeControl(controlRuta);
        controlRuta = null;
    }
    if (rutaORS) {
        mapa.removeLayer(rutaORS);
        rutaORS = null;
    }
    document.getElementById("btnLimpiar").style.display = "none";
    document.getElementById("btnAR").style.display = "none";
    mostrarTodosLosMarcadores();
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

  // Verifica si está dentro de los límites


 

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
    iconSize: [40, 40],
    iconAnchor: [20, 20]
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
  limpiarRuta(); // Elimina la ruta existente
  const modoTransporte = document.getElementById('modoTransporte').value;
  ocultarTodosLosMarcadores();
  rutaActiva = true;

  if (puntoA && puntoB) {
    let perfil = '';
    if (modoTransporte === 'walking') {
      perfil = 'foot-walking';
    } else if (modoTransporte === 'driving') {
      perfil = 'driving-car';
    }





    if (perfil) {
      obtenerRutaOpenRouteService(puntoA, puntoB, perfil);
    }
  }
}

function mostrarTodosLosMarcadores() {
    Object.entries(capasPorTipo).forEach(([tipo, capa]) => {
        const checkbox = document.querySelector(`#filtroTipos input[value="${tipo}"]`);
        if (checkbox && checkbox.checked) {
            capa.addTo(mapa);
        }
    });
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
        restaurarVisibilidadMarcadores();

        // Eliminar solo el marcador temporal si fue agregado
Object.values(referenciaMarcadores).forEach(marcador => {
  if (marcador._temporario) {
    mapa.removeLayer(marcador);
    delete marcador._temporario;
  }
});

        document.querySelectorAll('#filtroTipos input[type="checkbox"]').forEach(cb => {
  if (cb.dataset.temporal === "1" && !cb.checked) {
    capasPorTipo[cb.value]?.remove();
    delete cb.dataset.temporal;
  }
});
});

document.addEventListener('click', function(e) {
    const gear1 = document.getElementById("gearControl");
    const gear2 = document.getElementById("gearFilterControl");
    const menu1 = document.getElementById("mapLayersMenu");
    const menu2 = document.getElementById("filtroTipos");

    const clicFuera = !gear1.contains(e.target) && !menu1.contains(e.target) &&
                      !gear2.contains(e.target) && !menu2.contains(e.target);

    if (clicFuera) {
        const estabaAbierto = menu1.style.display === "block";
        menu1.style.display = "none";
        menu2.style.display = "none";

        if (estabaAbierto) {
            girarGearIcon(); // ← animar al cerrar el menú
        }
    }
});

document.getElementById("btnLimpiar").addEventListener("click", limpiarMapa);

function irA(destLat, destLng) {
    const btn = document.getElementById("btnIr");
    btn.textContent = "Obteniendo ubicación...";
    btn.disabled = true;

    if (!navigator.geolocation) {
        alert("La geolocalización no está soportada por tu navegador.");
        btn.textContent = "Quiero ir ahí";
        btn.disabled = false;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            seleccionarPuntoA(userLat, userLng); // define el origen
            seleccionarPuntoB(destLat, destLng); // define el destino

            btn.textContent = "Quiero ir ahí";
            btn.disabled = false;
        },
        (error) => {
            alert("No se pudo obtener tu ubicación. Por favor, verifica los permisos de geolocalización.");
            console.error("Error de geolocalización:", error);
            btn.textContent = "Quiero ir ahí";
            btn.disabled = false;
        }
    );
}



document.getElementById("gearFilterControl").addEventListener("click", () => {
    const menuFiltros = document.getElementById("filtroTipos");
    const menuCapas = document.getElementById("mapLayersMenu");

    const abierto = menuFiltros.style.display === "block";

    // Cierra el otro menú
    menuCapas.style.display = "none";

    // Cierra el infoPanel si está abierto
const infoPanel = document.getElementById("infoPanel");
if (infoPanel.classList.contains("show")) {
    infoPanel.classList.remove("show");
    document.getElementById("btnIr").disabled = true;
    restaurarVisibilidadMarcadores();

    // Elimina marcadores temporales
    Object.values(referenciaMarcadores).forEach(marcador => {
        if (marcador._temporario) {
            mapa.removeLayer(marcador);
            delete marcador._temporario;
        }
    });
}

    menuFiltros.style.display = abierto ? "none" : "block";
});

document.querySelectorAll('#filtroTipos input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        if (rutaActiva) return; // No hacer nada si la ruta está activa

        const tipo = checkbox.value;
        if (checkbox.checked) {
            capasPorTipo[tipo]?.addTo(mapa);
        } else {
            capasPorTipo[tipo]?.remove();
        }
    });
});

function girarGearIcon() {
    const gearIcon = document.querySelector("#gearControl img");
    gearIcon.classList.remove("spin-animation");
    void gearIcon.offsetWidth; // ← fuerza reflow
    gearIcon.classList.add("spin-animation");
}

let rutaActiva = false;

function ocultarTodosLosMarcadores() {
    Object.values(capasPorTipo).forEach(capa => {
        mapa.removeLayer(capa);
    });
}

document.getElementById("btnReiniciarFiltros").addEventListener("click", () => {
    // Marca todos los filtros como activos
    document.querySelectorAll('#filtroTipos input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });

    // Solo muestra los marcadores si no hay ruta activa
    if (!rutaActiva) {
        mostrarTodosLosMarcadores();
    }
});

function resaltarMarcador(nombre) {
    const marcador = referenciaMarcadores[nombre];
    if (!marcador) return;

    const latlng = marcador.getLatLng();

    // Pulse fijo en el mapa
    const pulseIcon = L.divIcon({
        className: '',
        html: '<div class="pulse-circle"></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 15],
    });
    const pulseMarker = L.marker(latlng, { icon: pulseIcon, interactive: false }).addTo(mapa);
    setTimeout(() => mapa.removeLayer(pulseMarker), 1000);

    // Restaurar el ícono anterior (si existe y es distinto)
    if (ultimoMarcadorAnimado && ultimoMarcadorAnimado !== marcador) {
        const nombreAnterior = Object.keys(referenciaMarcadores).find(key => referenciaMarcadores[key] === ultimoMarcadorAnimado);
        const tipoAnterior = marcadores.find(m => m.nombre === nombreAnterior)?.tipo ?? 'otro';
        ultimoMarcadorAnimado.setIcon(obtenerIconoPorTipo(tipoAnterior));
    }

    // Cambiar ícono actual por uno animado
    const divZoomIcon = L.divIcon({
        className: '',
        html: '<img src="assets/img/icon_coords.png" class="marker-zoom" style="width:32px;height:32px;">',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
    });

    marcador.setIcon(divZoomIcon);
    ultimoMarcadorAnimado = marcador;

    ocultarMarcadoresCercanos(latlng);
}





function ocultarMarcadoresCercanos(latlngCentral) {
    Object.entries(referenciaMarcadores).forEach(([nombre, marcador]) => {
        const el = marcador.getElement();
        if (!el) return;

        const latlng = marcador.getLatLng();
        const distancia = mapa.distance(latlng, latlngCentral);

        if (distancia < 35 && !latlng.equals(latlngCentral)) {
            el.classList.add("marker-muted");
        } else {
            el.classList.remove("marker-muted");
        }
    });
}

function restaurarVisibilidadMarcadores() {
    Object.entries(referenciaMarcadores).forEach(([nombre, marcador]) => {
        const el = marcador.getElement();
        if (el) el.classList.remove("marker-muted");

        // Restaurar ícono original basado en su tipo
        const tipoOriginal = marcadores.find(m => m.nombre === nombre)?.tipo ?? 'otro';
        marcador.setIcon(obtenerIconoPorTipo(tipoOriginal));
    });

    ultimoMarcadorAnimado = null;
}


// Click sobre imagen del panel para abrir el modal
document.getElementById("infoImagen").addEventListener("click", () => {
  const src = document.getElementById("infoImagen").src;
  document.getElementById("imagenAmpliada").src = src;
  document.getElementById("modalImagen").style.display = "flex";
});

// Cerrar modal con botón
document.getElementById("cerrarModal").addEventListener("click", () => {
  document.getElementById("modalImagen").style.display = "none";
});

// Cerrar modal al hacer clic fuera de la imagen
document.querySelector(".modal-overlay").addEventListener("click", () => {
  document.getElementById("modalImagen").style.display = "none";
});


document.getElementById("search").setAttribute("autocomplete", "off");

function obtenerIconoPorTipo(tipo) {
    const iconos = {
        salon: 'icon_salon.png',
        cubiculo: 'icon_cubiculo.png',
        laboratorio: 'icon_laboratorio.png',
        oficina: 'icon_oficina.png',
        cafetin: 'icon_cafetin.png',
        bano: 'icon_bano.png',
        recreacion: 'icon_recreacion.png',
        fotocopiadora: 'icon_fotocopiadora.png',
        estacionamiento: 'icon_estacionamiento.png',
        cabaña: 'icon_cabana.png'
    };

    const nombreArchivo = iconos[tipo] || 'icon_coords.png'; // por defecto
    return L.icon({
        iconUrl: `assets/img/${nombreArchivo}`,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });
}

function mostrarAlertaRuta() {
  const alerta = document.getElementById("alertaRuta");
  alerta.style.display = "block";
  alerta.style.opacity = "1";

  setTimeout(() => {
    alerta.style.opacity = "0";
    setTimeout(() => {
      alerta.style.display = "none";
    }, 300); // espera a que desaparezca con animación
  }, 3000); // visible por 3 segundos
}

document.addEventListener('contextmenu', function(e) {
  e.preventDefault();
});
</script>
</body>
</html>