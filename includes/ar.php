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
  </style>
</head>
<body>

<video id="camera" autoplay playsinline></video>
<img id="arrow" src="../assets/img/arrow.png" alt="Flecha">

<script>
  const video = document.getElementById('camera');
  const arrow = document.getElementById('arrow');

  const urlParams = new URLSearchParams(window.location.search);
  const destinoLat = parseFloat(urlParams.get('bLat'));
  const destinoLng = parseFloat(urlParams.get('bLng'));

  let heading = 0;
  let watchId = null;

  // Accede a la cámara del dispositivo
  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(stream => video.srcObject = stream)
    .catch(error => {
      console.error("Error al acceder a la cámara:", error);
      alert("No se pudo acceder a la cámara.");
    });

  // Convierte grados a radianes
  function toRad(grados) {
    return grados * Math.PI / 180;
  }

  // Calcula el ángulo desde la posición actual al destino
  function calcularAngulo(lat1, lng1, lat2, lng2) {
    const dLng = toRad(lng2 - lng1);
    const y = Math.sin(dLng) * Math.cos(toRad(lat2));
    const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) -
              Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLng);
    const brng = Math.atan2(y, x);
    return (brng * 180 / Math.PI + 360) % 360;
  }

  // Actualiza rotación de la flecha
  function actualizarDireccion(posicion) {
    const userLat = posicion.coords.latitude;
    const userLng = posicion.coords.longitude;

    const anguloDestino = calcularAngulo(userLat, userLng, destinoLat, destinoLng);
    const anguloFlecha = (anguloDestino - heading + 360) % 360;

    arrow.style.transform = `translate(-50%, -50%) rotate(${anguloFlecha}deg)`;

    console.log("Tu orientación:", heading);
    console.log("Ángulo hacia destino:", anguloDestino);
    console.log("Rotación final:", anguloFlecha);
  }

  // Escucha la orientación del dispositivo
  window.addEventListener('deviceorientationabsolute' in window ? 'deviceorientationabsolute' : 'deviceorientation', (event) => {
    if (typeof event.webkitCompassHeading !== "undefined") {
      heading = event.webkitCompassHeading; // iOS
    } else if (event.alpha !== null) {
      heading = 360 - event.alpha; // Android
    }
  });

  // Geolocalización en tiempo real
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
