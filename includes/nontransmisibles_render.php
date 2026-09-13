<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo NO TRANSMISIBLES - Funciones de render del reporte web (layout tipo Excel).
 *
 * Renderizan las secciones devueltas por ntEjecutarReporte() con el mismo
 * diseno de la plantilla oficial "Reporte_Actividades_NoTransmisibles.xlsx"
 * (hoja "Plantilla", cabeceras moradas #8064A2 como la original):
 *   - filas: etiqueta (1-3 niveles) + unidad de medida
 *   - columnas: TOTAL + grupos etareos (6 u 8) con subcolumnas M/F
 *   - HTA7 SESIONES: N y PARTICIPANTES (count(*) / sum(valor_lab))
 * Incluye el panel de auditoria "Condiciones SQL" (ver reporte_no_transmisibles.php).
 *
 * ntCeldasExport() construye el mapa [celda => valor] para llenar la plantilla
 * oficial con el mismo layout que el flujo ODBC original:
 *   Encabezado: B5 = PERIODO, B7 = CODIGO RENAES, H7 = IPRESS (H5 trae fija
 *   la RED "CHANCHAMAYO"; B8 contiene la formula =+B7*-1 de la plantilla).
 *   Datos: por cada fila, {colsX.total}{fila} y {colsX.gM[g]}{fila} /
 *   {colsX.gF[g]}{fila} por grupo etareo y sexo (layout 6GC/8GC/8GE), o
 *   {colsX.num}{fila} / {colsX.part}{fila} para HTA7 SESIONES.
 */
require_once __DIR__ . '/nontransmisibles_data.php';

// ====== RENDERIZADO DE SECCIONES (layout tipo Excel oficial) ======

/** Etiquetas de los grupos etareos (DimNT_Gedad / DimNT_Gedad2). */
function ntGedadLabel(string $tipo, int $g): string {
    $labels = ntGedadLabels($tipo);
    return $labels[$g] ?? (string)$g;
}

/** Descripcion legible de la regla de conteo (para auditoria). */
function ntEtiquetaRegla(string $regla): string {
    switch ($regla) {
        case 'personas': return 'count(distinct id_persona) del T-SQL (pares EE.SS|paciente|periodo)';
        case 'filas':    return 'count(*) del T-SQL (cada fila HIS que cumple)';
        case 'sesiones': return 'count(*) N&ordm; + sum(valor_lab) Participantes (NULL=&gt;1)';
    }
    return '';
}

/** Valor formateado de una celda (0 en gris si la seccion no tiene datos). */
function ntCelda($n, bool $ceroGris): string {
    $n = (float)$n;
    $txt = ($n == (int)$n) ? number_format((int)$n) : number_format($n, 1);
    if ($n == 0) return '<td class="num ' . ($ceroGris ? 'nt-cero' : 'text-muted') . '">0</td>';
    return '<td class="num fw-bold text-primary">' . $txt . '</td>';
}

/** Numero de niveles de etiqueta que usa una seccion (1-3). */
function ntNivelesSeccion(array $sec): int {
    $n = 1;
    foreach ($sec['filas'] as $f) {
        if (!empty($f['niv3'])) $n = 3;
        elseif (!empty($f['niv2']) && $n < 2) $n = 2;
    }
    return $n;
}

/**
 * Renderiza una seccion del reporte con el layout del Excel oficial.
 *
 * Estructura de la tabla:
 *   [ ETIQUETA (1-3 niveles) | UNIDAD DE MEDIDA | TOTAL | grupos etareos (M|F) ]
 * Las filas se muestran en el ORDEN EXACTO de la plantilla (campo 'fila').
 */
function ntRenderSeccion(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $layout = $sec['layout'];
    $regla = $sec['regla'];
    $gedadTipo = $sec['gedadTipo'];
    $grupos = ($gedadTipo === 'g8') ? range(1, 8) : range(1, 6);
    $niveles = ntNivelesSeccion($sec);

    $filas = $sec['filas'];
    if (!$mostrarCeros) {
        $filas = array_values(array_filter($filas, fn($f) => ($f['total'] ?? 0) > 0 || ($f['part'] ?? 0) > 0));
        if (!$filas) {
            echo '<div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta secci&oacute;n con los filtros actuales.</div>';
            return;
        }
    }

    echo '<div class="table-responsive"><table class="table table-sm table-hover nt-table mb-0">';
    echo '<thead><tr>';
    // Encabezado fila 1: etiquetas + grupos etareos con colspan 2 (M/F)
    // (las etiquetas son cadenas estaticas con entidades HTML ya aplicadas)
    echo '<th class="text-start nt-th-label" rowspan="2" style="min-width:260px;">' . ($sec['columna_label'] ?? 'Categor&iacute;a') . '</th>';
    if ($niveles >= 2) echo '<th class="text-start nt-th-label" rowspan="2" style="min-width:170px;">' . ($niveles === 3 ? 'Diagn&oacute;stico / Categor&iacute;a' : 'Subcategor&iacute;a') . '</th>';
    if ($niveles >= 3) echo '<th class="text-start nt-th-label" rowspan="2" style="min-width:130px;">Categor&iacute;a</th>';
    echo '<th class="nt-th-label" rowspan="2" style="min-width:100px;">UNIDAD DE MEDIDA</th>';
    if ($layout === 'SES') {
        echo '<th class="num nt-th-total" rowspan="2">N&ordm;</th>';
        echo '<th class="num nt-th-total" rowspan="2">PARTICIPANTES</th>';
    } else {
        echo '<th class="num nt-th-total" rowspan="2">TOTAL</th>';
        foreach ($grupos as $g) {
            echo '<th class="num nt-th-gedad" colspan="2">' . ntGedadLabel($gedadTipo, $g) . '</th>';
        }
    }
    echo '</tr>';
    if ($layout !== 'SES') {
        echo '<tr>';
        foreach ($grupos as $g) {
            echo '<th class="num nt-th-m">M</th><th class="num nt-th-f">F</th>';
        }
        echo '</tr>';
    }
    echo '</thead><tbody>';

    $esCero = ($sec['total'] ?? 0) == 0;
    $prev = ['niv1' => null, 'niv2' => null];
    foreach ($filas as $i => $f) {
        $clases = [];
        if ($i > 0 && $f['niv1'] !== $filas[$i - 1]['niv1']) $clases[] = 'nt-bloque-border';
        echo '<tr class="' . implode(' ', $clases) . '">';
        // Etiquetas (atenuadas si repiten el nivel superior, como el Excel).
        // Son cadenas estaticas del motor con entidades ya aplicadas (sin input de usuario).
        $rep1 = $i > 0 && $filas[$i - 1]['niv1'] === $f['niv1'];
        echo '<td class="' . ($rep1 ? 'nt-niv-cont' : 'nt-niv1') . '">' . $f['niv1'] . '</td>';
        if ($niveles >= 2) {
            $rep2 = $i > 0 && $filas[$i - 1]['niv1'] === $f['niv1'] && $filas[$i - 1]['niv2'] === $f['niv2'];
            echo '<td class="small ' . ($rep2 ? 'nt-niv-cont' : 'nt-niv2') . '">' . ($f['niv2'] ?? '') . '</td>';
        }
        if ($niveles >= 3) {
            echo '<td class="small text-muted">' . ($f['niv3'] ?? '') . '</td>';
        }
        echo '<td class="small text-muted">' . ($f['unidad'] ?? '-') . '</td>';
        if ($layout === 'SES') {
            echo ntCelda($f['total'] ?? 0, $esCero);
            echo ntCelda($f['part'] ?? 0, $esCero);
        } else {
            echo ntCelda($f['total'] ?? 0, $esCero);
            foreach ($grupos as $g) {
                echo ntCelda($f['sexo'][$g]['M'] ?? 0, $esCero);
                echo ntCelda($f['sexo'][$g]['F'] ?? 0, $esCero);
            }
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    if ($verSQL) ntPanelCondiciones($sec, $regla);
}

/** Panel colapsable con las condiciones SQL de cada fila (auditoria contra el archivo 03). */
function ntPanelCondiciones(array $sec, string $regla): void {
    echo '<details class="nt-cond-panel"><summary class="small px-3 py-2 bg-light border-top" style="cursor:pointer;">
          <i class="fas fa-code me-1 text-secondary"></i>Condiciones SQL de esta secci&oacute;n (auditor&iacute;a contra el archivo 03) &mdash;
          <code>' . htmlspecialchars($sec['procedimiento']) . '</code> &middot; regla: <code>' . htmlspecialchars($regla) . '</code>
          (' . ntEtiquetaRegla($regla) . ')</summary>
          <div class="p-2 px-3 bg-white border-top">';
    foreach ($sec['filas'] as $f) {
        $label = trim($f['niv1'] . ' / ' . ($f['niv2'] ?? '') . ' / ' . ($f['niv3'] ?? ''), ' /');
        echo '<div class="mb-2"><strong class="text-dark">' . $label . '</strong>';
        if (!empty($f['cond'])) {
            echo '<div class="nt-cond">' . htmlspecialchars(ntCondicionSQL($f['cond'])) . '</div>';
        } else {
            echo '<div class="nt-cond">sin consulta: el SP no produce datos para esta fila (queda en 0, como el flujo ODBC original)</div>';
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
 * "Reporte_Actividades_NoTransmisibles.xlsx" a partir del reporte ejecutado.
 *
 * El mapa posicional (colsX + campo 'fila' de cada fila) calza 1:1 con la
 * plantilla (hoja "Plantilla"):
 *   Encabezado:
 *     B5 = PERIODO | B7 = CODIGO RENAES (B8 = formula =+B7*-1) | H7 = IPRESS
 *   Cada fila del reporte escribe (layout 6GC/8GC/8GE):
 *     {colsX.total}{fila}            = Total de la fila
 *     {colsX.gM[g]}{fila}            = valor del grupo g, sexo M
 *     {colsX.gF[g]}{fila}            = valor del grupo g, sexo F
 *   HTA7 SESIONES (layout SES):
 *     {colsX.num}{fila}  = N (count)  |  {colsX.part}{fila} = Participantes
 *
 * @param array  $reporte    Reporte ejecutado (ntEjecutarReporte)
 * @param string $anioTxt    Texto del periodo (B5)
 * @param string $renaes     Codigo RENAES (B7) o '' (todos)
 * @param string $nombreEst  Nombre del IPRESS (H7) o '' (todos)
 * @return array<string,int|string|float> Mapa [refCelda => valor]
 */
function ntCeldasExport(array $reporte, string $anioTxt, string $renaes, string $nombreEst): array {
    $cells = [];
    // Cabecera de la plantilla (areas de valor vacias en el flujo ODBC original)
    $cells['B5'] = $anioTxt !== '' ? $anioTxt : 'TODOS LOS PERIODOS';
    if ($renaes !== '' && is_numeric($renaes)) $cells['B7'] = (int)$renaes;
    $cells['H7'] = $nombreEst !== '' ? $nombreEst : 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';

    foreach ($reporte['secciones'] as $sec) {
        $colsX = $sec['colsX'] ?? null;
        if ($colsX === null) continue;
        $layout = $sec['layout'];
        $grupos = ($sec['gedadTipo'] === 'g8') ? range(1, 8) : range(1, 6);

        foreach ($sec['filas'] as $f) {
            $row = $f['fila'] ?? null;
            if ($row === null) continue;
            if ($layout === 'SES') {
                if (!empty($colsX['num']))  $cells[$colsX['num'] . $row]  = (int)($f['total'] ?? 0);
                if (!empty($colsX['part'])) $cells[$colsX['part'] . $row] = (float)($f['part'] ?? 0);
                continue;
            }
            if (!empty($colsX['total'])) {
                $cells[$colsX['total'] . $row] = (float)($f['total'] ?? 0);
            }
            foreach ($grupos as $g) {
                if (isset($colsX['gM'][$g])) {
                    $cells[$colsX['gM'][$g] . $row] = (float)($f['sexo'][$g]['M'] ?? 0);
                }
                if (isset($colsX['gF'][$g])) {
                    $cells[$colsX['gF'][$g] . $row] = (float)($f['sexo'][$g]['F'] ?? 0);
                }
            }
        }
    }
    return $cells;
}
