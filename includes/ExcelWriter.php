<?php
/**
 * Sistema de Gestion de Datos HIS
 * Clase para generar archivos Excel (.xlsx)
 * Sin dependencias externas - usa ZipArchive + XML
 */

class ExcelWriter {
    private $sheets = [];
    private $styles = [];
    
    public function __construct() {
        // Estilos por defecto
        $this->styles = [
            'header' => [
                'font_bold' => true,
                'bg_color' => '2E75B6',
                'font_color' => 'FFFFFF',
                'alignment' => 'center'
            ],
            'normal' => [
                'font_bold' => false,
                'bg_color' => null,
                'font_color' => '000000',
                'alignment' => 'left'
            ]
        ];
    }
    
    /**
     * Agregar hoja con datos
     */
    public function addSheet($name, $headers, $data, $columnWidths = []) {
        $this->sheets[] = [
            'name' => substr($name, 0, 31), // Max 31 chars para nombre de hoja
            'headers' => $headers,
            'data' => $data,
            'columnWidths' => $columnWidths
        ];
    }
    
    /**
     * Generar y descargar archivo Excel
     */
    public function download($filename) {
        $tempDir = sys_get_temp_dir() . '/excel_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        try {
            $this->createContentTypes($tempDir);
            $this->createRels($tempDir);
            $this->createWorkbook($tempDir);
            $this->createWorkbookRels($tempDir);
            $this->createStyles($tempDir);
            $this->createSheets($tempDir);
            
            // Crear ZIP
            $zipFile = tempnam(sys_get_temp_dir(), 'xlsx_');
            $zip = new ZipArchive();
            $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            
            $this->addDirToZip($zip, $tempDir, '');
            $zip->close();
            
            // Enviar archivo
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($zipFile));
            header('Cache-Control: max-age=0');
            
            readfile($zipFile);
            
            // Limpiar
            unlink($zipFile);
            $this->deleteDir($tempDir);
            
        } catch (Exception $e) {
            $this->deleteDir($tempDir);
            throw $e;
        }
    }
    
    /**
     * Guardar archivo en ruta especificada
     */
    public function save($filepath) {
        $tempDir = sys_get_temp_dir() . '/excel_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        try {
            $this->createContentTypes($tempDir);
            $this->createRels($tempDir);
            $this->createWorkbook($tempDir);
            $this->createWorkbookRels($tempDir);
            $this->createStyles($tempDir);
            $this->createSheets($tempDir);
            
            $zip = new ZipArchive();
            $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $this->addDirToZip($zip, $tempDir, '');
            $zip->close();
            
            $this->deleteDir($tempDir);
            return true;
        } catch (Exception $e) {
            $this->deleteDir($tempDir);
            throw $e;
        }
    }
    
    private function createContentTypes($dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        
        foreach ($this->sheets as $i => $sheet) {
            $num = $i + 1;
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $num . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        
        $xml .= '</Types>';
        file_put_contents($dir . '/[Content_Types].xml', $xml);
    }
    
    private function createRels($dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $xml .= '</Relationships>';
        file_put_contents($dir . '/_rels/.rels', $xml);
    }
    
    private function createWorkbook($dir) {
        mkdir($dir . '/xl', 0777, true);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        foreach ($this->sheets as $i => $sheet) {
            $num = $i + 1;
            $xml .= '<sheet name="' . htmlspecialchars($sheet['name']) . '" sheetId="' . $num . '" r:id="rId' . $num . '"/>';
        }
        $xml .= '</sheets></workbook>';
        file_put_contents($dir . '/xl/workbook.xml', $xml);
    }
    
    private function createWorkbookRels($dir) {
        mkdir($dir . '/xl/_rels', 0777, true);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        
        foreach ($this->sheets as $i => $sheet) {
            $num = $i + 1;
            $xml .= '<Relationship Id="rId' . $num . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $num . '.xml"/>';
        }
        
        $xml .= '<Relationship Id="rIdStyle" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';
        file_put_contents($dir . '/xl/_rels/workbook.xml.rels', $xml);
    }
    
    private function createStyles($dir) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
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
        $xml .= '</styleSheet>';
        file_put_contents($dir . '/xl/styles.xml', $xml);
    }
    
    private function createSheets($dir) {
        mkdir($dir . '/xl/worksheets', 0777, true);
        
        foreach ($this->sheets as $i => $sheet) {
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
            $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
            
            // Columnas con ancho
            $xml .= '<cols>';
            $colCount = count($sheet['headers']);
            for ($c = 0; $c < $colCount; $c++) {
                $colLetter = $this->columnLetter($c);
                $width = isset($sheet['columnWidths'][$c]) ? $sheet['columnWidths'][$c] : 15;
                $xml .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $width . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
            
            $xml .= '<sheetData>';
            
            // Fila de encabezados (estilo 1 = header)
            $xml .= '<row r="1">';
            foreach ($sheet['headers'] as $c => $header) {
                $colLetter = $this->columnLetter($c);
                $xml .= '<c r="' . $colLetter . '1" s="1" t="inlineStr"><is><t>' . htmlspecialchars($header) . '</t></is></c>';
            }
            $xml .= '</row>';
            
            // Filas de datos (estilo 0 = normal)
            foreach ($sheet['data'] as $r => $row) {
                $rowNum = $r + 2;
                $xml .= '<row r="' . $rowNum . '">';
                foreach ($row as $c => $value) {
                    $colLetter = $this->columnLetter($c);
                    $cellRef = $colLetter . $rowNum;
                    
                    if ($value === null || $value === '') {
                        $xml .= '<c r="' . $cellRef . '" s="0"/>';
                    } elseif (is_numeric($value) && !preg_match('/^0\d+/', (string)$value)) {
                        $xml .= '<c r="' . $cellRef . '" s="0" t="n"><v>' . $value . '</v></c>';
                    } else {
                        $xml .= '<c r="' . $cellRef . '" s="0" t="inlineStr"><is><t>' . htmlspecialchars((string)$value) . '</t></is></c>';
                    }
                }
                $xml .= '</row>';
            }
            
            $xml .= '</sheetData></worksheet>';
            file_put_contents($dir . '/xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
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
    
    private function addDirToZip($zip, $dir, $prefix) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            $zipPath = $prefix ? $prefix . '/' . $item : $item;
            if (is_dir($path)) {
                $this->addDirToZip($zip, $path, $zipPath);
            } else {
                $zip->addFile($path, $zipPath);
            }
        }
    }
    
    private function deleteDir($dir) {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
