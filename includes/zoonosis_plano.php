<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ZOONOSIS - Logica de mapeo para la plantilla consolidada
 * "Zoonosis_Plano.xlsx" (NUEVA FUNCIONALIDAD "Exportar Plano").
 *
 * ANTECEDENTE:
 *   zoonosis_export.php llena la plantilla oficial "Reporte_Actividades_Zoonosis.xlsx"
 *   mediante zooCeldasExport() (includes/zoonosis_render.php), que produce el
 *   mapa [celda => valor] de TODOS los datos del reporte, ej: ['D13' => 2].
 *
 * NUEVA FUNCIONALIDAD (mapeo de 3 archivos):
 *
 *   1) REFERENCIA DE UBICACION - archivo "Ubicacion Filas.xlsx":
 *      Es un espejo del reporte donde cada celda de datos contiene un NUMERO
 *      DE DESTINO. Ejemplo: celda D13 tiene como valor 288.
 *
 *   2) REFERENCIA DEL DATO - "Reporte_Actividades_Zoonosis.xlsx":
 *      La misma celda (D13) contiene el DATO calculado por el motor
 *      (zooCeldasExport). Ejemplo: D13 = 2.
 *
 *   3) REFERENCIA DEL DESTINO - archivo "Zoonosis_Plano.xlsx":
 *      En la fila 13, las celdas A13 hasta BPX13 contienen la numeracion de
 *      columnas (1..1792). Se localiza la celda cuyo numero coincide con el
 *      numero de destino: con 288 la celda correspondiente es KB13.
 *      POR LO TANTO el dato 2 de D13 se rellena en la celda KB14.
 *
 *      Para todas las demas celdas se sigue la misma logica: el archivo plano
 *      es un layout horizontal donde cada celda de datos del reporte ocupa su
 *      propia columna y la fila de datos es la 14 (encima quedan las cabeceras
 *      1-12 y la fila de referencia 13).
 *
 * MEJORAS:
 *   - CORRECCION DE DUPLICADOS: la plantilla original "Ubicacion Filas.xlsx"
 *     traia dos typos: el 749 estaba repetido en R130 y P131 (el 750 no
 *     existia) y el 968 repetido en D176 y E176 (el 969 no existia). La
 *     copia de la plantilla ya fue corregida (R130=750, E176=969, quedando
 *     P131=749 y D176=968) y ademas el codigo aplica la correccion como red
 *     de seguridad por si se despliega una copia antigua de la plantilla.
 *   - CACHE EN DISCO: el mapeo de ubicaciones y el indice del plano dependen
 *     solo de 2 archivos ESTATICOS; parsearlos en cada exportacion es trabajo
 *     repetido. El resultado se guarda en JSON (cache/plano/) y se regenera
 *     unicamente cuando cambia el archivo fuente (mtime o size distintos).
 *
 * Funciones PURAS (sin acceso a BD ni sesion) para poder probarlas de forma
 * aislada. Requiere includes/ExcelTemplateReader.php.
 */

require_once __DIR__ . '/ExcelTemplateReader.php';

/** Fila de "Zoonosis_Plano.xlsx" que contiene los numeros de referencia (A13:BPX13). */
const ZOO_PLANO_FILA_REFERENCIA = 13;

/** Fila de "Zoonosis_Plano.xlsx" donde se escribe la data (ej: KB14). */
const ZOO_PLANO_FILA_DATOS = 14;

// ==================== CACHE EN DISCO DEL MAPEO ====================
// Ubicacion por defecto de la cache: <raiz del sistema>/cache/plano/.
// La carpeta se crea automaticamente al primer uso. Para ubicarla en otra
// ruta (p.ej. un disco temporal), defina la constante ANTES de incluir este
// archivo:
//   define('ZOO_PLANO_CACHE_DIR', '/tmp/zoo_plano_cache');
if (!defined('ZOO_PLANO_CACHE_DIR')) {
    define('ZOO_PLANO_CACHE_DIR', dirname(__DIR__) . '/cache/plano');
}

// ==================== CORRECCIONES DE LA PLANTILLA ====================
/**
 * Typos documentados de la plantilla original "Ubicacion Filas.xlsx"
 * (corregidos a peticion del usuario):
 *
 *   - El 749 estaba repetido en R130 y P131 y el 750 no existia.
 *     Valor correcto: R130 = 750 (P131 se queda con 749).
 *   - El 968 estaba repetido en D176 y E176 y el 969 no existia.
 *     Valor correcto: E176 = 969 (D176 se queda con 968).
 *
 * La copia de "Ubicacion Filas.xlsx" ya viene corregida; este mapa se aplica
 * igualmente como RED DE SEGURIDAD para que el mapeo siga siendo correcto
 * incluso si se despliega una copia antigua de la plantilla con los typos.
 * Es IDEMPOTENTE: si la celda ya tiene el valor correcto, no hace nada.
 *
 * @return array<string,int> Mapa [celda => valorCorrecto].
 */
function zooPlanoCorrecciones(): array
{
    return [
        'R130' => 750, // antes 749 (duplicado con P131); 750 no existia
        'E176' => 969, // antes 968 (duplicado con D176); 969 no existia
    ];
}

/**
 * Aplica las correcciones documentadas (zooPlanoCorrecciones) sobre un mapa
 * de ubicacion ya leido. Registra en el log solo cuando corrige algo.
 *
 * @param array<string,int> $mapa Mapa [celdaOrigen => numeroDestino].
 * @return array<string,int> Mapa corregido.
 */
function zooPlanoAplicarCorrecciones(array $mapa): array
{
    foreach (zooPlanoCorrecciones() as $celda => $valor) {
        $valor = (int)$valor;
        if (!isset($mapa[$celda])) {
            continue; // la celda no existe en esta version de la plantilla
        }
        if ((int)$mapa[$celda] === $valor) {
            continue; // ya corregida (plantilla actualizada): no-op
        }
        error_log("[zoonosis_plano] Correccion de plantilla: {$celda} tenia "
            . "{$mapa[$celda]} y se corrige a {$valor} (typo documentado).");
        $mapa[$celda] = $valor;
    }
    return $mapa;
}

// ==================== CACHE (lectura/escritura JSON) ====================
/**
 * Devuelve el directorio de cache, creandolo si no existe (mejor esfuerzo).
 *
 * @param string|null $cacheDir Ruta alternativa; null = ZOO_PLANO_CACHE_DIR.
 * @return string Ruta absoluta del directorio de cache.
 */
function zooPlanoCacheDir(?string $cacheDir = null): string
{
    $dir = $cacheDir !== null && $cacheDir !== '' ? $cacheDir : ZOO_PLANO_CACHE_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Ruta del archivo de cache para una clave y archivo fuente concretas.
 * El nombre incluye un hash de la ruta fuente para evitar colisiones entre
 * despliegues, p.ej.: ubicacion_filas_ubicacion_3f2a91bc.json
 */
function zooPlanoCacheRuta(string $clave, string $fuente, ?string $cacheDir = null): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '_', pathinfo($fuente, PATHINFO_FILENAME));
    $hash = substr(md5($fuente), 0, 8);
    return zooPlanoCacheDir($cacheDir) . '/' . strtolower((string)$base) . "_{$clave}_{$hash}.json";
}

/**
 * Lee el cache de un mapeo si sigue VIGENTE.
 *
 * Validez: el JSON existe, decodifica y ademas mtime y size del archivo
 * fuente COINCIDEN con los guardados. Si el xlsx se reemplaza o edita, la
 * cache queda invalidada y se regenera automaticamente.
 *
 * @param string      $clave    Clave logica ('ubicacion', 'plano_fila13', ...).
 * @param string      $fuente   Ruta del archivo xlsx del que proviene el mapeo.
 * @param string|null $cacheDir Ruta alternativa de cache; null = por defecto.
 * @return array<string,int>|array<int,string>|null Valores cacheados o null si no hay cache vigente.
 */
function zooPlanoCacheLeer(string $clave, string $fuente, ?string $cacheDir = null): ?array
{
    $ruta = zooPlanoCacheRuta($clave, $fuente, $cacheDir);
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
        error_log("[zoonosis_plano] Cache '{$clave}' ilegible o corrupta; se regenera desde el archivo.");
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
function zooPlanoCacheGuardar(string $clave, string $fuente, array $valores, ?string $cacheDir = null): void
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
    $ruta = zooPlanoCacheRuta($clave, $fuente, $cacheDir);
    if ($json === false || @file_put_contents($ruta, $json, LOCK_EX) === false) {
        error_log("[zoonosis_plano] No se pudo escribir la cache '{$clave}' en {$ruta}; "
            . "se seguira leyendo el xlsx en cada exportacion.");
    }
}

/**
 * Lee el archivo "Ubicacion Filas.xlsx" y devuelve el mapa de destino de cada
 * celda del reporte, con las correcciones de typos ya aplicadas.
 *
 * Usa CACHE EN DISCO: solo parsea el xlsx la primera vez (o cuando el archivo
 * cambia); las siguientes llamadas leen el JSON cacheado.
 *
 * Solo se toman las CELDAS NUMERICAS (los numeros de destino); las etiquetas
 * de texto del espejo se ignoran. Ejemplo de salida:
 *   ['D13' => 288, 'E13' => 290, 'D14' => 289, ...]
 *
 * @param string      $path     Ruta al archivo "Ubicacion Filas.xlsx".
 * @param string|null $cacheDir Ruta alternativa de cache; null = ZOO_PLANO_CACHE_DIR.
 * @return array<string,int> Mapa [celdaOrigen => numeroDestino] corregido.
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function zooPlanoMapaUbicacion(string $path, ?string $cacheDir = null): array
{
    $mapa = zooPlanoCacheLeer('ubicacion', $path, $cacheDir);
    if ($mapa === null) {
        $lector = new ExcelTemplateReader($path);
        $mapa = [];
        foreach ($lector->readNumericCells() as $ref => $valor) {
            $mapa[$ref] = (int)$valor;
        }
        zooPlanoCacheGuardar('ubicacion', $path, $mapa, $cacheDir);
    }
    // Red de seguridad contra los typos documentados (idempotente).
    return zooPlanoAplicarCorrecciones($mapa);
}

/**
 * Lee la plantilla "Zoonosis_Plano.xlsx" y construye el indice
 * [numeroReferencia => letraColumna] a partir de su fila de referencia
 * (A13:BPX13 con valores 1..1792).
 *
 * Usa CACHE EN DISCO (clave 'plano_fila{N}'), igual que el mapa de ubicacion.
 *
 * Ejemplo de salida: [288 => 'KB', 289 => 'KC', ...]
 * Asi, el numero 288 de "Ubicacion Filas.xlsx" resuelve a la columna KB y el
 * dato se escribe en KB14 (columna destino + fila de datos).
 *
 * @param string      $path            Ruta al archivo "Zoonosis_Plano.xlsx".
 * @param int         $filaReferencia  Fila con la numeracion (por defecto 13).
 * @param string|null $cacheDir        Ruta alternativa de cache; null = por defecto.
 * @return array<int,string> Mapa [numeroReferencia => letraColumna].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function zooPlanoIndiceDestino(string $path, int $filaReferencia = ZOO_PLANO_FILA_REFERENCIA, ?string $cacheDir = null): array
{
    $clave   = 'plano_fila' . $filaReferencia;
    $cache   = zooPlanoCacheLeer($clave, $path, $cacheDir);
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
    zooPlanoCacheGuardar($clave, $path, $indice, $cacheDir);
    return $indice;
}

/**
 * Cruza los DATOS del reporte con el mapa de ubicacion y el indice del plano.
 *
 * Por cada celda del mapa de ubicacion:
 *   - Si el reporte produce un dato para esa celda (zooCeldasExport) y el
 *     numero de destino existe en la fila de referencia del plano, el dato se
 *     asigna a la celda destino: {letraColumna}{filaDatos}. Ej: D13 (dato 2)
 *     + numero 288 (= KB) -> 'KB14' => 2.
 *   - Si el reporte no produce dato para esa celda (p.ej. filas TOTAL que el
 *     plano no tiene, o cabeceras), la celda se omite.
 *   - Si el numero de destino no existe en la fila de referencia del plano,
 *     se registra en el log y se omite (mejor esfuerzo, como el filler).
 *   - Si DOS celdas apuntan al mismo numero de destino (numeros duplicados),
 *     gana la ULTIMA en orden de lectura y se registra la colision en el log.
 *     NOTA: los duplicados 749/968 de la plantilla original ya fueron
 *     corregidos (R130=750, E176=969), por lo que con la plantilla actual
 *     esta situacion no deberia ocurrir; el manejo queda como red de
 *     seguridad frente a futuras ediciones de la plantilla.
 *
 * @param array<string,int|string|float> $celdasReporte  Datos del reporte (zooCeldasExport), ej: ['D13' => 2].
 * @param array<string,int>              $mapaUbicacion  Salida de zooPlanoMapaUbicacion(), ej: ['D13' => 288].
 * @param array<int,string>              $indicePlano    Salida de zooPlanoIndiceDestino(), ej: [288 => 'KB'].
 * @param int                            $filaDatos      Fila de datos del plano (por defecto 14).
 * @return array<string,int|string|float> Mapa [celdaPlano => valor], ej: ['KB14' => 2].
 */
function zooPlanoConstruirCeldas(
    array $celdasReporte,
    array $mapaUbicacion,
    array $indicePlano,
    int $filaDatos = ZOO_PLANO_FILA_DATOS
): array {
    $celdasPlano = [];

    foreach ($mapaUbicacion as $celdaOrigen => $numero) {
        // La celda origen debe tener dato en el reporte (mismo layout que la
        // plantilla oficial: zooCeldasExport produce TODAS las filas T/M/F,
        // el plano solo mapea las que tienen numero de destino).
        if (!array_key_exists($celdaOrigen, $celdasReporte)) {
            continue;
        }

        $numero = (int)$numero;
        if (!isset($indicePlano[$numero])) {
            error_log("[zoonosis_plano] Numero de destino {$numero} (celda {$celdaOrigen}) "
                . "no existe en la fila " . ZOO_PLANO_FILA_REFERENCIA . " del plano; celda omitida.");
            continue;
        }

        $refDestino = $indicePlano[$numero] . $filaDatos;
        if (isset($celdasPlano[$refDestino])) {
            error_log("[zoonosis_plano] COLISION en {$refDestino}: el numero {$numero} esta asignado a "
                . "mas de una celda de 'Ubicacion Filas.xlsx'; gana el ultimo dato ({$celdaOrigen}).");
        }
        $celdasPlano[$refDestino] = $celdasReporte[$celdaOrigen];
    }

    return $celdasPlano;
}

/**
 * Ejecuta el flujo completo de mapeo "Exportar Plano" a partir de los datos
 * del reporte ya calculados (zooCeldasExport).
 *
 * @param array<string,int|string|float> $celdasReporte Salida de zooCeldasExport().
 * @param string      $pathUbicacion Ruta al archivo "Ubicacion Filas.xlsx".
 * @param string      $pathPlano     Ruta al archivo "Zoonosis_Plano.xlsx".
 * @param string|null $cacheDir      Ruta alternativa de cache; null = ZOO_PLANO_CACHE_DIR.
 * @return array<string,int|string|float> Mapa [celdaPlano => valor] para ExcelTemplateFiller.
 */
function zooPlanoExportar(array $celdasReporte, string $pathUbicacion, string $pathPlano, ?string $cacheDir = null): array
{
    $mapaUbicacion = zooPlanoMapaUbicacion($pathUbicacion, $cacheDir);
    $indicePlano   = zooPlanoIndiceDestino($pathPlano, ZOO_PLANO_FILA_REFERENCIA, $cacheDir);
    return zooPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano);
}
