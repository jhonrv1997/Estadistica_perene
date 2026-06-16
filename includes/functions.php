<?php
/**
 * Sistema de Gestion de Datos HIS
 * Funciones comunes
 */

/**
 * Sanitizar string para salida HTML
 */
function clean($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generar token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatear fecha para mostrar
 */
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

/**
 * Formatear fecha y hora para mostrar
 */
function formatDateTime($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y H:i:s', $ts) : $datetime;
}

/**
 * Obtener nombre del mes
 */
function getNombreMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[intval($mes)] ?? $mes;
}

/**
 * Registrar en log de importacion
 */
function logImportacion($datos) {
    try {
        $pdo = getDBConnection();
        $sql = "INSERT INTO LOG_IMPORTACION 
                (tipo_operacion, tipo_archivo, nombre_archivo, tabla_destino, 
                 periodo_mes, periodo_anio, registros_procesados, modo_importacion, 
                 usuario, estado, mensaje, duracion_segundos)
                VALUES 
                (:tipo_operacion, :tipo_archivo, :nombre_archivo, :tabla_destino,
                 :periodo_mes, :periodo_anio, :registros_procesados, :modo_importacion,
                 :usuario, :estado, :mensaje, :duracion_segundos)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':tipo_operacion' => $datos['tipo_operacion'],
            ':tipo_archivo' => $datos['tipo_archivo'] ?? null,
            ':nombre_archivo' => $datos['nombre_archivo'] ?? null,
            ':tabla_destino' => $datos['tabla_destino'] ?? null,
            ':periodo_mes' => $datos['periodo_mes'] ?? null,
            ':periodo_anio' => $datos['periodo_anio'] ?? null,
            ':registros_procesados' => $datos['registros_procesados'] ?? 0,
            ':modo_importacion' => $datos['modo_importacion'] ?? null,
            ':usuario' => $datos['usuario'] ?? $_SESSION['usuario'] ?? 'sistema',
            ':estado' => $datos['estado'],
            ':mensaje' => $datos['mensaje'] ?? null,
            ':duracion_segundos' => $datos['duracion_segundos'] ?? null,
        ]);
    } catch (Exception $e) {
        error_log("Error logImportacion: " . $e->getMessage());
        return false;
    }
}

/**
 * Detectar tipo de archivo por nombre
 * Retorna: ['tipo' => 'tipo', 'tabla' => 'tabla', 'modo' => 'modo']
 */
function detectFileType($filename) {
    $filename = strtoupper($filename);
    
    if (strpos($filename, 'MAESTROREGISTRADOR') !== false) {
        return [
            'tipo' => 'MaestroRegistrador',
            'tabla' => 'MAESTRO_REGISTRADOR',
            'modo' => 'REEMPLAZO'
        ];
    }
    if (strpos($filename, 'MAESTROPERSONAL') !== false) {
        return [
            'tipo' => 'MaestroPersonal',
            'tabla' => 'MAESTRO_PERSONAL',
            'modo' => 'REEMPLAZO'
        ];
    }
    if (strpos($filename, 'MAESTROPACIENTE') !== false) {
        return [
            'tipo' => 'MaestroPaciente',
            'tabla' => 'MAESTRO_PACIENTE',
            'modo' => 'REEMPLAZO'
        ];
    }
    if (strpos($filename, 'NOMINALTRAMA') !== false) {
        return [
            'tipo' => 'NominalTrama',
            'tabla' => 'NOMINAL_TRAMA_NUEVO',
            'modo' => 'PERIODO'
        ];
    }
    return null;
}

/**
 * Obtener mapeo de columnas CSV a tabla para cada tipo
 */
function getColumnMapping($tipo) {
    $mappings = [
        'MaestroRegistrador' => [
            'Id_Registrador', 'Id_Tipo_Documento_Registrador', 'Numero_Documento_Registrador',
            'Apellido_Paterno_Registrador', 'Apellido_Materno_Registrador', 'Nombres_Registrador',
            'Fecha_Nacimiento_Registrador'
        ],
        'MaestroPersonal' => [
            'Id_Personal', 'Id_Tipo_Documento_Personal', 'Numero_Documento_Personal',
            'Apellido_Paterno_Personal', 'Apellido_Materno_Personal', 'Nombres_Personal',
            'Fecha_Nacimiento_Personal', 'Id_Condicion', 'Id_Profesion', 'Id_Colegio',
            'Numero_Colegiatura', 'Id_Establecimiento', 'Fecha_Alta', 'Fecha_Baja'
        ],
        'MaestroPaciente' => [
            'Id_Paciente', 'Id_Tipo_Documento_Paciente', 'Numero_Documento_Paciente',
            'Apellido_Paterno_Paciente', 'Apellido_Materno_Paciente', 'Nombres_Paciente',
            'Fecha_Nacimiento_Paciente', 'Id_Genero', 'Id_Etnia', 'Historia_Clinica',
            'Ficha_Familiar', 'Ubigeo_Nacimiento', 'Ubigeo_Reniec', 'Domicilio_Reniec',
            'Ubigeo_Declarado', 'Domicilio_Declarado', 'Referencia_Domicilio', 'Id_Pais',
            'Id_Establecimiento', 'Fecha_Alta', 'Fecha_Modificacion'
        ],
        'NominalTrama' => [
            'Id_Cita', 'Anio', 'Mes', 'Dia', 'Fecha_Atencion', 'Lote', 'Num_Pag', 'Num_Reg',
            'Id_Ups', 'Id_Establecimiento', 'Id_Paciente', 'Id_Personal', 'Id_Registrador',
            'Id_Financiador', 'Id_Condicion_Establecimiento', 'Id_Condicion_Servicio',
            'Edad_Reg', 'Tipo_Edad', 'Anio_Actual_Paciente', 'Mes_Actual_Paciente',
            'Dia_Actual_Paciente', 'Id_Turno', 'Codigo_Item', 'Tipo_Diagnostico', 'Valor_Lab',
            'Id_Correlativo_Item', 'Id_Correlativo_Lab', 'Peso', 'Talla', 'Hemoglobina',
            'Perimetro_Abdominal', 'Perimetro_Cefalico', 'Id_Otra_Condicion',
            'Id_Centro_Poblado', 'Fecha_Ultima_Regla', 'Fecha_Solicitud_Hb',
            'Fecha_Resultado_Hb', 'Fecha_Registro', 'Fecha_Modificacion', 'Id_Pais',
            'gruporiesgo_desc', 'condicion_gestante', 'Peso_Pregestacional', 'Id_Dosis',
            'renipress', 'Id_Institucion_Edu', 'Id_AplicacionOrigen', 'Alerta'
        ]
    ];
    return $mappings[$tipo] ?? [];
}

/**
 * Obtener tipos de fecha para conversion
 */
function getDateColumns($tipo) {
    $dateCols = [
        'MaestroRegistrador' => ['Fecha_Nacimiento_Registrador'],
        'MaestroPersonal' => ['Fecha_Nacimiento_Personal', 'Fecha_Alta', 'Fecha_Baja'],
        'MaestroPaciente' => ['Fecha_Nacimiento_Paciente', 'Fecha_Alta', 'Fecha_Modificacion'],
        'NominalTrama' => ['Fecha_Atencion', 'Fecha_Ultima_Regla', 'Fecha_Solicitud_Hb',
                          'Fecha_Resultado_Hb', 'Fecha_Registro', 'Fecha_Modificacion']
    ];
    return $dateCols[$tipo] ?? [];
}

/**
 * Convertir valor para SQL (manejar nulos y escapes)
 */
function sqlValue($val, $isDate = false) {
    if ($val === null || $val === '' || $val === 'NULL') {
        return 'NULL';
    }
    $val = trim($val);
    if ($val === '') return 'NULL';
    
    if ($isDate) {
        // Intentar convertir formato de fecha
        $val = str_replace('/', '-', $val);
        $ts = strtotime($val);
        if ($ts === false) return 'NULL';
        return "'" . date('Y-m-d', $ts) . "'";
    }
    
    // Escapar comillas simples
    $val = addslashes($val);
    return "'" . $val . "'";
}

/**
 * Marcar tipo de archivo como importado en IMPORT_ESTADO
 */
function marcarImportado($tipoArchivo, $nombreArchivo, $registros, $periodoMes = null, $periodoAnio = null) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO IMPORT_ESTADO (tipo_archivo, importado, fecha_importacion, nombre_archivo, periodo_mes, periodo_anio, registros_importados)
            VALUES (:tipo, 1, NOW(), :archivo, :mes, :anio, :registros)
            ON DUPLICATE KEY UPDATE 
                importado = 1, 
                fecha_importacion = NOW(), 
                nombre_archivo = VALUES(nombre_archivo), 
                periodo_mes = VALUES(periodo_mes), 
                periodo_anio = VALUES(periodo_anio), 
                registros_importados = VALUES(registros_importados)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':tipo' => $tipoArchivo,
        ':archivo' => $nombreArchivo,
        ':mes' => $periodoMes,
        ':anio' => $periodoAnio,
        ':registros' => $registros,
    ]);
}

/**
 * Obtener estado de importacion de los 4 archivos
 * Retorna array con clave tipo_archivo y valor array de datos
 */
function obtenerEstadoImportacion() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM IMPORT_ESTADO ORDER BY FIELD(tipo_archivo, 'MaestroRegistrador', 'MaestroPersonal', 'MaestroPaciente', 'NominalTrama')");
    $rows = $stmt->fetchAll();
    $estado = [];
    foreach ($rows as $row) {
        $estado[$row['tipo_archivo']] = $row;
    }
    return $estado;
}

/**
 * Verificar si los 4 archivos han sido importados
 * Retorna true solo si los 4 estan marcados como importado=1
 */
function verificarTodosImportados() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM IMPORT_ESTADO WHERE importado = 1");
    $importados = (int)$stmt->fetchColumn();
    return $importados >= 4;
}

/**
 * Obtener el ultimo periodo importado de NominalTrama (para procesamiento automatico)
 */
function obtenerUltimoPeriodoTrama() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT periodo_anio, periodo_mes FROM IMPORT_ESTADO WHERE tipo_archivo = 'NominalTrama' AND importado = 1");
    $row = $stmt->fetch();
    if ($row && $row['periodo_anio'] && $row['periodo_mes']) {
        return ['anio' => $row['periodo_anio'], 'mes' => $row['periodo_mes']];
    }
    return null;
}

/**
 * Reiniciar el estado de importacion (marcar todos como pendiente)
 */
function reiniciarEstadoImportacion() {
    $pdo = getDBConnection();
    $pdo->exec("UPDATE IMPORT_ESTADO SET importado = 0, fecha_importacion = NULL, nombre_archivo = NULL, periodo_mes = NULL, periodo_anio = NULL, registros_importados = 0");
}

/**
 * Obtener lista de archivos que faltan por importar
 */
function obtenerArchivosFaltantes() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT tipo_archivo FROM IMPORT_ESTADO WHERE importado = 0 ORDER BY FIELD(tipo_archivo, 'MaestroRegistrador', 'MaestroPersonal', 'MaestroPaciente', 'NominalTrama')");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Crear indices en columnas JOIN si no existen
 * Esto es CRITICO para que la consolidacion funcione sin timeout.
 * Sin indices, cada LEFT JOIN hace un full table scan = billones de comparaciones.
 */
function asegurarIndicesConsolidacion() {
    $pdo = getDBConnection();
    $indicesCreados = [];
    
    // Definir indices necesarios: [tabla => [nombre_indice => columna]]
    // Estos son los indices que la consulta de consolidacion necesita
    $indicesRequeridos = [
        // Tablas de datos importados (las mas criticas - sin indices causan full scan)
        'NOMINAL_TRAMA_NUEVO' => [
            'idx_ntn_id_personal' => 'Id_Personal',
            'idx_ntn_id_paciente' => 'Id_Paciente',
            'idx_ntn_id_registrador' => 'Id_Registrador',
            'idx_ntn_id_establecimiento' => 'Id_Establecimiento',
            'idx_ntn_id_ups' => 'Id_Ups',
            'idx_ntn_id_financiador' => 'Id_Financiador',
            'idx_ntn_id_otra_condicion' => 'Id_Otra_Condicion',
            'idx_ntn_codigo_item' => 'Codigo_Item',
            'idx_ntn_id_centro_poblado' => 'Id_Centro_Poblado',
            'idx_ntn_anio_mes' => 'Anio, Mes',
        ],
        'MAESTRO_PERSONAL' => [
            'idx_mp_id_personal' => 'Id_Personal',
            'idx_mp_id_tipo_doc' => 'Id_Tipo_Documento_Personal',
            'idx_mp_id_condicion' => 'Id_Condicion',
            'idx_mp_id_profesion' => 'Id_Profesion',
            'idx_mp_id_colegio' => 'Id_Colegio',
        ],
        'MAESTRO_PACIENTE' => [
            'idx_mpa_id_paciente' => 'Id_Paciente',
            'idx_mpa_id_tipo_doc' => 'Id_Tipo_Documento_Paciente',
            'idx_mpa_id_etnia' => 'Id_Etnia',
            'idx_mpa_id_pais' => 'Id_Pais',
        ],
        'MAESTRO_REGISTRADOR' => [
            'idx_mr_id_registrador' => 'Id_Registrador',
            'idx_mr_id_tipo_doc' => 'Id_Tipo_Documento_Registrador',
        ],
        'MAESTRO_HIS_ESTABLECIMIENTO' => [
            'idx_mhes_id_establecimiento' => 'Id_Establecimiento',
        ],
        'MAESTRO_HIS_TIPO_DOC' => [
            'idx_mhtd_id_tipo_documento' => 'Id_Tipo_Documento',
        ],
        'MAESTRO_HIS_UPS' => [
            'idx_mhu_id_ups' => 'Id_Ups',
        ],
        'MAESTRO_HIS_FINANCIADOR' => [
            'idx_mhf_id_financiador' => 'Id_Financiador',
        ],
        'MAESTRO_HIS_ETNIA' => [
            'idx_mhe_id_etnia' => 'Id_Etnia',
        ],
        'MAESTRO_HIS_COLEGIO' => [
            'idx_mhc_id_colegio' => 'Id_Colegio',
        ],
        'MAESTRO_HIS_PROFESION' => [
            'idx_mhpr_id_profesion' => 'Id_Profesion',
        ],
        'MAESTRO_HIS_OTRA_CONDICION' => [
            'idx_mhoc_id_otra_condicion' => 'Id_Otra_Condicion',
        ],
        'MAESTRO_HIS_CONDICION_CONTRATO' => [
            'idx_mhcc_id_condicion' => 'Id_Condicion',
        ],
        'MAESTRO_HIS_CENTRO_POBLADO' => [
            'idx_mhcp_id_centro_poblado' => 'Id_Centro_Poblado',
        ],
    ];
    
    // Obtener indices existentes por tabla
    foreach ($indicesRequeridos as $tabla => $indices) {
        try {
            // Verificar que la tabla existe
            $tablaExiste = $pdo->query("SHOW TABLES LIKE '$tabla'")->fetchColumn();
            if (!$tablaExiste) continue;
            
            // Obtener indices existentes
            $indicesExistentes = [];
            $showIndex = $pdo->query("SHOW INDEX FROM `$tabla`");
            if ($showIndex) {
                foreach ($showIndex->fetchAll() as $idx) {
                    $indicesExistentes[] = $idx['Key_name'];
                }
            }
            $indicesExistentes = array_unique($indicesExistentes);
            
            foreach ($indices as $nombreIdx => $columnas) {
                if (!in_array($nombreIdx, $indicesExistentes)) {
                    try {
                        $pdo->exec("ALTER TABLE `$tabla` ADD INDEX `$nombreIdx` ($columnas)");
                        $indicesCreados[] = "$tabla.$nombreIdx ($columnas)";
                    } catch (Exception $e) {
                        // Si el indice ya existe (por nombre diferente), ignorar
                        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                            error_log("No se pudo crear indice $nombreIdx en $tabla: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error verificando indices en $tabla: " . $e->getMessage());
        }
    }
    
    return $indicesCreados;
}

/**
 * Construir la consulta SQL de consolidacion (INSERT INTO ... SELECT)
 * Retorna el SQL completo
 */
function construirSQLConsolidacion($whereClause = '') {
    return "
        SET SQL_BIG_SELECTS=1; 
        
        INSERT INTO T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
    (Id_Cita, Anio, Mes, Dia, Fecha_Atencion, Lote, Num_Pag, Num_Reg,
     Id_Ups, Descripcion_Ups, Id_AplicacionOrigen, Alerta, Id_Institucion_Edu,
     Id_Establecimiento, Codigo_Sector, Descripcion_Sector, Codigo_Disa, Descripcion_Disa,
     Codigo_Red, Descripcion_Red, Codigo_MicroRed, Descripcion_MicroRed,
     Codigo_Unico, Nombre_Establecimiento, Ubigueo_Establecimiento,
     Departamento_Establecimiento, Provincia_Establecimiento, Distrito_Establecimiento,
     Id_Paciente, Tipo_Doc_Paciente, Abrev_Tipo_Doc_Paciente,
     Numero_Documento_Paciente, Apellido_Paterno_Paciente, Apellido_Materno_Paciente,
     Nombres_Paciente, Fecha_Nacimiento_Paciente, Id_Genero, Historia_Clinica,
     Ficha_Familiar, Id_Etnia, Descripcion_Etnia, Id_Financiador, Descripcion_Financiador,
     Id_Pais, Descripcion_Pais,
     Id_Personal, Tipo_Doc_Personal, Abrev_Tipo_Doc_Personal,
     Numero_Documento_Personal, Apellido_Paterno_Personal, Apellido_Materno_Personal,
     Nombres_Personal, Fecha_Nacimiento_Personal, Id_Condicion, Descripcion_Condicion,
     Id_Profesion, Descripcion_Profesion, Id_Colegio, Descripcion_Colegio,
     Numero_Colegiatura, Id_Registrador, Tipo_Doc_Registrador, Abrev_Tipo_Doc_Registrador,
     Numero_Documento_Registrador, Apellido_Paterno_Registrador, Apellido_Materno_Registrador,
     Nombres_Registrador, Fecha_Nacimiento_Registrador, Id_Condicion_Establecimiento,
     Id_Condicion_Servicio, Edad_Reg, Tipo_Edad, Anio_Actual_Paciente,
     Mes_Actual_Paciente, Dia_Actual_Paciente, Grupo_Edad, peso_pregestacional,
     Id_Turno, Fg_Tipo, Codigo_Item, Descripcion_Item, Tipo_Diagnostico, Valor_Lab,
     Id_Correlativo_Item, Id_Correlativo_Lab, Peso, Talla, Hemoglobina,
     Perimetro_Abdominal, Perimetro_Cefalico, Id_Otra_Condicion, Descripcion_Otra_Condicion,
     Id_Centro_Poblado, Descripcion_Centro_Poblado, Id_Codigo_Centro_Poblado,
     Id_Ubigueo_Centro_Poblado, Altitud_Centro_Poblado, Fecha_Ultima_Regla,
     Fecha_Solicitud_Hb, Fecha_Resultado_Hb, Fecha_Registro, Fecha_Modificacion)
    SELECT
    NTN.Id_Cita,
    NTN.Anio,
    NTN.Mes,
    NTN.Dia,
    NTN.Fecha_Atencion,
    NTN.Lote,
    NTN.Num_Pag,
    NTN.Num_Reg,
    NTN.Id_Ups,
    MHU.Descripcion_Ups,
    NTN.Id_AplicacionOrigen,
    NTN.Alerta,
    NTN.Id_Institucion_Edu,
    NTN.Id_Establecimiento,
    MHES.Codigo_Sector,
    MHES.Descripcion_Sector,
    MHES.Codigo_Disa,
    MHES.Disa AS Descripcion_Disa,
    MHES.Codigo_Red,
    MHES.Red AS Descripcion_Red,
    MHES.Codigo_MicroRed,
    MHES.MicroRed AS Descripcion_MicroRed,
    MHES.Codigo_Unico,
    MHES.Nombre_Establecimiento,
    MHES.Ubigueo_Establecimiento,
    MHES.Departamento AS Departamento_Establecimiento,
    MHES.Provincia AS Provincia_Establecimiento,
    MHES.Distrito AS Distrito_Establecimiento,
    NTN.Id_Paciente,
    HTDPA.Id_Tipo_Documento AS Tipo_Doc_Paciente,
    HTDPA.Abrev_Tipo_Doc AS Abrev_Tipo_Doc_Paciente,
    MPA.Numero_Documento_Paciente,
    MPA.Apellido_Paterno_Paciente,
    MPA.Apellido_Materno_Paciente,
    MPA.Nombres_Paciente,
    MPA.Fecha_Nacimiento_Paciente,
    MPA.Id_Genero,
    MPA.Historia_Clinica,
    CASE WHEN (NTN.Id_Paciente LIKE 'A%') THEN NTN.Id_Paciente ELSE MPA.Ficha_Familiar END AS Ficha_Familiar,
    MPA.Id_Etnia,
    MHE.Descripcion_Etnia,
    NTN.Id_Financiador,
    MHF.Descripcion_Financiador,
    MPA.Id_Pais,
    MHP.Descripcion_Pais,
    NTN.Id_Personal,
    HTDPE.Id_Tipo_Documento AS Tipo_Doc_Personal,
    HTDPE.Abrev_Tipo_Doc AS Abrev_Tipo_Doc_Personal,
    MP.Numero_Documento_Personal,
    MP.Apellido_Paterno_Personal,
    MP.Apellido_Materno_Personal,
    MP.Nombres_Personal,
    MP.Fecha_Nacimiento_Personal,
    MHCC.Id_Condicion,
    MHCC.Descripcion_Condicion,
    MHPR.Id_Profesion,
    MHPR.Descripcion_Profesion,
    MHC.Id_Colegio,
    MHC.Descripcion_Colegio,
    MP.Numero_Colegiatura,
    NTN.Id_Registrador,
    HTDRE.Id_Tipo_Documento AS Tipo_Doc_Registrador,
    HTDRE.Abrev_Tipo_Doc AS Abrev_Tipo_Doc_Registrador,
    MR.Numero_Documento_Registrador,
    MR.Apellido_Paterno_Registrador,
    MR.Apellido_Materno_Registrador,
    MR.Nombres_Registrador,
    MR.Fecha_Nacimiento_Registrador,
    NTN.Id_Condicion_Establecimiento,
    NTN.Id_Condicion_Servicio,
    NTN.Edad_Reg,
    NTN.Tipo_Edad,
    NTN.Anio_Actual_Paciente,
    NTN.Mes_Actual_Paciente,
    NTN.Dia_Actual_Paciente,
    CASE
        WHEN (NTN.Tipo_Edad='D' AND NTN.Edad_Reg BETWEEN 1 AND 29) THEN '01 a 29 dias'
        WHEN (NTN.Tipo_Edad='M' AND NTN.Edad_Reg BETWEEN 1 AND 11) THEN '01 a 11 meses'
        WHEN (NTN.Tipo_Edad='A' AND NTN.Edad_Reg BETWEEN 1 AND 4) THEN '01 a 04 anos'
        WHEN (NTN.Tipo_Edad='A' AND NTN.Edad_Reg BETWEEN 5 AND 11) THEN '05 a 11 anos'
        WHEN (NTN.Tipo_Edad='A' AND NTN.Edad_Reg BETWEEN 12 AND 17) THEN '12 a 17 anos'
        WHEN (NTN.Tipo_Edad='A' AND NTN.Edad_Reg BETWEEN 18 AND 29) THEN '18 a 29 anos'
        WHEN (NTN.Tipo_Edad='A' AND NTN.Edad_Reg BETWEEN 30 AND 59) THEN '30 a 59 anos'
        WHEN (NTN.Tipo_Edad='A' AND NTN.Edad_Reg > 59) THEN '60 anos a mas'
    END AS Grupo_Edad,
    NTN.Peso_Pregestacional,
    NTN.Id_Turno,
    MCC.Fg_Tipo,
    NTN.Codigo_Item,
    MCC.Descripcion_Item,
    NTN.Tipo_Diagnostico,
    NTN.Valor_Lab,
    NTN.Id_Correlativo_Item,
    NTN.Id_Correlativo_Lab,
    NTN.Peso,
    NTN.Talla,
    NTN.Hemoglobina,
    NTN.Perimetro_Abdominal,
    NTN.Perimetro_Cefalico,
    NTN.Id_Otra_Condicion,
    MOC.Descripcion_Otra_Condicion,
    NTN.Id_Centro_Poblado,
    MHCP.Descripcion_Centro_Poblado,
    MHCP.Id_Codigo_Centro_Poblado,
    MHCP.Id_Ubigueo_Centro_Poblado,
    MHCP.Altitud_Centro_Poblado,
    NTN.Fecha_Ultima_Regla,
    NTN.Fecha_Solicitud_Hb,
    NTN.Fecha_Resultado_Hb,
    NTN.Fecha_Registro,
    NTN.Fecha_Modificacion
    FROM NOMINAL_TRAMA_NUEVO NTN
    LEFT JOIN MAESTRO_PERSONAL MP ON MP.Id_Personal=NTN.Id_Personal
    LEFT JOIN MAESTRO_PACIENTE MPA ON MPA.Id_Paciente=NTN.Id_Paciente
    LEFT JOIN MAESTRO_REGISTRADOR MR ON MR.Id_Registrador=NTN.Id_Registrador
    LEFT JOIN MAESTRO_HIS_OTRA_CONDICION MOC ON MOC.Id_Otra_Condicion=NTN.Id_Otra_Condicion
    LEFT JOIN MAESTRO_HIS_CIE_CPMS MCC ON MCC.Codi_Item=NTN.Codigo_Item
    LEFT JOIN MAESTRO_HIS_TIPO_DOC HTDPE ON HTDPE.Id_Tipo_Documento=MP.Id_Tipo_Documento_Personal
    LEFT JOIN MAESTRO_HIS_TIPO_DOC HTDPA ON HTDPA.Id_Tipo_Documento=MPA.Id_Tipo_Documento_Paciente
    LEFT JOIN MAESTRO_HIS_TIPO_DOC HTDRE ON HTDRE.Id_Tipo_Documento=MR.Id_Tipo_Documento_Registrador
    LEFT JOIN MAESTRO_HIS_FINANCIADOR MHF ON MHF.Id_Financiador=NTN.Id_Financiador
    LEFT JOIN MAESTRO_HIS_ETNIA MHE ON MHE.Id_Etnia=MPA.Id_Etnia
    LEFT JOIN MAESTRO_HIS_COLEGIO MHC ON MHC.Id_Colegio=MP.Id_Colegio
    LEFT JOIN MAESTRO_HIS_PROFESION MHPR ON MHPR.Id_Profesion=MP.Id_Profesion
    LEFT JOIN MAESTRO_HIS_UPS MHU ON MHU.Id_Ups=NTN.Id_Ups
    INNER JOIN ZSPERENE MHES ON MHES.Id_Establecimiento=NTN.Id_Establecimiento
    LEFT JOIN MAESTRO_HIS_CONDICION_CONTRATO MHCC ON MHCC.Id_Condicion=MP.Id_Condicion
    LEFT JOIN MAESTRO_HIS_CENTRO_POBLADO MHCP ON MHCP.Id_Centro_Poblado=NTN.Id_Centro_Poblado
    LEFT JOIN MAESTRO_HIS_PAIS MHP ON MHP.Id_Pais=MPA.Id_Pais"
    . $whereClause;
}

/**
 * Ejecutar consulta de procesamiento (consolidacion)
 * 
 * Optimizaciones implementadas:
 * 1. Crea indices en columnas JOIN si no existen (evita full table scan)
 * 2. Desactiva max_statement_time para la sesion (evita timeout de MySQL)
 * 3. Para reconstruccion completa, procesa por periodos (batch)
 * 4. PHP set_time_limit(0) para evitar timeout de PHP
 */
function ejecutarProcesamiento($anio = null, $mes = null) {
    $pdo = getDBConnection();
    $startTime = microtime(true);
    
    // Evitar timeout de PHP
    @set_time_limit(0);
    @ignore_user_abort(true);
    
    try {
        $registrosProcesados = 0;
        $detalles = [];
        
        // =====================================================
        // PASO 1: Crear indices si no existen (CRITICO)
        // =====================================================
        $indicesCreados = asegurarIndicesConsolidacion();
        if (!empty($indicesCreados)) {
            $detalles[] = 'Indices creados: ' . count($indicesCreados);
        }
        
        // =====================================================
        // PASO 2: Desactivar timeout de MySQL
        // =====================================================
        try {
            // MySQL 5.7.4+ / 8.0: max_statement_time en milisegundos
            // 0 = sin limite
            $pdo->exec("SET SESSION max_statement_time = 0");
        } catch (Exception $e) {
            // Si no soporta max_statement_time, continuar
            $detalles[] = 'Nota: max_statement_time no disponible (' . $e->getMessage() . ')';
        }
        
        // Aumentar tiempo de espera de MySQL para esta sesion
        try {
            $pdo->exec("SET SESSION wait_timeout = 28800");
            $pdo->exec("SET SESSION interactive_timeout = 28800");
        } catch (Exception $e) {
            // Ignorar si no tiene permisos
        }
        
        // =====================================================
        // PASO 3: Diagnosticar tablas fuente
        // =====================================================
        $countTrama = (int)$pdo->query("SELECT COUNT(*) FROM NOMINAL_TRAMA_NUEVO")->fetchColumn();
        $countPersonal = (int)$pdo->query("SELECT COUNT(*) FROM MAESTRO_PERSONAL")->fetchColumn();
        $countPaciente = (int)$pdo->query("SELECT COUNT(*) FROM MAESTRO_PACIENTE")->fetchColumn();
        $countRegistrador = (int)$pdo->query("SELECT COUNT(*) FROM MAESTRO_REGISTRADOR")->fetchColumn();
        
        if ($countTrama === 0) {
            return [
                'success' => false,
                'registros' => 0,
                'duracion' => round(microtime(true) - $startTime, 2),
                'mensaje' => 'No hay datos en NOMINAL_TRAMA_NUEVO para procesar. Verifique que la importacion de NominalTrama se haya completado correctamente. Registros: Trama=' . number_format($countTrama) . ', Personal=' . number_format($countPersonal) . ', Paciente=' . number_format($countPaciente) . ', Registrador=' . number_format($countRegistrador)
            ];
        }
        
        // =====================================================
        // PASO 4: Asegurar columnas Id_Pais/Descripcion_Pais
        // =====================================================
        $colsNeeded = [
            'Id_Pais' => "ALTER TABLE T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO ADD COLUMN `Id_Pais` varchar(3) DEFAULT NULL AFTER `Descripcion_Financiador`",
            'Descripcion_Pais' => "ALTER TABLE T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO ADD COLUMN `Descripcion_Pais` varchar(100) DEFAULT NULL AFTER `Id_Pais`"
        ];
        $existingCols = $pdo->query("SHOW COLUMNS FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($colsNeeded as $colName => $alterSql) {
            if (!in_array($colName, $existingCols)) {
                $pdo->exec($alterSql);
            }
        }
        
        // =====================================================
        // PASO 5: Ejecutar consolidacion
        // =====================================================
        if ($anio && $mes) {
            // --- PROCESAMIENTO POR PERIODO ESPECIFICO ---
            
            // Verificar que existan datos para el periodo seleccionado
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM NOMINAL_TRAMA_NUEVO WHERE TRIM(Anio) = ? AND CAST(TRIM(Mes) AS UNSIGNED) = ?");
            $stmtCheck->execute([$anio, intval($mes)]);
            $countPeriodo = (int)$stmtCheck->fetchColumn();
            
            if ($countPeriodo === 0) {
                // Mostrar periodos disponibles en la tabla
                $periodosDisponibles = $pdo->query("SELECT DISTINCT TRIM(Anio) as Anio, TRIM(Mes) as Mes FROM NOMINAL_TRAMA_NUEVO WHERE Anio IS NOT NULL AND Anio != '' AND Mes IS NOT NULL AND Mes != '' ORDER BY Anio DESC, CAST(TRIM(Mes) AS UNSIGNED) DESC LIMIT 20")->fetchAll();
                $periodosTxt = [];
                foreach ($periodosDisponibles as $p) {
                    $periodosTxt[] = trim($p['Mes']) . '/' . trim($p['Anio']);
                }
                return [
                    'success' => false,
                    'registros' => 0,
                    'duracion' => round(microtime(true) - $startTime, 2),
                    'mensaje' => 'No hay datos en NOMINAL_TRAMA_NUEVO para el periodo ' . $mes . '/' . $anio . '. Periodos disponibles en la tabla: ' . (empty($periodosTxt) ? 'Ninguno (verifique que Anio y Mes tengan valores)' : implode(', ', $periodosTxt))
                ];
            }
            
            // Eliminar periodo existente del consolidado
            $stmt = $pdo->prepare("DELETE FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE TRIM(Anio) = ? AND CAST(TRIM(Mes) AS UNSIGNED) = ?");
            $stmt->execute([$anio, intval($mes)]);
            
            // Construir y ejecutar INSERT con filtro de periodo
            $wherePeriodo = " WHERE TRIM(NTN.Anio) = " . $pdo->quote($anio) . " AND CAST(TRIM(NTN.Mes) AS UNSIGNED) = " . $pdo->quote(intval($mes));
            $sql = construirSQLConsolidacion($wherePeriodo);
            $pdo->exec($sql);
            
            // Contar registros insertados
            $countSql = "SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE TRIM(Anio) = " . $pdo->quote($anio) . " AND CAST(TRIM(Mes) AS UNSIGNED) = " . $pdo->quote(intval($mes));
            $registrosProcesados = (int)$pdo->query($countSql)->fetchColumn();
            
        } else {
            // --- RECONSTRUCCION COMPLETA (POR PERIODOS / BATCH) ---
            // En lugar de un solo INSERT masivo que puede agotar el timeout,
            // procesamos periodo por periodo. Cada periodo es mas pequeño y rapido.
            
            // Limpiar tabla consolidado
            $pdo->exec("TRUNCATE TABLE T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO");
            
            // Obtener todos los periodos distintos que existen en NOMINAL_TRAMA_NUEVO
            $periodos = $pdo->query(
                "SELECT DISTINCT TRIM(Anio) as Anio, TRIM(Mes) as Mes 
                 FROM NOMINAL_TRAMA_NUEVO 
                 WHERE Anio IS NOT NULL AND Anio != '' AND Mes IS NOT NULL AND Mes != '' 
                 ORDER BY Anio ASC, CAST(TRIM(Mes) AS UNSIGNED) ASC"
            )->fetchAll();
            
            if (empty($periodos)) {
                // No hay periodos validos, intentar INSERT masivo sin filtro
                $sql = construirSQLConsolidacion('');
                $pdo->exec($sql);
                $registrosProcesados = (int)$pdo->query("SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetchColumn();
            } else {
                // Procesar cada periodo individualmente
                $totalPeriodos = count($periodos);
                $registrosPorPeriodo = [];
                
                foreach ($periodos as $idx => $periodo) {
                    $pAnio = trim($periodo['Anio']);
                    $pMes = trim($periodo['Mes']);
                    
                    $whereP = " WHERE TRIM(NTN.Anio) = " . $pdo->quote($pAnio) . " AND CAST(TRIM(NTN.Mes) AS UNSIGNED) = " . $pdo->quote(intval($pMes));
                    $sql = construirSQLConsolidacion($whereP);
                    
                    try {
                        $pdo->exec($sql);
                        
                        // Contar registros insertados para este periodo
                        $countP = $pdo->prepare("SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE TRIM(Anio) = ? AND CAST(TRIM(Mes) AS UNSIGNED) = ?");
                        $countP->execute([$pAnio, intval($pMes)]);
                        $regsP = (int)$countP->fetchColumn();
                        $registrosPorPeriodo[] = $pMes . '/' . $pAnio . ': ' . number_format($regsP);
                        $registrosProcesados += $regsP;
                        
                    } catch (Exception $ePeriodo) {
                        $detalles[] = 'Error en periodo ' . $pMes . '/' . $pAnio . ': ' . $ePeriodo->getMessage();
                        // Continuar con el siguiente periodo
                    }
                }
                
                if (!empty($registrosPorPeriodo)) {
                    $detalles[] = 'Periodos procesados (' . $totalPeriodos . '): ' . implode(', ', $registrosPorPeriodo);
                }
            }
        }
        
        $duration = round(microtime(true) - $startTime, 2);
        
        // Construir mensaje descriptivo
        $infoMsg = "Tablas fuente: Trama=" . number_format($countTrama) . ", Personal=" . number_format($countPersonal) . ", Paciente=" . number_format($countPaciente) . ", Registrador=" . number_format($countRegistrador);
        if (!empty($detalles)) {
            $infoMsg .= ' | ' . implode(' | ', $detalles);
        }
        
        // Registrar en log
        logImportacion([
            'tipo_operacion' => 'PROCESS',
            'tipo_archivo' => 'CONSOLIDADO',
            'tabla_destino' => 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO',
            'periodo_mes' => $mes,
            'periodo_anio' => $anio,
            'registros_procesados' => $registrosProcesados,
            'modo_importacion' => ($anio && $mes) ? 'PERIODO' : 'COMPLETO',
            'estado' => 'EXITO',
            'mensaje' => 'Procesamiento ejecutado. ' . $infoMsg . '. Registros consolidados: ' . $registrosProcesados,
            'duracion_segundos' => $duration
        ]);
        
        return [
            'success' => true,
            'registros' => $registrosProcesados,
            'duracion' => $duration,
            'mensaje' => 'Procesamiento completado: ' . number_format($registrosProcesados) . ' registros consolidados (' . $duration . 's). ' . $infoMsg
        ];
        
    } catch (Exception $e) {
        $duration = round(microtime(true) - $startTime, 2);
        
        logImportacion([
            'tipo_operacion' => 'PROCESS',
            'tipo_archivo' => 'CONSOLIDADO',
            'tabla_destino' => 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO',
            'periodo_mes' => $mes,
            'periodo_anio' => $anio,
            'registros_procesados' => 0,
            'estado' => 'ERROR',
            'mensaje' => $e->getMessage(),
            'duracion_segundos' => $duration
        ]);
        
        return [
            'success' => false,
            'registros' => 0,
            'duracion' => $duration,
            'mensaje' => 'Error en procesamiento: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener periodos disponibles para procesamiento
 * Retorna lista de periodos distinct en NOMINAL_TRAMA_NUEVO
 */
function obtenerPeriodosDisponibles() {
    $pdo = getDBConnection();
    try {
        $stmt = $pdo->query(
            "SELECT DISTINCT TRIM(Anio) as Anio, TRIM(Mes) as Mes 
             FROM NOMINAL_TRAMA_NUEVO 
             WHERE Anio IS NOT NULL AND Anio != '' AND Mes IS NOT NULL AND Mes != '' 
             ORDER BY Anio DESC, CAST(TRIM(Mes) AS UNSIGNED) DESC"
        );
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}
