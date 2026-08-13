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
        'tip_edad'        => ['Tipo_Edad_Reg', 'id_tipedad_reg', 'tip_edad', 'TipEdad', 'TipoEdad', 'Tipo_Edad'],
        'grupo_edad'      => ['Grupo_Edad', 'grupo_edad', 'GrupoEdad'],
        'sexo'            => ['Id_Genero', 'id_genero', 'sexo', 'Sexo', 'Genero'],
        'id_paciente'     => ['Id_Paciente', 'id_paciente', 'id_persona', 'Id_Persona'],
        'establecimiento' => ['Nombre_Establecimiento', 'nombre_establecimiento', 'NombreEstablecimiento'],
        'codigo_unico'    => ['Codigo_Unico', 'codigo_unico', 'CodigoUnico'],
        'departamento'    => ['Departamento_Establecimiento', 'departamento', 'Departamento'],
        'anio'            => ['Anio', 'anio', 'AnioMov'],
        'mes'             => ['Mes', 'mes'],
        'id_cita'         => ['Id_Cita', 'id_cita', 'Id_Cita'],
        'profesional'     => ['Id_Profesional', 'id_profesional', 'Profesional', 'Id_Personal', 'id_personal', 'Personal'],
        'renaes'          => ['Renaes', 'renaes', 'Codigo_Renaes', 'CodigoRenaes'],
        'id_gruporiesgo'  => ['Id_GrupoRiesgo', 'id_gruporiesgo', 'GrupoRiesgo'],
        'rownnum_lab'     => ['I_ROWNUM_LAB', 'i_rownum_lab', 'RowNumLab'],
        // Estrategia / UPS: el modulo ESNI solo debe leer filas con Id_Ups = 301204
        'id_ups'          => ['Id_Ups', 'id_ups', 'ID_UPS', 'IdUps', 'id_Ups'],
        // Etnia: usada por la regla excluye_etnia para filtrar por Id_Etnia
        // (ej: linea "COMUNIDADES NATIVAS" en seccion H)
        'id_etnia'        => ['Id_Etnia', 'id_etnia', 'ID_ETNIA', 'IdEtnia'],
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
    // Nota: r.* incluye las columnas requiere_valor_lab_cita y
    // excluye_valor_lab_cita agregadas por migration_seccion_j_covid.sql.
    // Si la migracion aun no se ha ejecutado, esniGetReglas lanzara una
    // excepcion PDOException (columna desconocida); el caller deberia
    // atraparla y mostrar mensaje de migracion pendiente.
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

    // Filtro fijo de estrategia ESNI: Id_Ups = 301204.
    // Se aplica SIEMPRE que la columna exista en la tabla origen y el filtro
    // venga en $filtros (el modulo reporte_esni.php lo setea por defecto).
    // Esto evita cargar datos de otras estrategias (VPH, Atencion, etc.)
    // que comparten la tabla T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO.
    if (!empty($filtros['id_ups']) && !empty($cols['id_ups'])) {
        $where[] = "`{$cols['id_ups']}` = :id_ups";
        $params[':id_ups'] = $filtros['id_ups'];
    }

    if (!empty($filtros['anio']) && $cols['anio']) {
        $where[] = "`{$cols['anio']}` = :anio";
        $params[':anio'] = $filtros['anio'];
    }
    if (!empty($filtros['mes']) && $cols['mes']) {
        $where[] = "CAST(TRIM(`{$cols['mes']}`) AS UNSIGNED) = :mes";
        $params[':mes'] = intval($filtros['mes']);
    }
    if (!empty($filtros['establecimiento']) && $cols['establecimiento']) {
        // El filtro de establecimiento ahora llega como un Codigo_Unico (valor del
        // <option> cargado desde la tabla ZSPERENE). Se filtra por la columna
        // Codigo_Unico de la tabla origen, que coincide con el Codigo_Unico de
        // ZSPERENE. Si por algun motivo no existiera la columna codigo_unico,
        // se hace un fallback al filtro por Nombre_Establecimiento.
        if (!empty($cols['codigo_unico'])) {
            $where[] = "`{$cols['codigo_unico']}` = :est";
            $params[':est'] = $filtros['establecimiento'];
        } else {
            $where[] = "`{$cols['establecimiento']}` = :est";
            $params[':est'] = $filtros['establecimiento'];
        }
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
 * Normaliza el codigo de tipo de edad del HIS al formato de ESNI_GRUPO_EDAD.
 *
 * La tabla consolidada HIS almacena Id_TipoEdad_Reg como codigo numerico:
 *   '1' = Dias, '2' = Meses, '3' = Anios
 * Pero ESNI_GRUPO_EDAD.tipo_edad usa letras:
 *   'D' = Dias, 'M' = Meses, 'A' = Anios
 *
 * Esta funcion mapea ambos formatos para permitir la comparacion correcta.
 */
function esniNormalizarTipoEdad(?string $tipEdad): ?string {
    if ($tipEdad === null || $tipEdad === '') return null;
    $tipEdad = strtoupper(trim($tipEdad));
    // Si ya esta en formato letra, devolver tal cual
    if (in_array($tipEdad, ['D', 'M', 'A'], true)) return $tipEdad;
    // Mapeo de codigo numerico HIS a letra ESNI
    $mapa = ['1' => 'D', '2' => 'M', '3' => 'A'];
    return $mapa[$tipEdad] ?? $tipEdad;
}

/**
 * Comprueba si un grupo de edad "encaja" con la edad de una fila HIS.
 *
 * IMPORTANTE: el filtrado por rango de edad se hace EXCLUSIVAMENTE en modo
 * numerico, usando Edad_Reg + Tipo_Edad de la tabla HIS, contra el rango
 * (edad_min / edad_max / tipo_edad) configurado en ESNI_GRUPO_EDAD (vinculado
 * a la regla mediante ESNI_REGLA.id_grupo_edad).
 *
 * La columna textual `Grupo_Edad` de la trama HIS se IGNORA para el filtrado
 * por rango: es solo informativa y su valor puede no coincidir con el esquema
 * ESNI. Por eso NO existe un "modo basico" basado en texto:
 *   - Si la regla NO define grupo de edad (id_grupo_edad NULL): encaja siempre
 *     (no hay restriccion de rango etario). Util SOLO para lineas que no
 *     dependen de un rango etario (p.ej. dosis unica global).
 *   - Si la regla SI define grupo de edad, la fila debe traer Edad_Reg y
 *     Tipo_Edad; si no los trae, NO encaja (no se cuenta una fila de edad
 *     desconocida dentro de un rango concreto).
 *   - Tipo_Edad de la fila (A=Anios, M=meses, D=dias) debe coincidir con el
 *     tipo_edad del grupo configurado; si no coincide, no encaja.
 *   - La edad numerica debe cumplir edad_min <= edad <= edad_max (si edad_max
 *     es NULL, solo se aplica el minimo).
 */
function esniEdadEncaja(array $regla, ?float $edadReg, ?string $tipEdad, ?string $grupoEdadTexto = null): bool {
    // Si la regla no tiene grupo_edad definido (id_grupo_edad NULL), encaja
    // siempre: no hay restriccion de rango etario.
    if (empty($regla['grupo_edad_codigo'])) return true;

    // Normalizar Tipo_Edad de la fila: acepta letra ESNI ('A','M','D') o el
    // codigo numerico HIS ('3'=Anios, '2'=Meses, '1'=Dias).
    $tipEdadNorm = esniNormalizarTipoEdad($tipEdad);

    // Si la fila no trae Edad_Reg y/o Tipo_Edad, no se puede comprobar el
    // rango: NO encaja (no se cuenta una fila de edad desconocida).
    if ($edadReg === null || $tipEdadNorm === null) return false;

    // El grupo configurado debe tener tipo_edad y edad_min definidos para que
    // el rango sea aplicable. Si por datos erroneos no los tiene, no encaja.
    if (empty($regla['ge_tipo_edad']) || $regla['ge_edad_min'] === null) return false;

    // El tipo de edad debe coincidir (Anios con Anios, Meses con Meses, ...).
    if ($tipEdadNorm !== $regla['ge_tipo_edad']) return false;

    // Validar rango numerico [edad_min, edad_max].
    if ($edadReg < $regla['ge_edad_min']) return false;
    if ($regla['ge_edad_max'] !== null && $edadReg > $regla['ge_edad_max']) return false;

    return true;
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

    // Comorbilidad: si alguna regla activa requiere/excluye comorbilidad, necesitamos
    // traer tambien las filas con cod_item=9999 para saber que pacientes la tienen.
    // El valor '9999' es el codigo de item HIS que marca condicion de comorbilidad.
    $usaComorbilidad = false;
    foreach ($reglas as $r) {
        if (!empty($r['requiere_comorbilidad']) || !empty($r['excluye_comorbilidad'])) {
            $usaComorbilidad = true;
            break;
        }
    }
    $codComorbilidad = '9999';
    if ($usaComorbilidad && !in_array($codComorbilidad, $codItems, true)) {
        $codItems[] = $codComorbilidad;
    }

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
    foreach (['valor_lab', 'edad_reg', 'tip_edad', 'grupo_edad', 'sexo', 'id_paciente', 'id_cita', 'id_gruporiesgo', 'id_etnia'] as $c) {
        $selCols[] = !empty($cols[$c]) ? "`{$cols[$c]}` AS {$c}" : "NULL AS {$c}";
    }
    // AnioMes: si existe la columna, usarla; si no, construir a partir de Anio+Mes (formato YYYYMM)
    if (!empty($cols['aniomes'])) {
        $selCols[] = "`{$cols['aniomes']}` AS aniomes";
    } elseif (!empty($cols['anio']) && !empty($cols['mes'])) {
        $selCols[] = "CONCAT(`{$cols['anio']}`, LPAD(TRIM(CAST(`{$cols['mes']}` AS CHAR)), 2, '0')) AS aniomes";
    } else {
        $selCols[] = "NULL AS aniomes";
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

    // Comorbilidad: construir conjunto de id_paciente que tienen al menos una
    // fila con cod_item=9999. Se usa para evaluar requiere/excluye_comorbilidad.
    //
    // IMPORTANTE: se construye el WHERE DESDE CERO llamando a
    // esniConstruirWhereFiltros() en lugar de modificar el WHERE principal
    // con regex. Esto evita que un regex fragil sobre el IN (...) corrompa
    // el SQL y haga que la consulta falle silenciosamente, dejando
    // $pacientesConComorbilidad vacio (lo que causaria 0 en ambas lineas).
    //
    // Ademas, NO se incluye el filtro I_ROWNUM_LAB=1 porque los registros
    // de comorbilidad (cod_item=9999) pueden tener I_ROWNUM_LAB > 1.
    $pacientesConComorbilidad = [];
    if ($usaComorbilidad && !empty($cols['id_paciente']) && !empty($cols['cod_item'])) {
        try {
            // Construir WHERE comun (anio, mes, EE.SS., departamento, profesional, id_ups)
            [$whereComorb, $paramsComorb] = esniConstruirWhereFiltros($cols, $filtros);
            // Agregar SOLO el filtro cod_item = 9999 (sin IN ni I_ROWNUM_LAB)
            $whereComorb .= " AND `{$cols['cod_item']}` = :cod_comorb";
            $paramsComorb[':cod_comorb'] = $codComorbilidad;

            $sqlComorb = "SELECT DISTINCT `{$cols['id_paciente']}` AS id_paciente "
                       . "FROM `{$tabla}` WHERE {$whereComorb}";
            $stmtComorb = $pdo->prepare($sqlComorb);
            $stmtComorb->execute($paramsComorb);
            $comorbRows = $stmtComorb->fetchAll(PDO::FETCH_ASSOC);
            foreach ($comorbRows as $cr) {
                $idPac = isset($cr['id_paciente']) && $cr['id_paciente'] !== null && $cr['id_paciente'] !== ''
                    ? (string)$cr['id_paciente'] : null;
                if ($idPac !== null) {
                    $pacientesConComorbilidad[$idPac] = true;
                }
            }
        } catch (Throwable $e) {
            // Si falla la consulta de comorbilidad, loguear pero continuar.
            // $pacientesConComorbilidad queda vacio:
            //   - Lineas CON comorbilidad (requiere=1) no contaran nada (correcto).
            //   - Lineas SIN comorbilidad (excluye=1) contaran a todos (tolerante).
        }
    }

    // Filtro por valor_lab en misma Id_cita (seccion J - Hepatitis B Adulto):
    // construir conjuntos de Id_cita que tienen al menos una fila con cada
    // valor_lab marcador requerido (G=gestante, ST=personal salud, etc).
    //
    // Esto permite evaluar requiere_valor_lab_cita / excluye_valor_lab_cita
    // de forma eficiente: en lugar de hacer una subconsulta por fila, hacemos
    // UNA sola consulta por marcador y almacenamos el conjunto de Id_cita.
    //
    // La consulta NO filtra por cod_item: el marcador puede estar en cualquier
    // fila de la cita (tipicamente en una fila con el mismo cod_item que la
    // dosis, pero podria estar en otra). Tampoco filtra por I_ROWNUM_LAB=1
    // porque los marcadores pueden tener rownum > 1.
    $citasConValorLab = []; // ['G' => [id_cita => true], 'ST' => [...], ...]
    $marcadoresRequeridos = [];
    foreach ($reglas as $r) {
        if (!empty($r['requiere_valor_lab_cita'])) {
            // Soporte para valor unico (ej: 'G')
            $marcadoresRequeridos[strtoupper(trim($r['requiere_valor_lab_cita']))] = true;
        }
        if (!empty($r['excluye_valor_lab_cita'])) {
            // Soporte para multiples valores separados por coma (ej: 'G,ST')
            // Cada marcador individual se agrega al conjunto para construir
            // los conjuntos de Id_cita correspondientes.
            $parts = array_map('strtoupper', array_map('trim', explode(',', $r['excluye_valor_lab_cita'])));
            foreach ($parts as $p) {
                if ($p !== '') $marcadoresRequeridos[$p] = true;
            }
        }
    }
    if (!empty($marcadoresRequeridos) && !empty($cols['id_cita']) && !empty($cols['valor_lab'])) {
        foreach (array_keys($marcadoresRequeridos) as $marcador) {
            try {
                [$whereMarc, $paramsMarc] = esniConstruirWhereFiltros($cols, $filtros);
                // Filtro por valor_lab = marcador (case-insensitive via UPPER)
                $whereMarc .= " AND UPPER(TRIM(`{$cols['valor_lab']}`)) = :vl_marc";
                $paramsMarc[':vl_marc'] = $marcador;

                $sqlMarc = "SELECT DISTINCT `{$cols['id_cita']}` AS id_cita "
                         . "FROM `{$tabla}` WHERE {$whereMarc}";
                $stmtMarc = $pdo->prepare($sqlMarc);
                $stmtMarc->execute($paramsMarc);
                $marcRows = $stmtMarc->fetchAll(PDO::FETCH_ASSOC);
                $setMarc = [];
                foreach ($marcRows as $mr) {
                    $idCit = isset($mr['id_cita']) && $mr['id_cita'] !== null && $mr['id_cita'] !== ''
                        ? (string)$mr['id_cita'] : null;
                    if ($idCit !== null) {
                        $setMarc[$idCit] = true;
                    }
                }
                $citasConValorLab[$marcador] = $setMarc;
            } catch (Throwable $e) {
                // Si falla la consulta del marcador, tratarlo como conjunto vacio.
                // Esto hara que las reglas con requiere_valor_lab_cita='X'
                // cuenten 0 (correcto: no hay citas con ese marcador), y las
                // reglas con excluye_valor_lab_cita='X' cuenten a todos
                // (tolerante: no podemos confirmar exclusion).
                $citasConValorLab[$marcador] = [];
            }
        }
    }


    // Recorrer filas y aplicar motor de reglas
    foreach ($filas as $f) {
        $cod = $f['cod_item'] ?? null;
        if ($cod === null) continue;
        $cod = trim((string)$cod);
        // Las filas con cod_item=9999 solo se usan como marcador de comorbilidad,
        // no se cuentan como dosis administrada en ninguna linea del reporte.
        if ($cod === $codComorbilidad) continue;
        if (!isset($reglasPorCod[$cod])) continue;

        $valorLabRaw = isset($f['valor_lab']) ? (string)$f['valor_lab'] : null;
        $valorLab = ($valorLabRaw !== null && $valorLabRaw !== '') ? strtoupper(trim($valorLabRaw)) : null;
        $edadReg  = isset($f['edad_reg'])  && $f['edad_reg']  !== null ? (float)$f['edad_reg']  : null;
        $tipEdad  = isset($f['tip_edad'])  && $f['tip_edad']  !== null ? (string)$f['tip_edad'] : null;
        $grupoEd  = isset($f['grupo_edad']) && $f['grupo_edad'] !== null ? (string)$f['grupo_edad'] : null;
        $sexoFila = isset($f['sexo']) && $f['sexo'] !== null ? strtoupper(trim((string)$f['sexo'])) : null;
        $aniomes  = isset($f['aniomes']) && $f['aniomes'] !== null ? (string)$f['aniomes'] : null;
        $idRiesgo = isset($f['id_gruporiesgo']) && $f['id_gruporiesgo'] !== null ? (string)$f['id_gruporiesgo'] : null;
        $idPaciente = isset($f['id_paciente']) && $f['id_paciente'] !== null && $f['id_paciente'] !== ''
            ? (string)$f['id_paciente'] : null;
        $idCita = isset($f['id_cita']) && $f['id_cita'] !== null && $f['id_cita'] !== ''
            ? (string)$f['id_cita'] : null;
        $idEtnia = isset($f['id_etnia']) && $f['id_etnia'] !== null && $f['id_etnia'] !== ''
            ? trim((string)$f['id_etnia']) : null;

        // Conteo dual: una fila puede contar en una linea "normal" (grupo etareo)
        // Y TAMBIEN en una linea "especializada" (requiere_valor_lab_cita, ej:
        // Personal de Salud o Gestantes). Esto permite que la seccion J muestre
        // cuantas dosis aplicaron a personal de salud sin quitarlas del conteo
        // del grupo etareo al que pertenecen (fines informativos).
        //
        // CONTEO POR-SECCION (no global):
        // Los flags matchedNormal / matchedEspecializada se trackean POR
        // id_seccion, no de forma global. Esto permite que una misma fila HIS
        // cuente en multiples secciones del reporte (por ejemplo, una dosis de
        // Hepatitis A aplicada a un nino de 1 anio cuenta en la seccion B
        // "DE 01 ANIO" y TAMBIEN en la seccion R "HEPATITIS A - Vacuna de
        // 1 a 5"), respetando el caracter informativo/paralelo de ciertas
        // secciones (R, Q, etc.) que consolidan una vacuna por rango etario.
        // Dentro de una misma seccion, la fila sigue contando como maximo una
        // vez en linea normal y una vez en linea especializada (comportamiento
        // original).
        $matchedNormalPorSeccion        = []; // [id_seccion => true]
        $matchedEspecializadaPorSeccion = []; // [id_seccion => true]

        foreach ($reglasPorCod[$cod] as $r) {
            // Conteo dual por-seccion: skip reglas del tipo que ya tuvo match
            // PARA LA SECCION ACTUAL. Otras secciones siguen siendo evaluables.
            $esEspecializada = !empty($r['requiere_valor_lab_cita']);
            if ($esEspecializada && !empty($matchedEspecializadaPorSeccion[$r['id_seccion']])) continue;
            if (!$esEspecializada && !empty($matchedNormalPorSeccion[$r['id_seccion']])) continue;
            // 1) Valor lab. Si la regla no especifica valor_lab (NULL o vacio), encaja con cualquier valor.
            //    Si la regla SI especifica valor_lab, encaja solo si coincide exactamente (case-insensitive).
            //    Adicionalmente, si el valor_lab de la fila es NULL, intentamos treatarlo como 'DU'
            //    (Dosis Unica) que es el caso tipico cuando la vacuna es de dosis unica pero no se registro el lab.
            if (!esniValorLabEncaja($r['valor_lab'], $valorLab)) {
                // Fallback: si la fila no tiene valor_lab, intentar con 'DU'
                if ($valorLab === null && esniValorLabEncaja($r['valor_lab'], 'DU')) {
                    // ok, sigue evaluando
                } else {
                    continue;
                }
            }

            // 2) Sexo. Si la regla pide Mujer (F) o Varon (M), la fila debe
            //    tener Id_Genero y coincidir. Una fila sin sexo no se cuenta
            //    en una linea que exige un sexo concreto (evita inflar la
            //    seccion F "mujeres" con filas sin Id_Genero).
            if ($r['sexo'] !== 'A') {
                if ($sexoFila === null || $sexoFila !== strtoupper($r['sexo'])) continue;
            }

            // 3) Rango aniomes (solo si tenemos aniomes calculado)
            if (!empty($r['aniomes_min']) && $aniomes !== null && strcmp($aniomes, $r['aniomes_min']) < 0) continue;
            if (!empty($r['aniomes_max']) && $aniomes !== null && strcmp($aniomes, $r['aniomes_max']) > 0) continue;

            // 4) Grupo de edad
            if (!empty($r['grupo_edad_codigo']) && !esniEdadEncaja($r, $edadReg, $tipEdad, $grupoEd)) continue;

            // 5) Requiere/excluye riesgo
            if ($r['requiere_riesgo'] == 1 && $idRiesgo !== '2') continue;
            if ($r['excluye_riesgo'] == 1 && $idRiesgo === '2') continue;

            // 6) Requiere/excluye comorbilidad (cod_item=9999 en otro registro del mismo paciente)
            if (!empty($r['requiere_comorbilidad'])) {
                if ($idPaciente === null || !isset($pacientesConComorbilidad[$idPaciente])) continue;
            }
            if (!empty($r['excluye_comorbilidad'])) {
                if ($idPaciente !== null && isset($pacientesConComorbilidad[$idPaciente])) continue;
            }

            // 7) Requiere/excluye valor_lab en la misma Id_cita.
            //    Usado por la seccion J (Hepatitis B Adulto, cod_item=90746)
            //    para distinguir No Gestantes / Gestantes / Personal de Salud
            //    segun exista o no en la misma cita una fila marcadora con
            //    valor_lab='G' (gestante) o 'ST' (personal de salud).
            //
            //    Requiere: si la regla exige marcador 'X', la cita debe
            //    aparecer en el conjunto $citasConValorLab['X']. Si la cita
            //    no tiene Id_cita (NULL), no se puede confirmar -> no encaja.
            //
            //    Excluye: si la regla excluye marcador 'Y', la cita NO debe
            //    aparecer en $citasConValorLab['Y']. Si la cita no tiene
            //    Id_cita (NULL), se asume que no tiene el marcador -> encaja
            //    (tolerante: cuenta la fila como no-gestante por defecto).
            if (!empty($r['requiere_valor_lab_cita'])) {
                $marcReq = strtoupper(trim($r['requiere_valor_lab_cita']));
                if ($idCita === null) continue;
                $setReq = $citasConValorLab[$marcReq] ?? [];
                if (!isset($setReq[$idCita])) continue;
            }
            if (!empty($r['excluye_valor_lab_cita'])) {
                // Soporte para multiples valores separados por coma (ej: 'G,ST')
                // La fila se excluye si la cita tiene CUALQUIERA de los marcadores.
                $marcExclList = array_map('strtoupper', array_map('trim', explode(',', $r['excluye_valor_lab_cita'])));
                $excluida = false;
                foreach ($marcExclList as $marcExc) {
                    if ($marcExc === '') continue;
                    $setExc = $citasConValorLab[$marcExc] ?? [];
                    if ($idCita !== null && isset($setExc[$idCita])) {
                        $excluida = true;
                        break;
                    }
                }
                if ($excluida) continue;
            }

            // 8) Excluye etnia (Id_Etnia).
            //    Si la regla tiene excluye_etnia definido (lista de Id_Etnia separados
            //    por coma, ej: '56,57,58,59,60'):
            //      - Si Id_Etnia es NULL → la fila NO se cuenta (semantica SQL:
            //        NULL NOT IN (...) es falso, no se puede confirmar que no
            //        pertenece a las etnias excluidas).
            //      - Si Id_Etnia tiene un valor que esta en la lista excluida →
            //        la fila NO se cuenta.
            //      - Si Id_Etnia tiene un valor que NO esta en la lista →
            //        la fila SI se cuenta.
            //    Si excluye_etnia es NULL o vacio → no se aplica ningun filtro
            //    de etnia, solo los demas filtros registrados.
            //    Uso principal: linea "COMUNIDADES NATIVAS" en seccion H del ESNI,
            //    que debe contar solo pacientes con Id_Etnia <> 56,57,58,59,60.
            if (!empty($r['excluye_etnia'])) {
                // Id_Etnia NULL → no se puede confirmar exclusion → no encaja (estricto)
                if ($idEtnia === null) continue;
                $etniasExcluidas = array_map('trim', explode(',', $r['excluye_etnia']));
                if (in_array($idEtnia, $etniasExcluidas, true)) continue;
            }

            // Match!
            $contadores[$r['id_linea']]++;

            // Conteo dual por-seccion: $esEspecializada ya se calculo al inicio
            // del loop. Permitimos que una fila cuente en AMBOS tipos (normal +
            // especializada) DENTRO DE LA MISMA SECCION, pero solo una vez por
            // tipo por seccion (la primera regla que encaja en cada).
            //
            // Adicionalmente, como los flags son por-seccion, la fila puede
            // volver a contar en otras secciones (R, Q, etc.) que consolidan
            // la misma vacuna por rango etario con fines informativos.
            if ($esEspecializada) {
                $matchedEspecializadaPorSeccion[$r['id_seccion']] = true;
            } else {
                $matchedNormalPorSeccion[$r['id_seccion']] = true;
            }

            // Nota: anteriormente habia un `break` cuando hubo match en ambos
            // tipos de forma global. Con el conteo por-seccion ese break se
            // elimina porque debemos seguir evaluando reglas de otras secciones
            // (la misma fila puede contar en B y en R). El `continue` de arriba
            // ya filtra eficientemente las reglas redundantes de la misma
            // seccion, por lo que el costo de iterar es minimo (las reglas por
            // cod_item suelen ser pocas, <= 20).
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
 *
 * @param PDO    $pdo
 * @param array  $cols   Mapa de columnas resueltas (de esniResolverColumnas)
 * @param string|null $idUps  Si se indica, filtra por Id_Ups (estrategia ESNI = 301204)
 * @return array
 */
function esniGetAniosDisponibles(PDO $pdo, array $cols, ?string $idUps = null): array {
    if (!$cols['anio']) return [date('Y')];
    try {
        $tabla = $cols['_tabla'];
        $where = ["`{$cols['anio']}` IS NOT NULL"];
        $params = [];
        // Filtro de estrategia: solo anios de la estrategia indicada (p.ej. ESNI=301204)
        if ($idUps !== null && $idUps !== '' && !empty($cols['id_ups'])) {
            $where[] = "`{$cols['id_ups']}` = :id_ups";
            $params[':id_ups'] = $idUps;
        }
        $sql = "SELECT DISTINCT `{$cols['anio']}` AS anio FROM `{$tabla}` WHERE " . implode(' AND ', $where) . " ORDER BY anio DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $r ?: [date('Y')];
    } catch (Throwable $e) {
        return [date('Y')];
    }
}

/**
 * Obtiene lista de establecimientos.
 *
 * @param PDO    $pdo
 * @param array  $cols
 * @param string|null $idUps  Si se indica, filtra por Id_Ups (estrategia ESNI = 301204)
 * @return array
 */
function esniGetEstablecimientos(PDO $pdo, array $cols, ?string $idUps = null): array {
    if (!$cols['establecimiento']) return [];
    try {
        $tabla = $cols['_tabla'];
        $where = ["`{$cols['establecimiento']}` IS NOT NULL"];
        $params = [];
        if ($idUps !== null && $idUps !== '' && !empty($cols['id_ups'])) {
            $where[] = "`{$cols['id_ups']}` = :id_ups";
            $params[':id_ups'] = $idUps;
        }
        $sql = "SELECT DISTINCT `{$cols['establecimiento']}` AS v FROM `{$tabla}` WHERE " . implode(' AND ', $where) . " ORDER BY v LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Obtiene la lista de establecimientos desde la tabla ZSPERENE (catalogo).
 *
 * A diferencia de esniGetEstablecimientos() (que hace un DISTINCT sobre la gran
 * tabla consolidada HIS, operacion lenta que saturaba la base de datos al cargar
 * la pagina), esta funcion lee el catalogo ZSPERENE, que es pequeno y rapido.
 *
 * Devuelve un array asociativo: [ Codigo_Unico => Nombre_Establecimiento ].
 * El Codigo_Unico se usa como valor del <option> para filtrar el reporte por
 * la columna Codigo_Unico de la tabla T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO,
 * que coincide con el Codigo_Unico de ZSPERENE.
 */
function esniGetEstablecimientosZS(PDO $pdo): array {
    try {
        $sql = "SELECT Codigo_Unico, Nombre_Establecimiento
                FROM ZSPERENE
                WHERE Codigo_Unico IS NOT NULL
                  AND Nombre_Establecimiento IS NOT NULL
                  AND TRIM(Nombre_Establecimiento) <> ''
                ORDER BY Nombre_Establecimiento";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[trim($r['Codigo_Unico'])] = trim($r['Nombre_Establecimiento']);
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Obtiene lista de departamentos.
 *
 * @param PDO    $pdo
 * @param array  $cols
 * @param string|null $idUps  Si se indica, filtra por Id_Ups (estrategia ESNI = 301204)
 * @return array
 */
function esniGetDepartamentos(PDO $pdo, array $cols, ?string $idUps = null): array {
    if (!$cols['departamento']) return [];
    try {
        $tabla = $cols['_tabla'];
        $where = ["`{$cols['departamento']}` IS NOT NULL"];
        $params = [];
        if ($idUps !== null && $idUps !== '' && !empty($cols['id_ups'])) {
            $where[] = "`{$cols['id_ups']}` = :id_ups";
            $params[':id_ups'] = $idUps;
        }
        $sql = "SELECT DISTINCT `{$cols['departamento']}` AS v FROM `{$tabla}` WHERE " . implode(' AND ', $where) . " ORDER BY v LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Obtiene lista de profesionales.
 *
 * @param PDO    $pdo
 * @param array  $cols
 * @param string|null $idUps  Si se indica, filtra por Id_Ups (estrategia ESNI = 301204)
 * @return array
 */
function esniGetProfesionales(PDO $pdo, array $cols, ?string $idUps = null): array {
    if (!$cols['profesional']) return [];
    try {
        $tabla = $cols['_tabla'];
        $where = ["`{$cols['profesional']}` IS NOT NULL"];
        $params = [];
        if ($idUps !== null && $idUps !== '' && !empty($cols['id_ups'])) {
            $where[] = "`{$cols['id_ups']}` = :id_ups";
            $params[':id_ups'] = $idUps;
        }
        $sql = "SELECT DISTINCT `{$cols['profesional']}` AS v FROM `{$tabla}` WHERE " . implode(' AND ', $where) . " ORDER BY v LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
 *
 * Comorbilidad: cuando requiereComorbilidad=1 la regla solo encaja si el
 * paciente tiene ademas otro registro con cod_item=9999. Cuando
 * excluyeComorbilidad=1 la regla solo encaja si el paciente NO tiene ningun
 * registro con cod_item=9999. Esto permite distinguir lineas como:
 *   - Influenza sin Comorbilidad  (excluye_comorbilidad=1)
 *   - Influenza con Comorbilidad  (requiere_comorbilidad=1)
 *   - Neumococo sin Comorbilidad  (excluye_comorbilidad=1)
 *   - Neumococo con Comorbilidad  (requiere_comorbilidad=1)
 *
 * Filtro por valor_lab en misma Id_cita (requerido por seccion J - Hepatitis B
 * Adulto, cod_item=90746): cuando requiereValorLabCita no es null, la regla
 * solo encaja si existe OTRA fila con la misma Id_cita cuyo valor_lab sea
 * igual a este valor. Tipicos:
 *   'G'  = la cita corresponde a una gestante
 *   'ST' = la cita corresponde a personal de salud
 * Cuando excluyeValorLabCita no es null, la regla solo encaja si NO existe
 * ninguna otra fila con la misma Id_cita cuyo valor_lab sea igual a este
 * valor. Tipico:
 *   'G'  = la cita NO es de gestante (no gestante)
 *
 * Estos 2 nuevos campos se agregan a ESNI_REGLA mediante el script
 * Database/migration_seccion_j_covid.sql. La funcion detecta dinamicamente
 * si las columnas existen; si no existen aun, los valores se ignoran
 * silenciosamente (retrocompatibilidad).
 *
 * Filtro por etnia (excluyeEtnia): lista de Id_Etnia separados por coma
 * que se deben excluir del conteo. Ej: '56,57,58,59,60' para la linea
 * "COMUNIDADES NATIVAS" de la seccion H. Agregado por
 * Database/migration_comunidades_nativas_etnia.sql.
 */
function esniCrearRegla(PDO $pdo, int $idLinea, string $codItem, ?string $valorLab = null, ?int $idGrupoEdad = null, string $sexo = 'A', ?string $aniomesMin = null, ?string $aniomesMax = null, int $requiereRiesgo = 0, int $excluyeRiesgo = 0, int $requiereComorbilidad = 0, int $excluyeComorbilidad = 0, ?string $requiereValorLabCita = null, ?string $excluyeValorLabCita = null, ?string $excluyeEtnia = null): int {
    // Detectar si las columnas nuevas existen (migration aplicada).
    static $tieneColsCita = null;
    static $tieneColEtnia = null;
    if ($tieneColsCita === null) {
        $tieneColsCita = esniColumnasReglaExisten($pdo, ['requiere_valor_lab_cita', 'excluye_valor_lab_cita']);
    }
    if ($tieneColEtnia === null) {
        $tieneColEtnia = esniColumnasReglaExisten($pdo, ['excluye_etnia']);
    }

    if ($tieneColsCita && $tieneColEtnia) {
        $sql = "INSERT INTO ESNI_REGLA
                (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, aniomes_min, aniomes_max,
                 requiere_riesgo, excluye_riesgo, requiere_comorbilidad, excluye_comorbilidad,
                 requiere_valor_lab_cita, excluye_valor_lab_cita, excluye_etnia)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idLinea, $codItem, $valorLab, $idGrupoEdad, $sexo, $aniomesMin, $aniomesMax,
                        $requiereRiesgo, $excluyeRiesgo, $requiereComorbilidad, $excluyeComorbilidad,
                        $requiereValorLabCita, $excluyeValorLabCita, $excluyeEtnia]);
    } elseif ($tieneColsCita) {
        $sql = "INSERT INTO ESNI_REGLA
                (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, aniomes_min, aniomes_max,
                 requiere_riesgo, excluye_riesgo, requiere_comorbilidad, excluye_comorbilidad,
                 requiere_valor_lab_cita, excluye_valor_lab_cita)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idLinea, $codItem, $valorLab, $idGrupoEdad, $sexo, $aniomesMin, $aniomesMax,
                        $requiereRiesgo, $excluyeRiesgo, $requiereComorbilidad, $excluyeComorbilidad,
                        $requiereValorLabCita, $excluyeValorLabCita]);
    } else {
        // Migracion no aplicada: insertar sin las columnas nuevas.
        $sql = "INSERT INTO ESNI_REGLA
                (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, aniomes_min, aniomes_max,
                 requiere_riesgo, excluye_riesgo, requiere_comorbilidad, excluye_comorbilidad)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idLinea, $codItem, $valorLab, $idGrupoEdad, $sexo, $aniomesMin, $aniomesMax,
                        $requiereRiesgo, $excluyeRiesgo, $requiereComorbilidad, $excluyeComorbilidad]);
    }
    return (int)$pdo->lastInsertId();
}

/**
 * Verifica si una o varias columnas existen en la tabla ESNI_REGLA.
 * Util para detectar si la migracion (migration_seccion_j_covid.sql) ya
 * se ejecuto, evitando errores SQL cuando las columnas nuevas todavia no
 * existen. Devuelve true solo si TODAS las columnas pasadas existen.
 */
function esniColumnasReglaExisten(PDO $pdo, array $columnas): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `ESNI_REGLA`");
        $stmt->execute();
        $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $set = array_map('strtolower', $existentes);
        foreach ($columnas as $c) {
            if (!in_array(strtolower($c), $set, true)) return false;
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
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

/**
 * Diagnostica la cobertura de reglas ESNI contra los datos reales de la tabla origen.
 *
 * Devuelve un array con:
 *   - 'cod_items_con_reglas'   : cod_items que estan en ESNI_REGLA
 *   - 'cod_items_en_datos'     : cod_items que aparecen en la tabla origen (con conteo)
 *   - 'cod_items_sin_reglas'   : cod_items en datos pero SIN reglas (potencialmente perdidos)
 *   - 'reglas_sin_datos'       : cod_items en reglas pero SIN datos reales (reglas inutiles)
 *   - 'total_filas_datos'      : total de filas HIS leidas
 *   - 'filas_cubiertas'        : filas HIS cuyo cod_item tiene al menos una regla
 *   - 'porcentaje_cobertura'   : filas_cubiertas / total_filas_datos * 100
 */
function esniDiagnosticarReglas(PDO $pdo, array $cols, ?array $filtros = null): array {
    $diag = [
        'cod_items_con_reglas'  => [],
        'cod_items_en_datos'    => [],
        'cod_items_sin_reglas'  => [],
        'reglas_sin_datos'      => [],
        'total_filas_datos'     => 0,
        'filas_cubiertas'       => 0,
        'porcentaje_cobertura'  => 0.0,
        'tabla_origen'          => $cols['_tabla'] ?? '?',
    ];

    // 1) cod_items en reglas
    try {
        $stmt = $pdo->query("SELECT DISTINCT cod_item FROM ESNI_REGLA WHERE activo=1");
        $diag['cod_items_con_reglas'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return $diag;
    }

    if (!$cols['cod_item']) return $diag;
    $tabla = $cols['_tabla'];

    // 2) cod_items en datos (aplicando los mismos filtros comunes si se pasan)
    try {
        [$where, $params] = esniConstruirWhereFiltros($cols, $filtros ?? []);
        $sql = "SELECT `{$cols['cod_item']}` AS cod, COUNT(*) AS n
                FROM `{$tabla}`
                WHERE {$where}
                GROUP BY `{$cols['cod_item']}`
                ORDER BY n DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $total = 0;
        $cubiertas = 0;
        $conReglaSet = array_fill_keys($diag['cod_items_con_reglas'], true);
        foreach ($rows as $r) {
            $cod = trim((string)($r['cod'] ?? ''));
            $n = (int)$r['n'];
            $diag['cod_items_en_datos'][] = ['cod' => $cod, 'n' => $n];
            $total += $n;
            if (isset($conReglaSet[$cod])) $cubiertas += $n;
        }
        $diag['total_filas_datos'] = $total;
        $diag['filas_cubiertas']   = $cubiertas;
        $diag['porcentaje_cobertura'] = $total > 0 ? round($cubiertas * 100.0 / $total, 2) : 0.0;

        // 3) cod_items en datos sin reglas (solo los que tienen > 0 filas)
        foreach ($diag['cod_items_en_datos'] as $r) {
            if (!isset($conReglaSet[$r['cod']])) {
                $diag['cod_items_sin_reglas'][] = $r;
            }
        }
        // 4) reglas sin datos
        $enDatosSet = [];
        foreach ($diag['cod_items_en_datos'] as $r) $enDatosSet[$r['cod']] = true;
        foreach ($diag['cod_items_con_reglas'] as $cod) {
            if (!isset($enDatosSet[$cod])) {
                $diag['reglas_sin_datos'][] = $cod;
            }
        }
    } catch (Throwable $e) {
        $diag['error'] = $e->getMessage();
    }

    return $diag;
}
