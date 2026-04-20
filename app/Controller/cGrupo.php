<?php
// ============================================================
//  cGrupo.php — Controlador de Grupo  (CU2)
//  Patrón MVC: clase que orquesta modelo → vista
// ============================================================

require_once __DIR__ . '/../Models/mGrupo.php';
require_once __DIR__ . '/../Models/mMateria.php';

class cGrupo {
    private $docente_id;
    private $modelo_grupo;
    private $modelo_materia;
    public $error  = '';
    public $exito  = '';
    public $accion = 'listar';
    public $materias = [];
    public $grupos = [];
    public $pageTitle  = 'Gestión de Grupos';
    public $activePage = 'grupo';

    public function __construct() {
        // Verificar autenticación: solo docentes
        if (!Auth::estaAutenticado() || Auth::obtenerRol() !== 'docente') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        // docente_id viene siempre de la sesión (nunca del formulario)
        $this->docente_id = (int)($_SESSION['usuario_id'] ?? 0);
        $this->modelo_grupo = new mGrupo();
        $this->modelo_materia = new mMateria();

        // Lista de materias disponibles (para el <select> en la vista)
        $this->materias = $this->modelo_materia->get_materias();
    }

    public function crear(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $nombre     = trim($_POST['nombre']     ?? '');
        $ciclo      = trim($_POST['ciclo']      ?? '');
        $materia_id = (int)($_POST['materia_id'] ?? 0);

        if (empty($nombre) || $materia_id <= 0) {
            $this->error = 'El nombre del grupo y la materia son requeridos.';
        } else {
            try {
                $this->modelo_grupo->insertar_grupo($nombre, $ciclo, $this->docente_id, $materia_id);
                $this->exito = 'Grupo "' . htmlspecialchars($nombre) . '" creado exitosamente.';
            } catch (Exception $e) {
                $this->error = 'Ya existe un grupo con ese nombre para esta materia.';
            }
        }
    }

    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id         = (int)($_POST['id']         ?? 0);
        $nombre     = trim($_POST['nombre']      ?? '');
        $ciclo      = trim($_POST['ciclo']       ?? '');
        $materia_id = (int)($_POST['materia_id'] ?? 0);

        if ($id <= 0 || empty($nombre) || $materia_id <= 0) {
            $this->error = 'Datos inválidos para actualizar.';
        } else {
            try {
                $this->modelo_grupo->actualizar_grupo($id, $nombre, $ciclo, $materia_id);
                $this->exito = 'Grupo actualizado exitosamente.';
            } catch (Exception $e) {
                $this->error = 'Ya existe un grupo con ese nombre para esta materia.';
            }
        }
    }

    public function eliminar(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->modelo_grupo->eliminar_grupo($id);
                $_SESSION['flash_success'] = 'Grupo eliminado exitosamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se puede eliminar: el grupo tiene clases o estudiantes registrados.';
            }
        }
        header('Location: ' . BASE_URL . '/?page=grupo');
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

        // Obtener lista actualizada de grupos del docente
        $this->grupos = $this->modelo_grupo->get_grupos_por_docente($this->docente_id);
    }

    public function render(): void {
        // Exportar propiedades como variables para la vista
        $error      = $this->error;
        $exito      = $this->exito;
        $accion     = $this->accion;
        $materias   = $this->materias;
        $grupos     = $this->grupos;
        $pageTitle  = $this->pageTitle;
        $activePage = $this->activePage;

        // Cargar vista
        require_once __DIR__ . '/../Views/vGrupo.phtml';
    }
}
