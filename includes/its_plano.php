<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ITS/VIH - Logica de mapeo para la plantilla consolidada
 * "ITS_Plano.xlsx" (NUEVA FUNCIONALIDAD "Exportar Plano").
 *
 * ANTECEDENTE:
 *   its_export.php llena la plantilla oficial "Reporte_Actividades_ITS.xlsx"
 *   mediante itsCeldasExport() (includes/its_render.php), que produce el mapa
 *   [celda => valor] de TODOS los datos del reporte, ej: ['B13' => 2].
 *
 * NUEVA FUNCIONALIDAD (mapeo de 3 archivos):
 *
 *   1) REFERENCIA DE UBICACION - archivo "Ubicacion_Filas_ITS.xlsx":
 *      Es un espejo del reporte oficial donde cada celda de datos contiene un
 *      NUMERO DE DESTINO. Ejemplo: la celda B5 (Dx F 01d-11a de "PERSONAS CON
 *      MANEJO SINDROMICO") tiene como valor 4.
 *
 *   2) REFERENCIA DEL DATO - "Reporte_Actividades_ITS.xlsx":
 *      La celda equivalente de la plantilla oficial (B13, MISMA fila de datos
 *      del reporte pero 8 filas mas abajo: el espejo esta compactado) contiene
 *      el DATO calculado por el motor (itsCeldasExport). Ejemplo: B13 = 2.
 *
 *   3) REFERENCIA DEL DESTINO - archivo "ITS_Plano.xlsx":
 *      En la fila 12, las celdas A12 hasta CBM12 contienen la numeracion de
 *      columnas (1..2093, sin duplicados ni faltantes). Se localiza la celda
 *      cuyo numero coincide con el numero de destino: con 4 la celda
 *      correspondiente es D12.
 *      POR LO TANTO el dato 2 de B13 se rellena en la celda D13.
 *
 *      Para todas las demas celdas se sigue la misma logica: el archivo plano
 *      es un layout horizontal donde cada celda de datos del reporte ocupa su
 *      propia columna (RENAES=1/A, RENAES=2/B, Establecimiento=3/C) y la fila
 *      de datos es la 13 (encima quedan las cabeceras 1-11 y la fila de
 *      referencia 12).
 *
 * OFFSET DEL ESPEJO (diferencia con Zoonosis):
 *   El espejo "Ubicacion Filas.xlsx" de Zoonosis tenia EXACTAMENTE las mismas
 *   coordenadas que la plantilla oficial. En ITS, en cambio, el espejo esta
 *   COMPACTADO 8 filas hacia arriba: la seccion 1 empieza en la fila 5 del
 *   espejo (fila 13 de la plantilla oficial), la seccion 2 en la 21 (29), etc.
 *   Verificado seccion por seccion (16 bloques):
 *     espejo  5-17  <-> oficial  13-25   (1.- POBLACION GENERAL: ITS, FMC3)
 *     espejo 21-28  <-> oficial  29-36   (2.- HEPATITIS B, FM5)
 *     espejo 32-37  <-> oficial  40-45   (2A.- HEPATITIS C, FM5)
 *     espejo 41-49  <-> oficial  49-57   (3.- TAMIZAJE VIH, FM5)
 *     espejo 53-61  <-> oficial  61-69   (4.- EXPOSICION, FM5)
 *     espejo 65-69  <-> oficial  73-77   (5.- VIOLENCIA SEXUAL, FM5)
 *     espejo 73-84  <-> oficial  81-92   (6.- PVV, FM5)
 *     espejo 87-97  <-> oficial  95-105  (7.- GESTANTES etareo, GES4)
 *     espejo 100-110<-> oficial 108-118  (7.- GESTANTES trimestre, TRI3)
 *     espejo 113-122<-> oficial 121-130  (7A.- RECIEN NACIDOS, RN1)
 *     espejo 126-137<-> oficial 134-145  (8.- POBLACION CLAVE, PC5)
 *     espejo 141-151<-> oficial 149-159  (9.- PC ITS dx/tto, PC5C)
 *     espejo 154-164<-> oficial 162-172  (9.- PC CONTACTOS, CC2)
 *     espejo 169-176<-> oficial 177-184  (10.- BRIGADAS MCC, BRIG10)
 *     espejo 180-187<-> oficial 188-195  (11.- BMA, FM5T)
 *     espejo 191-195<-> oficial 199-203  (12.- TELEMEDICINA, FM5T)
 *   Todas con offset +8, por lo que la constante ITS_PLANO_OFFSET_FILA = 8
 *   convierte fila del espejo -> fila de la plantilla oficial.
 *
 * CORRECCION DE DUPLICADOS (plantilla "Ubicacion_Filas_ITS.xlsx"):
 *   La plantilla trae 1 typo: el 1241 esta repetido en H9 y G131, y el 124 no
 *   existe. Por la secuencia de la fila 9 (G9=123, I9=125) el valor correcto
 *   de H9 (Dx F 18a-29a de "CASOS DE BUBON INGUINAL") es 124. La correccion se
 *   aplica en codigo como red de seguridad (itsPlanoCorrecciones()), de modo
 *   que el mapeo sigue siendo correcto incluso si se despliega una copia
 *   antigua de la plantilla. Es IDEMPOTENTE: si la celda ya tiene el valor
 *   correcto, no hace nada.
 *
 * CELDAS SIN NUMERO DE DESTINO (por diseno del plano):
 *   - Columna Total (F) de GESTANTES por grupo etareo: el espejo solo numera
 *     B-E (el plano calcula/muestra el total por su cuenta).
 *   - Fila "CASOS DE DESCARGA URETRAL" de la seccion 1: solo columnas M
 *     (diagnostico masculino); y "CASOS DE FLUJO VAGINAL": solo columnas F.
 *   - Numeros 1, 2 y 3 del plano (A/B/C = RENAES/RENAES/Establecimiento): se
 *     rellenan directamente en its_export_plano.php, no vienen del espejo.
 *   Todas se omiten de forma segura (mejor esfuerzo, igual que Zoonosis).
 *
 * CACHE DEL MAPEO (mejora de rendimiento):
 *   El mapa de ubicaciones y el indice del plano solo dependen de 2 archivos
 *   estaticos, por lo que se cachean en JSON dentro de cache/plano/ (misma
 *   carpeta que Zoonosis: los nombres de archivo incluyen el nombre del xlsx
 *   fuente, no hay colisiones). La cache se regenera automaticamente si cambia
 *   alguno de los xlsx (mtime o size distintos).
 *
 * Funciones PURAS (sin acceso a BD ni sesion) para poder probarlas de forma
 * aislada. Requiere includes/ExcelTemplateReader.php.
 */

require_once __DIR__ . '/ExcelTemplateReader.php';

/** Fila de "ITS_Plano.xlsx" que contiene los numeros de referencia (A12:CBM12). */
const ITS_PLANO_FILA_REFERENCIA = 12;

/** Fila de "ITS_Plano.xlsx" donde se escribe la data (ej: D13). */
const ITS_PLANO_FILA_DATOS = 13;

/**
 * Desplazamiento del espejo "Ubicacion_Filas_ITS.xlsx" respecto a la plantilla
 * oficial "Reporte_Actividades_ITS.xlsx": espejo fila R <-> oficial fila R+8.
 * Ej: espejo B5=4 -> dato en B13 (itsCeldasExport) -> plano D13.
 */
const ITS_PLANO_OFFSET_FILA = 8;

// ==================== CACHE EN DISCO DEL MAPEO ====================
// Ubicacion por defecto de la cache: <raiz del sistema>/cache/plano/ (la misma
// carpeta que el plano de Zoonosis; el nombre del JSON incluye el nombre del
// xlsx fuente y su hash, por lo que no hay colisiones entre modulos).
// La carpeta se crea automaticamente al primer uso. Para ubicarla en otra
// ruta (p.ej. un disco temporal), defina la constante ANTES de incluir este
// archivo:
//   define('ITS_PLANO_CACHE_DIR', '/tmp/its_plano_cache');
if (!defined('ITS_PLANO_CACHE_DIR')) {
    define('ITS_PLANO_CACHE_DIR', dirname(__DIR__) . '/cache/plano');
}

// ==================== CORRECCIONES DE LA PLANTILLA ====================
/**
 * Typos documentados de la plantilla "Ubicacion_Filas_ITS.xlsx":
 *
 *   - El 1241 estaba repetido en H9 (CASOS DE BUBON INGUINAL, Dx F 18a-29a) y
 *     en G131 (TS/HSH/TRANS tamizaje VIH reactivo, TRANS 30a-59a), y el 124 no
 *     existia. Por la secuencia de la fila 9 del espejo (G9=123, I9=125), el
 *     valor correcto de H9 es 124 (G131 se queda con 1241, que si existe en la
 *     fila 12 del plano: AUS12).
 *
 * Este mapa se aplica como RED DE SEGURIDAD para que el mapeo siga siendo
 * correcto incluso si se despliega una copia antigua de la plantilla con el
 * typo. Es IDEMPOTENTE: si la celda ya tiene el valor correcto, no hace nada.
 *
 * @return array<string,int> Mapa [celda => valorCorrecto].
 */
function itsPlanoCorrecciones(): array
{
    return [
        'H9' => 124, // antes 1241 (duplicado con G131); 124 no existia
    ];
}

/**
 * Aplica las correcciones documentadas (itsPlanoCorrecciones) sobre un mapa
 * de ubicacion ya leido. Registra en el log solo cuando corrige algo.
 *
 * @param array<string,int> $mapa Mapa [celdaOrigen => numeroDestino].
 * @return array<string,int> Mapa corregido.
 */
function itsPlanoAplicarCorrecciones(array $mapa): array
{
    foreach (itsPlanoCorrecciones() as $celda => $valor) {
        $valor = (int)$valor;
        if (!isset($mapa[$celda])) {
            continue; // la celda no existe en esta version de la plantilla
        }
        if ((int)$mapa[$celda] === $valor) {
            continue; // ya corregida (plantilla actualizada): no-op
        }
        error_log("[its_plano] Correccion de plantilla: {$celda} tenia "
            . "{$mapa[$celda]} y se corrige a {$valor} (typo documentado).");
        $mapa[$celda] = $valor;
    }
    return $mapa;
}

// ==================== CACHE (lectura/escritura JSON) ====================
/**
 * Devuelve el directorio de cache, creandolo si no existe (mejor esfuerzo).
 *
 * @param string|null $cacheDir Ruta alternativa; null = ITS_PLANO_CACHE_DIR.
 * @return string Ruta absoluta del directorio de cache.
 */
function itsPlanoCacheDir(?string $cacheDir = null): string
{
    $dir = $cacheDir !== null && $cacheDir !== '' ? $cacheDir : ITS_PLANO_CACHE_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Ruta del archivo de cache para una clave y archivo fuente concretas.
 * El nombre incluye un hash de la ruta fuente para evitar colisiones entre
 * despliegues, p.ej.: ubicacion_filas_its_ubicacion_3f2a91bc.json
 */
function itsPlanoCacheRuta(string $clave, string $fuente, ?string $cacheDir = null): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '_', pathinfo($fuente, PATHINFO_FILENAME));
    $hash = substr(md5($fuente), 0, 8);
    return itsPlanoCacheDir($cacheDir) . '/' . strtolower((string)$base) . "_{$clave}_{$hash}.json";
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
function itsPlanoCacheLeer(string $clave, string $fuente, ?string $cacheDir = null): ?array
{
    $ruta = itsPlanoCacheRuta($clave, $fuente, $cacheDir);
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
        error_log("[its_plano] Cache '{$clave}' ilegible o corrupta; se regenera desde el archivo.");
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
function itsPlanoCacheGuardar(string $clave, string $fuente, array $valores, ?string $cacheDir = null): void
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
    $ruta = itsPlanoCacheRuta($clave, $fuente, $cacheDir);
    if ($json === false || @file_put_contents($ruta, $json, LOCK_EX) === false) {
        error_log("[its_plano] No se pudo escribir la cache '{$clave}' en {$ruta}; "
            . "se seguira leyendo el xlsx en cada exportacion.");
    }
}

/**
 * Lee el archivo "Ubicacion_Filas_ITS.xlsx" y devuelve el mapa de destino de
 * cada celda del reporte, con las correcciones de typos ya aplicadas.
 *
 * Usa CACHE EN DISCO: solo parsea el xlsx la primera vez (o cuando el archivo
 * cambia); las siguientes llamadas leen el JSON cacheado.
 *
 * Solo se toman las CELDAS NUMERICAS (los numeros de destino); las etiquetas
 * de texto del espejo se ignoran. Ejemplo de salida:
 *   ['B5' => 4, 'C5' => 5, 'D5' => 6, ...]
 *
 * @param string      $path     Ruta al archivo "Ubicacion_Filas_ITS.xlsx".
 * @param string|null $cacheDir Ruta alternativa de cache; null = ITS_PLANO_CACHE_DIR.
 * @return array<string,int> Mapa [celdaOrigen => numeroDestino] corregido.
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function itsPlanoMapaUbicacion(string $path, ?string $cacheDir = null): array
{
    $mapa = itsPlanoCacheLeer('ubicacion', $path, $cacheDir);
    if ($mapa === null) {
        $lector = new ExcelTemplateReader($path);
        $mapa = [];
        foreach ($lector->readNumericCells() as $ref => $valor) {
            $mapa[$ref] = (int)$valor;
        }
        itsPlanoCacheGuardar('ubicacion', $path, $mapa, $cacheDir);
    }
    // Red de seguridad contra los typos documentados (idempotente).
    return itsPlanoAplicarCorrecciones($mapa);
}

/**
 * Lee la plantilla "ITS_Plano.xlsx" y construye el indice
 * [numeroReferencia => letraColumna] a partir de su fila de referencia
 * (A12:CBM12 con valores 1..2093).
 *
 * Usa CACHE EN DISCO (clave 'plano_fila{N}'), igual que el mapa de ubicacion.
 *
 * Ejemplo de salida: [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', ..., 2093 => 'CBM']
 * Asi, el numero 4 de "Ubicacion_Filas_ITS.xlsx" resuelve a la columna D y el
 * dato se escribe en D13 (columna destino + fila de datos).
 *
 * @param string      $path            Ruta al archivo "ITS_Plano.xlsx".
 * @param int         $filaReferencia  Fila con la numeracion (por defecto 12).
 * @param string|null $cacheDir        Ruta alternativa de cache; null = por defecto.
 * @return array<int,string> Mapa [numeroReferencia => letraColumna].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function itsPlanoIndiceDestino(string $path, int $filaReferencia = ITS_PLANO_FILA_REFERENCIA, ?string $cacheDir = null): array
{
    $clave = 'plano_fila' . $filaReferencia;
    $cache = itsPlanoCacheLeer($clave, $path, $cacheDir);
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
    itsPlanoCacheGuardar($clave, $path, $indice, $cacheDir);
    return $indice;
}

/**
 * Cruza los DATOS del reporte con el mapa de ubicacion y el indice del plano.
 *
 * Diferencia clave con Zoonosis: el espejo ITS esta compactado 8 filas hacia
 * arriba respecto de la plantilla oficial, por lo que el dato de la celda
 * espejo (columna X, fila R) NO esta en {X}{R} sino en {X}{R+8}
 * (ITS_PLANO_OFFSET_FILA) del mapa que produce itsCeldasExport().
 *
 * Por cada celda del mapa de ubicacion:
 *   - Se traduce la celda origen a coordenadas de la plantilla oficial:
 *     {columna}{fila + offset}. Ej: espejo B5 -> oficial B13.
 *   - Si el reporte produce un dato para esa celda (itsCeldasExport) y el
 *     numero de destino existe en la fila de referencia del plano, el dato se
 *     asigna a la celda destino: {letraColumna}{filaDatos}. Ej: B5 (numero 4)
 *     -> dato de B13 -> columna D -> 'D13' => dato.
 *   - Si el reporte no produce dato para esa celda (cabeceras del espejo o
 *     celdas sin numero), la celda se omite.
 *   - Si el numero de destino no existe en la fila de referencia del plano,
 *     se registra en el log y se omite (mejor esfuerzo, como el filler).
 *   - Si DOS celdas apuntan al mismo numero de destino (numeros duplicados),
 *     gana la ULTIMA en orden de lectura y se registra la colision en el log.
 *     NOTA: el duplicado 1241 de la plantilla original (H9/G131) ya fue
 *     corregido (H9=124), por lo que con la plantilla actual esta situacion
 *     no deberia ocurrir; el manejo queda como red de seguridad frente a
 *     futuras ediciones de la plantilla.
 *
 * @param array<string,int|string|float> $celdasReporte  Datos del reporte (itsCeldasExport), ej: ['B13' => 2].
 * @param array<string,int>              $mapaUbicacion  Salida de itsPlanoMapaUbicacion(), ej: ['B5' => 4].
 * @param array<int,string>              $indicePlano    Salida de itsPlanoIndiceDestino(), ej: [4 => 'D'].
 * @param int                            $filaDatos      Fila de datos del plano (por defecto 13).
 * @param int                            $offsetFila     Desplazamiento espejo -> plantilla oficial (por defecto 8).
 * @return array<string,int|string|float> Mapa [celdaPlano => valor], ej: ['D13' => 2].
 */
function itsPlanoConstruirCeldas(
    array $celdasReporte,
    array $mapaUbicacion,
    array $indicePlano,
    int $filaDatos = ITS_PLANO_FILA_DATOS,
    int $offsetFila = ITS_PLANO_OFFSET_FILA
): array {
    $celdasPlano = [];

    foreach ($mapaUbicacion as $celdaOrigen => $numero) {
        // Coordenadas de la celda espejo: letra(s) de columna + numero de fila.
        if (preg_match('/^([A-Z]+)([0-9]+)$/', $celdaOrigen, $m) !== 1) {
            continue; // referencia inesperada: omitir (no deberia ocurrir)
        }
        $columna = $m[1];
        $filaOficial = (int)$m[2] + $offsetFila; // espejo compactado -> plantilla oficial
        $celdaDato = $columna . $filaOficial;

        // La celda de la plantilla oficial debe tener dato en el reporte
        // (itsCeldasExport produce TODAS las filas T/M/F de cada layout; el
        // espejo solo numera las que el plano recibe).
        if (!array_key_exists($celdaDato, $celdasReporte)) {
            continue;
        }

        $numero = (int)$numero;
        if (!isset($indicePlano[$numero])) {
            error_log("[its_plano] Numero de destino {$numero} (celda {$celdaOrigen}) "
                . "no existe en la fila " . ITS_PLANO_FILA_REFERENCIA . " del plano; celda omitida.");
            continue;
        }

        $refDestino = $indicePlano[$numero] . $filaDatos;
        if (isset($celdasPlano[$refDestino])) {
            error_log("[its_plano] COLISION en {$refDestino}: el numero {$numero} esta asignado a "
                . "mas de una celda de 'Ubicacion_Filas_ITS.xlsx'; gana el ultimo dato ({$celdaOrigen}).");
        }
        $celdasPlano[$refDestino] = $celdasReporte[$celdaDato];
    }

    return $celdasPlano;
}

/**
 * Ejecuta el flujo completo de mapeo "Exportar Plano" a partir de los datos
 * del reporte ya calculados (itsCeldasExport).
 *
 * @param array<string,int|string|float> $celdasReporte Salida de itsCeldasExport().
 * @param string      $pathUbicacion Ruta al archivo "Ubicacion_Filas_ITS.xlsx".
 * @param string      $pathPlano     Ruta al archivo "ITS_Plano.xlsx".
 * @param string|null $cacheDir      Ruta alternativa de cache; null = ITS_PLANO_CACHE_DIR.
 * @return array<string,int|string|float> Mapa [celdaPlano => valor] para ExcelTemplateFiller.
 */
function itsPlanoExportar(array $celdasReporte, string $pathUbicacion, string $pathPlano, ?string $cacheDir = null): array
{
    $mapaUbicacion = itsPlanoMapaUbicacion($pathUbicacion, $cacheDir);
    $indicePlano   = itsPlanoIndiceDestino($pathPlano, ITS_PLANO_FILA_REFERENCIA, $cacheDir);
    return itsPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano);
}
