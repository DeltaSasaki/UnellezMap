<?php
include 'coords.php';

// Función para calcular distancia entre coordenadas (Haversine)
function distancia($lat1, $lon1, $lat2, $lon2) {
    $radio = 6371000; // metros
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $radio * $c;
}

// Captura coordenadas de A y B desde la URL
$puntoA = ['lat' => $_GET['aLat'] ?? null, 'lng' => $_GET['aLng'] ?? null];
$puntoB = ['lat' => $_GET['bLat'] ?? null, 'lng' => $_GET['bLng'] ?? null];

$marcadoresAR = [];

if ($puntoA['lat'] && $puntoB['lat']) {
    foreach ($marcadores as $m) {
        $dA = distancia($m['latitud'], $m['longitud'], $puntoA['lat'], $puntoA['lng']);
        $dB = distancia($m['latitud'], $m['longitud'], $puntoB['lat'], $puntoB['lng']);

        if ($dA < 150 || $dB < 150) {
            $marcadoresAR[] = $m;
        }
    }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Ruta en Realidad Aumentada</title>
    <script src="https://aframe.io/releases/1.2.0/aframe.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/jeromeetienne/AR.js/aframe/build/aframe-ar.min.js"></script>
    <style>
      body, html { margin: 0; padding: 0; overflow: hidden; }
      #info {
        position: fixed;
        bottom: 10px;
        left: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 6px 12px;
        font-size: 14px;
        border-radius: 6px;
        font-family: sans-serif;
        z-index: 10;
      }
    </style>
  </head>
  <body>
    <div id="info">Apunta tu cámara y camina hacia los puntos en la ruta</div>

    <a-scene
      embedded
      arjs="sourceType: webcam; gpsMinAccuracy: 100; gpsTimeInterval: 1000;"
      vr-mode-ui="enabled: false"
      renderer="logarithmicDepthBuffer: true;"
    >
      <a-camera gps-camera rotation-reader></a-camera>

      <?php foreach ($marcadoresAR as $m): ?>
        <a-entity 
          gps-entity-place="latitude: <?= $m['latitud'] ?>; longitude: <?= $m['longitud'] ?>;"
          look-at="[gps-camera]"
        >
          <a-text 
            value="<?= htmlspecialchars($m['nombre']) ?>"
            scale="20 20 20"
            color="yellow"
          ></a-text>
        </a-entity>
      <?php endforeach; ?>
    </a-scene>
  </body>
</html>
