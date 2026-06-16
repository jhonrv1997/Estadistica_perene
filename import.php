<?php
/**
 * Sistema de Gestion de Datos HIS
 * Modulo de Importacion de Datos
 */

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';

$pdo = getDBConnection();
$mensaje = '';
$tipoMensaje = '';

// Procesar importacion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_zip'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $mensaje = 'Token de seguridad invalido. Recargue la pagina e intente nuevamente.';
        $tipoMensaje = 'danger';
    } else {
        $resultado = procesarImportacion($_FILES['archivo_zip'], $_POST);
        $mensaje = $resultado['mensaje'];
        $tipoMensaje = $resultado['exito'] ? 'success' : 'danger';
    }
}

/**
 * Procesar la importacion del archivo ZIP
 */
function procesarImportacion($file, $post) {
    $pdo = getDBConnection();
    $startTime = microtime(true);
    
    // Validar archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['exito' => false, 'mensaje' => 'Error al subir archivo: ' . getUploadError($file['error'])];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'zip') {
        return ['exito' => false, 'mensaje' => 'Solo se permiten archivos .zip'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['exito' => false, 'mensaje' => 'El archivo excede el tamano maximo permitido (100MB)'];
    }
    
    // Detectar tipo de archivo por nombre
    $fileInfo = detectFileType($file['name']);
    if (!$fileInfo) {
        return ['exito' => false, 'mensaje' => 'No se pudo determinar el tipo de archivo. El nombre debe contener: MaestroRegistrador, MaestroPersonal, MaestroPaciente o NominalTrama'];
    }
    
    // Para NominalTrama, validar que se selecciono periodo
    $periodoMes = null;
    $periodoAnio = null;
    if ($fileInfo['tipo'] === 'NominalTrama') {
        $periodoMes = $post['periodo_mes'] ?? '';
        $periodoAnio = $post['periodo_anio'] ?? '';
        if (empty($periodoMes) || empty($periodoAnio)) {
            return ['exito' => false, 'mensaje' => 'Debe seleccionar el Mes y Anio del periodo para la importacion de NominalTrama'];
        }
    }
    
    // Crear directorio temporal
    $tempDir = UPLOAD_DIR . 'temp_' . uniqid() . '/';
    if (!mkdir($tempDir, 0777, true)) {
        return ['exito' => false, 'mensaje' => 'Error al crear directorio temporal'];
    }
    
    try {
        // Descomprimir ZIP
        $zip = new ZipArchive;
        if ($zip->open($file['tmp_name']) !== true) {
            throw new Exception('No se pudo abrir el archivo ZIP');
        }
        
        $zip->extractTo($tempDir);
        $zip->close();
        
        // Buscar archivo CSV dentro del ZIP
        $csvFile = null;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'csv') {
                $csvFile = $item->getPathname();
                break;
            }
        }
        
        if (!$csvFile) {
            throw new Exception('No se encontro ningun archivo .csv dentro del archivo ZIP');
        }
        
        // Leer CSV
        $csvData = leerCSV($csvFile);
        if (empty($csvData)) {
            throw new Exception('El archivo CSV esta vacio o no se pudo leer');
        }
        
        // Obtener columnas de la tabla destino
        $columnas = getColumnMapping($fileInfo['tipo']);
        $dateColumns = getDateColumns($fileInfo['tipo']);
        
        // Obtener encabezados del CSV (primera fila)
        $csvHeaders = array_map('trim', $csvData[0]);
        $csvHeaders = array_map(function($h) { return trim($h, "\xEF\xBB\xBF"); }, $csvHeaders); // BOM removal
        
        // Iniciar transaccion
        $pdo->beginTransaction();
        
        try {
            // Modo de importacion segun tipo
            if ($fileInfo['modo'] === 'REEMPLAZO') {
                // Eliminar todos los registros (DELETE en lugar de TRUNCATE para compatibilidad con transacciones)
                $pdo->exec("DELETE FROM `{$fileInfo['tabla']}`");
            } else {
                // Eliminar solo datos del periodo seleccionado
                $stmt = $pdo->prepare("DELETE FROM `{$fileInfo['tabla']}` WHERE Anio = ? AND Mes = ?");
                $stmt->execute([$periodoAnio, $periodoMes]);
            }
            
            // Insertar datos del CSV
            $registrosInsertados = 0;
            $totalFilas = count($csvData) - 1; // Restar encabezado
            
            // Insertar en lotes para mejor rendimiento
            $batchSize = 500;
            $values = [];
            $placeholders = [];
            
            for ($i = 1; $i <= $totalFilas; $i++) {
                $row = $csvData[$i];
                
                // Mapear valores CSV a columnas de tabla
                $rowValues = [];
                for ($c = 0; $c < count($columnas); $c++) {
                    $val = isset($row[$c]) ? trim($row[$c]) : '';
                    $isDate = in_array($columnas[$c], $dateColumns);
                    $rowValues[] = sqlValue($val, $isDate);
                }
                
                $placeholders[] = '(' . implode(',', $rowValues) . ')';
                
                // Ejecutar batch
                if (count($placeholders) >= $batchSize || $i === $totalFilas) {
                    $sql = "INSERT INTO `{$fileInfo['tabla']}` (`" . implode('`,`', $columnas) . "`) VALUES " . implode(',', $placeholders);
                    $pdo->exec($sql);
                    $registrosInsertados += count($placeholders);
                    $placeholders = [];
                }
            }
            
            $pdo->commit();
            
            $duration = round(microtime(true) - $startTime, 2);
            
            // Registrar en log de importacion
            logImportacion([
                'tipo_operacion' => 'IMPORT',
                'tipo_archivo' => $fileInfo['tipo'],
                'nombre_archivo' => $file['name'],
                'tabla_destino' => $fileInfo['tabla'],
                'periodo_mes' => $periodoMes,
                'periodo_anio' => $periodoAnio,
                'registros_procesados' => $registrosInsertados,
                'modo_importacion' => $fileInfo['modo'],
                'estado' => 'EXITO',
                'mensaje' => "Se importaron {$registrosInsertados} registros correctamente",
                'duracion_segundos' => $duration
            ]);
            
            // Marcar como importado en tabla IMPORT_ESTADO
            marcarImportado($fileInfo['tipo'], $file['name'], $registrosInsertados, $periodoMes, $periodoAnio);
            
            // Limpiar archivos temporales
            deleteDirectory($tempDir);
            
            $msg = "Importacion exitosa: {$registrosInsertados} registros en {$fileInfo['tabla']} ({$duration}s)";
            
            // Verificar si los 4 archivos ya estan importados
            if (verificarTodosImportados()) {
                $msg .= "<br><span class='text-success fw-bold'><i class='fas fa-check-circle me-1'></i>Los 4 archivos han sido importados. Puede ejecutar el Procesamiento (Consolidacion).</span>";
            } else {
                $faltantes = obtenerArchivosFaltantes();
                $msg .= "<br><span class='text-warning'><i class='fas fa-exclamation-circle me-1'></i>Falta importar: <strong>" . implode(', ', $faltantes) . "</strong> para habilitar el procesamiento.</span>";
            }
            
            return ['exito' => true, 'mensaje' => $msg];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        // Registrar error en log
        $duration = round(microtime(true) - $startTime, 2);
        logImportacion([
            'tipo_operacion' => 'IMPORT',
            'tipo_archivo' => $fileInfo['tipo'] ?? 'DESCONOCIDO',
            'nombre_archivo' => $file['name'],
            'tabla_destino' => $fileInfo['tabla'] ?? 'N/A',
            'periodo_mes' => $periodoMes,
            'periodo_anio' => $periodoAnio,
            'registros_procesados' => 0,
            'modo_importacion' => $fileInfo['modo'] ?? 'N/A',
            'estado' => 'ERROR',
            'mensaje' => $e->getMessage(),
            'duracion_segundos' => $duration
        ]);
        
        // Limpiar archivos temporales
        if (is_dir($tempDir)) {
            deleteDirectory($tempDir);
        }
        
        return ['exito' => false, 'mensaje' => 'Error en importacion: ' . $e->getMessage()];
    }
}

/**
 * Leer archivo CSV con manejo de delimitadores y codificacion
 */
function leerCSV($filepath) {
    $data = [];
    $handle = fopen($filepath, 'r');
    if (!$handle) return [];
    
    // Leer todo el contenido para convertir codificacion
    $contenido = stream_get_contents($handle);
    fclose($handle);
    
    // Detectar BOM UTF-8
    $bom = "\xEF\xBB\xBF";
    if (substr($contenido, 0, 3) === $bom) {
        $contenido = substr($contenido, 3);
    }
    
    // Detectar y convertir codificacion si no es UTF-8
    // Los archivos HIS del MINSA suelen venir en Windows-1252 o ISO-8859-1
    $encoding = mb_detect_encoding($contenido, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $contenido = iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $contenido);
    } elseif (!$encoding) {
        // Si no se detecta, intentar convertir desde Windows-1252 (comun en exportaciones HIS)
        $convertido = @iconv('Windows-1252', 'UTF-8//TRANSLIT//IGNORE', $contenido);
        if ($convertido !== false) {
            $contenido = $convertido;
        }
    }
    
    // Detectar delimitador desde la primera linea
    $firstLine = strtok($contenido, "\n");
    $delimitador = ',';
    if (strpos($firstLine, '|') !== false && strpos($firstLine, ',') === false && strpos($firstLine, ';') === false) {
        $delimitador = '|';
    } elseif (strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false) {
        $delimitador = ';';
    }
    
    // Crear stream temporal desde el contenido ya convertido
    $tempStream = fopen('php://temp', 'r+');
    fwrite($tempStream, $contenido);
    rewind($tempStream);
    
    while (($row = fgetcsv($tempStream, 0, $delimitador)) !== false) {
        $data[] = $row;
    }
    fclose($tempStream);
    
    return $data;
}

/**
 * Obtener mensaje de error de upload
 */
function getUploadError($code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamano maximo del servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamano maximo del formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo se subio parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se subio ningun archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
        UPLOAD_ERR_EXTENSION => 'Extension PHP detuvo la subida',
    ];
    return $errors[$code] ?? 'Error desconocido';
}

/**
 * Eliminar directorio recursivamente
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

// Obtener conteos de registros actuales
$counts = [
    'MAESTRO_REGISTRADOR' => $pdo->query("SELECT COUNT(*) FROM MAESTRO_REGISTRADOR")->fetchColumn(),
    'MAESTRO_PERSONAL' => $pdo->query("SELECT COUNT(*) FROM MAESTRO_PERSONAL")->fetchColumn(),
    'MAESTRO_PACIENTE' => $pdo->query("SELECT COUNT(*) FROM MAESTRO_PACIENTE")->fetchColumn(),
    'NOMINAL_TRAMA_NUEVO' => $pdo->query("SELECT COUNT(*) FROM NOMINAL_TRAMA_NUEVO")->fetchColumn(),
    'T_CONSOLIDADO' => $pdo->query("SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetchColumn(),
];

// Obtener periodos disponibles en NOMINAL_TRAMA
$periodos = $pdo->query("SELECT DISTINCT TRIM(Anio) as Anio, TRIM(Mes) as Mes FROM NOMINAL_TRAMA_NUEVO WHERE Anio IS NOT NULL AND Anio != '' AND Mes IS NOT NULL AND Mes != '' ORDER BY Anio DESC, Mes DESC")->fetchAll();

// Obtener estado de importacion de los 4 archivos
$importEstado = obtenerEstadoImportacion();
$todosImportados = verificarTodosImportados();
$archivosFaltantes = obtenerArchivosFaltantes();
$ultimoPeriodoTrama = obtenerUltimoPeriodoTrama();

// Obtener ultimos 16 registros del log de importacion
$ultimosLogs = $pdo->query("SELECT * FROM LOG_IMPORTACION ORDER BY fecha_operacion DESC LIMIT 16")->fetchAll();

// Procesar mensajes de sesion (desde process.php)
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipoMensaje = $_SESSION['tipo_mensaje'];
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
}

$pageTitle = 'Importar Datos - Sistema HIS';
include 'includes/header.php';
?>

<!-- Panel de Estado de Importacion -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm <?= $todosImportados ? 'border-success' : 'border-warning' ?>">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-tasks me-2 text-primary"></i>Estado de Importacion Requerida
                </h6>
                <?php if ($todosImportados): ?>
                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>4/4 Completado</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6"><?= 4 - count($archivosFaltantes) ?>/4 Importados</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php 
                    $iconos = [
                        'MaestroRegistrador' => ['icon' => 'fa-user-check', 'color' => 'primary', 'label' => 'Maestro Registrador'],
                        'MaestroPersonal' => ['icon' => 'fa-user-md', 'color' => 'success', 'label' => 'Maestro Personal'],
                        'MaestroPaciente' => ['icon' => 'fa-user-injured', 'color' => 'info', 'label' => 'Maestro Paciente'],
                        'MaestroTrama' => ['icon' => 'fa-file-medical', 'color' => 'warning', 'label' => 'Nominal Trama'],
                    ];
                    foreach ($importEstado as $tipo => $info):
                        $cfg = $iconos[$tipo] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => $tipo];
                    ?>
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="import-status-card <?= $info['importado'] ? 'status-done' : 'status-pending' ?>">
                            <div class="d-flex align-items-center gap-3">
                                <div class="status-icon bg-<?= $cfg['color'] ?> <?= $info['importado'] ? '' : 'opacity-50' ?>">
                                    <i class="fas <?= $cfg['icon'] ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?= $cfg['label'] ?></div>
                                    <?php if ($info['importado']): ?>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?= number_format($info['registros_importados']) ?> regs.
                                            <?= formatDateTime($info['fecha_importacion']) ?>
                                        </small>
                                        <?php if ($tipo === 'NominalTrama' && $info['periodo_anio'] && $info['periodo_mes']): ?>
                                            <small class="text-muted d-block">Periodo: <?= getNombreMes($info['periodo_mes']) ?> <?= $info['periodo_anio'] ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-danger"><i class="fas fa-times-circle me-1"></i>Pendiente</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!$todosImportados): ?>
                <div class="alert alert-warning mt-3 mb-0 py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Falta importar: <?= implode(', ', $archivosFaltantes) ?></strong>. 
                    El procesamiento (consolidacion) se habilitara solo cuando los 4 archivos hayan sido importados.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mensaje de resultado -->
<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
    <?= $mensaje ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Formulario de Importacion y Log de Auditoria -->
<div class="row">
    <div class="col-lg-8">
        <!-- Formulario ZIP -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-import me-2 text-primary"></i>Importar Archivo ZIP</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="importForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <!-- Seleccion de periodo (solo para NominalTrama) -->
                    <div class="row mb-3" id="periodoSection" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar me-1"></i> Periodo - Anio
                            </label>
                            <select name="periodo_anio" id="periodo_anio" class="form-select">
                                <option value="">-- Seleccionar Anio --</option>
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt me-1"></i> Periodo - Mes
                            </label>
                            <select name="periodo_mes" id="periodo_mes" class="form-select">
                                <option value="">-- Seleccionar Mes --</option>
                                <?php for ($m = 1; $m <= 12; $m++):
                                    $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                                ?>
                                    <option value="<?= $mStr ?>"><?= getNombreMes($mStr) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                <small>Para NominalTrama se reemplazaran solo los datos del periodo seleccionado.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Archivo ZIP -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-file-archive me-1"></i> Archivo ZIP
                        </label>
                        <input type="file" name="archivo_zip" id="archivo_zip" class="form-control" 
                               accept=".zip" required>
                        <div class="form-text">
                            Seleccione un archivo .zip que contenga un archivo .csv en su interior.
                            Tamano maximo: 100MB.
                        </div>
                    </div>
                    
                    <!-- Info del tipo detectado -->
                    <div id="tipoDetectado" class="alert alert-secondary d-none mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="tipoDetectadoText"></span>
                    </div>
                    
                    <!-- Boton de importar -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-his btn-lg" id="btnImportar">
                            <i class="fas fa-upload me-2"></i> Importar Datos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ultimos 16 registros del Log de Auditoria -->
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard-list me-2 text-primary"></i>Ultimos Registros del Log de Auditoria</h6>
                <a href="log.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i> Ver Log Completo</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimosLogs)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0 small">No se encontraron registros en el log</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Archivo</th>
                                    <th>Tabla Destino</th>
                                    <th>Periodo</th>
                                    <th>Registros</th>
                                    <th>Modo</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Duracion</th>
                                    <th>Mensaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosLogs as $log): ?>
                                <tr>
                                    <td class="text-muted"><?= $log['id_log'] ?></td>
                                    <td><?= formatDateTime($log['fecha_operacion']) ?></td>
                                    <td>
                                        <?php if ($log['tipo_operacion'] === 'IMPORT'): ?>
                                            <span class="badge bg-primary"><i class="fas fa-file-import me-1"></i>Import</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-cogs me-1"></i>Process</span>
                                        <?php endif; ?>
                                    </td>
                                    <td title="<?= clean($log['nombre_archivo']) ?>">
                                        <?= clean(mb_strimwidth($log['nombre_archivo'] ?? '-', 0, 25, '...')) ?>
                                    </td>
                                    <td><code><?= clean($log['tabla_destino'] ?? '-') ?></code></td>
                                    <td>
                                        <?php if ($log['periodo_anio'] && $log['periodo_mes']): ?>
                                            <?= getNombreMes($log['periodo_mes']) ?> <?= $log['periodo_anio'] ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= number_format($log['registros_procesados']) ?></td>
                                    <td>
                                        <?php if ($log['modo_importacion'] === 'REEMPLAZO'): ?>
                                            <span class="badge bg-danger">Reemplazo</span>
                                        <?php elseif ($log['modo_importacion'] === 'PERIODO'): ?>
                                            <span class="badge bg-warning text-dark">Periodo</span>
                                        <?php elseif ($log['modo_importacion'] === 'COMPLETO'): ?>
                                            <span class="badge bg-info text-dark">Completo</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= clean($log['usuario']) ?></td>
                                    <td>
                                        <?php if ($log['estado'] === 'EXITO'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Exito</span>
                                        <?php elseif ($log['estado'] === 'ERROR'): ?>
                                            <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Error</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $log['duracion_segundos'] ? $log['duracion_segundos'] . 's' : '-' ?></td>
                                    <td title="<?= clean($log['mensaje']) ?>">
                                        <?= clean(mb_strimwidth($log['mensaje'] ?? '-', 0, 40, '...')) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Panel lateral -->
    <div class="col-lg-4">
        <!-- Guia de Importacion -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-question-circle me-2 text-primary"></i>Guia de Importacion</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="fw-semibold text-primary">MaestroRegistrador</h6>
                    <p class="small text-muted mb-1">Archivo: MaestroRegistradorxxxxxxx.zip</p>
                    <span class="badge bg-danger">Reemplazo total</span>
                </div>
                <div class="mb-3">
                    <h6 class="fw-semibold text-success">MaestroPersonal</h6>
                    <p class="small text-muted mb-1">Archivo: MaestroPersonalxxxxxxx.zip</p>
                    <span class="badge bg-danger">Reemplazo total</span>
                </div>
                <div class="mb-3">
                    <h6 class="fw-semibold text-info">MaestroPaciente</h6>
                    <p class="small text-muted mb-1">Archivo: MaestroPacientexxxxxxx.zip</p>
                    <span class="badge bg-danger">Reemplazo total</span>
                </div>
                <div class="mb-3">
                    <h6 class="fw-semibold text-warning">NominalTrama</h6>
                    <p class="small text-muted mb-1">Archivo: NominalTramaxxxxxxxx_xxxxxx.zip</p>
                    <span class="badge bg-warning text-dark">Reemplazo por periodo</span>
                </div>
                <hr>
                <div class="alert alert-warning py-2 mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <small>El <strong>Procesamiento (Consolidacion)</strong> se ejecuta solo despues de importar los 4 archivos.</small>
                </div>
            </div>
        </div>
        
        <!-- Procesamiento / Consolidacion -->
        <div class="card shadow-sm mt-3 <?= $todosImportados ? 'border-success' : '' ?>">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-cogs me-2 text-primary"></i>Procesamiento (Consolidacion)</h6>
            </div>
            <div class="card-body">
                <?php if ($todosImportados): ?>
                    <!-- Los 4 archivos importados: habilitar procesamiento -->
                    <div class="alert alert-success py-2 mb-3">
                        <i class="fas fa-check-circle me-1"></i>
                        <strong>Los 4 archivos estan listos.</strong> Puede ejecutar la consolidacion.
                    </div>
                    
                    <?php if ($ultimoPeriodoTrama): ?>
                    <!-- Opcion 1: Procesar periodo de NominalTrama importado -->
                    <form method="POST" action="process.php" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="accion" value="procesar">
                        <input type="hidden" name="proc_anio" value="<?= htmlspecialchars($ultimoPeriodoTrama['anio']) ?>">
                        <input type="hidden" name="proc_mes" value="<?= htmlspecialchars($ultimoPeriodoTrama['mes']) ?>">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-play me-1"></i> Procesar Periodo: <?= getNombreMes($ultimoPeriodoTrama['mes']) ?> <?= $ultimoPeriodoTrama['anio'] ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Opcion 2: Procesar por periodo seleccionado -->
                    <form method="POST" action="process.php" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="accion" value="procesar">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <select name="proc_anio" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php foreach (array_unique(array_column($periodos, 'Anio')) as $a): ?>
                                        <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="proc_mes" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <?php for ($m = 1; $m <= 12; $m++):
                                        $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                                    ?>
                                        <option value="<?= $mStr ?>"><?= getNombreMes($mStr) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-his btn-sm w-100">
                            <i class="fas fa-play me-1"></i> Procesar por Periodo
                        </button>
                    </form>
                    
                    <!-- Opcion 3: Procesamiento completo -->
                    <form method="POST" action="process.php">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="accion" value="procesar_completo">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100" 
                                onclick="return confirm('Se reconstruira COMPLETAMENTE el consolidado. Esto puede tardar varios minutos. Desea continuar?')">
                            <i class="fas fa-sync-alt me-1"></i> Reconstruccion Completa del Consolidado
                        </button>
                    </form>
                    
                    <!-- Resetear estado -->
                    <hr>
                    <form method="POST" action="process.php">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="accion" value="resetear">
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-redo me-1"></i> Resetear Estado de Importacion
                        </button>
                    </form>
                    
                <?php else: ?>
                    <!-- Faltan archivos: procesamiento deshabilitado -->
                    <div class="text-center py-3">
                        <i class="fas fa-lock fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2 small">El procesamiento se habilitara cuando los 4 archivos sean importados.</p>
                        <div class="mb-0">
                            <?php foreach ($archivosFaltantes as $falt): ?>
                                <span class="badge bg-warning text-dark me-1 mb-1"><?= $falt ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Conteo de registros en tablas -->
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-database me-2 text-primary"></i>Registros en Tablas</h6>
            </div>
            <div class="card-body py-2">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small">MAESTRO_REGISTRADOR</span>
                    <span class="fw-bold small"><?= number_format($counts['MAESTRO_REGISTRADOR']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small">MAESTRO_PERSONAL</span>
                    <span class="fw-bold small"><?= number_format($counts['MAESTRO_PERSONAL']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small">MAESTRO_PACIENTE</span>
                    <span class="fw-bold small"><?= number_format($counts['MAESTRO_PACIENTE']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small">NOMINAL_TRAMA_NUEVO</span>
                    <span class="fw-bold small"><?= number_format($counts['NOMINAL_TRAMA_NUEVO']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="small">T_CONSOLIDADO</span>
                    <span class="fw-bold small text-danger"><?= number_format($counts['T_CONSOLIDADO']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('archivo_zip').addEventListener('change', function() {
    const filename = this.value.toUpperCase();
    const periodoSection = document.getElementById('periodoSection');
    const tipoDetectado = document.getElementById('tipoDetectado');
    const tipoDetectadoText = document.getElementById('tipoDetectadoText');
    
    if (filename.includes('NOMINALTRAMA')) {
        periodoSection.style.display = 'block';
        tipoDetectado.className = 'alert alert-warning mb-3';
        tipoDetectadoText.textContent = 'Tipo detectado: NominalTrama - Reemplazo por periodo. Seleccione Mes y Anio.';
    } else if (filename.includes('MAESTROREGISTRADOR')) {
        periodoSection.style.display = 'none';
        tipoDetectado.className = 'alert alert-primary mb-3';
        tipoDetectadoText.textContent = 'Tipo detectado: MaestroRegistrador - Reemplazo total de datos.';
    } else if (filename.includes('MAESTROPERSONAL')) {
        periodoSection.style.display = 'none';
        tipoDetectado.className = 'alert alert-success mb-3';
        tipoDetectadoText.textContent = 'Tipo detectado: MaestroPersonal - Reemplazo total de datos.';
    } else if (filename.includes('MAESTROPACIENTE')) {
        periodoSection.style.display = 'none';
        tipoDetectado.className = 'alert alert-info mb-3';
        tipoDetectadoText.textContent = 'Tipo detectado: MaestroPaciente - Reemplazo total de datos.';
    } else {
        periodoSection.style.display = 'none';
        tipoDetectado.className = 'alert alert-secondary d-none mb-3';
    }
});

// Confirmacion antes de importar
document.getElementById('importForm').addEventListener('submit', function(e) {
    const filename = document.getElementById('archivo_zip').value.toUpperCase();
    let msg = 'Esta a punto de importar datos. ';
    
    if (filename.includes('NOMINALTRAMA')) {
        const anio = document.getElementById('periodo_anio').value;
        const mes = document.getElementById('periodo_mes').value;
        if (!anio || !mes) {
            e.preventDefault();
            alert('Debe seleccionar el periodo (Anio y Mes) para la importacion de NominalTrama.');
            return;
        }
        msg += 'Se reemplazaran los datos del periodo seleccionado en NominalTrama. El procesamiento se ejecutara despues de importar los 4 archivos.';
    } else if (filename.includes('MAESTRO')) {
        msg += 'Se eliminaran TODOS los registros actuales de la tabla y se reemplazaran con los nuevos. El procesamiento se ejecutara despues de importar los 4 archivos.';
    }
    
    if (!confirm(msg)) {
        e.preventDefault();
    }
    
    // Mostrar indicador de carga
    document.getElementById('btnImportar').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Importando...';
    document.getElementById('btnImportar').disabled = true;
});
</script>

<?php include 'includes/footer.php'; ?>
