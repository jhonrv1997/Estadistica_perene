<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Modulo ITS/VIH - Funciones de render del reporte web (layout tipo Excel).
 *
 * Renderizan las secciones devueltas por itsEjecutarReporte() con el mismo
 * diseno de la plantilla oficial "Reporte_Actividades_ITS.xlsx" (hoja
 * "Plantilla"): 16 bloques con F/M x etapas, grupos de poblacion clave,
 * trimestres, brigadas y recien nacidos.
 *
 * itsCeldasExport() construye el mapa [celda => valor] para llenar la
 * plantilla oficial con el mismo layout que el flujo ODBC original:
 *   Encabezado: K6 = AÑO, K8 = MES (K8:L8 combinada), C8 = ESTABLECIMIENTO
 *   (C8:G8 combinada), I6 = IPRESS.
 *   Datos: por cada fila de reporte, celdas segun el layout de su seccion
 *   (el campo 'fila' de cada fila + el mapa de columnas por layout).
 */
require_once __DIR__ . '/its_data.php';

/* ============================================================
 * UTILIDADES DE RENDER
 * ============================================================ */

/** Letra de columna Excel a partir de su numero (1=A, 2=B, ..., 27=AA). */
function itsColLetra(int $n): string {
    $s = '';
    while ($n > 0) {
        $m = ($n - 1) % 26;
        $s = chr(65 + $m) . $s;
        $n = intdiv($n - $m - 1, 26);
    }
    return $s;
}

/** Descripcion legible de la regla de conteo (para auditoria). */
function itsEtiquetaRegla(string $regla): string {
    switch ($regla) {
        case 'filas': return 'count(*) del T-SQL (cada fila HIS que cumple)';
        case 'citas': return 'count(distinct id_cita) del T-SQL (fila rownum=1 de cada cita: ATENDIDOS/ATENCIONES)';
    }
    return '';
}

/** Valor formateado de una celda (0 en gris si la seccion no tiene datos). */
function itsCelda($n, bool $ceroGris): string {
    $n = (float)$n;
    $txt = ($n == (int)$n) ? number_format((int)$n) : number_format($n, 1);
    if ($n == 0) return '<td class="num ' . ($ceroGris ? 'its-cero' : 'text-muted') . '">0</td>';
    return '<td class="num fw-bold text-primary">' . $txt . '</td>';
}

/** Total de una fila segun layout (suma de todas las celdas de sus vals). */
function itsTotalFila(array $fr): int {
    return (int)($fr['total'] ?? 0);
}

/* ============================================================
 * RENDERIZADO DE SECCIONES (layout tipo Excel oficial)
 * ============================================================ */

/**
 * Renderiza una seccion del reporte ITS con el layout del Excel oficial.
 * Las filas se muestran en el ORDEN EXACTO de la plantilla (campo 'fila').
 */
function itsRenderSeccion(array $sec, bool $mostrarCeros, bool $verSQL): void {
    $layout = $sec['layout'];
    $etapas = $sec['etapas'];
    $eLbl = itsEtapaLabels($etapas);
    $gLbl = itsGrupoLabels();
    $regla = $sec['regla'];

    $filas = $sec['filas'];
    if (!$mostrarCeros) {
        $filas = array_values(array_filter($filas, fn($f) => ($f['total'] ?? 0) > 0));
        if (!$filas) {
            echo '<div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta sección con los filtros actuales.</div>';
            return;
        }
    }

    echo '<div class="table-responsive"><table class="table table-sm table-hover its-table mb-0">';
    echo '<thead>';

    switch ($layout) {
        case 'FMC3': // Seccion 1: F/M x 5 etapas x 3 condiciones + 6 totales
            $conds = ['1' => 'DIAGNOSTICO', '2' => 'TRATADO', '3' => 'CONTACTO'];
            echo '<tr><th class="text-start its-th-label" rowspan="3" style="min-width:280px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-m" colspan="15">F</th><th class="its-th-m" colspan="15">M</th>';
            echo '<th class="its-th-total" colspan="6">Total</th></tr>';
            echo '<tr>';
            foreach (['F', 'M'] as $sx) {
                foreach ($eLbl as $lbl) echo '<th class="its-th-gedad" colspan="3">' . $lbl . '</th>';
            }
            foreach ($conds as $cl) echo '<th class="its-th-total" colspan="2">' . $cl . '</th>';
            echo '</tr><tr>';
            foreach (['F', 'M'] as $sx) {
                foreach ($eLbl as $lbl) {
                    echo '<th class="its-th-m">D</th><th class="its-th-m">T</th><th class="its-th-m">C</th>';
                }
            }
            foreach ($conds as $cl) echo '<th class="its-th-m">F</th><th class="its-th-m">M</th>';
            echo '</tr>';
            break;

        case 'FM5': // F/M x 5 etapas + F + M + Total (secciones 2..6)
        case 'FM5T': // F/M x 5 etapas + F + M (secciones 11 y 12)
            echo '<tr><th class="text-start its-th-label" rowspan="2" style="min-width:280px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-m" colspan="5">F</th><th class="its-th-m" colspan="5">M</th>';
            if ($layout === 'FM5') echo '<th class="its-th-total" colspan="3">Total</th>';
            else echo '<th class="its-th-total" colspan="2">TOTAL</th>';
            echo '</tr><tr>';
            foreach (['F', 'M'] as $sx) foreach ($eLbl as $lbl) echo '<th class="its-th-gedad">' . $lbl . '</th>';
            echo '<th class="its-th-total">F</th><th class="its-th-total">M</th>';
            if ($layout === 'FM5') echo '<th class="its-th-total">Total</th>';
            echo '</tr>';
            break;

        case 'GES4': // 7a: 4 etapas + Total (solo gestantes)
            echo '<tr><th class="text-start its-th-label" rowspan="2" style="min-width:280px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-m" colspan="4">GESTANTES</th><th class="its-th-total" rowspan="2">Total</th></tr>';
            echo '<tr>';
            foreach ($eLbl as $lbl) echo '<th class="its-th-gedad">' . $lbl . '</th>';
            echo '</tr>';
            break;

        case 'TRI3': // 7b: 3 trimestres + Total
            echo '<tr><th class="text-start its-th-label" rowspan="2" style="min-width:280px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-m" colspan="3">GESTANTES POR TRIMESTRE</th><th class="its-th-total" rowspan="2">Total</th></tr>';
            echo '<tr>';
            foreach (['1ER TRIM.', '2DO TRIM', '3ER TRIM'] as $lbl) echo '<th class="its-th-gedad">' . $lbl . '</th>';
            echo '</tr>';
            break;

        case 'RN1': // 7A: solo Total
            echo '<tr><th class="text-start its-th-label" style="min-width:280px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-total">Total</th></tr>';
            break;

        case 'PC5': // 8/9a: 5 grupos x 3 etapas + 5 totales
        case 'PC5C':
            echo '<tr><th class="text-start its-th-label" rowspan="2" style="min-width:280px;">DIAGNÓSTICO</th>';
            foreach ($gLbl as $lbl) echo '<th class="its-th-m" colspan="3">' . $lbl . '</th>';
            echo '<th class="its-th-total" colspan="5">' . ($sec['codigo'] === 'ITS08_POBLACION_CLAVE' ? 'Total' : 'TRATADOS') . '</th></tr>';
            echo '<tr>';
            foreach ($gLbl as $lbl) foreach ($eLbl as $el) echo '<th class="its-th-gedad">' . $el . '</th>';
            foreach ($gLbl as $lbl) echo '<th class="its-th-total">' . $lbl . '</th>';
            echo '</tr>';
            break;

        case 'CC2': // 9b: 2 columnas
            echo '<tr><th class="text-start its-th-label" style="min-width:280px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-total">CONTACTOS</th><th class="its-th-total">CONTACTOS TRATADOS</th></tr>';
            break;

        case 'BRIG10': // 10: COMUNITARIA + MOVIL x 5 grupos x 4 etapas
            echo '<tr><th class="text-start its-th-label" rowspan="3" style="min-width:260px;">DIAGNÓSTICO</th>';
            echo '<th class="its-th-total" colspan="20">OFERTA COMUNITARIA (MCC)</th>';
            echo '<th class="its-th-total" colspan="20">OFERTA MÓVIL (BMU)</th></tr>';
            echo '<tr>';
            foreach ([0, 1] as $bloque) {
                foreach ([1, 2, 3, 5, 4] as $g) echo '<th class="its-th-m" colspan="4">' . $gLbl[$g] . '</th>';
            }
            echo '</tr><tr>';
            foreach ([0, 1] as $bloque) {
                foreach ([1, 2, 3, 5, 4] as $g) foreach ($eLbl as $el) echo '<th class="its-th-gedad">' . $el . '</th>';
            }
            echo '</tr>';
            break;
    }
    echo '</thead><tbody>';

    $esCero = ($sec['total'] ?? 0) == 0;
    foreach ($filas as $f) {
        echo '<tr>';
        // La etiqueta es estatica del motor (sin input de usuario)
        echo '<td class="its-niv1">' . $f['label'] . '</td>';

        switch ($layout) {
            case 'FMC3':
                $v = $f['vals'];
                foreach (['F', 'M'] as $sx) {
                    foreach (range(1, 5) as $e) {
                        foreach ([1, 2, 3] as $c) {
                            echo itsCelda(itsVal($v[$c] ?? [], $sx, $e), $esCero);
                        }
                    }
                }
                foreach ([1, 2, 3] as $c) {
                    $tF = array_sum(array_map(fn($e) => itsVal($v[$c] ?? [], 'F', $e), range(1, 5)));
                    $tM = array_sum(array_map(fn($e) => itsVal($v[$c] ?? [], 'M', $e), range(1, 5)));
                    echo itsCelda($tF, $esCero);
                    echo itsCelda($tM, $esCero);
                }
                break;

            case 'FM5':
            case 'FM5T':
                $v = $f['vals'];
                foreach (['F', 'M'] as $sx) {
                    foreach (range(1, 5) as $e) {
                        echo itsCelda(itsVal($v, $sx, $e), $esCero);
                    }
                }
                $tF = array_sum(array_map(fn($e) => itsVal($v, 'F', $e), range(1, 5)));
                $tM = array_sum(array_map(fn($e) => itsVal($v, 'M', $e), range(1, 5)));
                echo itsCelda($tF, $esCero);
                echo itsCelda($tM, $esCero);
                if ($layout === 'FM5') echo itsCelda($tF + $tM, $esCero);
                break;

            case 'GES4':
                $v = $f['vals'];
                foreach (range(1, 4) as $e) echo itsCelda(itsVal($v, $e), $esCero);
                echo itsCelda(itsTotalFila($f), $esCero);
                break;

            case 'TRI3':
                $v = $f['vals'];
                foreach (range(1, 3) as $t) echo itsCelda(itsVal($v, $t), $esCero);
                echo itsCelda(itsTotalFila($f), $esCero);
                break;

            case 'RN1':
                echo itsCelda(itsTotalFila($f), $esCero);
                break;

            case 'PC5':
            case 'PC5C':
                $v = $f['vals'];
                if ($sec['codigo'] === 'ITS08_POBLACION_CLAVE') {
                    foreach (range(1, 5) as $g) {
                        foreach (range(1, 3) as $e) echo itsCelda(itsVal($v, $g, $e), $esCero);
                    }
                    foreach (range(1, 5) as $g) {
                        echo itsCelda(array_sum(array_map(fn($e) => itsVal($v, $g, $e), range(1, 3))), $esCero);
                    }
                } else { // 9a: DIAGNOSTICOS (B-P) + TRATADOS (Q-U)
                    $v1 = $v[1] ?? [];
                    $v2 = $v[2] ?? [];
                    foreach (range(1, 5) as $g) {
                        foreach (range(1, 3) as $e) echo itsCelda(itsVal($v1, $g, $e), $esCero);
                    }
                    foreach (range(1, 5) as $g) {
                        echo itsCelda(array_sum(array_map(fn($e) => itsVal($v2, $g, $e), range(1, 3))), $esCero);
                    }
                }
                break;

            case 'CC2':
                $v = $f['vals'];
                echo itsCelda(array_sum(array_map(fn($g) => itsVal($v[1] ?? [], $g), range(1, 5))), $esCero);
                echo itsCelda(array_sum(array_map(fn($g) => itsVal($v[2] ?? [], $g), range(1, 5))), $esCero);
                break;

            case 'BRIG10':
                $v = $f['vals'];
                foreach ([2, 1] as $b) { // COMUNITARIA (2) primero, MOVIL (1) despues
                    foreach ([1, 2, 3, 5, 4] as $g) {
                        foreach (range(1, 4) as $e) echo itsCelda(itsVal($v, $b, $g, $e), $esCero);
                    }
                }
                break;
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    if ($verSQL) itsPanelCondiciones($sec, $regla);
}

/** Panel colapsable con las condiciones SQL de cada fila (auditoria contra el archivo 03). */
function itsPanelCondiciones(array $sec, string $regla): void {
    echo '<details class="its-cond-panel"><summary class="small px-3 py-2 bg-light border-top" style="cursor:pointer;">
          <i class="fas fa-code me-1 text-secondary"></i>Condiciones SQL de esta sección (auditoría contra el archivo 03) &mdash;
          <code>' . htmlspecialchars($sec['procedimiento']) . '</code> &middot; regla: <code>' . htmlspecialchars($regla) . '</code>
          (' . itsEtiquetaRegla($regla) . ')</summary>
          <div class="p-2 px-3 bg-white border-top">';
    foreach ($sec['filas'] as $f) {
        echo '<div class="mb-2"><strong class="text-dark">' . htmlspecialchars($f['label']) . '</strong>';
        $conds = $f['conds'] ?? ($f['cond'] !== null ? ['' => $f['cond']] : []);
        if (!empty($conds)) {
            foreach ($conds as $ck => $cond) {
                $titulo = '';
                if ($sec['codigo'] === 'ITS01_POBLACION_GENERAL') $titulo = $ck == 1 ? 'DIAGNOSTICO' : ($ck == 2 ? 'TRATADO' : 'CONTACTO');
                elseif (isset($f['conds'])) $titulo = $ck == 1 ? 'DIAGNOSTICOS/CONTACTOS' : ($ck == 2 ? 'TRATADOS' : '');
                if ($titulo !== '') echo ' <span class="badge bg-secondary">' . htmlspecialchars($titulo) . '</span>';
                echo '<div class="its-cond">' . htmlspecialchars(itsCondicionSQL($cond)) . '</div>';
            }
        } else {
            echo '<div class="its-cond">sin consulta: el SP no produce datos para esta fila (queda en 0, como el flujo ODBC original)</div>';
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
 * "Reporte_Actividades_ITS.xlsx" a partir del reporte ejecutado.
 *
 * Layouts (hoja "Plantilla", igual que el flujo ODBC original):
 *   FMC3  (s.1,  filas 13-25):  F B/C/D+E/F/G+... 3 cols por etapa (D/T/C);
 *          M Q/S/T...; totales AF-AK (F/M por condicion)
 *   FM5   (s.2-6, filas 29-92): F B-F; M G-K; L=F; M=M; N=Total
 *   FM5T  (s.11-12, 188-203):  F B-F; M G-K; L=F; M=M
 *   GES4  (s.7a, 95-105):       B-E etapas; F=Total
 *   TRI3  (s.7b, 108-118):      B-D trimestres; E=Total
 *   RN1   (s.7A, 121-130):      B=Total
 *   PC5   (s.8, 134-145):       grupos B-P (3 etapas); totales Q-U
 *   PC5C  (s.9a, 149-159):      DIAGNOSTICOS grupos B-P; TRATADOS Q-U
 *   CC2   (s.9b, 162-172):      B=CONTACTOS; C=CONTACTOS TRATADOS
 *   BRIG10 (s.10, 177-184):     COMUNITARIA B-U (5 grupos x 4 etapas,
 *          orden HSH/TRANS/TS-FEM/TS-H/TS-TRANS); MOVIL V-AO
 *
 * @param array  $reporte  Reporte ejecutado (itsEjecutarReporte)
 * @param string $anioTxt  Año (K6)
 * @param int    $mesTxt   Mes numerico (K8) o '' (todos)
 * @param string $estTxt   Nombre del establecimiento (C8) o ''
 * @param string $ipressTxt Nombre del IPRESS (I6) o ''
 * @return array<string,int|string|float> Mapa [refCelda => valor]
 */
function itsCeldasExport(array $reporte, string $anioTxt, string $mesTxt, string $estTxt, string $ipressTxt): array {
    $cells = [];
    // Cabecera de la plantilla
    if ($anioTxt !== '' && is_numeric($anioTxt)) $cells['K6'] = (int)$anioTxt;
    if ($mesTxt !== '') $cells['K8'] = (int)$mesTxt;
    if ($estTxt !== '') $cells['C8'] = $estTxt;
    if ($ipressTxt !== '') $cells['I6'] = $ipressTxt;

    foreach ($reporte['secciones'] as $sec) {
        $layout = $sec['layout'];
        $es9a = $sec['codigo'] === 'ITS09_1_PC_ITS_DX_TRAT';
        $es8 = $sec['codigo'] === 'ITS08_POBLACION_CLAVE';

        foreach ($sec['filas'] as $f) {
            $row = $f['fila'] ?? null;
            if ($row === null) continue;
            $v = $f['vals'];

            switch ($layout) {
                case 'FMC3':
                    foreach (['F', 'M'] as $si => $sx) {
                        $base = $si === 0 ? 2 : 17; // B / Q
                        foreach (range(1, 5) as $e) {
                            foreach ([1, 2, 3] as $c) {
                                $cells[itsColLetra($base + 3 * ($e - 1) + ($c - 1)) . $row] = itsVal($v[$c] ?? [], $sx, $e);
                            }
                        }
                    }
                    foreach ([1, 2, 3] as $c) {
                        $tF = array_sum(array_map(fn($e) => itsVal($v[$c] ?? [], 'F', $e), range(1, 5)));
                        $tM = array_sum(array_map(fn($e) => itsVal($v[$c] ?? [], 'M', $e), range(1, 5)));
                        $cells[itsColLetra(32 + ($c - 1) * 2) . $row] = $tF;       // AF/AH/AJ
                        $cells[itsColLetra(33 + ($c - 1) * 2) . $row] = $tM;       // AG/AI/AK
                    }
                    break;

                case 'FM5':
                case 'FM5T':
                    foreach (['F', 'M'] as $si => $sx) {
                        $base = $si === 0 ? 2 : 7; // B / G
                        foreach (range(1, 5) as $e) {
                            $cells[itsColLetra($base + ($e - 1)) . $row] = itsVal($v, $sx, $e);
                        }
                    }
                    $tF = array_sum(array_map(fn($e) => itsVal($v, 'F', $e), range(1, 5)));
                    $tM = array_sum(array_map(fn($e) => itsVal($v, 'M', $e), range(1, 5)));
                    $cells['L' . $row] = $tF;
                    $cells['M' . $row] = $tM;
                    if ($layout === 'FM5') $cells['N' . $row] = $tF + $tM;
                    break;

                case 'GES4':
                    foreach (range(1, 4) as $e) {
                        $cells[itsColLetra(2 + ($e - 1)) . $row] = itsVal($v, $e);
                    }
                    $cells['F' . $row] = itsTotalFila($f);
                    break;

                case 'TRI3':
                    foreach (range(1, 3) as $t) {
                        $cells[itsColLetra(2 + ($t - 1)) . $row] = itsVal($v, $t);
                    }
                    $cells['E' . $row] = itsTotalFila($f);
                    break;

                case 'RN1':
                    $cells['B' . $row] = itsTotalFila($f);
                    break;

                case 'PC5':
                case 'PC5C':
                    if ($es8) {
                        foreach (range(1, 5) as $g) {
                            foreach (range(1, 3) as $e) {
                                $cells[itsColLetra(2 + 3 * ($g - 1) + ($e - 1)) . $row] = itsVal($v, $g, $e);
                            }
                            $cells[itsColLetra(17 + ($g - 1)) . $row] = array_sum(array_map(fn($e) => itsVal($v, $g, $e), range(1, 3)));
                        }
                    } else { // 9a: DIAGNOSTICOS B-P + TRATADOS Q-U
                        $v1 = $v[1] ?? [];
                        $v2 = $v[2] ?? [];
                        foreach (range(1, 5) as $g) {
                            foreach (range(1, 3) as $e) {
                                $cells[itsColLetra(2 + 3 * ($g - 1) + ($e - 1)) . $row] = itsVal($v1, $g, $e);
                            }
                            $cells[itsColLetra(17 + ($g - 1)) . $row] = array_sum(array_map(fn($e) => itsVal($v2, $g, $e), range(1, 3)));
                        }
                    }
                    break;

                case 'CC2':
                    $cells['B' . $row] = array_sum(array_map(fn($g) => itsVal($v[1] ?? [], $g), range(1, 5)));
                    $cells['C' . $row] = array_sum(array_map(fn($g) => itsVal($v[2] ?? [], $g), range(1, 5)));
                    break;

                case 'BRIG10':
                    foreach ([2 => 2, 1 => 22] as $b => $base) { // COMUNITARIA=B, MOVIL=V
                        foreach ([1, 2, 3, 5, 4] as $pi => $g) {
                            foreach (range(1, 4) as $e) {
                                $cells[itsColLetra($base + 4 * $pi + ($e - 1)) . $row] = itsVal($v, $b, $g, $e);
                            }
                        }
                    }
                    break;
            }
        }
    }
    return $cells;
}
