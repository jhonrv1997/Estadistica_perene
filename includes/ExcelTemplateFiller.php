<?php
/**
 * Sistema de Gestion de Datos HIS
 * Clase para llenar una plantilla Excel .xlsx existente con valores en celdas
 * especificas, preservando TODO el formato original (merged cells, estilos,
 * anchos de columna, altos de fila, etc.).
 *
 * Estrategia:
 *   - Un archivo .xlsx es un ZIP con archivos XML internos.
 *   - Para modificar valores de celdas, basta con editar el archivo
 *     xl/worksheets/sheetN.xml dentro del ZIP.
 *   - Las celdas vacias tienen la forma <c r="G7" s="4"/>. Se reemplazan
 *     por <c r="G7" s="4"><v>123</v></c> (numericos) o
 *     <c r="C2" s="121" t="inlineStr"><is><t>Texto</t></is></c> (texto).
 *   - Se usa t="inlineStr" para los textos, evitando tener que modificar
 *     sharedStrings.xml (mas simple y robusto).
 *
 * No requiere PhpSpreadsheet ni composer. Solo ZipArchive (incluido en PHP).
 *
 * FIX v2 (2026-09-10) — CAUSA RAIZ del "archivo se dana al exportar":
 *   - BUG CRITICO corregido en buildCellXml(): cuando la plantilla tiene una
 *     celda de TEXTO, Excel la guarda como:
 *         <c r="G7" s="4" t="s"><v>5</v></c>
 *     donde t="s" significa "shared string" y <v>5</v> es el INDICE dentro de
 *     xl/sharedStrings.xml. Al escribir un NUMERO, la version anterior
 *     conservaba el atributo t="s" heredado y generaba:
 *         <c r="G7" s="4" t="s"><v>42</v></c>
 *     Excel interpreta 42 como INDICE de sharedStrings.xml; como la plantilla
 *     tiene pocas strings (indices 0..N-1), el indice no existe y el contenido
 *     es INVALIDO -> Excel muestra "el archivo esta dano" / cuadro de
 *     reparacion. Solucion: eliminar SIEMPRE el atributo t="..." heredado de
 *     la plantilla antes de escribir el nuevo valor, y escribir el tipo
 *     correcto (nada para numeros, t="inlineStr" para texto).
 *   - Resolucion de la hoja ya no asume sheet1.xml: se lee xl/workbook.xml +
 *     xl/_rels/workbook.xml.rels para localizar la PRIMERA hoja (compatible
 *     con plantillas guardadas por LibreOffice/Google Sheets que usan otros
 *     nombres de parte).
 *   - download(): se desactiva/neutraliza zlib.output_compression (hostings
 *     compartidos) para no truncar el binario, y se verifica headers_sent()
 *     para reportar con claridad si algo escribio salida antes de la descarga
 *     (echo, warning, BOM de archivo PHP) — eso tambien corrompe el xlsx.
 *
 * Uso:
 *   $filler = new ExcelTemplateFiller('uploads/Plantilla.xlsx');
 *   $filler->setCellValue('C2', 'Mi Establecimiento');
 *   $filler->setCellValue('G7', 42);          // numerico
 *   $filler->setCellValue('J7', 42);          // numerico
 *   $filler->download('Reporte_ESNI.xlsx');
 *
 * O guardar en disco:
 *   $filler->save('/ruta/salida.xlsx');
 */

class ExcelTemplateFiller
{
    /** @var string Ruta absoluta a la plantilla .xlsx original. */
    private $templatePath;

    /** @var array<string,int|string|float> Mapa [cellRef => value]. */
    private $cellValues = [];

    /**
     * @param string $templatePath Ruta a la plantilla .xlsx que se va a llenar.
     */
    public function __construct(string $templatePath)
    {
        if (!is_readable($templatePath)) {
            throw new InvalidArgumentException("Plantilla no legible: {$templatePath}");
        }
        $this->templatePath = $templatePath;
    }

    /**
     * Asigna el valor de una celda.
     *
     * @param string                $cellRef Referencia de celda tipo "G7", "C2".
     * @param int|string|float|null $value   Valor numerico o texto. Null limpia la celda.
     */
    public function setCellValue(string $cellRef, $value): void
    {
        if ($value === null || $value === '') {
            // No tocar la celda (se queda vacia como en la plantilla).
            return;
        }
        $this->cellValues[strtoupper(trim($cellRef))] = $value;
    }

    /**
     * Asigna varios valores de una sola vez.
     *
     * @param array<string,int|string|float> $values Mapa [cellRef => value].
     */
    public function setCellValues(array $values): void
    {
        foreach ($values as $ref => $val) {
            $this->setCellValue((string)$ref, $val);
        }
    }

    /**
     * Genera el .xlsx final y lo envia al navegador como descarga.
     *
     * @param string $filename Nombre del archivo a descargar.
     */
    public function download(string $filename): void
    {
        $tmpFile = $this->buildTempFile();

        // Limpiar cualquier buffer de salida previo (HTML, warnings, etc.)
        // que corromperia el contenido binario del xlsx.
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        // Si algo ya envio bytes al cliente (echo, warning mostrado, BOM de
        // un archivo PHP incluido antes), la descarga binaria saldra danoada.
        // Reportarlo con claridad en lugar de producir un archivo corrupto.
        if (headers_sent($sentFile, $sentLine)) {
            @unlink($tmpFile);
            throw new RuntimeException(
                'No se puede iniciar la descarga: ya hubo salida en ' . $sentFile . ':' . $sentLine .
                '. Elimine echo/warnings/BOM antes de llamar download().'
            );
        }

        // La compresion de salida del hosting (zlib.output_compression)
        // re-comprime el binario y rompe Content-Length -> archivo truncado.
        if (function_exists('ini_set')) {
            @ini_set('zlib.output_compression', '0');
        }
        $zlibOn = in_array(strtolower((string)@ini_get('zlib.output_compression')), ['1', 'on', 'true'], true);

        // Headers para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        if (!$zlibOn) {
            // Solo enviar Content-Length si el hosting no va a recomprimir.
            header('Content-Length: ' . filesize($tmpFile));
        }
        header('Cache-Control: max-age=0, no-store');
        header('Pragma: public');
        header('Expires: 0');

        // Enviar contenido binario. readfile() es la opcion mas compatible
        // con hostings que restringen fpassthru().
        $sent = @readfile($tmpFile);
        if ($sent === false) {
            // Fallback con fpassthru
            $fp = @fopen($tmpFile, 'rb');
            if ($fp) {
                @fpassthru($fp);
                @fclose($fp);
            }
        }

        // Limpieza del temporal
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }

    /**
     * Genera el .xlsx final y lo guarda en una ruta del servidor.
     *
     * @param string $outputPath Ruta absoluta donde guardar el archivo.
     * @return bool True si se guardo correctamente.
     */
    public function save(string $outputPath): bool
    {
        $tmpFile = $this->buildTempFile();
        $ok = copy($tmpFile, $outputPath);
        @unlink($tmpFile);
        return $ok;
    }

    // ============================================================
    //  Implementacion interna
    // ============================================================

    /**
     * Construye el archivo .xlsx final en un archivo temporal.
     *
     * Estrategia para directorio temporal:
     *   1) Usar uploads/tmp/ del proyecto (creandolo si hace falta). Esto es
     *      indispensable en hostings compartidos gratuitos (InfinityFree,
     *      000webhost, etc.) donde sys_get_temp_dir() suele devolver /tmp
     *      que NO es escribible por el proceso PHP.
     *   2) Si uploads/tmp/ no se puede crear/escribir, intentar con
     *      sys_get_temp_dir() como fallback.
     *
     * @return string Ruta del archivo temporal creado.
     */
    private function buildTempFile(): string
    {
        // Resolver un directorio temporal escribible.
        $tmpDir = $this->resolveTempDir();

        // Crear archivo temporal con nombre unico en ese directorio.
        // tempnam() crea el archivo vacio y devuelve la ruta.
        $tmpFile = @tempnam($tmpDir, 'xlsx_tpl_');
        if ($tmpFile === false) {
            // Fallback: construir nombre manualmente
            $tmpFile = $tmpDir . '/xlsx_tpl_' . uniqid('', true) . '.xlsx';
            if (@touch($tmpFile) === false) {
                throw new RuntimeException(
                    "No se pudo crear archivo temporal en {$tmpDir}. " .
                    "Verifique permisos de escritura."
                );
            }
        }

        // Copiar el contenido de la plantilla al archivo temporal.
        if (@copy($this->templatePath, $tmpFile) === false) {
            @unlink($tmpFile);
            throw new RuntimeException(
                "No se pudo copiar la plantilla ({$this->templatePath}) al temporal ({$tmpFile}). " .
                "Verifique permisos de lectura de la plantilla y escritura del temporal."
            );
        }

        // Abrir el ZIP y modificar el XML de la hoja.
        $zip = new ZipArchive();
        $openRes = $zip->open($tmpFile);
        if ($openRes !== true) {
            @unlink($tmpFile);
            throw new RuntimeException(
                'ZipArchive::open() fallo codigo: ' . $openRes .
                ' (verifique que la extension zip de PHP este cargada).'
            );
        }

        try {
            // Localizar la PRIMERA hoja del workbook leyendo
            // xl/workbook.xml + xl/_rels/workbook.xml.rels (en vez de asumir
            // sheet1.xml: LibreOffice/Google Sheets usan otros nombres).
            $sheetPath = $this->resolveFirstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                $zip->close();
                @unlink($tmpFile);
                throw new RuntimeException("No se encontro {$sheetPath} dentro del xlsx.");
            }

            $newSheetXml = $this->applyCellValues($sheetXml);

            // Sobrescribir la hoja dentro del ZIP.
            if ($zip->addFromString($sheetPath, $newSheetXml) === false) {
                $zip->close();
                @unlink($tmpFile);
                throw new RuntimeException("No se pudo escribir {$sheetPath} en el xlsx.");
            }
        } finally {
            $zip->close();
        }

        return $tmpFile;
    }

    /**
     * Devuelve la ruta interna (dentro del ZIP) del XML de la primera hoja.
     *
     * Lee xl/workbook.xml -> primer <sheet r:id="..."> y resuelve el target
     * real en xl/_rels/workbook.xml.rels. Si algo falla, hace fallback a
     * 'xl/worksheets/sheet1.xml' (nombre estandar que usa MS Excel).
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
            // Primer <sheet .../> del workbook: r:id="rIdN"
            if (!preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $wbXml, $mSheet)) {
                return $fallback;
            }
            $relId = $mSheet[1];

            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml === false) {
                return $fallback;
            }
            // Relationship con ese Id y tipo worksheet. Se recorren los tags
            // <Relationship ...> uno a uno porque el orden de los atributos
            // varia: MS Excel escribe Id primero ("Id=.. Type=.. Target=..")
            // pero openpyxl/LibreOffice escriben Type primero.
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

            // Normalizar la ruta: los targets son relativos a xl/
            if (strpos($target, '/') === 0) {
                $path = ltrim($target, '/');            // "/xl/worksheets/sheet1.xml"
            } else {
                $path = 'xl/' . ltrim($target, '/');    // "worksheets/sheet1.xml"
            }

            // Verificar que la parte realmente exista en el ZIP.
            if ($zip->locateName($path) === false) {
                return $fallback;
            }
            return $path;
        } catch (Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Resuelve un directorio temporal escribible.
     *
     * Orden de preferencia:
     *   1. uploads/tmp/ dentro del proyecto (lo crea si no existe).
     *   2. sys_get_temp_dir() si es escribible.
     *   3. Directorio del propio script (__DIR__) como ultimo recurso.
     *
     * Lanza RuntimeException si no encuentra ningun directorio escribible.
     *
     * @return string Ruta absoluta a un directorio escribible.
     */
    private function resolveTempDir(): string
    {
        // Candidatos en orden de preferencia.
        $candidates = [];

        // 1) uploads/tmp/ relativo a la plantilla (que normalmente esta en
        //    /ruta/proyecto/uploads/Plantilla.xlsx, asi que subimos un nivel
        //    y entramos a uploads/tmp).
        $tplDir = dirname($this->templatePath);
        if (basename($tplDir) === 'uploads') {
            $candidates[] = $tplDir . '/tmp';
        } else {
            // Si la plantilla esta en otra ruta, intentamos igualmente un tmp/
            // junto a ella.
            $candidates[] = $tplDir . '/tmp';
        }

        // 2) sys_get_temp_dir()
        $sysTmp = sys_get_temp_dir();
        if ($sysTmp) {
            $candidates[] = rtrim($sysTmp, '/\\');
        }

        // 3) Directorio del propio script PHP (incluye/)
        $candidates[] = __DIR__;

        // 4) Directorio padre del script (raiz del proyecto)
        $candidates[] = dirname(__DIR__);

        foreach ($candidates as $dir) {
            // Intentar crear el directorio si no existe.
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            // Verificar que exista y sea escribible.
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        throw new RuntimeException(
            'No se encontro ningun directorio temporal escribible. ' .
            'Cree uploads/tmp/ con permisos 0755 o 0777 y vuelva a intentar.'
        );
    }

    /**
     * Aplica todos los valores de $this->cellValues al XML de la hoja.
     *
     * @param string $sheetXml Contenido original de la hoja.
     * @return string Contenido modificado.
     */
    private function applyCellValues(string $sheetXml): string
    {
        $xml = $sheetXml;

        foreach ($this->cellValues as $cellRef => $value) {
            $xml = $this->replaceCell($xml, $cellRef, $value);
        }

        return $xml;
    }

    /**
     * Reemplaza el contenido de una celda en el XML.
     *
     * Busca el patron <c r="G7" .../> (celda vacia) y lo reemplaza por:
     *   - Numericos: <c r="G7" ...><v>123</v></c>
     *   - Texto:     <c r="C2" ... t="inlineStr"><is><t xml:space="preserve">Texto</t></is></c>
     *
     * Si la celda no se encuentra (no existe en la plantilla), se emite un
     * warning en el log de PHP pero no se lanza excepcion (mejor esfuerzo).
     *
     * @param string           $xml      Contenido XML.
     * @param string           $cellRef  Referencia de celda (ej: "G7").
     * @param int|string|float $value    Valor a asignar.
     * @return string XML modificado.
     */
    private function replaceCell(string $xml, string $cellRef, $value): string
    {
        // Escapar el cellRef por si tiene caracteres especiales (no deberia).
        $refEsc = preg_quote($cellRef, '/');

        // Patron 1: celda vacia con estilo: <c r="G7" s="4"/>
        // Patron 2: celda vacia sin estilo: <c r="G7"/>
        // Aceptamos cualquier secuencia de atributos antes del />.
        // Usamos PREG_OFFSET_CAPTURE para saber la posicion exacta del match
        // y reemplazar por substring (mas seguro que preg_replace, que
        // interpretaria $ y \ como backrefs en el replacement).
        $pattern = '/<c r="' . $refEsc . '"([^<>\/]*?)\/>/';

        if (preg_match($pattern, $xml, $m, PREG_OFFSET_CAPTURE)) {
            $matched   = $m[0][0];              // string completo matcheado
            $pos       = $m[0][1];              // offset dentro de $xml
            $attrs     = $m[1][0];              // atributos capturados (' s="4"' o '')
            $newCell   = $this->buildCellXml($cellRef, $attrs, $value);
            // Reemplazo posicional: solo toca la PRIMERA ocurrencia.
            $xml = substr($xml, 0, $pos) . $newCell . substr($xml, $pos + strlen($matched));
            return $xml;
        }

        // Si no se encontro celda vacia, buscar celda ya con contenido y
        // reemplazar su valor (incluye <f> formulas: se eliminan al
        // reemplazar el contenido completo). Patron: <c r="G7" ...>...</c>
        $patternFilled = '/<c r="' . $refEsc . '"([^<>]*?)>(.*?)<\/c>/s';
        if (preg_match($patternFilled, $xml, $m, PREG_OFFSET_CAPTURE)) {
            $matched   = $m[0][0];
            $pos       = $m[0][1];
            $attrs     = $m[1][0];
            $newCell   = $this->buildCellXml($cellRef, $attrs, $value);
            $xml = substr($xml, 0, $pos) . $newCell . substr($xml, $pos + strlen($matched));
            return $xml;
        }

        // No se encontro la celda en la plantilla: intentar INSERTARLA.
        // Necesario para plantillas cuya fila de datos esta completamente
        // vacia en el XML (ej: "ITS_Plano.xlsx", dimension A1:CBN12 y fila de
        // datos 13). Antes solo se registraba un warning y la celda se
        // omitia, con lo que el plano salia sin datos. Si la insercion no es
        // posible (XML anomalo), se conserva el comportamiento anterior.
        return $this->insertCell($xml, $cellRef, $value);
    }

    /**
     * Inserta una celda NUEVA en el XML cuando la plantilla no la trae
     * (complemento de replaceCell() para el caso "celda no encontrada").
     *
     * Dos casos:
     *   1) La fila <row r="N"> existe: la celda se inserta dentro de esa
     *      fila respetando el orden de columnas (Excel las espera
     *      ascendentes). Si la fila esta autocerrada (<row r="N"/>), se
     *      convierte en abierta para poder albergar la celda.
     *   2) La fila no existe: se crea <row r="N">...</row> dentro de
     *      <sheetData> respetando el orden de filas (antes de la primera
     *      fila con numero mayor; si no hay ninguna, justo antes de
     *      </sheetData>). Si <sheetData/> esta autocerrada, se abre.
     *
     * La celda insertada no lleva estilo (sin s=""), igual que el
     * comportamiento del filler para celdas nuevas; el valor se construye
     * con buildCellXml() para respetar el tipo (numero/texto).
     *
     * @param string           $xml     Contenido XML de la hoja.
     * @param string           $cellRef Referencia de celda (ej: "D13").
     * @param int|string|float $value   Valor a asignar.
     * @return string XML con la celda insertada (o sin cambios si no fue posible).
     */
    private function insertCell(string $xml, string $cellRef, $value): string
    {
        if (preg_match('/^([A-Z]+)([0-9]+)$/', $cellRef, $mRef) !== 1) {
            error_log("[ExcelTemplateFiller] Celda {$cellRef} no encontrada en la plantilla.");
            return $xml;
        }
        $columna = $mRef[1];
        $fila    = (int)$mRef[2];
        $colIdx  = $this->colLetterToIndex($columna);

        // ---- Caso 1: la fila existe -> insertar la celda en orden de columna ----
        // La etiqueta de apertura de fila es literal (<row r="N"...), asi que
        // strpos() la localiza mucho mas rapido que una expresion regular.
        $posTag = strpos($xml, '<row r="' . $fila . '"');
        if ($posTag !== false) {
            $posGt = strpos($xml, '>', $posTag);
            if ($posGt === false) {
                error_log("[ExcelTemplateFiller] Celda {$cellRef} no insertada: fila {$fila} sin cierre de etiqueta.");
                return $xml;
            }
            $tagTexto = substr($xml, $posTag, $posGt - $posTag + 1);

            // Fila autocerrada <row r="N"/>: convertirla en abierta.
            if (substr($tagTexto, -2) === '/>') {
                $rowOpen = substr($tagTexto, 0, -2) . '>';
                $newCell = $this->buildCellXml($cellRef, '', $value);
                return substr($xml, 0, $posTag) . $rowOpen . $newCell . '</row>' . substr($xml, $posGt + 1);
            }

            $rowStart = $posGt + 1;
            $rowEnd   = strpos($xml, '</row>', $rowStart);
            if ($rowEnd === false) {
                error_log("[ExcelTemplateFiller] Celda {$cellRef} no insertada: fila {$fila} sin cierre </row>.");
                return $xml;
            }
            $segmento  = substr($xml, $rowStart, $rowEnd - $rowStart);
            $posInsert = $rowEnd; // por defecto, al final de la fila
            if (preg_match_all('/<c\\s+r="([A-Z]+)[0-9]+"/', $segmento, $mCeldas, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) !== false) {
                foreach ($mCeldas as $mC) {
                    if ($this->colLetterToIndex($mC[1][0]) > $colIdx) {
                        $posInsert = $rowStart + $mC[0][1];
                        break;
                    }
                }
            }
            $newCell = $this->buildCellXml($cellRef, '', $value);
            return substr($xml, 0, $posInsert) . $newCell . substr($xml, $posInsert);
        }

        // ---- Caso 2: la fila no existe -> crear <row> dentro de <sheetData> ----
        $newRow = '<row r="' . $fila . '">' . $this->buildCellXml($cellRef, '', $value) . '</row>';

        // <sheetData/> autocerrada (hoja sin filas): abrirla con la fila nueva.
        $posSdAuto = strpos($xml, '<sheetData/>');
        if ($posSdAuto !== false) {
            return substr($xml, 0, $posSdAuto) . '<sheetData>' . $newRow . '</sheetData>' . substr($xml, $posSdAuto + strlen('<sheetData/>'));
        }

        $posSheetData = strpos($xml, '<sheetData>');
        if ($posSheetData === false) {
            error_log("[ExcelTemplateFiller] Celda {$cellRef} no encontrada en la plantilla.");
            return $xml;
        }
        $posCierre = strpos($xml, '</sheetData>', $posSheetData);
        if ($posCierre === false) {
            error_log("[ExcelTemplateFiller] Celda {$cellRef} no insertada: <sheetData> sin cierre.");
            return $xml;
        }
        // Punto de insercion por defecto: justo antes de </sheetData> (orden
        // ascendente cuando la fila nueva es la ultima).
        $insertAt = $posCierre;
        // Si existe una fila con numero mayor, insertar ANTES de esa fila.
        if (preg_match_all('/<row\\b[^>]*?\\br="([0-9]+)"/', $xml, $mFilas, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) !== false) {
            foreach ($mFilas as $mF) {
                if ((int)$mF[1][0] > $fila) {
                    $insertAt = $mF[0][1];
                    break;
                }
            }
        }
        return substr($xml, 0, $insertAt) . $newRow . substr($xml, $insertAt);
    }

    /**
     * Convierte una letra de columna Excel a indice 1-based (ej: 'D' -> 4,
     * 'CBM' -> 2093). Misma logica que ExcelTemplateReader::colLetterToIndex;
     * se replica aqui para mantener al filler autocontenido (no requiere al
     * lector de plantillas).
     *
     * @param string $letra Letra(s) de columna.
     * @return int Indice 1-based.
     */
    private function colLetterToIndex(string $letra): int
    {
        $indice = 0;
        $letra  = strtoupper(preg_replace('/[^A-Za-z]/', '', $letra));
        $largo  = strlen($letra);
        for ($i = 0; $i < $largo; $i++) {
            $indice = $indice * 26 + (ord($letra[$i]) - 64);
        }
        return $indice;
    }

    /**
     * Construye el XML de una celda con valor.
     *
     * FIX CRITICO (2026-09-10): se elimina SIEMPRE el atributo t="..."
     * heredado de la plantilla (t="s", t="str", t="b", t="e", ...).
     *
     * ¿Por que? Excel guarda las celdas de texto de una plantilla como
     *   <c r="G7" s="4" t="s"><v>5</v></c>
     * donde t="s" = shared string y <v>5</v> es el INDICE en
     * xl/sharedStrings.xml. Si al escribir un NUMERO se conserva t="s":
     *   <c r="G7" s="4" t="s"><v>42</v></c>
     * Excel busca la shared string numero 42; si la plantilla solo tiene 5
     * (indices 0..4), el contenido es invalido y Excel declara EL ARCHIVO
     * DANO ("no se puede abrir porque el formato o la extension no son
     * validos" / cuadro de reparacion). Ademas, si el numero cae dentro del
     * rango, la celda mostraria un TEXTO ALEATORIO de la plantilla en lugar
     * del numero.
     *
     * @param string           $cellRef Referencia ("G7").
     * @param string           $attrs   Atributos existentes (ej: ' s="4"').
     * @param int|string|float $value   Valor.
     * @return string XML de la celda.
     */
    private function buildCellXml(string $cellRef, string $attrs, $value): string
    {
        // 1) Eliminar SIEMPRE el atributo t="..." heredado de la plantilla.
        //    El estilo (s="...") se conserva intacto; solo el tipo cambia
        //    segun el valor que se escribe ahora.
        $cleanAttrs = preg_replace('/\s+t="[^"]*"/', '', $attrs);
        if ($cleanAttrs === null) {
            $cleanAttrs = $attrs;
        }

        // 2) Si es entero o float, escribir como numero (sin t: tipo "n"
        //    por defecto).
        if (is_int($value) || is_float($value)) {
            return '<c r="' . $cellRef . '"' . $cleanAttrs . '><v>'
                 . $this->formatNumber($value)
                 . '</v></c>';
        }

        // 3) Si es una cadena puramente numerica (ej: "2024") sin ceros a la
        //    izquierda, escribirla como numero (asi Excel la trata como
        //    numero y permite formulas). Si tiene ceros a la izquierda o no
        //    es numerica, se escribe como texto.
        $strVal = (string)$value;
        $isNumericString = preg_match('/^-?\d+(\.\d+)?$/', $strVal) === 1
                        && $strVal[0] !== '0';
        if ($isNumericString) {
            return '<c r="' . $cellRef . '"' . $cleanAttrs . '><v>'
                 . $strVal
                 . '</v></c>';
        }

        // 4) Texto: usar inlineStr para no tocar sharedStrings.xml.
        $textEsc = $this->escapeXml($strVal);
        return '<c r="' . $cellRef . '"' . $cleanAttrs . ' t="inlineStr">'
             . '<is><t xml:space="preserve">' . $textEsc . '</t></is></c>';
    }

    /**
     * Formatea un numero para escribirlo en XML.
     * Los enteros se escriben sin decimales; los floats con punto decimal.
     *
     * @param int|float $n
     * @return string
     */
    private function formatNumber($n): string
    {
        if (is_int($n)) {
            return (string)$n;
        }
        // Float: usar hasta 6 decimales, sin trailing zeros.
        return rtrim(rtrim(sprintf('%.6f', $n), '0'), '.');
    }

    /**
     * Escapa caracteres especiales de XML 1.0 y elimina caracteres de control.
     *
     * @param string $s
     * @return string
     */
    private function escapeXml(string $s): string
    {
        // Eliminar caracteres de control invalidos en XML 1.0
        // (excepto tab, newline, carriage return).
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        if ($s === null) {
            $s = '';
        }
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
