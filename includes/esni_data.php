<?php
/**
 * Sistema de Gestion de Datos HIS
 * Modulo ESNI - Funciones helper (motor de reglas data-driven)
 *
 * Reemplaza los Stored Procedures T-SQL del SQL Server (archivos
 * 03_StoredProcedure_TRAMA_BASE.txt y 04_USP_TRAMA_BASE_ESNI_2019_RPT.txt)
 * por consultas dinamicas construidas a partir de las tablas de configuracion
 * ESNI_VACUNA, ESNI_DOSIS, ESNI_GRUPO_EDAD, ESNI_SECCION_REPORTE,
 * ESNI_LINEA_REPORTE, ESNI_REGLA y ESNI_PARAMETRO.
 *
 * Ventaja: para agregar o modificar una vacuna/dosis/regla, basta con editar
 * un registro en la BD desde la pagina esni_config.php. No se necesita
 * reescribir SQL ni volver a generar Excel.
 */

require_once __DIR__ . '/../config.php';

/**
 * Detecta dinamicamente el nombre real de la columna en la tabla consolidada.
 * Algunas tablas pueden tener variantes (Id_Paciente vs id_paciente, etc.)
 */
function esniDetectarColumna(PDO $pdo, string $tabla, array $candidatas): ?string {
    static $cache = [];
    $key = $tabla . '|' . implode(',', $candidatas);
    if (isset($cache[$key])) return $cache[$key];

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabla`");
        $stmt->execute();
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $colsLower = array_map('strtolower', $cols);
        foreach ($candidatas as $cand) {
            $idx = array_search(strtolower($cand), $colsLower);
            if ($idx !== false) {
                $cache[$key] = $cols[$idx];
                return $cols[$idx];
            }
        }
    } catch (Throwable $e) {
        // Tabla no accesible
    }
    $cache[$key] = null;
    return null;
}

/**
 * Obtiene un parametro de configuracion ESNI.
 */
function esniGetParametro(PDO $pdo, string $clave, string $default = ''): string {
    try {
        $stmt = $pdo->prepare("SELECT valor FROM ESNI_PARAMETRO WHERE clave = ? LIMIT 1");
        $stmt->execute([$clave]);
        $v = $stmt->fetchColumn();
        return ($v === false || $v === null) ? $default : $v;
    } catch (Throwable $e) {
        return $default;
    }
}

/**
 * Devuelve un mapa con todas las columnas efectivas a usar en la tabla origen.
 * Resuelve cada parametro a la columna real que existe en la tabla.
 */
function esniResolverColumnas(PDO $pdo): array {
    $tabla = esniGetParametro($pdo, 'tabla_origen', 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO');

    $map = [
        'cod_item'        => ['Codigo_Item', 'cod_item', 'COD_ITEM'],
        'valor_lab'       => ['Valor_Lab', 'valor_lab', 'VALOR_LAB', 'ValorLab', 'LabValor'],
        'aniomes'         => ['AnioMes', 'aniomes', 'Periodo', 'periodo', 'AnioMesMov'],
        'edad_reg'        => ['Edad_Reg', 'edad_reg', 'Edad', 'edad'],
        'tip_edad'        => ['Tipo_Edad_Reg', 'id_tipedad_reg', 'tip_edad', 'TipEdad', 'TipoEdad'],
        'grupo_edad'      => ['Grupo_Edad', 'grupo_edad', 'GrupoEdad'],
        'sexo'            => ['Id_Genero', 'id_genero', 'sexo', 'Sexo', 'Genero'],
        'id_paciente'     => ['Id_Paciente', 'id_paciente', 'id_persona', 'Id_Persona'],
        'establecimiento' => ['Nombre_Establecimiento', 'nombre_establecimiento', 'NombreEstablecimiento'],
        'departamento'    => ['Departamento_Establecimiento', 'departamento', 'Departamento'],
        'anio'            => ['Anio', 'anio', 'AnioMov'],
        'mes'             => ['Mes', 'mes'],
        'id_cita'         => ['Id_Cita', 'id_cita', 'Id_Cita'],
        'profesional'     => ['Id_Profesional', 'id_profesional', 'Profesional'],
        'renaes'          => ['Renaes', 'renaes', 'Codigo_Renaes', 'CodigoRenaes'],
        'id_gruporiesgo'  => ['Id_GrupoRiesgo', 'id_gruporiesgo', 'GrupoRiesgo'],
        'rownnum_lab'     => ['I_ROWNUM_LAB', 'i_rownum_lab', 'RowNumLab'],
    ];

    $resueltos = [];
    foreach ($map as $campo => $candidatas) {
        $resueltos[$campo] = esniDetectarColumna($pdo, $tabla, $candidatas);
    }
    $resueltos['_tabla'] = $tabla;
    return $resueltos;
}

/**
 * Devuelve todas las secciones activas del reporte operacional ESNI,
 * ordenadas por el campo orden.
 */
function esniGetSecciones(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM ESNI_SECCION_REPORTE WHERE activo = 1 ORDER BY orden, codigo");
    return $stmt->fetchAll();
}

/**
 * Devuelve las lineas activas de una seccion, con joins a vacuna/dosis/grupo_edad.
 */
function esniGetLineasSeccion(PDO $pdo, int $idSeccion): array {
    $sql = "SELECT l.*, v.codigo AS vacuna_codigo, v.nombre AS vacuna_nombre, v.color AS vacuna_color,
                   d.codigo AS dosis_codigo, d.nombre AS dosis_nombre,
                   g.codigo AS grupo_edad_codigo, g.nombre AS grupo_edad_nombre,
                   g.tipo_edad AS ge_tipo_edad, g.edad_min AS ge_edad_min, g.edad_max AS ge_edad_max
            FROM ESNI_LINEA_REPORTE l
            LEFT JOIN ESNI_VACUNA v       ON v.id_vacuna = l.id_vacuna
            LEFT JOIN ESNI_DOSIS d        ON d.id_dosis  = l.id_dosis
            LEFT JOIN ESNI_GRUPO_EDAD g   ON g.id_grupo_edad = l.id_grupo_edad
            WHERE l.id_seccion = ? AND l.activo = 1
            ORDER BY l.orden, l.id_linea";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSeccion]);
    return $stmt->fetchAll();
}

/**
 * Devuelve todas las reglas activas (con info de la linea y grupo edad).
 */
function esniGetReglas(PDO $pdo, ?int $idLinea = null): array {
    $sql = "SELECT r.*, l.id_seccion, l.id_linea, l.etiqueta, l.orden AS linea_orden,
                   g.codigo AS grupo_edad_codigo, g.nombre AS grupo_edad_nombre,
                   g.tipo_edad AS ge_tipo_edad, g.edad_min AS ge_edad_min, g.edad_max AS ge_edad_max
            FROM ESNI_REGLA r
            INNER JOIN ESNI_LINEA_REPORTE l ON l.id_linea = r.id_linea
            LEFT JOIN ESNI_GRUPO_EDAD g ON g.id_grupo_edad = r.id_grupo_edad
            WHERE r.activo = 1 AND l.activo = 1";
    $params = [];
    if ($idLinea !== null) {
        $sql .= " AND r.id_linea = ?";
        $params[] = $idLinea;
    }
    $sql .= " ORDER BY l.id_seccion, l.orden, r.id_regla";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Construye la clausula WHERE de filtros comunes (anio/mes/establecimiento/etc).
 */
function esniConstruirWhereFiltros(array $cols, array $filtros): array {
    $where = ["1=1"];
    $params = [];

    if (!empty($filtros['anio']) && $cols['anio']) {
        $where[] = "`{$cols['anio']}` = :anio";
        $params[':anio'] = $filtros['anio'];
    }
    if (!empty($filtros['mes']) && $cols['mes']) {
        $where[] = "CAST(TRIM(`{$cols['mes']}`) AS UNSIGNED) = :mes";
        $params[':mes'] = intval($filtros['mes']);
    }
    if (!empty($filtros['establecimiento']) && $cols['establecimiento']) {
        $where[] = "`{$cols['establecimiento']}` = :est";
        $params[':est'] = $filtros['establecimiento'];
    }
    if (!empty($filtros['departamento']) && $cols['departamento']) {
        $where[] = "`{$cols['departamento']}` = :dep";
        $params[':dep'] = $filtros['departamento'];
    }
    if (!empty($filtros['profesional']) && $cols['profesional']) {
        $where[] = "`{$cols['profesional']}` = :prof";
        $params[':prof'] = $filtros['profesional'];
    }

    return [implode(' AND ', $where), $params];
}

/**
 * Comprueba si un grupo de edad "encaja" con un valor de edad/tipo edad de fila.
 * Para simplificar, en modo basico se trabaja con Grupo_Edad textual;
 * en modo avanzado (edad_reg + tip_edad) se compara numericamente.
 */
function esniEdadEncaja(array $regla, ?float $edadReg, ?string $tipEdad, ?string $grupoEdadTexto): bool {
    // Si la regla no tiene grupo_edad, encaja siempre
    if (empty($regla['grupo_edad_codigo'])) return true;

    // Modo avanzado: si tenemos edad_reg y tip_edad, comparamos numericamente
    if ($edadReg !== null && $tipEdad !== null && !empty($regla['ge_tipo_edad']) && $regla['ge_edad_min'] !== null) {
        $tipRegla = $regla['ge_tipo_edad'];
        if ($tipEdad !== $tipRegla) return false;
        if ($edadReg < $regla['ge_edad_min']) return false;
        if ($regla['ge_edad_max'] !== null && $edadReg > $regla['ge_edad_max']) return false;
        return true;
    }

    // Modo basico: comparamos por texto del grupo de edad
    if ($grupoEdadTexto === null) return false;
    $codigoRegla = $regla['grupo_edad_codigo'];
    $nombreRegla = $regla['grupo_edad_nombre'] ?? '';

    // Match exacto o por contiene
    $texto = strtoupper(trim($grupoEdadTexto));
    if (strpos($texto, strtoupper($codigoRegla)) !== false) return true;
    if (strpos($texto, strtoupper($nombreRegla)) !== false) return true;

    // Mapeo manual para casos comunes
    $simplificado = esniNormalizarGrupoEdad($texto);
    $reglaNorm = esniNormalizarGrupoEdad(strtoupper($nombreRegla . ' ' . $codigoRegla));
    if ($simplificado && $reglaNorm && strpos($reglaNorm, $simplificado) !== false) return true;

    return false;
}

/**
 * Normaliza un texto de grupo de edad a un codigo simple.
 */
function esniNormalizarGrupoEdad(string $txt): string {
    $txt = strtoupper($txt);
    // Dias
    if (preg_match('/24\s*H/', $txt) || preg_match('/MENOR\s*DE\s*1\s*DIA/', $txt)) return '24H';
    if (preg_match('/\b28\s*D/', $txt) || preg_match('/DE\s*2\s*A\s*28\s*D/', $txt)) return '28D';
    // Meses
    if (preg_match('/\b1\s*A\s*11\s*M/', $txt) || preg_match('/MENOR\s*DE\s*1\s*A/', $txt) || preg_match('/01\s*A\s*11\s*M/', $txt)) return '01_11M';
    if (preg_match('/\b02\s*A\s*04\s*M/', $txt) || preg_match('/2\s*A\s*4\s*M/', $txt)) return '02_04M';
    if (preg_match('/\b02\s*A\s*07\s*M/', $txt) || preg_match('/2\s*A\s*7\s*M/', $txt)) return '02_07M';
    if (preg_match('/\b06\s*A\s*07\s*M/', $txt) || preg_match('/6\s*A\s*7\s*M/', $txt)) return '06_07M';
    if (preg_match('/\b06\s*A\s*11\s*M/', $txt) || preg_match('/6\s*A\s*11\s*M/', $txt)) return '06_11M';
    if (preg_match('/\b12\s*A\s*23\s*M/', $txt)) return '12_23M';
    if (preg_match('/\b15\s*M/', $txt)) return '15M';
    if (preg_match('/\b18\s*M/', $txt)) return '18M';
    // Anios
    if (preg_match('/\b01\s*A/', $txt) || preg_match('/\b1\s*A/', $txt)) return '01A_1A11M';
    if (preg_match('/\b02\s*A\s*04\s*A/', $txt) || preg_match('/2\s*A\s*4\s*A/', $txt)) return '02_04A';
    if (preg_match('/\b02\s*A/', $txt)) return '02A';
    if (preg_match('/\b03\s*A/', $txt)) return '03A';
    if (preg_match('/\b04\s*A/', $txt)) return '04A';
    if (preg_match('/\b05\s*A\s*59\s*A/', $txt) || preg_match('/5\s*A\s*59\s*A/', $txt)) return '05_59A';
    if (preg_match('/\b10\s*A\s*49\s*A/', $txt) || preg_match('/10\s*A\s*49\s*A/', $txt)) return '10_49A_M';
    // Riesgo / comorbilidad / gestantes
    if (preg_match('/GEST/', $txt) || preg_match('/GESTANTE/', $txt)) return 'GEST';
    if (preg_match('/COMORB/', $txt)) return 'COMORB';
    if (preg_match('/SIN\s*COMORB/', $txt)) return 'SIN_COMORB';
    if (preg_match('/RIESGO/', $txt)) return 'RIESGO';
    if (preg_match('/CONTACTO\s*TB/', $txt)) return 'CONTACTO_TB';
    if (preg_match('/CONTACTO\s*INDICE/', $txt) || preg_match('/CONTACTO\s*VAR/', $txt)) return 'CONTACTO_VAR';
    if (preg_match('/VIAJA/', $txt) || preg_match('/ENDEM/', $txt)) return 'VIAJA_END';
    if (preg_match('/NO\s*VAC/', $txt)) return 'NO_VAC';
    return '';
}

/**
 * Comprueba si una regla encaja con un valor_lab concreto.
 * Si la regla tiene valor_lab=NULL, encaja con cualquier valor.
 */
function esniValorLabEncaja(?string $valorLabRegla, ?string $valorLabFila): bool {
    if ($valorLabRegla === null || $valorLabRegla === '') return true;
    if ($valorLabFila === null) return false;
    return strtoupper(trim($valorLabFila)) === strtoupper(trim($valorLabRegla));
}

/**
 * Ejecuta el reporte operacional ESNI completo.
 *
 * Estrategia: para optimizar consultas a la BD, primero agrupamos las reglas
 * por cod_item, luego hacemos UNA sola consulta que trae todas las filas de
 * la tabla origen que tengan alguno de los cod_items relevantes (con los
 * filtros comunes aplicados), y luego aplicamos el matching en PHP.
 *
 * @param array $filtros Filtros: anio, mes, establecimiento, departamento, profesional
 * @param array $cols    Mapa de columnas resueltas (de esniResolverColumnas)
 * @return array Estructura: ['secciones' => [...], 'totales' => [...]]
 */
function esniEjecutarReporte(PDO $pdo, array $filtros, array $cols): array {
    $reglas = esniGetReglas($pdo);
    if (empty($reglas)) return ['secciones' => [], 'totales' => [], 'error' => 'No hay reglas configuradas'];

    // Agrupar cod_items unicos
    $codItems = array_unique(array_column($reglas, 'cod_item'));
    $codItems = array_filter($codItems, fn($c) => $c !== null && $c !== '');
    if (empty($codItems)) return ['secciones' => [], 'totales' => [], 'error' => 'Las reglas no tienen cod_item definidos'];

    if (!$cols['cod_item']) {
        return ['secciones' => [], 'totales' => [], 'error' => 'La tabla origen no tiene columna de codigo de item detectable'];
    }

    // Clausula WHERE comun
    [$whereComun, $paramsComun] = esniConstruirWhereFiltros($cols, $filtros);

    // Placeholder para IN (cod_items...)
    $inPlaceholders = [];
    foreach ($codItems as $i => $ci) {
        $k = ':ci_' . $i;
        $inPlaceholders[] = $k;
        $paramsComun[$k] = $ci;
    }
    $whereComun .= " AND `{$cols['cod_item']}` IN (" . implode(',', $inPlaceholders) . ")";

    // Si existe columna de rownum, solo tomar la fila 1 (deduplicacion)
    if (!empty($cols['rownnum_lab'])) {
        $whereComun .= " AND `{$cols['rownnum_lab']}` = 1";
    }

    // Construir SELECT con las columnas relevantes
    $selCols = ["`{$cols['cod_item']}` AS cod_item"];
    foreach (['valor_lab', 'edad_reg', 'tip_edad', 'grupo_edad', 'sexo', 'aniomes', 'id_paciente', 'id_cita', 'id_gruporiesgo'] as $c) {
        $selCols[] = !empty($cols[$c]) ? "`{$cols[$c]}` AS {$c}" : "NULL AS {$c}";
    }
    $tabla = $cols['_tabla'];
    $sql = "SELECT " . implode(', ', $selCols) . " FROM `{$tabla}` WHERE {$whereComun}";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($paramsComun);
        $filas = $stmt->fetchAll();
    } catch (Throwable $e) {
        return ['secciones' => [], 'totales' => [], 'error' => 'Error SQL: ' . $e->getMessage(),
                'sql_debug' => $sql, 'params_debug' => $paramsComun];
    }

    // Indexar reglas por cod_item para match rapido
    $reglasPorCod = [];
    foreach ($reglas as $r) {
        $reglasPorCod[$r['cod_item']][] = $r;
    }

    // Inicializar contadores por linea
    $contadores = []; // [id_linea => int]
    $lineaMeta  = []; // [id_linea => ['etiqueta'=>..., 'id_seccion'=>...]]
    foreach ($reglas as $r) {
        if (!isset($contadores[$r['id_linea']])) {
            $contadores[$r['id_linea']] = 0;
            $lineaMeta[$r['id_linea']] = [
                'id_seccion' => $r['id_seccion'],
                'etiqueta'   => $r['etiqueta'],
                'orden'      => $r['linea_orden'],
            ];
        }
    }

    // Recorrer filas y aplicar motor de reglas
    foreach ($filas as $f) {
        $cod = $f['cod_item'] ?? null;
        if ($cod === null) continue;
        $cod = trim((string)$cod);
        if (!isset($reglasPorCod[$cod])) continue;

        $valorLab = isset($f['valor_lab']) ? (string)$f['valor_lab'] : null;
        $edadReg  = isset($f['edad_reg'])  && $f['edad_reg']  !== null ? (float)$f['edad_reg']  : null;
        $tipEdad  = isset($f['tip_edad'])  && $f['tip_edad']  !== null ? (string)$f['tip_edad'] : null;
        $grupoEd  = isset($f['grupo_edad']) && $f['grupo_edad'] !== null ? (string)$f['grupo_edad'] : null;
        $sexoFila = isset($f['sexo']) && $f['sexo'] !== null ? strtoupper(trim((string)$f['sexo'])) : null;
        $aniomes  = isset($f['aniomes']) && $f['aniomes'] !== null ? (string)$f['aniomes'] : null;
        $idRiesgo = isset($f['id_gruporiesgo']) && $f['id_gruporiesgo'] !== null ? (string)$f['id_gruporiesgo'] : null;

        foreach ($reglasPorCod[$cod] as $r) {
            // 1) Valor lab
            if (!esniValorLabEncaja($r['valor_lab'], $valorLab)) continue;

            // 2) Sexo
            if ($r['sexo'] !== 'A' && $sexoFila !== null) {
                if (strtoupper($r['sexo']) !== $sexoFila) continue;
            }

            // 3) Rango aniomes
            if (!empty($r['aniomes_min']) && $aniomes !== null && strcmp($aniomes, $r['aniomes_min']) < 0) continue;
            if (!empty($r['aniomes_max']) && $aniomes !== null && strcmp($aniomes, $r['aniomes_max']) > 0) continue;

            // 4) Grupo de edad
            if (!empty($r['grupo_edad_codigo']) && !esniEdadEncaja($r, $edadReg, $tipEdad, $grupoEd)) continue;

            // 5) Requiere/excluye riesgo
            if ($r['requiere_riesgo'] == 1 && $idRiesgo !== '2') continue;
            if ($r['excluye_riesgo'] == 1 && $idRiesgo === '2') continue;

            // Match!
            $contadores[$r['id_linea']]++;
            break; // Solo se cuenta una vez por fila (la primera regla que encaja)
        }
    }

    // Agrupar por seccion
    $secciones = esniGetSecciones($pdo);
    $resultado = ['secciones' => [], 'totales' => ['total_dosis' => 0, 'total_lineas_con_datos' => 0], 'filas_leidas' => count($filas), 'error' => null];

    foreach ($secciones as $sec) {
        $lineasSec = esniGetLineasSeccion($pdo, (int)$sec['id_seccion']);
        $lineasResueltas = [];
        $totalSec = 0;
        foreach ($lineasSec as $lin) {
            $cantidad = $contadores[$lin['id_linea']] ?? 0;
            $lineasResueltas[] = [
                'id_linea'         => $lin['id_linea'],
                'etiqueta'         => $lin['etiqueta'],
                'vacuna_codigo'    => $lin['vacuna_codigo'] ?? '',
                'vacuna_nombre'    => $lin['vacuna_nombre'] ?? '',
                'vacuna_color'     => $lin['vacuna_color'] ?? '#0d6efd',
                'dosis_codigo'     => $lin['dosis_codigo'] ?? '',
                'dosis_nombre'     => $lin['dosis_nombre'] ?? '',
                'grupo_edad_codigo'=> $lin['grupo_edad_codigo'] ?? '',
                'grupo_edad_nombre'=> $lin['grupo_edad_nombre'] ?? '',
                'sexo'             => $lin['sexo'],
                'cantidad'         => $cantidad,
            ];
            $totalSec += $cantidad;
            if ($cantidad > 0) $resultado['totales']['total_lineas_con_datos']++;
        }
        $resultado['secciones'][] = [
            'id_seccion' => $sec['id_seccion'],
            'codigo'     => $sec['codigo'],
            'titulo'     => $sec['titulo'],
            'descripcion'=> $sec['descripcion'],
            'layout'     => $sec['layout'],
            'orden'      => $sec['orden'],
            'lineas'     => $lineasResueltas,
            'total'      => $totalSec,
        ];
        $resultado['totales']['total_dosis'] += $totalSec;
    }

    return $resultado;
}

/**
 * Obtiene la lista de anios disponibles en la tabla origen.
 */
function esniGetAniosDisponibles(PDO $pdo, array $cols): array {
    if (!$cols['anio']) return [date('Y')];
    try {
        $tabla = $cols['_tabla'];
        $stmt = $pdo->query("SELECT DISTINCT `{$cols['anio']}` AS anio FROM `{$tabla}` WHERE `{$cols['anio']}` IS NOT NULL ORDER BY anio DESC");
        $r = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $r ?: [date('Y')];
    } catch (Throwable $e) {
        return [date('Y')];
    }
}

/**
 * Obtiene lista de establecimientos.
 */
function esniGetEstablecimientos(PDO $pdo, array $cols): array {
    if (!$cols['establecimiento']) return [];
    try {
        $tabla = $cols['_tabla'];
        $stmt = $pdo->query("SELECT DISTINCT `{$cols['establecimiento']}` AS v FROM `{$tabla}` WHERE `{$cols['establecimiento']}` IS NOT NULL ORDER BY v LIMIT 500");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Obtiene lista de departamentos.
 */
function esniGetDepartamentos(PDO $pdo, array $cols): array {
    if (!$cols['departamento']) return [];
    try {
        $tabla = $cols['_tabla'];
        $stmt = $pdo->query("SELECT DISTINCT `{$cols['departamento']}` AS v FROM `{$tabla}` WHERE `{$cols['departamento']}` IS NOT NULL ORDER BY v LIMIT 100");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Obtiene lista de profesionales.
 */
function esniGetProfesionales(PDO $pdo, array $cols): array {
    if (!$cols['profesional']) return [];
    try {
        $tabla = $cols['_tabla'];
        $stmt = $pdo->query("SELECT DISTINCT `{$cols['profesional']}` AS v FROM `{$tabla}` WHERE `{$cols['profesional']}` IS NOT NULL ORDER BY v LIMIT 500");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Verifica si el esquema ESNI esta instalado.
 */
function esniEsquemaInstalado(PDO $pdo): bool {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'ESNI_VACUNA'");
        return $stmt->fetch() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Genera un hash de filtros para cache/identificacion.
 */
function esniFiltrosHash(array $filtros): string {
    ksort($filtros);
    return md5(http_build_query($filtros));
}

/**
 * Crea una nueva vacuna. Devuelve el id_vacuna.
 */
function esniCrearVacuna(PDO $pdo, string $codigo, string $nombre, string $descripcion = '', string $color = '#0d6efd'): int {
    $stmt = $pdo->prepare("INSERT INTO ESNI_VACUNA (codigo, nombre, descripcion, color) VALUES (?, ?, ?, ?)");
    $stmt->execute([$codigo, $nombre, $descripcion, $color]);
    return (int)$pdo->lastInsertId();
}

/**
 * Crea un nuevo grupo de edad. Devuelve el id_grupo_edad.
 */
function esniCrearGrupoEdad(PDO $pdo, string $codigo, string $nombre, string $tipoEdad = 'A', ?int $edadMin = null, ?int $edadMax = null): int {
    $stmt = $pdo->prepare("INSERT INTO ESNI_GRUPO_EDAD (codigo, nombre, tipo_edad, edad_min, edad_max) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$codigo, $nombre, $tipoEdad, $edadMin, $edadMax]);
    return (int)$pdo->lastInsertId();
}

/**
 * Crea una nueva dosis. Devuelve el id_dosis.
 */
function esniCrearDosis(PDO $pdo, string $codigo, string $nombre, int $orden = 0): int {
    $stmt = $pdo->prepare("INSERT INTO ESNI_DOSIS (codigo, nombre, orden) VALUES (?, ?, ?)");
    $stmt->execute([$codigo, $nombre, $orden]);
    return (int)$pdo->lastInsertId();
}

/**
 * Crea una nueva seccion de reporte. Devuelve el id_seccion.
 */
function esniCrearSeccion(PDO $pdo, string $codigo, string $titulo, string $descripcion = '', string $layout = 'lista', int $orden = 99): int {
    $stmt = $pdo->prepare("INSERT INTO ESNI_SECCION_REPORTE (codigo, titulo, descripcion, layout, orden) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$codigo, $titulo, $descripcion, $layout, $orden]);
    return (int)$pdo->lastInsertId();
}

/**
 * Crea una nueva linea dentro de una seccion. Devuelve el id_linea.
 */
function esniCrearLinea(PDO $pdo, int $idSeccion, string $etiqueta, ?int $idVacuna = null, ?int $idDosis = null, ?int $idGrupoEdad = null, string $sexo = 'A', int $orden = 99): int {
    $stmt = $pdo->prepare("INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$idSeccion, $orden, $etiqueta, $idVacuna, $idDosis, $idGrupoEdad, $sexo]);
    return (int)$pdo->lastInsertId();
}

/**
 * Crea una nueva regla. Devuelve el id_regla.
 */
function esniCrearRegla(PDO $pdo, int $idLinea, string $codItem, ?string $valorLab = null, ?int $idGrupoEdad = null, string $sexo = 'A', ?string $aniomesMin = null, ?string $aniomesMax = null, int $requiereRiesgo = 0, int $excluyeRiesgo = 0): int {
    $stmt = $pdo->prepare("INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, aniomes_min, aniomes_max, requiere_riesgo, excluye_riesgo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$idLinea, $codItem, $valorLab, $idGrupoEdad, $sexo, $aniomesMin, $aniomesMax, $requiereRiesgo, $excluyeRiesgo]);
    return (int)$pdo->lastInsertId();
}

/**
 * Devuelve un resumen rapido para el dashboard del modulo ESNI.
 */
function esniGetResumenConfig(PDO $pdo): array {
    $res = ['vacunas' => 0, 'dosis' => 0, 'grupos_edad' => 0, 'secciones' => 0, 'lineas' => 0, 'reglas' => 0];
    try {
        $res['vacunas']      = (int)$pdo->query("SELECT COUNT(*) FROM ESNI_VACUNA WHERE activo=1")->fetchColumn();
        $res['dosis']        = (int)$pdo->query("SELECT COUNT(*) FROM ESNI_DOSIS WHERE activo=1")->fetchColumn();
        $res['grupos_edad']  = (int)$pdo->query("SELECT COUNT(*) FROM ESNI_GRUPO_EDAD WHERE activo=1")->fetchColumn();
        $res['secciones']    = (int)$pdo->query("SELECT COUNT(*) FROM ESNI_SECCION_REPORTE WHERE activo=1")->fetchColumn();
        $res['lineas']       = (int)$pdo->query("SELECT COUNT(*) FROM ESNI_LINEA_REPORTE WHERE activo=1")->fetchColumn();
        $res['reglas']       = (int)$pdo->query("SELECT COUNT(*) FROM ESNI_REGLA WHERE activo=1")->fetchColumn();
    } catch (Throwable $e) {}
    return $res;
}
