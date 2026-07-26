<?php
/**
 * Sistema de Gestion de Datos HIS
 * Exportar resultados a Excel
 *
 * FIX: La logica de filtros ahora replica EXACTAMENTE la de consulta_atenciones.php,
 * incluyendo:
 *   - Busqueda multi-token de Codigo_Item (coma, espacio, wildcard *)
 *   - Valor_Lab con soporte wildcard *
 *   - Sub-pagina preventivas (filtro UPS / Fg_Tipo='P')
 *   - Establecimiento: Codigo_Unico (general) o Nombre (preventivas)
 *   - Zona sanitaria con subquery ZSPERENE
 */

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';
require_once 'includes/ExcelWriter.php';

$pdo = getDBConnection();

// ============================================================
// RECUPERAR FILTROS DESDE POST  (con mismos defaults que consulta_atenciones.php)
// ============================================================
$sub = $_POST['filter_sub'] ?? 'general';
if (!in_array($sub, ['general', 'preventivas'], true)) {
    $sub = 'general';
}

$fAnio           = trim($_POST['filter_anio'] ?? '');
$fMes            = trim($_POST['filter_mes'] ?? '');
$fZonaSanitaria  = trim($_POST['filter_zona_sanitaria'] ?? '');
$fDepartamento   = trim($_POST['filter_departamento'] ?? '');
$fUps            = trim($_POST['filter_ups'] ?? '');
$fEstablecimiento = trim($_POST['filter_establecimiento'] ?? '');
$fGrupoEdad      = trim($_POST['filter_grupo_edad'] ?? '');
$fIdGenero       = trim($_POST['filter_id_genero'] ?? '');
$fOtraCondicion  = trim($_POST['filter_otra_condicion'] ?? '');
$fCodigoItem     = trim($_POST['filter_codigo_item'] ?? '');
$fTipoDiagnostico = trim($_POST['filter_tipo_diagnostico'] ?? '');
$fLote           = trim($_POST['filter_lote'] ?? '');
$fNumPag         = trim($_POST['filter_num_pag'] ?? '');
$fNumReg         = trim($_POST['filter_num_reg'] ?? '');
$fValorLab       = trim($_POST['filter_valor_lab'] ?? '');
$fDocPersonal    = trim($_POST['filter_doc_personal'] ?? '');
$fDocPaciente    = trim($_POST['filter_doc_paciente'] ?? '');
$fDocRegistrador = trim($_POST['filter_doc_registrador'] ?? '');

// ============================================================
// CONSTRUIR WHERE — replica exacta de consulta_atenciones.php
// ============================================================
$where = "1=1";
$params = [];

if ($fAnio !== '') {
    $where .= " AND Anio = :anio";
    $params[':anio'] = $fAnio;
}
if ($fMes !== '') {
    $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes";
    $params[':mes'] = intval($fMes);
}
if ($fZonaSanitaria !== '') {
    $where .= " AND Codigo_Unico IN (SELECT Codigo_Unico FROM ZSPERENE WHERE MicroRed = :microred)";
    $params[':microred'] = $fZonaSanitaria;
}

// Establecimiento: en sub=general el valor es Codigo_Unico;
// en sub=preventivas el valor es Nombre_Establecimiento.
if ($fEstablecimiento !== '') {
    if ($sub === 'general') {
        $where .= " AND Codigo_Unico = :est";
        $params[':est'] = $fEstablecimiento;
    } else {
        $where .= " AND Nombre_Establecimiento = :est";
        $params[':est'] = $fEstablecimiento;
    }
}

if ($fGrupoEdad !== '') {
    $where .= " AND Grupo_Edad = :gedad";
    $params[':gedad'] = $fGrupoEdad;
}
if ($fIdGenero !== '') {
    $where .= " AND Id_Genero = :genero";
    $params[':genero'] = $fIdGenero;
}
if ($fOtraCondicion !== '') {
    $where .= " AND Descripcion_Otra_Condicion = :otra_cond";
    $params[':otra_cond'] = $fOtraCondicion;
}
if ($fTipoDiagnostico !== '') {
    $where .= " AND Tipo_Diagnostico = :td";
    $params[':td'] = $fTipoDiagnostico;
}

// Valor_Lab: soporte wildcard con * (igual que consulta_atenciones.php)
if ($fValorLab !== '') {
    if (str_ends_with($fValorLab, '*')) {
        $vlabVal = rtrim($fValorLab, '*');
        $where .= " AND Valor_Lab LIKE :vlab";
        $params[':vlab'] = $vlabVal . '%';
    } else {
        $where .= " AND Valor_Lab = :vlab";
        $params[':vlab'] = $fValorLab;
    }
}

// Codigo_Item: busqueda multi-token (coma, espacio, wildcard *)
// Replica exacta de consulta_atenciones.php
if ($fCodigoItem !== '') {
    $tokens = preg_split('/[\s,]+/', $fCodigoItem);
    $tokens = array_filter(array_map('trim', $tokens), fn($t) => $t !== '');
    if (!empty($tokens)) {
        $orParts = [];
        $i = 0;
        foreach ($tokens as $tok) {
            $i++;
            $key = ':citem' . $i;
            if (str_ends_with($tok, '*')) {
                $orParts[] = "Codigo_Item LIKE $key";
                $params[$key] = rtrim($tok, '*') . '%';
            } else {
                $orParts[] = "Codigo_Item = $key";
                $params[$key] = $tok;
            }
        }
        $where .= " AND (" . implode(' OR ', $orParts) . ")";
    }
}

if ($fLote !== '') {
    $where .= " AND Lote = :lote";
    $params[':lote'] = $fLote;
}
if ($fNumPag !== '') {
    $where .= " AND Num_Pag = :numpag";
    $params[':numpag'] = intval($fNumPag);
}
if ($fNumReg !== '') {
    $where .= " AND Num_Reg = :numreg";
    $params[':numreg'] = intval($fNumReg);
}
if ($fDocPaciente !== '') {
    $where .= " AND Numero_Documento_Paciente LIKE :dpac";
    $params[':dpac'] = '%' . $fDocPaciente . '%';
}
if ($fDocPersonal !== '') {
    $where .= " AND Numero_Documento_Personal LIKE :dper";
    $params[':dper'] = '%' . $fDocPersonal . '%';
}
if ($fDocRegistrador !== '') {
    $where .= " AND Numero_Documento_Registrador LIKE :dreg";
    $params[':dreg'] = '%' . $fDocRegistrador . '%';
}
if ($fDepartamento !== '') {
    $where .= " AND Departamento_Establecimiento = :dep";
    $params[':dep'] = $fDepartamento;
}
if ($fUps !== '') {
    $where .= " AND Id_Ups = :ups";
    $params[':ups'] = $fUps;
}

// ============================================================
// FILTRO PREVENTIVAS (solo cuando sub=preventivas)
// Replica exacta de consulta_atenciones.php
// ============================================================
if ($sub === 'preventivas') {
    // Obtener UPS preventivas ( misma consulta que consulta_atenciones.php )
    $upsPreventivas = [];
    try {
        $upsPreventivas = $pdo->query(
            "SELECT DISTINCT Id_Ups, Descripcion_Ups
             FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
             WHERE Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%' OR Fg_Tipo = 'P'
             ORDER BY Descripcion_Ups LIMIT 200"
        )->fetchAll();
    } catch (Exception $e) {
        $upsPreventivas = [];
    }

    if (!empty($upsPreventivas)) {
        $upsIds = array_column($upsPreventivas, 'Id_Ups');
        $placeholders = [];
        foreach ($upsIds as $i => $uid) {
            if ($uid === null || $uid === '') continue;
            $k = ':pu' . $i;
            $placeholders[] = $k;
            $params[$k] = $uid;
        }
        if (!empty($placeholders)) {
            $where .= " AND (Id_Ups IN (" . implode(',', $placeholders) . ") OR Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
        } else {
            $where .= " AND (Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
        }
    } else {
        $where .= " AND (Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
    }
}

// ============================================================
// CONSULTA DE DATOS (sin limite, para exportar todo)
// ============================================================
$campos = "Id_Cita, Anio, Mes, Dia, Fecha_Atencion, Lote, Num_Pag, Num_Reg,
           Codigo_Unico, Nombre_Establecimiento,
           Abrev_Tipo_Doc_Paciente, Numero_Documento_Paciente,
           Apellido_Paterno_Paciente, Apellido_Materno_Paciente, Nombres_Paciente,
           Fecha_Nacimiento_Paciente, Id_Genero, Tipo_Edad, Edad_Reg, Grupo_Edad,
           Descripcion_Etnia, Descripcion_Financiador,
           Numero_Documento_Personal, Abrev_Tipo_Doc_Personal,
           Apellido_Paterno_Personal, Apellido_Materno_Personal, Nombres_Personal,
           Descripcion_Profesion, Descripcion_Condicion,
           Numero_Documento_Registrador, Apellido_Paterno_Registrador, Apellido_Materno_Registrador, Nombres_Registrador,
           Codigo_Item, Descripcion_Item, Fg_Tipo, Tipo_Diagnostico, Valor_Lab,
           Peso, Talla, Hemoglobina, Perimetro_Abdominal, Perimetro_Cefalico,
           Descripcion_Otra_Condicion, Descripcion_Centro_Poblado, Descripcion_Ups,
           Fecha_Registro, Fecha_Modificacion";

$dataSql = "SELECT {$campos} FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE {$where} ORDER BY Fecha_Atencion DESC";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$datos = $dataStmt->fetchAll();

// ============================================================
// GENERAR EXCEL
// ============================================================
// Encabezados del Excel
$headers = [
    'Id Cita', 'Anio', 'Mes', 'Dia', 'Fecha Atencion', 'Lote', 'Num Pag', 'Num Reg',
    'Codigo Unico', 'Establecimiento',
    'T. Doc. Pac.', 'Doc. Paciente', 'Ape. Pat. Paciente', 'Ape. Mat. Paciente', 'Nombres Paciente',
    'Fecha Nacimiento', 'Genero', 'Tipo Edad', 'Edad', 'Grupo Edad', 'Etnia', 'Financiador',
    'Doc. Personal', 'T. Doc. Per.', 'Ape. Pat. Personal', 'Ape. Mat. Personal', 'Nombres Personal',
    'Profesion', 'Condicion',
    'Doc. Registrador', 'Ape. Pat. Reg.', 'Ape. Mat. Reg.', 'Nombres Registrador',
    'Codigo Item', 'Descripcion Item', 'Tipo', 'Tipo Diagnostico', 'Valor Lab',
    'Peso', 'Talla', 'Hemoglobina', 'Perim. Abdominal', 'Perim. Cefalico',
    'Otra Condicion', 'Centro Poblado', 'UPS',
    'Fecha Registro', 'Fecha Modificacion'
];

// Anchos de columna
$widths = [15, 6, 4, 4, 14, 6, 8, 8, 12, 30, 8, 15, 18, 18, 25, 14, 8, 8, 6, 15, 15, 15, 15, 8, 18, 18, 25, 25, 15, 15, 18, 18, 25, 12, 35, 6, 10, 8, 10, 10, 10, 10, 20, 25, 30, 18, 18];

// Preparar datos para el Excel
$excelData = [];
foreach ($datos as $row) {
    $excelData[] = [
        $row['Id_Cita'],
        $row['Anio'],
        $row['Mes'],
        $row['Dia'],
        formatDate($row['Fecha_Atencion']),
        $row['Lote'],
        $row['Num_Pag'],
        $row['Num_Reg'],
        $row['Codigo_Unico'],
        $row['Nombre_Establecimiento'],
        $row['Abrev_Tipo_Doc_Paciente'],
        $row['Numero_Documento_Paciente'],
        $row['Apellido_Paterno_Paciente'],
        $row['Apellido_Materno_Paciente'],
        $row['Nombres_Paciente'],
        formatDate($row['Fecha_Nacimiento_Paciente']),
        $row['Id_Genero'],
        $row['Tipo_Edad'],
        $row['Edad_Reg'],
        $row['Grupo_Edad'],
        $row['Descripcion_Etnia'],
        $row['Descripcion_Financiador'],
        $row['Numero_Documento_Personal'],
        $row['Abrev_Tipo_Doc_Personal'],
        $row['Apellido_Paterno_Personal'],
        $row['Apellido_Materno_Personal'],
        $row['Nombres_Personal'],
        $row['Descripcion_Profesion'],
        $row['Descripcion_Condicion'],
        $row['Numero_Documento_Registrador'],
        $row['Apellido_Paterno_Registrador'],
        $row['Apellido_Materno_Registrador'],
        $row['Nombres_Registrador'],
        $row['Codigo_Item'],
        $row['Descripcion_Item'],
        $row['Fg_Tipo'],
        $row['Tipo_Diagnostico'],
        $row['Valor_Lab'],
        $row['Peso'],
        $row['Talla'],
        $row['Hemoglobina'],
        $row['Perimetro_Abdominal'],
        $row['Perimetro_Cefalico'],
        $row['Descripcion_Otra_Condicion'],
        $row['Descripcion_Centro_Poblado'],
        $row['Descripcion_Ups'],
        formatDateTime($row['Fecha_Registro']),
        formatDateTime($row['Fecha_Modificacion'])
    ];
}

// Generar nombre de archivo (incluye indicador de sub-pagina)
$filename = 'HIS_Atenciones';
if ($sub === 'preventivas') $filename .= '_Preventivas';
if ($fAnio) $filename .= '_' . $fAnio;
if ($fMes)  $filename .= '_' . $fMes;
$filename .= '_' . date('Ymd_His') . '.xlsx';

// Crear y descargar Excel
$excel = new ExcelWriter();
$excel->addSheet('Atenciones HIS', $headers, $excelData, $widths);
$excel->download($filename);
exit;
