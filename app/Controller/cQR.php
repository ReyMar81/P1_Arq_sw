<?php
// ============================================================
//  cQR.php — Controlador de QR  (CU5)
//  Patrón MVC: genera y gestiona códigos QR de clases
// ============================================================

require_once __DIR__ . '/../Models/mQR.php';

class cQR {
    private $docente_id;
    private $modelo_qr;
    public $error  = '';
    public $exito  = '';
    public $clases = [];
    public $pageTitle  = 'Generar Código QR';
    public $activePage = 'qr';

    public function __construct() {
        // Verificar autenticación: solo docentes
        if (!Auth::estaAutenticado() || Auth::obtenerRol() !== 'docente') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $this->docente_id = (int)($_SESSION['usuario_id'] ?? 0);
        $this->modelo_qr = new mQR();
    }

    /**
     * Genera un nuevo QR para una clase.
     * El anterior (si existe vigente) se marca como expirado.
     */
    public function generar_qr(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $clase_id = (int)($_POST['clase_id'] ?? 0);

        if ($clase_id <= 0) {
            $this->error = 'Clase no válida.';
            return;
        }

        try {
            $qr_token = $this->modelo_qr->generar_qr_token($clase_id);

            if ($qr_token) {
                $this->exito = 'Código QR generado exitosamente.';
            } else {
                $this->error = 'Error al generar el código QR.';
            }
        } catch (Exception $e) {
            $this->error = 'Error en el servidor: ' . $e->getMessage();
        }
    }

    /**
     * Obtiene el QR de una clase (AJAX).
     * Retorna JSON con qr_token, fecha_creacion y estado.
     */
    public function obtener_qr_json(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }

        $clase_id = (int)($_POST['clase_id'] ?? 0);

        if ($clase_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Clase no válida']);
            exit;
        }

        try {
            $qr = $this->modelo_qr->get_qr_por_clase($clase_id);

            if ($qr) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'qr_token' => $qr['qr_token'],
                    'qr_fecha' => $qr['qr_fecha_creacion'],
                    'qr_estado' => $qr['qr_estado']
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'No existe QR para esta clase']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Obtiene todas las clases del docente.
     */
    public function listar(): void {
        // Leer mensajes flash
        if (isset($_SESSION['flash_success'])) {
            $this->exito = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            $this->error = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }

        // Obtener todas las clases del docente
        $this->clases = $this->modelo_qr->get_clases_por_docente($this->docente_id);
    }

    /**
     * Renderiza la vista.
     */
    public function render(): void {
        // Exportar propiedades como variables para la vista
        $error = $this->error;
        $exito = $this->exito;
        $clases = $this->clases;
        $pageTitle = $this->pageTitle;
        $activePage = $this->activePage;

        // Cargar vista
        require_once __DIR__ . '/../Views/vQR.phtml';
    }
}
