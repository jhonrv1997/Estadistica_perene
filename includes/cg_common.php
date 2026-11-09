<?php
/**
 * Sistema de Gestion de Datos HIS
 * Modulo Convenio de Gestion MINSA-GORE - codigo comun compartido.
 *
 * Adaptacion del modulo al patron del Convenio FED (2026-11-09):
 *  El modulo Convenio de Gestion ahora replica la funcionalidad y la
 *  arquitectura del modulo Convenio FED, dividido en tres paginas:
 *    - convenio_gestion.php       -> Consulta y visualizacion de avances (todos los roles)
 *    - cg_gestion_indicadores.php -> CG: Gestion de Indicadores (solo Administrador)
 *    - cg_importar_avances.php    -> CG: Importar Avances (solo Administrador)
 *
 *  Este archivo centraliza el codigo compartido por las tres paginas:
 *    0. Red de seguridad contra errores fatales (panel de diagnostico legible).
 *    1. Conexion PDO, rol del usuario y variables de mensaje.
 *    2. Deteccion de tablas/columnas del esquema y AUTO-REPARACION del esquema
 *       (cgTablaExiste, cgTieneColumna, cgEsAutoIncrement, cgAsegurarAutoIncrement
 *       y cgAsegurarColumna).
 *       La tabla CONVENIO_GESTION_AVANCE original no tenia renipress,
 *       establecimiento ni fecha_importacion, y CONVENIO_GESTION_INDICADORES
 *       no tenia peso_ponderado ni ficha_tecnica_pdf: todas se agregan
 *       automaticamente la primera vez que se abre el modulo (si el hosting
 *       no permite ALTER TABLE, ejecutar Database/update_gestion_v1.sql).
 *    3. Flags de capacidades: $puedeGestionarCG, $puedeImportarCG,
 *       $sistemaCGok, $tieneFechaImportacionCG, $tienePesoCG, $tieneFichaPdfCG,
 *       $tieneRenipressCG.
 *    4. Helpers de presentacion: clasificarAvanceCG() y colorBarraCG().
 *    5. Ficha tecnica PDF: cgFichaDir(), cgFichaUrl(),
 *       cgEliminarFichaTecnica() y cgSubirFichaTecnica() (uploads/cg_fichas/).
 *
 * DEPENDENCIAS: incluir SIEMPRE despues de
 *    require_once 'includes/auth.php';   verificarAutenticacion();
 *    require_once 'includes/functions.php';
 * pues usa esAdmin() y getDBConnection().
 */

if (!defined('CG_COMMON_CARGADO')) {
    define('CG_COMMON_CARGADO', 1);

    /* ============================================================================
     * 0. RED DE SEGURIDAD CONTRA ERRORES FATALES
     * --------------------------------------------------------------------------
     * Si algo falla de forma inesperada (por ejemplo, una consulta incompatible
     * con la version de MySQL/MariaDB del hosting), se muestra un panel legible
     * con el motivo en lugar del en blanco "500 Internal Server Error".
     * ========================================================================== */
    if (!function_exists('cgPanelError')) {
        function cgPanelError($titulo, $detallesHtml) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            if (!headers_sent()) { http_response_code(500); }
            $tituloEsc = htmlspecialchars((string)$titulo, ENT_QUOTES, 'UTF-8');
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
               . '<meta name="viewport" content="width=device-width, initial-scale=1">'
               . '<title>Convenio de Gestion - ' . $tituloEsc . '</title></head>'
               . '<body style="font-family:Segoe UI,Arial,sans-serif;max-width:860px;margin:40px auto;padding:0 16px;color:#212529">'
               . '<div style="border:2px solid #dc3545;border-radius:10px;padding:22px 26px;background:#fff8f8">'
               . '<h1 style="font-size:20px;margin:0 0 12px;color:#dc3545">Convenio de Gestion - ' . $tituloEsc . '</h1>'
               . $detallesHtml
               . '<p style="font-size:13px"><a href="dashboard.php">&larr; Volver al panel</a></p>'
               . '</div></body></html>';
            exit;
        }
    }
    set_exception_handler(function ($e) {
        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        cgPanelError('Error inesperado',
            '<p>La pagina no pudo completarse por un error no previsto. Detalle tecnico:</p>'
          . '<p style="font-family:Consolas,monospace;background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:10px;word-break:break-word">' . $msg . '</p>');
    });
    register_shutdown_function(function () {
        $e = error_get_last();
        if ($e !== null && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $msg = htmlspecialchars($e['message'], ENT_QUOTES, 'UTF-8');
            cgPanelError('Error fatal del servidor',
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
     * --------------------------------------------------------------------------
     * Con PDO::ATTR_EMULATE_PREPARES => false de config.php, las sentencias SHOW
     * se preparan de forma NATIVA y ese protocolo NO admite placeholders, por lo
     * que aqui se consulta information_schema (que SI admite placeholders) y,
     * como plan B, se usa SHOW con el nombre como literal seguro.
     * ========================================================================== */

    /** Verifica si una tabla existe en la base de datos actual. */
    function cgTablaExiste($pdo, $nombre) {
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

    /** Verifica si una columna existe en una tabla. */
    function cgTieneColumna($pdo, $tabla, $columna) {
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

    /** Verifica si una columna es AUTO_INCREMENT (via information_schema). */
    function cgEsAutoIncrement($pdo, $tabla, $columna) {
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
     * Garantiza PRIMARY KEY + AUTO_INCREMENT en la columna indicada de una tabla
     * (misma auto-reparacion de esquema que usa el modulo FED). Si el hosting no
     * permite ALTER, los INSERT usan el modo de id manual (MAX + 1).
     */
    function cgAsegurarAutoIncrement($pdo, $tabla, $columna) {
        if (cgEsAutoIncrement($pdo, $tabla, $columna)) {
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
            return cgEsAutoIncrement($pdo, $tabla, $columna);
        } catch (Throwable $e) {
            return false; // sin permisos ALTER: los INSERT usaran id manual
        }
    }

    /**
     * Agrega una columna a una tabla si aun no existe (auto-reparacion de
     * esquema). Devuelve true si la columna existe al terminar (ya existia o se
     * creo con exito). Si el hosting bloquea ALTER TABLE, ejecutar manualmente
     * Database/update_gestion_v1.sql desde phpMyAdmin.
     */
    function cgAsegurarColumna($pdo, $tabla, $columna, $definicionSQL) {
        if (cgTieneColumna($pdo, $tabla, $columna)) {
            return true;
        }
        try {
            $pdo->exec("ALTER TABLE `{$tabla}` ADD COLUMN `{$columna}` {$definicionSQL}");
        } catch (Throwable $e) {
            // sin permisos ALTER o la columna acabo de crearse en paralelo
        }
        return cgTieneColumna($pdo, $tabla, $columna);
    }

    /* ----------------------------------------------------------------------------
     * Tablas del modulo. El volcado original crea CONVENIO_GESTION_AVANCE en
     * SINGULAR; se admite tambien el plural por si el hosting lo renombro.
     * ------------------------------------------------------------------------- */
    $tablaIndicadoresCG = cgTablaExiste($pdo, 'CONVENIO_GESTION_INDICADORES') ? 'CONVENIO_GESTION_INDICADORES' : null;
    $tablaAvancesCG     = cgTablaExiste($pdo, 'CONVENIO_GESTION_AVANCE')     ? 'CONVENIO_GESTION_AVANCE'
                      : (cgTablaExiste($pdo, 'CONVENIO_GESTION_AVANCES')     ? 'CONVENIO_GESTION_AVANCES' : null);

    $puedeGestionarCG = $tablaIndicadoresCG !== null;                          // CRUD indicadores
    $puedeImportarCG  = $tablaIndicadoresCG !== null && $tablaAvancesCG !== null; // importacion
    $sistemaCGok      = $tablaIndicadoresCG !== null && $tablaAvancesCG !== null;

    /* ----------------------------------------------------------------------------
     * AUTO-REPARACION DEL ESQUEMA (adaptacion al patron FED):
     *  - CONVENIO_GESTION_AVANCE: renipress, establecimiento y fecha_importacion
     *    (el esquema original no las tenia; son necesarias para el detalle y el
     *    ranking por establecimiento y la fecha de "Datos actualizados al").
     *  - CONVENIO_GESTION_INDICADORES: peso_ponderado (avance global ponderado)
     *    y ficha_tecnica_pdf (ficha tecnica en PDF por indicador).
     * Todas son NULL-able: los datos ya registrados (consolidados por periodo,
     * sin establecimiento) siguen siendo validos y se muestran como
     * "Consolidado Zona Sanitaria".
     * ------------------------------------------------------------------------- */
    $tieneFechaImportacionCG = false;
    $tieneRenipressCG        = false;
    $tienePesoCG             = false;
    $tieneFichaPdfCG         = false;

    if ($tablaAvancesCG !== null) {
        $tieneRenipressCG        = cgAsegurarColumna($pdo, $tablaAvancesCG, 'renipress',
            "VARCHAR(10) NULL DEFAULT NULL COMMENT 'Codigo RENIPRESS del establecimiento' AFTER `id_indicador`");
        if ($tieneRenipressCG) {
            $tieneRenipressCG = cgAsegurarColumna($pdo, $tablaAvancesCG, 'establecimiento',
                "VARCHAR(50) NULL DEFAULT NULL COMMENT 'Nombre del establecimiento' AFTER `renipress`");
        }
        $tieneFechaImportacionCG = cgAsegurarColumna($pdo, $tablaAvancesCG, 'fecha_importacion',
            "DATETIME NULL DEFAULT NULL AFTER `fecha_registro`");
    }
    if ($tablaIndicadoresCG !== null) {
        $tienePesoCG = cgAsegurarColumna($pdo, $tablaIndicadoresCG, 'peso_ponderado',
            "DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'Peso porcentual del indicador en el convenio' AFTER `meta_anual`");
        $tieneFichaPdfCG = cgAsegurarColumna($pdo, $tablaIndicadoresCG, 'ficha_tecnica_pdf',
            "VARCHAR(255) NULL DEFAULT NULL AFTER `responsable`");
    }

    /* ============================================================================
     * 2. HELPERS DE PRESENTACION (clasificacion y color de barras de avance)
     *    Mismos umbrales que el modulo FED y que la pagina original del
     *    Convenio de Gestion: >=90 Excelente, >=75 Bueno, >=60 Regular,
     *    >=40 Bajo, <40 Critico.
     * ========================================================================== */

    function clasificarAvanceCG($pct) {
        if ($pct === null) return ['clase' => 'avance-sin-dato', 'texto' => 'Sin dato', 'estado' => ''];
        if ($pct >= 90) return ['clase' => 'avance-excelente', 'texto' => 'Excelente', 'estado' => 'estado-excelente'];
        if ($pct >= 75) return ['clase' => 'avance-bueno', 'texto' => 'Bueno', 'estado' => 'estado-bueno'];
        if ($pct >= 60) return ['clase' => 'avance-regular', 'texto' => 'Regular', 'estado' => 'estado-regular'];
        if ($pct >= 40) return ['clase' => 'avance-bajo', 'texto' => 'Bajo', 'estado' => 'estado-bajo'];
        return ['clase' => 'avance-critico', 'texto' => 'Critico', 'estado' => 'estado-critico'];
    }

    function colorBarraCG($pct) {
        if ($pct === null) return 'bg-secondary';
        if ($pct >= 90) return 'bg-success';
        if ($pct >= 75) return 'bg-info';
        if ($pct >= 60) return 'bg-warning';
        if ($pct >= 40) return 'bg-orange';
        return 'bg-danger';
    }

    /* ============================================================================
     * 3. FICHA TECNICA EN PDF (misma funcionalidad que el modulo FED)
     * --------------------------------------------------------------------------
     * Cada indicador del Convenio de Gestion puede tener adjunto un PDF con su
     * ficha tecnica. El archivo fisico se guarda en la carpeta uploads/cg_fichas/
     * del servidor y en la base de datos solo se guarda su nombre (columna
     * ficha_tecnica_pdf de CONVENIO_GESTION_INDICADORES).
     * ========================================================================== */

    define('CG_FICHA_DIR_REL', 'uploads/cg_fichas'); // carpeta dentro de la raiz del sitio
    define('CG_FICHA_MAX_BYTES', 10485760);           // limite: 10 MB por PDF

    /**
     * Ruta ABSOLUTA de la carpeta de fichas PDF (uploads/cg_fichas). La crea
     * automaticamente si todavia no existe.
     */
    function cgFichaDir() {
        $dir = dirname(__DIR__) . '/' . CG_FICHA_DIR_REL;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        return $dir;
    }

    /**
     * URL publica (relativa a la raiz del sitio) para abrir una ficha PDF en
     * otra pestana/ventana del navegador. basename() evita cualquier intento
     * de path traversal.
     */
    function cgFichaUrl($nombre) {
        if (!is_string($nombre) || $nombre === '') return '';
        return CG_FICHA_DIR_REL . '/' . rawurlencode(basename($nombre));
    }

    /**
     * Elimina de la carpeta uploads/cg_fichas el PDF de ficha tecnica indicado
     * (mejor esfuerzo: si no existe o no se puede borrar, no falla).
     */
    function cgEliminarFichaTecnica($nombre) {
        if (!is_string($nombre) || $nombre === '') return;
        $ruta = cgFichaDir() . '/' . basename($nombre);
        if (is_file($ruta)) { @unlink($ruta); }
    }

    /**
     * Procesa la subida del PDF de la ficha tecnica (input file "ficha_pdf").
     *
     * Validaciones aplicadas:
     *  - que la subida no tenga errores de PHP (upload_max_filesize, etc.);
     *  - tamano maximo CG_FICHA_MAX_BYTES (10 MB);
     *  - extension .pdf;
     *  - contenido real de PDF (cabecera "%PDF-" dentro de los primeros KB).
     *
     * El nombre final es unico: cg_ficha_YYYYMMDD_HHMMSS_XXXXXXXX.pdf
     *
     * @param array|null $archivo  $_FILES['ficha_pdf'] o null
     * @return array [bool ok, string|null nombreGuardado, string|null mensajeError]
     */
    function cgSubirFichaTecnica($archivo) {
        // Campo opcional: sin archivo (o subida vacia) no es un error
        if (!is_array($archivo) || (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [true, null, null];
        }
        $err = (int)$archivo['error'];
        if ($err !== UPLOAD_ERR_OK) {
            $mapa = [
                UPLOAD_ERR_INI_SIZE   => 'El PDF supera el limite de subida del servidor (upload_max_filesize de PHP).',
                UPLOAD_ERR_FORM_SIZE  => 'El PDF supera el limite de subida del formulario.',
                UPLOAD_ERR_PARTIAL    => 'La subida del PDF se interrumpio. Intente nuevamente.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor (upload_tmp_dir).',
                UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo temporal de la subida.',
                UPLOAD_ERR_EXTENSION  => 'Una extension de PHP detuvo la subida del archivo.',
            ];
            return [false, null, $mapa[$err] ?? ('Error desconocido al subir el PDF (codigo ' . $err . ').')];
        }
        if (($archivo['size'] ?? 0) <= 0) {
            return [false, null, 'El archivo PDF esta vacio.'];
        }
        if ((int)$archivo['size'] > CG_FICHA_MAX_BYTES) {
            return [false, null, 'El PDF supera el tamano maximo permitido (10 MB).'];
        }
        if (strtolower(pathinfo((string)($archivo['name'] ?? ''), PATHINFO_EXTENSION)) !== 'pdf') {
            return [false, null, 'Solo se permite adjuntar archivos con extension .pdf.'];
        }
        // Verificacion del contenido: un PDF real comienza con la cabecera %PDF-
        $cabecera = '';
        $fh = @fopen($archivo['tmp_name'], 'rb');
        if ($fh) {
            $cabecera = (string)@fread($fh, 1024);
            @fclose($fh);
        }
        if (strpos($cabecera, '%PDF-') === false) {
            return [false, null, 'El archivo adjuntado no parece un PDF valido.'];
        }
        // Carpeta destino (se crea automaticamente la primera vez)
        $dir = cgFichaDir();
        if (!is_dir($dir)) {
            return [false, null, 'No se pudo crear la carpeta ' . CG_FICHA_DIR_REL . ' en el servidor. Verifique permisos de escritura.'];
        }
        $nombre = 'cg_ficha_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $destino = $dir . '/' . $nombre;
        if (!@move_uploaded_file($archivo['tmp_name'], $destino)) {
            return [false, null, 'No se pudo guardar el PDF en ' . CG_FICHA_DIR_REL . '. Verifique permisos de escritura de la carpeta.'];
        }
        @chmod($destino, 0644);
        return [true, $nombre, null];
    }
}
