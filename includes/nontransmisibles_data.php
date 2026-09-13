<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo NO TRANSMISIBLES - Motor de reporte data-driven (includes/nontransmisibles_data.php)
 *
 * ADAPTACION FIEL del archivo "03 Creacion de Procedimientos.txt"
 * (24 procedimientos usp_TRAMA_BASE_NT_2025_*) sobre la tabla consolidada
 * MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO. Reemplaza:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales" (DimNT_*: Factores,
 *      EvaluacionPAB, Valoracion01-03, Gedad, HTA_Diagnostico01-06, HTA_Riesgo,
 *      HTA_Sesiones, DM_Diagnostico01-07, TM_Valoracion01-05)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion" (TRAMA_BASE_NT_*)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos" (24 SP)
 *   4) Excel:      refrescar la conexion ODBC de "Reporte_Actividades_NoTransmisibles.xlsx"
 *
 * SECCIONES (24 procedimientos -> 24 secciones del reporte, en 4 grupos):
 *   GRUPO 01 VALORACION (gedad 1-6, cols C=TOTAL, D-O):
 *     VR01 usp_..._01_VALORACION_RPT_01_FACTORES_RIESGO   (20 factores, filas 12-31)
 *     VR02 usp_..._01_VALORACION_RPT_02_EVALUACION_PAB    (Normal/Anormal, filas 36-37)
 *     VR03 usp_..._01_VALORACION_RPT_03_VALORACION_CLINICA(valoracion 1-2, filas 42-43)
 *     VR04 usp_..._01_VALORACION_RPT_04_VALORACION_CLINICA(valoracion 1-4, filas 47-50)
 *     VR05 usp_..._01_VALORACION_RPT_05_VALORACION_CLINICA(valoracion 1,   fila 55)
 *   GRUPO 02 HTA (gedad 1-6, cols C=TOTAL, D-O):
 *     HTA1 usp_..._02_HTA_RPT_01_CASOS          (dx 1-8, filas 62-69)
 *     HTA2 usp_..._02_HTA_RPT_02_EMERGENCIA     (dx 1-3, filas 75-77)
 *     HTA3 usp_..._02_HTA_RPT_03_DISLIPIDEMIAS  (dx 1,   fila 82)
 *     HTA4 usp_..._02_HTA_RPT_04_HIPERTENSO_SIN_DANO    (dx 1-6, filas 87-92)
 *     HTA5 usp_..._02_HTA_RPT_05_HIPERTENSO_TRATAMIENTO (dx 1-10, filas 98-108)
 *     HTA6 usp_..._02_HTA_RPT_06_HIPERTENSO_RIESGO      (riesgo 1-5, filas 113-117)
 *     HTA7 usp_..._02_HTA_RPT_07_SESIONES      (6 filas, filas 122-127; N= count, Part= suma)
 *   GRUPO 03 DM (gedad 1-8):
 *     DM01 usp_..._03_DM_RPT_01_CASOS          (dx 1-13, filas 134-146; cols C=TOTAL, D-S)
 *     DM02 usp_..._03_DM_RPT_02_GLUCEMIA       (dx 1-7, filas 152-158; regla count(*))
 *     DM03 usp_..._03_DM_RPT_03_CONTROL        (cat 1-52, filas 163-214; cols E=TOTAL, F-U; RENAES I-2/I-3/I-4)
 *     DM04 usp_..._03_DM_RPT_04_TRATAMIENTO    (cat 1-52, filas 219-270; cols E=TOTAL; RENAES II-1..III-2/III-E)
 *     DM05 usp_..._03_DM_RPT_05_ATENCION       (dx 1-8, filas 275-282; cols C=TOTAL, D-S)
 *     DM06 usp_..._03_DM_RPT_06_VALORACION     (cat 1-88, filas 287-374; cols E=TOTAL; RENAES I-4 + II/III
 *     DM07 usp_..._03_DM_RPT_07_NEFROPATIA     (cat 1-11, filas 379-389; cols C=TOTAL, D-S; RENAES I-1..II-1)
 *   GRUPO 04 TM TELESALUD (gedad 1-6, cols C=TOTAL, D-O):
 *     TM01 usp_..._04_TM_RPT_01_TELEORIENTACION (valoracion 1-3, filas 396-398)
 *     TM02 usp_..._04_TM_RPT_02_TELEMONITOREO   (valoracion 1,   fila 403)
 *     TM03 usp_..._04_TM_RPT_03_TELEMONITOREO_HTA (1-2, filas 408-409)
 *     TM04 usp_..._04_TM_RPT_04_TELEMONITOREO_DM (2 filas sin/con glucometro, filas 414-415)
 *     TM05 usp_..._04_TM_RPT_05_TELECONSULTAS   (1 fila, fila 419)
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita         -> Id_Cita
 *   id_tipitem      -> Tipo_Diagnostico      ('D','P','R','C')
 *   cod_item        -> Codigo_Item
 *   cod_item_f      -> (sin columna) prefijo CIE de 3 letras si el codigo empieza
 *                      con letra (I10X->I10, E102->E10, O240->O24); si empieza con
 *                      digito se conserva completo (99199.22->99199.22). Igual que
 *                      materno_data.php: cod_item_f IN ('E10','E11') == Codigo_Item
 *                      LIKE 'E10%' / 'E11%'.
 *   valor_lab       -> Valor_Lab             ('NULL' en el DSL = IS NULL)
 *   I_ROWNUM_LAB    -> Id_Correlativo_Lab
 *   id_genero       -> Id_Genero             ('F','M')
 *   edad_reg        -> Edad_Reg
 *   id_tipedad_reg  -> Tipo_Edad             ('D','M','A')
 *   renaes          -> Codigo_Unico
 *   id_persona      -> Id_Paciente
 *   fichafam        -> Ficha_Familiar        (APP100 = sesiones GAM)
 *   id_ups          -> Id_Ups                (302701 nefrologia, 300201 cardiologia,
 *                                             303408 oftalmologia, 303101 nutricion, ...)
 *   fg_tipo         -> Fg_Tipo               ('CX' = caso confirmado, DM_RPT_01)
 *   perimetro_abdominal -> Perimetro_Abdominal (VR02, try_convert(int,...) del T-SQL
 *                                             se interpreta como rango decimal)
 *   periodo         -> Anio + Mes (filtros web del reporte)
 *   MAESTRO_HIS_ESTABLECIMIENTO (Categoria_Establecimiento) -> renaesCat de DM03/
 *      DM04/DM06/DM07 (#RENAES del T-SQL: I-2/I-3/I-4, II-2../III-2-E, I-4 + II/III,
 *      I-1/I-2/I-3/I-4/II-1)
 *
 * GRUPOS ETAREOS (CASE gedad del T-SQL):
 *   'g6' (VALORACION/HTA/TM): A 5-11 -> 1; 12-17 -> 2; 18-29 -> 3; 30-39 -> 4;
 *                              40-59 -> 5; 60+ -> 6. Las filas <5 anios y D/M quedan
 *                              fuera (el T-SQL exige id_tipedad_reg='A' y edad>=5).
 *   'g8' (DM): D/M -> 1 (menores de 1 anio); A 1-4 -> 2; 5-11 -> 3; 12-17 -> 4;
 *              18-29 -> 5; 30-39 -> 6; 40-59 -> 7; 60+ -> 8. (A 0 anios no mapea,
 *              igual que el CASE del T-SQL.)
 *
 * REGLAS DE CONTEO (columna "casos" de cada tabla consolidada):
 *   'personas' : count(distinct id_persona) - se replica como distintos pares
 *                (renaes|id_persona|anio|mes): la suma de los count(distinct) POR
 *                ESTABLECIMIENTO Y PERIODO que consolidaba el Excel ODBC.
 *   'filas'    : count(*) (DM02 GLUCEMIA y HTA7 SESIONES - numero de sesiones).
 *   'sesiones' : HTA7: ademas de 'filas' para N, sum(try_convert(int,valor_lab))
 *                con NULL -> 1 (iif del T-SQL) para PARTICIPANTES.
 *
 * NOTAS DE ADAPTACION (desviaciones documentadas):
 *   - Los rangos de valor_lab del T-SQL (valor_lab<'140', between '70' and '99')
 *     se interpretan NUMERICOS (vlNum), misma convencion que materno_data.php.
 *   - try_convert(int, perimetro_abdominal) se compara como decimal (88/102).
 *   - aniomes/periodo del T-SQL se convierte en los filtros web Anio/Mes.
 *   - HTA5 dx 7-10 (atencion integral especializado) usa id_ups 302701/300201/
 *     303408/303101 tal cual el SP.
 *   - TM04: el SP marca ambas filas (sin/con glucometro) con Valoracion=1; se
 *     exponen como 2 filas del reporte segun #TELEMONITOREO (99499.10) y
 *     #RESULTADOS (99499.11), como la plantilla.
 *   - Secciones con renaesCat (DM03/DM04/DM06/DM07) requieren el catalogo
 *     MAESTRO_HIS_ESTABLECIMIENTO cargado (import.php). Sin catalogo quedan en 0.
 */

require_once __DIR__ . '/../config.php';

define('NT_DATA_VERSION', '2026-09-13-r1');

/** Version del motor de reporte de No Transmisibles (badge del reporte). */
function ntDataVersion(): string {
    return NT_DATA_VERSION;
}

/* ============================================================
 * 1) NORMALIZACION DE FILAS HIS
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 *
 * Textos en MAYUSCULAS para replicar la colacion case-insensitive de MySQL
 * en el matching PHP (igual que zooFila/materno).
 */
function ntFila(array $r): array {
    $cod = $r['Codigo_Item'] !== null ? strtoupper(trim((string)$r['Codigo_Item'])) : '';
    // cod_item_f: prefijo CIE de 3 chars si empieza con letra; completo si no
    $codF = ($cod !== '' && ctype_alpha($cod[0])) ? substr($cod, 0, 3) : $cod;
    $pabRaw = $r['Perimetro_Abdominal'] ?? null;
    return [
        'cita'    => $r['Id_Cita'] !== null ? trim((string)$r['Id_Cita']) : '',
        'pac'     => $r['Id_Paciente'] !== null ? trim((string)$r['Id_Paciente']) : '',
        'renaes'  => $r['Codigo_Unico'] !== null ? trim((string)$r['Codigo_Unico']) : '',
        'cod'     => $cod,
        'codF'    => $codF,
        'tip'     => $r['Tipo_Diagnostico'] !== null ? strtoupper(trim((string)$r['Tipo_Diagnostico'])) : '',
        'vl'      => $r['Valor_Lab'] !== null ? strtoupper(trim((string)$r['Valor_Lab'])) : null,
        'rownum'  => $r['Id_Correlativo_Lab'] !== null ? (int)$r['Id_Correlativo_Lab'] : null,
        'sexo'    => $r['Id_Genero'] !== null ? strtoupper(trim((string)$r['Id_Genero'])) : '',
        'edad'    => $r['Edad_Reg'] !== null ? (int)$r['Edad_Reg'] : null,
        'tipEdad' => $r['Tipo_Edad'] !== null ? strtoupper(trim((string)$r['Tipo_Edad'])) : '',
        'ficha'   => $r['Ficha_Familiar'] !== null ? strtoupper(trim((string)$r['Ficha_Familiar'])) : null,
        'ups'     => $r['Id_Ups'] !== null ? strtoupper(trim((string)$r['Id_Ups'])) : '',
        'fg'      => $r['Fg_Tipo'] !== null ? strtoupper(trim((string)$r['Fg_Tipo'])) : '',
        'pab'     => ($pabRaw !== null && $pabRaw !== '' && is_numeric((string)$pabRaw)) ? (float)$pabRaw : null,
        'anio'    => $r['Anio'] !== null ? trim((string)$r['Anio']) : '',
        'mes'     => $r['Mes'] !== null ? trim((string)$r['Mes']) : '',
    ];
}

/* ============================================================
 * 2) PREDICADOS (mini-DSL que espeja las condiciones del T-SQL)
 * ============================================================ */

/**
 * Evalua un predicado del DSL contra una fila normalizada.
 *
 * Claves admitidas (se combinan con AND):
 *   'cod'         => 'Z019' | ['E100','E108']        Codigo_Item exacto
 *   'codF'        => 'I10' | ['E10','E11','E13','E14']  cod_item_f (familia CIE;
 *                                                    equivale a Codigo_Item LIKE 'I10%')
 *   'tip'         => 'D' | ['D','R']                 Tipo_Diagnostico (id_tipitem)
 *   'vl'          => 'NULL' | 'PC' | ['1','2','3']   Valor_Lab (NULL = IS NULL)
 *   'vlNum'       => ['lt',120] | ['ge',45] | ['bt',70,99]   rango numerico de
 *                    valor_lab (como materno: TRY_CONVERT numerico). 'any' = solo
 *                    ISNUMERIC(valor_lab)=1.
 *   'rownum'      => 1 | 2                           Id_Correlativo_Lab (I_ROWNUM_LAB)
 *   'sexo'        => 'F'                             Id_Genero
 *   'edadA'       => [12, 59] | [40, null]           Tipo_Edad='A' y Edad_Reg en rango
 *   'pab'         => ['le',88] | ['gt',102]          Perimetro_Abdominal numerico
 *   'ups'         => '302701' | [...]                Id_Ups
 *   'ficha'       => 'APP100'                        Ficha_Familiar
 *   'fg'          => 'CX'                            Fg_Tipo (caso confirmado)
 *   'renaesCat'   => ['I-2','I-3','I-4']             renaes en catalogo de categorias
 *                                                    MAESTRO_HIS_ESTABLECIMIENTO
 *   'citaTiene'   => <predicado>                     EXISTS: alguna fila de la MISMA
 *                                                    cita lo cumple (tablas #temp)
 *   'citaTieneTodo' => [<pred>, ...]                 AND de EXISTS sobre la cita
 *   'cualquieraDe'  => [<pred>, ...]                 OR de predicados sobre la misma fila
 *
 * $ctx: contexto de ejecucion (filas + indices porCita/porCod/porCodF + catalogo renaes).
 */
function ntCumple(array $f, array $cond, array $ctx): bool {
    if (isset($cond['cod'])) {
        $cods = is_array($cond['cod']) ? $cond['cod'] : [$cond['cod']];
        $cods = array_map(fn($c) => strtoupper((string)$c), $cods);
        if (!in_array($f['cod'], $cods, true)) return false;
    }
    if (isset($cond['codF'])) {
        $fams = is_array($cond['codF']) ? $cond['codF'] : [$cond['codF']];
        $fams = array_map(fn($c) => strtoupper((string)$c), $fams);
        if (!in_array($f['codF'], $fams, true)) return false;
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
    if (isset($cond['vlNum'])) {
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
        $v = (float)$f['vl'];
        $spec = $cond['vlNum'];
        if (is_array($spec)) {
            $op = strtolower((string)$spec[0]);
            if ($op !== 'any') {
                $a = isset($spec[1]) ? (float)$spec[1] : null;
                $b = isset($spec[2]) ? (float)$spec[2] : null;
                if ($op === 'lt'  && !($v <  $a)) return false;
                if ($op === 'le'  && !($v <= $a)) return false;
                if ($op === 'gt'  && !($v >  $a)) return false;
                if ($op === 'ge'  && !($v >= $a)) return false;
                if ($op === 'bt'  && !($v >= $a && $v <= $b)) return false;
            }
        }
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
        if ($min !== null && $f['edad'] < $min) return false;
        if ($max !== null && $f['edad'] > $max) return false;
    }
    if (isset($cond['pab'])) {
        if ($f['pab'] === null) return false;
        $v = $f['pab'];
        [$op, $a] = $cond['pab'];
        $op = strtolower((string)$op);
        if ($op === 'le' && !($v <= $a)) return false;
        if ($op === 'lt' && !($v <  $a)) return false;
        if ($op === 'gt' && !($v >  $a)) return false;
        if ($op === 'ge' && !($v >= $a)) return false;
    }
    if (isset($cond['ups'])) {
        $upss = is_array($cond['ups']) ? $cond['ups'] : [$cond['ups']];
        $upss = array_map(fn($u) => strtoupper((string)$u), $upss);
        if (!in_array($f['ups'], $upss, true)) return false;
    }
    if (isset($cond['ficha'])) {
        if ($f['ficha'] !== strtoupper((string)$cond['ficha'])) return false;
    }
    if (isset($cond['fg'])) {
        if ($f['fg'] !== strtoupper((string)$cond['fg'])) return false;
    }
    if (isset($cond['renaesCat'])) {
        $cats = is_array($cond['renaesCat']) ? $cond['renaesCat'] : [$cond['renaesCat']];
        $set = $ctx['renaesPorCategoria'] ?? null;
        if ($set === null) return false; // sin catalogo cargado
        $ok = false;
        foreach ($cats as $cat) {
            if (isset($set[strtoupper((string)$cat)][$f['renaes']])) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    if (isset($cond['citaTiene'])) {
        if (!ntCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!ntCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (ntCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** EXISTS sobre la cita: alguna fila de la cita cumple el predicado (por indice). */
function ntCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    // Optimizacion: usar buckets por codigo cuando el predicado filtra por cod/codF
    $cands = ntCandidatosCita($ctx, $cita, $cond);
    foreach ($cands as $idx) {
        if (ntCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Indices candidatos de la cita para un predicado (sobre-aproximacion por
 * buckets de codigo; la verificacion exacta la hace ntCumple).
 */
function ntCandidatosCita(array $ctx, string $cita, array $cond): array {
    $idxCita = $ctx['porCita'][$cita] ?? [];
    if (!isset($cond['cod']) && !isset($cond['codF'])) {
        return $idxCita; // sin filtro de codigo: todas las filas de la cita
    }
    $out = [];
    if (isset($cond['cod'])) {
        foreach ((array)$cond['cod'] as $c) {
            foreach ($ctx['porCitaCod'][$cita][strtoupper((string)$c)] ?? [] as $idx) $out[$idx] = true;
        }
    }
    if (isset($cond['codF'])) {
        foreach ((array)$cond['codF'] as $cf) {
            foreach ($ctx['porCitaCodF'][$cita][strtoupper((string)$cf)] ?? [] as $idx) $out[$idx] = true;
        }
    }
    return array_keys($out);
}

/**
 * Grupo etareo segun el CASE gedad del T-SQL.
 * $tipo: 'g6' (VALORACION/HTA/TM: exige A y >=5) | 'g8' (DM: D/M -> 1).
 * Null si la fila no mapea a ningun grupo (el T-SQL la excluye del nominal).
 */
function ntGedad(array $f, string $tipo): ?int {
    if ($tipo === 'g8') {
        if ($f['tipEdad'] === 'D' || $f['tipEdad'] === 'M') return 1;
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
        if ($f['edad'] < 1) return null; // A 0 anios no mapea (como el CASE)
        if ($f['edad'] <= 4) return 2;
        if ($f['edad'] <= 11) return 3;
        if ($f['edad'] <= 17) return 4;
        if ($f['edad'] <= 29) return 5;
        if ($f['edad'] <= 39) return 6;
        if ($f['edad'] <= 59) return 7;
        return 8;
    }
    // g6
    if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
    if ($f['edad'] < 5) return null;
    if ($f['edad'] <= 11) return 1;
    if ($f['edad'] <= 17) return 2;
    if ($f['edad'] <= 29) return 3;
    if ($f['edad'] <= 39) return 4;
    if ($f['edad'] <= 59) return 5;
    return 6;
}

/**
 * Genera una descripcion SQL-legible de una condicion del DSL (para el panel
 * "Ver condiciones SQL": auditoria contra el archivo 03).
 */
function ntCondicionSQL(array $cond, int $nivel = 0): string {
    $partes = [];
    foreach ($cond as $k => $v) {
        switch ($k) {
            case 'cod':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "cod_item IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'codF':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "cod_item_f IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'tip':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_tipitem IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'vl':
                if ($v === 'NULL') { $partes[] = "valor_lab IS NULL"; }
                else {
                    $lista = is_array($v) ? $v : [$v];
                    $partes[] = "valor_lab IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                }
                break;
            case 'vlNum':
                if (is_array($v)) {
                    $op = strtolower((string)$v[0]);
                    if ($op === 'any') $partes[] = "ISNUMERIC(valor_lab) = 1";
                    elseif ($op === 'bt') $partes[] = "TRY_CONVERT(numeric, valor_lab) BETWEEN {$v[1]} AND {$v[2]}";
                    else $partes[] = "TRY_CONVERT(numeric, valor_lab) {$op} " . ($v[1] ?? '');
                }
                break;
            case 'rownum':
                $partes[] = "I_ROWNUM_LAB = " . (int)$v;
                break;
            case 'sexo':
                $partes[] = "id_genero = '{$v}'";
                break;
            case 'edadA':
                $partes[] = "id_tipedad_reg = 'A' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 'NULL');
                break;
            case 'pab':
                $partes[] = "perimetro_abdominal {$v[0]} {$v[1]}";
                break;
            case 'ups':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_ups IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'ficha':
                $partes[] = "fichafam = '{$v}'";
                break;
            case 'fg':
                $partes[] = "fg_tipo = '{$v}'";
                break;
            case 'renaesCat':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "renaes IN (SELECT Codigo_Unico FROM MAESTRO_HIS_ESTABLECIMIENTO WHERE Categoria_Establecimiento IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . "))";
                break;
            case 'citaTiene':
                $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . ntCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaTieneTodo':
                $subs = array_map(fn($sub) => "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . ntCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' AND ', $subs) . ")";
                break;
            case 'cualquieraDe':
                $subs = array_map(fn($sub) => "(" . ntCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' OR ', $subs) . ")";
                break;
        }
    }
    $tab = str_repeat('    ', $nivel);
    return $tab . implode("\n{$tab}AND ", $partes);
}

/* ============================================================
 * 3) DEFINICION DE SECCIONES (adaptacion fiel del archivo
 *    "03 Creacion de Procedimientos": 24 procedimientos)
 * ============================================================ */

/** Layout 6GC: C=TOTAL, D-O = 6 grupos etareos x M/F (VALORACION/HTA/TM). */
function ntCols6GC(): array {
    $cols = ['total' => 'C', 'gM' => [], 'gF' => []];
    $letras = ['D', 'F', 'H', 'J', 'L', 'N']; // columna M de cada grupo
    foreach ($letras as $i => $l) {
        $cols['gM'][$i + 1] = $l;
        $cols['gF'][$i + 1] = chr(ord($l) + 1);
    }
    return $cols;
}

/** Layout 8GC: C=TOTAL, D-S = 8 grupos etareos x M/F (DM casos/glucemia/atencion/nefropatia). */
function ntCols8GC(): array {
    $cols = ['total' => 'C', 'gM' => [], 'gF' => []];
    $letras = ['D', 'F', 'H', 'J', 'L', 'N', 'P', 'R'];
    foreach ($letras as $i => $l) {
        $cols['gM'][$i + 1] = $l;
        $cols['gF'][$i + 1] = chr(ord($l) + 1);
    }
    return $cols;
}

/** Layout 8GE: E=TOTAL, F-U = 8 grupos etareos x M/F (DM control/tratamiento/valoracion). */
function ntCols8GE(): array {
    $cols = ['total' => 'E', 'gM' => [], 'gF' => []];
    $letras = ['F', 'H', 'J', 'L', 'N', 'P', 'R', 'T'];
    foreach ($letras as $i => $l) {
        $cols['gM'][$i + 1] = $l;
        $cols['gF'][$i + 1] = chr(ord($l) + 1);
    }
    return $cols;
}

/** Etiquetas de los grupos etareos. */
function ntGedadLabels(string $tipo): array {
    if ($tipo === 'g8') {
        return [1 => 'MENORES DE 1a', 2 => '01-04a', 3 => '05-11a', 4 => '12-17a',
                5 => '18-29a', 6 => '30-39a', 7 => '40-59a', 8 => '60a y m&aacute;s'];
    }
    return [1 => '05-11a', 2 => '12-17a', 3 => '18-29a', 4 => '30-39a',
            5 => '40-59a', 6 => '60a y m&aacute;s'];
}

/** Familias E1x (diabetes) usadas por los CASE del T-SQL. */
function ntFamE1x(): array {
    return ['E10', 'E11', 'E13', 'E14'];
}

/** Etiqueta "Categoría E1x" para las filas de DM. */
function ntCatE1x(): array {
    return ['E10' => 'Categor&iacute;a E10', 'E11' => 'Categor&iacute;a E11',
            'E13' => 'Categor&iacute;a E13', 'E14' => 'Categor&iacute;a E14'];
}

/**
 * Devuelve la definicion completa de las 24 secciones del reporte de
 * Enfermedades No Transmisibles (4 grupos: VALORACION, HTA, DM, TELESALUD).
 *
 * Cada seccion replica un bloque del Excel "Reporte_Actividades_NoTransmisibles.xlsx"
 * (hoja "Plantilla") y cada fila replica las categorias de su procedimiento
 * usp_TRAMA_BASE_NT_2025_* (mismo numero y orden). Campos:
 *   'grupo'       => 'VALORACION'|'HTA'|'DM'|'TM' (macro-grupo del Excel)
 *   'gedadTipo'   => 'g6'|'g8'    (CASE gedad del T-SQL)
 *   'regla'       => 'personas'|'filas'|'sesiones' (columna casos del consolidado)
 *   'layout'      => '6GC'|'8GC'|'8GE'|'SES'      (posicion de columnas Excel)
 *   'colsX'       => mapa de columnas Excel de la zona de datos
 *   'filas'       => cada fila: ['key','niv1','niv2','niv3','unidad','cond'|'calc','fila']
 *                    ('fila' = numero de fila Excel para el export)
 */
function ntSecciones(): array {

    $famE1x = ntFamE1x();
    $catE1x = ntCatE1x();
    $cols6 = ntCols6GC();
    $cols8C = ntCols8GC();
    $cols8E = ntCols8GE();

    // ============ Predicados base reutilizables ============
    $z019dr1 = ['cod' => 'Z019', 'tip' => 'D', 'rownum' => 1];                    // valoracion clinica
    $z019alt = ['cod' => 'Z019', 'tip' => 'D', 'vl' => 'ALT'];                    // valoracion con FR
    $citaZ019 = ['citaTiene' => $z019dr1];                                        // #EXAMEN (base)
    $presionR2 = ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['any']]; // PA diastolica
    $i1xfam = ['I10', 'I11', 'I12', 'I13'];                                       // familias HTA
    $e1xr1 = ['codF' => $famE1x, 'rownum' => 1];                                  // E1x R1 (any tip)
    $e1xdr1 = ['codF' => $famE1x, 'tip' => 'D', 'rownum' => 1];
    $e1xrr1 = ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1];
    $nefroDx = ['cod' => ['E102', 'E112', 'E134', 'E142'], 'tip' => 'R', 'rownum' => 1]; // nefropatia DM
    $n18r1 = ['cod' => ['N181', 'N182', 'N183'], 'tip' => 'R', 'rownum' => 1];

    /* ------------------------------------------------------------
     * GRUPO 01: VALORACION CLINICA Y TAMIZAJE (5 procedimientos)
     * ------------------------------------------------------------ */

    // ---- VR01: usp_..._01_VALORACION_RPT_01_FACTORES_RIESGO ----
    // #EXAMEN: cita con (cod base de factores R1) y Z019 D R1
    // #PRESION: cita con 99199.22 D R1 y R2 (ambos numericos)
    $frFilas = [];
    $frDefs = [
        1  => ['Sobrepeso',                                        ['cod' => 'E6690', 'rownum' => 1] + $citaZ019],
        2  => ['Obesidad',                                         ['cod' => 'E669', 'rownum' => 1] + $citaZ019],
        3  => ['Obesidad I',                                       ['cod' => 'E6691', 'rownum' => 1] + $citaZ019],
        4  => ['Obesidad II',                                      ['cod' => 'E6692', 'rownum' => 1] + $citaZ019],
        5  => ['Obesidad III',                                     ['cod' => 'E6693', 'rownum' => 1] + $citaZ019],
        6  => ['Problemas relacionados con el Tabaco',             ['cod' => 'Z720', 'rownum' => 1, 'edadA' => [12, null]] + $citaZ019],
        7  => ['Problemas relacionados con el Alcohol',            ['cod' => 'Z721', 'rownum' => 1] + $citaZ019],
        8  => ['Problemas relacionados con la falta ejercicio f&iacute;sico (sedentarismo)', ['cod' => 'Z723', 'rownum' => 1] + $citaZ019],
        9  => ['Problemas relacionados con la dieta y h&aacute;bitos alimenticios inapropiados', ['cod' => 'Z724', 'rownum' => 1] + $citaZ019],
        10 => ['Historia familiar de diabetes mellitus',           ['cod' => 'Z833', 'rownum' => 1] + $citaZ019],
        11 => ['Historia familiar de otras Enfermedades Endocrinas, Nutricionales y Metab&oacute;licas', ['cod' => 'Z834', 'rownum' => 1] + $citaZ019],
        12 => ['Dislipidemia',                                     ['cod' => ['E780', 'E781', 'E782', 'E784', 'E785'], 'tip' => 'D', 'rownum' => 1]],
        13 => ['Glucosa en sangre (82947) &oacute; Glucosa en tira reactiva (82948) - Normal',    ['cod' => ['82947', '82948'], 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['bt', 70, 99]]],
        14 => ['Glucosa en sangre (82947) &oacute; Glucosa en tira reactiva (82948) - Alterado',  ['cod' => ['82947', '82948'], 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['bt', 100, 125]]],
        15 => ['Glucosa en sangre (82947) &oacute; Glucosa en tira reactiva (82948) - Probable diabetes', ['cod' => ['82947', '82948'], 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['gt', 125]]],
        16 => ['Test de tolerancia oral a la glucosa alterada (de 140 a 199 mg/dl) *Opcional',    ['cod' => 'R730', 'tip' => 'D', 'rownum' => 1]],
        17 => ['Ex&aacute;men Presi&oacute;n Sangu&iacute;nea &Oacute;ptima',   ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['lt', 120], 'edadA' => [12, null],
                'citaTieneTodo' => [ ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['lt', 80]], $z019dr1 ]]],
        18 => ['Ex&aacute;men Presi&oacute;n Sangu&iacute;nea Normal',          ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['bt', 120, 129], 'edadA' => [12, null],
                'citaTieneTodo' => [ ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['bt', 80, 84]], $z019dr1 ]]],
        19 => ['Ex&aacute;men Presi&oacute;n Sangu&iacute;nea Normal-Alto',     ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['bt', 130, 139], 'edadA' => [12, null],
                'citaTieneTodo' => [ ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['bt', 85, 89]], $z019dr1 ]]],
        20 => ['Ex&aacute;men Presi&oacute;n Sangu&iacute;nea Anormal',         ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['ge', 140], 'edadA' => [12, null],
                'citaTieneTodo' => [ ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['ge', 90]], $z019dr1 ]]],
    ];
    foreach ($frDefs as $k => [$lbl, $cond]) {
        $frFilas[] = ['key' => "FR{$k}", 'niv1' => $lbl, 'niv2' => null, 'niv3' => null,
                      'unidad' => 'PERSONA', 'cond' => $cond, 'fila' => 11 + $k]; // Excel 12-31
    }

    // ---- VR04: #EXAMEN = cita con Z019 D R1 y Z017 D R1 ----
    $citaExamenZ017 = ['citaTieneTodo' => [$z019dr1, ['cod' => 'Z017', 'tip' => 'D', 'rownum' => 1]]];

    $secciones = [

    /* ------------------------------------------------------------
     * VR01 - usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_01_FACTORES_RIESGO
     * 20 factores de riesgo (DimNT_Factores). Filas Excel 12-31.
     * ------------------------------------------------------------ */
    [
        'codigo'        => 'VR01_FACTORES_RIESGO',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_01_FACTORES_RIESGO',
        'titulo'        => 'FACTORES DE RIESGO',
        'grupo'         => 'VALORACION',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'FACTOR DE RIESGO',
        'filas'         => $frFilas,
    ],

    /* ------------------------------------------------------------
     * VR02 - usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_02_EVALUACION_PAB
     * Evaluacion de perimetro abdominal: Normal/Anormal por sexo.
     * Filas Excel 36-37. Solo adultos (gedad 3-6 por el WHERE edad>=18).
     * ------------------------------------------------------------ */
    [
        'codigo'        => 'VR02_EVALUACION_PAB',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_02_EVALUACION_PAB',
        'titulo'        => 'EVALUACI&Oacute;N DE PER&Iacute;METRO ABDOMINAL',
        'grupo'         => 'VALORACION',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'EVALUACI&Oacute;N',
        'filas'         => [
            ['key' => 'PAB1', 'niv1' => 'NORMAL', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 36,
             'cond' => ['cod' => 'Z019', 'tip' => 'D', 'edadA' => [18, null],
                        'cualquieraDe' => [
                            ['sexo' => 'M', 'pab' => ['le', 102]],
                            ['sexo' => 'F', 'pab' => ['le', 88]],
                        ]]],
            ['key' => 'PAB2', 'niv1' => 'ANORMAL', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 37,
             'cond' => ['cod' => 'Z019', 'tip' => 'D', 'edadA' => [18, null],
                        'cualquieraDe' => [
                            ['sexo' => 'M', 'pab' => ['gt', 102]],
                            ['sexo' => 'F', 'pab' => ['gt', 88]],
                        ]]],
        ],
    ],

    /* ------------------------------------------------------------
     * VR03 - usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_03_VALORACION_CLINICA
     * Valoracion 1-2 (DimNT_Valoracion01). Filas Excel 42-43.
     * ------------------------------------------------------------ */
    [
        'codigo'        => 'VR03_VALORACION_CLINICA',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_03_VALORACION_CLINICA',
        'titulo'        => 'VALORACI&Oacute;N CL&Iacute;NICA Y TAMIZAJE LABORATORIAL DE ENFERMEDADES CR&Oacute;NICAS',
        'grupo'         => 'VALORACION',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'CLASIFICACI&Oacute;N',
        'filas'         => [
            ['key' => 'VC1', 'niv1' => 'PERSONAS QUE HAN RECIBIDO VALORACI&Oacute;N CL&Iacute;NICA DE FACTORES DE RIESGO (meta f&iacute;sica)', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 42,
             'cond' => $z019dr1 + ['edadA' => [5, null]]],
            ['key' => 'VC2', 'niv1' => 'VALORACI&Oacute;N CL&Iacute;NICA CON FACTORES DE RIESGO', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 43,
             'cond' => $z019alt + ['edadA' => [5, null], 'rownum' => 1]],
        ],
    ],

    /* ------------------------------------------------------------
     * VR04 - usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_04_VALORACION_CLINICA
     * Valoracion 1-4 (DimNT_Valoracion02). Filas Excel 47-50.
     * #EXAMEN = cita con Z019 D R1 y Z017 D R1; #LABORATORIO = cita con
     * Z019 R R1 y 82947/82948 D R1 numerico.
     * ------------------------------------------------------------ */
    [
        'codigo'        => 'VR04_VALORACION_CLINICA',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_04_VALORACION_CLINICA',
        'titulo'        => 'VALORACI&Oacute;N CL&Iacute;NICA Y TAMIZAJE (solicitud de laboratorio / entrega de resultados)',
        'grupo'         => 'VALORACION',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'CLASIFICACI&Oacute;N',
        'filas'         => [
            ['key' => 'VL1', 'niv1' => 'VALORACI&Oacute;N CL&Iacute;NICA CON FACTORES DE RIESGO Y SOLICITUD DE EXAMEN DE LABORATORIO', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 47,
             'cond' => $z019alt + ['edadA' => [5, null]] + $citaExamenZ017],
            ['key' => 'VL2', 'niv1' => 'VALORACI&Oacute;N CL&Iacute;NICA Y TAMIZAJE DE LABORATORIO', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 48,
             'cond' => $z019dr1 + ['edadA' => [40, null]] + $citaExamenZ017],
            ['key' => 'VL3', 'niv1' => 'VALORACI&Oacute;N CL&Iacute;NICA CON FACTORES DE RIESGO Y ENTREGA DE RESULTADOS (Glucosa s&eacute;rica 82947)', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 49,
             'cond' => ['cod' => 'Z019', 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '82947', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['any']]]],
            ['key' => 'VL4', 'niv1' => 'VALORACI&Oacute;N CL&Iacute;NICA CON FACTORES DE RIESGO Y ENTREGA DE RESULTADOS (Glucosa tira reactiva 82948)', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 50,
             'cond' => ['cod' => 'Z019', 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '82948', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['any']]]],
        ],
    ],

    /* ------------------------------------------------------------
     * VR05 - usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_05_VALORACION_CLINICA
     * Consejeria en estilos de vida saludable (99401.13 + Z019).
     * Fila Excel 55. #CONSEJERIA = cita con 99401.13 D R1 y Z019 D R1.
     * ------------------------------------------------------------ */
    [
        'codigo'        => 'VR05_INTERVENCION',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_01_VALORACION_RPT_05_VALORACION_CLINICA',
        'titulo'        => 'INTERVENCI&Oacute;N (CONSEJER&Iacute;A EN ESTILOS DE VIDA SALUDABLE)',
        'grupo'         => 'VALORACION',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'CLASIFICACI&Oacute;N',
        'filas'         => [
            ['key' => 'INT1', 'niv1' => 'VALORACI&Oacute;N CL&Iacute;NICA SIN FACTORES DE RIESGO, CON FACTORES DE RIESGO Y POST TAMIZAJE', 'niv2' => null, 'niv3' => null, 'unidad' => 'PERSONA', 'fila' => 55,
             'cond' => ['cod' => '99401.13', 'tip' => 'D', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => $z019dr1]],
        ],
    ],

    /* ------------------------------------------------------------
     * GRUPO 02: HTA (7 procedimientos)
     * ------------------------------------------------------------ */

    /* HTA1 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_01_CASOS
     * Casos diagnosticados (DimNT_HTA_Diagnostico01: 1-8). Filas 62-69.
     * #RETINOPATIA = H350 D/R R1 de citas con I10X R1. */
    [
        'codigo'        => 'HTA1_CASOS',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_01_CASOS',
        'titulo'        => 'CASOS DIAGNOSTICADOS',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'HTA_D1', 'niv1' => 'CASOS DE HIPERTENSI&Oacute;N ESENCIAL QUE ACUDEN A CONTROLES EN EL PERIODO DE EVALUACI&Oacute;N', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 62,
             'cond' => ['cod' => 'I10X', 'tip' => ['D', 'R'], 'rownum' => 1, 'edadA' => [5, null]]],
            ['key' => 'HTA_D2', 'niv1' => 'CASOS REPORTADOS COMO NUEVOS DE HIPERTENSI&Oacute;N ARTERIAL EN EL A&Ntilde;O', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 63,
             'cond' => ['cod' => 'I10X', 'tip' => 'D', 'rownum' => 1, 'edadA' => [5, null]]],
            ['key' => 'HTA_D3', 'niv1' => 'CASOS NUEVOS DE RETINOPAT&Iacute;A HIPERTENSIVA', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 64,
             'cond' => ['cod' => 'I10X', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => 'H350', 'tip' => 'D', 'rownum' => 1]]],
            ['key' => 'HTA_D4', 'niv1' => 'CASOS NUEVOS DE NEFROPAT&Iacute;A HIPERTENSIVA CON INSUFICIENCIA RENAL', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 65,
             'cond' => ['cod' => 'I120', 'tip' => 'D', 'rownum' => 1, 'edadA' => [5, null]]],
            ['key' => 'HTA_D5', 'niv1' => 'CASOS NUEVOS DE NEFROPAT&Iacute;A HIPERTENSIVA SIN INSUFICIENCIA RENAL', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 66,
             'cond' => ['cod' => 'I129', 'tip' => 'D', 'rownum' => 1, 'edadA' => [5, null]]],
            ['key' => 'HTA_D6', 'niv1' => 'CASOS ACUMULADOS DE RETINOPAT&Iacute;A HIPERTENSIVA', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 67,
             'cond' => ['cod' => 'I10X', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => 'H350', 'tip' => ['D', 'R'], 'rownum' => 1]]],
            ['key' => 'HTA_D7', 'niv1' => 'CASOS ACUMULADOS DE NEFROPAT&Iacute;A HIPERTENSIVA CON INSUFICIENCIA RENAL', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 68,
             'cond' => ['cod' => 'I120', 'tip' => ['D', 'R'], 'rownum' => 1, 'edadA' => [5, null]]],
            ['key' => 'HTA_D8', 'niv1' => 'CASOS ACUMULADOS DE NEFROPAT&Iacute;A HIPERTENSIVA SIN INSUFICIENCIA RENAL', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 69,
             'cond' => ['cod' => 'I129', 'tip' => ['D', 'R'], 'rownum' => 1, 'edadA' => [5, null]]],
        ],
    ],

    /* HTA2 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_02_EMERGENCIA
     * Manejo de emergencia/urgencia hipertensiva. Filas 75-77.
     * #HIPERTENSION = I1x(D) R1 de citas con R030 D URG/EMG R1. */
    [
        'codigo'        => 'HTA2_EMERGENCIA',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_02_EMERGENCIA',
        'titulo'        => 'MANEJO DE EMERGENCIA O URGENCIA HIPERTENSIVA',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'EMG1', 'niv1' => 'MANEJO DE LA URGENCIA HIPERTENSIVA', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 75,
             'cond' => ['cod' => 'R030', 'tip' => 'D', 'vl' => 'URG', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['codF' => $i1xfam, 'tip' => 'D', 'rownum' => 1]]],
            ['key' => 'EMG2', 'niv1' => 'CRISIS HIPERTENSIVA NO ESPECIFICADA', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 76,
             'cond' => ['cod' => 'I16X', 'tip' => 'D', 'rownum' => 1, 'edadA' => [5, null]]],
            ['key' => 'EMG3', 'niv1' => 'MANEJO DE LA EMERGENCIA HIPERTENSIVA', 'niv2' => null, 'niv3' => null, 'unidad' => 'CASO', 'fila' => 77,
             'cond' => ['cod' => 'R030', 'tip' => 'D', 'vl' => 'EMG', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['codF' => $i1xfam, 'tip' => 'D', 'rownum' => 1]]],
        ],
    ],

    /* HTA3 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_03_DISLIPIDEMIAS
     * Seguimiento dislipidemia + alto riesgo CV. Fila 82.
     * #DISLIP = 99199.23 D R1 >=10 de citas con E78x R1; #PRESION R1<140 y R2<90. */
    [
        'codigo'        => 'HTA3_DISLIPIDEMIAS',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_03_DISLIPIDEMIAS',
        'titulo'        => 'SEGUIMIENTO DE LAS PERSONAS CON DISLIPIDEMIA Y ALTO RIESGO CARDIOVASCULAR',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'DIS1', 'niv1' => 'PERSONA CON DISLIPIDEMIA Y ALTO RIESGO CARDIOVASCULAR EN SEGUIMIENTO', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 82,
             'cond' => ['cod' => ['E780', 'E781', 'E782', 'E784', 'E785'], 'tip' => 'D', 'rownum' => 1, 'edadA' => [40, null],
                        'citaTieneTodo' => [
                            ['cod' => '99199.23', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['ge', 10]],
                            ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 140]],
                            $presionR2 + ['vlNum' => ['lt', 90]],
                        ]]],
        ],
    ],

    /* HTA4 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_04_HIPERTENSO_SIN_DANO
     * Tratamiento HTA sin dano de organo (familias I10). Filas 87-92.
     * #TRATAMIENTO = 99199.22 R1 num de citas con I10(R) R1;
     * #PRESION = R1<130 y R2<80; #HIPERTENSION = 99199.23>=10;
     * #CONTROL = 99199.22 R1 de citas con I10 R R1 PC. */
    [
        'codigo'        => 'HTA4_SIN_DANO',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_04_HIPERTENSO_SIN_DANO',
        'titulo'        => 'TRATAMIENTO PARA PERSONA CON HIPERTENSI&Oacute;N SIN DA&Ntilde;O DE &Oacute;RGANO BLANCO',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => ntHtaFilasTratamiento('HTA4', 87, ['I10']),
    ],

    /* HTA5 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_05_HIPERTENSO_TRATAMIENTO
     * Pacientes hipertensivos con tratamiento especializado (familias I1x).
     * Filas 98-103 (paciente controlado) + 105-108 (atencion integral). */
    [
        'codigo'        => 'HTA5_TRATAMIENTO',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_05_HIPERTENSO_TRATAMIENTO',
        'titulo'        => 'PACIENTES HIPERTENSIVOS CON TRATAMIENTO ESPECIALIZADO (5001705)',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => array_merge(
            ntHtaFilasTratamiento('HTA5', 98, $i1xfam),
            [
                ['key' => 'HTA5_D7', 'niv1' => 'ATENCI&Oacute;N INTEGRAL ESPECIALIZADO', 'niv2' => 'Nefrolog&iacute;a', 'niv3' => null, 'unidad' => null, 'fila' => 105,
                 'cond' => ['codF' => $i1xfam, 'rownum' => 1, 'edadA' => [5, null], 'ups' => '302701']],
                ['key' => 'HTA5_D8', 'niv1' => 'ATENCI&Oacute;N INTEGRAL ESPECIALIZADO', 'niv2' => 'Cardiolog&iacute;a', 'niv3' => null, 'unidad' => null, 'fila' => 106,
                 'cond' => ['codF' => $i1xfam, 'rownum' => 1, 'edadA' => [5, null], 'ups' => '300201']],
                ['key' => 'HTA5_D9', 'niv1' => 'ATENCI&Oacute;N INTEGRAL ESPECIALIZADO', 'niv2' => 'Oftalmolog&iacute;a', 'niv3' => null, 'unidad' => null, 'fila' => 107,
                 'cond' => ['codF' => $i1xfam, 'rownum' => 1, 'edadA' => [5, null], 'ups' => '303408']],
                ['key' => 'HTA5_D10', 'niv1' => 'ATENCI&Oacute;N INTEGRAL ESPECIALIZADO', 'niv2' => 'Nutrici&oacute;n', 'niv3' => null, 'unidad' => null, 'fila' => 108,
                 'cond' => ['codF' => $i1xfam, 'rownum' => 1, 'edadA' => [5, null], 'ups' => '303101']],
            ]
        ),
    ],

    /* HTA6 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_06_HIPERTENSO_RIESGO
     * Estratificacion de riesgo cardiovascular (99199.23). Filas 113-117.
     * Solo gedad 5-6 (edad>=40). #ATENCION = I10(R) de citas con 99199.23 D. */
    [
        'codigo'        => 'HTA6_RIESGO',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_06_HIPERTENSO_RIESGO',
        'titulo'        => 'PACIENTES HIPERTENSOS CON ESTRATIFICACI&Oacute;N DE RIESGO CARDIOVASCULAR',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'RG1', 'niv1' => 'Bajo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona evaluada', 'fila' => 113,
             'cond' => ['cod' => '99199.23', 'rownum' => 1, 'vlNum' => ['lt', 5], 'edadA' => [40, null],
                        'citaTieneTodo' => [
                            ['cod' => '99199.23', 'tip' => 'D', 'vlNum' => ['any']],
                            ['codF' => 'I10', 'tip' => 'R'],
                        ]]],
            ['key' => 'RG2', 'niv1' => 'Moderado', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona evaluada', 'fila' => 114,
             'cond' => ['cod' => '99199.23', 'rownum' => 1, 'vlNum' => ['bt', 5, 9], 'edadA' => [40, null],
                        'citaTieneTodo' => [
                            ['cod' => '99199.23', 'tip' => 'D', 'vlNum' => ['any']],
                            ['codF' => 'I10', 'tip' => 'R'],
                        ]]],
            ['key' => 'RG3', 'niv1' => 'Alto', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona evaluada', 'fila' => 115,
             'cond' => ['cod' => '99199.23', 'rownum' => 1, 'vlNum' => ['bt', 10, 19], 'edadA' => [40, null],
                        'citaTieneTodo' => [
                            ['cod' => '99199.23', 'tip' => 'D', 'vlNum' => ['any']],
                            ['codF' => 'I10', 'tip' => 'R'],
                        ]]],
            ['key' => 'RG4', 'niv1' => 'Muy Alto', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona evaluada', 'fila' => 116,
             'cond' => ['cod' => '99199.23', 'rownum' => 1, 'vlNum' => ['bt', 20, 29], 'edadA' => [40, null],
                        'citaTieneTodo' => [
                            ['cod' => '99199.23', 'tip' => 'D', 'vlNum' => ['any']],
                            ['codF' => 'I10', 'tip' => 'R'],
                        ]]],
            ['key' => 'RG5', 'niv1' => 'Cr&iacute;tico', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona evaluada', 'fila' => 117,
             'cond' => ['cod' => '99199.23', 'rownum' => 1, 'vlNum' => ['ge', 30], 'edadA' => [40, null],
                        'citaTieneTodo' => [
                            ['cod' => '99199.23', 'tip' => 'D', 'vlNum' => ['any']],
                            ['codF' => 'I10', 'tip' => 'R'],
                        ]]],
        ],
    ],

    /* HTA7 - usp_TRAMA_BASE_NT_2025_02_HTA_RPT_07_SESIONES
     * Sesiones (C0009/C0010/C0012 + APP100). Filas 122-127.
     * Consolidado: count(*) Num + sum(LAB) Participantes (LAB null -> 1).
     * Layout SES: C=No, D=Participantes. Sin columnas de edad/sexo. */
    [
        'codigo'        => 'HTA7_SESIONES',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_02_HTA_RPT_07_SESIONES',
        'titulo'        => 'PACIENTES CON ENFERMEDAD CARDIOMETAB&Oacute;LICA ORGANIZADOS QUE RECIBEN SESIONES',
        'grupo'         => 'HTA',
        'gedadTipo'     => 'g6',
        'regla'         => 'sesiones',
        'layout'        => 'SES',
        'colsX'         => ['num' => 'C', 'part' => 'D'],
        'columna_label' => 'SESI&Oacute;N',
        'filas'         => [
            ['key' => 'SES_GAM_DM', 'niv1' => 'SESI&Oacute;N DE GRUPO DE AYUDA MUTUA', 'niv2' => 'PERSONAS CON DIABETES', 'niv3' => null, 'unidad' => null, 'fila' => 122,
             'cond' => ['cod' => 'C0012', 'ficha' => 'APP100', 'rownum' => 1, 'citaTiene' => $e1xr1]],
            ['key' => 'SES_GAM_HT', 'niv1' => 'SESI&Oacute;N DE GRUPO DE AYUDA MUTUA', 'niv2' => 'PERSONAS CON HIPERTENSI&Oacute;N', 'niv3' => null, 'unidad' => null, 'fila' => 123,
             'cond' => ['cod' => 'C0012', 'ficha' => 'APP100', 'rownum' => 1, 'citaTiene' => ['cod' => 'I10X', 'rownum' => 1]]],
            ['key' => 'SES_DEM_DM', 'niv1' => 'SESI&Oacute;N DEMOSTRATIVA', 'niv2' => 'PERSONAS CON DIABETES', 'niv3' => null, 'unidad' => null, 'fila' => 124,
             'cond' => ['cod' => 'C0010', 'ficha' => 'APP100', 'rownum' => 1, 'citaTiene' => $e1xr1]],
            ['key' => 'SES_DEM_HT', 'niv1' => 'SESI&Oacute;N DEMOSTRATIVA', 'niv2' => 'PERSONAS CON HIPERTENSI&Oacute;N', 'niv3' => null, 'unidad' => null, 'fila' => 125,
             'cond' => ['cod' => 'C0010', 'ficha' => 'APP100', 'rownum' => 1, 'citaTiene' => ['cod' => 'I10X', 'rownum' => 1]]],
            ['key' => 'SES_EDU_DM', 'niv1' => 'SESI&Oacute;N EDUCATIVA', 'niv2' => 'PERSONAS CON DIABETES', 'niv3' => null, 'unidad' => null, 'fila' => 126,
             'cond' => ['cod' => 'C0009', 'ficha' => 'APP100', 'rownum' => 1, 'citaTiene' => $e1xr1]],
            ['key' => 'SES_EDU_HT', 'niv1' => 'SESI&Oacute;N EDUCATIVA', 'niv2' => 'PERSONAS CON HIPERTENSI&Oacute;N', 'niv3' => null, 'unidad' => null, 'fila' => 127,
             'cond' => ['cod' => 'C0009', 'ficha' => 'APP100', 'rownum' => 1, 'citaTiene' => ['cod' => 'I10X', 'rownum' => 1]]],
        ],
    ],

    /* ------------------------------------------------------------
     * GRUPO 03: DIABETES MELLITUS (7 procedimientos, gedad 1-8)
     * ------------------------------------------------------------ */

    /* DM01 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_01_CASOS
     * Casos diagnosticados (DimNT_DM_Diagnostico01: 1-13). Filas 134-146.
     * #ATENCION = E1x(R1) de citas con E06/A15/I10 (D,R) R1.
     * Todos exigen fg_tipo='CX' (caso confirmado). */
    [
        'codigo'        => 'DM01_CASOS',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_01_CASOS',
        'titulo'        => 'CASOS DIAGNOSTICADOS',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'personas',
        'layout'        => '8GC',
        'colsX'         => $cols8C,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'DM_D1', 'niv1' => 'Casos atendidos de diabetes mellitus tipo 1 atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 134,
             'cond' => ['codF' => 'E10', 'tip' => ['D', 'R'], 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D2', 'niv1' => 'Casos atendidos de diabetes mellitus tipo 2 atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 135,
             'cond' => ['codF' => 'E11', 'tip' => ['D', 'R'], 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D3', 'niv1' => 'Casos atendidos de diabetes gestacional atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 136,
             'cond' => ['codF' => 'O24', 'tip' => ['D', 'R'], 'rownum' => 1, 'fg' => 'CX', 'edadA' => [5, 59]]],
            ['key' => 'DM_D4', 'niv1' => 'Casos atendidos de otros tipos de diabetes atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 137,
             'cond' => ['codF' => ['E13', 'E14'], 'tip' => ['D', 'R'], 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D5', 'niv1' => 'Casos nuevos de diabetes mellitus tipo 1 atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 138,
             'cond' => ['codF' => 'E10', 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D6', 'niv1' => 'Casos nuevos de diabetes mellitus tipo 2 atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 139,
             'cond' => ['codF' => 'E11', 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D7', 'niv1' => 'Casos nuevos de diabetes gestacional atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 140,
             'cond' => ['codF' => 'O24', 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX', 'edadA' => [5, 59]]],
            ['key' => 'DM_D8', 'niv1' => 'Casos nuevos de otros tipos de diabetes atendidos en el periodo de evaluaci&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 141,
             'cond' => ['codF' => ['E13', 'E14'], 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D9', 'niv1' => 'Nefropat&iacute;a diab&eacute;tica registrada en el periodo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 142,
             'cond' => ['cod' => ['E102', 'E112', 'E132', 'E142'], 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D10', 'niv1' => 'Retinopat&iacute;a diab&eacute;tica registrada en el periodo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 143,
             'cond' => ['cod' => ['E103', 'E113', 'E133', 'E143', 'H360'], 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX']],
            ['key' => 'DM_D11', 'niv1' => 'Tiroiditis registrada en el periodo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 144,
             'cond' => ['codF' => 'E06', 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX', 'citaTiene' => $e1xr1]],
            ['key' => 'DM_D12', 'niv1' => 'Tuberculosis pulmonar registrada en el periodo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 145,
             'cond' => ['codF' => 'A15', 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX', 'citaTiene' => $e1xr1]],
            ['key' => 'DM_D13', 'niv1' => 'Hipertensi&oacute;n arterial registrada en el periodo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 146,
             'cond' => ['codF' => 'I10', 'tip' => 'D', 'rownum' => 1, 'fg' => 'CX', 'citaTiene' => $e1xr1]],
        ],
    ],

    /* DM02 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_02_GLUCEMIA
     * Manejo basico de crisis de glucemia (DimNT_DM_Diagnostico02: 1-7).
     * Filas 152-158. Consolidado con count(*) (regla 'filas').
     * #GLUCEMIA = E1x (CX) R1 de citas con E160/E162/R739 R1. */
    [
        'codigo'        => 'DM02_GLUCEMIA',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_02_GLUCEMIA',
        'titulo'        => 'MANEJO B&Aacute;SICO DE CRISIS HIPOGLUCEMIA O HIPERGLUCEMIA EN PACIENTES CON DIAGN&Oacute;STICO DE DIABETES',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'filas',
        'layout'        => '8GC',
        'colsX'         => $cols8C,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'GLU1', 'niv1' => 'Hipoglicemia en pacientes con diabetes tipo 1', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 152,
             'cond' => ['cod' => ['E160', 'E162'], 'rownum' => 1,
                        'citaTieneTodo' => [
                            ['codF' => $famE1x, 'rownum' => 1, 'fg' => 'CX'],
                            ['cod' => ['E100', 'E108']],
                        ]]],
            ['key' => 'GLU2', 'niv1' => 'Hipoglicemia en pacientes con diabetes tipo 2', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 153,
             'cond' => ['cod' => ['E160', 'E162'], 'rownum' => 1,
                        'citaTieneTodo' => [
                            ['codF' => $famE1x, 'rownum' => 1, 'fg' => 'CX'],
                            ['cod' => ['E110', 'E118']],
                        ]]],
            ['key' => 'GLU3', 'niv1' => 'Hipoglicemia en pacientes con otros tipos de diabetes', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 154,
             'cond' => ['cod' => ['E160', 'E162'], 'rownum' => 1,
                        'citaTieneTodo' => [
                            ['codF' => $famE1x, 'rownum' => 1, 'fg' => 'CX'],
                            ['cod' => ['E130', 'E138', 'E140', 'E148']],
                        ]]],
            ['key' => 'GLU4', 'niv1' => 'Coma Diab&eacute;tico Seg&uacute;n tipo de diabetes', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 155,
             'cond' => ['cod' => ['E100', 'E110', 'E130', 'E140'], 'tip' => 'D', 'rownum' => 1]],
            ['key' => 'GLU5', 'niv1' => 'Cetoacidosis Seg&uacute;n tipo de diabetes', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 156,
             'cond' => ['cod' => ['E101', 'E111', 'E131', 'E141'], 'tip' => 'D', 'rownum' => 1]],
            ['key' => 'GLU6', 'niv1' => 'Hipoglicemia inducida por medicamentos seg&uacute;n tipo de diabetes', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 157,
             'cond' => ['cod' => 'E160', 'rownum' => 1, 'citaTiene' => ['codF' => $famE1x, 'rownum' => 1, 'fg' => 'CX']]],
            ['key' => 'GLU7', 'niv1' => 'Hiperglicemia no especificada seg&uacute;n tipo de diabetes', 'niv2' => null, 'niv3' => null, 'unidad' => 'Caso', 'fila' => 158,
             'cond' => ['cod' => 'R739', 'rownum' => 1, 'citaTiene' => ['codF' => $famE1x, 'rownum' => 1, 'fg' => 'CX']]],
        ],
    ],

    /* DM03 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_03_CONTROL
     * Paciente diabetico no complicado controlado con tratamiento basico
     * (52 categorias, DimNT_DM_Diagnostico03). Filas 163-214. Layout 8GE.
     * #RENAES = MAESTRO Categoria I-2/I-3/I-4 (nivel 1). */
    [
        'codigo'        => 'DM03_CONTROL',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_03_CONTROL',
        'titulo'        => 'PACIENTE DIAB&Eacute;TICO NO COMPLICADO CONTROLADO CON TRATAMIENTO B&Aacute;SICO (5001703) &mdash; Nivel I-2/I-3/I-4',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'personas',
        'layout'        => '8GE',
        'colsX'         => $cols8E,
        'columna_label' => 'PERSONA / DIAGN&Oacute;STICO',
        'filas'         => ntDmFilasControl(163, ['I-2', 'I-3', 'I-4']),
    ],

    /* DM04 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_04_TRATAMIENTO
     * Pacientes diabeticos con tratamiento especializado (mismas 52
     * categorias). Filas 219-270. Layout 8GE.
     * #RENAES = MAESTRO Categoria II/III nivel 2. */
    [
        'codigo'        => 'DM04_TRATAMIENTO',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_04_TRATAMIENTO',
        'titulo'        => 'PACIENTES DIAB&Eacute;TICOS CON TRATAMIENTO ESPECIALIZADO (5001704) &mdash; Nivel II-*/III-*',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'personas',
        'layout'        => '8GE',
        'colsX'         => $cols8E,
        'columna_label' => 'PERSONA / DIAGN&Oacute;STICO',
        'filas'         => ntDmFilasControl(219, ['II-1', 'II-2', 'II-E', 'III-1', 'III-2', 'III-E']),
    ],

    /* DM05 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_05_ATENCION
     * Atencion de personas con diabetes segun especialidad (DimNT_DM_
     * Diagnostico05: 1-8, por id_ups). Filas 275-282.
     * #NEFROPATIA = N181-N185/R80X de citas con E1x(R) R1 en ups 302701. */
    [
        'codigo'        => 'DM05_ATENCION',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_05_ATENCION',
        'titulo'        => 'ATENCI&Oacute;N DE PERSONAS CON DIABETES SEG&Uacute;N ESPECIALIDAD',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'personas',
        'layout'        => '8GC',
        'colsX'         => $cols8C,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'ATD1', 'niv1' => 'Cardiolog&iacute;a', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 275,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '300201']],
            ['key' => 'ATD2', 'niv1' => 'Endocrinolog&iacute;a', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 276,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '301001']],
            ['key' => 'ATD3', 'niv1' => 'Oftalmolog&iacute;a', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 277,
             'cond' => ['cualquieraDe' => [
                            ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '303408'],
                            ['cod' => 'H360', 'tip' => 'R', 'rownum' => 1, 'ups' => '303408'],
                        ]]],
            ['key' => 'ATD4', 'niv1' => 'Neurolog&iacute;a', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 278,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '303008']],
            ['key' => 'ATD5', 'niv1' => 'Atenci&oacute;n por nefrolog&iacute;a', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 279,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '302701']],
            ['key' => 'ATD6', 'niv1' => 'Atenci&oacute;n de nefropat&iacute;a G1 o G2 con proteinuria', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 280,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '302701',
                        'citaTieneTodo' => [
                            ['cod' => ['N181', 'N182']],
                            ['cod' => 'R80X'],
                        ]]],
            ['key' => 'ATD7', 'niv1' => 'Atenci&oacute;n por nefropat&iacute;a con G3, G4 o G5', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 281,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '302701',
                        'citaTiene' => ['cod' => ['N183', 'N184', 'N185']]]],
            ['key' => 'ATD8', 'niv1' => 'Nutrici&oacute;n', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => 282,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'ups' => '303099']],
        ],
    ],

    /* DM06 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_06_VALORACION
     * Valoracion de complicaciones en personas con diabetes (88 categorias,
     * DimNT_DM_Diagnostico06). Filas 287-374. Layout 8GE.
     * #RENAES = MAESTRO Categoria I-4 + II/III nivel 2. */
    [
        'codigo'        => 'DM06_VALORACION',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_06_VALORACION',
        'titulo'        => 'VALORACI&Oacute;N DE COMPLICACIONES EN PERSONAS CON DIABETES (5001706) &mdash; Nivel I-4/II-*/III-*',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'personas',
        'layout'        => '8GE',
        'colsX'         => $cols8E,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => ntDmFilasValoracion(287, ['I-4', 'II-1', 'II-2', 'II-E', 'III-1', 'III-2', 'III-E']),
    ],

    /* DM07 - usp_TRAMA_BASE_NT_2025_03_DM_RPT_07_NEFROPATIA
     * Enfermedad renal diabetica (DimNT_DM_Diagnostico07: 11 categorias).
     * Filas 379-389. #RENAES = MAESTRO I-1/I-2/I-3/I-4/II-1.
     * #NEFROPATIA = N181-N183(R,R1) de citas con E102/E112/E134/E142(R,R1). */
    [
        'codigo'        => 'DM07_NEFROPATIA',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_03_DM_RPT_07_NEFROPATIA',
        'titulo'        => 'ENFERMEDAD RENAL DIAB&Eacute;TICA (5001707) &mdash; Nivel I-1 al II-1',
        'grupo'         => 'DM',
        'gedadTipo'     => 'g8',
        'regla'         => 'personas',
        'layout'        => '8GC',
        'colsX'         => $cols8C,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => ntDmFilasNefropatia(379, ['I-1', 'I-2', 'I-3', 'I-4', 'II-1']),
    ],

    /* ------------------------------------------------------------
     * GRUPO 04: TELESALUD (5 procedimientos, gedad 1-6)
     * ------------------------------------------------------------ */

    /* TM01 - usp_TRAMA_BASE_NT_2025_04_TM_RPT_01_TELEORIENTACION
     * Filas 396-398. #TELEORIENTACION = 99499.08(D) de citas con Z019 D R1;
     * #LABORATORIO = 99499.08(D,R1) de citas con Z017 D R1. */
    [
        'codigo'        => 'TM01_TELEORIENTACION',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_04_TM_RPT_01_TELEORIENTACION',
        'titulo'        => 'VALORACI&Oacute;N CL&Iacute;NICA Y TAMIZAJE LABORATORIAL DE ENFERMEDADES CR&Oacute;NICAS (TELEORIENTACI&Oacute;N)',
        'grupo'         => 'TM',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'TO1', 'niv1' => 'Valoraci&oacute;n Cl&iacute;nica', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 396,
             'cond' => $z019dr1 + ['edadA' => [5, null], 'citaTiene' => ['cod' => '99499.08', 'tip' => 'D']]],
            ['key' => 'TO2', 'niv1' => 'Valoraci&oacute;n Cl&iacute;nica con factores de riesgo', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 397,
             'cond' => $z019alt + ['edadA' => [5, null], 'citaTiene' => ['cod' => '99499.08', 'tip' => 'D']]],
            ['key' => 'TO3', 'niv1' => 'Valoraci&oacute;n cl&iacute;nica con factores de riesgo y solicitud de laboratorio', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 398,
             'cond' => $z019alt + ['edadA' => [5, null],
                        'citaTieneTodo' => [
                            ['cod' => '99499.08', 'tip' => 'D', 'rownum' => 1],
                            ['cod' => 'Z017', 'tip' => 'D', 'rownum' => 1],
                        ]]],
        ],
    ],

    /* TM02 - usp_TRAMA_BASE_NT_2025_04_TM_RPT_02_TELEMONITOREO
     * Fila 403. #TELEMONITOREO = 99499.10(D) de citas con 82947 D R1 num. */
    [
        'codigo'        => 'TM02_TELEMONITOREO',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_04_TM_RPT_02_TELEMONITOREO',
        'titulo'        => 'TELEMONITOREO',
        'grupo'         => 'TM',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'TMN1', 'niv1' => 'Valoraci&oacute;n Cl&iacute;nica con factores de riesgo Y Entrega de Resultados', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 403,
             'cond' => ['cod' => 'Z019', 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '82947', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['any']]]],
        ],
    ],

    /* TM03 - usp_TRAMA_BASE_NT_2025_04_TM_RPT_03_TELEMONITOREO_HTA
     * Filas 408-409. #TELEMONITOREO = 99499.10/99199.22 (D,R1) de citas con
     * I10(R,R2,num). */
    [
        'codigo'        => 'TM03_TELEMONITOREO_HTA',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_04_TM_RPT_03_TELEMONITOREO_HTA',
        'titulo'        => 'SEGUIMIENTO DE PACIENTES CON DIAGN&Oacute;STICOS DE HIPERTENSI&Oacute;N ARTERIAL (TELEMONITOREO)',
        'grupo'         => 'TM',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'TH1', 'niv1' => 'Paciente Hipertenso Sin tensi&oacute;metro', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 408,
             'cond' => ['codF' => 'I10', 'tip' => 'R', 'vl' => 'PC', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '99499.10', 'tip' => 'D', 'rownum' => 1]]],
            ['key' => 'TH2', 'niv1' => 'Paciente hipertenso con tensi&oacute;metro', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 409,
             'cond' => ['codF' => 'I10', 'tip' => 'R', 'vl' => 'PC', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTieneTodo' => [
                            ['cod' => '99499.10', 'tip' => 'D', 'rownum' => 1],
                            ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 1],
                        ]]],
        ],
    ],

    /* TM04 - usp_TRAMA_BASE_NT_2025_04_TM_RPT_04_TELEMONITOREO_DM
     * Filas 414-415 (sin glucometro = #TELEMONITOREO 99499.10; con glucometro
     * o resultados = #RESULTADOS 99499.11). El SP marca ambas con Valoracion=1. */
    [
        'codigo'        => 'TM04_TELEMONITOREO_DM',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_04_TM_RPT_04_TELEMONITOREO_DM',
        'titulo'        => 'SEGUIMIENTO DE PACIENTES CON DIAGN&Oacute;STICO DE DIABETES MELLITUS (TELEMONITOREO)',
        'grupo'         => 'TM',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'TD1', 'niv1' => 'Paciente diab&eacute;tico sin gluc&oacute;metro', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 414,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '99499.10', 'tip' => 'D', 'rownum' => 1]]],
            ['key' => 'TD2', 'niv1' => 'Paciente diab&eacute;tico con gluc&oacute;metro o resultados de laboratorio', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 415,
             'cond' => ['codF' => $famE1x, 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '99499.11', 'tip' => 'D', 'rownum' => 1]]],
        ],
    ],

    /* TM05 - usp_TRAMA_BASE_NT_2025_04_TM_RPT_05_TELECONSULTAS
     * Fila 419. #TELECONSULTAS = 99499.01(D) de citas con E1x/I10 (R,R1). */
    [
        'codigo'        => 'TM05_TELECONSULTAS',
        'procedimiento' => 'usp_TRAMA_BASE_NT_2025_04_TM_RPT_05_TELECONSULTAS',
        'titulo'        => 'TELECONSULTAS',
        'grupo'         => 'TM',
        'gedadTipo'     => 'g6',
        'regla'         => 'personas',
        'layout'        => '6GC',
        'colsX'         => $cols6,
        'columna_label' => 'DIAGN&Oacute;STICO',
        'filas'         => [
            ['key' => 'TC1', 'niv1' => 'TELECONSULTAS', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona', 'fila' => 419,
             'cond' => ['codF' => array_merge($famE1x, ['I10']), 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                        'citaTiene' => ['cod' => '99499.01', 'tip' => 'D']]],
        ],
    ],

    ];

    return $secciones;
}

/**
 * Construye las 52 filas de DM03/DM04 (DimNT_DM_Diagnostico03/04) con el
 * layout del Excel (filas $filaBase..$filaBase+51, PERSONA/DIAGNOSTICO/
 * CATEGORIA/UNIDAD/TOTAL + 8 grupos).
 *
 * Temporales del T-SQL (todos con renaes en #RENAES):
 *   #TRATAMIENTO = E1x + vl 1/2/3 (R,R2) de citas con E1x + PC (R,R1)
 *   #SEGUIMIENTO = E1x + PC (R,R1) de citas con 83036/82947/82948 (D,R1)
 *   #CONTROL     = E1x (R,R1) de citas con 83036/82947/82948/99199.22 (D,R1)
 *   #PRESION     = 99199.22 (D,R2,num) de citas con 99199.22 (D,R1,num)
 */
function ntDmFilasControl(int $filaBase, array $cats): array {
    $fam = ntFamE1x();
    $catLbl = ntCatE1x();
    $filas = [];
    $f = $filaBase;

    // ---- HbA1c (83036) y glucosa (82947/82948) y presion (99199.22) ----
    // [dxKey, label, condBase(fila), rangos]
    $hba1cDefs = [
        ['HbA1c65',  'Diabetes Mellitus con HbA1c &lt;6.5',                  ['cod' => '83036', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 65]]],
        ['HbA1c7',   'Diabetes Mellitus controlada seg&uacute;n HbA1c &lt;7%',  ['cod' => '83036', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 70]]],
        ['HbA1c8',   'Diabetes Mellitus con HbA1c &lt;8%',                   ['cod' => '83036', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 80]]],
        ['HbA1c8n',  'Diabetes Mellitus no controlada HbA1c=8',              ['cod' => '83036', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['ge', 80]]],
        ['HbA1c10',  'Diabetes Mellitus con alto riesgo seg&uacute;n HbA1c=10', ['cod' => '83036', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['ge', 100]]],
    ];
    // Orden de categorias del Excel: 1-4 (HbA1c<6.5), 5-8 (<7), 9-12 (<8),
    // 13-16 (>=8), 17-20 (>=100), 21-24 (glucosa<130), 25-28 (glucosa>=130),
    // 29-32 (presion en meta), 33-36 (presion fuera de meta)
    foreach ($hba1cDefs as $i => [$k, $lbl, $cond]) {
        foreach ($fam as $j => $famCod) {
            $filas[] = ['key' => "DM3_{$k}_{$famCod}", 'niv1' => $lbl, 'niv2' => $catLbl[$famCod], 'niv3' => null,
                        'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => $cond + ['renaesCat' => $cats,
                                           'citaTiene' => ['codF' => $famCod, 'tip' => 'R', 'rownum' => 1]]];
            $f++;
        }
    }
    // Glucosa en ayunas 82947/82948 (categorias 21-28)
    foreach ([['Glu130', 'Diabetes Mellitus con controles de glucosa en ayunas (&lt;130)', ['lt', 130]],
              ['Glu130n', 'Diabetes Mellitus con controles de glucosa en ayunas (&gt;=130)', ['ge', 130]]] as [$k, $lbl, $rng]) {
        foreach ($fam as $j => $famCod) {
            $filas[] = ['key' => "DM3_{$k}_{$famCod}", 'niv1' => $lbl, 'niv2' => $catLbl[$famCod], 'niv3' => null,
                        'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => ['cod' => ['82947', '82948'], 'tip' => 'D', 'rownum' => 1, 'vlNum' => $rng,
                                   'renaesCat' => $cats,
                                   'citaTiene' => ['codF' => $famCod, 'tip' => 'R', 'rownum' => 1]]];
            $f++;
        }
    }
    // Presion arterial en meta (29-32) y fuera de meta (33-36).
    // Nota fiel al SP: categoria 29 (E10) ancla 99199.22; categorias 30-32
    // (E11/E13/E14) anclan 82947/82948 (tal cual el CASE del archivo 03).
    foreach ([['PAmeta', 'Diabetes Mellitus con presi&oacute;n arterial en meta', ['lt', 130], ['lt', 80]],
              ['PAfuera', 'Diabetes Mellitus con presi&oacute;n arterial fuera de meta', ['ge', 130], ['ge', 80]]] as [$k, $lbl, $r1, $r2]) {
        foreach ($fam as $j => $famCod) {
            $ancla = ($k === 'PAmeta' && $famCod === 'E10') || ($k === 'PAfuera' && $famCod === 'E10')
                ? ['cod' => '99199.22'] : ['cod' => ['82947', '82948']];
            $filas[] = ['key' => "DM3_{$k}_{$famCod}", 'niv1' => $lbl, 'niv2' => $catLbl[$famCod], 'niv3' => null,
                        'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => $ancla + ['tip' => 'D', 'rownum' => 1, 'vlNum' => $r1, 'renaesCat' => $cats,
                                            'citaTieneTodo' => [
                                                ['codF' => $famCod, 'tip' => 'R', 'rownum' => 1],
                                                ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => $r2],
                                            ]]];
            $f++;
        }
    }
    // ---- Persona con diabetes que recibe seguimiento (37-40): #SEGUIMIENTO ----
    foreach ($fam as $j => $famCod) {
        $filas[] = ['key' => "DM3_SEG_{$famCod}", 'niv1' => 'Persona con diabetes que recibe Seguimiento',
                    'niv2' => 'Diabetes mellitus que recibe controles', 'niv3' => $catLbl[$famCod],
                    'unidad' => 'Persona Atendida', 'fila' => $f,
                    'cond' => ['cod' => ['83036', '82947', '82948'], 'tip' => 'D', 'rownum' => 1,
                               'renaesCat' => $cats,
                               'citaTiene' => ['codF' => $famCod, 'tip' => 'R', 'rownum' => 1, 'vl' => 'PC']]];
        $f++;
    }
    // ---- Persona con diabetes que recibe tratamiento (41-52): #TRATAMIENTO ----
    $tratLbl = ['E10' => 'Diabetes Mellitus tipo 1, que recibe tratamiento',
                'E11' => 'Diabetes Mellitus tipo 2, que recibe tratamiento',
                'E13' => 'Otras Diabetes Mellitus Especificadas, que recibe tratamiento',
                'E14' => 'Diabetes Mellitus, no Especificada, que recibe tratamiento'];
    foreach ($fam as $famCod) {
        foreach ([1 => '1 mes de tratamiento', 2 => '2 meses de tratamiento', 3 => '3 meses de tratamiento'] as $mes => $mesLbl) {
            $filas[] = ['key' => "DM3_TRA_{$famCod}_{$mes}", 'niv1' => 'Persona con diabetes que recibe tratamiento',
                        'niv2' => $tratLbl[$famCod], 'niv3' => $mesLbl,
                        'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => ['codF' => $famCod, 'tip' => 'R', 'rownum' => 1, 'vl' => 'PC',
                                   'renaesCat' => $cats,
                                   'citaTiene' => ['codF' => $famCod, 'tip' => 'R', 'rownum' => 2, 'vl' => (string)$mes]]];
            $f++;
        }
    }
    return $filas;
}

/**
 * Construye las 88 filas de DM06 (DimNT_DM_Diagnostico06): valoracion de
 * complicaciones en personas con diabetes. Filas $filaBase..$filaBase+87.
 * #VALORACION = E1x (D,R1) de citas con (D,R1 y cod de valoracion) + renaes.
 */
function ntDmFilasValoracion(int $filaBase, array $cats): array {
    $fam = ntFamE1x();
    $catLbl = ntCatE1x();
    $filas = [];
    $f = $filaBase;
    $citaE1x = fn() => ['citaTiene' => ['codF' => $fam, 'tip' => 'D', 'rownum' => 1, 'renaesCat' => $cats]];

    // (dxKey, label, condBase, subCategorias|null)
    // subCategorias: por familia -> [label, condExtra por familia] o fija
    $defs = [
        ['PieN',  'Diabetes Mellitus con valoraci&oacute;n de pie diab&eacute;tico normal',
            ['cod' => '99214.07', 'tip' => 'D', 'rownum' => 1, 'vl' => 'N'], null],
        ['PieA',  'Diabetes Mellitus con valoraci&oacute;n de pie diab&eacute;tico anormal',
            ['cod' => '99214.07', 'tip' => 'D', 'rownum' => 1, 'vl' => 'A'], null],
        ['TFG1',  'Diabetes Mellitus con Tasa de filtraci&oacute;n glomerular G1',
            ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['ge', 90]], null],
        ['TFG2',  'Diabetes Mellitus con Tasa de filtraci&oacute;n glomerular G2',
            ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['bt', 60, 89]], null],
        ['TFG3a', 'Diabetes Mellitus con Tasa de filtraci&oacute;n glomerular G3a',
            ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['bt', 45, 59]], null],
        ['TFG3b', 'Diabetes Mellitus con Tasa de filtraci&oacute;n glomerular G3b',
            ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['bt', 30, 44]], null],
        ['TFG4',  'Diabetes Mellitus con Tasa de filtraci&oacute;n glomerular G4',
            ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['bt', 15, 29]], null],
        ['TFG5',  'Diabetes Mellitus con Tasa de filtraci&oacute;n glomerular G5',
            ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 15]], null],
    ];
    foreach ($defs as [$k, $lbl, $cond]) {
        foreach ($fam as $famCod) {
            $filas[] = ['key' => "DM6_{$k}_{$famCod}", 'niv1' => $lbl, 'niv2' => $catLbl[$famCod],
                        'niv3' => '---------------------', 'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => $cond + ['renaesCat' => $cats, 'citaTiene' => ['codF' => $famCod, 'tip' => 'D', 'rownum' => 1]]];
            $f++;
        }
    }
    // Proteinuria segun tira reactiva (82044, categorias 0-4 x E1x = 20 filas)
    foreach ([0, 1, 2, 3, 4] as $c) {
        foreach ($fam as $famCod) {
            $filas[] = ['key' => "DM6_TIRA_{$c}_{$famCod}", 'niv1' => 'Diabetes Mellitus con proteinuria seg&uacute;n tira reactiva',
                        'niv2' => $catLbl[$famCod], 'niv3' => "Categor&iacute;a {$c}", 'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => ['cod' => '82044', 'tip' => 'D', 'rownum' => 1, 'vl' => (string)$c,
                                   'renaesCat' => $cats, 'citaTiene' => ['codF' => $famCod, 'tip' => 'D', 'rownum' => 1]]];
            $f++;
        }
    }
    // Proteinuria segun albuminuria (82043, A1/A2/A3 x E1x = 12 filas)
    foreach ([['A1', ['lt', 30]], ['A2', ['bt', 30, 299]], ['A3', ['ge', 300]]] as [$a, $rng]) {
        foreach ($fam as $famCod) {
            $filas[] = ['key' => "DM6_ALB_{$a}_{$famCod}", 'niv1' => 'Diabetes Mellitus con proteinuria seg&uacute;n albuminuria',
                        'niv2' => $catLbl[$famCod], 'niv3' => "Categor&iacute;a {$a}", 'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => ['cod' => '82043', 'tip' => 'D', 'rownum' => 1, 'vlNum' => $rng,
                                   'renaesCat' => $cats, 'citaTiene' => ['codF' => $famCod, 'tip' => 'D', 'rownum' => 1]]];
            $f++;
        }
    }
    // Fondo de ojo (92250/92226 x E1x = 4)
    foreach ($fam as $famCod) {
        $filas[] = ['key' => "DM6_FONDO_{$famCod}", 'niv1' => 'Diabetes Mellitus con fondo de ojo',
                    'niv2' => $catLbl[$famCod], 'niv3' => '---------------------', 'unidad' => 'Persona Atendida', 'fila' => $f,
                    'cond' => ['cod' => ['92250', '92226'], 'tip' => 'D', 'rownum' => 1,
                               'renaesCat' => $cats, 'citaTiene' => ['codF' => $famCod, 'tip' => 'D', 'rownum' => 1]]];
        $f++;
    }
    // Colesterol LDL (83721: <70, 70-99, 100-129, >=130 x E1x = 16)
    foreach ([['LDL70', '&lt; 70', ['lt', 70]], ['LDL7099', '70-99', ['bt', 70, 99]],
              ['LDL100129', '100-129', ['bt', 100, 129]], ['LDL130', '= 130 (&gt;=130)', ['ge', 130]]] as [$k, $lbl, $rng]) {
        foreach ($fam as $famCod) {
            $filas[] = ['key' => "DM6_{$k}_{$famCod}", 'niv1' => "Diabetes Mellitus con colesterol LDL {$lbl}",
                        'niv2' => $catLbl[$famCod], 'niv3' => '---------------------', 'unidad' => 'Persona Atendida', 'fila' => $f,
                        'cond' => ['cod' => '83721', 'tip' => 'D', 'rownum' => 1, 'vlNum' => $rng,
                                   'renaesCat' => $cats, 'citaTiene' => ['codF' => $famCod, 'tip' => 'D', 'rownum' => 1]]];
            $f++;
        }
    }
    // Valoracion no especificada (Temporal1: E1x + VAL R1, sin tip) = 4
    foreach ($fam as $famCod) {
        $filas[] = ['key' => "DM6_NESP_{$famCod}", 'niv1' => 'Diabetes Mellitus con Valoraci&oacute;n no especificada',
                    'niv2' => $catLbl[$famCod], 'niv3' => '---------------------', 'unidad' => 'Persona Atendida', 'fila' => $f,
                    'cond' => ['codF' => $famCod, 'vl' => 'VAL', 'rownum' => 1, 'renaesCat' => $cats]];
        $f++;
    }
    return $filas;
}

/**
 * Construye las 11 filas de DM07 (DimNT_DM_Diagnostico07): enfermedad renal
 * diabetica. Filas $filaBase..$filaBase+10. #NEFROPATIA = N181-N183 (R,R1)
 * de citas con E102/E112/E134/E142 (R,R1) en renaes I-1..II-1.
 */
function ntDmFilasNefropatia(int $filaBase, array $cats): array {
    $fam = ntFamE1x();
    $citaNefro = [['cod' => ['N181', 'N182', 'N183'], 'tip' => 'R', 'rownum' => 1],
                  ['cod' => ['E102', 'E112', 'E134', 'E142'], 'tip' => 'R', 'rownum' => 1]];
    $filas = [];
    $f = $filaBase;
    $filas[] = ['key' => 'DM7_SEG', 'niv1' => 'Personas con nefropat&iacute;a diab&eacute;tica en seguimiento en establecimientos nivel I-1 al II-1',
                'niv2' => '---------------------', 'niv3' => null, 'unidad' => 'Persona Atendida', 'fila' => $f,
                'cond' => ['cod' => ['E102', 'E112', 'E134', 'E142'], 'tip' => 'R', 'rownum' => 1,
                           'renaesCat' => $cats, 'citaTieneTodo' => $citaNefro]];
    $f++;
    $filas[] = ['key' => 'DM7_TFG45', 'niv1' => 'Personas con nefropat&iacute;a diab&eacute;tica en seguimiento en establecimientos nivel I-1 al II-1 seg&uacute;n tasa de filtraci&oacute;n glomerular',
                'niv2' => 'Bajo riesgo (&gt;=45)', 'niv3' => null, 'unidad' => 'Persona Atendida', 'fila' => $f,
                'cond' => ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['ge', 45],
                           'renaesCat' => $cats, 'citaTieneTodo' => $citaNefro]];
    $f++;
    $filas[] = ['key' => 'DM7_TFG45n', 'niv1' => 'Personas con nefropat&iacute;a diab&eacute;tica en seguimiento en establecimientos nivel I-1 al II-1 seg&uacute;n tasa de filtraci&oacute;n glomerular',
                'niv2' => 'Alto riesgo (&lt;45)', 'niv3' => null, 'unidad' => 'Persona Atendida', 'fila' => $f,
                'cond' => ['cod' => '82565', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 45],
                           'renaesCat' => $cats, 'citaTieneTodo' => $citaNefro]];
    $f++;
    foreach ([0, 1, 2, 3, 4] as $c) {
        $filas[] = ['key' => "DM7_TIRA_{$c}", 'niv1' => 'Personas con nefropat&iacute;a diab&eacute;tica en seguimiento en establecimientos nivel I-1 al II-1 seg&uacute;n albuminuria en tira reactiva',
                    'niv2' => "Categor&iacute;a {$c}", 'niv3' => null, 'unidad' => 'Persona Atendida', 'fila' => $f,
                    'cond' => ['cod' => '82044', 'tip' => 'D', 'rownum' => 1, 'vl' => (string)$c,
                               'renaesCat' => $cats, 'citaTieneTodo' => $citaNefro]];
        $f++;
    }
    foreach ([['A1', 'Categor&iacute;a A1 (&lt;30)', ['lt', 30]],
              ['A2', 'Categor&iacute;a A2 (30-299)', ['bt', 30, 299]],
              ['A3', 'Categor&iacute;a A3 (&gt;=300)', ['ge', 300]]] as [$k, $lbl, $rng]) {
        $filas[] = ['key' => "DM7_ALB_{$k}", 'niv1' => 'Personas con nefropat&iacute;a diab&eacute;tica en seguimiento en establecimientos nivel I-1 al II-1 seg&uacute;n albuminuria cuantitativa',
                    'niv2' => $lbl, 'niv3' => null, 'unidad' => 'Persona Atendida', 'fila' => $f,
                    'cond' => ['cod' => '82043', 'tip' => 'D', 'rownum' => 1, 'vlNum' => $rng,
                               'renaesCat' => $cats, 'citaTieneTodo' => $citaNefro]];
        $f++;
    }
    return $filas;
}

/**
 * Filas de tratamiento HTA (DimNT_HTA_Diagnostico04/05 dx 1-6), compartidas
 * por HTA4 (familias I10) y HTA5 (familias I10-I13). $filaBase = fila Excel
 * del primer dx (87 para HTA4, 98 para HTA5).
 */
function ntHtaFilasTratamiento(string $pref, int $filaBase, array $fams): array {
    return [
        ['key' => "{$pref}_D1", 'niv1' => 'Hipertensi&oacute;n esencial controlado', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => $filaBase,
         'cond' => ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['lt', 90], 'edadA' => [5, null],
                    'citaTieneTodo' => [
                        ['codF' => $fams, 'tip' => 'R', 'rownum' => 1],
                        ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['lt', 140]],
                    ]]],
        ['key' => "{$pref}_D2", 'niv1' => 'Hipertensi&oacute;n esencial con alto riesgo cardiovascular controlado', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => $filaBase + 1,
         'cond' => ['codF' => $fams, 'tip' => 'R', 'rownum' => 1, 'edadA' => [40, null],
                    'citaTieneTodo' => [
                        ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['lt', 130]],
                        ['cod' => '99199.22', 'tip' => 'D', 'rownum' => 2, 'vlNum' => ['lt', 80]],
                        ['cod' => '99199.23', 'tip' => 'D', 'rownum' => 1, 'vlNum' => ['ge', 10]],
                    ]]],
        ['key' => "{$pref}_D3", 'niv1' => 'Hipertensi&oacute;n esencial que recibe controles', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => $filaBase + 2,
         'cond' => ['codF' => $fams, 'tip' => 'R', 'rownum' => 1, 'edadA' => [5, null],
                    'citaTiene' => ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['any']]]],
        ['key' => "{$pref}_D4", 'niv1' => 'Tratamiento de la hipertensi&oacute;n arterial (avance de meta f&iacute;sica) (1 mes)', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => $filaBase + 3,
         'cond' => ['codF' => $fams, 'tip' => 'R', 'rownum' => 2, 'vl' => '1', 'edadA' => [5, null],
                    'citaTieneTodo' => [
                        ['codF' => $fams, 'tip' => 'R', 'rownum' => 1, 'vl' => 'PC'],
                        ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['any']],
                    ]]],
        ['key' => "{$pref}_D5", 'niv1' => 'Tratamiento de la hipertensi&oacute;n arterial (avance de meta f&iacute;sica) (2 meses)', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => $filaBase + 4,
         'cond' => ['codF' => $fams, 'tip' => 'R', 'rownum' => 2, 'vl' => '2', 'edadA' => [5, null],
                    'citaTieneTodo' => [
                        ['codF' => $fams, 'tip' => 'R', 'rownum' => 1, 'vl' => 'PC'],
                        ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['any']],
                    ]]],
        ['key' => "{$pref}_D6", 'niv1' => 'Tratamiento de la hipertensi&oacute;n arterial (avance de meta f&iacute;sica) (3 meses)', 'niv2' => null, 'niv3' => null, 'unidad' => 'Persona atendida', 'fila' => $filaBase + 5,
         'cond' => ['codF' => $fams, 'tip' => 'R', 'rownum' => 2, 'vl' => '3', 'edadA' => [5, null],
                    'citaTieneTodo' => [
                        ['codF' => $fams, 'tip' => 'R', 'rownum' => 1, 'vl' => 'PC'],
                        ['cod' => '99199.22', 'rownum' => 1, 'vlNum' => ['any']],
                    ]]],
    ];
}

/* ============================================================
 * 4) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/**
 * Anios disponibles en la tabla consolidada DENTRO DEL AMBITO del filtro
 * establecimiento (mismo criterio que Zoonosis/Materno).
 */
function ntGetAniosDisponibles(PDO $pdo, string $establecimiento = ''): array {
    try {
        $params = [];
        $whereEst = ntWhereEstablecimientos($pdo, $establecimiento, $params);
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

/** Meses (1..12) con datos dentro del ambito (establecimiento + anio). */
function ntGetMesesDisponibles(PDO $pdo, string $anio = '', string $establecimiento = ''): array {
    try {
        $params = [];
        $cond = [];
        if (trim($anio) !== '') {
            $cond[] = "Anio = :anio";
            $params[':anio'] = (string)$anio;
        }
        $whereEst = ntWhereEstablecimientos($pdo, $establecimiento, $params);
        if ($whereEst !== '') $cond[] = $whereEst;
        $colMes = ntTieneColumnaMesInt($pdo) ? 'Mes_Int' : "CAST(TRIM(Mes) AS UNSIGNED)";
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

/** Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES). */
function ntGetEstablecimientosZS(PDO $pdo): array {
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
 * AMBITO DE ESTABLECIMIENTOS compartido por los filtros del modulo:
 *   - Establecimiento concreto: TRIM(Codigo_Unico) = :est
 *   - '-- Todos --': TRIM(Codigo_Unico) IN (lista ZSPERENE completa)
 */
function ntWhereEstablecimientos(PDO $pdo, string $establecimiento, array &$params): string {
    $establecimiento = trim($establecimiento);
    if ($establecimiento !== '') {
        $params[':est'] = $establecimiento;
        return "TRIM(Codigo_Unico) = :est";
    }
    $lista = ntGetEstablecimientosZS($pdo);
    if (empty($lista)) {
        return '';
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
 * Catalogo de RENAES por categoria de establecimiento (MAESTRO_HIS_
 * ESTABLECIMIENTO, como el #RENAES del T-SQL de DM03/DM04/DM06/DM07).
 * @return array ['I-2' => ['12345' => true, ...], ...] o [] sin catalogo.
 */
function ntRenaesPorCategoria(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    try {
        $stmt = $pdo->query("SELECT TRIM(Codigo_Unico) cod, TRIM(Categoria_Establecimiento) cat
                             FROM MAESTRO_HIS_ESTABLECIMIENTO
                             WHERE Codigo_Unico IS NOT NULL AND Categoria_Establecimiento IS NOT NULL");
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cache[strtoupper($r['cat'])][$r['cod']] = true;
        }
    } catch (Throwable $e) {
        // sin maestro: las secciones con renaesCat quedan en 0
    }
    return $cache;
}

/** Indica si el catalogo MAESTRO_HIS_ESTABLECIMIENTO tiene datos. */
function ntMaestroDisponible(PDO $pdo): bool {
    try {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM MAESTRO_HIS_ESTABLECIMIENTO")->fetchColumn();
        return $n > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Codigos de item que intervienen en el reporte de No Transmisibles, segun
 * los 24 procedimientos del archivo "03 Creacion de Procedimientos":
 *   'exactos'  -> codigos usados con cod_item = (WHERE directo)
 *   'prefijos' -> familias CIE (cod_item_f) usadas como LIKE 'XXX%'
 */
function ntCodigosInteres(): array {
    return [
        'exactos' => [
            // ---- VR: valoracion clinica / laboratorio / factores ----
            'Z019', 'Z017', '99401.13',
            'E660', 'E669', 'E6690', 'E6691', 'E6692', 'E6693',
            'Z720', 'Z721', 'Z723', 'Z724', 'Z833', 'Z834',
            'E780', 'E781', 'E782', 'E784', 'E785', 'R730',
            '82947', '82948',
            // ---- HTA ----
            'I10X', 'I120', 'I129', 'I16X', 'R030', 'H350',
            '99199.22', '99199.23',
            'C0009', 'C0010', 'C0012',
            // ---- DM ----
            'E100', 'E101', 'E102', 'E103', 'E108',
            'E110', 'E111', 'E112', 'E113', 'E118',
            'E130', 'E131', 'E132', 'E133', 'E138',
            'E140', 'E141', 'E142', 'E143', 'E148',
            'E160', 'E162', 'R739', 'H360',
            'N181', 'N182', 'N183', 'N184', 'N185', 'R80X',
            '83036', '99214.07', '82565', '82044', '82043',
            '92250', '92226', '83721',
            // ---- TM ----
            '99499.08', '99499.10', '99499.11', '99499.01',
        ],
        'prefijos' => [
            'E10', 'E11', 'E13', 'E14',   // familias diabetes
            'E06', 'A15', 'O24',          // comorbilidades DM
            'I10', 'I11', 'I12', 'I13',   // familias HTA
        ],
    ];
}

/** Construye la clausula WHERE de codigos de item a partir de ntCodigosInteres(). */
function ntWhereCodigos(array &$params): string {
    $lista = ntCodigosInteres();
    $partes = [];
    $inPh = [];
    foreach ($lista['exactos'] as $j => $c) {
        $k = ":cod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "TRIM(Codigo_Item) IN (" . implode(',', $inPh) . ")";
    foreach ($lista['prefijos'] as $i => $pref) {
        $k = ":pref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "TRIM(Codigo_Item) LIKE $k";
    }
    return "(" . implode(" OR ", $partes) . ")";
}

/** Conexion PDO dedicada en modo UNBUFFERED (streaming), como Zoonosis/Materno. */
function ntAbrirConexionStreaming(): ?PDO {
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
function ntTieneColumnaMesInt(PDO $pdo): bool {
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

/** Diagnostico del entorno PHP + tabla consolidada (panel de error del reporte). */
function ntDiagnosticoEntorno(PDO $pdo): array {
    $out = [
        'php' => PHP_VERSION,
        'memory_limit' => (string)ini_get('memory_limit'),
        'max_exec_time' => (string)ini_get('max_execution_time'),
        'filas_tabla' => null,
        'indices' => [],
        'maestro_establecimientos' => false,
    ];
    try {
        $out['filas_tabla'] = (int)$pdo->query("SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetchColumn();
    } catch (Throwable $e) { /* sin datos */ }
    try {
        foreach ($pdo->query("SHOW INDEX FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out['indices'][$row['Key_name']] = true;
        }
        $out['indices'] = array_keys($out['indices']);
    } catch (Throwable $e) { /* sin indices */ }
    $out['maestro_establecimientos'] = ntMaestroDisponible($pdo);
    return $out;
}

/**
 * Cuenta/suma las filas que cumplen una condicion aplicando la regla de conteo
 * del T-SQL (columna "casos" del consolidado correspondiente):
 *   'personas' : distintos pares (renaes|id_persona|anio|mes) por gedad/sexo.
 *   'filas'    : cada fila HIS que cumple cuenta 1 en su gedad (count(*)).
 *   'sesiones' : 'filas' para N y suma del valor numerico de valor_lab
 *                (NULL -> 1, iif del T-SQL) para PARTICIPANTES.
 *
 * $conSexo: la seccion desglosa por sexo (M/F en columnas). Las filas con sexo
 * distinto de M/F se excluyen (el T-SQL exige "sexo is not null" y luego
 * agrupa por sexo M/F en el consolidado).
 *
 * @return array ['gedades'=>[g=>n], 'sexo'=>[g][M|F]=>n, 'part'=>float]
 */
function ntContar(?array $cond, string $regla, array $ctx, bool $conSexo): array {
    $gedades = [];
    $sexoCnt = [];
    $part = 0.0;
    if ($cond === null) {
        return ['gedades' => [], 'sexo' => [], 'part' => 0.0];
    }
    $conGedad = $ctx['conGedad'];
    $pacSet = []; // gedad => set de "renaes|pac|anio|mes"
    $candidatos = ntCandidatosFila($cond, $ctx);
    foreach ($candidatos as $idx) {
        $f = $ctx['filas'][$idx];
        if (!ntCumple($f, $cond, $ctx)) continue;
        if ($conSexo && $conGedad) {
            if ($f['sexo'] !== 'M' && $f['sexo'] !== 'F') continue;
        }
        $g = $conGedad ? ntGedad($f, $ctx['gedadTipo']) : 0;
        if ($g === null) continue; // fuera de los grupos del reporte
        if ($regla === 'personas') {
            $pacKey = $f['renaes'] . '|' . $f['pac'] . '|' . $f['anio'] . '|' . $f['mes'];
            if ($pacKey === '|||') $pacKey = '#f' . $idx;
            if (!isset($pacSet[$g][$pacKey])) {
                $pacSet[$g][$pacKey] = true;
                $gedades[$g] = ($gedades[$g] ?? 0) + 1;
                if ($conSexo && $conGedad) $sexoCnt[$g][$f['sexo']] = ($sexoCnt[$g][$f['sexo']] ?? 0) + 1;
            }
        } else {
            $gedades[$g] = ($gedades[$g] ?? 0) + 1;
            if ($conSexo && $conGedad) $sexoCnt[$g][$f['sexo']] = ($sexoCnt[$g][$f['sexo']] ?? 0) + 1;
            if ($regla === 'sesiones') {
                $v = ($f['vl'] !== null && $f['vl'] !== '' && is_numeric($f['vl'])) ? (float)$f['vl'] : 1.0;
                $part += $v;
            }
        }
    }
    return ['gedades' => $gedades, 'sexo' => $sexoCnt, 'part' => $part];
}

/**
 * Indices candidatos de TODAS las filas (no de una cita) para un predicado,
 * por buckets de codigo (sobre-aproximacion; ntCumple verifica exacto).
 */
function ntCandidatosFila(array $cond, array $ctx): array {
    $total = count($ctx['filas']);
    if ($total <= 0) return [];
    if (!isset($cond['cod']) && !isset($cond['codF']) && !isset($cond['cualquieraDe'])) {
        return range(0, $total - 1);
    }
    $out = [];
    if (isset($cond['cod'])) {
        foreach ((array)$cond['cod'] as $c) {
            foreach ($ctx['porCod'][strtoupper((string)$c)] ?? [] as $idx) $out[$idx] = true;
        }
    }
    if (isset($cond['codF'])) {
        foreach ((array)$cond['codF'] as $cf) {
            foreach ($ctx['porCodF'][strtoupper((string)$cf)] ?? [] as $idx) $out[$idx] = true;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        foreach ($cond['cualquieraDe'] as $sub) {
            foreach (ntCandidatosFila($sub, $ctx) as $idx) $out[$idx] = true;
        }
    }
    return array_keys($out);
}

/**
 * Ejecuta el reporte de No Transmisibles completo (adaptacion de los 24
 * procedimientos del archivo "03 Creacion de Procedimientos").
 *
 * Estrategia (igual que ESNI/Cancer/Materno/Zoonosis): UNA sola consulta que
 * trae todas las filas HIS de los codigos de interes con los filtros comunes
 * aplicados, y el matching de cada fila del reporte se resuelve en PHP con el
 * DSL (que espeja los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function ntEjecutarReporte(PDO $pdo, array $filtros): array {
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
        if (ntTieneColumnaMesInt($pdo)) {
            $where[] = "Mes_Int = :mes";
        } else {
            $where[] = "CAST(TRIM(Mes) AS UNSIGNED) = :mes";
        }
        $params[':mes'] = intval($filtros['mes']);
    }
    $whereEst = ntWhereEstablecimientos($pdo, (string)($filtros['establecimiento'] ?? ''), $params);
    if ($whereEst !== '') {
        $where[] = $whereEst;
    }

    // ---- 2) Codigos de interes del paquete No Transmisibles ----
    $whereCod = ntWhereCodigos($params);

    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab,
                   Id_Correlativo_Lab, Ficha_Familiar, Codigo_Unico,
                   Id_Ups, Fg_Tipo, Perimetro_Abdominal, Anio, Mes
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    $pdoStream = ntAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = ntFila($r);
            if ($f['cod'] === '') continue;
            $filas[] = $f;
            if (count($filas) >= $MAX_FILAS) {
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

    return ntEjecutarDesdeFilas($pdo, $filas, $t0);
}

/**
 * Ejecuta el pipeline de matching/agregacion del reporte a partir de las
 * filas HIS YA normalizadas (ntFila). Separada de ntEjecutarReporte para
 * poder validar el pipeline sin base de datos.
 */
function ntEjecutarDesdeFilas(PDO $pdo, array $filas, float $t0 = 0.0): array {
    if ($t0 === 0.0) $t0 = microtime(true);

    // Indices por cita (por cita/cod/codF) y buckets globales por codigo
    $porCita = [];
    $porCitaCod = [];
    $porCitaCodF = [];
    $porCod = [];
    $porCodF = [];
    foreach ($filas as $idx => $f) {
        if ($f['cita'] !== '') {
            $porCita[$f['cita']][] = $idx;
            if ($f['cod'] !== '') $porCitaCod[$f['cita']][$f['cod']][] = $idx;
            if ($f['codF'] !== '') $porCitaCodF[$f['cita']][$f['codF']][] = $idx;
        }
        if ($f['cod'] !== '') $porCod[$f['cod']][] = $idx;
        if ($f['codF'] !== '') $porCodF[$f['codF']][] = $idx;
    }
    $ctx = [
        'filas' => $filas, 'porCita' => $porCita,
        'porCitaCod' => $porCitaCod, 'porCitaCodF' => $porCitaCodF,
        'porCod' => $porCod, 'porCodF' => $porCodF,
        'renaesPorCategoria' => ntRenaesPorCategoria($pdo),
    ];

    // ---- Matching por seccion ----
    $seccionesOut = [];
    $totPersonas = 0;

    foreach (ntSecciones() as $sec) {
        $conSexo = $sec['layout'] !== 'SES';
        $ctxSec = $ctx + [
            'conGedad' => $conSexo, // SES: sin gedad ni sexo
            'gedadTipo' => $sec['gedadTipo'],
        ];

        $secOut = $sec;
        $totalSec = 0.0;
        $filasOut = [];

        foreach ($sec['filas'] as $fr) {
            $r = ntContar($fr['cond'] ?? null, $sec['regla'], $ctxSec, $conSexo);
            $tot = array_sum($r['gedades']);
            $filasOut[] = [
                'key' => $fr['key'], 'niv1' => $fr['niv1'], 'niv2' => $fr['niv2'] ?? null,
                'niv3' => $fr['niv3'] ?? null, 'unidad' => $fr['unidad'] ?? null,
                'fila' => $fr['fila'] ?? null,
                'valores' => $r['gedades'], 'sexo' => $r['sexo'], 'part' => $r['part'],
                'total' => $tot, 'cond' => $fr['cond'] ?? null,
            ];
            $totalSec += $tot;
        }

        $secOut['filas'] = $filasOut;
        $secOut['total'] = $totalSec;
        $totPersonas += $totalSec;
        $seccionesOut[] = $secOut;
    }

    return [
        'secciones' => $seccionesOut,
        'totales'   => [
            'total_casos' => $totPersonas,
            'secciones_con_datos' => count(array_filter($seccionesOut, fn($s) => ($s['total'] ?? 0) > 0)),
            'grupos' => [
                'VALORACION' => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'VALORACION'))),
                'HTA'        => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'HTA'))),
                'DM'         => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'DM'))),
                'TM'         => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'TM'))),
            ],
        ],
        'version'          => NT_DATA_VERSION,
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}
