<?php

/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ADOLESCENTE - Motor de reporte data-driven (includes/adolescente_data.php)
 *
 * ADAPTACION FIEL del archivo "03 Creacion de Procedimientos.txt"
 * (25 procedimientos usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_*) sobre la tabla
 * consolidada MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO. Reemplaza:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales" (DimAdolescente2020_*:
 *      Actividad, Actividad2, Actividad3, Actividad4_Altern, AtcMental,
 *      AtcOdontologica, AtencionPF, Consejeria, Diagnostico, Estado, EvAuditiva,
 *      EvClinica, EvFisico, EvNutric, EvVisual, GrupoEdad1, Institucion,
 *      Morbilidad, Paquete, Prueba, Seguimiento, Sesion, Sesion2/3_Nutric,
 *      Sesion4/5_SSR, Sesion6/7_Psicosoc, Tamizaje, Vacunas, Visita)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *      (TRAMA_BASE_ADOLESCENTE_2020_RPT_*_CONSOLIDADO / _NOMINAL)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos" (25 SP)
 *   4) Excel:      refrescar la conexion ODBC de "Reporte_Actividades_
 *      Adolescentes.xlsx"
 *
 * SECCIONES (25 procedimientos -> 27 bloques del Excel, hoja "Plantilla"):
 *   01  usp_..._01_MORBILIDAD        filas 11-14  (4 morbilidades: IRA
 *                                      complicadas/no, EDA complicadas/no)
 *   02  usp_..._02_CONTROL           filas 21-26  (99801/Z003/99384 x Estado x
 *                                      Paquete x Institucion EE.SS/II.EE)
 *   03  usp_..._03_VISITA            filas 31-36  (C0011 y 99509 x N visita)
 *   04  usp_..._04_EVALUACION        filas 41-58  (18 evaluaciones: Z000, Z019,
 *                                      PAB RSM/RSA/RMA, IMC, T/E, etc.)
 *   05  usp_..._05_VISUAL            filas 63-66  (99173, Z010 N/A, 99401.16)
 *   06  usp_..._06_AUDITIVA          filas 71-73  (H919, Z011 N/A)
 *   07  usp_..._07_ODONTOLOGICA      filas 78-82  (D1310..U510)
 *   08  usp_..._08_FISICO            filas 87-92  (96008, M400..M419)
 *   09  usp_..._09_INMUNIZACIONES    filas 97-106 (HvB 1-3, Influenza, dT 1-3,
 *                                      VPH 1-2, Antiamarilica)
 *   10  usp_..._10_PLANIFICACION     filas 111-124 (Tanner, 99208, DIU,
 *                                      hormonales, barrera, MELA, abstinciencia,
 *                                      AOE/YUZPE con suma de valor_lab)
 *   11  usp_..._11_CLINICA           filas 128-144 (17 evaluaciones clinicas)
 *   12  usp_..._12_CONSEJERIAS       filas 149-161 (13 consejerias)
 *   13  usp_..._13_PRUEBAS           filas 166-170 (VIH, sifilis, hep B, embarazo)
 *   14  usp_..._14_MENTAL            filas 175-183 (96150, F419, Z553, Z7281,
 *                                      96150.01-05)
 *   15  usp_..._15_TAMIZAJES         filas 188-195 (8 tamizajes positivos)
 *   16_1 usp_..._16_1_ACTIVIDADES    filas 200-201 (C0009, C2111.02)
 *   16_2 usp_..._16_2_ACTIVIDADES    filas 204-212 (APP93..APP150 por ficha
 *                                      familiar, UPS 302304)
 *   16_3 usp_..._16_3_ACTIVIDADES    filas 215-226 (C1021..C7004, UPS 302304)
 *   17  usp_..._17_NUTRICIONAL       filas 231-239 (9 sesiones 12-14 con
 *                                      marcador PSA en C0009 fila 2)
 *   18  usp_..._18_NUTRICIONAL       filas 244-255 (12 sesiones 15-17, PSA)
 *   19  usp_..._19_SSR               filas 260-264 (5 sesiones 12-14, SSI)
 *   20  usp_..._20_SSR               filas 269-275 (7 sesiones 15-17, SSI)
 *   21  usp_..._21_PSICOSOCIAL       filas 280-289 (10 sesiones 12-14, PSI)
 *   22  usp_..._22_PSICOSOCIAL       filas 294-305 (12 sesiones 15-17, PSI)
 *   23  usp_..._23_COVID19           filas 310-319 (10 diagnosticos COVID)
 *   24  usp_..._24_SEGUIMIENTO       filas 324-329 (C0011, 99499.08-.11, 99499.01)
 *   25  usp_..._25_ALTERNATIVA       filas 334-337 (U0080, 99401.32, Medicina
 *                                      cuerpo mente UPS 301903, C2081)
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita         -> Id_Cita
 *   id_tipitem      -> Tipo_Diagnostico      ('D','P','R','C')
 *   cod_item        -> Codigo_Item
 *   cod_item_f      -> primeros 3 caracteres alfabeticos de Codigo_Item
 *                      (familia CIE: 'A05', 'J18', 'E86', 'R05'...)
 *   valor_lab       -> Valor_Lab             ('NULL' en el DSL = IS NULL)
 *   I_ROWNUM_LAB    -> Id_Correlativo_Lab
 *   rownum          -> Id_Correlativo_Item   (primera fila de la cita; lo usan
 *                      los SP 16_2 y 25 con "t.rownum=1")
 *   id_genero       -> Id_Genero             ('F','M'; el nominal exige
 *                      "sexo is not null" en todas las secciones salvo 16_2/16_3)
 *   edad_reg        -> Edad_Reg
 *   id_tipedad_reg  -> Tipo_Edad             ('A' anios)
 *   renaes          -> Codigo_Unico
 *   id_persona      -> Id_Paciente
 *   fichafam        -> Ficha_Familiar        (APP93..APP150: actividades
 *                      promocionales en colegios/EE.SS/municipalidades/...)
 *   id_ups          -> Id_Ups                ('302304' actividades promocionales,
 *                      '301903' medicina alternativa)
 *   periodo         -> Anio (CASE YEAR(periodo)<2025 del SP 02: C8002/99801)
 *
 * GRUPOS ETAREOS (como el Excel): 12a-14a (e12_14) y 15a-17a (e15_17).
 *
 * REGLAS DE CONTEO (columnas e12_14/e15_17 de cada tabla consolidada):
 *   'filas'    : sum(iif(edad between 12 and 14,1,0)) -> cada fila HIS que
 *                cumple la condicion cuenta 1 (todas las secciones salvo 10)
 *   'total_vl' : sum(iif(edad between X,Y,Total,0)) con Total =
 *                iif(TRY_CONVERT(int,valor_lab) IS NULL OR clave 10..13, 1,
 *                    TRY_CONVERT(int,valor_lab))   (SP 10: metodos
 *                anticonceptivos: DIU/hormonal/barrera/AOE suman la cantidad
 *                registrada en valor_lab; MELA/BILLINGS/RITMO/DIAS FIJO
 *                siempre 1; Tanner y consulta PF cuentan 1)
 *
 * ELIMINACIONES del T-SQL replicadas:
 *   SP 02: "delete ... where Paquete=3 and Institucion=2" -> el combo
 *          Paquete especializado x II.EE no existe en el reporte (P3I2).
 *   Nominal: "sexo is not null" -> las filas sin Id_Genero se excluyen en
 *          todas las secciones salvo 16_2/16_3 (sus SP no filtran sexo).
 */

define('ADO_DATA_VERSION', '2.0.0-trozos');

function adoDataVersion(): string {
    return ADO_DATA_VERSION;
}

/* ============================================================
 * 1) NORMALIZACION DE FILAS HIS
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 *
 * Textos en MAYUSCULAS para replicar la colacion case-insensitive de MySQL
 * en el matching PHP (igual que its_data.php / zoonosis_data.php).
 */
function adoFila(array $r): array {
    $cod = $r['Codigo_Item'] !== null ? strtoupper(trim((string)$r['Codigo_Item'])) : '';
    $codF = ($cod !== '' && ctype_alpha($cod[0])) ? substr($cod, 0, 3) : $cod;
    return [
        'cita'    => $r['Id_Cita'] !== null ? trim((string)$r['Id_Cita']) : '',
        'pac'     => $r['Id_Paciente'] !== null ? trim((string)$r['Id_Paciente']) : '',
        'renaes'  => $r['Codigo_Unico'] !== null ? trim((string)$r['Codigo_Unico']) : '',
        'cod'     => $cod,
        'codF'    => $codF,
        'tip'     => $r['Tipo_Diagnostico'] !== null ? strtoupper(trim((string)$r['Tipo_Diagnostico'])) : '',
        'vl'      => $r['Valor_Lab'] !== null ? strtoupper(trim((string)$r['Valor_Lab'])) : null,
        'rownum'  => $r['Id_Correlativo_Lab'] !== null ? (int)$r['Id_Correlativo_Lab'] : null,
        'rownumIt'=> $r['Id_Correlativo_Item'] !== null ? (int)$r['Id_Correlativo_Item'] : null,
        'sexo'    => $r['Id_Genero'] !== null ? strtoupper(trim((string)$r['Id_Genero'])) : '',
        'edad'    => $r['Edad_Reg'] !== null ? (int)$r['Edad_Reg'] : null,
        'tipEdad' => $r['Tipo_Edad'] !== null ? strtoupper(trim((string)$r['Tipo_Edad'])) : '',
        'ficha'   => isset($r['Ficha_Familiar']) && $r['Ficha_Familiar'] !== null
                        ? strtoupper(trim((string)$r['Ficha_Familiar'])) : '',
        'ups'     => isset($r['Id_Ups']) && $r['Id_Ups'] !== null
                        ? strtoupper(trim((string)$r['Id_Ups'])) : '',
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
 *   'cod'         => '99384' | ['C8002','99801']       Codigo_Item exacto
 *   'codF'        => 'A05' | ['A05','A07','A00']       cod_item_f (familia CIE)
 *   'tip'         => 'D' | ['D','R']                   Tipo_Diagnostico
 *   'vl'          => 'NULL' | 'TA' | ['1','2','3']     Valor_Lab (NULL = IS NULL)
 *   'vlNum'       => true                              ISNUMERIC(valor_lab)=1
 *   'vlGeNum'     => 3                                 TRY_CONVERT(int,valor_lab) >= 3
 *   'rownum'      => 1 | 2                             I_ROWNUM_LAB (Id_Correlativo_Lab)
 *   'rownumItem'  => 1                                 rownum del T-SQL (primera
 *                                                    fila de la cita: Id_Correlativo_Item)
 *   'sexo'        => 'F' | 'M'                          Id_Genero
 *   'tipEdad'     => 'A'                                Tipo_Edad en lista
 *   'edadA'       => [12, 17]                           id_tipedad_reg='A' y
 *                                                    edad_reg en rango [min,max]
 *   'ficha'       => 'APP93' | ['APP93','APP100']       fichafam (Ficha_Familiar)
 *   'ups'         => '302304' | ['302304','301903']     id_ups (Id_Ups)
 *   'anioLt'      => 2025                               YEAR(periodo) < N (CASE del SP 02)
 *   'anioGe'      => 2025                               YEAR(periodo) >= N
 *   'citaTiene'   => <predicado>                        EXISTS sobre la cita
 *   'citaTieneTodo' => [<pred>, ...]                    AND de EXISTS
 *   'citaSin'     => <predicado>                        NOT EXISTS sobre la cita
 *   'citaSinTodo' => [<pred>, ...]                      AND de NOT EXISTS
 *   'cualquieraDe'  => [<pred>, ...]                    OR de predicados (fila)
 *
 * $ctx: contexto de ejecucion (filas + indices porCita/porCod), igual que ITS.
 */
function adoCumple(array $f, array $cond, array $ctx): bool {
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
            if ($f['vl'] === null) return false;
            if (!in_array($f['vl'], $vals, true)) return false;
        }
    }
    if (isset($cond['vlNum'])) {
        // ISNUMERIC(valor_lab) del T-SQL: valor presente y numerico
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
    }
    if (isset($cond['vlGeNum'])) {
        // valor_lab >= N (comparacion numerica; el SP compara cadena, pero los
        // valores del 99384/PAI son digitos simples: identico resultado)
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
        if ((float)$f['vl'] < (float)$cond['vlGeNum']) return false;
    }
    if (isset($cond['rownum'])) {
        if ($f['rownum'] === null || $f['rownum'] !== (int)$cond['rownum']) return false;
    }
    if (isset($cond['rownumItem'])) {
        if ($f['rownumIt'] === null || $f['rownumIt'] !== (int)$cond['rownumItem']) return false;
    }
    if (isset($cond['sexo'])) {
        if ($f['sexo'] !== strtoupper((string)$cond['sexo'])) return false;
    }
    if (isset($cond['tipEdad'])) {
        $tes = is_array($cond['tipEdad']) ? $cond['tipEdad'] : [$cond['tipEdad']];
        $tes = array_map(fn($t) => strtoupper((string)$t), $tes);
        if (!in_array($f['tipEdad'], $tes, true)) return false;
    }
    if (isset($cond['edadA'])) {
        [$min, $max] = $cond['edadA'];
        if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return false;
        if ($min !== null && $f['edad'] < $min) return false;
        if ($max !== null && $f['edad'] > $max) return false;
    }
    if (isset($cond['ficha'])) {
        $fs = is_array($cond['ficha']) ? $cond['ficha'] : [$cond['ficha']];
        $fs = array_map(fn($x) => strtoupper((string)$x), $fs);
        if (!in_array($f['ficha'], $fs, true)) return false;
    }
    if (isset($cond['ups'])) {
        $us = is_array($cond['ups']) ? $cond['ups'] : [$cond['ups']];
        $us = array_map(fn($x) => strtoupper((string)$x), $us);
        if (!in_array($f['ups'], $us, true)) return false;
    }
    if (isset($cond['anioLt'])) {
        if ($f['anio'] === '' || !is_numeric($f['anio']) || (int)$f['anio'] >= (int)$cond['anioLt']) return false;
    }
    if (isset($cond['anioGe'])) {
        if ($f['anio'] === '' || !is_numeric($f['anio']) || (int)$f['anio'] < (int)$cond['anioGe']) return false;
    }
    if (isset($cond['citaTiene'])) {
        if (!adoCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!adoCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['citaSin'])) {
        if (adoCitaTiene($ctx, $f['cita'], $cond['citaSin'])) return false;
    }
    if (isset($cond['citaSinTodo'])) {
        foreach ($cond['citaSinTodo'] as $sub) {
            if (adoCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (adoCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** EXISTS sobre la cita: alguna fila de la cita cumple el predicado (por indice). */
function adoCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    $cands = adoCandidatosCita($ctx, $cita, $cond);
    foreach ($cands as $idx) {
        if (adoCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Indices candidatos de la cita para un predicado (sobre-aproximacion por
 * buckets de codigo; la verificacion exacta la hace adoCumple).
 */
function adoCandidatosCita(array $ctx, string $cita, array $cond): array {
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
 * "Ver SQL": auditoria contra el archivo 03).
 */
function adoCondicionSQL(array $cond, int $nivel = 0): string {
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
                if ($v) $partes[] = "ISNUMERIC(valor_lab) = 1";
                break;
            case 'vlGeNum':
                $partes[] = "TRY_CONVERT(int, valor_lab) >= " . (int)$v;
                break;
            case 'rownum':
                $partes[] = "I_ROWNUM_LAB = " . (int)$v;
                break;
            case 'rownumItem':
                $partes[] = "rownum = " . (int)$v . "  -- Id_Correlativo_Item (1a fila de la cita)";
                break;
            case 'sexo':
                $partes[] = "id_genero = '{$v}'";
                break;
            case 'tipEdad':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_tipedad_reg IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'edadA':
                $partes[] = "id_tipedad_reg = 'A' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 'NULL');
                break;
            case 'ficha':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "fichafam IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'ups':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "id_ups IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'anioLt':
                $partes[] = "YEAR(periodo) < " . (int)$v;
                break;
            case 'anioGe':
                $partes[] = "YEAR(periodo) >= " . (int)$v;
                break;
            case 'citaTiene':
                $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . adoCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaTieneTodo':
                foreach ($v as $sub) {
                    $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . adoCondicionSQL($sub, $nivel + 1) . ")";
                }
                break;
            case 'citaSin':
                $partes[] = "id_cita NOT IN (SELECT id_cita FROM #TEMP WHERE " . adoCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaSinTodo':
                foreach ($v as $sub) {
                    $partes[] = "id_cita NOT IN (SELECT id_cita FROM #TEMP WHERE " . adoCondicionSQL($sub, $nivel + 1) . ")";
                }
                break;
            case 'cualquieraDe':
                $sub = [];
                foreach ($v as $c) {
                    $sub[] = "(" . adoCondicionSQL($c, $nivel + 1) . ")";
                }
                $partes[] = "(" . implode(" OR ", $sub) . ")";
                break;
        }
    }
    $union = implode("\n  AND ", $partes);
    return $union;
}

/**
 * Combina varios predicados con AND resolviendo colisiones de claves
 * (el operador + de PHP descartaria las repetidas). Las claves de cita
 * (citaTiene/citaTieneTodo y citaSin/citaSinTodo) se agrupan en listas AND;
 * cualquieraDe tambien (mismo mecanismo que itsY()).
 */
function adoY(array ...$parts): array {
    $out = [];
    $tiene = [];
    $sin = [];
    $cual = [];
    foreach ($parts as $p) {
        if ($p === []) continue;
        foreach ($p as $k => $v) {
            if ($k === 'citaTiene') {
                $tiene[] = $v;
            } elseif ($k === 'citaTieneTodo') {
                foreach ($v as $c) $tiene[] = $c;
            } elseif ($k === 'citaSin') {
                $sin[] = $v;
            } elseif ($k === 'citaSinTodo') {
                foreach ($v as $c) $sin[] = $c;
            } elseif ($k === 'cualquieraDe') {
                foreach ($v as $c) $cual[] = $c;
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

/** Etiquetas de los grupos etareos del reporte adolescente (columnas Excel). */
function adoEdadLabels(): array {
    return [1 => '12a -14a', 2 => '15a - 17a'];
}


/* ============================================================
 * 3) DEFINICION DE LAS SECCIONES DEL REPORTE
 *    (adaptacion del archivo "03 Creacion de Procedimientos":
 *     25 SP -> 27 bloques, mismo numero y orden que el Excel)
 * ============================================================ */

/**
 * Devuelve la definicion completa de las 27 secciones del reporte
 * ADOLESCENTE (hoja "Plantilla" del Excel "Reporte_Actividades_
 * Adolescentes.xlsx").
 *
 * Cada seccion replica un bloque del Excel y cada fila replica las lineas de
 * su procedimiento usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_* (mismo numero y
 * orden). Campos por seccion:
 *   'codigo' / 'procedimiento' / 'titulo' / 'grupo' (macro-grupo web)
 *   'layout'   => 'STD' (Total|12-14|15-17) | 'CTRL' (paquetes x institucion)
 *                 | 'CASOS' (solo una columna de casos/valor)
 *   'cols'     => mapa de columnas del export: ['total'=>'B','e12'=>'C','e15'=>'D']
 *                 (STD); ['total'=>'C','e12'=>'D','e15'=>'E'] (variantes);
 *                 ['v'=>'B'] (CASOS)
 *   'conSexo'  => false solo en 16_2/16_3 (sus SP no exigen "sexo is not null")
 *   'ejes'     => ejes de agregacion: ['edadG'] (STD) | ['cond','edadG'] (CTRL)
 *                 | [] (CASOS)
 *   'regla'    => 'filas' (count) | 'total_vl' (sum valor_lab, SP 10)
 *   'filas'    => cada fila: ['key','label','cond' => predicado |
 *                 'conds' => [combo => predicado] (CTRL), 'fila' => fila Excel,
 *                 'regla' => regla propia (opcional)]
 */
function adoSecciones(): array {

    // ============ Listas de codigos (segun archivo 03) ============
    // SP 01: IRA / EDA
    $iraComp  = ['cod' => ['J180','J050','J051','J851','J939','J188','J189','J181','J229','J450','J459','J448','J449']];
    $iraCompF = ['codF' => ['A37','J90','J86','J18','J11','J13','J29','J16','J46']];
    $iraNo    = ['cod' => ['J020','J0309','H660','H669','J028','J029','J040','J041','J042','J069','J209']];
    $iraNoF   = ['codF' => ['J00','J01']];
    $edaList  = ['cod' => ['A018','A011','A013','A029','A020','A080','A082','A083','A084','A049','A030','A039','A045','A060','A062','A099']];
    $edaF     = ['codF' => ['A05','A07','A00']];
    // #EDA_COMP del SP 01: signos de complicacion (R571 o familia E86)
    $edaComplic = ['cualquieraDe' => [
        ['cod' => 'R571', 'tip' => 'D'],
        ['codF' => 'E86', 'tip' => 'D'],
    ]];

    // SP 02: Plan de Atencion Integral (C8002 hasta 2024 / 99801 desde 2025)
    $pai = ['cualquieraDe' => [
        ['cod' => 'C8002', 'anioLt' => 2025],
        ['cod' => '99801', 'anioGe' => 2025],
    ]];
    $paiD = ['cualquieraDe' => [          // #PAI (id_tipitem='D')
        ['cod' => 'C8002', 'anioLt' => 2025, 'tip' => 'D'],
        ['cod' => '99801', 'anioGe' => 2025, 'tip' => 'D'],
    ]];
    // #EVALUACION del SP 02 (99384 numerico, fila 1)
    $evalNum = ['cod' => '99384', 'tip' => 'D', 'rownum' => 1, 'vlNum' => true];

    // SP 17-22: marcadores de sesion educativa (C0009, fila 2 del item)
    $psa = ['cod' => 'C0009', 'tip' => 'D', 'vl' => 'PSA', 'rownum' => 2];
    $ssi = ['cod' => 'C0009', 'tip' => 'D', 'vl' => 'SSI', 'rownum' => 2];
    $psi = ['cod' => 'C0009', 'tip' => 'D', 'vl' => 'PSI', 'rownum' => 2];

    $D  = ['tip' => 'D', 'rownum' => 1];   // id_tipitem='D' AND I_ROWNUM_LAB=1
    $R1 = ['rownum' => 1];                 // solo I_ROWNUM_LAB=1 (SP 16_3)
    $e1217 = ['edadA' => [12, 17]];
    $e1214 = ['edadA' => [12, 14]];
    $e1517 = ['edadA' => [15, 17]];

    $secs = [];

    /* ------------------------------------------------------------
     * 01 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_01_MORBILIDAD
     * MORBILIDAD DEL ADOLESCENTE (filas Excel 11-14)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO01_MORBILIDAD',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_01_MORBILIDAD',
        'titulo'        => '1. MORBILIDAD DEL ADOLESCENTE',
        'grupo'         => 'ATN',
        'layout'        => 'STD',
        'labelCols'     => ['Morbilidad', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => 'Infecciones Respiratorias Agudas Complicadas', 'fila' => 11,
             'cond' => adoY($e1217, $D, ['cualquieraDe' => [$iraComp, $iraCompF]])],
            ['key' => 2, 'label' => 'Infecciones Respiratorias Agudas No Complicadas', 'fila' => 12,
             'cond' => adoY($e1217, $D, ['cualquieraDe' => [$iraNo, $iraNoF]])],
            ['key' => 3, 'label' => 'Enfermedades Diarreicas Agudas Complicadas', 'fila' => 13,
             'cond' => adoY($e1217, $D, ['cualquieraDe' => [$edaList, $edaF]], ['citaTiene' => $edaComplic])],
            ['key' => 4, 'label' => 'Enfermedades Diarreicas Agudas No Complicadas', 'fila' => 14,
             'cond' => adoY($e1217, $D, ['cualquieraDe' => [$edaList, $edaF]], ['citaSin' => $edaComplic])],
        ],
    ];

    /* ------------------------------------------------------------
     * 02 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_02_CONTROL
     * CONTROL Y SEGUIMIENTO DE ADOLESCENTES (filas Excel 21-26)
     * Combos Paquete (pos 2) x Institucion (pos 3) del #PAI; el delete
     * "Paquete=3 and Institucion=2" deja fuera el combo P3I2.
     * ------------------------------------------------------------ */
    $combos = [
        'P1I1' => [1, 1], 'P2I1' => [2, 1], 'P3I1' => [3, 1], 'P1I2' => [1, 2], 'P2I2' => [2, 2],
    ];
    $condCombo = [];
    foreach ($combos as $ck => [$pk, $ik]) {
        $condCombo[$ck] = adoY(
            ['citaTiene' => $paiD + ['rownum' => 2, 'vl' => (string)$pk]],
            ['citaTiene' => $paiD + ['rownum' => 3, 'vl' => (string)$ik]]
        );
    }
    $secs[] = [
        'codigo'        => 'ADO02_CONTROL',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_02_CONTROL',
        'titulo'        => '2. CONTROL Y SEGUIMIENTO DE ADOLESCENTES',
        'grupo'         => 'ATN',
        'layout'        => 'CTRL',
        'labelCols'     => ['Actividad', 'Estado'],
        'cols'          => ['total' => 'C',
                            'P1I1' => ['D','E'], 'P2I1' => ['F','G'], 'P3I1' => ['H','I'],
                            'P1I2' => ['J','K'], 'P2I2' => ['L','M']],
        'ejes'          => ['cond', 'edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => '99801 - Plan de Atención Integral de Salud', 'label2' => 'Iniciado', 'fila' => 21,
             'conds' => array_map(fn($cc) => adoY($e1217, $pai, ['rownum' => 1, 'vl' => '1'],
                                                     ['citaTiene' => ['cod' => '99384', 'tip' => 'D', 'rownum' => 1, 'vl' => '1']], $cc),
                                  $condCombo)],
            ['key' => 2, 'label' => '99801 - Plan de Atención Integral de Salud', 'label2' => 'Concluido', 'fila' => 22,
             'conds' => array_map(fn($cc) => adoY($e1217, $pai, ['rownum' => 1, 'vl' => 'TA'],
                                                     ['citaTiene' => $evalNum + ['vlGeNum' => 3]], $cc),
                                  $condCombo)],
            ['key' => 3, 'label' => 'Z003 - Examen del estado de desarrollo del adolescente', 'label2' => '--------------', 'fila' => 23,
             'conds' => array_map(fn($cc) => adoY($e1217, ['cod' => 'Z003'], $D, ['citaTiene' => $paiD], $cc),
                                  $condCombo)],
            ['key' => 4, 'label' => '99384 - Evaluación Integral del Adolescente', 'label2' => '1° Control', 'fila' => 24,
             'conds' => array_map(fn($cc) => adoY($e1217, ['cod' => '99384'], $D, ['vl' => '1'],
                                                     ['citaTiene' => $paiD + ['rownum' => 1, 'vl' => '1']], $cc),
                                  $condCombo)],
            ['key' => 5, 'label' => '99384 - Evaluación Integral del Adolescente', 'label2' => '2° Control', 'fila' => 25,
             'conds' => array_map(fn($cc) => adoY($e1217, ['cod' => '99384'], $D, ['vl' => '2'], $cc),
                                  $condCombo)],
            ['key' => 6, 'label' => '99384 - Evaluación Integral del Adolescente', 'label2' => '3° Control a + controles', 'fila' => 26,
             'conds' => array_map(fn($cc) => adoY($e1217, ['cod' => '99384'], $D, ['vlGeNum' => 3],
                                                     ['citaTiene' => $paiD + ['rownum' => 1, 'vl' => 'TA']], $cc),
                                  $condCombo)],
        ],
    ];

    /* ------------------------------------------------------------
     * 03 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_03_VISITA
     * VISITA DOMICILIARIA (filas Excel 31-36)
     * ------------------------------------------------------------ */
    $visita = function (string $cod, string $vlKey, int $keyBase, int $filaBase, string $grupo) use ($e1217, $D): array {
        $out = [];
        $lbl = [1 => ['1° visita', ''], 2 => ['2° visita', ''], 3 => ['Con 3 a + visitas', '']];
        foreach ([1 => '1', 2 => '2'] as $k => $vl) {
            $out[] = ['key' => $keyBase + $k - 1, 'label' => $grupo, 'label2' => $lbl[$k][0], 'fila' => $filaBase + $k - 1,
                      'cond' => adoY($e1217, ['cod' => $cod], $D, ['vl' => $vl])];
        }
        $out[] = ['key' => $keyBase + 2, 'label' => $grupo, 'label2' => $lbl[3][0], 'fila' => $filaBase + 2,
                  'cond' => adoY($e1217, ['cod' => $cod], $D, ['vlGeNum' => 3])];
        return $out;
    };
    $secs[] = [
        'codigo'        => 'ADO03_VISITA',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_03_VISITA',
        'titulo'        => '3. VISITA DOMICILIARIA',
        'grupo'         => 'ATN',
        'layout'        => 'STD',
        'labelCols'     => ['Grupo', 'Visita'],
        'cols'          => ['total' => 'C', 'e12' => 'D', 'e15' => 'E'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => array_merge(
            $visita('C0011', '', 1, 31, 'C0011 - Visita domiciliaria'),
            $visita('99509', '', 4, 34, '99509 - Visita domiciliaria para la ayuda con actividades de la vida diaria y del cuidado personal')
        ),
    ];

    /* ------------------------------------------------------------
     * 04 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_04_EVALUACION
     * EVALUACIÓN FÍSICA NUTRICIONAL (filas Excel 41-58)
     * ------------------------------------------------------------ */
    $evNut = [
        [1,  'Z000 - Examen médico general', '--------------', ['cod' => 'Z000']],
        [2,  'Z019 - Valoración de Factores de Riesgo (DNT)', '--------------', ['cod' => 'Z019']],
        [3,  '99209.04 - Evaluación Nutricional Antropometrica (PAB)', 'RSM - Riesgo Bajo', ['cod' => '99209.04', 'vl' => 'RSM']],
        [4,  '99209.04 - Evaluación Nutricional Antropometrica (PAB)', 'RSA - Riesgo Alto', ['cod' => '99209.04', 'vl' => 'RSA']],
        [5,  '99209.04 - Evaluación Nutricional Antropometrica (PAB)', 'RMA - Riesgo Muy Alto', ['cod' => '99209.04', 'vl' => 'RMA']],
        [6,  'Índice de Masa Corporal (IMC)', 'E660 - Obesidad debido a exceso de caloría (Sobrepeso)', ['cod' => 'E660']],
        [7,  'Índice de Masa Corporal (IMC)', 'E669 - Obesidad no especificada (Obesidad)', ['cod' => 'E669']],
        [8,  'Índice de Masa Corporal (IMC)', 'Z006 - Peso Normal', ['cod' => 'Z006', 'vl' => 'IMC']],
        [9,  'Índice de Masa Corporal (IMC)', 'E440 - Desnutrición Proteico Calórica Moderada(Desnutrición Aguda)', ['cod' => 'E440']],
        [10, 'Índice de Masa Corporal (IMC)', 'E43X - Desnutrición Proteico Calórica Severa No Especificada', ['cod' => 'E43X']],
        [11, 'Talla/Edad', 'E344X - Estatura Alta Constitucional (Talla Alta)', ['cod' => 'E344X']],
        [12, 'Talla/Edad', 'Z006 - Talla Normal', ['cod' => 'Z006', 'vl' => 'TE']],
        [13, 'Talla/Edad', 'E45X -  Desnutrición Crónica T/E (Talla Baja)', ['cod' => 'E45X']],
        [14, 'E785 - Hiperlipidemia No Especificada (Dislipidemia)', '--------------', ['cod' => 'E785']],
        [15, 'E65X - Adiposidad Localizada', '--------------', ['cod' => 'E65X']],
        [16, 'Z728 - Otros Problemas relacionados al Estilo de Vida', '--------------', ['cod' => 'Z728']],
        [17, '85018 - Dosaje de Hemoglobina/85018.01', '--------------', ['cod' => ['85018', '85018.01'], 'vl' => '1']],
        [18, '99199.26 - Suplementación de sulfato ferroso y ácido fólico', '--------------', ['cod' => '99199.26', 'vl' => 'TA']],
    ];
    // Filas del CASE sin filtro I_ROWNUM_LAB (99209.04, Z006, 85018/85018.01, 99199.26)
    $sinRownum = [3, 4, 5, 8, 12, 17, 18];
    $filas04 = [];
    foreach ($evNut as [$k, $g, $ev, $c]) {
        $conRownum = !in_array($k, $sinRownum, true);
        $cond = adoY($e1217, ['tip' => 'D'], $conRownum ? ['rownum' => 1] : [], $c);
        $filas04[] = ['key' => $k, 'label' => $g, 'label2' => $ev, 'fila' => 40 + $k, 'cond' => $cond];
    }
    $secs[] = [
        'codigo'        => 'ADO04_EVALUACION',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_04_EVALUACION',
        'titulo'        => '4. EVALUACIÓN FÍSICA NUTRICIONAL',
        'grupo'         => 'EVAL',
        'layout'        => 'STD',
        'labelCols'     => ['Grupo', 'Evaluación'],
        'cols'          => ['total' => 'E', 'e12' => 'F', 'e15' => 'G'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas04,
    ];

    /* ------------------------------------------------------------
     * 05 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_05_VISUAL
     * EVALUACIÓN DE LA AGUDEZA VISUAL (filas Excel 63-66)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO05_VISUAL',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_05_VISUAL',
        'titulo'        => '5. EVALUACIÓN DE LA AGUDEZA VISUAL',
        'grupo'         => 'EVAL',
        'layout'        => 'STD',
        'labelCols'     => ['Grupo', 'Evaluación'],
        'cols'          => ['total' => 'E', 'e12' => 'F', 'e15' => 'G'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => '99173 - Prueba  de la Agudeza Visual cuantitativa bilateral', 'label2' => '--------------', 'fila' => 63,
             'cond' => adoY($e1217, ['cod' => '99173'], $D)],
            ['key' => 2, 'label' => 'Z010 - Examen de Ojos y de la Visión', 'label2' => 'Normal', 'fila' => 64,
             'cond' => adoY($e1217, ['cod' => 'Z010'], ['tip' => 'D'], ['vl' => 'N'])],
            ['key' => 3, 'label' => 'Z010 - Examen de Ojos y de la Visión', 'label2' => 'Anormal', 'fila' => 65,
             'cond' => adoY($e1217, ['cod' => 'Z010'], ['tip' => 'D'], ['vl' => 'A'])],
            ['key' => 4, 'label' => '99401.16 - Consejerìa ocular', 'label2' => '--------------', 'fila' => 66,
             'cond' => adoY($e1217, ['cod' => '99401.16'], $D)],
        ],
    ];

    /* ------------------------------------------------------------
     * 06 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_06_AUDITIVA
     * EVALUACIÓN DE LA AGUDEZA AUDITIVA (filas Excel 71-73)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO06_AUDITIVA',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_06_AUDITIVA',
        'titulo'        => '6. EVALUACIÓN DE LA AGUDEZA AUDITIVA',
        'grupo'         => 'EVAL',
        'layout'        => 'STD',
        'labelCols'     => ['Evaluación', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => 'Disminución de la Agudeza Auditiva sin Especificación', 'fila' => 71,
             'cond' => adoY($e1217, ['cod' => 'H919'], $D)],
            ['key' => 2, 'label' => 'Examen de Oídos y de la Audición - Normal', 'fila' => 72,
             'cond' => adoY($e1217, ['cod' => 'Z011'], ['tip' => 'D'], ['vl' => 'N'])],
            ['key' => 3, 'label' => 'Examen de Oídos y de la Audición - Anormal', 'fila' => 73,
             'cond' => adoY($e1217, ['cod' => 'Z011'], ['tip' => 'D'], ['vl' => 'A'])],
        ],
    ];

    /* ------------------------------------------------------------
     * 07 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_07_ODONTOLOGICA
     * ATENCIÓN ODONTOLÓGICA (filas Excel 78-82)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO07_ODONTOLOGICA',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_07_ODONTOLOGICA',
        'titulo'        => '7. ATENCIÓN ODONTOLÓGICA',
        'grupo'         => 'EVAL',
        'layout'        => 'STD',
        'labelCols'     => ['Atención', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => 'D1310 - Asesoría Nutricional para el Control de Enfermedades Dentales', 'fila' => 78,
             'cond' => adoY($e1217, ['cod' => 'D1310'], $D)],
            ['key' => 2, 'label' => 'D1330 - Instrucción de Higiene Oral', 'fila' => 79,
             'cond' => adoY($e1217, ['cod' => 'D1330'], $D)],
            ['key' => 3, 'label' => 'D0120 - Examen Estomatológico', 'fila' => 80,
             'cond' => adoY($e1217, ['cod' => 'D0120'], $D)],
            ['key' => 4, 'label' => 'D1110 - Profilaxis Dental', 'fila' => 81,
             'cond' => adoY($e1217, ['cod' => 'D1110'], $D)],
            ['key' => 5, 'label' => 'U510 - Alta Básica Odontológica', 'fila' => 82,
             'cond' => adoY($e1217, ['cod' => 'U510'], $D)],
        ],
    ];

    /* ------------------------------------------------------------
     * 08 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_08_FISICO
     * EVALUACIÓN FÍSICO POSTURAL (filas Excel 87-92)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO08_FISICO',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_08_FISICO',
        'titulo'        => '8. EVALUACIÓN FÍSICO POSTURAL',
        'grupo'         => 'EVAL',
        'layout'        => 'STD',
        'labelCols'     => ['Evaluación', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => '96008 - Análisis Postural Estático (NORMAL)', 'fila' => 87,
             'cond' => adoY($e1217, ['cod' => '96008'], $D)],
            ['key' => 2, 'label' => 'M400 - Cifosis postural', 'fila' => 88,
             'cond' => adoY($e1217, ['cod' => 'M400'], $D)],
            ['key' => 3, 'label' => 'M402 - Otras cifosis y las no especificadas', 'fila' => 89,
             'cond' => adoY($e1217, ['cod' => 'M402'], $D)],
            ['key' => 4, 'label' => 'M403 - Síndrome de espalda plana', 'fila' => 90,
             'cond' => adoY($e1217, ['cod' => 'M403'], $D)],
            ['key' => 5, 'label' => 'M405 - Lordosis no especifica', 'fila' => 91,
             'cond' => adoY($e1217, ['cod' => 'M405'], $D)],
            ['key' => 6, 'label' => 'M419 - Escoliosis, no especificada', 'fila' => 92,
             'cond' => adoY($e1217, ['cod' => 'M419'], $D)],
        ],
    ];

    /* ------------------------------------------------------------
     * 09 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_09_INMUNIZACIONES
     * INMUNIZACIONES (filas Excel 97-106)
     * ------------------------------------------------------------ */
    $inmun = [
        [1,  '90746 -  Vacunación Antihepatitis Viral B (HvB) ==> DOSIS 1', ['cod' => '90746'], ['vl' => '1']],
        [2,  '90746 -  Vacunación Antihepatitis Viral B (HvB) ==> DOSIS 2', ['cod' => '90746'], ['vl' => '2']],
        [3,  '90746 -  Vacunación Antihepatitis Viral B (HvB) ==> DOSIS 3', ['cod' => '90746'], ['vl' => '3']],
        [4,  '90658 -  Vacuna contra la Influenza  (Estacional)',           ['cod' => '90658'], ['rownum' => 1]],
        [5,  '90714 -  Vacunación Diftotetánica (dT) (Incluye varones y gestantes) ======> DOSIS 1', ['cod' => '90714'], ['vl' => '1']],
        [6,  '90714 -  Vacunación Diftotetánica (dT) (Incluye varones y gestantes) ======> DOSIS 2', ['cod' => '90714'], ['vl' => '2']],
        [7,  '90714 -  Vacunación Diftotetánica (dT) (Incluye varones y gestantes) ======> DOSIS 3', ['cod' => '90714'], ['vl' => '3']],
        [8,  '90649 -  Vacuna contra Virus de Papiloma Humano  ======> DOSIS 1', ['cod' => '90649'], ['vl' => '1']],
        [9,  '90649 -  Vacuna contra Virus de Papiloma Humano  ======> DOSIS 2', ['cod' => '90649'], ['vl' => '2']],
        [10, '90717 -  Vacuna Antiamarilica', ['cod' => '90717'], ['rownum' => 1]],
    ];
    $filas09 = [];
    foreach ($inmun as [$k, $lbl, $c1, $c2]) {
        $filas09[] = ['key' => $k, 'label' => $lbl, 'fila' => 96 + $k,
                      'cond' => adoY($e1217, ['tip' => 'D'], $c1, $c2)];
    }
    $secs[] = [
        'codigo'        => 'ADO09_INMUNIZACIONES',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_09_INMUNIZACIONES',
        'titulo'        => '9. INMUNIZACIONES',
        'grupo'         => 'INM',
        'layout'        => 'STD',
        'labelCols'     => ['Vacuna', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas09,
    ];

    /* ------------------------------------------------------------
     * 10 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_10_PLANIFICACION
     * EVALUACIÓN DEL DESARROLLO SEXUAL/ PLANIFICACIÓN FAMILIAR
     * (filas Excel 111-124). Metodos con cantidad (Total = valor_lab);
     * MELA/BILLINGS/RITMO/DIAS FIJO y las filas base cuentan 1.
     * ------------------------------------------------------------ */
    $met = [
        [1,  '99384.02 - Evaluación del desarrollo sexual según Tanner', '--------------', ['cod' => '99384.02'], 'filas'],
        [2,  '99208 - Atención en Planificación Familiar y SSR', '--------------', ['cod' => '99208'], 'filas'],
        [3,  'DIU', '--------------', ['cod' => '58300'], 'total_vl'],
        [4,  'HORMONAL', 'ORAL COMBINADO', ['cod' => '99208.13'], 'total_vl'],
        [5,  'HORMONAL', 'INYECTABLE TRIMESTRAL', ['cod' => '99208.05'], 'total_vl'],
        [6,  'HORMONAL', 'INYECTABLE MENSUAL', ['cod' => '99208.04'], 'total_vl'],
        [7,  'HORMONAL', 'IMPLANTE', ['cod' => '11975'], 'total_vl'],
        [8,  'BARRERA', 'CONDON MASCULINO', ['cod' => '99208.02'], 'total_vl'],
        [9,  'BARRERA', 'CONDON FEMENINO', ['cod' => '99208.06'], 'total_vl'],
        [10, 'MELA', '--------------', ['cod' => '99208.07'], 'filas'],
        [11, 'ABSTINENCIA', 'BILLINGS', ['cod' => '99208.09'], 'filas'],
        [12, 'ABSTINENCIA', 'RITMO', ['cod' => '99208.08'], 'filas'],
        [13, 'ABSTINENCIA', 'DIAS FIJO', ['cod' => '99208.10'], 'filas'],
        [14, 'ADMINISTRACIÓN Y USO AOE/ YUSPE', 'Administración y uso de anticonceptivo oral de emergencia/YUZPE', ['cod' => '99208.11'], 'total_vl'],
    ];
    $filas10 = [];
    foreach ($met as [$k, $g, $at, $c, $regla]) {
        // Temporal1: id_tipitem in ('D','R'); el AOE (99208.11) solo con 'D'
        $tipos = ($k === 14) ? ['tip' => 'D'] : ['tip' => ['D', 'R']];
        $base = ($k <= 2) ? $D : $tipos + ['rownum' => 1];
        $filas10[] = ['key' => $k, 'label' => $g, 'label2' => $at, 'fila' => 110 + $k,
                      'cond' => adoY($e1217, $base, $c), 'regla' => $regla];
    }
    $secs[] = [
        'codigo'        => 'ADO10_PLANIFICACION',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_10_PLANIFICACION',
        'titulo'        => '10. EVALUACIÓN DEL DESARROLLO SEXUAL/ PLANIFICACIÓN FAMILIAR',
        'grupo'         => 'INM',
        'layout'        => 'STD',
        'labelCols'     => ['Grupo', 'Atención'],
        'cols'          => ['total' => 'C', 'e12' => 'D', 'e15' => 'E'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas10,
    ];

    /* ------------------------------------------------------------
     * 11 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_11_CLINICA
     * EVALUACIÓN CLÍNICA (filas Excel 128-144)
     * ------------------------------------------------------------ */
    $clin = [
        'L709 - Acné no especificado', 'D509 - Anemia por Deficiencia de Hierro sin Especificación',
        'O990 - Anemia que afecta al Embarazo, Parto o Puerperio', 'U310 - Administración de Tratamiento (Anemia)',
        'J459 - Asma No Especificada', 'I10X - Hipertensión Esencial Primaria (Hipertensión Arterial)',
        'R51X - Cefalea', 'B829 - Parasitosis Intestinal, sin otra Especificación',
        'N63X - Masa No Especificada en la Mama', 'A084 - Infección Intestinal Viral, Sin Otra Especificación',
        'B373 - Candidiasis de la Vulva y de la Vagina (Candidiasis Vaginal)', 'B853 - Pediculosis del Pubis',
        'B968 - Vaginosis Bacteriana', 'A64X9 - Síndrome de Flujo Vaginal',
        'A64X5 - Síndrome de Dolor Abdominal Bajo', 'A64X6 - Síndrome de Secreción Uretral',
        '99199.11 - Administración de Tratamiento (ITS)',
    ];
    $codClin = ['L709','D509','O990','U310','J459','I10X','R51X','B829','N63X','A084',
                'B373','B853','B968','A64X9','A64X5','A64X6','99199.11'];
    $filas11 = [];
    foreach ($codClin as $i => $c) {
        $filas11[] = ['key' => $i + 1, 'label' => $clin[$i], 'fila' => 127 + $i + 1,
                      'cond' => adoY($e1217, ['cod' => $c], $D)];
    }
    $secs[] = [
        'codigo'        => 'ADO11_CLINICA',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_11_CLINICA',
        'titulo'        => '11. EVALUACIÓN CLÍNICA',
        'grupo'         => 'CLIN',
        'layout'        => 'STD',
        'labelCols'     => ['Evaluación', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas11,
    ];

    /* ------------------------------------------------------------
     * 12 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_12_CONSEJERIAS
     * CONSEJERÍAS (filas Excel 149-161)
     * ------------------------------------------------------------ */
    $consej = [
        '99401 - Consejería Integral',
        '99402.03 - Consejería/Orientación en Salud Sexual y Reproductiva',
        '99402.04 - Orientación/Consejería en Planificación Familiar',
        '99402.09 - Consejería de Prevención en Riesgos de Salud Mental',
        '99401.31 - Consejería en Prevención de Enfermedades No transmisibles (Salud Física)',
        '99403 - Consejería Nutricional',
        '99403.01 - Consejería Nutricional: Alimentación Saludable',
        '99401.33 - Consejería Pre Test para VIH',
        '99401.34 - Consejería Post Test para VIH - Resultado No Reactivo',
        '99403.03 - Consejería Post Test para VIH - Resultado Reactivo',
        '99402.05 - Consejería/Orientación en Prevención de ITS, VIH, Hepatitis',
        '99401.24 - Consejerìa higiene de manos',
        '99401.19 - Consejerìa para el autocuidado',
    ];
    $codConsej = ['99401','99402.03','99402.04','99402.09','99401.31','99403','99403.01',
                  '99401.33','99401.34','99403.03','99402.05','99401.24','99401.19'];
    $filas12 = [];
    foreach ($codConsej as $i => $c) {
        $filas12[] = ['key' => $i + 1, 'label' => $consej[$i], 'fila' => 148 + $i + 1,
                      'cond' => adoY($e1217, ['cod' => $c], $D)];
    }
    $secs[] = [
        'codigo'        => 'ADO12_CONSEJERIAS',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_12_CONSEJERIAS',
        'titulo'        => '12. CONSEJERÍAS',
        'grupo'         => 'CLIN',
        'layout'        => 'STD',
        'labelCols'     => ['Consejería', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas12,
    ];

    /* ------------------------------------------------------------
     * 13 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_13_PRUEBAS
     * TOMA DE PRUEBA RÁPIDA (filas Excel 166-170)
     * ------------------------------------------------------------ */
    $prueb = [
        '86703 - Anticuerpos; HIV-1 y HIV-2, análisis único (Tamizaje de VIH por Prueba Rápida)',
        '86780 - Anticuerpo: Treponema Pallidum(Tamizaje de Sífilis por Prueba Rápida)',
        '86593 - Prueba de Sífilis; anticuerpo no treponémico cuantitativa (Tamizaje de Hepatitis B por Prueba Rápida)',
        '87342 - Tamizaje de Hepatitis B por Prueba Rápida',
        'Z320 - Embarazo No Confirmado: toma de pruebas para el descarte de embarazo en caso la adolescente lo requiera. (examen de orina)',
    ];
    $codPrueb = ['86703','86780','86593','87342','Z320'];
    $filas13 = [];
    foreach ($codPrueb as $i => $c) {
        $filas13[] = ['key' => $i + 1, 'label' => $prueb[$i], 'fila' => 165 + $i + 1,
                      'cond' => adoY($e1217, ['cod' => $c], $D)];
    }
    $secs[] = [
        'codigo'        => 'ADO13_PRUEBAS',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_13_PRUEBAS',
        'titulo'        => '13. TOMA DE PRUEBA RÁPIDA (en caso lo requiera)',
        'grupo'         => 'CLIN',
        'layout'        => 'STD',
        'labelCols'     => ['Prueba', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas13,
    ];

    /* ------------------------------------------------------------
     * 14 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_14_MENTAL
     * SALUD MENTAL (filas Excel 175-183)
     * ------------------------------------------------------------ */
    $mental = [
        [1, '96150 - Entrevista de Tamizaje', '--------------', '96150'],
        [2, 'F419 - Trastorno de ansiedad, no Especificado', '--------------', 'F419'],
        [3, 'Z553 - Problemas Relacionados con el Bajo Rendimiento Escolar', '--------------', 'Z553'],
        [4, 'Z7281 - Riesgos de Lesiones o Accidentes', '--------------', 'Z7281'],
        [5, 'Entrevista de Tamizaje', '96150.01 - Tamizaje de Salud Mental en Violencia', '96150.01'],
        [6, 'Entrevista de Tamizaje', '96150.02 - Tamizaje de Salud Mental en Alcohol y Drogas', '96150.02'],
        [7, 'Entrevista de Tamizaje', '96150.03 - Tamizaje de Salud Mental en Trastornos Depresivos', '96150.03'],
        [8, 'Entrevista de Tamizaje', '96150.04 - Tamizaje de Salud Mental en Psicosis', '96150.04'],
        [9, 'Entrevista de Tamizaje', '96150.05 - Tamizaje de Salud Mental en Habilidades Sociales', '96150.05'],
    ];
    $filas14 = [];
    foreach ($mental as [$k, $g, $at, $c]) {
        $filas14[] = ['key' => $k, 'label' => $g, 'label2' => $at, 'fila' => 174 + $k,
                      'cond' => adoY($e1217, ['cod' => $c], $D)];
    }
    $secs[] = [
        'codigo'        => 'ADO14_MENTAL',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_14_MENTAL',
        'titulo'        => '14. SALUD MENTAL',
        'grupo'         => 'CLIN',
        'layout'        => 'STD',
        'labelCols'     => ['Grupo', 'Atención'],
        'cols'          => ['total' => 'C', 'e12' => 'D', 'e15' => 'E'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas14,
    ];

    /* ------------------------------------------------------------
     * 15 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_15_TAMIZAJES
     * TAMIZAJES POSITIVOS (filas Excel 188-195)
     * ------------------------------------------------------------ */
    $tamiz = [
        'R456 - Problemas relacionados con violencia',
        'Z734 - Problemas Relacionados con Habilidades Sociales Inadecuadas',
        'Z720 - Problemas Relacionados con el Uso de Tabaco',
        'Z721 - Problemas Sociales Relacionados con el Uso de Alcohol',
        'Z722 - Problemas Sociales Relacionados con el Uso de drogas',
        'Z726 - Problemas relacionados con el Juego y las apuestas',
        'Z619 - Problemas Relacionados con Experiencia Negativa no Especificada en la Infancia',
        'Z639 - Otros Problemas Relacionados con el Grupo Primario de Apoyo, Inclusive Circunstancias Familiares (Relaciones Familiares)',
    ];
    $codTamiz = ['R456','Z734','Z720','Z721','Z722','Z726','Z619','Z639'];
    $filas15 = [];
    foreach ($codTamiz as $i => $c) {
        $filas15[] = ['key' => $i + 1, 'label' => $tamiz[$i], 'fila' => 187 + $i + 1,
                      'cond' => adoY($e1217, ['cod' => $c], $D)];
    }
    $secs[] = [
        'codigo'        => 'ADO15_TAMIZAJES',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_15_TAMIZAJES',
        'titulo'        => '15. TAMIZAJES POSITIVOS',
        'grupo'         => 'CLIN',
        'layout'        => 'STD',
        'labelCols'     => ['Tamizaje', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas15,
    ];

    /* ------------------------------------------------------------
     * 16_1 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_16_1_ACTIVIDADES
     * ACTIVIDADES PREVENTIVAS PROMOCIONALES - Sesiones (filas 200-201)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO16_1_SESIONES',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_16_1_ACTIVIDADES',
        'titulo'        => '16. ACTIVIDADES PREVENTIVAS PROMOCIONALES - Sesiones',
        'grupo'         => 'APP',
        'layout'        => 'STD',
        'labelCols'     => ['Sesión', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => 'C0009 - Sesión Educativa', 'fila' => 200,
             'cond' => adoY($e1217, ['cod' => 'C0009'], $D)],
            ['key' => 2, 'label' => 'C2111.02 - Taller en prevención de conducta de riesgo en adolescentes y sus familias - familias fuertes amor y límite', 'fila' => 201,
             'cond' => adoY($e1217, ['cod' => 'C2111.02'], $D)],
        ],
    ];

    /* ------------------------------------------------------------
     * 16_2 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_16_2_ACTIVIDADES
     * Actividades promocionales por ficha familiar APP93..APP150,
     * UPS 302304 (filas Excel 204-212). El SP no filtra edad ni sexo.
     * ------------------------------------------------------------ */
    $app = [
        'APP93 - Actividad en colegios', 'APP100 - Establecimiento de Salud', 'APP104 - Municipalidades',
        'APP136 - Actividades con familia', 'APP141 - Actividades con adolescentes',
        'APP144 - Actividades con docentes', 'APP145 - Actividades con alumnos',
        'APP146 - Actividades con padres', 'APP150 - Actividades con autoridades',
    ];
    $codApp = ['APP93','APP100','APP104','APP136','APP141','APP144','APP145','APP146','APP150'];
    $filas162 = [];
    foreach ($codApp as $i => $fam) {
        $filas162[] = ['key' => $i + 1, 'label' => $app[$i], 'fila' => 203 + $i + 1,
                       'cond' => adoY(['ficha' => $fam], ['rownumItem' => 1], ['ups' => '302304'])];
    }
    $secs[] = [
        'codigo'        => 'ADO16_2_APP',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_16_2_ACTIVIDADES',
        'titulo'        => '16. ACTIVIDADES PREVENTIVAS PROMOCIONALES - Actividades (APP)',
        'grupo'         => 'APP',
        'layout'        => 'CASOS',
        'labelCols'     => ['Actividad', null],
        'cols'          => ['v' => 'B'],
        'conSexo'       => false,   // el SP 16_2 no exige "sexo is not null"
        'ejes'          => [],
        'regla'         => 'filas',
        'colLabel'      => 'Casos',
        'filas'         => $filas162,
    ];

    /* ------------------------------------------------------------
     * 16_3 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_16_3_ACTIVIDADES
     * Actividades comunitarias C1021..C7004, UPS 302304 (filas 215-226).
     * El SP no filtra edad, sexo ni id_tipitem; usa I_ROWNUM_LAB=1.
     * ------------------------------------------------------------ */
    $comun = [
        'C1021 - Organización de Charla para Abogacía y Políticas Públicas',
        'C0007 - Taller para la familia', 'C0010 - Sesión Demostrativa',
        '90872 - Taller de habilidades sociales', 'C0008 - Taller para personal de salud',
        'C2121.01 - Animación Sociocultural', 'C2121 - Teatros Populares',
        'C3152 - Formación de Educadores de Pares', 'C7001 - Monitoreo',
        'C7002 - Supervisión', 'C7003 - Evaluación', 'C7004 - Asistencia Técnica',
    ];
    $codComun = ['C1021','C0007','C0010','90872','C0008','C2121.01','C2121','C3152',
                 'C7001','C7002','C7003','C7004'];
    $filas163 = [];
    foreach ($codComun as $i => $c) {
        $filas163[] = ['key' => $i + 1, 'label' => $comun[$i], 'fila' => 214 + $i + 1,
                       'cond' => adoY(['cod' => $c], $R1, ['ups' => '302304'])];
    }
    $secs[] = [
        'codigo'        => 'ADO16_3_COMUNITARIAS',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_16_3_ACTIVIDADES',
        'titulo'        => '16. ACTIVIDADES PREVENTIVAS PROMOCIONALES - Actividades comunitarias',
        'grupo'         => 'APP',
        'layout'        => 'CASOS',
        'labelCols'     => ['Actividad', null],
        'cols'          => ['v' => 'B'],
        'conSexo'       => false,   // el SP 16_3 no exige "sexo is not null"
        'ejes'          => [],
        'regla'         => 'filas',
        'colLabel'      => 'Casos',
        'filas'         => $filas163,
    ];

    /* ------------------------------------------------------------
     * 17-22 - Sesiones educativas por grupo etareo (C0009 + marcador
     * PSA/SSI/PSI en la fila 2 del item; el numero de sesion es valor_lab
     * de la fila 1). Columna unica B del Excel.
     * ------------------------------------------------------------ */
    $sesionesDef = [
        ['ADO17_SESIONES_NUTRIC_1214', 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_17_NUTRICIONAL',
         '17. SESIONES EDUCATIVAS - SALUD FÍSICA NUTRICIONAL DE 12 A 14 AÑOS', $e1214, $psa, 230,
         ['Actividad Física', 'Alimentación Saludable', 'Higiene', 'Protección solar', 'Salud Bucal',
          'Salud ocular', 'Cuidado del medio ambiente',
          'Medidas de seguridad y prevención de accidentes. Primeros auxilios',
          'Salud respiratoria y tuberculosis: Higiene y salud bucal']],
        ['ADO18_NUTRIC_1517', 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_18_NUTRICIONAL',
         '18. SALUD FÍSICA NUTRICIONAL DE 15 A 17 AÑOS', $e1517, $psa, 243,
         ['Actividad Física y deporte', 'Alimentación Saludable', 'Higiene',
          'Prevención de transtornos posturales', 'Protección solar', 'Salud Bucal', 'Salud ocular',
          'Salud respiratoria y tuberculosis: Higiene y salud bucal', 'Cuidado del medio ambiente',
          'Prevención de enfermedades transmisibles prevalentes: Dengue, malaria, bartonellosis etc.',
          'Medidas de Seguridad y prevención de accidentes',
          'Primeros auxilios. Resucitación cardiopulmonar']],
        ['ADO19_SSR_1214', 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_19_SSR',
         '19. SALUD SEXUAL Y REPRODUCTIVA DE 12 A 14 AÑOS', $e1214, $ssi, 259,
         ['Derechos Sexuales y Reproductivos', 'Sexualidad humana y afectividad',
          'Desarrollo sexual en la adolescencia',
          'Prevención del embarazo no deseado, ITS y VIH-SIDA',
          'Diversidad sexual e identidad de género']],
        ['ADO20_SSR_1517', 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_20_SSR',
         '20. SALUD SEXUAL Y REPRODUCTIVA DE 15 A 17 AÑOS', $e1517, $ssi, 268,
         ['Derechos Sexuales y Reproductivos', 'Sexualidad humana y afectividad',
          'Anticoncepción y paternidad saludable', 'Prevención del embarazo no deseado',
          'Relaciones sexuales, sexo seguro',
          'Infecciones de transmisión sexual, VIH-SIDA, Hepatitis B',
          'Diversidad sexual e identidad de género']],
        ['ADO21_PSICO_1214', 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_21_PSICOSOCIAL',
         '21. SALUD PSICOSOCIAL DE 12 A 14 AÑOS', $e1214, $psi, 279,
         ['Habilidades para la vida (autoestima, asertividad, toma de decisiones, comunicación); cognitivas y de control de emociones.',
          'Proyecto de vida', 'Resiliencia',
          'Rol del adolescente en la familia (derechos y responsabilidades)',
          'Derechos y responsabilidades en salud', 'Equidad de género e interculturalidad',
          'Prevención de la Violencia Familiar (maltrato físico, psicológico, por negligencia), castigo físico y humillante, violencia sexual, violencia por explotación sexual y trata de personas, social (pandillaje, delincuencia, bullying).',
          'Identificación de Signos de Alarma de: depresión, intento de suicidio, ansiedad, adicciones, trastornos de conducta alimentaria (anorexia, bulimia).',
          'Prevención de consumo de tabaco, alcohol, drogas ilícitas y nuevas adicciones (ludopatías, dependencia de las redes sociales, adicción tecnológica).',
          'Masculinidad']],
        ['ADO22_PSICO_1517', 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_22_PSICOSOCIAL',
         '22. SALUD PSICOSOCIAL DE 15 A 17 AÑOS', $e1517, $psi, 293,
         ['Habilidades para la vida (autoestima, asertividad, toma de decisiones, comunicación); cognitivas y de control de emociones.',
          'Resiliencia', 'Proyecto de vida y orientación vocacional',
          'Derechos y responsabilidades en salud', 'Equidad de género e interculturalidad',
          'Empoderamiento. liderazgo. Participación ciudadana',
          'Viviendo en familia: Relaciones intergeneracionales, mejorando la comunicación',
          'Prevención de la Violencia Familiar (maltrato físico, psicológico, por negligencia), castigo físico y humillante, violencia sexual, violencia por explotación sexual y trata de personas, social (pandillaje, delincuencia, bullying).',
          'Identificación de Signos de Alarma de: depresión, intento de suicidio, ansiedad, adicciones, trastornos de conducta alimentaria (anorexia, bulimia).',
          'Prevención de consumo de tabaco, alcohol, drogas ilícitas y nuevas adicciones (ludopatías, dependencia de las redes sociales, adicción tecnológica).',
          'Medios de Comunicación y sus Riesgos.', 'Masculinidad']],
    ];
    foreach ($sesionesDef as [$cod, $proc, $tit, $edad, $marc, $fila0, $labels]) {
        $fr = [];
        foreach ($labels as $i => $lbl) {
            $fr[] = ['key' => $i + 1, 'label' => $lbl, 'fila' => $fila0 + $i + 1,
                     'cond' => adoY($edad, ['cod' => 'C0009'], $D, ['vl' => (string)($i + 1)],
                                    ['citaTiene' => $marc])];
        }
        $secs[] = [
            'codigo'        => $cod,
            'procedimiento' => $proc,
            'titulo'        => $tit,
            'grupo'         => 'APP',
            'layout'        => 'CASOS',
            'labelCols'     => ['Sesión', null],
            'cols'          => ['v' => 'B'],
            'ejes'          => [],
            'regla'         => 'filas',
            'colLabel'      => (strpos($tit, '12 A 14') !== false) ? '12a -14a' : '15a - 17a',
            'filas'         => $fr,
        ];
    }

    /* ------------------------------------------------------------
     * 23 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_23_COVID19
     * COVID - 19 (filas Excel 310-319)
     * ------------------------------------------------------------ */
    $covid = [
        [1, '87635 - Prueba Molecular PCR', ['cod' => '87635']],
        [2, '87635.01 - Prueba Rápida Serológica', ['cod' => '87635.01']],
        [3, 'U071 - Virus identificado', ['cod' => 'U071']],
        [4, 'U072 - Virus no identificado', ['cod' => 'U072']],
        [5, 'R05 - Tos', ['codF' => 'R05']],
        [6, 'R060 - Falta de aire / Disnea', ['cod' => 'R060']],
        [7, 'R070 - Dolor de garganta', ['cod' => 'R070']],
        [8, 'R509 - Fiebre, no especificada', ['cod' => 'R509']],
        [9, 'Z208 - Contacto con y exposición a otras enfermedades transmisibles', ['cod' => 'Z208']],
        [10, 'Z038 - Observación de otras enfermedades y afecciones sospechosas.', ['cod' => 'Z038']],
    ];
    $filas23 = [];
    foreach ($covid as [$k, $lbl, $c]) {
        $filas23[] = ['key' => $k, 'label' => $lbl, 'fila' => 309 + $k,
                      'cond' => adoY($e1217, $D, $c)];
    }
    $secs[] = [
        'codigo'        => 'ADO23_COVID19',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_23_COVID19',
        'titulo'        => '23. COVID - 19',
        'grupo'         => 'OTROS',
        'layout'        => 'STD',
        'labelCols'     => ['Diagnóstico', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas23,
    ];

    /* ------------------------------------------------------------
     * 24 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_24_SEGUIMIENTO
     * SEGUIMIENTO DE COVID-19 (filas Excel 324-329)
     * ------------------------------------------------------------ */
    $segui = [
        'C0011 - Visita integral familiar', '99499.08 - Teleorientación síncrona',
        '99499.09 - Teleorientación asíncrona', '99499.10 - Telemonitoreo',
        '99499.11 - Teleinterconsulta síncrona', '99499 - Teleconsulta',
    ];
    $codSegui = ['C0011','99499.08','99499.09','99499.10','99499.11','99499.01'];
    $filas24 = [];
    foreach ($codSegui as $i => $c) {
        $filas24[] = ['key' => $i + 1, 'label' => $segui[$i], 'fila' => 323 + $i + 1,
                      'cond' => adoY($e1217, ['cod' => $c], $D)];
    }
    $secs[] = [
        'codigo'        => 'ADO24_SEGUIMIENTO',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_24_SEGUIMIENTO',
        'titulo'        => '24. SEGUIMIENTO DE COVID - 19 O CUALQUIER OTRA ACTIVIDAD O ENFERMEDAD',
        'grupo'         => 'OTROS',
        'layout'        => 'STD',
        'labelCols'     => ['Seguimiento', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => $filas24,
    ];

    /* ------------------------------------------------------------
     * 25 - usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_25_ALTERNATIVA
     * MEDICINA ALTERNATIVA Y COMPLEMENTARIA (filas Excel 334-337)
     * La fila "Medicina cuerpo mente" cuenta TODAS las primeras filas
     * (rownum=1) de las citas del UPS 301903 en adolescentes 12-17.
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo'        => 'ADO25_ALTERNATIVA',
        'procedimiento' => 'usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_25_ALTERNATIVA',
        'titulo'        => '25. CÓDIGOS PARA LA ATENCIÓN EN MEDICINA ALTERNATIVA Y COMPLEMENTARIA',
        'grupo'         => 'OTROS',
        'layout'        => 'STD',
        'labelCols'     => ['Actividad', null],
        'cols'          => ['total' => 'B', 'e12' => 'C', 'e15' => 'D'],
        'ejes'          => ['edadG'],
        'regla'         => 'filas',
        'filas'         => [
            ['key' => 1, 'label' => 'U0080 - Actividades de medicina alternativa y complementaria', 'fila' => 334,
             'cond' => adoY($e1217, ['cod' => 'U0080'], $D)],
            ['key' => 2, 'label' => '99401.32 - Consejería en medicina alternativa y complementaria', 'fila' => 335,
             'cond' => adoY($e1217, ['cod' => '99401.32'], $D)],
            ['key' => 3, 'label' => 'Medicina cuerpo mente', 'fila' => 336,
             'cond' => adoY($e1217, ['ups' => '301903'], ['rownumItem' => 1])],
            ['key' => 4, 'label' => 'C2081 - Difusión a través de materiales impresos y magnéticos (volantes, tripticos, afiches, gigantografías)', 'fila' => 337,
             'cond' => adoY($e1217, ['cod' => 'C2081'], $D)],
        ],
    ];

    return $secs;
}


/* ============================================================
 * 4) ACCESO A LA BASE DE DATOS (mismo patron que ITS/Zoonosis/Materno)
 * ============================================================ */

/** ¿Existe la columna generada Mes_Int en la tabla consolidada? */
function adoTieneColumnaMesInt(PDO $pdo): bool {
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
 * Codigos de item que intervienen en el reporte ADOLESCENTE segun los 25
 * procedimientos del archivo "03 Creacion de Procedimientos":
 *   'exactos'  -> codigos usados con cod_item = (WHERE directo)
 *   'prefijos' -> familias CIE (cod_item_f) usadas por el SP 01/23:
 *                 A37/J90/J86/J18/J11/J13/J29/J16/J46 (IRA complicadas),
 *                 J00/J01 (IRA no complicadas), A05/A07/A00 (EDA),
 *                 E86 (desnutricion: signo de complicacion EDA), R05 (tos)
 *   'ups'      -> UPS sin filtro de codigo: 302304 (actividades
 *                 promocionales, SP 16_2/16_3) y 301903 (medicina
 *                 alternativa, SP 25 "Medicina cuerpo mente")
 */
function adoCodigosInteres(): array {
    return [
        'exactos' => [
            // ---- 01: morbilidad IRA / EDA ----
            'J180','J050','J051','J851','J939','J188','J189','J181','J229','J450','J459','J448','J449',
            'J020','J0309','H660','H669','J028','J029','J040','J041','J042','J069','J209',
            'A018','A011','A013','A029','A020','A080','A082','A083','A084','A049','A030','A039',
            'A045','A060','A062','A099','R571',
            // ---- 02: control ----
            'C8002','99801','Z003','99384',
            // ---- 03: visita ----
            'C0011','99509',
            // ---- 04: evaluacion nutricional ----
            'Z000','Z019','99209.04','E660','E669','Z006','E440','E43X','E344X','E45X',
            'E785','E65X','Z728','85018','85018.01','99199.26',
            // ---- 05/06: visual / auditiva ----
            '99173','Z010','99401.16','H919','Z011',
            // ---- 07/08: odontologica / fisico postural ----
            'D1310','D1330','D0120','D1110','U510','96008','M400','M402','M403','M405','M419',
            // ---- 09: inmunizaciones ----
            '90746','90658','90714','90649','90717',
            // ---- 10: planificacion familiar ----
            '99384.02','99208','58300','99208.13','99208.05','99208.04','11975',
            '99208.02','99208.06','99208.07','99208.09','99208.08','99208.10','99208.11',
            // ---- 11: clinica ----
            'L709','D509','O990','U310','J459','I10X','R51X','B829','N63X','A084',
            'B373','B853','B968','A64X9','A64X5','A64X6','99199.11',
            // ---- 12: consejerias ----
            '99401','99402.03','99402.04','99402.09','99401.31','99403','99403.01',
            '99401.33','99401.34','99403.03','99402.05','99401.24','99401.19',
            // ---- 13: pruebas rapidas ----
            '86703','86780','86593','87342','Z320',
            // ---- 14: salud mental ----
            '96150','F419','Z553','Z7281','96150.01','96150.02','96150.03','96150.04','96150.05',
            // ---- 15: tamizajes ----
            'R456','Z734','Z720','Z721','Z722','Z726','Z619','Z639',
            // ---- 16_1/17-22: sesiones educativas ----
            'C0009','C2111.02',
            // ---- 16_3: actividades comunitarias ----
            'C1021','C0007','C0010','90872','C0008','C2121.01','C2121','C3152',
            'C7001','C7002','C7003','C7004',
            // ---- 23/24: covid / seguimiento ----
            '87635','87635.01','U071','U072','R060','R070','R509','Z208','Z038',
            '99499.08','99499.09','99499.10','99499.11','99499.01',
            // ---- 25: medicina alternativa ----
            'U0080','99401.32','C2081',
        ],
        'prefijos' => [
            'A00','A05','A07',   // familias CIE EDA (cod_item_f del T-SQL)
            'A37','J90','J86','J18','J11','J13','J29','J16','J46',  // IRA complicadas
            'J00','J01',         // IRA no complicadas
            'E86',               // desnutricion (complicacion EDA, #EDA_COMP)
            'R05',               // tos (familia del SP 23)
        ],
        'ups' => [
            '302304',            // actividades promocionales (SP 16_2 / 16_3)
            '301903',            // medicina alternativa (SP 25, medicina cuerpo mente)
        ],
    ];
}

/** Construye la clausula WHERE de codigos/UPS de interes. */
function adoWhereCodigos(array &$params): string {
    $lista = adoCodigosInteres();
    $partes = [];
    $inPh = [];
    foreach ($lista['exactos'] as $j => $c) {
        $k = ":adocod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "TRIM(Codigo_Item) IN (" . implode(',', $inPh) . ")";
    foreach ($lista['prefijos'] as $i => $pref) {
        $k = ":adopref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "TRIM(Codigo_Item) LIKE $k";
    }
    $inUps = [];
    foreach ($lista['ups'] as $i => $u) {
        $k = ":adoups_$i";
        $params[$k] = $u;
        $inUps[] = $k;
    }
    if ($inUps) $partes[] = "TRIM(Id_Ups) IN (" . implode(',', $inUps) . ")";
    return "(" . implode(" OR ", $partes) . ")";
}

/** Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES). */
function adoGetEstablecimientosZS(PDO $pdo): array {
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
function adoWhereEstablecimientos(PDO $pdo, string $establecimiento, array &$params): string {
    $establecimiento = trim($establecimiento);
    if ($establecimiento !== '') {
        $params[':adoest'] = $establecimiento;
        return "TRIM(Codigo_Unico) = :adoest";
    }
    $lista = adoGetEstablecimientosZS($pdo);
    if (empty($lista)) {
        return '';
    }
    $ph = [];
    $i = 0;
    foreach (array_keys($lista) as $cod) {
        $k = ':adoestLista_' . $i;
        $params[$k] = $cod;
        $ph[] = $k;
        $i++;
    }
    return "TRIM(Codigo_Unico) IN (" . implode(',', $ph) . ")";
}

/** Anios disponibles en la tabla consolidada DENTRO DEL AMBITO del filtro. */
function adoGetAniosDisponibles(PDO $pdo, string $establecimiento = ''): array {
    try {
        $params = [];
        $whereEst = adoWhereEstablecimientos($pdo, $establecimiento, $params);
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
function adoGetMesesDisponibles(PDO $pdo, string $anio = '', string $establecimiento = ''): array {
    try {
        $params = [];
        $cond = [];
        if (trim($anio) !== '') {
            $cond[] = "Anio = :anio";
            $params[':anio'] = (string)$anio;
        }
        $whereEst = adoWhereEstablecimientos($pdo, $establecimiento, $params);
        if ($whereEst !== '') $cond[] = $whereEst;
        $colMes = adoTieneColumnaMesInt($pdo) ? 'Mes_Int' : "CAST(TRIM(Mes) AS UNSIGNED)";
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
function adoAbrirConexionStreaming(): ?PDO {
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
function adoDiagnosticoEntorno(PDO $pdo): array {
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
 * 5) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/** Grupo etareo del reporte: 1 = 12a-14a, 2 = 15a-17a (e12_14/e15_17). */
function adoEdadG(array $f): ?int {
    if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
    if ($f['edad'] >= 12 && $f['edad'] <= 14) return 1;
    if ($f['edad'] >= 15 && $f['edad'] <= 17) return 2;
    return null;
}

/**
 * Cuenta/suma las filas que cumplen una condicion aplicando la regla de
 * conteo del T-SQL y agregando por los ejes de la seccion:
 *   'filas'    : cada fila HIS que cumple aporta 1 (sum iif(edad..,1,0)).
 *   'total_vl' : aporta Total = iif(TRY_CONVERT(int,valor_lab) IS NULL, 1,
 *                TRY_CONVERT(int,valor_lab)) (SP 10; las claves 10..13 van
 *                con regla 'filas' porque el SP las fuerza a 1).
 *
 * El nominal del T-SQL exige "sexo is not null" en todas las secciones
 * excepto 16_2/16_3 (sus SP no filtran sexo): el flag $ctx['conSexo']
 * controla esa exclusion.
 *
 * Ejes: 'edadG' (1=12a-14a, 2=15a-17a). El eje 'cond' no entra en la clave
 * (el caller ya indexa por combo), igual que ITS.
 *
 * @return array ['vals' => <por eje>, 'total' => int]
 */
function adoContar(?array $cond, array $ctx, string $condKey = ''): array {
    if ($cond === null) return ['vals' => [], 'total' => 0];
    $ejes = $ctx['ejes'];
    $regla = $ctx['regla'];
    $conSexo = $ctx['conSexo'] ?? true;
    $vals = [];
    $total = 0;
    $candidatos = adoCandidatosFila($cond, $ctx);

    foreach ($candidatos as $idx) {
        $f = $ctx['filas'][$idx];
        if (!adoCumple($f, $cond, $ctx)) continue;
        // "sexo is not null" del nominal (excepto 16_2/16_3)
        if ($conSexo && $f['sexo'] === '') continue;

        // Aporte de la fila segun la regla de conteo
        if ($regla === 'total_vl') {
            $aporte = ($f['vl'] !== null && $f['vl'] !== '' && is_numeric($f['vl']))
                        ? (int)$f['vl'] : 1;
        } else {
            $aporte = 1;
        }

        // Ruta de la celda segun los ejes de la seccion
        $ruta = [];
        $ok = true;
        foreach ($ejes as $eje) {
            switch ($eje) {
                case 'cond':     break;
                case 'edadG':
                    $g = adoEdadG($f);
                    if ($g === null) { $ok = false; break 2; }
                    $ruta[] = $g; break;
            }
        }
        if (!$ok) continue;

        $key = implode('|', $ruta);
        $vals[$key] = ($vals[$key] ?? 0) + $aporte;
        $total += $aporte;
    }
    return ['vals' => $vals, 'total' => $total];
}

/**
 * Lee un valor del mapa plano de celdas devuelto por adoContar.
 * Ejemplo: adoVal($vals, 1) lee $vals['1'] (12a-14a).
 */
function adoVal(array $vals, ...$keys): int {
    $key = implode('|', $keys);
    return (int)($vals[$key] ?? 0);
}

/**
 * Indices candidatos de TODAS las filas para un predicado, por buckets de
 * codigo/ficha/UPS (sobre-aproximacion; adoCumple verifica exacto).
 */
function adoCandidatosFila(array $cond, array $ctx): array {
    $total = count($ctx['filas']);
    if ($total <= 0) return [];
    $tieneCod  = isset($cond['cod']) || isset($cond['codF']);
    $tieneCual = isset($cond['cualquieraDe']);
    if (!$tieneCod && !$tieneCual && !isset($cond['ficha']) && !isset($cond['ups'])) {
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
                foreach (adoCandidatosFila($sub, $ctx) as $idx) $out[$idx] = true;
            }
        }
        return array_keys($out);
    }
    if (isset($cond['ficha'])) {
        foreach ((array)$cond['ficha'] as $fa) {
            foreach ($ctx['porFicha'][strtoupper((string)$fa)] ?? [] as $idx) $out[$idx] = true;
        }
        if ($out) return array_keys($out);
    }
    if (isset($cond['ups'])) {
        foreach ((array)$cond['ups'] as $u) {
            foreach ($ctx['porUps'][strtoupper((string)$u)] ?? [] as $idx) $out[$idx] = true;
        }
    }
    return array_keys($out);
}

/**
 * Ejecuta el reporte ADOLESCENTE completo (adaptacion de los 25
 * procedimientos del archivo "03 Creacion de Procedimientos").
 *
 * Estrategia (igual que ESNI/Cancer/Materno/Zoonosis/NT/ITS): UNA sola
 * consulta que trae todas las filas HIS de los codigos/UPS de interes con
 * los filtros comunes aplicados, y el matching de cada fila del reporte se
 * resuelve en PHP con el DSL (que espeja los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function adoEjecutarReporte(PDO $pdo, array $filtros): array {
    $t0 = microtime(true);
    $tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';

    // ---- 0) Proteccion contra HTTP 500 en hostings compartidos ----
    if (function_exists('set_time_limit')) @set_time_limit(0);
    @ini_set('memory_limit', '512M');
    $MAX_FILAS = 2000000;

    // ---- 1) Clausula WHERE comun (filtros del usuario) ----
    // Se admiten dos formas de filtrar el periodo:
    //   'anio'/'mes'          : las amigables del formulario (valor vacio = sin filtro)
    //   'anioRaw'/'mesRaw'    : las exactas de los TROZOS del asistente (soportan
    //                           '' y null: Anio/Mes vacios o NULL de la tabla)
    //   'mesFuera'            : filas con Mes fuera de 1..12 o NULL (complemento)
    $where = ["1=1"];
    $params = [];
    $colMesQ = adoTieneColumnaMesInt($pdo) ? 'Mes_Int' : 'CAST(TRIM(Mes) AS UNSIGNED)';
    if (!empty($filtros['anioNonNull'])) {
        // solo trozos del plan: filas con Anio NO NULL (complemento exacto)
        $where[] = "Anio IS NOT NULL";
    } elseif (array_key_exists('anioRaw', $filtros)) {
        if ($filtros['anioRaw'] === null) {
            $where[] = "Anio IS NULL";
        } else {
            $where[] = "Anio = :anioRaw";
            $params[':anioRaw'] = (string)$filtros['anioRaw'];
        }
    } elseif (!empty($filtros['anio'])) {
        $where[] = "Anio = :anio";
        $params[':anio'] = (string)$filtros['anio'];
    }
    if (!empty($filtros['mesFuera'])) {
        $where[] = "({$colMesQ} IS NULL OR {$colMesQ} NOT BETWEEN 1 AND 12)";
    } elseif (array_key_exists('mesRaw', $filtros)) {
        if ($filtros['mesRaw'] === null) {
            $where[] = "{$colMesQ} IS NULL";
        } else {
            $where[] = "{$colMesQ} = :mesRaw";
            $params[':mesRaw'] = $filtros['mesRaw'];
        }
    } elseif (!empty($filtros['mes'])) {
        $where[] = "{$colMesQ} = :mes";
        $params[':mes'] = intval($filtros['mes']);
    }
    $whereEst = adoWhereEstablecimientos($pdo, (string)($filtros['establecimiento'] ?? ''), $params);
    if ($whereEst !== '') {
        $where[] = $whereEst;
    }

    // ---- 2) Codigos / UPS de interes del paquete ADOLESCENTE ----
    $whereCod = adoWhereCodigos($params);

    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab,
                   Id_Correlativo_Item, Id_Correlativo_Lab,
                   Ficha_Familiar, Id_Ups, Codigo_Unico, Anio, Mes
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    $pdoStream = adoAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = adoFila($r);
            if ($f['cod'] === '' && $f['ficha'] === '' && $f['ups'] === '') continue;
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

    return adoEjecutarDesdeFilas($filas, $t0);
}

/**
 * Ejecuta el pipeline de matching/agregacion del reporte a partir de las
 * filas HIS YA normalizadas (adoFila). Separada de adoEjecutarReporte para
 * poder validar el pipeline sin base de datos.
 */
function adoEjecutarDesdeFilas(array $filas, float $t0 = 0.0): array {
    if ($t0 === 0.0) $t0 = microtime(true);

    // Indices por cita (cita/cod/codF) y buckets globales (cod/codF/ficha/ups)
    $porCita = [];
    $porCitaCod = [];
    $porCitaCodF = [];
    $porCod = [];
    $porCodF = [];
    $porFicha = [];
    $porUps = [];
    foreach ($filas as $idx => $f) {
        if ($f['cita'] !== '') {
            $porCita[$f['cita']][] = $idx;
            if ($f['cod'] !== '') $porCitaCod[$f['cita']][$f['cod']][] = $idx;
            if ($f['codF'] !== '') $porCitaCodF[$f['cita']][$f['codF']][] = $idx;
        }
        if ($f['cod'] !== '') $porCod[$f['cod']][] = $idx;
        if ($f['codF'] !== '') $porCodF[$f['codF']][] = $idx;
        if ($f['ficha'] !== '') $porFicha[$f['ficha']][] = $idx;
        if ($f['ups'] !== '') $porUps[$f['ups']][] = $idx;
    }
    $ctx = [
        'filas' => $filas, 'porCita' => $porCita,
        'porCitaCod' => $porCitaCod, 'porCitaCodF' => $porCitaCodF,
        'porCod' => $porCod, 'porCodF' => $porCodF,
        'porFicha' => $porFicha, 'porUps' => $porUps,
    ];

    // ---- Matching por seccion ----
    $seccionesOut = [];
    $totCasos = 0;
    $totPorGrupo = [];

    foreach (adoSecciones() as $sec) {
        $ctxSec = $ctx + [
            'ejes'    => $sec['ejes'],
            'conSexo' => $sec['conSexo'] ?? true,
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
                $r = adoContar($cond, $ctxFila, (string)$condKey);
                if ($condKey !== '') $vals[$condKey] = $r['vals'];
                else $vals = $r['vals'];
                $totFila += $r['total'];
            }
            $filasOut[] = [
                'key' => $fr['key'], 'label' => $fr['label'],
                'label2' => $fr['label2'] ?? null, 'fila' => $fr['fila'] ?? null,
                'vals' => $vals, 'total' => $totFila,
                'cond' => $fr['cond'] ?? null, 'conds' => $fr['conds'] ?? null,
                'regla' => $regla,
            ];
            $totalSec += $totFila;
        }

        $secOut['filas'] = $filasOut;
        $secOut['total'] = $totalSec;
        $totCasos += $totalSec;
        $g = $sec['grupo'];
        $totPorGrupo[$g] = ($totPorGrupo[$g] ?? 0) + $totalSec;
        $seccionesOut[] = $secOut;
    }

    return [
        'secciones' => $seccionesOut,
        'totales'   => [
            'total_casos' => $totCasos,
            'secciones_con_datos' => count(array_filter($seccionesOut, fn($s) => ($s['total'] ?? 0) > 0)),
            'grupos' => $totPorGrupo,
        ],
        'version'          => ADO_DATA_VERSION,
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}

/* ============================================================
 * 6) GENERACION POR TROZOS (hosting con limites: 512M / 60s)
 * ============================================================
 *
 * PROBLEMA: con filtros amplios ("Todos" los anios/meses/EE.SS) la consulta
 * unica de adoEjecutarReporte devuelve cientos de miles de filas y PHP agota
 * memory_limit (512M) o max_execution_time (60s) en InfinityFree -> error
 * fatal (el famoso "No se pudo generar el reporte ADOLESCENTE").
 *
 * SOLUCION: dividir la generacion en TROZOS pequenos (un anio-mes, o un
 * anio-mes-EE.SS), ejecutarlos en peticiones HTTP separadas (asistente con
 * barra de progreso) y acumular los parciales en la tabla de cache
 * ADOLESCENTE_RPT_CACHE. La union de los trozos es EXACTAMENTE igual al
 * resultado de la consulta unica porque:
 *   - Todas las condiciones que cruzan filas (citaTiene / citaSin de los SP)
 *     se resuelven DENTRO de una misma cita, y una cita vive completa en un
 *     unico (Anio, Mes, Establecimiento).
 *   - adoContar es puramente aditivo por fila: sumar por partes = sumar todo.
 *
 * Flujo (reporte_adolescente.php):
 *   ajax=plan       -> decide modo directo/trozos y guarda el plan en cache
 *   ajax=trozo      -> ejecuta el trozo i y lo suma al acumulador
 *   ajax=dividir    -> auto-reparacion: subdivide un trozo que agoto limites
 *   ajax=resultado  -> URL con token para renderizar el reporte terminado
 */

/** Umbral: filas TOTALES por (anio, mes) antes de subdividir por EE.SS. */
define('ADO_TROZO_MAX_FILAS', 400000);

/** Nombre de la tabla de cache/buffer del asistente. */
function adoTablaCache(): string {
    return 'ADOLESCENTE_RPT_CACHE';
}

/** Crea la tabla de cache si no existe (idempotente, sin install previo). */
function adoCacheCrearTablaSiFalta(PDO $pdo): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `" . adoTablaCache() . "` (
            token        VARCHAR(40)  NOT NULL,
            id_usuario   INT          NOT NULL,
            cache_key    VARCHAR(32)  NOT NULL,
            filtros      TEXT         NOT NULL,
            plan         LONGTEXT     NOT NULL,
            payload      LONGTEXT     NULL,
            hechos       INT          NOT NULL DEFAULT 0,
            total        INT          NOT NULL DEFAULT 0,
            estado       VARCHAR(8)   NOT NULL DEFAULT 'curso',
            creado       DATETIME     NOT NULL,
            actualizado  DATETIME     NOT NULL,
            PRIMARY KEY (token),
            INDEX idx_ado_cache_key (cache_key),
            INDEX idx_ado_cache_creado (creado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }
    return $ok;
}

/** Limpieza oportunista de buffers viejos (> 24 h). */
function adoCacheLimpiar(PDO $pdo): void {
    try {
        $pdo->exec("DELETE FROM `" . adoTablaCache() . "`
                    WHERE creado < (NOW() - INTERVAL 1 DAY)");
    } catch (Throwable $e) { /* tabla ausente: la crea plan */ }
}

/** Inserta un buffer nuevo y devuelve su token (null si fallo). */
function adoCacheNuevo(PDO $pdo, array $filtros, array $trozos, int $idUsuario, string $cacheKey): ?string {
    if (!adoCacheCrearTablaSiFalta($pdo)) return null;
    try {
        // higiene: descartar intentos abandonados del mismo usuario/filtros
        $st = $pdo->prepare("DELETE FROM `" . adoTablaCache() . "`
                             WHERE cache_key = :ck AND id_usuario = :idu AND estado = 'curso'");
        $st->execute([':ck' => $cacheKey, ':idu' => $idUsuario]);

        $token = bin2hex(random_bytes(16));
        $now   = date('Y-m-d H:i:s');
        $st = $pdo->prepare("INSERT INTO `" . adoTablaCache() . "`
                (token, id_usuario, cache_key, filtros, plan, payload, hechos, total, estado, creado, actualizado)
                VALUES (:token, :idu, :ck, :filtros, :plan, NULL, 0, :total, 'curso', :now, :now)");
        $st->execute([
            ':token'  => $token,
            ':idu'    => $idUsuario,
            ':ck'     => $cacheKey,
            ':filtros'=> json_encode($filtros, JSON_UNESCAPED_UNICODE),
            ':plan'   => json_encode($trozos, JSON_UNESCAPED_UNICODE),
            ':total'  => count($trozos),
            ':now'    => $now,
        ]);
        return $token;
    } catch (Throwable $e) {
        return null;
    }
}

/** Lee un buffer por token (null si no existe / token invalido). */
function adoCacheLeer(PDO $pdo, string $token): ?array {
    $token = trim($token);
    if (!preg_match('/^[0-9a-f]{32}$/', $token)) return null;
    try {
        $st = $pdo->prepare("SELECT * FROM `" . adoTablaCache() . "` WHERE token = :t");
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Busca una generacion IDENTICA ya terminada (< 12 h) para reutilizarla. */
function adoCacheBuscarListo(PDO $pdo, string $cacheKey, int $idUsuario): ?array {
    if (!adoCacheCrearTablaSiFalta($pdo)) return null;
    try {
        $st = $pdo->prepare("SELECT * FROM `" . adoTablaCache() . "`
                WHERE cache_key = :ck AND id_usuario = :idu AND estado = 'listo'
                  AND actualizado >= (NOW() - INTERVAL 12 HOUR)
                ORDER BY actualizado DESC LIMIT 1");
        $st->execute([':ck' => $cacheKey, ':idu' => $idUsuario]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Persiste el acumulador y el avance (estado 'listo' al terminar). */
function adoCacheGuardarAvance(PDO $pdo, string $token, string $payloadJson, int $hechos, int $total): bool {
    try {
        $st = $pdo->prepare("UPDATE `" . adoTablaCache() . "`
                SET payload = :payload, hechos = :hechos, estado = :estado, actualizado = :now
                WHERE token = :token");
        $st->execute([
            ':payload' => $payloadJson,
            ':hechos'  => $hechos,
            ':estado'  => ($hechos >= $total) ? 'listo' : 'curso',
            ':now'     => date('Y-m-d H:i:s'),
            ':token'   => $token,
        ]);
        return $st->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Reemplaza el plan por una version donde el trozo $i queda subdividido por
 * establecimiento (auto-reparacion cuando un trozo agoto memoria/tiempo).
 * Devuelve ['ok'=>bool, 'total'=>n, 'mensaje'=>s].
 */
function adoCacheDividirTrozo(PDO $pdo, string $token, int $i): array {
    $row = adoCacheLeer($pdo, $token);
    if (!$row) return ['ok' => false, 'mensaje' => 'Buffer de generación no encontrado (¿caducó?). Vuelva a generar.'];
    $trozos = json_decode((string)$row['plan'], true);
    if (!is_array($trozos) || !isset($trozos[$i])) {
        return ['ok' => false, 'mensaje' => 'Trozo inválido.'];
    }
    if ((int)$row['hechos'] !== $i) {
        return ['ok' => false, 'mensaje' => 'Solo se puede dividir el trozo en curso.'];
    }
    $t = $trozos[$i];
    if (trim((string)($t['establecimiento'] ?? '')) !== '') {
        return ['ok' => false, 'mensaje' => 'El trozo ya está acotado a un establecimiento; no se puede subdividir más. Acote los filtros (mes y/o establecimiento).'];
    }
    $lista = adoGetEstablecimientosZS($pdo);
    if (empty($lista)) {
        return ['ok' => false, 'mensaje' => 'No hay catálogo ZSPERENE para subdividir por establecimiento.'];
    }
    $sub = [];
    foreach ($lista as $cod => $nom) {
        $u = $t;
        $u['establecimiento'] = $cod;
        $u['etiqueta'] = ($t['etiqueta'] ?? '') . ' — ' . $nom;
        $sub[] = $u;
    }
    array_splice($trozos, $i, 1, $sub);
    try {
        $st = $pdo->prepare("UPDATE `" . adoTablaCache() . "`
                SET plan = :plan, total = :total, actualizado = :now
                WHERE token = :token");
        $st->execute([
            ':plan'  => json_encode($trozos, JSON_UNESCAPED_UNICODE),
            ':total' => count($trozos),
            ':now'   => date('Y-m-d H:i:s'),
            ':token' => $token,
        ]);
        return ['ok' => true, 'total' => count($trozos), 'mensaje' => 'Trozo subdividido en ' . count($sub) . ' establecimientos.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'mensaje' => 'No se pudo actualizar el plan: ' . $e->getMessage()];
    }
}

/**
 * ¿El ambito cabe en UNA consulta directa (sin asistente)?
 *   - un anio y un mes (con o sin EE.SS), o
 *   - un anio de un solo establecimiento.
 * En esos casos el volumen de filas es pequeno y no hace falta trocear.
 */
function adoAmbitoDirecto(array $filtros): bool {
    $anio = trim((string)($filtros['anio'] ?? ''));
    $mes  = trim((string)($filtros['mes'] ?? ''));
    $est  = trim((string)($filtros['establecimiento'] ?? ''));
    return ($anio !== '' && $mes !== '') || ($anio !== '' && $est !== '');
}

/**
 * Construye el plan de trozos para un ambito amplio.
 *
 * Con columna Mes_Int: una sola pasada GROUP BY (Anio, Mes_Int) — indexada
 * por idx_ado_anio_mesint, cubre TODOS los grupos existentes (incluidos
 * Anio NULL/'', Mes NULL/0/13) => la union de trozos es exacta.
 * Sin Mes_Int (o si falla): anios disponibles x meses 1..12 + trozos de
 * complemento (Anio NULL, Mes fuera de 1..12).
 *
 * Si un (anio, mes) supera ADO_TROZO_MAX_FILAS filas TOTALES, se subdivide
 * en un trozo por establecimiento del catalogo ZSPERENE.
 *
 * @return array ['trozos'=>[...], 'conConteo'=>bool, 'filas_estimadas'=>?int, 'indices'=>bool]
 */
function adoPlanTrozos(PDO $pdo, array $filtros): array {
    $tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';
    $est   = trim((string)($filtros['establecimiento'] ?? ''));
    $fAnio = trim((string)($filtros['anio'] ?? ''));

    $out = ['trozos' => [], 'conConteo' => false, 'filas_estimadas' => null, 'indices' => true];

    // Indices presentes? (SHOW INDEX es metadata pura: instantaneo)
    try {
        $n = 0;
        foreach ($pdo->query("SHOW INDEX FROM {$tabla}") as $r) { $n++; }
        $out['indices'] = ($n > 0);
    } catch (Throwable $e) { $out['indices'] = true; }

    $nombresEst = adoGetEstablecimientosZS($pdo);
    $colMes     = adoTieneColumnaMesInt($pdo) ? 'Mes_Int' : null;

    // ---- 1) Grupos (anio, mes) con conteo de filas totales ----
    $grupos = [];
    if ($colMes !== null) {
        try {
            $sql = "SELECT Anio, {$colMes} AS m, COUNT(*) AS c
                    FROM {$tabla}
                    GROUP BY Anio, {$colMes}";
            foreach ($pdo->query($sql) as $r) {
                $grupos[] = ['anio' => $r['Anio'], 'mes' => $r['m'], 'c' => (int)$r['c']];
            }
            $out['conConteo'] = true;
        } catch (Throwable $e) {
            $grupos = [];
        }
    }
    if (!$out['conConteo']) {
        // Fallback sin Mes_Int: anios del ambito x 12 meses + complementos
        $anios = adoGetAniosDisponibles($pdo, $est);
        if ($fAnio !== '') $anios = [$fAnio];
        foreach ($anios as $a) {
            for ($m = 1; $m <= 12; $m++) {
                $grupos[] = ['anio' => $a, 'mes' => $m, 'c' => null];
            }
        }
    }

    // ---- 2) Trozos a partir de los grupos ----
    $totFilas = 0;
    $hayConteo = $out['conConteo'];
    foreach ($grupos as $g) {
        $anioV = $g['anio'];
        if ($fAnio !== '' && (string)$anioV !== $fAnio) continue;   // ambito del usuario
        if ($hayConteo) $totFilas += (int)$g['c'];

        $base = ['anioRaw' => ($anioV === null) ? null : (string)$anioV];
        if ($g['mes'] === null) {
            $base['mesRaw'] = null;
        } elseif (is_numeric($g['mes'])) {
            $base['mesRaw'] = (int)$g['mes'];
        } else {
            $base['mesFuera'] = true;
        }

        $etiqueta = ($anioV === null ? 'SIN AÑO' : (string)$anioV)
                  . ' — ' . adoEtiquetaMes($g['mes']);

        if ($est !== '') {
            // ambito ya acotado por el filtro de EE.SS del usuario
            $out['trozos'][] = $base + [
                'establecimiento' => $est,
                'etiqueta' => $etiqueta . ' — ' . ($nombresEst[$est] ?? $est),
            ];
        } elseif ($g['c'] !== null && $g['c'] > ADO_TROZO_MAX_FILAS && !empty($nombresEst)) {
            foreach ($nombresEst as $cod => $nom) {
                $out['trozos'][] = $base + [
                    'establecimiento' => $cod,
                    'etiqueta' => $etiqueta . ' — ' . $nom,
                ];
            }
        } else {
            $out['trozos'][] = $base + ['establecimiento' => '', 'etiqueta' => $etiqueta];
        }
    }

    // ---- 3) Complementos del fallback (solo cuando no hubo conteos) ----
    // La particion debe ser EXACTA y sin solapes:
    //   (A, mes 1..12) para cada Anio NO-NULL del ambito  |  Anio IS NULL
    //   (cualquier mes)  |  mes fuera de 1..12 y Anio NO NULL
    if (!$out['conConteo']) {
        if ($fAnio === '') {
            // TODAS las filas con Anio NULL (cualquier Mes)
            $out['trozos'][] = ['anioRaw' => null,
                                'establecimiento' => $est, 'etiqueta' => 'SIN AÑO (complemento)'];
        }
        // filas con Mes NULL / fuera de 1..12 y Anio NO NULL (las de Anio
        // NULL ya las cubre el trozo anterior; evitar el doble conteo)
        $comp = ['mesFuera' => true, 'establecimiento' => $est, 'etiqueta' => 'MES ATÍPICO (complemento)'];
        if ($fAnio !== '') $comp['anioRaw'] = $fAnio;
        else                $comp['anioNonNull'] = true;
        $out['trozos'][] = $comp;
    }

    if ($out['conConteo']) $out['filas_estimadas'] = $totFilas;
    return $out;
}

/** Etiqueta corta de mes para el asistente ("MARZO", "MES 0", "MES NULL"). */
function adoEtiquetaMes($mes): string {
    if ($mes === null) return 'MES NULL';
    if (!is_numeric($mes)) return 'MES ' . (string)$mes;
    $m = (int)$mes;
    if ($m >= 1 && $m <= 12 && function_exists('getNombreMes')) {
        $n = getNombreMes($m);
        return $n !== '' ? $n : ('MES ' . $m);
    }
    return 'MES ' . $m;
}

/** Traduce un trozo del plan a los filtros de adoEjecutarReporte. */
function adoFiltrosTrozo(array $t): array {
    $f = ['establecimiento' => (string)($t['establecimiento'] ?? '')];
    if (!empty($t['anioNonNull'])) {
        $f['anioNonNull'] = true;
    } elseif (array_key_exists('anioRaw', $t)) {
        $f['anioRaw'] = $t['anioRaw'];
    } elseif (!empty($t['anio'])) {
        $f['anio'] = (string)$t['anio'];
    }
    if (!empty($t['mesFuera'])) {
        $f['mesFuera'] = true;
    } elseif (array_key_exists('mesRaw', $t)) {
        $f['mesRaw'] = $t['mesRaw'];
    } elseif (!empty($t['mes'])) {
        $f['mes'] = (string)$t['mes'];
    }
    return $f;
}

/** Suma recursiva de mapas de valores (vals por combo / eje etareo). */
function adoSumaVals(array &$dest, array $src): void {
    foreach ($src as $k => $v) {
        if (is_array($v)) {
            if (!isset($dest[$k]) || !is_array($dest[$k])) $dest[$k] = [];
            adoSumaVals($dest[$k], $v);
        } else {
            $dest[$k] = ($dest[$k] ?? 0) + $v;
        }
    }
}

/**
 * Fusiona el reporte $nuevo (un trozo) dentro del acumulador $acum.
 * La estructura de secciones/filas es fija (adoSecciones()), asi que el
 * merge es posicional y puramente aditivo.
 *
 * @param array|null $acum  acumulador previo (null = primer trozo)
 * @param array      $nuevo reporte del trozo recien ejecutado
 */
function adoMergeReporte(?array $acum, array $nuevo): array {
    if ($acum === null || empty($acum['secciones'])) {
        return $nuevo;
    }
    foreach ($nuevo['secciones'] as $i => $secN) {
        if (!isset($acum['secciones'][$i]) || !is_array($acum['secciones'][$i])) {
            $acum['secciones'][$i] = $secN;
            continue;
        }
        $secA =& $acum['secciones'][$i];
        foreach ($secN['filas'] as $j => $fN) {
            if (!isset($secA['filas'][$j]) || !is_array($secA['filas'][$j])) {
                $secA['filas'][$j] = $fN;
                continue;
            }
            $fA =& $secA['filas'][$j];
            if (!is_array($fA['vals'])) $fA['vals'] = [];
            adoSumaVals($fA['vals'], is_array($fN['vals']) ? $fN['vals'] : []);
            $fA['total'] = ($fA['total'] ?? 0) + ($fN['total'] ?? 0);
        }
        $secA['total'] = ($secA['total'] ?? 0) + ($secN['total'] ?? 0);
    }
    $acum['totales']['total_casos'] = ($acum['totales']['total_casos'] ?? 0)
                                    + ($nuevo['totales']['total_casos'] ?? 0);
    foreach (($nuevo['totales']['grupos'] ?? []) as $g => $v) {
        $acum['totales']['grupos'][$g] = ($acum['totales']['grupos'][$g] ?? 0) + $v;
    }
    $acum['totales']['secciones_con_datos'] =
        count(array_filter($acum['secciones'], fn($s) => ($s['total'] ?? 0) > 0));
    $acum['filas_leidas']     = ($acum['filas_leidas'] ?? 0) + ($nuevo['filas_leidas'] ?? 0);
    $acum['tiempo_ejecucion'] = round(($acum['tiempo_ejecucion'] ?? 0) + ($nuevo['tiempo_ejecucion'] ?? 0), 2);
    $acum['version']          = $nuevo['version'] ?? ($acum['version'] ?? ADO_DATA_VERSION);
    return $acum;
}
