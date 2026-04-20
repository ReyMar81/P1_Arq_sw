<?php

class Conexion {
    private static $conexion;

    public static function conectar() {
        if (!isset(self::$conexion)) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$conexion = new PDO($dsn, DB_USER, DB_PASS);
                self::$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error de conexión a BD: " . $e->getMessage());
                throw new Exception("No se pudo conectar a la base de datos");
            }
        }
        return self::$conexion;
    }

    public static function desconectar() {
        self::$conexion = null;
    }
}
