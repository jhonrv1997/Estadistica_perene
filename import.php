<?php
/**
 * Sistema de Gestion de Datos HIS
 * Modulo de Importacion de Datos
 */

require_once 'includes/auth.php';
verificarAutenticacion();
verificarAdmin();

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
    
    // CRITICO: Prevenir timeout de PHP y agotamiento de memoria.
    // MaestroPaciente y NominalTrama pueden tener millones de filas y
    // el procesamiento en hosts compartidos (InfinityFree) excede los
    // 30s por defecto de max_execution_time.
    @set_time_limit(0);
    @ignore_user_abort(true);
    @ini_set('memory_limit', '1024M');
    
    // CRITICO: Desactivar el timeout por consulta de MariaDB/MySQL para
    // esta sesion. InfinityFree (MariaDB 10.x) impone max_statement_time
    // (~30s por consulta por defecto) que interrumpe los INSERT masivos
    // de NominalTrama sobre una tabla con 10+ indices (idx_ntn_*).
    // Sintoma: la PRIMERA carga de NominalTrama falla con
    //   SQLSTATE[70100]: 1969 Query execution was interrupted
    // y la segunda carga funciona porque las paginas de indices ya estan
    // cacheadas en el buffer pool. Desactivando max_statement_time se
    // elimina el fallo intermitente desde el primer intento.
    // Cada SET se envuelve en try/catch porque algunos hosts compartidos
    // no permiten cambiar variables de sesion (en ese caso el ajuste se
    // ignora silenciosamente y se mantiene el limite por defecto).
    try { $pdo->exec("SET SESSION max_statement_time = 0"); } catch (Exception $e) {}
    try { $pdo->exec("SET SESSION wait_timeout = 28800"); } catch (Exception $e) {}
    try { $pdo->exec("SET SESSION interactive_timeout = 28800"); } catch (Exception $e) {}
    try { $pdo->exec("SET SESSION net_read_timeout = 600"); } catch (Exception $e) {}
    try { $pdo->exec("SET SESSION net_write_timeout = 600"); } catch (Exception $e) {}
    
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
        
        // Extraer archivo por archivo para manejar correctamente nombres
        // con caracteres no-ASCII (algunos ZIPs vienen con encoding CP437).
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) continue;
            $entryName = $stat['name'];
            // Evitar path traversal
            if (strpos($entryName, '..') !== false) continue;
            // Directorio
            if (substr($entryName, -1) === '/') {
                @mkdir($tempDir . $entryName, 0777, true);
                continue;
            }
            // Crear subdirectorio si hace falta
            $subdir = dirname($entryName);
            if ($subdir && $subdir !== '.') {
                @mkdir($tempDir . $subdir, 0777, true);
            }
            // Extraer contenido
            $stream = $zip->getStream($entryName);
            if ($stream === false) {
                // Fallback a getFromIndex
                $contents = $zip->getFromIndex($i);
                if ($contents !== false) {
                    @file_put_contents($tempDir . $entryName, $contents);
                }
            } else {
                $out = @fopen($tempDir . $entryName, 'wb');
                if ($out) {
                    stream_copy_to_stream($stream, $out);
                    fclose($out);
                }
                fclose($stream);
            }
        }
        $zip->close();
        
        // Buscar archivo CSV dentro del ZIP
        // Estrategia: si hay varios CSVs, elegir el de mayor tamano
        // (evita tomar un README.csv o ejemplo.csv vacio).
        $csvFile = null;
        $csvFileSize = 0;
        $csvCandidates = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'csv') {
                $size = $item->getSize();
                $csvCandidates[] = ['path' => $item->getPathname(), 'size' => $size];
                if ($size > $csvFileSize) {
                    $csvFileSize = $size;
                    $csvFile = $item->getPathname();
                }
            }
        }
        
        // Tambien considerar archivos sin extension si el ZIP venia sin .csv
        // pero con un nombre obvio. (Solo si no se encontro ningun .csv)
        if (!$csvFile) {
            $iterator2 = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator2 as $item) {
                if ($item->isFile()) {
                    $nombre = strtolower($item->getFilename());
                    // Algunos HIS exportan como .txt cuando en realidad es CSV
                    if (in_array($item->getExtension(), ['txt', 'dat'], true)) {
                        $csvFile = $item->getPathname();
                        $csvFileSize = $item->getSize();
                        break;
                    }
                }
            }
        }
        
        if (!$csvFile) {
            $lista = [];
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item) {
                if ($item->isFile()) {
                    $lista[] = $item->getFilename();
                }
            }
            $listaStr = implode(', ', $lista);
            throw new Exception('No se encontro ningun archivo .csv dentro del archivo ZIP. Archivos encontrados: ' . $listaStr);
        }
        
        if ($csvFileSize === 0) {
            throw new Exception('El archivo CSV encontrado dentro del ZIP esta vacio (0 bytes). Verifique el contenido del ZIP.');
        }
        
        // Obtener columnas de la tabla destino
        $columnas = getColumnMapping($fileInfo['tipo']);
        $dateColumns = getDateColumns($fileInfo['tipo']);
        
        if (empty($columnas)) {
            throw new Exception('No se encontraron columnas mapeadas para el tipo: ' . $fileInfo['tipo']);
        }
        
        // Detectar engine de la tabla para decidir uso de transacciones.
        // El schema del sistema usa ENGINE=MyISAM, que NO soporta transacciones:
        // llamar a beginTransaction/rollBack sobre MyISAM no da error pero el
        // rollback NO deshace cambios. Peor aun: si una fila falla a mitad del
        // INSERT masivo, los datos previos ya estaban persistidos. Por eso se
        // usa TRUNCATE (en lugar de DELETE) para MyISAM en modo REEMPLAZO, y
        // se omite beginTransaction/commit para evitar la confusion de un
        // rollback que no hace nada.
        $tablaEngine = obtenerEngineTabla($pdo, $fileInfo['tabla']);
        $soportaTransacciones = ($tablaEngine === 'InnoDB');
        
        if ($soportaTransacciones) {
            $pdo->beginTransaction();
        }
        
        try {
            // Modo de importacion: siempre TRUNCATE (vaciado total) antes de insertar
            // Aplica a las 4 tablas: MAESTRO_REGISTRADOR, MAESTRO_PERSONAL,
            // MAESTRO_PACIENTE y NOMINAL_TRAMA_NUEVO
            if ($soportaTransacciones) {
                $pdo->exec("DELETE FROM `{$fileInfo['tabla']}`");
            } else {
                $pdo->exec("TRUNCATE TABLE `{$fileInfo['tabla']}`");
            }
            
            // =========================================================
            // INSERCION STREAMING DEL CSV
            // =========================================================
            // Se lee el CSV fila por fila (sin cargar todo en memoria) y se
            // inserta en lotes pequenos usando prepared statements con
            // placeholders tipados (?). Esto evita dos problemas clasicos en
            // hosts compartidos (InfinityFree):
            //   1. memory_limit agotado al cargar millones de filas en $csvData.
            //   2. max_allowed_packet (1 MB) excedido al construir un INSERT
            //      gigante con 500 filas x 47 columnas (NominalTrama, Alerta
            //      varchar(3000)) -> PDOException -> HTTP ERROR 500.
            // Con batch=50 y placeholders vinculados, cada lote ocupa ~150 KB
            // como maximo, muy por debajo del limite.
            // =========================================================
            $batchSize = 50;
            $registrosInsertados = 0;
            $filasLeidas = 0;
            
            $csvStream = leerCSVStream($csvFile);
            if (!$csvStream) {
                // Diagnostico adicional para identificar la causa real
                $diag = 'No se pudo abrir el CSV para lectura streaming. ';
                $diag .= 'Tamano: ' . $csvFileSize . ' bytes. ';
                $diag .= 'Ruta: ' . basename($csvFile) . '. ';
                $fp = @fopen($csvFile, 'rb');
                if ($fp) {
                    $muestra = fread($fp, 200);
                    fclose($fp);
                    $diag .= 'Primeros bytes (hex): ' . bin2hex(substr($muestra, 0, 30));
                }
                throw new Exception('El archivo CSV no se pudo abrir. ' . $diag);
            }
            
            // Leer encabezado (primera fila) y descartarlo
            $header = fgetcsv($csvStream['handle'], 0, $csvStream['delimitador']);
            if ($header === false || $header === null) {
                fclose($csvStream['handle']);
                throw new Exception('El archivo CSV esta vacio o no se pudo leer la cabecera. Tamano: ' . $csvFileSize . ' bytes.');
            }
            
            // Plantilla de placeholders para una fila: (?, ?, ..., ?)
            $rowPlaceholder = '(' . implode(',', array_fill(0, count($columnas), '?')) . ')';
            $batch = [];
            
            while (($row = fgetcsv($csvStream['handle'], 0, $csvStream['delimitador'])) !== false) {
                // Saltar filas completamente vacias (fgetcsv puede devolver [''] )
                if (count($row) === 1 && $row[0] === '') {
                    continue;
                }
                $filasLeidas++;
                
                // Convertir encoding de cada celda (Windows-1252 / ISO-8859-1 -> UTF-8)
                foreach ($row as &$cell) {
                    $cell = $csvStream['convertEncoding']($cell);
                }
                unset($cell);
                
                // Mapear valores CSV a parametros de la tabla.
                // A diferencia de sqlValue() que retorna literales SQL (con comillas
                // y addslashes), aqui se pasan valores nativos a PDO::execute()
                // que se encarga de escapar de forma segura via protocolo binario.
                $rowParams = [];
                for ($c = 0; $c < count($columnas); $c++) {
                    $val = isset($row[$c]) ? trim($row[$c]) : '';
                    if ($val === '' || $val === 'NULL') {
                        $rowParams[] = null;
                    } elseif (in_array($columnas[$c], $dateColumns)) {
                        // Convertir fecha (acepta dd/mm/yyyy o dd-mm-yyyy) a YYYY-MM-DD
                        $valNorm = str_replace('/', '-', $val);
                        $ts = strtotime($valNorm);
                        $rowParams[] = ($ts === false) ? null : date('Y-m-d', $ts);
                    } else {
                        $rowParams[] = $val;
                    }
                }
                
                $batch[] = $rowParams;
                
                // Ejecutar lote cuando se completa el tamano de batch
                if (count($batch) >= $batchSize) {
                    ejecutarLoteInsert($pdo, $fileInfo['tabla'], $columnas, $batch, $rowPlaceholder);
                    $registrosInsertados += count($batch);
                    $batch = [];
                }
            }
            fclose($csvStream['handle']);
            
            // Insertar lote final (filas restantes)
            if (!empty($batch)) {
                ejecutarLoteInsert($pdo, $fileInfo['tabla'], $columnas, $batch, $rowPlaceholder);
                $registrosInsertados += count($batch);
                $batch = [];
            }
            
            if ($registrosInsertados === 0) {
                throw new Exception(
                    'No se inserto ningun registro. Filas leidas del CSV: ' . $filasLeidas .
                    '. Verifique que el archivo tenga datos despues de la cabecera y que el delimitador sea correcto.'
                );
            }
            
            if ($soportaTransacciones) {
                $pdo->commit();
            }
            
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
            
            // =====================================================
            // PASO ESPECIAL para NominalTrama (mejora 2.4 + 3):
            // - Eliminar del consolidado los registros del periodo
            //   seleccionado (Anio + Mes)
            // - Ejecutar la consolidacion automaticamente para ese
            //   periodo despues de la importacion
            // =====================================================
            $consolidacionMsg = '';
            if ($fileInfo['tipo'] === 'NominalTrama' && $periodoAnio && $periodoMes) {
                // 2.4: Eliminar registros del consolidado para el periodo seleccionado
                $stmtDel = $pdo->prepare("DELETE FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE TRIM(Anio) = ? AND CAST(TRIM(Mes) AS UNSIGNED) = ?");
                $stmtDel->execute([$periodoAnio, intval($periodoMes)]);
                $regsEliminadosConsolidado = $stmtDel->rowCount();
                
                // 3: Ejecutar consolidacion automaticamente para el periodo importado
                try {
                    $resultadoConsolidacion = ejecutarProcesamiento($periodoAnio, $periodoMes);
                    if ($resultadoConsolidacion['success']) {
                        $consolidacionMsg = "<br><span class='text-info fw-bold'><i class='fas fa-cogs me-1'></i>Consolidacion automatica ejecutada para " . getNombreMes($periodoMes) . " {$periodoAnio}: " . number_format($resultadoConsolidacion['registros']) . " registros consolidados (" . $resultadoConsolidacion['duracion'] . "s). Registros previos del consolidado eliminados: " . number_format($regsEliminadosConsolidado) . "</span>";
                    } else {
                        $consolidacionMsg = "<br><span class='text-warning'><i class='fas fa-exclamation-triangle me-1'></i>La consolidacion automatica fallo: " . clean($resultadoConsolidacion['mensaje']) . "</span>";
                    }
                } catch (Exception $eConsol) {
                    $consolidacionMsg = "<br><span class='text-warning'><i class='fas fa-exclamation-triangle me-1'></i>Error en consolidacion automatica: " . clean($eConsol->getMessage()) . "</span>";
                }
            }
            
            // Verificar si los 4 archivos ya estan importados
            if (verificarTodosImportados()) {
                $msg .= "<br><span class='text-success fw-bold'><i class='fas fa-check-circle me-1'></i>Los 4 archivos han sido importados.";
                if ($consolidacionMsg) {
                    $msg .= $consolidacionMsg;
                } else {
                    $msg .= "</span>";
                }
            } else {
                $faltantes = obtenerArchivosFaltantes();
                $msg .= "<br><span class='text-warning'><i class='fas fa-exclamation-circle me-1'></i>Falta importar: <strong>" . implode(', ', $faltantes) . "</strong> para completar la importacion.";
                if ($consolidacionMsg) {
                    $msg .= $consolidacionMsg;
                }
            }
            
            return ['exito' => true, 'mensaje' => $msg];
            
        } catch (Exception $e) {
            // Solo intentar rollback si la tabla soporta transacciones (InnoDB).
            // Para MyISAM, rollBack() lanzaria una PDOException "There is no
            // active transaction" que enmascararia la excepcion original.
            if ($soportaTransacciones) {
                try { $pdo->rollBack(); } catch (Exception $rbErr) { /* ignorar */ }
            }
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
 * Leer archivo CSV con manejo de delimitadores y codificacion.
 * Robusto frente a:
 *   - Archivos grandes (no carga todo el archivo en memoria).
 *   - Distintos fines de linea (\r\n, \r, \n).
 *   - BOM UTF-8 al inicio.
 *   - Codificaciones Windows-1252 / ISO-8859-1 (comunes en exportaciones HIS).
 *   - Delimitadores | ; , (autodeteccion).
 *   - memory_limit insuficiente (se eleva temporalmente).
 *
 * Retorna array de filas (cada fila = array de strings) o [] si falla.
 */
function leerCSV($filepath) {
    if (!is_file($filepath) || !is_readable($filepath)) {
        return [];
    }

    // Activar deteccion automatica de fines de linea para archivos viejos
    // (Mac usa \r, Windows usa \r\n). En PHP 8.1+ es obsoleto pero inofensivo.
    @ini_set('auto_detect_line_endings', '1');

    // Subir memory_limit temporalmente para archivos grandes
    // (MaestroPaciente / NominalTrama pueden tener millones de filas).
    $currentLimit = ini_get('memory_limit');
    if ($currentLimit !== '-1') {
        $currentBytes = returnBytesFromLimit($currentLimit);
        // Al menos 512M
        if ($currentBytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
    }

    // Detectar BOM y leer primeras lineas en crudo para identificar
    // delimitador y codificacion sin cargar todo el archivo en memoria.
    $rawFirst = '';
    $fp = fopen($filepath, 'rb');
    if (!$fp) {
        return [];
    }
    // Leer hasta 8 KB para inspeccionar cabecera (suficiente para detectar
    // delimitador y BOM).
    $rawFirst = fread($fp, 8192);
    fclose($fp);

    // Quitar BOM UTF-8 si existe
    $bom = "\xEF\xBB\xBF";
    $hasBom = (substr($rawFirst, 0, 3) === $bom);
    if ($hasBom) {
        $rawFirst = substr($rawFirst, 3);
    }

    // Detectar codificacion usando la muestra inicial
    $encoding = mb_detect_encoding($rawFirst, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);

    // Decidir funcion de conversion por linea
    $convertEncoding = function($line) use ($encoding, $hasBom) {
        // Quitar BOM si esta embebido al inicio de la primera linea
        if (strlen($line) >= 3 && substr($line, 0, 3) === "\xEF\xBB\xBF") {
            $line = substr($line, 3);
        }
        if ($encoding === 'UTF-8' || $encoding === 'ASCII') {
            return $line;
        }
        if ($encoding) {
            $converted = @iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $line);
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }
        // Fallback: intentar Windows-1252 (comun en exportaciones HIS del MINSA)
        $converted = @iconv('Windows-1252', 'UTF-8//TRANSLIT//IGNORE', $line);
        if ($converted !== false && $converted !== '') {
            return $converted;
        }
        return $line;
    };

    // Detectar delimitador desde la primera linea (ya convertida)
    $firstLineConverted = $convertEncoding($rawFirst);
    // Tomar solo hasta el primer \n para no mezclar lineas
    $firstLineForDelimiter = strtok($firstLineConverted, "\n");
    $firstLineForDelimiter = str_replace(["\r\n", "\r"], ["\n", "\n"], $firstLineForDelimiter);

    $delimitador = ',';
    $hasPipe = strpos($firstLineForDelimiter, '|') !== false;
    $hasSemi = strpos($firstLineForDelimiter, ';') !== false;
    $hasComma = strpos($firstLineForDelimiter, ',') !== false;
    if ($hasPipe && !$hasComma && !$hasSemi) {
        $delimitador = '|';
    } elseif ($hasSemi && !$hasComma) {
        $delimitador = ';';
    }

    // Ahora procesar el archivo linea por linea usando fgetcsv.
    // fgetcsv con length=0 (sin limite) y auto_detect_line_endings=1.
    $handle = fopen($filepath, 'rb');
    if (!$handle) {
        return [];
    }

    $data = [];
    $rowIndex = 0;
    while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
        // Si la fila completa vino en un solo campo porque no se detecto el
        // delimitador correcto, intentar re-parsear con str_getcsv probando
        // otros delimitadores.
        if (count($row) === 1 && $delimitador !== '|' && $delimitador !== ';') {
            $line = $row[0];
            if (strpos($line, '|') !== false && strpos($line, ',') === false) {
                $row = str_getcsv($line, '|');
            } elseif (strpos($line, ';') !== false && strpos($line, ',') === false) {
                $row = str_getcsv($line, ';');
            }
        }

        // Convertir codificacion de cada celda
        foreach ($row as &$cell) {
            $cell = $convertEncoding($cell);
        }
        unset($cell);

        // Quitar BOM del primer encabezado si quedo
        if ($rowIndex === 0 && isset($row[0]) && strlen($row[0]) >= 3 && substr($row[0], 0, 3) === "\xEF\xBB\xBF") {
            $row[0] = substr($row[0], 3);
        }

        $data[] = $row;
        $rowIndex++;
    }
    fclose($handle);

    return $data;
}

/**
 * Convierte un valor de memory_limit tipo "128M" / "1G" a bytes.
 */
function returnBytesFromLimit($val) {
    if ($val === '-1') return -1;
    $val = trim($val);
    $last = strtolower(substr($val, -1));
    $num = (int)$val;
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
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

/**
 * Obtener el ENGINE de una tabla (InnoDB, MyISAM, ...).
 * Consulta information_schema.TABLES. Si falla (permisos o tabla
 * no existe), asume MyISAM (que es lo que usa el schema del sistema).
 */
function obtenerEngineTabla($pdo, $tabla) {
    try {
        $stmt = $pdo->prepare(
            "SELECT ENGINE FROM information_schema.TABLES "
            . "WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
        );
        $stmt->execute([DB_NAME, $tabla]);
        $engine = $stmt->fetchColumn();
        return $engine ?: 'MyISAM';
    } catch (Exception $e) {
        return 'MyISAM';
    }
}

/**
 * Abrir un CSV para lectura STREAMING (fila por fila) sin cargar todo
 * el archivo en memoria. Detecta BOM UTF-8, encoding (Windows-1252 /
 * ISO-8859-1 / UTF-8) y delimitador (| ; ,) a partir de los primeros
 * 8 KB del archivo.
 *
 * Retorna: ['handle' => resource, 'delimitador' => string, 'convertEncoding' => callable]
 *          o false si no se pudo abrir.
 */
function leerCSVStream($filepath) {
    if (!is_file($filepath) || !is_readable($filepath)) {
        return false;
    }

    @ini_set('auto_detect_line_endings', '1');

    // Subir memory_limit temporalmente (la conversion iconv por celda
    // puede usar memoria adicional incluso en modo streaming).
    $currentLimit = ini_get('memory_limit');
    if ($currentLimit !== '-1') {
        $currentBytes = returnBytesFromLimit($currentLimit);
        if ($currentBytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
    }

    // Leer primeros 8 KB para inspeccionar cabecera (BOM, encoding, delimitador)
    $fpInspect = fopen($filepath, 'rb');
    if (!$fpInspect) return false;
    $rawFirst = fread($fpInspect, 8192);
    fclose($fpInspect);

    // Quitar BOM UTF-8 si existe
    $bom = "\xEF\xBB\xBF";
    $hasBom = (substr($rawFirst, 0, 3) === $bom);
    if ($hasBom) {
        $rawFirst = substr($rawFirst, 3);
    }

    // Detectar codificacion de la muestra inicial
    $encoding = mb_detect_encoding($rawFirst, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);

    // Funcion de conversion por celda (cierra sobre $encoding y $hasBom)
    $convertEncoding = function($line) use ($encoding, $hasBom) {
        if (strlen($line) >= 3 && substr($line, 0, 3) === "\xEF\xBB\xBF") {
            $line = substr($line, 3);
        }
        if ($encoding === 'UTF-8' || $encoding === 'ASCII') {
            return $line;
        }
        if ($encoding) {
            $converted = @iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $line);
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }
        // Fallback: Windows-1252 (comun en exportaciones HIS del MINSA)
        $converted = @iconv('Windows-1252', 'UTF-8//TRANSLIT//IGNORE', $line);
        if ($converted !== false && $converted !== '') {
            return $converted;
        }
        return $line;
    };

    // Detectar delimitador desde la primera linea (ya convertida)
    $firstLineConverted = $convertEncoding($rawFirst);
    $firstLineForDelimiter = strtok($firstLineConverted, "\n");
    $firstLineForDelimiter = str_replace(["\r\n", "\r"], ["\n", "\n"], $firstLineForDelimiter);

    $delimitador = ',';
    $hasPipe = strpos($firstLineForDelimiter, '|') !== false;
    $hasSemi = strpos($firstLineForDelimiter, ';') !== false;
    $hasComma = strpos($firstLineForDelimiter, ',') !== false;
    if ($hasPipe && !$hasComma && !$hasSemi) {
        $delimitador = '|';
    } elseif ($hasSemi && !$hasComma) {
        $delimitador = ';';
    }

    // Abrir archivo para lectura streaming
    $handle = fopen($filepath, 'rb');
    if (!$handle) return false;

    // Si tenia BOM, saltar los 3 bytes del BOM para que fgetcsv no los
    // incluya en el primer campo del encabezado.
    if ($hasBom) {
        fread($handle, 3);
    }

    return [
        'handle' => $handle,
        'delimitador' => $delimitador,
        'convertEncoding' => $convertEncoding,
        'encoding' => $encoding,
    ];
}

/**
 * Ejecutar un INSERT multi-fila usando prepared statement con placeholders.
 *
 * @param PDO    $pdo           Conexion PDO
 * @param string $tabla         Nombre de la tabla destino
 * @param array  $columnas      Lista de nombres de columnas
 * @param array  $batch         Lote de filas (cada fila = array de valores nativos)
 * @param string $rowPlaceholder Plantilla '(?,?,?)' para una fila
 */
function ejecutarLoteInsert($pdo, $tabla, $columnas, $batch, $rowPlaceholder) {
    $rowCount = count($batch);
    $allPlaceholders = implode(',', array_fill(0, $rowCount, $rowPlaceholder));
    $sql = "INSERT INTO `{$tabla}` (`" . implode('`,`', $columnas) . "`) VALUES " . $allPlaceholders;
    $stmt = $pdo->prepare($sql);

    // Aplanar el batch (array 2D) a un array 1D de parametros para execute()
    $params = [];
    foreach ($batch as $rowParams) {
        foreach ($rowParams as $val) {
            $params[] = $val;
        }
    }

    try {
        $stmt->execute($params);
    } catch (PDOException $e) {
        // Recuperacion ante el error 1969 de MariaDB:
        //   SQLSTATE[70100]: 1969 Query execution was interrupted
        //   (max_statement_time exceeded)
        // Si SET SESSION max_statement_time=0 no tuvo efecto (hosts
        // compartidos que lo prohiben), o si el lote es demasiado grande
        // para el limite por consulta, dividimos el lote a la mitad y
        // reintentamos recursivamente. Con batch=50 el primer fallo baja
        // a 25, luego 12, 6, 3, 1 - en el peor caso se inserta fila por
        // fila, que es lo suficientemente rapido para no exceder el limite.
        $isMaxStmtTime = (
            $e->getCode() === '70100'
            || $e->getCode() === 1969
            || stripos($e->getMessage(), 'max_statement_time') !== false
            || stripos($e->getMessage(), '1969') !== false
            || stripos($e->getMessage(), 'Query execution was interrupted') !== false
        );

        if ($isMaxStmtTime && $rowCount > 1) {
            $mitad = (int) ceil($rowCount / 2);
            $batchA = array_slice($batch, 0, $mitad);
            $batchB = array_slice($batch, $mitad);
            ejecutarLoteInsert($pdo, $tabla, $columnas, $batchA, $rowPlaceholder);
            ejecutarLoteInsert($pdo, $tabla, $columnas, $batchB, $rowPlaceholder);
            return;
        }
        // Otro tipo de error o lote de 1 fila: relanzar para que el
        // llamador lo trate (incluye rollback si aplica).
        throw $e;
    }
}

// Obtener conteos de registros actuales
$counts = [
    'MAESTRO_REGISTRADOR' => $pdo->query("SELECT COUNT(*) FROM MAESTRO_REGISTRADOR")->fetchColumn(),
    'MAESTRO_PERSONAL' => $pdo->query("SELECT COUNT(*) FROM MAESTRO_PERSONAL")->fetchColumn(),
    'MAESTRO_PACIENTE' => $pdo->query("SELECT COUNT(*) FROM MAESTRO_PACIENTE")->fetchColumn(),
    'NOMINAL_TRAMA_NUEVO' => $pdo->query("SELECT COUNT(*) FROM NOMINAL_TRAMA_NUEVO")->fetchColumn(),
    'T_CONSOLIDADO' => $pdo->query("SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetchColumn(),
];

// Obtener estado de importacion de los 4 archivos
$importEstado = obtenerEstadoImportacion();
$todosImportados = verificarTodosImportados();
$archivosFaltantes = obtenerArchivosFaltantes();

// Obtener estado de consolidacion por periodo (compara trama vs consolidado)
$estadoConsolidacion = obtenerEstadoConsolidacionPorPeriodo();
$periodosPendientes = array_filter($estadoConsolidacion, function($p) { return $p['estado'] === 'pendiente'; });
$periodosConsolidados = array_filter($estadoConsolidacion, function($p) { return $p['estado'] === 'consolidado'; });
$hayPeriodosPendientes = count($periodosPendientes) > 0;

// Obtener ultimos 16 registros del log de importacion
$ultimosLogs = $pdo->query("SELECT * FROM LOG_IMPORTACION ORDER BY fecha_operacion DESC LIMIT 16")->fetchAll();

// Mejora 4: Obtener fecha/hora de la ultima importacion exitosa por cada tabla
// Se consulta LOG_IMPORTACION para tener el historial real (no depende de IMPORT_ESTADO)
$ultimaImportacionPorTabla = [];
try {
    // Consulta compatible con cPanel/MySQL sin procedimientos almacenados
    // Usa subquery para obtener la ultima fecha por tabla_destino
    $tablasDestino = ['MAESTRO_REGISTRADOR', 'MAESTRO_PERSONAL', 'MAESTRO_PACIENTE', 'NOMINAL_TRAMA_NUEVO'];
    foreach ($tablasDestino as $tablaDest) {
        $stmtUlt = $pdo->prepare("
            SELECT tipo_archivo, tabla_destino, fecha_operacion, registros_procesados, nombre_archivo
            FROM LOG_IMPORTACION 
            WHERE tipo_operacion = 'IMPORT' AND estado = 'EXITO' AND tabla_destino = ?
            ORDER BY fecha_operacion DESC
            LIMIT 1
        ");
        $stmtUlt->execute([$tablaDest]);
        $rowUlt = $stmtUlt->fetch();
        if ($rowUlt) {
            $ultimaImportacionPorTabla[$tablaDest] = $rowUlt;
        }
    }
} catch (Exception $e) {
    // Fallback silencioso
}

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
                        'MaestroRegistrador' => ['icon' => 'fa-user-check', 'color' => 'primary', 'label' => 'Maestro Registrador', 'tabla' => 'MAESTRO_REGISTRADOR'],
                        'MaestroPersonal' => ['icon' => 'fa-user-md', 'color' => 'success', 'label' => 'Maestro Personal', 'tabla' => 'MAESTRO_PERSONAL'],
                        'MaestroPaciente' => ['icon' => 'fa-user-injured', 'color' => 'info', 'label' => 'Maestro Paciente', 'tabla' => 'MAESTRO_PACIENTE'],
                        'MaestroTrama' => ['icon' => 'fa-file-medical', 'color' => 'warning', 'label' => 'Nominal Trama', 'tabla' => 'NOMINAL_TRAMA_NUEVO'],
                    ];
                    foreach ($importEstado as $tipo => $info):
                        $cfg = $iconos[$tipo] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => $tipo, 'tabla' => ''];
                        $tablaNombre = $cfg['tabla'];
                        $logUltimo = $ultimaImportacionPorTabla[$tablaNombre] ?? null;
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
                                        </small>
                                        <?php if ($tipo === 'NominalTrama' && $info['periodo_anio'] && $info['periodo_mes']): ?>
                                            <small class="text-muted d-block">Periodo: <?= getNombreMes($info['periodo_mes']) ?> <?= $info['periodo_anio'] ?></small>
                                        <?php endif; ?>
                                        <?php if ($logUltimo): ?>
                                            <small class="text-muted d-block"><i class="fas fa-clock me-1"></i>Ultima importacion: <?= formatDateTime($logUltimo['fecha_operacion']) ?></small>
                                        <?php elseif ($info['fecha_importacion']): ?>
                                            <small class="text-muted d-block"><i class="fas fa-clock me-1"></i>Importado: <?= formatDateTime($info['fecha_importacion']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-danger"><i class="fas fa-times-circle me-1"></i>Pendiente</small>
                                        <?php if ($logUltimo): ?>
                                            <small class="text-muted d-block"><i class="fas fa-clock me-1"></i>Ultima: <?= formatDateTime($logUltimo['fecha_operacion']) ?></small>
                                        <?php endif; ?>
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
                    La consolidacion se ejecuta automaticamente al importar NominalTrama.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Panel de Estado de Consolidacion por Periodo -->
<?php if (!empty($estadoConsolidacion)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm <?= $hayPeriodosPendientes ? 'border-warning' : 'border-success' ?>">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-layer-group me-2 text-primary"></i>Estado de Consolidacion por Periodo
                </h6>
                <div>
                    <?php if (count($periodosConsolidados) > 0): ?>
                        <span class="badge bg-success me-1" id="badgeConsolidadosHeader"><i class="fas fa-check me-1"></i><?= count($periodosConsolidados) ?> Consolidado<?= count($periodosConsolidados) > 1 ? 's' : '' ?></span>
                    <?php else: ?>
                        <span class="badge bg-success me-1" id="badgeConsolidadosHeader" style="display:none;"><i class="fas fa-check me-1"></i>0 Consolidados</span>
                    <?php endif; ?>
                    <?php if ($hayPeriodosPendientes): ?>
                        <span class="badge bg-warning text-dark" id="badgePendientesHeader"><i class="fas fa-clock me-1"></i><?= count($periodosPendientes) ?> Pendiente<?= count($periodosPendientes) > 1 ? 's' : '' ?></span>
                    <?php else: ?>
                        <span class="badge bg-success" id="badgePendientesHeader"><i class="fas fa-check-double me-1"></i>Todos consolidados</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Periodo</th>
                                <th class="text-end">Regs. Trama</th>
                                <th class="text-end">Regs. Consolidado</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEstadoConsolidacion">
                            <?php foreach ($estadoConsolidacion as $ep): ?>
                            <tr class="<?= $ep['estado'] === 'pendiente' ? 'table-warning' : '' ?>">
                                <td class="fw-semibold"><?= getNombreMes(trim($ep['Mes'])) ?> <?= trim($ep['Anio']) ?></td>
                                <td class="text-end"><?= number_format($ep['regs_trama']) ?></td>
                                <td class="text-end"><?= number_format($ep['regs_consolidado']) ?></td>
                                <td class="text-center">
                                    <?php if ($ep['estado'] === 'consolidado'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Consolidado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($hayPeriodosPendientes && $todosImportados): ?>
                <div class="alert alert-info mt-2 mb-0 py-2">
                    <i class="fas fa-info-circle me-1"></i>
                    <small>Los periodos <strong>pendientes</strong> pueden ser procesados usando "Procesar por Periodo" en el panel de Consolidacion.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mensaje de resultado -->
<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert" id="mensajeResultado">
    <i class="fas fa-<?= $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
    <?= $mensaje ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Overlay de carga para procesamiento (reutiliza .loading-overlay de style.css) -->
<div id="processingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h5 class="mb-2" id="processingTitle" style="color: var(--his-primary); font-weight: 700;">
            <i class="fas fa-cogs me-2"></i>Procesando...
        </h5>
        <p class="mb-2 text-muted" id="processingMessage">Esto puede tardar varios minutos. Por favor espere.</p>
        <p class="mb-0 text-muted small">
            <i class="fas fa-info-circle me-1"></i>No cierre ni recargue esta pagina.
        </p>
    </div>
</div>

<!-- Formulario de Importacion y Log de Auditoria -->
<div class="row">
    <div class="col-lg-8">
        <!-- Formulario ZIP -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-import me-2 text-primary"></i>Importar Archivo ZIP</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="importForm" class="no-auto-loading">
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
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>"><?= getNombreMes($m) ?></option>
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
        <!-- Conteo de registros en tablas -->
        <div class="card shadow-sm">
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
        tipoDetectadoText.textContent = 'Tipo detectado: NominalTrama - Vaciado total de NOMINAL_TRAMA_NUEVO. Seleccione Mes y Anio para eliminar el periodo del consolidado y ejecutar consolidacion automatica.';
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
function mostrarOverlayProcesamiento(titulo, mensaje) {
    var overlay = document.getElementById('processingOverlay');
    var titleEl = document.getElementById('processingTitle');
    var msgEl = document.getElementById('processingMessage');

    if (titleEl) {
        titleEl.innerHTML = '<i class="fas fa-cogs me-2"></i>' + titulo;
    }
    if (msgEl) {
        msgEl.textContent = mensaje;
    }
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

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
        msg += 'Se VACIARAN TODOS los registros de NOMINAL_TRAMA_NUEVO y se insertaran los nuevos. Tambien se eliminaran los registros del consolidado para el periodo ' + mes + '/' + anio + ' y se ejecutara la consolidacion automaticamente.';
    } else if (filename.includes('MAESTRO')) {
        msg += 'Se eliminaran TODOS los registros actuales de la tabla y se reemplazaran con los nuevos.';
    }
    
    if (!confirm(msg)) {
        e.preventDefault();
        return;
    }
    
    // Mostrar indicador de carga en el boton
    document.getElementById('btnImportar').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Importando...';
    document.getElementById('btnImportar').disabled = true;
    
    // Mostrar overlay de procesamiento
    mostrarOverlayProcesamiento('Importando datos...', 'Esto puede tardar varios minutos dependiendo del tamano del archivo. Por favor espere.');
});

// =====================================================
// Al cargar la pagina, scroll al mensaje de resultado
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    var mensajeDiv = document.getElementById('mensajeResultado');
    if (mensajeDiv) {
        mensajeDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
