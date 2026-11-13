<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ADULTO - Logica de mapeo para el plano consolidado
 * "Adulto_Plano.xlsx" (NUEVA FUNCIONALIDAD "Exportar Plano").
 *
 * ANTECEDENTE:
 *   adulto_export.php llena la plantilla oficial
 *   "Reporte_Actividades_Adulto.xlsx" mediante aduCeldasExport()
 *   (includes/adulto_render.php), que produce el mapa [celda => valor]
 *   de TODOS los datos del reporte, ej: ['D14' => 2].
 *
 * NUEVA FUNCIONALIDAD (mapeo de 3 archivos, mismo patron que Zoonosis/ITS/
 * Materno/NoTransmisibles/Adolescente):
 *
 *   1) REFERENCIA DE UBICACION - archivo "Ubicacion_Filas_Adulto.xlsx":
 *      Es el espejo del reporte oficial donde cada celda de datos contiene un
 *      NUMERO DE DESTINO. Ejemplo: la celda D14 (A64 - Enfermedad de
 *      Transmision Sexual no Especificada, mujeres 30a-59a) tiene el valor 4
 *      y la celda E14 (varones) el valor 5.
 *      El espejo trae 316 numeros (4..319, secuenciales en orden de lectura
 *      fila a fila; verificado automaticamente contra este motor).
 *
 *   2) REFERENCIA DEL DATO - plantilla oficial "Reporte_Actividades_
 *      Adulto.xlsx": la celda equivalente (MISMA letra de columna y
 *      MISMA fila) contiene el DATO calculado por el motor (aduCeldasExport).
 *      Ejemplo: D14 = 2.
 *      El espejo calza FILA POR FILA y COLUMNA POR COLUMNA con las coordenadas
 *      de aduCeldasExport (ADU_PLANO_OFFSET_FILA = 0, sin ajustes), porque
 *      aduSecciones() numera cada fila con la fila REAL del Excel oficial
 *      (MORBILIDAD 14-23, PAI 29-30, IMC 36-43, PAB 49-50, DNT 56-58,
 *      HTA 62-64, DM 68-73, PF 79-84, metodos 88-113, AOE 116-119, CANCER
 *      124-125, SALUD BUCAL 130-157, SALUD MENTAL 162-172, TBC 177-178,
 *      SSR 183-195, ITS-VIH 200-206, OCULAR 211-215, APP 220-224,
 *      INMUNIZACIONES 229-238, SESIONES 243-246 y CONSEJERIAS 251-256).
 *      Verificacion automatica: las 316 celdas de datos que produce
 *      aduCeldasExport (layouts FMT D/E, PFM E/F, AOE E, SES D/E y TOT D,
 *      excluidas las columnas Total y la fila TOTAL E115) tienen su numero
 *      en el espejo, cobertura 100% (0 celdas del espejo sin dato, 0 celdas
 *      del motor sin numero).
 *
 *   3) REFERENCIA DEL DESTINO - archivo "Adulto_Plano.xlsx":
 *      En la fila 12, las celdas A12:LG12 contienen la numeracion de
 *      columnas (1..319, consecutivos sin duplicados ni faltantes). Se
 *      localiza la celda cuyo numero coincide con el numero de destino: con
 *      4 la columna correspondiente es D.
 *      => El dato de D14 se rellena en la celda D13. Para todas las demas
 *         celdas se sigue la misma logica: el plano es un layout horizontal
 *         donde cada celda de datos del reporte ocupa su propia columna
 *         (1=A Ubigeo, 2=B Codigo RENAES, 3=C Dpto/Red/Provincia/Distrito/
 *         EE.SS) y la fila de datos es la 13 (encima quedan las cabeceras
 *         1-11 y la fila de referencia 12).
 *
 * CELDAS SIN NUMERO DE DESTINO (por diseno del plano):
 *   - Las 123 columnas "Total" de la plantilla oficial (F en los layouts
 *     FMT): el plano no las numera (el espejo empieza en el numero 4, los
 *     numeros 1-3 son las columnas de identificacion Ubigeo/RENAES/EE.SS).
 *   - La fila TOTAL de nuevas usuarias (E115, suma calculada por
 *     aduCeldasExport para el bloque de metodos de PF): el plano tampoco la
 *     numera (no existe como columna propia).
 *   - ADU_PLANO_FILA_DATOS: A13 (Ubigeo) se deja vacia y B13/C13 se
 *     rellenan directamente en adulto_export_plano.php, no vienen del espejo.
 *   Todas se omiten de forma segura (mejor esfuerzo, igual que los demas
 *   modulos).
 *
 * CACHE DEL MAPEO (mejora de rendimiento):
 *   El mapa de ubicaciones y el indice del plano solo dependen de 2 archivos
 *   estaticos, por lo que se cachean en JSON dentro de cache/plano/ (misma
 *   carpeta que los planos de ITS/Zoonosis/Materno/NoTransmisibles/
 *   Adolescente: el nombre del JSON incluye el nombre del xlsx fuente y su
 *   hash, por lo que no hay colisiones entre modulos). La cache se regenera
 *   automaticamente si cambia alguno de los xlsx (mtime o size distintos),
 *   de modo que reemplazar una plantilla nunca produce datos viejos.
 *
 * Funciones PURAS (sin acceso a BD ni sesion) para poder probarlas de forma
 * aislada. Requiere includes/ExcelTemplateReader.php.
 */

require_once __DIR__ . '/ExcelTemplateReader.php';

/** Fila de "Adulto_Plano.xlsx" que contiene los numeros de referencia (A12:LG12). */
const ADU_PLANO_FILA_REFERENCIA = 12;

/** Fila de "Adulto_Plano.xlsx" donde se escribe la data (ej: D13). */
const ADU_PLANO_FILA_DATOS = 13;

/**
 * Desplazamiento del espejo "Ubicacion_Filas_Adulto.xlsx" respecto de
 * las coordenadas que produce aduCeldasExport(): aqui el espejo calza fila
 * por fila con la plantilla oficial (MORBILIDAD 14-23, PAI 29-30, IMC 36-43,
 * PAB 49-50, DNT 56-58, HTA 62-64, DM 68-73, PF 79-84, metodos 88-113, AOE
 * 116-119, cancer 124-125, salud bucal 130-157, salud mental 162-172, TBC
 * 177-178, SSR 183-195, ITS-VIH 200-206, ocular 211-215, APP 220-224,
 * inmunizaciones 229-238, sesiones 243-246 y consejerias 251-256), por lo
 * que el offset es 0. Se mantiene como constante por simetria con ITS
 * (offset 8).
 */
const ADU_PLANO_OFFSET_FILA = 0;

// ==================== CACHE EN DISCO DEL MAPEO ====================
// Ubicacion por defecto de la cache: <raiz del sistema>/cache/plano/ (la misma
// carpeta que los planos de ITS/Zoonosis/Materno/NoTransmisibles/Adolescente;
// el nombre del JSON incluye el nombre del xlsx fuente y su hash, por lo que
// no hay colisiones entre modulos). La carpeta se crea automaticamente al
// primer uso. Para ubicarla en otra ruta (p.ej. un disco temporal), defina la
// constante ANTES de incluir este archivo:
//   define('ADU_PLANO_CACHE_DIR', '/tmp/adu_plano_cache');
if (!defined('ADU_PLANO_CACHE_DIR')) {
    define('ADU_PLANO_CACHE_DIR', dirname(__DIR__) . '/cache/plano');
}

// ==================== CACHE (lectura/escritura JSON) ====================
/**
 * Devuelve el directorio de cache, creandolo si no existe (mejor esfuerzo).
 *
 * @param string|null $cacheDir Ruta alternativa; null = ADU_PLANO_CACHE_DIR.
 * @return string Ruta absoluta del directorio de cache.
 */
function aduPlanoCacheDir(?string $cacheDir = null): string
{
    $dir = $cacheDir !== null && $cacheDir !== '' ? $cacheDir : ADU_PLANO_CACHE_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Ruta del archivo de cache para una clave y archivo fuente concretas.
 * El nombre incluye un hash de la ruta fuente para evitar colisiones, p.ej.:
 * ubicacion_filas_adulto_ubicacion_3f2a91bc.json
 */
function aduPlanoCacheRuta(string $clave, string $fuente, ?string $cacheDir = null): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '_', pathinfo($fuente, PATHINFO_FILENAME));
    $hash = substr(md5($fuente), 0, 8);
    return aduPlanoCacheDir($cacheDir) . '/' . strtolower((string)$base) . "_{$clave}_{$hash}.json";
}

/**
 * Lee el cache de un mapeo si sigue VIGENTE.
 *
 * Validez: el JSON existe, decodifica y ademas mtime y size del archivo
 * fuente COINCIDEN con los guardados. Si el xlsx se reemplaza o edita, la
 * cache queda invalidada y se regenera automaticamente.
 *
 * @param string      $clave    Clave logica ('ubicacion', 'plano_fila12', ...).
 * @param string      $fuente   Ruta del archivo xlsx del que proviene el mapeo.
 * @param string|null $cacheDir Ruta alternativa de cache; null = por defecto.
 * @return array<string,int>|array<int,string>|null Valores cacheados o null si no hay cache vigente.
 */
function aduPlanoCacheLeer(string $clave, string $fuente, ?string $cacheDir = null): ?array
{
    $ruta = aduPlanoCacheRuta($clave, $fuente, $cacheDir);
    if (!is_file($ruta) || !is_readable($ruta)) {
        return null;
    }
    $json = @file_get_contents($ruta);
    if ($json === false) {
        return null;
    }
    $datos = json_decode($json, true);
    if (!is_array($datos)
        || !isset($datos['mtime'], $datos['size'], $datos['valores'])
        || !is_array($datos['valores'])
    ) {
        error_log("[adu_plano] Cache '{$clave}' ilegible o corrupta; se regenera desde el archivo.");
        return null;
    }
    $mtime = @filemtime($fuente);
    $size  = @filesize($fuente);
    if ($mtime === false || $size === false
        || (int)$datos['mtime'] !== (int)$mtime
        || (int)$datos['size'] !== (int)$size
    ) {
        return null; // el archivo fuente cambio: regenerar
    }
    return $datos['valores'];
}

/**
 * Guarda un mapeo en la cache (mejor esfuerzo: si no se puede escribir,
 * solo registra en el log y el flujo sigue leyendo el xlsx directamente).
 */
function aduPlanoCacheGuardar(string $clave, string $fuente, array $valores, ?string $cacheDir = null): void
{
    $mtime = @filemtime($fuente);
    $size  = @filesize($fuente);
    if ($mtime === false || $size === false) {
        return; // sin metadatos no hay invalidacion confiable: no cachear
    }
    $payload = [
        'clave'    => $clave,
        'fuente'   => basename($fuente),
        'mtime'    => $mtime,
        'size'     => $size,
        'generado' => date('c'),
        'total'    => count($valores),
        'valores'  => $valores,
    ];
    $json = json_encode($payload);
    $ruta = aduPlanoCacheRuta($clave, $fuente, $cacheDir);
    if ($json === false || @file_put_contents($ruta, $json, LOCK_EX) === false) {
        error_log("[adu_plano] No se pudo escribir la cache '{$clave}' en {$ruta}; "
            . "se seguira leyendo el xlsx en cada exportacion.");
    }
}

/**
 * Lee el archivo "Ubicacion_Filas_Adulto.xlsx" y devuelve el mapa de
 * destino de cada celda del reporte.
 *
 * Usa CACHE EN DISCO: solo parsea el xlsx la primera vez (o cuando el archivo
 * cambia); las siguientes llamadas leen el JSON cacheado.
 *
 * Solo se toman las CELDAS NUMERICAS (los numeros de destino); las etiquetas
 * de texto del espejo se ignoran. Ejemplo de salida:
 *   ['D14' => 4, 'E14' => 5, 'D15' => 6, ...]
 *
 * @param string      $path     Ruta al archivo "Ubicacion_Filas_Adulto.xlsx".
 * @param string|null $cacheDir Ruta alternativa de cache; null = ADU_PLANO_CACHE_DIR.
 * @return array<string,int> Mapa [celdaOrigen => numeroDestino].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function aduPlanoMapaUbicacion(string $path, ?string $cacheDir = null): array
{
    $mapa = aduPlanoCacheLeer('ubicacion', $path, $cacheDir);
    if ($mapa === null) {
        $lector = new ExcelTemplateReader($path);
        $mapa = [];
        foreach ($lector->readNumericCells() as $ref => $valor) {
            $mapa[$ref] = (int)$valor;
        }
        aduPlanoCacheGuardar('ubicacion', $path, $mapa, $cacheDir);
    }
    return $mapa;
}

/**
 * Lee la plantilla "Adulto_Plano.xlsx" y construye el indice
 * [numeroReferencia => letraColumna] a partir de su fila de referencia
 * (A12:LG12 con valores 1..319).
 *
 * Usa CACHE EN DISCO (clave 'plano_fila{N}'), igual que el mapa de ubicacion.
 *
 * Ejemplo de salida: [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', ..., 319 => 'LG']
 * Asi, el numero 4 de "Ubicacion_Filas_Adulto.xlsx" resuelve a la
 * columna D y el dato se escribe en D13 (columna destino + fila de datos).
 *
 * NOTA: la fila 12 del plano trae formulas (=D12+1 ...) CON su valor
 * cacheado en el XML (<v>5</v>), por lo que ExcelTemplateReader las lee
 * como cualquier celda numerica.
 *
 * @param string      $path            Ruta al archivo "Adulto_Plano.xlsx".
 * @param int         $filaReferencia  Fila con la numeracion (por defecto 12).
 * @param string|null $cacheDir        Ruta alternativa de cache; null = por defecto.
 * @return array<int,string> Mapa [numeroReferencia => letraColumna].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function aduPlanoIndiceDestino(string $path, int $filaReferencia = ADU_PLANO_FILA_REFERENCIA, ?string $cacheDir = null): array
{
    $clave = 'plano_fila' . $filaReferencia;
    $cache = aduPlanoCacheLeer($clave, $path, $cacheDir);
    if ($cache !== null) {
        // Normaliza claves a int (json_decode las entrega como strings numericas)
        // y valores a string, para que el indice sea identico al recien leido.
        $indice = [];
        foreach ($cache as $numero => $columna) {
            $indice[(int)$numero] = (string)$columna;
        }
        return $indice;
    }

    $lector = new ExcelTemplateReader($path);
    $indice = $lector->readRowNumbers($filaReferencia);
    aduPlanoCacheGuardar($clave, $path, $indice, $cacheDir);
    return $indice;
}

/**
 * Cruza los DATOS del reporte con el mapa de ubicacion y el indice del plano.
 *
 * Con offset 0 (espejo = plantilla oficial), por cada celda del mapa de
 * ubicacion:
 *   - Si el reporte produce un dato para ESA MISMA celda (aduCeldasExport) y
 *     el numero de destino existe en la fila de referencia del plano, el dato
 *     se asigna a la celda destino: {letraColumna}{filaDatos}. Ej: D14
 *     (numero 4) -> dato de D14 -> columna D -> 'D13' => dato.
 *   - Si el reporte no produce dato para esa celda (cabeceras del espejo o
 *     celdas sin numero), la celda se omite.
 *   - Si el numero de destino no existe en la fila de referencia del plano,
 *     se registra en el log y se omite (mejor esfuerzo, como el filler).
 *   - Si DOS celdas apuntan al mismo numero de destino (numeros duplicados),
 *     gana la ULTIMA en orden de lectura y se registra la colision en el log.
 *     NOTA: la plantilla actual no tiene duplicados (316 numeros, 4..319
 *     consecutivos, verificados automaticamente); el manejo queda como red de
 *     seguridad frente a futuras ediciones de la plantilla.
 *
 * @param array<string,int|string|float> $celdasReporte  Datos del reporte (aduCeldasExport), ej: ['D14' => 2].
 * @param array<string,int>              $mapaUbicacion  Salida de aduPlanoMapaUbicacion(), ej: ['D14' => 4].
 * @param array<int,string>              $indicePlano    Salida de aduPlanoIndiceDestino(), ej: [4 => 'D'].
 * @param int                            $filaDatos      Fila de datos del plano (por defecto 13).
 * @param int                            $offsetFila     Desplazamiento espejo -> plantilla oficial (por defecto 0).
 * @return array<string,int|string|float> Mapa [celdaPlano => valor], ej: ['D13' => 2].
 */
function aduPlanoConstruirCeldas(
    array $celdasReporte,
    array $mapaUbicacion,
    array $indicePlano,
    int $filaDatos = ADU_PLANO_FILA_DATOS,
    int $offsetFila = ADU_PLANO_OFFSET_FILA
): array {
    $celdasPlano = [];

    foreach ($mapaUbicacion as $celdaOrigen => $numero) {
        // Coordenadas de la celda espejo: letra(s) de columna + numero de fila.
        if (preg_match('/^([A-Z]+)([0-9]+)$/', $celdaOrigen, $m) !== 1) {
            continue; // referencia inesperada: omitir (no deberia ocurrir)
        }
        $columna     = $m[1];
        $filaOficial = (int)$m[2] + $offsetFila; // espejo -> plantilla oficial
        $celdaDato   = $columna . $filaOficial;

        // La celda de la plantilla oficial debe tener dato en el reporte
        // (aduCeldasExport produce las celdas de datos; el espejo no numera
        // las columnas Total ni la fila TOTAL E115).
        if (!array_key_exists($celdaDato, $celdasReporte)) {
            continue;
        }

        $numero = (int)$numero;
        if (!isset($indicePlano[$numero])) {
            error_log("[adu_plano] Numero de destino {$numero} (celda {$celdaOrigen}) "
                . "no existe en la fila " . ADU_PLANO_FILA_REFERENCIA . " del plano; celda omitida.");
            continue;
        }

        $refDestino = $indicePlano[$numero] . $filaDatos;
        if (isset($celdasPlano[$refDestino])) {
            error_log("[adu_plano] COLISION en {$refDestino}: el numero {$numero} esta asignado a "
                . "mas de una celda de 'Ubicacion_Filas_Adulto.xlsx'; gana el ultimo dato ({$celdaOrigen}).");
        }
        $celdasPlano[$refDestino] = $celdasReporte[$celdaDato];
    }

    return $celdasPlano;
}

/**
 * Ejecuta el flujo completo de mapeo "Exportar Plano" a partir de los datos
 * del reporte ya calculados (aduCeldasExport).
 *
 * @param array<string,int|string|float> $celdasReporte Salida de aduCeldasExport().
 * @param string      $pathUbicacion Ruta al archivo "Ubicacion_Filas_Adulto.xlsx".
 * @param string      $pathPlano     Ruta al archivo "Adulto_Plano.xlsx".
 * @param string|null $cacheDir      Ruta alternativa de cache; null = ADU_PLANO_CACHE_DIR.
 * @return array<string,int|string|float> Mapa [celdaPlano => valor] para ExcelTemplateFiller.
 */
function aduPlanoExportar(array $celdasReporte, string $pathUbicacion, string $pathPlano, ?string $cacheDir = null): array
{
    $mapaUbicacion = aduPlanoMapaUbicacion($pathUbicacion, $cacheDir);
    $indicePlano   = aduPlanoIndiceDestino($pathPlano, ADU_PLANO_FILA_REFERENCIA, $cacheDir);
    return aduPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano);
}
