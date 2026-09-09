<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ZOONOSIS - Motor de reporte data-driven (includes/zoonosis_data.php)
 *
 * ADAPTACION FIEL del archivo "03 Creacion de Procedimientos.txt"
 * (procedimientos usp_TRAMA_BASE_ZOONOSIS_2022_*) sobre la tabla consolidada
 * MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO. Reemplaza:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales" (DimZoonosis*)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion" (TRAMA_BASE_*)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos" (13 SP)
 *   4) Excel:      refrescar la conexion ODBC de "Reporte_Actividades_Zoonosis.xlsx"
 *
 * Secciones (13 procedimientos -> 15 bloques del reporte):
 *   PONZ1  usp_..._ZOONOSIS_2022_PONZONOSIS          (13 diagnosticos + subtotales, 3 filas T/M/F)
 *   PONZ2  usp_..._ZOONOSIS_2022_PONZONOSOS_RPT02    (3 grupos, con U310)
 *   RU01   usp_..._RABIA_URBANA_01  (indicacion pre-exposicion: 90675 PRE/ST)
 *   RU02   usp_..._RABIA_URBANA_02  (administracion pre-exposicion: 90675 1/2/3 + PRE + ST)
 *   RU03   usp_..._RABIA_URBANA_03  (post-exposicion manejo de herida: W540/W550/W558/W530)
 *   RU04   usp_..._RABIA_URBANA_04  (vacunacion post-exposicion: 90675 1-5 + MOC/DS/SR/POS/CE/DA)
 *   RU05   usp_..._RABIA_URBANA_05  (suspension: 90675 2/SUS + TA/null)
 *   RU06   usp_..._RABIA_URBANA_06_1(referencias: 90675 1-5 + DVR/DVC/CC)
 *   FRVH   usp_..._RABIA_URBANA_06_2(VARH PRE/POS: 90675 R2 + ST/null/TA R3)
 *   FRRIG  usp_..._RABIA_URBANA_06_3(frascos RIG: 90375 + 90675 1 + DVR/DVC/DS/SR/CE)
 *   RU07   usp_..._RABIA_URBANA_07  (diagnostico rabia humana: A821)
 *   RU08   usp_..._RABIA_URBANA_08  (observacion animal mordedor: C5092 + AAA04/AAA09)
 *   RU09   usp_..._RABIA_URBANA_09  (vigilancia reservorio: 88025 + C0041 + AAA04/AAA09/AAA91)
 *   RU10   usp_..._RABIA_URBANA_10  (control de foco: C0091 + C0041 + APP108)
 *   RU11   usp_..._RABIA_URBANA_11  (vacunacion canina: C5041 + APP99/APP108/APP98)
 *
 * MAPEO DE COLUMNAS (TRAMAHIS_DTSG SQL Server -> tabla consolidada MySQL):
 *   id_cita         -> Id_Cita
 *   id_tipitem      -> Tipo_Diagnostico      ('D','P','R','C')
 *   cod_item        -> Codigo_Item
 *   valor_lab       -> Valor_Lab             ('NULL' en el DSL = IS NULL)
 *   I_ROWNUM_LAB    -> Id_Correlativo_Lab
 *   id_genero       -> Id_Genero             ('F','M')
 *   edad_reg        -> Edad_Reg
 *   id_tipedad_reg  -> Tipo_Edad             ('D','M','A')
 *   renaes          -> Codigo_Unico
 *   id_persona      -> Id_Paciente
 *   fichafam        -> Ficha_Familiar        (AAA04=perro, AAA09=gato, AAA91=otros
 *                                             mamiferos, APP108/APP99/APP98=focos)
 *   periodo         -> Anio + Mes (filtros web del reporte)
 *
 * GRUPOS ETAREOS (CASE Etapa del T-SQL, DimZoonosisEtapa):
 *   PONZONOSOS: M/D -> 1; A 1-11 -> 1; A 12-17 -> 2; A 18-29 -> 3; A 30-59 -> 4; A 60+ -> 5
 *               (A 0 anios no mapea a ninguna etapa -> fila excluida, como el CASE)
 *   RABIA URBANA: M/D o A<=11 -> 1; A 12-17 -> 2; A 18-29 -> 3; A 30-59 -> 4; A 60+ -> 5
 *
 * REGLAS DE CONTEO (columna "Casos" de cada tabla consolidada):
 *   'filas'    : count(*)                     (PONZ1, PONZ2, RU03, FRVH, RU07, RU08)
 *   'personas' : count(distinct id_persona)   (RU01, RU02, RU04, RU05, RU06)
 *                Se replica como distintos pares (renaes|id_persona): es exactamente
 *                la suma que hacia el Excel de los count(distinct) POR ESTABLECIMIENTO
 *                de la tabla consolidada (una persona atendida en 2 EE.SS. cuenta 2).
 *   'suma'     : sum(TRY_CONVERT(int,valor_lab)) del registro ancla
 *                (FRRIG frascos de RIG, RU09/RU10/RU11 suman el valor de laboratorio).
 *
 * GESTANTES (#GESTANTES de PONZONOSOS): citas de MUJERES 12-59 a con diagnostico
 * ponzoñoso (X-cod D R1) que tienen alguna fila con valor_lab='G'. El marcador 'G'
 * puede estar en cualquier codigo, por eso la consulta base trae todas las filas
 * con Valor_Lab='G' (igual que hacia el modulo Materno con #GEST).
 *
 * NOTAS DE ADAPTACION (desviaciones documentadas):
 *   - aniomes >= '202201' / >= '20220101' del T-SQL se convierte en los filtros web
 *     de Anio/Mes/Establecimiento (mismos datos, distinta presentacion).
 *   - FRRIG usa el codigo 90375 tal cual aparece en el archivo 03 (frascos de RIG);
 *     si en su base ese item esta cargado como 90675, ajuste un solo lugar:
 *     zooSecciones() -> seccion FRRIG, campo 'cond' de cada fila.
 *   - RU04 bloque 6 (Re-exposicion / valor 'DA'): el SP solo produce Situacion 1-2;
 *     las filas 3-5 del bloque en la plantilla se muestran en 0 (cond = null).
 *   - Los subtotales "Total" de PONZ1/PONZ2/RU03/FRVH/FRRIG/RU08/RU09/RU10/RU11 y el
 *     bloque "Total" de RU03 son filas calculadas (suma de sus componentes), igual
 *     que las filas TOTAL de la plantilla oficial.
 *   - La fila de subtotal por grupo de PONZ1 (filas Excel 15/31/47 con SEXO='T')
 *     es calculada (la tabla TRAMA_BASE_ZOONOSIS_2022_PONZONOSOS_CONSOLIDADO del
 *     flujo original no consolidaba por GrupoID, solo por DiagnosticoKey 1-13).
 */

require_once __DIR__ . '/../config.php';

define('ZOONOSIS_DATA_VERSION', '2026-09-09-r1');

/** Version del motor de reporte de Zoonosis (para el badge del reporte). */
function zooDataVersion(): string {
    return ZOONOSIS_DATA_VERSION;
}

/* ============================================================
 * 1) NORMALIZACION DE FILAS HIS
 * ============================================================ */

/**
 * Normaliza una fila HIS traida de la tabla consolidada.
 *
 * Textos en MAYUSCULAS para replicar la colacion case-insensitive de MySQL
 * en el matching PHP (igual que materno_data.php).
 */
function zooFila(array $r): array {
    return [
        'cita'    => $r['Id_Cita'] !== null ? trim((string)$r['Id_Cita']) : '',
        'pac'     => $r['Id_Paciente'] !== null ? trim((string)$r['Id_Paciente']) : '',
        'renaes'  => $r['Codigo_Unico'] !== null ? trim((string)$r['Codigo_Unico']) : '',
        'cod'     => $r['Codigo_Item'] !== null ? strtoupper(trim((string)$r['Codigo_Item'])) : '',
        'tip'     => $r['Tipo_Diagnostico'] !== null ? strtoupper(trim((string)$r['Tipo_Diagnostico'])) : '',
        'vl'      => $r['Valor_Lab'] !== null ? strtoupper(trim((string)$r['Valor_Lab'])) : null,
        'rownum'  => $r['Id_Correlativo_Lab'] !== null ? (int)$r['Id_Correlativo_Lab'] : null,
        'sexo'    => $r['Id_Genero'] !== null ? strtoupper(trim((string)$r['Id_Genero'])) : '',
        'edad'    => $r['Edad_Reg'] !== null ? (int)$r['Edad_Reg'] : null,
        'tipEdad' => $r['Tipo_Edad'] !== null ? strtoupper(trim((string)$r['Tipo_Edad'])) : '',
        'ficha'   => $r['Id_Paciente'] !== null ? strtoupper(trim((string)$r['Id_Paciente'])) : '',
    ];
}

/* ============================================================
 * 2) PREDICADOS (mini-DSL que espeja las condiciones del T-SQL)
 * ============================================================ */

/**
 * Evalua un predicado del DSL contra una fila normalizada.
 *
 * Claves admitidas (se combinan con AND):
 *   'cod'         => '90675' | ['W540','W550']     Codigo_Item en la lista (exacto)
 *   'tip'         => 'D' | ['D','R']               Tipo_Diagnostico (id_tipitem)
 *   'vl'          => 'NULL' | 'PRE' | ['1','2']    Valor_Lab (NULL = IS NULL)
 *   'vlNum'       => true                          ISNUMERIC(valor_lab)=1
 *   'rownum'      => 1                             Id_Correlativo_Lab = N (I_ROWNUM_LAB)
 *   'sexo'        => 'F'                           Id_Genero
 *   'edadA'       => [12, 59] | [60, null]         Tipo_Edad='A' y Edad_Reg en rango
 *   'ficha'       => 'AAA04' | ['AAA04','AAA09']   Ficha_Familiar en la lista
 *   'citaTiene'   => <predicado>                   EXISTS: alguna fila de la MISMA
 *                                                   cita lo cumple (tablas #temp)
 *   'citaTieneTodo' => [<pred>, ...]               AND de EXISTS sobre la cita
 *   'citaTieneCual' => [<pred>, ...]               OR de EXISTS sobre la cita
 *   'cualquieraDe'  => [<pred>, ...]               OR de predicados sobre la misma fila
 *
 * $ctx: contexto de ejecucion (filas + indices porCita/porCod).
 */
function zooCumple(array $f, array $cond, array $ctx): bool {
    if (isset($cond['cod'])) {
        $cods = is_array($cond['cod']) ? $cond['cod'] : [$cond['cod']];
        $cods = array_map(fn($c) => strtoupper((string)$c), $cods);
        if (!in_array($f['cod'], $cods, true)) return false;
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
    if (!empty($cond['vlNum'])) {
        // ISNUMERIC(valor_lab) del T-SQL: valor presente, numerico y > 0
        if ($f['vl'] === null || $f['vl'] === '' || !is_numeric($f['vl'])) return false;
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
    if (isset($cond['ficha'])) {
        $fichas = is_array($cond['ficha']) ? $cond['ficha'] : [$cond['ficha']];
        $fichas = array_map(fn($x) => strtoupper((string)$x), $fichas);
        if (!in_array($f['ficha'], $fichas, true)) return false;
    }
    if (isset($cond['citaTiene'])) {
        if (!zooCitaTiene($ctx, $f['cita'], $cond['citaTiene'])) return false;
    }
    if (isset($cond['citaTieneTodo'])) {
        foreach ($cond['citaTieneTodo'] as $sub) {
            if (!zooCitaTiene($ctx, $f['cita'], $sub)) return false;
        }
    }
    if (isset($cond['citaTieneCual'])) {
        $ok = false;
        foreach ($cond['citaTieneCual'] as $sub) {
            if (zooCitaTiene($ctx, $f['cita'], $sub)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    if (isset($cond['cualquieraDe'])) {
        $ok = false;
        foreach ($cond['cualquieraDe'] as $sub) {
            if (zooCumple($f, $sub, $ctx)) { $ok = true; break; }
        }
        if (!$ok) return false;
    }
    return true;
}

/** EXISTS sobre la cita: alguna fila de la cita cumple el predicado (por indice). */
function zooCitaTiene(array $ctx, string $cita, array $cond): bool {
    if ($cita === '' || !isset($ctx['porCita'][$cita])) return false;
    foreach ($ctx['porCita'][$cita] as $idx) {
        if (zooCumple($ctx['filas'][$idx], $cond, $ctx)) return true;
    }
    return false;
}

/**
 * Grupo etareo (1..5) de una fila segun el CASE Etapa del T-SQL.
 * $tipo: 'ponz' (PONZONOSOS: A 1-11, la edad 0 con tipo 'A' queda fuera)
 *        'ru'   (RABIA URBANA: M/D o A<=11, incluye la edad 0 con tipo 'A')
 * Null si la fila no mapea a ninguna etapa (el T-SQL la excluye del nominal).
 */
function zooEtapa(array $f, string $tipo): ?int {
    if ($f['tipEdad'] === 'M' || $f['tipEdad'] === 'D') return 1;
    if ($f['tipEdad'] !== 'A' || $f['edad'] === null) return null;
    if ($tipo === 'ponz') {
        if ($f['edad'] < 1) return null;
    } else {
        if ($f['edad'] > 11) {
            if ($f['edad'] < 18) return 2;
            if ($f['edad'] < 30) return 3;
            if ($f['edad'] < 60) return 4;
            return 5;
        }
        return 1;
    }
    if ($f['edad'] < 12) return 1;
    if ($f['edad'] < 18) return 2;
    if ($f['edad'] < 30) return 3;
    if ($f['edad'] < 60) return 4;
    return 5;
}

/**
 * Genera una descripcion SQL-legible de una condicion del DSL (para el panel
 * "Ver condiciones SQL" del reporte web: auditoria contra el archivo 03).
 */
function zooCondicionSQL(array $cond, int $nivel = 0): string {
    $partes = [];
    foreach ($cond as $k => $v) {
        switch ($k) {
            case 'cod':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "cod_item IN (" . implode(', ', array_map(fn($x) => "'" . $x . "'", $lista)) . ")";
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
            case 'rownum':
                $partes[] = "I_ROWNUM_LAB = " . (int)$v;
                break;
            case 'sexo':
                $partes[] = "id_genero = '" . $v . "'";
                break;
            case 'edadA':
                $partes[] = "id_tipedad_reg = 'A' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 'NULL');
                break;
            case 'ficha':
                $lista = is_array($v) ? $v : [$v];
                $partes[] = "fichafam IN (" . implode(', ', array_map(fn($x) => "'$x'", $lista)) . ")";
                break;
            case 'citaTiene':
                $partes[] = "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . zooCondicionSQL($v, $nivel + 1) . ")";
                break;
            case 'citaTieneTodo':
                $subs = array_map(fn($sub) => "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . zooCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' AND ', $subs) . ")";
                break;
            case 'citaTieneCual':
                $subs = array_map(fn($sub) => "id_cita IN (SELECT id_cita FROM #TEMP WHERE " . zooCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' OR ', $subs) . ")";
                break;
            case 'cualquieraDe':
                $subs = array_map(fn($sub) => "(" . zooCondicionSQL($sub, $nivel + 1) . ")", $v);
                $partes[] = "(" . implode(' OR ', $subs) . ")";
                break;
        }
    }
    $tab = str_repeat('    ', $nivel);
    return $tab . implode("\n{$tab}AND ", $partes);
}

/* ============================================================
 * 3) DEFINICION DE SECCIONES (adaptacion fiel del archivo
 *    "03 Creacion de Procedimientos": 13 procedimientos)
 * ============================================================ */

/**
 * Devuelve la definicion completa de las secciones del reporte de Zoonosis.
 *
 * Cada seccion replica un bloque del Excel "Reporte_Actividades_Zoonosis.xlsx"
 * (hoja "Plantilla") y cada fila replica las categorias de los procedimientos
 * usp_TRAMA_BASE_ZOONOSIS_2022_* (mismo numero y orden). El layout de cada
 * seccion se describe con:
 *   'etapaTipo'   => 'ponz' | 'ru'   (CASE Etapa del T-SQL a aplicar)
 *   'conEtapas'   => bool            (columnas 0-11a .. 60 y mas)
 *   'conSexo'     => bool            (cada bloque tiene filas T/M/F)
 *   'conGestantes'=> bool            (columna GESTANTES de PONZ1/PONZ2)
 *   'regla'       => 'filas' | 'personas' | 'suma' (columna Casos del consolidado)
 *   'colsX'       => mapa de columnas Excel de la zona de datos:
 *                    ['total' => 'D', 'etapas' => [1=>'E',...5=>'N'], 'ges' => 'P']
 *   'filas'       => cada fila: ['key','niv1','niv2','sexo','cond'|'calc','fila']
 *                    ('fila' = numero de fila Excel; 'cond' = null => fila siempre 0,
 *                     como las filas 3-5 del bloque Re-exposicion de RU04)
 */
function zooSecciones(): array {
    // ===== Codigos ponzoñosos y toxicos (X2* / T63*) =====
    $xCodOfidio = ['X2091', 'X2092', 'X2093', 'X2094', 'X209'];
    $xCodAracnido = ['X2191', 'X2192', 'X219', 'X229', 'X2193'];
    $xCodOtro = ['X249', 'X239', 'X259'];
    $xCodTodos = array_merge($xCodOfidio, $xCodAracnido, $xCodOtro);
    // Predicados base: fila de diagnostico ponzoñoso D R1 + toxicacion confirmada
    $T630 = ['cod' => 'T630', 'tip' => 'D'];                  // toxicacion por veneno de serpiente
    $T631 = ['cod' => ['T630', 'T631'], 'tip' => 'D'];        // serpiente (confirmada)
    $T633 = ['cod' => 'T633', 'tip' => 'D'];                  // aranas/escorpiones
    $T632 = ['cod' => 'T632', 'tip' => 'D'];                  // escorpion
    $T634 = ['cod' => 'T634', 'tip' => 'D'];                  // otros animales venenosos
    $U310 = ['cod' => 'U310'];                                // tratamiento (suero antiofidico)
    // Predicados base rabia urbana (90675 = vacunacion antirrabica humana)
    $RU90675 = ['cod' => '90675'];
    $ruT1 = ['cod' => '99199.11', 'vl' => '1'];               // tratamiento 1 (99199.11)
    $ruT2 = ['cod' => '99199.11', 'vl' => '2'];               // tratamiento 2
    $ruSev = ['cod' => ['W540', 'W550', 'W558'], 'vl' => 'SEV', 'rownum' => 1]; // exposicion SEV R1
    $ruConocido = ['cod' => ['W540', 'W550'], 'vl' => 'C', 'rownum' => 2];      // animal conocido R2
    // Situaciones / estados vacunales (90675 valor 1..5)
    $sitVL = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'];

    // ---- Diagnósticos ponzoñosos (CASE Diagnostico del SP PONZONOSOS) ----
    $dxC = [
        1  => ['Persona mordida por Bothroops (Jergon, Loro, Machaco)',  ['cod' => 'X2091', 'tip' => 'D', 'rownum' => 1], $T630, 1],
        2  => ['Persona mordida por Mordedura de Lachesis (Shushupe)',   ['cod' => 'X2092', 'tip' => 'D', 'rownum' => 1], $T630, 1],
        3  => ['Persona mordida por Crotalus',                           ['cod' => 'X2093', 'tip' => 'D', 'rownum' => 1], $T630, 1],
        4  => ['Personas mordidas por otras especies (serpientes y lagartos venenosos)', ['cod' => 'X2094', 'tip' => 'D', 'rownum' => 1], $T631, 1],
        5  => ['Personas mordidas por ofidios sin identificación',       ['cod' => 'X209',  'tip' => 'D', 'rownum' => 1], $T630, 1],
        6  => ['Persona mordida por Loxosceles (araña casera)',          ['cod' => 'X2191', 'tip' => 'D', 'rownum' => 1], $T633, 2],
        7  => ['Persona mordida por Lacrodectus (araña silvestre o viuda negra)', ['cod' => 'X2192', 'tip' => 'D', 'rownum' => 1], $T633, 2],
        8  => ['Persona mordida por Phoneutria (araña de platano o mercado de frutas)', ['cod' => 'X219', 'tip' => 'D', 'rownum' => 1], $T633, 2],
        9  => ['Persona mordida por Escorpión o Alacrán',                ['cod' => 'X229',  'tip' => 'D', 'rownum' => 1], $T632, 2],
        10 => ['Persona mordida por Otros arácnidos o no definidos',     ['cod' => 'X2193', 'tip' => 'D', 'rownum' => 1], $T632, 2],
        11 => ['Por especies larvarias (orugas, gusanos, etc)',          ['cod' => 'X249',  'tip' => 'D', 'rownum' => 1], $T634, 3],
        12 => ['Por Avispones, Avispas, Abejas',                         ['cod' => 'X239',  'tip' => 'D', 'rownum' => 1], $T634, 3],
        13 => ['Por otros artrópodos Venenosos (hormigas, etc)',         ['cod' => 'X259',  'tip' => 'D', 'rownum' => 1], $T634, 3],
    ];
    // Filas Excel de cada diagnóstico (TOTAL/M/F): 16-56 con headers de grupo en 15/31/47
    $filaDx = [1 => 16, 2 => 19, 3 => 22, 4 => 25, 5 => 28, 6 => 32, 7 => 35, 8 => 38,
               9 => 41, 10 => 44, 11 => 48, 12 => 51, 13 => 54];

    // Construye filas de PONZ1: 13 diagnosticos x T/M/F + subtotales
    $ponz1Filas = [];
    foreach ($dxC as $kd => [$lbl, $anchor, $tox, $grupo]) {
        foreach (['T', 'M', 'F'] as $i => $sx) {
            $k = "D{$kd}|{$sx}";
            $ponz1Filas[] = ['key' => $k, 'niv1' => $lbl, 'niv2' => null, 'sexo' => $sx,
                             'cond' => array_merge($anchor, ['citaTiene' => $tox]),
                             'fila' => $filaDx[$kd] + $i];
        }
    }
    // Subtotal por grupo (filas Excel 15/31/47, SEXO='T'): suma las filas T
    // de los diagnosticos del grupo (la fila T ya cuenta ambos sexos).
    $grupos = [1 => ['ACCIDENTES POR ANIMALES OFÍDICOS', 15], 2 => ['ACCIDENTES POR ARÁCNIDOS', 31], 3 => ['ACCIDENTES POR OTRAS ESPECIES', 47]];
    $porGrupo = [1 => [], 2 => [], 3 => []];
    foreach ($dxC as $kd => [$lbl, $anchor, $tox, $grupo]) {
        $porGrupo[$grupo][] = "D{$kd}|T";
    }
    foreach ($grupos as $gid => [$glbl, $grow]) {
        $ponz1Filas[] = ['key' => "G{$gid}|T", 'niv1' => $glbl, 'niv2' => null, 'sexo' => 'T',
                         'calc' => $porGrupo[$gid], 'fila' => $grow];
    }
    // Gran total (filas Excel 12-14: TOTAL/M/F) = suma de los 13 diagnosticos
    foreach (['T', 'M', 'F'] as $i => $sx) {
        $ponz1Filas[] = ['key' => "TOT|{$sx}", 'niv1' => 'Total', 'niv2' => null, 'sexo' => $sx,
                         'calc' => array_map(fn($kd) => "D{$kd}|{$sx}", range(1, 13)),
                         'fila' => 12 + $i];
    }

    return [

    /* ------------------------------------------------------------
     * PONZ1 - usp_TRAMA_BASE_ZOONOSIS_2022_PONZONOSOS
     * INFORME MENSUAL DE ACCIDENTES POR ANIMALES PONZOÑOSOS (morbilidad)
     * #OFIDICOS/#ARACNIDOS/#OTROS: cita con diagnostico X D R1 y toxicacion T63x D
     * #GESTANTES: mujer 12-59 a con dx ponzoñoso y alguna fila valor_lab='G'
     * Consolidado: count(*) por Diagnostico x Etapa x Sexo x Gestacion
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'PONZ1_MORBILIDAD',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_PONZONOSOS',
        'titulo'       => 'INFORME MENSUAL DE ACCIDENTES POR ANIMALES PONZOÑOSOS',
        'subtitulo'    => 'Morbilidad por diagnostico, sexo, grupo etareo y gestantes (13 categorias + subtotales)',
        'etapaTipo'    => 'ponz',
        'conEtapas'    => true,
        'conSexo'      => true,
        'conGestantes' => true,
        'regla'        => 'filas',
        'colsX'        => ['total' => 'D', 'etapas' => [1 => 'E', 2 => 'G', 3 => 'J', 4 => 'L', 5 => 'N'], 'ges' => 'P'],
        'columna_label' => 'MORBILIDAD',
        'filas'        => $ponz1Filas,
    ],

    /* ------------------------------------------------------------
     * PONZ2 - usp_TRAMA_BASE_ZOONOSIS_2022_PONZONOSOS_RPT02
     * Casos con tratamiento (U310): 3 grupos, sin desglose por sexo
     * Consolidado: count(*) por Diagnostico(1-3) x Etapa x Gestacion
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'PONZ2_RPT02',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_PONZONOSOS_RPT02',
        'titulo'       => 'ACCIDENTES POR ANIMALES PONZOÑOSOS CON TRATAMIENTO ESPECÍFICO',
        'subtitulo'    => 'Casos con tratamiento (suero antiofidico U310): 3 grupos de diagnostico',
        'etapaTipo'    => 'ponz',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => true,
        'regla'        => 'filas',
        'colsX'        => ['total' => 'B', 'etapas' => [1 => 'D', 2 => 'E', 3 => 'G', 4 => 'J', 5 => 'L'], 'ges' => 'N'],
        'columna_label' => 'DIAGNOSTICO',
        'filas'        => [
            ['key' => 'G1', 'niv1' => 'POR  ANIMALES OFÍDICOS', 'niv2' => null, 'sexo' => null, 'fila' => 60,
             'cond' => ['citaTiene' => $U310, 'cualquieraDe' => [
                 array_merge(['cod' => ['X2091', 'X2092', 'X2093', 'X209'], 'tip' => 'D', 'rownum' => 1], ['citaTiene' => $T630]),
                 array_merge(['cod' => 'X2094', 'tip' => 'D', 'rownum' => 1], ['citaTiene' => $T631]),
             ]]],
            ['key' => 'G2', 'niv1' => 'POR ARACNIDOS', 'niv2' => null, 'sexo' => null, 'fila' => 61,
             'cond' => ['citaTiene' => $U310, 'cualquieraDe' => [
                 array_merge(['cod' => ['X2191', 'X2192', 'X219'], 'tip' => 'D', 'rownum' => 1], ['citaTiene' => $T633]),
                 array_merge(['cod' => ['X229', 'X2193'], 'tip' => 'D', 'rownum' => 1], ['citaTiene' => $T632]),
             ]]],
            ['key' => 'G3', 'niv1' => 'POR OTRAS ESPECIES', 'niv2' => null, 'sexo' => null, 'fila' => 62,
             'cond' => array_merge(['cod' => $xCodOtro, 'tip' => 'D', 'rownum' => 1, 'citaTiene' => $T634], ['citaTiene' => $U310])],
            ['key' => 'TOT', 'niv1' => 'Total', 'niv2' => null, 'sexo' => null, 'fila' => 59,
             'calc' => ['G1', 'G2', 'G3']],
        ],
    ],

    /* ------------------------------------------------------------
     * RU01 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_01
     * 1. Indicacion de la profilaxis Pre-exposicion en Rabia Urbana
     * #PROFILAXIS: cita con (90675 'PRE' R1 tipitem P) y (90675 'ST' R2)
     * Consolidado: count(distinct id_persona)
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU01_PRE_EXPOSICION',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_01',
        'titulo'       => '1. Indicación de la Profilaxis Pre-Exposición en Rabia Urbana',
        'subtitulo'    => 'Personal de Salud con indicación de profilaxis pre-exposición (90675 PRE R1 P + ST R2)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => true,
        'conGestantes' => false,
        'regla'        => 'personas',
        'colsX'        => ['total' => 'E', 'etapas' => [1 => 'G', 2 => 'J', 3 => 'L', 4 => 'N', 5 => 'P']],
        'columna_label' => 'GRUPO DE RIESGO',
        'filas'        => [
            ['key' => 'PS|T', 'niv1' => 'Personas que manipulan muestras y tienen contacto con animales susceptibles de transmitir rabia urbana', 'niv2' => 'Personal de Salud', 'sexo' => 'T', 'fila' => 67,
             'cond' => ['cod' => '90675', 'vl' => 'PRE', 'rownum' => 1, 'tip' => 'P',
                        'citaTiene' => ['cod' => '90675', 'vl' => 'ST', 'rownum' => 2]]],
            ['key' => 'PS|M', 'niv1' => 'Personas que manipulan muestras y tienen contacto con animales susceptibles de transmitir rabia urbana', 'niv2' => 'Personal de Salud', 'sexo' => 'M', 'fila' => 68,
             'cond' => ['cod' => '90675', 'vl' => 'PRE', 'rownum' => 1, 'tip' => 'P',
                        'citaTiene' => ['cod' => '90675', 'vl' => 'ST', 'rownum' => 2]]],
            ['key' => 'PS|F', 'niv1' => 'Personas que manipulan muestras y tienen contacto con animales susceptibles de transmitir rabia urbana', 'niv2' => 'Personal de Salud', 'sexo' => 'F', 'fila' => 69,
             'cond' => ['cod' => '90675', 'vl' => 'PRE', 'rownum' => 1, 'tip' => 'P',
                        'citaTiene' => ['cod' => '90675', 'vl' => 'ST', 'rownum' => 2]]],
        ],
    ],

    /* ------------------------------------------------------------
     * RU02 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_02
     * 2. Administracion de la Profilaxis Pre-Exposicion (Estado Vacunal)
     * #PROFILAXIS: cita con (90675 1/2/3 R1 D) y (90675 'PRE' R2)
     * #PERSONAL:   cita con (90675 1/2/3 R1 D) y (90675 'ST' R3)
     * Estado = valor 1/2/3. Consolidado: count(distinct id_persona)
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU02_ADMIN_PRE_EXPOSICION',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_02',
        'titulo'       => '2. Administración de la Profilaxis Pre-Exposición en Rabia Urbana: Vacunación Antirrábica',
        'subtitulo'    => 'Estado vacunal del personal (90675 1/2/3 R1 D + PRE R2 + ST R3)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => true,
        'conGestantes' => false,
        'regla'        => 'personas',
        'colsX'        => ['total' => 'E', 'etapas' => [1 => 'G', 2 => 'J', 3 => 'L', 4 => 'N', 5 => 'P']],
        'columna_label' => 'Estado Vacunal',
        'filas'        => (function () use ($sitVL) {
            $estados = [1 => 'Acceso', 2 => 'Seguimiento', 3 => 'Cobertura'];
            $out = [];
            foreach ($estados as $k => $lbl) {
                $cond = ['cod' => '90675', 'vl' => $sitVL[$k], 'rownum' => 1, 'tip' => 'D',
                         'citaTiene' => ['cod' => '90675', 'vl' => 'PRE', 'rownum' => 2],
                         'citaTieneTodo' => [['cod' => '90675', 'vl' => 'ST', 'rownum' => 3]]];
                foreach (['T', 'M', 'F'] as $i => $sx) {
                    $out[] = ['key' => "E{$k}|{$sx}", 'niv1' => $lbl, 'niv2' => null, 'sexo' => $sx,
                              'cond' => $cond, 'fila' => 72 + ($k - 1) * 3 + $i];
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * RU03 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_03
     * 3. Profilaxis Post-Exposicion: Manejo de la herida por mordedura
     * #TRATAMIENTO: cita con ((W540/W550 C/DS/SR) o W558 SR) R2 D y 99199.11 valor 1/2
     * #CONOCIDO:    cita con (W540/W550 LEV R1 D) y (W540/W550 C R2)
     * #SEVERIDAD:   cita con (W540/W550/W558 SEV R1)
     * Tratamientos 1-15 (DimZoonosis2022_RU_Tratamiento03) + bloque Total (calc).
     * Consolidado: count(*) por Tratamiento x Etapa x Sexo
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU03_POST_EXPOSICION',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_03',
        'titulo'       => '3. Profilaxis Post-Exposición en Rabia Urbana: Manejo de la herida por mordedura',
        'subtitulo'    => 'Indicaciones de tratamiento según tipo de exposición (W540/W550/W558/W530 + 99199.11)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => true,
        'conGestantes' => false,
        'regla'        => 'filas',
        'colsX'        => ['total' => 'G', 'etapas' => [1 => 'J', 2 => 'L', 3 => 'N', 4 => 'P', 5 => 'R']],
        'columna_label' => 'Tipo de Exposición',
        'filas'        => (function () use ($ruT1, $ruT2, $ruConocido) {
            // Cada entrada: [key, niv1(bloque), niv2(exposicion + indicacion), filaExcel(1ra), cond|calc]
            // Orden EXACTO de la plantilla (filas 84-140; sexo T/F/M en 3 filas consecutivas).
            $ttoCon = function (string $cod, string $vl, array $trat): array {
                // fila ancla R2 D + tratamiento 99199.11 (valor 1/2) + exposicion SEV R1
                return ['cod' => $cod, 'vl' => $vl, 'rownum' => 2, 'tip' => 'D',
                        'citaTiene' => $trat,
                        'citaTieneTodo' => [['cod' => ['W540', 'W550', 'W558'], 'vl' => 'SEV', 'rownum' => 1]]];
            };
            // [key, bloque, indicacion, fila, cond, calcRefs(claves TTO a sumar)]
            $filasDef = [
                // ---- Bloque Total (filas 84-95, calculado) ----
                ['CALC_LEV',  'Total', 'LEV  -----------',        84, null, ['TTO02', 'TTO04']],
                ['CALC_VAC',  'Total', 'SEV  Vac',                87, null, ['TTO01', 'TTO03', 'TTO05', 'TTO07', 'TTO09', 'TTO11', 'TTO13']],
                ['CALC_RIG',  'Total', 'SEV  RIG Vac',            90, null, ['TTO06', 'TTO08', 'TTO10', 'TTO12', 'TTO14']],
                ['CALC_SIN',  'Total', 'Sin riesgo  -----------', 93, null, ['TTO15']],
                // ---- Mordedura por can conocido ----
                ['TTO02', 'Mordedura por can conocido', 'LEV  -----------', 96, ['cod' => 'W540', 'vl' => 'LEV', 'rownum' => 1, 'tip' => 'D', 'citaTiene' => $ruConocido], null],
                ['TTO01', 'Mordedura por can conocido', 'SEV  Vac',           99, $ttoCon('W540', 'C', $ruT1), null],
                // ---- Mordedura por can desconocido ----
                ['TTO05', 'Mordedura por can desconocido', 'SEV  Vac',      102, $ttoCon('W540', 'DS', $ruT1), null],
                ['TTO06', 'Mordedura por can desconocido', 'SEV  RIG Vac',  105, $ttoCon('W540', 'DS', $ruT2), null],
                // ---- Mordedura por can sospechoso de rabia ----
                ['TTO09', 'Mordedura por can sospechoso de rabia', 'SEV  Vac',      108, $ttoCon('W540', 'SR', $ruT1), null],
                ['TTO10', 'Mordedura por can sospechoso de rabia', 'SEV  RIG Vac',  111, $ttoCon('W540', 'SR', $ruT2), null],
                // ---- Mordedura por gato conocido ----
                ['TTO04', 'Mordedura por gato conocido', 'LEV  -----------', 114, ['cod' => 'W550', 'vl' => 'LEV', 'rownum' => 1, 'tip' => 'D', 'citaTiene' => $ruConocido], null],
                ['TTO03', 'Mordedura por gato conocido', 'SEV  Vac',           117, $ttoCon('W550', 'C', $ruT1), null],
                // ---- Mordedura por gato desconocido ----
                ['TTO07', 'Mordedura por gato desconocido', 'SEV  Vac',      120, $ttoCon('W550', 'DS', $ruT1), null],
                ['TTO08', 'Mordedura por gato desconocido', 'SEV  RIG Vac',  123, $ttoCon('W550', 'DS', $ruT2), null],
                // ---- Mordedura por gato sospechoso de rabia ----
                ['TTO11', 'Mordedura por gato sospechoso de rabia', 'SEV  Vac',      126, $ttoCon('W550', 'SR', $ruT1), null],
                ['TTO12', 'Mordedura por gato sospechoso de rabia', 'SEV  RIG Vac',  129, $ttoCon('W550', 'SR', $ruT2), null],
                // ---- Mordedura por otro mamífero doméstico ----
                ['TTO13', 'Mordedura por otro mamífero doméstico-rabia urbana', 'SEV  Vac',     132, $ttoCon('W558', 'SR', $ruT1), null],
                ['TTO14', 'Mordedura por otro mamífero doméstico-rabia urbana', 'SEV  RIG Vac', 135, $ttoCon('W558', 'SR', $ruT2), null],
                // ---- Mordedura por roedor ----
                ['TTO15', 'Mordedura por roedor', 'Sin riesgo  -----------', 138, ['cod' => 'W530', 'rownum' => 1, 'tip' => 'D'], null],
            ];
            $out = [];
            foreach ($filasDef as [$key, $niv1, $niv2, $fila, $cond, $calc]) {
                foreach (['T', 'F', 'M'] as $i => $sx) {  // la plantilla RU3 usa T/F/M
                    $row = ['key' => "{$key}|{$sx}", 'niv1' => $niv1, 'niv2' => $niv2,
                            'sexo' => $sx, 'fila' => $fila + $i];
                    if ($calc !== null) {
                        $row['calc'] = array_map(fn($c) => "{$c}|{$sx}", $calc);
                    } else {
                        $row['cond'] = $cond;
                    }
                    $out[] = $row;
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * RU04 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_04
     * 4. Profilaxis Post-Exposicion: Vacunacion Antirrabica Humana
     * #TRATAMIENTO: cita con (90675 valor 1-5 R1 D) y (90675 MOC/DS/SR/POS/CE/DA)
     * Bloques 1-5 (MOC/DS/SR/POS/CE) x Situacion 1-5 y bloque 6 (DA) x Situacion 1-2
     * Consolidado: count(distinct id_persona)
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU04_VACUNACION_POST',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_04',
        'titulo'       => '4. Profilaxis Post-Exposición en Rabia Urbana: Vacunación Antirrábica Humana',
        'subtitulo'    => 'Situación vacunal según tratamiento del animal mordedor (90675 1-5 + MOC/DS/SR/POS/CE/DA)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'personas',
        'colsX'        => ['total' => 'D', 'etapas' => [1 => 'E', 2 => 'G', 3 => 'J', 4 => 'L', 5 => 'N']],
        'columna_label' => 'Situación Vacunal',
        'filas'        => (function () use ($sitVL) {
            // Bloques de la plantilla (DimZoonosis2022_RU_Tratamiento04; el SP llena 1-6)
            $bloques = [
                1 => ['Tratamiento en caso de animal mordedor MOC luego de una exposición LEV', 'MOC', 143],
                2 => ['Tratamiento en caso de animal mordedor DS luego de una exposición LEV',   'DS',  148],
                3 => ['Tratamiento en caso de animal mordedor SR luego de una exposición LEV',  'SR',  153],
                4 => ['Tratamiento en caso de mordedura por can o gato SIN SIGNOS O SÍNTOMAS DE RABIA en Cara, cabeza, cuello, genitales, pulpejo de dedos de manos y pies, lesiones desgarradas, profundas o múltiples', 'POS', 158],
                5 => ['Tratamiento en caso de Contacto o Animal confirmado de rabia', 'CE', 163],
                6 => ['Tratamiento en caso de Re-exposición', 'DA', 168],
            ];
            $sits = [1 => 'Acceso:1', 2 => 'Seguimiento:2', 3 => 'Seguimiento:3', 4 => 'Seguimiento:4', 5 => 'Cobertura:5'];
            $out = [];
            foreach ($bloques as $kb => [$lbl, $valor, $filaIni]) {
                foreach ($sits as $ks => $slbl) {
                    // Bloque 6 (DA): el SP solo produce Situacion 1-2 (ramas del CASE)
                    $cond = ($kb === 6 && $ks > 2) ? null :
                        ['cod' => '90675', 'vl' => $sitVL[$ks], 'rownum' => 1, 'tip' => 'D',
                         'citaTiene' => ['cod' => '90675', 'vl' => $valor]];
                    $out[] = ['key' => "B{$kb}|S{$ks}", 'niv1' => $lbl, 'niv2' => $slbl,
                              'sexo' => null, 'fila' => $filaIni + ($ks - 1), 'cond' => $cond];
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * RU05 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_05
     * 5. Suspension de la Vacunacion Antirrabica Humana
     * #DEFINITIVO: cita con (90675 SUS R2 D) y (90675 TA R3)
     * #TEMPORAL:   cita con (90675 SUS R2 D) y (90675 null R3)
     * Situacion 1: 90675 valor '2' R1 D + #TEMPORAL (temporal)
     * Situacion 2: 90675 SUS R2 D + #DEFINITIVO (definitiva)
     * Consolidado: count(distinct id_persona)
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU05_SUSPENSION',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_05',
        'titulo'       => '5. Suspensión de la Vacunación Antirrábica Humana en Profilaxis Post-Exposición',
        'subtitulo'    => 'Suspensión temporal / definitiva del esquema (90675 2/SUS + TA/null R3)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'personas',
        'colsX'        => ['total' => 'D', 'etapas' => [1 => 'E', 2 => 'G', 3 => 'J', 4 => 'L', 5 => 'N']],
        'columna_label' => 'Situación Vacunal',
        'filas'        => [
            ['key' => 'S1', 'niv1' => 'Total', 'niv2' => 'Suspensión temporal', 'sexo' => null, 'fila' => 176,
             'cond' => ['cod' => '90675', 'vl' => '2', 'rownum' => 1, 'tip' => 'D',
                        'citaTiene' => ['cod' => '90675', 'vl' => 'SUS', 'rownum' => 2, 'tip' => 'D'],
                        'citaTieneTodo' => [['cod' => '90675', 'vl' => 'NULL', 'rownum' => 3]]]],
            ['key' => 'S2', 'niv1' => 'Total', 'niv2' => 'Suspensión Definitiva', 'sexo' => null, 'fila' => 177,
             'cond' => ['cod' => '90675', 'vl' => 'SUS', 'rownum' => 2, 'tip' => 'D',
                        'citaTiene' => ['cod' => '90675', 'vl' => 'TA', 'rownum' => 3]]],
        ],
    ],

    /* ------------------------------------------------------------
     * RU06 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_06_1
     * 6. Referencias y Contrarreferencias para el tratamiento antirrabico
     * #TRATAMIENTO: cita con (90675 1-5 R1 D) y (90675 DVR/DVC/CC)
     * Consolidado: count(distinct id_persona)
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU06_REFERENCIAS',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_06_1',
        'titulo'       => '6. Referencias y Contrarreferencias para el tratamiento antirrábico',
        'subtitulo'    => 'DVR / DVC / CC según situación vacunal (90675 1-5 + DVR/DVC/CC)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'personas',
        'colsX'        => ['total' => 'D', 'etapas' => [1 => 'E', 2 => 'G', 3 => 'J', 4 => 'L', 5 => 'N']],
        'columna_label' => 'Situación Vacunal',
        'filas'        => (function () use ($sitVL) {
            $bloques = [
                1 => ['DVR Transferencia realizada', 'DVR', 180],
                2 => ['DVC Transferencia controlada', 'DVC', 185],
                3 => ['CC Contrarreferencia controlada', 'CC', 190],
            ];
            $sits = [1 => 'Acceso:1', 2 => 'Seguimiento:2', 3 => 'Seguimiento:3', 4 => 'Seguimiento:4', 5 => 'Cobertura:5'];
            $out = [];
            foreach ($bloques as $kb => [$lbl, $valor, $filaIni]) {
                foreach ($sits as $ks => $slbl) {
                    $out[] = ['key' => "B{$kb}|S{$ks}", 'niv1' => $lbl, 'niv2' => $slbl, 'sexo' => null,
                              'fila' => $filaIni + ($ks - 1),
                              'cond' => ['cod' => '90675', 'vl' => $sitVL[$ks], 'rownum' => 1, 'tip' => 'D',
                                         'citaTiene' => ['cod' => '90675', 'vl' => $valor]]];
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * FRVH - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_06_2
     * Frascos Monodosis de Vacunacion Antirrabica Humana IM
     * #PRE: cita con (90675 PRE R2 D) y (90675 ST R3)
     * #POS: cita con (90675 MOC/DS/SR/POS/CE/DA/DVR/DVC/CC R2 D) y (90675 null R3)
     *       UNION cita con (90675 SUS R2 D) y (90675 null o TA R3)
     * Consolidado: count(*) por Vacunacion (1=PRE, 2=POS) x Etapa
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'FRVH_FRASCOS_VARH',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_06_2',
        'titulo'       => 'Frascos Monodosis de Vacunación Antirrábica Humana IM',
        'subtitulo'    => 'VARH PRE / VARH POS (90675 R2 + ST/null/TA R3)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'filas',
        'colsX'        => ['total' => 'B', 'etapas' => [1 => 'D', 2 => 'E', 3 => 'G', 4 => 'J', 5 => 'L']],
        'columna_label' => 'Vacunación',
        'filas'        => [
            ['key' => 'V1', 'niv1' => 'Total', 'niv2' => 'VARH PRE (monodosis)', 'sexo' => null, 'fila' => 198,
             'cond' => ['cod' => '90675', 'vl' => 'PRE', 'rownum' => 2, 'tip' => 'D',
                        'citaTiene' => ['cod' => '90675', 'vl' => 'ST', 'rownum' => 3]]],
            ['key' => 'V2', 'niv1' => 'Total', 'niv2' => 'VARH POS (monodosis)', 'sexo' => null, 'fila' => 199,
             'cond' => ['cualquieraDe' => [
                 ['cod' => '90675', 'vl' => ['MOC', 'DS', 'SR', 'POS', 'CE', 'DA', 'DVR', 'DVC', 'CC'], 'rownum' => 2, 'tip' => 'D',
                  'citaTiene' => ['cod' => '90675', 'vl' => 'NULL', 'rownum' => 3]],
                 ['cod' => '90675', 'vl' => 'SUS', 'rownum' => 2, 'tip' => 'D',
                  'citaTieneCual' => [
                      ['cod' => '90675', 'vl' => 'NULL', 'rownum' => 3],
                      ['cod' => '90675', 'vl' => 'TA', 'rownum' => 3]]],
             ]]],
            ['key' => 'TOT', 'niv1' => 'Total', 'niv2' => null, 'sexo' => null, 'fila' => 197,
             'calc' => ['V1', 'V2']],
        ],
    ],

    /* ------------------------------------------------------------
     * FRRIG - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_06_3
     * Frascos de Inmunoglobulina Antirrabica para Humanos (RIG) administrados
     * #TRATAMIENTO: cita con (90675 valor 1 R1 D) y (90675 DVR/DVC/DS/SR/CE)
     * Ancla: 90375 R1 D ISNUMERIC(valor_lab) (fila del archivo 03)
     * Consolidado: sum(valor_lab) por Tratamiento (1=DS,2=SR,3=CE,4=DVR,5=DVC) x Etapa
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'FRRIG_FRASCOS_RIG',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_06_3',
        'titulo'       => 'Frascos de Inmunoglobulina Antirrábica para Humanos (RIG) administrados',
        'subtitulo'    => 'Suma del valor de laboratorio del item 90375 según tratamiento (DS/SR/CE/DVR/DVC)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'suma',
        'colsX'        => ['total' => 'B', 'etapas' => [1 => 'D', 2 => 'E', 3 => 'G', 4 => 'J', 5 => 'L']],
        'columna_label' => 'Tratamiento',
        'filas'        => [
            ['key' => 'T1', 'niv1' => 'Total', 'niv2' => 'Tratamiento en caso de animal mordedor DS en Cara, cabeza, cuello, genitales, pulpejo de dedos de manos y pies, lesiones desgarradas, profundas o múltiples.', 'sexo' => null, 'fila' => 203,
             'cond' => ['cod' => '90375', 'rownum' => 1, 'tip' => 'D', 'vlNum' => true,
                        'citaTiene' => ['cod' => '90675', 'vl' => '1', 'rownum' => 1, 'tip' => 'D'],
                        'citaTieneTodo' => [['cod' => '90675', 'vl' => 'DS']]]],
            ['key' => 'T2', 'niv1' => 'Total', 'niv2' => 'Tratamiento en caso de animal mordedor SR en Cara, cabeza, cuello, genitales, pulpejo de dedos de manos y pies, lesiones desgarradas, profundas o múltiples.', 'sexo' => null, 'fila' => 204,
             'cond' => ['cod' => '90375', 'rownum' => 1, 'tip' => 'D', 'vlNum' => true,
                        'citaTiene' => ['cod' => '90675', 'vl' => '1', 'rownum' => 1, 'tip' => 'D'],
                        'citaTieneTodo' => [['cod' => '90675', 'vl' => 'SR']]]],
            ['key' => 'T3', 'niv1' => 'Total', 'niv2' => 'Tratamiento en caso de Contacto o Animal confirmado de rabia', 'sexo' => null, 'fila' => 205,
             'cond' => ['cod' => '90375', 'rownum' => 1, 'tip' => 'D', 'vlNum' => true,
                        'citaTiene' => ['cod' => '90675', 'vl' => '1', 'rownum' => 1, 'tip' => 'D'],
                        'citaTieneTodo' => [['cod' => '90675', 'vl' => 'CE']]]],
            ['key' => 'T4', 'niv1' => 'Total', 'niv2' => 'DVR Transferencia realizada', 'sexo' => null, 'fila' => 206,
             'cond' => ['cod' => '90375', 'rownum' => 1, 'tip' => 'D', 'vlNum' => true,
                        'citaTiene' => ['cod' => '90675', 'vl' => '1', 'rownum' => 1, 'tip' => 'D'],
                        'citaTieneTodo' => [['cod' => '90675', 'vl' => 'DVR']]]],
            ['key' => 'T5', 'niv1' => 'Total', 'niv2' => 'DVC Transferencia controlada', 'sexo' => null, 'fila' => 207,
             'cond' => ['cod' => '90375', 'rownum' => 1, 'tip' => 'D', 'vlNum' => true,
                        'citaTiene' => ['cod' => '90675', 'vl' => '1', 'rownum' => 1, 'tip' => 'D'],
                        'citaTieneTodo' => [['cod' => '90675', 'vl' => 'DVC']]]],
            ['key' => 'TOT', 'niv1' => 'Total', 'niv2' => null, 'sexo' => null, 'fila' => 202,
             'calc' => ['T1', 'T2', 'T3', 'T4', 'T5']],
        ],
    ],

    /* ------------------------------------------------------------
     * RU07 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_07
     * 7. Diagnostico de Rabia Humana Urbana (A821)
     * Consolidado: COUNT(*) por Etapa
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU07_DX_HUMANO',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_07',
        'titulo'       => '7. Diagnóstico de Rabia Humana Urbana',
        'subtitulo'    => 'Casos notificados de rabia humana urbana (A821 D R1)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => true,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'filas',
        'colsX'        => ['total' => 'B', 'etapas' => [1 => 'D', 2 => 'E', 3 => 'G', 4 => 'J', 5 => 'L']],
        'columna_label' => 'Diagnóstico',
        'filas'        => [
            ['key' => 'TOT', 'niv1' => 'Total', 'niv2' => null, 'sexo' => null, 'fila' => 211,
             'cond' => ['cod' => 'A821', 'rownum' => 1, 'tip' => 'D']],
        ],
    ],

    /* ------------------------------------------------------------
     * RU08 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_08
     * 8. Observacion del Animal Mordedor
     * #OBSERVACION: cita (AAA04/AAA09, C5092 1/2/3 R1 D) con C5092 AS/SR/MOC R2
     * Observacion 1-6 x Visita 1-3. Consolidado: count(*). Solo columna Total.
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU08_OBSERVACION_ANIMAL',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_08',
        'titulo'       => '8. Observación del Animal Mordedor',
        'subtitulo'    => 'Perro/Gato observado AS/SR/MOC por número de visita (C5092 + ficha AAA04/AAA09)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => false,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'filas',
        'colsX'        => ['total' => 'D'],
        'columna_label' => 'Observación',
        'filas'        => (function () {
            $obs = [
                1 => ['Perro observado AS',  'AAA04', 'AS'],
                2 => ['Gato observado AS',   'AAA09', 'AS'],
                3 => ['Perro observado SR',  'AAA04', 'SR'],
                4 => ['Gato observado SR',   'AAA09', 'SR'],
                5 => ['Perro observado MOC', 'AAA04', 'MOC'],
                6 => ['Gato observado MOC',  'AAA09', 'MOC'],
            ];
            $visitas = [1 => '1ra', 2 => '2da', 3 => '3ra'];
            $out = [];
            $layouts = ['TOT' => [214, 'Total']];
            foreach ($obs as $k => $o) $layouts[$k] = [217 + ($k - 1) * 3, $o[0]];
            foreach ($layouts as $kb => [$filaIni, $lbl]) {
                foreach ($visitas as $kv => $vlbl) {
                    if ($kb === 'TOT') {
                        $out[] = ['key' => "TOT|V{$kv}", 'niv1' => $lbl, 'niv2' => $vlbl, 'sexo' => null,
                                  'fila' => $filaIni + ($kv - 1),
                                  'calc' => array_map(fn($k) => "O{$k}|V{$kv}", array_keys($obs))];
                    } else {
                        $out[] = ['key' => "O{$kb}|V{$kv}", 'niv1' => $lbl, 'niv2' => $vlbl, 'sexo' => null,
                                  'fila' => $filaIni + ($kv - 1),
                                  'cond' => ['ficha' => $obs[$kb][1], 'cod' => 'C5092', 'vl' => (string)$kv,
                                             'rownum' => 1, 'tip' => 'D',
                                             'citaTiene' => ['cod' => 'C5092', 'vl' => $obs[$kb][2], 'rownum' => 2]]];
                    }
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * RU09 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_09
     * 9. Vigilancia del reservorio y Diagnostico Laboratorial
     * #VIGILANCIA: cita (AAA04/AAA09/AAA91, 88025 MR/MT/RP/RN R2 D) con C0041 1/2 R1 D
     * #MUESTRAS:   cita (AAA04/AAA09/AAA91, 88025 R1 D) con 88025 MR/MT/RP/RN R2
     * Vigilancia 1-4 x Muestra 1-4. Consolidado: sum(valor_lab). Solo Total.
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU09_VIGILANCIA_RESERVORIO',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_09',
        'titulo'       => '9. Vigilancia del reservorio y Diagnóstico Laboratorial',
        'subtitulo'    => 'Muestras de can/gato/otros mamíferos por situación de la muestra (88025 + C0041)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => false,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'suma',
        'colsX'        => ['total' => 'E'],
        'columna_label' => 'Vigilancia',
        'filas'        => (function () {
            $vig = [
                1 => ['Muestras de can en vigilancia activa',  'AAA04', '1'],
                2 => ['Muestras de can en vigilancia pasiva',  'AAA04', '2'],
                3 => ['Muestras de gato en vigilancia pasiva', 'AAA09', '2'],
                4 => ['Muestras de otros mamíferos domésticos (equino, porcino, bovino, ovino, etc) en rabia urbana, vigilancia pasiva', 'AAA91', '2'],
            ];
            $muestras = [1 => 'Remitida', 2 => 'Procesada', 3 => 'Resultado positivo', 4 => 'Resultado Negativo'];
            $vlMuestra = [1 => 'MR', 2 => 'MT', 3 => 'RP', 4 => 'RN'];
            $out = [];
            $layouts = ['TOT' => [237, 'Total']];
            foreach ($vig as $k => $v) $layouts[$k] = [241 + ($k - 1) * 4, $v[0]];
            foreach ($layouts as $kb => [$filaIni, $lbl]) {
                foreach ($muestras as $km => $mlbl) {
                    if ($kb === 'TOT') {
                        $out[] = ['key' => "TOT|M{$km}", 'niv1' => $lbl, 'niv2' => $mlbl, 'sexo' => null,
                                  'fila' => $filaIni + ($km - 1),
                                  'calc' => array_map(fn($k) => "V{$k}|M{$km}", array_keys($vig))];
                    } else {
                        $out[] = ['key' => "V{$kb}|M{$km}", 'niv1' => $lbl, 'niv2' => $mlbl, 'sexo' => null,
                                  'fila' => $filaIni + ($km - 1),
                                  'cond' => ['ficha' => $vig[$kb][1], 'cod' => '88025', 'vlNum' => true,
                                             'rownum' => 1,
                                             'citaTiene' => ['cod' => 'C0041', 'vl' => $vig[$kb][2], 'rownum' => 1, 'tip' => 'D'],
                                             'citaTieneTodo' => [['cod' => '88025', 'vl' => $vlMuestra[$km], 'rownum' => 2]]]];
                    }
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * RU10 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_10
     * 10. Control Epidemiologico (Control de Foco)
     * #VIGILANCIA: cita (APP108, C0091 NOT/IN/CC R2 D) con C0041 1/2 R1 D
     * #SITUACION:  cita (APP108, C0091 R1 D) con C0091 NOT/IN/CC R2
     * Vigilancia 1-2 x Situacion 1-3. Consolidado: sum(valor_lab). Solo Total.
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU10_CONTROL_FOCO',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_10',
        'titulo'       => '10. Control Epidemiológico (Control de Foco)',
        'subtitulo'    => 'Control de foco en vigilancia activa/pasiva por situación (C0091 + C0041 + APP108)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => false,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'suma',
        'colsX'        => ['total' => 'E'],
        'columna_label' => 'Vigilancia',
        'filas'        => (function () {
            $vig = [1 => ['Control de foco en vigilancia activa', '1'], 2 => ['Control de foco en vigilancia pasiva', '2']];
            $sits = [1 => 'Notificado', 2 => 'Investigado', 3 => 'Controlado'];
            $vlSit = [1 => 'NOT', 2 => 'IN', 3 => 'CC'];
            $out = [];
            $layouts = ['TOT' => [259, 'Total']];
            foreach ($vig as $k => $v) $layouts[$k] = [262 + ($k - 1) * 3, $v[0]];
            foreach ($layouts as $kb => [$filaIni, $lbl]) {
                foreach ($sits as $ks => $slbl) {
                    if ($kb === 'TOT') {
                        $out[] = ['key' => "TOT|F{$ks}", 'niv1' => $lbl, 'niv2' => $slbl, 'sexo' => null,
                                  'fila' => $filaIni + ($ks - 1),
                                  'calc' => array_map(fn($k) => "V{$k}|F{$ks}", array_keys($vig))];
                    } else {
                        $out[] = ['key' => "V{$kb}|F{$ks}", 'niv1' => $lbl, 'niv2' => $slbl, 'sexo' => null,
                                  'fila' => $filaIni + ($ks - 1),
                                  'cond' => ['ficha' => 'APP108', 'cod' => 'C0091', 'vlNum' => true,
                                             'rownum' => 1,
                                             'citaTiene' => ['cod' => 'C0041', 'vl' => $vig[$kb][1], 'rownum' => 1, 'tip' => 'D'],
                                             'citaTieneTodo' => [['cod' => 'C0091', 'vl' => $vlSit[$ks], 'rownum' => 2]]]];
                    }
                }
            }
            return $out;
        })(),
    ],

    /* ------------------------------------------------------------
     * RU11 - usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_11
     * 11. Vacunacion Antirrabica Canina (C5041 + APP99/APP108/APP98)
     * Consolidado: sum(valor_lab). Solo columna Total.
     * ------------------------------------------------------------ */
    [
        'codigo'       => 'RU11_VACUNACION_CANINA',
        'procedimiento' => 'usp_TRAMA_BASE_ZOONOSIS_2022_RABIA_URBANA_11',
        'titulo'       => '11. Vacunación Antirrábica Canina',
        'subtitulo'    => 'Vacunación canina por centros antirrábicos / VANCAN / control de foco (C5041)',
        'etapaTipo'    => 'ru',
        'conEtapas'    => false,
        'conSexo'      => false,
        'conGestantes' => false,
        'regla'        => 'suma',
        'colsX'        => ['total' => 'F'],
        'columna_label' => 'Situación vacunal',
        'filas'        => [
            ['key' => 'S1', 'niv1' => 'Total', 'niv2' => 'Centros antirrábicos', 'sexo' => null, 'fila' => 270,
             'cond' => ['ficha' => 'APP99', 'cod' => 'C5041', 'vlNum' => true, 'rownum' => 1, 'tip' => 'D']],
            ['key' => 'S2', 'niv1' => 'Total', 'niv2' => 'VANCAN', 'sexo' => null, 'fila' => 271,
             'cond' => ['ficha' => 'APP108', 'cod' => 'C5041', 'vlNum' => true, 'rownum' => 1, 'tip' => 'D']],
            ['key' => 'S3', 'niv1' => 'Total', 'niv2' => 'Control de foco', 'sexo' => null, 'fila' => 272,
             'cond' => ['ficha' => 'APP98', 'cod' => 'C5041', 'vlNum' => true, 'rownum' => 1, 'tip' => 'D']],
        ],
    ],

    ];
}

/* ============================================================
 * 4) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/**
 * Anios disponibles en la tabla consolidada DENTRO DEL AMBITO del filtro
 * establecimiento (mismo criterio que el modulo Materno):
 *   - establecimiento concreto -> anios con datos de ese EE.SS
 *   - '-- Todos --'            -> anios con datos de la lista ZSPERENE
 */
function zooGetAniosDisponibles(PDO $pdo, string $establecimiento = ''): array {
    try {
        $params = [];
        $whereEst = zooWhereEstablecimientos($pdo, $establecimiento, $params);
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
 * anio seleccionado ('' = todos los anios). Usa la columna generada Mes_Int
 * si existe (mismo criterio que el reporte).
 */
function zooGetMesesDisponibles(PDO $pdo, string $anio = '', string $establecimiento = ''): array {
    try {
        $params = [];
        $cond = [];
        if (trim($anio) !== '') {
            $cond[] = "Anio = :anio";
            $params[':anio'] = (string)$anio;
        }
        $whereEst = zooWhereEstablecimientos($pdo, $establecimiento, $params);
        if ($whereEst !== '') $cond[] = $whereEst;
        $colMes = zooTieneColumnaMesInt($pdo) ? 'Mes_Int' : "CAST(TRIM(Mes) AS UNSIGNED)";
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

/**
 * Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES).
 * Resultado cacheado estaticamente (el ambito se consulta varias veces).
 */
function zooGetEstablecimientosZS(PDO $pdo): array {
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
 * AMBITO DE ESTABLECIMIENTOS compartido por todos los filtros del modulo
 * (mismo criterio que el modulo Materno):
 *   - Establecimiento concreto: TRIM(Codigo_Unico) = :est
 *   - '-- Todos --': TRIM(Codigo_Unico) IN (lista ZSPERENE completa)
 *   - Catalogo no disponible: '' (no restringir, degrada bien)
 */
function zooWhereEstablecimientos(PDO $pdo, string $establecimiento, array &$params): string {
    $establecimiento = trim($establecimiento);
    if ($establecimiento !== '') {
        $params[':est'] = $establecimiento;
        return "TRIM(Codigo_Unico) = :est";
    }
    $lista = zooGetEstablecimientosZS($pdo);
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
 * Codigos de item que intervienen en el reporte de Zoonosis, segun los 13
 * procedimientos del archivo "03 Creacion de Procedimientos".
 *
 * La consulta trae ademas TODAS las filas con Valor_Lab='G' (el marcador de
 * gestante de #GESTANTES puede estar en cualquier codigo, igual que #GEST
 * del modulo Materno).
 */
function zooCodigosInteres(): array {
    return [
        'exactos' => [
            // ---- PONZONOSOS: diagnosticos X2* ----
            'X2091', 'X2092', 'X2093', 'X2094', 'X209',     // ofidios
            'X2191', 'X2192', 'X219', 'X229', 'X2193',      // aracnidos
            'X249', 'X239', 'X259',                          // otras especies
            // ---- PONZONOSOS: toxicaciones T63* ----
            'T630', 'T631', 'T632', 'T633', 'T634',
            // ---- PONZ2: tratamiento ----
            'U310',                                           // suero antiofidico
            // ---- RABIA URBANA ----
            '90675',                                          // vacunacion antirrabica humana
            '99199.11',                                       // tratamiento de la exposicion (1/2)
            'W540', 'W550', 'W558',                           // mordedura can/gato/otro mamifero
            'W530',                                           // mordedura por roedor
            '90375',                                          // frascos de RIG (codigo del archivo 03)
            'A821',                                           // rabia humana urbana
            // ---- OBSERVACION ANIMAL / VIGILANCIA / CONTROL DE FOCO / VAC. CANINA ----
            'C5092',                                          // observacion del animal mordedor
            '88025',                                          // toma de muestra (reservorio)
            'C0041',                                          // toma de muestra (control de foco)
            'C0091',                                          // control de foco
            'C5041',                                          // vacunacion antirrabica canina
        ],
        'prefijos' => [],
    ];
}

/**
 * Construye la clausula WHERE de codigos de item a partir de
 * zooCodigosInteres(). Incluye las filas con Valor_Lab='G'.
 */
function zooWhereCodigos(array &$params): string {
    $lista = zooCodigosInteres();
    $partes = [];
    $inPh = [];
    foreach ($lista['exactos'] as $j => $c) {
        $k = ":cod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "Codigo_Item IN (" . implode(',', $inPh) . ")";
    foreach ($lista['prefijos'] as $i => $pref) {
        $k = ":pref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "Codigo_Item LIKE $k";
    }
    // #GESTANTES: el marcador valor_lab='G' puede estar en cualquier codigo
    $params[':vl_g'] = 'G';
    $partes[] = "Valor_Lab = :vl_g";
    return "(" . implode(" OR ", $partes) . ")";
}

/** Conexion PDO dedicada en modo UNBUFFERED (streaming), como los modulos Cancer/Materno. */
function zooAbrirConexionStreaming(): ?PDO {
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
function zooTieneColumnaMesInt(PDO $pdo): bool {
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
function zooDiagnosticoEntorno(PDO $pdo): array {
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

/**
 * Candidatos por buckets pre-indexados (codigo exacto). Sobre-aproximacion:
 * la verificacion exacta la hace zooCumple. Evita recorrer TODAS las filas
 * por cada fila del reporte.
 */
function zooCandidatos(array $cond, array $porCod, int $total): array {
    if ($total <= 0) return [];
    // Sin filtro de codigo (o solo ORs que se resuelven recursivamente): barrido
    if (!isset($cond['cod']) && !isset($cond['cualquieraDe'])) {
        return range(0, $total - 1);
    }
    $out = [];
    if (isset($cond['cod'])) {
        foreach ((array)$cond['cod'] as $c) {
            foreach ($porCod[strtoupper((string)$c)] ?? [] as $idx) $out[$idx] = true;
        }
        return array_keys($out);
    }
    // cualquieraDe: union de candidatos de cada rama
    foreach ($cond['cualquieraDe'] as $sub) {
        foreach (zooCandidatos($sub, $porCod, $total) as $idx) $out[$idx] = true;
    }
    return array_keys($out);
}

/**
 * Cuenta/suma las filas que cumplen una condicion aplicando la regla de conteo
 * del T-SQL (columna "Casos" de la tabla consolidada correspondiente).
 *
 *   'filas'    : cada fila HIS que cumple cuenta 1 en su etapa (count(*)).
 *   'personas' : distintos pares (renaes|id_persona) por etapa: replica la suma
 *                que hacia el Excel de los count(distinct id_persona) POR
 *                ESTABLECIMIENTO de la tabla consolidada.
 *   'suma'     : suma del valor numerico de valor_lab por etapa (sum(LAB)).
 *
 * $sexoFiltro: 'M' | 'F' | null (T = ambos sexos). Las filas con sexo distinto
 * de M/F se excluyen (el T-SQL filtra "sexo is not null" en el nominal).
 * $conGes: si la seccion tiene columna GESTANTES, se cuentan ademas las filas
 * cuya cita esta en ctx['citasGestantes'] (#GESTANTES del T-SQL).
 *
 * @return array ['etapas'=>[etapa=>n], 'ges'=>int, 'citas'=>int]
 */
function zooContar(?array $cond, string $regla, array $ctx, ?string $sexoFiltro, bool $conGes): array {
    $etapas = [];
    $ges = 0;
    $citas = [];
    $pacSet = [];      // etapa => set de "renaes|pac" ('personas')
    $conEtapas = $ctx['conEtapas'];

    if ($cond === null) {
        return ['etapas' => $conEtapas ? [] : [0 => 0], 'ges' => 0, 'citas' => 0];
    }
    $candidatos = zooCandidatos($cond, $ctx['porCod'], count($ctx['filas']));
    foreach ($candidatos as $idx) {
        $f = $ctx['filas'][$idx];
        if (!zooCumple($f, $cond, $ctx)) continue;
        // 'T' = fila TOTAL de la plantilla: cuenta ambos sexos (M+F);
        // 'M'/'F' filtran. El nominal del T-SQL exige sexo M/F valido.
        //
        // EXCEPCION: las secciones de animal (RU08 observacion, RU09 vigilancia,
        // RU10 control de foco, RU11 vacunacion canina) operan sobre filas que
        // describen al ANIMAL (Codigo_Item=C5092 / 88025 / C0091 / C5041), donde
        // Id_Genero es NULL porque no aplica el sexo del paciente humano. Para
        // esas secciones conSexo=false y conEtapas=false y la fila del reporte
        // viene con 'sexo'=>null: el filtro M/F no debe ejecutarse, si no se
        // descartarian TODAS las filas C5092/88025/C0091/C5041 y la seccion
        // quedaria en 0 aunque la condicion del predicado SI coincida con los
        // datos (verificado contra el SQL de ejemplo).
        $aplicarFiltroSexo = $conEtapas || $conGes || $sexoFiltro !== null;
        if ($aplicarFiltroSexo) {
            if ($sexoFiltro !== null && $sexoFiltro !== 'T' && $f['sexo'] !== $sexoFiltro) continue;
            if ($f['sexo'] !== 'M' && $f['sexo'] !== 'F') continue;
        }

        $etapa = $conEtapas ? zooEtapa($f, $ctx['etapaTipo']) : 0;
        if ($etapa === null) continue; // fuera de los grupos del reporte

        if ($regla === 'personas') {
            $pacKey = $f['renaes'] . '|' . $f['pac'];
            if ($pacKey === '|') $pacKey = '#f' . $idx; // sin renaes ni paciente: fila unica
            if (!isset($pacSet[$etapa][$pacKey])) {
                $pacSet[$etapa][$pacKey] = true;
                $etapas[$etapa] = ($etapas[$etapa] ?? 0) + 1;
            }
        } elseif ($regla === 'suma') {
            $v = ($f['vl'] !== null && is_numeric($f['vl'])) ? (float)$f['vl'] : 0.0;
            $etapas[$etapa] = ($etapas[$etapa] ?? 0) + $v;
        } else { // 'filas'
            $etapas[$etapa] = ($etapas[$etapa] ?? 0) + 1;
        }
        if ($conGes && isset($ctx['citasGestantes'][$f['cita']])) {
            $ges++;
        }
        if ($f['cita'] !== '') $citas[$f['cita']] = true;
    }
    return ['etapas' => $etapas, 'ges' => $ges, 'citas' => count($citas)];
}

/**
 * Ejecuta el reporte de Zoonosis completo (adaptacion de los 13 procedimientos
 * del archivo "03 Creacion de Procedimientos").
 *
 * Estrategia (igual que ESNI/Cancer/Materno): UNA sola consulta que trae todas
 * las filas HIS de los codigos de interes del paquete zoonosis con los filtros
 * comunes aplicados, y luego el matching de cada fila del reporte se resuelve
 * en PHP usando el DSL (que espeja los WHERE del T-SQL).
 *
 * @param array $filtros ['anio'=>, 'mes'=>, 'establecimiento'=> (Codigo_Unico)]
 * @return array ['secciones'=>[...], 'totales'=>[...], 'filas_leidas'=>int, ...]
 */
function zooEjecutarReporte(PDO $pdo, array $filtros): array {
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
        if (zooTieneColumnaMesInt($pdo)) {
            $where[] = "Mes_Int = :mes";
        } else {
            $where[] = "CAST(TRIM(Mes) AS UNSIGNED) = :mes";
        }
        $params[':mes'] = intval($filtros['mes']);
    }
    // Ambito de establecimientos: EE.SS concreto o lista ZSPERENE con 'Todos'
    $whereEst = zooWhereEstablecimientos($pdo, (string)($filtros['establecimiento'] ?? ''), $params);
    if ($whereEst !== '') {
        $where[] = $whereEst;
    }

    // ---- 2) Codigos de interes del paquete zoonosis ----
    $whereCod = zooWhereCodigos($params);

    $sql = "SELECT Id_Cita, Id_Paciente, Id_Genero, Edad_Reg, Tipo_Edad,
                   Codigo_Item, Tipo_Diagnostico, Valor_Lab,
                   Id_Correlativo_Lab, Ficha_Familiar, Codigo_Unico
            FROM {$tabla}
            WHERE {$whereCod} AND (" . implode(' AND ', $where) . ")";

    // ---- 3) Consulta en streaming (unbuffered) + fetch protegido ----
    $pdoStream = zooAbrirConexionStreaming();
    $pdoQ = $pdoStream !== null ? $pdoStream : $pdo;

    $filas = [];
    try {
        $stmt = $pdoQ->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $f = zooFila($r);
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

    return zooEjecutarDesdeFilas($filas, $t0);
}

/**
 * Ejecuta el pipeline de matching/agregacion del reporte a partir de las
 * filas HIS YA normalizadas (zooFila). Separada de zooEjecutarReporte para
 * poder validar todo el pipeline sin base de datos (harness de pruebas).
 *
 * @param array $filas Filas normalizadas por zooFila()
 * @param float $t0    Timestamp de inicio (para tiempo_ejecucion)
 * @return array Estructura del reporte (ver zooEjecutarReporte)
 */
function zooEjecutarDesdeFilas(array $filas, float $t0 = 0.0): array {
    if ($t0 === 0.0) $t0 = microtime(true);

    // Indices por cita y buckets por codigo
    $porCita = [];
    $porCod = [];
    foreach ($filas as $idx => $f) {
        if ($f['cita'] !== '') $porCita[$f['cita']][] = $idx;
        $porCod[$f['cod']][] = $idx;
    }

    // #GESTANTES del T-SQL: citas de mujeres 12-59 a con diagnostico ponzoñoso
    // (X-cod D R1) que tienen alguna fila con valor_lab='G'
    $xCodGest = ['X2091', 'X2092', 'X2093', 'X2094', 'X209', 'X2191', 'X2192', 'X219', 'X229', 'X2193', 'X249', 'X239', 'X259'];
    $citasXmujer = [];
    $citasConG = [];
    foreach ($filas as $idx => $f) {
        if ($f['vl'] === 'G' && $f['cita'] !== '') $citasConG[$f['cita']] = true;
        if (in_array($f['cod'], $xCodGest, true) && $f['tip'] === 'D' && $f['rownum'] === 1
            && $f['tipEdad'] === 'A' && $f['edad'] !== null && $f['edad'] >= 12 && $f['edad'] <= 59
            && $f['sexo'] === 'F' && $f['cita'] !== '') {
            $citasXmujer[$f['cita']] = true;
        }
    }
    $citasGestantes = array_intersect_key($citasXmujer, $citasConG);

    // ---- Matching por seccion ----
    $seccionesOut = [];
    $totCasos = 0;

    foreach (zooSecciones() as $sec) {
        $conEtapas = $sec['conEtapas'];
        $regla = $sec['regla'];
        $conGes = $sec['conGestantes'];
        $ctx = ['filas' => $filas, 'porCita' => $porCita, 'porCod' => $porCod,
                'conEtapas' => $conEtapas, 'etapaTipo' => $sec['etapaTipo'],
                'citasGestantes' => $citasGestantes];

        $secOut = $sec;
        $totalSec = 0.0;

        $filasOut = [];
        $porKey = []; // key => ['etapas'=>[..], 'ges'=>n] (para las calc)
        foreach ($sec['filas'] as $fr) {
            $key = $fr['key'];
            if (isset($fr['calc'])) {
                $porKey[$key] = null; // se resuelve en la 2a pasada
                $filasOut[] = ['key' => $key, 'niv1' => $fr['niv1'], 'niv2' => $fr['niv2'],
                               'sexo' => $fr['sexo'], 'fila' => $fr['fila'],
                               'calc' => $fr['calc'], 'cond' => null];
                continue;
            }
            $r = zooContar($fr['cond'] ?? null, $regla, $ctx, $fr['sexo'], $conGes);
            $etapas = [];
            if ($conEtapas) {
                foreach ([1, 2, 3, 4, 5] as $e) {
                    if (isset($r['etapas'][$e]) && (float)$r['etapas'][$e] != 0.0) $etapas[$e] = $r['etapas'][$e];
                }
            } else {
                $etapas = isset($r['etapas'][0]) && (float)$r['etapas'][0] != 0.0 ? [0 => $r['etapas'][0]] : [];
            }
            $tot = array_sum($etapas);
            // El total de la seccion cuenta cada evento UNA vez: solo las filas
            // 'T' (ambos sexos) o sin dimension de sexo suman; las M/F son el
            // desglose del mismo evento (ya contado en la fila T).
            if ($fr['sexo'] === 'T' || $fr['sexo'] === null) {
                $totalSec += $tot;
            }
            $porKey[$key] = ['etapas' => $etapas, 'ges' => $r['ges']];
            $filasOut[] = ['key' => $key, 'niv1' => $fr['niv1'], 'niv2' => $fr['niv2'],
                           'sexo' => $fr['sexo'], 'fila' => $fr['fila'],
                           'valores' => $etapas, 'ges' => $r['ges'], 'total' => $tot,
                           'citas' => $r['citas'], 'cond' => $fr['cond'] ?? null,
                           'sinDatosSP' => ($fr['cond'] ?? null) === null];
        }
        // Resolver filas calc (suma de sus componentes). Las filas calc son
        // DERIVADAS de las filas de detalle: NO suman al total de la seccion
        // (evita el doble conteo de los mismos eventos).
        foreach ($filasOut as &$fo) {
            if (!isset($fo['calc'])) continue;
            $etapas = [];
            $ges = 0;
            $tot = 0.0;
            foreach ($fo['calc'] as $ck) {
                $src = $porKey[$ck] ?? null;
                if ($src === null) continue;
                foreach ($src['etapas'] as $e => $v) {
                    $etapas[$e] = ($etapas[$e] ?? 0) + $v;
                    $tot += $v;
                }
                $ges += $src['ges'];
            }
            $fo['valores'] = $etapas;
            $fo['ges'] = $ges;
            $fo['total'] = $tot;
            $fo['cond'] = null;
            $porKey[$fo['key']] = ['etapas' => $etapas, 'ges' => $ges];
        }
        unset($fo);

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
        ],
        'version'          => ZOONOSIS_DATA_VERSION,
        'filas_leidas'     => count($filas),
        'tiempo_ejecucion' => round(microtime(true) - $t0, 2),
    ];
}

