<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Guía en Realidad Aumentada</title>
  <style>
    body {
      margin: 0;
      overflow: hidden;
      background: linear-gradient(135deg, #181c24 0%, #232a34 100%);
      color: #fff;
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
      letter-spacing: 0.02em;
      min-height: 100vh;
    }

    #compassWrapper {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 45px;
      overflow: hidden;
      background: rgba(20, 20, 20, 0.92);
      box-shadow: 0 2px 12px 0 rgba(0,0,0,0.25);
      z-index: 999;
      border-bottom: 1.5px solid #ffcc00;
    }

    #compassLabels {
      display: flex;
      width: max-content;
      padding-top: 12px;
      transition: transform 0.1s linear;
    }

    #compassLabels span {
      width: 60px;
      text-align: center;
      font-size: 16px;
      opacity: 0.5;
      color: #e0e0e0;
      text-shadow: 0 1px 4px #000;
      letter-spacing: 0.1em;
    }

    #compassLabels span.norte {
      font-weight: bold;
      color: #ffcc00;
      font-size: 18px;
      opacity: 1;
      text-shadow: 0 2px 8px #000, 0 0 6px #ffcc00;
    }

    #compassPointer {
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      font-size: 26px;
      color: #ffcc00;
      z-index: 1000;
      pointer-events: none;
      padding-top: 6px;
      text-shadow: 0 2px 10px #000, 0 0 8px #ffcc00;
    }

    #direccionActual {
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 20px;
      color: #fff;
      background: linear-gradient(90deg, #232a34 60%, #2e3540 100%);
      padding: 6px 22px;
      border-radius: 14px;
      z-index: 998;
      white-space: nowrap;
      box-shadow: 0 2px 12px 0 rgba(0,0,0,0.25);
      border: 1.5px solid #ffcc00;
      font-weight: 500;
      letter-spacing: 0.04em;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.3s;
    }
    #direccionActual::before {
      content: "🧭";
      font-size: 22px;
      margin-right: 6px;
      filter: drop-shadow(0 0 4px #ffcc00);
    }

    #gpsPrecision {
      position: fixed;
      top: 70px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(30, 30, 30, 0.85);
      color: #ffcc00;
      padding: 4px 18px;
      border-radius: 10px;
      font-size: 15px;
      z-index: 998;
      display: none;
      box-shadow: 0 2px 8px 0 rgba(0,0,0,0.18);
      border: 1px solid #ffcc00;
      font-weight: 500;
      letter-spacing: 0.03em;
      text-shadow: 0 1px 4px #000;
    }

    #camera, #arrow, #contador {
      display: none;
    }

    video#camera {
      position: absolute;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      object-fit: cover;
      filter: brightness(0.95) contrast(1.08) saturate(1.1);
    }

    img#arrow {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 110px;
      height: 110px;
      transform: translate(-50%, -50%) rotate(0deg);
      z-index: 10;
      filter: drop-shadow(0 0 18px #ffcc00cc) drop-shadow(0 0 8px #fff2);
      transition: filter 0.2s;
      animation: pulseArrow 1.2s infinite alternate;
    }
    @keyframes pulseArrow {
      0% { filter: drop-shadow(0 0 10px #ffcc00cc) drop-shadow(0 0 4px #fff2);}
      100% { filter: drop-shadow(0 0 28px #ffcc00cc) drop-shadow(0 0 16px #fff2);}
    }

    #contador {
      position: absolute;
      bottom: 32px;
      left: 50%;
      transform: translateX(-50%);
      background: linear-gradient(90deg, #232a34 60%, #2e3540 100%);
      color: #fff;
      padding: 14px 32px;
      border-radius: 16px;
      font-size: 20px;
      z-index: 11;
      text-align: center;
      font-weight: 600;
      box-shadow: 0 2px 16px 0 rgba(0,0,0,0.25);
      border: 1.5px solid #ffcc00;
      letter-spacing: 0.04em;
      text-shadow: 0 2px 8px #000;
      transition: background 0.3s;
      backdrop-filter: blur(2px);
      animation: fadeInContador 1.2s;
    }
    @keyframes fadeInContador {
      from { opacity: 0; transform: translateX(-50%) translateY(30px);}
      to { opacity: 1; transform: translateX(-50%) translateY(0);}
    }

    #cancelarRuta {
      position: fixed;
      top: 100px;
      left: 16px;
      z-index: 1001;
      background: linear-gradient(90deg, #232a34 60%, #2e3540 100%);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 12px 22px;
      font-size: 16px;
      cursor: pointer;
      font-weight: 600;
      box-shadow: 0 2px 12px 0 rgba(0,0,0,0.22);
      border: 1.5px solid #ff4d4d;
      transition: background 0.2s, color 0.2s, border 0.2s;
      display: flex;
      align-items: center;
      justify-content: center; /* <-- Añade esta línea para centrar */
      gap: 8px;
    }
    #cancelarRuta::before {
      content: "✖";
      font-size: 24px; /* Puedes aumentar el tamaño si lo deseas */
      color: #ff4d4d;
      margin: 0; /* Elimina el margin-right para centrar perfectamente */
    }
    #cancelarRuta:hover {
      background: linear-gradient(90deg, #ff4d4d 60%, #ffcc00 100%);
      color: #232a34;
      border: 1.5px solid #fff;
    }

    #btnAyuda {
      display: none;
      position: fixed;
      top: 160px;
      left: 16px;
      z-index: 1002;
      background: linear-gradient(135deg, #232a34 60%, #ffcc00 100%);
      color: #232a34;
      border: none;
      border-radius: 50%;
      width: 54px;
      height: 54px;
      font-size: 32px;
      cursor: pointer;
      box-shadow: 0 2px 12px 0 rgba(0,0,0,0.22);
      border: 2px solid #ffcc00;
      font-weight: bold;
      transition: background 0.2s, color 0.2s, border 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeInAyuda 1.2s;
    }
    #btnAyuda:hover {
      background: linear-gradient(135deg, #ffcc00 60%, #232a34 100%);
      color: #ff4d4d;
      border: 2px solid #ff4d4d;
    }
    @keyframes fadeInAyuda {
      from { opacity: 0; transform: scale(0.7);}
      to { opacity: 1; transform: scale(1);}
    }

    #loader {
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, #181c24 0%, #232a34 100%);
      backdrop-filter: blur(8px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      animation: fadeInLoader 0.8s;
    }
    @keyframes fadeInLoader {
      from { opacity: 0;}
      to { opacity: 1;}
    }

    #loader video {
      width: 220px;
      height: auto;
      border-radius: 16px;
      box-shadow: 0 0 32px 0 #ffcc0033, 0 0 12px #000;
      margin-bottom: 18px;
    }

    #loader p {
      margin-top: 12px;
      font-size: 22px;
      text-align: center;
      color: #ffcc00;
      font-weight: 600;
      letter-spacing: 0.04em;
      text-shadow: 0 2px 8px #000;
      animation: pulseText 1.2s infinite alternate;
    }
    @keyframes pulseText {
      0% { color: #ffcc00;}
      100% { color: #fff;}
    }

    .nota {
      margin-top: 18px;
      text-align: center;
      font-size: 17px;
      max-width: 80%;
      opacity: 0;
      animation: fadeIn 2s ease-in forwards;
      color: #fff;
      text-shadow: 0 1px 4px #000;
    }

    .nota strong {
      font-size: 20px;
      display: block;
      margin-bottom: 4px;
      color: #ffcc00;
      text-shadow: 0 2px 8px #000, 0 0 6px #ffcc00;
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }

    /* Overlay de llegada */
    #llegadaOverlay {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: linear-gradient(135deg, #232a34 60%, #00ff8855 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      animation: fadeInLlegada 0.7s;
    }
    @keyframes fadeInLlegada {
      from { opacity: 0;}
      to { opacity: 1;}
    }
    #llegadaOverlay div:first-child {
      font-size: 110px !important;
      color: #00ff88;
      margin-bottom: 24px;
      text-shadow: 0 2px 18px #000, 0 0 24px #00ff88;
      animation: popLlegada 0.7s cubic-bezier(.17,.67,.83,.67);
    }
    #llegadaOverlay div:last-child {
      font-size: 2.2rem !important;
      color: #fff;
      text-align: center;
      text-shadow: 0 2px 8px #000;
      font-weight: bold;
      letter-spacing: 0.04em;
      margin-top: 8px;
    }
    @keyframes popLlegada {
      0% { transform: scale(0.2); opacity: 0; }
      60% { transform: scale(1.2); opacity: 1; }
      100% { transform: scale(1); opacity: 1; }
    }

    /* Responsive */
    @media (max-width: 600px) {
      #direccionActual, #gpsPrecision, #contador {
        font-size: 16px !important;
        padding: 8px 10px !important;
      }
      #cancelarRuta, #btnAyuda {
        font-size: 14px !important;
        padding: 8px 10px !important;
        width: 44px !important;
        height: 44px !important;
      }
      img#arrow {
        width: 70px;
        height: 70px;
      }
      #loader video {
        width: 120px;
      }
    }
  </style>
</head>
<body>


  <!-- Dirección actual -->
  <div id="direccionActual" style="top:16px;font-size:17px;">Orientado hacia: --</div>

  <!-- Precisión GPS -->
  <div id="gpsPrecision" style="display:none;">Precisión: -- m</div>

!-- Botón cancelar -->
<button id="cancelarRuta" onclick="window.location.href='../index.php'" style="display:none;" aria-label="Cancelar ruta"></button>

  <!-- Carga -->
  <div id="loader">
    <video src="../assets/img/brujula.WEBM" autoplay muted playsinline loop></video>
    <p id="loadingText">Recalibrando</p>
    <div class="nota fade-in">
      <strong>NOTA:</strong>
      <span>Es recomendable agitar ligeramente el teléfono para calibrar la brújula</span>
    </div>
  </div>

  <video id="camera" autoplay playsinline></video>
  <img id="arrow" src="../assets/img/arrow.png" alt="Flecha" />
  <div id="contador">Paso 0 de 0</div>

  <?php
  include 'coords.php'; // Asegúrate de incluir coords.php al inicio del archivo
  ?>

  <!-- Botón ayuda "?" -->
  <button id="btnAyuda" title="¿Dónde estoy?" style="display:none;position:fixed;top:160px;left:16px;z-index:1002;background:#222;color:#ffcc00;border:none;border-radius:50%;width:44px;height:44px;font-size:28px;cursor:pointer;">?</button>

  <script>
    window.speechSynthesis.speak(new SpeechSynthesisUtterance("")); // precarga voz

    const compassLabels = document.getElementById("compassLabels");
    const direccionActual = document.getElementById("direccionActual");
    const gpsPrecision = document.getElementById("gpsPrecision");

    const direcciones = ["N", "NE", "E", "SE", "S", "SO", "O", "NO"];
    const direccionesText = ["Norte", "Noreste", "Este", "Sureste", "Sur", "Suroeste", "Oeste", "Noroeste"];

 

    const video = document.getElementById('camera');
    const arrow = document.getElementById('arrow');
    const contador = document.getElementById('contador');
    const loader = document.getElementById('loader');

    let heading = 0;
    let watchId = null;
    let anguloFlecha = 0;
    let anguloActual = 0;

    let ruta = JSON.parse(localStorage.getItem("ruta_AR") || "[]");
    let indicePuntoActual = 0;

    if (ruta.length === 0) alert("No hay ruta cargada en memoria.");

    let puntos = 0;
    const textoOriginal = "Recalibrando";
    const intervaloTexto = setInterval(() => {
      puntos = (puntos + 1) % 4;
      document.getElementById("loadingText").textContent = textoOriginal + ".".repeat(puntos);
    }, 500);

    // Oculta los botones al inicio
    document.getElementById("cancelarRuta").style.display = "none";
    document.getElementById("btnAyuda").style.display = "none";

    setTimeout(() => {
      clearInterval(intervaloTexto);
      loader.style.display = "none";
      video.style.display = "block";
      arrow.style.display = "block";
      contador.style.display = "block";

      // Mostrar los botones después de la carga
      document.getElementById("cancelarRuta").style.display = "block";
      document.getElementById("btnAyuda").style.display = "block";

      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => video.srcObject = stream)
        .catch(error => {
          console.error("Error al acceder a la cámara:", error);
          alert("No se pudo acceder a la cámara.");
        });
    }, 5000);

    function toRad(grados) {
      return grados * Math.PI / 180;
    }

    function calcularAngulo(lat1, lng1, lat2, lng2) {
      const dLng = toRad(lng2 - lng1);
      const y = Math.sin(dLng) * Math.cos(toRad(lat2));
      const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) -
                Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLng);
      const brng = Math.atan2(y, x);
      return (brng * 180 / Math.PI + 360) % 360;
    }

    function distanciaMetros(lat1, lng1, lat2, lng2) {
      const R = 6371e3;
      const φ1 = toRad(lat1);
      const φ2 = toRad(lat2);
      const Δφ = toRad(lat2 - lat1);
      const Δλ = toRad(lng2 - lng1);
      const a = Math.sin(Δφ / 2) ** 2 +
                Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    function anguloACardinal(angulo) {
      const index = Math.round(angulo / 45) % 8;
      return direccionesText[index];
    }

    function anunciarDireccion(distancia, angulo) {
      const pasos = Math.round(distancia / 0.9);
      const direccion = anguloACardinal(angulo);
      const mensaje = `Avanza aproximadamente ${pasos} pasos hacia el ${direccion}.`;
      const voz = new SpeechSynthesisUtterance(mensaje);
      voz.lang = "es-ES";
      voz.rate = 1;
      voz.pitch = 1;
      window.speechSynthesis.speak(voz);
    }

    function mostrarIndicadorLlegada() {
      // Crear overlay visual
      let overlay = document.createElement("div");
      overlay.id = "llegadaOverlay";
      overlay.style.position = "fixed";
      overlay.style.top = 0;
      overlay.style.left = 0;
      overlay.style.width = "100vw";
      overlay.style.height = "100vh";
      overlay.style.background = "rgba(0,0,0,0.7)";
      overlay.style.display = "flex";
      overlay.style.flexDirection = "column";
      overlay.style.alignItems = "center";
      overlay.style.justifyContent = "center";
      overlay.style.zIndex = 2000;

      // Animación de check
      let check = document.createElement("div");
      check.innerHTML = "&#10004;";
      check.style.fontSize = "90px";
      check.style.color = "#00ff88";
      check.style.marginBottom = "20px";
      check.style.animation = "popLlegada 0.7s cubic-bezier(.17,.67,.83,.67)";

      // Mensaje
      let mensaje = document.createElement("div");
      mensaje.textContent = "¡Has llegado a tu destino!";
      mensaje.style.fontSize = "2rem";
      mensaje.style.color = "#fff";
      mensaje.style.textAlign = "center";
      mensaje.style.textShadow = "0 2px 8px #000";

      // Agregar al overlay
      overlay.appendChild(check);
      overlay.appendChild(mensaje);
      document.body.appendChild(overlay);

      // Animación CSS
      const style = document.createElement('style');
      style.innerHTML = `
        @keyframes popLlegada {
          0% { transform: scale(0.2); opacity: 0; }
          60% { transform: scale(1.2); opacity: 1; }
          100% { transform: scale(1); opacity: 1; }
        }
      `;
      document.head.appendChild(style);
    }

    function actualizarDireccion(pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;

      // Mostrar precisión de GPS
      const precision = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : "--";
      const gpsPrecision = document.getElementById("gpsPrecision");
      gpsPrecision.style.display = "block";
      gpsPrecision.textContent = `Precisión GPS: ${precision} m`;

      if (indicePuntoActual >= ruta.length) {
        contador.textContent = "¡Has llegado a tu destino!";
        mostrarIndicadorLlegada();
        const vozFinal = new SpeechSynthesisUtterance("¡Has llegado a tu destino!");
        vozFinal.lang = "es-ES";
        window.speechSynthesis.speak(vozFinal);

        // Redirigir al mapa después de 4 segundos
        setTimeout(() => {
          window.location.href = "../index.php";
        }, 4000);
        return;
      }

      const objetivo = ruta[indicePuntoActual];
      const distancia = distanciaMetros(lat, lng, objetivo[0], objetivo[1]);

      // Calcular distancia restante hasta el destino
      let distanciaRestante = distancia;
      for (let i = indicePuntoActual; i < ruta.length - 1; i++) {
        distanciaRestante += distanciaMetros(ruta[i][0], ruta[i][1], ruta[i + 1][0], ruta[i + 1][1]);
      }
      const distanciaRestanteStr = distanciaRestante > 1000
        ? (distanciaRestante / 1000).toFixed(2) + " km"
        : Math.round(distanciaRestante) + " m";

      if (distancia < 10 && indicePuntoActual < ruta.length - 1) {
        indicePuntoActual++;
        const nuevo = ruta[indicePuntoActual];
        const nuevoAngulo = calcularAngulo(lat, lng, nuevo[0], nuevo[1]);
const nuevaDistancia = distanciaMetros(lat, lng, nuevo[0], nuevo[1]);
        anunciarDireccion(nuevaDistancia, nuevoAngulo);
      }

      const siguiente = ruta[indicePuntoActual];
      const angulo = calcularAngulo(lat, lng, siguiente[0], siguiente[1]);
      anguloFlecha = (angulo - heading + 360) % 360;

      // Mostrar paso y distancia restante en líneas separadas
      contador.innerHTML = `Paso ${indicePuntoActual + 1} de ${ruta.length}<br>${distanciaRestanteStr} faltante`;
    }

    function animarFlecha() {
      const diferencia = (anguloFlecha - anguloActual + 540) % 360 - 180;
      anguloActual = (anguloActual + diferencia * 0.1 + 360) % 360;
      arrow.style.transform = `translate(-50%, -50%) rotate(${anguloActual}deg)`;
      requestAnimationFrame(animarFlecha);
    }
    requestAnimationFrame(animarFlecha);

    window.addEventListener(
      'deviceorientationabsolute' in window ? 'deviceorientationabsolute' : 'deviceorientation',
      (event) => {
        if (typeof event.webkitCompassHeading !== "undefined") {
          heading = event.webkitCompassHeading;
        } else if (event.alpha !== null) {
          heading = 360 - event.alpha;
        }
        const dirIndex = Math.round(heading / 45) % 8;
        direccionActual.textContent = "Orientado hacia: " + ["norte", "noreste", "este", "sureste", "sur", "suroeste", "oeste", "noroeste"][dirIndex];
      }
    );

    if ("geolocation" in navigator) {
      watchId = navigator.geolocation.watchPosition(
        actualizarDireccion,
        (err) => {
          console.error("Error de geolocalización:", err);
          alert("No se pudo obtener tu ubicación.");
        },
        {
          enableHighAccuracy: true,
          maximumAge: 0,
          timeout: 10000
        }
      );
    } else {
      alert("Geolocalización no disponible.");
    }

    window.addEventListener('beforeunload', () => {
      if (watchId) navigator.geolocation.clearWatch(watchId);
    });

    // Exportar marcadores PHP a JS (solo los que tengan nombre, latitud y longitud)
    const lugares = <?php
      $lugares = [];
      foreach ($marcadores as $m) {
        if (
          isset($m['nombre']) &&
          isset($m['latitud']) &&
          isset($m['longitud'])
        ) {
          $lugares[] = [
            'nombre' => $m['nombre'],
            'lat' => $m['latitud'],
            'lng' => $m['longitud']
          ];
        }
      }
      echo json_encode($lugares, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    ?>;

    // Función para buscar el lugar más cercano
    function lugarMasCercano(lat, lng) {
      let minDist = Infinity;
      let masCercano = null;
      lugares.forEach(lugar => {
        const d = distanciaMetros(lat, lng, lugar.lat, lugar.lng);
        if (d < minDist) {
          minDist = d;
          masCercano = lugar;
          masCercano.dist = d;
        }
      });
      return masCercano;
    }

    // Evento del botón "?" para mostrar el lugar más cercano
    document.getElementById("btnAyuda").onclick = function() {
      if (!navigator.geolocation) {
        alert("Geolocalización no disponible.");
        return;
      }
      navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const lugar = lugarMasCercano(lat, lng);
        if (lugar && lugar.dist < 30) { // 30 metros de tolerancia
          const mensaje = `Estás cerca de ${lugar.nombre}`;
          direccionActual.textContent = mensaje;
          window.speechSynthesis.speak(new SpeechSynthesisUtterance(mensaje));
        } else {
          const mensaje = "No hay lugares cercanos detectados.";
          direccionActual.textContent = mensaje;
          window.speechSynthesis.speak(new SpeechSynthesisUtterance(mensaje));
        }
      }, function() {
        alert("No se pudo obtener tu ubicación.");
      }, { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 });
    };
  </script>
</body>
</html>
