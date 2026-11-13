<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Reporte Operacional ADULTO (Reporte de Actividades Mensuales de
 * la Etapa de Vida Adulto - Direccion de Promocion de la Salud)
 *
 * REEMPLAZA EL FLUJO MANUAL del Modulo de Adulto:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales" (DimAdulto*)
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *                  (TRAMA_BASE_ADULTO_RPT_*)
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos" (21 SP)
 *   4) Excel:      abrir "Reporte_Actividades_Adulto.xlsx" y refrescar
 *                  la conexion ODBC
 *
 * FLUJO NUEVO (1 click):
 *   Reportes Operacionales -> Adulto -> [Generar Reporte]
 *   (Opcional) [Exportar Excel] -> llena la plantilla oficial
 *   "uploads/Reporte_Actividades_Adulto.xlsx" con los mismos datos
 *   (adulto_export.php).
 *   (Opcional) [Exportar Plano] -> deposita los mismos datos en el plano
 *   consolidado "uploads/Adulto_Plano.xlsx" (adulto_export_plano.php):
 *   cada dato va a la columna indicada por el numero de destino de
 *   "Ubicacion_Filas_Adulto.xlsx" (ej: espejo D14=4 con dato en D14 ->
 *   plano D13; fila de datos 13, D13:LG13).
 *
 * El motor de reglas (includes/adulto_data.php) adapta los 21
 * procedimientos usp_TRAMA_BASE_ADULTO_RPT_* del archivo "03 Creacion de
 * Procedimientos" y los ejecuta contra la tabla consolidada MySQL. El
 * layout replica el Excel "Reporte_Actividades_Adulto.xlsx" (17 bloques:
 * morbilidad, plan de atencion integral, evaluacion antropometrica, riesgo
 * nutricional, danos no transmisibles, planificacion familiar, prevencion
 * de cancer, atencion en salud bucal, salud mental, TBC, salud sexual y
 * reproductiva, ITS-VIH, salud ocular, actividades preventivo
 * promocionales, inmunizaciones, sesiones y otras consejerias).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/adulto_data.php';
require_once 'includes/adulto_render.php';

// ====== PROTECCION ANTI HTTP 500 (errores fatales de PHP) ======
// La generacion del reporte es pesada: en hostings compartidos puede agotar
// el memory_limit o el max_execution_time. Esos errores FATALES no se
// capturan con try/catch; sin este handler el navegador muestra "HTTP ERROR 500".
// Si la peticion es AJAX (asistente por trozos), se responde JSON para que el
// asistente pueda reintentar/subdividir en lugar de romper la pagina.
ob_start();
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    while (ob_get_level() > 0) { @ob_end_clean(); }
    if (!headers_sent()) {
        http_response_code(200);
    }
    $msg     = $e['message'];
    $esMem   = (stripos($msg, 'memory') !== false);
    $esTiempo= (stripos($msg, 'execution time') !== false || stripos($msg, 'time limit') !== false);
    $causa   = $esMem ? 'Se agotó la memoria disponible de PHP (memory_limit) al procesar los datos del reporte.'
              : ($esTiempo ? 'Se agotó el tiempo de ejecución permitido por el hosting (max_execution_time).' : 'Ocurrió un error fatal de PHP durante la generación del reporte.');
    if (($_GET['ajax'] ?? '') !== '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => false,
            'error'   => 'fatal',
            'causa'   => $esMem ? 'memoria' : ($esTiempo ? 'tiempo' : 'fatal'),
            'detalle' => 'Se agotó la memoria (memory_limit) o el tiempo (max_execution_time) del servidor en este paso. ' . substr($msg, 0, 180),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    $ml      = (string)ini_get('memory_limit');
    $met     = (string)ini_get('max_execution_time');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Reporte Adulto - Error</title>'
       . '<style>body{font-family:system-ui,sans-serif;background:#f4f6f7;padding:2rem}.card{max-width:720px;margin:auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:1.5rem}h1{color:#c0392b;font-size:1.3rem}code{background:#eef1f2;padding:2px 6px;border-radius:4px}</style>'
       . '</head><body><div class="card">'
       . '<h1>⚠ No se pudo generar el reporte ADULTO</h1>'
       . '<p>' . htmlspecialchars($causa) . '</p>'
       . '<ol><li>Ejecute una vez <strong>install_adulto.php</strong> (índices de la tabla consolidada) si aún no lo hizo.</li>'
       . '<li>Genere el reporte con filtros más acotados: seleccione <strong>un mes</strong> y/o <strong>un establecimiento</strong> en lugar de "Todos".</li>'
       . '<li>Si el problema persiste, el hosting gratuito (InfinityFree) tiene límites fijos; considere migrar a un plan de pago.</li></ol>'
       . '<p class="small text-muted mb-0">Límites actuales de PHP en este servidor &mdash; memory_limit: <code>' . htmlspecialchars($ml) . '</code> &nbsp;|&nbsp; max_execution_time: <code>' . htmlspecialchars($met) . 's</code> &nbsp;|&nbsp; PHP: <code>' . htmlspecialchars(PHP_VERSION) . '</code></p>'
       . '</div></body></html>';
});

$pdo = getDBConnection();

// ==================== AJAX: FILTROS DEPENDIENTES ====================
// Devuelve las opciones de Anio y Mes recalculadas DENTRO del ambito del
// establecimiento seleccionado (mismo mecanismo que los otros modulos).
if (($_GET['ajax'] ?? '') === 'filtros') {
    header('Content-Type: application/json; charset=utf-8');
    $ajaxAnio = trim($_GET['anio'] ?? '');
    $ajaxEst  = trim($_GET['establecimiento'] ?? '');
    try {
        $aniosAjax = array_map('strval', aduGetAniosDisponibles($pdo, $ajaxEst));
        $mesesAjax = array_map(function ($m) {
            return ['v' => (int)$m, 't' => getNombreMes((int)$m)];
        }, aduGetMesesDisponibles($pdo, $ajaxAnio, $ajaxEst));
        echo json_encode([
            'ok'    => true,
            'anios' => $aniosAjax,
            'meses' => $mesesAjax,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'anios' => [], 'meses' => []]);
    }
    exit; // respuesta JSON: no continuar con el HTML del reporte
}

/* ============================================================
 * AJAX: ASISTENTE DE GENERACION POR TROZOS (hosting 512M / 60s)
 * ------------------------------------------------------------
 * Reemplaza la consulta unica (que agota memoria/tiempo con filtros
 * amplios) por varios pasos pequenos acumulados en ADULTO_RPT_CACHE:
 *   ajax=plan      -> modo directo / trozos / cache + plan guardado
 *   ajax=trozo     -> ejecuta el trozo i y lo suma al acumulador
 *   ajax=dividir   -> auto-reparacion: subdivide el trozo en curso por EE.SS
 *   ajax=resultado -> URL con token para renderizar el reporte terminado
 * ============================================================ */

if (($_GET['ajax'] ?? '') === 'plan') {
    header('Content-Type: application/json; charset=utf-8');
    $fPlan = [
        'anio'            => trim($_GET['anio'] ?? ''),
        'mes'             => trim($_GET['mes'] ?? ''),
        'establecimiento' => trim($_GET['establecimiento'] ?? ''),
    ];
    try {
        if (aduAmbitoDirecto($fPlan)) {
            // rango angosto (anio+mes o anio+EE.SS): la consulta unica es segura
            echo json_encode(['ok' => true, 'modo' => 'directo']);
            exit;
        }
        if (!aduCacheCrearTablaSiFalta($pdo)) {
            echo json_encode(['ok' => false, 'error' => 'No se pudo preparar la tabla de generación ADULTO_RPT_CACHE en la base de datos.']);
            exit;
        }
        if (mt_rand(0, 9) === 0) aduCacheLimpiar($pdo); // limpieza oportunista ~10%

        $cacheKey = md5(json_encode($fPlan));
        $idUsu    = (int)($_SESSION['usuario_id'] ?? 0);

        // reutilizar una generacion identica terminada hace menos de 12 h
        $rowListo = aduCacheBuscarListo($pdo, $cacheKey, $idUsu);
        if ($rowListo) {
            $qs = $fPlan + ['generar' => 1, 'token' => $rowListo['token']];
            echo json_encode(['ok' => true, 'modo' => 'cache', 'token' => $rowListo['token'],
                              'url' => 'reporte_adulto.php?' . http_build_query($qs)]);
            exit;
        }

        $plan = aduPlanTrozos($pdo, $fPlan);
        if (empty($plan['trozos'])) {
            echo json_encode(['ok' => false, 'error' => 'No hay datos en la tabla consolidada para los filtros seleccionados.']);
            exit;
        }
        $token = aduCacheNuevo($pdo, $fPlan, $plan['trozos'], $idUsu, $cacheKey);
        if ($token === null) {
            echo json_encode(['ok' => false, 'error' => 'No se pudo crear el búfer de generación. Intente nuevamente.']);
            exit;
        }
        echo json_encode([
            'ok'             => true,
            'modo'           => 'trozos',
            'token'          => $token,
            'total'          => count($plan['trozos']),
            'filas_estimadas'=> $plan['filas_estimadas'],
            'con_conteo'     => $plan['conConteo'],
            'indices'        => $plan['indices'],
            'trozos'         => $plan['trozos'],
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Error al planificar la generación: ' . $e->getMessage()]);
    }
    exit;
}

if (($_GET['ajax'] ?? '') === 'trozo') {
    header('Content-Type: application/json; charset=utf-8');
    $token = trim($_GET['token'] ?? '');
    $i     = (int)($_GET['i'] ?? -1);
    $row   = aduCacheLeer($pdo, $token);
    if (!$row || (int)$row['id_usuario'] !== (int)($_SESSION['usuario_id'] ?? 0)) {
        echo json_encode(['ok' => false, 'error' => 'Buffer de generación no encontrado o sesión cambiada. Vuelva a pulsar Generar Reporte.']);
        exit;
    }
    $trozos = json_decode((string)$row['plan'], true);
    if (!is_array($trozos) || $i < 0 || $i >= count($trozos)) {
        echo json_encode(['ok' => false, 'error' => 'Trozo inválido (i=' . $i . ').']);
        exit;
    }
    $hechos = (int)$row['hechos'];
    $etq    = (string)($trozos[$i]['etiqueta'] ?? ('Trozo ' . ($i + 1)));
    if ($i < $hechos) {
        // reenvio/reintento de un trozo ya procesado: nada que hacer
        echo json_encode(['ok' => true, 'ya' => true, 'hechos' => $hechos, 'total' => (int)$row['total'],
                          'seg' => 0, 'filas' => 0, 'etiqueta' => $etq]);
        exit;
    }
    if ($i > $hechos) {
        echo json_encode(['ok' => false, 'error' => "Trozo fuera de orden: el servidor esperaba el trozo $hechos y llegó el $i. Reinicie la generación."]);
        exit;
    }
    try {
        $r = aduEjecutarReporte($pdo, aduFiltrosTrozo($trozos[$i]));
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Error SQL en el trozo: ' . $e->getMessage(), 'etiqueta' => $etq]);
        exit;
    }
    if (!empty($r['error'])) {
        echo json_encode(['ok' => false, 'error' => $r['error'], 'etiqueta' => $etq]);
        exit;
    }
    $acum  = ($row['payload'] !== null && $row['payload'] !== '') ? json_decode((string)$row['payload'], true) : null;
    if (!is_array($acum)) $acum = null;
    $acum  = aduMergeReporte($acum, $r);
    $hechosN = $i + 1;
    aduCacheGuardarAvance($pdo, $token, json_encode($acum, JSON_UNESCAPED_UNICODE), $hechosN, (int)$row['total']);
    echo json_encode(['ok' => true, 'hechos' => $hechosN, 'total' => (int)$row['total'],
                      'seg' => (float)$r['tiempo_ejecucion'], 'filas' => (int)$r['filas_leidas'],
                      'etiqueta' => $etq]);
    exit;
}

if (($_GET['ajax'] ?? '') === 'dividir') {
    header('Content-Type: application/json; charset=utf-8');
    $token = trim($_GET['token'] ?? '');
    $i     = (int)($_GET['i'] ?? -1);
    $row   = aduCacheLeer($pdo, $token);
    if (!$row || (int)$row['id_usuario'] !== (int)($_SESSION['usuario_id'] ?? 0)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Buffer de generación no encontrado. Vuelva a generar.']);
        exit;
    }
    echo json_encode(aduCacheDividirTrozo($pdo, $token, $i), JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_GET['ajax'] ?? '') === 'resultado') {
    header('Content-Type: application/json; charset=utf-8');
    $token = trim($_GET['token'] ?? '');
    $row   = aduCacheLeer($pdo, $token);
    if (!$row || (int)$row['id_usuario'] !== (int)($_SESSION['usuario_id'] ?? 0)) {
        echo json_encode(['ok' => false, 'error' => 'Buffer de generación no encontrado. Vuelva a generar.']);
        exit;
    }
    if ($row['estado'] !== 'listo') {
        echo json_encode(['ok' => false, 'error' => 'La generación aún no termina.', 'hechos' => (int)$row['hechos'], 'total' => (int)$row['total']]);
        exit;
    }
    $fRow  = json_decode((string)$row['filtros'], true);
    if (!is_array($fRow)) $fRow = [];
    $qs    = $fRow + ['generar' => 1, 'token' => $token];
    echo json_encode(['ok' => true, 'url' => 'reporte_adulto.php?' . http_build_query($qs)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==================== FILTROS ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fDetalle         = trim($_GET['detalle'] ?? '1'); // 1 = incluir filas en 0
$fVerSQL          = trim($_GET['versql'] ?? '0');  // 1 = mostrar panel de condiciones
$fGrupo           = trim($_GET['grupo'] ?? '');    // '' = todos los grupos

// Anios dentro del ambito del establecimiento (lista ZSPERENE con 'Todos')
$anios = aduGetAniosDisponibles($pdo, $fEstablecimiento);
if (empty($anios)) $anios = [date('Y')];
if ($fAnio === '' && !empty($anios)) $fAnio = $anios[0];

$establecimientos = aduGetEstablecimientosZS($pdo);
$nombreEstablecimiento = $establecimientos[$fEstablecimiento] ?? '';
$renaes = $fEstablecimiento !== '' ? $fEstablecimiento : '';

// Meses con datos dentro del mismo ambito (establecimiento + anio)
$mesesDisponibles = aduGetMesesDisponibles($pdo, $fAnio, $fEstablecimiento);
if (empty($mesesDisponibles)) $mesesDisponibles = range(1, 12);

// Ejecutar SOLO cuando el usuario pulse "Generar Reporte" (como en los
// otros modulos: evita saturar la base de datos con cada carga).
$ejecutar = isset($_GET['generar']);
$reporte = null;
$debugSQL = null;
$fToken  = trim($_GET['token'] ?? '');   // token del asistente por trozos
$ambitoAmplio = false;                   // true = el asistente JS debe hacerse cargo
$infoTrozos   = null;                    // info del reporte generado por trozos

if ($ejecutar && $fToken !== '') {
    // ---- Reporte YA generado por el asistente: cargar del buffer ----
    $rowT = aduCacheLeer($pdo, $fToken);
    if ($rowT
        && (int)$rowT['id_usuario'] === (int)($_SESSION['usuario_id'] ?? 0)
        && $rowT['estado'] === 'listo'
        && $rowT['payload'] !== null && $rowT['payload'] !== '') {
        $reporte = json_decode((string)$rowT['payload'], true) ?: null;
        if (is_array($reporte) && empty($reporte['error'])) {
            $infoTrozos = ['pasos' => max(1, (int)$rowT['total'])];
        } else {
            $reporte = null;
        }
    }
    // token caducado/invalido: cae al flujo normal (o al asistente si el ambito es amplio)
}

if ($ejecutar && $reporte === null) {
    $filtros = [
        'anio'            => $fAnio,
        'mes'             => $fMes,
        'establecimiento' => $fEstablecimiento,
    ];
    if (!aduAmbitoDirecto($filtros)) {
        // Rango amplio (varios meses de varios EE.SS): la consulta unica agota
        // memory_limit/max_execution_time en el hosting. NO ejecutarla aqui:
        // el asistente por trozos (JS) genera el reporte paso a paso; si el
        // navegador no tiene JS se muestra la advertencia para acotar filtros.
        $ambitoAmplio = true;
    } else {
        $t0 = microtime(true);
        $reporte = aduEjecutarReporte($pdo, $filtros);
        $reporte['tiempo_ejecucion'] = round(microtime(true) - $t0 + ($reporte['tiempo_ejecucion'] ?? 0), 2);
        if (!empty($reporte['error']) && !empty($reporte['sql_debug'])) {
            $debugSQL = ['sql' => $reporte['sql_debug'] ?? '', 'params' => $reporte['params_debug'] ?? []];
        }
    }
}

// Texto del periodo (como la cabecera del Excel: B5 PERIODO)
$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));
$periodoTxt = $periodoTxt ? implode(' - ', $periodoTxt) : 'TODOS LOS PERIODOS';

$gruposMeta = [
    'ATN' => ['nombre' => 'ATENCIÓN INTEGRAL DEL ADULTO',           'icon' => 'fa-user-md',        'desc' => 'Morbilidad y Plan de Atención Integral'],
    'NUT' => ['nombre' => 'ESTADO NUTRICIONAL DEL ADULTO',           'icon' => 'fa-weight-scale',   'desc' => 'IMC (antropometría) y Riesgo Nutricional (PAB)'],
    'DNT' => ['nombre' => 'ENFERMEDADES NO TRANSMISIBLES',           'icon' => 'fa-heart-pulse',    'desc' => 'Valoración DNT, casos HTA y Diabetes Mellitus'],
    'PF'  => ['nombre' => 'PLANIFICACIÓN FAMILIAR',                  'icon' => 'fa-people-arrows',  'desc' => 'Clasificación, 13 métodos (A/I) y AOE'],
    'ESP' => ['nombre' => 'CÁNCER, SALUD BUCAL, SALUD MENTAL Y TBC', 'icon' => 'fa-star-of-life',  'desc' => 'Prevención de cáncer, salud bucal, salud mental y TBC'],
    'SSR' => ['nombre' => 'SALUD SEXUAL Y REPRODUCTIVA / ITS-VIH',   'icon' => 'fa-baby',           'desc' => '13 prestaciones SSR e ITS-VIH'],
    'OCU' => ['nombre' => 'SALUD OCULAR Y ACTIVIDADES PREVENTIVAS',  'icon' => 'fa-eye',            'desc' => 'Salud ocular y actividades preventivo promocionales'],
    'INM' => ['nombre' => 'INMUNIZACIONES',                          'icon' => 'fa-syringe',        'desc' => 'dT, Hepatitis B e Influenza en adultos'],
    'COM' => ['nombre' => 'SESIONES Y OTRAS CONSEJERÍAS',            'icon' => 'fa-chalkboard-user','desc' => 'Sesiones (Nº / Part-Benef) y otras consejerías'],
];

$pageTitle = 'Reporte Operacional Adulto - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* Estilos del modulo ADULTO (mismo patron que los otros modulos), con la
   paleta verde-azulada (teal) de la estrategia Adulto del indice operativo */
.adu-section-card { border: 1px solid #dee2e6; border-radius: .5rem; margin-bottom: 1.2rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.adu-section-header { background: linear-gradient(90deg,#0f766e 0%,#14b8a6 100%); color:#fff; padding:.6rem 1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
.adu-section-header small { opacity:.85; font-weight:400; }
.adu-table { font-size:.72rem; margin:0; }
.adu-table thead th { background:#f1f3f5; border-bottom:2px solid #dee2e6; padding:.3rem .35rem; text-align:center; vertical-align:middle; font-weight:600; color:#343a40; }
.adu-th-label { background:#0f766e !important; color:#fff !important; border-color:#0b5d56 !important; }
.adu-th-total { background:#0b5d56 !important; color:#fff !important; border-color:#08443f !important; }
.adu-th-gedad { background:#d8f3f0 !important; color:#0b5d56 !important; font-weight:500; padding:.15rem .3rem !important; }
.adu-th-m { background:#a7e8e1 !important; color:#0b5d56 !important; font-weight:500; padding:.15rem .3rem !important; }
.adu-table tbody td { padding:.22rem .35rem; vertical-align:middle; border-bottom:1px solid #f1f3f5; }
.adu-table tbody tr:hover { background:#f8f9fa; }
.adu-table td.num, .adu-table th.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.adu-cero { color:#adb5bd; font-style:italic; }
.adu-niv1 { font-weight:500; color:#343a40; }
.adu-niv2 { color:#495057; }
.adu-sub { color:#0f766e; font-weight:500; }
.adu-cond { font-family:monospace; font-size:.7rem; background:#2c3e50; color:#ecf0f1; padding:.5rem .75rem; border-radius:.35rem; white-space:pre-wrap; word-break:break-all; }
.adu-head-cover { background:linear-gradient(90deg,#0b5d56 0%,#14b8a6 100%); color:#fff; border-radius:.5rem; padding:.8rem 1.25rem; margin-bottom:1rem; }
.adu-group-header { background:linear-gradient(90deg,#08443f 0%,#0f766e 100%); color:#fff; border-radius:.5rem; padding:.55rem 1rem; margin:1.6rem 0 .8rem 0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; box-shadow:0 2px 4px rgba(8,68,63,.25); }
.adu-group-header h6 { margin:0; font-weight:700; letter-spacing:.3px; }
.adu-group-header small { opacity:.8; }
.adu-secflag { font-size:.68rem; }
.adu-secnota { border-top:1px dashed #e0e0e0; }
.adu-nota td { font-size:.68rem; color:#6c757d; background:#fcfcfc; }
.adu-sqlrow td { padding:.15rem .35rem; }
</style>

<div class="page-header-section">
    <h4><i class="fas fa-user me-2 text-primary"></i>Reporte Operacional Adulto</h4>
    <p class="subtitle">Reporte de Actividades Mensuales de la Etapa de Vida Adulto (grupo etareo 30a-59a)</p>
</div>

<ul class="nav nav-pills subpage-tabs flex-wrap mb-3">
    <li class="nav-item me-1">
        <a class="nav-link" href="reporte_operacionales.php"><i class="fas fa-arrow-left me-1"></i>Volver</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#"><i class="fas fa-user me-1"></i>Adulto</a>
    </li>
</ul>

<!-- Filtros del reporte -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros del Reporte</h6>
        <div class="d-flex gap-2">
            <a href="adulto_export_plano.php?<?= http_build_query(array_filter([
                'anio' => $fAnio, 'mes' => $fMes, 'establecimiento' => $fEstablecimiento,
                'generar' => $ejecutar ? 1 : null,
                'token' => ($ejecutar && $fToken !== '') ? $fToken : null,
            ])) ?>" class="btn btn-sm btn-warning" title="Exportar el plano consolidado (Adulto_Plano.xlsx): cada dato va a la columna indicada por el numero de destino de 'Ubicacion_Filas_Adulto.xlsx' (ej: espejo D14=4 con dato en D14 -> plano D13; fila de datos 13, D13:LG13)">
                <i class="fas fa-table me-1"></i> Exportar Plano
            </a>
            <a href="adulto_export.php?<?= http_build_query(array_filter([
                'anio' => $fAnio, 'mes' => $fMes, 'establecimiento' => $fEstablecimiento,
                'generar' => $ejecutar ? 1 : null,
                'token' => ($ejecutar && $fToken !== '') ? $fToken : null,
            ])) ?>" class="btn btn-sm btn-success" title="Exportar Excel con el layout de la plantilla oficial Reporte_Actividades_Adulto.xlsx">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </a>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his" id="btnGenerar"><i class="fas fa-play me-1"></i> Generar Reporte</button>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="reporte_adulto.php">
            <input type="hidden" name="generar" value="1">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small" for="selAnio"><i class="fas fa-calendar me-1"></i>A&ntilde;o</label>
                    <select name="anio" id="selAnio" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $fAnio === (string)$a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small" for="selMes"><i class="fas fa-calendar-alt me-1"></i>Mes</label>
                    <select name="mes" id="selMes" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($mesesDisponibles as $m): ?>
                            <option value="<?= $m ?>" <?= $fMes === (string)$m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small" for="selEst"><i class="fas fa-hospital me-1"></i>Establecimiento (RENAES / IPRESS)</label>
                    <select name="establecimiento" id="selEst" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $codUnico => $nombre): ?>
                            <option value="<?= htmlspecialchars($codUnico) ?>" <?= $fEstablecimiento === $codUnico ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small" for="selGrupo"><i class="fas fa-layer-group me-1"></i>Grupo del reporte</label>
                    <select name="grupo" id="selGrupo" class="form-select form-select-sm">
                        <option value="">-- Todos los grupos --</option>
                        <?php foreach ($gruposMeta as $gk => $gm): ?>
                            <option value="<?= $gk ?>" <?= $fGrupo === $gk ? 'selected' : '' ?>><?= $gm['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-1 col-md-12 d-flex align-items-end gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="chkDetalle" name="detalle" value="1" <?= $fDetalle === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkDetalle">Filas en 0</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="chkSQL" name="versql" value="1" <?= $fVerSQL === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkSQL">Ver SQL</label>
                    </div>
                </div>
            </div>
        </form>

        <!-- Filtros dependientes: Anio y Mes se recalculan dentro del ambito del
             establecimiento. Cambiar EE.SS/anio NO genera el reporte. -->
        <script>
        (function () {
            var selAnio = document.getElementById('selAnio');
            var selMes  = document.getElementById('selMes');
            var selEst  = document.getElementById('selEst');
            if (!selAnio || !selMes || !selEst) return;

            function pedir(soloMeses) {
                var qs = new URLSearchParams();
                qs.set('ajax', 'filtros');
                qs.set('anio', selAnio.value);
                qs.set('establecimiento', selEst.value);
                fetch('reporte_adulto.php?' + qs.toString(), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d || !d.ok) return;
                        if (!soloMeses && d.anios && d.anios.length) {
                            var anioPrevio = selAnio.value;
                            reconstruir(selAnio, d.anios.map(String), true);
                            if (selAnio.value !== anioPrevio) { pedir(true); return; }
                        }
                        if (d.meses) reconstruir(selMes, d.meses, false);
                    })
                    .catch(function () { /* sin AJAX se conservan las opciones del servidor */ });
            }

            /* Reconstruye las opciones de un select conservando "-- Todos --"
               (value='') y la seleccion actual si sigue disponible. */
            function reconstruir(sel, valores, preferirPrimero) {
                var previo = sel.value;
                while (sel.options.length > 1) sel.remove(1);
                valores.forEach(function (v) {
                    var o = document.createElement('option');
                    if (v && typeof v === 'object') {
                        o.value = String(v.v);
                        o.textContent = v.t;
                    } else {
                        o.value = String(v);
                        o.textContent = String(v);
                    }
                    sel.appendChild(o);
                });
                var sigue = Array.prototype.some.call(sel.options, function (o) {
                    return o.value === previo;
                });
                sel.value = sigue ? previo : (preferirPrimero && valores.length ? sel.options[1].value : '');
            }

            selAnio.addEventListener('change', function () { pedir(true); });
            selEst.addEventListener('change', function () { pedir(false); });
        })();
        </script>
    </div>
</div>

<?php if ($ejecutar && $ambitoAmplio && $reporte === null): ?>
<!-- Rango amplio sin JS: el asistente trocea la generacion; sin JS se pide acotar -->
<div class="alert alert-warning d-flex" id="aduAutoWizard">
    <i class="fas fa-tasks me-3 mt-1 fa-2x"></i>
    <div>
        <strong>Rango amplio detectado.</strong> Para no agotar la memoria ni el tiempo del servidor
        (512&nbsp;MB / 60&nbsp;s), el reporte se genera <strong>por partes</strong> (un mes por paso,
        subdividido por establecimiento si hiciera falta). El asistente comenzar&aacute; autom&aacute;ticamente&hellip;
        <div class="small text-muted mt-1">
            Si no se inicia: active JavaScript en su navegador, o acote los filtros (un <strong>mes</strong>
            y/o un <strong>establecimiento</strong>) y vuelva a pulsar <em>Generar Reporte</em>.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==================== ASISTENTE DE GENERACION POR TROZOS ==================== -->
<div id="aduWizard" class="card shadow-sm mb-4 d-none">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-cogs me-2 text-primary"></i>Generando el reporte por partes</h6>
        <span class="badge bg-secondary" id="aduWizCont">0 / 0</span>
    </div>
    <div class="card-body">
        <div class="progress mb-2" style="height:22px;">
            <div id="aduWizBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <span id="aduWizEstado" class="small text-muted">Preparando&hellip;</span>
            <span id="aduWizStats" class="small text-muted"></span>
        </div>
        <div class="small text-muted mt-2">
            <i class="fas fa-info-circle me-1"></i>Cada paso procesa un mes (y, si hace falta, un establecimiento)
            para respetar los l&iacute;mites del hosting (512&nbsp;MB / 60&nbsp;s por petici&oacute;n). Puede tardar unos
            minutos: <strong>no cierre ni recargue esta pesta&ntilde;a</strong>.
        </div>
        <div id="aduWizError" class="alert alert-danger mt-3 mb-0 d-none">
            <h6 class="alert-heading mb-2"><i class="fas fa-exclamation-triangle me-1"></i>No se pudo terminar la generaci&oacute;n</h6>
            <p class="mb-2 small" id="aduWizErrMsg"></p>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary" id="aduWizRetry"><i class="fas fa-redo me-1"></i> Reintentar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="aduWizCancel">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
/* Asistente: Generar Reporte -> plan -> trozos secuenciales -> resultado.
   Un rango angosto se envia directo (formulario normal). Un rango amplio se
   trocea: cada paso es una peticion pequena que el hosting si puede servir. */
(function () {
    var form    = document.getElementById('filterForm');
    var panel   = document.getElementById('aduWizard');
    var barra   = document.getElementById('aduWizBar');
    var cont    = document.getElementById('aduWizCont');
    var estado  = document.getElementById('aduWizEstado');
    var stats   = document.getElementById('aduWizStats');
    var errBox  = document.getElementById('aduWizError');
    var errMsg  = document.getElementById('aduWizErrMsg');
    var btnGen  = document.getElementById('btnGenerar');
    var btnRetry= document.getElementById('aduWizRetry');
    var btnCanc = document.getElementById('aduWizCancel');
    if (!form) return;

    var corriendo = false;
    var cancelado = false;
    var plan = null;              /* {token, total, trozos} */
    var acumSeg = 0, acumFilas = 0;

    function espera(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

    function getJson(url) {
        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.text(); })
            .then(function (t) {
                try { return JSON.parse(t); }
                catch (e) { throw new Error('Respuesta no válida del servidor (¿expiró la sesión?).'); }
            });
    }

    function paramsFiltros() {
        var u = new URLSearchParams();
        u.set('ajax', 'plan');
        u.set('anio', document.getElementById('selAnio').value);
        u.set('mes', document.getElementById('selMes').value);
        u.set('establecimiento', document.getElementById('selEst').value);
        return u.toString();
    }

    /* Conserva las opciones de vista (filas en 0 / Ver SQL / grupo) al
       redirigir a la URL del reporte generado por el asistente. */
    function conOpcionesVista(url) {
        var extra = new URLSearchParams();
        var chkDet = document.getElementById('chkDetalle');
        if (chkDet && !chkDet.checked) extra.set('detalle', '0');
        var chkSql = document.getElementById('chkSQL');
        if (chkSql && chkSql.checked) extra.set('versql', '1');
        var selG = document.getElementById('selGrupo');
        if (selG && selG.value) extra.set('grupo', selG.value);
        var qs = extra.toString();
        return url + (qs ? (url.indexOf('?') >= 0 ? '&' : '?') + qs : '');
    }

    function progreso(hechos, total) {
        var pct = total > 0 ? Math.round(hechos * 100 / total) : 0;
        if (barra) { barra.style.width = pct + '%'; barra.textContent = pct + '%'; }
        if (cont) cont.textContent = hechos + ' / ' + total;
    }

    function ocultarError() {
        if (errBox) errBox.classList.add('d-none');
        if (barra) {
            barra.classList.add('bg-success', 'progress-bar-animated');
            barra.classList.remove('bg-danger');
        }
    }

    function errorPanel(msg) {
        if (errMsg) errMsg.textContent = msg || 'Error desconocido.';
        if (errBox) errBox.classList.remove('d-none');
        if (barra) {
            barra.classList.remove('bg-success', 'progress-bar-animated');
            barra.classList.add('bg-danger');
        }
        if (btnGen) btnGen.disabled = false;
        corriendo = false;
    }

    async function correr() {
        acumSeg = 0; acumFilas = 0;
        var i = 0;
        while (!cancelado) {
            if (i >= plan.total) break;
            progreso(i, plan.total);
            var etiqueta = (plan.trozos && plan.trozos[i] && plan.trozos[i].etiqueta)
                            ? plan.trozos[i].etiqueta : ('paso ' + (i + 1));
            if (estado) estado.textContent = 'Procesando: ' + etiqueta + '…';

            var res = null;
            try {
                res = await getJson('reporte_adulto.php?ajax=trozo&token=' +
                        encodeURIComponent(plan.token) + '&i=' + i);
            } catch (e) {
                var intentos = 0, okNet = false;
                while (intentos < 2 && !okNet && !cancelado) {
                    await espera(2500);
                    try {
                        res = await getJson('reporte_adulto.php?ajax=trozo&token=' +
                                encodeURIComponent(plan.token) + '&i=' + i);
                        okNet = true;
                    } catch (e2) { intentos++; }
                }
                if (!okNet) { errorPanel('No se pudo comunicar con el servidor tras varios intentos: ' + e.message); return; }
            }
            if (cancelado) return;

            if (!res || res.ok === false) {
                if (res && (res.causa === 'memoria' || res.causa === 'tiempo')) {
                    /* Auto-reparacion: subdividir el paso por establecimiento */
                    var dv = null;
                    try {
                        dv = await getJson('reporte_adulto.php?ajax=dividir&token=' +
                                encodeURIComponent(plan.token) + '&i=' + i);
                    } catch (e2) { /* sigue al error */ }
                    if (dv && dv.ok) {
                        plan.total = dv.total;
                        if (estado) estado.textContent = 'Paso subdividido por establecimiento (' + plan.total + ' pasos)…';
                        continue;                 /* repetir el mismo i, ya subdividido */
                    }
                    errorPanel('El paso «' + etiqueta + '» agotó la memoria/tiempo del servidor y no se pudo subdividir. ' +
                               ((dv && dv.mensaje) || (res.detalle || '')));
                    return;
                }
                errorPanel('Paso «' + etiqueta + '»: ' + ((res && res.error) || 'error desconocido.'));
                return;
            }

            acumSeg   += (res.seg || 0);
            acumFilas += (res.filas || 0);
            if (stats) stats.textContent = acumFilas.toLocaleString('es-PE') + ' filas · ' + Math.round(acumSeg) + ' s';
            i = res.hechos;                        /* el servidor dicta el avance real */
        }
        if (cancelado) return;

        progreso(plan.total, plan.total);
        if (estado) estado.textContent = 'Completado. Preparando el reporte…';
        try {
            var fin = await getJson('reporte_adulto.php?ajax=resultado&token=' + encodeURIComponent(plan.token));
            if (fin && fin.ok && fin.url) { window.location.href = conOpcionesVista(fin.url); return; }
            errorPanel((fin && fin.error) || 'No se pudo recuperar el reporte final.');
        } catch (e) {
            errorPanel(e.message);
        }
    }

    async function iniciar() {
        if (corriendo) return;
        corriendo = true;
        cancelado = false;
        plan = null;
        if (btnGen) btnGen.disabled = true;
        try {
            var d = await getJson('reporte_adulto.php?' + paramsFiltros());
            if (!d || d.ok === false) { errorPanel((d && d.error) || 'No se pudo iniciar la generación.'); return; }
            if (d.modo === 'directo') { form.submit(); return; }        /* rango angosto: normal */
            if (d.modo === 'cache' && d.url) { window.location.href = conOpcionesVista(d.url); return; }  /* ya generado */
            plan = d;
            if (panel) panel.classList.remove('d-none');
            ocultarError();
            progreso(0, plan.total);
            if (estado) estado.textContent = plan.total + ' pasos en cola…';
            if (stats) stats.textContent = '';
            await correr();
        } catch (e) {
            errorPanel(e.message);
        }
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        iniciar();
    });
    if (btnRetry) btnRetry.addEventListener('click', function () {
        if (corriendo) return;
        ocultarError();
        if (btnGen) btnGen.disabled = true;
        corriendo = true;
        cancelado = false;
        if (plan) { correr(); } else { iniciar(); }
    });
    if (btnCanc) btnCanc.addEventListener('click', function () {
        cancelado = true;
        corriendo = false;
        if (panel) panel.classList.add('d-none');
        if (btnGen) btnGen.disabled = false;
    });

    /* Auto-arranque cuando el servidor marco el rango como amplio */
    if (document.getElementById('aduAutoWizard')) { iniciar(); }
})();
</script>

<?php if ($ejecutar && $reporte): ?>

<?php if (!empty($reporte['error'])): ?>
<div class="alert alert-danger">
    <h6 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>Error al ejecutar el reporte</h6>
    <p class="mb-2"><?= htmlspecialchars($reporte['error']) ?></p>
    <?php if ($debugSQL): ?>
    <details>
        <summary class="small">Ver SQL debug</summary>
        <div class="adu-cond mt-2"><?= "SQL: " . htmlspecialchars($debugSQL['sql']) . "\n\nPARAMS: " . htmlspecialchars(json_encode($debugSQL['params'], JSON_PRETTY_PRINT)) ?></div>
    </details>
    <?php endif; ?>
    <?php $diag = aduDiagnosticoEntorno($pdo); ?>
    <hr>
    <h6 class="small fw-bold mb-2"><i class="fas fa-stethoscope me-1"></i>Diagn&oacute;stico del entorno</h6>
    <ul class="small mb-2 ps-3">
        <li>PHP: <code><?= htmlspecialchars($diag['php']) ?></code> &nbsp;|&nbsp; memory_limit: <code><?= htmlspecialchars($diag['memory_limit']) ?></code> &nbsp;|&nbsp; max_execution_time: <code><?= htmlspecialchars($diag['max_exec_time']) ?>s</code></li>
        <li>Filas aprox. en la tabla consolidada: <code><?= $diag['filas_tabla'] !== null ? number_format((int)$diag['filas_tabla']) : 'n/d' ?></code></li>
        <li>
            &Iacute;ndices en la tabla consolidada:
            <?php if (empty($diag['indices'])): ?>
                <span class="badge bg-danger">NINGUNO</span>
                &rarr; la consulta hace un barrido completo de la tabla. Ejecute una vez
                <a href="install_adulto.php" class="fw-bold">install_adulto.php</a> para crearlos.
            <?php else: ?>
                <span class="badge bg-success"><?= count($diag['indices']) ?></span>
                <code class="small"><?= htmlspecialchars(implode(', ', $diag['indices'])) ?></code>
            <?php endif; ?>
        </li>
    </ul>
    <div class="small text-muted">
        <i class="fas fa-lightbulb me-1"></i>Recomendaciones: ejecute <strong>install_adulto.php</strong> (una sola vez), y genere el reporte con filtros m&aacute;s acotados
        (seleccione un mes y/o un establecimiento en lugar de "-- Todos --").
    </div>
</div>

<?php else: ?>

<!-- Cabecera tipo Excel: PERIODO / RED / RENAES / IPRESS -->
<div class="adu-head-cover d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <i class="fas fa-check-circle me-2"></i>
        <strong>REPORTE DE ACTIVIDADES MENSUALES DE LA ETAPA DE VIDA ADULTO</strong><br>
        <small>
            DIRESA: JUNIN &nbsp;|&nbsp; RED: CHANCHAMAYO &nbsp;|&nbsp; MICRO RED: PERENE<br>
            PERIODO: <?= htmlspecialchars($periodoTxt) ?>
            <?= $renaes ? ' &nbsp;|&nbsp; ESTABLECIMIENTO (RENAES): ' . htmlspecialchars($renaes) : '' ?>
            <?= $nombreEstablecimiento ? ' &nbsp;|&nbsp; IPRESS: ' . htmlspecialchars($nombreEstablecimiento) : ' &nbsp;|&nbsp; TODOS LOS ESTABLECIMIENTOS DE LA LISTA (' . count($establecimientos) . ' EE.SS)' ?>
        </small>
    </div>
    <div class="text-end">
        <strong><?= number_format($reporte['totales']['total_casos']) ?></strong> cifras totales /
        <?= number_format($reporte['totales']['secciones_con_datos']) ?> de 21 secciones con datos /
        <?= number_format($reporte['filas_leidas']) ?> filas HIS le&iacute;das / <?= $reporte['tiempo_ejecucion'] ?>s<br>
        <?php if ($infoTrozos): ?>
        <span class="badge bg-success mb-0 me-1" title="Generado por pasos (asistente por trozos) para respetar los l&iacute;mites de memoria/tiempo del hosting."><i class="fas fa-cogs me-1"></i><?= (int)$infoTrozos['pasos'] ?> pasos</span>
        <?php endif; ?>
        <span class="badge bg-dark mb-0" title="Versión del motor includes/adulto_data.php en ejecución.">Motor adulto_data.php v<?= htmlspecialchars($reporte['version'] ?? aduDataVersion()) ?></span>
    </div>
</div>

<?php if ($fVerSQL === '1'): ?>
<div class="alert alert-warning py-2">
    <i class="fas fa-search me-2"></i>
    <strong>Modo auditor&iacute;a activado:</strong> cada bloque muestra la condici&oacute;n SQL equivalente que ejecuta el motor
    (adaptaci&oacute;n de los 21 procedimientos <code>usp_TRAMA_BASE_ADULTO_RPT_*</code> del archivo <code>03 Creacion de Procedimientos</code>).
    Compare estas condiciones con su archivo original y aj&uacute;stelas en <code>includes/adulto_data.php &rarr; aduSecciones()</code> si alguna difiere.
</div>
<?php endif; ?>

<?php
// ====== Render de secciones agrupadas por macro-grupo del Excel ======
$secciones = $reporte['secciones'];
$grupoActual = null;
foreach ($secciones as $sec):
    if ($fGrupo !== '' && $sec['grupo'] !== $fGrupo) continue;
    if ($sec['grupo'] !== $grupoActual):
        $grupoActual = $sec['grupo'];
        $gm = $gruposMeta[$grupoActual] ?? null;
        $totGrupo = $reporte['totales']['grupos'][$grupoActual] ?? 0;
        $nSecsGrupo = count(array_filter($secciones, fn($s) => $s['grupo'] === $grupoActual));
?>
<div class="adu-group-header">
    <h6><i class="fas <?= $gm['icon'] ?? 'fa-layer-group' ?> me-2"></i><?= $gm['nombre'] ?? htmlspecialchars($grupoActual) ?></h6>
    <small><?= $gm['desc'] ?? '' ?> &nbsp;|&nbsp; <?= $nSecsGrupo ?> secciones &nbsp;|&nbsp; total: <strong><?= number_format($totGrupo) ?></strong></small>
</div>
<?php endif; ?>
<div class="adu-section-card">
    <div class="adu-section-header">
        <span><i class="fas fa-file-medical me-2"></i><?= htmlspecialchars($sec['titulo']) ?></span>
        <small>
            <span class="adu-secflag badge bg-light text-dark me-1"><?= htmlspecialchars($sec['codigo']) ?></span>
            <?= count($sec['filas']) ?> filas
            | total: <strong><?= number_format($sec['total']) ?></strong>
        </small>
    </div>
    <div class="card-body p-0">
        <?php aduRenderSeccion($sec, $fDetalle === '1', $fVerSQL === '1'); ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Nota sobre equivalencia con el flujo original -->
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Equivalencia con el flujo original:</strong> este reporte replica los 21 procedimientos
    <code>usp_TRAMA_BASE_ADULTO_RPT_*</code> (archivos 01/02/03) sobre la tabla consolidada MySQL y el
    layout de la plantilla <code>Reporte_Actividades_Adulto.xlsx</code> (17 bloques: morbilidad, plan de
    atenci&oacute;n integral, evaluaci&oacute;n antropom&eacute;trica, riesgo nutricional, da&ntilde;os no transmisibles,
    planificaci&oacute;n familiar, prevenci&oacute;n de c&aacute;ncer, atenci&oacute;n en salud bucal, salud mental, TBC, salud
    sexual y reproductiva, ITS-VIH, salud ocular, actividades preventivo promocionales, inmunizaciones,
    sesiones y otras consejer&iacute;as). Ya no necesita SQL Server ni refrescar la conexi&oacute;n ODBC:
    <strong>1 click y (opcional) Exportar Excel / Exportar Plano</strong>.<br>
    Si el rango elegido es amplio (varios meses/establecimientos), el sistema lo genera <strong>por partes</strong>
    con el asistente, para respetar los l&iacute;mites del hosting gratuito sin perder exactitud: la suma de las partes
    es id&eacute;ntica al total.<br>
    <strong>Desarrollado por JKRV</strong>
</div>

<?php endif; ?>

<?php elseif (!$ejecutar): ?>
<div class="alert alert-info d-flex align-items-center">
    <i class="fas fa-info-circle me-3 fa-2x"></i>
    <div>
        <strong>Reporte listo para generar.</strong><br>
        <div class="small text-muted mt-1">
            Seleccione el periodo y pulse <strong>Generar Reporte</strong>. El informe incluye los 17 bloques del reporte
            operacional Adulto (morbilidad, plan de atenci&oacute;n integral, evaluaci&oacute;n antropom&eacute;trica, riesgo
            nutricional, da&ntilde;os no transmisibles, planificaci&oacute;n familiar, prevenci&oacute;n de c&aacute;ncer, salud bucal,
            salud mental, TBC, salud sexual y reproductiva, ITS-VIH, salud ocular, actividades preventivo
            promocionales, inmunizaciones, sesiones y otras consejer&iacute;as) del grupo etareo 30a-59a.
            Rangos amplios (&quot;Todos&quot;): el sistema los genera autom&aacute;ticamente por partes para respetar los
            l&iacute;mites del hosting. Desarrollado por JKRV
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
