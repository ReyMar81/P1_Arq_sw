<?php
// ============================================================
//  cRegistroEstudiante.php — Controlador CU3  (TRANSACCIONAL)
//  Registra estudiantes en un grupo:
//    · persona + estudiante (si no existen)
//    · registroEstudiante   (cabecera: grupo_id)
//  Patrón MVC: clase que orquesta transacciones
// ============================================================

require_once __DIR__ . '/../Models/mGrupo.php';
require_once __DIR__ . '/../Models/mEstudiante.php';
require_once __DIR__ . '/../Models/mRegistroEstudiante.php';

class cRegistroEstudiante {
    private $docente_id;
    private $modelo_grupo;
    private $modelo_est;
    private $modelo_re;
    public $error  = '';
    public $exito  = '';
    public $grupos = [];
    public $grupo_actual = null;
    public $grupo_id_actual = 0;
    public $staging = [];
    public $ya_registrados = [];
    public $pageTitle  = 'Registrar Estudiantes';
    public $activePage = 'registroEstudiante';

    public function __construct() {
        // Solo docentes
        if (!Auth::estaAutenticado() || Auth::obtenerRol() !== 'docente') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $this->docente_id = (int)($_SESSION['usuario_id'] ?? 0);
        $this->modelo_grupo = new mGrupo();
        $this->modelo_est = new mEstudiante();
        $this->modelo_re = new mRegistroEstudiante();

        // Inicializar staging en sesión
        if (!isset($_SESSION['reg_staging'])) {
            $_SESSION['reg_staging'] = [];
        }

        // Leer estado actual
        $this->grupo_id_actual = (int)($_SESSION['reg_grupo_id'] ?? 0);
        $this->staging = $_SESSION['reg_staging'] ?? [];
    }

    private function redirect($mensaje = null, $tipo = 'success'): void {
        if ($mensaje !== null) {
            $_SESSION[$tipo === 'error' ? 'flash_error' : 'flash_success'] = $mensaje;
        }
        header('Location: ' . BASE_URL . '/?page=registroEstudiante');
        exit;
    }

    public function descargar_plantilla(): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_estudiantes.csv"');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF"; // BOM para que Excel lo abra correctamente
        echo "Nombre,Apellido Paterno,Apellido Materno,Registro\n";
        echo "Juan,Perez,Garcia,210000001\n";
        echo "Maria,Lopez,Torres,221000002\n";
        exit;
    }

    public function seleccionar_grupo(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $grupo_id       = (int)($_POST['grupo_id'] ?? 0);
        $grupos_docente = $this->modelo_grupo->get_grupos_por_docente($this->docente_id);
        $ids_validos    = array_column($grupos_docente, 'id');

        if ($grupo_id > 0 && in_array($grupo_id, $ids_validos)) {
            $_SESSION['reg_grupo_id'] = $grupo_id;
            $_SESSION['reg_staging']  = [];
            $this->redirect('Grupo seleccionado correctamente.');
        }
        $this->redirect('Grupo no válido.', 'error');
    }

    public function cargar_csv(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('No se pudo subir el archivo. Verifica que hayas seleccionado un CSV.', 'error');
        }

        if (!str_ends_with(strtolower($_FILES['archivo']['name']), '.csv')) {
            $this->redirect('Solo se aceptan archivos CSV (.csv). Guarda tu Excel como CSV primero.', 'error');
        }

        $contenido = file_get_contents($_FILES['archivo']['tmp_name']);

        // Quitar BOM UTF-8 si viene de Excel
        if (substr($contenido, 0, 3) === "\xEF\xBB\xBF") {
            $contenido = substr($contenido, 3);
        }

        // Normalizar saltos de línea
        $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);

        // Detectar delimitador desde la primera línea
        $primera_linea = strtok($contenido, "\n");
        $delimiter     = (substr_count($primera_linea, ';') > substr_count($primera_linea, ',')) ? ';' : ',';

        $lineas    = array_values(array_filter(explode("\n", $contenido)));
        $agregados = 0;
        $omitidos  = 0;

        // Saltar cabecera (primera fila)
        for ($i = 1; $i < count($lineas); $i++) {
            if (trim($lineas[$i]) === '') continue;

            $fila = str_getcsv($lineas[$i], $delimiter);
            if (count($fila) < 4) { $omitidos++; continue; }

            $nombre_e   = trim($fila[0]);
            $apellido_p = trim($fila[1]);
            $apellido_m = trim($fila[2] ?? '');
            $registro_e = trim($fila[3]);

            if (empty($nombre_e) || empty($apellido_p) || empty($registro_e)) {
                $omitidos++;
                continue;
            }

            // Evitar duplicados en staging
            $dup = false;
            foreach ($_SESSION['reg_staging'] as $item) {
                if ($item['registro_e'] === $registro_e) { $dup = true; break; }
            }
            if ($dup) { $omitidos++; continue; }

            $_SESSION['reg_staging'][] = [
                'nombre'     => $nombre_e,
                'apellido_p' => $apellido_p,
                'apellido_m' => $apellido_m,
                'registro_e' => $registro_e,
            ];
            $agregados++;
        }

        $msg = "$agregados estudiante(s) cargado(s) desde el CSV.";
        if ($omitidos > 0) {
            $msg .= " ($omitidos fila(s) omitida(s) por datos inválidos o duplicados).";
        }
        $this->redirect($msg);
    }

    public function agregar_manual(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $nombre_e   = trim($_POST['nombre']     ?? '');
        $apellido_p = trim($_POST['apellido_p'] ?? '');
        $apellido_m = trim($_POST['apellido_m'] ?? '');
        $registro_e = trim($_POST['registro_e'] ?? '');

        if (empty($nombre_e) || empty($apellido_p) || empty($registro_e)) {
            $this->redirect('Nombre, apellido paterno y registro son requeridos.', 'error');
        }

        // Verificar duplicado en staging
        foreach ($_SESSION['reg_staging'] as $item) {
            if ($item['registro_e'] === $registro_e) {
                $this->redirect('El registro "' . htmlspecialchars($registro_e) . '" ya está en la lista.', 'error');
            }
        }

        $_SESSION['reg_staging'][] = [
            'nombre'     => $nombre_e,
            'apellido_p' => $apellido_p,
            'apellido_m' => $apellido_m,
            'registro_e' => $registro_e,
        ];
        $this->redirect('Estudiante agregado a la lista.');
    }

    public function quitar_staging(): void {
        $idx = (int)($_GET['idx'] ?? -1);
        if (isset($_SESSION['reg_staging'][$idx])) {
            array_splice($_SESSION['reg_staging'], $idx, 1);
        }
        header('Location: ' . BASE_URL . '/?page=registroEstudiante');
        exit;
    }

    public function limpiar_staging(): void {
        $_SESSION['reg_staging'] = [];
        header('Location: ' . BASE_URL . '/?page=registroEstudiante');
        exit;
    }

    public function limpiar(): void {
        $_SESSION['reg_staging'] = [];
        unset($_SESSION['reg_grupo_id']);
        header('Location: ' . BASE_URL . '/?page=registroEstudiante');
        exit;
    }

    public function guardar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $staging  = $_SESSION['reg_staging']  ?? [];
        $grupo_id = (int)($_SESSION['reg_grupo_id'] ?? 0);

        if (empty($staging) || $grupo_id <= 0) {
            $this->redirect('Selecciona un grupo y agrega al menos un estudiante a la lista.', 'error');
        }

        $con = Conexion::conectar();
        $con->beginTransaction();

        try {
            $guardados = 0;
            $omitidos  = 0;

            foreach ($staging as $item) {
                // 1. Buscar si ya existe en el sistema
                $existente = $this->modelo_est->obtenerPorRegistro($item['registro_e']);

                if ($existente) {
                    $estudiante_id = (int)$existente['id'];
                } else {
                    // 2. Crear persona + estudiante dentro de la transacción
                    $estudiante_id = $this->modelo_est->crear_persona_estudiante(
                        $item['nombre'],
                        $item['apellido_p'],
                        $item['apellido_m'],
                        $item['registro_e']
                    );
                }

                // 3. Comprobar si ya está registrado en el grupo
                if ($this->modelo_re->ya_registrado($estudiante_id, $grupo_id)) {
                    $omitidos++;
                    continue;
                }

                // 4. Insertar registroEstudiante
                $this->modelo_re->insertar_registro($estudiante_id, $grupo_id);
                $guardados++;
            }

            $con->commit();

            // Limpiar lista (conservar grupo seleccionado)
            $_SESSION['reg_staging'] = [];

            $msg = "$guardados estudiante(s) registrado(s) exitosamente en el grupo.";
            if ($omitidos > 0) {
                $msg .= " ($omitidos omitido(s) porque ya estaban registrados).";
            }
            $this->redirect($msg);

        } catch (Exception $e) {
            $con->rollBack();
            $this->redirect('Error en la transacción. Ningún dato fue guardado. Detalle: ' . $e->getMessage(), 'error');
        }
    }

    public function eliminar_registro(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->modelo_re->eliminar_registro($id);
                $this->redirect('Estudiante removido del grupo.');
            } catch (Exception $e) {
                $this->redirect('No se pudo eliminar el registro.', 'error');
            }
        }
        header('Location: ' . BASE_URL . '/?page=registroEstudiante');
        exit;
    }

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

        // Datos para la vista
        $this->grupos = $this->modelo_grupo->get_grupos_por_docente($this->docente_id);
        $this->grupo_id_actual = (int)($_SESSION['reg_grupo_id'] ?? 0);
        $this->grupo_actual = ($this->grupo_id_actual > 0) ? $this->modelo_grupo->get_grupo_por_id($this->grupo_id_actual) : null;
        $this->staging = $_SESSION['reg_staging'] ?? [];
        $this->ya_registrados = ($this->grupo_id_actual > 0)
                                  ? $this->modelo_re->get_estudiantes_por_grupo($this->grupo_id_actual)
                                  : [];
    }

    public function render(): void {
        // Exportar propiedades como variables para la vista
        $error = $this->error;
        $exito = $this->exito;
        $grupos = $this->grupos;
        $grupo_id_actual = $this->grupo_id_actual;
        $grupo_actual = $this->grupo_actual;
        $staging = $this->staging;
        $ya_registrados = $this->ya_registrados;
        $pageTitle = $this->pageTitle;
        $activePage = $this->activePage;

        // Cargar vista
        require_once __DIR__ . '/../Views/vRegistroEstudiante.phtml';
    }
}
