<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo MATERNO - Motor de reporte data-driven (includes/materno_data.php)
 *
 * ADAPTACION FIEL del archivo "03 Creacion de Procedimientos.txt"
 * (procedimientos usp_TRAMA_BASE_MATERNO_2023_RPT_01..RPT_10) sobre la tabla
 * consolidada MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO.
 *
 * El archivo original NO estaba disponible cuando se migro el modulo (la
 * version anterior de este archivo lo declaraba en sus "NOTAS DE ADAPTACION"
 * y usaba codigos HIS-MINSA estimados). Esta version reemplaza TODAS las
 * condiciones por las del archivo 03, categoria por categoria:
 *
 *   I    RPT_01_APN_REENFOCADA        (25 categorias + Total calculado)
 *   II   RPT_02_BIENESTAR             (6 categorias)
 *   III  RPT_03_ANEMIA                (11 categorias)
 *   IV   RPT_04_COMPLICACIONES        (15 filas)
 *   V    RPT_05_MORBILIDAD_RN         (7 filas)
 *   VI   RPT_06_ADMIN_MICRONUT        (10 categorias)
 *   VII  RPT_07_PUERPERIO             (3 categorias)
 *   VIII RPT_08_VISITA                (2 filas x 3 grupos etareos + Total web)
 *   IX-1 RPT_09_1_TRANSMISION_VERT    (21 categorias)
 *   IX-2 RPT_09_2_TRANSMISION_VERT    (6 categorias)
 *   IX-3 RPT_09_3_TRANSMISION_VERT    (4 categorias; sin zona en la plantilla)
 *   X    RPT_10_CONSEJERIA            (3 categorias)
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita         -> Id_Cita
 *   id_tipitem      -> Tipo_Diagnostico      ('D','P','R','C')
 *   cod_item        -> Codigo_Item
 *   cod_item_f      -> (sin columna) se replica con prefijo CIE de 3 letras
 *                      (cod_item_f in ('O44','O45') == Codigo_Item LIKE 'O44%')
 *   valor_lab       -> Valor_Lab
 *   I_ROWNUM_LAB    -> Id_Correlativo_Lab
 *   id_genero       -> Id_Genero             ('F','M')
 *   edad_reg        -> Edad_Reg
 *   id_tipedad_reg  -> Tipo_Edad             ('D','M','A')
 *   renaes          -> Codigo_Unico
 *   periodo         -> Anio + Mes (filtro web del reporte)
 *
 * REGLA DE CONTEO (regla 'filas'): el T-SQL inserta en cada tabla
 * TRAMA_BASE_MATERNO_*_NOMINAL UNA FILA POR CADA FILA de TRAMAHIS_DTSG que
 * cumple el WHERE de la categoria y luego consolida con COUNT(*). El motor
 * replica exactamente eso: cada FILA HIS que cumple la condicion cuenta 1
 * en su grupo etareo. NO hay dedup por cita ni reglas de ocurrencia.
 *
 * EL "N-ESIMO" VIENE EN VALOR_LAB (no se calcula): en el archivo 03 el numero
 * de atencion / entrega / sesion / monitoreo / tamizaje / ecografia viene
 * codificado en valor_lab del item HIS:
 *   - Z3491/Z3492/Z3493 y Z3591/Z3592/Z3593: valor '1'..'14' = N° atencion
 *     prenatal ('6' = gestante CONTROLADA; 'TA' con I_ROWNUM_LAB=3 = tamizaje
 *     para la atencion prenatal reenfocada).
 *   - 76817/76805 (ecografia): valor '1'/'2'/'3' = 1°/2°/3° ecografia.
 *   - 59020/59025 (monitoreo fetal): valor '1'/'2' = 1°/2° monitoreo.
 *   - 99412.01/99412.02: valor '1' = atendida/1°, '6' = preparada/6°.
 *   - 99199.26 (sulfato ferroso): '1'/'6' = 1°/6° entrega; 'TA' = puerpera.
 *   - 99199.18 (acido folico), 59401.05 (calcio), 85018 (hemoglobina),
 *     U1692/59401.06 (plan de parto), 90714/90744/90746 (vacunas): idem.
 *   - O990: valor 'LEV'/'MOD'/'SEV' (D) = anemia leve/moderada/severa;
 *     valor 'PR' (R) = recuperada.
 *   - Laboratorios IX: valor 'RP' = reactivo; 99401.33/99402.05 (consejeria)
 *     valor '1' = 1° prueba, '2' = 2° prueba.
 *   - 59430 (puerperio): valor '1' = atendida, '2' = controlada.
 *
 * TRIMESTRE GESTACIONAL (seccion IX-1): lo da el CODIGO, no la FUR:
 *   Z3491/Z3591 = I Trim, Z3492/Z3592 = II Trim, Z3493/Z3593 = III Trim,
 *   o Z359/Z349 con valor_lab 1/2/3 e I_ROWNUM_LAB=1. 1° vs 2° tamizaje =
 *   Tipo_Diagnostico 'D' (definitivo) vs 'R' (repetido), como en el CASE.
 *
 * GRUPOS ETAREOS (CASE gedad del T-SQL):
 *   1 = 10-11 a.   2 = 12-17 a.   3 = 18-29 a.   4 = 30-59 a.
 *   (RPT_05: recien nacidos, Tipo_Edad 'D' y 1-29 dias -> columna "N°")
 *
 * NOTAS DE ADAPTACION (desviaciones documentadas):
 *   - periodo >= '20230101' del T-SQL se convierte en los filtros web de
 *     Anio/Mes/Establecimiento (mismos datos, distinta presentacion).
 *   - RPT_03 (#EMBARAZO y #ANEMIA): el T-SQL escribe "(A) OR (B) AND filtros"
 *     que por precedencia de AND/OR deja la rama A sin filtros; aqui los
 *     filtros se aplican a ambas ramas porque el resultado final es identico
 *     (los SELECT de las tablas nominales re-aplican todos los filtros a
 *     nivel de fila: sexo, edad y periodo).
 *   - RPT_04/RPT_01: la tabla #GEST usa EXISTS(valor_lab='G'); las filas con
 *     valor 'G' pueden estar en cualquier codigo, por eso la consulta base
 *     trae tambien todas las filas con Valor_Lab='G'.
 *   - RPT_06: se replica la exclusion "id_cita NOT IN (select ... #ANEMIA)"
 *     (citas con diagnostico O990) mediante el predicado 'citaNoTiene'.
 *   - RPT_08/RPT_09_2 usan EXISTS sobre la cita (#VISITA/#PUERPERAS) =>
 *     predicado 'citaTiene' / 'citaTieneTodo'.
 *   - La fila TOTAL de cada seccion es la suma de los grupos etareos (como la
 *     plantilla con =SUM). La columna "Gestante Atendida Total" de la seccion
 *     I es calculada = I + II + III Trim (la plantilla usa =SUM(C:E)).
 *   - RPT_09_3 (1° prueba rapida VIH en trabajo de parto / aborto) no tiene
 *     zona de datos en la plantilla oficial: se muestra en la web y NO se
 *     exporta al Excel (igual que en el flujo ODBC original, quedaba en 0).
 *
 * CAMBIO 2026-09-05 (ambito de establecimientos):
 *   - El valor '-- Todos --' del filtro establecimiento ya NO significa
 *     'toda la tabla consolidada': significa 'todos los establecimientos de
 *     la lista del select' (catalogo ZSPERENE). La consulta del reporte se
 *     limita a esa lista (TRIM(Codigo_Unico) IN (...ZSPERENE...)) y por lo
 *     tanto TODOS los demas filtros (anio, mes, codigos de interes) se
 *     aplican unicamente dentro de esa lista de EE.SS.
 *   - Los filtros dependientes siguen el mismo ambito: los anios disponibles
 *     (maternoGetAniosDisponibles) y los meses disponibles (nueva funcion
 *     maternoGetMesesDisponibles) se calculan sobre los datos de la lista
 *     completa cuando '-- Todos --' esta seleccionado, o del establecimiento
 *     concreto cuando se elige uno.
 *   - Si el catalogo ZSPERENE no esta disponible (tabla vacia o error) no se
 *     restringe nada y se conserva el comportamiento anterior, para no
 *     romper el reporte en instalaciones sin catalogo.
 *
 * CAMBIO 2026-09-05 r2 (columna Total en VIII. VISITA DOMICILIARIA):
 *   - La seccion VIII ahora muestra en la web una columna Total al final de
 *     cada fila (a la derecha de "30 - 59 a."), calculada como la suma de
 *     las columnas "12 - 17 a." + "18 - 29 a." + "30 - 59 a.". El valor ya
 *     existia en el motor (total de fila = suma de los grupos etareos de la
 *     seccion); solo se activo su render con 'conTotal' + 'totalPos' 'fin'.
 *   - Solo cambia la vista web: la plantilla oficial de Excel no tiene esa
 *     columna y el export (maternoCeldasExport) la sigue omitiendo, igual
 *     que en el flujo ODBC original.
 */

require_once __DIR__ . '/../config.php';

define('MATERNO_DATA_VERSION', '2026-09-05-r2'); // r2: VIII. VISITA DOMICILIARIA gana columna Total web (12-17 + 18-29 + 30-59)

/** Version del motor de reporte de Materno (para el badge del reporte). */
function maternoDataVersion(): string {
    return MATERNO_DATA_VERSION;
}

/* ============================================================
 * 1) NORMALIZACION DE FILAS HIS
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 *
 * El modulo Materno necesita, ademas de los campos comunes (codigo, valor
 * lab, sexo, edad), tres campos adicionales que los modulos ESNI/Cancer no
 * usaban:
 *   - Fecha_Atencion + Fecha_Ultima_Regla -> edad gestacional (informativa)
 *   - Hemoglobina                         -> severidad de anemia (informativa)
 *   - Descripcion_Otra_Condicion          -> 'GESTANTE' / 'PUERPERA'
 *
 * Los textos se normalizan a MAYUSCULAS para replicar la colacion
 * case-insensitive de MySQL en el matching PHP. La regla 'filas' del archivo
 * 03 NO usa FUR ni hemoglobina (el trimestre lo da el codigo Z y la severidad
 * el valor 'LEV'/'MOD'/'SEV'/'PR' de O990), pero se conservan por si el
 * usuario quiere auditar esos campos.
 */
function mtrFila(array $r): array {
    $otraCondRaw = isset($r['Descripcion_Otra_Condicion']) ? $r['Descripcion_Otra_Condicion'] : null;
    $furRaw  = isset($r['Fecha_Ultima_Regla']) ? $r['Fecha_Ultima_Regla'] : null;
    $fAtRaw  = isset($r['Fecha_Atencion']) ? $r['Fecha_Atencion'] : null;
    return [
        'cita'    => $r['Id_Cita'] !== null ? trim((string)$r['Id_Cita']) : '',
        'pac'     => $r['Id_Paciente'] !== null ? trim((string)$r['Id_Paciente']) : '',
        'cod'     => $r['Codigo_Item'] !== null ? strtoupper(trim((string)$r['Codigo_Item'])) : '',
        'tip'     => $r['Tipo_Diagnostico'] !== null ? strtoupper(trim((string)$r['Tipo_Diagnostico'])) : '',
        'vl'      => $r['Valor_Lab'] !== null ? strtoupper(trim((string)$r['Valor_Lab'])) : null,
        'rownum'  => $r['Id_Correlativo_Lab'] !== null ? (int)$r['Id_Correlativo_Lab'] : null,
        'sexo'    => $r['Id_Genero'] !== null ? strtoupper(trim((string)$r['Id_Genero'])) : '',
        'edad'    => $r['Edad_Reg'] !== null ? (int)$r['Edad_Reg'] : null,
        'tipEdad' => $r['Tipo_Edad'] !== null ? strtoupper(trim((string)$r['Tipo_Edad'])) : '',
        'fg'      => $r['Fg_Tipo'] !== null ? strtoupper(trim((string)$r['Fg_Tipo'])) : '',
        'otraCond'=> ($otraCondRaw !== null && $otraCondRaw !== '') ? strtoupper(trim((string)$otraCondRaw)) : null,
        'fecha'   => $fAtRaw !== null ? (is_string($fAtRaw) ? strtotime($fAtRaw) : ($fAtRaw instanceof DateTime ? $fAtRaw->getTimestamp() : null)) : null,
        'fur'     => $furRaw !== null ? (is_string($furRaw) ? strtotime($furRaw) : ($furRaw instanceof DateTime ? $furRaw->getTimestamp() : null)) : null,
        'hb'      => isset($r['Hemoglobina']) && $r['Hemoglobina'] !== null && $r['Hemoglobina'] !== ''
                        ? (float)$r['Hemoglobina'] : null,
    ];
}

/**
 * Edad gestacional en semanas (float) de una fila, segun
 * Fecha_Atencion - Fecha_Ultima_Regla. Null si no se puede calcular.
 * (Informativa: la regla 'filas' del archivo 03 no la usa.)
 */
function mtrEdadGestacional(array $f): ?float {
    if ($f['fecha'] === null || $f['fur'] === null) return null;
    $dias = ($f['fecha'] - $f['fur']) / 86400.0;
    if ($dias < 0 || $dias > 320) return null; // fuera de rango razonable (0-45 sem)
    return $dias / 7.0;
}

/** Trimestre gestacional (1, 2 o 3) por FUR (informativo; el archivo 03 usa el codigo Z). */
function mtrTrimestreEG(array $f): ?int {
    $eg = mtrEdadGestacional($f);
    if ($eg === null) return null;
    if ($eg < 14.0) return 1;
    if ($eg < 28.0) return 2;
    return 3;
}

/** Indica si la fila corresponde a una mujer GESTANTE (Descripcion_Otra_Condicion). */
function mtrEsGestante(array $f): bool {
    if (!isset($f['otraCond']) || $f['otraCond'] === null || $f['otraCond'] === '') return false;
    if ($f['otraCond'] === 'GESTANTE') return true;
    return strpos($f['otraCond'], 'GESTANTE') === 0; // variantes 'GESTANTE ...'
}

/** Indica si la fila corresponde a una PUERPERA (Descripcion_Otra_Condicion). */
function mtrEsPuerpera(array $f): bool {
    if (!isset($f['otraCond']) || $f['otraCond'] === null || $f['otraCond'] === '') return false;
    if ($f['otraCond'] === 'PUERPERA') return true;
    return strpos($f['otraCond'], 'PUERPER') === 0; // 'PUERPERA', 'PUERPERIO', ...
}

/* ============================================================
 * 2) PREDICADOS (mini-DSL que espeja las condiciones del T-SQL)
 * ============================================================ */

/**
 * Evalua un predicado del DSL contra una fila normalizada.
 *
 * Claves admitidas (se combinan con AND):
 *   'cod'         => '85018' | ['Z321','Z320']   Codigo_Item en la lista (exacto)
 *   'codPref'     => 'O44' | ['O44','O45']       Codigo_Item LIKE 'O44%'
 *                                                   (equivale a cod_item_f = 'O44')
 *   'codEntre'    => ['O10','O16Z']               BETWEEN de strings (como el T-SQL)
 *   'tip'         => 'D' | ['P','R']             Tipo_Diagnostico (id_tipitem)
 *   'vl'          => 'NULL' | '1' | ['1','A']    Valor_Lab (NULL = IS NULL)
 *   'vlVacia'     => true                        Valor_Lab IS NULL o ''
 *   'vlNum'       => [6, null]                   TRY_CONVERT(int, valor_lab) >= 6
 *                                                   (rango numerico, como el T-SQL)
 *   'vlPresente'  => true                        Valor_Lab IS NOT NULL
 *   'rownum'      => 1                           Id_Correlativo_Lab = N (I_ROWNUM_LAB)
 *   'sexo'        => 'F' | 'M'                   Id_Genero
 *   'edadA'       => [12, 17] | [30, null]       Tipo_Edad='A' y Edad_Reg en rango
 *   'edadD'       => [1, 29]                     Tipo_Edad='D' y Edad_Reg en rango
 *   'fgTipo'      => 'CX'                        Fg_Tipo
 *   'gestante'    => true                        Descripcion_Otra_Condicion GESTANTE
 *   'puerpera'    => true                        Descripcion_Otra_Condicion PUERPERA
 *   'otraCond'    => 'NULL' | 'GESTANTE'         igualdad exacta / IS NULL
 *   'hb'          => [10, 10.9] | [7, null]      Hemoglobina presente y en rango
 *   'hbVacia'     => true                        Hemoglobina IS NULL/vacia
 *   'citaTiene'   => <predicado>                 EXISTS: alguna fila de la MISMA
 *                                                   cita lo cumple (tabla #temp)
 *   'citaTieneTodo' => [<pred>, ...]             AND de EXISTS sobre la cita
 *   'citaNoTiene' => <predicado>                 NOT EXISTS sobre la cita
 *                                                   ("id_cita NOT IN (select...)")
 *   'pacTiene'    => <predicado>                 alguna cita del MISMO paciente
 *   'pacTieneTodo'  => [<pred>, ...]             todas existen en alguna cita
 *   'cualquieraDe'  => [<pred>, ...]             OR de predicados sobre la misma fila
 *
 * $ctx: contexto de ejecucion (filas + indices porCita/porPaciente).
 */
function mtrCumple(array $f, array $cond, array $ctx): bool {
    if (isset($cond['cod'])) {
        $cods = is_array($cond['cod']) ? $cond['cod'] : [$cond['cod']];
        $cods = array_map(fn($c) => strtoupper((string)$c), $cods);
        if (!in_array($f['cod'], $cods, true)) return false;
    }
    if (isset($cond['codPref'])) {
        $prefs = is_array($cond['codPref']) ? $cond['codPref'] : [$cond['codPref']];
        $ok = false;
        foreach ($prefs as $p) {
            $p = strtoupper((string)$p);
            if ($p !== '' && strpos($f['cod'], $p) === 0) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    if (isset($cond['codEntre'])) {
        $min = strtoupper((string)$cond['codEntre'][0]);
        $max = strtoupper((string)$cond['codEntre'][1]);
        if ($f['cod'] === '' || $f['cod'] < $min || $f['cod'] > $max) return false;
    }
    if (isset($cond['tip'])) {
        $tips = is_array($cond['tip']) ? $cond['tip'] : [$cond['tip']];
        $tips = array_map(fn($t) => strtoupper((string)$t), $tips);
        if (!in_array($f['tip'], $tips, true)) return false;
    }
    if (isset($cond['vl'])) {
        if ($cond['vl'] === 'NULL') {
            if ($f['vl'] !== null) return false;
        } else {
            $vals = is_array($cond['vl']) ? $cond['vl'] : [$cond['vl']];
            $vals = array_map(fn($v) => strtoupper((string)$v), $vals);
            if ($f['vl'] === null || !in_array($f['vl'], $vals, true)) return false;
        }
    }
    if (!empty($cond['vlVacia'])) {
        // como el T-SQL: (valor_lab in ('') or valor_lab is null)  [#PAP del RPT_01]
        if ($f['vl'] !== null && $f['vl'] !== '') return false;
    }
    if (isset($cond['vlNum'])) {
        // como TRY_CONVERT(int, valor_lab) del T-SQL [Temporal11 del RPT_01]
        [$min, $max] = $cond['vlNum'];
        if ($f['vl'] === null || !is_numeric($f['vl'])) return false;
        $v = (float)$f['vl'];
        if ($v < (float)$min) return false;
        if ($max !== null && $v > (float)$max) return false;
    }
    if (!empty($cond['vlPresente'])) {
        if ($f['vl'] === null || $f['vl'] === '') return false;
    }
    if (isset($cond['rownum'])) {
        if ($f['rownum'] === null || $f['rownum'] !== (int)$cond['rownum']) return false;
    }
    if (isset($cond['sexo'])) {
        if ($f['sexo'] !== strtoupper((string)$cond['sexo'])) return false;
    }
    if (isset($cond['edadA'])) {
        [$min, $max] = $cond['edadA'];
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return false;
        if ($f['edad'] < $min) return false;
        if ($max !== null && $f['edad'] > $max) return false;
    }
    if (isset($cond['edadD'])) {
        // RPT_05: id_tipedad_reg='D' and edad_reg between 1 and 29
        [$min, $max] = $cond['edadD'];
        if ($f['tipEdad'] !== 'D' || $f['edad'] === null) return false;
        if ($f['edad'] < $min) return false;
        if ($max !== null && $f['edad'] > $max) return false;
    }
    if (isset($cond['fgTipo'])) {
        if ($f['fg'] !== strtoupper((string)$cond['fgTipo'])) return false;
    }
    if (!empty($cond['gestante'])) {
        if (!mtrEsGestante($f)) return false;
    }
    if (!empty($cond['puerpera'])) {
        if (!mtrEsPuerpera($f)) return false;
    }
    if (isset($cond['otraCond'])) {
        if ($cond['otraCond'] === 'NULL') {
            if ($f['otraCond'] !== null && $f['otraCond'] !== '') return false;
        } else {
            $esperado = strtoupper(trim((string)$cond['otraCond']));
            if ($esperado === 'GESTANTE') {
                if (!mtrEsGestante($f)) return false;
            } elseif ($esperado === 'PUERPERA') {
                if (!mtrEsPuerpera($f)) return false;
            } else {
                if ($f['otraCond'] !== $esperado) return false;
            }
        }
    }
    if (isset($cond['hb'])) {
        [$min, $max] = $cond['hb'];
        if ($f['hb'] === null) return false;
        if ($f['hb'] < $min) return false;
        if ($max !== null && $f['hb'] > $max) return false;
    }
    if (!empty($cond['hbVacia'])) {
        if ($f['hb'] !== null) return false;
    }
    if (isset($cond['citaTiene'])) {
        if (!mtrCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!mtrCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['citaNoTiene'])) {
        // "id_cita not in (select id_cita from #ANEMIA)" del RPT_06
        if (mtrCitaTiene($ctx, $f['cita'], $cond['citaNoTiene'])) return false;
    }
    if (isset($cond['pacTiene'])) {
        if (!mtrPacTiene($ctx, $f['pac'], $cond['pacTiene'])) return false;
    }
    if (isset($cond['pacTieneTodo'])) {
        foreach ($cond['pacTieneTodo'] as $sub) {
            if (!mtrPacTiene($ctx, $f['pac'], $sub)) return false;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (mtrCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** EXISTS sobre la cita: alguna fila de la cita cumple el predicado (por indice). */
function mtrCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    foreach ($ctx['porCita'][$cita] as $idx) {
        if (mtrCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/** EXISTS sobre el paciente: alguna fila de cualquier cita del paciente lo cumple. */
function mtrPacTiene(array $ctx, string $pac, array $cond): bool {
    if ($pac === '' || !isset($ctx['porPaciente'][$pac])) return false;
    foreach ($ctx['porPaciente'][$pac] as $idx) {
        if (mtrCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Resuelve el grupo etareo (1..4) de una fila. Null si esta fuera de los
 * grupos del reporte (p.ej. 60+ anos: el reporte materno solo llega a 59).
 *
 * CASE gedad del T-SQL (con Tipo_Edad='A' y 10-59):
 *   1 = 10-11   2 = 12-17   3 = 18-29   4 = 30-59
 * RPT_05 (RN, Tipo_Edad='D'): todo cuenta en el grupo 1 (columna "N°").
 */
function mtrGedad(array $f): ?int {
    if ($f['tipEdad'] === 'D' || $f['tipEdad'] === 'M') return 1;
    if ($f['edad'] === null) return null;
    if ($f['tipEdad'] !== 'A' && $f['tipEdad'] !== '') return null;
    if ($f['edad'] < 12) return 1;
    if ($f['edad'] < 18) return 2;
    if ($f['edad'] < 30) return 3;
    if ($f['edad'] < 60) return 4;
    return null;
}

/**
 * Genera una descripcion SQL-legible de una condicion del DSL (para el panel
 * "Ver condiciones SQL" del reporte web: auditoria contra el archivo 03).
 */
function maternoCondicionSQL(array $cond, int $nivel = 0): string {
    $partes = [];
    foreach ($cond as $k => $v) {
        switch ($k) {
            case 'cod':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "codigo_item IN (" . implode(', ', array_map(fn($x) => "'" . $x . "'", $lista)) . ")";
                break;
            case 'codPref':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "(" . implode(' OR ', array_map(fn($x) => "codigo_item LIKE '" . $x . "%' (= cod_item_f)", $lista)) . ")";
                break;
            case 'codEntre':
                $partes[] = "codigo_item BETWEEN '{$v[0]}' AND '{$v[1]}'";
                break;
            case 'tip':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "tipo_diagnostico IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'vl':
                if ($v === 'NULL') { $partes[] = "valor_lab IS NULL"; }
                else {
                    $lista = is_array($v) ? $v : [$v];
                    $partes[] = "valor_lab IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                }
                break;
            case 'vlVacia':
                if ($v) $partes[] = "(valor_lab IS NULL OR valor_lab = '')";
                break;
            case 'vlNum':
                $partes[] = "TRY_CONVERT(int, valor_lab) BETWEEN {$v[0]} AND " . ($v[1] ?? 999);
                break;
            case 'vlPresente':
                if ($v) $partes[] = "valor_lab IS NOT NULL";
                break;
            case 'rownum':
                $partes[] = "I_ROWNUM_LAB = $v";
                break;
            case 'sexo':
                $partes[] = "id_genero = '$v'";
                break;
            case 'edadA':
                $partes[] = "tipo_edad = 'A' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 999);
                break;
            case 'edadD':
                $partes[] = "tipo_edad = 'D' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 999);
                break;
            case 'fgTipo':
                $partes[] = "fg_tipo = '$v'";
                break;
            case 'gestante':
                if ($v) $partes[] = "otra_condicion LIKE 'GESTANTE%'";
                break;
            case 'puerpera':
                if ($v) $partes[] = "otra_condicion LIKE 'PUERPER%'";
                break;
            case 'otraCond':
                $partes[] = $v === 'NULL' ? "otra_condicion IS NULL" : "otra_condicion = '$v'";
                break;
            case 'hb':
                $partes[] = "hemoglobina BETWEEN {$v[0]} AND " . ($v[1] ?? 999);
                break;
            case 'hbVacia':
                if ($v) $partes[] = "hemoglobina IS NULL";
                break;
            case 'citaTiene':
                $partes[] = "EXISTS (misma cita: " . maternoCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaTieneTodo':
                $sub = implode(' AND ', array_map(fn($s) => "EXISTS (misma cita: " . maternoCondicionSQL($s, $nivel + 1) . ")", $v));
                $partes[] = "($sub)";
                break;
            case 'citaNoTiene':
                $partes[] = "NOT EXISTS (misma cita: " . maternoCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'pacTiene':
                $partes[] = "EXISTS (mismo paciente: " . maternoCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'pacTieneTodo':
                $sub = implode(' AND ', array_map(fn($s) => "EXISTS (mismo paciente: " . maternoCondicionSQL($s, $nivel + 1) . ")", $v));
                $partes[] = "($sub)";
                break;
            case 'cualquieraDe':
                $sub = implode(' OR ', array_map(fn($s) => "(" . maternoCondicionSQL($s, $nivel + 1) . ")", $v));
                $partes[] = "($sub)";
                break;
        }
    }
    return implode(' AND ', $partes);
}

/* ============================================================
 * 3) DEFINICION DE SECCIONES (adaptacion fiel del archivo
 *    "03 Creacion de Procedimientos": RPT_01..RPT_10)
 * ============================================================ */

/**
 * Devuelve la definicion completa de las secciones del reporte de Materno.
 *
 * Cada seccion replica un bloque del Excel "Reporte_Actividades_Materno.xlsx"
 * y cada columna/fila replica una Categoria de los procedimientos
 * usp_TRAMA_BASE_MATERNO_2023_RPT_01..RPT_10 (mismo numero y orden).
 * La regla 'filas' cuenta cada fila HIS que cumple, como el count(*) del
 * T-SQL sobre las tablas nominales.
 */
function maternoSecciones(): array {
    // ===== Predicados base compartidos (WHERE comun de los RPT) =====
    $mujer1059 = ['sexo' => 'F', 'edadA' => [10, 59]];        // id_genero='F' and id_tipedad_reg='A' and edad between 10 and 59
    $rn        = ['edadD' => [1, 29]];                        // RPT_05: id_tipedad_reg='D' and edad between 1 and 29
    $filas     = ['tipo' => 'filas'];                         // count(*) del T-SQL

    // Codigos Z de atencion prenatal: I/II/III trimestre por codigo
    $zTrim1 = ['Z3491', 'Z3591'];
    $zTrim2 = ['Z3492', 'Z3592'];
    $zTrim3 = ['Z3493', 'Z3593'];
    $z6     = ['Z3491', 'Z3492', 'Z3493', 'Z3591', 'Z3592', 'Z3593'];
    $z8     = ['Z3491', 'Z3492', 'Z3493', 'Z3591', 'Z3592', 'Z3593', 'Z359', 'Z349'];

    // Trimestre de la gestante (EXISTS en la cita): como el T-SQL
    // "#PRUEBAS where cod in (Z3491,Z3591) OR (Z359/Z349 and valor in ('N') and ROWNUM=1)"
    $trim1 = ['cualquieraDe' => [
        ['cod' => $zTrim1, 'tip' => 'D'],
        ['cod' => ['Z359', 'Z349'], 'vl' => '1', 'rownum' => 1, 'tip' => 'D'],
    ]];
    $trim2 = ['cualquieraDe' => [
        ['cod' => $zTrim2, 'tip' => 'D'],
        ['cod' => ['Z359', 'Z349'], 'vl' => '2', 'rownum' => 1, 'tip' => 'D'],
    ]];
    $trim3 = ['cualquieraDe' => [
        ['cod' => $zTrim3, 'tip' => 'D'],
        ['cod' => ['Z359', 'Z349'], 'vl' => '3', 'rownum' => 1, 'tip' => 'D'],
    ]];

    // Laboratorios de tamizaje de transmision vertical (RPT_09_1)
    $vihLabs = ['86703.01', '86703.02', '87389', '86703'];
    $sifLabs = ['86780.01', '86592', '86593', '86780'];
    $hepLabs = ['87342', '87340', '82397', '86706', '86704', '86705', '87351', '86707'];
    // Fila de laboratorio con I_ROWNUM_LAB=1 (o prueba rapida 86318.01 1a vez)
    $vihRow = ['cualquieraDe' => [
        ['cod' => $vihLabs, 'rownum' => 1],
        ['cod' => '86318.01', 'rownum' => 1],
    ]];
    $sifRow = ['cualquieraDe' => [
        ['cod' => $sifLabs, 'rownum' => 1],
        ['cod' => '86318.01', 'rownum' => 1],
    ]];
    $hepRow = ['cod' => $hepLabs, 'rownum' => 1];
    // Base de #PRUEBAS: cualquier laboratorio de tamizaje (D o R) en la cita
    $tamizBase = ['cualquieraDe' => [
        ['cod' => array_merge($vihLabs, $sifLabs, $hepLabs), 'rownum' => 1, 'tip' => ['D', 'R']],
        ['cod' => '86318.01', 'tip' => ['D', 'R']],
    ]];
    // Z-row de gestante (parte de #PRUEBAS: la cita tiene codigo gestacional)
    $gestZ = ['cualquieraDe' => [
        ['cod' => $z6, 'tip' => 'D'],
        ['cod' => ['Z359', 'Z349'], 'vl' => ['1', '2', '3'], 'rownum' => 1, 'tip' => 'D'],
    ]];
    // Bases de #CONSEJ_VIH / #CONSEJ_SIFILIS / #CONSEJ_HEPATITIS
    $consejVihBase = ['cualquieraDe' => [
        ['cod' => $vihLabs, 'rownum' => 1, 'tip' => ['D', 'R']],
        ['cod' => '86318.01', 'tip' => ['D', 'R']],
    ]];
    $consejSifBase = ['cualquieraDe' => [
        ['cod' => $sifLabs, 'rownum' => 1, 'tip' => ['D', 'R']],
        ['cod' => '86318.01', 'tip' => ['D', 'R']],
    ]];
    $consejHepBase = ['cod' => $hepLabs, 'rownum' => 1, 'tip' => ['D', 'R']];
    // Consejeria previa al resultado: valor '1' (1a prueba) / '2' (2a prueba)
    $consejVih1 = ['cod' => '99401.33', 'vl' => '1', 'tip' => 'D'];
    $consejVih2 = ['cod' => '99401.33', 'vl' => '2', 'tip' => 'D'];
    $consejSif1 = ['cod' => '99402.05', 'vl' => '1', 'tip' => 'D'];
    $consejSif2 = ['cod' => '99402.05', 'vl' => '2', 'tip' => 'D'];
    $consejHep1 = ['cod' => '99402.05', 'vl' => '1', 'tip' => 'D'];
    $consejHep2 = ['cod' => '99402.05', 'vl' => '2', 'tip' => 'D'];

    // #EMBARAZO del RPT_01: citas con (96150.01/81002/82044/R456/81000.02/81007 D R1)
    // y ademas una fila Z8 D R1 (gestante en atencion prenatal)
    $embarazoR01 = ['citaTieneTodo' => [
        ['cod' => ['96150.01', '81002', '82044', 'R456', '81000.02', '81007'], 'tip' => 'D', 'rownum' => 1],
        ['cod' => $z8, 'tip' => 'D', 'rownum' => 1],
    ]];
    // #GEST del RPT_01/RPT_04: la cita tiene una fila de vacuna/procedimiento con
    // I_ROWNUM_LAB=1 y una fila con valor_lab='G' (gestante)
    $gestTodo = ['citaTieneTodo' => [
        ['cod' => ['90715', '90714', '90744', '90746', '90658', '90749.01', 'D1110'], 'rownum' => 1],
        ['vl' => 'G'],
    ]];
    // #EMBARAZO del RPT_02: citas con (59020/59025 valor '1'/'2') y ademas
    // (Z359 valor '3' D o Z3493/Z3593 D R1) => gestante de 2do/3er trimestre
    $embarazoR02 = ['citaTieneTodo' => [
        ['cod' => ['59020', '59025'], 'vl' => ['1', '2']],
        ['cualquieraDe' => [
            ['cod' => 'Z359', 'vl' => '3', 'tip' => 'D'],
            ['cod' => ['Z3493', 'Z3593'], 'tip' => 'D', 'rownum' => 1],
        ]],
    ]];

    // #SUPLEM del RPT_06 (union de 4 ramas):
    //   S1: (85018 '1' R1) + Z6 D R1            -> dosaje 1° en gestante (cualq. trim.)
    //   S2: (85018 '2'/'3' R1 o 99199.26 '1'/'6' R1 o 59401.05 '1'/'5' R1) + Z II/III trim D R1
    //   S3: (99199.18 '1'/'2' R1) + Z I trim D R1
    //   S4: (99199.26 'TA') + 59430 D R1         -> entrega a puerpera
    $S1 = ['citaTieneTodo' => [
        ['cod' => ['85018', '85018.01'], 'vl' => '1', 'rownum' => 1],
        ['cod' => $z6, 'tip' => 'D', 'rownum' => 1],
    ]];
    $S2 = ['citaTieneTodo' => [
        ['cualquieraDe' => [
            ['cod' => ['85018', '85018.01'], 'vl' => ['2', '3'], 'rownum' => 1],
            ['cod' => '99199.26', 'vl' => ['1', '6'], 'rownum' => 1],
            ['cod' => '59401.05', 'vl' => ['1', '5'], 'rownum' => 1],
        ]],
        ['cod' => ['Z3593', 'Z3493', 'Z3592', 'Z3492'], 'tip' => 'D', 'rownum' => 1],
    ]];
    $S3 = ['citaTieneTodo' => [
        ['cod' => '99199.18', 'vl' => ['1', '2'], 'rownum' => 1],
        ['cod' => ['Z3591', 'Z3491'], 'tip' => 'D', 'rownum' => 1],
    ]];
    $S4 = ['citaTieneTodo' => [
        ['cod' => '99199.26', 'vl' => 'TA'],
        ['cod' => '59430', 'tip' => 'D', 'rownum' => 1],
    ]];
    $suplem = ['cualquieraDe' => [$S1, $S2, $S3, $S4]];
    // #ANEMIA del RPT_06: "id_cita not in (select id_cita ... O990 R1)"
    $noAnemia = ['citaNoTiene' => ['cod' => 'O990', 'rownum' => 1]];

    // #PUERPERAS del RPT_09_2: citas con tamizaje y 59430 valor '1' D
    // (atencion de puerperio inmediato)
    $puerpera59430 = ['citaTiene' => ['cod' => '59430', 'vl' => '1', 'tip' => 'D']];

    // #TEMP del RPT_10 (union de #CONSEJ_GEST y #CONSEJ_PUERP):
    //  - gestante: 99401.02 D '3' y (Z3493/Z3593 D o Z359/Z349 '3' R1 D)
    //  - puerpera inmediata: 99401.02 D '4' y 59410/59515 R1
    //  - atencion puerperal: 99401.02 D '5' y 59430 R1
    $gestZ3 = ['cualquieraDe' => [
        ['cod' => ['Z3493', 'Z3593'], 'tip' => 'D'],
        ['cod' => ['Z359', 'Z349'], 'vl' => '3', 'rownum' => 1, 'tip' => 'D'],
    ]];
    $consejGest = ['citaTieneTodo' => [ ['cod' => '99401.02', 'tip' => 'D', 'vl' => '3'], $gestZ3 ]];
    $consejPuerp4 = ['citaTieneTodo' => [ ['cod' => '99401.02', 'tip' => 'D', 'vl' => '4'], ['cod' => ['59410', '59515'], 'rownum' => 1] ]];
    $consejPuerp5 = ['citaTieneTodo' => [ ['cod' => '99401.02', 'tip' => 'D', 'vl' => '5'], ['cod' => '59430', 'rownum' => 1] ]];
    $tempX = ['cualquieraDe' => [$consejGest, $consejPuerp4, $consejPuerp5]];

    return [

    /* ------------------------------------------------------------
     * SECCION I - RPT_01_APN_REENFOCADA (usptrama...RPT_01)
     * I. ATENCION PRENATAL REENFOCADA (25 categorias + Total calculado)
     * Temporales del T-SQL:
     *   #PAP       = citas (Z8 D R1) con 88141 valor null/''
     *   #POSITIVO  = citas (Z359 D R1) con D060/D061/D069/N879/N870/N871/N872 D R1
     *   #GEST      = citas (vacunas/D1110 R1) con alguna fila valor_lab='G'
     *   #EMBARAZO  = citas (96150.01/81002/82044/R456/81000.02/81007 D R1)
     *                con Z8 D R1
     *   #LAB_TA    = citas (Z3593/Z3493 D R1) con Z3593/Z3493 valor 'TA' R3
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT01_APN',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_01_APN_REENFOCADA',
        'titulo'   => 'I. ATENCIÓN PRENATAL REENFOCADA',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 16, 2 => 17, 3 => 18, 4 => 19, 'T' => 20], 'colIni' => 'B'],
        'columnas' => [
            // GESTANTE ATENDIDA (Total = I + II + III Trim, como =SUM(C:E) del Excel)
            ['key' => 1, 'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'Total',
             'regla' => ['tipo' => 'calc', 'cols' => [2, 3, 4]]],
            // Cat 1 (#NOMINAL): Z3491/Z3591 D valor '1' R1
            ['key' => 2, 'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'I Trim',
             'cond' => array_merge($mujer1059, ['cod' => $zTrim1, 'vl' => '1', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 2: Z3492/Z3592 D valor '1' R1
            ['key' => 3, 'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, ['cod' => $zTrim2, 'vl' => '1', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 3: Z3493/Z3593 D valor '1' R1
            ['key' => 4, 'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, ['cod' => $zTrim3, 'vl' => '1', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 4 (Temporal1): Z6 D R1 con valor '1'..'14' (todas las atenciones)
            ['key' => 5, 'niv1' => 'Gestante', 'niv2' => 'Atenciones', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => $z6, 'vl' => ['1','2','3','4','5','6','7','8','9','10','11','12','13','14'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 5 (#NOMINAL): Z6 D R1 con valor '6' (gestante CONTROLADA)
            ['key' => 6, 'niv1' => 'Gestante', 'niv2' => 'Controlada', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => $z6, 'vl' => '6', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 6 (Temporal2 + #PAP): Z8 D R1 y la cita tiene 88141 con valor null/''
            ['key' => 7, 'niv1' => 'Papanicolaou', 'niv2' => 'Toma de Muestra', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => $z8, 'tip' => 'D', 'rownum' => 1],
                        ['citaTiene' => ['cod' => '88141', 'vlVacia' => true]]),
             'regla' => $filas],
            // Cat 7 (Temporal3 + #POSITIVO): Z359 D R1 y la cita tiene Dx de lesion D R1
            ['key' => 8, 'niv1' => 'Papanicolaou', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => 'Z359', 'tip' => 'D', 'rownum' => 1],
                        ['citaTiene' => ['cod' => ['D060','D061','D069','N879','N870','N871','N872'], 'tip' => 'D', 'rownum' => 1]]),
             'regla' => $filas],
            // Cat 8 (Temporal4): 80055.01 D R1 (1a bateria completa)
            ['key' => 9, 'niv1' => 'Gestante Controlada con Batería Completa', 'niv2' => '1° Batería', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => '80055.01', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 9: 80055.02 D R1 (2a bateria completa)
            ['key' => 10, 'niv1' => 'Gestante Controlada con Batería Completa', 'niv2' => '2° Batería', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => '80055.02', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 10 (Temporal5 + #EMBARAZO): 96150.01 D R1 (VBG tamizada)
            ['key' => 11, 'niv1' => 'Violencia Basada en Género (VBG)', 'niv2' => 'Tamizada', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => '96150.01', 'tip' => 'D', 'rownum' => 1], $embarazoR01),
             'regla' => $filas],
            // Cat 11 (Temporal5 + #EMBARAZO): R456 D R1 (VBG positivo)
            ['key' => 12, 'niv1' => 'Violencia Basada en Género (VBG)', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => 'R456', 'tip' => 'D', 'rownum' => 1], $embarazoR01),
             'regla' => $filas],
            // Cat 12 (Temporal4): 76817/76805 D valor '1' (1a ecografia)
            ['key' => 13, 'niv1' => 'Ecografía', 'niv2' => '1° Ecografía', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['76817', '76805'], 'vl' => '1', 'tip' => 'D']),
             'regla' => $filas],
            ['key' => 14, 'niv1' => 'Ecografía', 'niv2' => '2° Ecografía', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['76817', '76805'], 'vl' => '2', 'tip' => 'D']),
             'regla' => $filas],
            ['key' => 15, 'niv1' => 'Ecografía', 'niv2' => '3° Ecografía', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['76817', '76805'], 'vl' => '3', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 15 (Temporal5 + #EMBARAZO): 81000.02/81002/81007 D R1 (tamizaje bacteriuria)
            ['key' => 16, 'niv1' => 'Tamizaje de Bacteriuria', 'niv2' => 'N°', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['81000.02', '81002', '81007'], 'tip' => 'D', 'rownum' => 1], $embarazoR01),
             'regla' => $filas],
            // Cat 16 (Temporal8 + #EMBARAZO): idem con valor 'RP' (positivo)
            ['key' => 17, 'niv1' => 'Tamizaje de Bacteriuria', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['81000.02', '81002', '81007'], 'tip' => 'D', 'vl' => 'RP'], $embarazoR01),
             'regla' => $filas],
            // Cat 17 (Temporal5 + #EMBARAZO): 82044 D R1 (tamizaje proteinuria)
            ['key' => 18, 'niv1' => 'Tamizaje de Proteniuria', 'niv2' => 'N°', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => '82044', 'tip' => 'D', 'rownum' => 1], $embarazoR01),
             'regla' => $filas],
            // Cat 18 (Temporal8 + #EMBARAZO): 82044 D valor 'RP' (positivo)
            ['key' => 19, 'niv1' => 'Tamizaje de Proteniuria', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => '82044', 'tip' => 'D', 'vl' => 'RP'], $embarazoR01),
             'regla' => $filas],
            // Cat 19 (Temporal11 + #LAB_TA): Z3593/Z3493 D R1 con TRY_CONVERT(int,valor)>=6
            // y la cita tiene Z3593/Z3493 valor 'TA' con I_ROWNUM_LAB=3
            ['key' => 20, 'niv1' => 'Gestante con Atención Prenatal Reenfocada', 'niv2' => '----------', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['Z3593', 'Z3493'], 'tip' => 'D', 'rownum' => 1, 'vlNum' => [6, null]],
                        ['citaTiene' => ['cod' => ['Z3593', 'Z3493'], 'vl' => 'TA', 'rownum' => 3]]),
             'regla' => $filas],
            // Cat 20 (Temporal12 + #GEST): 90715 D R1 (dTpa)
            ['key' => 21, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'DTPa', 'niv3' => 'Protegidas',
             'cond' => array_merge($mujer1059, ['cod' => '90715', 'tip' => 'D', 'rownum' => 1], $gestTodo),
             'regla' => $filas],
            // Cat 21: 90714 D valor '2' (dT)
            ['key' => 22, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'dT', 'niv3' => 'Protegidas',
             'cond' => array_merge($mujer1059, ['cod' => '90714', 'tip' => 'D', 'vl' => '2'], $gestTodo),
             'regla' => $filas],
            // Cat 22: 90744/90746 D valor '3' (Hepatitis B)
            ['key' => 23, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'HvB', 'niv3' => 'Protegidas',
             'cond' => array_merge($mujer1059, ['cod' => ['90744', '90746'], 'tip' => 'D', 'vl' => '3'], $gestTodo),
             'regla' => $filas],
            // Cat 23: 90658 D R1 (Influenza)
            ['key' => 24, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'Influenza', 'niv3' => 'Protegidas',
             'cond' => array_merge($mujer1059, ['cod' => '90658', 'tip' => 'D', 'rownum' => 1], $gestTodo),
             'regla' => $filas],
            // Cat 24: 90749.01 D R1 (COVID)
            ['key' => 25, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'COVID', 'niv3' => 'Protegidas',
             'cond' => array_merge($mujer1059, ['cod' => '90749.01', 'tip' => 'D', 'rownum' => 1], $gestTodo),
             'regla' => $filas],
            // Cat 25 (Temporal13 + #GEST): D1110 D valor '1' (atencion odontologica)
            ['key' => 26, 'niv1' => 'Atención Odontológica', 'niv2' => 'Protegidas', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => 'D1110', 'tip' => 'D', 'vl' => '1'], $gestTodo),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION II - RPT_02_BIENESTAR
     * II. EVALUACION DE BIENESTAR FETAL, PSICOPROFILAXIS Y ESTIMULACION
     * #EMBARAZO(RPT_02) = citas (59020/59025 valor 1/2) y
     *   (Z359 valor '3' D o Z3493/Z3593 D R1)
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT02_BIENESTAR',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_02_BIENESTAR',
        'titulo'   => 'II. EVALUACIÓN DE BIENESTAR FETAL, PSICOPROFILAXIS Y ESTIMULACIÓN PRENATAL',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 25, 2 => 26, 3 => 27, 4 => 28, 'T' => 29], 'colIni' => 'B'],
        'columnas' => [
            // Cat 1 (#NOMINAL + #EMBARAZO): 59020/59025 D valor '1' (1° monitoreo)
            ['key' => 1, 'niv1' => 'Evaluación de Bienestar Fetal', 'niv2' => '1° Monitoreo',
             'cond' => array_merge($mujer1059, ['cod' => ['59020', '59025'], 'vl' => '1', 'tip' => 'D'], $embarazoR02),
             'regla' => $filas],
            // Cat 2: 59020/59025 D valor '2' (2° monitoreo)
            ['key' => 2, 'niv1' => 'Evaluación de Bienestar Fetal', 'niv2' => '2° Monitoreo',
             'cond' => array_merge($mujer1059, ['cod' => ['59020', '59025'], 'vl' => '2', 'tip' => 'D'], $embarazoR02),
             'regla' => $filas],
            // Cat 3 (Temporal1): 99412.02 D valor '1' (psicoprofilaxis atendida)
            ['key' => 3, 'niv1' => 'Psicoprofilaxis', 'niv2' => 'Atendida',
             'cond' => array_merge($mujer1059, ['cod' => '99412.02', 'vl' => '1', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 4: 99412.02 D valor '6' (psicoprofilaxis preparada)
            ['key' => 4, 'niv1' => 'Psicoprofilaxis', 'niv2' => 'Preparada',
             'cond' => array_merge($mujer1059, ['cod' => '99412.02', 'vl' => '6', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 5: 99412.01 D valor '1' (estimulacion 1° sesion)
            ['key' => 5, 'niv1' => 'Estimulación Prenatal', 'niv2' => '1° Sesión',
             'cond' => array_merge($mujer1059, ['cod' => '99412.01', 'vl' => '1', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 6: 99412.01 D valor '6' (estimulacion 6° sesion)
            ['key' => 6, 'niv1' => 'Estimulación Prenatal', 'niv2' => '6° Sesión',
             'cond' => array_merge($mujer1059, ['cod' => '99412.01', 'vl' => '6', 'tip' => 'D']),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION III - RPT_03_ANEMIA
     * III. GESTANTE CON ANEMIA, MANEJO TERAPEUTICO Y PLAN DE PARTO
     * #EMBARAZO(RPT_03) = citas (99199.26 valor 1/6 o 85018 valor '1' D)
     *                     y una fila Z8 D
     * #ANEMIA(RPT_03)   = citas (99199.26 valor 1/6 o 85018 valor '1' R1)
     *                     con O990 R1
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT03_ANEMIA',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_03_ANEMIA',
        'titulo'   => 'III. GESTANTE CON ANEMIA, MANEJO TERAPÉUTICO Y PLAN DE PARTO',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 25, 2 => 26, 3 => 27, 4 => 28, 'T' => 29], 'colIni' => 'J'],
        'columnas' => [
            // Cat 1 (#NOMINAL): O990 D valor 'LEV' (anemia leve)
            ['key' => 1, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Leve',
             'cond' => array_merge($mujer1059, ['cod' => 'O990', 'vl' => 'LEV', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 2: O990 D valor 'MOD'
            ['key' => 2, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Moderada',
             'cond' => array_merge($mujer1059, ['cod' => 'O990', 'vl' => 'MOD', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 3: O990 D valor 'SEV'
            ['key' => 3, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Severa',
             'cond' => array_merge($mujer1059, ['cod' => 'O990', 'vl' => 'SEV', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 4: O990 R valor 'PR' (anemia recuperada)
            ['key' => 4, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Recuperada',
             'cond' => array_merge($mujer1059, ['cod' => 'O990', 'vl' => 'PR', 'tip' => 'R']),
             'regla' => $filas],
            // Cat 5 (Temporal0): 99199.26 D valor '1' y cita en #EMBARAZO (fila Z D)
            // y cita con O990 R1 tipo D (en #ANEMIA con id_tipitem='D')
            ['key' => 5, 'niv1' => 'Gestante', 'niv2' => 'Manejo Terapéutico', 'niv3' => '1° Entrega',
             'cond' => array_merge($mujer1059, ['cod' => '99199.26', 'vl' => '1', 'tip' => 'D'],
                        ['citaTieneTodo' => [ ['cod' => $z8, 'tip' => 'D'], ['cod' => 'O990', 'rownum' => 1, 'tip' => 'D'] ]]),
             'regla' => $filas],
            // Cat 6: 99199.26 D valor '6' y cita en #EMBARAZO y #ANEMIO con id_tipitem='R'
            ['key' => 6, 'niv1' => 'Gestante', 'niv2' => 'Manejo Terapéutico', 'niv3' => '6° Entrega',
             'cond' => array_merge($mujer1059, ['cod' => '99199.26', 'vl' => '6', 'tip' => 'D'],
                        ['citaTieneTodo' => [ ['cod' => $z8, 'tip' => 'D'], ['cod' => 'O990', 'rownum' => 1, 'tip' => 'R'] ]]),
             'regla' => $filas],
            // Cat 7 (Temporal0): 85018/85018.01 D valor '1' y cita en #ANEMIA
            // (#ANEMIA = base 99199.26 1/6 o 85018 '1' R1, con O990 R1)
            ['key' => 7, 'niv1' => 'Gestante', 'niv2' => 'Dosaje de Hemoglobina', 'niv3' => '1° Dosaje',
             'cond' => array_merge($mujer1059, ['cod' => ['85018', '85018.01'], 'vl' => '1', 'tip' => 'D'],
                        ['citaTieneTodo' => [
                            ['cod' => 'O990', 'rownum' => 1],
                            ['cualquieraDe' => [
                                ['cod' => '99199.26', 'vl' => ['1', '6']],
                                ['cod' => ['85018', '85018.01'], 'vl' => '1', 'rownum' => 1],
                            ]],
                        ]]),
             'regla' => $filas],
            // Cat 8 (Temporal1): U1692/59401.06 D valor '1' (plan de parto 1° entrevista)
            ['key' => 8, 'niv1' => 'Plan de Parto', 'niv2' => '1° Entrevista', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['U1692', '59401.06'], 'vl' => '1', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 9: valor '2'
            ['key' => 9, 'niv1' => 'Plan de Parto', 'niv2' => '2° Entrevista', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['U1692', '59401.06'], 'vl' => '2', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 10: valor '3'
            ['key' => 10, 'niv1' => 'Plan de Parto', 'niv2' => '3° Entrevista', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['U1692', '59401.06'], 'vl' => '3', 'tip' => 'D']),
             'regla' => $filas],
            // Cat 11: valor 'TA' (plan de parto efectivo)
            ['key' => 11, 'niv1' => 'Plan de Parto', 'niv2' => 'Efectivo', 'niv3' => '----------',
             'cond' => array_merge($mujer1059, ['cod' => ['U1692', '59401.06'], 'vl' => 'TA', 'tip' => 'D']),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IV - RPT_04_COMPLICACIONES (15 filas)
     * #NOMINAL = codigos exactos; Temporal1 = familias cod_item_f (prefijos)
     * #GEST (para TBC) = citas con A15/A16 D R1 y alguna fila valor 'G'
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT04_COMPLICACIONES',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_04_COMPLICACIONES',
        'titulo'   => 'IV. ATENCIÓN DE LA GESTANTE CON COMPLICACIONES',
        'eje'      => 'categoria',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['colTotal' => 'F', 'colGedad' => [1 => 'G', 2 => 'H', 3 => 'I', 4 => 'J'], 'filaIni' => 33],
        'filas' => [
            // Cat 1 (#NOMINAL): O470/O60X D R1
            ['key' => 1, 'label' => 'Amenaza de parto prematuro',
             'cond' => array_merge($mujer1059, ['cod' => ['O470', 'O60X'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 2 (Temporal1): cod_item_f in (O20,O03,O06,O01,O02)
            ['key' => 2, 'label' => 'Hemorragias de la 1º mitad del embarazo sin laparotomía',
             'cond' => array_merge($mujer1059, ['codPref' => ['O20', 'O03', 'O06', 'O01', 'O02'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 3: cod_item_f in (O44,O45,O71)
            ['key' => 3, 'label' => 'Hemorragia de la 2º mitad del embarazo',
             'cond' => array_merge($mujer1059, ['codPref' => ['O44', 'O45', 'O71'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 4: cod_item_f O21
            ['key' => 4, 'label' => 'Hiperémesis gravídica',
             'cond' => array_merge($mujer1059, ['codPref' => 'O21', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 5: cod_item_f O23
            ['key' => 5, 'label' => 'Infección del tracto urinario en el embarazo',
             'cond' => array_merge($mujer1059, ['codPref' => 'O23', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 6: cod_item_f O41
            ['key' => 6, 'label' => 'Ruptura prematura de membranas y otras relacionadas',
             'cond' => array_merge($mujer1059, ['codPref' => 'O41', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 7 (#NOMINAL): O009 D R1
            ['key' => 7, 'label' => 'Hemorragias de la 1º mitad del embarazo con laparotomía',
             'cond' => array_merge($mujer1059, ['cod' => 'O009', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 8 (Temporal1): cod_item_f in (O11,O13,O14)
            ['key' => 8, 'label' => 'Trastorno hipertensivos en el Embarazo',
             'cond' => array_merge($mujer1059, ['codPref' => ['O11', 'O13', 'O14'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 9: E010 (#NOMINAL) o cod_item_f O24/E05 (Temporal1)
            ['key' => 9, 'label' => 'Trastornos metabólicos del embarazo',
             'cond' => array_merge($mujer1059, ['cualquieraDe' => [
                 ['cod' => 'E010', 'tip' => 'D', 'rownum' => 1],
                 ['codPref' => 'O24', 'tip' => 'D', 'rownum' => 1],
                 ['codPref' => 'E05', 'tip' => 'D', 'rownum' => 1],
             ]]),
             'regla' => $filas],
            // Cat 10 (#NOMINAL): O40X/O410/O48X/O360/O362 D R1
            ['key' => 10, 'label' => 'Otras enfermedades del embarazo',
             'cond' => array_merge($mujer1059, ['cod' => ['O40X', 'O410', 'O48X', 'O360', 'O362'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 11 (#NOMINAL): O85X D R1
            ['key' => 11, 'label' => 'Sepsis',
             'cond' => array_merge($mujer1059, ['cod' => 'O85X', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 12: O980 (#NOMINAL) o A15/A16 + #GEST (TBC en gestante)
            ['key' => 12, 'label' => 'TBC',
             'cond' => array_merge($mujer1059, ['cualquieraDe' => [
                 ['cod' => 'O980', 'tip' => 'D', 'rownum' => 1],
                 ['codPref' => ['A15', 'A16'], 'tip' => 'D', 'rownum' => 1, 'citaTiene' => ['vl' => 'G']],
             ]]),
             'regla' => $filas],
            // Cat 13 (#NOMINAL): O730/O731 D R1
            ['key' => 13, 'label' => 'Retención de Placentaria',
             'cond' => array_merge($mujer1059, ['cod' => ['O730', 'O731'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 14 (#NOMINAL): O420/O421/O422/O429 D R1
            ['key' => 14, 'label' => 'Ruptura prematura de las membranas',
             'cond' => array_merge($mujer1059, ['cod' => ['O420', 'O421', 'O422', 'O429'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 15 (#NOMINAL): O450/O458/O459 D R1
            ['key' => 15, 'label' => 'Desprendimiento Prematuro de la Placenta',
             'cond' => array_merge($mujer1059, ['cod' => ['O450', 'O458', 'O459'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION V - RPT_05_MORBILIDAD_RN (7 filas, columna N unica)
     * RN: id_tipedad_reg='D' and edad_reg between 1 and 29 (sin filtro de sexo)
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT05_MORBILIDAD_RN',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_05_MORBILIDAD_RN',
        'titulo'   => 'V. MORBILIDAD DEL RN',
        'eje'      => 'categoria',
        'gedades'  => [1],            // RN: todo cuenta en '<12 a.'
        'soloTotal'=> true,           // el Excel solo muestra la columna 'N°'
        'xmap'     => ['colTotal' => 'O', 'colGedad' => [1 => null], 'filaIni' => 33],
        'filas' => [
            // Cat 1: P050/P070/P071/P0711/P0712 D R1 (bajo peso)
            ['key' => 1, 'label' => 'Bajo Peso',
             'cond' => array_merge($rn, ['cod' => ['P050', 'P070', 'P071', 'P0711', 'P0712'], 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 2: P072 D R1 (prematuro)
            ['key' => 2, 'label' => 'Prematuro',
             'cond' => array_merge($rn, ['cod' => 'P072', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 3: cod_item_f P21 (hipoxia)
            ['key' => 3, 'label' => 'Hipoxia',
             'cond' => array_merge($rn, ['codPref' => 'P21', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 4: P240 o cod_item_f P22/P23 (SDR)
            ['key' => 4, 'label' => 'Síndrome de Distrés Respiratorio',
             'cond' => array_merge($rn, ['cualquieraDe' => [
                 ['cod' => 'P240', 'tip' => 'D', 'rownum' => 1],
                 ['codPref' => 'P22', 'tip' => 'D', 'rownum' => 1],
                 ['codPref' => 'P23', 'tip' => 'D', 'rownum' => 1],
             ]]),
             'regla' => $filas],
            // Cat 5: cod_item_f P36 (sepsis neonatal)
            ['key' => 5, 'label' => 'Sepsis Neonatal',
             'cond' => array_merge($rn, ['codPref' => 'P36', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 6: cod_item_f A50 (sifilis congenita)
            ['key' => 6, 'label' => 'Sífilis Congénita',
             'cond' => array_merge($rn, ['codPref' => 'A50', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 7: Z206 D R1 (RN VIH expuesto)
            ['key' => 7, 'label' => 'RN - VIH Expuesto',
             'cond' => array_merge($rn, ['cod' => 'Z206', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION VI - RPT_06_ADMIN_MICRONUT (10 columnas)
     * #SUPLEM = union de 4 ramas (S1..S4); se excluyen citas con O990 R1
     * (#ANEMIA): a esas gestantes se les entrega sulfato ferroso por
     * manejo TERAPEUTICO (seccion III), no preventivo.
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT06_ADMIN_MICRONUT',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_06_ADMIN_MICRONUT',
        'titulo'   => 'VI. TAMIZAJE DE HB Y ADMINISTRACIÓN PREVENTIVA DE MICRONUTRIENTES',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 53, 2 => 54, 3 => 55, 4 => 56, 'T' => 57], 'colIni' => 'B'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'Dosaje de Hemoglobina', 'niv2' => '1°',
             'cond' => array_merge($mujer1059, ['cod' => ['85018', '85018.01'], 'vl' => '1', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 2, 'niv1' => 'Dosaje de Hemoglobina', 'niv2' => '2°',
             'cond' => array_merge($mujer1059, ['cod' => ['85018', '85018.01'], 'vl' => '2', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 3, 'niv1' => 'Dosaje de Hemoglobina', 'niv2' => '3°',
             'cond' => array_merge($mujer1059, ['cod' => ['85018', '85018.01'], 'vl' => '3', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 4, 'niv1' => 'Suplem. Con Sulfato Ferroso', 'niv2' => 'Atendida',
             'cond' => array_merge($mujer1059, ['cod' => '99199.26', 'vl' => '1', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 5, 'niv1' => 'Suplem. Con Sulfato Ferroso', 'niv2' => 'Suplementada - Gestante',
             'cond' => array_merge($mujer1059, ['cod' => '99199.26', 'vl' => '6', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 6, 'niv1' => 'Suplem. Con Sulfato Ferroso', 'niv2' => 'Suplementada - Puérpera',
             'cond' => array_merge($mujer1059, ['cod' => '99199.26', 'vl' => 'TA', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 7, 'niv1' => 'Suplem. Con Ácido Fólico', 'niv2' => '1°',
             'cond' => array_merge($mujer1059, ['cod' => '99199.18', 'vl' => '1', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 8, 'niv1' => 'Suplem. Con Ácido Fólico', 'niv2' => '2°',
             'cond' => array_merge($mujer1059, ['cod' => '99199.18', 'vl' => '2', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 9, 'niv1' => 'Suplem. Cálcio', 'niv2' => 'Atendida 1° Dosis',
             'cond' => array_merge($mujer1059, ['cod' => '59401.05', 'vl' => '1', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
            ['key' => 10, 'niv1' => 'Suplem. Cálcio', 'niv2' => 'Suplementada 5° Dosis',
             'cond' => array_merge($mujer1059, ['cod' => '59401.05', 'vl' => '5', 'tip' => 'D'], $suplem, $noAnemia),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION VII - RPT_07_PUERPERIO (3 columnas)
     * Cat 1/2 = 59430 valor '1'/'2'; Cat 3 = O85X/O152 (#NOMINAL)
     * o cod_item_f O91/O86/O90 (Temporal1)
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT07_PUERPERIO',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_07_PUERPERIO',
        'titulo'   => 'VII. ATENCIÓN DE PUERPERIO',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 53, 2 => 54, 3 => 55, 4 => 56, 'T' => 57], 'colIni' => 'N'],
        'columnas' => [
            // Cat 1 (#NOMINAL): 59430 D valor '1' R1 (puerperio atendida)
            ['key' => 1, 'niv1' => 'Puerperio', 'niv2' => 'Atendida',
             'cond' => array_merge($mujer1059, ['cod' => '59430', 'vl' => '1', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 2: 59430 D valor '2' R1 (puerperio controlada)
            ['key' => 2, 'niv1' => 'Puerperio', 'niv2' => 'Controlada',
             'cond' => array_merge($mujer1059, ['cod' => '59430', 'vl' => '2', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
            // Cat 3: O85X/O152 D R1 o cod_item_f O91/O86/O90 D R1 (complicada)
            ['key' => 3, 'niv1' => 'Puerperio', 'niv2' => 'Complicada',
             'cond' => array_merge($mujer1059, ['cualquieraDe' => [
                 ['cod' => ['O85X', 'O152'], 'tip' => 'D', 'rownum' => 1],
                 ['codPref' => ['O91', 'O86', 'O90'], 'tip' => 'D', 'rownum' => 1],
             ]]),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION VIII - RPT_08_VISITA (2 filas x 3 grupos etareos)
     * #VISITA = citas (Z359/Z349 D R1) con C0011 D R1 (visita domiciliaria)
     * Cat 2 = 99501 D R1 (atencion integral a la puerpera)
     * La plantilla oficial usa una mini-tabla de 2 filas x 3 columnas
     * (12-17 / 18-29 / 30-59), filas 50-51.
     * CAMBIO 2026-09-05 r2: en la web se agrego la columna Total al final
     * de cada fila = "12 - 17 a." + "18 - 29 a." + "30 - 59 a." (conTotal
     * + totalPos 'fin'). La plantilla oficial NO tiene esa columna, por lo
     * que el export a Excel la omite (xmap sin colTotal, igual que antes).
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT08_VISITA',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_08_VISITA',
        'titulo'   => 'VIII. VISITA DOMICILIARIA',
        'eje'      => 'categoria',
        'gedades'  => [2, 3, 4],      // 12-17 / 18-29 / 30-59 (sin '<12'; el total es columna, no fila)
        'conTotal' => true,           // columna Total web = suma de las 3 columnas etareas
        'totalPos' => 'fin',          // Total a la derecha de "30 - 59 a." (IV la usa al inicio)
        'xmap'     => ['colGedad' => [2 => 'S', 3 => 'T', 4 => 'U'], 'filaIni' => 50],
        'filas' => [
            // Cat 1 (#NOMINAL + #VISITA): Z359/Z349 D R1 y la cita tiene C0011 D R1
            ['key' => 1, 'label' => 'A Gestante',
             'cond' => array_merge($mujer1059, ['cod' => ['Z359', 'Z349'], 'tip' => 'D', 'rownum' => 1],
                        ['citaTiene' => ['cod' => 'C0011', 'tip' => 'D', 'rownum' => 1]]),
             'regla' => $filas],
            // Cat 2 (#NOMINAL): 99501 D R1
            ['key' => 2, 'label' => 'A Puérpera',
             'cond' => array_merge($mujer1059, ['cod' => '99501', 'tip' => 'D', 'rownum' => 1]),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IX-1 - RPT_09_1_TRANSMISION_VERTICAL (21 columnas)
     * Temporales: #PRUEBAS (tamizajes + Z de gestante), #CONSEJ_VIH,
     * #CONSEJ_SIFILIS, #CONSEJ_HEPATITIS, #TEMP (union de consejerias).
     * 1° vs 2° tamizaje = Tipo_Diagnostico 'D' vs 'R'; el trimestre lo da
     * el codigo Z (Z3491/Z3591 = I, Z3492/Z3592 = II, Z3493/Z3593 = III
     * o Z359/Z349 con valor 1/2/3 y R1).
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT09_1_TRANSMISION_VERTICAL',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_09_1_TRANSMISION_VERTICAL',
        'titulo'   => 'IX. TRANSMISIÓN VERTICAL — GESTANTES',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 64, 2 => 65, 3 => 66, 4 => 67, 'T' => 68], 'colIni' => 'B'],
        'columnas' => [
            // VIH/SIDA - 1° Tamizaje (Tipo 'D'): I / II / III trimestre
            ['key' => 1, 'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'I Trim',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim1, $consejVih1]]),
             'regla' => $filas],
            ['key' => 2, 'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim2, $consejVih1]]),
             'regla' => $filas],
            ['key' => 3, 'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim3, $consejVih1]]),
             'regla' => $filas],
            // VIH/SIDA - 1° Tamizaje Reactivo (Temporal3): labs 'RP' o 86318.01 'RP' R1,
            // cita en #PRUEBAS y #CONSEJ_VIH valor '1'
            ['key' => 4, 'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $vihLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 1],
                        ]],
                        ['citaTieneTodo' => [$tamizBase, $gestZ, $consejVihBase, $consejVih1]]),
             'regla' => $filas],
            // VIH/SIDA - 2° Tamizaje (Tipo 'R'): II / III trimestre
            ['key' => 5, 'niv1' => 'VIH/SIDA', 'niv2' => '2° Tamizaje', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'R'],
                        ['citaTieneTodo' => [$trim2, $consejVih2]]),
             'regla' => $filas],
            ['key' => 6, 'niv1' => 'VIH/SIDA', 'niv2' => '2° Tamizaje', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'R'],
                        ['citaTieneTodo' => [$trim3, $consejVih2]]),
             'regla' => $filas],
            // VIH/SIDA - 2° Tamizaje Reactivo: labs 'RP' o 86318.01 'RP' R1 y #CONSEJ_VIH valor '2'
            ['key' => 7, 'niv1' => 'VIH/SIDA', 'niv2' => '2° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $vihLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 1],
                        ]],
                        ['citaTieneTodo' => [$tamizBase, $gestZ, $consejVihBase, $consejVih2]]),
             'regla' => $filas],
            // SIFILIS - 1° Tamizaje (Tipo 'D'): I / II / III trimestre
            ['key' => 8, 'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'I Trim',
             'cond' => array_merge($mujer1059, $sifRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim1, $consejSif1]]),
             'regla' => $filas],
            ['key' => 9, 'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, $sifRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim2, $consejSif1]]),
             'regla' => $filas],
            ['key' => 10, 'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, $sifRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim3, $consejSif1]]),
             'regla' => $filas],
            // SIFILIS - 1° Tamizaje Positivo: labs 'RP' o 86318.01 'RP' R2 y #CONSEJ_SIF '1'
            ['key' => 11, 'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'Positivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $sifLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 2],
                        ]],
                        ['citaTieneTodo' => [$tamizBase, $gestZ, $consejSifBase, $consejSif1]]),
             'regla' => $filas],
            // SIFILIS - 2° Tamizaje (Tipo 'R'): II / III trimestre
            ['key' => 12, 'niv1' => 'SIFILIS', 'niv2' => '2° Tamizaje', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, $sifRow, ['tip' => 'R'],
                        ['citaTieneTodo' => [$trim2, $consejSif2]]),
             'regla' => $filas],
            ['key' => 13, 'niv1' => 'SIFILIS', 'niv2' => '2° Tamizaje', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, $sifRow, ['tip' => 'R'],
                        ['citaTieneTodo' => [$trim3, $consejSif2]]),
             'regla' => $filas],
            // SIFILIS - 2° Tamizaje Positivo: labs 'RP' o 86318.01 'RP' R2 y #CONSEJ_SIF '2'
            ['key' => 14, 'niv1' => 'SIFILIS', 'niv2' => '2° Tamizaje', 'niv3' => 'Positivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $sifLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 2],
                        ]],
                        ['citaTieneTodo' => [$tamizBase, $gestZ, $consejSifBase, $consejSif2]]),
             'regla' => $filas],
            // HEPATITIS B - 1° Tamizaje (Tipo 'D'): I / II / III trimestre
            ['key' => 15, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'I Trim',
             'cond' => array_merge($mujer1059, $hepRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim1, $consejHep1]]),
             'regla' => $filas],
            ['key' => 16, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, $hepRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim2, $consejHep1]]),
             'regla' => $filas],
            ['key' => 17, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, $hepRow, ['tip' => 'D'],
                        ['citaTieneTodo' => [$trim3, $consejHep1]]),
             'regla' => $filas],
            // HEPATITIS B - 1° Tamizaje Reactivo: labs 'RP' y #CONSEJ_HEPATITIS '1'
            ['key' => 18, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => array_merge($mujer1059, ['cod' => $hepLabs, 'vl' => 'RP', 'tip' => 'D'],
                        ['citaTieneTodo' => [$tamizBase, $gestZ, $consejHepBase, $consejHep1]]),
             'regla' => $filas],
            // HEPATITIS B - 2° Tamizaje (Tipo 'R'): II / III trimestre
            ['key' => 19, 'niv1' => 'HEPATITIS B', 'niv2' => '2° Tamizaje', 'niv3' => 'II Trim',
             'cond' => array_merge($mujer1059, $hepRow, ['tip' => 'R'],
                        ['citaTieneTodo' => [$trim2, $consejHep2]]),
             'regla' => $filas],
            ['key' => 20, 'niv1' => 'HEPATITIS B', 'niv2' => '2° Tamizaje', 'niv3' => 'III Trim',
             'cond' => array_merge($mujer1059, $hepRow, ['tip' => 'R'],
                        ['citaTieneTodo' => [$trim3, $consejHep2]]),
             'regla' => $filas],
            // HEPATITIS B - 2° Tamizaje Reactivo: labs 'RP' y #CONSEJ_HEPATITIS '2'
            // (los reactivos del T-SQL son siempre id_tipitem='D')
            ['key' => 21, 'niv1' => 'HEPATITIS B', 'niv2' => '2° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => array_merge($mujer1059, ['cod' => $hepLabs, 'vl' => 'RP', 'tip' => 'D'],
                        ['citaTieneTodo' => [$tamizBase, $gestZ, $consejHepBase, $consejHep2]]),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IX-2 - RPT_09_2_TRANSMISION_VERTICAL (6 columnas)
     * #PUERPERAS = citas con tamizaje (VIH o sifilis) y 59430 valor '1' D
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT09_2_TRANSMISION_VERTICAL',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_09_2_TRANSMISION_VERTICAL',
        'titulo'   => 'IX. TRANSMISIÓN VERTICAL — PUÉRPERAS INMEDIATAS',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 75, 2 => 76, 3 => 77, 4 => 78, 'T' => 79], 'colIni' => 'B'],
        'columnas' => [
            // Cat 1 (#NOMINAL): VIH labs D R1 (o 86318.01 R1) en puerpera inmediata
            ['key' => 1, 'niv1' => 'VIH/SIDA', 'niv2' => 'PR / Para VIH',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'D'], $puerpera59430),
             'regla' => $filas],
            // Cat 2 (Temporal1): VIH labs 'RP' o 86318.01 'RP' R1
            ['key' => 2, 'niv1' => 'VIH/SIDA', 'niv2' => 'Reactivo Para VIH',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $vihLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 1],
                        ]], $puerpera59430),
             'regla' => $filas],
            // Cat 3: 86780.01/86780 D R1 (o 86318.01 R1) = prueba rapida sifilis
            ['key' => 3, 'niv1' => 'SIFILIS', 'niv2' => 'Prueba Rápida',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => ['86780.01', '86780'], 'rownum' => 1],
                            ['cod' => '86318.01', 'rownum' => 1],
                        ]], $puerpera59430),
             'regla' => $filas],
            // Cat 4: 86780.01/86780 'RP' o 86318.01 'RP' R2 = prueba rapida positiva
            ['key' => 4, 'niv1' => 'SIFILIS', 'niv2' => 'Positivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => ['86780.01', '86780'], 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 2],
                        ]], $puerpera59430),
             'regla' => $filas],
            // Cat 5: 86592/86593 D R1 = tamizaje RPR
            ['key' => 5, 'niv1' => 'SIFILIS', 'niv2' => 'Tamizaje RPR',
             'cond' => array_merge($mujer1059, ['cod' => ['86592', '86593'], 'tip' => 'D', 'rownum' => 1], $puerpera59430),
             'regla' => $filas],
            // Cat 6: 86592/86593 'RP' R1 = RPR reactivo
            ['key' => 6, 'niv1' => 'SIFILIS', 'niv2' => 'RPR Reactivo',
             'cond' => array_merge($mujer1059, ['cod' => ['86592', '86593'], 'vl' => 'RP', 'tip' => 'D', 'rownum' => 1], $puerpera59430),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IX-3 - RPT_09_3_TRANSMISION_VERTICAL (4 columnas)
     * 1° prueba rapida para VIH en trabajo de parto / aborto.
     * #RIESGO = citas con 99401.34/99403.03 valor RSA (trabajo de parto) /
     * RMA (aborto). La plantilla oficial NO tiene zona de datos para este
     * bloque (en el flujo ODBC original quedaba siempre en 0): se muestra
     * en la web y NO se exporta al Excel.
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT09_3_TRANSMISION_VERTICAL',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_09_3_TRANSMISION_VERTICAL',
        'titulo'   => 'IX. TRANSMISIÓN VERTICAL — 1° PRUEBA RÁPIDA PARA VIH EN TRABAJO DE PARTO / ABORTO',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => null, // sin zona en la plantilla oficial
        'columnas' => [
            // Cat 1 (#NOMINAL): VIH labs D R1 y #RIESGO 99401.34 valor 'RSA'
            ['key' => 1, 'niv1' => 'VIH en Trabajo de Parto', 'niv2' => '1° Prueba Rápida',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'D'],
                        ['citaTiene' => ['cod' => '99401.34', 'vl' => 'RSA']]),
             'regla' => $filas],
            // Cat 2 (Temporal1): VIH labs 'RP' o 86318.01 'RP' R1 y #RIESGO 99403.03 'RSA'
            ['key' => 2, 'niv1' => 'VIH en Trabajo de Parto', 'niv2' => 'Reactivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $vihLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 1],
                        ]],
                        ['citaTiene' => ['cod' => '99403.03', 'vl' => 'RSA']]),
             'regla' => $filas],
            // Cat 3: VIH labs D R1 y #RIESGO 99401.34 valor 'RMA'
            ['key' => 3, 'niv1' => 'VIH en Aborto', 'niv2' => '1° Prueba Rápida',
             'cond' => array_merge($mujer1059, $vihRow, ['tip' => 'D'],
                        ['citaTiene' => ['cod' => '99401.34', 'vl' => 'RMA']]),
             'regla' => $filas],
            // Cat 4: VIH labs 'RP' o 86318.01 'RP' R1 y #RIESGO 99403.03 'RMA'
            ['key' => 4, 'niv1' => 'VIH en Aborto', 'niv2' => 'Reactivo',
             'cond' => array_merge($mujer1059, ['tip' => 'D',
                        'cualquieraDe' => [
                            ['cod' => $vihLabs, 'vl' => 'RP'],
                            ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 1],
                        ]],
                        ['citaTiene' => ['cod' => '99403.03', 'vl' => 'RMA']]),
             'regla' => $filas],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION X - RPT_10_CONSEJERIA (3 columnas)
     * #CONSEJ_GEST  = citas con 99401.02 D valor '3' y (Z3493/Z3593 D o
     *                 Z359/Z349 valor '3' R1)
     * #CONSEJ_PUERP = citas con 99401.02 D valor '4' y 59410/59515 R1,
     *                 o 99401.02 D valor '5' y 59430 R1
     * ------------------------------------------------------------ */
    [
        'codigo'   => 'RPT10_CONSEJERIA',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_10_CONSEJERIA',
        'titulo'   => 'X. CONSEJERÍA EN LACTANCIA MATERNA',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 85, 2 => 86, 3 => 87, 4 => 88, 'T' => 89], 'colIni' => 'B'],
        'columnas' => [
            // Cat 1: 99401.02 D valor '3' (gestante 3° consejeria)
            ['key' => 1, 'niv1' => 'Consejería Lactancia Materna', 'niv2' => 'GESTANTE 3° CONSEJERÍA',
             'cond' => array_merge($mujer1059, ['cod' => '99401.02', 'tip' => 'D', 'vl' => '3'], $tempX),
             'regla' => $filas],
            // Cat 2: 99401.02 D valor '4' (puerperio inmediato 4° consejeria)
            ['key' => 2, 'niv1' => 'Consejería Lactancia Materna', 'niv2' => 'PUÉRPERIO INMEDIATO 4° CONSEJERÍA',
             'cond' => array_merge($mujer1059, ['cod' => '99401.02', 'tip' => 'D', 'vl' => '4'], $tempX),
             'regla' => $filas],
            // Cat 3: 99401.02 D valor '5' (atencion puerperal 5° a + consejeria)
            ['key' => 3, 'niv1' => 'Consejería Lactancia Materna', 'niv2' => 'ATENCIÓN PUÉRPERAL 5° a + CONSEJERÍA',
             'cond' => array_merge($mujer1059, ['cod' => '99401.02', 'tip' => 'D', 'vl' => '5'], $tempX),
             'regla' => $filas],
        ],
    ],
    ];
}

/* ============================================================
 * 4) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/**
 * Anios disponibles en la tabla consolidada DENTRO DEL AMBITO del filtro
 * establecimiento (para el filtro Anio):
 *   - establecimiento concreto -> anios con datos de ese EE.SS
 *   - '-- Todos --'            -> anios con datos de la lista ZSPERENE
 *     (antes se calculaban sobre TODA la tabla consolidada, mezclando anios
 *     de establecimientos ajenos a la lista del select)
 */
function maternoGetAniosDisponibles(PDO $pdo, string $establecimiento = ''): array {
    try {
        $params = [];
        $whereEst = maternoWhereEstablecimientos($pdo, $establecimiento, $params);
        $sql = "SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
                WHERE Anio IS NOT NULL"
             . ($whereEst !== '' ? " AND {$whereEst}" : "")
             . " ORDER BY Anio DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $anios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $anios ?: [date('Y')];
    } catch (Throwable $e) {
        return [date('Y')];
    }
}

/**
 * Meses (1..12) con datos dentro del ambito del filtro establecimiento y del
 * anio seleccionado ('' = todos los anios). Da las opciones del filtro Mes
 * para que tambien se base unicamente en la lista de establecimientos
 * ('-- Todos --' = catalogo ZSPERENE completo) y no en toda la tabla.
 * Usa la columna generada Mes_Int si existe (mismo criterio que el reporte).
 */
function maternoGetMesesDisponibles(PDO $pdo, string $anio = '', string $establecimiento = ''): array {
    try {
        $params = [];
        $cond = [];
        if (trim($anio) !== '') {
            $cond[] = "Anio = :anio";
            $params[':anio'] = (string)$anio;
        }
        $whereEst = maternoWhereEstablecimientos($pdo, $establecimiento, $params);
        if ($whereEst !== '') $cond[] = $whereEst;
        $colMes = maternoTieneColumnaMesInt($pdo) ? 'Mes_Int' : "CAST(TRIM(Mes) AS UNSIGNED)";
        $sql = "SELECT DISTINCT {$colMes} FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
                WHERE {$colMes} BETWEEN 1 AND 12"
             . ($cond ? " AND " . implode(' AND ', $cond) : "")
             . " ORDER BY 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

/** Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES).
 *  El resultado se cachea estaticamente: el ambito de establecimientos se
 *  consulta en varias partes del reporte (filtros anio/mes y consulta
 *  principal) y no tiene sentido repetir la consulta del catalogo. */
function maternoGetEstablecimientosZS(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $stmt = $pdo->query("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE
                             WHERE Codigo_Unico IS NOT NULL ORDER BY Nombre_Establecimiento");
        $out = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[trim((string)$r['Codigo_Unico'])] = $r['Nombre_Establecimiento'];
        }
        return $cache = $out;
    } catch (Throwable $e) {
        return $cache = [];
    }
}

/**
 * AMBITO DE ESTABLECIMIENTOS compartido por todos los filtros del modulo.
 *
 * - Establecimiento concreto: TRIM(Codigo_Unico) = :est (como siempre).
 * - '-- Todos --' (cadena vacia): TRIM(Codigo_Unico) IN (:estLista_0..N) con
 *   TODOS los codigos del catalogo ZSPERENE, es decir, la misma lista que se
 *   muestra en el select de reporte_materno.php. Asi, cuando se selecciona
 *   '-- Todos --', todos los demas filtros (anio, mes y los codigos de interes
 *   del reporte) se aplican unicamente dentro de esa lista de EE.SS y nunca
 *   sobre establecimientos ajenos al catalogo que existan en la tabla
 *   consolidada.
 * - Catalogo no disponible (tabla ZSPERENE vacia o con error): devuelve ''
 *   para NO restringir la consulta (comportamiento anterior, degrada bien).
 *
 * @param PDO    $pdo             Conexion activa
 * @param string $establecimiento Codigo_Unico del EE.SS ('' = '-- Todos --')
 * @param array  $params          Parametros PDO de la consulta (se completan)
 * @return string Predicado SQL ('' = sin restriccion de establecimiento)
 */
function maternoWhereEstablecimientos(PDO $pdo, string $establecimiento, array &$params): string {
    $establecimiento = trim($establecimiento);
    if ($establecimiento !== '') {
        $params[':est'] = $establecimiento;
        return "TRIM(Codigo_Unico) = :est";
    }
    // '-- Todos --': limitar al universo de la lista del select (ZSPERENE)
    $lista = maternoGetEstablecimientosZS($pdo);
    if (empty($lista)) {
        return ''; // sin catalogo no se puede acotar: no romper el reporte
    }
    $ph = [];
    $i = 0;
    foreach (array_keys($lista) as $cod) {
        $k = ':estLista_' . $i;
        $params[$k] = $cod;
        $ph[] = $k;
        $i++;
    }
    return "TRIM(Codigo_Unico) IN (" . implode(',', $ph) . ")";
}

/**
 * Codigos de item que intervienen en el reporte de Materno, segun los
 * procedimientos del archivo "03 Creacion de Procedimientos" (RPT_01..RPT_10).
 *
 * Devuelve ['prefijos' => [...], 'exactos' => [...]]:
 *   - prefijos: familias CIE (como cod_item_f) y bloques de codigos -> LIKE 'XXX%'
 *   - exactos: items de laboratorio / procedimientos / vacunas / consejeria
 *
 * La consulta trae ademas TODAS las filas con Valor_Lab='G' (el marcador de
 * gestante que usan #GEST del RPT_01 y RPT_04 puede estar en cualquier codigo).
 */
function maternoCodigosInteres(): array {
    return [
        // Familias CIE (cod_item_f) y bloques usados por los RPT_04/RPT_05
        'prefijos' => [
            'O',                    // O00-O9A: embarazo/parto/puerperio/complicaciones (O470, O60X, O009, O85X, O152, O980, O72x, O45x...)
            'Z34', 'Z35',          // atencion prenatal: Z3491/Z3492/Z3493/Z3591/Z3592/Z3593/Z349/Z359
            'A15', 'A16',          // TBC (cod_item_f A15/A16 del RPT_04 cat 12)
            'A50',                 // sifilis congenita (cod_item_f A50 del RPT_05)
            'D06',                 // D060/D061/D069 (lesiones por PAP, #POSITIVO RPT_01)
            'N87',                 // N870/N871/N872/N879 (lesiones por PAP, #POSITIVO RPT_01)
            'P05', 'P07',          // RN bajo peso / prematuro (P050, P07x, P0711, P0712, P072)
            'P2', 'P3',            // RN: P21x (hipoxia), P22x, P23x, P240, P36x (sepsis)
            'E05',                 // tiroides (cod_item_f E05 del RPT_04 cat 9)
            'Z20',                 // Z206 (RN VIH expuesto)
        ],
        'exactos' => [
            // ---- RPT_01: APN reenfocada ----
            '88141',                              // Papanicolaou (#PAP)
            '90715', '90714',                     // dTpa / dT (inmunizacion gestante)
            '90744', '90746',                     // Hepatitis B (inmunizacion gestante)
            '90658',                              // Influenza (inmunizacion gestante)
            '90749.01',                           // COVID (inmunizacion gestante)
            'D1110',                              // atencion odontologica
            '96150.01',                           // VBG tamizada (#EMBARAZO / Temporal5)
            'R456',                               // VBG positivo (Temporal5)
            '81000.02', '81002', '81007',         // tamizaje bacteriuria (#EMBARAZO / Temporal5/8)
            '82044',                              // tamizaje proteinuria
            '80055.01', '80055.02',               // 1a/2a bateria completa
            '76817', '76805',                     // ecografia obstetrica (1a/2a/3a por valor)
            // ---- RPT_02: bienestar fetal / psicoprofilaxis / estimulacion ----
            '59020', '59025',                     // monitoreo fetal (1o/2o por valor)
            '99412.01', '99412.02',               // estimulacion prenatal / psicoprofilaxis
            // ---- RPT_03: anemia / manejo terapeutico / plan de parto ----
            '99199.26',                           // sulfato ferroso (1a/6a entrega, 'TA' puerpera)
            '85018', '85018.01',                  // dosaje de hemoglobina
            'U1692', '59401.06',                  // plan de parto
            // (O990 = anemia, cubierto por el prefijo 'O')
            // ---- RPT_06: micronutrientes ----
            '99199.18',                           // acido folico
            '59401.05',                           // suplemento de calcio
            // ---- RPT_07 / RPT_09_2 / RPT_10: puerperio ----
            '59430',                              // atencion de puerperio (vl 1/2)
            '59410', '59515',                     // puerperio inmediato (RPT_10)
            // ---- RPT_08: visita domiciliaria ----
            'C0011',                              // visita domiciliaria a la gestante
            '99501',                              // atencion integral a la puerpera
            // ---- RPT_09_1/2/3: transmision vertical ----
            '86703.01', '86703.02', '87389', '86703', // ELISA VIH
            '86780.01', '86592', '86593', '86780',   // RPR / VDRL sifilis
            '87342', '87340', '82397', '86706', '86704', '86705', '87351', '86707', // hepatitis B
            '86318.01',                           // prueba rapida VIH (1a/2a por I_ROWNUM_LAB)
            '99401.33',                           // consejeria pre-test VIH (vl 1/2)
            '99402.05',                           // consejeria pre-test sifilis/hepatitis (vl 1/2)
            '99401.34', '99403.03',               // riesgo en trabajo de parto/aborto (RSA/RMA)
            // ---- RPT_10: consejeria en lactancia materna ----
            '99401.02',                           // consejeria LMF (vl 3/4/5)
            // ---- RPT_04 cat 9: trastornos metabolicos ----
            'E010',                               // trastorno tiroideo en el embarazo
        ],
    ];
}

/**
 * Construye la clausula WHERE de codigos de item a partir de
 * maternoCodigosInteres(): prefijos (LIKE) + codigos exactos (IN).
 * Incluye las filas con Valor_Lab='G' (marcador de gestante de #GEST).
 */
function maternoWhereCodigos(array &$params): string {
    $lista = maternoCodigosInteres();
    $partes = [];
    $i = 0;
    foreach ($lista['prefijos'] as $pref) {
        $k = ":pref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "Codigo_Item LIKE $k";
        $i++;
    }
    $inPh = [];
    foreach ($lista['exactos'] as $j => $c) {
        $k = ":cod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "Codigo_Item IN (" . implode(',', $inPh) . ")";
    // #GEST (RPT_01/RPT_04): el marcador valor_lab='G' puede estar en cualquier
    // codigo de la cita, por eso se traen todas las filas con valor 'G'.
    $params[':vl_g'] = 'G';
    $partes[] = "Valor_Lab = :vl_g";
    return "(" . implode(" OR ", $partes) . ")";
}

/** Conexion PDO dedicada en modo UNBUFFERED (streaming), como el modulo Cancer. */
function maternoAbrirConexionStreaming(): ?PDO {
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
function maternoTieneColumnaMesInt(PDO $pdo): bool {
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

/** Diagnostico del entorno (limites PHP, indices, tamano de tabla). */
function maternoDiagnosticoEntorno(PDO $pdo): array {
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
 * Candidatos por buckets pre-indexados (codigo exacto, prefijo de 2 letras y
 * de 1 letra). Sobre-aproximacion: la verificacion exacta la hace mtrCumple.
 * Evita recorrer TODAS las filas por cada columna del reporte.
 */
function mtrCandidatos(array $cond, array $porCod, array $porIni2, array $porIni, int $total): array {
    if ($total <= 0) return [];
    $sinCodigo = !isset($cond['cod']) && !isset($cond['codPref'])
              && !isset($cond['codEntre']) && !isset($cond['cualquieraDe']);
    if ($sinCodigo) {
        return range(0, $total - 1);
    }
    $out = [];
    if (isset($cond['cod'])) {
        foreach ((array)$cond['cod'] as $c) {
            foreach ($porCod[strtoupper((string)$c)] ?? [] as $idx) $out[$idx] = true;
        }
        return array_keys($out);
    }
    if (isset($cond['codPref'])) {
        foreach ((array)$cond['codPref'] as $p) {
            $p = strtoupper((string)$p);
            if ($p === '') return range(0, $total - 1);
            $b2 = substr($p, 0, 2);
            if (isset($porIni2[$b2])) {
                foreach ($porIni2[$b2] as $idx) $out[$idx] = true;
            } elseif (isset($porIni[$p[0]])) {
                foreach ($porIni[$p[0]] as $idx) $out[$idx] = true;
            }
        }
        return array_keys($out);
    }
    if (isset($cond['codEntre'])) {
        [$min, $max] = $cond['codEntre'];
        $min = strtoupper((string)$min);
        $max = strtoupper((string)$max);
        $b2 = substr($min, 0, 2);
        $b2max = substr($max, 0, 2);
        if ($b2 === $b2max && isset($porIni2[$b2])) {
            return $porIni2[$b2];
        }
        if ($min[0] === $max[0] && isset($porIni[$min[0]])) {
            return $porIni[$min[0]];
        }
        return range(0, $total - 1);
    }
    foreach ($cond['cualquieraDe'] as $sub) {
        foreach (mtrCandidatos($sub, $porCod, $porIni2, $porIni, $total) as $idx) $out[$idx] = true;
    }
    return array_keys($out);
}

/**
 * Cuenta las FILAS que cumplen una condicion aplicando la regla de conteo.
 *
 * REGLA 'filas' (adaptacion fiel del archivo 03): el T-SQL inserta en cada
 * tabla TRAMA_BASE_MATERNO_*_NOMINAL una fila por cada fila de TRAMAHIS_DTSG
 * que cumple el WHERE de la categoria y consolida con COUNT(*) agrupado por
 * renaes/periodo/sexo/etnia/financiador/pais/ups/Categoria/gedad. El motor
 * replica eso: cada fila HIS que cumple la condicion cuenta 1 en el grupo
 * etareo de su propia edad (CASE gedad del T-SQL). Sin dedup por cita ni
 * orden por fecha: el "N-esimo" viene en valor_lab del item HIS.
 *
 * Las reglas 'simple'/'trimestre'/'ocurrencia'/'ocurrenciaMin'/'conteoMinimo'
 * se conservan por compatibilidad con versiones anteriores del modulo, pero
 * las secciones actuales del reporte solo usan 'filas' (y 'calc', que se
 * resuelve fuera).
 *
 * @return array ['valores'=>[gedad=>n], 'citas'=>int, 'pacientes'=>int]
 */
function mtrContarRegla(array $cond, array $regla, array $ctx): array {
    $valores = [];
    $tipo = $regla['tipo'] ?? 'simple';

    // ---- Regla 'filas': count(*) fila a fila, como el T-SQL ----
    if ($tipo === 'filas') {
        $citas = [];
        $pacs = [];
        $candidatos = mtrCandidatos($cond, $ctx['porCod'], $ctx['porIni2'], $ctx['porIni'], count($ctx['filas']));
        foreach ($candidatos as $idx) {
            $f = $ctx['filas'][$idx];
            if (!mtrCumple($f, $cond, $ctx)) continue;
            $g = mtrGedad($f);
            if ($g !== null) {
                $valores[$g] = ($valores[$g] ?? 0) + 1;
            }
            if ($f['cita'] !== '') $citas[$f['cita']] = true;
            if ($f['pac'] !== '')  $pacs[$f['pac']] = true;
        }
        return ['valores' => $valores, 'citas' => count($citas), 'pacientes' => count($pacs)];
    }

    // ---- Reglas legacy (versiones anteriores; ya no las usan las secciones) ----
    $nCitas = 0;
    $nPacs = 0;

    // 1) Matching y dedup por cita
    $candidatos = mtrCandidatos($cond, $ctx['porCod'], $ctx['porIni2'], $ctx['porIni'], count($ctx['filas']));
    $infoCita = []; // cita => [pac, gedad, fecha, trim]
    foreach ($candidatos as $idx) {
        $f = $ctx['filas'][$idx];
        if (!mtrCumple($f, $cond, $ctx)) continue;
        $cita = $f['cita'] !== '' ? $f['cita'] : '#f' . $idx;
        if (!isset($infoCita[$cita])) {
            $infoCita[$cita] = [
                'pac'    => $f['pac'],
                'gedad'  => mtrGedad($f),
                'fecha'  => $f['fecha'] ?? 0,
                'trim'   => mtrTrimestreEG($f),
            ];
        }
    }
    // 1b) Completar trimestre/edad gestacional desde OTRAS filas de la misma cita
    foreach ($infoCita as $cita => $info) {
        if ($info['trim'] !== null || $cita === '' || $cita[0] === '#') continue;
        if (!isset($ctx['porCita'][$cita])) continue;
        foreach ($ctx['porCita'][$cita] as $idx) {
            $t = mtrTrimestreEG($ctx['filas'][$idx]);
            if ($t !== null) { $infoCita[$cita]['trim'] = $t; break; }
        }
    }

    // 2) Agrupar por paciente y ordenar por (fecha, cita)
    $porPac = [];
    foreach ($infoCita as $cita => $info) {
        $pacKey = $info['pac'] !== '' ? $info['pac'] : '#c' . $cita;
        $porPac[$pacKey][] = ['cita' => $cita] + $info;
    }
    $nCitas = count($infoCita);
    $nPacs = count($porPac);

    // 3) Aplicar la regla de conteo
    $citaCond = $regla['citaCond'] ?? null; // condicion adicional evaluada sobre
    // las filas de la CITA seleccionada (resultado reactivo del tamizaje, etc.)
    $citaOk = function (array $c) use ($citaCond, $ctx): bool {
        if ($citaCond === null) return true;
        if ($c['cita'] === '' || $c['cita'][0] === '#') return false;
        return mtrCitaTiene($ctx, $c['cita'], $citaCond);
    };
    foreach ($porPac as $citas) {
        usort($citas, function ($a, $b) {
            if ($a['fecha'] !== $b['fecha']) return $a['fecha'] <=> $b['fecha'];
            return strcmp($a['cita'], $b['cita']);
        });
        switch ($tipo) {
            case 'simple':
                foreach ($citas as $c) {
                    if ($c['gedad'] !== null && $citaOk($c)) {
                        $valores[$c['gedad']] = ($valores[$c['gedad']] ?? 0) + 1;
                    }
                }
                break;
            case 'trimestre':
                $n = (int)($regla['n'] ?? 1);
                $oc = (int)($regla['oc'] ?? 1);
                if (count($citas) >= $oc) {
                    $c = $citas[$oc - 1];
                    if ($c['trim'] === $n && $c['gedad'] !== null && $citaOk($c)) {
                        $valores[$c['gedad']] = ($valores[$c['gedad']] ?? 0) + 1;
                    }
                }
                break;
            case 'ocurrencia':
            case 'ocurrenciaMin':
                $n = (int)($regla['n'] ?? 1);
                if (count($citas) >= $n) {
                    $c = $citas[$n - 1];
                    if ($c['gedad'] !== null && $citaOk($c)) {
                        $valores[$c['gedad']] = ($valores[$c['gedad']] ?? 0) + 1;
                    }
                }
                break;
            case 'conteoMinimo':
                $n = (int)($regla['n'] ?? 1);
                if (count($citas) >= $n) {
                    $c = $citas[0];
                    if ($c['gedad'] !== null && $citaOk($c)) {
                        $valores[$c['gedad']] = ($valores[$c['gedad']] ?? 0) + 1;
                    }
                }
                break;
        }
    }

    return ['valores' => $valores, 'citas' => $nCitas, 'pacientes' => $nPacs];
}

/**
 * Ejecuta el reporte de Materno completo (adaptacion de los procedimientos
 * RPT_01..RPT_10 del archivo "03 Creacion de Procedimientos").
 *
 * Estrategia (igual que ESNI/Cancer): UNA sola consulta que trae todas las
 * filas HIS de los codigos de interes del paquete materno con los filtros
 * comunes aplicados, y luego el matching de cada columna/fila se resuelve
 * en PHP usando el DSL (que espeja los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function maternoEjecutarReporte(PDO $pdo, array $filtros): array {
    $t0 = microtime(true);
    $tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';

    // ---- 0) Proteccion contra HTTP 500 en hostings compartidos ----
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
        if (maternoTieneColumnaMesInt($pdo)) {
            $where[] = "Mes_Int = :mes";
        } else {
            $where[] = "CAST(TRIM(Mes) AS UNSIGNED) = :mes";
        }
        $params[':mes'] = intval($filtros['mes']);
    }
    // Ambito de establecimientos: con un EE.SS concreto se filtra por su
    // Codigo_Unico (como siempre); con '-- Todos --' se limita a la LISTA del
    // select (catalogo ZSPERENE) en lugar de toda la tabla consolidada, de
    // modo que el resto de filtros (anio/mes/codigos) se aplica solo a esa
    // lista de establecimientos.
    $whereEst = maternoWhereEstablecimientos($pdo, (string)($filtros['establecimiento'] ?? ''), $params);
    if ($whereEst !== '') {
        $where[] = $whereEst;
    }

    // ---- 2) Codigos de interes del paquete materno ----
    $whereCod = maternoWhereCodigos($params);

    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Fecha_Atencion, Fecha_Ultima_Regla, Hemoglobina,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab,
                   Id_Correlativo_Lab, Fg_Tipo, Descripcion_Otra_Condicion
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    $pdoStream = maternoAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    $porCita = [];
    $porPaciente = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = mtrFila($r);
            if ($f['cod'] === '') continue;
            $idx = count($filas);
            $filas[] = $f;
            if ($f['cita'] !== '') $porCita[$f['cita']][] = $idx;
            if ($f['pac'] !== '')  $porPaciente[$f['pac']][] = $idx;
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
    $pdoStream = null;

    return maternoEjecutarDesdeFilas($filas, $t0);
}

/**
 * Ejecuta el pipeline de matching/agregacion del reporte a partir de las
 * filas HIS YA normalizadas (mtrFila). Separada de maternoEjecutarReporte
 * para poder validar todo el pipeline sin base de datos (harness de pruebas).
 *
 * @param array $filas Filas normalizadas por mtrFila()
 * @param float $t0 Timestamp de inicio (para tiempo_ejecucion)
 * @return array Estructura del reporte (ver maternoEjecutarReporte)
 */
function maternoEjecutarDesdeFilas(array $filas, float $t0 = 0.0): array {
    if ($t0 === 0.0) $t0 = microtime(true);

    // Indices por cita y paciente
    $porCita = [];
    $porPaciente = [];
    foreach ($filas as $idx => $f) {
        if ($f['cita'] !== '') $porCita[$f['cita']][] = $idx;
        if ($f['pac'] !== '')  $porPaciente[$f['pac']][] = $idx;
    }

    // Buckets para matching rapido (codigo exacto / prefijo 2 letras / 1 letra)
    $porCod = [];
    $porIni2 = [];
    $porIni = [];
    foreach ($filas as $idx => $f) {
        $porCod[$f['cod']][] = $idx;
        if (strlen($f['cod']) >= 2) {
            $porIni2[substr($f['cod'], 0, 2)][] = $idx;
        }
        $porIni[$f['cod'] !== '' ? $f['cod'][0] : '#'][] = $idx;
    }
    $ctx = ['filas' => $filas, 'porCita' => $porCita, 'porPaciente' => $porPaciente,
            'porCod' => $porCod, 'porIni2' => $porIni2, 'porIni' => $porIni];

    // ---- 4) Matching por seccion ----
    $seccionesOut = [];
    $totCasos = 0;

    foreach (maternoSecciones() as $sec) {
        $gedadesSec = $sec['gedades'] ?? [1, 2, 3, 4];
        $secOut = $sec;
        $totalSec = 0;

        if (($sec['eje'] ?? 'gedad') === 'gedad') {
            $colsOut = [];
            $porKey = []; // key => valores (para columnas calc)
            foreach ($sec['columnas'] as $col) {
                $regla = $col['regla'] ?? ['tipo' => 'simple'];
                if (($regla['tipo'] ?? '') === 'calc') {
                    $colsOut[] = ['key' => $col['key'], 'niv1' => $col['niv1'], 'niv2' => $col['niv2'], 'niv3' => $col['niv3'] ?? null,
                                  'calc' => $regla['cols'], 'valores' => null, 'total' => 0, 'citas' => 0, 'pacientes' => 0];
                    continue;
                }
                $r = mtrContarRegla($col['cond'], $regla, $ctx);
                // Restringir a los grupos etareos de la seccion
                $vals = [];
                foreach ($gedadesSec as $g) {
                    if (isset($r['valores'][$g]) && $r['valores'][$g] > 0) $vals[$g] = (int)$r['valores'][$g];
                }
                $porKey[$col['key']] = $vals;
                $tot = array_sum($vals);
                $totalSec += $tot;
                $colsOut[] = ['key' => $col['key'], 'niv1' => $col['niv1'], 'niv2' => $col['niv2'], 'niv3' => $col['niv3'] ?? null,
                              'valores' => $vals, 'total' => $tot, 'citas' => $r['citas'], 'pacientes' => $r['pacientes'],
                              'regla' => $regla, 'cond' => $col['cond'] ?? null];
            }
            // Resolver columnas calc (suma de las columnas indicadas)
            foreach ($colsOut as &$co) {
                if (!empty($co['calc'])) {
                    $vals = [];
                    foreach ($gedadesSec as $g) {
                        $s = 0;
                        foreach ($co['calc'] as $ck) {
                            $s += $porKey[$ck][$g] ?? 0;
                        }
                        if ($s > 0) $vals[$g] = $s;
                    }
                    $co['valores'] = $vals;
                    $co['total'] = array_sum($vals);
                    $totalSec += $co['total'];
                }
            }
            unset($co);
            $secOut['columnas'] = $colsOut;
        } else {
            // eje 'categoria': filas con condicion x columnas de grupo etareo
            $filasOut = [];
            foreach ($sec['filas'] as $fila) {
                $regla = $fila['regla'] ?? ['tipo' => 'simple'];
                $r = mtrContarRegla($fila['cond'], $regla, $ctx);
                $vals = [];
                foreach ($gedadesSec as $g) {
                    if (isset($r['valores'][$g]) && $r['valores'][$g] > 0) $vals[$g] = (int)$r['valores'][$g];
                }
                $tot = array_sum($vals);
                $totalSec += $tot;
                $filasOut[] = ['key' => $fila['key'], 'label' => $fila['label'], 'valores' => $vals,
                               'total' => $tot, 'citas' => $r['citas'], 'pacientes' => $r['pacientes'],
                               'cond' => $fila['cond'] ?? null];
            }
            $secOut['filas'] = $filasOut;
        }

        $secOut['total'] = $totalSec;
        $totCasos += $totalSec;
        $seccionesOut[] = $secOut;
    }

    return [
        'secciones' => $seccionesOut,
        'totales'   => [
            'total_casos' => $totCasos,
            'secciones_con_datos' => count(array_filter($seccionesOut, fn($s) => ($s['total'] ?? 0) > 0)),
        ],
        'version'          => MATERNO_DATA_VERSION,
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}
