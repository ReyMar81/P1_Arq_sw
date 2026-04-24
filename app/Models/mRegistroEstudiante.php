<?php
// ============================================================
//  mRegistroEstudiante.php — Modelo de RegistroEstudiante  (CU3)
//  Tabla: registroEstudiante
//  Relaciones: estudiante (estudiante_id) y grupo (grupo_id)
// ============================================================

require_once __DIR__ . '/mEstudiante.php';

class mRegistroEstudiante
{

    private $con;

    public function __construct()
    {
        $this->con = Conexion::conectar();
    }

    /**
     * Retorna todos los estudiantes registrados en un grupo.
     * JOIN a estudiante y persona para mostrar datos completos.
     *
     * @param  int $grupo_id
     * @return array
     */
    public function get_estudiantes_por_grupo($grupo_id)
    {
        $sql = "SELECT  re.id,
                        re.fecha_asignacion,
                        re.estudiante_id,
                        p.nombre,
                        p.apellido_p,
                        p.apellido_m,
                        e.registro_e
                FROM    registroEstudiante re
                INNER   JOIN estudiante e ON e.persona_id = re.estudiante_id
                INNER   JOIN persona    p ON p.id          = e.persona_id
                WHERE   re.grupo_id = :grupo_id
                ORDER   BY p.apellido_p ASC, p.apellido_m ASC, p.nombre ASC";

        $stmt = $this->con->prepare($sql);
        $stmt->execute([':grupo_id' => (int)$grupo_id]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica si un estudiante ya está registrado en un grupo.
     *
     * @param  int $estudiante_id  (= persona.id = estudiante.persona_id)
     * @param  int $grupo_id
     * @return bool
     */
    public function ya_registrado($estudiante_id, $grupo_id)
    {
        $sql  = "SELECT COUNT(*)
                 FROM   registroEstudiante
                 WHERE  estudiante_id = :eid AND grupo_id = :gid";
        $stmt = $this->con->prepare($sql);
        $stmt->execute([':eid' => (int)$estudiante_id, ':gid' => (int)$grupo_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Reutilizada para operaciones de CU3.
     * - Guardar en BD: insertar_registro($estudiantes_id, $grupo_id)
     * - Cargar CSV:    insertar_registro(['op'=>'csv','contenido'=>string,'staging'=>array], 0)
     * - Agregar manual:insertar_registro(['op'=>'manual','staging'=>array,'nombre'=>...,'apellido_p'=>...,'apellido_m'=>...,'registro_e'=>...], 0)
     *
     * @param  mixed $estudiantes_id
     * @param  int   $grupo_id
     * @return array
     */
    public function insertar_registro($estudiantes_id, $grupo_id)
    {
        // Operacion: cargar CSV al staging
        if (is_array($estudiantes_id) && ($estudiantes_id['op'] ?? '') === 'csv') {
            $resultado = [
                'staging'   => is_array($estudiantes_id['staging'] ?? null) ? $estudiantes_id['staging'] : [],
                'agregados' => 0,
                'omitidos'  => 0,
                'error'     => null,
            ];

            $contenido = $estudiantes_id['contenido'] ?? '';
            if (!is_string($contenido) || trim($contenido) === '') {
                $resultado['error'] = 'El archivo CSV está vacío o no se pudo leer.';
                return $resultado;
            }

            if (substr($contenido, 0, 3) === "\xEF\xBB\xBF") {
                $contenido = substr($contenido, 3);
            }
            $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);

            $primera_linea = strtok($contenido, "\n");
            if ($primera_linea === false) {
                $resultado['error'] = 'No se detectaron filas en el CSV.';
                return $resultado;
            }

            $delimiter = (substr_count($primera_linea, ';') > substr_count($primera_linea, ',')) ? ';' : ',';
            $lineas = array_values(array_filter(explode("\n", $contenido)));

            for ($i = 1; $i < count($lineas); $i++) {
                if (trim($lineas[$i]) === '') {
                    continue;
                }

                $fila = str_getcsv($lineas[$i], $delimiter);
                if (count($fila) < 4) {
                    $resultado['omitidos']++;
                    continue;
                }

                $nombre_e   = trim($fila[0]);
                $apellido_p = trim($fila[1]);
                $apellido_m = trim($fila[2] ?? '');
                $registro_e = trim($fila[3]);

                if ($nombre_e === '' || $apellido_p === '' || $registro_e === '') {
                    $resultado['omitidos']++;
                    continue;
                }

                $dup = false;
                foreach ($resultado['staging'] as $item) {
                    if (($item['registro_e'] ?? '') === $registro_e) {
                        $dup = true;
                        break;
                    }
                }

                if ($dup) {
                    $resultado['omitidos']++;
                    continue;
                }

                $resultado['staging'][] = [
                    'nombre'     => $nombre_e,
                    'apellido_p' => $apellido_p,
                    'apellido_m' => $apellido_m,
                    'registro_e' => $registro_e,
                ];
                $resultado['agregados']++;
            }

            return $resultado;
        }

        // Operacion: agregar manual al staging
        if (is_array($estudiantes_id) && ($estudiantes_id['op'] ?? '') === 'manual') {
            $resultado = [
                'staging' => is_array($estudiantes_id['staging'] ?? null) ? $estudiantes_id['staging'] : [],
                'error'   => null,
            ];

            $nombre_e   = trim((string)($estudiantes_id['nombre'] ?? ''));
            $apellido_p = trim((string)($estudiantes_id['apellido_p'] ?? ''));
            $apellido_m = trim((string)($estudiantes_id['apellido_m'] ?? ''));
            $registro_e = trim((string)($estudiantes_id['registro_e'] ?? ''));

            if ($nombre_e === '' || $apellido_p === '' || $registro_e === '') {
                $resultado['error'] = 'Nombre, apellido paterno y registro son requeridos.';
                return $resultado;
            }

            foreach ($resultado['staging'] as $item) {
                if (($item['registro_e'] ?? '') === $registro_e) {
                    $resultado['error'] = 'El registro "' . $registro_e . '" ya está en la lista.';
                    return $resultado;
                }
            }

            $resultado['staging'][] = [
                'nombre'     => $nombre_e,
                'apellido_p' => $apellido_p,
                'apellido_m' => $apellido_m,
                'registro_e' => $registro_e,
            ];

            return $resultado;
        }

        // Operacion: guardar registros en BD
        $resultado = [
            'guardados' => 0,
            'omitidos'  => 0,
            'error'     => null,
        ];

        if (empty($estudiantes_id) || $grupo_id <= 0) {
            $resultado['error'] = 'Staging vacío o grupo_id inválido';
            return $resultado;
        }

        $modelo_est = new mEstudiante();
        $this->con->beginTransaction();

        try {
            foreach ($estudiantes_id as $item) {
                $existente = $modelo_est->obtenerPorRegistro($item['registro_e']);

                if ($existente) {
                    $estudiante_id = (int)$existente['id'];
                } else {
                    $estudiante_id = $modelo_est->crear_persona_estudiante(
                        $item['nombre'],
                        $item['apellido_p'],
                        $item['apellido_m'] ?? '',
                        $item['registro_e']
                    );
                }

                if ($this->ya_registrado($estudiante_id, $grupo_id)) {
                    $resultado['omitidos']++;
                    continue;
                }

                $sql  = "INSERT INTO registroEstudiante (estudiante_id, grupo_id)
                         VALUES (:eid, :gid)";
                $stmt = $this->con->prepare($sql);
                $stmt->execute([
                    ':eid' => (int)$estudiante_id,
                    ':gid' => (int)$grupo_id,
                ]);
                $resultado['guardados']++;
            }

            $this->con->commit();
        } catch (Exception $e) {
            $this->con->rollBack();
            $resultado['error'] = $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Elimina un registro de estudiante del grupo por ID.
     *
     * @param  int $id
     * @return bool
     */
    public function eliminar_registro($id)
    {
        $sql  = "DELETE FROM registroEstudiante WHERE id = :id";
        $stmt = $this->con->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}
