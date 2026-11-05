<?php
/**
 * Sistema de Gestion de Datos HIS
 * Modulo Convenio FED - Fondo de Estimulo al Desempeno
 * Codigo comun compartido por las paginas del modulo (v4).
 *
 * v4 - Separacion del modulo en paginas independientes (2026-11-04):
 *  El antiguo convenio_fed.php (que concentraba consulta, gestion de
 *  indicadores e importacion en una sola pagina de ~1,900 lineas) se divide
 *  en tres paginas mas ligeras y faciles de mantener:
 *    - convenio_fed.php            -> Consulta y visualizacion de avances (todos los roles)
 *    - fed_gestion_indicadores.php -> FED: Gestion de Indicadores (solo Administrador)
 *    - fed_importar_avances.php    -> FED: Importar Avances (solo Administrador)
 *
 *  Este archivo centraliza el codigo compartido por las tres paginas:
 *    0. Red de seguridad contra errores fatales (panel de diagnostico legible).
 *    1. Conexion PDO, rol del usuario y variables de mensaje.
 *    2. Deteccion de tablas/columnas del esquema FED y auto-reparacion de
 *       PRIMARY KEY / AUTO_INCREMENT (fedTablaExiste, fedTieneColumna,
 *       fedEsAutoIncrement, fedAsegurarAutoIncrement).
 *    3. Flags de capacidades: $puedeGestionar, $puedeImportar, $sistemaOK,
 *       $tieneFechaImportacion, $tieneFichaPdf.
 *    4. Helpers de presentacion: clasificarAvanceFED() y colorBarraFED().
 *    5. Ficha tecnica PDF (v8): fedFichaDir(), fedFichaUrl(),
 *       fedEliminarFichaTecnica() y fedSubirFichaTecnica().
 *
 * DEPENDENCIAS: incluir SIEMPRE despues de
 *    require_once 'includes/auth.php';   verificarAutenticacion();
 *    require_once 'includes/functions.php';
 * pues usa esAdmin() y getDBConnection().
 */

if (!defined('FED_COMMON_CARGADO')) {
    define('FED_COMMON_CARGADO', 1);

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

    /* ----------------------------------------------------------------------------
     * v8: Columna ficha_tecnica_pdf (ficha tecnica en PDF de cada indicador).
     * Si no existe se intenta crearla automaticamente (misma auto-reparacion de
     * esquema que fedAsegurarAutoIncrement). Si el hosting no permite ALTER,
     * ejecutar manualmente Database/update_fed_v4_fichas.sql desde phpMyAdmin;
     * mientras tanto las paginas ocultan la funcionalidad de fichas PDF.
     * ------------------------------------------------------------------------- */
    $tieneFichaPdf = $tablaIndicadores !== null ? fedTieneColumna($pdo, $tablaIndicadores, 'ficha_tecnica_pdf') : false;
    if ($tablaIndicadores !== null && !$tieneFichaPdf) {
        try {
            $pdo->exec("ALTER TABLE `{$tablaIndicadores}`
                        ADD COLUMN `ficha_tecnica_pdf` VARCHAR(255) NULL DEFAULT NULL AFTER `responsable`");
            $tieneFichaPdf = fedTieneColumna($pdo, $tablaIndicadores, 'ficha_tecnica_pdf');
        } catch (Throwable $e) {
            $tieneFichaPdf = false; // sin permisos ALTER: aplicar el SQL manual
        }
    }

    /* ============================================================================
     * 2. HELPERS DE PRESENTACION (clasificacion y color de barras de avance)
     * ========================================================================== */

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

    /* ============================================================================
     * 3. FICHA TECNICA EN PDF (v8)
     * --------------------------------------------------------------------------
     * Cada indicador del Convenio FED puede tener adjunto un PDF con su ficha
     * tecnica. El archivo fisico se guarda en la carpeta uploads/fed_fichas/ del
     * servidor y en la base de datos solo se guarda su nombre (columna
     * ficha_tecnica_pdf de CONVENIO_FED_INDICADORES).
     * ========================================================================== */

    define('FED_FICHA_DIR_REL', 'uploads/fed_fichas'); // carpeta dentro de la raiz del sitio
    define('FED_FICHA_MAX_BYTES', 10485760);           // limite: 10 MB por PDF

    /**
     * Ruta ABSOLUTA de la carpeta de fichas PDF (uploads/fed_fichas). La crea
     * automaticamente si todavia no existe. La raiz del sitio se calcula desde
     * includes/ (este archivo vive en includes/, por eso se sube un nivel).
     */
    function fedFichaDir() {
        $dir = dirname(__DIR__) . '/' . FED_FICHA_DIR_REL;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        return $dir;
    }

    /**
     * URL publica (relativa a la raiz del sitio) para abrir una ficha PDF en
     * otra pestana/ventana del navegador. basename() evita cualquier intento
     * de path traversal.
     */
    function fedFichaUrl($nombre) {
        if (!is_string($nombre) || $nombre === '') return '';
        return FED_FICHA_DIR_REL . '/' . rawurlencode(basename($nombre));
    }

    /**
     * Elimina de la carpeta uploads/fed_fichas el PDF de ficha tecnica indicado
     * (mejor esfuerzo: si no existe o no se puede borrar, no falla).
     */
    function fedEliminarFichaTecnica($nombre) {
        if (!is_string($nombre) || $nombre === '') return;
        $ruta = fedFichaDir() . '/' . basename($nombre);
        if (is_file($ruta)) { @unlink($ruta); }
    }

    /**
     * Procesa la subida del PDF de la ficha tecnica (input file "ficha_pdf").
     *
     * Validaciones aplicadas:
     *  - que la subida no tenga errores de PHP (upload_max_filesize, etc.);
     *  - tamano maximo FED_FICHA_MAX_BYTES (10 MB);
     *  - extension .pdf;
     *  - contenido real de PDF (cabecera "%PDF-" dentro de los primeros KB).
     *
     * El nombre final es unico: fed_ficha_YYYYMMDD_HHMMSS_XXXXXXXX.pdf
     *
     * @param array|null $archivo  $_FILES['ficha_pdf'] o null
     * @return array [bool ok, string|null nombreGuardado, string|null mensajeError]
     */
    function fedSubirFichaTecnica($archivo) {
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
        if ((int)$archivo['size'] > FED_FICHA_MAX_BYTES) {
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
        $dir = fedFichaDir();
        if (!is_dir($dir)) {
            return [false, null, 'No se pudo crear la carpeta ' . FED_FICHA_DIR_REL . ' en el servidor. Verifique permisos de escritura.'];
        }
        $nombre = 'fed_ficha_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $destino = $dir . '/' . $nombre;
        if (!@move_uploaded_file($archivo['tmp_name'], $destino)) {
            return [false, null, 'No se pudo guardar el PDF en ' . FED_FICHA_DIR_REL . '. Verifique permisos de escritura de la carpeta.'];
        }
        @chmod($destino, 0644);
        return [true, $nombre, null];
    }
}
