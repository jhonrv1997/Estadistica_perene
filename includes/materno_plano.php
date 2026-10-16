<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo MATERNO - Logica de mapeo para la plantilla consolidada
 * "Materno_Plano.xlsx" (NUEVA FUNCIONALIDAD "Exportar Plano").
 *
 * ANTECEDENTE:
 *   materno_export.php llena la plantilla oficial "Reporte_Actividades_Materno.xlsx"
 *   mediante maternoCeldasExport() (includes/materno_render.php), que produce el
 *   mapa [celda => valor] de TODOS los datos del reporte, ej: ['C16' => 5].
 *
 * NUEVA FUNCIONALIDAD (mapeo de 3 archivos):
 *
 *   1) REFERENCIA DE UBICACION - archivo "Ubicacion_Filas_Materno.xlsx":
 *      Es un espejo del reporte oficial donde cada celda de datos contiene un
 *      NUMERO DE DESTINO. Ejemplo: la celda C16 (Gestante Atendida, I Trim,
 *      <12 a.) tiene como valor 4. El espejo trae 513 numeros (4..516, sin
 *      duplicados ni faltantes); los numeros 1, 2 y 3 son por diseno las
 *      columnas de identificacion del plano (Codigo_Unico / RENAES /
 *      Establecimiento) y no vienen del espejo.
 *
 *   2) REFERENCIA DEL DATO - "Reporte_Actividades_Materno.xlsx":
 *      La celda equivalente de la plantilla oficial (MISMA letra de columna y
 *      MISMA fila) contiene el DATO calculado por el motor (maternoCeldasExport).
 *      Ejemplo: C16 = 5. A diferencia de ITS (espejo compactado, offset +8),
 *      aqui el espejo calza FILA POR FILA con la plantilla oficial:
 *        I     16-20  | II/III 25-29 | IV 33-47 | V 33-39 | VI/VII 53-57
 *        VIII  50-51  | IX-1 64-68   | IX-2 75-79 | X 85-89
 *      => MATERNO_PLANO_OFFSET_FILA = 0.
 *      Excepcion de columnas: la plantilla oficial NO numera la columna B de
 *      la seccion I (Total de Gestante Atendida, columna calculada =SUM(C:E)),
 *      que el plano tampoco recibe (su primer bloque es I TRIMESTRE).
 *
 *   3) REFERENCIA DEL DESTINO - archivo "Materno_Plano.xlsx":
 *      En la fila 12, las celdas A12 hasta SV12 contienen la numeracion de
 *      columnas (1..516, sin duplicados ni faltantes). Se localiza la celda
 *      cuyo numero coincide con el numero de destino: con 4 la celda
 *      correspondiente es D12.
 *      POR LO TANTO el dato 5 de C16 se rellena en la celda D13.
 *      Para todas las demas celdas se sigue la misma logica: el plano es un
 *      layout horizontal donde cada celda de datos del reporte ocupa su propia
 *      columna (1=A Codigo_Unico, 2=B Codigo RENAES, 3=C Establecimiento) y la
 *      fila de datos es la 13 (encima quedan las cabeceras 1-11 y la fila de
 *      referencia 12).
 *
 * AJUSTES DE COLUMNA (diferencias puntuales espejo vs plantilla oficial):
 *   Verificados celda a celda contra la plantilla oficial, el espejo calza
 *   columna a columna en TODAS las secciones excepto dos:
 *
 *     - V. MORBILIDAD DEL RN: el espejo numera la columna P (P32 = 'N°',
 *       P33..P39 = 289..295) y la plantilla oficial tiene el 'Nº' en O
 *       (O32 = 'Nº', datos O33..O39, xmap colTotal='O')  => delta -1.
 *
 *     - VIII. VISITA DOMICILIARIA: el espejo numera la columna T
 *       (T50 = 361, T51 = 362) y la plantilla oficial escribe en U
 *       (xmap colTotal='U', U50/U51)  => delta +1.
 *
 *   Los ajustes se aplican POR NUMERO DE DESTINO (no por letra de columna),
 *   de modo que siguen siendo correctos aunque el espejo se reemplace por una
 *   copia con las letras desplazadas: el numero identifica la zona del plano.
 *
 * CELDAS SIN ZONA EN LA PLANTILLA OFICIAL (IX-3):
 *   La seccion "IX. 1° PRUEBA RAPIDA PARA VIH EN TRABAJO DE PARTO / ABORTO"
 *   no tiene zona en la plantilla oficial (xmap null en maternoSecciones), pero
 *   el plano SI la recibe: los numeros 498-501 (TAMIZAJE VIH EN PARTO, columnas
 *   individuales sin desglose etareo). maternoPlanoCeldasIX3() toma esos 4
 *   valores de los TOTALES de las columnas 1-4 de RPT09_3_TRANSMISION_VERTICAL.
 *
 * CACHE DEL MAPEO (mejora de rendimiento):
 *   El mapa de ubicaciones y el indice del plano solo dependen de 2 archivos
 *   estaticos, por lo que se cachean en JSON dentro de cache/plano/ (misma
 *   carpeta que ITS/Zoonosis: el nombre del JSON incluye el nombre del xlsx
 *   fuente y su hash, por lo que no hay colisiones entre modulos). La cache se
 *   regenera automaticamente si cambia alguno de los xlsx (mtime o size
 *   distintos), de modo que reemplazar una plantilla nunca produce datos viejos.
 *
 * Funciones PURAS (sin acceso a BD ni sesion) para poder probarlas de forma
 * aislada. Requiere includes/ExcelTemplateReader.php.
 */

require_once __DIR__ . '/ExcelTemplateReader.php';

/** Fila de "Materno_Plano.xlsx" que contiene los numeros de referencia (A12:SV12). */
const MATERNO_PLANO_FILA_REFERENCIA = 12;

/** Fila de "Materno_Plano.xlsx" donde se escribe la data (ej: D13). */
const MATERNO_PLANO_FILA_DATOS = 13;

/**
 * Desplazamiento del espejo "Ubicacion_Filas_Materno.xlsx" respecto de la
 * plantilla oficial "Reporte_Actividades_Materno.xlsx": aqui el espejo calza
 * fila por fila (I: 16-20, II/III: 25-29, IV/V: 33-47, VI/VII: 53-57,
 * VIII: 50-51, IX: 64-68/75-79, X: 85-89), por lo que el offset es 0.
 * Se mantiene como constante para documentar la diferencia con ITS (offset 8)
 * y por si una futura version del espejo viniera compactada.
 */
const MATERNO_PLANO_OFFSET_FILA = 0;

// ==================== AJUSTES DE COLUMNA (espejo vs oficial) ====================
/**
 * Diferencias de columna entre el espejo "Ubicacion_Filas_Materno.xlsx" y la
 * plantilla oficial, identificadas comparando celda a celda ambas plantillas.
 *
 * Se expresan por RANGO DE NUMERO DE DESTINO para que el ajuste dependa de la
 * zona del plano (identificada sin ambiguedad por el numero) y no de la letra
 * de columna de la copia del espejo en uso:
 *
 *   - 289..295 (V. MORBILIDAD DEL RN, columna unica 'N°'): el espejo la numera
 *     en P y la plantilla oficial la escribe en O (colTotal='O')  => delta -1.
 *   - 361..362 (VIII. VISITA DOMICILIARIA, 'A Gestante'/'A Puérpera'): el
 *     espejo las numera en T y la oficial escribe en U (colTotal='U') => +1.
 *
 * @return array<int,array{desde:int,hasta:int,delta:int,motivo:string}>
 */
function maternoPlanoAjustesColumna(): array
{
    return [
        [
            'desde'  => 289,
            'hasta'  => 295,
            'delta'  => -1,
            'motivo' => 'V. MORBILIDAD DEL RN: espejo numera en P, plantilla oficial escribe en O (colTotal).',
        ],
        [
            'desde'  => 361,
            'hasta'  => 362,
            'delta'  => 1,
            'motivo' => 'VIII. VISITA DOMICILIARIA: espejo numera en T, plantilla oficial escribe en U (colTotal).',
        ],
    ];
}

/**
 * Devuelve el desplazamiento de columna que corresponde aplicar a un numero de
 * destino concreto (0 si ninguno).
 *
 * @param int $numero Numero de destino (valor de una celda del espejo).
 * @return int Delta de columna (-1, 0 o +1 con los ajustes vigentes).
 */
function maternoPlanoDeltaColumna(int $numero): int
{
    foreach (maternoPlanoAjustesColumna() as $ajuste) {
        if ($numero >= $ajuste['desde'] && $numero <= $ajuste['hasta']) {
            return (int)$ajuste['delta'];
        }
    }
    return 0;
}

// ==================== CACHE EN DISCO DEL MAPEO ====================
// Ubicacion por defecto de la cache: <raiz del sistema>/cache/plano/ (la misma
// carpeta que los planos de ITS/Zoonosis; el nombre del JSON incluye el nombre
// del xlsx fuente y su hash, por lo que no hay colisiones entre modulos).
// La carpeta se crea automaticamente al primer uso. Para ubicarla en otra
// ruta (p.ej. un disco temporal), defina la constante ANTES de incluir este
// archivo:
//   define('MATERNO_PLANO_CACHE_DIR', '/tmp/materno_plano_cache');
if (!defined('MATERNO_PLANO_CACHE_DIR')) {
    define('MATERNO_PLANO_CACHE_DIR', dirname(__DIR__) . '/cache/plano');
}

/**
 * Devuelve el directorio de cache, creandolo si no existe (mejor esfuerzo).
 *
 * @param string|null $cacheDir Ruta alternativa; null = MATERNO_PLANO_CACHE_DIR.
 * @return string Ruta absoluta del directorio de cache.
 */
function maternoPlanoCacheDir(?string $cacheDir = null): string
{
    $dir = $cacheDir !== null && $cacheDir !== '' ? $cacheDir : MATERNO_PLANO_CACHE_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Ruta del archivo de cache para una clave y archivo fuente concretas.
 * El nombre incluye un hash de la ruta fuente para evitar colisiones, p.ej.:
 * ubicacion_filas_materno_ubicacion_3f2a91bc.json
 */
function maternoPlanoCacheRuta(string $clave, string $fuente, ?string $cacheDir = null): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '_', pathinfo($fuente, PATHINFO_FILENAME));
    $hash = substr(md5($fuente), 0, 8);
    return maternoPlanoCacheDir($cacheDir) . '/' . strtolower((string)$base) . "_{$clave}_{$hash}.json";
}

/**
 * Lee el cache de un mapeo si sigue VIGENTE (mtime y size del xlsx fuente
 * coinciden). Si el xlsx se reemplaza o edita, la cache queda invalidada.
 *
 * @return array<string,int>|array<int,string>|null Valores cacheados o null.
 */
function maternoPlanoCacheLeer(string $clave, string $fuente, ?string $cacheDir = null): ?array
{
    $ruta = maternoPlanoCacheRuta($clave, $fuente, $cacheDir);
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
        error_log("[materno_plano] Cache '{$clave}' ilegible o corrupta; se regenera desde el archivo.");
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
function maternoPlanoCacheGuardar(string $clave, string $fuente, array $valores, ?string $cacheDir = null): void
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
    $ruta = maternoPlanoCacheRuta($clave, $fuente, $cacheDir);
    if ($json === false || @file_put_contents($ruta, $json, LOCK_EX) === false) {
        error_log("[materno_plano] No se pudo escribir la cache '{$clave}' en {$ruta}; "
            . "se seguira leyendo el xlsx en cada exportacion.");
    }
}

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
/**
 * Lee el archivo "Ubicacion_Filas_Materno.xlsx" y devuelve el mapa de destino
 * de cada celda del reporte.
 *
 * Usa CACHE EN DISCO: solo parsea el xlsx la primera vez (o cuando el archivo
 * cambia); las siguientes llamadas leen el JSON cacheado.
 *
 * Solo se toman las CELDAS NUMERICAS (los numeros de destino); las etiquetas
 * de texto del espejo se ignoran. Ejemplo de salida:
 *   ['C16' => 4, 'D16' => 9, 'E16' => 14, ...]
 *
 * Nota: la plantilla de Materno no trae typos documentados (513 numeros unicos,
 * 4..516 sin duplicados ni faltantes), por lo que NO se aplican correcciones
 * (a diferencia de its_plano.php, que corrige el typo H9=1241 -> 124).
 *
 * @param string      $path     Ruta al archivo "Ubicacion_Filas_Materno.xlsx".
 * @param string|null $cacheDir Ruta alternativa de cache; null = por defecto.
 * @return array<string,int> Mapa [celdaOrigen => numeroDestino].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function maternoPlanoMapaUbicacion(string $path, ?string $cacheDir = null): array
{
    $mapa = maternoPlanoCacheLeer('ubicacion', $path, $cacheDir);
    if ($mapa === null) {
        $lector = new ExcelTemplateReader($path);
        $mapa = [];
        foreach ($lector->readNumericCells() as $ref => $valor) {
            $mapa[$ref] = (int)$valor;
        }
        maternoPlanoCacheGuardar('ubicacion', $path, $mapa, $cacheDir);
    }
    return $mapa;
}

/**
 * Lee la plantilla "Materno_Plano.xlsx" y construye el indice
 * [numeroReferencia => letraColumna] a partir de su fila de referencia
 * (A12:SV12 con valores 1..516).
 *
 * Usa CACHE EN DISCO (clave 'plano_fila{N}'), igual que el mapa de ubicacion.
 *
 * Ejemplo de salida: [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', ..., 516 => 'SV']
 * Asi, el numero 4 de "Ubicacion_Filas_Materno.xlsx" resuelve a la columna D y
 * el dato se escribe en D13 (columna destino + fila de datos).
 *
 * @param string      $path            Ruta al archivo "Materno_Plano.xlsx".
 * @param int         $filaReferencia  Fila con la numeracion (por defecto 12).
 * @param string|null $cacheDir        Ruta alternativa de cache; null = por defecto.
 * @return array<int,string> Mapa [numeroReferencia => letraColumna].
 * @throws RuntimeException|InvalidArgumentException si el archivo no se puede leer.
 */
function maternoPlanoIndiceDestino(string $path, int $filaReferencia = MATERNO_PLANO_FILA_REFERENCIA, ?string $cacheDir = null): array
{
    $clave = 'plano_fila' . $filaReferencia;
    $cache = maternoPlanoCacheLeer($clave, $path, $cacheDir);
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
    maternoPlanoCacheGuardar($clave, $path, $indice, $cacheDir);
    return $indice;
}

/**
 * Cruza los DATOS del reporte con el mapa de ubicacion y el indice del plano.
 *
 * A diferencia de ITS, aqui el espejo calza fila por fila con la plantilla
 * oficial (MATERNO_PLANO_OFFSET_FILA = 0): la celda espejo {col}{fila} contiene
 * el numero de destino y el dato esta en la MISMA celda {col}{fila} del mapa
 * que produce maternoCeldasExport(), salvo los AJUSTES DE COLUMNA puntuales de
 * las secciones V (-1) y VIII (+1), que se aplican por numero de destino
 * (maternoPlanoDeltaColumna).
 *
 * Por cada celda del mapa de ubicacion:
 *   - Se traduce la celda origen a coordenadas de la plantilla oficial:
 *     {columna + delta(numero)}{fila + offset}. Ej: espejo C16 (numero 4,
 *     sin delta) -> oficial C16; espejo P33 (numero 289, delta -1) -> O33;
 *     espejo T50 (numero 361, delta +1) -> U50.
 *   - Si el reporte produce un dato para esa celda (maternoCeldasExport) y el
 *     numero de destino existe en la fila de referencia del plano, el dato se
 *     asigna a la celda destino: {letraColumna}{filaDatos}. Ej: C16 (numero 4)
 *     -> dato de C16 -> columna D -> 'D13' => dato.
 *   - Si el reporte no produce dato para esa celda, la celda se omite de forma
 *     segura (mejor esfuerzo).
 *   - Si el numero de destino no existe en la fila de referencia del plano,
 *     se registra en el log y se omite.
 *   - Si DOS celdas apuntan al mismo numero de destino, gana la ULTIMA en
 *     orden de lectura y se registra la colision en el log (con la plantilla
 *     actual, 513 numeros unicos, esta situacion no deberia ocurrir; el
 *     manejo queda como red de seguridad frente a futuras ediciones).
 *
 * @param array<string,int|string|float> $celdasReporte  Datos del reporte (maternoCeldasExport), ej: ['C16' => 5].
 * @param array<string,int>              $mapaUbicacion  Salida de maternoPlanoMapaUbicacion(), ej: ['C16' => 4].
 * @param array<int,string>              $indicePlano    Salida de maternoPlanoIndiceDestino(), ej: [4 => 'D'].
 * @param int                            $filaDatos      Fila de datos del plano (por defecto 13).
 * @param int                            $offsetFila     Desplazamiento espejo -> plantilla oficial (por defecto 0).
 * @return array<string,int|string|float> Mapa [celdaPlano => valor], ej: ['D13' => 5].
 */
function maternoPlanoConstruirCeldas(
    array $celdasReporte,
    array $mapaUbicacion,
    array $indicePlano,
    int $filaDatos = MATERNO_PLANO_FILA_DATOS,
    int $offsetFila = MATERNO_PLANO_OFFSET_FILA
): array {
    $celdasPlano = [];

    foreach ($mapaUbicacion as $celdaOrigen => $numero) {
        // Coordenadas de la celda espejo: letra(s) de columna + numero de fila.
        if (preg_match('/^([A-Z]+)([0-9]+)$/', $celdaOrigen, $m) !== 1) {
            continue; // referencia inesperada: omitir (no deberia ocurrir)
        }
        $numero = (int)$numero;

        // Ajuste de columna por numero de destino (V: -1, VIII: +1, resto: 0).
        $colIdx = ExcelTemplateReader::colLetterToIndex($m[1]) + maternoPlanoDeltaColumna($numero);
        $filaOficial = (int)$m[2] + $offsetFila; // espejo calza con la oficial (offset 0)
        $celdaDato = ExcelTemplateReader::colIndexToLetter($colIdx) . $filaOficial;

        // La celda de la plantilla oficial debe tener dato en el reporte
        // (maternoCeldasExport). Ej: la columna B de la seccion I (Total de
        // Gestante Atendida, calculada) no es numerada por el espejo y no
        // llega al plano.
        if (!array_key_exists($celdaDato, $celdasReporte)) {
            continue;
        }

        if (!isset($indicePlano[$numero])) {
            error_log("[materno_plano] Numero de destino {$numero} (celda {$celdaOrigen}) "
                . "no existe en la fila " . MATERNO_PLANO_FILA_REFERENCIA . " del plano; celda omitida.");
            continue;
        }

        $refDestino = $indicePlano[$numero] . $filaDatos;
        if (isset($celdasPlano[$refDestino])) {
            error_log("[materno_plano] COLISION en {$refDestino}: el numero {$numero} esta asignado a "
                . "mas de una celda de 'Ubicacion_Filas_Materno.xlsx'; gana el ultimo dato ({$celdaOrigen}).");
        }
        $celdasPlano[$refDestino] = $celdasReporte[$celdaDato];
    }

    return $celdasPlano;
}

/**
 * Añade al plano las 4 celdas de la seccion IX-3 ("1° PRUEBA RAPIDA PARA VIH
 * EN TRABAJO DE PARTO / ABORTO"), que NO tienen zona en la plantilla oficial
 * (xmap null) pero SI en el plano consolidado (bloque "TAMIZAJE VIH EN PARTO",
 * columnas individuales sin desglose etareo):
 *
 *   498 = 1° PR VIH EN PARTO                  <- RPT09_3 columna key 1 (total)
 *   499 = 1 PR VIH EN PARTO REACTIVO          <- RPT09_3 columna key 2 (total)
 *   500 = 1° PRUEBA RAPIDA PARA VIH ABORTO    <- RPT09_3 columna key 3 (total)
 *   501 = 1° PRUEBA RAPIDA VIH ABORTO REACTIVO<- RPT09_3 columna key 4 (total)
 *
 * Es tolerante a fallos: si la seccion/columna no existe en el reporte o el
 * numero no esta en la fila de referencia del plano, la celda se omite con un
 * registro en el log (mismo criterio "mejor esfuerzo" del resto del flujo).
 *
 * @param array              $reporte     Reporte ejecutado (maternoEjecutarReporte).
 * @param array<int,string>  $indicePlano Salida de maternoPlanoIndiceDestino().
 * @param int                $filaDatos   Fila de datos del plano (por defecto 13).
 * @return array<string,int> Celdas [celdaPlano => valor] para fusionar con el mapa principal.
 */
function maternoPlanoCeldasIX3(array $reporte, array $indicePlano, int $filaDatos = MATERNO_PLANO_FILA_DATOS): array
{
    // numero de destino en el plano => key de la columna en RPT09_3.
    $mapaIX3 = [
        498 => 1, // 1° PRUEBA RAPIDA PARA VIH EN TRABAJO DE PARTO
        499 => 2, // 1° PRUEBA RAPIDA PARA VIH EN TRABAJO DE PARTO REACTIVO
        500 => 3, // 1° PRUEBA RAPIDA PARA VIH EN ABORTO
        501 => 4, // 1° PRUEBA RAPIDA VIH EN ABORTO REACTIVO
    ];

    // Localizar la seccion IX-3 (es la unica con xmap null en maternoSecciones;
    // se busca por codigo y, como red de seguridad, por xmap null).
    $seccionIX3 = null;
    foreach (($reporte['secciones'] ?? []) as $sec) {
        if (($sec['codigo'] ?? '') === 'RPT09_3_TRANSMISION_VERTICAL' || $sec['xmap'] === null) {
            $seccionIX3 = $sec;
            if (($sec['codigo'] ?? '') === 'RPT09_3_TRANSMISION_VERTICAL') {
                break; // coincidencia exacta: no seguir buscando
            }
        }
    }
    if ($seccionIX3 === null) {
        error_log("[materno_plano] Seccion RPT09_3_TRANSMISION_VERTICAL no encontrada en el reporte; "
            . "las celdas 498-501 del plano (TAMIZAJE VIH EN PARTO) quedan vacias.");
        return [];
    }

    $columnasPorKey = [];
    foreach (($seccionIX3['columnas'] ?? []) as $col) {
        $columnasPorKey[(int)($col['key'] ?? 0)] = $col;
    }

    $celdas = [];
    foreach ($mapaIX3 as $numero => $key) {
        if (!isset($indicePlano[$numero])) {
            error_log("[materno_plano] Numero de destino {$numero} (IX-3) no existe en la fila "
                . MATERNO_PLANO_FILA_REFERENCIA . " del plano; celda omitida.");
            continue;
        }
        if (!isset($columnasPorKey[$key])) {
            error_log("[materno_plano] La seccion IX-3 no tiene columna key={$key}; "
                . "celda del plano numero {$numero} omitida.");
            continue;
        }
        $refDestino = $indicePlano[$numero] . $filaDatos;
        $celdas[$refDestino] = (int)($columnasPorKey[$key]['total'] ?? 0);
    }
    return $celdas;
}

/**
 * Ejecuta el flujo completo de mapeo "Exportar Plano" a partir de los datos
 * del reporte ya calculados (maternoCeldasExport).
 *
 * @param array<string,int|string|float> $celdasReporte Salida de maternoCeldasExport().
 * @param string      $pathUbicacion Ruta al archivo "Ubicacion_Filas_Materno.xlsx".
 * @param string      $pathPlano     Ruta al archivo "Materno_Plano.xlsx".
 * @param string|null $cacheDir      Ruta alternativa de cache; null = por defecto.
 * @return array<string,int|string|float> Mapa [celdaPlano => valor] para ExcelTemplateFiller.
 */
function maternoPlanoExportar(array $celdasReporte, string $pathUbicacion, string $pathPlano, ?string $cacheDir = null): array
{
    $mapaUbicacion = maternoPlanoMapaUbicacion($pathUbicacion, $cacheDir);
    $indicePlano   = maternoPlanoIndiceDestino($pathPlano, MATERNO_PLANO_FILA_REFERENCIA, $cacheDir);
    return maternoPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano);
}
