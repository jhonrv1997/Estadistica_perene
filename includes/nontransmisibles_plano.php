<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo NO TRANSMISIBLES - Logica de mapeo para el plano consolidado
 * "Notransmisibles_Plano.xlsx" (NUEVA FUNCIONALIDAD "Exportar Plano").
 *
 * ANTECEDENTE:
 *   nontransmisibles_export.php llena la plantilla oficial 2026
 *   "Reporte_Actividades_NoTransmisiblesN.xlsx" mediante ntCeldasExport()
 *   (includes/nontransmisibles_render.php), que produce el mapa
 *   [celda => valor] de TODOS los datos del reporte, ej: ['C12' => 5].
 *
 * NUEVA FUNCIONALIDAD (mapeo de 3 archivos, mismo patron que ITS/Zoonosis/
 * Materno):
 *
 *   1) REFERENCIA DE UBICACION - archivo "Ubicacion_Filas_NoTransmisibles.xlsx":
 *      Es el espejo del reporte donde cada celda de datos contiene un NUMERO
 *      DE DESTINO. Ejemplo: la celda C12 (Sobrepeso, TOTAL) tiene el valor 4.
 *      El espejo trae 4944 numeros (4..4947, secuenciales en orden de lectura
 *      fila a fila; verificado automaticamente contra este motor).
 *
 *   2) REFERENCIA DEL DATO - plantilla oficial 2026 "Reporte_Actividades_
 *      NoTransmisiblesN.xlsx": la celda equivalente (MISMA letra de columna y
 *      MISMA fila) contiene el DATO calculado por el motor (ntCeldasExport).
 *      Ejemplo: C12 = 5.
 *      El espejo calza FILA POR FILA y COLUMNA POR COLUMNA con las coordenadas
 *      de ntCeldasExport (NT_PLANO_OFFSET_FILA = 0, sin ajustes de columna).
 *      Verificacion automatica: las 4918 celdas de datos que produce
 *      ntCeldasExport (VR01 12-31 ... TM05 423, layouts 6GC/8GC/8GE/SES) tienen
 *      su numero en el espejo, cobertura 100%.
 *
 *   3) REFERENCIA DEL DESTINO - archivo "Notransmisibles_Plano.xlsx":
 *      En la fila 7, las celdas A7:GHG7 contienen la numeracion de columnas
 *      (1..4947). Se localiza la celda cuyo numero coincide con el numero de
 *      destino: con 4 la columna correspondiente es D.
 *      => El dato 5 de C12 se rellena en la celda D8. Para todas las demas
 *         celdas se sigue la misma logica: el plano es un layout horizontal
 *         donde cada celda de datos del reporte ocupa su propia columna
 *         (1=A Ubigeo, 2=B Codigo RENAES, 3=C Dpto/Red/Provincia/Distrito/
 *         EE.SS) y la fila de datos es la 8 (encima quedan las cabeceras 1-6
 *         y la fila de referencia 7).
 *
 * CELDAS DEL ESPEJO SIN DATO EN EL MOTOR (filas 99 y 107):
 *   El espejo numera 2 filas que la plantilla oficial 2026 y el motor ya no
 *   producen (26 columnas del plano quedan vacias):
 *     - Fila 99  "PACIENTE CONTROLADO"             -> numeros 641-653
 *     - Fila 107 "ATENCION INTEGRAL ESPECIALIZADO" -> numeros 745-757
 *   ntPlanoConstruirCeldas() las omite de forma segura (mejor esfuerzo), igual
 *   que el manejo de celdas sin dato en los modulos ITS/Zoonosis/Materno.
 *
 * CACHE DEL MAPEO (mejora de rendimiento):
 *   El mapa de ubicaciones y el indice del plano solo dependen de 2 archivos
 *   estaticos, por lo que se cachean en JSON dentro de cache/plano/ (misma
 *   carpeta que los planos de ITS/Zoonosis/Materno: el nombre del JSON incluye
 *   el nombre del xlsx fuente y su hash, por lo que no hay colisiones entre
 *   modulos). La cache se regenera automaticamente si cambia alguno de los
 *   xlsx (mtime o size distintos), de modo que reemplazar una plantilla nunca
 *   produce datos viejos. Esto es critico aqui: el plano NT tiene 4947
 *   columnas y su lectura directa en cada exportacion seria lenta.
 *
 * Funciones PURAS (sin acceso a BD ni sesion) para poder probarlas de forma
 * aislada. Requiere includes/ExcelTemplateReader.php.
 */

require_once __DIR__ . '/ExcelTemplateReader.php';

/** Fila de "Notransmisibles_Plano.xlsx" que contiene los numeros de referencia (A7:GHG7). */
const NT_PLANO_FILA_REFERENCIA = 7;

/** Fila de "Notransmisibles_Plano.xlsx" donde se escribe la data (ej: D8). */
const NT_PLANO_FILA_DATOS = 8;

/**
 * Desplazamiento del espejo "Ubicacion_Filas_NoTransmisibles.xlsx" respecto de
 * las coordenadas que produce ntCeldasExport(): aqui el espejo calza fila por
 * fila con la plantilla oficial (VR01 12-31, VR02 36-37, VR03 42-43, VR04 47-50,
 * VR05 55, HTA1 62-69, HTA2 75-77, HTA3 82-83, HTA4 88-94, HTA5 100-106+108-111,
 * HTA6 116-120, HTA7 125-130, DM01 137-149, DM02 155-161, DM03 166-217,
 * DM04 222-273, DM05 278-287, DM06 292-379, DM07 384-393, TM01 400-402,
 * TM02 407, TM03 412-413, TM04 418-419, TM05 423), por lo que el offset es 0.
 * Se mantiene como constante por simetria con ITS (offset 8).
 */
const NT_PLANO_OFFSET_FILA = 0;

// ==================== CACHE EN DISCO DEL MAPEO ====================
// Ubicacion por defecto de la cache: <raiz del sistema>/cache/plano/ (la misma
// carpeta que los planos de ITS/Zoonosis/Materno; el nombre del JSON incluye
// el nombre del xlsx fuente y su hash, por lo que no hay colisiones entre
// modulos). La carpeta se crea automaticamente al primer uso. Para ubicarla en
// otra ruta, defina la constante ANTES de incluir este archivo:
//   define('NT_PLANO_CACHE_DIR', '/tmp/nt_plano_cache');
if (!defined('NT_PLANO_CACHE_DIR')) {
    define('NT_PLANO_CACHE_DIR', dirname(__DIR__) . '/cache/plano');
}

/**
 * Devuelve el directorio de cache, creandolo si no existe (mejor esfuerzo).
 *
 * @param string|null $cacheDir Ruta alternativa; null = NT_PLANO_CACHE_DIR.
 * @return string Ruta absoluta del directorio de cache.
 */
function ntPlanoCacheDir(?string $cacheDir = null): string
{
    $dir = $cacheDir !== null && $cacheDir !== '' ? $cacheDir : NT_PLANO_CACHE_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Ruta del archivo de cache para una clave y archivo fuente concretas.
 * El nombre incluye un hash de la ruta fuente para evitar colisiones, p.ej.:
 * ubicacion_filas_no_transmisibles_ubicacion_3f2a91bc.json
 */
function ntPlanoCacheRuta(string $clave, string $fuente, ?string $cacheDir = null): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '_', pathinfo($fuente, PATHINFO_FILENAME));
    $hash = substr(md5($fuente), 0, 8);
    return ntPlanoCacheDir($cacheDir) . '/' . strtolower((string)$base) . "_{$clave}_{$hash}.json";
}

/**
 * Lee el cache de un mapeo si sigue VIGENTE (mtime y size del xlsx fuente
 * coinciden). Si el xlsx se reemplaza o edita, la cache queda invalidada.
 *
 * @return array<string,int>|array<int,string>|null Valores cacheados o null.
 */
function ntPlanoCacheLeer(string $clave, string $fuente, ?string $cacheDir = null): ?array
{
    $ruta = ntPlanoCacheRuta($clave, $fuente, $cacheDir);
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
        error_log("[nt_plano] Cache '{$clave}' ilegible o corrupta; se regenera desde el archivo.");
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
 * Guarda un mapeo en la cache (mejor esfuerzo: si no se puede escribir, solo
 * registra en el log y el flujo sigue leyendo el xlsx directamente).
 */
function ntPlanoCacheGuardar(string $clave, string $fuente, array $valores, ?string $cacheDir = null): void
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
    $ruta = ntPlanoCacheRuta($clave, $fuente, $cacheDir);
    if ($json === false || @file_put_contents($ruta, $json, LOCK_EX) === false) {
        error_log("[nt_plano] No se pudo escribir la cache '{$clave}' en {$ruta}; "
            . "se seguira leyendo el xlsx en cada exportacion.");
    }
}

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
/**
 * Lee el archivo "Ubicacion_Filas_NoTransmisibles.xlsx" y devuelve el mapa de
 * destino de cada celda del reporte.
 *
 * Usa CACHE EN DISCO: solo parsea el xlsx la primera vez (o cuando el archivo
 * cambia); las siguientes llamadas leen el JSON cacheado.
 *
 * Solo se toman las CELDAS NUMERICAS (los numeros de destino); las etiquetas
 * de texto del espejo se ignoran. Ejemplo de salida:
 *   ['C12' => 4, 'D12' => 5, 'E12' => 6, ...]
 *
 * Nota: el espejo de No Transmisibles trae 4944 numeros UNICOS (4..4947 sin
 * duplicados ni faltantes, verificado automaticamente), por lo que NO se
 * aplican correcciones de typos (a diferencia de its_plano.php / zoo_plano).
 *
 * @param string      $path     Ruta al archivo "Ubicacion_Filas_NoTransmisibles.xlsx".
 * @param string|null $cacheDir Ruta alternativa de cache; null = por defecto.
 * @return array<string,int> Mapa [celdaOrigen => numeroDestino].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function ntPlanoMapaUbicacion(string $path, ?string $cacheDir = null): array
{
    $mapa = ntPlanoCacheLeer('ubicacion', $path, $cacheDir);
    if ($mapa === null) {
        $lector = new ExcelTemplateReader($path);
        $mapa = [];
        foreach ($lector->readNumericCells() as $ref => $valor) {
            $mapa[$ref] = (int)$valor;
        }
        ntPlanoCacheGuardar('ubicacion', $path, $mapa, $cacheDir);
    }
    return $mapa;
}

/**
 * Lee la plantilla "Notransmisibles_Plano.xlsx" y construye el indice
 * [numeroReferencia => letraColumna] a partir de su fila de referencia
 * (A7:GHG7 con valores 1..4947).
 *
 * Usa CACHE EN DISCO (clave 'plano_fila{N}'), igual que el mapa de ubicacion.
 *
 * Ejemplo de salida: [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', ..., 4947 => 'GHG']
 * Asi, el numero 4 de "Ubicacion_Filas_NoTransmisibles.xlsx" resuelve a la
 * columna D y el dato se escribe en D8 (columna destino + fila de datos).
 *
 * @param string      $path            Ruta al archivo "Notransmisibles_Plano.xlsx".
 * @param int         $filaReferencia  Fila con la numeracion (por defecto 7).
 * @param string|null $cacheDir        Ruta alternativa de cache; null = por defecto.
 * @return array<int,string> Mapa [numeroReferencia => letraColumna].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function ntPlanoIndiceDestino(string $path, int $filaReferencia = NT_PLANO_FILA_REFERENCIA, ?string $cacheDir = null): array
{
    $clave = 'plano_fila' . $filaReferencia;
    $cache = ntPlanoCacheLeer($clave, $path, $cacheDir);
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
    ntPlanoCacheGuardar($clave, $path, $indice, $cacheDir);
    return $indice;
}

/**
 * Cruza los DATOS del reporte con el mapa de ubicacion y el indice del plano.
 *
 * A diferencia de ITS, aqui el espejo calza fila por fila con la plantilla
 * oficial (NT_PLANO_OFFSET_FILA = 0) y SIN ajustes de columna (verificado
 * automaticamente celda a celda): la celda espejo {col}{fila} contiene el
 * numero de destino y el dato esta en la MISMA celda {col}{fila} del mapa que
 * produce ntCeldasExport().
 *
 * Por cada celda del mapa de ubicacion:
 *   - Se resuelve la celda de la plantilla oficial: {columna}{fila + offset}.
 *     Ej: espejo C12 (numero 4) -> oficial C12.
 *   - Si el reporte produce un dato para esa celda (ntCeldasExport) y el
 *     numero de destino existe en la fila de referencia del plano, el dato se
 *     asigna a la celda destino: {letraColumna}{filaDatos}.
 *     Ej: C12 (numero 4) -> dato de C12 -> columna D -> 'D8' => dato.
 *   - Si el reporte no produce dato para esa celda, la celda se omite de forma
 *     segura (mejor esfuerzo). Es el caso de las filas 99 ("PACIENTE
 *     CONTROLADO", numeros 641-653) y 107 ("ATENCION INTEGRAL ESPECIALIZADO",
 *     numeros 745-757), que la plantilla oficial 2026 / el motor ya no
 *     producen: esas 26 columnas del plano quedan vacias.
 *   - Si el numero de destino no existe en la fila de referencia del plano,
 *     se registra en el log y se omite.
 *   - Si DOS celdas apuntan al mismo numero de destino, gana la ULTIMA en
 *     orden de lectura y se registra la colision en el log (con el espejo
 *     actual, 4944 numeros unicos, esta situacion no deberia ocurrir; el
 *     manejo queda como red de seguridad frente a futuras ediciones).
 *
 * @param array<string,int|string|float> $celdasReporte  Datos del reporte (ntCeldasExport), ej: ['C12' => 5].
 * @param array<string,int>              $mapaUbicacion  Salida de ntPlanoMapaUbicacion(), ej: ['C12' => 4].
 * @param array<int,string>              $indicePlano    Salida de ntPlanoIndiceDestino(), ej: [4 => 'D'].
 * @param int                            $filaDatos      Fila de datos del plano (por defecto 8).
 * @param int                            $offsetFila     Desplazamiento espejo -> plantilla oficial (por defecto 0).
 * @return array<string,int|string|float> Mapa [celdaPlano => valor], ej: ['D8' => 5].
 */
function ntPlanoConstruirCeldas(
    array $celdasReporte,
    array $mapaUbicacion,
    array $indicePlano,
    int $filaDatos = NT_PLANO_FILA_DATOS,
    int $offsetFila = NT_PLANO_OFFSET_FILA
): array {
    $celdasPlano = [];

    foreach ($mapaUbicacion as $celdaOrigen => $numero) {
        // Coordenadas de la celda espejo: letra(s) de columna + numero de fila.
        if (preg_match('/^([A-Z]+)([0-9]+)$/', (string)$celdaOrigen, $m) !== 1) {
            continue; // referencia inesperada: omitir (no deberia ocurrir)
        }
        $numero = (int)$numero;

        // Coordenadas de la celda de la plantilla oficial (sin ajustes de
        // columna: el espejo NT calza columna por columna con ntCeldasExport).
        $colIdx = ExcelTemplateReader::colLetterToIndex($m[1]);
        $filaOficial = (int)$m[2] + $offsetFila;
        $celdaDato = ExcelTemplateReader::colIndexToLetter($colIdx) . $filaOficial;

        // La celda de la plantilla oficial debe tener dato en el reporte
        // (ntCeldasExport). Ej: las filas 99 y 107 del espejo no se producen
        // en el motor 2026 y sus columnas del plano quedan vacias.
        if (!array_key_exists($celdaDato, $celdasReporte)) {
            continue;
        }

        if (!isset($indicePlano[$numero])) {
            error_log("[nt_plano] Numero de destino {$numero} (celda {$celdaOrigen}) "
                . "no existe en la fila " . NT_PLANO_FILA_REFERENCIA . " del plano; celda omitida.");
            continue;
        }

        $refDestino = $indicePlano[$numero] . $filaDatos;
        if (isset($celdasPlano[$refDestino])) {
            error_log("[nt_plano] COLISION en {$refDestino}: el numero {$numero} esta asignado a "
                . "mas de una celda de 'Ubicacion_Filas_NoTransmisibles.xlsx'; gana el ultimo dato ({$celdaOrigen}).");
        }
        $celdasPlano[$refDestino] = $celdasReporte[$celdaDato];
    }

    return $celdasPlano;
}

/**
 * Ejecuta el flujo completo de mapeo "Exportar Plano" a partir de los datos
 * del reporte ya calculados (ntCeldasExport).
 *
 * @param array<string,int|string|float> $celdasReporte Salida de ntCeldasExport().
 * @param string      $pathUbicacion Ruta al archivo "Ubicacion_Filas_NoTransmisibles.xlsx".
 * @param string      $pathPlano     Ruta al archivo "Notransmisibles_Plano.xlsx".
 * @param string|null $cacheDir      Ruta alternativa de cache; null = por defecto.
 * @return array<string,int|string|float> Mapa [celdaPlano => valor] para ExcelTemplateFiller.
 */
function ntPlanoExportar(array $celdasReporte, string $pathUbicacion, string $pathPlano, ?string $cacheDir = null): array
{
    $mapaUbicacion = ntPlanoMapaUbicacion($pathUbicacion, $cacheDir);
    $indicePlano   = ntPlanoIndiceDestino($pathPlano, NT_PLANO_FILA_REFERENCIA, $cacheDir);
    return ntPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano);
}
