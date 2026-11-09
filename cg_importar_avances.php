<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Convenio de Gestion - Importar Avances (solo rol Administrador)
 *
 * Adaptacion al patron del modulo FED (2026-11-09): replica la pagina
 * fed_importar_avances.php sobre las tablas del Convenio de Gestion.
 *  Funciones:
 *   - Importacion del archivo de avances (.txt / .csv exportado del SQL Server
 *     local o del Excel de seguimiento) a la tabla CONVENIO_GESTION_AVANCE,
 *     con modos "actualizar" y "reemplazar".
 *   - Historial de las ultimas importaciones (LOG_IMPORTACION, tipo CG_TXT).
 *
 * FORMATOS DETECTADOS AUTOMATICAMENTE (linea por linea, separador ";"):
 *
 *   FORMATO NUEVO - 8 columnas (mismo formato que el Convenio FED):
 *     codigo; renipress; establecimiento; anio; mes; valor_numerador;
 *     valor_denominador; porcentaje_avance
 *     Ej: CG-04;319;BAJO MARANKIARI;2026;01;120;150;80
 *     Variantes aceptadas:
 *       - 7 columnas: sin porcentaje_avance (se recalcula num/den*100).
 *       - 9 columnas: con valor_resultado antes del porcentaje:
 *         codigo; renipress; establecimiento; anio; mes; num; den; resultado; pct
 *     El indicador se resuelve por CODIGO contra CONVENIO_GESTION_INDICADORES.
 *
 *   FORMATO ANTIGUO - 12 columnas (volcado de CONVENIO_GESTION_AVANCE):
 *     id_avance; id_indicador; anio; mes; trimestre; valor_numerador;
 *     valor_denominador; valor_resultado; porcentaje_avance; observaciones;
 *     fecha_registro; usuario
 *     (El indicador se identifica por su id numerico; renipress y
 *     establecimiento quedan NULL -> fila "Consolidado Zona Sanitaria".)
 *
 *  Requisito: la tabla CONVENIO_GESTION_AVANCE debe tener las columnas
 *  renipress/establecimiento (se agregan automaticamente al abrir el modulo;
 *  si el hosting bloquea ALTER TABLE, ejecute Database/update_gestion_v1.sql).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/cg_common.php';

/* ----------------------------------------------------------------------------
 * Pagina exclusiva del rol Administrador: los demas roles vuelven al reporte.
 * ------------------------------------------------------------------------- */
if (!$esAdmin) {
    header('Location: convenio_gestion.php');
    exit;
}

/* ============================================================================
 * 1. PARSER DE LAS LINEAS DEL ARCHIVO DE AVANCES
 *    (Los textos "NULL" y los campos vacios se convierten en NULL de PHP)
 * ========================================================================== */

function cgParseLineaAvance($linea) {
    $linea = trim($linea);
    if ($linea === '') {
        return [null, 'Linea vacia'];
    }
    $campos = explode(';', $linea);
    $nCol = count($campos);
    if ($nCol !== 7 && $nCol !== 8 && $nCol !== 9 && $nCol !== 12) {
        return [null, 'Numero de columnas invalido (' . $nCol . '). Se esperaban 8 columnas '
            . '(codigo; renipress; establecimiento; anio; mes; numerador; denominador; porcentaje), '
            . '9 (con resultado) o 12 (formato antiguo), separadas por ";"'];
    }
    $c = [];
    foreach ($campos as $v) {
        $v = trim($v);
        $c[] = ($v === '' || strcasecmp($v, 'NULL') === 0) ? null : $v;
    }

    if ($nCol <= 9) {
        /* -------- FORMATO NUEVO: 7/8/9 columnas (con codigo) -------- */
        $d = [
            'id_indicador'     => null,      // se resuelve por CODIGO en el importador
            'codigo_indicador' => $c[0],     // ej. CG-04
            'renipress'        => $c[1],
            'establecimiento'  => $c[2],
            'anio'             => $c[3],
            'trimestre'        => null,
            'mes'              => $c[4],
            'valor_numerador'  => $c[5],
            'valor_denominador'=> $c[6],
            'valor_resultado'  => null,
            'porcentaje_avance'=> null,
            'observaciones'    => null,
            'fecha_registro'   => null,
            'usuario'          => null,
        ];
        if ($nCol === 9) {
            $d['valor_resultado']   = $c[7];
            $d['porcentaje_avance'] = $c[8];
        } elseif ($nCol === 8) {
            $d['porcentaje_avance'] = $c[7];
        }
        // $nCol === 7: sin porcentaje (se recalcula mas abajo)
    } else {
        /* -------- FORMATO ANTIGUO: 12 columnas -------- */
        $d = [
            // [0] id_avance del SQL Server se IGNORA: la web asigna el suyo (AUTO_INCREMENT)
            'id_indicador'     => $c[1],
            'codigo_indicador' => null,
            'renipress'        => null,     // formato antiguo: fila consolidada
            'establecimiento'  => null,
            'anio'             => $c[2],
            'trimestre'        => $c[4],
            'mes'              => $c[3],
            'valor_numerador'  => $c[5],
            'valor_denominador'=> $c[6],
            'valor_resultado'  => $c[7],
            'porcentaje_avance'=> $c[8],
            'observaciones'    => $c[9],
            'fecha_registro'   => $c[10] !== null ? substr($c[10], 0, 19) : null,
            'usuario'          => $c[11],
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
    if ($d['renipress'] !== null) {
        if ($d['renipress'] === '') {
            return [null, 'renipress vacio'];
        }
        if (mb_strlen($d['renipress']) > 10) {
            return [null, "renipress '{$d['renipress']}' supera los 10 caracteres permitidos"];
        }
    }
    if ($d['establecimiento'] !== null && mb_strlen($d['establecimiento']) > 50) {
        $d['establecimiento'] = mb_substr($d['establecimiento'], 0, 50);
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
    if ($d['valor_resultado'] !== null) {
        if (!is_numeric((string)$d['valor_resultado'])) {
            return [null, "valor_resultado invalido ('{$d['valor_resultado']}')"];
        }
        $d['valor_resultado'] = (float)$d['valor_resultado'];
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
            if ($accion === 'cg_importar') {
                if (!$puedeImportarCG || !$tieneRenipressCG) {
                    throw new Exception('Las tablas del Convenio de Gestion no existen o no tienen las columnas renipress/establecimiento. Ejecute Database/update_gestion_v1.sql.');
                }
                if (!isset($_FILES['archivo_txt']) || $_FILES['archivo_txt']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No se recibio el archivo. Verifique que haya seleccionado el archivo de avances (error ' . ($_FILES['archivo_txt']['error'] ?? 'N/D') . ').');
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
                if (!$tieneFechaImportacionCG) {
                    try {
                        $pdo->exec("ALTER TABLE `{$tablaAvancesCG}` ADD COLUMN `fecha_importacion` DATETIME NULL DEFAULT NULL AFTER `fecha_registro`");
                        $tieneFechaImportacionCG = true;
                    } catch (Exception $e) {
                        // Si falla (sin permisos / ya existe), se continua sin la columna
                        $tieneFechaImportacionCG = cgTieneColumna($pdo, $tablaAvancesCG, 'fecha_importacion');
                    }
                }

                $modo = ($_POST['modo'] ?? 'actualizar') === 'reemplazar' ? 'reemplazar' : 'actualizar';
                $tInicio = microtime(true);

                // --- Parsear todas las lineas ---
                $lineas = preg_split('/\R/', $contenido);

                // Indicadores registrados, indexados por CODIGO (formato nuevo,
                // ej. "CG-04" => id) y lista de ids validos (formato antiguo).
                $mapCodigoAId = [];
                foreach ($pdo->query("SELECT id_indicador, codigo FROM `{$tablaIndicadoresCG}`")->fetchAll(PDO::FETCH_ASSOC) as $indReg) {
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
                    [$fila, $err] = cgParseLineaAvance($linea);
                    if ($fila === null) {
                        $errores[] = "Linea {$nLinea}: {$err}";
                        continue;
                    }
                    // Resolver el indicador:
                    //  - Formato nuevo (7/8/9 col): busca el CODIGO (ej. CG-04) en la
                    //    Gestion de Indicadores y obtiene su id_indicador.
                    //  - Formato antiguo (12 col): valida el id_indicador numerico.
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
                // KEY / AUTO_INCREMENT, se repara sola; si el hosting no permite
                // ALTER, se inserta con id manual (MAX + 1).
                $autoIncAvances = cgAsegurarAutoIncrement($pdo, $tablaAvancesCG, 'id_avance');
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
                        $stDel = $pdo->prepare("DELETE FROM `{$tablaAvancesCG}`
                                                WHERE id_indicador = ? AND anio = ?
                                                  AND ((mes IS NULL AND ? IS NULL) OR mes = ?)");
                        foreach ($combos as $p) {
                            $stDel->execute([$p[0], $p[1], $p[2], $p[2]]);
                            $eliminados += $stDel->rowCount();
                        }
                    }

                    $stSel = $pdo->prepare("SELECT id_avance FROM `{$tablaAvancesCG}`
                                            WHERE id_indicador = ? AND anio = ?
                                              AND ((renipress IS NULL AND ? IS NULL) OR renipress = ?)
                                              AND ((mes IS NULL AND ? IS NULL) OR mes = ?)");
                    if ($tieneFechaImportacionCG) {
                        $sqlIns = "INSERT INTO `{$tablaAvancesCG}`
                            (" . (!$autoIncAvances ? "id_avance, " : "") . "id_indicador, renipress, establecimiento, anio, trimestre, mes,
                             valor_numerador, valor_denominador, valor_resultado, porcentaje_avance, observaciones,
                             fecha_registro, fecha_importacion, usuario)
                            VALUES (" . (!$autoIncAvances ? "?, " : "") . "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stIns = $pdo->prepare($sqlIns);
                        $stUpd = $pdo->prepare("UPDATE `{$tablaAvancesCG}`
                            SET establecimiento = ?, valor_numerador = ?, valor_denominador = ?, valor_resultado = ?,
                                porcentaje_avance = ?, observaciones = ?, trimestre = ?,
                                fecha_registro = ?, fecha_importacion = ?, usuario = ?
                            WHERE id_avance = ?");
                    } else {
                        $sqlIns = "INSERT INTO `{$tablaAvancesCG}`
                            (" . (!$autoIncAvances ? "id_avance, " : "") . "id_indicador, renipress, establecimiento, anio, trimestre, mes,
                             valor_numerador, valor_denominador, valor_resultado, porcentaje_avance, observaciones,
                             fecha_registro, usuario)
                            VALUES (" . (!$autoIncAvances ? "?, " : "") . "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stIns = $pdo->prepare($sqlIns);
                        $stUpd = $pdo->prepare("UPDATE `{$tablaAvancesCG}`
                            SET establecimiento = ?, valor_numerador = ?, valor_denominador = ?, valor_resultado = ?,
                                porcentaje_avance = ?, observaciones = ?, trimestre = ?,
                                fecha_registro = ?, usuario = ?
                            WHERE id_avance = ?");
                    }

                    $idsEnArchivo = []; // para deduplicar claves repetidas dentro del mismo archivo
                    foreach ($registros as $r) {
                        $usuarioFinal = $r['usuario'] !== null && $r['usuario'] !== '' ? $r['usuario'] : ($_SESSION['usuario'] ?? 'sistema');
                        $fechaReg     = $r['fecha_registro'] !== null ? $r['fecha_registro'] : $horaImportacion;
                        $clave = $r['id_indicador'] . '|' . ($r['renipress'] ?? '') . '|' . $r['anio'] . '|' . ($r['mes'] ?? '');

                        $idExistente = $idsEnArchivo[$clave] ?? null;
                        if ($idExistente === null && $modo === 'actualizar') {
                            $stSel->execute([$r['id_indicador'], $r['anio'], $r['renipress'], $r['renipress'], $r['mes'], $r['mes']]);
                            $idExistente = $stSel->fetchColumn();
                            $idExistente = $idExistente !== false ? (int)$idExistente : null;
                        }

                        if ($idExistente !== null) {
                            // Ya existe -> ACTUALIZAR con los valores frescos del SQL Server
                            $esRepetidoEnArchivo = isset($idsEnArchivo[$clave]);
                            if ($tieneFechaImportacionCG) {
                                $stUpd->execute([$r['establecimiento'], $r['valor_numerador'], $r['valor_denominador'],
                                                 $r['valor_resultado'], $r['porcentaje_avance'], $r['observaciones'], $r['trimestre'],
                                                 $fechaReg, $horaImportacion, $usuarioFinal, $idExistente]);
                            } else {
                                $stUpd->execute([$r['establecimiento'], $r['valor_numerador'], $r['valor_denominador'],
                                                 $r['valor_resultado'], $r['porcentaje_avance'], $r['observaciones'], $r['trimestre'],
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
                                $idManual = (int)$pdo->query("SELECT COALESCE(MAX(id_avance), 0) + 1 FROM `{$tablaAvancesCG}`")->fetchColumn();
                            }
                            if ($tieneFechaImportacionCG) {
                                $paramsIns = [$r['id_indicador'], $r['renipress'], $r['establecimiento'], $r['anio'],
                                              $r['trimestre'], $r['mes'], $r['valor_numerador'], $r['valor_denominador'],
                                              $r['valor_resultado'], $r['porcentaje_avance'], $r['observaciones'], $fechaReg, $horaImportacion, $usuarioFinal];
                            } else {
                                $paramsIns = [$r['id_indicador'], $r['renipress'], $r['establecimiento'], $r['anio'],
                                              $r['trimestre'], $r['mes'], $r['valor_numerador'], $r['valor_denominador'],
                                              $r['valor_resultado'], $r['porcentaje_avance'], $r['observaciones'], $fechaReg, $usuarioFinal];
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
                    'tipo_archivo'        => 'CG_TXT',
                    'nombre_archivo'      => $nombreOriginal,
                    'tabla_destino'       => $tablaAvancesCG,
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
                'tipo_archivo'   => 'CG_TXT',
                'estado'         => 'FALLIDO',
                'mensaje'        => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $mensaje = $e->getMessage();
            $tipoMensaje = 'danger';
            logImportacion([
                'tipo_operacion' => 'IMPORTACION',
                'tipo_archivo'   => 'CG_TXT',
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
if ($tablaAvancesCG !== null) {
    try {
        $colUltima = $tieneFechaImportacionCG
            ? 'MAX(COALESCE(fecha_importacion, fecha_registro))'
            : 'MAX(fecha_registro)';
        $ultimaActualizacion = $pdo->query("SELECT {$colUltima} FROM `{$tablaAvancesCG}`")->fetchColumn();
    } catch (Throwable $e) {
        $ultimaActualizacion = null;
    }
}

// Ultimas importaciones del Convenio de Gestion (auditoria, si existe la tabla de log)
$ultimasImportaciones = [];
if ($tablaAvancesCG !== null) {
    try {
        $stLog = $pdo->query("SELECT nombre_archivo, registros_procesados, modo_importacion, fecha_operacion, usuario, estado
                              FROM LOG_IMPORTACION WHERE tipo_archivo = 'CG_TXT'
                              ORDER BY fecha_operacion DESC LIMIT 5");
        $ultimasImportaciones = $stLog->fetchAll();
    } catch (Exception $e) {
        $ultimasImportaciones = []; // la tabla de log no existe: se omite silenciosamente
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'CG: Importar Avances - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos de la importacion de avances del Convenio de Gestion ---- */
.cg-info-import { background: #eef6ff; border: 1px solid #cfe5ff; border-radius: 8px; }
.cg-formato { background: #f8f9fa; border: 1px dashed #ced4da; border-radius: 8px; font-size: .8rem; }
.cg-formato code { background: #e9ecef; padding: 1px 5px; border-radius: 4px; }
</style>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-file-import me-2"></i>CG: Importar Avances</h4>
            <p class="subtitle">Convenio de Gestion MINSA-GORE - Carga del archivo de avances exportado de la base SQL Server local.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="convenio_gestion.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chart-line me-1"></i> Ver reporte de avances
            </a>
            <a href="cg_gestion_indicadores.php" class="btn btn-his btn-sm">
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

<?php if (!$puedeImportarCG || !$tieneRenipressCG): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php if (!$puedeImportarCG): ?>
            <?php if ($tablaIndicadoresCG === null && $tablaAvancesCG === null): ?>
                Las tablas <code>CONVENIO_GESTION_INDICADORES</code> y <code>CONVENIO_GESTION_AVANCE</code> no existen en la base de datos.
            <?php elseif ($tablaIndicadoresCG === null): ?>
                La tabla <code>CONVENIO_GESTION_INDICADORES</code> no existe en la base de datos.
            <?php else: ?>
                La tabla <code>CONVENIO_GESTION_AVANCE</code> no existe en la base de datos.
            <?php endif; ?>
            Ejecute el script <code>Database/if0_42181393_his.sql</code> desde phpMyAdmin para habilitar la importacion.
        <?php else: ?>
            La tabla <code>CONVENIO_GESTION_AVANCE</code> no tiene todavia las columnas <code>renipress</code> y
            <code>establecimiento</code> (el servidor no permitio crearlas automaticamente).
            Ejecute el script <code>Database/update_gestion_v1.sql</code> desde phpMyAdmin para habilitar la importacion
            con detalle por establecimiento.
        <?php endif; ?>
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
                    Importe el archivo <code>.txt</code> / <code>.csv</code> exportado de su BD local.
                    Si un registro ya existe (mismo indicador + renipress + anio + mes) se <strong>actualiza</strong>;
                    si es nuevo se <strong>agrega</strong>. El sistema registra la fecha y hora de cada importacion.
                </p>
                <form method="POST" action="cg_importar_avances.php" enctype="multipart/form-data" id="formImportar" class="card border-0 bg-light p-3">
                    <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
                    <input type="hidden" name="accion" value="cg_importar">

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
                                <strong>Reemplazar periodo</strong>: elimina los registros de los periodos (indicador + anio + mes) que trae el archivo y los recarga.
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
                <div class="cg-formato p-3 mb-3">
                    <div class="mb-2"><strong>Formato actual: 8 columnas</strong> separadas por <code>;</code> (punto y coma). El indicador se identifica por su <strong>codigo</strong>:</div>
                    <code class="d-block mb-2" style="font-size:.72rem; word-break:break-all;">
                        codigo; renipress; establecimiento; anio; mes; valor_numerador; valor_denominador; porcentaje_avance
                    </code>
                    <code class="d-block mb-2 text-success" style="font-size:.72rem; word-break:break-all;">
                        Ej: CG-04;319;BAJO MARANKIARI;2026;01;120;150;80
                    </code>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>El <code>codigo</code> (ej. <code>CG-04</code>) debe estar registrado en la <a href="cg_gestion_indicadores.php">Gestion de Indicadores</a>.</div>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>Se aceptan <strong>7 columnas</strong> (sin porcentaje: se recalcula) y <strong>9 columnas</strong> (con <code>valor_resultado</code> antes del porcentaje).</div>
                    <div class="mb-1"><i class="fas fa-check text-success me-1"></i>Tambien se acepta el <strong>formato antiguo de 12 columnas</strong> (volcado de CONVENIO_GESTION_AVANCE con id_indicador numerico).</div>
                    <div class="mb-1"><i class="fas fa-info-circle text-primary me-1"></i>Deje el <code>mes</code> vacio (o NULL) para la fila de <strong>acumulado anual</strong>.</div>
                </div>

                <div class="cg-info-import px-3 py-2">
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

<?php endif; /* fin puedeImportarCG */ ?>

<script>
/* ============================================================
 * Interactividad de la importacion del Convenio de Gestion (JS puro)
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
