<?php
// Cargar variables de entorno desde archivo .env
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '\'"');
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Configuración de la aplicación
define('APP_NAME', getenv('APP_NAME') ?: 'Sistema de Asistencia con QR');
define('APP_DEBUG', getenv('APP_DEBUG') ?: 'false');

// BASE_URL dinámica: usa $_SERVER['HTTP_HOST'] si es accesible, sino .env
$base_url_from_env = getenv('BASE_URL');
if (!empty($base_url_from_env) && $base_url_from_env !== 'http://localhost:8000') {
    define('BASE_URL', $base_url_from_env);
} else {
    // Fallback dinámico para desarrollo local y Docker
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    define('BASE_URL', $protocol . '://' . $host);
}

define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'UTC');

// Configuración de BD
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'asistencia_qr');
define('DB_USER', getenv('DB_USER') ?: 'user');
define('DB_PASS', getenv('DB_PASS') ?: 'password');

// Configuración de sesión
define('SESSION_TIMEOUT', (int)(getenv('SESSION_TIMEOUT') ?: '1800'));

// Configuración de QR
define('QR_EXPIRATION_MINUTES', (int)(getenv('QR_EXPIRATION_MINUTES') ?: '5'));
define('QR_SIZE', (int)(getenv('QR_SIZE') ?: '300'));

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);
