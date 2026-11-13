<?php

/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ADULTO - Motor de reporte data-driven (includes/adulto_data.php)
 *
 * ADAPTACION FIEL del archivo "03 Creacion de Procedimientos.txt"
 * (21 procedimientos usp_TRAMA_BASE_ADULTO_RPT_*) sobre la tabla consolidada
 * MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO. Reemplaza:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales" (DimAdulto*: los
 *      catalogos de etiquetas ya viven en el motor PHP, no hacen falta tablas)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *      (TRAMA_BASE_ADULTO_RPT_*_CONSOLIDADO / _NOMINAL)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos" (21 SP)
 *   4) Excel:      refrescar la conexion ODBC de "Reporte_Actividades_
 *      Adulto.xlsx"
 *
 * SECCIONES (21 SP -> 17 bloques del Excel, hoja "Plantilla", 256 filas):
 *   01  usp_..._01_MORBILIDAD            filas 14-23 (cod_item_f A64/E66/J00/
 *                                      J02/J06/K02/K04/K29/M54/N39, F|M|TOTAL)
 *   02  usp_..._02_PAI                  filas 29-30 (C8002: 'TA'=ejecutado,
 *                                      TRY_CONVERT(vl)=1=elaborado)
 *   03  usp_..._03_EVAL_ANTROPOMETRICA  filas 36-43 (IMC E46X3..E6693 vl=IMC)
 *   04  usp_..._04_RIESGO_NUT           filas 49-50 (U8170 vl N/A)
 *   05_1 usp_..._05_1_DNT_VALORACION    filas 56-58 (Z019/U262 vl=DNT con
 *                                      EXISTS #TMP_CITA [Z006] /
 *                                      #TMP_CITA2 [FR: E65X,Z72x,Z883,Z834,
 *                                      cod_item_f=E66] / U262)
 *   05_2 usp_..._05_2_DNT_HTA           filas 62-64 (I10X / H350 / I120,I129)
 *   05_3 usp_..._05_3_DNT_DM             filas 68-73 (fg_tipo='CX':
 *                                      cod_item_f E10/E11/O24/E12-E14 +
 *                                      nefropatia E10x2/E11x2/... y
 *                                      retinopatia E10x3/... con rownum=1)
 *   06_1 usp_..._06_1_PF_CLASIF         filas 79-84 (99402.04/.03/.06 con
 *                                      TRY_CONVERT(vl) in (1,2) = sesion)
 *   06_2 usp_..._06_2_PF_METODOS        filas 88-113 (13 metodos x fila A
 *                                      [count(Usuarias) Actividad] / fila I
 *                                      [sum(LAB) Insumo]; Usuarias 1=NUEVO
 *                                      tipitem D, 2=CONTI tipitem R; AQV
 *                                      (58600/58605/58611/55250) solo cuenta
 *                                      con valor_lab null/vacio; metodos 8-13
 *                                      con insumo 0; LAB numerico de valor_lab)
 *                                      + fila 115 E115='TOTAL' (suma de nuevas
 *                                      usuarias de los 13 metodos)
 *   06_3 usp_..._06_3_PF_METODOS        filas 116-119 (AOE YUZPE/PROGESTAGENO:
 *                                      fila A count(*) Actividad, fila I
 *                                      sum(LAB) Insumo, LAB=iif(vl null|<1,1,
 *                                      vl); solo id_genero='F')
 *   07  usp_..._07_CANCER               filas 124-125 (99402.08 vl 1/2)
 *   08  usp_..._08_SALUD_BUCAL          filas 130-157 (7 prestaciones x
 *                                      [NO GESTANTES|GESTANTES] x [INICIAN|
 *                                      TRATADO/CONTROLADO]. v1.1.0: en
 *                                      ASESORIA NUTRICIONAL (D1310),
 *                                      PROFILAXIS DENTAL (D1110), CONSULTA
 *                                      ESTOMATOLOGICA (D0140), APLICACION
 *                                      FLUOR BARNIZ (D1206) y RASPAJE
 *                                      DENTAL (D4355) el gestante se toma
 *                                      del campo Descripcion_Otra_Condicion
 *                                      de la propia fila ('GESTANTE' /
 *                                      <>'GESTANTE'), NO del marcador
 *                                      valor_lab='G' de la cita; EXAMEN
 *                                      ESTOMATOLOGICO e INSTRUCCION DE
 *                                      HIGIENE ORAL conservan el criterio
 *                                      del SP (#GESTANTES); las filas 156/157
 *                                      del raspaje gestante ahora cuentan
 *                                      casos reales)
 *   09  usp_..._09_SALUD_MENTAL         filas 162-172 (v1.2.0: grupo 1
 *                                      [entrevista]: 96150.01+VIF / 96150.02+
 *                                      AD / 96150.03+TD tipitem D; U140 VSX/VS
 *                                      conserva el SP original; grupo 2
 *                                      [tamizaje positivo]: fila de la
 *                                      entrevista 96150.0X tipitem D en citas
 *                                      que ademas registran el diagnostico
 *                                      R456/Z720/Z721/Z722/Z133 tipitem D)
 *   10  usp_..._10_TBC                  filas 177-178 (#TEMPORAL1 [cita con
 *                                      U2142 vl='1']: Diagnostico 1; y
 *                                      #TEMPORAL2 [U200 tipitem D]:
 *                                      Diagnostico 2)
 *   11  usp_..._11_SSR                  filas 183-195 (13 prestaciones;
 *                                      #TERMINADA [cita con vl='TA'],
 *                                      #SUPLEMENTACION [cita con Z298 SF6/
 *                                      AF3], #PAP [cita con 88141/Z0141],
 *                                      #TEMPORAL4 [cita con U2601 o Z359])
 *   12  usp_..._12_VIH                  filas 200-206 (99402.05 vl='PV' / vl
 *                                      null|'' ; Z7172/Z7173 rownum=1; 86706
 *                                      rownum=1; Z115 vl='PRA'. NOTA fiel al
 *                                      SP: el CASE nunca produce Diagnostico 5
 *                                      (POST-TEST NEGATIVO => fila 204 = 0)
 *                                      y asigna 86706 -> 6 y Z115-PRA -> 7)
 *   13  usp_..._13_SALUD_OCULAR         filas 211-215 (99401.16/99173/H456/
 *                                      H259/Z006 rownum=1)
 *   14  usp_..._14_APP                  filas 220-224 (99344 vl 1/2/>=3 +
 *                                      C0011.01/.02 rownum=1)
 *   15  usp_..._15_VACUNA               filas 229-238 (Z2781 vl 1|D1, 2|D2,
 *                                      3|D3 separado por #GESTANTES [cita con
 *                                      vl='G'] en no gestantes 1-3 / gestantes
 *                                      4-6; 90744 vl 1|D1, 2|D2, 3|D3;
 *                                      90657 vl='D1'; todos rownum=1)
 *   16  usp_..._16_SESIONES             filas 243-246 (C0010/C0009/U114/U122
 *                                      tipitem D rownum=1 id_persona IS NULL
 *                                      con EXISTS #TEMPORAL1 [cita con U0041];
 *                                      sin filtro de edad; Nº=count(Sesion) y
 *                                      Part/Benef=sum(LAB), LAB=iif(vl null|
 *                                      <1,1,vl))
 *   17  usp_..._17_CONSEJERIAS          filas 251-256 (99403.01/99402.08/
 *                                      99401.31/99401.15/99173/99174 rownum=1;
 *                                      una sola columna TOTAL en D)
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita         -> Id_Cita
 *   id_tipitem      -> Tipo_Diagnostico      ('D','P','R','C')
 *   cod_item        -> Codigo_Item
 *   cod_item_f      -> primeros 3 caracteres alfabeticos de Codigo_Item
 *                      (familia CIE: 'A64', 'J06', 'E10'...)
 *   valor_lab       -> Valor_Lab             ('NULL' en el DSL = IS NULL)
 *   I_ROWNUM_LAB    -> Id_Correlativo_Lab
 *   rownum          -> Id_Correlativo_Item   (primera fila de la cita)
 *   id_genero       -> Id_Genero             ('F','M'; el nominal exige
 *                      "sexo is not null" en todas las secciones salvo 16)
 *   edad_reg        -> Edad_Reg
 *   id_tipedad_reg  -> Tipo_Edad             ('A' anios)
 *   fg_tipo         -> Fg_Tipo               ('CX' consulta externa: SP 05_3,
 *                      09)
 *   renaes          -> Codigo_Unico
 *   id_persona      -> Id_Paciente           (NULL/vacio en SP 16: sesiones
 *                      sin paciente)
 *   otra_condicion  -> Descripcion_Otra_Condicion ('GESTANTE' cuando la
 *                      atencion registra esa otra condicion; NULL/vacio
 *                      en el resto) [v1.1.0: seccion 08]
 *   periodo         -> Anio                  (los filtros web usan Anio/Mes)
 *   id_ups          -> Id_Ups
 *
 * GRUPO ETAREO del reporte: 30a-59a (columnas F | M | TOTAL del Excel,
 * igual que la plantilla; el nominal del T-SQL exige sexo IS NOT NULL).
 *
 * REGLAS DE CONTEO (columnas del consolidado del T-SQL):
 *   'filas'      : count(*) -> cada fila HIS que cumple aporta 1
 *   'insumo06_2' : sum(LAB) con LAB = iif(TRY_CONVERT(int,valor_lab) IS NULL
 *                  OR Metodos 8..13, 0, TRY_CONVERT(int,valor_lab))
 *                  (fila I de los metodos de PF)
 *   'min1'       : sum(LAB) con LAB = iif(TRY_CONVERT(int,valor_lab) IS NULL
 *                  OR <1, 1, TRY_CONVERT(int,valor_lab)) (fila I del AOE y
 *                  Part/Benef de las sesiones)
 *
 * ELIMINACIONES / duplicidades del T-SQL replicadas (auditoria contra el
 * archivo 03): ver la nota de cada seccion en aduSecciones().
 */

define('ADU_DATA_VERSION', '1.2.0-adulto');

function aduDataVersion(): string {
    return ADU_DATA_VERSION;
}

/* ============================================================
 * 1) NORMALIZACION DE FILAS HIS
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 *
 * Textos en MAYUSCULAS para replicar la colacion case-insensitive de MySQL
 * en el matching PHP (igual que adolescente_data.php / cancer_data.php).
 *
 * Desde v1.1.0 incluye 'otraCond' (Descripcion_Otra_Condicion) para que la
 * seccion 08 (Atencion en Salud Bucal) clasifique GESTANTES / NO GESTANTES
 * con dicho campo de la base de datos.
 */
function aduFila(array $r): array {
    $cod = $r['Codigo_Item'] !== null ? strtoupper(trim((string)$r['Codigo_Item'])) : '';
    // cod_item_f del T-SQL: primeros 3 caracteres si el primero es alfabetico
    // (familia CIE: 'A642'->'A64', 'J069'->'J06', 'E102'->'E10'); codigos que
    // empiezan con digito ('99402.05', '58300'...) se usan solo como exactos.
    // (mismo criterio que adoFila/cnrFila de los modulos previos)
    $codF = ($cod !== '' && ctype_alpha($cod[0])) ? substr($cod, 0, 3) : $cod;
    // Descripcion_Otra_Condicion ('GESTANTE', 'PUERPERA', ...): viene del
    // maestro HIS_OTRA_CONDICION via Id_Otra_Condicion de cada fila (NULL/vacio
    // cuando la atencion no registra otra condicion).
    $otraCondRaw = isset($r['Descripcion_Otra_Condicion']) ? $r['Descripcion_Otra_Condicion'] : null;
    return [
        'cita'    => $r['Id_Cita'] !== null ? trim((string)$r['Id_Cita']) : '',
        'pac'     => $r['Id_Paciente'] !== null ? trim((string)$r['Id_Paciente']) : '',
        'renaes'  => $r['Codigo_Unico'] !== null ? trim((string)$r['Codigo_Unico']) : '',
        'cod'     => $cod,
        'codF'    => strtoupper($codF),
        'tip'     => $r['Tipo_Diagnostico'] !== null ? strtoupper(trim((string)$r['Tipo_Diagnostico'])) : '',
        'vl'      => $r['Valor_Lab'] !== null ? strtoupper(trim((string)$r['Valor_Lab'])) : null,
        'rownum'  => $r['Id_Correlativo_Lab'] !== null ? (int)$r['Id_Correlativo_Lab'] : null,
        'rownumIt'=> $r['Id_Correlativo_Item'] !== null ? (int)$r['Id_Correlativo_Item'] : null,
        'sexo'    => $r['Id_Genero'] !== null ? strtoupper(trim((string)$r['Id_Genero'])) : '',
        'edad'    => $r['Edad_Reg'] !== null ? (int)$r['Edad_Reg'] : null,
        'tipEdad' => $r['Tipo_Edad'] !== null ? strtoupper(trim((string)$r['Tipo_Edad'])) : '',
        'fg'      => isset($r['Fg_Tipo']) && $r['Fg_Tipo'] !== null ? strtoupper(trim((string)$r['Fg_Tipo'])) : '',
        'ups'     => isset($r['Id_Ups']) && $r['Id_Ups'] !== null ? strtoupper(trim((string)$r['Id_Ups'])) : '',
        'anio'    => $r['Anio'] !== null ? trim((string)$r['Anio']) : '',
        'mes'     => $r['Mes'] !== null ? trim((string)$r['Mes']) : '',
        'otraCond'=> ($otraCondRaw !== null && $otraCondRaw !== '') ? strtoupper(trim((string)$otraCondRaw)) : null,
    ];
}

/**
 * Indica si la fila HIS corresponde a una GESTANTE segun el campo
 * Descripcion_Otra_Condicion de la tabla consolidada (Id_Otra_Condicion=1,
 * 'GESTANTE'). Igual que mtrEsGestante() del modulo Materno: admite la
 * variante con sufijo ('GESTANTE ...') por robustez.
 */
function aduEsGestante(array $f): bool {
    if (!isset($f['otraCond']) || $f['otraCond'] === null || $f['otraCond'] === '') return false;
    if ($f['otraCond'] === 'GESTANTE') return true;
    return strpos($f['otraCond'], 'GESTANTE') === 0; // variantes 'GESTANTE ...'
}

/* ============================================================
 * 2) PREDICADOS (mini-DSL que espeja las condiciones del T-SQL)
 * ============================================================ */

/**
 * Evalua un predicado del DSL contra una fila normalizada.
 *
 * Claves admitidas (se combinan con AND):
 *   'cod'         => '99402.05' | ['I120','I129']       Codigo_Item exacto
 *   'codF'        => 'A64' | ['E12','E13','E14']       cod_item_f (familia)
 *   'codFEntre'   => ['O80','O84']                      cod_item_f BETWEEN
 *   'tip'         => 'D' | ['D','R']                    Tipo_Diagnostico
 *   'vl'          => 'NULL' | 'TA' | ['1','2']          Valor_Lab (NULL=IS NULL)
 *   'vlVacia'     => true                              valor_lab='' OR IS NULL
 *   'vlNumIn'     => [1,2]                             TRY_CONVERT(int,vl) IN
 *   'vlNumEq'     => 1                                 TRY_CONVERT(int,vl) = N
 *   'vlGeNum'     => 3                                 TRY_CONVERT(int,vl)>=N
 *   'rownum'      => 1                                 I_ROWNUM_LAB
 *   'rownumItem'  => 1                                 rownum (1a fila de la cita)
 *   'sexo'        => 'F' | 'M'                          Id_Genero
 *   'tipEdad'     => 'A'                                Tipo_Edad
 *   'edadA'       => [30, 59] | [30, null]              tipEdad A + rango de edad
 *   'fgTipo'      => 'CX'                               fg_tipo
 *   'pacNull'     => true                               id_persona IS NULL/vacio
 *   'otraCond'    => 'NULL' | 'GESTANTE'          Descripcion_Otra_Condicion de
 *                                                   la PROPIA fila (v1.1.0: seccion 08
 *                                                   salud bucal; 'GESTANTE' acepta
 *                                                   variantes 'GESTANTE ...')
 *   'otraCondNo'  => 'GESTANTE'                     Descripcion_Otra_Condicion <> valor
 *                                                   (NULL/vacio/otro valor cuentan:
 *                                                   "NO GESTANTES" de la seccion 08)
 *   'citaTiene'   => <predicado>                        EXISTS sobre la cita
 *   'citaTieneTodo' => [<pred>, ...]                    AND de EXISTS
 *   'citaSin'     => <predicado>                        NOT EXISTS sobre la cita
 *   'citaSinTodo' => [<pred>, ...]                      AND de NOT EXISTS
 *   'cualquieraDe'  => [<pred>, ...]                    OR de predicados (fila)
 *
 * $ctx: contexto de ejecucion (filas + indices porCita/porCod), igual que
 * adolescente_data.php.
 */
function aduCumple(array $f, array $cond, array $ctx): bool {
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
    if (isset($cond['codFEntre'])) {
        // cod_item_f BETWEEN 'O80' AND 'O84' (comparacion de strings)
        $min = strtoupper((string)$cond['codFEntre'][0]);
        $max = strtoupper((string)$cond['codFEntre'][1]);
        if ($f['codF'] === '' || $f['codF'] < $min || $f['codF'] > $max) return false;
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
    if (!empty($cond['vlVacia'])) {
        // "(valor_lab='' or valor_lab is null)" del SP 12
        if ($f['vl'] !== null && $f['vl'] !== '') return false;
    }
    if (isset($cond['vlNumIn'])) {
        $lista = (array)$cond['vlNumIn'];
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
        $n = (int)$f['vl'];
        $encontrado = false;
        foreach ($lista as $x) { if ((int)$x === $n) { $encontrado = true; break; } }
        if (!$encontrado) return false;
    }
    if (isset($cond['vlNumEq'])) {
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
        if ((int)$f['vl'] !== (int)$cond['vlNumEq']) return false;
    }
    if (isset($cond['vlGeNum'])) {
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
        if ((int)$f['vl'] < (int)$cond['vlGeNum']) return false;
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
    if (isset($cond['fgTipo'])) {
        if ($f['fg'] !== strtoupper((string)$cond['fgTipo'])) return false;
    }
    if (!empty($cond['pacNull'])) {
        if ($f['pac'] !== '') return false;   // Id_Paciente NULL o ''
    }
    if (isset($cond['otraCond'])) {
        // Descripcion_Otra_Condicion de la propia fila (seccion 08 v1.1.0)
        if ($cond['otraCond'] === 'NULL') {
            if ($f['otraCond'] !== null && $f['otraCond'] !== '') return false;
        } else {
            $esperado = strtoupper(trim((string)$cond['otraCond']));
            if ($esperado === 'GESTANTE') {
                if (!aduEsGestante($f)) return false;    // = 'GESTANTE' (admite variantes)
            } else {
                if ($f['otraCond'] !== $esperado) return false;
            }
        }
    }
    if (isset($cond['otraCondNo'])) {
        // Descripcion_Otra_Condicion <> valor: la fila NO es del tipo indicado.
        // NULL/vacio/otro valor CUMPLEN (en SQL estricto NULL <> 'GESTANTE' es
        // UNKNOWN, pero esas filas NO son gestantes y deben caer en NO GESTANTES).
        $esperadoNo = strtoupper(trim((string)$cond['otraCondNo']));
        if ($esperadoNo === 'GESTANTE') {
            if (aduEsGestante($f)) return false;
        } else {
            if ($f['otraCond'] === $esperadoNo) return false;
        }
    }
    if (isset($cond['citaTiene'])) {
        if (!aduCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!aduCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['citaSin'])) {
        if (aduCitaTiene($ctx, $f['cita'], $cond['citaSin'])) return false;
    }
    if (isset($cond['citaSinTodo'])) {
        foreach ($cond['citaSinTodo'] as $sub) {
            if (aduCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (aduCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** EXISTS sobre la cita: alguna fila de la cita cumple el predicado. */
function aduCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    $cands = aduCandidatosCita($ctx, $cita, $cond);
    foreach ($cands as $idx) {
        if (aduCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Indices candidatos de la cita para un predicado (sobre-aproximacion por
 * buckets de codigo; la verificacion exacta la hace aduCumple).
 */
function aduCandidatosCita(array $ctx, string $cita, array $cond): array {
    $idxCita = $ctx['porCita'][$cita] ?? [];
    $conCod = isset($cond['cod']) || isset($cond['codF']) || isset($cond['codFEntre'])
           || isset($cond['cualquieraDe']);
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
    if (isset($cond['codFEntre'])) {
        foreach ($ctx['porCita'][$cita] ?? [] as $idx) {
            $f = $ctx['filas'][$idx];
            if ($f['codF'] !== ''
                && $f['codF'] >= strtoupper((string)$cond['codFEntre'][0])
                && $f['codF'] <= strtoupper((string)$cond['codFEntre'][1])) $out[$idx] = true;
        }
    }
    if (isset($cond['cualquieraDe'])) {
        foreach ($cond['cualquieraDe'] as $sub) {
            foreach (aduCandidatosCita($ctx, $cita, $sub) as $idx) $out[$idx] = true;
        }
    }
    return array_keys($out);
}

/**
 * Genera una descripcion SQL-legible de una condicion del DSL (para el panel
 * "Ver SQL": auditoria contra el archivo 03).
 */
function aduCondicionSQL(array $cond, int $nivel = 0): string {
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
            case 'codFEntre':
                $partes[] = "cod_item_f BETWEEN '{$v[0]}' AND '{$v[1]}'";
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
            case 'vlVacia':
                if ($v) $partes[] = "(valor_lab = '' OR valor_lab IS NULL)";
                break;
            case 'vlNumIn':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "TRY_CONVERT(int, valor_lab) IN (" . implode(', ', array_map(fn($x) => (string)(int)$x, $lista)) . ")";
                break;
            case 'vlNumEq':
                $partes[] = "TRY_CONVERT(int, valor_lab) = " . (int)$v;
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
            case 'fgTipo':
                $partes[] = "fg_tipo = '{$v}'";
                break;
            case 'pacNull':
                if ($v) $partes[] = "id_persona IS NULL";
                break;
            case 'otraCond':
                if ($v === 'NULL') {
                    $partes[] = "Descripcion_Otra_Condicion IS NULL";
                } elseif (strtoupper((string)$v) === 'GESTANTE') {
                    $partes[] = "Descripcion_Otra_Condicion LIKE 'GESTANTE%'";
                } else {
                    $partes[] = "Descripcion_Otra_Condicion = '$v'";
                }
                break;
            case 'otraCondNo':
                if (strtoupper((string)$v) === 'GESTANTE') {
                    $partes[] = "(Descripcion_Otra_Condicion IS NULL OR TRIM(Descripcion_Otra_Condicion) = '' OR Descripcion_Otra_Condicion NOT LIKE 'GESTANTE%')";
                } else {
                    $partes[] = "(Descripcion_Otra_Condicion IS NULL OR Descripcion_Otra_Condicion <> '$v')";
                }
                break;
            case 'citaTiene':
                $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . aduCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaTieneTodo':
                foreach ($v as $sub) {
                    $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . aduCondicionSQL($sub, $nivel + 1) . ")";
                }
                break;
            case 'citaSin':
                $partes[] = "id_cita NOT IN (SELECT id_cita FROM #TEMP WHERE " . aduCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaSinTodo':
                foreach ($v as $sub) {
                    $partes[] = "id_cita NOT IN (SELECT id_cita FROM #TEMP WHERE " . aduCondicionSQL($sub, $nivel + 1) . ")";
                }
                break;
            case 'cualquieraDe':
                $sub = [];
                foreach ($v as $c) {
                    $sub[] = "(" . aduCondicionSQL($c, $nivel + 1) . ")";
                }
                $partes[] = "(" . implode(" OR ", $sub) . ")";
                break;
        }
    }
    return implode("\n  AND ", $partes);
}

/**
 * Combina varios predicados con AND resolviendo colisiones de claves
 * (mismo mecanismo que adoY() de adolescente_data.php).
 */
function aduY(array ...$parts): array {
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

/* ============================================================
 * 3) EJE USUARIAS (SP 06_2: 1 = NUEVAS tipitem D, 2 = CONTI tipitem R)
 * ============================================================ */

/** Codigos con Usuarias estandar (lista del CASE del SP 06_2). */
function aduCodigosUsuarias(): array {
    return ['58300','99208.13','99208.05','99208.04','11975','99208.02',
            '99208.06','99208.07','99208.09','99208.08','99208.10'];
}

/**
 * Eje Usuarias del SP 06_2 para una fila:
 *   1 (NUEVAS)        tipitem D en la lista estandar
 *   2 (CONTINUADORAS) tipitem R en la lista estandar
 *   AQV (58600/58605/58611/55250): 1/2 solo si valor_lab IS NULL o ''
 *   null en otro caso (la fila no aporta a NUEVO/CONTI)
 */
function aduEjeUsuarias(array $f): ?int {
    $tip = $f['tip'];
    $cod = $f['cod'];
    $vl  = $f['vl'];
    if ($tip === 'D' && in_array($cod, aduCodigosUsuarias(), true)) return 1;
    if ($tip === 'R' && in_array($cod, aduCodigosUsuarias(), true)) return 2;
    if (in_array($cod, ['58600','58605','58611','55250'], true) && ($vl === null || $vl === '')) {
        if ($tip === 'D') return 1;
        if ($tip === 'R') return 2;
    }
    return null;
}

/* ============================================================
 * 4) DEFINICION DE LAS SECCIONES DEL REPORTE
 *    (adaptacion del archivo "03 Creacion de Procedimientos":
 *     21 SP -> 21 secciones, mismo numero y orden que el Excel)
 * ============================================================ */

/**
 * Devuelve la definicion completa de las 21 secciones del reporte ADULTO
 * (hoja "Plantilla" del Excel "Reporte_Actividades_Adulto.xlsx", filas
 * 10-256).
 *
 * Campos por seccion:
 *   'codigo' / 'procedimiento' / 'titulo' / 'grupo' (macro-grupo web)
 *   'layout'   => 'FMT' (D=F | E=M | F=TOTAL)
 *               | 'PFM' (metodos PF: fila A/I, E=NUEVO, F=CONTI)
 *               | 'AOE' (fila A/I, unica columna E)
 *               | 'SES' (D=Nº, E=Part/Benef)
 *               | 'TOT' (unica columna D=TOTAL)
 *   'eje'      => 'sexo' (F=1, M=2) | 'usuarias' (SP 06_2) | null
 *   'regla'    => 'filas' | 'insumo06_2' | 'min1'   (regla por defecto)
 *   'conSexo'  => false solo en 16 (su SP no exige "sexo is not null")
 *   'filas'    => cada fila: ['key','label','label2','fila' => fila Excel,
 *                 'cond' => predicado | 'ramas' => [pred,...] (suma de ramas,
 *                 como los INSERT multiples del T-SQL), 'regla' propia,
 *                 'metodoKey' (para 'insumo06_2'), 'nota' (pie de fila)]
 */
function aduSecciones(): array {

    // ============ Atajos comunes (segun archivo 03) ============
    $e3059 = ['edadA' => [30, 59]];                       // 30a-59a
    $D     = ['tip' => 'D'];
    $R1    = ['rownum' => 1];                             // I_ROWNUM_LAB=1
    $A     = ['edadA' => [30, 59], 'tip' => 'D', 'rownum' => 1];

    // SP 05_1: #TMP_CITA = citas con Z006 / #TMP_CITA2 = citas con FR
    $citaZ006 = ['cod' => 'Z006'];
    $fr = ['cualquieraDe' => [
        ['cod' => ['E65X','Z720','Z721','Z723','Z724','Z883','Z834']],
        ['codF' => 'E66'],
    ]];

    $secs = [];

    /* ------------------------------------------------------------
     * 01 - usp_TRAMA_BASE_ADULTO_RPT_01_MORBILIDAD (filas 14-23)
     * cod_item_f IN (A64,E66,J00,J02,J06,K02,K04,K29,M54,N39) AND
     * I_ROWNUM_LAB=1; nominal: sexo is not null.
     * ------------------------------------------------------------ */
    $morb = [
        ['A64 - ENFERMEDAD DE TRANSMISION SEXUAL NO ESPECIFICADA', 14, 'A64'],
        ['E66 - OBESIDAD',                                        15, 'E66'],
        ['J00 - RINOFARINGITIS AGUDA [RESFRIADO COMUN]',          16, 'J00'],
        ['J02 - FARINGITIS AGUDA',                                17, 'J02'],
        ['J06 - INFECCIONES AGUDAS DE LAS VIAS RESPIRATORIAS SUPERIORES, DE SITIOS MULTIPLES', 18, 'J06'],
        ['K02 - CARIES DENTAL',                                   19, 'K02'],
        ['K04 - ENFERMEDADES DE LA PULPA Y DE LOS TEJIDOS PERIAPICALES', 20, 'K04'],
        ['K29 - GASTRITIS Y DUODENITIS',                          21, 'K29'],
        ['M54 - DORSALGIA',                                       22, 'M54'],
        ['N39 - OTROS TRASTORNOS DEL SISTEMA URINARIO',           23, 'N39'],
    ];
    $filas01 = [];
    foreach ($morb as $i => [$lbl, $fila, $cf]) {
        $filas01[] = [
            'key' => $i + 1, 'label' => $lbl, 'fila' => $fila,
            'cond' => aduY($e3059, $D, $R1, ['codF' => $cf]),
        ];
    }
    $secs[] = [
        'codigo' => 'ADU01_MORBILIDAD', 'grupo' => 'ATN',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_01_MORBILIDAD',
        'titulo' => '1. MORBILIDAD DEL ADULTO', 'sub' => 'Diagnóstico',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas01,
    ];

    /* ------------------------------------------------------------
     * 02 - usp_TRAMA_BASE_ADULTO_RPT_02_PAI (filas 29-30)
     * C8002: 'TA' -> Planes 2 (EJECUTADO, fila 29); TRY_CONVERT(vl)=1
     * -> Planes 1 (ELABORADO, fila 30).
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU02_PAI', 'grupo' => 'ATN',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_02_PAI',
        'titulo' => '2. PLAN DE ATENCION INTEGRAL DEL ADULTO', 'sub' => 'Plan de Atención Integral',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'PLAN DE ATENCION EJECUTADO',  'fila' => 29,
             'cond' => aduY($e3059, $D, ['cod' => '99801'], ['vl' => 'TA'])],
            ['key' => 2, 'label' => 'PLAN DE ATENCION ELABORADO', 'fila' => 30,
             'cond' => aduY($e3059, $D, ['cod' => '99801'], ['vlNumEq' => 1])],
        ],
    ];

    /* ------------------------------------------------------------
     * 03 - usp_TRAMA_BASE_ADULTO_RPT_03_EVAL_ANTROPOMETRICA (36-43)
     * ------------------------------------------------------------ */
    $imc = [
        ['E46X3 - DELGADEZ III',  36, 'E46X3'],
        ['E46X2 - DELGADEZ II',   37, 'E46X2'],
        ['E46X1 - DELGADEZ I',    38, 'E46X1'],
        ['Z006 - NORMAL',         39, 'Z006'],
        ['E660 - SOBREPESO',      40, 'E660'],
        ['E669 - OBESIDAD I',     41, 'E669'],
        ['E6692 - OBESIDAD II',   42, 'E6692'],
        ['E6693 - OBESIDAD III',  43, 'E6693'],
    ];
    $filas03 = [];
    foreach ($imc as $i => [$lbl, $fila, $cod]) {
        $filas03[] = [
            'key' => $i + 1, 'label' => $lbl, 'fila' => $fila,
            'cond' => aduY($e3059, $D, ['cod' => $cod], ['vl' => 'IMC']),
        ];
    }
    $secs[] = [
        'codigo' => 'ADU03_IMC', 'grupo' => 'NUT',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_03_EVAL_ANTROPOMETRICA',
        'titulo' => '3. EVALUACIÓN ANTROPOMETRICA', 'sub' => 'Índice de Masa Corporal (IMC)',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas03,
    ];

    /* ------------------------------------------------------------
     * 04 - usp_TRAMA_BASE_ADULTO_RPT_04_RIESGO_NUT (49-50)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU04_PAB', 'grupo' => 'NUT',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_04_RIESGO_NUT',
        'titulo' => '4. RIESGO NUTRICIONAL', 'sub' => 'Evaluación del Perímetro Abdominal (PAB)',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'NORMAL',   'fila' => 49,
             'cond' => aduY($e3059, $D, ['cod' => 'U8170'], ['vl' => 'N'])],
            ['key' => 2, 'label' => 'ANORMAL',  'fila' => 50,
             'cond' => aduY($e3059, $D, ['cod' => 'U8170'], ['vl' => 'A'])],
        ],
    ];

    /* ------------------------------------------------------------
     * 05_1 - usp_TRAMA_BASE_ADULTO_RPT_05_1_DNT_VALORACION (56-58)
     * Z019 vl='DNT' + EXISTS #TMP_CITA [cita con Z006] -> 1
     * U262 vl='DNT' -> 3 ; Temporal1: Z019 + EXISTS #TMP_CITA2 [FR] -> 2
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU05_1_VALORACION', 'grupo' => 'DNT',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_05_1_DNT_VALORACION',
        'titulo' => '5. DAÑOS NO TRANSMISIBLES — Clasificación', 'sub' => 'Clasificación',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'VALORACION CLINICA SIN FACTORES DE RIESGO', 'fila' => 56,
             'cond' => aduY($e3059, $D, ['cod' => 'Z019'], ['vl' => 'DNT'],
                            ['citaTiene' => $citaZ006])],
            ['key' => 2, 'label' => 'VALORACION CLINICA CON FACTORES DE RIESGO', 'fila' => 57,
             'cond' => aduY($e3059, $D, ['cod' => 'Z019'], ['vl' => 'DNT'],
                            ['citaTiene' => $fr])],
            ['key' => 3, 'label' => 'VALORACION CLINICA Y TAMIZAJE DE LABORATORIO', 'fila' => 58,
             'cond' => aduY($e3059, $D, ['cod' => 'U262'], ['vl' => 'DNT'])],
        ],
    ];

    /* ------------------------------------------------------------
     * 05_2 - usp_TRAMA_BASE_ADULTO_RPT_05_2_DNT_HTA (62-64)
     * I_ROWNUM_LAB=1.
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU05_2_HTA', 'grupo' => 'DNT',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_05_2_DNT_HTA',
        'titulo' => '5. DAÑOS NO TRANSMISIBLES — Casos diagnosticados HTA', 'sub' => 'Casos diagnosticados HTA',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'HIPERTENSIÓN ESENCIAL (PRIMARIA)', 'fila' => 62,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'I10X'])],
            ['key' => 2, 'label' => 'RETINOPATIA HIPERTENSIVA', 'fila' => 63,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'H350'])],
            ['key' => 3, 'label' => 'NEFROPATIA HIPERTENSIVA', 'fila' => 64,
             'cond' => aduY($e3059, $D, $R1, ['cod' => ['I120', 'I129']])],
        ],
    ];

    /* ------------------------------------------------------------
     * 05_3 - usp_TRAMA_BASE_ADULTO_RPT_05_3_DNT_DM (68-73)
     * fg_tipo='CX' + I_ROWNUM_LAB=1. #TEMPORAL2 (cod_item_f) +
     * Temporal1 (nefropatia E10x2 / retinopatia E10x3).
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU05_3_DM', 'grupo' => 'DNT',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_05_3_DNT_DM',
        'titulo' => '5. DAÑOS NO TRANSMISIBLES — Casos diagnosticados Diabetes Mellitus',
        'sub' => 'Casos diagnosticados Diabetes Mellitus',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'DIABETES MELLITUS TIPO 1', 'fila' => 68,
             'cond' => aduY($e3059, $D, $R1, ['fgTipo' => 'CX'], ['codF' => 'E10'])],
            ['key' => 2, 'label' => 'DIABETES MELLITUS TIPO 2', 'fila' => 69,
             'cond' => aduY($e3059, $D, $R1, ['fgTipo' => 'CX'], ['codF' => 'E11'])],
            ['key' => 3, 'label' => 'DIABETES MELLITUS EN EL EMBARAZO', 'fila' => 70,
             'cond' => aduY($e3059, $D, $R1, ['fgTipo' => 'CX'], ['codF' => 'O24'])],
            ['key' => 4, 'label' => 'OTRAS DIABETES MELLITUS', 'fila' => 71,
             'cond' => aduY($e3059, $D, $R1, ['fgTipo' => 'CX'], ['codF' => ['E12', 'E13', 'E14']])],
            ['key' => 5, 'label' => 'NEFROPATIA DIABÉTICA', 'fila' => 72,
             'cond' => aduY($e3059, $D, $R1, ['fgTipo' => 'CX'],
                            ['cod' => ['E102', 'E112', 'E122', 'E132', 'E142']])],
            ['key' => 6, 'label' => 'RETINOPATIA DIABÉTICA', 'fila' => 73,
             'cond' => aduY($e3059, $D, $R1, ['fgTipo' => 'CX'],
                            ['cod' => ['E103', 'E113', 'E123', 'E133', 'E143']])],
        ],
    ];

    /* ------------------------------------------------------------
     * 06_1 - usp_TRAMA_BASE_ADULTO_RPT_06_1_PF_CLASIF (79-84)
     * 99402.04 (PF) / 99402.03 (SSR) / 99402.06 (PERIMENOPAUSIA) con
     * TRY_CONVERT(vl) in (1,2) = 1°/2° sesion; I_ROWNUM_LAB=1.
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU06_1_PF_CLASIF', 'grupo' => 'PF',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_06_1_PF_CLASIF',
        'titulo' => '6. PLANIFICACION FAMILIAR — Clasificación', 'sub' => 'Clasificación',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'CONSEJERÍA/ORIENTACION EN PLANIFICACION FAMILIAR',
             'label2' => '1° SESION', 'fila' => 79,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '99402.04'], ['vlNumIn' => [1]])],
            ['key' => 2, 'label' => 'CONSEJERÍA/ORIENTACION EN PLANIFICACION FAMILIAR',
             'label2' => '2° SESION', 'fila' => 80,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '99402.04'], ['vlNumIn' => [2]])],
            ['key' => 3, 'label' => 'CONSEJERÍA/ORIENTACION EN SALUD SEXUAL Y REPRODUCTIVA',
             'label2' => '1° SESION', 'fila' => 81,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '99402.03'], ['vlNumIn' => [1]])],
            ['key' => 4, 'label' => 'CONSEJERÍA/ORIENTACION EN SALUD SEXUAL Y REPRODUCTIVA',
             'label2' => '2° SESION', 'fila' => 82,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '99402.03'], ['vlNumIn' => [2]])],
            ['key' => 5, 'label' => 'CONSEJERÍA/ORIENTACION EN PERIMENOPAUSIA, MENOPAUSIA Y CLIMATERIO',
             'label2' => '1° SESION', 'fila' => 83,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '99402.06'], ['vlNumIn' => [1]])],
            ['key' => 6, 'label' => 'CONSEJERÍA/ORIENTACION EN PERIMENOPAUSIA, MENOPAUSIA Y CLIMATERIO',
             'label2' => '2° SESION', 'fila' => 84,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '99402.06'], ['vlNumIn' => [2]])],
        ],
    ];

    /* ------------------------------------------------------------
     * 06_2 - usp_TRAMA_BASE_ADULTO_RPT_06_2_PF_METODOS (88-113)
     * 13 metodos x fila A (Actividad = count(Usuarias)) / fila I
     * (Insumo = sum(LAB)); Usuarias 1=NUEVO (D), 2=CONTI (R).
     * ------------------------------------------------------------ */
    $metodos = [
        // [etiqueta, filaA, cond extra (ademas de 30-59 + rownum=1 + tip D/R)]
        1  => ['DIU',                                 88,  ['cod' => '58300',   'sexo' => 'F']],
        2  => ['HORMONAL - ORAL COMBINADO',           90,  ['cod' => '99208.13','sexo' => 'F']],
        3  => ['HORMONAL - INYECTABLE TRIMESTRAL',    92,  ['cod' => '99208.05','sexo' => 'F']],
        4  => ['HORMONAL - INYECTABLE MENSUAL',       94,  ['cod' => '99208.04','sexo' => 'F']],
        5  => ['HORMONAL - IMPLANTE',                 96,  ['cod' => '11975',   'sexo' => 'F']],
        6  => ['BARRERA - CONDON MASCULINO',          98,  ['cod' => '99208.02']],
        7  => ['BARRERA - CONDON FEMENINO',           100, ['cod' => '99208.06']],
        8  => ['AQV FEMENINO',                        102, ['cod' => ['58600','58605','58611'], 'sexo' => 'F']],
        9  => ['AQV MASCULINO',                       104, ['cod' => '55250',   'sexo' => 'M']],
        10 => ['MELA',                                106, ['cod' => '99208.07','sexo' => 'F']],
        11 => ['ABSTINENCIA PERIODICA - BILLINGS',    108, ['cod' => '99208.09','sexo' => 'F']],
        12 => ['ABSTINENCIA PERIODICA - RITMO',       110, ['cod' => '99208.08','sexo' => 'F']],
        13 => ['ABSTINENCIA PERIODICA - DIAS FIJO',   112, ['cod' => '99208.10','sexo' => 'F']],
    ];
    $filas062 = [];
    $k = 0;
    foreach ($metodos as $m => [$lbl, $filaA, $condMet]) {
        $cond = aduY($e3059, $R1, ['tip' => ['D', 'R']], $condMet);
        $filas062[] = [ // fila A (Actividad)
            'key' => ++$k, 'label' => $lbl, 'label2' => 'A', 'fila' => $filaA,
            'cond' => $cond, 'metodoKey' => $m, 'regla' => 'filas',
        ];
        $filas062[] = [ // fila I (Insumo)
            'key' => ++$k, 'label' => $lbl, 'label2' => 'I', 'fila' => $filaA + 1,
            'cond' => $cond, 'metodoKey' => $m, 'regla' => 'insumo06_2',
        ];
    }
    $secs[] = [
        'codigo' => 'ADU06_2_PF_METODOS', 'grupo' => 'PF',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_06_2_PF_METODOS',
        'titulo' => '6. PLANIFICACION FAMILIAR — Método de Planificación Familiar en Adultos',
        'sub' => 'Método de Planificación Familiar en Adultos',
        'layout' => 'PFM', 'eje' => 'usuarias', 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas062,
        'nota' => 'Fila A = Actividad (count(Usuarias) del T-SQL); fila I = Insumo (sum(LAB)). '
                 . 'Usuarias: 1 = NUEVAS (id_tipitem D), 2 = CONTINUADORAS (id_tipitem R). '
                 . 'AQV (58600/58605/58611/55250) solo entra con valor_lab NULL/vacío; '
                 . 'métodos 8-13 con Insumo 0 (iif del T-SQL).',
        'nota_total' => 'La celda E115 (TOTAL) del Excel se llena con la suma de nuevas '
                      . 'usuarias (Actividad, Usuarias=1) de los 13 métodos; el SP 06_2 no '
                      . 'la genera (no existe una fila TOTAL en el consolidado).',
    ];

    /* ------------------------------------------------------------
     * 06_3 - usp_TRAMA_BASE_ADULTO_RPT_06_3_PF_METODOS (116-119)
     * AOE: Z3091/99208.11 (YUZPE) y Z3092/99208.12 (PROGESTAGENO),
     * id_genero='F', I_ROWNUM_LAB=1; fila A=count(*), fila I=sum(LAB
     * con minimo 1).
     * ------------------------------------------------------------ */
    $aoe = [
        1 => ['ANTICONCEPCIÓN ORAL DE EMERGENCIA/YUZPE',       116, ['Z3091', '99208.11']],
        2 => ['ANTICONCEPCIÓN ORAL DE EMERGENCIA/PROGESTAGENO', 118, ['Z3092', '99208.12']],
    ];
    $filas063 = [];
    $k = 0;
    foreach ($aoe as $m => [$lbl, $filaA, $cods]) {
        $cond = aduY($e3059, $D, $R1, ['sexo' => 'F'], ['cod' => $cods]);
        $filas063[] = [
            'key' => ++$k, 'label' => $lbl, 'label2' => 'A', 'fila' => $filaA,
            'cond' => $cond, 'regla' => 'filas',
        ];
        $filas063[] = [
            'key' => ++$k, 'label' => $lbl, 'label2' => 'I', 'fila' => $filaA + 1,
            'cond' => $cond, 'regla' => 'min1',
        ];
    }
    $secs[] = [
        'codigo' => 'ADU06_3_AOE', 'grupo' => 'PF',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_06_3_PF_METODOS',
        'titulo' => '6. PLANIFICACION FAMILIAR — Anticoncepción Oral de Emergencia',
        'sub' => 'AOE (YUZPE / PROGESTÁGENO)',
        'layout' => 'AOE', 'eje' => null, 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas063,
        'nota' => 'Fila A = Actividad (count(*)); fila I = Insumo (sum(LAB), LAB = '
                 . 'iif(TRY_CONVERT(int,valor_lab) IS NULL o <1, 1, valor_lab)). Solo mujeres.',
    ];

    /* ------------------------------------------------------------
     * 07 - usp_TRAMA_BASE_ADULTO_RPT_07_CANCER (124-125)
     * 99402.08 con valor_lab 1/2.
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU07_CANCER', 'grupo' => 'ESP',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_07_CANCER',
        'titulo' => '7. PREVENCION DE CANCER', 'sub' => 'Sesión',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CANCER',
             'label2' => '1° SESION', 'fila' => 124,
             'cond' => aduY($e3059, $D, ['cod' => '99402.08'], ['vl' => '1'])],
            ['key' => 2, 'label' => 'CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CANCER',
             'label2' => '2° SESION', 'fila' => 125,
             'cond' => aduY($e3059, $D, ['cod' => '99402.08'], ['vl' => '2'])],
        ],
    ];

    /* ------------------------------------------------------------
     * 08 - usp_TRAMA_BASE_ADULTO_RPT_08_SALUD_BUCAL (130-157)
     * 7 prestaciones x (NO GESTANTES|GESTANTES) x (INICIAN|TRATADO).
     *
     * v1.1.0 (correccion de reglas): en ASESORIA NUTRICIONAL (D1310),
     * PROFILAXIS DENTAL (D1110), CONSULTA ESTOMATOLOGICA NO ESPECIALIZADA
     * (D0140), APLICACION FLUOR BARNIZ (D1206) y RASPAJE DENTAL (D4355) la
     * clasificacion GESTANTE / NO GESTANTE ya NO usa el marcador valor_lab
     * 'G' de la cita (#GESTANTES / citaTiene / citaSin del SP): usa el campo
     * Descripcion_Otra_Condicion DE LA PROPIA FILA de la tabla consolidada:
     *   GESTANTES    -> Descripcion_Otra_Condicion = 'GESTANTE'
     *   NO GESTANTES -> Descripcion_Otra_Condicion <> 'GESTANTE'
     *                   (NULL/vacio/otro valor: la atencion no registra otra
     *                    condicion y cuenta como NO GESTANTE)
     * EXAMEN ESTOMATOLOGICO (D0120) e INSTRUCCION DE HIGIENE ORAL (D0130)
     * conservan el criterio original del SP (#GESTANTES = cita con valor_lab
     * 'G') porque no fueron parte de la correccion solicitada.
     * ------------------------------------------------------------ */
    $sb = [ // [Dx, etiqueta, codigo, filaBase, gestPorOtraCond]
        [1, 'EXAMEN ESTOMATOLOGICO', 'D0120', 130, true],
        [2, 'INSTRUCCION DE HIGIENE ORAL', 'D1330', 134, true],
        [3, 'ASESORIA NUTRICIONAL PARA EL CONTROL DE ENFERMEDADES DENTALES', 'D1310', 138, true],
        [4, 'PROFILAXIS DENTAL', 'D1110', 142, true],
        [5, 'CONSULTA ESTOMATOLOGICA NO ESPECIALIZADA', 'D0140', 146, true],
        [6, 'APLICACION FLUOR BARNIZ', 'D1206', 150, true],
    ];
    $filas08 = [];
    $k = 0;
    foreach ($sb as [$dx, $lbl, $cod, $fb, $porOtraCond]) {
        foreach ([1 => 'INICIAN', 2 => 'TRATADO/CONTROLADO'] as $cond => $condLbl) {
            if ($porOtraCond) {
                // ---- v1.1.0: GESTANTE/NO GESTANTE por Descripcion_Otra_Condicion ----
                $filas08[] = [
                    'key' => ++$k, 'label' => $lbl, 'label2' => 'NO GESTANTES', 'label3' => $condLbl,
                    'fila' => $fb + ($cond - 1),
                    'cond' => aduY($e3059, $D, ['cod' => $cod], ['vl' => (string)$cond],
                                   ['otraCondNo' => 'GESTANTE']),
                ];
                $filas08[] = [
                    'key' => ++$k, 'label' => $lbl, 'label2' => 'GESTANTES', 'label3' => $condLbl,
                    'fila' => $fb + 2 + ($cond - 1),
                    'cond' => aduY($e3059, $D, ['cod' => $cod], ['vl' => (string)$cond],
                                   ['sexo' => 'F'], ['otraCond' => 'GESTANTE']),
                ];
            } else {
                // ---- Criterio original del SP: #GESTANTES = cita con valor_lab='G' ----
                $filas08[] = [
                    'key' => ++$k, 'label' => $lbl, 'label2' => 'NO GESTANTES', 'label3' => $condLbl,
                    'fila' => $fb + ($cond - 1),
                    'cond' => aduY($e3059, $D, ['cod' => $cod], ['vl' => (string)$cond],
                                   ['citaSin' => ['vl' => 'G']]),
                ];
                $filas08[] = [
                    'key' => ++$k, 'label' => $lbl, 'label2' => 'GESTANTES', 'label3' => $condLbl,
                    'fila' => $fb + 2 + ($cond - 1),
                    'cond' => aduY($e3059, $D, ['cod' => $cod], ['vl' => (string)$cond],
                                   ['sexo' => 'F'], ['citaTiene' => ['vl' => 'G']]),
                ];
            }
        }
    }
    // Raspaje dental (D4355) v1.1.0: misma logica de la correccion (1 y 2):
    // GESTANTES -> Descripcion_Otra_Condicion='GESTANTE' (las filas 156/157
    // ahora cuentan de verdad; antes quedaban en 0 porque el SP escribia la
    // TABLA2 del raspaje gestante con Gestacion=0), NO GESTANTES ->
    // Descripcion_Otra_Condicion<>'GESTANTE' (sin las ramas TABLA1/TABLA2
    // ni los marcadores FIG/FIN de #RASPAJE_GESTANTES: la condicion
    // gestante ya no depende de otras filas de la cita).
    $raspajeBase = aduY($e3059, $D, ['cod' => 'D4355']);
    $filas08[] = [
        'key' => ++$k, 'label' => 'RASPAJE DENTAL', 'label2' => 'NO GESTANTES', 'label3' => 'INICIAN',
        'fila' => 154,
        'cond' => aduY($raspajeBase, ['vl' => '1'], ['otraCondNo' => 'GESTANTE']),
    ];
    $filas08[] = [
        'key' => ++$k, 'label' => 'RASPAJE DENTAL', 'label2' => 'NO GESTANTES', 'label3' => 'TRATADO/CONTROLADO',
        'fila' => 155,
        'cond' => aduY($raspajeBase, ['vl' => '2'], ['otraCondNo' => 'GESTANTE']),
    ];
    $filas08[] = [
        'key' => ++$k, 'label' => 'RASPAJE DENTAL', 'label2' => 'GESTANTES', 'label3' => 'INICIAN',
        'fila' => 156,
        'cond' => aduY($raspajeBase, ['vl' => '1'], ['sexo' => 'F'], ['otraCond' => 'GESTANTE']),
    ];
    $filas08[] = [
        'key' => ++$k, 'label' => 'RASPAJE DENTAL', 'label2' => 'GESTANTES', 'label3' => 'TRATADO/CONTROLADO',
        'fila' => 157,
        'cond' => aduY($raspajeBase, ['vl' => '2'], ['sexo' => 'F'], ['otraCond' => 'GESTANTE']),
    ];
    $secs[] = [
        'codigo' => 'ADU08_SALUD_BUCAL', 'grupo' => 'ESP',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_08_SALUD_BUCAL',
        'titulo' => '8. ATENCION EN SALUD BUCAL', 'sub' => 'Prestación',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas08,
        'nota' => 'Gestante (ASESORIA NUTRICIONAL, PROFILAXIS DENTAL, CONSULTA ESTOMATOLOGICA, '
                 . 'APLICACION FLUOR BARNIZ y RASPAJE DENTAL) = Descripcion_Otra_Condicion=\'GESTANTE\' '
                 . 'de la propia fila de la base de datos; NO GESTANTES = Descripcion_Otra_Condicion'
                 . '<>\'GESTANTE\' (incluye NULL/vacio). EXAMEN ESTOMATOLOGICO e INSTRUCCION DE '
                 . 'HIGIENE ORAL conservan el criterio del SP original (#GESTANTES: cita con alguna '
                 . 'fila valor_lab=\'G\').',
    ];

    /* ------------------------------------------------------------
     * 09 - usp_TRAMA_BASE_ADULTO_RPT_09_SALUD_MENTAL (162-172)
     * v1.2.0 - correccion de criterios de la seccion:
     *   GRUPO 1 (ENTREVISTA DE TAMIZAJE, filas 162-166): la fila cuenta con
     *   Codigo_Item + Tipo_Diagnostico + Valor_Lab:
     *     VIOLENCIA FAMILIAR        162: 96150.01 + D + VIF
     *     ALCOHOL Y DROGAS          165: 96150.02 + D + AD
     *     TRASTORNOS DEPRESIVOS     166: 96150.03 + D + TD
     *     VIOLENCIA SEXUAL/SOCIAL   163/164: conservan el SP original
     *     (fg_tipo='CX' + U140 + VSX/VS, tipitem D).
     *   GRUPO 2 (TAMIZAJE POSITIVO, filas 167-172): cuenta la fila de la
     *   entrevista (96150.0X tipitem D) de citas que ADEMAS registran el
     *   diagnostico confirmatorio (tipitem D) en la MISMA cita:
     *     VIOLENCIA      167: (96150.01 D) + (R456 D)
     *     TABACO         168: (96150.02 D) + (Z720 D)
     *     ALCOHOL        169: (96150.02 D) + (Z721 D)
     *     DROGAS         170: (96150.02 D) + (Z722 D)
     *     DEPRESION      171: (96150.03 D) + (Z133 D)
     *     PSICOSIS       172: (96150.04 D) + (Z133 D)
     *   Mismos codigos 96150.01-04 del modulo Adolescente (seccion 14) y del
     *   #EMBARAZO del materno; el "+" del usuario = AND de predicados (la
     *   entrevista es la fila contada y el diagnostico va como EXISTS de la
     *   cita, igual que hacia el SP original con U140 TD/EP + #TEMPORAL1).
     * ------------------------------------------------------------ */
    $citaZ133 = ['cod' => 'Z133', 'tip' => 'D'];
    $secs[] = [
        'codigo' => 'ADU09_SALUD_MENTAL', 'grupo' => 'ESP',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_09_SALUD_MENTAL',
        'titulo' => '9. SALUD MENTAL', 'sub' => 'Prestaciones de Salud Mental en Adultos',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'ENTREVISTA DE TAMIZAJE', 'label2' => 'VIOLENCIA FAMILIAR', 'fila' => 162,
             'cond' => aduY($e3059, $D, ['cod' => '96150.01'], ['vl' => 'VIF'])],
            ['key' => 2, 'label' => 'ENTREVISTA DE TAMIZAJE', 'label2' => 'VIOLENCIA SEXUAL', 'fila' => 163,
             'cond' => aduY($e3059, $D, ['fgTipo' => 'CX'], ['cod' => 'U140'], ['vl' => 'VSX'])],
            ['key' => 3, 'label' => 'ENTREVISTA DE TAMIZAJE', 'label2' => 'VIOLENCIA SOCIAL', 'fila' => 164,
             'cond' => aduY($e3059, $D, ['fgTipo' => 'CX'], ['cod' => 'U140'], ['vl' => 'VS'])],
            ['key' => 4, 'label' => 'ENTREVISTA DE TAMIZAJE', 'label2' => 'ALCOHOL Y DROGAS', 'fila' => 165,
             'cond' => aduY($e3059, $D, ['cod' => '96150.02'], ['vl' => 'AD'])],
            ['key' => 5, 'label' => 'ENTREVISTA DE TAMIZAJE', 'label2' => 'TRASTORNOS DEPRESIVOS', 'fila' => 166,
             'cond' => aduY($e3059, $D, ['cod' => '96150.03'], ['vl' => 'TD'])],
            ['key' => 6, 'label' => 'TAMIZAJE POSITIVO', 'label2' => 'PROBLEMAS RELACIONADOS CON VIOLENCIA', 'fila' => 167,
             'cond' => aduY($e3059, $D, ['cod' => '96150.01'],
                            ['citaTiene' => ['cod' => 'R456', 'tip' => 'D']])],
            ['key' => 7, 'label' => 'TAMIZAJE POSITIVO', 'label2' => 'PROBLEMAS RELACIONADOS CON EL USO DE TABACO', 'fila' => 168,
             'cond' => aduY($e3059, $D, ['cod' => '96150.02'],
                            ['citaTiene' => ['cod' => 'Z720', 'tip' => 'D']])],
            ['key' => 8, 'label' => 'TAMIZAJE POSITIVO', 'label2' => 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE ALCOHOL', 'fila' => 169,
             'cond' => aduY($e3059, $D, ['cod' => '96150.02'],
                            ['citaTiene' => ['cod' => 'Z721', 'tip' => 'D']])],
            ['key' => 9, 'label' => 'TAMIZAJE POSITIVO', 'label2' => 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE DROGAS', 'fila' => 170,
             'cond' => aduY($e3059, $D, ['cod' => '96150.02'],
                            ['citaTiene' => ['cod' => 'Z722', 'tip' => 'D']])],
            ['key' => 10, 'label' => 'TAMIZAJE POSITIVO', 'label2' => 'DEPRESIÓN', 'fila' => 171,
             'cond' => aduY($e3059, $D, ['cod' => '96150.03'],
                            ['citaTiene' => $citaZ133])],
            ['key' => 11, 'label' => 'TAMIZAJE POSITIVO', 'label2' => 'PSICOSIS', 'fila' => 172,
             'cond' => aduY($e3059, $D, ['cod' => '96150.04'],
                            ['citaTiene' => $citaZ133])],
        ],
        'nota' => 'v1.2.0 — Grupo 1 (entrevista de tamizaje): la fila cuenta por '
                 . 'Codigo_Item + tipitem D + valor_lab (96150.01+VIF, 96150.02+AD, '
                 . '96150.03+TD); violencia sexual/social conservan el criterio del '
                 . 'SP original (fg_tipo=\'CX\' + U140 + VSX/VS). Grupo 2 (tamizaje '
                 . 'positivo): se cuenta la fila de la entrevista (96150.01/02/03/04 '
                 . 'tipitem D) cuando la MISMA cita registra ademas el diagnostico '
                 . 'confirmatorio tipitem D (R456/Z720/Z721/Z722/Z133) — el EXISTS '
                 . 'del #TEMPORAL1 del T-SQL, generalizado a todo el grupo 2. '
                 . 'Se retira el filtro fg_tipo=\'CX\' de las filas corregidas: el '
                 . 'criterio de cada item queda definido por cod+tip+vl indicados '
                 . 'para la seccion (igual que el modulo adolescente con 96150.01-05).',
    ];

    /* ------------------------------------------------------------
     * 10 - usp_TRAMA_BASE_ADULTO_RPT_10_TBC (177-178)
     * #TEMPORAL1 = cita con U2142 valor_lab='1'. Diagnostico 1 =
     * U200 (D|R) en citas con U2142; Diagnostico 2 = U200 (D).
     * ------------------------------------------------------------ */
    $citaU2142 = ['cod' => 'U2142', 'vl' => '1'];
    $secs[] = [
        'codigo' => 'ADU10_TBC', 'grupo' => 'ESP',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_10_TBC',
        'titulo' => '10. TBC', 'sub' => 'Detección de TBC en Adultos',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'SINTOMATICOS RESPIRATORIOS EXAMINADOS (TBC)', 'fila' => 177,
             'cond' => aduY($e3059, ['tip' => ['D', 'R']], ['cod' => 'U200'],
                            ['citaTiene' => $citaU2142])],
            ['key' => 2, 'label' => 'SINTOMATICOS RESPIRATORIOS IDENTIFICADOS', 'fila' => 178,
             'cond' => aduY($e3059, $D, ['cod' => 'U200'])],
        ],
        'nota' => 'Asignacion fiel al SP 10: Diagnostico 1 = U200 (D o R) en citas con '
                 . 'U2142 valor_lab=1 (TAB_TEMPORAL1); Diagnostico 2 = U200 tipitem D '
                 . '(#TEMPORAL2). Los nombres de las filas siguen DimAdultoDxTBC 1/2.',
    ];

    /* ------------------------------------------------------------
     * 11 - usp_TRAMA_BASE_ADULTO_RPT_11_SSR (183-195)
     * ------------------------------------------------------------ */
    $z359fam = ['Z359', 'Z3591', 'Z3491', 'Z3592', 'Z3492', 'Z3593', 'Z3493'];
    $z3596   = ['Z3591', 'Z3491', 'Z3592', 'Z3492', 'Z3593', 'Z3493'];
    $secs[] = [
        'codigo' => 'ADU11_SSR', 'grupo' => 'SSR',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_11_SSR',
        'titulo' => '11. SALUD SEXUAL Y REPRODUCTIVA',
        'sub' => 'Prestaciones de Salud Sexual y Reproductiva en Adultos, Gestantes y No Gestantes',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'N° PERSONAS TAMIZADAS PARA VIH (INCLUYE DATOS DE ESNSSR Y ESN TB)', 'fila' => 183,
             'cond' => aduY($e3059, $D, $R1, ['cod' => ['Z7172', 'Z7173']])],
            ['key' => 2, 'label' => 'N° PERSONAS TAMIZADAS PARA VIH CON RESULTADO REACTIVO', 'fila' => 184,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'Z7172'])],
            ['key' => 3, 'label' => 'GESTANTES ATENDIDAS EN 1° TRIMESTRE DE GESTACION', 'fila' => 185,
             'cond' => aduY($e3059, $D, ['cod' => ['Z3591', 'Z3491']], ['vl' => '1'])],
            ['key' => 4, 'label' => 'GESTANTES ATENDIDAS EN 2° TRIMESTRE DE GESTACION', 'fila' => 186,
             'cond' => aduY($e3059, $D, ['cod' => ['Z3592', 'Z3492']], ['vl' => '1'])],
            ['key' => 5, 'label' => 'GESTANTES ATENDIDAS EN 3° TRIMESTRE DE GESTACION', 'fila' => 187,
             'cond' => aduY($e3059, $D, ['cod' => ['Z3593', 'Z3493']], ['vl' => '1'])],
            ['key' => 6, 'label' => 'GESTANTES CONTROLADAS (6° CONTROL PRENATAL)', 'fila' => 188,
             'cond' => aduY($e3059, $D, ['cod' => $z3596], ['vl' => '6'])],
            ['key' => 7, 'label' => 'GESTANTES CON ATENCION PRENATAL REENFOCADA', 'fila' => 189,
             'cond' => aduY($e3059, $D, $R1, ['cod' => $z359fam], ['vlGeNum' => 6],
                            ['citaTiene' => ['vl' => 'TA']])],
            ['key' => 8, 'label' => 'SUPLEMENTACION CON SULFATO FERROSO EN GESTANTES', 'fila' => 190,
             'cond' => aduY($e3059, $D, $R1, ['cod' => $z359fam],
                            ['citaTiene' => ['cod' => 'Z298', 'vl' => 'SF6']])],
            ['key' => 9, 'label' => 'SUPLEMENTACION CON ACIDO FOLICO EN GESTANTES', 'fila' => 191,
             'cond' => aduY($e3059, $D, $R1, ['cod' => $z359fam],
                            ['citaTiene' => ['cod' => 'Z298', 'vl' => 'AF3']])],
            ['key' => 10, 'label' => 'PARTO', 'fila' => 192,
             'cond' => aduY($e3059, $D, $R1, ['codFEntre' => ['O80', 'O84']])],
            ['key' => 11, 'label' => 'TOMA DE PAP', 'fila' => 193,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'Z359'],
                            ['citaTiene' => ['cod' => ['88141', 'Z0141']]])],
            ['key' => 12, 'label' => 'ENTREGA DE RESULTADOS PAP', 'fila' => 194,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'U2601'])],
            ['key' => 13, 'label' => 'RESULTADO POSITIVO DE PAP (GESTANTES)', 'fila' => 195,
             'cond' => aduY($e3059, $D, $R1,
                            ['cod' => ['D060', 'D061', 'D069', 'N879', 'N870', 'N871', 'N872']],
                            ['citaTieneTodo' => [
                                ['cualquieraDe' => [['cod' => 'U2601'], ['cod' => 'Z359']]],
                                ['cod' => 'U2601', 'tip' => 'D'],
                                ['cualquieraDe' => [['vl' => 'G'], ['cod' => 'Z359']]],
                            ]])],
        ],
        'nota' => 'Prestacion 13 (fila 195): el SP exige que la cita tenga U2601 o Z359 '
                 . '(#TEMPORAL4), una fila U2601 tipitem D, y ademas (valor_lab=\'G\' o '
                 . 'Z359) — el EXISTS del #TEMPORAL4 del T-SQL.',
    ];

    /* ------------------------------------------------------------
     * 12 - usp_TRAMA_BASE_ADULTO_RPT_12_VIH (200-206)
     * El CASE nunca produce Diagnostico 5 (fila 204 = 0) y asigna
     * 86706 -> 6 (fila 205) y Z115-PRA -> 7 (fila 206).
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU12_VIH', 'grupo' => 'SSR',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_12_VIH',
        'titulo' => '12. ITS-VIH', 'sub' => 'Diagnóstico',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'CONSEJERIA/ORIENTACION EN PREVENCION DE ITS, VIH, HEPATITIS B (1° VEZ)', 'fila' => 200,
             'cond' => aduY($e3059, $D, ['cod' => '99402.05'], ['vl' => 'PV'])],
            ['key' => 2, 'label' => 'CONSEJERIA/ORIENTACION EN PREVENCION DE ITS, VIH, HEPATITIS B (MÁS DE UNA VEZ)', 'fila' => 201,
             'cond' => aduY($e3059, $D, ['cod' => '99402.05'], ['vlVacia' => true])],
            ['key' => 3, 'label' => 'CONSEJERIA PRE-TEST PARA VIH', 'fila' => 202,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'Z7172'])],
            ['key' => 4, 'label' => 'CONSEJERIA POST-TEST POSITIVO PARA VIH', 'fila' => 203,
             'cond' => aduY($e3059, $D, $R1, ['cod' => 'Z7173'])],
            ['key' => 5, 'label' => 'CONSEJERIA POST-TEST NEGATIVO PARA VIH', 'fila' => 204, 'cond' => null,
             'nota' => 'El CASE del SP 12 nunca produce Diagnostico 5: esta fila queda en 0, '
                     . 'igual que en el Excel generado con ODBC.'],
            ['key' => 6, 'label' => 'TAMIZAJE EN HEPATITIS B (REACTIVO)', 'fila' => 205,
             'cond' => aduY($e3059, $D, $R1, ['cod' => '86706'])],
            ['key' => 7, 'label' => 'TAMIZAJE EN HEPATITIS B (NO REACTIVO)', 'fila' => 206,
             'cond' => aduY($e3059, $D, ['cod' => 'Z115'], ['vl' => 'PRA'])],
        ],
    ];

    /* ------------------------------------------------------------
     * 13 - usp_TRAMA_BASE_ADULTO_RPT_13_SALUD_OCULAR (211-215)
     * ------------------------------------------------------------ */
    $ocu = [
        ['CONSEJERIA EN SALUD OCULAR',            211, '99401.16'],
        ['DETERMINACION DE LA AGUDEZA VISUAL',    212, '99173'],
        ['LIMITACION VISUAL MODERADA',            213, 'H456'],
        ['CATARATA NO ESPECIFICADA',              214, 'H259'],
        ['EXAMEN DE LOS OJOS Y LA VISION',        215, 'Z006'],
    ];
    $filas13 = [];
    foreach ($ocu as $i => [$lbl, $fila, $cod]) {
        $filas13[] = [
            'key' => $i + 1, 'label' => $lbl, 'fila' => $fila,
            'cond' => aduY($e3059, $D, $R1, ['cod' => $cod]),
        ];
    }
    $secs[] = [
        'codigo' => 'ADU13_OCULAR', 'grupo' => 'OCU',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_13_SALUD_OCULAR',
        'titulo' => '13. SALUD OCULAR', 'sub' => 'Diagnóstico',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas13,
    ];

    /* ------------------------------------------------------------
     * 14 - usp_TRAMA_BASE_ADULTO_RPT_14_APP (220-224)
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU14_APP', 'grupo' => 'OCU',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_14_APP',
        'titulo' => '14. ACTIVIDADES PREVENTIVAS PROMOCIONALES', 'sub' => 'Diagnóstico',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'VISITA FAMILIAR INTEGRAL (CON 1 VISITA)', 'fila' => 220,
             'cond' => aduY($A, ['cod' => '99344'], ['vl' => '1'])],
            ['key' => 2, 'label' => 'VISITA FAMILIAR INTEGRAL (CON 2 VISITAS)', 'fila' => 221,
             'cond' => aduY($A, ['cod' => '99344'], ['vl' => '2'])],
            ['key' => 3, 'label' => 'VISITA FAMILIAR INTEGRAL (CON 3 A MÁS VISITAS)', 'fila' => 222,
             'cond' => aduY($A, ['cod' => '99344'], ['vlGeNum' => 3])],
            ['key' => 4, 'label' => 'V.F.I DE IDENTIFICACION DE RIESGOS Y NECESIDADES', 'fila' => 223,
             'cond' => aduY($A, ['cod' => 'C0011.01'])],
            ['key' => 5, 'label' => 'V.F.I PARA CUIDADOS ESENCIALES', 'fila' => 224,
             'cond' => aduY($A, ['cod' => 'C0011.02'])],
        ],
    ];

    /* ------------------------------------------------------------
     * 15 - usp_TRAMA_BASE_ADULTO_RPT_15_VACUNA (229-238)
     * #GESTANTES separa dT no gestantes (1-3) de gestantes (4-6).
     * ------------------------------------------------------------ */
    $secs[] = [
        'codigo' => 'ADU15_VACUNA', 'grupo' => 'INM',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_15_VACUNA',
        'titulo' => '15. INMUNIZACIONES', 'sub' => 'Inmunizaciones en Adultos',
        'layout' => 'FMT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => [
            ['key' => 1, 'label' => 'VACUNACION DIFTOTETÁNICA(dT) - DOSIS 1 (NO GESTANTES)', 'fila' => 229,
             'cond' => aduY($A, ['cod' => 'Z2781'], ['vl' => ['1', 'D1']], ['citaSin' => ['vl' => 'G']])],
            ['key' => 2, 'label' => 'VACUNACION DIFTOTETÁNICA(dT) - DOSIS 2 (NO GESTANTES)', 'fila' => 230,
             'cond' => aduY($A, ['cod' => 'Z2781'], ['vl' => ['2', 'D2']], ['citaSin' => ['vl' => 'G']])],
            ['key' => 3, 'label' => 'VACUNACION DIFTOTETÁNICA(dT) - DOSIS 3 (PROTEGIDA) (NO GESTANTES)', 'fila' => 231,
             'cond' => aduY($A, ['cod' => 'Z2781'], ['vl' => ['3', 'D3']], ['citaSin' => ['vl' => 'G']])],
            ['key' => 4, 'label' => 'VACUNACION DIFTOTETÁNICA(dT) - DOSIS 1 (GESTANTES)', 'fila' => 232,
             'cond' => aduY($A, ['cod' => 'Z2781'], ['vl' => ['1', 'D1']], ['citaTiene' => ['vl' => 'G']])],
            ['key' => 5, 'label' => 'VACUNACION DIFTOTETÁNICA(dT) - DOSIS 2 (GESTANTES)', 'fila' => 233,
             'cond' => aduY($A, ['cod' => 'Z2781'], ['vl' => ['2', 'D2']], ['citaTiene' => ['vl' => 'G']])],
            ['key' => 6, 'label' => 'VACUNACION DIFTOTETÁNICA(dT) - DOSIS 3 (PROTEGIDA) (GESTANTES)', 'fila' => 234,
             'cond' => aduY($A, ['cod' => 'Z2781'], ['vl' => ['3', 'D3']], ['citaTiene' => ['vl' => 'G']])],
            ['key' => 7, 'label' => 'VACUNACION CONTRA LA HEPATITIS B - DOSIS 1', 'fila' => 235,
             'cond' => aduY($A, ['cod' => '90744'], ['vl' => ['1', 'D1']])],
            ['key' => 8, 'label' => 'VACUNACION CONTRA LA HEPATITIS B - DOSIS 2', 'fila' => 236,
             'cond' => aduY($A, ['cod' => '90744'], ['vl' => ['2', 'D2']])],
            ['key' => 9, 'label' => 'VACUNACION CONTRA LA HEPATITIS B - DOSIS 3 (PROTEGIDO)', 'fila' => 237,
             'cond' => aduY($A, ['cod' => '90744'], ['vl' => ['3', 'D3']])],
            ['key' => 10, 'label' => 'INFLUENZA - DOSIS 1', 'fila' => 238,
             'cond' => aduY($A, ['cod' => '90657'], ['vl' => 'D1'])],
        ],
        'nota' => 'Gestante = cita con alguna fila valor_lab=\'G\' (#GESTANTES del SP, '
                 . 'calculado sobre las citas con Z2781).',
    ];

    /* ------------------------------------------------------------
     * 16 - usp_TRAMA_BASE_ADULTO_RPT_16_SESIONES (243-246)
     * SIN filtro de edad (comentado en el T-SQL) y SIN "sexo is not
     * null"; id_persona IS NULL. Nº=count(Sesion), Part/Benef=sum(LAB).
     * ------------------------------------------------------------ */
    $ses = [
        ['DEMOSTRATIVA',                    243, 'C0010'],
        ['EDUCATIVA',                       244, 'C0009'],
        ['CAMPAÑAS',                        245, 'U114'],
        ['TALLERES DE HABILIDADES SOCIALES', 246, 'U122'],
    ];
    $filas16 = [];
    foreach ($ses as $i => [$lbl, $fila, $cod]) {
        $filas16[] = [
            'key' => $i + 1, 'label' => $lbl, 'fila' => $fila,
            'reglas' => ['num' => 'filas', 'part' => 'min1'],
            'cond' => aduY(['tip' => 'D'], $R1, ['pacNull' => true], ['cod' => $cod],
                           ['citaTiene' => ['cod' => 'U0041']]),
        ];
    }
    $secs[] = [
        'codigo' => 'ADU16_SESIONES', 'grupo' => 'COM',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_16_SESIONES',
        'titulo' => '16. SESIONES', 'sub' => 'Sesiones',
        'layout' => 'SES', 'eje' => null, 'regla' => 'filas', 'conSexo' => false,
        'filas' => $filas16,
        'nota' => 'El SP 16 no filtra edad (viene comentado en el T-SQL) ni exige sexo; '
                 . 'id_persona IS NULL (sesiones comunitarias). #TEMPORAL1 = la cita tiene '
                 . 'una fila U0041. Nº = count(Sesion); Part/Benef = sum(LAB), LAB = '
                 . 'iif(TRY_CONVERT(int,valor_lab) IS NULL o <1, 1, valor_lab).',
    ];

    /* ------------------------------------------------------------
     * 17 - usp_TRAMA_BASE_ADULTO_RPT_17_CONSEJERIAS (251-256)
     * Una sola columna TOTAL (D250 del Excel).
     * ------------------------------------------------------------ */
    $con = [
        ['CONSEJERIA NUTRICIONAL', 251, '99403.01'],
        ['CONSEJERIA PREVENTIVA EN FACTORES DE RIESGO PARA EL CÁNCER', 252, '99402.08'],
        ['CONSEJERÍA EN PREVENCIÓN DE ENFERMEDADES NO TRANSMISIBLES', 253, '99401.31'],
        ['CONSEJERÍA EN HABILIDADES SOCIALES', 254, '99401.15'],
        ['EXAMEN DE AGUDEZA VISUAL CUANTITATIVA BILATERAL', 255, '99173'],
        ['TAMIZAJE OCULAR CON INSTRUMENTO (FOTOTAMIZAJE, REFRACCIÓN AUTOMATIZADA) BILATERAL', 256, '99174'],
    ];
    $filas17 = [];
    foreach ($con as $i => [$lbl, $fila, $cod]) {
        $filas17[] = [
            'key' => $i + 1, 'label' => $lbl, 'fila' => $fila,
            'cond' => aduY($e3059, $D, $R1, ['cod' => $cod]),
        ];
    }
    $secs[] = [
        'codigo' => 'ADU17_CONSEJERIAS', 'grupo' => 'COM',
        'procedimiento' => 'usp_TRAMA_BASE_ADULTO_RPT_17_CONSEJERIAS',
        'titulo' => '17. OTRAS CONSEJERIAS', 'sub' => 'Otras consejerías',
        'layout' => 'TOT', 'eje' => 'sexo', 'regla' => 'filas', 'conSexo' => true,
        'filas' => $filas17,
    ];

    return $secs;
}

/* ============================================================
 * 5) CONSULTA DE DATOS (helpers de BD, igual que adolescente_data.php)
 * ============================================================ */

/** ¿Existe la columna Mes_Int (generada)? */
function aduTieneColumnaMesInt(PDO $pdo): bool {
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
 * Codigos de item que intervienen en el reporte ADULTO segun los 21
 * procedimientos del archivo "03 Creacion de Procedimientos":
 *   'exactos'  -> codigos usados con cod_item = (WHERE directo)
 *   'prefijos' -> familias CIE (cod_item_f) usadas por los SP 01/05_1/05_3/11
 */
function aduCodigosInteres(): array {
    return [
        'exactos' => [
            // ---- 02: PAI ----
            '99801',
            // ---- 03: IMC ----
            'E46X3','E46X2','E46X1','Z006','E660','E669','E6692','E6693',
            // ---- 04: PAB ----
            'U8170',
            // ---- 05_1: valoracion DNT ----
            'Z019','U262','E65X','Z883','Z834',
            // ---- 05_2: HTA ----
            'I10X','H350','I120','I129',
            // ---- 05_3: DM (nefropatia/retinopatia) ----
            'E102','E112','E122','E132','E142','E103','E113','E123','E133','E143',
            // ---- 06_1: PF clasificacion ----
            '99402.04','99402.03','99402.06',
            // ---- 06_2: PF metodos ----
            '58300','99208.13','99208.05','99208.04','11975','99208.02','99208.06',
            '58600','58605','58611','55250','99208.07','99208.09','99208.08','99208.10',
            // ---- 06_3: AOE ----
            'Z3091','99208.11','Z3092','99208.12',
            // ---- 07: cancer ----
            '99402.08',
            // ---- 08: salud bucal ----
            'D1310','D0120','D1330','D1206','D1110','D0140','D4355',
            // ---- 09: salud mental (v1.2.0: codigos de la entrevista de
            //      tamizaje 96150.01-04 + diagnosticos confirmatorios) ----
            'U140','Z133','R456','Z720','Z721','Z722',
            '96150.01','96150.02','96150.03','96150.04',
            // ---- 10: TBC ----
            'U200','U2142',
            // ---- 11: SSR ----
            'Z7172','Z7173','Z359','Z3591','Z3491','Z3592','Z3492','Z3593','Z3493',
            'Z298','U2601','88141','Z0141',
            'D060','D061','D069','N879','N870','N871','N872',
            // ---- 12: ITS-VIH ----
            '99402.05','86706','Z115',
            // ---- 13: salud ocular ----
            '99401.16','99173','H456','H259',
            // ---- 14: APP ----
            '99344','C0011.01','C0011.02',
            // ---- 15: vacunas ----
            'Z2781','90744','90657',
            // ---- 16: sesiones ----
            'C0010','C0009','U114','U122','U0041',
            // ---- 17: otras consejerias ----
            '99403.01','99401.31','99401.15','99174',
        ],
        'prefijos' => [
            // ---- 01: morbilidad (cod_item_f) ----
            'A64','E66','J00','J02','J06','K02','K04','K29','M54','N39',
            // ---- 05_3: DM (cod_item_f E10x/E11x/O24x/E12-E14) ----
            'E10','E11','E12','E13','E14','O24',
            // ---- 11: parto (cod_item_f BETWEEN 'O80' AND 'O84') ----
            'O80','O81','O82','O83','O84',
        ],
        'ups' => [],   // el reporte ADULTO no usa UPS en el WHERE (sin SP 16_2/25)
    ];
}

/** Construye la clausula WHERE de codigos de interes. */
function aduWhereCodigos(array &$params): string {
    $lista = aduCodigosInteres();
    $partes = [];
    $inPh = [];
    foreach ($lista['exactos'] as $j => $c) {
        $k = ":aducod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "TRIM(Codigo_Item) IN (" . implode(',', $inPh) . ")";
    foreach ($lista['prefijos'] as $i => $pref) {
        $k = ":adupref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "TRIM(Codigo_Item) LIKE $k";
    }
    return "(" . implode(" OR ", $partes) . ")";
}

/** Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES). */
function aduGetEstablecimientosZS(PDO $pdo): array {
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
function aduWhereEstablecimientos(PDO $pdo, string $establecimiento, array &$params): string {
    $establecimiento = trim($establecimiento);
    if ($establecimiento !== '') {
        $params[':aduest'] = $establecimiento;
        return "TRIM(Codigo_Unico) = :aduest";
    }
    $lista = aduGetEstablecimientosZS($pdo);
    if (empty($lista)) {
        return '';
    }
    $ph = [];
    $i = 0;
    foreach (array_keys($lista) as $cod) {
        $k = ':aduestLista_' . $i;
        $params[$k] = $cod;
        $ph[] = $k;
        $i++;
    }
    return "TRIM(Codigo_Unico) IN (" . implode(',', $ph) . ")";
}

/** Anios disponibles en la tabla consolidada DENTRO DEL AMBITO del filtro. */
function aduGetAniosDisponibles(PDO $pdo, string $establecimiento = ''): array {
    try {
        $params = [];
        $whereEst = aduWhereEstablecimientos($pdo, $establecimiento, $params);
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
function aduGetMesesDisponibles(PDO $pdo, string $anio = '', string $establecimiento = ''): array {
    try {
        $params = [];
        $cond = [];
        if (trim($anio) !== '') {
            $cond[] = "Anio = :anio";
            $params[':anio'] = (string)$anio;
        }
        $whereEst = aduWhereEstablecimientos($pdo, $establecimiento, $params);
        if ($whereEst !== '') $cond[] = $whereEst;
        $colMes = aduTieneColumnaMesInt($pdo) ? 'Mes_Int' : "CAST(TRIM(Mes) AS UNSIGNED)";
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

/** Conexion PDO dedicada en modo UNBUFFERED (streaming). */
function aduAbrirConexionStreaming(): ?PDO {
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
function aduDiagnosticoEntorno(PDO $pdo): array {
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
 * Aporte de una fila segun la regla de conteo del T-SQL:
 *   'filas'     : 1  (count(*))
 *   'insumo06_2': LAB = iif(TRY_CONVERT null OR metodo 8-13, 0, valor)
 *   'min1'      : LAB = iif(TRY_CONVERT null o <1, 1, valor)
 */
function aduAporte(array $f, string $regla, array $ctxFila): int {
    switch ($regla) {
        case 'insumo06_2':
            $m = (int)($ctxFila['metodoKey'] ?? 0);
            if ($m >= 8 && $m <= 13) return 0;
            $vl = $f['vl'];
            return ($vl !== null && $vl !== '' && is_numeric($vl)) ? (int)$vl : 0;
        case 'min1':
            $vl = $f['vl'];
            if ($vl === null || $vl === '' || !is_numeric($vl)) return 1;
            $n = (int)$vl;
            return $n < 1 ? 1 : $n;
        case 'filas':
        default:
            return 1;
    }
}

/**
 * Cuenta/suma las filas que cumplen una condicion aplicando la regla y
 * agregando por el eje de la seccion:
 *   eje 'sexo'    : vals[1] = mujeres (F), vals[2] = hombres (M)
 *   eje 'usuarias': vals[1] = NUEVAS, vals[2] = CONTINUADORAS (SP 06_2)
 *   eje null      : vals[''] = unica cifra (AOE / TOT)
 *   filas con 'reglas' (SP 16): vals['num'/'part'] (reglas multiples)
 *
 * El nominal del T-SQL exige "sexo is not null" en todas las secciones
 * excepto 16: lo controla $ctx['conSexo'].
 *
 * @return array ['vals' => <por eje>, 'total' => int]
 */
function aduContar(?array $cond, array $ctx): array {
    if ($cond === null) return ['vals' => [], 'total' => 0];
    $eje = $ctx['eje'] ?? null;
    $conSexo = $ctx['conSexo'] ?? true;
    $vals = [];
    $total = 0;
    $candidatos = aduCandidatosFila($cond, $ctx);

    foreach ($candidatos as $idx) {
        $f = $ctx['filas'][$idx];
        if (!aduCumple($f, $cond, $ctx)) continue;
        // "sexo is not null" del nominal (excepto seccion 16)
        if ($conSexo && $f['sexo'] === '') continue;

        // Ruta de la celda segun el eje de la seccion
        $ruta = '';
        if ($eje === 'sexo') {
            if ($f['sexo'] === 'F') $ruta = '1';
            elseif ($f['sexo'] === 'M') $ruta = '2';
            else continue;
        } elseif ($eje === 'usuarias') {
            $u = aduEjeUsuarias($f);
            if ($u === null) continue;
            $ruta = (string)$u;
        }

        // Aporte: una regla, o varias (SP 16: num + part). Las claves del
        // mapa llevan el sufijo del eje solo cuando existe ('num', 'num|1').
        if (isset($ctx['reglas']) && is_array($ctx['reglas'])) {
            foreach ($ctx['reglas'] as $rk => $rr) {
                $ap = aduAporte($f, $rr, $ctx);
                $k = $rk . ($ruta !== '' ? '|' . $ruta : '');
                $vals[$k] = ($vals[$k] ?? 0) + $ap;
                $total += $ap;
            }
        } else {
            $ap = aduAporte($f, $ctx['regla'] ?? 'filas', $ctx);
            $vals[$ruta] = ($vals[$ruta] ?? 0) + $ap;
            $total += $ap;
        }
    }
    return ['vals' => $vals, 'total' => $total];
}

/**
 * Lee un valor del mapa plano de celdas devuelto por aduContar.
 * Ejemplos: aduVal($vals, 1) lee $vals['1'] (mujeres del eje sexo);
 *           aduVal($vals, 'num') lee $vals['num|'] (SP 16, eje null);
 *           aduVal($vals) lee $vals[''] (AOE, eje null).
 */
function aduVal(array $vals, ...$keys): int {
    $key = implode('|', $keys);
    return (int)($vals[$key] ?? 0);
}

/**
 * Indices candidatos de TODAS las filas para un predicado, por buckets de
 * codigo (sobre-aproximacion; aduCumple verifica exacto).
 */
function aduCandidatosFila(array $cond, array $ctx): array {
    $total = count($ctx['filas']);
    if ($total <= 0) return [];
    $tieneCod  = isset($cond['cod']) || isset($cond['codF']) || isset($cond['codFEntre']);
    $tieneCual = isset($cond['cualquieraDe']);
    if (!$tieneCod && !$tieneCual) {
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
        if (isset($cond['codFEntre'])) {
            $min = strtoupper((string)$cond['codFEntre'][0]);
            $max = strtoupper((string)$cond['codFEntre'][1]);
            foreach ($ctx['porCodF'] as $fam => $idxs) {
                if ($fam !== '' && $fam >= $min && $fam <= $max) {
                    foreach ($idxs as $idx) $out[$idx] = true;
                }
            }
        }
        if ($tieneCual) {
            foreach ($cond['cualquieraDe'] as $sub) {
                foreach (aduCandidatosFila($sub, $ctx) as $idx) $out[$idx] = true;
            }
        }
        return array_keys($out);
    }
    return array_keys($out);
}

/**
 * Ejecuta el reporte ADULTO completo (adaptacion de los 21 procedimientos
 * del archivo "03 Creacion de Procedimientos").
 *
 * Estrategia (igual que Adolescente/ITS/Cancer/...): UNA sola consulta que
 * trae todas las filas HIS de los codigos de interes con los filtros
 * comunes aplicados, y el matching de cada fila del reporte se resuelve en
 * PHP con el DSL (que espeja los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function aduEjecutarReporte(PDO $pdo, array $filtros): array {
    $t0 = microtime(true);
    $tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';

    // ---- 0) Proteccion contra HTTP 500 en hostings compartidos ----
    if (function_exists('set_time_limit')) @set_time_limit(0);
    @ini_set('memory_limit', '512M');
    $MAX_FILAS = 2000000;

    // ---- 1) Clausula WHERE comun (filtros del usuario) ----
    $where = ["1=1"];
    $params = [];
    $colMesQ = aduTieneColumnaMesInt($pdo) ? 'Mes_Int' : 'CAST(TRIM(Mes) AS UNSIGNED)';
    if (!empty($filtros['anioNonNull'])) {
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
    $whereEst = aduWhereEstablecimientos($pdo, (string)($filtros['establecimiento'] ?? ''), $params);
    if ($whereEst !== '') {
        $where[] = $whereEst;
    }

    // ---- 2) Codigos de interes del paquete ADULTO ----
    $whereCod = aduWhereCodigos($params);

    // v1.1.0: Descripcion_Otra_Condicion ('GESTANTE'...) se trae para que la
    // seccion 08 (salud bucal) clasifique GESTANTES / NO GESTANTES con dicho
    // campo (aduFila lo normaliza como 'otraCond').
    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab,
                   Id_Correlativo_Item, Id_Correlativo_Lab,
                   Fg_Tipo, Id_Ups, Codigo_Unico, Anio, Mes,
                   Descripcion_Otra_Condicion
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    $pdoStream = aduAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = aduFila($r);
            if ($f['cod'] === '' && $f['ups'] === '') continue;
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

    return aduEjecutarDesdeFilas($filas, $t0);
}

/**
 * Ejecuta el pipeline de matching/agregacion del reporte a partir de las
 * filas HIS YA normalizadas (aduFila). Separada de aduEjecutarReporte para
 * poder validar el pipeline sin base de datos.
 */
function aduEjecutarDesdeFilas(array $filas, float $t0 = 0.0): array {
    if ($t0 === 0.0) $t0 = microtime(true);

    // Indices por cita (cita/cod/codF) y buckets globales (cod/codF)
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
    ];

    // ---- Matching por seccion ----
    $seccionesOut = [];
    $totCasos = 0;
    $totPorGrupo = [];

    foreach (aduSecciones() as $sec) {
        $ctxSec = $ctx + [
            'eje'     => $sec['eje'] ?? null,
            'conSexo' => $sec['conSexo'] ?? true,
        ];
        $secOut = $sec;
        $totalSec = 0;
        $filasOut = [];

        foreach ($sec['filas'] as $fr) {
            $ctxFila = $ctxSec + ['regla' => $fr['regla'] ?? $sec['regla'] ?? 'filas'];
            if (isset($fr['metodoKey'])) $ctxFila['metodoKey'] = $fr['metodoKey'];
            if (isset($fr['reglas'])) $ctxFila['reglas'] = $fr['reglas'];

            // 'ramas' = varios INSERT del mismo SP que el consolidado suma
            // en la misma celda (SP 08 raspaje: TABLA1 + TABLA2).
            $ramas = isset($fr['ramas']) ? $fr['ramas']
                   : (isset($fr['cond']) ? [$fr['cond']] : [null]);
            $vals = [];
            $totFila = 0;
            foreach ($ramas as $cond) {
                $r = aduContar($cond, $ctxFila);
                foreach ($r['vals'] as $k => $v) {
                    $vals[$k] = ($vals[$k] ?? 0) + $v;
                }
                $totFila += $r['total'];
            }
            $filasOut[] = [
                'key' => $fr['key'], 'label' => $fr['label'],
                'label2' => $fr['label2'] ?? null, 'label3' => $fr['label3'] ?? null,
                'nota' => $fr['nota'] ?? null,
                'fila' => $fr['fila'] ?? null,
                'vals' => $vals, 'total' => $totFila,
                'cond' => $fr['cond'] ?? null, 'ramas' => $fr['ramas'] ?? null,
                'regla' => $fr['regla'] ?? null, 'reglas' => $fr['reglas'] ?? null,
                'metodoKey' => $fr['metodoKey'] ?? null,
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
        'version'          => ADU_DATA_VERSION,
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}

/* ============================================================
 * 7) GENERACION POR TROZOS (hosting con limites: 512M / 60s)
 * ============================================================
 *
 * PROBLEMA: con filtros amplios ("Todos") la consulta unica devuelve
 * cientos de miles de filas y PHP agota memory_limit / max_execution_time
 * en InfinityFree -> HTTP 500.
 *
 * SOLUCION: dividir la generacion en TROZOS pequenos (un anio-mes, o un
 * anio-mes-EE.SS), ejecutarlos en peticiones HTTP separadas (asistente con
 * barra de progreso) y acumular los parciales en la tabla de cache
 * ADULTO_RPT_CACHE. La union de los trozos es EXACTAMENTE igual al
 * resultado de la consulta unica porque:
 *   - Las condiciones que cruzan filas (citaTiene / citaSin de los SP)
 *     se resuelven DENTRO de una misma cita, y una cita vive completa en
 *     un unico (Anio, Mes, Establecimiento).
 *   - aduContar es puramente aditivo por fila: sumar por partes = sumar todo.
 */

/** Umbral: filas TOTALES por (anio, mes) antes de subdividir por EE.SS. */
define('ADU_TROZO_MAX_FILAS', 400000);

/** Nombre de la tabla de cache/buffer del asistente. */
function aduTablaCache(): string {
    return 'ADULTO_RPT_CACHE';
}

/** Crea la tabla de cache si no existe (idempotente, sin install previo). */
function aduCacheCrearTablaSiFalta(PDO $pdo): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `" . aduTablaCache() . "` (
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
            INDEX idx_adu_cache_key (cache_key),
            INDEX idx_adu_cache_creado (creado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }
    return $ok;
}

/** Limpieza oportunista de buffers viejos (> 24 h). */
function aduCacheLimpiar(PDO $pdo): void {
    try {
        $pdo->exec("DELETE FROM `" . aduTablaCache() . "`
                    WHERE creado < (NOW() - INTERVAL 1 DAY)");
    } catch (Throwable $e) { /* tabla ausente: la crea plan */ }
}

/** Inserta un buffer nuevo y devuelve su token (null si fallo). */
function aduCacheNuevo(PDO $pdo, array $filtros, array $trozos, int $idUsuario, string $cacheKey): ?string {
    if (!aduCacheCrearTablaSiFalta($pdo)) return null;
    try {
        $st = $pdo->prepare("DELETE FROM `" . aduTablaCache() . "`
                             WHERE cache_key = :ck AND id_usuario = :idu AND estado = 'curso'");
        $st->execute([':ck' => $cacheKey, ':idu' => $idUsuario]);

        $token = bin2hex(random_bytes(16));
        $now   = date('Y-m-d H:i:s');
        $st = $pdo->prepare("INSERT INTO `" . aduTablaCache() . "`
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
function aduCacheLeer(PDO $pdo, string $token): ?array {
    $token = trim($token);
    if (!preg_match('/^[0-9a-f]{32}$/', $token)) return null;
    try {
        $st = $pdo->prepare("SELECT * FROM `" . aduTablaCache() . "` WHERE token = :t");
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Busca una generacion IDENTICA ya terminada (< 12 h) para reutilizarla. */
function aduCacheBuscarListo(PDO $pdo, string $cacheKey, int $idUsuario): ?array {
    if (!aduCacheCrearTablaSiFalta($pdo)) return null;
    try {
        $st = $pdo->prepare("SELECT * FROM `" . aduTablaCache() . "`
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
function aduCacheGuardarAvance(PDO $pdo, string $token, string $payloadJson, int $hechos, int $total): bool {
    try {
        $st = $pdo->prepare("UPDATE `" . aduTablaCache() . "`
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
 */
function aduCacheDividirTrozo(PDO $pdo, string $token, int $i): array {
    $row = aduCacheLeer($pdo, $token);
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
    $lista = aduGetEstablecimientosZS($pdo);
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
        $st = $pdo->prepare("UPDATE `" . aduTablaCache() . "`
                SET plan = :plan, total = :total, actualizado = :now
                WHERE token = :token");
        $st->execute([
            ':plan'  => json_encode($trozos, JSON_UNESCAPED_UNICODE),
            ':total' => count($trozos),
            ':now'   => date('Y-m-d H:i:s'),
            ':token'  => $token,
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
 */
function aduAmbitoDirecto(array $filtros): bool {
    $anio = trim((string)($filtros['anio'] ?? ''));
    $mes  = trim((string)($filtros['mes'] ?? ''));
    $est  = trim((string)($filtros['establecimiento'] ?? ''));
    return ($anio !== '' && $mes !== '') || ($anio !== '' && $est !== '');
}

/**
 * Construye el plan de trozos para un ambito amplio.
 * (Mismo mecanismo que adoPlanTrozos: una pasada GROUP BY (Anio, Mes_Int)
 * cubre TODOS los grupos existentes, incluidos Anio/Mes NULL o atipicos.)
 */
function aduPlanTrozos(PDO $pdo, array $filtros): array {
    $tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';
    $est   = trim((string)($filtros['establecimiento'] ?? ''));
    $fAnio = trim((string)($filtros['anio'] ?? ''));

    $out = ['trozos' => [], 'conConteo' => false, 'filas_estimadas' => null, 'indices' => true];

    try {
        $n = 0;
        foreach ($pdo->query("SHOW INDEX FROM {$tabla}") as $r) { $n++; }
        $out['indices'] = ($n > 0);
    } catch (Throwable $e) { $out['indices'] = true; }

    $nombresEst = aduGetEstablecimientosZS($pdo);
    $colMes     = aduTieneColumnaMesInt($pdo) ? 'Mes_Int' : null;

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
        $anios = aduGetAniosDisponibles($pdo, $est);
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
                  . ' — ' . aduEtiquetaMes($g['mes']);

        if ($est !== '') {
            $out['trozos'][] = $base + [
                'establecimiento' => $est,
                'etiqueta' => $etiqueta . ' — ' . ($nombresEst[$est] ?? $est),
            ];
        } elseif ($g['c'] !== null && $g['c'] > ADU_TROZO_MAX_FILAS && !empty($nombresEst)) {
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
    if (!$out['conConteo']) {
        if ($fAnio === '') {
            $out['trozos'][] = ['anioRaw' => null,
                                'establecimiento' => $est, 'etiqueta' => 'SIN AÑO (complemento)'];
        }
        $comp = ['mesFuera' => true, 'establecimiento' => $est, 'etiqueta' => 'MES ATÍPICO (complemento)'];
        if ($fAnio !== '') $comp['anioRaw'] = $fAnio;
        else                $comp['anioNonNull'] = true;
        $out['trozos'][] = $comp;
    }

    if ($out['conConteo']) $out['filas_estimadas'] = $totFilas;
    return $out;
}

/** Etiqueta corta de mes para el asistente ("MARZO", "MES 0", "MES NULL"). */
function aduEtiquetaMes($mes): string {
    if ($mes === null) return 'MES NULL';
    if (!is_numeric($mes)) return 'MES ' . (string)$mes;
    $m = (int)$mes;
    if ($m >= 1 && $m <= 12 && function_exists('getNombreMes')) {
        $n = getNombreMes($m);
        return $n !== '' ? $n : ('MES ' . $m);
    }
    return 'MES ' . $m;
}

/** Traduce un trozo del plan a los filtros de aduEjecutarReporte. */
function aduFiltrosTrozo(array $t): array {
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

/** Suma recursiva de mapas de valores (vals por eje / por regla). */
function aduSumaVals(array &$dest, array $src): void {
    foreach ($src as $k => $v) {
        if (is_array($v)) {
            if (!isset($dest[$k]) || !is_array($dest[$k])) $dest[$k] = [];
            aduSumaVals($dest[$k], $v);
        } else {
            $dest[$k] = ($dest[$k] ?? 0) + $v;
        }
    }
}

/**
 * Fusiona el reporte $nuevo (un trozo) dentro del acumulador $acum.
 * La estructura de secciones/filas es fija (aduSecciones()), asi que el
 * merge es posicional y puramente aditivo.
 */
function aduMergeReporte(?array $acum, array $nuevo): array {
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
            aduSumaVals($fA['vals'], is_array($fN['vals']) ? $fN['vals'] : []);
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
    $acum['version']          = $nuevo['version'] ?? ($acum['version'] ?? ADU_DATA_VERSION);
    return $acum;
}
