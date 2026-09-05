<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo MATERNO - Funciones de render del reporte web (layout tipo Excel).
 *
 * Renderizan las secciones devueltas por maternoEjecutarReporte() con el
 * mismo diseno de la plantilla oficial "Reporte_Actividades_Materno.xlsx":
 *   - eje 'gedad'    : filas = grupos etareos (4 + TOTAL), columnas = categorias
 *   - eje 'categoria': filas = categorias, columnas = TOTAL (inicio, o al
 *     final con 'totalPos' 'fin' — VIII. VISITA DOMICILIARIA) + grupos etareos
 * Incluye el panel de auditoria "Condiciones SQL" (ver reporte_materno.php).
 *
 * CAMBIO 2026-09-05 r2: mtrRenderSeccionCategoria() soporta la opcion por
 * seccion 'totalPos' => 'fin' para pintar la columna TOTAL a la derecha del
 * ultimo grupo etareo (VIII: Total = 12-17 + 18-29 + 30-59). Sin esa opcion
 * el comportamiento es identico al anterior (TOTAL tras la etiqueta, como
 * en la seccion IV).
 */
require_once __DIR__ . '/materno_data.php';

// ====== RENDERIZADO DE SECCIONES (layout tipo Excel oficial) ======

/** Etiquetas de los grupos etareos (DimMaterno2023_Gedad). */
function mtrGedadLabel(int $g): string {
    return [1 => '<12 a.', 2 => '12 - 17 a.', 3 => '18 - 29 a.', 4 => '30 - 59 a.'][$g] ?? (string)$g;
}

/** Descripcion legible de una regla de conteo (para auditoria). */
function mtrEtiquetaRegla(array $regla): string {
    switch ($regla['tipo'] ?? 'simple') {
        case 'filas':
            return 'cada fila HIS que cumple (count(*) del T-SQL)';
        case 'simple':
            return 'cada cita que cumple';
        case 'trimestre':
            return ($regla['oc'] ?? 1) . '-ésima cita, trimestre ' . ['I', 'II', 'III'][(int)$regla['n'] - 1] . ' (por FUR)';
        case 'ocurrencia':
            return $regla['n'] . '-ésima cita del paciente';
        case 'ocurrenciaMin':
            return 'pacientes con >= ' . $regla['n'] . ' citas (cuenta la ' . $regla['n'] . '-ésima)';
        case 'conteoMinimo':
            return 'pacientes con >= ' . $regla['n'] . ' citas (cuentan 1 vez)';
        case 'calc':
            return 'calculada = suma de ' . implode(' + ', $regla['cols']);
    }
    return '';
}

/** Valor formateado de una celda (0 en gris si la seccion no tiene datos). */
function mtrCelda($n, bool $ceroGris): string {
    $n = (int)$n;
    if ($n === 0) return '<td class="num ' . ($ceroGris ? 'mtr-cero' : 'text-muted') . '">0</td>';
    return '<td class="num fw-bold text-primary">' . number_format($n) . '</td>';
}

/**
 * Renderiza una seccion del reporte con el layout del Excel oficial.
 *   - eje 'gedad'    : filas = grupos etareos (+ TOTAL), columnas = categorias
 *   - eje 'categoria': filas = categorias, columnas = TOTAL + grupos etareos.
 *     La columna TOTAL puede ir al inicio (defecto, como la seccion IV y la
 *     plantilla oficial) o al final con 'totalPos' => 'fin' (VIII. VISITA
 *     DOMICILIARIA: Total = "12 - 17 a." + "18 - 29 a." + "30 - 59 a.").
 */
function mtrRenderSeccion(array $sec, bool $mostrarCeros, bool $verSQL): void {
    if (($sec['eje'] ?? 'gedad') === 'gedad') {
        mtrRenderSeccionGedad($sec, $mostrarCeros, $verSQL);
    } else {
        mtrRenderSeccionCategoria($sec, $mostrarCeros, $verSQL);
    }
}

/** Secciones con filas = grupos etareos (I, II, III, VI, VII, IX-1, IX-2, IX-3, X). */
function mtrRenderSeccionGedad(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $gedades = $sec['gedades'];

    // Filtrar columnas en 0 si no se piden (se conserva la columna Total/calc si
    // alguna de sus fuentes tiene datos)
    $cols = $sec['columnas'];
    if (!$mostrarCeros) {
        $conDatos = [];
        foreach ($cols as $c) {
            if (!empty($c['calc'])) {
                $s = 0;
                foreach ($cols as $cc) if (in_array($cc['key'], $c['calc'])) $s += $cc['total'];
                if ($s > 0) $conDatos[] = $c;
            } elseif ($c['total'] > 0) {
                $conDatos[] = $c;
            }
        }
        if (!$conDatos) {
            echo '<div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta secci&oacute;n con los filtros actuales.</div>';
            return;
        }
        $cols = $conDatos;
    }

    // Cabecera multinivel: nivel 1 (grupo) y nivel 2 (subgrupo) con colspan de
    // columnas CONSECUTIVAS que comparten etiqueta; nivel 3 = categoria.
    echo '<div class="table-responsive"><table class="table table-sm table-hover mtr-table mb-0"><thead>';
    // Nivel 1
    echo '<tr><th rowspan="3" class="text-start mtr-gedad-label" style="min-width:110px;">Grupo Et&aacute;reo</th>';
    $i = 0; $n = count($cols);
    while ($i < $n) {
        $j = $i;
        while ($j < $n && $cols[$j]['niv1'] === $cols[$i]['niv1']) $j++;
        echo '<th colspan="' . ($j - $i) . '" class="' . (($cols[$i]['niv3'] ?? '') === 'Total' ? 'mtr-col-calc' : '') . '">' . htmlspecialchars($cols[$i]['niv1']) . '</th>';
        $i = $j;
    }
    echo '</tr>';
    // Nivel 2
    echo '<tr>';
    $i = 0;
    while ($i < $n) {
        $j = $i;
        while ($j < $n && $cols[$j]['niv1'] === $cols[$i]['niv1'] && $cols[$j]['niv2'] === $cols[$i]['niv2']) $j++;
        echo '<th colspan="' . ($j - $i) . '">' . htmlspecialchars($cols[$i]['niv2']) . '</th>';
        $i = $j;
    }
    echo '</tr>';
    // Nivel 3
    echo '<tr>';
    foreach ($cols as $c) {
        echo '<th class="' . (($c['niv3'] ?? '') === 'Total' ? 'mtr-col-calc' : '') . '">' . htmlspecialchars($c['niv3'] ?? '') . '</th>';
    }
    echo '</tr></thead><tbody>';

    // Filas de grupos etareos + TOTAL (como el Excel)
    $esCero = ($sec['total'] ?? 0) == 0;
    foreach ($gedades as $g) {
        echo '<tr><td class="mtr-gedad-label">' . htmlspecialchars(mtrGedadLabel($g)) . '</td>';
        foreach ($cols as $c) {
            $v = (int)($c['valores'][$g] ?? 0);
            echo mtrCelda($v, $esCero);
        }
        echo '</tr>';
    }
    echo '<tr class="mtr-total-row"><td>TOTAL</td>';
    foreach ($cols as $c) {
        echo '<td class="num">' . number_format($c['total']) . '</td>';
    }
    echo '</tr></tbody></table></div>';

    if ($verSQL) mtrPanelCondiciones($sec, $cols, 'col');
}

/** Secciones con filas = categorias (IV, V, VIII). */
function mtrRenderSeccionCategoria(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $gedades = $sec['gedades'] ?? [1, 2, 3, 4];
    $soloTotal = !empty($sec['soloTotal']);
    $conTotal = $sec['conTotal'] ?? true;
    // Posicion de la columna Total: 'inicio' (defecto, tras la etiqueta, como
    // la seccion IV y la plantilla oficial) o 'fin' (a la derecha del ultimo
    // grupo etareo; VIII. VISITA DOMICILIARIA: Total = 12-17 + 18-29 + 30-59).
    $totalFin = ($sec['totalPos'] ?? 'inicio') === 'fin';

    echo '<div class="table-responsive"><table class="table table-sm table-hover mtr-table mb-0"><thead><tr>';
    echo '<th class="text-start" style="min-width:230px;">' . htmlspecialchars($sec['columna_label'] ?? 'Categor&iacute;a') . '</th>';
    if (!$soloTotal) {
        if ($conTotal && !$totalFin) echo '<th class="num mtr-col-calc">TOTAL</th>';
        foreach ($gedades as $g) echo '<th class="num">' . htmlspecialchars(mtrGedadLabel($g)) . '</th>';
        if ($conTotal && $totalFin) echo '<th class="num mtr-col-calc">TOTAL</th>';
    } else {
        echo '<th class="num">N&deg;</th>';
    }
    echo '</tr></thead><tbody>';

    $filas = $sec['filas'];
    if (!$mostrarCeros) {
        $filas = array_values(array_filter($filas, fn($f) => $f['total'] > 0));
        if (!$filas) {
            echo '</tbody></table></div><div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta secci&oacute;n con los filtros actuales.</div>';
            return;
        }
    }
    foreach ($filas as $f) {
        echo '<tr><td>' . htmlspecialchars($f['label']) . '</td>';
        if (!$soloTotal) {
            if ($conTotal && !$totalFin) echo '<td class="num fw-bold mtr-col-calc">' . number_format($f['total']) . '</td>';
            foreach ($gedades as $g) {
                $v = (int)($f['valores'][$g] ?? 0);
                echo ($v > 0 ? '<td class="num text-primary">' . number_format($v) . '</td>'
                            : '<td class="num ' . (($sec['total'] ?? 0) == 0 ? 'mtr-cero' : '') . '">0</td>');
            }
            // Total al final = suma de las columnas etareas mostradas a su
            // izquierda (VIII: 12 - 17 a. + 18 - 29 a. + 30 - 59 a.).
            if ($conTotal && $totalFin) echo '<td class="num fw-bold mtr-col-calc">' . number_format($f['total']) . '</td>';
        } else {
            echo '<td class="num fw-bold text-primary">' . number_format($f['total']) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    if ($verSQL) mtrPanelCondiciones($sec, $filas, 'fila');
}

/** Panel colapsable con las condiciones SQL de cada columna/fila (auditoria). */
function mtrPanelCondiciones(array $sec, array $items, string $tipo): void {
    echo '<details class="mtr-cond-panel"><summary class="small px-3 py-2 bg-light border-top" style="cursor:pointer;">
          <i class="fas fa-code me-1 text-secondary"></i>Condiciones SQL de esta secci&oacute;n (auditor&iacute;a contra el archivo 03)</summary>
          <div class="p-2 px-3 bg-white border-top">';
    foreach ($items as $c) {
        $label = $tipo === 'col'
            ? trim(($c['niv1'] ?? '') . ' / ' . ($c['niv2'] ?? '') . ' / ' . ($c['niv3'] ?? ''), ' /')
            : ($c['label'] ?? '');
        echo '<div class="mb-2"><strong class="text-dark">' . htmlspecialchars($label) . '</strong>';
        if (!empty($c['regla'])) {
            echo ' <span class="mtr-regla">[' . htmlspecialchars(mtrEtiquetaRegla($c['regla'])) . ']</span>';
        }
        if (!empty($c['cond'])) {
            echo '<div class="mtr-cond">' . htmlspecialchars(maternoCondicionSQL($c['cond'])) . '</div>';
        } else {
            echo '<div class="mtr-cond">calculada (sin consulta directa)</div>';
        }
        echo '</div>';
    }
    echo '</div></details>';
}

/* ============================================================
 * MAPA DE CELDAS PARA EXPORTAR A LA PLANTILLA OFICIAL
 * ============================================================ */

/** Convierte indice de columna (1=A) a letra Excel (soporta AA..ZZ). */
function mtrColLetra(int $n): string {
    $s = '';
    while ($n > 0) {
        $m = ($n - 1) % 26;
        $s = chr(65 + $m) . $s;
        $n = intdiv($n - $m - 1, 26);
    }
    return $s;
}

/** Letra -> indice numerico (B=2, AA=27). */
function mtrColNumero(string $letra): int {
    $n = 0;
    foreach (str_split($letra) as $ch) {
        $n = $n * 26 + (ord($ch) - 64);
    }
    return $n;
}

/**
 * Construye el mapa [celda => valor] para llenar la plantilla oficial
 * "Reporte_Actividades_Materno.xlsx" a partir del reporte ejecutado
 * (maternoEjecutarReporte / maternoEjecutarDesdeFilas).
 *
 * El mapa posicional (xmap) de cada seccion esta definido en maternoSecciones()
 * y calza 1:1 con las filas/columnas de la plantilla:
 *   - eje 'gedad'    : filas de grupos etareos (1..4 + 'T' TOTAL), columnas
 *                      consecutivas desde xmap['colIni']
 *   - eje 'categoria': filas desde xmap['filaIni'], columnas xmap['colTotal']
 *                      y xmap['colGedad'] (o la unica xmap['colTotal'] en V)
 *
 * @param array      $reporte   Reporte ejecutado (['secciones'=>[...], ...])
 * @param string     $periodo  Texto del periodo (celda B6)
 * @param string     $nombreEst Nombre del EE.SS o 'TODOS LOS ESTABLECIMIENTOS' (celda B8)
 * @return array<string,int|string> Mapa [refCelda => valor]
 */
function maternoCeldasExport(array $reporte, string $periodo, string $nombreEst): array {
    $cells = [];
    $cells['B6'] = $periodo !== '' ? $periodo : 'TODOS LOS PERIODOS';
    $cells['B8'] = $nombreEst !== '' ? $nombreEst : 'TODOS LOS ESTABLECIMIENTOS';

    foreach ($reporte['secciones'] as $sec) {
        $xmap = $sec['xmap'] ?? null;
        if ($xmap === null) continue; // IX-3: sin zona en la plantilla oficial

        if (($sec['eje'] ?? 'gedad') === 'gedad') {
            $colNum = mtrColNumero($xmap['colIni']);
            foreach ($sec['columnas'] as $col) {
                $letra = mtrColLetra($colNum);
                foreach ($xmap['filas'] as $g => $fila) {
                    $cells[$letra . $fila] = ($g === 'T') ? (int)$col['total'] : (int)($col['valores'][$g] ?? 0);
                }
                $colNum++;
            }
        } else {
            $i = 0;
            foreach ($sec['filas'] as $fila) {
                $row = $xmap['filaIni'] + $i;
                if (!empty($sec['soloTotal'])) {
                    $cells[$xmap['colTotal'] . $row] = (int)$fila['total'];
                } else {
                    if (!empty($xmap['colTotal'])) {
                        $cells[$xmap['colTotal'] . $row] = (int)$fila['total'];
                    }
                    foreach ($xmap['colGedad'] as $g => $letra) {
                        if ($letra === null) continue;
                        $cells[$letra . $row] = (int)($fila['valores'][$g] ?? 0);
                    }
                }
                $i++;
            }
        }
    }
    return $cells;
}
