<?php
// Mostrar errores (solo para depuración, puedes comentar luego)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Validar parámetros obligatorios
if (!isset($_GET['start']) || !isset($_GET['end']) || !isset($_GET['perfil'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

// Obtener los parámetros
$start = $_GET['start'];
$end = $_GET['end'];
$perfil = $_GET['perfil'];

// ✅ Tu API key de OpenRouteService
$apiKey = '5b3ce3597851110001cf62481455a2f503ad7991960035746997531ace349481ef88e136b3034428'; // Sustitúyela si la renuevas

// Construir la URL para la API
$url = "https://api.openrouteservice.org/v2/directions/$perfil?api_key=$apiKey&start=$start&end=$end";

// Inicializar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Verificar errores
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con OpenRouteService', 'detalle' => $error]);
    exit;
}

// Enviar respuesta
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;
