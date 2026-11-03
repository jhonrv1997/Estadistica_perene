<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 02: Convenio FED - Fondo de Estimulo al Desempeno
 *
 * v3 - Mejoras (2026-11):
 *  1. Gestion de indicadores FED (agregar / modificar / activar-desactivar)
 *     visible SOLO para el rol Administrador -> tabla CONVENIO_FED_INDICADORES.
 *  2. Importacion del archivo Resultados.txt (exportado del SQL Server local)
 *     a la tabla CONVENIO_FED_AVANCES, visible SOLO para el rol Administrador.
 *  3. Visualizacion interactiva con filtros por establecimiento, anio, mes
 *     (con opcion "todos los meses") e indicador. Orden de columnas del detalle:
 *     renipress, establecimiento, anio, mes, valor_numerador, valor_denominador,
 *     porcentaje_avance, observaciones (+ Indicador y Fecha/Hora de importacion).
 *
 * v3.1 - Correccion del ERROR 500 (2026-11-04):
 *  - fedTablaExiste() y fedTieneColumna() ya no usan prepare("SHOW ... LIKE ?").
 *    Con PDO::ATTR_EMULATE_PREPARES => false (config.php), el protocolo de
 *    sentencias preparadas de MySQL/MariaDB rechaza el placeholder '?' en las
 *    sentencias SHOW (error 1064) y la pagina caia con "500 Internal Server
 *    Error" nada mas entrar. Ahora se consulta information_schema (admite
 *    placeholders) con respaldo SHOW + literal seguro.
 *  - Red de seguridad global: cualquier error no previsto muestra un panel
 *    de diagnostico legible en lugar de una pagina 500 en blanco.
 *  - Auto-reparacion del esquema: si la tabla de avances/indicadores quedo sin
 *    PRIMARY KEY o AUTO_INCREMENT (los ALTER del volcado antiguo usaban el
 *    nombre singular CONVENIO_FED_AVANCE), se repara automaticamente; si el
 *    hosting no permite ALTER, los INSERT asignan el id manualmente (MAX + 1).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

/* ============================================================================
 * 0. RED DE SEGURIDAD CONTRA ERRORES FATALES
 * --------------------------------------------------------------------------
 * Si algo falla de forma inesperada (por ejemplo, una consulta incompatible
 * con la version de MySQL/MariaDB del hosting), se muestra un panel legible
 * con el motivo en lugar del en blanco "500 Internal Server Error".
 * ========================================================================== */
if (!function_exists('fedPanelError')) {
    function fedPanelError($titulo, $detallesHtml) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if (!headers_sent()) { http_response_code(500); }
        $tituloEsc = htmlspecialchars((string)$titulo, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>Convenio FED - ' . $tituloEsc . '</title></head>'
           . '<body style="font-family:Segoe UI,Arial,sans-serif;max-width:860px;margin:40px auto;padding:0 16px;color:#212529">'
           . '<div style="border:2px solid #dc3545;border-radius:10px;padding:22px 26px;background:#fff8f8">'
           . '<h1 style="font-size:20px;margin:0 0 12px;color:#dc3545">Convenio FED - ' . $tituloEsc . '</h1>'
           . $detallesHtml
           . '<p style="margin-top:18px;font-size:13px;color:#6c757d">Consejo: suba el archivo <code>diagnostico_fed.php</code> a la raiz del sitio y abralo para un diagnostico completo del servidor.</p>'
           . '<p style="font-size:13px"><a href="dashboard.php">&larr; Volver al panel</a></p>'
           . '</div></body></html>';
        exit;
    }
}
set_exception_handler(function ($e) {
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    fedPanelError('Error inesperado',
        '<p>La pagina no pudo completarse por un error no previsto. Detalle tecnico:</p>'
      . '<p style="font-family:Consolas,monospace;background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:10px;word-break:break-word">' . $msg . '</p>');
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e !== null && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $msg = htmlspecialchars($e['message'], ENT_QUOTES, 'UTF-8');
        fedPanelError('Error fatal del servidor',
            '<p>El script se detuvo de forma abrupta. Detalle tecnico:</p>'
          . '<p style="font-family:Consolas,monospace;background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:10px;word-break:break-word">'
          . $msg . ' (linea ' . (int)$e['line'] . ')</p>');
    }
});
ob_start(); // captura la salida: permite mostrar el panel de error limpio si algo falla

$pdo = getDBConnection();
$esAdmin = esAdmin();
$mensaje = '';
$detalleMensaje = '';
$tipoMensaje = '';

/* ============================================================================
 * 1. DETECCION DE TABLAS Y COLUMNAS
 * ========================================================================== */

/**
 * Verifica si una tabla existe en la base de datos actual.
 *
 * IMPORTANTE (causa del error 500 corregido en v3.1): con la configuracion
 * PDO::ATTR_EMULATE_PREPARES => false de config.php, las sentencias SHOW se
 * preparan de forma NATIVA en MySQL/MariaDB y ese protocolo NO admite el
 * placeholder '?' dentro de SHOW (error 1064 "near '?'"). Por eso aqui se
 * consulta information_schema (que SI admite placeholders) y, como plan B,
 * se usa SHOW con el nombre como literal seguro (lista blanca interna,
 * nunca entrada del usuario), igual que hacia la version anterior.
 */
function fedTablaExiste($pdo, $nombre) {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $st->execute([$nombre]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        try { // Plan B: SHOW con literal (el nombre proviene del codigo, es seguro)
            $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($nombre));
            return (bool)$st->fetchColumn();
        } catch (Throwable $e2) {
            return false;
        }
    }
}

/**
 * Verifica si una columna existe en una tabla (mismo criterio que fedTablaExiste)
 */
function fedTieneColumna($pdo, $tabla, $columna) {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $st->execute([$tabla, $columna]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        try { // Plan B: SHOW COLUMNS con literal (tabla de lista blanca interna)
            $tablaSeg = str_replace('`', '', (string)$tabla);
            $st = $pdo->query("SHOW COLUMNS FROM `{$tablaSeg}` LIKE " . $pdo->quote($columna));
            return (bool)$st->fetchColumn();
        } catch (Throwable $e2) {
            return false;
        }
    }
}

/**
 * Verifica si una columna es AUTO_INCREMENT (via information_schema, compatible
 * con prepare nativo).
 */
function fedEsAutoIncrement($pdo, $tabla, $columna) {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                               AND COLUMN_NAME = ? AND EXTRA LIKE '%auto_increment%'");
        $st->execute([$tabla, $columna]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Garantiza PRIMARY KEY + AUTO_INCREMENT en la columna indicada de una tabla.
 *
 * MOTIVO (bug real detectado): en Database/if0_42181393_his.sql los ALTER de
 * clave primaria y AUTO_INCREMENT de la tabla de avances apuntan al nombre en
 * SINGULAR (CONVENIO_FED_AVANCE), que no existe, por lo que al importar ese
 * volcado la tabla real quedo sin PK ni AUTO_INCREMENT y los INSERT fallaban
 * con "Field 'id_avance' doesn't have a default value". Esta funcion repara
 * el esquema de forma automatica la primera vez que se necesita. Si el hosting
 * no permite ALTER, los INSERT usan el modo de id manual (MAX + 1).
 */
function fedAsegurarAutoIncrement($pdo, $tabla, $columna) {
    if (fedEsAutoIncrement($pdo, $tabla, $columna)) {
        return true;
    }
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                               AND CONSTRAINT_TYPE = 'PRIMARY KEY'");
        $st->execute([$tabla]);
        if (!$st->fetchColumn()) {
            $pdo->exec("ALTER TABLE `{$tabla}` ADD PRIMARY KEY (`{$columna}`)");
        }
        $pdo->exec("ALTER TABLE `{$tabla}` MODIFY `{$columna}` int(11) NOT NULL AUTO_INCREMENT");
        return fedEsAutoIncrement($pdo, $tabla, $columna);
    } catch (Throwable $e) {
        return false; // sin permisos ALTER: los INSERT usaran id manual
    }
}

$tablaIndicadores = fedTablaExiste($pdo, 'CONVENIO_FED_INDICADORES') ? 'CONVENIO_FED_INDICADORES' : null;
$tablaAvances     = fedTablaExiste($pdo, 'CONVENIO_FED_AVANCES')     ? 'CONVENIO_FED_AVANCES'
                  : (fedTablaExiste($pdo, 'CONVENIO_FED_AVANCE')     ? 'CONVENIO_FED_AVANCE'     : null);

$puedeGestionar = $tablaIndicadores !== null;                       // CRUD indicadores
$puedeImportar  = $tablaIndicadores !== null && $tablaAvances !== null; // importacion
$sistemaOK      = $tablaIndicadores !== null && $tablaAvances !== null;

// Columna fecha_importacion (puede no existir si aun no se aplica update_fed_v3.sql)
$tieneFechaImportacion = $tablaAvances !== null ? fedTieneColumna($pdo, $tablaAvances, 'fecha_importacion') : false;

/* ============================================================================
 * 2. PARSER DEL ARCHIVO Resultados.txt (13 columnas separadas por ;)
 *    Formato exportado del SQL Server:
 *    id_avance; id_indicador; renipress; establecimiento; anio; trimestre; mes;
 *    valor_numerador; valor_denominador; porcentaje_avance; observaciones;
 *    fecha_registro; usuario
 *    (Los textos "NULL" y los campos vacios se convierten en NULL de PHP)
 * ========================================================================== */

function fedParseLineaAvance($linea) {
    $linea = trim($linea);
    if ($linea === '') {
        return [null, 'Linea vacia'];
    }
    $campos = explode(';', $linea);
    if (count($campos) !== 13) {
        return [null, 'Se esperaban 13 columnas separadas por ";" y hay ' . count($campos)];
    }
    $c = [];
    foreach ($campos as $v) {
        $v = trim($v);
        $c[] = ($v === '' || strcasecmp($v, 'NULL') === 0) ? null : $v;
    }

    $d = [
        // [0] id_avance del SQL Server se IGNORA: la web asigna el suyo (AUTO_INCREMENT)
        'id_indicador'     => $c[1],
        'renipress'        => $c[2],
        'establecimiento'  => $c[3],
        'anio'             => $c[4],
        'trimestre'        => $c[5],
        'mes'              => $c[6],
        'valor_numerador'  => $c[7],
        'valor_denominador'=> $c[8],
        'porcentaje_avance'=> $c[9],
        'observaciones'    => $c[10],
        'fecha_registro'   => $c[11] !== null ? substr($c[11], 0, 19) : null,
        'usuario'          => $c[12],
    ];

    // --- Validaciones ---
    if (!ctype_digit((string)$d['id_indicador'])) {
        return [null, "id_indicador invalido ('{$d['id_indicador']}')"];
    }
    if ($d['renipress'] === null || $d['renipress'] === '') {
        return [null, 'renipress vacio'];
    }
    if (mb_strlen($d['renipress']) > 10) {
        return [null, "renipress '{$d['renipress']}' supera los 10 caracteres permitidos"];
    }
    if ($d['establecimiento'] === null || $d['establecimiento'] === '') {
        return [null, 'establecimiento vacio'];
    }
    if (!preg_match('/^\d{4}$/', (string)$d['anio'])) {
        return [null, "anio invalido ('{$d['anio']}')"];
    }
    if ($d['mes'] !== null) {
        if (!preg_match('/^\d{1,2}$/', (string)$d['mes'])) {
            return [null, "mes invalido ('{$d['mes']}')"];
        }
        $mesN = (int)$d['mes'];
        if ($mesN < 1 || $mesN > 12) {
            return [null, "mes fuera de rango 01-12 ('{$d['mes']}')"];
        }
        $d['mes'] = str_pad((string)$mesN, 2, '0', STR_PAD_LEFT);
    }
    if ($d['trimestre'] !== null && mb_strlen($d['trimestre']) > 2) {
        $d['trimestre'] = mb_substr($d['trimestre'], 0, 2);
    }
    foreach (['valor_numerador', 'valor_denominador'] as $k) {
        if ($d[$k] === null || !is_numeric((string)$d[$k])) {
            return [null, "{$k} invalido o vacio ('{$d[$k]}')"];
        }
        $d[$k] = (float)$d[$k];
    }
    if ($d['porcentaje_avance'] !== null && !is_numeric((string)$d['porcentaje_avance'])) {
        return [null, "porcentaje_avance invalido ('{$d['porcentaje_avance']}')"];
    }
    if ($d['porcentaje_avance'] === null) {
        // Si el archivo no trae porcentaje se recalcula: numerador / denominador * 100
        $den = (float)$d['valor_denominador'];
        $d['porcentaje_avance'] = $den != 0 ? round(((float)$d['valor_numerador'] / $den) * 100, 2) : 0.0;
    } else {
        $d['porcentaje_avance'] = min(999.99, max(-999.99, (float)$d['porcentaje_avance']));
    }
    if (mb_strlen($d['establecimiento']) > 50) {
        $d['establecimiento'] = mb_substr($d['establecimiento'], 0, 50);
    }
    if ($d['fecha_registro'] !== null) {
        $ts = strtotime($d['fecha_registro']);
        $d['fecha_registro'] = $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }
    if ($d['usuario'] !== null && mb_strlen($d['usuario']) > 100) {
        $d['usuario'] = mb_substr($d['usuario'], 0, 100);
    }
    return [$d, null];
}

/* ============================================================================
 * 3. PROCESAR ACCIONES POST (SOLO ADMINISTRADOR)
 * ========================================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$esAdmin) {
        $mensaje = 'Acceso denegado: las acciones de administracion estan reservadas al rol Administrador.';
        $tipoMensaje = 'danger';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $mensaje = 'Token de seguridad invalido. Recargue la pagina e intente nuevamente.';
        $tipoMensaje = 'danger';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            /* --------------------------------------------------------------
             * 3.1 GUARDAR INDICADOR (crear o modificar)
             * ----------------------------------------------------------- */
            if ($accion === 'fed_guardar_indicador') {
                if (!$puedeGestionar) {
                    throw new Exception('La tabla CONVENIO_FED_INDICADORES no existe. Ejecute Database/update_fed_v3.sql.');
                }
                $idInd    = (int)($_POST['id_indicador'] ?? 0);
                $codigo   = strtoupper(trim($_POST['codigo'] ?? ''));
                $nombre   = trim($_POST['nombre'] ?? '');
                $descr    = trim($_POST['descripcion'] ?? '');
                $unidad   = trim($_POST['unidad_medida'] ?? '');
                $metaRaw  = trim($_POST['meta_anual'] ?? '');
                $pesoRaw  = trim($_POST['peso_ponderado'] ?? '');
                $periodo  = strtoupper(trim($_POST['periodo'] ?? 'TRIMESTRAL'));
                $respons  = trim($_POST['responsable'] ?? '');
                $orden    = (int)($_POST['orden'] ?? 0);
                $estado   = isset($_POST['estado']) ? 1 : 0;

                if ($codigo === '' || $nombre === '') {
                    throw new Exception('El codigo y el nombre del indicador son obligatorios.');
                }
                if (mb_strlen($codigo) > 20) {
                    throw new Exception('El codigo no puede superar 20 caracteres.');
                }
                if (!in_array($periodo, ['MENSUAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL'], true)) {
                    $periodo = 'TRIMESTRAL';
                }
                $meta = null;
                if ($metaRaw !== '') {
                    if (!is_numeric($metaRaw)) throw new Exception("La meta anual debe ser un numero ('{$metaRaw}').");
                    $meta = (float)$metaRaw;
                }
                $peso = null;
                if ($pesoRaw !== '') {
                    if (!is_numeric($pesoRaw)) throw new Exception("El peso ponderado debe ser un numero ('{$pesoRaw}').");
                    $peso = (float)$pesoRaw;
                    if ($peso < 0 || $peso > 100) throw new Exception('El peso ponderado debe estar entre 0 y 100.');
                }

                if ($idInd > 0) {
                    $st = $pdo->prepare("SELECT COUNT(*) FROM `{$tablaIndicadores}` WHERE id_indicador = ?");
                    $st->execute([$idInd]);
                    if (!$st->fetchColumn()) throw new Exception('El indicador que intenta modificar no existe.');
                    $sql = "UPDATE `{$tablaIndicadores}`
                            SET codigo = ?, nombre = ?, descripcion = ?, unidad_medida = ?, meta_anual = ?,
                                peso_ponderado = ?, periodo = ?, responsable = ?, orden = ?, estado = ?
                            WHERE id_indicador = ?";
                    $pdo->prepare($sql)->execute([$codigo, $nombre, $descr ?: null, $unidad ?: null, $meta,
                                                  $peso, $periodo, $respons ?: null, $orden, $estado, $idInd]);
                    $mensaje = "Indicador <strong>" . clean($codigo) . "</strong> modificado correctamente.";
                } else {
                    // Reparacion preventiva: si la tabla quedo sin AUTO_INCREMENT
                    // (volcados antiguos), se intenta activar; si no se puede,
                    // se inserta con id manual (MAX + 1).
                    $autoIncInd = fedAsegurarAutoIncrement($pdo, $tablaIndicadores, 'id_indicador');
                    if ($autoIncInd) {
                        $sql = "INSERT INTO `{$tablaIndicadores}`
                                (codigo, nombre, descripcion, unidad_medida, meta_anual, peso_ponderado,
                                 periodo, responsable, orden, estado)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $pdo->prepare($sql)->execute([$codigo, $nombre, $descr ?: null, $unidad ?: null, $meta,
                                                      $peso, $periodo, $respons ?: null, $orden, $estado]);
                    } else {
                        $nuevoId = (int)$pdo->query("SELECT COALESCE(MAX(id_indicador), 0) + 1 FROM `{$tablaIndicadores}`")->fetchColumn();
                        $sql = "INSERT INTO `{$tablaIndicadores}`
                                (id_indicador, codigo, nombre, descripcion, unidad_medida, meta_anual, peso_ponderado,
                                 periodo, responsable, orden, estado)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $pdo->prepare($sql)->execute([$nuevoId, $codigo, $nombre, $descr ?: null, $unidad ?: null, $meta,
                                                      $peso, $periodo, $respons ?: null, $orden, $estado]);
                    }
                    $mensaje = "Indicador <strong>" . clean($codigo) . "</strong> agregado correctamente.";
                }
                $tipoMensaje = 'success';

            /* --------------------------------------------------------------
             * 3.2 ACTIVAR / DESACTIVAR INDICADOR
             * ----------------------------------------------------------- */
            } elseif ($accion === 'fed_toggle_indicador') {
                if (!$puedeGestionar) {
                    throw new Exception('La tabla CONVENIO_FED_INDICADORES no existe. Ejecute Database/update_fed_v3.sql.');
                }
                $idInd = (int)($_POST['id_indicador'] ?? 0);
                if ($idInd <= 0) throw new Exception('ID de indicador invalido.');
                $st = $pdo->prepare("SELECT codigo, estado FROM `{$tablaIndicadores}` WHERE id_indicador = ?");
                $st->execute([$idInd]);
                $row = $st->fetch();
                if (!$row) throw new Exception('El indicador no existe.');
                $nuevoEstado = (int)$row['estado'] === 1 ? 0 : 1;
                $pdo->prepare("UPDATE `{$tablaIndicadores}` SET estado = ? WHERE id_indicador = ?")
                    ->execute([$nuevoEstado, $idInd]);
                $mensaje = "Indicador <strong>" . clean($row['codigo']) . "</strong> " .
                           ($nuevoEstado === 1 ? 'ACTIVADO' : 'DESACTIVADO') . " correctamente." .
                           ($nuevoEstado === 0 ? ' Los indicadores desactivados no se muestran en el reporte, pero conservan su historial de avances.' : '');
                $tipoMensaje = 'success';

            /* --------------------------------------------------------------
             * 3.3 IMPORTAR Resultados.txt -> CONVENIO_FED_AVANCES
             * ----------------------------------------------------------- */
            } elseif ($accion === 'fed_importar') {
                if (!$puedeImportar) {
                    throw new Exception('Las tablas del Convenio FED no existen. Ejecute Database/update_fed_v3.sql.');
                }
                if (!isset($_FILES['archivo_txt']) || $_FILES['archivo_txt']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No se recibio el archivo. Verifique que haya seleccionado el Resultados.txt (error ' . ($_FILES['archivo_txt']['error'] ?? 'N/D') . ').');
                }
                if ($_FILES['archivo_txt']['size'] <= 0 || $_FILES['archivo_txt']['size'] > MAX_FILE_SIZE) {
                    throw new Exception('El archivo esta vacio o supera el limite de ' . round(MAX_FILE_SIZE / 1048576) . ' MB del hosting.');
                }
                $nombreOriginal = $_FILES['archivo_txt']['name'];
                $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                if (!in_array($ext, ['txt', 'csv', 'out'], true)) {
                    throw new Exception('Formato no permitido (' . clean($ext) . '). Use el archivo .txt exportado del SQL Server.');
                }

                // --- Cargar y normalizar contenido ---
                $contenido = file_get_contents($_FILES['archivo_txt']['tmp_name']);
                if ($contenido === false) throw new Exception('No se pudo leer el archivo subido.');
                if (substr($contenido, 0, 3) === "\xEF\xBB\xBF") {
                    $contenido = substr($contenido, 3); // quitar BOM UTF-8
                }
                if (!mb_check_encoding($contenido, 'UTF-8')) {
                    $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
                }

                // --- Asegurar la columna fecha_importacion (auto-reparacion) ---
                if (!$tieneFechaImportacion) {
                    try {
                        $pdo->exec("ALTER TABLE `{$tablaAvances}` ADD COLUMN `fecha_importacion` DATETIME NULL DEFAULT NULL AFTER `fecha_registro`");
                        $tieneFechaImportacion = true;
                    } catch (Exception $e) {
                        // Si falla (sin permisos / ya existe), se continua sin la columna
                        $tieneFechaImportacion = fedTieneColumna($pdo, $tablaAvances, 'fecha_importacion');
                    }
                }

                $modo = ($_POST['modo'] ?? 'actualizar') === 'reemplazar' ? 'reemplazar' : 'actualizar';
                $tInicio = microtime(true);

                // --- Parsear todas las lineas ---
                $lineas = preg_split('/\R/', $contenido);
                $idsValidos = array_map('intval', $pdo->query("SELECT id_indicador FROM `{$tablaIndicadores}`")->fetchAll(PDO::FETCH_COLUMN));
                $registros = [];
                $errores = [];
                $nLinea = 0;
                foreach ($lineas as $linea) {
                    $nLinea++;
                    if (trim($linea) === '') continue;
                    // Saltar una posible linea de cabecera
                    if (stripos($linea, 'renipress') !== false && stripos($linea, 'indicador') !== false) continue;
                    [$fila, $err] = fedParseLineaAvance($linea);
                    if ($fila === null) {
                        $errores[] = "Linea {$nLinea}: {$err}";
                        continue;
                    }
                    if (!in_array((int)$fila['id_indicador'], $idsValidos, true)) {
                        $errores[] = "Linea {$nLinea}: el id_indicador {$fila['id_indicador']} no esta registrado en la Gestion de Indicadores. Registrelo primero.";
                        continue;
                    }
                    $registros[] = $fila;
                }

                if (empty($registros)) {
                    $detalleMsjErr = implode('<br>', array_slice($errores, 0, 10));
                    throw new Exception('No se importo ningun registro. ' . ($detalleMsjErr !== '' ? 'Errores: <br>' . $detalleMsjErr : 'El archivo no contiene lineas validas.'));
                }

                // --- Escritura en BD (transaccion) ---
                // Reparacion preventiva: si la tabla de avances quedo sin PRIMARY
                // KEY / AUTO_INCREMENT (volcado antiguo con nombre singular), se
                // repara sola; si el hosting no permite ALTER, se inserta con id
                // manual (MAX + 1).
                $autoIncAvances = fedAsegurarAutoIncrement($pdo, $tablaAvances, 'id_avance');
                $horaImportacion = date('Y-m-d H:i:s');
                $insertados = 0;
                $actualizados = 0;
                $eliminados = 0;
                $duplicadosArchivo = 0;
                try {
                    $pdo->beginTransaction();

                    // Modo REEMPLAZAR: borra los periodos (indicador + anio + mes) que trae el archivo
                    if ($modo === 'reemplazar') {
                        $combos = [];
                        foreach ($registros as $r) {
                            $clave = $r['id_indicador'] . '|' . $r['anio'] . '|' . ($r['mes'] ?? '');
                            $combos[$clave] = [$r['id_indicador'], $r['anio'], $r['mes']];
                        }
                        $stDel = $pdo->prepare("DELETE FROM `{$tablaAvances}`
                                                WHERE id_indicador = ? AND anio = ?
                                                  AND ((mes IS NULL AND ? IS NULL) OR mes = ?)");
                        foreach ($combos as $p) {
                            $stDel->execute([$p[0], $p[1], $p[2], $p[2]]);
                            $eliminados += $stDel->rowCount();
                        }
                    }

                    $stSel = $pdo->prepare("SELECT id_avance FROM `{$tablaAvances}`
                                            WHERE id_indicador = ? AND renipress = ? AND anio = ?
                                              AND ((mes IS NULL AND ? IS NULL) OR mes = ?)");
                    if ($tieneFechaImportacion) {
                        $sqlIns = "INSERT INTO `{$tablaAvances}`
                            (" . (!$autoIncAvances ? "id_avance, " : "") . "id_indicador, renipress, establecimiento, anio, trimestre, mes,
                             valor_numerador, valor_denominador, porcentaje_avance, observaciones,
                             fecha_registro, fecha_importacion, usuario)
                            VALUES (" . (!$autoIncAvances ? "?, " : "") . "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stIns = $pdo->prepare($sqlIns);
                        $stUpd = $pdo->prepare("UPDATE `{$tablaAvances}`
                            SET establecimiento = ?, valor_numerador = ?, valor_denominador = ?,
                                porcentaje_avance = ?, observaciones = ?, trimestre = ?,
                                fecha_registro = ?, fecha_importacion = ?, usuario = ?
                            WHERE id_avance = ?");
                    } else {
                        $sqlIns = "INSERT INTO `{$tablaAvances}`
                            (" . (!$autoIncAvances ? "id_avance, " : "") . "id_indicador, renipress, establecimiento, anio, trimestre, mes,
                             valor_numerador, valor_denominador, porcentaje_avance, observaciones,
                             fecha_registro, usuario)
                            VALUES (" . (!$autoIncAvances ? "?, " : "") . "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stIns = $pdo->prepare($sqlIns);
                        $stUpd = $pdo->prepare("UPDATE `{$tablaAvances}`
                            SET establecimiento = ?, valor_numerador = ?, valor_denominador = ?,
                                porcentaje_avance = ?, observaciones = ?, trimestre = ?,
                                fecha_registro = ?, usuario = ?
                            WHERE id_avance = ?");
                    }

                    $idsEnArchivo = []; // para deduplicar claves repetidas dentro del mismo archivo
                    foreach ($registros as $r) {
                        $usuarioFinal = $r['usuario'] !== null && $r['usuario'] !== '' ? $r['usuario'] : ($_SESSION['usuario'] ?? 'sistema');
                        $fechaReg     = $r['fecha_registro'] !== null ? $r['fecha_registro'] : $horaImportacion;
                        $clave = $r['id_indicador'] . '|' . $r['renipress'] . '|' . $r['anio'] . '|' . ($r['mes'] ?? '');

                        $idExistente = $idsEnArchivo[$clave] ?? null;
                        if ($idExistente === null && $modo === 'actualizar') {
                            $stSel->execute([$r['id_indicador'], $r['renipress'], $r['anio'], $r['mes'], $r['mes']]);
                            $idExistente = $stSel->fetchColumn();
                            $idExistente = $idExistente !== false ? (int)$idExistente : null;
                        }

                        if ($idExistente !== null) {
                            // Ya existe -> ACTUALIZAR con los valores frescos del SQL Server
                            $esRepetidoEnArchivo = isset($idsEnArchivo[$clave]);
                            if ($tieneFechaImportacion) {
                                $stUpd->execute([$r['establecimiento'], $r['valor_numerador'], $r['valor_denominador'],
                                                 $r['porcentaje_avance'], $r['observaciones'], $r['trimestre'],
                                                 $fechaReg, $horaImportacion, $usuarioFinal, $idExistente]);
                            } else {
                                $stUpd->execute([$r['establecimiento'], $r['valor_numerador'], $r['valor_denominador'],
                                                 $r['porcentaje_avance'], $r['observaciones'], $r['trimestre'],
                                                 $fechaReg, $usuarioFinal, $idExistente]);
                            }
                            $idsEnArchivo[$clave] = (int)$idExistente;
                            $actualizados++;
                            if ($esRepetidoEnArchivo) $duplicadosArchivo++;
                        } else {
                            // No existe -> INSERTAR (el id_avance lo genera MySQL
                            // si la tabla tiene AUTO_INCREMENT; si no, se asigna
                            // manualmente como MAX(id_avance) + 1)
                            $idManual = null;
                            if (!$autoIncAvances) {
                                $idManual = (int)$pdo->query("SELECT COALESCE(MAX(id_avance), 0) + 1 FROM `{$tablaAvances}`")->fetchColumn();
                            }
                            if ($tieneFechaImportacion) {
                                $paramsIns = [$r['id_indicador'], $r['renipress'], $r['establecimiento'], $r['anio'],
                                              $r['trimestre'], $r['mes'], $r['valor_numerador'], $r['valor_denominador'],
                                              $r['porcentaje_avance'], $r['observaciones'], $fechaReg, $horaImportacion, $usuarioFinal];
                            } else {
                                $paramsIns = [$r['id_indicador'], $r['renipress'], $r['establecimiento'], $r['anio'],
                                              $r['trimestre'], $r['mes'], $r['valor_numerador'], $r['valor_denominador'],
                                              $r['porcentaje_avance'], $r['observaciones'], $fechaReg, $usuarioFinal];
                            }
                            if ($idManual !== null) { array_unshift($paramsIns, $idManual); }
                            $stIns->execute($paramsIns);
                            $idsEnArchivo[$clave] = ($idManual !== null) ? $idManual : (int)$pdo->lastInsertId();
                            $insertados++;
                        }
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }

                $duracion = round(microtime(true) - $tInicio, 2);
                $mensaje = "Importacion completada: <strong>{$insertados}</strong> insertados, <strong>{$actualizados}</strong> actualizados" .
                           ($modo === 'reemplazar' ? ", <strong>{$eliminados}</strong> eliminados (modo reemplazo)" : "") .
                           ", <strong>" . count($errores) . "</strong> lineas con error. Archivo: <strong>" . clean($nombreOriginal) . "</strong>." .
                           "<br>Fecha y hora de importacion: <strong>" . clean($horaImportacion) . "</strong> (ya visible para todos los usuarios).";
                $tipoMensaje = 'success';
                if (!empty($errores)) {
                    $detalleMensaje = '<strong>Lineas con error (primeras 10):</strong><br>' . implode('<br>', array_slice($errores, 0, 10)) .
                                      (count($errores) > 10 ? '<br>... y ' . (count($errores) - 10) . ' mas.' : '');
                }
                logImportacion([
                    'tipo_operacion'      => 'IMPORTACION',
                    'tipo_archivo'        => 'FED_TXT',
                    'nombre_archivo'      => $nombreOriginal,
                    'tabla_destino'       => $tablaAvances,
                    'registros_procesados'=> count($registros),
                    'modo_importacion'    => $modo,
                    'estado'              => 'EXITOSO',
                    'mensaje'             => "{$insertados} insertados, {$actualizados} actualizados, " . count($errores) . " errores",
                    'duracion_segundos'   => $duracion,
                ]);

            } else {
                throw new Exception('Accion no reconocida.');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getCode() === '23000') {
                $mensaje = 'Error de integridad: probablemente el codigo del indicador ya existe. Verifique e intente con otro codigo.';
            } else {
                $mensaje = 'Error de base de datos: ' . $e->getMessage();
            }
            $tipoMensaje = 'danger';
            if ($accion === 'fed_importar') {
                logImportacion([
                    'tipo_operacion' => 'IMPORTACION',
                    'tipo_archivo'   => 'FED_TXT',
                    'estado'         => 'FALLIDO',
                    'mensaje'        => $e->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $mensaje = $e->getMessage();
            $tipoMensaje = 'danger';
            if ($accion === 'fed_importar') {
                logImportacion([
                    'tipo_operacion' => 'IMPORTACION',
                    'tipo_archivo'   => 'FED_TXT',
                    'estado'         => 'FALLIDO',
                    'mensaje'        => $e->getMessage(),
                ]);
            }
        }
    }
}

/* ============================================================================
 * 4. FILTROS DE LA VISTA (GET)
 *    anio | mes ('' = todos los meses) | est (renipress) | ind (id_indicador)
 * ========================================================================== */

$fAnio = trim($_GET['anio'] ?? '');
$fMes  = trim($_GET['mes'] ?? '');
$fEst  = trim($_GET['est'] ?? '');
$fInd  = trim($_GET['ind'] ?? '');

$aniosDisp = [];
$establecimientosDisp = [];
$indicadoresFiltro = [];

if ($tablaAvances !== null) {
    $aniosDisp = $pdo->query("SELECT DISTINCT anio FROM `{$tablaAvances}` WHERE anio IS NOT NULL ORDER BY anio DESC")
                     ->fetchAll(PDO::FETCH_COLUMN);
}
if (empty($aniosDisp)) {
    $aniosDisp = [date('Y')];
}
if ($fAnio === '' || !in_array($fAnio, $aniosDisp, true)) {
    // Por defecto: el anio mas reciente con datos (o el anio actual)
    $fAnio = in_array((string)date('Y'), $aniosDisp, true) ? (string)date('Y') : (string)$aniosDisp[0];
}

if ($tablaAvances !== null) {
    $establecimientosDisp = $pdo->query("SELECT renipress, establecimiento FROM `{$tablaAvances}`
                                         GROUP BY renipress, establecimiento ORDER BY establecimiento ASC")
                                  ->fetchAll();
}
if ($tablaIndicadores !== null) {
    $indicadoresFiltro = $pdo->query("SELECT id_indicador, codigo, nombre, estado FROM `{$tablaIndicadores}`
                                      ORDER BY orden ASC, codigo ASC")->fetchAll();
}
// Validar id de indicador recibido
if ($fInd !== '' && !ctype_digit($fInd)) {
    $fInd = '';
}

/* ============================================================================
 * 5. CONSULTAS DE DATOS PARA LA VISUALIZACION
 * ========================================================================== */

$avances = [];            // detalle (filas de la tabla principal)
$resumenIndicadores = []; // agregado por indicador
$resumenEstablecimientos = []; // agregado por establecimiento (calculado en PHP)
$ultimaActualizacion = null;
$avanceGlobalPonderado = null;
$pesoTotal = 0.0;

if ($sistemaOK) {
    // --- 5.1 Ultima actualizacion de datos (fecha/hora de importacion) ---
    $colUltima = $tieneFechaImportacion
        ? 'MAX(COALESCE(fecha_importacion, fecha_registro))'
        : 'MAX(fecha_registro)';
    $ultimaActualizacion = $pdo->query("SELECT {$colUltima} FROM `{$tablaAvances}`")->fetchColumn();

    // --- 5.2 Detalle de avances con filtros ---
    $colFI = $tieneFechaImportacion ? ', a.fecha_importacion' : ', NULL AS fecha_importacion';
    $sql = "SELECT a.id_avance, a.id_indicador, a.renipress, a.establecimiento, a.anio, a.mes,
                   a.valor_numerador, a.valor_denominador, a.porcentaje_avance, a.observaciones,
                   a.fecha_registro {$colFI},
                   i.codigo, i.nombre AS indicador_nombre
            FROM `{$tablaAvances}` a
            LEFT JOIN `{$tablaIndicadores}` i ON i.id_indicador = a.id_indicador
            WHERE 1=1";
    $params = [];
    if ($fAnio !== '') { $sql .= " AND a.anio = ?";            $params[] = $fAnio; }
    if ($fMes  !== '') { $sql .= " AND a.mes = ?";             $params[] = $fMes; }
    if ($fEst  !== '') { $sql .= " AND a.renipress = ?";       $params[] = $fEst; }
    if ($fInd  !== '') { $sql .= " AND a.id_indicador = ?";    $params[] = (int)$fInd; }
    $sql .= " ORDER BY a.establecimiento ASC, i.codigo ASC, a.mes ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $avances = $st->fetchAll();

    // --- 5.3 Resumen agregado por indicador (mismos filtros) ---
    $conInd = [];
    $paramsInd = [];
    if ($fAnio !== '') { $conInd[] = "a.anio = ?";         $paramsInd[] = $fAnio; }
    if ($fMes  !== '') { $conInd[] = "a.mes = ?";          $paramsInd[] = $fMes; }
    if ($fEst  !== '') { $conInd[] = "a.renipress = ?";    $paramsInd[] = $fEst; }
    if ($fInd  !== '') { $conInd[] = "a.id_indicador = ?"; $paramsInd[] = (int)$fInd; }

    $sqlInd = "SELECT i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                      i.meta_anual, i.peso_ponderado, i.periodo, i.responsable, i.estado, i.orden,
                      COUNT(a.id_avance) AS n_reg,
                      SUM(a.valor_numerador) AS sum_num,
                      SUM(a.valor_denominador) AS sum_den,
                      AVG(a.porcentaje_avance) AS pct_prom
               FROM `{$tablaIndicadores}` i
               LEFT JOIN `{$tablaAvances}` a ON a.id_indicador = i.id_indicador"
               . (!empty($conInd) ? ' AND ' . implode(' AND ', $conInd) : '') . "
               WHERE i.estado = 1" . ($fInd !== '' ? " AND i.id_indicador = ?" : "") . "
               GROUP BY i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                        i.meta_anual, i.peso_ponderado, i.periodo, i.responsable, i.estado, i.orden
               ORDER BY i.orden ASC, i.codigo ASC";
    $stInd = $pdo->prepare($sqlInd);
    $stInd->execute($fInd !== '' ? array_merge($paramsInd, [(int)$fInd]) : $paramsInd);
    $resumenIndicadores = $stInd->fetchAll();

    // Avance global ponderado del FED (según filtros aplicados)
    foreach ($resumenIndicadores as $ri) {
        $pctCalc = ((float)$ri['sum_den'] > 0) ? round(((float)$ri['sum_num'] / (float)$ri['sum_den']) * 100, 2) : null;
        $pct = $pctCalc !== null ? $pctCalc : ($ri['pct_prom'] !== null ? (float)$ri['pct_prom'] : null);
        $peso = $ri['peso_ponderado'] !== null ? (float)$ri['peso_ponderado'] : 0;
        if ($pct !== null && $ri['n_reg'] > 0) {
            $avanceGlobalPonderado = ($avanceGlobalPonderado ?? 0) + ($pct * $peso / 100);
            $pesoTotal += $peso;
        }
    }
    if ($pesoTotal > 0 && $avanceGlobalPonderado !== null) {
        $avanceGlobalPonderado = round(($avanceGlobalPonderado / $pesoTotal) * 100, 1);
    } else {
        $avanceGlobalPonderado = null;
    }

    // --- 5.4 Resumen agregado por establecimiento (PHP, a partir del detalle) ---
    $tmpEst = [];
    foreach ($avances as $a) {
        $key = $a['renipress'];
        if (!isset($tmpEst[$key])) {
            $tmpEst[$key] = ['renipress' => $a['renipress'], 'establecimiento' => $a['establecimiento'],
                             'n' => 0, 'suma' => 0.0];
        }
        $tmpEst[$key]['n']++;
        $tmpEst[$key]['suma'] += (float)$a['porcentaje_avance'];
    }
    foreach ($tmpEst as $t) {
        $t['pct_prom'] = $t['n'] > 0 ? round($t['suma'] / $t['n'], 2) : 0;
        $resumenEstablecimientos[] = $t;
    }
    // Peores primero: para el seguimiento diario lo urgente va arriba
    usort($resumenEstablecimientos, function ($x, $y) { return $x['pct_prom'] <=> $y['pct_prom']; });
}

/* ============================================================================
 * 6. LISTA COMPLETA DE INDICADORES PARA EL PANEL ADMIN (incluye inactivos)
 * ========================================================================== */
$indicadoresAdmin = [];
if ($tablaIndicadores !== null) {
    $indicadoresAdmin = $pdo->query("SELECT * FROM `{$tablaIndicadores}` ORDER BY orden ASC, codigo ASC")->fetchAll();
}

// Ultimas importaciones FED (auditoria, si existe la tabla de log)
$ultimasImportaciones = [];
if ($tablaAvances !== null) {
    try {
        $stLog = $pdo->query("SELECT nombre_archivo, registros_procesados, modo_importacion, fecha_operacion, usuario, estado
                              FROM LOG_IMPORTACION WHERE tipo_archivo = 'FED_TXT'
                              ORDER BY fecha_operacion DESC LIMIT 5");
        $ultimasImportaciones = $stLog->fetchAll();
    } catch (Exception $e) {
        $ultimasImportaciones = []; // la tabla de log no existe: se omite silenciosamente
    }
}

function clasificarAvanceFED($pct) {
    if ($pct === null) return ['clase' => 'avance-sin-dato', 'texto' => 'Sin dato', 'estado' => ''];
    if ($pct >= 90) return ['clase' => 'avance-excelente', 'texto' => 'Excelente', 'estado' => 'estado-excelente'];
    if ($pct >= 75) return ['clase' => 'avance-bueno', 'texto' => 'Bueno', 'estado' => 'estado-bueno'];
    if ($pct >= 60) return ['clase' => 'avance-regular', 'texto' => 'Regular', 'estado' => 'estado-regular'];
    if ($pct >= 40) return ['clase' => 'avance-bajo', 'texto' => 'Bajo', 'estado' => 'estado-bajo'];
    return ['clase' => 'avance-critico', 'texto' => 'Critico', 'estado' => 'estado-critico'];
}

function colorBarraFED($pct) {
    if ($pct === null) return 'bg-secondary';
    if ($pct >= 90) return 'bg-success';
    if ($pct >= 75) return 'bg-info';
    if ($pct >= 60) return 'bg-warning';
    if ($pct >= 40) return 'bg-orange';
    return 'bg-danger';
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Convenio FED - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos del modulo Convenio FED v3 ---- */
.fed-table-detalle th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.fed-table-detalle th.sortable:hover { background: #e9ecef; }
.fed-table-detalle th.sortable::after { content: "\f0dc"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: .68rem; margin-left: 5px; color: #adb5bd; }
.fed-table-detalle th.sortable.sort-asc::after { content: "\f0de"; color: #0d6efd; }
.fed-table-detalle th.sortable.sort-desc::after { content: "\f0dd"; color: #0d6efd; }
.fed-num { font-variant-numeric: tabular-nums; }
.fed-barra { height: 7px; border-radius: 4px; }
.fed-est-name { font-weight: 600; }
.fed-info-import { background: #eef6ff; border: 1px solid #cfe5ff; border-radius: 8px; }
.fed-formato { background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px; font-size: .8rem; }
.fed-formato code { background: #e9ecef; padding: 1px 5px; border-radius: 4px; }
.fed-tabla-admin td { vertical-align: middle; }
</style>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-medal me-2"></i>Convenio FED - Fondo de Estimulo al Desempeno</h4>
            <p class="subtitle">Red de Salud Perené - Seguimiento del avance de los indicadores FED por establecimiento y mes.</p>
        </div>
        <?php if ($esAdmin && $sistemaOK): ?>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-his btn-sm" onclick="irATab('gestion')">
                <i class="fas fa-tasks me-1"></i> Gestion de Indicadores
            </button>
            <button type="button" class="btn btn-warning btn-sm text-dark fw-semibold" onclick="irATab('importar')">
                <i class="fas fa-file-import me-1"></i> Importar Resultados.txt
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
    <i class="fas <?= $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
    <?= $mensaje ?>
    <?php if ($detalleMensaje !== ''): ?>
        <hr>
        <div class="small"><?= $detalleMensaje ?></div>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$sistemaOK): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php if ($tablaIndicadores === null): ?>
            La tabla <code>CONVENIO_FED_INDICADORES</code> no existe en la base de datos.
        <?php else: ?>
            La tabla <code>CONVENIO_FED_AVANCES</code> no existe en la base de datos.
        <?php endif; ?>
        Ejecute el script <code>Database/update_fed_v3.sql</code> desde phpMyAdmin para crear las tablas
        del Convenio FED y la columna de fecha de importacion.
    </div>
<?php endif; ?>

<?php if ($sistemaOK): ?>

<!-- ===================== FILTROS ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros de Consulta</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="convenio_fed.php" class="row g-2 align-items-end" id="formFiltros">
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-0">Anio</label>
                <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($aniosDisp as $a): ?>
                        <option value="<?= clean($a) ?>" <?= (string)$fAnio === (string)$a ? 'selected' : '' ?>><?= clean($a) ?></option>
                    <?php endforeach; ?>
                    <?php if (!in_array((string)date('Y'), array_map('strval', $aniosDisp), true)): ?>
                        <option value="<?= date('Y') ?>" <?= (string)$fAnio === (string)date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-0">Mes</label>
                <select name="mes" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los meses --</option>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $mPad = str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $mPad ?>" <?= $fMes === $mPad ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <label class="form-label small fw-semibold mb-0">Establecimiento</label>
                <select name="est" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los establecimientos (<?= count($establecimientosDisp) ?>) --</option>
                    <?php foreach ($establecimientosDisp as $e): ?>
                        <option value="<?= clean($e['renipress']) ?>" <?= $fEst === (string)$e['renipress'] ? 'selected' : '' ?>>
                            <?= clean($e['establecimiento']) ?> (<?= clean($e['renipress']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label small fw-semibold mb-0">Indicador</label>
                <select name="ind" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los indicadores --</option>
                    <?php foreach ($indicadoresFiltro as $if): ?>
                        <option value="<?= (int)$if['id_indicador'] ?>" <?= $fInd === (string)$if['id_indicador'] ? 'selected' : '' ?>>
                            <?= clean($if['codigo']) ?> - <?= clean(mb_strimwidth($if['nombre'], 0, 55, '...')) ?><?= (int)$if['estado'] === 0 ? ' (inactivo)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1 col-12">
                <button type="submit" class="btn btn-his btn-sm w-100" title="Consultar"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== AVISO DE ACTUALIZACION DE DATOS ===================== -->
<div class="fed-info-import d-flex align-items-center gap-2 px-3 py-2 mb-4">
    <i class="fas fa-clock text-primary fs-5"></i>
    <div class="small">
        <strong>Datos actualizados al:</strong>
        <?php if ($ultimaActualizacion): ?>
            <strong class="text-primary"><?= formatDateTime($ultimaActualizacion) ?></strong>
            (fecha y hora de la ultima importacion desde el SQL Server)
        <?php else: ?>
            <span class="text-muted">Aun no se ha importado informacion de avances.</span>
        <?php endif; ?>
        <span class="text-muted d-block d-md-inline">Fuente: base SQL Server local &rarr; importacion .txt &rarr; sitio web.</span>
    </div>
</div>

<!-- ===================== RESUMEN RAPIDO ===================== -->
<?php
$nRegistros = count($avances);
$nEstablecimientos = count($resumenEstablecimientos);
$nIndicadoresConDato = count(array_unique(array_map(function ($a) { return $a['id_indicador']; }, $avances)));
$pctPromedioGeneral = $nRegistros > 0 ? round(array_sum(array_map(function ($a) { return (float)$a['porcentaje_avance']; }, $avances)) / $nRegistros, 1) : null;
$avClasifProm = clasificarAvanceFED($pctPromedioGeneral);
?>
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($nRegistros) ?></span>
                <span class="stat-label">Registros visibles</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-hospital"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($nEstablecimientos) ?></span>
                <span class="stat-label">Establecimientos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($nIndicadoresConDato) ?></span>
                <span class="stat-label">Indicadores con datos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card <?= $pctPromedioGeneral === null ? 'stat-warning' : ($pctPromedioGeneral >= 75 ? 'stat-success' : 'stat-warning') ?>">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $pctPromedioGeneral !== null ? number_format($pctPromedioGeneral, 1) . '%' : '-' ?></span>
                <span class="stat-label">Avance promedio (filtros)</span>
            </div>
        </div>
    </div>
</div>

<!-- ===================== AVANCE GLOBAL PONDERADO ===================== -->
<?php if ($avanceGlobalPonderado !== null): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Avance Global del Convenio FED (ponderado)</h6>
            <span class="avance-badge <?= clasificarAvanceFED($avanceGlobalPonderado)['clase'] ?>">
                <?= number_format($avanceGlobalPonderado, 1) ?>% &middot; <?= clasificarAvanceFED($avanceGlobalPonderado)['texto'] ?>
            </span>
        </div>
        <div class="progress" style="height: 22px; border-radius: 11px;">
            <div class="progress-bar <?= colorBarraFED($avanceGlobalPonderado) ?>"
                 role="progressbar" style="width: <?= min(100, $avanceGlobalPonderado) ?>%;"
                 aria-valuenow="<?= $avanceGlobalPonderado ?>" aria-valuemin="0" aria-valuemax="100">
                <?= number_format($avanceGlobalPonderado, 1) ?>%
            </div>
        </div>
        <small class="text-muted">Calculado con los pesos de cada indicador y los filtros seleccionados (<?= number_format($pesoTotal, 1) ?>% de peso con datos).</small>
    </div>
</div>
<?php endif; ?>

<!-- ===================== DETALLE DE AVANCES (tabla interactiva) ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Detalle de Avances por Establecimiento y Mes</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="stat-mini" id="contadorDetalle"></span>
            <div class="input-group input-group-sm" style="width: 230px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="busquedaDetalle" class="form-control" placeholder="Buscar en la tabla...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-success" id="btnExportarCsv" title="Exportar lo que se muestra a CSV (Excel)">
                <i class="fas fa-file-csv me-1"></i> Exportar CSV
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="px-3 pt-2">
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Haga clic en el encabezado de una columna para ordenar. La busqueda filtra en vivo.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 fed-table-detalle" id="tablaDetalle">
                <thead class="table-light">
                    <tr>
                        <th class="sortable" data-col="0" data-type="num" data-csv="renipress">Renipress</th>
                        <th class="sortable" data-col="1" data-type="str" data-csv="establecimiento">Establecimiento</th>
                        <th class="sortable" data-col="2" data-type="num" data-csv="anio">Anio</th>
                        <th class="sortable" data-col="3" data-type="str" data-csv="mes">Mes</th>
                        <th class="sortable text-end" data-col="4" data-type="num" data-csv="valor_numerador">Valor Numerador</th>
                        <th class="sortable text-end" data-col="5" data-type="num" data-csv="valor_denominador">Valor Denominador</th>
                        <th class="sortable" data-col="6" data-type="num" data-csv="porcentaje_avance">Porcentaje Avance</th>
                        <th class="sortable" data-col="7" data-type="str" data-csv="observaciones">Observaciones</th>
                        <th class="sortable" data-col="8" data-type="str" data-csv="indicador">Indicador</th>
                        <th class="sortable" data-col="9" data-type="str" data-csv="fecha_importacion">Fecha Importacion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avances)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                No hay registros de avances para los filtros seleccionados.
                                <?php if ($esAdmin): ?>Utilice el modulo <strong>Importar Resultados.txt</strong> para cargar los datos.<?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avances as $a):
                            $pct  = $a['porcentaje_avance'] !== null ? (float)$a['porcentaje_avance'] : null;
                            $av   = clasificarAvanceFED($pct);
                            $mesN = $a['mes'] !== null ? (int)$a['mes'] : null;
                            $mesTxt = $a['mes'] !== null ? $a['mes'] . ' &middot; ' . getNombreMes($mesN) : '-';
                            $fechaImp = $a['fecha_importacion'] ?? $a['fecha_registro'];
                        ?>
                        <tr data-sortable="1">
                            <td data-sort="<?= clean($a['renipress']) ?>"><code><?= clean($a['renipress']) ?></code></td>
                            <td class="fed-est-name" data-sort="<?= clean($a['establecimiento']) ?>"><?= clean($a['establecimiento']) ?></td>
                            <td data-sort="<?= clean($a['anio']) ?>"><?= clean($a['anio']) ?></td>
                            <td data-sort="<?= clean($a['mes'] ?? '') ?>" data-csv="<?= $a['mes'] !== null ? clean($a['mes']) . ' - ' . clean(getNombreMes($mesN)) : '' ?>"><?= $mesTxt ?></td>
                            <td class="text-end fed-num" data-sort="<?= (float)$a['valor_numerador'] ?>" data-csv="<?= number_format((float)$a['valor_numerador'], 2, '.', '') ?>"><?= number_format((float)$a['valor_numerador'], 2) ?></td>
                            <td class="text-end fed-num" data-sort="<?= (float)$a['valor_denominador'] ?>" data-csv="<?= number_format((float)$a['valor_denominador'], 2, '.', '') ?>"><?= number_format((float)$a['valor_denominador'], 2) ?></td>
                            <td data-sort="<?= $pct ?? -1 ?>" data-csv="<?= $pct !== null ? number_format($pct, 2, '.', '') : '' ?>">
                                <?php if ($pct !== null): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avance-badge <?= $av['clase'] ?>" style="min-width:64px;"><?= number_format($pct, 1) ?>%</span>
                                        <div class="progress progress-his flex-grow-1 fed-barra">
                                            <div class="progress-bar <?= colorBarraFED($pct) ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Sin dato</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= clean($a['observaciones'] ?? '') ?>" data-csv="<?= clean($a['observaciones'] ?? '') ?>">
                                <?php if (!empty($a['observaciones'])): ?>
                                    <small title="<?= clean($a['observaciones']) ?>"><?= clean(mb_strimwidth($a['observaciones'], 0, 40, '...')) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= clean($a['codigo'] ?? '') ?>" data-csv="<?= clean(($a['codigo'] ?? '')) ?> <?= clean($a['indicador_nombre'] ?? '') ?>">
                                <?php if (!empty($a['codigo'])): ?>
                                    <span class="badge bg-light text-dark border" title="<?= clean($a['indicador_nombre'] ?? '') ?>"><?= clean($a['codigo']) ?></span>
                                    <small class="text-muted d-none d-xl-inline"><?= clean(mb_strimwidth($a['indicador_nombre'] ?? '', 0, 30, '...')) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">N/D</small>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= clean($fechaImp ?? '') ?>" data-csv="<?= clean($fechaImp ?? '') ?>">
                                <?php if ($fechaImp): ?>
                                    <small><i class="far fa-clock text-muted me-1"></i><?= formatDateTime($fechaImp) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== RESUMEN POR INDICADOR ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-bullseye me-2 text-primary"></i>Reporte de Avance por Indicador FED</h6>
        <span class="stat-mini">Acumulado segun los filtros seleccionados</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">Codigo</th>
                        <th>Indicador</th>
                        <th style="width:90px;">Unidad</th>
                        <th style="width:90px;" class="text-end">Meta</th>
                        <th style="width:80px;" class="text-end">Registros</th>
                        <th style="width:110px;" class="text-end">Numerador</th>
                        <th style="width:110px;" class="text-end">Denominador</th>
                        <th style="width:100px;" class="text-end">Avance</th>
                        <th style="width:170px;">Barra de Avance</th>
                        <th style="width:100px;">Estado</th>
                        <th style="width:130px;">Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resumenIndicadores)): ?>
                        <tr><td colspan="11" class="text-center text-muted py-4">No hay indicadores activos registrados. <?php if ($esAdmin): ?>Agreguelos en <strong>Gestion de Indicadores</strong>.<?php endif; ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($resumenIndicadores as $ri):
                            $nReg = (int)$ri['n_reg'];
                            $sumNum = $ri['sum_num'] !== null ? (float)$ri['sum_num'] : null;
                            $sumDen = $ri['sum_den'] !== null ? (float)$ri['sum_den'] : null;
                            $pctCalc = ($sumDen !== null && $sumDen > 0) ? round(($sumNum / $sumDen) * 100, 1) : null;
                            $pct = $pctCalc !== null ? $pctCalc : ($ri['pct_prom'] !== null ? round((float)$ri['pct_prom'], 1) : null);
                            $av = clasificarAvanceFED($pct);
                        ?>
                        <tr>
                            <td><code><?= clean($ri['codigo']) ?></code></td>
                            <td title="<?= clean($ri['descripcion'] ?? '') ?>">
                                <strong><?= clean($ri['nombre']) ?></strong>
                                <?php if (!empty($ri['descripcion'])): ?>
                                    <small class="d-block text-muted" style="font-size:0.74rem;"><?= clean(mb_strimwidth($ri['descripcion'], 0, 80, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= clean($ri['unidad_medida'] ?? '-') ?></small></td>
                            <td class="text-end"><?= $ri['meta_anual'] !== null ? number_format((float)$ri['meta_anual'], 2) : '-' ?></td>
                            <td class="text-end"><?= $nReg ?></td>
                            <td class="text-end fed-num"><?= $sumNum !== null ? number_format($sumNum, 2) : '-' ?></td>
                            <td class="text-end fed-num"><?= $sumDen !== null ? number_format($sumDen, 2) : '-' ?></td>
                            <td class="text-end">
                                <?php if ($pct !== null): ?><strong><?= number_format($pct, 1) ?>%</strong>
                                <?php else: ?><span class="text-muted">Sin dato</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pct !== null): ?>
                                    <div class="progress progress-his" style="height:8px;">
                                        <div class="progress-bar <?= colorBarraFED($pct) ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="avance-badge <?= $av['clase'] ?>"><?= $av['texto'] ?></span></td>
                            <td><small><?= clean($ri['responsable'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== RESUMEN POR ESTABLECIMIENTO ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-hospital me-2 text-primary"></i>Avance Promedio por Establecimiento</h6>
        <span class="stat-mini">Los que requieren atencion aparecen primero</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:100px;">Renipress</th>
                        <th>Establecimiento</th>
                        <th style="width:90px;" class="text-end">Registros</th>
                        <th style="width:110px;" class="text-end">Avance Promedio</th>
                        <th style="width:220px;">Barra de Avance</th>
                        <th style="width:110px;">Estado</th>
                        <th style="width:120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resumenEstablecimientos)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay datos de establecimientos para los filtros seleccionados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($resumenEstablecimientos as $re):
                            $pctE = (float)$re['pct_prom'];
                            $avE = clasificarAvanceFED($pctE);
                            $urlDetalle = 'convenio_fed.php?' . http_build_query([
                                'anio' => $fAnio,
                                'mes'  => $fMes,
                                'est'  => $re['renipress'],
                                'ind'  => $fInd,
                            ]);
                        ?>
                        <tr>
                            <td><code><?= clean($re['renipress']) ?></code></td>
                            <td class="fed-est-name"><?= clean($re['establecimiento']) ?></td>
                            <td class="text-end"><?= (int)$re['n'] ?></td>
                            <td class="text-end"><strong><?= number_format($pctE, 1) ?>%</strong></td>
                            <td>
                                <div class="progress progress-his" style="height:8px;">
                                    <div class="progress-bar <?= colorBarraFED($pctE) ?>" role="progressbar" style="width: <?= min(100, $pctE) ?>%;"></div>
                                </div>
                            </td>
                            <td><span class="avance-badge <?= $avE['clase'] ?>"><?= $avE['texto'] ?></span></td>
                            <td class="text-end">
                                <a href="<?= clean($urlDetalle) ?>" class="btn btn-xs btn-outline-primary" title="Ver solo este establecimiento">
                                    <i class="fas fa-search-plus me-1"></i>Detalle
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== VISTA DE TARJETAS POR INDICADOR ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-th-large me-2 text-primary"></i>Vista de Tarjetas por Indicador FED</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if (empty($resumenIndicadores)): ?>
                <div class="col-12 text-center text-muted py-3">No hay indicadores activos para mostrar.</div>
            <?php else: ?>
                <?php foreach ($resumenIndicadores as $ri):
                    $sumNumC = $ri['sum_num'] !== null ? (float)$ri['sum_num'] : 0;
                    $sumDenC = $ri['sum_den'] !== null ? (float)$ri['sum_den'] : 0;
                    $pctCard = $sumDenC > 0 ? round(($sumNumC / $sumDenC) * 100, 1) : ($ri['pct_prom'] !== null ? round((float)$ri['pct_prom'], 1) : null);
                    $avCard = clasificarAvanceFED($pctCard);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="indicador-card <?= $avCard['estado'] ?>">
                        <span class="indicador-codigo"><?= clean($ri['codigo']) ?> &middot; Peso <?= $ri['peso_ponderado'] !== null ? number_format((float)$ri['peso_ponderado'], 1) . '%' : 'N/D' ?></span>
                        <div class="indicador-nombre"><?= clean($ri['nombre']) ?></div>
                        <div class="indicador-meta">
                            <i class="fas fa-bullseye me-1"></i> Meta: <strong><?= $ri['meta_anual'] !== null ? number_format((float)$ri['meta_anual'], 2) : '-' ?></strong>
                            &middot; <i class="fas fa-ruler me-1"></i> <?= clean($ri['unidad_medida'] ?? '-') ?>
                        </div>
                        <?php if ($pctCard !== null): ?>
                            <div class="progress progress-his mb-2">
                                <div class="progress-bar <?= colorBarraFED($pctCard) ?>" role="progressbar" style="width: <?= min(100, $pctCard) ?>%;"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><?= number_format((int)$ri['n_reg']) ?> registro(s)</small>
                                <span class="avance-badge <?= $avCard['clase'] ?>"><?= number_format($pctCard, 1) ?>% &middot; <?= $avCard['texto'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-2">
                                <span class="avance-badge avance-sin-dato"><i class="fas fa-minus-circle me-1"></i>Sin registro de avance</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; /* fin $sistemaOK */ ?>

<!-- =====================================================================
     PANEL DE ADMINISTRACION (visible SOLO para el rol Administrador)
     Pestaña 1: Gestion de Indicadores  |  Pestaña 2: Importar Avances
     =================================================================== -->
<?php if ($esAdmin && $puedeGestionar): ?>
<div class="card shadow-sm mb-4 border-warning" id="panelAdmin">
    <div class="card-header bg-white">
        <ul class="nav nav-pills card-header-pills" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="btn-tab-gestion" type="button" role="tab"
                        data-bs-toggle="pill" data-bs-target="#tab-gestion" aria-selected="true">
                    <i class="fas fa-tasks me-1"></i> Gestion de Indicadores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="btn-tab-importar" type="button" role="tab"
                        data-bs-toggle="pill" data-bs-target="#tab-importar" aria-selected="false">
                    <i class="fas fa-file-import me-1"></i> Importar Avances (Resultados.txt)
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            <!-- ============ PESTAÑA 1: GESTION DE INDICADORES ============ -->
            <div class="tab-pane fade show active" id="tab-gestion" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">Indicadores del Convenio FED</h6>
                        <small class="text-muted">Agregue, modifique o desactive los indicadores. Los indicadores pueden variar cada anio: los desactivados conservan su historial.</small>
                    </div>
                    <button type="button" class="btn btn-his btn-sm" id="btnNuevoIndicador" data-bs-toggle="modal" data-bs-target="#modalIndicador">
                        <i class="fas fa-plus me-1"></i> Nuevo Indicador
                    </button>
                </div>

                <?php if (empty($indicadoresAdmin)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h5>Sin indicadores</h5>
                        <p>Aun no hay indicadores FED registrados. Use el boton <strong>Nuevo Indicador</strong> para agregar el primero.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 fed-tabla-admin">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;">ID</th>
                                    <th style="width:90px;">Codigo</th>
                                    <th>Nombre</th>
                                    <th style="width:80px;">Unidad</th>
                                    <th style="width:80px;" class="text-end">Meta</th>
                                    <th style="width:70px;" class="text-end">Peso</th>
                                    <th style="width:105px;">Periodo</th>
                                    <th style="width:120px;">Responsable</th>
                                    <th style="width:60px;" class="text-end">Orden</th>
                                    <th style="width:95px;">Estado</th>
                                    <th style="width:150px;" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($indicadoresAdmin as $ia): ?>
                                <tr>
                                    <td class="text-muted"><?= (int)$ia['id_indicador'] ?></td>
                                    <td><code><?= clean($ia['codigo']) ?></code></td>
                                    <td>
                                        <strong><?= clean($ia['nombre']) ?></strong>
                                        <?php if (!empty($ia['descripcion'])): ?>
                                            <small class="d-block text-muted" style="font-size:.72rem;" title="<?= clean($ia['descripcion']) ?>"><?= clean(mb_strimwidth($ia['descripcion'], 0, 70, '...')) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= clean($ia['unidad_medida'] ?? '-') ?></small></td>
                                    <td class="text-end"><?= $ia['meta_anual'] !== null ? number_format((float)$ia['meta_anual'], 2) : '-' ?></td>
                                    <td class="text-end"><?= $ia['peso_ponderado'] !== null ? number_format((float)$ia['peso_ponderado'], 1) . '%' : '-' ?></td>
                                    <td><small><?= clean($ia['periodo'] ?? '-') ?></small></td>
                                    <td><small><?= clean($ia['responsable'] ?? '-') ?></small></td>
                                    <td class="text-end"><?= (int)$ia['orden'] ?></td>
                                    <td>
                                        <?php if ((int)$ia['estado'] === 1): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-xs btn-outline-primary btn-editar-indicador"
                                                data-indicador='<?= clean(json_encode([
                                                    'id_indicador'   => (int)$ia['id_indicador'],
                                                    'codigo'         => $ia['codigo'],
                                                    'nombre'         => $ia['nombre'],
                                                    'descripcion'    => $ia['descripcion'],
                                                    'unidad_medida'  => $ia['unidad_medida'],
                                                    'meta_anual'     => $ia['meta_anual'],
                                                    'peso_ponderado' => $ia['peso_ponderado'],
                                                    'periodo'        => $ia['periodo'],
                                                    'responsable'    => $ia['responsable'],
                                                    'orden'          => (int)$ia['orden'],
                                                    'estado'         => (int)$ia['estado'],
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'
                                                title="Modificar indicador">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <form method="POST" action="convenio_fed.php" class="d-inline form-toggle-indicador"
                                              data-confirm="<?= (int)$ia['estado'] === 1
                                                  ? 'Desactivar el indicador ' . clean($ia['codigo']) . '? No se eliminara y conservara su historial, pero dejara de mostrarse en el reporte.'
                                                  : 'Activar el indicador ' . clean($ia['codigo']) . '?' ?>">
                                            <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
                                            <input type="hidden" name="accion" value="fed_toggle_indicador">
                                            <input type="hidden" name="id_indicador" value="<?= (int)$ia['id_indicador'] ?>">
                                            <button type="submit" class="btn btn-xs <?= (int)$ia['estado'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                    title="<?= (int)$ia['estado'] === 1 ? 'Desactivar' : 'Activar' ?>">
                                                <i class="fas <?= (int)$ia['estado'] === 1 ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i>
                                                <?= (int)$ia['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ============ PESTAÑA 2: IMPORTAR AVANCES ============ -->
            <div class="tab-pane fade" id="tab-importar" role="tabpanel">
                <?php if (!$puedeImportar): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        La tabla <code>CONVENIO_FED_AVANCES</code> no existe. Ejecute <code>Database/update_fed_v3.sql</code> para habilitar la importacion.
                    </div>
                <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-2"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Importar archivo de avances</h6>
                        <p class="small text-muted mb-3">
                            Importe el archivo <code>Resultados.txt</code> exportado de su base SQL Server local.
                            Si un registro ya existe (mismo indicador + renipress + anio + mes) se <strong>actualiza</strong>;
                            si es nuevo se <strong>agrega</strong>. El sistema registra la fecha y hora de cada importacion.
                        </p>
                        <form method="POST" action="convenio_fed.php" enctype="multipart/form-data" id="formImportar" class="card border-0 bg-light p-3">
                            <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
                            <input type="hidden" name="accion" value="fed_importar">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Archivo de avances (.txt / .csv, max. <?= round(MAX_FILE_SIZE / 1048576) ?> MB)</label>
                                <input type="file" class="form-control" name="archivo_txt" id="archivo_txt" accept=".txt,.csv,.out" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Modo de importacion</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modo" id="modoActualizar" value="actualizar" checked>
                                    <label class="form-check-label small" for="modoActualizar">
                                        <strong>Actualizar</strong> (recomendado): inserta los nuevos y actualiza los existentes.
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modo" id="modoReemplazar" value="reemplazar">
                                    <label class="form-check-label small" for="modoReemplazar">
                                        <strong>Reemplazar periodo</strong>: elimina los registros de los periodos (indicador + anio + mes) que trae el archivo y los recarga. Use cuando haya quitado establecimientos o meses en el SQL Server.
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning text-dark fw-semibold">
                                <i class="fas fa-file-import me-1"></i> Importar Ahora
                            </button>
                        </form>
                    </div>

                    <div class="col-lg-5">
                        <h6 class="fw-bold mb-2"><i class="fas fa-code me-2 text-primary"></i>Formato esperado del archivo</h6>
                        <div class="fed-formato p-3 mb-3">
                            <div class="mb-2">13 columnas separadas por <code>;</code> (punto y coma):</div>
                            <code class="d-block mb-2" style="font-size:.72rem; word-break:break-all;">
                                id_avance; id_indicador; renipress; establecimiento; anio; trimestre; mes; valor_numerador; valor_denominador; porcentaje_avance; observaciones; fecha_registro; usuario
                            </code>
                            <div class="mb-1"><i class="fas fa-check text-success me-1"></i>La columna <code>id_avance</code> se ignora: la web asigna su propio ID.</div>
                            <div class="mb-1"><i class="fas fa-check text-success me-1"></i><code>id_indicador</code> debe existir en la Gestion de Indicadores.</div>
                            <div class="mb-1"><i class="fas fa-check text-success me-1"></i>El texto <code>NULL</code> o vacio se guarda como NULL.</div>
                            <div class="mb-1"><i class="fas fa-check text-success me-1"></i>Fecha en formato <code>YYYY-MM-DD HH:MM:SS</code> (los milisegundos se ignoran).</div>
                            <div><i class="fas fa-check text-success me-1"></i>Codificacion UTF-8 o ANSI. Si no trae <code>porcentaje_avance</code> se recalcula (num/den*100).</div>
                        </div>

                        <div class="fed-info-import px-3 py-2 mb-3">
                            <i class="fas fa-clock text-primary me-2"></i>
                            <span class="small">
                                <strong>Ultima importacion registrada:</strong>
                                <?= $ultimaActualizacion ? '<strong class="text-primary">' . formatDateTime($ultimaActualizacion) . '</strong>' : '<span class="text-muted">Sin importaciones aun</span>' ?>
                            </span>
                        </div>

                        <?php if (!empty($ultimasImportaciones)): ?>
                        <h6 class="fw-bold mb-2"><i class="fas fa-history me-2 text-primary"></i>Historial reciente</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:.75rem;">
                                <thead class="table-light">
                                    <tr><th>Archivo</th><th>Regs.</th><th>Modo</th><th>Fecha</th><th>Usuario</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimasImportaciones as $ul): ?>
                                    <tr>
                                        <td><?= clean(mb_strimwidth($ul['nombre_archivo'] ?? '-', 0, 22, '...')) ?></td>
                                        <td class="text-end"><?= (int)($ul['registros_procesados'] ?? 0) ?></td>
                                        <td><?= clean($ul['modo_importacion'] ?? '-') ?></td>
                                        <td><?= formatDateTime($ul['fecha_operacion'] ?? null) ?></td>
                                        <td><?= clean($ul['usuario'] ?? '-') ?></td>
                                        <td>
                                            <?php if (($ul['estado'] ?? '') === 'EXITOSO'): ?>
                                                <span class="badge bg-success" style="font-size:.62rem;">OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" style="font-size:.62rem;">Error</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; /* fin puedeImportar */ ?>
            </div>

        </div>
    </div>
</div>

<!-- ============ MODAL: NUEVO / MODIFICAR INDICADOR ============ -->
<div class="modal fade" id="modalIndicador" tabindex="-1" aria-labelledby="tituloModalIndicador" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="convenio_fed.php" id="formIndicador">
                <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
                <input type="hidden" name="accion" value="fed_guardar_indicador">
                <input type="hidden" name="id_indicador" id="f_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalIndicador">Nuevo Indicador FED</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Codigo *</label>
                            <input type="text" class="form-control form-control-sm" name="codigo" id="f_codigo" maxlength="20" placeholder="FED-08" required>
                            <div class="form-text">Unico, ej. FED-01... FED-NN</div>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label small fw-semibold">Nombre del indicador *</label>
                            <input type="text" class="form-control form-control-sm" name="nombre" id="f_nombre" maxlength="300" placeholder="Nombre oficial del indicador" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripcion</label>
                            <textarea class="form-control form-control-sm" name="descripcion" id="f_descripcion" rows="2" maxlength="1000" placeholder="Formula, denominator, exclusiones, etc."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Unidad de medida</label>
                            <input type="text" class="form-control form-control-sm" name="unidad_medida" id="f_unidad" maxlength="50" placeholder="% / Numero">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Meta anual</label>
                            <input type="number" class="form-control form-control-sm" name="meta_anual" id="f_meta" step="0.01" min="0" placeholder="Ej. 90">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Peso ponderado (%)</label>
                            <input type="number" class="form-control form-control-sm" name="peso_ponderado" id="f_peso" step="0.01" min="0" max="100" placeholder="0 - 100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Periodicidad</label>
                            <select class="form-select form-select-sm" name="periodo" id="f_periodo">
                                <option value="MENSUAL">Mensual</option>
                                <option value="TRIMESTRAL" selected>Trimestral</option>
                                <option value="SEMESTRAL">Semestral</option>
                                <option value="ANUAL">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Responsable</label>
                            <input type="text" class="form-control form-control-sm" name="responsable" id="f_responsable" maxlength="150" placeholder="Nombre del responsable">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Orden de presentacion</label>
                            <input type="number" class="form-control form-control-sm" name="orden" id="f_orden" step="1" value="0">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="estado" id="f_estado" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="f_estado">Indicador activo (visible en el reporte)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-his btn-sm"><i class="fas fa-save me-1"></i> Guardar Indicador</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; /* fin panel admin */ ?>

<script>
/* ============================================================
 * Interactividad del modulo Convenio FED v3 (JavaScript puro)
 * ============================================================ */
function irATab(nombre) {
    var btn = document.getElementById('btn-tab-' + nombre);
    var panel = document.getElementById('panelAdmin');
    if (btn && window.bootstrap) {
        new bootstrap.Tab(btn).show();
    }
    if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.addEventListener('DOMContentLoaded', function () {

    /* ---- 1) Deep-link ?tab=gestion | ?tab=importar ---- */
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab');
    if (tab === 'gestion' || tab === 'importar') {
        var btn = document.getElementById('btn-tab-' + tab);
        if (btn && window.bootstrap) new bootstrap.Tab(btn).show();
        var panel = document.getElementById('panelAdmin');
        if (panel) setTimeout(function () { panel.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 250);
    }

    /* ---- 2) Modal de indicadores: rellenar al editar / limpiar al crear ---- */
    document.querySelectorAll('.btn-editar-indicador').forEach(function (b) {
        b.addEventListener('click', function () {
            var d;
            try { d = JSON.parse(b.getAttribute('data-indicador')); } catch (e) { return; }
            document.getElementById('tituloModalIndicador').textContent = 'Modificar Indicador: ' + d.codigo;
            document.getElementById('f_id').value = d.id_indicador;
            document.getElementById('f_codigo').value = d.codigo || '';
            document.getElementById('f_nombre').value = d.nombre || '';
            document.getElementById('f_descripcion').value = d.descripcion || '';
            document.getElementById('f_unidad').value = d.unidad_medida || '';
            document.getElementById('f_meta').value = (d.meta_anual !== null && d.meta_anual !== undefined) ? d.meta_anual : '';
            document.getElementById('f_peso').value = (d.peso_ponderado !== null && d.peso_ponderado !== undefined) ? d.peso_ponderado : '';
            document.getElementById('f_periodo').value = d.periodo || 'TRIMESTRAL';
            document.getElementById('f_responsable').value = d.responsable || '';
            document.getElementById('f_orden').value = (d.orden !== null && d.orden !== undefined) ? d.orden : 0;
            document.getElementById('f_estado').checked = parseInt(d.estado, 10) === 1;
        });
    });
    var btnNuevo = document.getElementById('btnNuevoIndicador');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function () {
            document.getElementById('tituloModalIndicador').textContent = 'Nuevo Indicador FED';
            document.getElementById('formIndicador').reset();
            document.getElementById('f_id').value = '0';
            document.getElementById('f_orden').value = '0';
            document.getElementById('f_estado').checked = true;
        });
    }

    /* ---- 3) Confirmaciones de activar / desactivar ---- */
    document.querySelectorAll('.form-toggle-indicador').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    /* ---- 4) Confirmacion de importacion en modo reemplazo ---- */
    var formImp = document.getElementById('formImportar');
    if (formImp) {
        formImp.addEventListener('submit', function (e) {
            var modo = formImp.querySelector('input[name="modo"]:checked');
            if (modo && modo.value === 'reemplazar') {
                if (!window.confirm('MODO REEMPLAZO: se ELIMINARAN los registros existentes de los periodos (indicador + anio + mes) que trae el archivo y se recargaran con estos datos.\n\nDesea continuar?')) {
                    e.preventDefault();
                }
            }
        });
    }

    /* ---- 5) Tabla de detalle: busqueda en vivo, orden por columna y CSV ---- */
    var tabla = document.getElementById('tablaDetalle');
    if (!tabla) return;
    var tbody = tabla.querySelector('tbody');
    var filas = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-sortable="1"]'));
    var input = document.getElementById('busquedaDetalle');
    var contador = document.getElementById('contadorDetalle');
    var estadoSort = { col: -1, dir: 1 };

    function aplicarFiltro() {
        if (!input || !contador) return;
        var q = (input.value || '').toLowerCase().trim();
        var visibles = 0;
        filas.forEach(function (tr) {
            var mostrar = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
            tr.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        contador.textContent = visibles + ' de ' + filas.length + ' registros';
    }
    if (input) input.addEventListener('input', aplicarFiltro);

    tabla.querySelectorAll('th.sortable').forEach(function (th) {
        th.addEventListener('click', function () {
            var idx = parseInt(th.getAttribute('data-col'), 10);
            var tipo = th.getAttribute('data-type') || 'str';
            var dir = (estadoSort.col === idx) ? -estadoSort.dir : 1;
            estadoSort = { col: idx, dir: dir };
            tabla.querySelectorAll('th.sortable').forEach(function (o) { o.classList.remove('sort-asc', 'sort-desc'); });
            th.classList.add(dir === 1 ? 'sort-asc' : 'sort-desc');
            var ordenadas = filas.slice().sort(function (a, b) {
                var ca = a.children[idx], cb = b.children[idx];
                var va = ca ? (ca.hasAttribute('data-sort') ? ca.getAttribute('data-sort') : ca.textContent.trim()) : '';
                var vb = cb ? (cb.hasAttribute('data-sort') ? cb.getAttribute('data-sort') : cb.textContent.trim()) : '';
                if (tipo === 'num') {
                    va = parseFloat(va); if (isNaN(va)) va = -Infinity;
                    vb = parseFloat(vb); if (isNaN(vb)) vb = -Infinity;
                    return (va - vb) * dir;
                }
                return String(va).localeCompare(String(vb), 'es') * dir;
            });
            ordenadas.forEach(function (tr) { tbody.appendChild(tr); });
        });
    });

    var btnCsv = document.getElementById('btnExportarCsv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function () {
            var lineas = [];
            var heads = Array.prototype.slice.call(tabla.querySelectorAll('thead th')).map(function (th) {
                return th.getAttribute('data-csv') || th.textContent.trim();
            });
            lineas.push(heads.join(';'));
            filas.forEach(function (tr) {
                if (tr.style.display === 'none') return;
                var cells = Array.prototype.slice.call(tr.children).map(function (td) {
                    var v = td.hasAttribute('data-csv') ? td.getAttribute('data-csv') : td.textContent.trim();
                    return '"' + String(v).replace(/"/g, '""') + '"';
                });
                lineas.push(cells.join(';'));
            });
            var blob = new Blob(["\uFEFF" + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = <?= json_encode('avances_fed_' . $fAnio . ($fMes !== '' ? '_' . $fMes : 's_todos') . ($fEst !== '' ? '_est' . $fEst : '') . '.csv') ?>;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }

    aplicarFiltro();
});
</script>

<?php include 'includes/footer.php'; ?>


