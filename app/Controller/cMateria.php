<?php
// ============================================================
//  cMateria.php — Controlador de Materia  (CU1)
//  Patrón MVC: clase que orquesta modelo → vista
// ============================================================

require_once __DIR__ . '/../Models/mMateria.php';

class cMateria {
    private $modelo;
    public $error  = '';
    public $exito  = '';
    public $materias = [];
    public $pageTitle  = 'Gestión de Materias';
    public $activePage = 'materia';

    public function __construct() {
        // Verificar autenticación: solo docentes
        if (!Auth::estaAutenticado() || Auth::obtenerRol() !== 'docente') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $this->modelo = new mMateria();
        $this->materias = [];
    }

    public function crear(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $this->error = 'El nombre de la materia es requerido.';
        } else {
            try {
                $this->modelo->insertar_materia($nombre);
                $this->exito = 'Materia "' . htmlspecialchars($nombre) . '" creada exitosamente.';
            } catch (Exception $e) {
                $this->error = 'Ya existe una materia con ese nombre.';
            }
        }
    }

    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id     = (int)($_POST['id']     ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($id <= 0 || empty($nombre)) {
            $this->error = 'Datos inválidos para actualizar.';
        } else {
            try {
                $this->modelo->actualizar_materia($id, $nombre);
                $this->exito = 'Materia actualizada exitosamente.';
            } catch (Exception $e) {
                $this->error = 'Ya existe una materia con ese nombre.';
            }
        }
    }

    public function eliminar(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->modelo->eliminar_materia($id);
                $_SESSION['flash_success'] = 'Materia eliminada exitosamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se puede eliminar: la materia tiene grupos asociados.';
            }
        }
        header('Location: ' . BASE_URL . '/?page=materia');
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

        // Obtener lista actualizada de materias
        $this->materias = $this->modelo->get_materias();
    }

    public function render(): void {
        // Exportar propiedades como variables para la vista
        $error  = $this->error;
        $exito  = $this->exito;
        $materias = $this->materias;
        $pageTitle = $this->pageTitle;
        $activePage = $this->activePage;

        // Cargar vista
        require_once __DIR__ . '/../Views/vMateria.phtml';
    }
}
