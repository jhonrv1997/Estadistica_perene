<?php
/**
 * Sistema de Gestion de Datos HIS
 * Modulo CANCER - Motor de reporte data-driven (includes/cancer_data.php)
 *
 * Reemplaza el flujo manual del Modulo de Cancer:
 *   SQL Server: ejecutar "01 Creacion tablas iniciales"
 *   SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *   SQL Server: ejecutar "03 Creacion de Procedimientos"
 *   Excel:      abrir "Reporte_Actividades_Cancer.xlsx" y refrescar ODBC
 *
 * Ahora TODO se hace desde la web (reporte_cancer.php):
 *   1) El usuario pulsa "Generar Reporte" (1 click).
 *   2) Este motor adapta la logica de los 8 Stored Procedures del archivo
 *      "03 Creacion de Procedimientos.txt" y la ejecuta directamente contra
 *      la tabla consolidada MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
 *      (la misma que usa el modulo ESNI), aplicando los filtros seleccionados.
 *   3) Se muestran las secciones con el mismo layout del Excel oficial
 *      "Reporte_Actividades_Cancer.xlsx".
 *   4) Boton "Exportar Excel" -> llena la plantilla oficial (cancer_export.php).
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita        -> Id_Cita
 *   renaes         -> Codigo_Unico
 *   id_persona     -> Id_Paciente
 *   aniomes        -> Anio + Mes
 *   fichafam       -> Ficha_Familiar
 *   ubigeo         -> Ubigueo_Establecimiento
 *   edad_reg       -> Edad_Reg
 *   id_tipedad_reg -> Tipo_Edad            ('D','M','A')
 *   id_genero      -> Id_Genero            ('F','M')
 *   id_etnia (et)  -> Id_Etnia
 *   id_financiador -> Id_Financiador
 *   id_profesional -> Id_Personal
 *   pais           -> Id_Pais
 *   id_ups         -> Id_Ups
 *   id_tipitem     -> Tipo_Diagnostico     ('D','P','R','M')  [adaptacion]
 *   cod_item       -> Codigo_Item
 *   valor_lab      -> Valor_Lab
 *   I_ROWNUM_LAB   -> Id_Correlativo_Lab   [adaptacion]
 *   fg_tipo        -> Fg_Tipo              ('CX','DX','PX',...)
 *   cod_item_f     -> Codigo_Item (la fila CIE referida existe como fila propia
 *                     en la trama; se busca por prefijo CIE en la misma cita)
 *
 * PROCEDIMIENTOS ADAPTADOS (archivo "03 Creacion de Procedimientos.txt"):
 *   RPT01_01_CUTERINO   -> seccion 1  MUJERES TAMIZADAS EN CANCER DE CUELLO UTERINO
 *   RPT01_02_CMAMA      -> seccion 2  MUJERES TAMIZADAS EN CANCER DE MAMA
 *   RPT01_04_OTROS      -> seccion 4  PERSONAS TAMIZADAS PARA LA DETECCION DE OTROS CANCER
 *   RPT02_02_LESIONES   -> seccion 6  PERSONAS ATENDIDAS CON LESIONES PRE MALIGNAS
 *   RPT04_01_CONSEJERIAS-> seccion 10 PERSONA CON CONSEJERIA PARA LA PREVENCION Y CONTROL
 *   RPT06_01_ATENCION   -> seccion 15 ATENDIDOS SEGUN TIPO DE CANCER
 *   RPT06_03_CANCER_ADULTOS -> seccion 17 ATENCIONES DE TODO TIPO DE CANCER ADULTO
 *   RPT07_01_DETECCION_CANCER -> seccion 19 DETECCION TEMPRANA DE CANCER INFANTIL
 *
 * NOTAS DE ADAPTACION (desviaciones documentadas respecto al T-SQL original):
 *   - RPT04_01 Temporal3 en el original asigna Consejeria 3/4 (duplicaria la linea
 *     "Telemedicina" preventiva); semantically corresponde a 7/8 (Telemedicina en
 *     pacientes diagnosticados). Se adapta a 7/8 para respetar el diseno del Excel.
 *   - Los rangos de codigo CIE (cod_item / cod_item_f) se evaluan por PREFIJO
 *     (ej. 'C50' casa con C50, C50X, C509...) para cubrir variantes de relleno 'X'
 *     y subcategorias CIE-10 presentes en la trama MySQL.
 *   - periodo >= '202601' del T-SQL se convierte en el filtro web de Anio/Mes.
 */

require_once __DIR__ . '/../config.php';

/* ============================================================
 * 1) PREDICADOS (mini-DSL que espeja las condiciones del T-SQL)
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 */
function cnrFila(array $r): array {
    return [
        'cita'    => $r['Id_Cita'] !== null ? trim((string)$r['Id_Cita']) : '',
        'pac'     => $r['Id_Paciente'] !== null ? trim((string)$r['Id_Paciente']) : '',
        'cod'     => $r['Codigo_Item'] !== null ? trim((string)$r['Codigo_Item']) : '',
        'tip'     => $r['Tipo_Diagnostico'] !== null ? trim((string)$r['Tipo_Diagnostico']) : '',
        'vl'      => $r['Valor_Lab'] !== null ? trim((string)$r['Valor_Lab']) : null,
        'rownum'  => $r['Id_Correlativo_Lab'] !== null ? (int)$r['Id_Correlativo_Lab'] : null,
        'sexo'    => $r['Id_Genero'] !== null ? trim((string)$r['Id_Genero']) : '',
        'edad'    => $r['Edad_Reg'] !== null ? (int)$r['Edad_Reg'] : null,
        'tipEdad' => $r['Tipo_Edad'] !== null ? trim((string)$r['Tipo_Edad']) : '',
        'fg'      => $r['Fg_Tipo'] !== null ? trim((string)$r['Fg_Tipo']) : '',
    ];
}

/**
 * Evalua un predicado del DSL contra una fila normalizada.
 *
 * Claves admitidas (se combinan con AND):
 *   'cod'       => ['88141'] | '88141'   Codigo_Item en la lista (exacto)
 *   'codPref'   => ['C50'] | 'C50'       Codigo_Item LIKE 'C50%'
 *   'codEntre'  => ['C000','C218']       BETWEEN de strings (como el T-SQL)
 *   'tip'       => 'D' | ['P','R']       Tipo_Diagnostico
 *   'vl'        => 'NULL' | ['N','A']    Valor_Lab (NULL = IS NULL)
 *   'rownum'    => 1                     Id_Correlativo_Lab = N
 *   'sexo'      => 'F' | 'M'             Id_Genero
 *   'edadA'     => [25, 64] | [65, null] Tipo_Edad='A' y Edad_Reg en rango
 *   'menor18'   => true                  Tipo_Edad in ('D','M') o (A y <18)
 *   'fgTipo'    => 'CX'                  Fg_Tipo
 *   'citaTiene' => <predicado>           otra fila de la misma cita lo cumple
 *   'citaTieneTodo' => [<pred>, ...]     todas existen en la cita (AND de EXISTS)
 *   'cualquieraDe' => [<pred>, ...]      OR de predicados sobre la misma fila
 *
 * $ctx es el contexto de ejecucion compartido por los predicados:
 *   'filas'   => lista de filas normalizadas (almacenadas UNA sola vez)
 *   'porCita' => [cita => [idx, ...]] indices enteros que apuntan a 'filas'
 * Se guardan indices (int) en lugar de copias de las filas para reducir a la
 * mitad el consumo de memoria (critico en hostings compartidos con
 * memory_limit bajo, donde antes se agotaba la RAM y salia HTTP 500).
 */
function cnrCumple(array $f, array $cond, array $ctx): bool {
    if (isset($cond['cod'])) {
        $cods = is_array($cond['cod']) ? $cond['cod'] : [$cond['cod']];
        if (!in_array($f['cod'], $cods, true)) return false;
    }
    if (isset($cond['codPref'])) {
        $prefs = is_array($cond['codPref']) ? $cond['codPref'] : [$cond['codPref']];
        $ok = false;
        foreach ($prefs as $p) {
            if ($p !== '' && strpos($f['cod'], $p) === 0) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    if (isset($cond['codEntre'])) {
        [$min, $max] = $cond['codEntre'];
        if ($f['cod'] === '' || $f['cod'] < $min || $f['cod'] > $max) return false;
    }
    if (isset($cond['tip'])) {
        $tips = is_array($cond['tip']) ? $cond['tip'] : [$cond['tip']];
        if (!in_array($f['tip'], $tips, true)) return false;
    }
    if (isset($cond['vl'])) {
        if ($cond['vl'] === 'NULL') {
            if ($f['vl'] !== null) return false;
        } else {
            $vals = is_array($cond['vl']) ? $cond['vl'] : [$cond['vl']];
            if ($f['vl'] === null || !in_array($f['vl'], $vals, true)) return false;
        }
    }
    if (isset($cond['rownum'])) {
        if ($f['rownum'] === null || $f['rownum'] !== (int)$cond['rownum']) return false;
    }
    if (isset($cond['sexo'])) {
        if ($f['sexo'] !== $cond['sexo']) return false;
    }
    if (isset($cond['edadA'])) {
        [$min, $max] = $cond['edadA'];
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return false;
        if ($f['edad'] < $min) return false;
        if ($max !== null && $f['edad'] > $max) return false;
    }
    if (!empty($cond['menor18'])) {
        if (!cnrEsMenor18($f)) return false;
    }
    if (isset($cond['fgTipo'])) {
        if ($f['fg'] !== $cond['fgTipo']) return false;
    }
    if (isset($cond['citaTiene'])) {
        if (!cnrCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!cnrCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        // OR de sub-predicados sobre la MISMA fila (ramas del WHERE T-SQL)
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (cnrCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** Tipo_Edad en ('D','M') o (A y edad < 18) - "menor de 18 anios". */
function cnrEsMenor18(array $f): bool {
    if ($f['tipEdad'] === 'D' || $f['tipEdad'] === 'M') return true;
    if ($f['tipEdad'] === 'A' && $f['edad'] !== null && $f['edad'] < 18) return true;
    return false;
}

/** EXISTS: alguna fila de la cita cumple el predicado (resuelve por indice). */
function cnrCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    foreach ($ctx['porCita'][$cita] as $idx) {
        if (cnrCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Devuelve la lista de indices candidatos para una condicion de nivel
 * superior usando buckets pre-indexados (codigo exacto y letra inicial).
 * Es una SOBRE-aproximacion: la verificacion exacta la hace cnrCumple().
 * Evita recorrer TODAS las filas por cada linea del reporte (antes:
 * ~150 lineas x N filas = millones de iteraciones que agotaban el tiempo
 * de ejecucion del hosting y provocaban el HTTP 500).
 */
function cnrCandidatos(array $cond, array $porCod, array $porIni, int $total): array {
    if ($total <= 0) return [];
    $sinCodigo = !isset($cond['cod']) && !isset($cond['codPref'])
              && !isset($cond['codEntre']) && !isset($cond['cualquieraDe']);
    if ($sinCodigo) {
        return range(0, $total - 1);
    }
    $out = [];
    if (isset($cond['cod'])) {
        foreach ((array)$cond['cod'] as $c) {
            foreach ($porCod[$c] ?? [] as $idx) $out[$idx] = true;
        }
        return array_keys($out);
    }
    if (isset($cond['codPref'])) {
        foreach ((array)$cond['codPref'] as $p) {
            if ($p === '') return range(0, $total - 1);
            $ini = strtoupper((string)$p[0]);
            foreach ($porIni[$ini] ?? [] as $idx) $out[$idx] = true;
        }
        return array_keys($out);
    }
    if (isset($cond['codEntre'])) {
        [$min, $max] = $cond['codEntre'];
        $iniMin = $min !== '' ? strtoupper((string)$min[0]) : '';
        $iniMax = $max !== '' ? strtoupper((string)$max[0]) : '';
        if ($iniMin !== '' && $iniMin === $iniMax && isset($porIni[$iniMin])) {
            return array_keys($porIni[$iniMin]);
        }
        return range(0, $total - 1);
    }
    // cualquieraDe: union de los candidatos de cada rama
    foreach ($cond['cualquieraDe'] as $sub) {
        foreach (cnrCandidatos($sub, $porCod, $porIni, $total) as $idx) $out[$idx] = true;
    }
    return array_keys($out);
}

/** Resuelve el grupo de edad (gedad) de una fila segun la definicion de la seccion. */
function cnrGedad(array $f, array $gedades): ?int {
    foreach ($gedades as $g) {
        if (!empty($g['menor18'])) {
            if (cnrEsMenor18($f)) return (int)$g['key'];
            continue;
        }
        // Columnas solo-estructura (ej. 'Gestantes'): no clasifican filas
        if (!isset($g['min'])) continue;
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) continue;
        $min = $g['min'];
        $max = $g['max'] ?? null;
        if ($f['edad'] >= $min && ($max === null || $f['edad'] <= $max)) {
            return (int)$g['key'];
        }
    }
    return null;
}

/* ============================================================
 * 2) DEFINICION DE SECCIONES (etiquetas de las dims del archivo 01
 *    + condiciones adaptadas del archivo 03)
 * ============================================================ */

/**
 * Devuelve la definicion completa de las secciones del reporte de Cancer.
 * Cada seccion: codigo, titulo, columnas de etiquetas, gedades, medidas y filas.
 */
function cancerSecciones(): array {
    $secciones = [

    /* ------------------------------------------------------------
     * SECCION 1 - RPT01_01_CUTERINO (us usp_TRAMA_BASE_CANCER_2026_RPT01_01_CUTERINO)
     * MUJERES TAMIZADAS EN CANCER DE CUELLO UTERINO (Dim Tamizaje01_01 / Gedad01)
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT01_01',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT01_01_CUTERINO',
        'titulo'   => 'MUJERES TAMIZADAS EN CANCER DE CUELLO UTERINO',
        'cabe1'    => 'TAMIZAJE', 'cabe2' => 'ACTIVIDAD', 'cabe3' => 'RESULTADO',
        'con_sexo' => false,   // solo mujeres (columnas sin M/F)
        'medidas'  => ['casos', 'personas'],
        'tot_label'=> 'TOTAL DE TAMIZAJES',
        'tot2_label' => 'TOTAL DE PERSONAS TAMIZADAS',
        'gedades'  => [
            ['key' => 1, 'label' => '25a-29a', 'min' => 25, 'max' => 29],
            ['key' => 2, 'label' => '30a-39a', 'min' => 30, 'max' => 39],
            ['key' => 3, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 4, 'label' => '50a-64a', 'min' => 50, 'max' => 64],
            ['key' => 'G', 'label' => 'Gestantes', 'cero' => true], // columna del Excel sin SP asociado
        ],
        'filas' => [
            ['clave' => 1,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Toma de muestra de Citologia', 'c3' => '----------',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64]]],
            ['clave' => 2,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Resultados de Citologia', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'N', 'sexo' => 'F', 'edadA' => [25, 64]]],
            ['clave' => 3,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Resultados de Citologia', 'c3' => 'Celulas escamosas y glandulares atipicas (ASCUS y AGC)',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'A', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => 'R876', 'tip' => 'P', 'rownum' => 1]]],
            ['clave' => 4,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Resultados de Citologia', 'c3' => 'Lesion Intraepitelial cervical de bajo grado (NIC I)',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'A', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => 'N870', 'tip' => 'P', 'rownum' => 1]]],
            ['clave' => 5,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Resultados de Citologia', 'c3' => 'Lesion Intraepitelial cervical de alto grado NIC II, NIC III y Cancer in situ',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'A', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => ['N871', 'N872', 'D069'], 'tip' => 'P', 'rownum' => 1]]],
            ['clave' => 6,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Resultados de Citologia', 'c3' => 'Cancer invasor del cuello uterino',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'A', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => 'C539', 'tip' => 'P', 'rownum' => 1]]],
            ['clave' => 7,  'c1' => 'CITOLOGIA - PAP', 'c2' => 'Telemedicina', 'c3' => 'Entrega de PAP con telemedicina',
             'cond' => ['tip' => 'D', 'cod' => '88141', 'vl' => 'N', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 8,  'c1' => 'INSPECCION VISUAL ACIDO ACETICO - IVAA', 'c2' => 'Persona examinada con Inspeccion Visual Acido Acetico', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => '88141.01', 'vl' => 'N', 'sexo' => 'F', 'edadA' => [30, 49]]],
            ['clave' => 9,  'c1' => 'INSPECCION VISUAL ACIDO ACETICO - IVAA', 'c2' => 'Persona examinada con Inspeccion Visual Acido Acetico', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => '88141.01', 'vl' => 'A', 'sexo' => 'F', 'edadA' => [30, 49]]],
            ['clave' => 10, 'c1' => 'VIRUS DEL PAPILOMA HUMANO - VPH', 'c2' => 'Toma de muestra de VPH', 'c3' => 'Por Proveedor',
             'cond' => ['tip' => 'D', 'cod' => '87621', 'vl' => '1', 'sexo' => 'F', 'edadA' => [30, 49]]],
            ['clave' => 11, 'c1' => 'VIRUS DEL PAPILOMA HUMANO - VPH', 'c2' => 'Toma de muestra de VPH', 'c3' => 'Auto Toma',
             'cond' => ['tip' => 'D', 'cod' => '87621', 'vl' => '2', 'sexo' => 'F', 'edadA' => [30, 49]]],
            ['clave' => 12, 'c1' => 'VIRUS DEL PAPILOMA HUMANO - VPH', 'c2' => 'Resultado VPH', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => '87621', 'vl' => 'N', 'sexo' => 'F', 'edadA' => [25, 64]]],
            ['clave' => 13, 'c1' => 'VIRUS DEL PAPILOMA HUMANO - VPH', 'c2' => 'Resultado VPH', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => '87621', 'vl' => 'A', 'sexo' => 'F', 'edadA' => [25, 64]]],
            ['clave' => 14, 'c1' => 'VIRUS DEL PAPILOMA HUMANO - VPH', 'c2' => 'Telemedicina', 'c3' => 'Entrega de VPH con telemedicina',
             'cond' => ['tip' => 'D', 'cod' => '87621', 'vl' => 'N', 'sexo' => 'F', 'edadA' => [30, 49],
                        'citaTiene' => ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 2 - RPT01_02_CMAMA (us usp_TRAMA_BASE_CANCER_2026_RPT01_02_CMAMA)
     * MUJERES TAMIZADAS EN CANCER DE MAMA (Dim Actividad01_02 / Gedad02)
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT01_02',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT01_02_CMAMA',
        'titulo'   => 'MUJERES TAMIZADAS EN CANCER DE MAMA',
        'cabe1'    => 'TAMIZAJE', 'cabe2' => 'ACTIVIDAD', 'cabe3' => 'RESULTADO',
        'con_sexo' => false,
        'medidas'  => ['casos', 'personas'],
        'tot_label'=> 'TOTAL DE TAMIZAJES',
        'tot2_label' => 'TOTAL DE PERSONAS TAMIZADAS',
        'gedades'  => [
            ['key' => 1, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 2, 'label' => '50a-59a', 'min' => 50, 'max' => 59],
            ['key' => 3, 'label' => '60a-69a', 'min' => 60, 'max' => 69],
        ],
        'filas' => [
            // EXAMEN CLINICO DE MAMAS (orden del Excel: Referidos, Negativo, Positivo)
            ['clave' => 3,  'c1' => 'EXAMEN CLINICO DE MAMAS', 'c2' => '----------', 'c3' => 'ECM Referidos',
             'cond' => ['tip' => 'D', 'cod' => ['Z123', 'Z0143', '99386.03'], 'vl' => 'A', 'sexo' => 'F', 'edadA' => [40, 69],
                        'citaTiene' => ['cod' => ['Z123', 'Z0143', '99386.03'], 'tip' => 'D', 'vl' => 'RF', 'rownum' => 2]]],
            ['clave' => 1,  'c1' => 'EXAMEN CLINICO DE MAMAS', 'c2' => '----------', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => ['Z123', 'Z0143', '99386.03'], 'vl' => 'N', 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 2,  'c1' => 'EXAMEN CLINICO DE MAMAS', 'c2' => '----------', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => ['Z123', 'Z0143', '99386.03'], 'vl' => 'A', 'sexo' => 'F', 'edadA' => [40, 69]]],
            // MAMOGRAFIA BILATERAL DE TAMIZAJE
            ['clave' => 4,  'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Indicacion de mamografia para tamizaje', 'c3' => '----------',
             'cond' => ['tip' => 'D', 'cod' => '77057', 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 5,  'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Toma de mamografia para tamizaje', 'c3' => '----------',
             'cond' => ['tip' => 'D', 'cod' => '77057', 'vl' => 'I', 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 6,  'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Evaluacion adicional (BI RADS 0)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '0', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 7,  'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Negativa (BI RADS 1)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '1', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 8,  'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Benigna (BI RADS 2)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '2', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 9,  'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Probablemente benigna (BI RADS 3)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '3', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 10, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Anormalidad sospechosa (BI RADS 4)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '4', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 11, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Altamente sugestiva de malignidad (BI RADS 5)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '5', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 12, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Lectura e interpretacion de imagenes de mamografia', 'c3' => 'Malignidad conocida (Bi RADS 6)',
             'cond' => ['tip' => 'D', 'cod' => '76140.04', 'vl' => '6', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 13, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Evaluacion adicional (BI RADS 0)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '0', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 14, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Negativa (BI RADS 1)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '1', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 15, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Benigna (BI RADS 2)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '2', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 16, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Probablemente benigna (BI RADS 3)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '3', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 17, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Anormalidad sospechosa (BI RADS 4)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '4', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 18, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Altamente sugestiva de malignidad (BI RADS 5)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '5', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
            ['clave' => 19, 'c1' => 'MAMOGRAFIA BILATERAL DE TAMIZAJE', 'c2' => 'Entrega de resultado de Mamografia Bilateral de Tamizaje', 'c3' => 'Malignidad conocida (Bi RADS 6)',
             'cond' => ['tip' => 'D', 'cod' => '99199.20', 'vl' => '6', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [40, 69]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 4 - RPT01_04_OTROS (us usp_TRAMA_BASE_CANCER_2026_RPT01_04_OTROS)
     * PERSONAS TAMIZADAS PARA LA DETECCION DE OTROS CANCER
     * (Dim Tamizaje01_04 / Gedad02_1) - columnas M/F por grupo de edad
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT01_04',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT01_04_OTROS',
        'titulo'   => 'PERSONAS TAMIZADAS PARA LA DETECCION DE OTROS CANCER',
        'cabe1'    => 'TAMIZAJE', 'cabe2' => 'ACTIVIDAD', 'cabe3' => 'RESULTADO',
        'con_sexo' => true,
        'medidas'  => ['casos', 'personas'],
        'tot_label'=> 'TOTAL DE TAMIZAJES',
        'tot2_label' => 'TOTAL DE PERSONAS TAMIZADAS',
        'gedades'  => [
            ['key' => 1, 'label' => '18a-29a', 'min' => 18, 'max' => 29],
            ['key' => 2, 'label' => '30a-39a', 'min' => 30, 'max' => 39],
            ['key' => 3, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 4, 'label' => '50a-59a', 'min' => 50, 'max' => 59],
            ['key' => 5, 'label' => '60a-69a', 'min' => 60, 'max' => 69],
            ['key' => 6, 'label' => '70a+',    'min' => 70, 'max' => null],
        ],
        'filas' => [
            // TAMIZAJE DE CANCER COLORRECTAL - Test Inmunoquimico Fecal (82274)
            ['clave' => 1,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Indicacion del Test Inmunoquimico Fecal', 'c3' => '--------------',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'NULL', 'edadA' => [50, 70]]],
            ['clave' => 2,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Indicacion del Test Inmunoquimico Fecal con Telemedicina', 'c3' => '--------------',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'NULL', 'edadA' => [50, 70],
                        'citaTiene' => ['cod' => ['99499.01', '99499.03', '99499.10', '99499.11', '99499.12'], 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 3,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test Inmunoquimico Fecal', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'N', 'edadA' => [50, 70]]],
            ['clave' => 4,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test Inmunoquimico Fecal', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'A', 'edadA' => [50, 70]]],
            ['clave' => 5,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test Inmunoquimico Fecal referido', 'c3' => 'Positivo Referido',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'A', 'rownum' => 1, 'edadA' => [50, 70],
                        'citaTiene' => ['cod' => '82274', 'tip' => 'D', 'vl' => 'RF', 'rownum' => 2]]],
            ['clave' => 6,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test Inmunoquimico Fecal con Telemedicina', 'c3' => 'Negativo con Telemedicina, Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'N', 'edadA' => [50, 70],
                        'citaTieneTodo' => [
                            ['cod' => '99402.08', 'tip' => 'D', 'vl' => '1', 'rownum' => 1],
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            ['clave' => 7,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test Inmunoquimico Fecal con Telemedicina', 'c3' => 'Negativo con Telemedicina, Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => '82274', 'vl' => 'N', 'edadA' => [50, 70],
                        'citaTieneTodo' => [
                            ['cod' => '99402.08', 'tip' => 'D', 'vl' => '2', 'rownum' => 1],
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            // Test de Sangre Oculta en Heces (82270)
            ['clave' => 8,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Indicacion del Test de Sangre Oculta en Heces', 'c3' => '--------------',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'NULL', 'edadA' => [50, 70]]],
            ['clave' => 9,  'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Indicacion del Test de Sangre Oculta en Heces con Telemedicina', 'c3' => 'Indicacion con Telemedicina',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'NULL', 'edadA' => [50, 70],
                        'citaTiene' => ['cod' => ['99499.01', '99499.03', '99499.10', '99499.11', '99499.12'], 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 10, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test de Sangre Oculta en Heces', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'N', 'edadA' => [50, 70]]],
            ['clave' => 11, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test de Sangre Oculta en Heces', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'A', 'edadA' => [50, 70]]],
            ['clave' => 12, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test de Sangre Oculta en Heces referido', 'c3' => 'Positivo Referido',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'A', 'rownum' => 1, 'edadA' => [50, 70],
                        'citaTiene' => ['cod' => '82270', 'tip' => 'D', 'vl' => 'RF', 'rownum' => 2]]],
            ['clave' => 13, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test de Sangre Oculta en Heces con Telemedicina', 'c3' => 'Negativo con Telemedicina, Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'N', 'edadA' => [50, 70],
                        'citaTieneTodo' => [
                            ['cod' => '99402.08', 'tip' => 'D', 'vl' => '1', 'rownum' => 1],
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            ['clave' => 14, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Entrega de resultado del Test de Sangre Oculta en Heces con Telemedicina', 'c3' => 'Negativo con Telemedicina, Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => '82270', 'vl' => 'N', 'edadA' => [50, 70],
                        'citaTieneTodo' => [
                            ['cod' => '99402.08', 'tip' => 'D', 'vl' => '2', 'rownum' => 1],
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            ['clave' => 15, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Evaluacion del riesgo de desarrollar cancer de colorrectal', 'c3' => '--------------',
             'cond' => ['tip' => 'D', 'cod' => '99214.08', 'vl' => 'NULL', 'rownum' => 1, 'edadA' => [50, 70]]],
            ['clave' => 16, 'c1' => 'TAMIZAJE DE CANCER DE COLORRECTAL', 'c2' => 'Evaluacion del riesgo de desarrollar cancer de colorrectal referida', 'c3' => '--------------',
             'cond' => ['tip' => 'D', 'cod' => '99214.08', 'vl' => 'RF', 'rownum' => 1, 'edadA' => [50, 70]]],
            // TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA - PSA (84152, varones)
            ['clave' => 17, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Indicacion del Examen de Antigeno Prostatico Especifico', 'c3' => '--------------',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'NULL', 'sexo' => 'M', 'edadA' => [50, 75]]],
            ['clave' => 18, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Indicacion del Examen de Antigeno Prostatico Especifico con Telemedicina', 'c3' => 'Indicacion con Telemedicina',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'NULL', 'sexo' => 'M', 'edadA' => [50, 75],
                        'citaTiene' => ['cod' => ['99499.01', '99499.03', '99499.10', '99499.11', '99499.12'], 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 19, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Entrega de Resultado del Examen de Antigeno Prostatico', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'N', 'sexo' => 'M', 'edadA' => [50, 75]]],
            ['clave' => 20, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Entrega de Resultado del Examen de Antigeno Prostatico', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'A', 'sexo' => 'M', 'edadA' => [50, 75]]],
            ['clave' => 21, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Entrega de Resultado del Examen de Antigeno Prostatico referido', 'c3' => 'Positivo Referido',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'A', 'rownum' => 1, 'sexo' => 'M', 'edadA' => [50, 75],
                        'citaTiene' => ['cod' => '84152', 'tip' => 'D', 'vl' => 'RF', 'rownum' => 2]]],
            ['clave' => 22, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Entrega de Resultado del Examen de Antigeno Prostatico con Telemedicina', 'c3' => 'Negativo con Telemedicina, Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'N', 'sexo' => 'M', 'edadA' => [50, 75],
                        'citaTieneTodo' => [
                            ['cod' => '99402.08', 'tip' => 'D', 'vl' => '1', 'rownum' => 1],
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            ['clave' => 23, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PROSTATA', 'c2' => 'Entrega de Resultado del Examen de Antigeno Prostatico con Telemedicina', 'c3' => 'Negativo con Telemedicina, Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => '84152', 'vl' => 'N', 'sexo' => 'M', 'edadA' => [50, 75],
                        'citaTieneTodo' => [
                            ['cod' => '99402.08', 'tip' => 'D', 'vl' => '2', 'rownum' => 1],
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            // TAMIZAJE PARA LA DETECCION DE CANCER DE PIEL (Z128)
            ['clave' => 24, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PIEL', 'c2' => 'Entrega de Resultado del Examen Clinico de Piel', 'c3' => 'Negativo',
             'cond' => ['tip' => 'D', 'cod' => 'Z128', 'vl' => 'N', 'edadA' => [18, 70]]],
            ['clave' => 25, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PIEL', 'c2' => 'Entrega de Resultado del Examen Clinico de Piel', 'c3' => 'Positivo',
             'cond' => ['tip' => 'D', 'cod' => 'Z128', 'vl' => 'A', 'edadA' => [18, 70]]],
            ['clave' => 26, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PIEL', 'c2' => 'Entrega de Resultado del Examen Clinico de Piel referido', 'c3' => 'Positivo Referido',
             'cond' => ['tip' => 'D', 'cod' => 'Z128', 'vl' => 'A', 'rownum' => 1, 'edadA' => [18, 70],
                        'citaTiene' => ['cod' => 'Z128', 'tip' => 'D', 'vl' => 'RF', 'rownum' => 2]]],
            ['clave' => 27, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PIEL', 'c2' => 'Entrega de Resultado del Examen Clinico de Piel', 'c3' => 'Telemedicina, IPRESS Consultante',
             'cond' => ['tip' => 'D', 'cod' => 'Z128', 'vl' => 'A', 'edadA' => [18, 70],
                        'citaTieneTodo' => [
                            ['cod' => ['99499.01', '99499.03', '99499.10', '99499.11', '99499.12'], 'tip' => 'D', 'vl' => '1', 'rownum' => 1],
                            ['cod' => '96904.01', 'tip' => 'P', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            ['clave' => 28, 'c1' => 'TAMIZAJE PARA LA DETECCION DE CANCER DE PIEL', 'c2' => 'Entrega de Resultado del Examen Clinico de Piel', 'c3' => 'Telemedicina, IPRESS Consultora',
             'cond' => ['tip' => 'D', 'cod' => ['96904.01', '96904.02'], 'vl' => 'NULL', 'edadA' => [18, 70],
                        'citaTieneTodo' => [
                            ['cod' => ['99499.11', '99499.12'], 'tip' => 'D', 'vl' => '2', 'rownum' => 1],
                            ['cod' => ['96904.01', '96904.02'], 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 6 - RPT02_02_LESIONES (us usp_TRAMA_BASE_CANCER_2026_RPT02_02_LESIONES)
     * PERSONAS ATENDIDAS CON LESIONES PRE MALIGNAS DE CANCER DE CUELLO UTERINO
     * (Dim Lesiones02_02 / Gedad04)
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT02_02',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT02_02_LESIONES',
        'titulo'   => 'PERSONAS ATENDIDAS CON LESIONES PRE MALIGNAS DE CANCER DE CUELLO UTERINO',
        'cabe1'    => 'ACTIVIDAD', 'cabe2' => null, 'cabe3' => 'RESULTADO',
        'con_sexo' => false,
        'medidas'  => ['casos', 'personas'],
        'tot_label'=> 'Total de Diagnostico',
        'tot2_label' => 'Total de Personas',
        'gedades'  => [
            ['key' => 1, 'label' => '25a-29a', 'min' => 25, 'max' => 29],
            ['key' => 2, 'label' => '30a-39a', 'min' => 30, 'max' => 39],
            ['key' => 3, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 4, 'label' => '50a-59a', 'min' => 50, 'max' => 59],
            ['key' => 5, 'label' => '60a-64a', 'min' => 60, 'max' => 64],
        ],
        'filas' => [
            ['clave' => 1,  'c1' => 'MUJER EXAMINADA CON TRIAJE PARA EL TRATAMIENTO', 'c3' => 'Negativo',
             'cond' => ['tip' => 'P', 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '88141.01', 'tip' => 'D', 'vl' => 'RN']]],
            ['clave' => 2,  'c1' => 'MUJER EXAMINADA CON TRIAJE PARA EL TRATAMIENTO', 'c3' => 'Positivo',
             'cond' => ['tip' => 'P', 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '88141.01', 'tip' => 'D', 'vl' => 'RP']]],
            ['clave' => 3,  'c1' => 'MUJER EXAMINADA CON COLPOSCOPIA', 'c3' => 'Colposcopia Sin ICA',
             'cond' => ['tip' => 'P', 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57452', 'tip' => 'D', 'vl' => 'N']]],
            ['clave' => 4,  'c1' => 'MUJER EXAMINADA CON COLPOSCOPIA', 'c3' => 'Colposcopia Con ICA',
             'cond' => ['tip' => 'P', 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57452', 'tip' => 'D', 'vl' => 'A']]],
            ['clave' => 5,  'c1' => 'MUJER EXAMINADA CON COLPOSCOPIA', 'c3' => 'Toma de Biopsia Endocervical',
             'cond' => ['tip' => 'P', 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '58100', 'tip' => 'D', 'vl' => 'NULL']]],
            ['clave' => 6,  'c1' => 'MUJER EXAMINADA CON COLPOSCOPIA', 'c3' => 'Toma de Biopsia Exocervical',
             'cond' => ['tip' => 'P', 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57500', 'tip' => 'D', 'vl' => 'NULL']]],
            ['clave' => 7,  'c1' => 'ATENCION DE PACIENTES CON LESIONES PREMALIGNAS DE CUELLO UTERINO CON ABLACION', 'c3' => 'Termocoagulacion',
             'cond' => ['tip' => ['P', 'R'], 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57510', 'tip' => 'D', 'vl' => 'NULL']]],
            ['clave' => 8,  'c1' => 'ATENCION DE PACIENTES CON LESIONES PREMALIGNAS DE CUELLO UTERINO CON ABLACION', 'c3' => 'Crioterapia',
             'cond' => ['tip' => ['P', 'R'], 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57511', 'tip' => 'D', 'vl' => 'NULL']]],
            ['clave' => 9,  'c1' => 'ATENCION DE PACIENTES CON LESIONES PREMALIGNAS DE CUELLO UTERINO CON TRATAMIENTO ESCISIONAL', 'c3' => 'Cono Leep',
             'cond' => ['tip' => ['P', 'R'], 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57522', 'tip' => 'D', 'vl' => 'NULL']]],
            ['clave' => 10, 'c1' => 'ATENCION DE PACIENTES CON LESIONES PREMALIGNAS DE CUELLO UTERINO CON TRATAMIENTO ESCISIONAL', 'c3' => 'Cono Frio',
             'cond' => ['tip' => ['P', 'R'], 'cod' => ['N870', 'N871', 'N872', 'D069', 'C539', 'B977'], 'vl' => 'NULL', 'sexo' => 'F', 'edadA' => [25, 64],
                        'citaTiene' => ['cod' => '57520', 'tip' => 'D', 'vl' => 'NULL']]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 10 - RPT04_01_CONSEJERIAS (us usp_TRAMA_BASE_CANCER_2026_RPT04_01_CONSEJERIAS)
     * PERSONA CON CONSEJERIA PARA LA PREVENCION Y CONTROL DEL CANCER
     * (Dim Consejeria04 / Gedad05)
     * NOTA: Temporal3 del original asignaba 3/4 (duplicaba Telemedicina preventiva);
     * se adapta a 7/8 (Telemedicina en pacientes diagnosticados) segun el diseno.
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT04_01',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT04_01_CONSEJERIAS',
        'titulo'   => 'PERSONA CON CONSEJERIA PARA LA PREVENCION Y CONTROL DEL CANCER',
        'cabe1'    => 'ACTIVIDAD', 'cabe2' => null, 'cabe3' => 'UNIDAD DE MEDIDA',
        'con_sexo' => true,
        'medidas'  => ['casos', 'personas'],
        'tot_label'=> 'TOTAL DE CONSEJERIA',
        'tot2_label' => 'TOTAL DE PERSONAS CON CONSEJERIA',
        'gedades'  => [
            ['key' => 1, 'label' => '<18a',    'menor18' => true],
            ['key' => 2, 'label' => '18a-29a', 'min' => 18, 'max' => 29],
            ['key' => 3, 'label' => '30a-39a', 'min' => 30, 'max' => 39],
            ['key' => 4, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 5, 'label' => '50a-59a', 'min' => 50, 'max' => 59],
            ['key' => 6, 'label' => '60a-69a', 'min' => 60, 'max' => 69],
            ['key' => 7, 'label' => '70a+',    'min' => 70, 'max' => null],
        ],
        'filas' => [
            ['clave' => 1, 'c1' => 'CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CANCER', 'c3' => 'Consultorio Externo, Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => '99402.08', 'vl' => '1', 'edadA' => [18, 75]]],
            ['clave' => 2, 'c1' => 'CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CANCER', 'c3' => 'Consultorio Externo, Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => '99402.08', 'vl' => '2', 'edadA' => [18, 75]]],
            ['clave' => 3, 'c1' => 'CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CANCER', 'c3' => 'Telemedicina, Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => '99402.08', 'vl' => '1', 'edadA' => [18, 75],
                        'citaTiene' => ['cod' => ['99499.08', '99499.09'], 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 4, 'c1' => 'CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CANCER', 'c3' => 'Telemedicina, Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => '99402.08', 'vl' => '2', 'edadA' => [18, 75],
                        'citaTiene' => ['cod' => ['99499.08', '99499.09'], 'tip' => 'D', 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 5, 'c1' => 'CONSEJERIA PARA PACIENTES DIAGNOSTICADOS Y CON TRATAMIENTO DE CANCER', 'c3' => 'Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => ['99401.19', '99401.26'], 'vl' => '1', 'edadA' => [18, 75],
                        'citaTiene' => ['codEntre' => ['C00', 'C97'], 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 6, 'c1' => 'CONSEJERIA PARA PACIENTES DIAGNOSTICADOS Y CON TRATAMIENTO DE CANCER', 'c3' => 'Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => ['99401.19', '99401.26'], 'vl' => '2', 'edadA' => [18, 75],
                        'citaTiene' => ['codEntre' => ['C00', 'C97'], 'vl' => 'NULL', 'rownum' => 1]]],
            ['clave' => 7, 'c1' => 'CONSEJERIA PARA PACIENTES DIAGNOSTICADOS Y CON TRATAMIENTO DE CANCER', 'c3' => 'Telemedicina, Personas con una consejeria',
             'cond' => ['tip' => 'D', 'cod' => ['99401.19', '99401.26'], 'vl' => '1', 'edadA' => [18, 75],
                        'citaTieneTodo' => [
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL'],
                            ['codEntre' => ['C00', 'C97'], 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
            ['clave' => 8, 'c1' => 'CONSEJERIA PARA PACIENTES DIAGNOSTICADOS Y CON TRATAMIENTO DE CANCER', 'c3' => 'Telemedicina, Personas con dos consejerias',
             'cond' => ['tip' => 'D', 'cod' => ['99401.19', '99401.26'], 'vl' => '2', 'edadA' => [18, 75],
                        'citaTieneTodo' => [
                            ['cod' => '99499.10', 'tip' => 'D', 'vl' => 'NULL'],
                            ['codEntre' => ['C00', 'C97'], 'vl' => 'NULL', 'rownum' => 1],
                        ]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 15 - RPT06_01_ATENCION (us usp_TRAMA_BASE_CANCER_2026_RPT06_01_ATENCION)
     * ATENDIDOS SEGUN TIPO DE CANCER (Dim Atencion06_01 / Gedad06)
     * Medidas: ATENCIONES y ATENDIDOS (no casos/personas)
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT06_01',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT06_01_ATENCION',
        'titulo'   => 'ATENDIDOS SEGUN TIPO DE CANCER',
        'cabe1'    => 'TIPO DE CANCER', 'cabe2' => null, 'cabe3' => null,
        'con_sexo' => true,
        'medidas'  => ['atenciones', 'atendidos'],
        'tot_label'=> 'TOTAL DE ATENCIONES',
        'tot2_label' => 'TOTAL DE ATENDIDOS',
        'gedades'  => [
            ['key' => 1, 'label' => '<18a',    'menor18' => true],
            ['key' => 2, 'label' => '18a-24a', 'min' => 18, 'max' => 24],
            ['key' => 3, 'label' => '25a-29a', 'min' => 25, 'max' => 29],
            ['key' => 4, 'label' => '30a-39a', 'min' => 30, 'max' => 39],
            ['key' => 5, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 6, 'label' => '50a-59a', 'min' => 50, 'max' => 59],
            ['key' => 7, 'label' => '60a-64a', 'min' => 60, 'max' => 64],
            ['key' => 8, 'label' => '65a+',    'min' => 65, 'max' => null],
        ],
        'filas' => [
            ['clave' => 1, 'c1' => 'Cancer de cuello uterino',
             'cond' => ['codPref' => ['C53'], 'rownum' => 1, 'sexo' => 'F', 'edadA' => [18, null]]],
            ['clave' => 2, 'c1' => 'Cancer de mama',
             'cond' => ['codPref' => 'C50', 'rownum' => 1, 'sexo' => 'F', 'edadA' => [18, null]]],
            ['clave' => 3, 'c1' => 'Cancer de estomago',
             'cond' => ['codPref' => 'C16', 'rownum' => 1, 'edadA' => [18, null]]],
            ['clave' => 4, 'c1' => 'Cancer de prostata',
             'cond' => ['codPref' => 'C61', 'rownum' => 1, 'sexo' => 'M', 'edadA' => [18, null]]],
            ['clave' => 5, 'c1' => 'Cancer de pulmon',
             'cond' => ['codPref' => 'C34', 'rownum' => 1, 'edadA' => [18, null]]],
            ['clave' => 6, 'c1' => 'Cancer de colon y recto',
             'cond' => ['codPref' => ['C18'], 'rownum' => 1, 'edadA' => [18, null]]],
            ['clave' => 7, 'c1' => 'Cancer de higado',
             'cond' => ['codPref' => 'C22', 'rownum' => 1]],
            ['clave' => 8, 'c1' => 'Leucemia',
             'cond' => ['codPref' => ['C901', 'C91', 'C92', 'C93', 'C94', 'C95'], 'rownum' => 1]],
            ['clave' => 9, 'c1' => 'Linfoma',
             'cond' => ['codPref' => ['C963', 'C81', 'C82', 'C83', 'C84', 'C85'], 'rownum' => 1]],
            ['clave' => 10, 'c1' => 'Cancer de piel',
             'cond' => ['codPref' => ['C43', 'C44'], 'rownum' => 1, 'edadA' => [18, null]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 17 - RPT06_03_CANCER_ADULTOS (us usp_TRAMA_BASE_CANCER_2026_RPT06_03_CANCER_ADULTOS)
     * ATENCIONES DE TODO TIPO DE CANCER ADULTO
     * Rangos CIE originales: C000-C218, C23X-C809, C860-C900, C960-C962, C964-C97X,
     * C80X/C900/C902/C903 (18+) | C901/C910-C959 con vl='1' | C963/C810-C859 con vl='1'
     * | C22 (referido). Adaptacion: fg_tipo='CX' del DTSG no se replica (el catalogo
     * local puede variar); se mantiene el resto de condiciones.
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT06_03',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT06_03_CANCER_ADULTOS',
        'titulo'   => 'ATENCIONES DE TODO TIPO DE CANCER ADULTO',
        'cabe1'    => 'TIPO DE CANCER', 'cabe2' => null, 'cabe3' => null,
        'con_sexo' => true,
        'medidas'  => ['atenciones', 'atendidos'],
        'tot_label'=> 'TOTAL DE ATENCIONES',
        'tot2_label' => 'TOTAL DE ATENDIDOS',
        'gedades'  => [
            ['key' => 1, 'label' => '<18a',    'menor18' => true],
            ['key' => 2, 'label' => '18a-24a', 'min' => 18, 'max' => 24],
            ['key' => 3, 'label' => '25a-29a', 'min' => 25, 'max' => 29],
            ['key' => 4, 'label' => '30a-39a', 'min' => 30, 'max' => 39],
            ['key' => 5, 'label' => '40a-49a', 'min' => 40, 'max' => 49],
            ['key' => 6, 'label' => '50a-59a', 'min' => 50, 'max' => 59],
            ['key' => 7, 'label' => '60a-64a', 'min' => 60, 'max' => 64],
            ['key' => 8, 'label' => '65a+',    'min' => 65, 'max' => null],
        ],
        'filas' => [
            ['clave' => 1, 'c1' => 'Todo tipo de cancer',
             'cond' => ['rownum' => 1, 'cualquieraDe' => [
                 // Rama 1: CIE de cancer en adulto (18+)
                 ['codEntre' => ['C000', 'C218'], 'edadA' => [18, null]],
                 ['codEntre' => ['C23X', 'C809'], 'edadA' => [18, null]],
                 ['codEntre' => ['C860', 'C900'], 'edadA' => [18, null]],
                 ['codEntre' => ['C960', 'C962'], 'edadA' => [18, null]],
                 ['codEntre' => ['C964', 'C97X'], 'edadA' => [18, null]],
                 ['cod' => ['C80X', 'C900', 'C902', 'C903'], 'edadA' => [18, null]],
                 // Rama 2/3: leucemias/linfomas marcados con valor_lab='1'
                 ['cod' => 'C901', 'vl' => '1'],
                 ['codEntre' => ['C910', 'C959'], 'vl' => '1'],
                 ['cod' => 'C963', 'vl' => '1'],
                 ['codEntre' => ['C810', 'C859'], 'vl' => '1'],
                 // Rama 4: cancer de higado referido
                 ['codPref' => 'C22'],
             ]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION 19 - RPT07_01_DETECCION_CANCER (us usp_TRAMA_BASE_CANCER_2026_RPT07_01_DETECCION_CANCER)
     * DETECCION TEMPRANA DE CANCER INFANTIL (99201.03, menores de 18)
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT07_01',
        'procedimiento' => 'usp_TRAMA_BASE_CANCER_2026_RPT07_01_DETECCION_CANCER',
        'titulo'   => 'DETECCION TEMPRANA DE CANCER INFANTIL',
        'cabe1'    => 'ACTIVIDAD', 'cabe2' => 'PROFESIONAL', 'cabe3' => null,
        'con_sexo' => true,
        'medidas'  => ['casos', 'personas'],
        'tot_label'=> 'TOTAL DE TRATAMIENTO',
        'tot2_label' => 'TOTAL DE PERSONAS CON TRATAMIENTO',
        'gedades'  => [
            ['key' => 1, 'label' => '<18a', 'menor18' => true],
        ],
        'filas' => [
            ['clave' => 1, 'c1' => 'DETECCION TEMPRANA DE CANCER INFANTIL', 'c2' => 'En Consultorio Externo',
             'cond' => ['tip' => 'D', 'cod' => '99201.03', 'vl' => 'NULL', 'rownum' => 1, 'menor18' => true]],
            ['clave' => 2, 'c1' => 'DETECCION TEMPRANA DE CANCER INFANTIL', 'c2' => 'En Consultorio Externo y referido',
             'cond' => ['tip' => 'D', 'cod' => '99201.03', 'vl' => 'RF', 'rownum' => 1, 'menor18' => true]],
            // Filas de la plantilla oficial sin procedimiento asociado (siempre 0)
            ['clave' => 0, 'c1' => 'DETECCION TEMPRANA DE CANCER INFANTIL', 'c2' => 'Por teleconsulta en linea', 'cero' => true],
            ['clave' => 0, 'c1' => 'DETECCION TEMPRANA DE CANCER INFANTIL', 'c2' => 'Por teleinterconsulta sincrona/IPRESS consultante', 'cero' => true],
            ['clave' => 0, 'c1' => 'DETECCION TEMPRANA DE CANCER INFANTIL', 'c2' => 'Por teleinterconsulta sincrona/IPRESS consultora', 'cero' => true],
        ],
    ],

    ]; // fin $secciones

    return $secciones;
}

/* ============================================================
 * 3) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/**
 * Anios disponibles en la tabla consolidada (para el filtro).
 * Se restringe a los anios con items del modulo Cancer para no saturar la BD.
 */
function cancerGetAniosDisponibles(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
                             WHERE Anio IS NOT NULL ORDER BY Anio DESC");
        $anios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $anios ?: [date('Y')];
    } catch (Throwable $e) {
        return [date('Y')];
    }
}

/**
 * Establecimientos del catalogo ZSPERENE (mismo criterio que el modulo ESNI:
 * clave = Codigo_Unico, valor = Nombre_Establecimiento).
 */
function cancerGetEstablecimientosZS(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE
                             WHERE Codigo_Unico IS NOT NULL ORDER BY Nombre_Establecimiento");
        $out = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[trim((string)$r['Codigo_Unico'])] = $r['Nombre_Establecimiento'];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Lista de codigos de item exactos que intervienen en el reporte
 * (lineas + condiciones auxiliares de los 8 procedimientos).
 */
function cancerCodigosInteres(): array {
    return [
        // RPT01_01 Cuterino
        '88141', '88141.01', '87621', 'R876', 'N870', 'N871', 'N872', 'D069', 'C539', 'B977',
        // RPT01_02 CMama
        'Z123', 'Z0143', '99386.03', '77057', '99214.08', '99199.20', '76140.04',
        // RPT01_04 Otros
        '82270', '82274', '84152', 'Z128', '99499.01', '99499.03', '99499.11', '99499.12', '99402.08',
        '96904.01', '96904.02',
        // RPT02_02 Lesiones (57500/58100: biopsias usadas como condicion auxiliar)
        '57500', '58100', '57452', '57510', '57511', '57522', '57520',
        // RPT04_01 Consejerias
        '99401.19', '99401.26', '99499.08', '99499.09',
        // RPT07_01 Deteccion infantil
        '99201.03',
        // Comunes (telemedicina)
        '99499.10',
    ];
}

/**
 * Abre una conexion PDO dedicada en modo UNBUFFERED para leer el resultado
 * del reporte en streaming (sin volcar todo el resultado a memoria de golpe).
 * Devuelve null si no se puede abrir; en ese caso se usa la conexion principal.
 */
function cancerAbrirConexionStreaming(): ?PDO {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci",
        ]);
    } catch (Throwable $e) {
        return null;
    }
}

/** Indica si la tabla consolidada tiene la columna generada Mes_Int (cacheada). */
function cancerTieneColumnaMesInt(PDO $pdo): bool {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $st = $pdo->query("SHOW COLUMNS FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO LIKE 'Mes_Int'");
        $cache = ($st->fetch() !== false);
    } catch (Throwable $e) {
        $cache = false;
    }
    return $cache;
}

/**
 * Diagnostico del entorno para el panel de errores del reporte Cancer:
 * limites de PHP, indices presentes en la tabla consolidada y tamano aprox.
 * Permite que el usuario VEA por que fallo (memoria/tiempo/faltan indices)
 * en lugar de un HTTP 500 en blanco.
 */
function cancerDiagnosticoEntorno(PDO $pdo): array {
    $out = [
        'php'           => PHP_VERSION,
        'memory_limit'  => (string)ini_get('memory_limit'),
        'max_exec_time' => (string)ini_get('max_execution_time'),
        'indices'       => [],
        'filas_tabla'   => null,
    ];
    try {
        $st = $pdo->query("SHOW INDEX FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO");
        $idx = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $idx[] = $row['Key_name'];
        }
        $out['indices'] = array_values(array_unique($idx));
    } catch (Throwable $e) { /* ignorar */ }
    try {
        $st = $pdo->query("SELECT TABLE_ROWS FROM information_schema.TABLES
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO'");
        $v = $st->fetchColumn();
        $out['filas_tabla'] = $v ? (int)$v : null;
    } catch (Throwable $e) { /* ignorar */ }
    return $out;
}

/**
 * Ejecuta el reporte de Cancer completo (adaptacion de los 8 USP).
 *
 * Estrategia (igual que el modulo ESNI): UNA sola consulta que trae todas las
 * filas HIS de los codigos de interes (exactos + todos los CIE 'C%') con los
 * filtros comunes aplicados, y luego el matching de cada linea se resuelve en
 * PHP usando las condiciones del DSL (que espejan los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function cancerEjecutarReporte(PDO $pdo, array $filtros): array {
    $t0 = microtime(true);
    $tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';

    // ---- 0) Proteccion contra HTTP 500 en hostings compartidos ----
    // Los fatales "Allowed memory size exhausted" / "Maximum execution time
    // exceeded" NO se capturan con try/catch. Se intenta elevar los limites
    // (si el hosting lo permite) y se fija un tope de filas de seguridad.
    if (function_exists('set_time_limit')) @set_time_limit(0);
    @ini_set('memory_limit', '512M');
    $MAX_FILAS = 2000000;

    // ---- 1) Clausula WHERE comun (filtros del usuario) ----
    $where = ["1=1"];
    $params = [];
    if (!empty($filtros['anio'])) {
        $where[] = "Anio = :anio";
        $params[':anio'] = (string)$filtros['anio'];
    }
    if (!empty($filtros['mes'])) {
        // Usar la columna generada Mes_Int (sargable, ya existe en la tabla)
        // permite que MySQL use indice; el CAST(TRIM(...)) lo impedia.
        if (cancerTieneColumnaMesInt($pdo)) {
            $where[] = "Mes_Int = :mes";
        } else {
            $where[] = "CAST(TRIM(Mes) AS UNSIGNED) = :mes";
        }
        $params[':mes'] = intval($filtros['mes']);
    }
    if (!empty($filtros['establecimiento'])) {
        $where[] = "TRIM(Codigo_Unico) = :est";
        $params[':est'] = (string)$filtros['establecimiento'];
    }

    // ---- 2) Codigo de items de interes (exactos + CIE C%) ----
    $inPh = [];
    foreach (cancerCodigosInteres() as $i => $c) {
        $ph = ":cod_$i";
        $inPh[] = $ph;
        $params[$ph] = $c;
    }
    $whereCod = "(Codigo_Item IN (" . implode(',', $inPh) . ") OR Codigo_Item LIKE 'C%')";
    // Excluir codigos CIE Z/O/B que empiezan con C pero no son neoplasias: LIKE 'C%'
    // ya restringe a la letra C (C00-C97 son los unicos codigos CIE usados por Cancer).

    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab, Id_Correlativo_Lab, Fg_Tipo
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    // La conexion principal es buffered: MySQL entrega TODO el resultado de
    // golpe en memoria PHP antes del primer fetch (con "C%" + un anio completo
    // eso agotaba el memory_limit del hosting -> Fatal error -> HTTP 500).
    // Se abre una conexion dedicada unbuffered: las filas llegan en flujo y se
    // normalizan/indexan una a una, con uso de RAM estable. Si no se puede
    // abrir, se cae a la conexion principal (comportamiento anterior).
    //
    // ADEMAS: el bucle de fetch antes NO estaba dentro del try/catch; si MySQL
    // mataba la consulta lenta a mitad de lectura (server has gone away),
    // la PDOException no capturada producía el HTTP 500. Ahora todo queda
    // protegido y el error se muestra de forma controlada.
    $pdoStream = cancerAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    $porCita = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = cnrFila($r);
            if ($f['cod'] === '') continue;
            $idx = count($filas);
            $filas[] = $f;
            if ($f['cita'] !== '') $porCita[$f['cita']][] = $idx;
            if ($idx >= $MAX_FILAS) {
                $stmt = null;
                return ['secciones' => [], 'totales' => [], 'error' =>
                        'La consulta devolvio mas de ' . number_format($MAX_FILAS) . ' filas. Aplique filtros (anio / mes / establecimiento) para acotar el reporte.',
                        'error_tipo' => 'datos'];
            }
        }
        $stmt = null;
    } catch (Throwable $e) {
        $pdoStream = null;
        return ['secciones' => [], 'totales' => [], 'error' => 'Error SQL: ' . $e->getMessage(),
                'sql_debug' => $sql, 'params_debug' => $params, 'error_tipo' => 'sql'];
    }
    $pdoStream = null; // cerrar la conexion de streaming

    // Buckets para matching rapido (codigo exacto y letra inicial)
    $porCod = [];
    $porIni = [];
    $totalFilas = count($filas);
    foreach ($filas as $idx => $f) {
        $porCod[$f['cod']][] = $idx;
        $porIni[$f['cod'] !== '' ? strtoupper($f['cod'][0]) : '#'][] = $idx;
    }
    $ctx = ['filas' => $filas, 'porCita' => $porCita];

    // ---- 4) Matching por seccion / fila ----
    $seccionesOut = [];
    $totCasos = 0;
    $totPersonas = 0;
    $totAtenciones = 0;
    $totAtendidos = 0;

    foreach (cancerSecciones() as $sec) {
        $esAtencion = ($sec['medidas'][0] === 'atenciones');
        $gedades = $sec['gedades'];
        $filasOut = [];

        foreach ($sec['filas'] as $fila) {
            $valores = []; // [sexo][gedad] => ['casos'=>n,'personas'=>set] o ['atenciones'=>,'atendidos'=>set]
            // FIX "Cannot use a scalar value as an array": 'personas' y 'atendidos'
            // se llenan como SETS (array) en el bucle (ver lineas siguientes) y solo
            // al final se convierten a conteos con count(). Inicializarlos a 0
            // (escalar) hacia fallar $total['personas'][$f['pac']] = true con
            // "Uncaught Error" fatal en PHP 8 -> HTTP 500 en "Generar Reporte".
            // Debe espejar la inicializacion de $valores[$sexo][$g] mas abajo.
            $total = ['casos' => 0, 'personas' => [], 'atenciones' => 0, 'atendidos' => []];

            if (empty($fila['cero'])) {
                $cond = $fila['cond'];
                // Matching sobre los candidatos pre-indexados (misma semantica:
                // la verificacion exacta la hace cnrCumple). Antes se recorrian
                // TODAS las filas por cada linea (~150 x N iteraciones).
                foreach (cnrCandidatos($cond, $porCod, $porIni, $totalFilas) as $idx) {
                    $f = $filas[$idx];
                    if (!cnrCumple($f, $cond, $ctx)) continue;

                    $g = cnrGedad($f, $gedades);
                    if ($g === null) continue; // edad fuera de los grupos de la seccion

                    $sexo = $f['sexo'] ?: 'X';
                    if (!isset($valores[$sexo])) $valores[$sexo] = [];
                    if (!isset($valores[$sexo][$g])) {
                        $valores[$sexo][$g] = ['casos' => 0, 'personas' => [], 'atenciones' => 0, 'atendidos' => []];
                    }
                    $valores[$sexo][$g]['casos']++;
                    $valores[$sexo][$g]['atenciones']++;
                    if ($f['pac'] !== '') {
                        $valores[$sexo][$g]['personas'][$f['pac']] = true;
                        if (!$esAtencion || $f['tip'] === 'D') {
                            $valores[$sexo][$g]['atendidos'][$f['pac']] = true;
                        }
                    }
                    if (!$esAtencion) {
                        $total['casos']++;
                        if ($f['pac'] !== '') $total['personas'][$f['pac']] = true;
                    } else {
                        $total['atenciones']++;
                        if ($f['pac'] !== '' && $f['tip'] === 'D') $total['atendidos'][$f['pac']] = true;
                    }
                }
            }

            // Consolidar sets a conteos
            foreach ($valores as &$vx) {
                foreach ($vx as &$cell) {
                    $cell['personas'] = count($cell['personas']);
                    $cell['atendidos'] = count($cell['atendidos']);
                }
            }
            unset($vx, $cell);
            $total['personas'] = is_array($total['personas']) ? count($total['personas']) : 0;
            $total['atendidos'] = is_array($total['atendidos']) ? count($total['atendidos']) : 0;

            $filasOut[] = [
                'clave'    => $fila['clave'],
                'c1'       => $fila['c1'] ?? '',
                'c2'       => $fila['c2'] ?? null,
                'c3'       => $fila['c3'] ?? null,
                'cero'     => !empty($fila['cero']),
                'valores'  => $valores,
                'total'    => $total,
            ];
        }

        $totalSec = ['casos' => 0, 'personas' => 0, 'atenciones' => 0, 'atendidos' => 0];
        foreach ($filasOut as $fo) {
            $totalSec['casos'] += $fo['total']['casos'];
            $totalSec['personas'] += $fo['total']['personas'];
            $totalSec['atenciones'] += $fo['total']['atenciones'];
            $totalSec['atendidos'] += $fo['total']['atendidos'];
        }

        $seccionesOut[] = [
            'codigo'       => $sec['codigo'],
            'procedimiento'=> $sec['procedimiento'],
            'titulo'       => $sec['titulo'],
            'cabe1'        => $sec['cabe1'],
            'cabe2'        => $sec['cabe2'],
            'cabe3'        => $sec['cabe3'],
            'con_sexo'     => $sec['con_sexo'],
            'medidas'      => $sec['medidas'],
            'tot_label'    => $sec['tot_label'],
            'tot2_label'   => $sec['tot2_label'],
            'gedades'      => $sec['gedades'],
            'filas'        => $filasOut,
            'total'        => $totalSec,
        ];

        $totCasos += $totalSec['casos'];
        $totPersonas += $totalSec['personas'];
        $totAtenciones += $totalSec['atenciones'];
        $totAtendidos += $totalSec['atendidos'];
    }

    return [
        'secciones' => $seccionesOut,
        'totales'   => [
            'total_casos'      => $totCasos,
            'total_personas'   => $totPersonas,
            'total_atenciones' => $totAtenciones,
            'total_atendidos'  => $totAtendidos,
            'secciones_con_datos' => count(array_filter($seccionesOut, fn($s) =>
                ($s['medidas'][0] === 'atenciones' ? $s['total']['atenciones'] : $s['total']['casos']) > 0)),
        ],
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}
