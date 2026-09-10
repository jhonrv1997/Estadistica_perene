<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ZOONOSIS - Funciones de render del reporte web (layout tipo Excel).
 *
 * Renderizan las secciones devueltas por zooEjecutarReporte() con el mismo
 * diseno de la plantilla oficial "Reporte_Actividades_Zoonosis.xlsx":
 *   - filas: bloque (diagnostico / estado / tratamiento) x sub-categoria x sexo
 *   - columnas: TOTAL + grupos etareos (0-11a .. 60 y mas) + GESTANTES
 * Incluye el panel de auditoria "Condiciones SQL" (ver reporte_zoonosis.php).
 *
 * zooCeldasExport() construye el mapa [celda => valor] para llenar la plantilla
 * oficial con el mismo layout que el flujo ODBC original.
 */
require_once __DIR__ . '/zoonosis_data.php';

// ====== RENDERIZADO DE SECCIONES (layout tipo Excel oficial) ======

/** Etiquetas de los grupos etareos (DimZoonosisEtapa). */
function zooEtapaLabel(int $e): string {
    return [1 => '0-11a', 2 => '12-17a', 3 => '18-29a', 4 => '30-59a', 5 => '60 y más'][$e] ?? (string)$e;
}

/** Etiqueta de la fila de sexo (T = TOTAL como la plantilla). */
function zooSexoLabel(?string $sx): string {
    if ($sx === 'T') return 'TOTAL';
    if ($sx === 'M') return 'M';
    if ($sx === 'F') return 'F';
    return '';
}

/** Descripcion legible de la regla de conteo (para auditoria). */
function zooEtiquetaRegla(string $regla): string {
    switch ($regla) {
        case 'filas':    return 'count(*) del T-SQL (cada fila HIS que cumple)';
        case 'personas': return 'count(distinct id_persona) del T-SQL (pares EE.SS|paciente)';
        case 'suma':     return 'sum(valor_lab) del T-SQL (frascos / dosis)';
    }
    return '';
}

/** Valor formateado de una celda (0 en gris si la seccion no tiene datos). */
function zooCelda($n, bool $ceroGris): string {
    $n = (float)$n;
    $txt = ($n == (int)$n) ? number_format((int)$n) : number_format($n, 1);
    if ($n == 0) return '<td class="num ' . ($ceroGris ? 'zoo-cero' : 'text-muted') . '">0</td>';
    return '<td class="num fw-bold text-primary">' . $txt . '</td>';
}

/**
 * Renderiza una seccion del reporte con el layout del Excel oficial.
 *
 * Estructura de la tabla:
 *   [ Bloque / Categoria | Ind./Subcat. | SEXO ] + TOTAL + etapas + GESTANTES
 * Las filas se muestran en el ORDEN EXACTO de la plantilla (campo 'fila'),
 * con las filas T/M/F agrupadas visualmente bajo el mismo bloque.
 */
function zooRenderSeccion(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $conEtapas = $sec['conEtapas'];
    $conGes = $sec['conGestantes'];
    $conSexo = $sec['conSexo'];
    $regla = $sec['regla'];

    $filas = $sec['filas'];
    if (!$mostrarCeros) {
        $filas = array_values(array_filter($filas, fn($f) => ($f['total'] ?? 0) > 0));
        if (!$filas) {
            echo '<div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta secci&oacute;n con los filtros actuales.</div>';
            return;
        }
    }

    echo '<div class="table-responsive"><table class="table table-sm table-hover zoo-table mb-0"><thead><tr>';
    echo '<th class="text-start" style="min-width:260px;">' . htmlspecialchars($sec['columna_label'] ?? 'Categor&iacute;a') . '</th>';
    echo '<th class="text-start" style="min-width:150px;">Indicaci&oacute;n / Subcategor&iacute;a</th>';
    if ($conSexo) echo '<th class="text-center">SEXO</th>';
    echo '<th class="num zoo-col-calc">TOTAL</th>';
    if ($conEtapas) {
        foreach ([1, 2, 3, 4, 5] as $e) echo '<th class="num">' . htmlspecialchars(zooEtapaLabel($e)) . '</th>';
    }
    if ($conGes) echo '<th class="num zoo-col-ges">GESTANTES</th>';
    echo '</tr></thead><tbody>';

    $esCero = ($sec['total'] ?? 0) == 0;
    $bloquePrev = null;
    $n = count($filas);
    foreach ($filas as $i => $f) {
        $esCalc = !empty($f['calc']);
        $esTotalBloque = ($f['niv1'] === 'Total' && $esCalc);
        $clases = [];
        if ($esTotalBloque) $clases[] = 'zoo-total-row';
        if ($f['niv1'] !== $bloquePrev && $bloquePrev !== null) $clases[] = 'zoo-bloque-border';
        $bloquePrev = $f['niv1'];
        echo '<tr class="' . implode(' ', $clases) . '">';
        // Bloque (con rowspan visual simple: se repite pero se atenua si es igual al anterior)
        $nuevoBloque = ($i === 0) || $filas[$i - 1]['niv1'] !== $f['niv1'];
        echo '<td class="' . ($nuevoBloque ? 'zoo-bloque' : 'zoo-bloque-cont') . '">' . htmlspecialchars($f['niv1']) . '</td>';
        echo '<td class="small">' . htmlspecialchars($f['niv2'] ?? '') . '</td>';
        if ($conSexo) echo '<td class="text-center small fw-semibold">' . htmlspecialchars(zooSexoLabel($f['sexo'])) . '</td>';
        echo zooCelda($f['total'] ?? 0, $esCero);
        if ($conEtapas) {
            foreach ([1, 2, 3, 4, 5] as $e) {
                echo zooCelda($f['valores'][$e] ?? 0, $esCero);
            }
        }
        if ($conGes) echo zooCelda($f['ges'] ?? 0, $esCero);
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    if ($verSQL) zooPanelCondiciones($sec, $regla);
}

/** Panel colapsable con las condiciones SQL de cada fila (auditoria contra el archivo 03). */
function zooPanelCondiciones(array $sec, string $regla): void {
    echo '<details class="zoo-cond-panel"><summary class="small px-3 py-2 bg-light border-top" style="cursor:pointer;">
          <i class="fas fa-code me-1 text-secondary"></i>Condiciones SQL de esta secci&oacute;n (auditor&iacute;a contra el archivo 03) &mdash;
          regla: <code>' . htmlspecialchars($regla) . '</code> (' . htmlspecialchars(zooEtiquetaRegla($regla)) . ')</summary>
          <div class="p-2 px-3 bg-white border-top">';
    foreach ($sec['filas'] as $f) {
        $label = trim($f['niv1'] . ' / ' . ($f['niv2'] ?? '') . ' / ' . zooSexoLabel($f['sexo']), ' /');
        echo '<div class="mb-2"><strong class="text-dark">' . htmlspecialchars($label) . '</strong>';
        if (!empty($f['cond'])) {
            echo '<div class="zoo-cond">' . htmlspecialchars(zooCondicionSQL($f['cond'])) . '</div>';
        } elseif (!empty($f['calc'])) {
            echo ' <span class="zoo-regla">[calculada = suma de ' . count($f['calc']) . ' filas]</span>';
        } else {
            echo '<div class="zoo-cond">sin consulta: el SP no produce datos para esta fila (queda en 0, como el flujo ODBC original)</div>';
        }
        echo '</div>';
    }
    echo '</div></details>';
}

/* ============================================================
 * MAPA DE CELDAS PARA EXPORTAR A LA PLANTILLA OFICIAL
 * ============================================================ */

/**
 * Construye el mapa [celda => valor] para llenar la plantilla oficial
 * "Reporte_Actividades_Zoonosis.xlsx" a partir del reporte ejecutado
 * (zooEjecutarReporte / zooEjecutarDesdeFilas).
 *
 * El mapa posicional (colsX + campo 'fila' de cada fila) calza 1:1 con la
 * plantilla (hoja "Plantilla"):
 *   Encabezado:
 *     D5 = IPRESS (area combinada D5:M5)
 *     R4 = MES    (area combinada R4:U4)
 *     O5 = AÑO    (escribir en el ancla O5)
 *   Cada fila del reporte escribe:
 *     {colsX.total}{fila}       = Total de la fila
 *     {colsX.etapas[e]}{fila}   = valor del grupo etareo e (si la seccion tiene etapas)
 *     {colsX.ges}{fila}         = GESTANTES (si la seccion tiene la columna)
 *
 * @param array  $reporte  Reporte ejecutado (['secciones'=>[...], ...])
 * @param string $mesTxt   Texto del mes (celda R4) o '' para todos
 * @param string $anioTxt  Texto del año (celda O5)
 * @param string $nombreEst Nombre del EE.SS o '' (todos)
 * @return array<string,int|string|float> Mapa [refCelda => valor]
 */
function zooCeldasExport(array $reporte, string $mesTxt, string $anioTxt, string $nombreEst): array {
    $cells = [];
    // Cabecera de la plantilla (areas de valor vacias en el flujo ODBC original)
    $cells['D5'] = $nombreEst !== '' ? $nombreEst : 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';
    $cells['R4'] = $mesTxt !== '' ? $mesTxt : 'TODOS';
    $cells['O5'] = $anioTxt !== '' ? $anioTxt : 'TODOS';

    foreach ($reporte['secciones'] as $sec) {
        $colsX = $sec['colsX'] ?? null;
        if ($colsX === null) continue;
        $conEtapas = $sec['conEtapas'];
        $conGes = $sec['conGestantes'];

        foreach ($sec['filas'] as $f) {
            $row = $f['fila'] ?? null;
            if ($row === null) continue;
            if (!empty($colsX['total'])) {
                $cells[$colsX['total'] . $row] = (float)($f['total'] ?? 0);
            }
            if ($conEtapas && !empty($colsX['etapas'])) {
                foreach ([1, 2, 3, 4, 5] as $e) {
                    if (isset($colsX['etapas'][$e])) {
                        $cells[$colsX['etapas'][$e] . $row] = (float)($f['valores'][$e] ?? 0);
                    }
                }
            }
            if ($conGes && !empty($colsX['ges'])) {
                $cells[$colsX['ges'] . $row] = (int)($f['ges'] ?? 0);
            }
        }
    }
    return $cells;
}
