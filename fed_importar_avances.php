<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: FED - Importar Avances (Resultados.txt) (solo rol Administrador)
 *
 * v4 - Separacion del modulo (2026-11-04):
 *  Antes esta funcionalidad era la pestaña "Importar Avances" del panel de
 *  admin de convenio_fed.php. Ahora es una pagina independiente y ligera.
 *  Funciones:
 *   - Importacion del archivo Resultados.txt (exportado del SQL Server local)
 *     a la tabla CONVENIO_FED_AVANCES, con modos "actualizar" y "reemplazar".
 *   - Historial de las ultimas importaciones (LOG_IMPORTACION).
 *  El codigo compartido del modulo vive en includes/fed_common.php; el reporte
 *  de avances sigue en convenio_fed.php y la gestion de indicadores en
 *  fed_gestion_indicadores.php.
 *
 * v3.2 - Importacion del Resultados.txt REAL (2026-11-04):
 *  - El export actual del SQL Server trae 8 COLUMNAS separadas por ";" y el
 *    indicador se identifica por su CODIGO (ej. FED-04), no por su id numerico:
 *      codigo; renipress; establecimiento; anio; mes; valor_numerador;
 *      valor_denominador; porcentaje_avance
 *      Ej: FED-04;319;BAJO MARANKIARI;2026;01;1;1;100
 *  - El importador DETECTA AUTOMATICAMENTE el formato de cada linea:
 *      * 8 columnas (o 7, sin porcentaje) -> formato nuevo, resuelve el
 *        id_indicador buscandolo por codigo en CONVENIO_FED_INDICADORES.
 *      * 13 columnas -> formato antiguo (id_indicador numerico).
 *  - Si el codigo del archivo no esta registrado en la Gestion de Indicadores,
 *    la linea se omite y se informa con el codigo exacto (ej. FED-99).
 *  - Compatible con archivos con BOM UTF-8, codificacion ANSI y salto Windows.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/fed_common.php';

/* ----------------------------------------------------------------------------
 * Pagina exclusiva del rol Administrador: los demas roles vuelven al reporte.
 * ------------------------------------------------------------------------- */
if (!$esAdmin) {
    header('Location: convenio_fed.php');
    exit;
}

/* ============================================================================
 * 1. PARSER DEL ARCHIVO Resultados.txt
 *    El importador detecta AUTOMATICAMENTE uno de los dos formatos soportados
 *    (linea por linea, segun el numero de columnas separadas por ";"):
 *
 *    FORMATO NUEVO - 8 columnas (export actual del SQL Server):
 *      codigo; renipress; establecimiento; anio; mes; valor_numerador;
 *      valor_denominador; porcentaje_avance
 *      Ej: FED-04;319;BAJO MARANKIARI;2026;01;1;1;100
 *      (tambien se aceptan 7 columnas, sin porcentaje_avance: se recalcula)
 *      El indicador se resuelve por CODIGO contra CONVENIO_FED_INDICADORES.
 *
 *    FORMATO ANTIGUO - 13 columnas (export anterior):
 *      id_avance; id_indicador; renipress; establecimiento; anio; trimestre; mes;
 *      valor_numerador; valor_denominador; porcentaje_avance; observaciones;
 *      fecha_registro; usuario
 *
 *    (Los textos "NULL" y los campos vacios se convierten en NULL de PHP)
 * ========================================================================== */

function fedParseLineaAvance($linea) {
    $linea = trim($linea);
    if ($linea === '') {
        return [null, 'Linea vacia'];
    }
    $campos = explode(';', $linea);
    $nCol = count($campos);
    if ($nCol !== 7 && $nCol !== 8 && $nCol !== 13) {
        return [null, 'Numero de columnas invalido (' . $nCol . '). Se esperaban 8 columnas '
            . '(codigo; renipress; establecimiento; anio; mes; numerador; denominador; porcentaje) '
            . 'o 13 columnas (formato antiguo), separadas por ";"'];
    }
    $c = [];
    foreach ($campos as $v) {
        $v = trim($v);
        $c[] = ($v === '' || strcasecmp($v, 'NULL') === 0) ? null : $v;
    }

    if ($nCol <= 8) {
        /* -------- FORMATO NUEVO: 8 columnas (o 7, sin porcentaje) -------- */
        $d = [
            'id_indicador'     => null,      // se resuelve por CODIGO en el importador
            'codigo_indicador' => $c[0],     // ej. FED-04
            'renipress'        => $c[1],
            'establecimiento'  => $c[2],
            'anio'             => $c[3],
            'trimestre'        => null,
            'mes'              => $c[4],
            'valor_numerador'  => $c[5],
            'valor_denominador'=> $c[6],
            'porcentaje_avance'=> $nCol === 8 ? $c[7] : null,
            'observaciones'    => null,
            'fecha_registro'   => null,
            'usuario'          => null,
        ];
    } else {
        /* -------- FORMATO ANTIGUO: 13 columnas -------- */
        $d = [
            // [0] id_avance del SQL Server se IGNORA: la web asigna el suyo (AUTO_INCREMENT)
            'id_indicador'     => $c[1],
            'codigo_indicador' => null,
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
    }

    // --- Validaciones ---
    if ($d['id_indicador'] === null && $d['codigo_indicador'] === null) {
        return [null, 'la linea no trae ni id_indicador ni codigo de indicador'];
    }
    if ($d['id_indicador'] !== null && !ctype_digit((string)$d['id_indicador'])) {
        return [null, "id_indicador invalido ('{$d['id_indicador']}')"];
    }
    if ($d['codigo_indicador'] !== null) {
        $d['codigo_indicador'] = strtoupper((string)$d['codigo_indicador']);
        if (mb_strlen($d['codigo_indicador']) > 20) {
            return [null, "codigo de indicador invalido: '{$d['codigo_indicador']}' supera los 20 caracteres"];
        }
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
 * 2. PROCESAR IMPORTACION (POST)
 * ========================================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $mensaje = 'Token de seguridad invalido. Recargue la pagina e intente nuevamente.';
        $tipoMensaje = 'danger';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            if ($accion === 'fed_importar') {
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

                // Indicadores registrados, indexados por CODIGO (formato nuevo de
                // 8 columnas, ej. "FED-04" => id) y lista de ids validos (formato
                // antiguo de 13 columnas). Mismo conjunto, dos accesos.
                $mapCodigoAId = [];
                foreach ($pdo->query("SELECT id_indicador, codigo FROM `{$tablaIndicadores}`")->fetchAll(PDO::FETCH_ASSOC) as $indReg) {
                    $codigoReg = strtoupper(trim((string)$indReg['codigo']));
                    if ($codigoReg !== '') {
                        $mapCodigoAId[$codigoReg] = (int)$indReg['id_indicador'];
                    }
                }
                $idsValidos = array_values($mapCodigoAId);

                $registros = [];
                $errores = [];
                $nLinea = 0;
                foreach ($lineas as $linea) {
                    $nLinea++;
                    if (trim($linea) === '') continue;
                    // Saltar una posible linea de cabecera (contiene nombres de columna)
                    if (stripos($linea, 'renipress') !== false
                        || (stripos($linea, 'indicador') !== false && stripos($linea, 'establecimiento') !== false)) {
                        continue;
                    }
                    [$fila, $err] = fedParseLineaAvance($linea);
                    if ($fila === null) {
                        $errores[] = "Linea {$nLinea}: {$err}";
                        continue;
                    }
                    // Resolver el indicador:
                    //  - Formato nuevo (8 col): busca el CODIGO (ej. FED-04) en la
                    //    Gestion de Indicadores y obtiene su id_indicador.
                    //  - Formato antiguo (13 col): valida el id_indicador numerico.
                    if ($fila['codigo_indicador'] !== null) {
                        $codigoBusq = strtoupper(trim((string)$fila['codigo_indicador']));
                        if (!isset($mapCodigoAId[$codigoBusq])) {
                            $errores[] = "Linea {$nLinea}: el codigo de indicador {$codigoBusq} no esta registrado en la Gestion de Indicadores. Registrelo primero (con ese codigo) y vuelva a importar.";
                            continue;
                        }
                        $fila['id_indicador'] = $mapCodigoAId[$codigoBusq];
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
            $mensaje = 'Error de base de datos: ' . $e->getMessage();
            $tipoMensaje = 'danger';
            logImportacion([
                'tipo_operacion' => 'IMPORTACION',
                'tipo_archivo'   => 'FED_TXT',
                'estado'         => 'FALLIDO',
                'mensaje'        => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $mensaje = $e->getMessage();
            $tipoMensaje = 'danger';
            logImportacion([
                'tipo_operacion' => 'IMPORTACION',
                'tipo_archivo'   => 'FED_TXT',
                'estado'         => 'FALLIDO',
                'mensaje'        => $e->getMessage(),
            ]);
        }
    }
}

/* ============================================================================
 * 3. DATOS PARA LA PAGINA (ultima importacion + historial de auditoria)
 * ========================================================================== */

$ultimaActualizacion = null;
if ($tablaAvances !== null) {
    $colUltima = $tieneFechaImportacion
        ? 'MAX(COALESCE(fecha_importacion, fecha_registro))'
        : 'MAX(fecha_registro)';
    $ultimaActualizacion = $pdo->query("SELECT {$colUltima} FROM `{$tablaAvances}`")->fetchColumn();
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

$csrfToken = generateCSRFToken();
$pageTitle = 'FED: Importar Avances - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos de la importacion de avances FED ---- */
.fed-info-import { background: #eef6ff; border: 1px solid #cfe5ff; border-radius: 8px; }
.fed-formato { background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px; font-size: .8rem; }
.fed-formato code { background: #e9ecef; padding: 1px 5px; border-radius: 4px; }
</style>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-file-import me-2"></i>FED: Importar Avances</h4>
            <p class="subtitle">Convenio FED - Carga del archivo Resultados.txt exportado de la base SQL Server local.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="convenio_fed.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chart-line me-1"></i> Ver reporte de avances
            </a>
            <a href="fed_gestion_indicadores.php" class="btn btn-his btn-sm">
                <i class="fas fa-tasks me-1"></i> Gestion de Indicadores
            </a>
        </div>
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

<?php if (!$puedeImportar): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php if ($tablaIndicadores === null && $tablaAvances === null): ?>
            Las tablas <code>CONVENIO_FED_INDICADORES</code> y <code>CONVENIO_FED_AVANCES</code> no existen en la base de datos.
        <?php elseif ($tablaIndicadores === null): ?>
            La tabla <code>CONVENIO_FED_INDICADORES</code> no existe en la base de datos.
        <?php else: ?>
            La tabla <code>CONVENIO_FED_AVANCES</code> no existe en la base de datos.
        <?php endif; ?>
        Ejecute el script <code>Database/update_fed_v3.sql</code> desde phpMyAdmin para habilitar la importacion.
    </div>
<?php else: ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Importar archivo de avances</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Importe el archivo <code>Resultados.txt</code> exportado de su base SQL Server local.
                    El sistema detecta automaticamente el formato: <strong>8 columnas con codigo de indicador</strong>
                    (export actual, ej. <code>FED-04;319;...</code>) o el antiguo de 13 columnas.
                    Si un registro ya existe (mismo indicador + renipress + anio + mes) se <strong>actualiza</strong>;
                    si es nuevo se <strong>agrega</strong>. El sistema registra la fecha y hora de cada importacion.
                </p>
                <form method="POST" action="fed_importar_avances.php" enctype="multipart/form-data" id="formImportar" class="card border-0 bg-light p-3">
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
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><i class="fas fa-code me-2 text-primary"></i>Formato esperado del archivo</h6>
            </div>
            <div class="card-body">
                <div class="fed-formato p-3 mb-3">
                    <div class="mb-2"><strong>Formato actual: 8 columnas</strong> separadas por <code>;</code> (punto y coma). El indicador se identifica por su <strong>codigo</strong>:</div>
                    <code class="d-block mb-2" style="font-size:.72rem; word-break:break-all;">
                        codigo; renipress; establecimiento; anio; mes; valor_numerador; valor_denominador; porcentaje_avance
                    </code>
                    <code class="d-block mb-2 text-success" style="font-size:.72rem; word-break:break-all;">
                        Ej: FED-04;319;BAJO MARANKIARI;2026;01;1;1;100
                    </code>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>El <code>codigo</code> (ej. <code>FED-04</code>) debe estar registrado en la <a href="fed_gestion_indicadores.php">Gestion de Indicadores</a>.</div>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>Si el archivo trae 7 columnas (sin <code>porcentaje_avance</code>) se recalcula: num/den*100.</div>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>Codificacion UTF-8 (con o sin BOM) o ANSI; saltos de linea Windows o Unix.</div>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>Si la primera linea es una cabecera de columnas, se detecta y se omite.</div>
                    <div><i class="fas fa-info-circle text-secondary me-1"></i>Tambien se acepta el formato antiguo de 13 columnas (<code>id_avance; id_indicador; renipress; ...; usuario</code>): se detecta automaticamente linea por linea.</div>
                </div>

                <div class="fed-info-import px-3 py-2">
                    <i class="fas fa-clock text-primary me-2"></i>
                    <span class="small">
                        <strong>Ultima importacion registrada:</strong>
                        <?= $ultimaActualizacion ? '<strong class="text-primary">' . formatDateTime($ultimaActualizacion) . '</strong>' : '<span class="text-muted">Sin importaciones aun</span>' ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($ultimasImportaciones)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>Historial reciente</h6>
            </div>
            <div class="card-body p-0">
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
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; /* fin puedeImportar */ ?>

<script>
/* ============================================================
 * Interactividad de la importacion FED (JS puro)
 * ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    /* ---- Confirmacion de importacion en modo reemplazo ---- */
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
});
</script>

<?php include 'includes/footer.php'; ?>
