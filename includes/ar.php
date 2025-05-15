<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guía en Realidad Aumentada</title>
  <style>
    body {
      margin: 0;
      overflow: hidden;
    }
    video {
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
      font-family: sans-serif;
      display: none;
    }
  </style>
</head>
<body>

<video id="camera" autoplay playsinline></video>
<img id="arrow" src="../assets/img/arrow.png" alt="Flecha">
<div id="contador">Paso 0 de 0</div>

<script>
  const video = document.getElementById('camera');
  const arrow = document.getElementById('arrow');
  const contador = document.getElementById('contador');

  let heading = 0;
  let watchId = null;

  // Carga la ruta desde localStorage
  let ruta = JSON.parse(localStorage.getItem("ruta_AR") || "[]");
  let indicePuntoActual = 0;

  if (ruta.length === 0) {
    alert("No hay ruta cargada en memoria.");
  }

  // Accede a la cámara
  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(stream => video.srcObject = stream)
    .catch(error => {
      console.error("Error al acceder a la cámara:", error);
      alert("No se pudo acceder a la cámara.");
    });

  // Conversión a radianes
  function toRad(grados) {
    return grados * Math.PI / 180;
  }

  // Cálculo de ángulo entre dos puntos
  function calcularAngulo(lat1, lng1, lat2, lng2) {
    const dLng = toRad(lng2 - lng1);
    const y = Math.sin(dLng) * Math.cos(toRad(lat2));
    const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) -
              Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLng);
    const brng = Math.atan2(y, x);
    return (brng * 180 / Math.PI + 360) % 360;
  }

  // Cálculo de distancia en metros entre dos puntos
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

  function actualizarDireccion(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    if (indicePuntoActual >= ruta.length) {
      contador.textContent = "¡Has llegado a tu destino!";
      return;
    }

    const objetivo = ruta[indicePuntoActual];
    const distancia = distanciaMetros(lat, lng, objetivo[0], objetivo[1]);

    if (distancia < 10 && indicePuntoActual < ruta.length - 1) {
      indicePuntoActual++;
    }

    const siguiente = ruta[indicePuntoActual];
    const angulo = calcularAngulo(lat, lng, siguiente[0], siguiente[1]);
    const anguloFlecha = (angulo - heading + 360) % 360;

    arrow.style.transform = `translate(-50%, -50%) rotate(${anguloFlecha}deg)`;

    // Mostrar contador actualizado
    contador.textContent = `Paso ${indicePuntoActual + 1} de ${ruta.length}`;
    contador.style.display = "block";
  }

  // Detecta orientación del dispositivo
  window.addEventListener('deviceorientationabsolute' in window ? 'deviceorientationabsolute' : 'deviceorientation', (event) => {
    if (typeof event.webkitCompassHeading !== "undefined") {
      heading = event.webkitCompassHeading;
    } else if (event.alpha !== null) {
      heading = 360 - event.alpha;
    }
  });

  // Habilita geolocalización
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

  // Limpieza al salir
  window.addEventListener('beforeunload', () => {
    if (watchId) navigator.geolocation.clearWatch(watchId);
  });
</script>

</body>
</html>
