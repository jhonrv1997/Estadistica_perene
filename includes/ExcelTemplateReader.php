<?php
/**
 * Sistema de Gestion de Datos HIS
 * Clase para LEER celdas de un archivo Excel .xlsx existente sin PhpSpreadsheet
 * ni composer (solo ZipArchive + XML). Es el complemento inverso de
 * ExcelTemplateFiller.php: ese escribe valores, este los lee.
 *
 * Estrategia (igual que ExcelTemplateFiller):
 *   - Un archivo .xlsx es un ZIP con archivos XML internos.
 *   - Se localiza la PRIMERA hoja leyendo xl/workbook.xml +
 *     xl/_rels/workbook.xml.rels (compatible con plantillas guardadas por
 *     LibreOffice / Google Sheets que usan otros nombres de parte).
 *   - Se analizan las etiquetas <c r="D13" ...><v>288</v></c> del XML de la hoja.
 *
 * Alcance deliberado: solo se extraen CELDAS NUMERICAS (sin atributo t= o con
 * t="n"), que es lo unico que necesita el flujo "Exportar Plano" de Zoonosis:
 *   - "Ubicacion Filas.xlsx": numeros de destino (ej: D13 = 288).
 *   - "Zoonosis_Plano.xlsx": numeracion de referencia de la fila 13
 *     (A13:BPX13 = 1..1792).
 * Las celdas de texto (t="s" shared string, t="inlineStr"), booleanas, de
 * error o con formula se ignoran de forma segura.
 *
 * Uso:
 *   $lector = new ExcelTemplateReader('uploads/Ubicacion Filas.xlsx');
 *   $celdas = $lector->readNumericCells();          // ['D13' => 288, ...]
 *   $lector2 = new ExcelTemplateReader('uploads/Zoonosis_Plano.xlsx');
 *   $indice = $lector2->readRowNumbers(13);         // [288 => 'KB', ...]
 *
 * Requiere la extension zip de PHP (misma dependencia que ExcelTemplateFiller).
 */
class ExcelTemplateReader
{
    /** @var string Ruta absoluta al archivo .xlsx a leer. */
    private $xlsxPath;

    /** @var array<string,int|float>|null Cache de celdas numericas de la primera hoja. */
    private $cacheCeldas = null;

    /**
     * @param string $xlsxPath Ruta al archivo .xlsx que se va a leer.
     * @throws InvalidArgumentException si el archivo no existe o no es legible.
     */
    public function __construct(string $xlsxPath)
    {
        if (!is_readable($xlsxPath)) {
            throw new InvalidArgumentException("Archivo Excel no legible: {$xlsxPath}");
        }
        $this->xlsxPath = $xlsxPath;
    }

    /**
     * Devuelve todas las celdas NUMERICAS de la primera hoja del archivo.
     *
     * @return array<string,int|float> Mapa [cellRef => valor], ej: ['D13' => 288, 'E13' => 290].
     * @throws RuntimeException si el ZIP no se puede abrir o la hoja no se encuentra.
     */
    public function readNumericCells(): array
    {
        if ($this->cacheCeldas !== null) {
            return $this->cacheCeldas;
        }

        $zip = new ZipArchive();
        $openRes = $zip->open($this->xlsxPath);
        if ($openRes !== true) {
            throw new RuntimeException(
                'ZipArchive::open() fallo codigo: ' . $openRes .
                ' para ' . $this->xlsxPath . ' (verifique la extension zip de PHP).'
            );
        }

        try {
            $sheetPath = $this->resolveFirstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException("No se encontro {$sheetPath} dentro del xlsx: {$this->xlsxPath}");
            }
        } finally {
            $zip->close();
        }

        $this->cacheCeldas = $this->parseNumericCells($sheetXml);
        return $this->cacheCeldas;
    }

    /**
     * Devuelve el mapa [numero => letraColumna] de los valores numericos de
     * UNA fila, ej. para la fila 13 de "Zoonosis_Plano.xlsx": [288 => 'KB'].
     *
     * Si dos columnas tuvieran el mismo numero (plantilla anomala), gana la
     * PRIMERA en orden de aparicion y se registra un aviso en el log.
     *
     * @param int $fila Numero de fila (1-based), ej: 13.
     * @return array<int,string> Mapa [numeroReferencia => letraColumna].
     */
    public function readRowNumbers(int $fila): array
    {
        $indice = [];
        $patron = '/^([A-Z]+)' . (int)$fila . '$/';
        foreach ($this->readNumericCells() as $ref => $valor) {
            if (preg_match($patron, $ref, $m) !== 1) {
                continue;
            }
            $numero = (int)$valor;
            $columna = strtoupper($m[1]);
            if (isset($indice[$numero])) {
                error_log("[ExcelTemplateReader] Numero {$numero} repetido en fila {$fila} "
                    . "({$indice[$numero]} y {$columna}); se usa la primera.");
                continue;
            }
            $indice[$numero] = $columna;
        }
        return $indice;
    }

    // ============================================================
    //  Implementacion interna
    // ============================================================

    /**
     * Extrae las celdas numericas del XML de una hoja.
     *
     * Reconoce:
     *   <c r="D13" s="13"><v>288</v></c>            -> numerica (sin t=)
     *   <c r="D13" s="13" t="n"><v>288</v></c>      -> numerica explicita
     *   <c r="D13" s="13"/>                          -> vacia: ignorada
     *   <c r="B13" s="39" t="s"><v>11</v></c>       -> shared string: ignorada
     *
     * @param string $sheetXml Contenido XML de la hoja.
     * @return array<string,int|float> Mapa [cellRef => valor].
     */
    private function parseNumericCells(string $sheetXml): array
    {
        $celdas = [];

        // Recorre TODAS las celdas: auto-cerradas <c .../> y con cuerpo <c ...>...</c>.
        if (preg_match_all('/<c\b([^>]*?)(?:\/>|>(.*?)<\/c>)/s', $sheetXml, $m, PREG_SET_ORDER) === false) {
            return $celdas;
        }

        foreach ($m as $celda) {
            $attrs = $celda[1];
            $body  = isset($celda[2]) ? $celda[2] : '';

            // Referencia de celda (ej: r="D13"). Sin ella, la celda no sirve.
            if (preg_match('/\br="([A-Za-z]+[0-9]+)"/', $attrs, $mr) !== 1) {
                continue;
            }
            $ref = strtoupper($mr[1]);

            // Tipo: solo numericas (sin t=, o t="n"). Ignorar texto/bool/error/fecha ISO.
            if (preg_match('/\bt="([^"]*)"/', $attrs, $mt) === 1) {
                $tipo = strtolower($mt[1]);
                if ($tipo !== 'n') {
                    continue;
                }
            }

            // Valor: primer <v>...</v> del cuerpo (las formulas escapan < > dentro
            // de <f>, asi que el primer <v> es siempre el valor).
            if (preg_match('/<v>([^<]*)<\/v>/', $body, $mv) !== 1) {
                continue;
            }
            $raw = trim($mv[1]);
            if ($raw === '' || preg_match('/^-?\d+(\.\d+)?([eE][+-]?\d+)?$/', $raw) !== 1) {
                continue;
            }

            // Entero sin decimales -> int; en otro caso -> float.
            $celdas[$ref] = (strpos($raw, '.') !== false || stripos($raw, 'e') !== false)
                ? (float)$raw
                : (int)$raw;
        }

        return $celdas;
    }

    /**
     * Devuelve la ruta interna (dentro del ZIP) del XML de la primera hoja.
     *
     * Misma logica que ExcelTemplateFiller::resolveFirstSheetPath(): lee
     * xl/workbook.xml -> primer <sheet r:id="..."> y resuelve el target en
     * xl/_rels/workbook.xml.rels; fallback a 'xl/worksheets/sheet1.xml'.
     *
     * @param ZipArchive $zip ZIP del xlsx ya abierto.
     * @return string Ruta interna, p.ej. "xl/worksheets/sheet1.xml".
     */
    private function resolveFirstSheetPath(ZipArchive $zip): string
    {
        $fallback = 'xl/worksheets/sheet1.xml';

        try {
            $wbXml = $zip->getFromName('xl/workbook.xml');
            if ($wbXml === false) {
                return $fallback;
            }
            if (!preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $wbXml, $mSheet)) {
                return $fallback;
            }
            $relId = $mSheet[1];

            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml === false) {
                return $fallback;
            }
            $relEsc = preg_quote($relId, '/');
            $found  = null;
            if (preg_match_all('/<Relationship\b[^>]*>/', $relsXml, $allRels)) {
                foreach ($allRels[0] as $relTag) {
                    $isId   = preg_match('/\bId="' . $relEsc . '"/', $relTag) === 1;
                    $isType = preg_match('/\bType="[^"]*\/worksheet"/', $relTag) === 1;
                    if ($isId && $isType) {
                        if (preg_match('/\bTarget="([^"]+)"/', $relTag, $mTarget)) {
                            $found = $mTarget[1];
                        }
                        break;
                    }
                }
            }
            if ($found === null) {
                return $fallback;
            }
            $target = $found;

            if (strpos($target, '/') === 0) {
                $path = ltrim($target, '/');
            } else {
                $path = 'xl/' . ltrim($target, '/');
            }

            if ($zip->locateName($path) === false) {
                return $fallback;
            }
            return $path;
        } catch (Throwable $e) {
            return $fallback;
        }
    }

    // ============================================================
    //  Utilidades estaticas
    // ============================================================

    /**
     * Convierte un indice de columna (1-based) a letra Excel.
     * Ej: 1 -> 'A', 28 -> 'AB', 288 -> 'KB', 1792 -> 'BPX'.
     *
     * @param int $indice Indice 1-based de la columna.
     * @return string Letra(s) de columna.
     */
    public static function colIndexToLetter(int $indice): string
    {
        $letra = '';
        while ($indice > 0) {
            $modulo = ($indice - 1) % 26;
            $letra = chr(65 + $modulo) . $letra;
            $indice = intdiv($indice - $modulo, 26);
        }
        return $letra;
    }

    /**
     * Convierte una letra de columna Excel a indice 1-based.
     * Ej: 'A' -> 1, 'KB' -> 288, 'BPX' -> 1792.
     *
     * @param string $letra Letra(s) de columna.
     * @return int Indice 1-based.
     */
    public static function colLetterToIndex(string $letra): int
    {
        $indice = 0;
        $letra = strtoupper(preg_replace('/[^A-Za-z]/', '', $letra));
        $largo = strlen($letra);
        for ($i = 0; $i < $largo; $i++) {
            $indice = $indice * 26 + (ord($letra[$i]) - 64);
        }
        return $indice;
    }
}
