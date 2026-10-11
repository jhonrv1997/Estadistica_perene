<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ITS/VIH - Motor de reporte data-driven (includes/its_data.php)
 *
 * ADAPTACION FIEL del archivo "03 Creacion de Procedimientos.txt"
 * (15 procedimientos usp_TRAMA_BASE_ITS_VIH_2022_*) sobre la tabla consolidada
 * MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO. Reemplaza:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales" (DimItsVih2022_*:
 *      Etapa, Etapa07, Etapa08, Trimestre07_2, Grupo08, Brigada10,
 *      Condicion01/09_1/09_2 y Diagnostico01..12)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *      (TRAMA_BASE_ITS_VIH_2022_*_CONSOLIDADO / _NOMINAL)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos" (15 SP)
 *   4) Excel:      refrescar la conexion ODBC de "Reporte_Actividades_ITS.xlsx"
 *
 * SECCIONES (15 procedimientos -> 16 bloques del Excel, hoja "Plantilla"):
 *   01 usp_..._01_POBLACION_GENERAL       filas 13-25  (13 dx x DIAG/TRAT/CONT,
 *                                                      F/M x 5 etapas + 6 totales)
 *   02 usp_..._02_HEPATITIS_B              filas 29-36  (F/M x 5 etapas + F/M/Total)
 *   02A usp_..._02A_HEPATITIS_C            filas 40-45  (igual que 02)
 *   03 usp_..._03_TAMIZAJE_VIH             filas 49-57  (igual que 02)
 *   04 usp_..._04_EXPOSICION_HEPATITIS_B   filas 61-69  (igual que 02)
 *   05 usp_..._05_..._VIOLENCIA            filas 73-77  (igual que 02)
 *   06 usp_..._06_PVVIH_ATENCION_INTEGRAL  filas 81-92  (igual que 02)
 *   07_1 usp_..._07_1_GESTANTES_RN         filas 95-105 (4 etapas 12-17..60+ + Total)
 *   07_2 usp_..._07_2_GESTANTES_RN         filas 108-118 (1ER/2DO/3ER TRIM + Total)
 *   07_3 usp_..._07_3_GESTANTES_RN         filas 121-130 (solo Total, RN)
 *   08 usp_..._08_POBLACION_CLAVE          filas 134-145 (5 grupos x 3 etapas
 *                                                      18-29/30-59/60+ + 5 totales)
 *   09_1 usp_..._09_1_POBLACION_CLAVE_ITS  filas 149-159 (grupos x etapas =
 *                                                      DIAGNOSTICOS + 5 col TRATADOS)
 *   09_2 usp_..._09_2_POBLACION_CLAVE_ITS  filas 162-172 (CONTACTOS /
 *                                                      CONTACTOS TRATADOS)
 *   10 usp_..._10_..._BRIGADAS_MCC         filas 177-184 (OFERTA COMUNITARIA +
 *                                                      OFERTA MOVIL; 5 grupos x
 *                                                      4 etapas 12-17..60+)
 *   11 usp_..._11_..._BRIGADAS_BMA         filas 188-195 (F/M x 5 etapas + F/M)
 *   12 usp_..._12_TELEMEDICINA             filas 199-203 (F/M x 5 etapas + F/M)
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita         -> Id_Cita
 *   id_tipitem      -> Tipo_Diagnostico      ('D','P','R','C')
 *   cod_item        -> Codigo_Item
 *   cod_item_f      -> (sin columna) prefijo CIE de 3 letras si el codigo
 *                      empieza con letra; si no se conserva completo.
 *   valor_lab       -> Valor_Lab             ('NULL' en el DSL = IS NULL)
 *   I_ROWNUM_LAB    -> Id_Correlativo_Lab
 *   id_genero       -> Id_Genero             ('F','M')
 *   edad_reg        -> Edad_Reg
 *   id_tipedad_reg  -> Tipo_Edad             ('D','M','A')
 *   renaes          -> Codigo_Unico
 *   id_persona      -> Id_Paciente
 *   id_etnia        -> Id_Etnia              (56-60 = poblacion indigena, SP 02/03)
 *   nom_lote        -> Lote                  ('VHM','BMU','MCC','BMA': brigadas)
 *   id_tipcond_serv -> Id_Condicion_Servicio ('N' nueva / 'R' reprogramada)
 *   rownum          -> (sin columna) numero de fila de la cita en la trama:
 *                      en los SP 10/11 se usa rownum=1 para contar CADA CITA
 *                      una sola vez -> se replica con la regla 'citas'
 *                      (count distinct Id_Cita).
 *   periodo         -> Anio + Mes (filtros web del reporte)
 *
 * GRUPOS ETAREOS (CASE Etapa del T-SQL):
 *   'e5' (01,02,02A,03,04,05,06,11,12): D/M o A 1-11 -> 1 (01d-11a);
 *        A 12-17 -> 2; 18-29 -> 3; 30-59 -> 4; 60+ -> 5.
 *   'e4' (07_1, 07_2, 10): A 12-17 -> 1; 18-29 -> 2; 30-59 -> 3; 60+ -> 4.
 *   'e3' (08, 09_1, 09_2):  A 18-29 -> 1; 30-59 -> 2; 60+ -> 3.
 *
 * GRUPO POBLACION CLAVE (CASE Grupo del T-SQL, secciones 08/09/10):
 *   HSH + M -> 1 (HSH); TRA + M -> 2 (TRANS); TS + F -> 3 (TS-FEMENINO);
 *   TTS + M -> 4 (TS-TRANS); HTS + M -> 5 (TS-H). Se determina por la fila
 *   de la cita con valor_lab en (TS,HSH,TTS,TRA,HTS) (marcador #PC/#ATENCION).
 * BRIGADA (seccion 10, Dim Brigada10): nom_lote BMU -> 1 OFERTA MOVIL,
 *   MCC -> 2 OFERTA COMUNITARIA (el Excel muestra COMUNITARIA primero).
 * TRIMESTRE (07_2): (Z359/Z349 + vl 1|2|3) o (Z3491/Z3591 + R1) -> 1|2|3, etc.
 *
 * REGLAS DE CONTEO (columna "Casos" de cada consolidado):
 *   'filas' : count(*) del T-SQL (cada fila HIS que cumple).
 *   'citas' : los SP 10/11 cuentan "atendidos" y "atenciones" sobre la fila
 *             rownum=1 de cada cita -> count(distinct Id_Cita).
 *
 * NOTAS DE ADAPTACION (desviaciones documentadas):
 *   - aniomes>='202210'/'202301' del T-SQL se convierte en los filtros web
 *     Anio/Mes (el reporte web ya acota el periodo).
 *   - El Grupo de poblacion clave se determina por cualquier fila de la cita
 *     con valor_lab en (TS,HSH,TTS,TRA,HTS); el SP lo hace por tabla temporal
 *     especifica (#TEMP/#ATENCION/#DX_VIH) - la diferencia es despreciable.
 *   - SP 07_3 dx8/dx10: el CASE exige id_tipedad_reg='D' (la rama M+edad=1 del
 *     OR interno nunca aplica por el AND externo) - se replica D + edad>=2.
 *   - SP 01 dx12: union de Temporal1 (con exclusion #POBLACION_CLAVE y R1) y
 *     Temporal2 (sin exclusion y sin R1) -> se simplifica al union fiel.
 *   - Sexo: los SP exigen "sexo is not null" y agrupan M/F; las filas con sexo
 *     distinto de M/F quedan fuera de las secciones con eje sexo.
 */

require_once __DIR__ . '/../config.php';

define('ITS_DATA_VERSION', '2026-10-12-r1');

/** Version del motor de reporte de ITS/VIH (badge del reporte). */
function itsDataVersion(): string {
    return ITS_DATA_VERSION;
}

/* ============================================================
 * 1) NORMALIZACION DE FILAS HIS
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 *
 * Textos en MAYUSCULAS para replicar la colacion case-insensitive de MySQL
 * en el matching PHP (igual que nontransmisibles_data.php).
 */
function itsFila(array $r): array {
    $cod = $r['Codigo_Item'] !== null ? strtoupper(trim((string)$r['Codigo_Item'])) : '';
    $codF = ($cod !== '' && ctype_alpha($cod[0])) ? substr($cod, 0, 3) : $cod;
    $etnia = isset($r['Id_Etnia']) ? trim((string)$r['Id_Etnia']) : '';
    $etnia = $etnia !== '' ? str_pad($etnia, 2, '0', STR_PAD_LEFT) : '';
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
        'etnia'   => $etnia,
        'lote'    => isset($r['Lote']) && $r['Lote'] !== null ? strtoupper(trim((string)$r['Lote'])) : '',
        'tipcond' => isset($r['Id_Condicion_Servicio']) && $r['Id_Condicion_Servicio'] !== null
                        ? strtoupper(trim((string)$r['Id_Condicion_Servicio'])) : '',
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
 *   'cod'         => 'A509' | ['A540','A541']           Codigo_Item exacto
 *   'codF'        => 'A51' | ['A51','A52','A53']        cod_item_f (familia CIE)
 *   'tip'         => 'D' | ['D','R']                    Tipo_Diagnostico
 *   'vl'          => 'NULL' | 'TA' | ['IA','1','4']     Valor_Lab (NULL = IS NULL)
 *   'vlNum'       => ['bt',2,11] | ['ge',45]            rango numerico valor_lab
 *   'rownum'      => 1 | 2                              Id_Correlativo_Lab
 *   'sexo'        => 'F' | 'M'                          Id_Genero
 *   'tipEdad'     => 'D' | ['D','M']                    Tipo_Edad en lista
 *   'edad'        => 1 | [2, null]                      Edad_Reg igual / en rango
 *   'edadA'       => [12, null]                         Tipo_Edad='A' y Edad_Reg
 *                                                    en rango [min, max]
 *   'etniaIn'     => ['56'..'60']                       Id_Etnia IN
 *   'etniaNo'     => ['56'..'60']                       Id_Etnia NOT IN
 *   'lote'        => ['BMU','MCC'] | 'BMA'              Lote IN (nom_lote)
 *   'loteNo'      => ['VHM','BMU','MCC','BMA']          Lote NOT IN
 *   'tipcond'     => ['N','R']                          Id_Condicion_Servicio IN
 *   'citaTiene'   => <predicado>                        EXISTS sobre la cita
 *   'citaTieneTodo' => [<pred>, ...]                    AND de EXISTS
 *   'citaSin'     => <predicado>                        NOT EXISTS sobre la cita
 *   'citaSinTodo' => [<pred>, ...]                      AND de NOT EXISTS
 *   'cualquieraDe'  => [<pred>, ...]                    OR de predicados (fila)
 */
function itsCumple(array $f, array $cond, array $ctx): bool {
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
            // 'NULL' dentro del array permite valor_lab IS NULL (SP 07_3 dx7)
            $admiteNull = in_array('NULL', $vals, true);
            $vals = array_values(array_filter($vals, fn($v) => $v !== 'NULL'));
            $vals = array_map(fn($v) => strtoupper((string)$v), $vals);
            if ($f['vl'] === null) {
                if (!$admiteNull) return false;
            } elseif (!in_array($f['vl'], $vals, true)) {
                return false;
            }
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
    if (isset($cond['tipEdad'])) {
        $tes = is_array($cond['tipEdad']) ? $cond['tipEdad'] : [$cond['tipEdad']];
        $tes = array_map(fn($t) => strtoupper((string)$t), $tes);
        if (!in_array($f['tipEdad'], $tes, true)) return false;
    }
    if (isset($cond['edad'])) {
        if ($f['edad'] === null) return false;
        if (is_array($cond['edad'])) {
            [$min, $max] = $cond['edad'];
            if ($min !== null && $f['edad'] < $min) return false;
            if ($max !== null && $f['edad'] > $max) return false;
        } elseif ($f['edad'] !== (int)$cond['edad']) {
            return false;
        }
    }
    if (isset($cond['edadA'])) {
        [$min, $max] = $cond['edadA'];
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return false;
        if ($min !== null && $f['edad'] < $min) return false;
        if ($max !== null && $f['edad'] > $max) return false;
    }
    if (isset($cond['etniaIn'])) {
        $ets = array_map(fn($e) => str_pad(strtoupper(trim((string)$e)), 2, '0', STR_PAD_LEFT),
                         (array)$cond['etniaIn']);
        if (!in_array($f['etnia'], $ets, true)) return false;
    }
    if (isset($cond['etniaNo'])) {
        $ets = array_map(fn($e) => str_pad(strtoupper(trim((string)$e)), 2, '0', STR_PAD_LEFT),
                         (array)$cond['etniaNo']);
        if (in_array($f['etnia'], $ets, true)) return false;
    }
    if (isset($cond['lote'])) {
        $ls = array_map(fn($l) => strtoupper((string)$l), (array)$cond['lote']);
        if (!in_array($f['lote'], $ls, true)) return false;
    }
    if (isset($cond['loteNo'])) {
        $ls = array_map(fn($l) => strtoupper((string)$l), (array)$cond['loteNo']);
        if (in_array($f['lote'], $ls, true)) return false;
    }
    if (isset($cond['tipcond'])) {
        $tcs = array_map(fn($t) => strtoupper((string)$t), (array)$cond['tipcond']);
        if (!in_array($f['tipcond'], $tcs, true)) return false;
    }
    if (isset($cond['citaTiene'])) {
        if (!itsCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!itsCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['citaSin'])) {
        if (itsCitaTiene($ctx, $f['cita'], $cond['citaSin'])) return false;
    }
    if (isset($cond['citaSinTodo'])) {
        foreach ($cond['citaSinTodo'] as $sub) {
            if (itsCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (itsCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** EXISTS sobre la cita: alguna fila de la cita cumple el predicado (por indice). */
function itsCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    $cands = itsCandidatosCita($ctx, $cita, $cond);
    foreach ($cands as $idx) {
        if (itsCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Indices candidatos de la cita para un predicado (sobre-aproximacion por
 * buckets de codigo; la verificacion exacta la hace itsCumple).
 */
function itsCandidatosCita(array $ctx, string $cita, array $cond): array {
    $idxCita = $ctx['porCita'][$cita] ?? [];
    $conCod = isset($cond['cod']) || isset($cond['codF']);
    if (!$conCod) return $idxCita; // sin filtro de codigo: todas las filas de la cita
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
 * Genera una descripcion SQL-legible de una condicion del DSL (para el panel
 * "Ver condiciones SQL": auditoria contra el archivo 03).
 */
function itsCondicionSQL(array $cond, int $nivel = 0): string {
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
            case 'tipEdad':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_tipedad_reg IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'edad':
                if (is_array($v)) $partes[] = "edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 'NULL');
                else $partes[] = "edad_reg = " . (int)$v;
                break;
            case 'edadA':
                $partes[] = "id_tipedad_reg = 'A' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 'NULL');
                break;
            case 'etniaIn':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_etnia IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'etniaNo':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_etnia NOT IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'lote':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "nom_lote IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'loteNo':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "nom_lote NOT IN (" . implode(', ', array_map(fn($x) => "'{$x}'", $lista)) . ")";
                break;
            case 'tipcond':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_tipcond_serv IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'citaTiene':
                $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . itsCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaTieneTodo':
                $subs = array_map(fn($sub) => "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . itsCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' AND ', $subs) . ")";
                break;
            case 'citaSin':
                $partes[] = "id_cita NOT IN (SELECT id_cita FROM #TEMP WHERE " . itsCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaSinTodo':
                $subs = array_map(fn($sub) => "id_cita NOT IN (SELECT id_cita FROM #TEMP WHERE " . itsCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' AND ', $subs) . ")";
                break;
            case 'cualquieraDe':
                $subs = array_map(fn($sub) => "(" . itsCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' OR ', $subs) . ")";
                break;
        }
    }
    $tab = str_repeat('    ', $nivel);
    return $tab . implode("\n{$tab}AND ", $partes);
}

/* ============================================================
 * 3) DIMENSIONES (Etapa / Grupo / Brigada / Trimestre)
 * ============================================================ */

/**
 * Grupo etareo segun el CASE Etapa del T-SQL.
 * $tipo: 'e5' | 'e4' | 'e3'. Null si la fila no mapea (el T-SQL la excluye).
 */
function itsEtapa(array $f, string $tipo): ?int {
    if ($tipo === 'e3') {
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
        if ($f['edad'] >= 18 && $f['edad'] <= 29) return 1;
        if ($f['edad'] >= 30 && $f['edad'] <= 59) return 2;
        if ($f['edad'] >= 60) return 3;
        return null;
    }
    if ($tipo === 'e4') {
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
        if ($f['edad'] >= 12 && $f['edad'] <= 17) return 1;
        if ($f['edad'] >= 18 && $f['edad'] <= 29) return 2;
        if ($f['edad'] >= 30 && $f['edad'] <= 59) return 3;
        if ($f['edad'] >= 60) return 4;
        return null;
    }
    // e5
    if ($f['tipEdad'] === 'D' || $f['tipEdad'] === 'M') return 1;
    if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
    if ($f['edad'] <= 11) return 1;   // A 0-11 (el CASE agrupa 1-11; 0 cae fuera
    if ($f['edad'] <= 17) return 2;   // por el between, se documenta la salvedad)
    if ($f['edad'] <= 29) return 3;
    if ($f['edad'] <= 59) return 4;
    return 5;
}

/**
 * Grupo de poblacion clave de la cita (CASE Grupo del T-SQL): 1 HSH,
 * 2 TRANS, 3 TS-FEMENINO, 4 TS-TRANS, 5 TS-H. Se determina buscando la fila
 * de la cita con valor_lab marcador (prioridad del CASE: HSH, TRA, TS, TTS,
 * HTS) combinada con el sexo de la fila contada.
 */
function itsGrupoDeCita(array $ctx, array $f): ?int {
    if ($f['cita'] === '' || !isset($ctx['porCita'][$f['cita']])) return null;
    $sexo = $f['sexo'];
    $marcas = [
        'HSH' => ['M', 1],
        'TRA' => ['M', 2],
        'TS'  => ['F', 3],
        'TTS' => ['M', 4],
        'HTS' => ['M', 5],
    ];
    foreach ($marcas as $vl => [$sx, $g]) {
        if (isset($ctx['porCitaVl'][$f['cita']][$vl])) {
            if ($sexo === $sx) return $g;
        }
    }
    return null;
}

/** Brigada de la fila (Dim Brigada10): BMU -> 1 OFERTA MOVIL, MCC -> 2 OFERTA COMUNITARIA. */
function itsBrigadaDeFila(array $f): ?int {
    if ($f['lote'] === 'BMU') return 1;
    if ($f['lote'] === 'MCC') return 2;
    return null;
}

/**
 * Trimestre gestacional (CASE Trimestre del T-SQL 07_2) evaluado en la fila
 * Z de control prenatal: (Z359/Z349 + valor_lab 1|2|3) o (Z349n/Z359n + R1).
 */
function itsTrimestreDeFila(array $f): ?int {
    if ($f['cod'] === 'Z359' || $f['cod'] === 'Z349') {
        if ($f['vl'] === '1') return 1;
        if ($f['vl'] === '2') return 2;
        if ($f['vl'] === '3') return 3;
        return null;
    }
    if ($f['cod'] === 'Z3491' || $f['cod'] === 'Z3591') return $f['rownum'] === 1 ? 1 : null;
    if ($f['cod'] === 'Z3492' || $f['cod'] === 'Z3592') return $f['rownum'] === 1 ? 2 : null;
    if ($f['cod'] === 'Z3493' || $f['cod'] === 'Z3593') return $f['rownum'] === 1 ? 3 : null;
    return null;
}

/* ============================================================
 * 4) DEFINICION DE SECCIONES (adaptacion fiel del archivo
 *    "03 Creacion de Procedimientos": 15 SP -> 16 bloques)
 * ============================================================ */

/**
 * Combina varios predicados con AND resolviendo colisiones de claves
 * (el operador + de PHP descartaria las repetidas: $a + $b con la misma
 * clave pierde $b). Las claves de cita (citaTiene/citaTieneTodo y
 * citaSin/citaSinTodo) se agrupan en listas AND; cualquieraDe tambien.
 */
function itsY(array ...$parts): array {
    $out = [];
    $tiene = [];
    $sin = [];
    $cual = [];
    foreach ($parts as $p) {
        if ($p === []) continue;
        foreach ($p as $k => $v) {
            if ($k === 'citaTiene') {
                $tiene[] = $v;                       // cond simple (array asociativo)
            } elseif ($k === 'citaTieneTodo') {
                foreach ($v as $c) $tiene[] = $c;     // lista de conds
            } elseif ($k === 'citaSin') {
                $sin[] = $v;                          // cond simple
            } elseif ($k === 'citaSinTodo') {
                foreach ($v as $c) $sin[] = $c;       // lista de conds
            } elseif ($k === 'cualquieraDe') {
                foreach ($v as $c) $cual[] = $c;      // lista de conds (OR)
            } else {
                $out[$k] = $v;
            }
        }
    }
    if ($tiene) {
        if (count($tiene) === 1) $out['citaTiene'] = $tiene[0];
        else $out['citaTieneTodo'] = $tiene;
    }
    if ($sin) {
        if (count($sin) === 1) $out['citaSin'] = $sin[0];
        else $out['citaSinTodo'] = $sin;
    }
    if ($cual) {
        $out['cualquieraDe'] = $cual;
    }
    return $out;
}

/** Etiquetas de las etapas por tipo de CASE del T-SQL. */
function itsEtapaLabels(string $tipo): array {
    if ($tipo === 'e3') return [1 => '18a-29a', 2 => '30a-59a', 3 => '60a+'];
    if ($tipo === 'e4') return [1 => '12a-17a', 2 => '18a-29a', 3 => '30a-59a', 4 => '60a+'];
    return [1 => '01d-11a', 2 => '12a-17a', 3 => '18a-29a', 4 => '30a-59a', 5 => '60a+'];
}

/** Etiquetas del grupo de poblacion clave (DimItsVih2022_Grupo08). */
function itsGrupoLabels(): array {
    return [1 => 'HSH', 2 => 'TRANS', 3 => 'TS-FEMENINO', 4 => 'TS-TRANS', 5 => 'TS-H'];
}

/**
 * Devuelve la definicion completa de las 16 secciones del reporte ITS
 * (hoja "Plantilla" del Excel "Reporte_Actividades_ITS.xlsx").
 *
 * Cada seccion replica un bloque del Excel y cada fila replica las lineas de
 * su procedimiento usp_TRAMA_BASE_ITS_VIH_2022_* (mismo numero y orden).
 * Campos por seccion:
 *   'codigo' / 'procedimiento' / 'titulo' / 'grupo' (macro-grupo web)
 *   'layout'   => 'FMC3'|'FM5'|'FM5T'|'GES4'|'TRI3'|'RN1'|'PC5'|'PC5C'|'CC2'|'BRIG10'
 *   'etapas'   => 'e5'|'e4'|'e3'   (CASE Etapa del T-SQL)
 *   'ejes'     => ejes de agregacion, p.ej. ['cond','sexo','etapa']
 *   'regla'    => 'filas' (count(*)) | 'citas' (count distinct id_cita)
 *   'filas'    => cada fila: ['dx','label','conds' => [n => predicado] |
 *                 'cond' => predicado, 'fila' => fila Excel del export]
 */
function itsSecciones(): array {

    // ============ Listas de codigos (segun archivo 03) ============
    $PC5   = ['TS', 'HTS', 'TTS', 'HSH', 'TRA'];       // marcadores poblacion clave
    $PC4   = ['TS', 'HSH', 'TTS', 'TRA'];              // 01/02/03
    $zGest = ['Z359', 'Z349', 'Z3491', 'Z3492', 'Z3493', 'Z3591', 'Z3592', 'Z3593'];
    $sif4  = ['86780.01', '86592', '86593', '86780'];
    $vih4  = ['86703.01', '86703.02', '87389', '86703'];
    $vih5  = ['86703.01', '86703.02', '87389', '86703', '86318.01'];
    $hepB  = ['87342', '87340', '82397', '86706', '86704', '86705', '87351', '86707'];
    $hepC  = ['86803', '86803.01'];
    $gono  = ['A540', 'A541', 'A542', 'A546', 'A549', 'O982'];
    $sifiD = ['A528', 'A529', 'A530', 'A539'];
    $etio  = ['A509', 'A549', 'A562', 'A590', 'A599', 'O981'];
    $itsD  = ['A64X6', 'A64X4', 'A64X1', 'A64X5', 'A64X7', 'A630',
              'A540', 'A541', 'A542', 'A546', 'A549', 'A528', 'A529', 'A530', 'A539'];
    $etnInd = ['56', '57', '58', '59', '60'];
    $brigLotes = ['VHM', 'BMU', 'MCC', 'BMA'];

    // ============ Predicados base reutilizables ============
    $pcSin    = ['citaSin'    => ['vl' => $PC4]];                       // NOT #POBLACION_CLAVE (01/02)
    $pcSin5   = ['citaSin'    => ['vl' => $PC5]];                       // NOT #POBLACION_CLAVE (03/09)
    $tratTA   = ['citaTiene'  => ['cod' => '99199.11', 'vl' => 'TA']];  // #TRATADOS (01/09)
    $contacto = ['citaTiene'  => ['cod' => 'Z202']];                    // #CONTACTOS
    $consej   = ['citaTiene'  => ['cod' => '99402.05', 'tip' => 'D', 'rownum' => 1]]; // #CONSEJ_ITS
    $consejG  = ['citaTiene'  => ['cod' => ['99402.05', '99401.33'], 'vl' => '1']];   // #CONSEJERIA (07)
    $sifilis  = ['citaTiene'  => ['cualquieraDe' => [
                    ['cod' => $sif4, 'rownum' => 1],
                    ['cod' => '86318.01', 'rownum' => 2]]]];            // #SIFILIS (07)
    $sifRP    = ['citaTiene'  => ['cualquieraDe' => [
                    ['cod' => $sif4, 'rownum' => 1, 'vl' => 'RP'],
                    ['cod' => '86318.01', 'rownum' => 2, 'vl' => 'RP']]]];
    $vihGest  = ['citaTiene'  => ['cod' => $vih5, 'tip' => 'D', 'rownum' => 1]];      // #VIH (07)
    $vihRP    = ['citaTiene'  => ['cod' => $vih5, 'tip' => 'D', 'rownum' => 1, 'vl' => 'RP']];
    $hepGest  = ['citaTiene'  => ['cod' => $hepB, 'tip' => 'D']];                     // #HEPATITIS (07)
    $hepRP    = ['citaTiene'  => ['cod' => $hepB, 'tip' => 'D', 'vl' => 'RP']];
    $gestBase = ['citaTiene'  => ['cod' => $zGest, 'tip' => 'D', 'rownum' => 1, 'sexo' => 'F']]; // base gestantes
    $tar991   = ['citaTiene'  => ['cod' => '99199.11', 'vl' => 'TAR']];  // #LAB_TAR / TAR marker

    /* ------------------------------------------------------------
     * SECCION 1 - usp_TRAMA_BASE_ITS_VIH_2022_01_POBLACION_GENERAL
     * 13 diagnosticos x Condicion01 (1=DIAGNOSTICO, 2=TRATADO,
     * 3=CONTACTO). Filas Excel 13-25. Layout FMC3.
     * ------------------------------------------------------------ */
    $dxS = [
        2  => ['cod' => 'A64X6', 'sexo' => 'M'],
        3  => ['cod' => 'A64X9', 'vl' => 'ITS', 'sexo' => 'F'],
        4  => ['cod' => 'A64X4'],
        5  => ['cod' => 'A64X1'],
        6  => ['cod' => 'A64X5', 'sexo' => 'F'],
        7  => ['cod' => 'A630'],
        9  => ['cod' => $gono],
        10 => ['cualquieraDe' => [ ['cod' => $sifiD], ['codF' => 'A51'] ]],
        11 => ['cod' => array_merge($sif4, ['86318.01'])],
    ];
    $dxSindBase = ['cualquieraDe' => [
        ['cod' => ['A64X4', 'A64X1', 'A64X5', 'A630'], 'sexo' => 'F', 'rownum' => 1],
        ['cod' => ['A64X6', 'A64X4', 'A64X1', 'A630'], 'sexo' => 'M', 'rownum' => 1],
        ['cod' => 'A64X9', 'vl' => 'ITS', 'rownum' => 1],
    ]];
    $dxEtioBase = ['cualquieraDe' => [ ['cod' => $etio], ['codF' => ['A51', 'A52', 'A53']] ]];

    $f1 = [];
    $labels1 = [
        1  => 'PERSONAS CON MANEJO SINDROMICO',
        2  => 'CASOS DE DESCARGA URETRAL',
        3  => 'CASOS DE FLUJO VAGINAL COMPATIBLE CON ITS',
        4  => 'CASOS DE ULCERA GENITAL',
        5  => 'CASOS DE BUBON INGUINAL',
        6  => 'CASOS CON SINDROME DE DOLOR ABDOMINAL BAJO',
        7  => 'CASOS DE CONDILOMA',
        8  => 'PERSONAS CON MANEJO ETIOLOGICO',
        9  => 'CASOS DE GONORREA',
        10 => 'CASOS DE SIFILIS',
        11 => 'PERSONAS TAMIZADAS PARA SIFILIS',
        12 => 'PERSONAS TAMIZADAS REACTIVAS PARA SIFILIS',
        13 => 'PERSONAS QUE RECIBEN CONSEJERIA EN ITS',
    ];
    foreach ($labels1 as $dx => $lbl) {
        $conds = [];
        if ($dx === 1) {
            $conds[1] = itsY($dxSindBase, ['tip' => 'D', 'rownum' => 1], $consej, $pcSin);
            $conds[2] = itsY($dxSindBase, ['tip' => 'D', 'rownum' => 1], $tratTA, $consej, $pcSin);
            $conds[3] = itsY($dxSindBase, ['tip' => 'D', 'rownum' => 1], $contacto, $consej, $pcSin);
        } elseif ($dx === 8) {
            $conds[1] = itsY($dxEtioBase, ['tip' => 'D', 'rownum' => 1], $consej, $pcSin);
            $conds[2] = itsY($dxEtioBase, ['tip' => 'D', 'rownum' => 1], $tratTA, $consej, $pcSin);
            $conds[3] = itsY($dxEtioBase, ['tip' => 'D', 'rownum' => 1], $contacto, $consej, $pcSin);
        } elseif ($dx === 12) {
            $conds[1] = ['cualquieraDe' => [
                ['cod' => $sif4, 'tip' => 'D', 'vl' => 'RP'],
                ['cod' => '86318.01', 'tip' => 'D', 'vl' => 'RP', 'rownum' => 2] + $pcSin,
            ]];
        } elseif ($dx === 13) {
            $conds[1] = ['cod' => '99402.05', 'tip' => 'D', 'rownum' => 1, 'tipEdad' => 'A', 'edadA' => [12, null]];
        } elseif (isset($dxS[$dx])) {
            $base = $dxS[$dx];
            $conds[1] = $base + ['tip' => 'D', 'rownum' => 1] + $pcSin;
            $conds[2] = $base + ['tip' => 'D', 'rownum' => 1] + $tratTA + $pcSin;
            if ($dx <= 7) {
                // CONTACTO: Temporal5 (case sin tip, sin sexo, sin valor_lab)
                $conds[3] = ['cod' => $base['cod'], 'rownum' => 1] + $contacto + $pcSin;
            } elseif ($dx === 9 || $dx === 10) {
                // CONTACTO: Temporal7 (dx 9/10 requieren CONSEJ_ITS)
                $base3c = ($dx === 9 ? ['cod' => $gono] : ['cualquieraDe' => [ ['cod' => $sifiD], ['codF' => 'A51'] ]]);
                $conds[3] = itsY($base3c, ['rownum' => 1], $contacto, $consej, $pcSin);
            }
        }
        $f1[] = ['dx' => "D{$dx}", 'label' => $lbl, 'conds' => $conds, 'fila' => 12 + $dx];
    }

    /* ------------------------------------------------------------
     * SECCION 2 - usp_..._02_HEPATITIS_B (filas 29-36)
     * ------------------------------------------------------------ */
    $noGest = ['citaSin' => ['cualquieraDe' => [
        ['cod' => ['Z3491', 'Z3492', 'Z3493', 'Z3591', 'Z3592', 'Z3593', 'Z359'], 'rownum' => 1],
        ['vl' => 'G'],
    ]]];
    $tratHepB = ['citaTiene' => ['cod' => '99199.11', 'vl' => ['IA', '1', '4']]];
    $f2 = [
        ['dx' => 'D1', 'label' => 'PERSONAS TAMIZADAS PARA HEPATITIS B',
         'cond' => itsY(['cod' => $hepB, 'tip' => 'D', 'rownum' => 1, 'etniaIn' => $etnInd], $noGest, $pcSin), 'fila' => 29],
        ['dx' => 'D2', 'label' => 'PERSONAS TAMIZADAS REACTIVAS A HEPATITIS B',
         'cond' => itsY(['cod' => $hepB, 'vl' => 'RP', 'etniaIn' => $etnInd], $noGest, $pcSin), 'fila' => 30],
        ['dx' => 'D3', 'label' => 'PERSONAS CON DIAGNOSTICO DE HEPATITIS B CRONICA',
         'cond' => itsY(['cod' => ['B180', 'B181'], 'tip' => 'D', 'rownum' => 1, 'etniaIn' => $etnInd], $noGest, $pcSin), 'fila' => 31],
        ['dx' => 'D4', 'label' => 'PERSONAS DE POBLACION INDIGENA TAMIZADAS PARA HEPATITIS B',
         'cond' => itsY(['cod' => $hepB, 'tip' => 'D', 'rownum' => 1, 'etniaNo' => $etnInd], $noGest, $pcSin), 'fila' => 32],
        ['dx' => 'D5', 'label' => 'PERSONAS DE POBLACION INDIGENA TAMIZADAS REACTIVAS A HEPATITIS B',
         'cond' => itsY(['cod' => $hepB, 'vl' => 'RP', 'etniaNo' => $etnInd], $noGest, $pcSin), 'fila' => 33],
        ['dx' => 'D6', 'label' => 'PERSONAS DE POBLACION INDIGENA CON DIAGNOSTICO DE HEPATITIS B CRONICA',
         'cond' => itsY(['cod' => ['B180', 'B181'], 'tip' => 'D', 'rownum' => 1, 'etniaNo' => $etnInd], $noGest, $pcSin), 'fila' => 34],
        ['dx' => 'D7', 'label' => 'TOTAL DE PERSONAS CON DIAGNOSTICO DE HEPATITIS B - INICIAN TRATAMIENTO (Incluye todas las poblaciones)',
         'cond' => ['cod' => ['B180', 'B181'], 'tip' => ['D', 'R'], 'rownum' => 1,
                    'citaTiene' => ['cod' => '99199.11', 'vl' => ['IA', '1']]], 'fila' => 35],
        ['dx' => 'D8', 'label' => 'TOTAL DE PERSONAS CON DIAGNOSTICO DE HEPATITIS B - CON TRATAMIENTO ANUAL COMPLETO (Incluye todas las poblaciones)',
         'cond' => ['cod' => ['B180', 'B181'], 'tip' => 'R', 'rownum' => 1, 'citaTiene' => ['cod' => '99199.11', 'vl' => '4']], 'fila' => 36],
    ];

    /* ------------------------------------------------------------
     * SECCION 2A - usp_..._02A_HEPATITIS_C (filas 40-45)
     * ------------------------------------------------------------ */
    $f2A = [
        ['dx' => 'D1', 'label' => 'PERSONAS TAMIZADAS PARA HEPATITIS C',
         'cond' => ['cod' => $hepC, 'tip' => 'D', 'rownum' => 1], 'fila' => 40],
        ['dx' => 'D2', 'label' => 'PERSONAS TAMIZADAS REACTIVAS A HEPATITIS C',
         'cond' => ['cod' => $hepC, 'vl' => 'RP'], 'fila' => 41],
        ['dx' => 'D3', 'label' => 'PERSONAS CON DIAGNOSTICO DE HEPATITIS C CRONICA',
         'cond' => ['cod' => 'B182', 'tip' => 'D', 'rownum' => 1], 'fila' => 42],
        ['dx' => 'D4', 'label' => 'TOTAL DE PERSONAS CON DIAGNOSTICO DE HEPATITIS C - INICIAN TRATAMIENTO',
         'cond' => ['cod' => 'B182', 'rownum' => 1, 'citaTiene' => ['cod' => '99199.11', 'vl' => 'IA']], 'fila' => 43],
        ['dx' => 'D5', 'label' => 'TOTAL DE PERSONAS CON DIAGNOSTICO DE HEPATITIS C - CON TRATAMIENTO COMPLETO',
         'cond' => ['cod' => 'B182', 'rownum' => 1, 'tip' => 'R', 'citaTiene' => ['cod' => '99199.11', 'vl' => 'TA']], 'fila' => 44],
        ['dx' => 'D6', 'label' => 'TOTAL DE PERSONAS CON DIAGNOSTICO DE HEPATITIS C CURADAS',
         'cond' => ['cod' => 'B182', 'tip' => 'R', 'vl' => 'PC'], 'fila' => 45],
    ];

    /* ------------------------------------------------------------
     * SECCION 3 - usp_..._03_TAMIZAJE_VIH (filas 49-57)
     * ------------------------------------------------------------ */
    $tbc = ['citaTiene' => ['vl' => 'TBC']];
    $ppl = ['citaTiene' => ['vl' => 'PPL']];
    $base3 = ['cod' => $vih5, 'tip' => 'D', 'rownum' => 1];
    $f3 = [
        ['dx' => 'D1', 'label' => 'PERSONAS TAMIZADAS PARA VIH (EXCEPTO GESTANTES, POBLACION CLAVE, TBC E INDIGENA)',
         'cond' => $base3 + ['etniaIn' => $etnInd] + $pcSin5 + ['citaSinTodo' => [$tbc]], 'fila' => 49],
        ['dx' => 'D2', 'label' => 'PERSONAS TAMIZADAS PARA VIH CON RESULTADO REACTIVO (EXCEPTO GESTANTES, POBLACION CLAVE, TBC E INDIGENA)',
         'cond' => $base3 + ['vl' => 'RP', 'etniaIn' => $etnInd] + $pcSin5 + ['citaSinTodo' => [$tbc]], 'fila' => 50],
        ['dx' => 'D3', 'label' => 'PERSONAS CON TB TAMIZADAS PARA VIH',
         'cond' => $base3 + $pcSin5 + $tbc, 'fila' => 51],
        ['dx' => 'D4', 'label' => 'PERSONAS CON TB TAMIZADAS REACTIVAS PARA VIH',
         'cond' => $base3 + ['vl' => 'RP'] + $pcSin5 + $tbc, 'fila' => 52],
        ['dx' => 'D5', 'label' => 'PERSONAS DE POBLACION INDIGENA TAMIZADAS PARA VIH',
         'cond' => $base3 + ['etniaNo' => $etnInd] + $pcSin5 + ['citaSinTodo' => [$tbc]], 'fila' => 53],
        ['dx' => 'D6', 'label' => 'PERSONAS DE POBLACION INDIGENA TAMIZADAS PARA VIH CON RESULTADO REACTIVO',
         'cond' => $base3 + ['vl' => 'RP', 'etniaNo' => $etnInd] + $pcSin5 + ['citaSinTodo' => [$tbc]], 'fila' => 54],
        ['dx' => 'D7', 'label' => 'PERSONAS PRIVADAS DE LIBERTAD TAMIZADAS PARA VIH',
         'cond' => $base3 + $ppl + $pcSin5, 'fila' => 55],
        ['dx' => 'D8', 'label' => 'PERSONAS PRIVADAS DE LIBERTAD TAMIZADAS REACTIVAS PARA VIH',
         'cond' => $base3 + ['vl' => 'RP'] + $ppl + $pcSin5, 'fila' => 56],
        ['dx' => 'D9', 'label' => 'PERSONAS CON DIAGNOSTICO DE VIH (Incluye todas las poblaciones)',
         'cond' => ['cod' => ['Z21X1', 'B24X'], 'tip' => 'D', 'rownum' => 1], 'fila' => 57],
    ];

    /* ------------------------------------------------------------
     * SECCION 4 - usp_..._04_EXPOSICION_HEPATITIS_B (filas 61-69)
     * ------------------------------------------------------------ */
    $profTAR = ['citaTiene' => ['cod' => '99199.11', 'vl' => 'TAR']];   // #PROFILAXIS (04/05)
    $expoVIH = ['citaTiene' => ['cod' => ['B24X', 'Z21X1'], 'rownum' => 1]]; // #EXPOSICION B24X/Z21X1
    $expoITS = ['citaTiene' => ['cualquieraDe' => [
        ['cod' => $itsD, 'rownum' => 1],
        ['codF' => 'A51', 'rownum' => 1],
    ]]];                                                                // #EXPOSICION ITS
    $vacNOV  = ['citaTiene' => ['cod' => '90371', 'vl' => 'NOV']];      // #VACUNA
    $f4 = [
        ['dx' => 'D1', 'label' => 'PERSONAS CON EXPOSICION OCUPACIONAL AL VIH',
         'cond' => ['cod' => 'Z5781', 'tip' => 'D', 'vl' => 'VIH', 'tipEdad' => 'A', 'edadA' => [18, null]], 'fila' => 61],
        ['dx' => 'D2', 'label' => 'PERSONAS CON EXPOSICION OCUPACIONAL AL VIH QUE RECIBEN PROFILAXIS ARV',
         'cond' => ['cod' => 'Z5781', 'tip' => 'D', 'vl' => 'VIH', 'tipEdad' => 'A', 'edadA' => [18, null]] + $profTAR, 'fila' => 62],
        ['dx' => 'D3', 'label' => 'PERSONAS EXPUESTAS A VIH POR EXPOSICION NO OCUPACIONAL',
         'cond' => ['cod' => 'Z206', 'tip' => 'D', 'vl' => ['VSX', 'RSA', 'PAD', 'OTR']], 'fila' => 63],
        ['dx' => 'D4', 'label' => 'PERSONAS EXPUESTAS A VIH POR EXPOSICION NO OCUPACIONAL Y RECIBEN PROFILAXIS ARV',
         'cond' => ['cod' => 'Z206', 'tip' => 'D', 'vl' => ['VSX', 'RSA', 'PAD', 'OTR']] + $profTAR, 'fila' => 64],
        ['dx' => 'D5', 'label' => 'PERSONAS CON DIAGNOSTICO DE VIH POR EXPOSICION OCUPACIONAL',
         'cond' => ['cod' => 'Z5781', 'tip' => 'D', 'vl' => 'VIH', 'tipEdad' => 'A', 'edadA' => [18, null]] + $expoVIH, 'fila' => 65],
        ['dx' => 'D6', 'label' => 'PERSONAS CON DIAGNOSTICO DE ITS POR EXPOSICION NO OCUPACIONAL',
         'cond' => ['cod' => 'Z5781', 'tip' => 'D', 'vl' => 'VIH', 'tipEdad' => 'A', 'edadA' => [18, null]] + $expoITS
                 + ['citaSin' => ['cod' => ['B24X', 'Z21X1'], 'rownum' => 1]], 'fila' => 66],
        ['dx' => 'D7', 'label' => 'PERSONAS CON DIAGNOSTICO DE VIH POR EXPOSICION NO OCUPACIONAL',
         'cond' => ['cod' => 'Z206', 'tip' => 'D', 'vl' => 'VSX'] + $expoVIH, 'fila' => 67],
        ['dx' => 'D8', 'label' => 'PERSONAS CON EXPOSICION OCUPACIONAL A HEPATITIS B',
         'cond' => ['cod' => 'Z5781', 'tip' => 'D', 'vl' => 'HB'], 'fila' => 68],
        ['dx' => 'D9', 'label' => 'PERSONAS CON EXPOSICION OCUPACIONAL A HEPATITIS B SIN VACUNA PREVIA',
         'cond' => ['cod' => 'Z5781', 'tip' => 'D', 'vl' => 'HB'] + $vacNOV, 'fila' => 69],
    ];

    /* ------------------------------------------------------------
     * SECCION 5 - usp_..._05_..._VIOLENCIA (filas 73-77)
     * ------------------------------------------------------------ */
    $prof55 = ['citaTiene' => ['cod' => ['99199.11', '90371']]];        // #PROFILAXIS (05)
    $expo55 = ['citaTiene' => ['cod' => ['B24X', 'Z21X1'], 'tip' => 'D']]; // #EXPOSICION (B24X/Z21X1 D)
    $f5 = [
        ['dx' => 'D1', 'label' => 'PERSONAS CON EXPOSICION AL VIH POR VIOLENCIA SEXUAL QUE RECIBEN PROFILAXIS ARV',
         'cond' => ['cod' => 'Z206', 'tip' => 'D', 'vl' => 'VSX'] + ['citaTiene' => ['cod' => '99199.11', 'vl' => 'TAR']], 'fila' => 73],
        ['dx' => 'D2', 'label' => 'PERSONAS CON EXPOSICION A ITS Y HEPATITIS B POR VIOLENCIA SEXUAL QUE RECIBEN PROFILAXIS',
         'cond' => ['cod' => 'Z205', 'tip' => 'D', 'vl' => 'VSX'] + $prof55, 'fila' => 74],
        ['dx' => 'D3', 'label' => 'PERSONAS CON EXPOSICION AL VIH POR VIOLENCIA SEXUAL CON DIAGNOSTICO DE ITS (Incluye Hepatitis B)',
         'cond' => ['cod' => 'Z205', 'tip' => 'D', 'vl' => 'VSX'] + $expoITS, 'fila' => 75],
        ['dx' => 'D4', 'label' => 'PERSONAS CON EXPOSICION AL VIH POR VIOLENCIA SEXUAL CON DIAGNOSTICO DE VIH',
         'cond' => ['cod' => 'Z206', 'tip' => 'D', 'vl' => 'VSX'] + $expo55, 'fila' => 76],
        ['dx' => 'D5', 'label' => 'PERSONAS CON EXPOSICION A VIH POR VIOLENCIA SEXUAL CON SEGUIMIENTO COMPLETO',
         'cond' => ['cod' => 'Z206', 'tip' => 'D', 'vl' => 'VSX'] + ['citaTiene' => ['cod' => '99199.11', 'vl' => 'TA']], 'fila' => 77],
    ];

    /* ------------------------------------------------------------
     * SECCION 6 - usp_..._06_PVVIH_ATENCION_INTEGRAL (filas 81-92)
     * ------------------------------------------------------------ */
    $trat06 = ['citaTiene' => ['cualquieraDe' => [
        ['cod' => ['99199.11', '99199.12'], 'vl' => ['IA', 'TA']],
        ['cod' => '99199.13', 'vl' => 'IA'],
    ]]];                                                               // #TRATAMIENTO (06)
    $diag06 = ['citaTiene' => ['cod' => array_merge($hepB, ['B180', 'B181', 'B182'], $hepC), 'rownum' => 1]]; // #DIAGNOSTICOS
    $vac06  = ['citaTiene' => ['cod' => ['90744', '90746', '90670'], 'vl' => ['3', 'D3', 'B3']]];            // #VACUNA
    $pvv = ['cod' => ['Z21X1', 'B24X'], 'rownum' => 1];
    $f6 = [
        ['dx' => 'D1', 'label' => 'PVV QUE INICIAN TERAPIA PREVENTIVA CON COTRIMOXAZOL (TPC)',
         'cond' => $pvv + ['citaTiene' => ['cod' => '99199.13']], 'fila' => 81],
        ['dx' => 'D2', 'label' => 'PVV QUE INICIAN TERAPIA PREVENTIVA PARA TUBERCULOSIS (TPTB)',
         'cond' => $pvv + ['citaTiene' => ['cod' => '99199.12', 'vl' => 'IA']], 'fila' => 82],
        ['dx' => 'D3', 'label' => 'PVV QUE COMPLETAN TERAPIA PREVENTIVA TUBERCULOSIS (TPTB)',
         'cond' => $pvv + ['citaTiene' => ['cod' => '99199.12', 'vl' => 'TA']], 'fila' => 83],
        ['dx' => 'D4', 'label' => 'N° DE PVV CON DIAGNOSTICO DE TB',
         'cond' => ['cod' => 'B200', 'rownum' => 1, 'tip' => 'D'], 'fila' => 84],
        ['dx' => 'D5', 'label' => 'N° DE PVV CON TAMIZAJE PARA HEPATITIS B',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => $hepB]], 'fila' => 85],
        ['dx' => 'D6', 'label' => 'N° DE PVV CON HEPATITIS B CRONICA',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => ['B180', 'B181']]], 'fila' => 86],
        ['dx' => 'D7', 'label' => 'N° DE PVV QUE COMPLETAN VACUNACION DE HEPATITIS B',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => ['90744', '90746'], 'vl' => ['3', 'D3', 'B3']]], 'fila' => 87],
        ['dx' => 'D8', 'label' => 'N° DE PVV QUE COMPLETAN VACUNACION DE NEUMOCOCO',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => '90670', 'vl' => ['3', 'D3', 'B3']]], 'fila' => 88],
        ['dx' => 'D9', 'label' => 'N° DE PVV CON TAMIZAJE PARA HEPATITIS C',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => $hepC]], 'fila' => 89],
        ['dx' => 'D10', 'label' => 'N° DE PVV CON HEPATITIS C',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => 'B182']], 'fila' => 90],
        ['dx' => 'D11', 'label' => 'N° DE PVV CON HEPATITIS C QUE INICIAN TRATAMIENTO',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => '99199.11', 'vl' => 'IA'],
                'citaTieneTodo' => [['cod' => 'B182', 'rownum' => 1]]], 'fila' => 91],
        ['dx' => 'D12', 'label' => 'N° DE PVV CON HEPATITIS C QUE COMPLETAN TRATAMIENTO',
         'cond' => $pvv + ['tip' => ['D', 'R'], 'citaTiene' => ['cod' => '99199.11', 'vl' => 'TA'],
                'citaTieneTodo' => [['cod' => 'B182', 'rownum' => 1]]], 'fila' => 92],
    ];

    /* ------------------------------------------------------------
     * SECCION 7a - usp_..._07_1_GESTANTES_RN POR GRUPO ETAREO (filas 95-105)
     * ------------------------------------------------------------ */
    $zF = ['cod' => $zGest, 'rownum' => 1, 'sexo' => 'F'];
    $consej05  = ['citaTiene' => ['cod' => '99402.05', 'vl' => '1']];   // #CONSEJERIA 99402.05
    $consej133 = ['citaTiene' => ['cod' => '99401.33', 'vl' => '1']];   // #CONSEJERIA 99401.33
    $trat07    = ['citaTiene' => ['cod' => '99199.11', 'vl' => 'TA']];  // #TRATADOS TA
    $gest91    = ['citaTiene' => ['cod' => ['99199.11', '99199.64'], 'tip' => 'D', 'rownum' => 1]]; // #GESTANTES pos 1
    $gestPAD3  = ['citaTiene' => ['vl' => 'PAD', 'rownum' => 3]];       // #GESTANTES PAD@3
    $labels7 = [
        1  => 'GESTANTES TAMIZADAS PARA SIFILIS (1ER TAMIZAJE)',
        2  => 'GESTANTES TAMIZADAS REACTIVAS PARA SIFILIS',
        3  => 'GESTANTES CON SIFILIS QUE RECIBEN TRATAMIENTO COMPLETO',
        4  => 'GESTANTES TAMIZADAS PARA VIH (1ER TAMIZAJE)',
        5  => 'GESTANTES TAMIZADAS REACTIVAS PARA VIH',
        6  => 'GESTANTES CON VIH QUE RECIBEN TAR',
        7  => 'GESTANTES TAMIZADAS PARA HEPATITIS B',
        8  => 'GESTANTES TAMIZADAS REACTIVAS PARA HEPATITIS B',
        9  => 'GESTANTES CON HEPATITIS B QUE RECIBEN TRATAMIENTO',
        10 => 'GESTANTE SERODISCORDANTES PARA VIH',
        11 => 'GESTANTE SERODISCORDANTES PARA VIH CON PROFILAXIS PRE-EXPOSICION',
    ];
    $f7a = [];
    foreach ($labels7 as $dx => $lbl) {
        $cond = null;
        switch ($dx) {
            case 1:  $cond = itsY($zF, ['tip' => 'D'], $sifilis, $consej05); break;
            case 2:  $cond = itsY($zF, $sifRP); break;
            case 3:  $cond = itsY(['cod' => 'O981', 'tip' => ['R', 'D'], 'rownum' => 1, 'sexo' => 'F'], $trat07); break;
            case 4:  $cond = itsY($zF, $vihGest, $consej133); break;
            case 5:  $cond = $zF + $vihRP; break;
            case 6:  $cond = itsY(['cod' => 'O987', 'rownum' => 1, 'sexo' => 'F'], $gestBase, $tar991); break;
            case 7:  $cond = itsY($zF, $hepGest, $consej05); break;
            case 8:  $cond = itsY($zF, $hepRP); break;
            case 9:  $cond = itsY(['cod' => 'O984', 'rownum' => 1, 'sexo' => 'F'], $gest91); break;
            case 10: $cond = itsY($zF, $gestPAD3); break;
            case 11: $cond = $zF + ['cualquieraDe' => [
                        ['citaTieneTodo' => [
                            ['cod' => '99199.11', 'vl' => 'TAR', 'rownum' => 1],
                            ['cod' => ['99199.11', '99199.64'], 'vl' => 'PRE', 'rownum' => 2],
                            ['vl' => 'PAD', 'rownum' => 3],
                        ]],
                        ['citaTiene' => ['cod' => '99199.64', 'rownum' => 1]],
                     ]]; break;
        }
        $f7a[] = ['dx' => "D{$dx}", 'label' => $lbl, 'cond' => $cond, 'fila' => 94 + $dx];
    }

    /* ------------------------------------------------------------
     * SECCION 7b - usp_..._07_2_GESTANTES_RN POR TRIMESTRE (filas 108-118)
     * ------------------------------------------------------------ */
    $o984Base = ['citaTiene' => ['cod' => 'O984', 'rownum' => 1, 'sexo' => 'F']];
    $labTarO987 = ['citaTieneTodo' => [
        ['cod' => 'O987', 'tip' => ['R', 'D'], 'rownum' => 1],
        ['cod' => '99199.11', 'vl' => 'TAR'],
    ]];
    $f7b = [];
    foreach ($labels7 as $dx => $lbl) {
        $cond = null;
        switch ($dx) {
            case 1:  $cond = itsY($zF, ['tip' => 'D'], $sifilis, $consej05); break;
            case 2:  $cond = itsY($zF, $sifRP); break;
            case 3:  $cond = itsY($zF, ['tip' => 'D'], $trat07); break;
            case 4:  $cond = itsY($zF, $vihGest, $consej133); break;
            case 5:  $cond = itsY($zF, $vihRP); break;
            case 6:  $cond = itsY($zF, ['tip' => 'D'], $labTarO987); break;
            case 7:  $cond = itsY($zF, $hepGest, $consej05); break;
            case 8:  $cond = itsY($zF, $hepRP); break;
            case 9:  $cond = itsY($zF, $o984Base, $gest91); break;
            case 10: $cond = itsY($zF, $o984Base, $gestPAD3); break;
            case 11: $cond = $zF + $o984Base + ['cualquieraDe' => [
                        ['citaTieneTodo' => [
                            ['cod' => '99199.11', 'vl' => 'TAR', 'rownum' => 1],
                            ['cod' => ['99199.11', '99199.64'], 'vl' => 'PRE', 'rownum' => 2],
                            ['vl' => 'PAD', 'rownum' => 3],
                        ]],
                        ['citaTiene' => ['cod' => '99199.64', 'rownum' => 1]],
                     ]]; break;
        }
        $f7b[] = ['dx' => "D{$dx}", 'label' => $lbl, 'cond' => $cond, 'fila' => 107 + $dx];
    }

    /* ------------------------------------------------------------
     * SECCION 7A - usp_..._07_3_GESTANTES_RN RECIEN NACIDOS (filas 121-130)
     * ------------------------------------------------------------ */
    $rnDM   = ['cualquieraDe' => [ ['tipEdad' => 'D'], ['tipEdad' => 'M', 'edad' => 1] ]];
    $rnDMA1 = ['cualquieraDe' => [ ['tipEdad' => 'D'], ['tipEdad' => 'M', 'edad' => 1], ['tipEdad' => 'A', 'edad' => 1] ]];
    $trat73 = ['citaTiene' => ['cualquieraDe' => [
        ['cod' => '99199.11', 'vl' => 'TA'],
        ['cod' => '99199.14', 'vl' => 'IA'],
    ]]];                                                               // #TRATAMIENTO (07_3)
    $vac73  = ['citaTiene' => ['cod' => ['90744', '90371'], 'rownum' => 1]]; // #VACUNA
    $f7A = [
        ['dx' => 'D1', 'label' => 'RN CON SIFILIS CONGENITA QUE RECIBEN TRATAMIENTO COMPLETO',
         'cond' => ['cod' => 'A509', 'rownum' => 1] + $rnDM + ['citaTiene' => ['cod' => '99199.11', 'vl' => 'TA']], 'fila' => 121],
        ['dx' => 'D2', 'label' => 'RN EXPUESTOS A VIH QUE RECIBEN ARV',
         'cond' => ['cod' => 'Z2061', 'tip' => ['R', 'D'], 'rownum' => 1] + $rnDM
                 + ['citaTiene' => ['cod' => '99199.14', 'vl' => 'IA']], 'fila' => 122],
        ['dx' => 'D3', 'label' => 'RN EXPUESTOS A VIH QUE INICIAN SUCEDANEOS DE LECHE MATERNA',
         'cond' => ['cod' => '99199.15', 'vl' => 'IA'] + $rnDM, 'fila' => 123],
        ['dx' => 'D4', 'label' => 'RN EXPUESTOS A VIH QUE RECIBEN SUCEDANEOS DE LECHE MATERNA',
         'cond' => ['cod' => '99199.15', 'vlNum' => ['bt', 2, 11]] + $rnDMA1, 'fila' => 124],
        ['dx' => 'D5', 'label' => 'RN EXPUESTOS A VIH QUE COMPLETAN SUCEDANEOS DE LECHE MATERNA',
         'cond' => ['cod' => '99199.15', 'vl' => 'TA', 'tipEdad' => 'A', 'edad' => 1], 'fila' => 125],
        ['dx' => 'D6', 'label' => 'RN EXPUESTOS A HEPATITIS B',
         'cond' => ['cod' => 'Z205', 'rownum' => 1] + $rnDM, 'fila' => 126],
        ['dx' => 'D7', 'label' => 'RN EXPUESTOS CON VACUNA DE HEPATITIS B EN ATENCION DE PARTO ANTES DE LAS 24HRS',
         'cond' => ['cod' => 'Z205', 'rownum' => 1, 'tipEdad' => 'D', 'edad' => 1]
                 + ['citaTiene' => ['cod' => '90744', 'vl' => ['NULL', 'DU']]], 'fila' => 127],
        ['dx' => 'D8', 'label' => 'RN EXPUESTOS CON VACUNA DE HEPATITIS B EN ATENCION DE PARTO DESPUES DE LAS 24HRS',
         'cond' => ['cod' => 'Z205', 'rownum' => 1, 'tipEdad' => 'D', 'edad' => [2, null]]
                 + ['citaTiene' => ['cod' => '90744', 'vl' => ['D1', '1']]], 'fila' => 128],
        ['dx' => 'D9', 'label' => 'RN EXPUESTOS QUE RECIBEN INMUNOGLOBULINA (HBIG) ANTES DE LAS 24 HRS',
         'cond' => ['cod' => 'Z205', 'rownum' => 1, 'tipEdad' => 'D', 'edad' => 1]
                 + ['citaTiene' => ['cod' => '90371']], 'fila' => 129],
        ['dx' => 'D10', 'label' => 'RN EXPUESTOS QUE RECIBEN INMUNOGLOBULINA (HBIG) DESPUES DE LAS 24 HRS',
         'cond' => ['cod' => 'Z205', 'rownum' => 1, 'tipEdad' => 'D', 'edad' => [2, null]]
                 + ['citaTiene' => ['cod' => '90371']], 'fila' => 130],
    ];

    /* ------------------------------------------------------------
     * SECCION 8 - usp_..._08_POBLACION_CLAVE (filas 134-145)
     * Exclusion: nom_lote NOT IN (VHM,BMU,MCC,BMA) en las tablas base.
     * Ejes: Grupo (5) x Etapa e3 (18-29, 30-59, 60+) + total por grupo.
     * ------------------------------------------------------------ */
    $loteNoBrig = ['loteNo' => $brigLotes];
    $pc08 = ['citaTiene' => ['cod' => '99402.05', 'vl' => $PC5]];      // #SIFILIS/#VIH/#HEPATITIS
    $pc08v = ['citaTiene' => ['cod' => ['99402.05', '99401.33'], 'vl' => $PC5]]; // #VIH
    $atencion = ['citaTiene' => ['cod' => '99351', 'rownum' => 2, 'vl' => $PC5]]; // #ATENCION
    $dxVih08 = ['citaTiene' => ['cod' => '99402.16', 'vl' => $PC5]];   // #DX_VIH
    $tar08 = ['citaTieneTodo' => [
        ['cod' => '99199.11', 'vl' => 'IA', 'rownum' => 1],
        ['cod' => '99199.11', 'vl' => 'TAR', 'rownum' => 2],
    ]];                                                                // #TAR
    $a18 = ['tipEdad' => 'A', 'edadA' => [18, null]];
    $f8 = [
        ['dx' => 'D1', 'label' => 'ATENDIDOS/AS HSH/TRANS', 'regla' => 'filas',
         'cond' => ['cod' => '99351', 'vl' => '1'] + $a18 + $atencion
                 + ['citaTiene' => ['tipcond' => ['N', 'R']]], 'fila' => 134],
        ['dx' => 'D2', 'label' => 'ATENCIONES A HSH/TRANS',
         'cond' => ['cod' => '99351', 'rownum' => 1] + $a18 + $atencion, 'fila' => 135],
        ['dx' => 'D3', 'label' => 'HSH/TRANS TAMIZADOS/AS PARA SIFILIS (1° TAMIZAJE)',
         'cond' => ['cualquieraDe' => [
             ['cod' => $sif4, 'tip' => 'D', 'rownum' => 1],
             ['cod' => '86318.01', 'tip' => 'D', 'rownum' => 2],
         ]] + $a18 + $loteNoBrig + $pc08, 'fila' => 136],
        ['dx' => 'D4', 'label' => 'HSH/TRANS TAMIZADOS/AS PARA SIFILIS CON RESULTADO REACTIVO',
         'cond' => ['cualquieraDe' => [
             ['cod' => $sif4, 'tip' => 'D', 'vl' => 'RP', 'rownum' => 1],
             ['cod' => '86318.01', 'tip' => 'D', 'vl' => 'RP', 'rownum' => 2],
         ]] + $a18 + $loteNoBrig + $pc08, 'fila' => 137],
        ['dx' => 'D5', 'label' => 'TS/HSH/TRANS TAMIZADOS/AS PARA VIH (1º TAMIZAJE)',
         'cond' => ['cod' => $vih5, 'tip' => 'D', 'rownum' => 1] + $a18 + $loteNoBrig + $pc08v, 'fila' => 138],
        ['dx' => 'D6', 'label' => 'TS/HSH/TRANS CON TAMIZAJE PARA VIH CON RESULTADO REACTIVO',
         'cond' => ['cod' => $vih5, 'tip' => 'D', 'rownum' => 1, 'vl' => 'RP'] + $a18 + $loteNoBrig + $pc08v, 'fila' => 139],
        ['dx' => 'D7', 'label' => 'TS/HSH/TRANS CON DIAGNOSTICO DE VIH',
         'cond' => ['cod' => ['B24X', 'Z21X1'], 'tip' => 'D', 'rownum' => 1] + $a18 + $dxVih08, 'fila' => 140],
        ['dx' => 'D8', 'label' => 'TS/HSH/TRANS QUE INICIAN TERAPIA ANTIRRETROVIRAL',
         'cond' => ['cod' => ['B24X', 'Z21X1'], 'rownum' => 1] + $a18 + $dxVih08 + $tar08, 'fila' => 141],
        ['dx' => 'D9', 'label' => 'TS/HSH/TRANS TAMIZADOS/AS PARA HEPATITIS B',
         'cond' => ['cod' => $hepB, 'tip' => 'D', 'rownum' => 1] + $a18 + $loteNoBrig + $pc08, 'fila' => 142],
        ['dx' => 'D10', 'label' => 'TS/HSH/TRANS CON TAMIZAJE PARA HEPATITIS B CON RESULTADO REACTIVO',
         'cond' => ['cod' => $hepB, 'tip' => 'D', 'rownum' => 1, 'vl' => 'RP'] + $a18 + $loteNoBrig + $pc08, 'fila' => 143],
        ['dx' => 'D11', 'label' => 'TS/HSH/TRANS QUE COMPLETAN VACUNACION DE HEPATITIS B',
         'cond' => ['cod' => '90744', 'vl' => ['3', 'D3', 'B3']] + $a18 + $loteNoBrig
                 + ['citaTiene' => ['vl' => $PC5]], 'fila' => 144],
        ['dx' => 'D12', 'label' => 'TS/HSH/TRANS TAMIZADOS/AS PARA HEPATITIS C',
         'cond' => ['cod' => array_merge($hepC, []), 'tip' => 'D', 'rownum' => 1] + $a18 + $loteNoBrig + $pc08, 'fila' => 145],
    ];

    /* ------------------------------------------------------------
     * SECCION 9a - usp_..._09_1_POBLACION_CLAVE_ITS (filas 149-159)
     * Conds: 1=DIAGNOSTICOS (grupos x etapas, cols B-P),
     *        2=TRATADOS (grupos, cols Q-U). Ejes: cond x grupo x etapa e3.
     * ------------------------------------------------------------ */
    $pc09 = ['citaTiene' => ['cod' => '99402.05', 'tip' => 'D', 'vl' => $PC5]]; // #POBLACION_CLAVE
    $dxS9 = [
        1  => ['cualquieraDe' => [
            ['cod' => ['A64X6', 'A64X4', 'A64X1', 'A630', 'A64X7'], 'sexo' => 'M'],
            ['cod' => 'A64X9', 'vl' => 'ITS', 'sexo' => 'F'],
        ]],
        2  => ['cod' => 'A64X6', 'sexo' => 'M'],
        3  => ['cod' => 'A64X9', 'vl' => 'ITS', 'sexo' => 'F'],
        4  => ['cod' => 'A64X4'],
        5  => ['cod' => 'A64X1'],
        6  => ['cod' => 'A64X5', 'sexo' => 'F'],
        7  => ['cod' => 'A630'],
        8  => ['cod' => 'A64X7', 'sexo' => 'M'],
        9  => ['cualquieraDe' => [ ['cod' => $etio], ['codF' => ['A51', 'A52', 'A53']] ]],
        10 => ['cod' => ['A540', 'A541', 'A542', 'A546', 'A549']],
        11 => ['cualquieraDe' => [ ['cod' => $sifiD], ['codF' => 'A51'] ]],
    ];
    $labels9 = [
        1  => 'PERSONAS CON MANEJO SINDROMICO',
        2  => 'CASOS DE DESCARGA URETRAL',
        3  => 'CASOS DE FLUJO VAGINAL COMPATIBLE CON ITS',
        4  => 'CASOS DE ULCERA GENITAL',
        5  => 'CASOS DE BUBON INGUINAL',
        6  => 'CASOS DE SINDROME DE DOLOR ABDOMINAL BAJO',
        7  => 'CASOS DE CONDILOMA',
        8  => 'CASOS DE PROCTITIS',
        9  => 'PERSONAS CON MANEJO ETIOLOGICO',
        10 => 'CASOS DE GONORREA',
        11 => 'CASOS DE SIFILIS',
    ];
    $f9a = [];
    foreach ($dxS9 as $dx => $base) {
        $baseT = $base + ['tip' => 'D', 'rownum' => 1];
        $f9a[] = ['dx' => "D{$dx}", 'label' => $labels9[$dx],
            'conds' => [
                1 => itsY($baseT, $pc09),
                2 => itsY($baseT, $pc09, $tratTA),
            ],
            'fila' => 148 + $dx];
    }

    /* ------------------------------------------------------------
     * SECCION 9b - usp_..._09_2_POBLACION_CLAVE_ITS CONTACTOS (filas 162-172)
     * Conds: 1=CONTACTOS (col B), 2=CONTACTOS TRATADOS (col C).
     * ------------------------------------------------------------ */
    $f9b = [];
    foreach ($dxS9 as $dx => $base) {
        $baseT = $base + ['tip' => 'D', 'rownum' => 1];
        $f9b[] = ['dx' => "D{$dx}", 'label' => $labels9[$dx],
            'conds' => [
                1 => itsY($baseT, $pc09, $contacto),
                2 => itsY($baseT, $pc09, $contacto, $tratTA),
            ],
            'fila' => 161 + $dx];
    }

    /* ------------------------------------------------------------
     * SECCION 10 - usp_..._10_..._BRIGADAS_MCC (filas 177-184)
     * Ejes: brigada (BMU=1 MOVIL, MCC=2 COMUNITARIA) x grupo x etapa e4.
     * dx1/dx2 cuentan citas (rownum=1 del SP).
     * ------------------------------------------------------------ */
    $loteMCC = ['lote' => ['BMU', 'MCC']];
    $tam10 = ['citaTiene' => ['vl' => $PC5]];                          // #TAMIZAJE/#ATENCION
    $f10 = [
        ['dx' => 'D1', 'label' => 'Nº HSH/TRANS ATENDIDOS/AS', 'regla' => 'citas',
         'cond' => ['vl' => $PC5] + $loteMCC + ['citaTiene' => ['tipcond' => ['N', 'R']]], 'fila' => 177],
        ['dx' => 'D2', 'label' => 'Nº DE ATENCIONES A HSH/TRANS', 'regla' => 'citas',
         'cond' => ['vl' => $PC5] + $loteMCC, 'fila' => 178],
        ['dx' => 'D3', 'label' => 'Nº DE HSH/TRANS TAMIZADOS/AS PARA VIH (1º TAMIZAJE)',
         'cond' => ['cualquieraDe' => [
             ['cod' => $vih4, 'tip' => 'D', 'rownum' => 1],
             ['cod' => '86318.01', 'rownum' => 1],
         ]] + $loteMCC + $tam10, 'fila' => 179],
        ['dx' => 'D4', 'label' => 'Nº HSH/TRANS CON TAMIZAJE PARA VIH CON RESULTADO REACTIVO',
         'cond' => ['cod' => $vih5, 'vl' => 'RP', 'rownum' => 1] + $loteMCC + $tam10, 'fila' => 180],
        ['dx' => 'D5', 'label' => 'Nº DE HSH/TRANS TAMIZADOS/AS PARA SIFILIS (1º TAMIZAJE)',
         'cond' => ['cod' => array_merge($sif4, ['86318.01']), 'tip' => 'D', 'rownum' => 1] + $loteMCC + $tam10, 'fila' => 181],
        ['dx' => 'D6', 'label' => 'Nº HSH/TRANS CON TAMIZAJE PARA SIFILIS CON RESULTADO REACTIVO',
         'cond' => ['cualquieraDe' => [
             ['cod' => $sif4, 'vl' => 'RP', 'rownum' => 1],
             ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 2],
         ]] + $loteMCC + $tam10, 'fila' => 182],
        ['dx' => 'D7', 'label' => 'Nº DE HSH/TRANS TAMIZADOS/AS PARA HEPATITIS (1º TAMIZAJE)',
         'cond' => ['cod' => $hepB, 'tip' => 'D', 'rownum' => 1] + $loteMCC + $tam10, 'fila' => 183],
        ['dx' => 'D8', 'label' => 'Nº HSH/TRANS CON TAMIZAJE PARA HEPATITIS CON RESULTADO REACTIVO',
         'cond' => ['cod' => $hepB, 'vl' => 'RP', 'rownum' => 1] + $loteMCC + $tam10, 'fila' => 184],
    ];

    /* ------------------------------------------------------------
     * SECCION 11 - usp_..._11_..._BRIGADAS_BMA (filas 188-195)
     * Ejes: sexo x etapa e5 (F: B-F, M: G-K, L=F, M=M).
     * dx1/dx2 cuentan citas (rownum=1 del SP).
     * ------------------------------------------------------------ */
    $loteBMA = ['lote' => ['BMA']];
    $f11 = [
        ['dx' => 'D1', 'label' => 'Nº ATENDIDOS/AS', 'regla' => 'citas',
         'cond' => $loteBMA + ['citaTiene' => ['tipcond' => ['N', 'R']]], 'fila' => 188],
        ['dx' => 'D2', 'label' => 'Nº DE ATENCIONES', 'regla' => 'citas',
         'cond' => $loteBMA, 'fila' => 189],
        ['dx' => 'D3', 'label' => 'Nº DE PERSONAS TAMIZADAS PARA VIH (1º TAMIZAJE)',
         'cond' => ['cualquieraDe' => [
             ['cod' => $vih4, 'tip' => 'D', 'rownum' => 1],
             ['cod' => '86318.01', 'rownum' => 1],
         ]] + $loteBMA, 'fila' => 190],
        ['dx' => 'D4', 'label' => 'Nº DE PERSONAS TAMIZADAS PARA VIH CON RESULTADO REACTIVO',
         'cond' => ['cod' => $vih4, 'vl' => 'RP', 'rownum' => 1] + $loteBMA, 'fila' => 191],
        ['dx' => 'D5', 'label' => 'Nº DE PERSONAS TAMIZADAS PARA SIFILIS (1º TAMIZAJE)',
         'cond' => ['cod' => array_merge($sif4, ['86318.01']), 'tip' => 'D', 'rownum' => 1] + $loteBMA, 'fila' => 192],
        ['dx' => 'D6', 'label' => 'Nº DE PERSONAS TAMIZADAS PARA SIFILIS CON RESULTADO REACTIVO',
         'cond' => ['cualquieraDe' => [
             ['cod' => $sif4, 'vl' => 'RP', 'rownum' => 1],
             ['cod' => '86318.01', 'vl' => 'RP', 'rownum' => 2],
         ]] + $loteBMA, 'fila' => 193],
        ['dx' => 'D7', 'label' => 'Nº DE PERSONAS TAMIZADAS PARA HEPATITIS (1º TAMIZAJE)',
         'cond' => ['cod' => $hepB, 'tip' => 'D', 'rownum' => 1] + $loteBMA, 'fila' => 194],
        ['dx' => 'D8', 'label' => 'Nº DE PERSONAS TAMIZADAS PARA HEPATITIS CON RESULTADO REACTIVO',
         'cond' => ['cod' => $hepB, 'vl' => 'RP', 'rownum' => 1] + $loteBMA, 'fila' => 195],
    ];

    /* ------------------------------------------------------------
     * SECCION 12 - usp_..._12_TELEMEDICINA (filas 199-203)
     * ------------------------------------------------------------ */
    $serv12 = ['cod' => ['99402.05', '99402.16', '99351', '99199.11'], 'rownum' => 1];
    $pac12  = ['citaTiene' => ['cod' => '99499.10', 'rownum' => 1]];   // #PACIENTES
    $f12 = [
        ['dx' => 'D1', 'label' => 'NÚMERO DE TELEORIENTACIONES REALIZADOS EN LOS SERVICIOS DE VIH,ITS Y HEPATITIS',
         'cond' => $serv12 + ['citaTiene' => ['cod' => ['99499.08', '99499.09']]], 'fila' => 199],
        ['dx' => 'D2', 'label' => 'NÚMERO DE TELEMONITOREOS REALIZADOS EN LOS SERVICIOS DE VIH, ITS Y HEPATITIS',
         'cond' => $serv12 + ['citaTiene' => ['cod' => '99499.10']], 'fila' => 200],
        ['dx' => 'D3', 'label' => 'NÚMERO DE TELECONSULTAS REALIZADOS EN LOS SERVICIOS DE VIH, ITS Y HEPATITIS',
         'cond' => $serv12 + ['citaTiene' => ['cod' => '99499.01']], 'fila' => 201],
        ['dx' => 'D4', 'label' => 'NÚMERO DE TELEMONITOREOS REALIZADOS A LOS PACIENTES CON VIH',
         'cond' => ['cod' => ['Z21X1', 'B24X'], 'rownum' => 1] + $pac12, 'fila' => 202],
        ['dx' => 'D5', 'label' => 'NÚMERO DE TELEMONITOREOS REALIZADO A LOS PACIENTES CON HEPATITIS B CRÓNICA',
         'cond' => ['cod' => ['B180', 'B181'], 'rownum' => 1] + $pac12, 'fila' => 203],
    ];

    /* ============================================================
     * Ensamblado de las 16 secciones
     * ============================================================ */
    return [

    ['codigo' => 'ITS01_POBLACION_GENERAL', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_01_POBLACION_GENERAL',
     'titulo' => '1.- POBLACIÓN GENERAL: ITS', 'grupo' => 'PG',
     'layout' => 'FMC3', 'etapas' => 'e5', 'ejes' => ['cond', 'sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f1],

    ['codigo' => 'ITS02_HEPATITIS_B', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_02_HEPATITIS_B',
     'titulo' => '2.- HEPATITIS B', 'grupo' => 'PG',
     'layout' => 'FM5', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f2],

    ['codigo' => 'ITS02A_HEPATITIS_C', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_02A_HEPATITIS_C',
     'titulo' => '2A.- HEPATITIS C', 'grupo' => 'PG',
     'layout' => 'FM5', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f2A],

    ['codigo' => 'ITS03_TAMIZAJE_VIH', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_03_TAMIZAJE_VIH',
     'titulo' => '3.- POBLACIÓN GENERAL: TAMIZAJE VIH', 'grupo' => 'PG',
     'layout' => 'FM5', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f3],

    ['codigo' => 'ITS04_EXPOSICION_VIH_HB', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_04_EXPOSICION_HEPATITIS_B',
     'titulo' => '4.- EXPOSICIÓN A VIH / HEPATITIS B', 'grupo' => 'PG',
     'layout' => 'FM5', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f4],

    ['codigo' => 'ITS05_EXPOSICION_VIOLENCIA', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_05_EXPOSICION_HEPATITIS_B_VIOLENCIA',
     'titulo' => '5.- EXPOSICIÓN A VIH / HEPATITIS B (VIOLENCIA SEXUAL)', 'grupo' => 'PG',
     'layout' => 'FM5', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f5],

    ['codigo' => 'ITS06_PVVIH_INTEGRAL', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_06_PVVIH_ATENCION_INTEGRAL',
     'titulo' => '6.- PVV: ATENCIÓN INTEGRAL', 'grupo' => 'PG',
     'layout' => 'FM5', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f6],

    ['codigo' => 'ITS07_1_GESTANTES_ETAREO', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_07_1_GESTANTES_RN',
     'titulo' => '7.- GESTANTES Y RECIÉN NACIDOS: Transmisión Materno Infantil (Sífilis, VIH, Hepatitis B) — por grupo etario',
     'grupo' => 'GES', 'layout' => 'GES4', 'etapas' => 'e4', 'ejes' => ['etapa'], 'regla' => 'filas',
     'filas' => $f7a],

    ['codigo' => 'ITS07_2_GESTANTES_TRIMESTRE', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_07_2_GESTANTES_RN',
     'titulo' => '7.- GESTANTES: Transmisión Materno Infantil (Sífilis, VIH, Hepatitis B) — por trimestre',
     'grupo' => 'GES', 'layout' => 'TRI3', 'etapas' => 'e4', 'ejes' => ['trimestre'], 'regla' => 'filas',
     'filas' => $f7b],

    ['codigo' => 'ITS07_3_RECien_NACIDOS', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_07_3_GESTANTES_RN',
     'titulo' => '7A.- RECIÉN NACIDOS (Sífilis, VIH, Hepatitis B)', 'grupo' => 'GES',
     'layout' => 'RN1', 'etapas' => 'e5', 'ejes' => [], 'regla' => 'filas',
     'filas' => $f7A],

    ['codigo' => 'ITS08_POBLACION_CLAVE', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_08_POBLACION_CLAVE',
     'titulo' => '8.- POBLACIÓN CLAVE: Hombres que tienen Sexo con otros Hombres / Trans / TS', 'grupo' => 'PC',
     'layout' => 'PC5', 'etapas' => 'e3', 'ejes' => ['grupo', 'etapa'], 'regla' => 'filas',
     'filas' => $f8],

    ['codigo' => 'ITS09_1_PC_ITS_DX_TRAT', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_09_1_POBLACION_CLAVE_ITS',
     'titulo' => '9.- POBLACIÓN CLAVE: HSH / Trans / TS : ITS (Diagnósticos y Tratados)', 'grupo' => 'PC',
     'layout' => 'PC5C', 'etapas' => 'e3', 'ejes' => ['cond', 'grupo', 'etapa'], 'regla' => 'filas',
     'filas' => $f9a],

    ['codigo' => 'ITS09_2_PC_ITS_CONTACTOS', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_09_2_POBLACION_CLAVE_ITS',
     'titulo' => '9.- POBLACIÓN CLAVE: HSH / Trans / TS : ITS (Contactos)', 'grupo' => 'PC',
     'layout' => 'CC2', 'etapas' => 'e3', 'ejes' => ['cond', 'grupo'], 'regla' => 'filas',
     'filas' => $f9b],

    ['codigo' => 'ITS10_BRIGADAS_MCC', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_10_POBLACION_CLAVE_BRIGADAS_MCC',
     'titulo' => '10.- POBLACIÓN CLAVE: BRIGADAS MÓVILES/MCC', 'grupo' => 'PC',
     'layout' => 'BRIG10', 'etapas' => 'e4', 'ejes' => ['brigada', 'grupo', 'etapa'], 'regla' => 'filas',
     'filas' => $f10],

    ['codigo' => 'ITS11_BRIGADAS_BMA', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_11_POBLACION_CLAVE_BRIGADAS_BMA',
     'titulo' => '11.- BRIGADAS MÓVILES AMAZÓNICAS - BMA', 'grupo' => 'PC',
     'layout' => 'FM5T', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f11],

    ['codigo' => 'ITS12_TELEMEDICINA', 'procedimiento' => 'usp_TRAMA_BASE_ITS_VIH_2022_12_TELEMEDICINA',
     'titulo' => '12.- TELEMEDICINA', 'grupo' => 'TM',
     'layout' => 'FM5T', 'etapas' => 'e5', 'ejes' => ['sexo', 'etapa'], 'regla' => 'filas',
     'filas' => $f12],
    ];
}

/* ============================================================
 * 5) CODIGOS DE INTERES Y CONSULTA A LA TABLA CONSOLIDADA
 * ============================================================ */

/** Indica si la tabla consolidada tiene la columna generada Mes_Int (cacheada). */
function itsTieneColumnaMesInt(PDO $pdo): bool {
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
 * Codigos de item que intervienen en el reporte ITS segun los 15
 * procedimientos del archivo "03 Creacion de Procedimientos":
 *   'exactos'  -> codigos usados con cod_item = (WHERE directo)
 *   'prefijos' -> familias CIE (cod_item_f) usadas como LIKE 'XXX%'
 */
function itsCodigosInteres(): array {
    return [
        'exactos' => [
            // ---- 01: ITS sindromico / etiologico / tamizaje sifilis ----
            'A64X6', 'A64X9', 'A64X4', 'A64X1', 'A64X5', 'A64X7', 'A630',
            'A509', 'A540', 'A541', 'A542', 'A546', 'A549', 'A562', 'A590', 'A599',
            'A528', 'A529', 'A530', 'A539', 'O981', 'O982',
            '86780.01', '86592', '86593', '86780', '86318.01',
            '99199.11', 'Z202', '99402.05',
            // ---- 02 / 02A: hepatitis B / C ----
            '87342', '87340', '82397', '86706', '86704', '86705', '87351', '86707',
            'B180', 'B181', 'B182', '86803', '86803.01',
            'Z3491', 'Z3492', 'Z3493', 'Z3591', 'Z3592', 'Z3593', 'Z349', 'Z359',
            // ---- 03: tamizaje VIH ----
            '86703', '86703.01', '86703.02', '87389', 'Z21X1', 'B24X', '99401.33',
            // ---- 04 / 05: exposicion / violencia sexual ----
            'Z5781', 'Z206', 'Z205', '90371',
            // ---- 06: PVV atencion integral ----
            '99199.12', '99199.13', '90744', '90746', '90670', 'B200',
            // ---- 07_3: recien nacidos ----
            'Z2061', '99199.14', '99199.15',
            // ---- 07_1 / 07_2: gestantes ----
            'O984', 'O987', '99199.64',
            // ---- 08 / 09 / 10 / 11: poblacion clave / brigadas ----
            '86689', '99402.16', '99351',
            // ---- 12: telemedicina ----
            '99499.08', '99499.09', '99499.10', '99499.01',
        ],
        'prefijos' => [
            'A51', 'A52', 'A53',   // familias CIE sifilis (cod_item_f del T-SQL)
        ],
    ];
}

/** Construye la clausula WHERE de codigos de item a partir de itsCodigosInteres(). */
function itsWhereCodigos(array &$params): string {
    $lista = itsCodigosInteres();
    $partes = [];
    $inPh = [];
    foreach ($lista['exactos'] as $j => $c) {
        $k = ":itscod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "TRIM(Codigo_Item) IN (" . implode(',', $inPh) . ")";
    foreach ($lista['prefijos'] as $i => $pref) {
        $k = ":itspref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "TRIM(Codigo_Item) LIKE $k";
    }
    return "(" . implode(" OR ", $partes) . ")";
}

/** Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES). */
function itsGetEstablecimientosZS(PDO $pdo): array {
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
function itsWhereEstablecimientos(PDO $pdo, string $establecimiento, array &$params): string {
    $establecimiento = trim($establecimiento);
    if ($establecimiento !== '') {
        $params[':itsest'] = $establecimiento;
        return "TRIM(Codigo_Unico) = :itsest";
    }
    $lista = itsGetEstablecimientosZS($pdo);
    if (empty($lista)) {
        return '';
    }
    $ph = [];
    $i = 0;
    foreach (array_keys($lista) as $cod) {
        $k = ':itsestLista_' . $i;
        $params[$k] = $cod;
        $ph[] = $k;
        $i++;
    }
    return "TRIM(Codigo_Unico) IN (" . implode(',', $ph) . ")";
}

/**
 * Anios disponibles en la tabla consolidada DENTRO DEL AMBITO del filtro
 * establecimiento (mismo criterio que No Transmisibles/Materno/Zoonosis).
 */
function itsGetAniosDisponibles(PDO $pdo, string $establecimiento = ''): array {
    try {
        $params = [];
        $whereEst = itsWhereEstablecimientos($pdo, $establecimiento, $params);
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
function itsGetMesesDisponibles(PDO $pdo, string $anio = '', string $establecimiento = ''): array {
    try {
        $params = [];
        $cond = [];
        if (trim($anio) !== '') {
            $cond[] = "Anio = :anio";
            $params[':anio'] = (string)$anio;
        }
        $whereEst = itsWhereEstablecimientos($pdo, $establecimiento, $params);
        if ($whereEst !== '') $cond[] = $whereEst;
        $colMes = itsTieneColumnaMesInt($pdo) ? 'Mes_Int' : "CAST(TRIM(Mes) AS UNSIGNED)";
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

/** Conexion PDO dedicada en modo UNBUFFERED (streaming), como los otros modulos. */
function itsAbrirConexionStreaming(): ?PDO {
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

/** Diagnostico del entorno PHP + tabla consolidada (panel de error del reporte). */
function itsDiagnosticoEntorno(PDO $pdo): array {
    $out = [
        'php' => PHP_VERSION,
        'memory_limit' => (string)ini_get('memory_limit'),
        'max_exec_time' => (string)ini_get('max_execution_time'),
        'filas_tabla' => null,
        'indices' => [],
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
    return $out;
}

/* ============================================================
 * 6) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/**
 * Cuenta/suma las filas que cumplen una condicion aplicando la regla de
 * conteo del T-SQL y agregando por los ejes de la seccion:
 *   'filas' : count(*) (cada fila HIS que cumple cuenta 1).
 *   'citas' : count(distinct id_cita) - SP 10/11 filas "ATENDIDOS/ATENCIONES"
 *             que en el T-SQL cuentan la fila rownum=1 de cada cita.
 *
 * Ejes: 'cond' (valor fijo $condKey), 'sexo', 'etapa', 'grupo', 'brigada',
 * 'trimestre'. Las filas cuyo valor de eje es null se excluyen (igual que el
 * "WHERE Etapa/Grupo/Brigada/Trimestre IS NOT NULL" del T-SQL).
 *
 * @return array ['vals' => <nested por ejes>, 'total' => int]
 */
function itsContar(?array $cond, array $ctx, string $condKey = ''): array {
    if ($cond === null) return ['vals' => [], 'total' => 0];
    $ejes = $ctx['ejes'];
    $regla = $ctx['regla'];
    $vals = [];
    $total = 0;
    $vistos = []; // celda => set de citas (regla 'citas')
    $candidatos = itsCandidatosFila($cond, $ctx);

    foreach ($candidatos as $idx) {
        $f = $ctx['filas'][$idx];
        if (!itsCumple($f, $cond, $ctx)) continue;

        // Ruta de la celda segun los ejes de la seccion (orden fijo).
        // El eje 'cond' NO entra en la clave: el caller ya indexa por
        // vals[condKey] (secciones multi-condicion).
        $ruta = [];
        $ok = true;
        foreach ($ejes as $eje) {
            switch ($eje) {
                case 'cond':     break;
                case 'sexo':
                    if ($f['sexo'] !== 'F' && $f['sexo'] !== 'M') { $ok = false; break 2; }
                    $ruta[] = $f['sexo']; break;
                case 'etapa':
                    $e = itsEtapa($f, $ctx['etapas']);
                    if ($e === null) { $ok = false; break 2; }
                    $ruta[] = $e; break;
                case 'grupo':
                    $g = itsGrupoDeCita($ctx, $f);
                    if ($g === null) { $ok = false; break 2; }
                    $ruta[] = $g; break;
                case 'brigada':
                    $b = itsBrigadaDeFila($f);
                    if ($b === null) { $ok = false; break 2; }
                    $ruta[] = $b; break;
                case 'trimestre':
                    $t = itsTrimestreDeFila($f);
                    if ($t === null) { $ok = false; break 2; }
                    $ruta[] = $t; break;
            }
        }
        if (!$ok) continue;

        // Celda en clave plana "eje1|eje2|..." -> valor entero
        $key = implode('|', $ruta);
        if ($regla === 'citas') {
            $citaKey = $f['cita'] !== '' ? $f['cita'] : ('#f' . $idx);
            if (isset($vistos[$key][$citaKey])) continue;
            $vistos[$key][$citaKey] = true;
        }
        $vals[$key] = ($vals[$key] ?? 0) + 1;
        $total++;
    }
    return ['vals' => $vals, 'total' => $total];
}

/**
 * Lee un valor del mapa plano de celdas devuelto por itsContar (SIN el eje
 * cond, que ya indexa el mapa). Ejemplo: itsVal($vals, 'F', 3) sobre una
 * seccion con ejes ['cond','sexo','etapa'] lee $vals['F|3'].
 */
function itsVal(array $vals, ...$keys): int {
    $key = implode('|', $keys);
    return (int)($vals[$key] ?? 0);
}

/**
 * Indices candidatos de TODAS las filas (no de una cita) para un predicado,
 * por buckets de codigo/valor_lab/lote (sobre-aproximacion; itsCumple
 * verifica exacto).
 */
function itsCandidatosFila(array $cond, array $ctx): array {
    $total = count($ctx['filas']);
    if ($total <= 0) return [];
    $tieneCod  = isset($cond['cod']) || isset($cond['codF']);
    $tieneCual = isset($cond['cualquieraDe']);
    if (!$tieneCod && !$tieneCual && !isset($cond['vl']) && !isset($cond['lote'])) {
        return range(0, $total - 1);
    }
    $out = [];
    if ($tieneCod || $tieneCual) {
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
        if ($tieneCual) {
            foreach ($cond['cualquieraDe'] as $sub) {
                foreach (itsCandidatosFila($sub, $ctx) as $idx) $out[$idx] = true;
            }
        }
        return array_keys($out);
    }
    if (isset($cond['vl'])) {
        $vals = is_array($cond['vl']) ? $cond['vl'] : [$cond['vl']];
        foreach ($vals as $v) {
            if ($v === 'NULL') continue; // IS NULL no indexa: cae al barrido
            foreach ($ctx['porVl'][strtoupper((string)$v)] ?? [] as $idx) $out[$idx] = true;
        }
        if ($out) return array_keys($out);
        return range(0, $total - 1); // contiene NULL: barrido completo
    }
    // lote
    foreach ((array)$cond['lote'] as $l) {
        foreach ($ctx['porLote'][strtoupper((string)$l)] ?? [] as $idx) $out[$idx] = true;
    }
    return array_keys($out);
}

/**
 * Ejecuta el reporte ITS completo (adaptacion de los 15 procedimientos del
 * archivo "03 Creacion de Procedimientos").
 *
 * Estrategia (igual que ESNI/Cancer/Materno/Zoonosis/NoTransmisibles): UNA
 * sola consulta que trae todas las filas HIS de los codigos de interes con
 * los filtros comunes aplicados, y el matching de cada fila del reporte se
 * resuelve en PHP con el DSL (que espeja los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function itsEjecutarReporte(PDO $pdo, array $filtros): array {
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
        if (itsTieneColumnaMesInt($pdo)) {
            $where[] = "Mes_Int = :mes";
        } else {
            $where[] = "CAST(TRIM(Mes) AS UNSIGNED) = :mes";
        }
        $params[':mes'] = intval($filtros['mes']);
    }
    $whereEst = itsWhereEstablecimientos($pdo, (string)($filtros['establecimiento'] ?? ''), $params);
    if ($whereEst !== '') {
        $where[] = $whereEst;
    }

    // ---- 2) Codigos de interes del paquete ITS ----
    $whereCod = itsWhereCodigos($params);

    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab,
                   Id_Correlativo_Lab, Codigo_Unico,
                   Lote, Id_Etnia, Id_Condicion_Servicio, Anio, Mes
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    $pdoStream = itsAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = itsFila($r);
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

    return itsEjecutarDesdeFilas($filas, $t0);
}

/**
 * Ejecuta el pipeline de matching/agregacion del reporte a partir de las
 * filas HIS YA normalizadas (itsFila). Separada de itsEjecutarReporte para
 * poder validar el pipeline sin base de datos.
 */
function itsEjecutarDesdeFilas(array $filas, float $t0 = 0.0): array {
    if ($t0 === 0.0) $t0 = microtime(true);

    // Indices por cita (por cita/cod/codF/vl) y buckets globales por codigo
    $porCita = [];
    $porCitaCod = [];
    $porCitaCodF = [];
    $porCitaVl = [];
    $porCod = [];
    $porCodF = [];
    $porVl = [];
    $porLote = [];
    foreach ($filas as $idx => $f) {
        if ($f['cita'] !== '') {
            $porCita[$f['cita']][] = $idx;
            if ($f['cod'] !== '') $porCitaCod[$f['cita']][$f['cod']][] = $idx;
            if ($f['codF'] !== '') $porCitaCodF[$f['cita']][$f['codF']][] = $idx;
            if ($f['vl'] !== null && $f['vl'] !== '') $porCitaVl[$f['cita']][$f['vl']][] = $idx;
        }
        if ($f['cod'] !== '') $porCod[$f['cod']][] = $idx;
        if ($f['codF'] !== '') $porCodF[$f['codF']][] = $idx;
        if ($f['vl'] !== null && $f['vl'] !== '') $porVl[$f['vl']][] = $idx;
        if ($f['lote'] !== '') $porLote[$f['lote']][] = $idx;
    }
    $ctx = [
        'filas' => $filas, 'porCita' => $porCita,
        'porCitaCod' => $porCitaCod, 'porCitaCodF' => $porCitaCodF,
        'porCitaVl' => $porCitaVl, 'porCod' => $porCod, 'porCodF' => $porCodF,
        'porVl' => $porVl, 'porLote' => $porLote,
    ];

    // ---- Matching por seccion ----
    $seccionesOut = [];
    $totCasos = 0;

    foreach (itsSecciones() as $sec) {
        $ctxSec = $ctx + [
            'ejes'   => $sec['ejes'],
            'etapas' => $sec['etapas'],
        ];
        $reglaSec = $sec['regla'];

        $secOut = $sec;
        $totalSec = 0;
        $filasOut = [];

        foreach ($sec['filas'] as $fr) {
            $regla = $fr['regla'] ?? $reglaSec;
            $ctxFila = $ctxSec + ['regla' => $regla];
            $condsFr = isset($fr['conds']) ? $fr['conds'] : ['' => ($fr['cond'] ?? null)];
            $vals = [];
            $totFila = 0;
            foreach ($condsFr as $condKey => $cond) {
                $r = itsContar($cond, $ctxFila, (string)$condKey);
                if ($condKey !== '') $vals[$condKey] = $r['vals'];
                else $vals = $r['vals'];
                $totFila += $r['total'];
            }
            $filasOut[] = [
                'dx' => $fr['dx'], 'label' => $fr['label'],
                'fila' => $fr['fila'] ?? null,
                'vals' => $vals, 'total' => $totFila,
                'cond' => $fr['cond'] ?? null, 'conds' => $fr['conds'] ?? null,
            ];
            $totalSec += $totFila;
        }

        $secOut['filas'] = $filasOut;
        $secOut['total'] = $totalSec;
        $totCasos += $totalSec;
        $seccionesOut[] = $secOut;
    }

    return [
        'secciones' => $seccionesOut,
        'totales'   => [
            'total_casos' => $totCasos,
            'secciones_con_datos' => count(array_filter($seccionesOut, fn($s) => ($s['total'] ?? 0) > 0)),
            'grupos' => [
                'PG'  => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'PG'))),
                'GES' => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'GES'))),
                'PC'  => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'PC'))),
                'TM'  => array_sum(array_map(fn($s) => $s['total'], array_filter($seccionesOut, fn($s) => $s['grupo'] === 'TM'))),
            ],
        ],
        'version'          => ITS_DATA_VERSION,
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}
