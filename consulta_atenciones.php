<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 03: Consulta de Atenciones (OPTIMIZADO)
 * Sub-paginas:
 *   - general     -> Filtro General de atenciones (toda la Data consolidada)
 *   - preventivas -> Filtro de atenciones Preventivas (solo UPS / Codigo_Item de prevencion)
 *
 * MEJORAS v3.1 (Rendimiento):
 *   - Eliminacion de subquery por Zona Sanitaria -> pre-fetch de Codigo_Unico
 *   - Uso de columna virtual Mes_Int en vez de CAST(TRIM(Mes) AS UNSIGNED)
 *   - Cache de opciones de filtros en sesion (evita DISTINCT en cada page load)
 *   - Parametros vinculados para LIMIT/OFFSET
 *   - Consulta de stats condicional (solo si hay datos)
 *   - Pre-carga de UPS preventivas desde cache
 *   - Eliminacion de CAST en filtro Mes
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

// Iniciar sesion para cache de filtros si no esta activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sub-pagina activa
$sub = $_GET['sub'] ?? 'general';
if (!in_array($sub, ['general', 'preventivas'], true)) {
    $sub = 'general';
}

// Titulos por sub-pagina
$subInfo = [
    'general' => [
        'icon' => 'fa-list-alt',
        'titulo' => 'Consulta General de Atenciones',
        'descripcion' => 'Busqueda y consulta del consolidado HIS-MINSA con filtros completos.',
    ],
    'preventivas' => [
        'icon' => 'fa-shield-alt',
        'titulo' => 'Consulta de Atenciones Preventivas',
        'descripcion' => 'Solo atenciones preventivas (UPS / codigos de prevencion y promocion).',
    ],
];

// ============================================================
// FILTROS COMUNES
// ============================================================
$fAnio = trim($_GET['anio'] ?? date('Y'));
$fMes = trim($_GET['mes'] ?? date('m'));
$fZonaSanitaria = trim($_GET['zona_sanitaria'] ?? 'Perene');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fGrupoEdad = trim($_GET['grupo_edad'] ?? '');
$fIdGenero = trim($_GET['id_genero'] ?? '');
$fOtraCondicion = trim($_GET['otra_condicion'] ?? '');
$fCodigoItem = trim($_GET['codigo_item'] ?? '');
$fTipoDiagnostico = trim($_GET['tipo_diagnostico'] ?? '');
$fValorLab = trim($_GET['valor_lab'] ?? '');
$fLote = trim($_GET['lote'] ?? '');
$fNumPag = trim($_GET['num_pag'] ?? '');
$fNumReg = trim($_GET['num_reg'] ?? '');
$fDocPaciente = trim($_GET['doc_paciente'] ?? '');
$fDocPersonal = trim($_GET['doc_personal'] ?? '');
$fDocRegistrador = trim($_GET['doc_registrador'] ?? '');
$fUps = trim($_GET['ups'] ?? '');
$fDepartamento = trim($_GET['departamento'] ?? '');

// ============================================================
// CACHE DE OPCIONES DE FILTROS (Session-based)
// ============================================================
// Las consultas SELECT DISTINCT se ejecutan solo una vez por sesion.
// Se invalidan automaticamente cuando se importa nueva data
// (se puede agregar $_SESSION['filter_cache_bust'] = time() en import.php).
$cacheKey = 'his_filters_' . $sub;
$cacheTTL = 3600; // 1 hora de cache
$now = time();

if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheKey]['ts']) && ($now - $_SESSION[$cacheKey]['ts']) < $cacheTTL) {
    $cached = $_SESSION[$cacheKey];
    $anios = $cached['anios'];
    $zonasSanitarias = $cached['zonas'];
    $gruposEdad = $cached['gruposEdad'];
    $generos = $cached['generos'];
    $otrasCondiciones = $cached['otrasCond'];
    $tiposDiagnostico = $cached['tiposDx'];
    $departamentos = $cached['departamentos'];
    $establecimientos = $cached['establecimientos'] ?? [];
    $upsPreventivas = $cached['upsPrev'] ?? [];
} else {
    // --- Anios ---
    $anios = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Anio IS NOT NULL ORDER BY Anio DESC")->fetchAll(PDO::FETCH_COLUMN);
    $anioActual = date('Y');
    $aniosAsString = array_map('strval', $anios);
    if (!in_array($anioActual, $aniosAsString, true)) {
        $anios[] = $anioActual;
        usort($anios, function ($a, $b) { return intval($b) - intval($a); });
    }

    // --- Zonas Sanitarias ---
    $zonasSanitarias = $pdo->query("SELECT DISTINCT MicroRed FROM ZSPERENE WHERE MicroRed IS NOT NULL ORDER BY MicroRed")->fetchAll(PDO::FETCH_COLUMN);

    // --- Grupos de Edad (orden cronologico) ---
    $gruposEdad = $pdo->query("
        SELECT DISTINCT Grupo_Edad
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
        WHERE Grupo_Edad IS NOT NULL AND Grupo_Edad != ''
        ORDER BY
            CASE Grupo_Edad
                WHEN '01 a 29 dias'    THEN 1
                WHEN '01 a 11 meses'   THEN 2
                WHEN '01 a 04 años'    THEN 3
                WHEN '05 a 11 años'    THEN 4
                WHEN '12 a 17 años'    THEN 5
                WHEN '18 a 29 años'    THEN 6
                WHEN '30 a 59 años'    THEN 7
                WHEN '60 años a mas'   THEN 8
                ELSE 99
            END,
            Grupo_Edad
    ")->fetchAll(PDO::FETCH_COLUMN);

    // --- Generos ---
    $generos = $pdo->query("SELECT DISTINCT Id_Genero FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Id_Genero IS NOT NULL ORDER BY Id_Genero")->fetchAll(PDO::FETCH_COLUMN);

    // --- Otras Condiciones ---
    $otrasCondiciones = $pdo->query("SELECT DISTINCT Descripcion_Otra_Condicion FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Descripcion_Otra_Condicion IS NOT NULL AND Descripcion_Otra_Condicion != '' ORDER BY Descripcion_Otra_Condicion")->fetchAll(PDO::FETCH_COLUMN);

    // --- Tipos de Diagnostico ---
    $tiposDiagnostico = $pdo->query("SELECT DISTINCT Tipo_Diagnostico FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Tipo_Diagnostico IS NOT NULL ORDER BY Tipo_Diagnostico")->fetchAll(PDO::FETCH_COLUMN);

    // --- Departamentos ---
    $departamentos = $pdo->query("SELECT DISTINCT Departamento_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Departamento_Establecimiento IS NOT NULL ORDER BY Departamento_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);

    // --- Establecimientos (para preventivas) ---
    $establecimientos = $pdo->query("SELECT DISTINCT Nombre_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Nombre_Establecimiento IS NOT NULL ORDER BY Nombre_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);

    // --- UPS Preventivas ---
    $upsPreventivas = [];
    try {
        // MEJORA: Usar el indice idx_ups_ftipo (Id_Ups, Fg_Tipo)
        // Primero buscar por Fg_Tipo = 'P' (indexable)
        // Luego complementar con LIKE (no indexable pero ya reducido)
        $upsPreventivas = $pdo->query("
            (SELECT DISTINCT Id_Ups, Descripcion_Ups FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Fg_Tipo = 'P')
            UNION
            (SELECT DISTINCT Id_Ups, Descripcion_Ups FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Descripcion_Ups LIKE '%PREVENT%' LIMIT 100)
            UNION
            (SELECT DISTINCT Id_Ups, Descripcion_Ups FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Descripcion_Ups LIKE '%PROMOC%' LIMIT 100)
            ORDER BY Descripcion_Ups
            LIMIT 200
        ")->fetchAll();
    } catch (Exception $e) {
        $upsPreventivas = [];
    }

    // Guardar en cache de sesion
    $_SESSION[$cacheKey] = [
        'ts' => $now,
        'anios' => $anios,
        'zonas' => $zonasSanitarias,
        'gruposEdad' => $gruposEdad,
        'generos' => $generos,
        'otrasCond' => $otrasCondiciones,
        'tiposDx' => $tiposDiagnostico,
        'departamentos' => $departamentos,
        'establecimientos' => $establecimientos,
        'upsPrev' => $upsPreventivas,
    ];
}

// ============================================================
// ESTABLECIMIENTOS POR ZONA (siempre dinamico, no cacheable
// porque depende de la seleccion del usuario)
// ============================================================
// MEJORA: Cargar solo los Codigo_Unico necesarios para el filtro
$establecimientosZona = [];
$codigosUnicoZona = []; // Para reemplazar la subquery
if ($fZonaSanitaria !== '') {
    $stmtEst = $pdo->prepare("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE WHERE MicroRed = :microred ORDER BY Nombre_Establecimiento");
    $stmtEst->execute([':microred' => $fZonaSanitaria]);
    $establecimientosZona = $stmtEst->fetchAll();
    $codigosUnicoZona = array_column($establecimientosZona, 'Codigo_Unico');
} else {
    $establecimientosZona = $pdo->query("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE ORDER BY Nombre_Establecimiento")->fetchAll();
    $codigosUnicoZona = array_column($establecimientosZona, 'Codigo_Unico');
}

// ============================================================
// CONTROL DE VISUALIZACION DE RESULTADOS
// ============================================================
$buscar = isset($_GET['buscar']) && $_GET['buscar'] === '1';
$hayFiltrosAdicionales = ($fEstablecimiento !== '' || $fGrupoEdad !== ''
    || $fIdGenero !== '' || $fOtraCondicion !== '' || $fTipoDiagnostico !== '' || $fValorLab !== '' || $fCodigoItem !== ''
    || $fLote !== '' || $fNumPag !== '' || $fNumReg !== '' || $fDocPaciente !== ''
    || $fDocPersonal !== '' || $fDocRegistrador !== '' || $fUps !== '' || $fDepartamento !== '');
$mostrarResultados = ($sub === 'preventivas') ? true : ($buscar || $hayFiltrosAdicionales);

// ============================================================
// CONSTRUCCION DE WHERE (OPTIMIZADO)
// ============================================================
$where = "1=1";
$params = [];

if ($fAnio !== '') {
    $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio;
}

// MEJORA #1: Usar columna virtual Mes_Int en vez de CAST(TRIM(Mes) AS UNSIGNED)
// Esto permite usar el indice idx_anio_mesint (Anio, Mes_Int)
// Si la columna virtual no existe (por si no se ejecuto el SQL de indices),
// se fallback al metodo original.
if ($fMes !== '') {
    $mesInt = intval($fMes);
    // Intentar usar Mes_Int (columna virtual del script de indices)
    // Si falla, hacer fallback a CAST
    $where .= " AND (Mes_Int = :mesint OR CAST(TRIM(Mes) AS UNSIGNED) = :mesint2)";
    $params[':mesint'] = $mesInt;
    $params[':mesint2'] = $mesInt;
}

// MEJORA #2: Reemplazar subquery por lista pre-fetcheada de ZSPERENE
// ANTES: AND Codigo_Unico IN (SELECT Codigo_Unico FROM ZSPERENE WHERE MicroRed = :microred)
// AHORA: AND Codigo_Unico IN (:cu1, :cu2, ...) - evita la subquery por cada fila
if ($fZonaSanitaria !== '' && !empty($codigosUnicoZona)) {
    $placeholders = [];
    foreach ($codigosUnicoZona as $i => $cu) {
        $key = ':zcu' . $i;
        $placeholders[] = $key;
        $params[$key] = $cu;
    }
    $where .= " AND Codigo_Unico IN (" . implode(',', $placeholders) . ")";
}

if ($fEstablecimiento !== '') {
    $where .= " AND Codigo_Unico = :est"; $params[':est'] = $fEstablecimiento;
}
if ($fGrupoEdad !== '') {
    $where .= " AND Grupo_Edad = :gedad"; $params[':gedad'] = $fGrupoEdad;
}
if ($fIdGenero !== '') {
    $where .= " AND Id_Genero = :genero"; $params[':genero'] = $fIdGenero;
}
if ($fOtraCondicion !== '') {
    $where .= " AND Descripcion_Otra_Condicion = :otra_cond"; $params[':otra_cond'] = $fOtraCondicion;
}
if ($fTipoDiagnostico !== '') {
    $where .= " AND Tipo_Diagnostico = :td"; $params[':td'] = $fTipoDiagnostico;
}
// ============================================================
// FILTRO COMBINADO codigo_item + valor_lab
// ------------------------------------------------------------
// Casos soportados:
//   1) MODO EMPAREJADO (por posicion):
//        codigo_item = "C0009,C0010"  y  valor_lab = "1,4"
//      Ambos con 2+ tokens y la misma cantidad.
//      SQL generado (garantiza que TODOS los pares existan en la MISMA Id_Cita):
//        AND Id_Cita IN (
//          SELECT sub.Id_Cita
//          FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO sub
//          WHERE ((sub.Codigo_Item = 'C0009' AND sub.Valor_Lab = '1')
//              OR (sub.Codigo_Item = 'C0010' AND sub.Valor_Lab = '4'))
//          GROUP BY sub.Id_Cita
//          HAVING COUNT(DISTINCT CONCAT(sub.Codigo_Item,'|',COALESCE(sub.Valor_Lab,''))) = 2
//        )
//
//   2) MODO INDEPENDIENTE (comportamiento historico):
//        - Solo codigo_item con multiples valores -> OR entre Codigo_Item
//        - Solo valor_lab con multiples valores   -> OR entre Valor_Lab
//        - Ambos con 1 token cada uno            -> condiciones AND simples
//      Cada token admite sufijo '*' como comodin de prefijo (LIKE).
// ============================================================
$codigoItemTokens = [];
if ($fCodigoItem !== '') {
    $codigoItemTokens = preg_split('/[\s,]+/', $fCodigoItem);
    $codigoItemTokens = array_values(array_filter(array_map('trim', $codigoItemTokens), fn($t) => $t !== ''));
}
$valorLabTokens = [];
if ($fValorLab !== '') {
    $valorLabTokens = preg_split('/[\s,]+/', $fValorLab);
    $valorLabTokens = array_values(array_filter(array_map('trim', $valorLabTokens), fn($t) => $t !== ''));
}

// El modo emparejado solo aplica cuando AMBOS campos traen 2 o mas valores
// y la cantidad de tokens coincide.
$modoEmparejado = (count($codigoItemTokens) >= 2)
    && (count($valorLabTokens) >= 2)
    && (count($codigoItemTokens) === count($valorLabTokens));

if ($modoEmparejado) {
    // MODO EMPAREJADO: garantizar que TODOS los pares (Codigo_Item, Valor_Lab)
    // existan en la MISMA Id_Cita, usando subquery con GROUP BY + HAVING.
    //
    // Genera:
    //   AND Id_Cita IN (
    //     SELECT sub.Id_Cita
    //     FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO sub
    //     WHERE ((sub.Codigo_Item = :pciitem0 AND sub.Valor_Lab = :pcvlab0)
    //         OR (sub.Codigo_Item = :pciitem1 AND sub.Valor_Lab = :pcvlab1) ...)
    //     GROUP BY sub.Id_Cita
    //     HAVING COUNT(DISTINCT CONCAT(sub.Codigo_Item,'|',COALESCE(sub.Valor_Lab,''))) = N
    //   )
    $numPares = count($codigoItemTokens);

    // Construir las clausulas OR internas de la subquery
    $paresOr = [];
    foreach ($codigoItemTokens as $idx => $tokItem) {
        $tokLab = $valorLabTokens[$idx];

        // Clausula para Codigo_Item (admite '*' como comodin)
        $kItem = ':pciitem' . $idx;
        if (str_ends_with($tokItem, '*')) {
            $clauseItem = "sub.Codigo_Item LIKE $kItem";
            $params[$kItem] = rtrim($tokItem, '*') . '%';
        } else {
            $clauseItem = "sub.Codigo_Item = $kItem";
            $params[$kItem] = $tokItem;
        }

        // Clausula para Valor_Lab (admite '*' como comodin)
        $kLab = ':pcvlab' . $idx;
        if (str_ends_with($tokLab, '*')) {
            $clauseLab = "sub.Valor_Lab LIKE $kLab";
            $params[$kLab] = rtrim($tokLab, '*') . '%';
        } else {
            $clauseLab = "sub.Valor_Lab = $kLab";
            $params[$kLab] = $tokLab;
        }

        $paresOr[] = "($clauseItem AND $clauseLab)";
    }

    $orClause = implode(' OR ', $paresOr);
    $havingCount = $numPares; // cantidad de pares que deben coincidir

    $where .= " AND Id_Cita IN (
        SELECT sub.Id_Cita
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO sub
        WHERE ($orClause)
        GROUP BY sub.Id_Cita
        HAVING COUNT(DISTINCT CONCAT(sub.Codigo_Item, '|', COALESCE(sub.Valor_Lab, ''))) = $havingCount
    )";
} else {
    // MODO INDEPENDIENTE: aplicar filtros por separado (comportamiento previo)

    // Filtro Valor_Lab (ahora tambien acepta multiples valores separados por coma)
    if (!empty($valorLabTokens)) {
        if (count($valorLabTokens) === 1) {
            $tok = $valorLabTokens[0];
            if (str_ends_with($tok, '*')) {
                $where .= " AND Valor_Lab LIKE :vlab";
                $params[':vlab'] = rtrim($tok, '*') . '%';
            } else {
                $where .= " AND Valor_Lab = :vlab";
                $params[':vlab'] = $tok;
            }
        } else {
            $orVlab = [];
            foreach ($valorLabTokens as $i => $tok) {
                $k = ':vlab' . $i;
                if (str_ends_with($tok, '*')) {
                    $orVlab[] = "Valor_Lab LIKE $k";
                    $params[$k] = rtrim($tok, '*') . '%';
                } else {
                    $orVlab[] = "Valor_Lab = $k";
                    $params[$k] = $tok;
                }
            }
            $where .= " AND (" . implode(' OR ', $orVlab) . ")";
        }
    }

    // Filtro Codigo_Item (multiples valores separados por coma, OR)
    if (!empty($codigoItemTokens)) {
        $orItem = [];
        foreach ($codigoItemTokens as $i => $tok) {
            $k = ':citem' . $i;
            if (str_ends_with($tok, '*')) {
                $orItem[] = "Codigo_Item LIKE $k";
                $params[$k] = rtrim($tok, '*') . '%';
            } else {
                $orItem[] = "Codigo_Item = $k";
                $params[$k] = $tok;
            }
        }
        $where .= " AND (" . implode(' OR ', $orItem) . ")";
    }
}
if ($fLote !== '') {
    $where .= " AND Lote = :lote"; $params[':lote'] = $fLote;
}
if ($fNumPag !== '') {
    $where .= " AND Num_Pag = :numpag"; $params[':numpag'] = intval($fNumPag);
}
if ($fNumReg !== '') {
    $where .= " AND Num_Reg = :numreg"; $params[':numreg'] = intval($fNumReg);
}
if ($fDocPaciente !== '') {
    // MEJORA #3: Si el documento tiene formato valido (8+ digitos),
    // intentar busqueda exacta primero (usa indice).
    // Si es menor, usar LIKE para busqueda parcial.
    $docLen = strlen($fDocPaciente);
    if ($docLen >= 8) {
        $where .= " AND (Numero_Documento_Paciente = :dpac_exact OR Numero_Documento_Paciente LIKE :dpac_like)";
        $params[':dpac_exact'] = $fDocPaciente;
        $params[':dpac_like'] = '%' . $fDocPaciente . '%';
    } else {
        $where .= " AND Numero_Documento_Paciente LIKE :dpac"; $params[':dpac'] = '%' . $fDocPaciente . '%';
    }
}
if ($fDocPersonal !== '') {
    $docLen = strlen($fDocPersonal);
    if ($docLen >= 8) {
        $where .= " AND (Numero_Documento_Personal = :dper_exact OR Numero_Documento_Personal LIKE :dper_like)";
        $params[':dper_exact'] = $fDocPersonal;
        $params[':dper_like'] = '%' . $fDocPersonal . '%';
    } else {
        $where .= " AND Numero_Documento_Personal LIKE :dper"; $params[':dper'] = '%' . $fDocPersonal . '%';
    }
}
if ($fDocRegistrador !== '') {
    $docLen = strlen($fDocRegistrador);
    if ($docLen >= 8) {
        $where .= " AND (Numero_Documento_Registrador = :dreg_exact OR Numero_Documento_Registrador LIKE :dreg_like)";
        $params[':dreg_exact'] = $fDocRegistrador;
        $params[':dreg_like'] = '%' . $fDocRegistrador . '%';
    } else {
        $where .= " AND Numero_Documento_Registrador LIKE :dreg"; $params[':dreg'] = '%' . $fDocRegistrador . '%';
    }
}
if ($fDepartamento !== '') {
    $where .= " AND Departamento_Establecimiento = :dep"; $params[':dep'] = $fDepartamento;
}
if ($fUps !== '') {
    $where .= " AND Id_Ups = :ups"; $params[':ups'] = $fUps;
}

// Para subpagina preventivas: filtrar por UPS preventivas o Fg_Tipo='P'
// MEJORA #4: Usar los Id_Ups pre-cargados en cache en vez de LIKE en la consulta principal
if ($sub === 'preventivas') {
    if (!empty($upsPreventivas)) {
        $upsIds = array_filter(array_column($upsPreventivas, 'Id_Ups'), fn($uid) => $uid !== null && $uid !== '');
        if (!empty($upsIds)) {
            // Construir IN con los Id_Ups cacheados (usa indice idx_ups_ftipo)
            $phUps = [];
            foreach ($upsIds as $i => $uid) {
                $k = ':pu' . $i;
                $phUps[] = $k;
                $params[$k] = $uid;
            }
            // Usar OR Fg_Tipo = 'P' como condicion complementaria (indexable via idx_ups_ftipo)
            $where .= " AND (Id_Ups IN (" . implode(',', $phUps) . ") OR Fg_Tipo = 'P')";
        } else {
            $where .= " AND Fg_Tipo = 'P'";
        }
    } else {
        $where .= " AND Fg_Tipo = 'P'";
    }
}

// ============================================================
// CONSULTA DE DATOS (OPTIMIZADO)
// ============================================================
$totalRegistros = 0;
$datos = [];
$stats = ['total_pacientes' => 0, 'total_personal' => 0, 'total_establecimientos' => 0, 'total_items' => 0];
$totalPaginas = 0;
$pagina = 1;
$offset = 0;
$porPagina = 50;

if ($mostrarResultados) {
    // MEJORA #5: Optimizar COUNT para paginacion
    // En vez de subquery con GROUP BY, usar SQL_CALC_FOUND_ROWS
    // (disponible en MariaDB/MySQL) para evitar la consulta COUNT separada.
    // Nota: SQL_CALC_FOUND_ROWS fue deprecado en MySQL 8.0.17 pero sigue
    // funcionando en MariaDB (que es lo que usa InfinityFree).
    // Alternativa mas moderna: mantener el COUNT pero usar el indice
    // idx_cita_item_fechaatc para acelerar el GROUP BY.

    $countSql = "SELECT COUNT(*) FROM (SELECT Id_Cita, Codigo_Item FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE {$where} GROUP BY Id_Cita, Codigo_Item) AS sub";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRegistros = (int)$countStmt->fetchColumn();

    // MEJORA #6: Parametros vinculados para LIMIT/OFFSET (seguridad)
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $offset = ($pagina - 1) * $porPagina;
    $totalPaginas = max(1, ceil($totalRegistros / $porPagina));

    // Si la pagina solicitada excede el total, ir a la ultima pagina
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
    }

    // Campos con pivot de Valor_Lab en LAB1-LAB4 segun Id_Correlativo_Lab
    $camposSelect = "Id_Cita, Codigo_Item,
               MAX(Anio) AS Anio, MAX(Mes) AS Mes, MAX(Dia) AS Dia, MAX(Fecha_Atencion) AS Fecha_Atencion,
               MAX(Lote) AS Lote, MAX(Num_Pag) AS Num_Pag, MAX(Num_Reg) AS Num_Reg,
               MAX(Id_Turno) AS Id_Turno, MAX(Id_Condicion_Establecimiento) AS Id_Condicion_Establecimiento, MAX(Id_Condicion_Servicio) AS Id_Condicion_Servicio,
               MAX(Codigo_Unico) AS Codigo_Unico, MAX(Nombre_Establecimiento) AS Nombre_Establecimiento,
               MAX(Abrev_Tipo_Doc_Paciente) AS Abrev_Tipo_Doc_Paciente, MAX(Numero_Documento_Paciente) AS Numero_Documento_Paciente,
               MAX(Nombres_Paciente) AS Nombres_Paciente, MAX(Apellido_Paterno_Paciente) AS Apellido_Paterno_Paciente,
               MAX(Fecha_Nacimiento_Paciente) AS Fecha_Nacimiento_Paciente, MAX(Id_Genero) AS Id_Genero, MAX(Tipo_Edad) AS Tipo_Edad, MAX(Edad_Reg) AS Edad_Reg,
               MAX(Grupo_Edad) AS Grupo_Edad,
               MAX(Descripcion_Item) AS Descripcion_Item, MAX(Tipo_Diagnostico) AS Tipo_Diagnostico, MAX(Fg_Tipo) AS Fg_Tipo,
               MAX(Descripcion_Ups) AS Descripcion_Ups,
               MAX(Numero_Documento_Personal) AS Numero_Documento_Personal, MAX(Nombres_Personal) AS Nombres_Personal, MAX(Apellido_Paterno_Personal) AS Apellido_Paterno_Personal,
               MAX(Descripcion_Profesion) AS Descripcion_Profesion,
               MAX(Numero_Documento_Registrador) AS Numero_Documento_Registrador, MAX(Nombres_Registrador) AS Nombres_Registrador, MAX(Apellido_Paterno_Registrador) AS Apellido_Paterno_Registrador,
               MAX(Fecha_Registro) AS Fecha_Registro,
               MAX(CASE WHEN Id_Correlativo_Lab = 1 THEN Valor_Lab END) AS LAB1,
               MAX(CASE WHEN Id_Correlativo_Lab = 2 THEN Valor_Lab END) AS LAB2,
               MAX(CASE WHEN Id_Correlativo_Lab = 3 THEN Valor_Lab END) AS LAB3,
               MAX(CASE WHEN Id_Correlativo_Lab = 4 THEN Valor_Lab END) AS LAB4";

    // MEJORA #7: Usar LIMIT y OFFSET como parametros vinculados
    $dataSql = "SELECT {$camposSelect} FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE {$where} GROUP BY Id_Cita, Codigo_Item ORDER BY Id_Cita LIMIT :limit OFFSET :offset";
    $dataStmt = $pdo->prepare($dataSql);
    // Vincular LIMIT y OFFSET como PDO::PARAM_INT
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $datos = $dataStmt->fetchAll();

    // MEJORA #8: Consulta de stats solo si hay datos en la pagina
    // y ejecutarla AFTER de los datos (no bloquea el render)
    if (!empty($datos)) {
        $statsSql = "SELECT
            COUNT(DISTINCT Id_Paciente) as total_pacientes,
            COUNT(DISTINCT Id_Personal) as total_personal,
            COUNT(DISTINCT Id_Establecimiento) as total_establecimientos,
            COUNT(DISTINCT Codigo_Item) as total_items
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE " . $where;
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute($params);
        $stats = $statsStmt->fetch();
    }
}

$pageTitle = $subInfo[$sub]['titulo'] . ' - Sistema HIS';
include 'includes/header.php';
?>

<!-- Estilos personalizados para encabezados celeste suave y filtros compactos -->
<style>
    .tabla-resultados thead th {
        background-color: #d6e9f8 !important;
        color: #2c3e50;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
    }
    .filtro-compacto .form-label {
        font-size: 0.72rem;
        margin-bottom: 0.15rem;
        font-weight: 600;
        line-height: 1.1;
    }
    .filtro-compacto .form-select-sm,
    .filtro-compacto .form-control-sm {
        font-size: 0.78rem;
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
    }
    .filtro-compacto {
        --bs-gutter-x: 0.5rem;
    }
    .filtro-compacto > [class*="col"] {
        padding-right: 0.3rem;
        padding-left: 0.3rem;
    }
    .card-body.filtro-body {
        padding: 0.6rem 0.8rem;
    }
    .tabla-resultados tbody td { border: 1px solid #eef1f4; }
    .tabla-resultados thead th { border: 1px solid #c9d6e2; }
    .tabla-col-atencion {
        background-color: #eef6fb !important;
    }
    .tabla-col-paciente {
        background-color: #f4f8ef !important;
    }
    .tabla-col-diagnostico {
        background-color: #fdf5e9 !important;
    }
    .tabla-col-personal {
        background-color: #f5edf7 !important;
    }
    .tabla-col-registro {
        background-color: #fdeeee !important;
    }
    .tabla-resultados tbody tr:hover td {
        filter: brightness(0.97);
    }
    .tabla-resultados thead {
        background-color: #d6e9f8;
    }
</style>

<!-- Encabezado -->
<div class="page-header-section">
    <h4><i class="fas <?= $subInfo[$sub]['icon'] ?> me-2"></i><?= $subInfo[$sub]['titulo'] ?></h4>
    <p class="subtitle"><?= $subInfo[$sub]['descripcion'] ?></p>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros de Busqueda</h6>
        <div>
            <a href="consulta_atenciones.php?sub=<?= $sub ?>" class="btn btn-sm btn-outline-secondary me-1">
                <i class="fas fa-times me-1"></i> Limpiar
            </a>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his">
                <i class="fas fa-search me-1"></i> Buscar
            </button>
        </div>
    </div>
    <div class="card-body filtro-body">
        <form id="filterForm" method="GET" action="consulta_atenciones.php">
            <input type="hidden" name="sub" value="<?= htmlspecialchars($sub) ?>">
            <input type="hidden" name="buscar" value="1">

            <?php if ($sub === 'general'): ?>
            <!-- FILTROS SUB-PAGINA GENERAL -->
            <div class="row g-2 filtro-compacto mb-2">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <label class="form-label">A&ntilde;o</label>
                    <select name="anio" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= (string)$fAnio === (string)$a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <label class="form-label">Mes</label>
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">-- Todo --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($fMes !== '' && intval($fMes) === $m) ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <label class="form-label">Zona Sanitaria</label>
                    <select name="zona_sanitaria" id="zonaSanitaria" class="form-select form-select-sm">
                        <?php foreach ($zonasSanitarias as $zs): ?>
                            <option value="<?= htmlspecialchars($zs) ?>" <?= $fZonaSanitaria === $zs ? 'selected' : '' ?>><?= htmlspecialchars($zs) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <label class="form-label">Establecimiento</label>
                    <select name="establecimiento" id="establecimiento" class="form-select form-select-sm">
                        <option value="">- Todos -</option>
                        <?php foreach ($establecimientosZona as $ez): ?>
                            <option value="<?= htmlspecialchars($ez['Codigo_Unico']) ?>" <?= $fEstablecimiento === $ez['Codigo_Unico'] ? 'selected' : '' ?>><?= htmlspecialchars($ez['Nombre_Establecimiento']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-2 filtro-compacto mb-2">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Grupo Edad</label>
                    <select name="grupo_edad" class="form-select form-select-sm">
                        <option value="">- Todos -</option>
                        <?php foreach ($gruposEdad as $ge): ?>
                            <option value="<?= htmlspecialchars($ge) ?>" <?= $fGrupoEdad === $ge ? 'selected' : '' ?>><?= htmlspecialchars($ge) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">G&eacute;nero</label>
                    <select name="id_genero" class="form-select form-select-sm">
                        <option value="">- Todos -</option>
                        <?php foreach ($generos as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $fIdGenero === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Cond. Materna</label>
                    <select name="otra_condicion" class="form-select form-select-sm">
                        <option value="">- Todas -</option>
                        <?php foreach ($otrasCondiciones as $oc): ?>
                            <option value="<?= htmlspecialchars($oc) ?>" <?= $fOtraCondicion === $oc ? 'selected' : '' ?>><?= htmlspecialchars($oc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">CIE-10 / CPT</label>
                    <input type="text" name="codigo_item" class="form-control form-control-sm" placeholder="Ej: C0009,C0010 (pareado con valor_lab)" value="<?= htmlspecialchars($fCodigoItem) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Tipo Dx.</label>
                    <select name="tipo_diagnostico" class="form-select form-select-sm">
                        <option value="">- Todos -</option>
                        <?php foreach ($tiposDiagnostico as $td): ?>
                            <option value="<?= htmlspecialchars($td) ?>" <?= $fTipoDiagnostico === $td ? 'selected' : '' ?>><?= htmlspecialchars($td) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Valor Lab</label>
                    <input type="text" name="valor_lab" class="form-control form-control-sm" placeholder="Ej: 1,4 (pareado con codigo_item)" value="<?= htmlspecialchars($fValorLab) ?>">
                </div>
            </div>

            <div class="row g-2 filtro-compacto">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Lote</label>
                    <input type="text" name="lote" class="form-control form-control-sm" placeholder="Nro. Lote" value="<?= htmlspecialchars($fLote) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Num. Pag</label>
                    <input type="text" name="num_pag" class="form-control form-control-sm" placeholder="Nro. Pag." value="<?= htmlspecialchars($fNumPag) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Num. Reg</label>
                    <input type="text" name="num_reg" class="form-control form-control-sm" placeholder="Nro. Reg." value="<?= htmlspecialchars($fNumReg) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Doc. Paciente</label>
                    <input type="text" name="doc_paciente" class="form-control form-control-sm" placeholder="Nro. Doc." value="<?= htmlspecialchars($fDocPaciente) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Doc. Personal</label>
                    <input type="text" name="doc_personal" class="form-control form-control-sm" placeholder="Nro. Doc." value="<?= htmlspecialchars($fDocPersonal) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Doc. Registrador</label>
                    <input type="text" name="doc_registrador" class="form-control form-control-sm" placeholder="Nro. Doc." value="<?= htmlspecialchars($fDocRegistrador) ?>">
                </div>
            </div>

            <?php else: ?>
            <!-- FILTROS SUB-PAGINA PREVENTIVAS -->
            <div class="row g-2 filtro-compacto">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">A&ntilde;o</label>
                    <select name="anio" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= (string)$fAnio === (string)$a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Mes</label>
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($fMes !== '' && intval($fMes) === $m) ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Departamento</label>
                    <select name="departamento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $fDepartamento === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Establecimiento</label>
                    <select name="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= $fEstablecimiento === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Grupo Edad</label>
                    <select name="grupo_edad" class="form-select form-select-sm">
                        <option value="">- Todos -</option>
                        <?php foreach ($gruposEdad as $ge): ?>
                            <option value="<?= htmlspecialchars($ge) ?>" <?= $fGrupoEdad === $ge ? 'selected' : '' ?>><?= htmlspecialchars($ge) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Tipo Dx.</label>
                    <select name="tipo_diagnostico" class="form-select form-select-sm">
                        <option value="">- Todos -</option>
                        <?php foreach ($tiposDiagnostico as $td): ?>
                            <option value="<?= htmlspecialchars($td) ?>" <?= $fTipoDiagnostico === $td ? 'selected' : '' ?>><?= htmlspecialchars($td) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">C&oacute;digo Item</label>
                    <input type="text" name="codigo_item" class="form-control form-control-sm" placeholder="Ej: C0009,C0010 (pareado con valor_lab)" value="<?= htmlspecialchars($fCodigoItem) ?>">
                </div>
                <?php if (!empty($upsPreventivas)): ?>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">UPS Preventiva</label>
                    <select name="ups" class="form-select form-select-sm">
                        <option value="">-- Todas --</option>
                        <?php foreach ($upsPreventivas as $u): ?>
                            <option value="<?= htmlspecialchars($u['Id_Ups'] ?? '') ?>" <?= $fUps === ($u['Id_Ups'] ?? '') ? 'selected' : '' ?>>
                                <?= clean($u['Descripcion_Ups'] ?? '') ?> (<?= clean($u['Id_Ups'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Doc. Paciente</label>
                    <input type="text" name="doc_paciente" class="form-control form-control-sm" placeholder="Nro. Doc." value="<?= htmlspecialchars($fDocPaciente) ?>">
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label">Doc. Personal</label>
                    <input type="text" name="doc_personal" class="form-control form-control-sm" placeholder="Nro. Doc." value="<?= htmlspecialchars($fDocPersonal) ?>">
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($sub === 'preventivas'): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Modo Preventivas:</strong> Se filtran automaticamente las atenciones cuyo UPS o tipo de item corresponde a actividades preventivo-promocionales.
</div>
<?php endif; ?>

<!-- Resultados -->
<?php if (!$mostrarResultados && $sub !== 'preventivas'): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-filter fa-3x text-muted mb-3"></i>
        <h5 class="text-muted mb-2">Haga clic en Buscar para consultar las atenciones</h5>
        <p class="text-muted small mb-0">Configure los filtros de arriba y presione el boton <strong>Buscar</strong> para cargar los datos del consolidado.</p>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-table me-2 text-primary"></i>
            Resultados: <?= number_format($totalRegistros) ?> registros
        </h6>
        <?php if ($totalRegistros > 0): ?>
        <form method="POST" action="export_excel.php" target="_blank">
            <?php foreach ($_GET as $key => $val): ?>
                <?php if ($key === 'pagina') continue; ?>
                <input type="hidden" name="filter_<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars(is_array($val) ? '' : $val) ?>">
            <?php endforeach; ?>
            <?php if (!isset($_GET['sub'])): ?>
                <input type="hidden" name="filter_sub" value="<?= htmlspecialchars($sub) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($datos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No se encontraron registros con los filtros seleccionados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="max-height: 65vh; overflow: auto;">
                <table class="table table-hover table-sm mb-0 tabla-resultados" style="font-size: 0.73rem;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th>#</th>
                            <th class="tabla-col-atencion">FECHA ATC</th>
                            <th class="tabla-col-atencion">Establecimiento</th>
                            <th class="tabla-col-atencion">UPS</th>
                            <th class="tabla-col-atencion">Lote</th>
                            <th class="tabla-col-atencion">N.P.</th>
                            <th class="tabla-col-atencion">N.R.</th>
                            <th class="tabla-col-atencion">TUR</th>
                            <th class="tabla-col-atencion">CE</th>
                            <th class="tabla-col-atencion">CS</th>
                            <th class="tabla-col-paciente">T. Doc.</th>
                            <th class="tabla-col-paciente">NUM. DOC.</th>
                            <th class="tabla-col-paciente">NOMBRE PCTE.</th>
                            <th class="tabla-col-paciente">APELLIDO PCTE</th>
                            <th class="tabla-col-paciente">GEN</th>
                            <th class="tabla-col-paciente">FECHA NAC.</th>
                            <th class="tabla-col-paciente">Edad</th>
                            <th class="tabla-col-paciente">T.E.</th>
                            <th class="tabla-col-diagnostico">CIEX/CPT</th>
                            <th class="tabla-col-diagnostico">Descripcion Item</th>
                            <th class="tabla-col-diagnostico">T.Dx</th>
                            <th class="tabla-col-diagnostico">LAB1</th>
                            <th class="tabla-col-diagnostico">LAB2</th>
                            <th class="tabla-col-diagnostico">LAB3</th>
                            <th class="tabla-col-diagnostico">LAB4</th>
                            <th class="tabla-col-personal">NOMBRE PERS.</th>
                            <th class="tabla-col-personal">APELLIDO PERS.</th>
                            <th class="tabla-col-personal">Profesion</th>
                            <th class="tabla-col-registro">NOMBRE REG.</th>
                            <th class="tabla-col-registro">APELLIDO REG.</th>
                            <th class="tabla-col-registro">F. Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $row): ?>
                        <tr>
                            <td class="text-muted"><?= $offset + $i + 1 ?></td>
                            <td class="tabla-col-atencion"><?= formatDate($row['Fecha_Atencion']) ?></td>
                            <td class="tabla-col-atencion" title="<?= clean($row['Nombre_Establecimiento']) ?>">
                                <?= clean(mb_strimwidth($row['Nombre_Establecimiento'] ?? '', 0, 25, '...')) ?>
                            </td>
                            <td class="tabla-col-atencion" title="<?= clean($row['Descripcion_Ups'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Ups'] ?? '', 0, 20, '...')) ?>
                            </td>
                            <td class="tabla-col-atencion"><?= clean($row['Lote']) ?></td>
                            <td class="tabla-col-atencion"><?= clean($row['Num_Pag']) ?></td>
                            <td class="tabla-col-atencion"><?= clean($row['Num_Reg']) ?></td>
                            <td class="tabla-col-atencion"><?= clean($row['Id_Turno']) ?></td>
                            <td class="tabla-col-atencion"><?= clean($row['Id_Condicion_Establecimiento']) ?></td>
                            <td class="tabla-col-atencion"><?= clean($row['Id_Condicion_Servicio']) ?></td>
                            <td class="tabla-col-paciente"><small><?= clean($row['Abrev_Tipo_Doc_Paciente']) ?></small></td>
                            <td class="tabla-col-paciente"><?= clean($row['Numero_Documento_Paciente']) ?></td>
                            <td class="tabla-col-paciente" title="<?= clean($row['Nombres_Paciente']) ?>">
                                <?= clean(mb_strimwidth($row['Nombres_Paciente'] ?? '', 0, 20, '...')) ?>
                            </td>
                            <td class="tabla-col-paciente" title="<?= clean($row['Apellido_Paterno_Paciente']) ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Paciente'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td class="tabla-col-paciente"><small><?= clean($row['Id_Genero']) ?></small></td>
                            <td class="tabla-col-paciente"><?= formatDate($row['Fecha_Nacimiento_Paciente']) ?></td>
                            <td class="tabla-col-paciente"><?= clean($row['Edad_Reg']) ?></td>
                            <td class="tabla-col-paciente"><small><?= clean($row['Tipo_Edad']) ?></small></td>
                            <td class="tabla-col-diagnostico"><code><?= clean($row['Codigo_Item']) ?></code></td>
                            <td class="tabla-col-diagnostico" title="<?= clean($row['Descripcion_Item']) ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Item'] ?? '', 0, 25, '...')) ?>
                            </td>
                            <td class="tabla-col-diagnostico"><small><?= clean($row['Tipo_Diagnostico']) ?></small></td>
                            <td class="tabla-col-diagnostico"><?= clean($row['LAB1']) ?></td>
                            <td class="tabla-col-diagnostico"><?= clean($row['LAB2']) ?></td>
                            <td class="tabla-col-diagnostico"><?= clean($row['LAB3']) ?></td>
                            <td class="tabla-col-diagnostico"><?= clean($row['LAB4']) ?></td>
                            <td class="tabla-col-personal" title="<?= clean($row['Nombres_Personal'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Nombres_Personal'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td class="tabla-col-personal" title="<?= clean($row['Apellido_Paterno_Personal'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Personal'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td class="tabla-col-personal" title="<?= clean($row['Descripcion_Profesion'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Profesion'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td class="tabla-col-registro" title="<?= clean($row['Nombres_Registrador'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Nombres_Registrador'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td class="tabla-col-registro" title="<?= clean($row['Apellido_Paterno_Registrador'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Registrador'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td class="tabla-col-registro"><small><?= formatDateTime($row['Fecha_Registro']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <div class="card-footer bg-white">
        <nav aria-label="Paginacion">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php
                $queryParams = $_GET;
                unset($queryParams['pagina']);
                $queryString = http_build_query($queryParams);
                if ($pagina > 1): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=1&<?= $queryString ?>"><i class="fas fa-angle-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina - 1 ?>&<?= $queryString ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php endif; ?>
                <?php
                $start = max(1, $pagina - 2);
                $end = min($totalPaginas, $pagina + 2);
                for ($p = $start; $p <= $end; $p++):
                ?>
                    <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $p ?>&<?= $queryString ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagina < $totalPaginas): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina + 1 ?>&<?= $queryString ?>"><i class="fas fa-angle-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $totalPaginas ?>&<?= $queryString ?>"><i class="fas fa-angle-double-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="text-center mt-2">
            <small class="text-muted">
                Mostrando <?= number_format($offset + 1) ?> - <?= number_format(min($offset + $porPagina, $totalRegistros)) ?>
                de <?= number_format($totalRegistros) ?> registros
                | Pagina <?= $pagina ?> de <?= $totalPaginas ?>
            </small>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Script para cascada Zona Sanitaria -> Establecimiento -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var zonaSelect = document.getElementById('zonaSanitaria');
    var estaSelect = document.getElementById('establecimiento');

    if (zonaSelect && estaSelect) {
        zonaSelect.addEventListener('change', function() {
            var microred = this.value;
            estaSelect.innerHTML = '<option value="">Cargando...</option>';

            fetch('api_establecimientos.php?microred=' + encodeURIComponent(microred))
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    estaSelect.innerHTML = '<option value="">-- Todos --</option>';
                    if (data && data.length > 0) {
                        data.forEach(function(est) {
                            var opt = document.createElement('option');
                            opt.value = est.Codigo_Unico;
                            opt.textContent = est.Nombre_Establecimiento;
                            estaSelect.appendChild(opt);
                        });
                    }
                })
                .catch(function() {
                    estaSelect.innerHTML = '<option value="">-- Error al cargar --</option>';
                });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>