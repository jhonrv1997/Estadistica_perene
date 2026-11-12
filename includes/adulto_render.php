<?php

/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ADULTO - Funciones de render del reporte web (layout tipo Excel).
 *
 * Renderizan las secciones devueltas por aduEjecutarReporte() con el mismo
 * diseno de la plantilla oficial "Reporte_Actividades_Adulto.xlsx" (hoja
 * "Plantilla"): 17 bloques con las columnas F | M | TOTAL del grupo etareo
 * 30a-59a (o sus variantes: metodos de PF con filas A/I y columnas NUEVO |
 * CONTI, AOE con fila A/I y una sola cifra, Sesiones con Nº | Part/Benef y
 * Otras Consejerias con una sola columna TOTAL).
 *
 * aduCeldasExport() construye el mapa [celda => valor] para llenar la
 * plantilla oficial con el mismo layout que el flujo ODBC original:
 *   Encabezado: B5 = PERIODO ("JUNIO 2026"), B7 = CODIGO RENAES
 *   (Codigo_Unico RENAES, vacio si el filtro es "-- Todos --"),
 *   D5 = "CHANCHAMAYO" (ya viene fija en la plantilla) y
 *   D7 (combinada D7:G7) = ESTABLECIMIENTO (nombre o "TODOS").
 *   Datos: por cada fila de reporte, celdas D/E/F segun el layout de su
 *   seccion (el campo 'fila' de cada fila + el layout de la seccion).
 */
require_once __DIR__ . '/adulto_data.php';
require_once __DIR__ . '/functions.php';   // getNombreMes(): B5 escribe el mes en texto

/* ============================================================
 * UTILIDADES DE RENDER
 * ============================================================ */

/** Descripcion legible de la regla de conteo (para auditoria). */
function aduEtiquetaRegla(string $regla): string {
    switch ($regla) {
        case 'filas':      return 'count(*) del T-SQL: cada fila HIS que cumple aporta 1';
        case 'insumo06_2': return 'sum(LAB) del SP 06_2: LAB = iif(TRY_CONVERT(int,valor_lab) IS NULL o método 8-13, 0, valor_lab) (fila I de los métodos de PF)';
        case 'min1':       return 'sum(LAB) del T-SQL: LAB = iif(TRY_CONVERT(int,valor_lab) IS NULL o <1, 1, valor_lab) (fila I del AOE / Part-Benef de sesiones)';
    }
    return '';
}

/** Valor formateado de una celda (0 en gris si la seccion no tiene datos). */
function aduCelda($n, bool $ceroGris): string {
    $n = (float)$n;
    $txt = ($n == (int)$n) ? number_format((int)$n) : number_format($n, 1);
    if ($n == 0) return '<td class="num ' . ($ceroGris ? 'adu-cero' : 'text-muted') . '">0</td>';
    return '<td class="num fw-bold text-primary">' . $txt . '</td>';
}

/* ============================================================
 * RENDERIZADO DE SECCIONES (layout tipo Excel oficial)
 * ============================================================ */

/**
 * Renderiza una seccion del reporte ADULTO con el layout del Excel oficial.
 * Las filas se muestran en el ORDEN EXACTO de la plantilla (campo 'fila').
 */
function aduRenderSeccion(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $layout = $sec['layout'];

    $filas = $sec['filas'];
    if (!$mostrarCeros) {
        $filas = array_values(array_filter($filas, fn($f) => ($f['total'] ?? 0) > 0));
        if (!$filas) {
            echo '<div class="p-3 text-muted small"><i class="fas fa-info-circle me-1"></i>'
               . 'Sin datos para el periodo seleccionado (todas las cifras en 0).</div>';
            return;
        }
    }

    echo '<div class="table-responsive"><table class="table adu-table mb-0">';

    // ---- Cabecera: igual a la plantilla (30a-59a sobre F|M + TOTAL) ----
    echo '<thead>';
    switch ($layout) {
        case 'FMT':
            echo '<tr>'
               . '<th class="adu-th-label" colspan="3">' . htmlspecialchars($sec['sub'] ?? $sec['titulo']) . '</th>'
               . '<th class="adu-th-gedad" colspan="2">30a-59a</th>'
               . '<th class="adu-th-total" rowspan="2">TOTAL</th>'
               . '</tr><tr>'
               . '<th class="adu-th-label" colspan="3"></th>'
               . '<th class="adu-th-m">F</th><th class="adu-th-m">M</th>'
               . '</tr>';
            break;
        case 'PFM':
            echo '<tr>'
               . '<th class="adu-th-label" colspan="3">' . htmlspecialchars($sec['sub'] ?? 'Método') . '</th>'
               . '<th class="adu-th-gedad">Activ. (A) / INSUMO</th>'
               . '<th class="adu-th-m">NUEVO</th><th class="adu-th-m">CONTI</th>'
               . '</tr>';
            break;
        case 'AOE':
            echo '<tr>'
               . '<th class="adu-th-label" colspan="3">' . htmlspecialchars($sec['sub'] ?? 'Método') . '</th>'
               . '<th class="adu-th-gedad">Activ. (A) / INSUMO</th>'
               . '<th class="adu-th-total">CIFRA</th>'
               . '</tr>';
            break;
        case 'SES':
            echo '<tr>'
               . '<th class="adu-th-label" colspan="4">' . htmlspecialchars($sec['sub'] ?? 'Sesión') . '</th>'
               . '<th class="adu-th-m">Nº</th>'
               . '<th class="adu-th-total">Part/Benef</th>'
               . '</tr>';
            break;
        case 'TOT':
            echo '<tr>'
               . '<th class="adu-th-label" colspan="5">' . htmlspecialchars($sec['sub'] ?? '') . '</th>'
               . '<th class="adu-th-total">TOTAL</th>'
               . '</tr>';
            break;
    }
    echo '</thead><tbody>';

    foreach ($filas as $f) {
        // Etiqueta principal (columna A:C de la plantilla)
        $lbl = htmlspecialchars($f['label']);
        $sub = '';
        if ($f['label2'] !== null && $f['label2'] !== '') $sub .= htmlspecialchars($f['label2']);
        if ($f['label3'] !== null && $f['label3'] !== '')  $sub .= ($sub !== '' ? ' · ' : '') . htmlspecialchars($f['label3']);

        echo '<tr>';
        switch ($layout) {
            case 'FMT':
                echo '<td class="adu-niv1" colspan="3">' . $lbl . ($sub !== '' ? ' <span class="adu-sub">— ' . $sub . '</span>' : '') . '</td>';
                echo aduCelda(aduVal($f['vals'], 1), $mostrarCeros);   // D = F (mujeres)
                echo aduCelda(aduVal($f['vals'], 2), $mostrarCeros);   // E = M (hombres)
                echo aduCelda($f['total'], $mostrarCeros);             // F = TOTAL
                break;

            case 'PFM':
                $esI = ($f['label2'] === 'I');
                echo '<td class="' . ($esI ? 'adu-niv2' : 'adu-niv1') . '" colspan="3">' . $lbl
                   . ($esI ? '' : ' <span class="adu-sub">— actividad</span>') . '</td>';
                echo '<td class="text-center fw-bold ' . ($esI ? 'text-warning' : 'text-success') . '">'
                   . htmlspecialchars($f['label2']) . '</td>';
                echo aduCelda(aduVal($f['vals'], 1), $mostrarCeros);   // E = NUEVO
                echo aduCelda(aduVal($f['vals'], 2), $mostrarCeros);   // F = CONTI
                break;

            case 'AOE':
                $esI = ($f['label2'] === 'I');
                echo '<td class="' . ($esI ? 'adu-niv2' : 'adu-niv1') . '" colspan="3">' . $lbl . '</td>';
                echo '<td class="text-center fw-bold ' . ($esI ? 'text-warning' : 'text-success') . '">'
                   . htmlspecialchars($f['label2']) . '</td>';
                echo '<td colspan="2">' . aduCelda($f['total'], $mostrarCeros) . '</td>';
                break;

            case 'SES':
                echo '<td class="adu-niv1" colspan="4">' . $lbl . '</td>';
                echo aduCelda(aduVal($f['vals'], 'num'), $mostrarCeros);    // D = Nº
                echo aduCelda(aduVal($f['vals'], 'part'), $mostrarCeros);   // E = Part/Benef
                break;

            case 'TOT':
                echo '<td class="adu-niv1" colspan="5">' . $lbl . '</td>';
                echo '<td colspan="1">' . aduCelda($f['total'], $mostrarCeros) . '</td>';
                break;
        }
        echo '</tr>';

        // Nota de la fila (pie de auditoria, p.ej. filas 156/157 del raspaje)
        if ($verSQL && !empty($f['nota'])) {
            echo '<tr class="adu-nota"><td colspan="6"><i class="fas fa-info-circle me-1 text-muted"></i>'
               . htmlspecialchars($f['nota']) . '</td></tr>';
        }
        // Panel "Ver SQL"
        if ($verSQL && ($f['cond'] !== null || $f['ramas'] !== null)) {
            $sqls = [];
            if ($f['ramas'] !== null) {
                foreach ($f['ramas'] as $ri => $rc) $sqls[] = '-- rama ' . ($ri + 1) . " (INSERT del SP)\n" . aduCondicionSQL($rc);
            } elseif ($f['cond'] !== null) {
                $sqls[] = aduCondicionSQL($f['cond']);
            }
            if ($sqls) {
                echo '<tr class="adu-sqlrow"><td colspan="6"><div class="adu-cond">'
                   . htmlspecialchars(implode("\n\n", $sqls))
                   . ($f['regla'] ? "\n\n-- regla: " . aduEtiquetaRegla($f['regla']) : '')
                   . '</div></td></tr>';
            }
        }
    }

    // ---- Pie de seccion: fila TOTAL del bloque de metodos (E115) ----
    if ($layout === 'PFM') {
        $totNuevas = 0;
        foreach ($sec['filas'] as $f) {
            if ($f['label2'] === 'A') $totNuevas += aduVal($f['vals'], 1);
        }
        echo '<tr class="table-light fw-bold">'
           . '<td colspan="4">TOTAL — nuevas usuarias (E115 del Excel)</td>'
           . aduCelda($totNuevas, $mostrarCeros)
           . '<td class="text-muted">—</td></tr>';
    }

    echo '</tbody></table></div>';

    // ---- Nota de la seccion ----
    if (!empty($sec['nota'])) {
        echo '<div class="p-2 px-3 small text-muted adu-secnota"><i class="fas fa-info-circle me-1"></i>'
           . htmlspecialchars($sec['nota']) . '</div>';
    }
    if (!empty($sec['nota_total'])) {
        echo '<div class="p-2 px-3 small text-muted adu-secnota"><i class="fas fa-calculator me-1"></i>'
           . htmlspecialchars($sec['nota_total']) . '</div>';
    }
}

/* ============================================================
 * MAPA DE CELDAS PARA EL EXPORT (plantilla oficial)
 * ============================================================ */

/**
 * Construye el mapa [celda Excel => valor] para llenar la plantilla oficial
 * "uploads/Reporte_Actividades_Adulto.xlsx" (hoja "Plantilla"), con el
 * mismo layout que el flujo ODBC original.
 *
 * Encabezado:
 *   B5 = PERIODO ("JUNIO 2026" | "2026" | "TODOS LOS PERIODOS")
 *   B7 = CODIGO RENAES (Codigo_Unico; vacio con "-- Todos --")
 *   D5 = "CHANCHAMAYO" (fija en la plantilla) ; D7 (D7:G7) = EE.SS o TODOS
 *
 * Datos (celda = columna del layout + campo 'fila' de cada fila):
 *   FMT: D=F(mujeres) E=M(hombres) F=TOTAL
 *   TOT: D=TOTAL                       (bloque 17)
 *   PFM: fila A/I de cada metodo -> E=NUEVO, F=CONTI; y E115 = TOTAL
 *        de nuevas usuarias de los 13 metodos (suma calculada: el SP no
 *        produce una fila TOTAL)
 *   AOE: fila A/I -> E=cifra
 *   SES: D=Nº, E=Part/Benef
 */
function aduCeldasExport(array $reporte, string $anioTxt, string $mesTxt, string $estTxt, string $estCod = ''): array {
    $cells = [];

    // ---- Cabecera de la plantilla ----
    // A5='PERIODO' -> B5 = "MES AÑO" (o solo el año, o TODOS)
    if ($anioTxt !== '' && $mesTxt !== '') {
        $cells['B5'] = strtoupper(getNombreMes((int)$mesTxt)) . ' ' . $anioTxt;
    } elseif ($anioTxt !== '') {
        $cells['B5'] = (string)$anioTxt;
    } else {
        $cells['B5'] = 'TODOS LOS PERIODOS';
    }
    // A7='CODIGO RENAES' -> B7 = Codigo_Unico RENAES
    // (vacio cuando el filtro es "-- Todos --": no existe un unico codigo)
    if ($estCod !== '') {
        $cells['B7'] = $estCod;
    }
    // C7='ESTABLECIMIENTO' -> D7 (combinada D7:G7) = nombre o TODOS
    $cells['D7'] = $estTxt !== '' ? $estTxt : 'TODOS';

    foreach ($reporte['secciones'] as $sec) {
        $layout = $sec['layout'];

        foreach ($sec['filas'] as $f) {
            $row = $f['fila'] ?? null;
            if ($row === null) continue;
            $v = $f['vals'];

            switch ($layout) {
                case 'FMT':
                    $cells['D' . $row] = aduVal($v, 1);        // F (mujeres)
                    $cells['E' . $row] = aduVal($v, 2);        // M (hombres)
                    $cells['F' . $row] = $f['total'];          // TOTAL
                    break;

                case 'TOT':
                    $cells['D' . $row] = $f['total'];          // TOTAL (bloque 17)
                    break;

                case 'PFM':                                    // 06_2: E=NUEVO, F=CONTI
                    $cells['E' . $row] = aduVal($v, 1);
                    $cells['F' . $row] = aduVal($v, 2);
                    break;

                case 'AOE':                                    // 06_3: E = cifra (fila A/I)
                    $cells['E' . $row] = $f['total'];
                    break;

                case 'SES':                                    // 16: D=Nº, E=Part/Benef
                    $cells['D' . $row] = aduVal($v, 'num');
                    $cells['E' . $row] = aduVal($v, 'part');
                    break;
            }
        }

        // Fila TOTAL del bloque de metodos (E115 del Excel): suma de nuevas
        // usuarias (Actividad, Usuarias=1) de los 13 metodos.
        if ($layout === 'PFM') {
            $totNuevas = 0;
            foreach ($sec['filas'] as $f) {
                if (($f['label2'] ?? '') === 'A') $totNuevas += aduVal($f['vals'], 1);
            }
            $cells['E115'] = $totNuevas;
        }
    }
    return $cells;
}
