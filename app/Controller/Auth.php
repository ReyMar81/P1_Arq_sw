<?php

class Auth {
    private $docente;
    private $estudiante;

    public function __construct() {
        $this->docente = new mDocente();
        $this->estudiante = new mEstudiante();
    }

    public function identificar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $rol = isset($_POST['rol']) ? trim($_POST['rol']) : '';
        $identificacion = isset($_POST['identificacion']) ? trim($_POST['identificacion']) : '';

        if (empty($rol) || empty($identificacion)) {
            $_SESSION['error'] = 'Debe proporcionar rol e identificación';
            return false;
        }

        if ($rol === 'docente') {
            return $this->identificarDocente($identificacion);
        } elseif ($rol === 'estudiante') {
            return $this->identificarEstudiante($identificacion);
        } else {
            $_SESSION['error'] = 'Rol inválido';
            return false;
        }
    }

    private function identificarDocente($codigo) {
        try {
            $docente = $this->docente->obtenerPorCodigo($codigo);

            if (!$docente) {
                $_SESSION['error'] = 'Docente no encontrado';
                return false;
            }

            $_SESSION['usuario_id'] = $docente['id'];
            $_SESSION['usuario_nombre'] = $docente['nombre'];
            $_SESSION['usuario_apellido_p'] = $docente['apellido_p'];
            $_SESSION['usuario_apellido_m'] = $docente['apellido_m'];
            $_SESSION['usuario_codigo'] = $docente['codigo_d'];
            $_SESSION['rol'] = 'docente';
            $_SESSION['login_time'] = time();

            return true;
        } catch (Exception $e) {
            error_log("Error al identificar docente: " . $e->getMessage());
            $_SESSION['error'] = 'Error al identificar';
            return false;
        }
    }

    private function identificarEstudiante($registro) {
        try {
            $estudiante = $this->estudiante->obtenerPorRegistro($registro);

            if (!$estudiante) {
                $_SESSION['error'] = 'Estudiante no encontrado';
                return false;
            }

            $_SESSION['usuario_id'] = $estudiante['id'];
            $_SESSION['usuario_nombre'] = $estudiante['nombre'];
            $_SESSION['usuario_apellido_p'] = $estudiante['apellido_p'];
            $_SESSION['usuario_apellido_m'] = $estudiante['apellido_m'];
            $_SESSION['usuario_registro'] = $estudiante['registro_e'];
            $_SESSION['rol'] = 'estudiante';
            $_SESSION['login_time'] = time();

            return true;
        } catch (Exception $e) {
            error_log("Error al identificar estudiante: " . $e->getMessage());
            $_SESSION['error'] = 'Error al identificar';
            return false;
        }
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public static function estaAutenticado() {
        return isset($_SESSION['usuario_id']) && isset($_SESSION['rol']);
    }

    public static function obtenerRol() {
        return $_SESSION['rol'] ?? null;
    }

    public static function obtenerUsuario() {
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nombre' => $_SESSION['usuario_nombre'] ?? '',
            'apellido_p' => $_SESSION['usuario_apellido_p'] ?? '',
            'apellido_m' => $_SESSION['usuario_apellido_m'] ?? '',
            'rol' => $_SESSION['rol'] ?? '',
        ];
    }
}
