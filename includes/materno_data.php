<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo MATERNO - Motor de reporte data-driven (includes/materno_data.php)
 *
 * REEMPLAZA EL FLUJO MANUAL del Modulo de Materno:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales"     (DimMaterno2023_*)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion" (TRAMA_BASE_MATERNO_2023_RPT_*)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos"    (usp RPT_01..RPT_10)
 *   4) Excel:      abrir "Reporte_Actividades_Materno.xlsx" y refrescar conexion ODBC
 *
 * FLUJO NUEVO (1 click):
 *   Reportes Operacionales -> MATERNO -> [Generar Reporte]
 *   (Opcional) [Exportar Excel] -> llena la plantilla oficial
 *   "uploads/Reporte_Actividades_Materno.xlsx" con los mismos datos (materno_export.php).
 *
 * El motor adapta la logica de los procedimientos del archivo "03 Creacion de
 * Procedimientos" (RPT_01_APN_REENFOCADA ... RPT_10_CONSEJERIA) y la ejecuta
 * contra la tabla consolidada MySQL T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
 * (la misma que usan los modulos ESNI y CANCER). El layout replica el Excel
 * "Reporte_Actividades_Materno.xlsx" (10 secciones I-X).
 *
 * MAPEO DE COLUMNAS (TRAMAHIS SQL Server -> tabla consolidada MySQL):
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
 *   cod_item       -> Codigo_Item
 *   valor_lab      -> Valor_Lab
 *   fg_tipo        -> Fg_Tipo              ('CX','DX','PX','CP',...)
 *   fecha_atencion -> Fecha_Atencion       [adicional: calculo de trimestre por FUR]
 *   FUR            -> Fecha_Ultima_Regla   [adicional: edad gestacional]
 *   hemoglobina    -> Hemoglobina          [adicional: severidad de anemia]
 *   id_otra_cond   -> Id_Otra_Condicion / Descripcion_Otra_Condicion
 *                    ('GESTANTE', 'PUERPERA', ...)
 *
 * ESTRUCTURA DEL REPORTE (10 secciones = 12 bloques, como el Excel oficial):
 *   I    RPT_01_APN_REENFOCADA      25 columnas x 4 grupos etareos + TOTAL
 *   II   RPT_02_BIENESTAR             6 columnas x 4 grupos etareos + TOTAL
 *   III  RPT_03_ANEMIA               11 columnas x 4 grupos etareos + TOTAL
 *   IV   RPT_04_COMPLICACIONES       15 filas (complicaciones) x TOTAL + 4 grupos
 *   V    RPT_05_MORBILIDAD_RN         7 filas (causas RN) x N (una columna)
 *   VI   RPT_06_ADMIN_MICRONUT       10 columnas x 4 grupos etareos + TOTAL
 *   VII  RPT_07_PUERPERIO             3 columnas x 4 grupos etareos + TOTAL
 *   VIII RPT_08_VISITA                2 filas (gestante/puerpera) x 3 grupos
 *   IX-1 RPT_09_1_TRANSMISION_VERT   21 columnas x 4 grupos etareos + TOTAL
 *   IX-2 RPT_09_2_TRANSMISION_VERT    6 columnas x 4 grupos etareos + TOTAL
 *   IX-3 RPT_09_3_TRANSMISION_VERT    4 columnas (sin zona en la plantilla; web only)
 *   X    RPT_10_CONSEJERIA            3 columnas x 4 grupos etareos + TOTAL
 *
 * GRUPOS ETAREOS (DimMaterno2023_Gedad):
 *   1 = '<12 a.'    2 = '12 - 17 a.'    3 = '18 - 29 a.'    4 = '30 - 59 a.'
 *
 * REGLAS DE CONTEO (adaptan la logica "N-ésima atencion/entrega" del archivo 03):
 *   simple        : cada CITA que cumple cuenta 1 vez en su grupo etareo
 *   trimestre N   : cuenta la cita si la edad gestacional (por Fecha_Ultima_Regla)
 *                   cae en el trimestre N (I: <14 sem, II: 14-27.6, III: >=28 sem)
 *   ocurrencia N  : del paciente, solo la N-esima cita que cumple (por fecha) cuenta
 *   ocurrenciaMin N: pacientes con N o mas citas que cumplen: la N-esima cuenta
 *                    (para filas "5° a +", "6° ENTREGA", etc.)
 *   conteoMinimo N: pacientes con N o mas citas que cumplen cuentan 1 vez
 *                    (para "Gestante CONTROLADA": >= 6 atenciones prenatales)
 *   calc          : columna calculada = suma de otras columnas (como la columna
 *                   "Total" de Gestante Atendida en el Excel: =SUM(C:E))
 *
 * NOTAS DE ADAPTACION (desviaciones documentadas respecto al T-SQL original):
 *   - El archivo "03 Creacion de Procedimientos.txt" del modulo Materno NO fue
 *     adjuntado al migrar; las condiciones iniciales por categoria usan los
 *     codigos HIS-MINSA estandar del paquete materno (Z32/Z34/Z35 APN, 88141 PAP,
 *     85018 Hb, O00-O9A complicaciones/parto/puerperio, P05-P39 morbilidad RN,
 *     90715/90714/90744/90658 inmunizaciones, etc.). TODAS las condiciones estan
 *     centralizadas en maternoSecciones() con un DSL legible y auditable: use el
 *     boton "Ver condiciones SQL" del reporte web para compararlas con su archivo
 *     03 y ajustarlas en un solo lugar.
 *   - El conteo es por CITA (igual que las tablas TRAMA_*_NOMINAL del archivo 02,
 *     que tienen una fila por id_cita); los totales consolidan COUNT(casos).
 *   - periodo >= '202301' del T-SQL se convierte en el filtro web de Anio/Mes.
 *   - La fila TOTAL de cada seccion es la suma de los 4 grupos etareos (la
 *     plantilla calcula lo mismo con =SUM).
 *   - La plantilla oficial no tiene zona de datos para RPT_09_3 (prueba rapida
 *     VIH en trabajo de parto / aborto): en el flujo ODBC original esas 4
 *     categorias quedaban siempre en 0. Se muestran en la web y NO se exportan.
 */

require_once __DIR__ . '/../config.php';

define('MATERNO_DATA_VERSION', '2026-09-04-r1');

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
 * El modulo Materno necesita, ademas de los campos comunes (codigo, valor lab,
 * sexo, edad), tres campos adicionales que los modulos ESNI/Cancer no usaban:
 *   - Fecha_Atencion + Fecha_Ultima_Regla -> edad gestacional (trimestres)
 *   - Hemoglobina                         -> severidad de anemia
 *   - Descripcion_Otra_Condicion          -> 'GESTANTE' / 'PUERPERA'
 *
 * Como en cancer_data.php, los textos se normalizan a MAYUSCULAS para replicar
 * la colacion case-insensitive de MySQL en el matching PHP.
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
 */
function mtrEdadGestacional(array $f): ?float {
    if ($f['fecha'] === null || $f['fur'] === null) return null;
    $dias = ($f['fecha'] - $f['fur']) / 86400.0;
    if ($dias < 0 || $dias > 320) return null; // fuera de rango razonable (0-45 sem)
    return $dias / 7.0;
}

/** Trimestre gestacional (1, 2 o 3) de una fila segun su EG; null si no calculable. */
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
 *   'codEntre'    => ['O10','O16']               BETWEEN de strings (como el T-SQL)
 *   'tip'         => 'D' | ['P','R']             Tipo_Diagnostico
 *   'vl'          => 'NULL' | '1' | ['1','A']    Valor_Lab (NULL = IS NULL)
 *   'vlPresente'  => true                        Valor_Lab IS NOT NULL
 *   'rownum'      => 1                           Id_Correlativo_Lab = N
 *   'sexo'        => 'F' | 'M'                   Id_Genero
 *   'edadA'       => [12, 17] | [30, null]       Tipo_Edad='A' y Edad_Reg en rango
 *   'fgTipo'      => 'CX'                        Fg_Tipo
 *   'gestante'    => true                        Descripcion_Otra_Condicion GESTANTE
 *   'puerpera'    => true                        Descripcion_Otra_Condicion PUERPERA
 *   'otraCond'    => 'NULL' | 'GESTANTE'         igualdad exacta / IS NULL
 *   'hb'          => [10, 10.9] | [7, null]      Hemoglobina presente y en rango
 *   'hbVacia'     => true                        Hemoglobina IS NULL/vacia
 *   'citaTiene'   => <predicado>                 alguna fila de la MISMA cita lo cumple
 *   'citaTieneTodo' => [<pred>, ...]             todas existen en la cita (AND)
 *   'pacTiene'    => <predicado>                 alguna cita del MISMO paciente lo cumple
 *   'pacTieneTodo'  => [<pred>, ...]             todas existen en alguna cita del paciente
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
 *   1 = '<12 a.'  : Tipo_Edad 'D'/'M' o ('A' y edad < 12)
 *   2 = '12 - 17 a.'
 *   3 = '18 - 29 a.'
 *   4 = '30 - 59 a.'
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
                $partes[] = "(" . implode(' OR ', array_map(fn($x) => "codigo_item LIKE '" . $x . "%'", $lista)) . ")";
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
            case 'vlPresente':
                if ($v) $partes[] = "valor_lab IS NOT NULL";
                break;
            case 'rownum':
                $partes[] = "Id_Correlativo_Lab = $v";
                break;
            case 'sexo':
                $partes[] = "id_genero = '$v'";
                break;
            case 'edadA':
                $partes[] = "tipo_edad = 'A' AND edad_reg BETWEEN {$v[0]} AND " . ($v[1] ?? 999);
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
 * 3) DEFINICION DE SECCIONES (etiquetas de las dims del archivo 01
 *    + condiciones adaptadas del archivo 03)
 * ============================================================ */

/**
 * Devuelve la definicion completa de las secciones del reporte de Materno.
 *
 * Cada seccion replica un bloque del Excel "Reporte_Actividades_Materno.xlsx":
 *   - eje 'gedad'    : filas = grupos etareos (4 + TOTAL); 'columnas' = categorias
 *   - eje 'categoria': filas = categorias; columnas = TOTAL + grupos etareos
 *
 * Las etiquetas de las columnas/filas son EXACTAMENTE las de las tablas
 * DimMaterno2023_Categoria01..10 del archivo "01 Creacion tablas iniciales"
 * (CategoriaKey, Grupo, Subgrupo, Categoria), en el mismo orden del Excel.
 *
 * CONDICIONES: la adaptacion inicial del archivo "03 Creacion de Procedimientos"
 * usa los codigos HIS-MINSA estandar del paquete materno. Cada condicion es
 * auditable desde el reporte web (boton "Ver condiciones SQL") y se ajusta en
 * este unico archivo.
 */
function maternoSecciones(): array {
    // Condiciones base reutilizables (adaptadas de los procedimientos RPT_*)
    $labsVIH   = ['cod' => ['86701', '86702', '86703', '87389']];
    $labsSif   = ['cod' => ['86592', '86780', '86781']];
    $labsHepB  = ['cod' => ['87340']];
    $labsBact  = ['cod' => ['87086', '87088', '81003', '81001', '81002']];
    $labsProt  = ['cod' => ['81000', '81003', '81001', '81002']];
    $bateriaLabs = [ // laboratorios de la bateria completa de la gestante
        ['cod' => ['85018']],                          // Dosaje de hemoglobina
        ['cod' => ['81003', '81001', '81005']],        // Examen de orina
        ['cod' => ['86592', '86780']],                  // RPR / VDRL
        ['cod' => ['82947', '82948', '82950']],         // Glicemia
        ['cod' => ['86900', '86901', '86904']],         // Grupo sanguineo y Rh
        ['cod' => ['87340', '86701', '86702', '87389']],// HBsAg / VIH
    ];

    return [

    /* ------------------------------------------------------------
     * SECCION I - RPT_01_APN_REENFOCADA
     * I. ATENCION PRENATAL REENFOCADA (25 columnas, Dim Categoria01)
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT01_APN',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_01_APN_REENFOCADA',
        'titulo'   => 'I. ATENCIÓN PRENATAL REENFOCADA',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 16, 2 => 17, 3 => 18, 4 => 19, 'T' => 20], 'colIni' => 'B'],
        'columnas' => [
            // GESTANTE / ATENDIDA (Total = I + II + III Trim, como =SUM(C:E) del Excel)
            ['key' => 1,  'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'Total',
             'regla' => ['tipo' => 'calc', 'cols' => [2, 3, 4]]],
            ['key' => 2,  'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'I Trim',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'cod' => ['Z321', 'Z320']],
             'regla' => ['tipo' => 'trimestre', 'n' => 1, 'oc' => 1]],
            ['key' => 3,  'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'II Trim',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'cod' => ['Z321', 'Z320']],
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 1]],
            ['key' => 4,  'niv1' => 'Gestante', 'niv2' => 'Atendida', 'niv3' => 'III Trim',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'cod' => ['Z321', 'Z320']],
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 1]],
            ['key' => 5,  'niv1' => 'Gestante', 'niv2' => 'Atenciones', 'niv3' => '----------',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => ['Z32', 'Z34', 'Z35']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 6,  'niv1' => 'Gestante', 'niv2' => 'Controlada', 'niv3' => '----------',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => ['Z32', 'Z34', 'Z35']],
             'regla' => ['tipo' => 'conteoMinimo', 'n' => 6]],
            // PAPANICOLAU
            ['key' => 7,  'niv1' => 'Papanicolaou', 'niv2' => 'Toma de Muestra', 'niv3' => '----------',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'gestante' => true, 'cod' => '88141', 'vl' => 'NULL'],
             'regla' => ['tipo' => 'simple']],
            ['key' => 8,  'niv1' => 'Papanicolaou', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'gestante' => true, 'cod' => '88141',
                        'citaTiene' => ['cod' => ['N870', 'N871', 'N872', 'N873', 'D069', 'R876', 'C539'], 'tip' => 'P']],
             'regla' => ['tipo' => 'simple']],
            // GESTANTE CON BATERIA COMPLETA (1ra / 2da cita con la bateria de laboratorio)
            ['key' => 9,  'niv1' => 'Gestante Controlada con Bateria Completa', 'niv2' => '1° Bateria', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'citaTieneTodo' => $bateriaLabs],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 10, 'niv1' => 'Gestante Controlada con Bateria Completa', 'niv2' => '2° Bateria', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'citaTieneTodo' => $bateriaLabs],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2]],
            // VIOLENCIA BASADA EN GENERO
            ['key' => 11, 'niv1' => 'Violencia Basada en Genero (VBG)', 'niv2' => 'Tamizada', 'niv3' => '----------',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'gestante' => true, 'cod' => ['Z004', '99408']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 12, 'niv1' => 'Violencia Basada en Genero (VBG)', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'gestante' => true,
                        'cualquieraDe' => [ ['codPref' => 'T74'], ['cod' => 'Z634'] ]],
             'regla' => ['tipo' => 'simple']],
            // ECOGRAFIA (1ra / 2da / 3ra ecografia de la gestante)
            ['key' => 13, 'niv1' => 'Ecografía', 'niv2' => '1° Ecografía', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['76801', '76805', '76811']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 14, 'niv1' => 'Ecografía', 'niv2' => '2° Ecografía', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['76801', '76805', '76811']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2]],
            ['key' => 15, 'niv1' => 'Ecografía', 'niv2' => '3° Ecografía', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['76801', '76805', '76811']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 3]],
            // TAMIZAJE DE BACTERIURIA
            ['key' => 16, 'niv1' => 'Tamizaje de Bacteriuria', 'niv2' => 'N°', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsBact,
             'regla' => ['tipo' => 'simple']],
            ['key' => 17, 'niv1' => 'Tamizaje de Bacteriuria', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'vl' => ['1', 'A', 'P', 'R', '+']] + $labsBact,
             'regla' => ['tipo' => 'simple']],
            // TAMIZAJE DE PROTEINURIA
            ['key' => 18, 'niv1' => 'Tamizaje de Proteniuria', 'niv2' => 'N°', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsProt,
             'regla' => ['tipo' => 'simple']],
            ['key' => 19, 'niv1' => 'Tamizaje de Proteniuria', 'niv2' => 'Positivo', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'vl' => ['1', 'A', 'P', 'R', '+']] + $labsProt,
             'regla' => ['tipo' => 'simple']],
            // GESTANTE CON ATENCION PRENATAL REENFOCADA
            ['key' => 20, 'niv1' => 'Gestante con Atención Prenatal Reenfocada', 'niv2' => '----------', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true,
                        'citaTieneTodo' => [ ['codPref' => ['Z34', 'Z35', 'Z32'], 'tip' => 'D'], ['cod' => '85018'] ]],
             'regla' => ['tipo' => 'simple']],
            // INMUNIZACION A LA GESTANTE (PROTEGIDAS)
            ['key' => 21, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'DTPA', 'niv3' => 'Protegidas',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '90715'],
             'regla' => ['tipo' => 'simple']],
            ['key' => 22, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'dT', 'niv3' => 'Protegidas',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '90714'],
             'regla' => ['tipo' => 'ocurrenciaMin', 'n' => 5]],
            ['key' => 23, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'HvB', 'niv3' => 'Protegidas',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['90744', '90746']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 24, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'Influenza', 'niv3' => 'Protegidas',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['90657', '90658']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 25, 'niv1' => 'Inmunización a la Gestante', 'niv2' => 'COVID', 'niv3' => 'Protegidas',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codEntre' => ['90692', '90703']],
             'regla' => ['tipo' => 'simple']],
            // ATENCION ODONTOLOGICA
            ['key' => 26, 'niv1' => 'Atención Odontológica', 'niv2' => 'Protegidas', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codPref' => ['Z012', 'Z013']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION II - RPT_02_BIENESTAR (bloque izquierdo del Excel)
     * EVALUACION DE BIENESTAR FETAL / PSICOPROFILAXIS / ESTIMULACION
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT02_BIENESTAR',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_02_BIENESTAR',
        'titulo'   => 'II. EVALUACIÓN DE BIENESTAR FETAL, PSICOPROFILAXIS Y ESTIMULACIÓN PRENATAL',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 25, 2 => 26, 3 => 27, 4 => 28, 'T' => 29], 'colIni' => 'B'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'Evaluación de Bienestar Fetal', 'niv2' => '1° Monitoreo',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '59025'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 2, 'niv1' => 'Evaluación de Bienestar Fetal', 'niv2' => '2° Monitoreo',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '59025'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2]],
            ['key' => 3, 'niv1' => 'Psicoprofilaxis', 'niv2' => 'Atendida',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99207.04'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 4, 'niv1' => 'Psicoprofilaxis', 'niv2' => 'Preparada',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99207.04'],
             'regla' => ['tipo' => 'ocurrenciaMin', 'n' => 6]],
            ['key' => 5, 'niv1' => 'Estimulación Prenatal', 'niv2' => '1° Sesión',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99207.05'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 6, 'niv1' => 'Estimulación Prenatal', 'niv2' => '6° Sesión',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99207.05'],
             'regla' => ['tipo' => 'ocurrenciaMin', 'n' => 6]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION III - RPT_03_ANEMIA (bloque derecho del Excel)
     * GESTANTE CON ANEMIA / MANEJO TERAPEUTICO / DOSAJE HB / PLAN DE PARTO
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT03_ANEMIA',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_03_ANEMIA',
        'titulo'   => 'III. GESTANTE CON ANEMIA, MANEJO TERAPÉUTICO Y PLAN DE PARTO',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 25, 2 => 26, 3 => 27, 4 => 28, 'T' => 29], 'colIni' => 'J'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Leve',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['D50', 'D64Z'], 'hb' => [10, 10.9]],
             'regla' => ['tipo' => 'simple']],
            ['key' => 2, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Moderada',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['D50', 'D64Z'], 'hb' => [7, 9.9]],
             'regla' => ['tipo' => 'simple']],
            ['key' => 3, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Severa',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['D50', 'D64Z'], 'hb' => [0, 6.9]],
             'regla' => ['tipo' => 'simple']],
            ['key' => 4, 'niv1' => 'Gestante', 'niv2' => 'Anemia', 'niv3' => 'Recuperada',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['D50', 'D64Z'], 'hb' => [11, null]],
             'regla' => ['tipo' => 'simple']],
            ['key' => 5, 'niv1' => 'Gestante', 'niv2' => 'Manejo Terapéutico', 'niv3' => '1° Entrega',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codPref' => ['99604']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 6, 'niv1' => 'Gestante', 'niv2' => 'Manejo Terapéutico', 'niv3' => '6° Entrega',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codPref' => ['99604']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 6]],
            ['key' => 7, 'niv1' => 'Gestante', 'niv2' => 'Dosaje de Hemoglobina', 'niv3' => '1° Dosaje',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '85018'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 8, 'niv1' => 'Plan de Parto', 'niv2' => '1° Entrevista', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codPref' => ['99499']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 9, 'niv1' => 'Plan de Parto', 'niv2' => '2° Entrevista', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codPref' => ['99499']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2]],
            ['key' => 10, 'niv1' => 'Plan de Parto', 'niv2' => '3° Entrevista', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'codPref' => ['99499']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 3]],
            ['key' => 11, 'niv1' => 'Plan de Parto', 'niv2' => 'Efectivo', 'niv3' => '----------',
             'cond' => ['sexo' => 'F', 'tip' => 'D', 'codEntre' => ['O80', 'O84Z'],
                        'pacTiene' => ['codPref' => ['99499'], 'sexo' => 'F']],
             'regla' => ['tipo' => 'simple']],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IV - RPT_04_COMPLICACIONES (15 filas)
     * IV. ATENCION DE LA GESTANTE CON COMPLICACIONES
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT04_COMPLICACIONES',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_04_COMPLICACIONES',
        'titulo'   => 'IV. ATENCIÓN DE LA GESTANTE CON COMPLICACIONES',
        'eje'      => 'categoria',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['colTotal' => 'F', 'colGedad' => [1 => 'G', 2 => 'H', 3 => 'I', 4 => 'J'], 'filaIni' => 33],
        'filas' => [
            ['key' => 1,  'label' => 'Amenaza de parto prematuro',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O47']],
            ['key' => 2,  'label' => 'Hemorragias de la 1º mitad del embarazo sin laparotomía',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O20']],
            ['key' => 3,  'label' => 'Hemorragia de la 2º mitad del embarazo',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => ['O44', 'O45', 'O46']]],
            ['key' => 4,  'label' => 'Hiperémesis gravídica',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O21']],
            ['key' => 5,  'label' => 'Infección del tracto urinario en el embarazo',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O23']],
            ['key' => 6,  'label' => 'Ruptura prematura de membranas y otras relacionadas',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O42']],
            ['key' => 7,  'label' => 'Hemorragias de la 1º mitad del embarazo con laparotomía',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => ['O00', 'O02', 'O08']]],
            ['key' => 8,  'label' => 'Trastorno hipertensivos en el Embarazo',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['O10', 'O16Z']]],
            ['key' => 9,  'label' => 'Trastornos metabólicos del embarazo',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => ['O24', 'O25']]],
            ['key' => 10, 'label' => 'Otras enfermedades del embarazo',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['O98', 'O99Z']]],
            ['key' => 11, 'label' => 'Sepsis',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'cualquieraDe' => [ ['codPref' => 'A41'], ['codPref' => 'O85'] ]]],
            ['key' => 12, 'label' => 'TBC',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['A15', 'A19Z']]],
            ['key' => 13, 'label' => 'Retención de Placentaria',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => ['O72', 'O73']]],
            ['key' => 14, 'label' => 'Ruptura prematura de las membranas',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O42']],
            ['key' => 15, 'label' => 'Desprendimiento Prematuro de la Placenta',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codPref' => 'O45']],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION V - RPT_05_MORBILIDAD_RN (7 filas, columna N unica)
     * V. MORBILIDAD DEL RN
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT05_MORBILIDAD_RN',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_05_MORBILIDAD_RN',
        'titulo'   => 'V. MORBILIDAD DEL RN',
        'eje'      => 'categoria',
        'gedades'  => [1],            // RN: todo cuenta en '<12 a.'
        'soloTotal'=> true,           // el Excel solo muestra la columna 'N°'
        'xmap'     => ['colTotal' => 'O', 'colGedad' => [1 => null], 'filaIni' => 33],
        'filas' => [
            ['key' => 1, 'label' => 'Bajo Peso',
             'cond' => ['tip' => 'D', 'cualquieraDe' => [ ['codPref' => 'P05'], ['cod' => 'P072'] ]]],
            ['key' => 2, 'label' => 'Prematuro',
             'cond' => ['tip' => 'D', 'codPref' => 'P07']],
            ['key' => 3, 'label' => 'Hipoxia',
             'cond' => ['tip' => 'D', 'codEntre' => ['P20', 'P21Z']]],
            ['key' => 4, 'label' => 'Síndrome de Distrés Respiratorio',
             'cond' => ['tip' => 'D', 'codPref' => 'P22']],
            ['key' => 5, 'label' => 'Sepsis Neonatal',
             'cond' => ['tip' => 'D', 'codEntre' => ['P36', 'P39Z']]],
            ['key' => 6, 'label' => 'Sífilis Congénita',
             'cond' => ['tip' => 'D', 'codPref' => 'A50']],
            ['key' => 7, 'label' => 'RN - VIH Expuesto',
             'cond' => ['tip' => 'D', 'cod' => ['R75', 'Z134']]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION VI - RPT_06_ADMIN_MICRONUT (10 columnas)
     * VI. TAMIZAJE DE HB Y ADMINISTRACION PREVENTIVA DE MICRONUTRIENTES
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT06_ADMIN_MICRONUT',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_06_ADMIN_MICRONUT',
        'titulo'   => 'VI. TAMIZAJE DE HB Y ADMINISTRACIÓN PREVENTIVA DE MICRONUTRIENTES',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 53, 2 => 54, 3 => 55, 4 => 56, 'T' => 57], 'colIni' => 'B'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'Dosaje de Hemoglobina', 'niv2' => '1°',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '85018'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 2, 'niv1' => 'Dosaje de Hemoglobina', 'niv2' => '2°',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '85018'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2]],
            ['key' => 3, 'niv1' => 'Dosaje de Hemoglobina', 'niv2' => '3°',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '85018'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 3]],
            ['key' => 4, 'niv1' => 'Suplem. Con Sulfato Ferroso', 'niv2' => 'Atendida',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['99604', '99604.01']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 5, 'niv1' => 'Suplem. Con Sulfato Ferroso', 'niv2' => 'Suplementada - Gestante',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['99604', '99604.01']],
             'regla' => ['tipo' => 'ocurrenciaMin', 'n' => 6]],
            ['key' => 6, 'niv1' => 'Suplem. Con Sulfato Ferroso', 'niv2' => 'Suplementada - Puérpera',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'cod' => ['99604', '99604.01']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 7, 'niv1' => 'Suplem. Con Ácido Fólico', 'niv2' => '1°',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99604.02'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 8, 'niv1' => 'Suplem. Con Ácido Fólico', 'niv2' => '2°',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99604.02'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2]],
            ['key' => 9, 'niv1' => 'Suplem. Cálcio', 'niv2' => 'Atendida 1° Dosis',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99604.03'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 10, 'niv1' => 'Suplem. Cálcio', 'niv2' => 'Suplementada 5° Dosis',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => '99604.03'],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 5]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION VII - RPT_07_PUERPERIO (3 columnas)
     * VII. ATENCION DE PUERPERIO
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT07_PUERPERIO',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_07_PUERPERIO',
        'titulo'   => 'VII. ATENCIÓN DE PUERPERIO',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 53, 2 => 54, 3 => 55, 4 => 56, 'T' => 57], 'colIni' => 'N'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'Puerperio', 'niv2' => 'Atendida',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'cod' => ['Z390', 'Z39']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 2, 'niv1' => 'Puerperio', 'niv2' => 'Controlada',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'cod' => ['Z392', 'Z391']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1]],
            ['key' => 3, 'niv1' => 'Puerperio', 'niv2' => 'Complicada',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'codEntre' => ['O85', 'O92Z']],
             'regla' => ['tipo' => 'simple']],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION VIII - RPT_08_VISITA (2 filas x 3 grupos etareos)
     * VIII. VISITA DOMICILIARIA
     * La plantilla official usa una mini-tabla de 2 filas (A Gestante /
     * A Puérpera) x 3 columnas (12-17 / 18-29 / 30-59) en las filas 50-51.
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT08_VISITA',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_08_VISITA',
        'titulo'   => 'VIII. VISITA DOMICILIARIA',
        'eje'      => 'categoria',
        'gedades'  => [2, 3, 4],      // 12-17 / 18-29 / 30-59 (sin '<12' ni TOTAL)
        'conTotal' => false,
        'xmap'     => ['colGedad' => [2 => 'S', 3 => 'T', 4 => 'U'], 'filaIni' => 50],
        'filas' => [
            ['key' => 1, 'label' => 'A Gestante',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'gestante' => true, 'cod' => ['Z001', 'Z008']]],
            ['key' => 2, 'label' => 'A Puérpera',
             'cond' => ['tip' => 'D', 'sexo' => 'F', 'puerpera' => true, 'cod' => ['Z001', 'Z008']]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IX-1 - RPT_09_1_TRANSMISION_VERTICAL (21 columnas)
     * IX. TRANSMISION VERTICAL - GESTANTES
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT09_1_TRANSMISION_VERTICAL',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_09_1_TRANSMISION_VERTICAL',
        'titulo'   => 'IX. TRANSMISIÓN VERTICAL — GESTANTES',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 64, 2 => 65, 3 => 66, 4 => 67, 'T' => 68], 'colIni' => 'B'],
        'columnas' => [
            // VIH/SIDA - 1° Tamizaje (I / II / III trimestre + Reactivo)
            ['key' => 1,  'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'I Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'trimestre', 'n' => 1, 'oc' => 1]],
            ['key' => 2,  'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'II Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 1]],
            ['key' => 3,  'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'III Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 1]],
            ['key' => 4,  'niv1' => 'VIH/SIDA', 'niv2' => '1° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1,
                         'citaCond' => ['cualquieraDe' => [ ['vl' => ['1', 'A', 'R']] + $labsVIH, ['cod' => ['B20', 'B24'], 'tip' => 'D'] ]]]],
            // VIH/SIDA - 2° Tamizaje (II / III trimestre + Reactivo)
            ['key' => 5,  'niv1' => 'VIH/SIDA', 'niv2' => '2° Tamizaje', 'niv3' => 'II Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 2]],
            ['key' => 6,  'niv1' => 'VIH/SIDA', 'niv2' => '2° Tamizaje', 'niv3' => 'III Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 2]],
            ['key' => 7,  'niv1' => 'VIH/SIDA', 'niv2' => '2° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsVIH,
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2,
                         'citaCond' => ['vl' => ['1', 'A', 'R']] ]],
            // SIFILIS - 1° Tamizaje
            ['key' => 8,  'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'I Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'trimestre', 'n' => 1, 'oc' => 1]],
            ['key' => 9,  'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'II Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 1]],
            ['key' => 10, 'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'III Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 1]],
            ['key' => 11, 'niv1' => 'SIFILIS', 'niv2' => '1° Tamizaje', 'niv3' => 'Positivo',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1,
                         'citaCond' => ['cualquieraDe' => [ ['vl' => ['1', 'A', 'R']] + $labsSif, ['cod' => 'A53', 'tip' => 'D'] ]]]],
            // SIFILIS - 2° Tamizaje
            ['key' => 12, 'niv1' => 'SIFILIS', 'niv2' => '2° Tamizaje', 'niv3' => 'II Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 2]],
            ['key' => 13, 'niv1' => 'SIFILIS', 'niv2' => '2° Tamizaje', 'niv3' => 'III Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 2]],
            ['key' => 14, 'niv1' => 'SIFILIS', 'niv2' => '2° Tamizaje', 'niv3' => 'Positivo',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsSif,
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2,
                         'citaCond' => ['vl' => ['1', 'A', 'R']] ]],
            // HEPATITIS B - 1° Tamizaje
            ['key' => 15, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'I Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'trimestre', 'n' => 1, 'oc' => 1]],
            ['key' => 16, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'II Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 1]],
            ['key' => 17, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'III Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 1]],
            ['key' => 18, 'niv1' => 'HEPATITIS B', 'niv2' => '1° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'ocurrencia', 'n' => 1,
                         'citaCond' => ['cualquieraDe' => [ ['vl' => ['1', 'A', 'R']] + $labsHepB, ['codPref' => 'B16', 'tip' => 'D'] ]]]],
            // HEPATITIS B - 2° Tamizaje
            ['key' => 19, 'niv1' => 'HEPATITIS B', 'niv2' => '2° Tamizaje', 'niv3' => 'II Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'trimestre', 'n' => 2, 'oc' => 2]],
            ['key' => 20, 'niv1' => 'HEPATITIS B', 'niv2' => '2° Tamizaje', 'niv3' => 'III Trim',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'trimestre', 'n' => 3, 'oc' => 2]],
            ['key' => 21, 'niv1' => 'HEPATITIS B', 'niv2' => '2° Tamizaje', 'niv3' => 'Reactivo',
             'cond' => ['sexo' => 'F', 'gestante' => true] + $labsHepB,
             'regla' => ['tipo' => 'ocurrencia', 'n' => 2,
                         'citaCond' => ['vl' => ['1', 'A', 'R']] ]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IX-2 - RPT_09_2_TRANSMISION_VERTICAL (6 columnas)
     * IX. TRANSMISION VERTICAL - PUERPERAS INMEDIATAS
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT09_2_TRANSMISION_VERTICAL',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_09_2_TRANSMISION_VERTICAL',
        'titulo'   => 'IX. TRANSMISIÓN VERTICAL — PUÉRPERAS INMEDIATAS',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 75, 2 => 76, 3 => 77, 4 => 78, 'T' => 79], 'colIni' => 'B'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'VIH/SIDA', 'niv2' => 'PR / Para VIH',
             'cond' => ['sexo' => 'F', 'puerpera' => true] + $labsVIH,
             'regla' => ['tipo' => 'simple']],
            ['key' => 2, 'niv1' => 'VIH/SIDA', 'niv2' => 'Reactivo Para VIH',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'vl' => ['1', 'A', 'R']] + $labsVIH,
             'regla' => ['tipo' => 'simple']],
            ['key' => 3, 'niv1' => 'SIFILIS', 'niv2' => 'Prueba Rápida',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'cod' => ['Z113', '86781']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 4, 'niv1' => 'SIFILIS', 'niv2' => 'Positivo',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'vl' => ['1', 'A', 'R'], 'cod' => ['Z113', '86781']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 5, 'niv1' => 'SIFILIS', 'niv2' => 'Tamizaje RPR',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'cod' => ['86592', '86780']],
             'regla' => ['tipo' => 'simple']],
            ['key' => 6, 'niv1' => 'SIFILIS', 'niv2' => 'RPR Reactivo',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'vl' => ['1', 'A', 'R'], 'cod' => ['86592', '86780']],
             'regla' => ['tipo' => 'simple']],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION IX-3 - RPT_09_3_TRANSMISION_VERTICAL (4 columnas)
     * Prueba rapida VIH en trabajo de parto / aborto.
     * La plantilla oficial NO tiene zona de datos para este bloque (en el
     * flujo ODBC original quedaba siempre en 0): se muestra en la web con
     * fines de auditoria y NO se exporta al Excel.
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT09_3_TRANSMISION_VERTICAL',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_09_3_TRANSMISION_VERTICAL',
        'titulo'   => 'IX. TRANSMISIÓN VERTICAL — 1° PRUEBA RÁPIDA PARA VIH EN TRABAJO DE PARTO / ABORTO',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => null, // sin zona en la plantilla oficial
        'columnas' => [
            ['key' => 1, 'niv1' => 'VIH en Trabajo de Parto', 'niv2' => '1° Prueba Rápida',
             'cond' => ['sexo' => 'F', 'citaTiene' => ['tip' => 'D', 'codEntre' => ['O80', 'O84Z']]] + $labsVIH,
             'regla' => ['tipo' => 'simple']],
            ['key' => 2, 'niv1' => 'VIH en Trabajo de Parto', 'niv2' => 'Reactivo',
             'cond' => ['sexo' => 'F', 'citaTiene' => ['tip' => 'D', 'codEntre' => ['O80', 'O84Z']]] + $labsVIH,
             'regla' => ['tipo' => 'simple', 'citaCond' => ['vl' => ['1', 'A', 'R']]]],
            ['key' => 3, 'niv1' => 'VIH en Aborto', 'niv2' => '1° Prueba Rápida',
             'cond' => ['sexo' => 'F', 'citaTiene' => ['tip' => 'D', 'codEntre' => ['O00', 'O07Z']]] + $labsVIH,
             'regla' => ['tipo' => 'simple']],
            ['key' => 4, 'niv1' => 'VIH en Aborto', 'niv2' => 'Reactivo',
             'cond' => ['sexo' => 'F', 'citaTiene' => ['tip' => 'D', 'codEntre' => ['O00', 'O07Z']]] + $labsVIH,
             'regla' => ['tipo' => 'simple', 'citaCond' => ['vl' => ['1', 'A', 'R']]]],
        ],
    ],

    /* ------------------------------------------------------------
     * SECCION X - RPT_10_CONSEJERIA (3 columnas)
     * X. CONSEJERIA EN LACTANCIA MATERNA
     * ---------------------------------------------------------- */
    [
        'codigo'   => 'RPT10_CONSEJERIA',
        'procedimiento' => 'usp_TRAMA_BASE_MATERNO_2023_RPT_10_CONSEJERIA',
        'titulo'   => 'X. CONSEJERÍA EN LACTANCIA MATERNA',
        'eje'      => 'gedad',
        'gedades'  => [1, 2, 3, 4],
        'xmap'     => ['filas' => [1 => 85, 2 => 86, 3 => 87, 4 => 88, 'T' => 89], 'colIni' => 'B'],
        'columnas' => [
            ['key' => 1, 'niv1' => 'Consejería Lactancia Materna', 'niv2' => 'GESTANTE 3° CONSEJERÍA',
             'cond' => ['sexo' => 'F', 'gestante' => true, 'cod' => ['99401', '99207.04']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 3]],
            ['key' => 2, 'niv1' => 'Consejería Lactancia Materna', 'niv2' => 'PUÉRPERIO INMEDIATO 4° CONSEJERÍA',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'cod' => ['99401', '99207.04']],
             'regla' => ['tipo' => 'ocurrencia', 'n' => 4]],
            ['key' => 3, 'niv1' => 'Consejería Lactancia Materna', 'niv2' => 'ATENCIÓN PUÉRPERAL 5° a + CONSEJERÍA',
             'cond' => ['sexo' => 'F', 'puerpera' => true, 'cod' => ['99401', '99207.04']],
             'regla' => ['tipo' => 'ocurrenciaMin', 'n' => 5]],
        ],
    ],
    ];
}

/* ============================================================
 * 4) MOTOR DE EJECUCION DEL REPORTE
 * ============================================================ */

/** Anios disponibles en la tabla consolidada (para el filtro). */
function maternoGetAniosDisponibles(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
                             WHERE Anio IS NOT NULL ORDER BY Anio DESC");
        $anios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $anios ?: [date('Y')];
    } catch (Throwable $e) {
        return [date('Y')];
    }
}

/** Establecimientos del catalogo ZSPERENE (clave = Codigo_Unico / RENAES). */
function maternoGetEstablecimientosZS(PDO $pdo): array {
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
 * Codigos de item que intervienen en el reporte de Materno (para acotar la
 * consulta a la tabla consolidada): prefijos CIE del paquete materno + items
 * de laboratorio / procedimientos / vacunas.
 */
function maternoCodigosInteres(): array {
    return [
        // Prefijos CIE (se usan con LIKE 'xxx%')
        'O',                                  // embarazo / parto / puerperio / complicaciones (O00-O9A)
        'Z32', 'Z33', 'Z34', 'Z35',           // atencion prenatal
        'Z39', 'Z390', 'Z391', 'Z392',        // atencion de puerperio
        'Z00', 'Z001', 'Z008', 'Z012', 'Z013',// visita domiciliaria / atencion odontologica
        'Z63', 'Z634',                        // VBG (positivo)
        'Z11', 'Z113', 'Z114',                // tamizajes
        'D50',                                // anemia (D50-D64)
        'P05', 'P07', 'P2', 'P3',             // morbilidad del RN
        'A50', 'A41', 'A53',                  // sifilis congenita / sepsis / sifilis
        'A15', 'A16', 'A17', 'A18', 'A19',    // TBC
        'B16', 'B17', 'B18', 'B20', 'B24',    // hepatitis B / VIH
        'T74',                                // maltrato (VBG positivo)
        'R75', 'Z134',                        // RN VIH expuesto
        'N87', 'N870', 'N871', 'N872', 'N873',// lesiones PAP
        'D069', 'R876', 'C539',               // lesiones PAP (con C539 para PAP positivo)
        // Items exactos (laboratorio / procedimientos / vacunas / consejeria)
        '88141',                              // Papanicolaou
        '85018',                              // Dosaje de hemoglobina
        '81000', '81001', '81002', '81003', '81005', // orina / proteinuria
        '82947', '82948', '82950',            // glicemia
        '86592', '86780', '86781',            // RPR / VDRL / prueba rapida sifilis
        '86701', '86702', '86703', '87389',   // VIH
        '87340',                              // HBsAg (hepatitis B)
        '86900', '86901', '86904',            // grupo sanguineo y Rh
        '87086', '87088',                     // urocultivo (bacteriuria)
        '76801', '76805', '76811',            // ecografia obstetrica
        '59025',                              // monitoreo fetal
        '99604', '99604.01', '99604.02', '99604.03', // entrega de micronutrientes
        '99401', '99207.04', '99207.05', '99408', '99499', // consejeria / plan de parto / VBG
        '90715', '90714',                     // dtpa / dT (gestantes)
        '90744', '90746',                     // Hepatitis B (gestantes)
        '90657', '90658',                     // Influenza
    ];
}

/**
 * Construye la clausula WHERE de codigos de item a partir de
 * maternoCodigosInteres(): prefijos (LIKE) + codigos exactos (IN).
 */
function maternoWhereCodigos(array &$params): string {
    $codigos = maternoCodigosInteres();
    $likes = [];
    $exactos = [];
    foreach ($codigos as $c) {
        if (strlen($c) === 1) {
            $likes[] = $c;
        } else {
            $exactos[] = $c;
        }
    }
    $partes = [];
    $i = 0;
    foreach ($likes as $pref) {
        $k = ":pref_$i";
        $params[$k] = $pref . '%';
        $partes[] = "Codigo_Item LIKE $k";
        $i++;
    }
    $inPh = [];
    foreach ($exactos as $j => $c) {
        $k = ":cod_$j";
        $params[$k] = $c;
        $inPh[] = $k;
    }
    if ($inPh) $partes[] = "Codigo_Item IN (" . implode(',', $inPh) . ")";
    // Vacunas COVID: rango 90692-90703 (codigo_item alfanumerico de 5 digitos)
    $params[':cod_covid_min'] = '90692';
    $params[':cod_covid_max'] = '90703';
    $partes[] = "(Codigo_Item BETWEEN :cod_covid_min AND :cod_covid_max AND Codigo_Item REGEXP '^90[0-9]{3}$')";
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
 * Cuenta las CITAS que cumplen una condicion aplicando la regla de conteo.
 *
 * 1) Encuentra las filas que cumplen la condicion y las DEDUP por cita
 *    (una cita = una atencion: varias filas de la misma cita cuentan 1 vez,
 *    igual que las tablas TRAMA_*_NOMINAL del archivo 02).
 * 2) Ordena las citas de cada paciente por fecha.
 * 3) Aplica la regla:
 *      simple          : cada cita cuenta en su grupo etareo
 *      trimestre N [oc]: la oc-esima cita del paciente cuenta si su edad
 *                        gestacional (por FUR) cae en el trimestre N
 *      ocurrencia N    : la N-esima cita del paciente cuenta 1 vez
 *      ocurrenciaMin N : pacientes con N o mas citas: la N-esima cuenta
 *      conteoMinimo N  : pacientes con N o mas citas cuentan 1 vez (la 1a)
 *
 * @return array ['valores'=>[gedad=>n], 'citas'=>int, 'pacientes'=>int]
 */
function mtrContarRegla(array $cond, array $regla, array $ctx): array {
    $valores = [];
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
    // (el HIS repite la FUR en todas las filas de la cita, pero si solo viene
    // en una, se propaga aqui para no perder el trimestre del tamizaje).
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
    $tipo = $regla['tipo'] ?? 'simple';
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
    if (!empty($filtros['establecimiento'])) {
        $where[] = "TRIM(Codigo_Unico) = :est";
        $params[':est'] = (string)$filtros['establecimiento'];
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
