<?php
// ============================================================
//  cClase.php — Controlador de Clase  (CU4)
//  Patrón MVC: clase que orquesta modelo → vista
// ============================================================

require_once __DIR__ . '/../Models/mClase.php';
require_once __DIR__ . '/../Models/mGrupo.php';

class cClase {
    private $docente_id;
    private $modelo_clase;
    private $modelo_grupo;
    public $error  = '';
    public $exito  = '';
    public $grupos = [];
    public $grupo_actual = null;
    public $grupo_id_actual = 0;
    public $clases = [];
    public $pageTitle  = 'Gestión de Clases';
    public $activePage = 'clase';

    public function __construct() {
        // Verificar autenticación: solo docentes
        if (!Auth::estaAutenticado() || Auth::obtenerRol() !== 'docente') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $this->docente_id = (int)($_SESSION['usuario_id'] ?? 0);
        $this->modelo_clase = new mClase();
        $this->modelo_grupo = new mGrupo();

        // Obtener grupos disponibles
        $this->grupos = $this->modelo_grupo->get_grupos_por_docente($this->docente_id);
    }

    public function seleccionar_grupo(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $grupo_id       = (int)($_POST['grupo_id'] ?? 0);
        $grupos_docente = $this->modelo_grupo->get_grupos_por_docente($this->docente_id);
        $ids_validos    = array_column($grupos_docente, 'id');

        if ($grupo_id > 0 && in_array($grupo_id, $ids_validos)) {
            $_SESSION['clase_grupo_id'] = $grupo_id;
        } else {
            $_SESSION['clase_grupo_id'] = 0;
        }
        header('Location: ' . BASE_URL . '/?page=clase');
        exit;
    }

    public function crear(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $fecha       = trim($_POST['fecha']       ?? '');
        $hora_inicio = trim($_POST['hora_inicio'] ?? '');
        $hora_fin    = trim($_POST['hora_fin']    ?? '');
        $grupo_id    = (int)($_POST['grupo_id']   ?? 0);

        // Validar que el grupo pertenece al docente
        $grupos_docente = $this->modelo_grupo->get_grupos_por_docente($this->docente_id);
        $ids_validos    = array_column($grupos_docente, 'id');

        if (empty($fecha) || empty($hora_inicio) || empty($hora_fin) || $grupo_id <= 0 || !in_array($grupo_id, $ids_validos)) {
            $this->error = 'Todos los campos son requeridos y válidos.';
        } elseif ($hora_inicio >= $hora_fin) {
            $this->error = 'La hora de inicio debe ser menor a la hora de fin.';
        } else {
            try {
                $this->modelo_clase->insertar_clase($fecha, $hora_inicio, $hora_fin, $grupo_id);
                $this->exito = 'Clase creada exitosamente.';
            } catch (Exception $e) {
                $this->error = 'Error al crear la clase.';
            }
        }
    }

    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id          = (int)($_POST['id']          ?? 0);
        $fecha       = trim($_POST['fecha']        ?? '');
        $hora_inicio = trim($_POST['hora_inicio']  ?? '');
        $hora_fin    = trim($_POST['hora_fin']     ?? '');

        if ($id <= 0 || empty($fecha) || empty($hora_inicio) || empty($hora_fin)) {
            $this->error = 'Datos inválidos para actualizar.';
        } elseif ($hora_inicio >= $hora_fin) {
            $this->error = 'La hora de inicio debe ser menor a la hora de fin.';
        } else {
            try {
                $this->modelo_clase->actualizar_clase($id, $fecha, $hora_inicio, $hora_fin);
                $this->exito = 'Clase actualizada exitosamente.';
            } catch (Exception $e) {
                $this->error = 'Error al actualizar la clase.';
            }
        }
    }

    public function eliminar(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->modelo_clase->eliminar_clase($id);
                $_SESSION['flash_success'] = 'Clase eliminada exitosamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se puede eliminar la clase.';
            }
        }
        header('Location: ' . BASE_URL . '/?page=clase');
        exit;
    }

    public function listar(): void {
        // Leer mensajes flash (post-redirect-get)
        if (empty($this->error) && empty($this->exito)) {
            if (isset($_SESSION['flash_success'])) {
                $this->exito = $_SESSION['flash_success'];
                unset($_SESSION['flash_success']);
            }
            if (isset($_SESSION['flash_error'])) {
                $this->error = $_SESSION['flash_error'];
                unset($_SESSION['flash_error']);
            }
        }

        // Obtener grupo actual de sesión
        $this->grupo_id_actual = (int)($_SESSION['clase_grupo_id'] ?? 0);

        if ($this->grupo_id_actual > 0) {
            $this->grupo_actual = $this->modelo_grupo->get_grupo_por_id($this->grupo_id_actual);
            if ($this->grupo_actual) {
                $this->clases = $this->modelo_clase->get_clases_por_grupo($this->grupo_id_actual);
            }
        }
    }

    public function render(): void {
        // Exportar propiedades como variables para la vista
        $error  = $this->error;
        $exito  = $this->exito;
        $grupos = $this->grupos;
        $grupo_id_actual = $this->grupo_id_actual;
        $grupo_actual = $this->grupo_actual;
        $clases = $this->clases;
        $pageTitle = $this->pageTitle;
        $activePage = $this->activePage;
        $accion = isset($_GET['accion']) ? trim($_GET['accion']) : 'listar';

        // Cargar vista
        require_once __DIR__ . '/../Views/vClase.phtml';
    }
}
