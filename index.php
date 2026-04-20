<?php
// ============================================================
//  index.php — Punto de entrada único (Router)
//  Patrón: MVC según guía del docente
// ============================================================

require_once __DIR__ . '/app/Config.php';
require_once __DIR__ . '/app/db/Conexion.php';
require_once __DIR__ . '/app/Controller/Auth.php';   // Siempre disponible

$page = isset($_GET['page']) ? trim($_GET['page']) : '';

// Ruta raíz: redirigir según estado de sesión
if (empty($page)) {
    if (Auth::estaAutenticado()) {
        $rol      = Auth::obtenerRol();
        $redirect = ($rol === 'docente') ? 'materia' : 'asistencia';
        header('Location: ' . BASE_URL . '/?page=' . $redirect);
    } else {
        header('Location: ' . BASE_URL . '/?page=auth');
    }
    exit;
}

// Enrutamiento principal
switch ($page) {

    case 'materia':
        require_once __DIR__ . '/app/Controller/cMateria.php';
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';
        $controller = new cMateria();

        // Ejecutar la acción (crear, editar, eliminar)
        if (method_exists($controller, $accion) && !in_array($accion, ['listar', 'render'])) {
            $controller->{$accion}();
        }

        // Siempre cargar la lista antes de renderizar
        $controller->listar();
        $controller->render();
        break;

    case 'grupo':
        require_once __DIR__ . '/app/Controller/cGrupo.php';
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';
        $controller = new cGrupo();

        // Ejecutar la acción (crear, editar, eliminar)
        if (method_exists($controller, $accion) && !in_array($accion, ['listar', 'render'])) {
            $controller->{$accion}();
        }

        // Siempre cargar la lista antes de renderizar
        $controller->listar();
        $controller->render();
        break;

    case 'registroEstudiante':
        require_once __DIR__ . '/app/Controller/cRegistroEstudiante.php';
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';
        $controller = new cRegistroEstudiante();

        // Ejecutar la acción
        if (method_exists($controller, $accion) && !in_array($accion, ['listar', 'render'])) {
            $controller->{$accion}();
        }

        // Cargar datos y renderizar
        $controller->listar();
        $controller->render();
        break;

    case 'clase':
        require_once __DIR__ . '/app/Controller/cClase.php';
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';
        $controller = new cClase();

        // Ejecutar la acción (crear, editar, eliminar)
        if (method_exists($controller, $accion) && !in_array($accion, ['listar', 'render'])) {
            $controller->{$accion}();
        }

        // Siempre cargar la lista antes de renderizar
        $controller->listar();
        $controller->render();
        break;

    case 'qr':
        require_once __DIR__ . '/app/Controller/cQR.php';
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';
        $controller = new cQR();

        // Ejecutar la acción (generar_qr, obtener_qr_json, listar)
        if (method_exists($controller, $accion) && !in_array($accion, ['listar', 'render'])) {
            $controller->{$accion}();
        }

        // Siempre cargar la lista antes de renderizar
        $controller->listar();
        $controller->render();
        break;

    case 'asistencia':
        require_once __DIR__ . '/app/Controller/cAsistencia.php';
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';
        $controller = new cAsistencia();

        // Ejecutar la acción (marcar_asistencia, listar)
        if (method_exists($controller, $accion) && !in_array($accion, ['listar', 'render'])) {
            $controller->{$accion}();
        }

        // Cargar datos y renderizar
        $controller->listar();
        $controller->render();
        break;

    case 'auth':
        require_once __DIR__ . '/app/Controller/cAuth.php';
        break;

    default:
        header('Location: ' . BASE_URL . '/?page=auth');
        exit;
}
