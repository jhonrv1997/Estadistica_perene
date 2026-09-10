<?php
/**
 * Sistema de Gestion de Datos HIS
 * Clase para generar archivos Excel .xlsx nativos
 * Sin dependencias externas — usa ZipArchive + XML
 *
 * FIX v4 (2026-09-10):
 *  - (v3) Escritura directa de partes XML en el ZIP, <dimension> y
 *    <autoFilter> en la hoja, verificacion de ZipArchive open/close,
 *    ob_end_clean() exhaustivo, strings con ceros a la izquierda como
 *    texto, caracteres de control XML eliminados.
 *  - (v4) styles.xml: se agrega <cellStyles> con el estilo "Normal".
 *    Sin el, algunos lectores (LibreOffice, openpyxl) advertian "Workbook
 *    contains no default style"; Excel lo tolera, pero incluirlo es lo que
 *    hace todo generador serio (PhpSpreadsheet incluido).
 *  - (v4) worksheet: se agrega <sheetViews> (congelar nada, solo la vista
 *    por defecto) y workbook.xml: <bookViews>. Son partes que MS Excel
 *    siempre escribe; incluirlas maximiza compatibilidad.
 *  - (v4) download(): guardas anti-corrupcion — se desactiva
 *    zlib.output_compression (hostings compartidos que recomprimen el
 *    binario y truncan el archivo) y se detecta headers_sent() para
 *    reportar con claridad si hubo salida previa (echo/warning/BOM),
 *    causa clasica de "archivo dano".
 */

class ExcelWriter {
    /** @var array<int, array{name:string,headers:array,data:array,columnWidths:array}> */
    private $sheets = [];

    public function __construct() {}

    public function addSheet($name, $headers, $data, $columnWidths = []) {
        $cleanName = str_replace([':', '\\', '/', '?', '*', '[', ']'], '', (string)$name);
        $cleanName = mb_substr($cleanName, 0, 31);
        $this->sheets[] = [
            'name'         => $cleanName ?: 'Sheet',
            'headers'      => array_values((array)$headers),
            'data'         => (array)$data,
            'columnWidths' => (array)$columnWidths,
        ];
    }

    public function download($filename) {
        $zipFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($zipFile === false) {
            throw new RuntimeException('No se pudo crear archivo temporal: ' . sys_get_temp_dir());
        }
        try {
            $this->buildXlsx($zipFile);
            while (ob_get_level() > 0) ob_end_clean();

            // Si algo ya envio bytes al cliente (echo, warning mostrado, BOM
            // de un archivo PHP incluido antes), la descarga binaria saldra
            // danoada. Reportarlo con claridad en lugar de corromper.
            if (headers_sent($sentFile, $sentLine)) {
                throw new RuntimeException(
                    'No se puede iniciar la descarga: ya hubo salida en ' . $sentFile . ':' . $sentLine .
                    '. Elimine echo/warnings/BOM antes de llamar download().'
                );
            }

            // La compresion de salida del hosting (zlib.output_compression)
            // re-comprime el binario y rompe Content-Length -> truncado.
            if (function_exists('ini_set')) {
                @ini_set('zlib.output_compression', '0');
            }
            $zlibOn = in_array(
                strtolower((string)@ini_get('zlib.output_compression')),
                ['1', 'on', 'true'], true
            );

            if (!is_readable($zipFile)) {
                throw new RuntimeException('Archivo xlsx no legible despues de build.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            if (!$zlibOn) {
                header('Content-Length: ' . filesize($zipFile));
            }
            header('Cache-Control: max-age=0, no-store');
            header('Pragma: public');
            header('Expires: 0');

            $fp = fopen($zipFile, 'rb');
            if (!$fp) throw new RuntimeException('No se pudo abrir xlsx para lectura.');
            fpassthru($fp);
            fclose($fp);
            flush();
        } finally {
            if (file_exists($zipFile)) @unlink($zipFile);
        }
    }

    public function save($filepath) {
        $this->buildXlsx($filepath);
        return true;
    }

    // ============================================================
    private function buildXlsx($zipFile) {
        $zip = new ZipArchive();
        $ok = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if (!$ok) {
            throw new RuntimeException('ZipArchive::open() fallo codigo: ' . $ok);
        }

        $zip->addFromString('[Content_Types].xml',      $this->renderContentTypes());
        $zip->addFromString('_rels/.rels',              $this->renderRels());
        $zip->addFromString('xl/workbook.xml',          $this->renderWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->renderWorkbookRels());
        $zip->addFromString('xl/styles.xml',            $this->renderStyles());
        foreach ($this->sheets as $i => $sheet) {
            $num = $i + 1;
            $zip->addFromString('xl/worksheets/sheet' . $num . '.xml', $this->renderSheetXml($sheet));
        }

        if (!$zip->close()) {
            throw new RuntimeException('ZipArchive::close() fallo (permisos de escritura?).');
        }
    }

    private function renderContentTypes() {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($this->sheets as $i => $s) {
            $num = $i + 1;
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $num . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml .= '</Types>';
        return $xml;
    }

    private function renderRels() {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $xml .= '</Relationships>';
        return $xml;
    }

    private function renderWorkbook() {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        // <bookViews> antes de <sheets> (orden exigido por el esquema; Excel
        // siempre lo escribe, y sin el algunos lectores se quejan).
        $xml .= '<bookViews><workbookView/></bookViews>';
        $xml .= '<sheets>';
        foreach ($this->sheets as $i => $s) {
            $num = $i + 1;
            $xml .= '<sheet name="' . htmlspecialchars($s['name'], ENT_QUOTES) . '" sheetId="' . $num . '" r:id="rId' . $num . '"/>';
        }
        $xml .= '</sheets></workbook>';
        return $xml;
    }

    private function renderWorkbookRels() {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $s) {
            $num = $i + 1;
            $xml .= '<Relationship Id="rId' . $num . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $num . '.xml"/>';
        }
        $xml .= '<Relationship Id="rIdStyle" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';
        return $xml;
    }

    private function renderStyles() {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<fonts count="2">';
        $xml .= '<font><sz val="10"/><color rgb="FF000000"/><name val="Calibri"/></font>';
        $xml .= '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>';
        $xml .= '</fonts>';
        $xml .= '<fills count="3">';
        $xml .= '<fill><patternFill patternType="none"/></fill>';
        $xml .= '<fill><patternFill patternType="gray125"/></fill>';
        $xml .= '<fill><patternFill patternType="solid"><fgColor rgb="FF2E75B6"/></patternFill></fill>';
        $xml .= '</fills>';
        $xml .= '<borders count="2">';
        $xml .= '<border><left/><right/><top/><bottom/><diagonal/></border>';
        $xml .= '<border><left style="thin"><color rgb="FFD9D9D9"/></left><right style="thin"><color rgb="FFD9D9D9"/></right><top style="thin"><color rgb="FFD9D9D9"/></top><bottom style="thin"><color rgb="FFD9D9D9"/></bottom><diagonal/></border>';
        $xml .= '</borders>';
        $xml .= '<cellStyleXfs count="2">';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyFont="1" applyFill="1" applyBorder="1"/>';
        $xml .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>';
        $xml .= '</cellStyleXfs>';
        $xml .= '<cellXfs count="2">';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>';
        $xml .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>';
        $xml .= '</cellXfs>';
        // v4: estilo "Normal" por defecto. Sin esta parte, los lectores
        // advertian "Workbook contains no default style" (openpyxl) y algunos
        // visores mostraban estilos raros. Es lo que todo generador serio
        // escribe.
        $xml .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>';
        $xml .= '</styleSheet>';
        return $xml;
    }

    private function renderSheetXml(array $sheet) {
        $colCount    = count($sheet['headers']);
        $totalRows   = count($sheet['data']) + 1;    // +1 por la fila de headers
        $lastCol     = $colCount > 0 ? $this->columnLetter($colCount - 1) : 'A';
        $dimRef      = 'A1:' . $lastCol . $totalRows;
        $filterRef   = 'A1:' . $lastCol . '1';

        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="' . $dimRef . '"/>';
        // v4: sheetViews (orden del esquema: dimension -> sheetViews -> cols)
        $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';

        // Columnas
        $xml .= '<cols>';
        for ($c = 0; $c < $colCount; $c++) {
            $w = isset($sheet['columnWidths'][$c]) ? max(1, (float)$sheet['columnWidths'][$c]) : 15;
            $xml .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . sprintf('%.2f', $w) . '" customWidth="1"/>';
        }
        $xml .= '</cols>';

        $xml .= '<sheetData>';

        // Headers (estilo 1)
        $xml .= '<row r="1">';
        foreach ($sheet['headers'] as $c => $header) {
            $cl = $this->columnLetter($c);
            $xml .= '<c r="' . $cl . '1" s="1" t="inlineStr"><is><t xml:space="preserve">'
                   . $this->escapeXml((string)$header) . '</t></is></c>';
        }
        $xml .= '</row>';

        // Datos (estilo 0)
        foreach ($sheet['data'] as $r => $row) {
            $rowNum = $r + 2;
            $xml .= '<row r="' . $rowNum . '">';
            $values = array_values((array)$row);
            for ($c = 0; $c < count($values); $c++) {
                $value    = $values[$c];
                $cl       = $this->columnLetter($c);
                $cellRef  = $cl . $rowNum;

                if ($value === null || $value === '') {
                    $xml .= '<c r="' . $cellRef . '" s="0"/>';
                    continue;
                }

                $strval = (string)$value;
                // Es numero entero/decimal SI no empieza con 0 (ej: 01234 se conserva como texto = DNI)
                $isNumber = preg_match('/^-?\d+(\.\d+)?$/', $strval) === 1
                          && $strval[0] !== '0';

                if ($isNumber) {
                    $xml .= '<c r="' . $cellRef . '" s="0" t="n"><v>' . $strval . '</v></c>';
                } else {
                    $xml .= '<c r="' . $cellRef . '" s="0" t="inlineStr"><is><t xml:space="preserve">'
                           . $this->escapeXml($strval) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        // AutoFilter que abarca solo los headers (habilita filtros al abrir Excel)
        $xml .= '<autoFilter ref="' . $filterRef . '"/>';

        $xml .= '</worksheet>';
        return $xml;
    }

    private function escapeXml(string $s): string {
        // Elimina caracteres de control invalidos en XML 1.0
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        if ($s === null) {
            $s = '';
        }
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function columnLetter($index) {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int)($index / 26);
        }
        return $letter;
    }
}
