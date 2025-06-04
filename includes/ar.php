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
      background-color: black;
      color: white;
      font-family: sans-serif;
    }

    #compassWrapper {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 45px;
      overflow: hidden;
      background: rgba(0, 0, 0, 0.85);
      z-index: 999;
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
    }

    #compassLabels span.norte {
      font-weight: bold;
      color: #ffcc00;
      font-size: 18px;
      opacity: 1;
    }

    #compassPointer {
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      font-size: 22px;
      color: #ffcc00;
      z-index: 1000;
      pointer-events: none;
      padding-top: 6px;
    }

    #direccionActual {
      position: fixed;
      top: 46px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 15px;
      color: white;
      background: rgba(0, 0, 0, 0.7);
      padding: 2px 10px;
      border-radius: 8px;
      z-index: 998;
    }

    #camera, #arrow, #contador {
      display: none;
    }

    video#camera {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    img#arrow {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 100px;
      height: 100px;
      transform: translate(-50%, -50%) rotate(0deg);
      z-index: 10;
    }

    #contador {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(0, 0, 0, 0.6);
      color: white;
      padding: 8px 16px;
      border-radius: 10px;
      font-size: 16px;
      z-index: 11;
    }

    #cancelarRuta {
      position: fixed;
      top: 80px;
      left: 16px;
      z-index: 1001;
      background-color: rgba(0, 0, 0, 0.7);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 16px;
      font-size: 14px;
      cursor: pointer;
    }

    #cancelarRuta:hover {
      background-color: rgba(255, 0, 0, 0.6);
    }

    #loader {
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(8px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    #loader video {
      width: 200px;
      height: auto;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
    }

    #loader p {
      margin-top: 12px;
      font-size: 18px;
      text-align: center;
    }

    .nota {
      margin-top: 14px;
      text-align: center;
      font-size: 16px;
      max-width: 80%;
      opacity: 0;
      animation: fadeIn 2s ease-in forwards;
    }

    .nota strong {
      font-size: 18px;
      display: block;
      margin-bottom: 4px;
      color: #ffcc00;
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }
  </style>
</head>
<body>

  <!-- Brújula -->
  <div id="compassWrapper">
    <div id="compassLabels"></div>
    <div id="compassPointer">▲</div>
  </div>

  <!-- Dirección actual -->
  <div id="direccionActual">Orientado hacia: --</div>

  <!-- Botón cancelar -->
  <button id="cancelarRuta" onclick="window.location.href='../index.php'">Cancelar Ruta</button>

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

  <script>
    window.speechSynthesis.speak(new SpeechSynthesisUtterance("")); // precarga voz

    const compassLabels = document.getElementById("compassLabels");
    const direccionActual = document.getElementById("direccionActual");

    const direcciones = ["N", "NE", "E", "SE", "S", "SO", "O", "NO"];
    const direccionesText = ["Norte", "Noreste", "Este", "Sureste", "Sur", "Suroeste", "Oeste", "Noroeste"];

    // Generar brújula
    for (let i = 0; i < 15; i++) {
      direcciones.forEach(dir => {
        const span = document.createElement("span");
        span.textContent = dir;
        if (dir === "N") span.classList.add("norte");
        compassLabels.appendChild(span);
      });
    }

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

    setTimeout(() => {
      clearInterval(intervaloTexto);
      loader.style.display = "none";
      video.style.display = "block";
      arrow.style.display = "block";
      contador.style.display = "block";

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

    function actualizarDireccion(pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;

      if (indicePuntoActual >= ruta.length) {
        contador.textContent = "¡Has llegado a tu destino!";
        const vozFinal = new SpeechSynthesisUtterance("¡Has llegado a tu destino!");
        vozFinal.lang = "es-ES";
        window.speechSynthesis.speak(vozFinal);
        return;
      }

      const objetivo = ruta[indicePuntoActual];
      const distancia = distanciaMetros(lat, lng, objetivo[0], objetivo[1]);

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

      contador.textContent = `Paso ${indicePuntoActual + 1} de ${ruta.length}`;
    }

    function animarFlecha() {
      const diferencia = (anguloFlecha - anguloActual + 540) % 360 - 180;
      anguloActual = (anguloActual + diferencia * 0.1 + 360) % 360;
      arrow.style.transform = `translate(-50%, -50%) rotate(${anguloActual}deg)`;
      requestAnimationFrame(animarFlecha);
    }
    requestAnimationFrame(animarFlecha);

    window.addEventListener('deviceorientationabsolute' in window ? 'deviceorientationabsolute' : 'deviceorientation', (event) => {
      if (typeof event.webkitCompassHeading !== "undefined") {
        heading = event.webkitCompassHeading;
      } else if (event.alpha !== null) {
        heading = 360 - event.alpha;
      }

      const totalWidth = compassLabels.scrollWidth;
      const offset = (heading / 360) * totalWidth - window.innerWidth / 2 + 30;
      compassLabels.style.transform = `translateX(${-offset}px)`;

      // Mostrar dirección actual
      const dirIndex = Math.round(heading / 45) % 8;
      direccionActual.textContent = "Orientado hacia: " + direccionesText[dirIndex];
    });

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
  </script>
</body>
</html>
