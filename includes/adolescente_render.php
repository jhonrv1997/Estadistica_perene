<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ADOLESCENTE - Funciones de render del reporte web (layout tipo Excel).
 *
 * Renderizan las secciones devueltas por adoEjecutarReporte() con el mismo
 * diseno de la plantilla oficial "Reporte_Actividades_Adolescentes.xlsx"
 * (hoja "Plantilla"): 27 bloques con Total | 12a-14a | 15a-17a (o sus
 * variantes: paquetes x institucion en Control, casos en APP/sesiones).
 *
 * adoCeldasExport() construye el mapa [celda => valor] para llenar la
 * plantilla oficial con el mismo layout que el flujo ODBC original:
 *   Encabezado: C6 = PERIODO ("JUNIO 2026" o el anio), H6 = MICRO RED
 *   ("PERENE"), H7 = ESTABLECIMIENTO (nombre o "TODOS"). La RED y el
 *   RENIPRESS ya vienen fijos en la plantilla (C5 / H5).
 *   Datos: por cada fila de reporte, celdas segun el layout de su seccion
 *   (el campo 'fila' de cada fila + el mapa 'cols' de la seccion).
 */
require_once __DIR__ . '/adolescente_data.php';
require_once __DIR__ . '/functions.php';   // getNombreMes(): C6 escribe el mes en texto

/* ============================================================
 * UTILIDADES DE RENDER
 * ============================================================ */

/** Descripcion legible de la regla de conteo (para auditoria). */
function adoEtiquetaRegla(string $regla): string {
    switch ($regla) {
        case 'filas':    return 'count(*) del T-SQL (sum iif(edad between X and Y,1,0)): cada fila HIS que cumple aporta 1';
        case 'total_vl': return 'sum(Total) del T-SQL: Total = iif(TRY_CONVERT(int,valor_lab) IS NULL, 1, TRY_CONVERT(int,valor_lab)) (metodos anticonceptivos: cantidad registrada)';
    }
    return '';
}

/** Valor formateado de una celda (0 en gris si la seccion no tiene datos). */
function adoCelda($n, bool $ceroGris): string {
    $n = (float)$n;
    $txt = ($n == (int)$n) ? number_format((int)$n) : number_format($n, 1);
    if ($n == 0) return '<td class="num ' . ($ceroGris ? 'ado-cero' : 'text-muted') . '">0</td>';
    return '<td class="num fw-bold text-primary">' . $txt . '</td>';
}

/** Combos del bloque Control: etiqueta corta y larga. */
function adoComboLabels(): array {
    return [
        'P1I1' => ['EE.SS · Paquete básico',      'EE.SS / Paquete básico'],
        'P2I1' => ['EE.SS · Paquete completo',    'EE.SS / Paquete completo'],
        'P3I1' => ['EE.SS · Paquete especializado', 'EE.SS / Paquete especializado'],
        'P1I2' => ['II.EE · Paquete básico',      'II.EE / Paquete básico'],
        'P2I2' => ['II.EE · Paquete completo',    'II.EE / Paquete completo'],
    ];
}

/* ============================================================
 * RENDERIZADO DE SECCIONES (layout tipo Excel oficial)
 * ============================================================ */

/**
 * Renderiza una seccion del reporte ADOLESCENTE con el layout del Excel
 * oficial. Las filas se muestran en el ORDEN EXACTO de la plantilla
 * (campo 'fila').
 */
function adoRenderSeccion(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $layout = $sec['layout'];

    $filas = $sec['filas'];
    if (!$mostrarCeros) {
        $filas = array_values(array_filter($filas, fn($f) => ($f['total'] ?? 0) > 0));
        if (!$filas) {
            echo '<div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta sección con los filtros actuales.</div>';
            return;
        }
    }

    echo '<div class="table-responsive"><table class="table table-sm table-hover ado-table mb-0">';
    echo '<thead>';

    switch ($layout) {
        case 'STD': // Total | 12a-14a | 15a-17a (con o sin 2o nivel de etiqueta)
            $lcols = $sec['labelCols'] ?? ['DESCRIPCIÓN', null];
            if ($lcols[1] !== null) {
                echo '<tr><th class="text-start ado-th-label" colspan="2">' . htmlspecialchars($lcols[0]) . ' / ' . htmlspecialchars($lcols[1]) . '</th>';
            } else {
                echo '<tr><th class="text-start ado-th-label">' . htmlspecialchars($lcols[0]) . '</th>';
            }
            echo '<th class="ado-th-total">Total</th>';
            foreach (adoEdadLabels() as $lbl) echo '<th class="ado-th-gedad">' . $lbl . '</th>';
            echo '</tr>';
            break;

        case 'CTRL': // Actividad x Estado x Paquete x Institucion x Edad
            $cl = adoComboLabels();
            echo '<tr><th class="text-start ado-th-label" rowspan="3" style="min-width:260px;">ACTIVIDAD</th>';
            echo '<th class="text-start ado-th-label" rowspan="3" style="min-width:130px;">ESTADO</th>';
            echo '<th class="ado-th-total" rowspan="3">Total</th>';
            echo '<th class="ado-th-m" colspan="6">EE.SS</th><th class="ado-th-m" colspan="4">II.EE</th></tr>';
            echo '<tr>';
            echo '<th class="ado-th-m" colspan="2">' . $cl['P1I1'][0] . '</th>';
            echo '<th class="ado-th-m" colspan="2">' . $cl['P2I1'][0] . '</th>';
            echo '<th class="ado-th-m" colspan="2">' . $cl['P3I1'][0] . '</th>';
            echo '<th class="ado-th-m" colspan="2">' . $cl['P1I2'][0] . '</th>';
            echo '<th class="ado-th-m" colspan="2">' . $cl['P2I2'][0] . '</th>';
            echo '</tr><tr>';
            foreach (['P1I1','P2I1','P3I1','P1I2','P2I2'] as $ck) {
                foreach (adoEdadLabels() as $lbl) echo '<th class="ado-th-gedad">' . $lbl . '</th>';
            }
            echo '</tr>';
            break;

        case 'CASOS': // una sola columna (Casos o grupo etareo)
            echo '<tr><th class="text-start ado-th-label">DESCRIPCIÓN</th>';
            echo '<th class="ado-th-total">' . htmlspecialchars($sec['colLabel'] ?? 'Casos') . '</th></tr>';
            break;
    }
    echo '</thead><tbody>';

    $esCero = ($sec['total'] ?? 0) == 0;
    $labelAnterior = null;
    foreach ($filas as $f) {
        echo '<tr>';
        // La etiqueta es estatica del motor (sin input de usuario)
        $dosNiveles = ($sec['layout'] === 'STD') && (($sec['labelCols'][1] ?? null) !== null);
        if ($dosNiveles) {
            // Como el Excel: el grupo se muestra solo en su primera fila
            // (las continuas quedan vacias, imitando las celdas combinadas)
            $esNuevo = $f['label'] !== $labelAnterior;
            $labelAnterior = $f['label'];
            echo '<td class="ado-niv1">' . ($esNuevo ? $f['label'] : '') . '</td>';
            echo '<td class="ado-niv2">' . ($f['label2'] ?? '') . '</td>';
        } elseif ($sec['layout'] === 'STD') {
            echo '<td class="ado-niv1">' . $f['label'] . '</td>';
        } elseif ($sec['layout'] === 'CTRL') {
            echo '<td class="ado-niv1">' . $f['label'] . '</td><td class="ado-niv2">' . ($f['label2'] ?? '') . '</td>';
        } else {
            echo '<td class="ado-niv1">' . $f['label'] . '</td>';
        }

        switch ($layout) {
            case 'STD':
                $v = $f['vals'];
                echo adoCelda(adoVal($v, 1) + adoVal($v, 2), $esCero);
                echo adoCelda(adoVal($v, 1), $esCero);
                echo adoCelda(adoVal($v, 2), $esCero);
                break;

            case 'CTRL':
                $v = $f['vals'];
                $tot = 0;
                foreach (['P1I1','P2I1','P3I1','P1I2','P2I2'] as $ck) {
                    $vv = $v[$ck] ?? [];
                    $tot += adoVal($vv, 1) + adoVal($vv, 2);
                }
                echo adoCelda($tot, $esCero);
                foreach (['P1I1','P2I1','P3I1','P1I2','P2I2'] as $ck) {
                    $vv = $v[$ck] ?? [];
                    echo adoCelda(adoVal($vv, 1), $esCero);
                    echo adoCelda(adoVal($vv, 2), $esCero);
                }
                break;

            case 'CASOS':
                echo adoCelda($f['total'], $esCero);
                break;
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    if ($verSQL) adoPanelCondiciones($sec);
}

/** Panel colapsable con las condiciones SQL de cada fila (auditoria contra el archivo 03). */
function adoPanelCondiciones(array $sec): void {
    $regla = $sec['regla'];
    echo '<details class="ado-cond-panel"><summary class="small px-3 py-2 bg-light border-top" style="cursor:pointer;">
          <i class="fas fa-code me-1 text-secondary"></i>Condiciones SQL de esta sección (auditoría contra el archivo 03) &mdash;
          <code>' . htmlspecialchars($sec['procedimiento']) . '</code> &middot; regla: <code>' . htmlspecialchars($regla) . '</code>
          (' . adoEtiquetaRegla($regla) . ')</summary>
          <div class="p-2 px-3 bg-white border-top">';
    foreach ($sec['filas'] as $f) {
        echo '<div class="mb-2"><strong class="text-dark">' . htmlspecialchars($f['label']);
        if (!empty($f['label2'])) echo ' &middot; ' . htmlspecialchars($f['label2']);
        echo '</strong>';
        $conds = $f['conds'] ?? ($f['cond'] !== null ? ['' => $f['cond']] : []);
        if (!empty($conds)) {
            $cl = adoComboLabels();
            foreach ($conds as $ck => $cond) {
                $titulo = '';
                if (isset($f['conds']) && $ck !== '') $titulo = $cl[$ck][1] ?? (string)$ck;
                if ($titulo !== '') echo ' <span class="badge bg-secondary">' . htmlspecialchars($titulo) . '</span>';
                echo '<div class="ado-cond">' . htmlspecialchars(adoCondicionSQL($cond)) . '</div>';
            }
        } else {
            echo '<div class="ado-cond">sin consulta: el SP no produce datos para esta fila (queda en 0, como el flujo ODBC original)</div>';
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
 * "Reporte_Actividades_Adolescentes.xlsx" a partir del reporte ejecutado.
 *
 * Layouts (hoja "Plantilla", igual que el flujo ODBC original):
 *   STD (cols de la seccion): p.ej. Morbilidad B/C/D; Visita-Planificacion-
 *          Mental C/D/E; Nutricional-Visual E/F/G (B:D combinado en la
 *          plantilla para la etiqueta de 2 niveles).
 *   CTRL (filas 21-26): C=Total; EE.SS D/E (P1), F/G (P2), H/I (P3);
 *          II.EE J/K (P1), L/M (P2). El combo P3I2 no existe (delete del SP).
 *   CASOS: B=valor (Casos o grupo etareo de las sesiones 17-22).
 *
 * @param array  $reporte  Reporte ejecutado (adoEjecutarReporte)
 * @param string $anioTxt  Año del filtro
 * @param string $mesTxt   Mes del filtro ('' = todos)
 * @param string $estTxt   Nombre del establecimiento (H7) o ''
 * @return array<string,int|string|float> Mapa [refCelda => valor]
 */
function adoCeldasExport(array $reporte, string $anioTxt, string $mesTxt, string $estTxt): array {
    $cells = [];

    // ---- Cabecera de la plantilla ----
    // A6:B6 = "PERIODO:" -> C6 = periodo ("JUNIO 2026" / "2026" / "TODOS")
    $periodo = 'TODOS LOS PERIODOS';
    if ($anioTxt !== '' && $mesTxt !== '') {
        $periodo = strtoupper(getNombreMes((int)$mesTxt)) . ' ' . $anioTxt;
    } elseif ($anioTxt !== '') {
        $periodo = (string)$anioTxt;
    }
    $cells['C6'] = $periodo;
    // F6 = "MICRO RED:" -> H6 (combinada H6:I6)
    $cells['H6'] = 'PERENE';
    // F7 = "EE.SS:" -> H7 (combinada H7:I7)
    $cells['H7'] = $estTxt !== '' ? $estTxt : 'TODOS';

    foreach ($reporte['secciones'] as $sec) {
        $layout = $sec['layout'];
        $cols = $sec['cols'];

        foreach ($sec['filas'] as $f) {
            $row = $f['fila'] ?? null;
            if ($row === null) continue;
            $v = $f['vals'];

            switch ($layout) {
                case 'STD':
                    $cells[$cols['total'] . $row] = adoVal($v, 1) + adoVal($v, 2);
                    $cells[$cols['e12'] . $row]   = adoVal($v, 1);
                    $cells[$cols['e15'] . $row]   = adoVal($v, 2);
                    break;

                case 'CTRL':
                    $tot = 0;
                    foreach (['P1I1','P2I1','P3I1','P1I2','P2I2'] as $ck) {
                        $vv = $v[$ck] ?? [];
                        $tot += adoVal($vv, 1) + adoVal($vv, 2);
                        $cells[$cols[$ck][0] . $row] = adoVal($vv, 1);
                        $cells[$cols[$ck][1] . $row] = adoVal($vv, 2);
                    }
                    $cells[$cols['total'] . $row] = $tot;
                    break;

                case 'CASOS':
                    $cells[$cols['v'] . $row] = $f['total'];
                    break;
            }
        }
    }
    return $cells;
}
