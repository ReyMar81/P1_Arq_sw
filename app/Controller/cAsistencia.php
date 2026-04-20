<?php
// ============================================================
//  cAsistencia.php — Controlador de Asistencia  (CU6)
//  Patrón MVC: marca asistencia mediante código QR
// ============================================================

require_once __DIR__ . '/../Models/mAsistencia.php';
require_once __DIR__ . '/../Models/mQR.php';

class cAsistencia {
    private $modelo_asistencia;
    private $modelo_qr;
    public $error  = '';
    public $exito  = '';
    public $mensaje = '';
    public $clase_id = 0;
    public $qr_token = '';
    public $clase_datos = null;
    public $estudiante_datos = null;
    public $asistencia_registrada = false;
    public $pageTitle  = 'Marcar Asistencia';
    public $activePage = 'asistencia';

    public function __construct() {
        $this->modelo_asistencia = new mAsistencia();
        $this->modelo_qr = new mQR();

        // Obtener parámetros de URL (QR)
        $this->clase_id = (int)($_GET['clase_id'] ?? 0);
        $this->qr_token = trim($_GET['qr_token'] ?? '');
    }

    /**
     * Marca la asistencia de un estudiante.
     * Flujo:
     * 1. Recibe registro_e en POST
     * 2. Valida que QR sea vigente
     * 3. Obtiene datos del estudiante
     * 4. Verifica que está registrado en el grupo
     * 5. Verifica que no ha marcado ya
     * 6. Registra asistencia
     */
    public function marcar_asistencia(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $registro_e = trim($_POST['registro_e'] ?? '');

        if (empty($registro_e)) {
            $this->error = 'Por favor ingresa tu número de registro.';
            return;
        }

        // 1. Validar QR vigente
        if (empty($this->qr_token) || empty($this->clase_id)) {
            $this->error = 'Código QR inválido o expirado.';
            return;
        }

        $qr_valido = $this->modelo_qr->validar_qr_token($this->qr_token);
        if (!$qr_valido) {
            $this->error = 'Código QR inválido, expirado o ya no es válido.';
            return;
        }

        // 2. Obtener datos de la clase
        $this->clase_datos = $this->modelo_asistencia->obtener_clase_completa($this->clase_id);
        if (!$this->clase_datos) {
            $this->error = 'Clase no encontrada.';
            return;
        }

        // 3. Obtener datos del estudiante
        $this->estudiante_datos = $this->modelo_asistencia->obtener_estudiante_por_registro($registro_e);
        if (!$this->estudiante_datos) {
            $this->error = 'Número de registro no encontrado en el sistema.';
            return;
        }

        $estudiante_id = $this->estudiante_datos['id'];

        // 4. Verificar que el estudiante está registrado en el grupo
        if (!$this->modelo_asistencia->esta_registrado_en_grupo($estudiante_id, $this->clase_datos['grupo_id'])) {
            $this->error = 'No estás registrado en este grupo.';
            return;
        }

        // 5. Verificar que no ha marcado ya
        if ($this->modelo_asistencia->ya_marco_asistencia($estudiante_id, $this->clase_id)) {
            $this->error = 'Ya has marcado asistencia en esta clase.';
            return;
        }

        // 6. Registrar asistencia
        try {
            $this->modelo_asistencia->registrar_asistencia($estudiante_id, $this->clase_id);
            $this->asistencia_registrada = true;
            $this->exito = '✓ ¡Asistencia registrada correctamente!';
        } catch (Exception $e) {
            $this->error = 'Error al registrar la asistencia: ' . $e->getMessage();
        }
    }

    /**
     * Carga los datos para la vista.
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

        // Si QR válido, obtener datos de la clase
        if (!empty($this->qr_token) && $this->clase_id > 0) {
            $qr_valido = $this->modelo_qr->validar_qr_token($this->qr_token);
            if ($qr_valido) {
                $this->clase_datos = $this->modelo_asistencia->obtener_clase_completa($this->clase_id);
            }
        }
    }

    /**
     * Renderiza la vista.
     */
    public function render(): void {
        // Exportar propiedades como variables para la vista
        $error = $this->error;
        $exito = $this->exito;
        $clase_id = $this->clase_id;
        $qr_token = $this->qr_token;
        $clase_datos = $this->clase_datos;
        $estudiante_datos = $this->estudiante_datos;
        $asistencia_registrada = $this->asistencia_registrada;
        $pageTitle = $this->pageTitle;
        $activePage = $this->activePage;

        // Cargar vista
        require_once __DIR__ . '/../Views/vAsistencia.phtml';
    }
}
