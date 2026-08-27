<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Exportar Reporte Operacional ESNI a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera un archivo Excel usando como base la plantilla oficial "Plantilla.xlsx"
 * (ubicada en uploads/Plantilla.xlsx) y llenando las celdas con los datos del
 * reporte ESNI generado por el motor data-driven.
 *
 * Mapeo de celdas (Seccion A - Menores de 01 anio):
 *
 *   ENCABEZADO (filtros seleccionados por el usuario):
 *     C2 = Establecimiento (nombre)
 *     H2 = Mes (nombre, ej: "Enero")
 *     L2 = Anio
 *
 *   BCG (dosis unica -> G=Casos, J=Total=Casos):
 *     G7  = BCG - 24 HORAS (Casos)
 *     J7  = BCG - 24 HORAS (Total = G7)
 *     G8  = BCG - 28 DIAS (Casos)
 *     J8  = BCG - 28 DIAS (Total = G8)
 *     G9  = BCG - DE 01M A 11M 29D (Casos)
 *     J9  = BCG - DE 01M A 11M 29D (Total = G9)
 *
 *   HEPATITIS VIRAL B (dosis unica -> G=Casos, J=Total=Casos):
 *     G10 = HEPATITIS VIRAL B - 12 HORAS (Casos)
 *     J10 = HEPATITIS VIRAL B - 12 HORAS (Total = G10)
 *     G11 = HEPATITIS VIRAL B - 24 HORAS (Casos)
 *     J11 = HEPATITIS VIRAL B - 24 HORAS (Total = G11)
 *
 *   ANTIPOLIO IPV (3 dosis -> G=1ra, H=2da, I=3ra, J=Total=G+H+I):
 *     G13 = ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS
 *     H13 = ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS
 *     I13 = ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS
 *     J13 = G13 + H13 + I13
 *
 *   PENTAVALENTE (3 dosis -> G=1ra, H=2da, I=3ra, J=Total=G+H+I):
 *     G15 = PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS
 *     H15 = PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS
 *     I15 = PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS
 *     J15 = G15 + H15 + I15
 *
 *   ROTAVIRUS (2 dosis -> G=1ra, H=2da, J=Total=G+H):
 *     G20 = ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS
 *     H20 = ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS
 *     J20 = G20 + H20
 *
 *   NEUMOCOCO (2 dosis -> G=1ra, H=2da, J=Total=G+H):
 *     G21 = NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS
 *     H21 = NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS
 *     J21 = G21 + H21
 *
 *   INFLUENZA (2 dosis -> G=1ra, H=2da, J=Total=G+H):
 *     G22 = INFLUENZA - 06 Y 07 MESES - 1RA DOSIS
 *     H22 = INFLUENZA - 06 Y 07 MESES - 2DA DOSIS
 *     J22 = G22 + H22
 *
 *   SECCION B - MENORES DE 01 ANIO (celdas I28..J41):
 *     I28/J28 = 1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS  (I=Casos, J=I)
 *     G29/J29 = 1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS        (G=Casos, J=G)
 *     G30/J30 = 1A 11M 29D - DOSIS UNICA - INFLUENZA           (G=Casos, J=G)
 *     G31/J31 = VARICELA 1RA                                  (G=Casos, J=G)
 *     G33/J33 = 15 MESES - ANTIAMARILICA - DOSIS UNICA       (G=Casos, J=G)
 *     G34/J34 = 15 MESES - HEPATITIS A - DOSIS UNICA         (G=Casos, J=G)
 *     H35/J35 = 18 MESES - SPR - 2DA DOSIS                   (H=Casos, J=H)
 *     G36/J36 = 18 MESES - REF. DPT - 1RA DOSIS              (G=Casos, J=G)
 *     G37/J37 = 18 MESES - REF. IPV                          (G=Casos, J=G)
 *     G38/J38 = 18 MESES - REF. PENTAVALENTE                (G=Casos, J=G)
 *     I39/J39 = No vacunado IPV                             (I=Casos, J=I)
 *     H41      = No vacunado PENTAVALENTE 2da               (H=Casos)
 *     I41      = No vacunado PENTAVALENTE 3ra               (I=Casos)
 *     J41      = H41 + I41                                  (suma)
 *
 * Seccion C - Mayores de 01 anio (celdas F/I en filas 50-66):
 *     F50/I50 = INFLUENZA CON COMORBILIDAD - 1RA DOSIS  (F=Casos, I=F)
 *     F51/I51 = INFLUENZA SIN COMORBILIDAD - 1RA DOSIS  (F=Casos, I=F)
 *     F52/I52 = NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS   (F=Casos, I=F)
 *     F53 = VACUNACION NO OPORTUNA - NEUMOCOCO D1
 *     G53 = VACUNACION NO OPORTUNA - NEUMOCOCO D2
 *     H53 = VACUNACION NO OPORTUNA - NEUMOCOCO D3
 *     I53 = F53 + G53 + H53
 *     F55/I55 = ANTIAMARILICA - 1RA DOSIS                (F=Casos, I=F)
 *     F62 = VACUNACION NO OPORTUNA - SPR - 1RA DOSIS
 *     G62 = VACUNACION NO OPORTUNA - SPR - 2DA DOSIS
 *     I62 = F62 + G62
 *     F65/I65 = REFUERZO ANTIPOLIO IPV - 1RA DOSIS      (F=Casos, I=F)
 *     F66/I66 = REFUERZO PENTAVALENTE - 1RA DOSIS       (F=Casos, I=F)
 *
 * Seccion D - DE 03 ANIOS (celdas J/K/L/M en filas 50-66):
 *     J50/M50 = INFLUENZA CON COMORBILIDAD - 1RA DOSIS  (J=Casos, M=J)
 *     J51/M51 = INFLUENZA SIN COMORBILIDAD - 1RA DOSIS  (J=Casos, M=J)
 *     J52/M52 = NEUMOCOCO CON COMORBILIDAD              (J=Casos, M=J)
 *     J55/M55 = ANTIAMARILICA                            (J=Casos, M=J)
 *     J58     = Pentavalente No vacunado D1               (J=Casos)
 *     K58     = Pentavalente No vacunado D2               (K=Casos)
 *     L58     = Pentavalente No vacunado D3               (L=Casos)
 *     M58     = J58 + K58 + L58                          (suma)
 *     J62     = SPR 1RA Dosis                            (J=Casos)
 *     K62     = SPR 2DA Dosis                            (K=Casos)
 *     M62     = J62 + K62                                (suma)
 *     J64/M64 = REFUERZO DPT                             (J=Casos, M=J)
 *     J65/M65 = REFUERZO ANTIPOLIO IPV                   (J=Casos, M=J)
 *     J66/M66 = REFUERZO PENTAVALENTE                     (J=Casos, M=J)
 *
 * Seccion E1 - GRUPO ESPECIAL / RIESGO (celdas F/G/H/I en filas 71-86):
 *     F71/I71 = INFLUENZA CON COMORBILIDAD - 1RA DOSIS    (F=Casos, I=F)
 *     F72/I72 = INFLUENZA SIN COMORBILIDAD - 1RA DOSIS    (F=Casos, I=F)
 *     F73/I73 = Neumococo con Comorbilidad                 (F=Casos, I=F)
 *     F75/I75 = ANTIAMARILICA                             (F=Casos, I=F)
 *     F78     = Pentavalente D1 -No vacunado               (F=Casos)
 *     G78     = Pentavalente D2 -No vacunado               (G=Casos)
 *     H78     = Pentavalente D3 -No vacunado               (H=Casos)
 *     I78     = F78 + G78 + H78                           (suma)
 *     F82     = SPR 1RA Dosis                             (F=Casos)
 *     G82     = SPR 2DA Dosis                             (G=Casos)
 *     I82     = F82 + G82                                 (suma)
 *     F84/I84 = REFUERZO ANTIPOLIO(IPV)                    (F=Casos, I=F)
 *     G85/I85 = REFUERZO DPT                               (G=Casos, I=G)
 *     G86/I86 = REFUERZO ANTIPOLIO(APO)                    (G=Casos, I=G)
 *
 * Seccion E2 - GRUPO ESPECIAL / RIESGO (celdas J/K/L/M en filas 71-86):
 *     J78     = Pentavalente D1 -No vacunado               (J=Casos)
 *     K78     = Pentavalente D2 -No vacunado               (K=Casos)
 *     L78     = Pentavalente D3 -No vacunado               (L=Casos)
 *     M78     = J78 + K78 + L78                           (suma)
 *     K85     = REFUERZO DPT                               (K=Casos)
 *     M85     = REFUERZO DPT                               (M=Casos = K85)
 *
 * Seccion F - dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS
 *   (celdas M/N/O/P en filas 8-14, layout matriz_dosis):
 *     M8/N8/O8 = dT 1ra/2da/3ra - Mujeres 5 a 9 anos       (M=D1, N=D2, O=D3)
 *     P8  = M8 + N8 + O8                                  (suma)
 *     M9/N9/O9 = dT 1ra/2da/3ra - Mujeres 10 a 11 anos
 *     P9  = M9 + N9 + O9
 *     M10/N10/O10 = dT 1ra/2da/3ra - Mujeres 12 a 17 anos
 *     P10 = M10 + N10 + O10
 *     M11/N11/O11 = dT 1ra/2da/3ra - Mujeres 18 a 29 anos
 *     P11 = M11 + N11 + O11
 *     M12/N12/O12 = dT 1ra/2da/3ra - Mujeres 30 a 49 anos
 *     P12 = M12 + N12 + O12
 *     M13/N13/O13 = dT 1ra/2da/3ra - Mujeres 50 a 59 anos
 *     P13 = M13 + N13 + O13
 *     M14/N14/O14 = dT 1ra/2da/3ra - Mujeres 60 a mas anos
 *     P14 = M14 + N14 + O14
 *
 * Seccion F2 - dT (GRUPOS DE EDAD)  (celdas M/N/O/P en filas 19-23, layout matriz_dosis):
 *     M19/N19/O19 = dT 1ra/2da/3ra - 10 y 11 anos         (M=D1, N=D2, O=D3)
 *     P19 = M19 + N19 + O19                              (suma)
 *     M20/N20/O20 = dT 1ra/2da/3ra - 12 y 17 anos
 *     P20 = M20 + N20 + O20
 *     M21/N21/O21 = dT 1ra/2da/3ra - 18 y 29 anos
 *     P21 = M21 + N21 + O21
 *     M22/N22/O22 = dT 1ra/2da/3ra - 30 y 49 anos
 *     P22 = M22 + N22 + O22
 *     M23/N23/O23 = dT 1ra/2da/3ra - 50 y 59 anos
 *     P23 = M23 + N23 + O23
 *
 * Seccion G - dT ADULTO EN VARONES EN RIESGO (celdas M/N/O/P en filas 28-33,
 * layout matriz_dosis):
 *     M28/N28/O28 = dT 1ra/2da/3ra - 05 y 09 anios (varones)   (M=D1, N=D2, O=D3)
 *     P28 = M28 + N28 + O28                                    (suma)
 *     M29/N29/O29 = dT 1ra/2da/3ra - 10 y 11 anios (varones)
 *     P29 = M29 + N29 + O29
 *     M30/N30/O30 = dT 1ra/2da/3ra - 12 y 17 anios (varones)
 *     P30 = M30 + N30 + O30
 *     M31/N31/O31 = dT 1ra/2da/3ra - 18 y 29 anios (varones)
 *     P31 = M31 + N31 + O31
 *     M32/N32/O32 = dT 1ra/2da/3ra - 30 y 59 anios (varones)
 *     P32 = M32 + N32 + O32
 *     M33/N33/O33 = dT 1ra/2da/3ra - 60 anios a mas (varones)
 *     P33 = M33 + N33 + O33
 */

require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';
require_once 'includes/ExcelTemplateFiller.php';

// ============================================================================
// 0. Validar que exista la plantilla en uploads/Plantilla.xlsx
// ============================================================================
$templatePath = __DIR__ . '/uploads/Plantilla.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    htmlError(
        'Falta la plantilla Excel',
        'No se encontro <code>uploads/Plantilla.xlsx</code> en el servidor.<br><br>' .
        '<b>Soluci&oacute;n:</b> Suba el archivo <code>Plantilla.xlsx</code> a la carpeta <code>uploads/</code> del proyecto mediante FTP o el administrador de archivos del hosting.<br><br>' .
        'Ruta esperada: <code>' . htmlspecialchars($templatePath) . '</code>'
    );
}

// Verificar que la extension ZipArchive este disponible
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    htmlError(
        'Extension ZIP no disponible',
        'El servidor PHP no tiene cargada la extension <code>zip</code> (clase <code>ZipArchive</code>).<br><br>' .
        '<b>Soluci&oacute;n:</b> En InfinityFree esto se activa desde el panel de control &rarr; PHP Configuration &rarr; marcar "zip". En otros hostings, editar <code>php.ini</code> y agregar <code>extension=zip</code>.'
    );
}

// Crear el directorio uploads/tmp/ con permisos adecuados (si no existe)
$uploadsTmp = __DIR__ . '/uploads/tmp';
if (!is_dir($uploadsTmp)) {
    @mkdir($uploadsTmp, 0755, true);
}

// ============================================================================
// 1. Filtros (los mismos que reporte_esni.php)
// ============================================================================
$pdo = getDBConnection();

if (!esniEsquemaInstalado($pdo)) {
    http_response_code(500);
    die('Esquema ESNI no instalado. Ejecute Database/install_esni.sql primero.');
}

// Estrategia fija del modulo ESNI: solo Id_Ups = 301204 (Inmunizaciones)
define('ESNI_ID_UPS', '301204');

$filtros = [
    'anio'            => trim($_GET['anio'] ?? ''),
    'mes'             => trim($_GET['mes'] ?? ''),
    'establecimiento' => trim($_GET['establecimiento'] ?? ''),
    'id_ups'          => ESNI_ID_UPS,
];

// El valor de "establecimiento" llega como Codigo_Unico (cargado desde ZSPERENE).
// Se resuelve el nombre para mostrarlo en C2.
$nombreEstablecimiento = '';
$estExport = esniGetEstablecimientosZS($pdo);
$establecimientosPermitidos = array_keys($estExport);
if ($filtros['establecimiento'] !== '') {
    $nombreEstablecimiento = $estExport[$filtros['establecimiento']] ?? $filtros['establecimiento'];
}

// ============================================================================
// 2. Ejecutar reporte ESNI
// ============================================================================
$cols = esniResolverColumnas($pdo);
$reporte = esniEjecutarReporte($pdo, $filtros, $cols, $establecimientosPermitidos);

if (!empty($reporte['error'])) {
    http_response_code(500);
    die('Error al generar reporte: ' . $reporte['error']);
}

// ============================================================================
// 3. Indexar lineas de las SECCIONES A, B, C y D por etiqueta normalizada
// ----------------------------------------------------------------------------
// El motor de reglas devuelve $reporte['secciones'] con todas las secciones
// (A, B, C, D, H, ...). Nos interesan las secciones "A" (Menores de 1 anio,
// casilla A), "B" (Menores de 1 anio - seccion B de la plantilla),
// "C" (Mayores de 01 anio - seccion C de la plantilla) y
// "D" (DE 03 anios - seccion D de la plantilla).
//
// La etiqueta de la linea puede tener ligeras variaciones (espacios extra,
// Mayusculas) respecto a la nomenclatura de la plantilla. Por eso se
// normaliza con una funcion que:
//   - Pasa a MAYUSCULAS
//   - Colapsa espacios multiples
//   - Quita espacios al inicio/final
// ============================================================================

$seccionA = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'A') === 0) {
        $seccionA = $sec;
        break;
    }
}

// Construir mapa [etiqueta_normalizada => cantidad] para Seccion A
$casosPorEtiqueta = [];
if ($seccionA !== null) {
    foreach ($seccionA['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        // Si la etiqueta ya existe (no deberia), sumamos las cantidades
        // para ser tolerantes con configuraciones que registren la misma
        // vacuna en varias lineas con la misma etiqueta.
        if (isset($casosPorEtiqueta[$etqNorm])) {
            $casosPorEtiqueta[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiqueta[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.1 Indexar lineas de la SECCION B (id_seccion = 2 en la BD) por etiqueta
// normalizada. Las etiquetas aqui son las del esquema ESNI (BCG, Hepatitis,
// IPV, Pentavalente, etc.) pero para el rango etario de 1 anio (12 meses).
// -----------------------------------------------------------------------------
$seccionB = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'B') === 0) {
        $seccionB = $sec;
        break;
    }
}

$casosPorEtiquetaB = [];
if ($seccionB !== null) {
    foreach ($seccionB['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaB[$etqNorm])) {
            $casosPorEtiquetaB[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaB[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.2 Indexar lineas de la SECCION C (Mayores de 01 anio) por etiqueta
// normalizada.
// -----------------------------------------------------------------------------
$seccionC = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'C') === 0) {
        $seccionC = $sec;
        break;
    }
}

$casosPorEtiquetaC = [];
if ($seccionC !== null) {
    foreach ($seccionC['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaC[$etqNorm])) {
            $casosPorEtiquetaC[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaC[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.3 Indexar lineas de la SECCION D (DE 03 ANIOS) por etiqueta
// normalizada.
// -----------------------------------------------------------------------------
$seccionD = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'D') === 0) {
        $seccionD = $sec;
        break;
    }
}

$casosPorEtiquetaD = [];
if ($seccionD !== null) {
    foreach ($seccionD['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaD[$etqNorm])) {
            $casosPorEtiquetaD[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaD[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.4 Indexar lineas de la SECCION E1 (GRUPO ESPECIAL / RIESGO) por etiqueta
// normalizada. Esta seccion alimenta las celdas F/G/H/I de las filas 71-86
// de la plantilla oficial.
// -----------------------------------------------------------------------------
$seccionE1 = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'E1') === 0) {
        $seccionE1 = $sec;
        break;
    }
}

$casosPorEtiquetaE1 = [];
if ($seccionE1 !== null) {
    foreach ($seccionE1['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaE1[$etqNorm])) {
            $casosPorEtiquetaE1[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaE1[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.5 Indexar lineas de la SECCION E2 (GRUPO ESPECIAL / RIESGO) por etiqueta
// normalizada. Esta seccion alimenta las celdas J/K/L/M de las filas 71-86
// de la plantilla oficial (paralelo a la seccion E1 que usa F/G/H/I).
// -----------------------------------------------------------------------------
$seccionE2 = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'E2') === 0) {
        $seccionE2 = $sec;
        break;
    }
}

$casosPorEtiquetaE2 = [];
if ($seccionE2 !== null) {
    foreach ($seccionE2['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaE2[$etqNorm])) {
            $casosPorEtiquetaE2[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaE2[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.6 Indexar lineas de la SECCION F (dT ADULTO EN MUJERES EN EDAD FERTIL
// DESDE 5 ANIOS) por etiqueta normalizada. Esta seccion tiene layout
// "matriz_dosis" y alimenta las celdas M/N/O/P de las filas 8-14 de la
// plantilla oficial (D1, D2, D3 y Total por grupo de edad).
// -----------------------------------------------------------------------------
$seccionF = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'F') === 0) {
        $seccionF = $sec;
        break;
    }
}

$casosPorEtiquetaF = [];
if ($seccionF !== null) {
    foreach ($seccionF['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaF[$etqNorm])) {
            $casosPorEtiquetaF[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaF[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.7 Indexar lineas de la SECCION F2 (dT por grupos de edad 10-11, 12-17,
// 18-29, 30-49, 50-59 anos) por etiqueta normalizada. Esta seccion tiene
// layout "matriz_dosis" y alimenta las celdas M/N/O/P de las filas 19-23
// de la plantilla oficial (D1, D2, D3 y Total por grupo de edad).
// -----------------------------------------------------------------------------
$seccionF2 = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'F2') === 0) {
        $seccionF2 = $sec;
        break;
    }
}

$casosPorEtiquetaF2 = [];
if ($seccionF2 !== null) {
    foreach ($seccionF2['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaF2[$etqNorm])) {
            $casosPorEtiquetaF2[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaF2[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

// -----------------------------------------------------------------------------
// 3.8 Indexar lineas de la SECCION G (dT ADULTO EN VARONES EN RIESGO) por
// etiqueta normalizada. Esta seccion tiene layout "matriz_dosis" y alimenta
// las celdas M/N/O/P de las filas 28-33 de la plantilla oficial (D1, D2, D3
// y Total por grupo de edad: 05-09, 10-11, 12-17, 18-29, 30-59, 60 a mas).
// -----------------------------------------------------------------------------
$seccionG = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'G') === 0) {
        $seccionG = $sec;
        break;
    }
}

$casosPorEtiquetaG = [];
if ($seccionG !== null) {
    foreach ($seccionG['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        if (isset($casosPorEtiquetaG[$etqNorm])) {
            $casosPorEtiquetaG[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiquetaG[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

/**
 * Helper: obtiene la cantidad de casos para una etiqueta de linea.
 * Devuelve 0 si la etiqueta no existe en la seccion indicada (no se
 * registraron dosis para esa vacuna/dosis/grupo).
 *
 * @param array  $casosPorEtiqueta Mapa [etiqueta_normalizada => int].
 * @param string $etiqueta         Etiqueta tal como esta en ESNI_LINEA_REPORTE.
 * @return int
 */
function esniGetCasos(array $casosPorEtiqueta, string $etiqueta): int
{
    $etqNorm = esniNormalizarEtiqueta($etiqueta);
    return $casosPorEtiqueta[$etqNorm] ?? 0;
}

/**
 * Normaliza una etiqueta de linea para comparacion robusta.
 *   - MAYUSCULAS
 *   - Colapsa espacios multiples a uno solo
 *   - Quita espacios al inicio/final
 *   - Quita asterisco inicial "*" (marcador de "linea informativa")
 *
 * @param string $etiqueta
 * @return string
 */
function esniNormalizarEtiqueta(string $etiqueta): string
{
    $s = trim($etiqueta);
    // Quitar asterisco inicial si lo hay (lineas informativas como "* Personal de Salud")
    $s = preg_replace('/^\*\s*/', '', $s);
    // PASAR A MAYUSCULAS (preserva acentos)
    $s = mb_strtoupper($s, 'UTF-8');
    // Colapsar espacios multiples
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

// ============================================================================
// 4. Mapear lineas de seccion A a celdas de la plantilla
// ----------------------------------------------------------------------------
// El array $cellMap define, para cada celda destino de la plantilla, la
// etiqueta exacta de la linea (en ESNI_LINEA_REPORTE.etiqueta) de donde se
// toma el valor "Casos".
//
// Las celdas J (totales) se calculan despues como suma de las G/H/I
// correspondientes, salvo en los casos de dosis unica (BCG, Hepatitis B)
// donde J = G (la unica dosis aplicada).
// ============================================================================

// 4.1 Encabezado (filtros seleccionados por el usuario)
$nombreMes = $filtros['mes'] !== ''
    ? getNombreMes((int)$filtros['mes'])
    : 'TODOS';
$nombreAnio = $filtros['anio'] !== '' ? $filtros['anio'] : 'TODOS';
$nombreEst = $nombreEstablecimiento !== '' ? $nombreEstablecimiento : 'TODOS';

// 4.2 Casos por linea (Seccion A)
$bcg_24h    = esniGetCasos($casosPorEtiqueta, 'BCG - 24 HORAS');
$bcg_28d    = esniGetCasos($casosPorEtiqueta, 'BCG - 28 DIAS');
$bcg_01_11m = esniGetCasos($casosPorEtiqueta, 'BCG - DE 01M A 11M 29D');

$vhb_12h    = esniGetCasos($casosPorEtiqueta, 'HEPATITIS VIRAL B - 12 HORAS');
$vhb_24h    = esniGetCasos($casosPorEtiqueta, 'HEPATITIS VIRAL B - 24 HORAS');

$ipv_d1     = esniGetCasos($casosPorEtiqueta, 'ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS');
$ipv_d2     = esniGetCasos($casosPorEtiqueta, 'ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS');
$ipv_d3     = esniGetCasos($casosPorEtiqueta, 'ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS');

$penta_d1   = esniGetCasos($casosPorEtiqueta, 'PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS');
$penta_d2   = esniGetCasos($casosPorEtiqueta, 'PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS');
$penta_d3   = esniGetCasos($casosPorEtiqueta, 'PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS');

$rota_d1    = esniGetCasos($casosPorEtiqueta, 'ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS');
$rota_d2    = esniGetCasos($casosPorEtiqueta, 'ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS');

$neumo_d1   = esniGetCasos($casosPorEtiqueta, 'NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS');
$neumo_d2   = esniGetCasos($casosPorEtiqueta, 'NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS');

$inf_d1     = esniGetCasos($casosPorEtiqueta, 'INFLUENZA - 06 Y 07 MESES - 1RA DOSIS');
$inf_d2     = esniGetCasos($casosPorEtiqueta, 'INFLUENZA - 06 Y 07 MESES - 2DA DOSIS');

// 4.3 Calcular totales por vacuna (celdas J) - Seccion A
$ipv_total     = $ipv_d1   + $ipv_d2   + $ipv_d3;
$penta_total   = $penta_d1 + $penta_d2 + $penta_d3;
$rota_total    = $rota_d1  + $rota_d2;
$neumo_total   = $neumo_d1 + $neumo_d2;
$inf_total     = $inf_d1   + $inf_d2;

// -----------------------------------------------------------------------------
// 4.2B Casos por linea (Seccion B - MENORES DE 01 ANIO)
// -----------------------------------------------------------------------------
// Nota: en la BD la etiqueta de INFLUENZA es
// '1A 11M 29D - DOSIS UNICA - INFLUENZA' (sin sufijo '- DOSIS UNICA' final).
$b_neumo_3ra    = esniGetCasos($casosPorEtiquetaB, '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS');
$b_spr_1ra      = esniGetCasos($casosPorEtiquetaB, '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS');
$b_inf_du       = esniGetCasos($casosPorEtiquetaB, '1A 11M 29D - DOSIS UNICA - INFLUENZA');
$b_varicela_1ra = esniGetCasos($casosPorEtiquetaB, 'VARICELA 1RA');
$b_amarilica_du = esniGetCasos($casosPorEtiquetaB, '15 MESES - ANTIAMARILICA - DOSIS UNICA');
$b_hepA_du      = esniGetCasos($casosPorEtiquetaB, '15 MESES - HEPATITIS A - DOSIS UNICA');
$b_spr_2da      = esniGetCasos($casosPorEtiquetaB, '18 MESES - SPR - 2DA DOSIS');
$b_dpt_1ra      = esniGetCasos($casosPorEtiquetaB, '18 MESES - REF. DPT - 1RA DOSIS');
$b_ipv_ref      = esniGetCasos($casosPorEtiquetaB, '18 MESES - REF. IPV');
$b_penta_ref    = esniGetCasos($casosPorEtiquetaB, '18 MESES - REF. PENTAVALENTE');
$b_nv_ipv       = esniGetCasos($casosPorEtiquetaB, 'No vacunado IPV');
$b_nv_penta_2da = esniGetCasos($casosPorEtiquetaB, 'No vacunado PENTAVALENTE 2da');
$b_nv_penta_3ra = esniGetCasos($casosPorEtiquetaB, 'No vacunado PENTAVALENTE 3ra');

// 4.3B Totales Seccion B:
//   - Lineas con dosis unica o dosis simple: el total (J) es igual a la unica
//     columna de Casos usada (G, H o I segun la celda destino indicada).
//   - Linea 41 (No vacunado PENTAVALENTE 2da/3ra): J41 = H41 + I41.
$b_penta_nv_total = $b_nv_penta_2da + $b_nv_penta_3ra;

//-----------------------------------------------------------------------------
// 4.2C Casos por linea (Seccion C - MAYORES DE 01 ANIO)
//-----------------------------------------------------------------------------
$c_inf_comorb_d1  = esniGetCasos($casosPorEtiquetaC, 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS');
$c_inf_sincom_d1  = esniGetCasos($casosPorEtiquetaC, 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS');
$c_neumo_com_d1   = esniGetCasos($casosPorEtiquetaC, 'NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS');
$c_vno_neumo_d1   = esniGetCasos($casosPorEtiquetaC, 'VACUNACION NO OPORTUNA - NEUMOCOCO D1');
$c_vno_neumo_d2   = esniGetCasos($casosPorEtiquetaC, 'VACUNACION NO OPORTUNA - NEUMOCOCO D2');
$c_vno_neumo_d3   = esniGetCasos($casosPorEtiquetaC, 'VACUNACION NO OPORTUNA - NEUMOCOCO D3');
$c_amarilica_d1   = esniGetCasos($casosPorEtiquetaC, 'ANTIAMARILICA - 1RA DOSIS');
$c_vno_spr_d1     = esniGetCasos($casosPorEtiquetaC, 'VACUNACION NO OPORTUNA - SPR - 1RA DOSIS');
$c_vno_spr_d2     = esniGetCasos($casosPorEtiquetaC, 'VACUNACION NO OPORTUNA - SPR - 2DA DOSIS');
$c_ref_ipv_d1     = esniGetCasos($casosPorEtiquetaC, 'REFUERZO ANTIPOLIO IPV- 1RA DOSIS');
$c_ref_penta_d1   = esniGetCasos($casosPorEtiquetaC, 'REFUERZO PENTAVALENTE - 1RA DOSIS');

// 4.3C Totales Seccion C (celdas que son suma de otras)
$c_vno_neumo_total = $c_vno_neumo_d1 + $c_vno_neumo_d2 + $c_vno_neumo_d3;
$c_vno_spr_total   = $c_vno_spr_d1 + $c_vno_spr_d2;

//-----------------------------------------------------------------------------
// 4.2D Casos por linea (Seccion D - DE 03 ANIOS)
//-----------------------------------------------------------------------------
$d_inf_comorb_d1     = esniGetCasos($casosPorEtiquetaD, 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS');
$d_inf_sincom_d1     = esniGetCasos($casosPorEtiquetaD, 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS');
$d_neumo_com         = esniGetCasos($casosPorEtiquetaD, 'Neumococo con Comorbilidad');
$d_amarilica         = esniGetCasos($casosPorEtiquetaD, 'ANTIAMARILICA');
$d_penta_nv_d1       = esniGetCasos($casosPorEtiquetaD, 'Pentavalente No vacunado D1');
$d_penta_nv_d2       = esniGetCasos($casosPorEtiquetaD, 'Pentavalente No vacunado D2');
$d_penta_nv_d3       = esniGetCasos($casosPorEtiquetaD, 'Pentavalente No vacunado D3');
$d_spr_1ra           = esniGetCasos($casosPorEtiquetaD, 'SPR 1RA Dosis');
$d_spr_2da           = esniGetCasos($casosPorEtiquetaD, 'SPR 2DA Dosis');
$d_ref_dpt           = esniGetCasos($casosPorEtiquetaD, 'Refuerzo DPT');
$d_ref_ipv           = esniGetCasos($casosPorEtiquetaD, 'Refuerzo Antipolio IPV');
$d_ref_penta         = esniGetCasos($casosPorEtiquetaD, 'REFUERZO PENTAVALENTE');

// 4.3D Totales Seccion D (celdas que son suma de otras)
$d_penta_nv_total    = $d_penta_nv_d1 + $d_penta_nv_d2 + $d_penta_nv_d3;
$d_spr_total         = $d_spr_1ra + $d_spr_2da;

//-----------------------------------------------------------------------------
// 4.2E1 Casos por linea (Seccion E1 - GRUPO ESPECIAL / RIESGO)
//-----------------------------------------------------------------------------
// Etiquetas tomadas literalmente de la configuracion ESNI_LINEA_REPORTE para
// la seccion con codigo 'E1'. Alimentan las celdas F/G/H/I de las filas
// 71-86 de la plantilla oficial.
$e1_inf_comorb_d1   = esniGetCasos($casosPorEtiquetaE1, 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS');
$e1_inf_sincom_d1   = esniGetCasos($casosPorEtiquetaE1, 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS');
$e1_neumo_com       = esniGetCasos($casosPorEtiquetaE1, 'Neumococo con Comorbilidad');
$e1_amarilica       = esniGetCasos($casosPorEtiquetaE1, 'ANTIAMARILICA');
$e1_penta_nv_d1     = esniGetCasos($casosPorEtiquetaE1, 'Pentavalente D1 -No vacunado');
$e1_penta_nv_d2     = esniGetCasos($casosPorEtiquetaE1, 'Pentavalente D2 -No vacunado');
$e1_penta_nv_d3     = esniGetCasos($casosPorEtiquetaE1, 'Pentavalente D3 -No vacunado');
$e1_spr_1ra         = esniGetCasos($casosPorEtiquetaE1, 'SPR 1RA Dosis');
$e1_spr_2da         = esniGetCasos($casosPorEtiquetaE1, 'SPR 2DA Dosis');
$e1_ref_ipv         = esniGetCasos($casosPorEtiquetaE1, 'REFUERZO ANTIPOLIO(IPV)');
$e1_ref_dpt         = esniGetCasos($casosPorEtiquetaE1, 'REFUERZO DPT');
$e1_ref_apo         = esniGetCasos($casosPorEtiquetaE1, 'REFUERZO ANTIPOLIO(APO)');

// 4.3E1 Totales Seccion E1 (celdas que son suma de otras)
$e1_penta_nv_total  = $e1_penta_nv_d1 + $e1_penta_nv_d2 + $e1_penta_nv_d3;
$e1_spr_total       = $e1_spr_1ra + $e1_spr_2da;

//-----------------------------------------------------------------------------
// 4.2E2 Casos por linea (Seccion E2 - GRUPO ESPECIAL / RIESGO)
//-----------------------------------------------------------------------------
// Etiquetas tomadas literalmente de la configuracion ESNI_LINEA_REPORTE para
// la seccion con codigo 'E2'. Alimentan las celdas J/K/L/M de las filas
// 71-86 de la plantilla oficial (paralelo a E1 que usa F/G/H/I).
$e2_penta_nv_d1     = esniGetCasos($casosPorEtiquetaE2, 'Pentavalente D1 -No vacunado');
$e2_penta_nv_d2     = esniGetCasos($casosPorEtiquetaE2, 'Pentavalente D2 -No vacunado');
$e2_penta_nv_d3     = esniGetCasos($casosPorEtiquetaE2, 'Pentavalente D3 -No vacunado');
$e2_ref_dpt         = esniGetCasos($casosPorEtiquetaE2, 'REFUERZO DPT');

// 4.3E2 Totales Seccion E2 (celdas que son suma de otras)
// M78 = J78 + K78 + L78 (Pentavalente D1/D2/D3 -No vacunado)
$e2_penta_nv_total  = $e2_penta_nv_d1 + $e2_penta_nv_d2 + $e2_penta_nv_d3;

//-----------------------------------------------------------------------------
// 4.2F Casos por linea (Seccion F - dT ADULTO MUJERES EN EDAD FERTIL)
//-----------------------------------------------------------------------------
// Etiquetas tomadas literalmente de la configuracion ESNI_LINEA_REPORTE para
// la seccion con codigo 'F' (id_seccion = 4). Alimentan las celdas M/N/O/P
// de las filas 8-14 de la plantilla oficial. Las celdas P (columna Total)
// se calculan como M + N + O (suma de las 3 dosis dT del grupo de edad).
// --- 5 a 9 anios (fila 8) ---
$f_dt5a9_d1    = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 5 a 9 anos');
$f_dt5a9_d2    = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 5 a 9 anos');
$f_dt5a9_d3    = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 5 a 9 anos');
// --- 10 a 11 anios (fila 9) ---
$f_dt10a11_d1  = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 10 a 11 anos');
$f_dt10a11_d2  = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 10 a 11 anos');
$f_dt10a11_d3  = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 10 a 11 anos');
// --- 12 a 17 anios (fila 10) ---
$f_dt12a17_d1  = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 12 a 17 anos');
$f_dt12a17_d2  = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 12 a 17 anos');
$f_dt12a17_d3  = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 12 a 17 anos');
// --- 18 a 29 anios (fila 11) ---
$f_dt18a29_d1  = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 18 a 29 anos');
$f_dt18a29_d2  = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 18 a 29 anos');
$f_dt18a29_d3  = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 18 a 29 anos');
// --- 30 a 49 anios (fila 12) ---
$f_dt30a49_d1  = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 30 a 49 anos');
$f_dt30a49_d2  = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 30 a 49 anos');
$f_dt30a49_d3  = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 30 a 49 anos');
// --- 50 a 59 anios (fila 13) ---
$f_dt50a59_d1  = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 50 a 59 anos');
$f_dt50a59_d2  = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 50 a 59 anos');
$f_dt50a59_d3  = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 50 a 59 anos');
// --- 60 anios a mas (fila 14) ---
$f_dt60mas_d1  = esniGetCasos($casosPorEtiquetaF, 'dT 1ra - Mujeres 60 a mas anos');
$f_dt60mas_d2  = esniGetCasos($casosPorEtiquetaF, 'dT 2da - Mujeres 60 a mas anos');
$f_dt60mas_d3  = esniGetCasos($casosPorEtiquetaF, 'dT 3ra - Mujeres 60 a mas anos');

// 4.3F Totales Seccion F (celda P = M + N + O por grupo de edad)
$f_dt5a9_total    = $f_dt5a9_d1   + $f_dt5a9_d2   + $f_dt5a9_d3;
$f_dt10a11_total  = $f_dt10a11_d1 + $f_dt10a11_d2 + $f_dt10a11_d3;
$f_dt12a17_total  = $f_dt12a17_d1 + $f_dt12a17_d2 + $f_dt12a17_d3;
$f_dt18a29_total  = $f_dt18a29_d1 + $f_dt18a29_d2 + $f_dt18a29_d3;
$f_dt30a49_total  = $f_dt30a49_d1 + $f_dt30a49_d2 + $f_dt30a49_d3;
$f_dt50a59_total  = $f_dt50a59_d1 + $f_dt50a59_d2 + $f_dt50a59_d3;
$f_dt60mas_total  = $f_dt60mas_d1 + $f_dt60mas_d2 + $f_dt60mas_d3;

//-----------------------------------------------------------------------------
// 4.2F2 Casos por linea (Seccion F2 - dT por grupos de edad 10-59 anos)
//-----------------------------------------------------------------------------
// Etiquetas tomadas literalmente de la configuracion ESNI_LINEA_REPORTE para
// la seccion con codigo 'F2' (id_seccion = 5). Alimentan las celdas M/N/O/P
// de las filas 19-23 de la plantilla oficial. Las celdas P (columna Total)
// se calculan como M + N + O (suma de las 3 dosis dT del grupo de edad).
// --- 10 y 11 anios (fila 19) ---
$f2_dt10a11_d1   = esniGetCasos($casosPorEtiquetaF2, 'dT 1ra -10_11A');
$f2_dt10a11_d2   = esniGetCasos($casosPorEtiquetaF2, 'dT 2da -10_11A');
$f2_dt10a11_d3   = esniGetCasos($casosPorEtiquetaF2, 'dT 3ra -10_11A');
// --- 12 y 17 anios (fila 20) ---
$f2_dt12a17_d1   = esniGetCasos($casosPorEtiquetaF2, 'dT 1ra -12_17A');
$f2_dt12a17_d2   = esniGetCasos($casosPorEtiquetaF2, 'dT 2da -12_17A');
$f2_dt12a17_d3   = esniGetCasos($casosPorEtiquetaF2, 'dT 3ra -12_17A');
// --- 18 y 29 anios (fila 21) ---
$f2_dt18a29_d1   = esniGetCasos($casosPorEtiquetaF2, 'dT 1ra -18_29A');
$f2_dt18a29_d2   = esniGetCasos($casosPorEtiquetaF2, 'dT 2da -18_29A');
$f2_dt18a29_d3   = esniGetCasos($casosPorEtiquetaF2, 'dT 3ra -18_29A');
// --- 30 y 49 anios (fila 22) ---
$f2_dt30a49_d1   = esniGetCasos($casosPorEtiquetaF2, 'dT 1ra -30_49A');
$f2_dt30a49_d2   = esniGetCasos($casosPorEtiquetaF2, 'dT 2da -30_49A');
$f2_dt30a49_d3   = esniGetCasos($casosPorEtiquetaF2, 'dT 3ra -30_49A');
// --- 50 y 59 anios (fila 23) ---
$f2_dt50a59_d1   = esniGetCasos($casosPorEtiquetaF2, 'dT 1ra -50_59A');
$f2_dt50a59_d2   = esniGetCasos($casosPorEtiquetaF2, 'dT 2da -50_59A');
$f2_dt50a59_d3   = esniGetCasos($casosPorEtiquetaF2, 'dT 3ra -50_59A');

// 4.3F2 Totales Seccion F2 (celda P = M + N + O por grupo de edad)
$f2_dt10a11_total = $f2_dt10a11_d1 + $f2_dt10a11_d2 + $f2_dt10a11_d3;
$f2_dt12a17_total = $f2_dt12a17_d1 + $f2_dt12a17_d2 + $f2_dt12a17_d3;
$f2_dt18a29_total = $f2_dt18a29_d1 + $f2_dt18a29_d2 + $f2_dt18a29_d3;
$f2_dt30a49_total = $f2_dt30a49_d1 + $f2_dt30a49_d2 + $f2_dt30a49_d3;
$f2_dt50a59_total = $f2_dt50a59_d1 + $f2_dt50a59_d2 + $f2_dt50a59_d3;

//-----------------------------------------------------------------------------
// 4.2G Casos por linea (Seccion G - dT ADULTO EN VARONES EN RIESGO)
//-----------------------------------------------------------------------------
// Etiquetas tomadas literalmente de la configuracion ESNI_LINEA_REPORTE para
// la seccion con codigo 'G' (id_seccion = 6). Alimentan las celdas M/N/O/P
// de las filas 28-33 de la plantilla oficial. Las celdas P (columna Total)
// se calculan como M + N + O (suma de las 3 dosis dT del grupo de edad).
// --- 05 y 09 anios (fila 28) ---
$g_dt5a9_d1    = esniGetCasos($casosPorEtiquetaG, 'dT 1ra -05_09A');
$g_dt5a9_d2    = esniGetCasos($casosPorEtiquetaG, 'dT 2da -05_09A');
$g_dt5a9_d3    = esniGetCasos($casosPorEtiquetaG, 'dT 3ra -05_09A');
// --- 10 y 11 anios (fila 29) ---
$g_dt10a11_d1  = esniGetCasos($casosPorEtiquetaG, 'dT 1ra -10_11A');
$g_dt10a11_d2  = esniGetCasos($casosPorEtiquetaG, 'dT 2da -10_11A');
$g_dt10a11_d3  = esniGetCasos($casosPorEtiquetaG, 'dT 3ra -10_11A');
// --- 12 y 17 anios (fila 30) ---
$g_dt12a17_d1  = esniGetCasos($casosPorEtiquetaG, 'dT 1ra -12_17A');
$g_dt12a17_d2  = esniGetCasos($casosPorEtiquetaG, 'dT 2da -12_17A');
$g_dt12a17_d3  = esniGetCasos($casosPorEtiquetaG, 'dT 3ra -12_17A');
// --- 18 y 29 anios (fila 31) ---
$g_dt18a29_d1  = esniGetCasos($casosPorEtiquetaG, 'dT 1ra -18_29A');
$g_dt18a29_d2  = esniGetCasos($casosPorEtiquetaG, 'dT 2da -18_29A');
$g_dt18a29_d3  = esniGetCasos($casosPorEtiquetaG, 'dT 3ra -18_29A');
// --- 30 y 59 anios (fila 32) ---
$g_dt30a59_d1  = esniGetCasos($casosPorEtiquetaG, 'dT 1ra -30_59A');
$g_dt30a59_d2  = esniGetCasos($casosPorEtiquetaG, 'dT 2da -30_59A');
$g_dt30a59_d3  = esniGetCasos($casosPorEtiquetaG, 'dT 3ra -30_59A');
// --- 60 anios a mas (fila 33) ---
$g_dt60mas_d1  = esniGetCasos($casosPorEtiquetaG, 'dT 1ra -60A_MAS');
$g_dt60mas_d2  = esniGetCasos($casosPorEtiquetaG, 'dT 2da -60A_MAS');
$g_dt60mas_d3  = esniGetCasos($casosPorEtiquetaG, 'dT 3ra -60A_MAS');

// 4.3G Totales Seccion G (celda P = M + N + O por grupo de edad)
$g_dt5a9_total    = $g_dt5a9_d1   + $g_dt5a9_d2   + $g_dt5a9_d3;
$g_dt10a11_total  = $g_dt10a11_d1 + $g_dt10a11_d2 + $g_dt10a11_d3;
$g_dt12a17_total  = $g_dt12a17_d1 + $g_dt12a17_d2 + $g_dt12a17_d3;
$g_dt18a29_total  = $g_dt18a29_d1 + $g_dt18a29_d2 + $g_dt18a29_d3;
$g_dt30a59_total  = $g_dt30a59_d1 + $g_dt30a59_d2 + $g_dt30a59_d3;
$g_dt60mas_total  = $g_dt60mas_d1 + $g_dt60mas_d2 + $g_dt60mas_d3;

// 4.4 Construir el mapa final celda => valor
$cellValues = [
    // Encabezado (texto)
    'C2' => $nombreEst,
    'H2' => $nombreMes,
    'L2' => $nombreAnio,

    // BCG (dosis unica -> G = J = Casos)
    'G7'  => $bcg_24h,    'J7'  => $bcg_24h,
    'G8'  => $bcg_28d,    'J8'  => $bcg_28d,
    'G9'  => $bcg_01_11m, 'J9'  => $bcg_01_11m,

    // Hepatitis Viral B (dosis unica -> G = J = Casos)
    'G10' => $vhb_12h,    'J10' => $vhb_12h,
    'G11' => $vhb_24h,    'J11' => $vhb_24h,

    // Antipolio IPV (3 dosis + total)
    'G13' => $ipv_d1, 'H13' => $ipv_d2, 'I13' => $ipv_d3, 'J13' => $ipv_total,

    // Pentavalente (3 dosis + total)
    'G15' => $penta_d1, 'H15' => $penta_d2, 'I15' => $penta_d3, 'J15' => $penta_total,

    // Rotavirus (2 dosis + total)
    'G20' => $rota_d1, 'H20' => $rota_d2, 'J20' => $rota_total,

    // Neumococo (2 dosis + total)
    'G21' => $neumo_d1, 'H21' => $neumo_d2, 'J21' => $neumo_total,

    // Influenza (2 dosis + total)
    'G22' => $inf_d1, 'H22' => $inf_d2, 'J22' => $inf_total,

    // --- Seccion B: MENORES DE 01 ANIO (celdas I28..J41) ---
    // NEUMOCOCO 3ra dosis (celda I=Casos, total J=I)
    'I28' => $b_neumo_3ra,    'J28' => $b_neumo_3ra,
    // SPR 1ra dosis (celda G=Casos, total J=G)
    'G29' => $b_spr_1ra,      'J29' => $b_spr_1ra,
    // INFLUENZA dosis unica (celda G=Casos, total J=G)
    'G30' => $b_inf_du,       'J30' => $b_inf_du,
    // VARICELA 1ra (celda G=Casos, total J=G)
    'G31' => $b_varicela_1ra, 'J31' => $b_varicela_1ra,
    // ANTIAMARILICA dosis unica (celda G=Casos, total J=G)
    'G33' => $b_amarilica_du, 'J33' => $b_amarilica_du,
    // HEPATITIS A dosis unica (celda G=Casos, total J=G)
    'G34' => $b_hepA_du,      'J34' => $b_hepA_du,
    // SPR 2da dosis (celda H=Casos, total J=H)
    'H35' => $b_spr_2da,      'J35' => $b_spr_2da,
    // REF. DPT 1ra (celda G=Casos, total J=G)
    'G36' => $b_dpt_1ra,      'J36' => $b_dpt_1ra,
    // REF. IPV (celda G=Casos, total J=G)
    'G37' => $b_ipv_ref,      'J37' => $b_ipv_ref,
    // REF. PENTAVALENTE (celda G=Casos, total J=G)
    'G38' => $b_penta_ref,    'J38' => $b_penta_ref,
    // No vacunado IPV (celda I=Casos, total J=I)
    'I39' => $b_nv_ipv,       'J39' => $b_nv_ipv,
    // No vacunado PENTAVALENTE 2da/3ra (celdas H e I) y total J = H + I
    'H41' => $b_nv_penta_2da,
    'I41' => $b_nv_penta_3ra,
    'J41' => $b_penta_nv_total,

    // --- Seccion C: MAYORES DE 01 ANIO (celdas F/I en filas 50-66) ---
    // INFLUENZA CON COMORBILIDAD - 1RA DOSIS (F=I=Casos)
    'F50' => $c_inf_comorb_d1,    'I50' => $c_inf_comorb_d1,
    // INFLUENZA SIN COMORBILIDAD - 1RA DOSIS (F=I=Casos)
    'F51' => $c_inf_sincom_d1,    'I51' => $c_inf_sincom_d1,
    // NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS (F=I=Casos)
    'F52' => $c_neumo_com_d1,     'I52' => $c_neumo_com_d1,
    // VACUNACION NO OPORTUNA - NEUMOCOCO D1/D2/D3 y total I = F+G+H
    'F53' => $c_vno_neumo_d1,
    'G53' => $c_vno_neumo_d2,
    'H53' => $c_vno_neumo_d3,
    'I53' => $c_vno_neumo_total,
    // ANTIAMARILICA - 1RA DOSIS (F=I=Casos)
    'F55' => $c_amarilica_d1,     'I55' => $c_amarilica_d1,
    // VACUNACION NO OPORTUNA - SPR 1ra/2da dosis y total I = F+G
    'F62' => $c_vno_spr_d1,
    'G62' => $c_vno_spr_d2,
    'I62' => $c_vno_spr_total,
    // REFUERZO ANTIPOLIO IPV - 1RA DOSIS (F=I=Casos)
    'F65' => $c_ref_ipv_d1,       'I65' => $c_ref_ipv_d1,
    // REFUERZO PENTAVALENTE - 1RA DOSIS (F=I=Casos)
    'F66' => $c_ref_penta_d1,     'I66' => $c_ref_penta_d1,

    // --- Seccion D: DE 03 ANIOS (celdas J/K/L/M en filas 50-66) ---
    // INFLUENZA CON COMORBILIDAD - 1RA DOSIS (J=M=Casos)
    'J50' => $d_inf_comorb_d1,    'M50' => $d_inf_comorb_d1,
    // INFLUENZA SIN COMORBILIDAD - 1RA DOSIS (J=M=Casos)
    'J51' => $d_inf_sincom_d1,    'M51' => $d_inf_sincom_d1,
    // NEUMOCOCO CON COMORBILIDAD (J=M=Casos)
    'J52' => $d_neumo_com,        'M52' => $d_neumo_com,
    // ANTIAMARILICA (J=M=Casos)
    'J55' => $d_amarilica,        'M55' => $d_amarilica,
    // Pentavalente No vacunado D1/D2/D3 y total M = J+K+L
    'J58' => $d_penta_nv_d1,
    'K58' => $d_penta_nv_d2,
    'L58' => $d_penta_nv_d3,
    'M58' => $d_penta_nv_total,
    // SPR 1ra/2da Dosis y total M = J+K
    'J62' => $d_spr_1ra,
    'K62' => $d_spr_2da,
    'M62' => $d_spr_total,
    // REFUERZO DPT (J=M=Casos)
    'J64' => $d_ref_dpt,          'M64' => $d_ref_dpt,
    // REFUERZO ANTIPOLIO IPV (J=M=Casos)
    'J65' => $d_ref_ipv,          'M65' => $d_ref_ipv,
    // REFUERZO PENTAVALENTE (J=M=Casos)
    'J66' => $d_ref_penta,        'M66' => $d_ref_penta,

    // --- Seccion E1: GRUPO ESPECIAL / RIESGO (celdas F/G/H/I en filas 71-86) ---
    // INFLUENZA CON COMORBILIDAD - 1RA DOSIS (F=I=Casos)
    'F71' => $e1_inf_comorb_d1,    'I71' => $e1_inf_comorb_d1,
    // INFLUENZA SIN COMORBILIDAD - 1RA DOSIS (F=I=Casos)
    'F72' => $e1_inf_sincom_d1,    'I72' => $e1_inf_sincom_d1,
    // Neumococo con Comorbilidad (F=I=Casos)
    'F73' => $e1_neumo_com,        'I73' => $e1_neumo_com,
    // ANTIAMARILICA (F=I=Casos)
    'F75' => $e1_amarilica,        'I75' => $e1_amarilica,
    // Pentavalente D1/D2/D3 -No vacunado y total I = F+G+H
    'F78' => $e1_penta_nv_d1,
    'G78' => $e1_penta_nv_d2,
    'H78' => $e1_penta_nv_d3,
    'I78' => $e1_penta_nv_total,
    // SPR 1ra/2da Dosis y total I = F+G
    'F82' => $e1_spr_1ra,
    'G82' => $e1_spr_2da,
    'I82' => $e1_spr_total,
    // REFUERZO ANTIPOLIO(IPV) (F=I=Casos)
    'F84' => $e1_ref_ipv,          'I84' => $e1_ref_ipv,
    // REFUERZO DPT (G=I=Casos)
    'G85' => $e1_ref_dpt,          'I85' => $e1_ref_dpt,
    // REFUERZO ANTIPOLIO(APO) (G=I=Casos)
    'G86' => $e1_ref_apo,          'I86' => $e1_ref_apo,

    // --- Seccion E2: GRUPO ESPECIAL / RIESGO (celdas J/K/L/M en filas 71-86) ---
    // Pentavalente D1/D2/D3 -No vacunado y total M = J+K+L
    'J78' => $e2_penta_nv_d1,
    'K78' => $e2_penta_nv_d2,
    'L78' => $e2_penta_nv_d3,
    'M78' => $e2_penta_nv_total,
    // REFUERZO DPT (K=M=Casos)
    'K85' => $e2_ref_dpt,          'M85' => $e2_ref_dpt,

    // --- Seccion F: dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS ---
    // (celdas M/N/O/P en filas 8-14, layout matriz_dosis: M=D1, N=D2, O=D3, P=Total)
    // 5 a 9 anios (fila 8)
    'M8'  => $f_dt5a9_d1,
    'N8'  => $f_dt5a9_d2,
    'O8'  => $f_dt5a9_d3,
    'P8'  => $f_dt5a9_total,
    // 10 a 11 anios (fila 9)
    'M9'  => $f_dt10a11_d1,
    'N9'  => $f_dt10a11_d2,
    'O9'  => $f_dt10a11_d3,
    'P9'  => $f_dt10a11_total,
    // 12 a 17 anios (fila 10)
    'M10' => $f_dt12a17_d1,
    'N10' => $f_dt12a17_d2,
    'O10' => $f_dt12a17_d3,
    'P10' => $f_dt12a17_total,
    // 18 a 29 anios (fila 11)
    'M11' => $f_dt18a29_d1,
    'N11' => $f_dt18a29_d2,
    'O11' => $f_dt18a29_d3,
    'P11' => $f_dt18a29_total,
    // 30 a 49 anios (fila 12)
    'M12' => $f_dt30a49_d1,
    'N12' => $f_dt30a49_d2,
    'O12' => $f_dt30a49_d3,
    'P12' => $f_dt30a49_total,
    // 50 a 59 anios (fila 13)
    'M13' => $f_dt50a59_d1,
    'N13' => $f_dt50a59_d2,
    'O13' => $f_dt50a59_d3,
    'P13' => $f_dt50a59_total,
    // 60 anios a mas (fila 14)
    'M14' => $f_dt60mas_d1,
    'N14' => $f_dt60mas_d2,
    'O14' => $f_dt60mas_d3,
    'P14' => $f_dt60mas_total,

    // --- Seccion F2: dT (GRUPOS DE EDAD 10-11, 12-17, 18-29, 30-49, 50-59) ---
    // (celdas M/N/O/P en filas 19-23, layout matriz_dosis: M=D1, N=D2, O=D3, P=Total)
    // 10 y 11 anios (fila 19)
    'M19' => $f2_dt10a11_d1,
    'N19' => $f2_dt10a11_d2,
    'O19' => $f2_dt10a11_d3,
    'P19' => $f2_dt10a11_total,
    // 12 y 17 anios (fila 20)
    'M20' => $f2_dt12a17_d1,
    'N20' => $f2_dt12a17_d2,
    'O20' => $f2_dt12a17_d3,
    'P20' => $f2_dt12a17_total,
    // 18 y 29 anios (fila 21)
    'M21' => $f2_dt18a29_d1,
    'N21' => $f2_dt18a29_d2,
    'O21' => $f2_dt18a29_d3,
    'P21' => $f2_dt18a29_total,
    // 30 y 49 anios (fila 22)
    'M22' => $f2_dt30a49_d1,
    'N22' => $f2_dt30a49_d2,
    'O22' => $f2_dt30a49_d3,
    'P22' => $f2_dt30a49_total,
    // 50 y 59 anios (fila 23)
    'M23' => $f2_dt50a59_d1,
    'N23' => $f2_dt50a59_d2,
    'O23' => $f2_dt50a59_d3,
    'P23' => $f2_dt50a59_total,

    // --- Seccion G: dT ADULTO EN VARONES EN RIESGO (celdas M/N/O/P filas 28-33) ---
    // (layout matriz_dosis: M=D1, N=D2, O=D3, P=Total)
    // 05 y 09 anios (fila 28)
    'M28' => $g_dt5a9_d1,
    'N28' => $g_dt5a9_d2,
    'O28' => $g_dt5a9_d3,
    'P28' => $g_dt5a9_total,
    // 10 y 11 anios (fila 29)
    'M29' => $g_dt10a11_d1,
    'N29' => $g_dt10a11_d2,
    'O29' => $g_dt10a11_d3,
    'P29' => $g_dt10a11_total,
    // 12 y 17 anios (fila 30)
    'M30' => $g_dt12a17_d1,
    'N30' => $g_dt12a17_d2,
    'O30' => $g_dt12a17_d3,
    'P30' => $g_dt12a17_total,
    // 18 y 29 anios (fila 31)
    'M31' => $g_dt18a29_d1,
    'N31' => $g_dt18a29_d2,
    'O31' => $g_dt18a29_d3,
    'P31' => $g_dt18a29_total,
    // 30 y 59 anios (fila 32)
    'M32' => $g_dt30a59_d1,
    'N32' => $g_dt30a59_d2,
    'O32' => $g_dt30a59_d3,
    'P32' => $g_dt30a59_total,
    // 60 anios a mas (fila 33)
    'M33' => $g_dt60mas_d1,
    'N33' => $g_dt60mas_d2,
    'O33' => $g_dt60mas_d3,
    'P33' => $g_dt60mas_total,
];

// ============================================================================
// 5. Llenar la plantilla y descargar
// ============================================================================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cellValues);

$nombreArchivo = 'Reporte_ESNI_Plantilla_'
               . ($filtros['anio'] !== '' ? $filtros['anio'] : 'all')
               . ($filtros['mes'] !== '' ? '_' . str_pad($filtros['mes'], 2, '0', STR_PAD_LEFT) : '')
               . '_' . date('Ymd_His')
               . '.xlsx';

try {
    $filler->download($nombreArchivo);
} catch (Throwable $e) {
    http_response_code(500);
    htmlError(
        'Error al generar el Excel',
        'No se pudo generar el archivo Excel.<br><br>' .
        '<b>Error tecnico:</b><br>' .
        '<pre style="background:#f8f9fa;padding:.6rem;border-radius:.25rem;overflow:auto;">' .
        htmlspecialchars($e->getMessage()) . '</pre><br>' .
        '<b>Posibles causas:</b><br>' .
        '<ul>' .
        '<li>El directorio <code>uploads/tmp/</code> no existe o no es escribible. Cree la carpeta con permisos 0755 (o 0777).</li>' .
        '<li>La plantilla <code>uploads/Plantilla.xlsx</code> esta corrupta o no es un .xlsx valido.</li>' .
        '<li>El hosting tiene funciones restringidas (tempnam, copy, etc.). Ejecute <a href="esni_diagnostic.php">esni_diagnostic.php</a> para ver el detalle.</li>' .
        '</ul>'
    );
}
exit;

/**
 * Muestra un error HTML amigable y termina la ejecucion.
 *
 * @param string $titulo  Titulo corto del error.
 * @param string $mensaje Mensaje HTML con detalles y soluciones.
 */
function htmlError(string $titulo, string $mensaje): void
{
    // Asegurar que no haya salida previa que rompa el HTML
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<title>Error - ' . htmlspecialchars($titulo) . '</title>'
       . '<style>'
       . 'body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:20px;color:#333;}'
       . '.container{max-width:720px;margin:40px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);}'
       . 'h1{color:#dc3545;margin:0 0 16px 0;font-size:1.5rem;}'
       . '.icon{font-size:48px;color:#dc3545;margin-bottom:16px;}'
       . 'pre{font-family:Consolas,monospace;font-size:.85rem;}'
       . 'a{color:#0d6efd;}'
       . '</style></head><body>'
       . '<div class="container">'
       . '<div class="icon">&#9888;</div>'
       . '<h1>' . htmlspecialchars($titulo) . '</h1>'
       . '<div style="line-height:1.6;">' . $mensaje . '</div>'
       . '<hr style="margin:24px 0;border:none;border-top:1px solid #eee;">'
       . '<p style="font-size:.85rem;color:#6c757d;margin:0;">'
       . 'Sistema de Gestion de Datos HIS - Modulo ESNI</p>'
       . '</div></body></html>';
    exit;
}
