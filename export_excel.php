<?php
/**
 * Sistema de Gestion de Datos HIS - Exportar a Excel (.xlsx REAL con autofiltros)
 *
 * Requisitos:
 *   - PHP >= 7.0 con extension ZipArchive habilitada
 *   - Carpeta temporal del servidor escribible (sys_get_temp_dir())
 *   - includes/ExcelWriter.php (clase corregida con autofiltros)
 */

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';
require_once 'includes/ExcelWriter.php';

// Polyfill de str_ends_with para PHP < 8.0
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len <= strlen($haystack) && substr($haystack, -$len) === $needle;
    }
}

// Directorio temporal alternativo si el del sistema no es escribible
if (!is_writable(sys_get_temp_dir())) {
    $localTmp = __DIR__ . '/includes/tmp';
    if (!is_dir($localTmp)) { @mkdir($localTmp, 0775, true); }
    if (is_writable($localTmp)) {
        putenv('TMPDIR=' . $localTmp);
        putenv('TMP=' . $localTmp);   // Windows
        putenv('TEMP=' . $localTmp);  // Windows
    }
}

$pdo = getDBConnection();

// ============================================================
// RECUPERAR FILTROS DESDE POST
// ============================================================
$sub = trim($_POST['filter_sub'] ?? 'general');
if (!in_array($sub, ['general', 'preventivas'], true)) {
    $sub = 'general';
}

$g = function (string $key) {
    $raw = $_POST[$key] ?? '';
    if (is_array($raw)) return implode(',', array_map('strval', $raw));
    return trim((string)$raw);
};

$fAnio            = $g('filter_anio');
$fMes             = $g('filter_mes');
$fZonaSanitaria   = $g('filter_zona_sanitaria');
$fDepartamento    = $g('filter_departamento');
$fUps             = $g('filter_ups');
$fEstablecimiento = $g('filter_establecimiento');
$fGrupoEdad       = $g('filter_grupo_edad');
$fIdGenero        = $g('filter_id_genero');
$fOtraCondicion   = $g('filter_otra_condicion');
$fCodigoItem      = $g('filter_codigo_item');
$fTipoDiagnostico = $g('filter_tipo_diagnostico');
$fLote            = $g('filter_lote');
$fNumPag          = $g('filter_num_pag');
$fNumReg          = $g('filter_num_reg');
$fValorLab        = $g('filter_valor_lab');
$fDocPersonal     = $g('filter_doc_personal');
$fDocPaciente     = $g('filter_doc_paciente');
$fDocRegistrador  = $g('filter_doc_registrador');

// ============================================================
// CONSTRUIR WHERE
// ============================================================
$where = "1=1";
$params = [];

if ($fAnio !== '') {
    $where .= " AND Anio = :anio";
    $params[':anio'] = $fAnio;
}
if ($fMes !== '') {
    $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes";
    $params[':mes'] = (int)$fMes;
}
if ($fZonaSanitaria !== '') {
    $where .= " AND Codigo_Unico IN (SELECT Codigo_Unico FROM ZSPERENE WHERE MicroRed = :microred)";
    $params[':microred'] = $fZonaSanitaria;
}
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

if ($fValorLab !== '') {
    if (str_ends_with($fValorLab, '*')) {
        $where .= " AND Valor_Lab LIKE :vlab";
        $params[':vlab'] = rtrim($fValorLab, '*') . '%';
    } else {
        $where .= " AND Valor_Lab = :vlab";
        $params[':vlab'] = $fValorLab;
    }
}

if ($fCodigoItem !== '') {
    $tokens = preg_split('/[\s,]+/', $fCodigoItem);
    $tokens = array_filter(array_map('trim', $tokens), fn($t) => $t !== '');
    if (!empty($tokens)) {
        $orParts = [];
        foreach ($tokens as $i => $tok) {
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
    $params[':numpag'] = (int)$fNumPag;
}
if ($fNumReg !== '') {
    $where .= " AND Num_Reg = :numreg";
    $params[':numreg'] = (int)$fNumReg;
}
if ($fDocPaciente !== '') {
    $docLen = strlen($fDocPaciente);
    if ($docLen >= 8) {
        $where .= " AND (Numero_Documento_Paciente = :dpac_exact OR Numero_Documento_Paciente LIKE :dpac_like OR Id_Paciente = :dpac_id_exact OR Id_Paciente LIKE :dpac_id_like)";
        $params[':dpac_exact'] = $fDocPaciente;
        $params[':dpac_like'] = '%' . $fDocPaciente . '%';
        $params[':dpac_id_exact'] = $fDocPaciente;
        $params[':dpac_id_like'] = '%' . $fDocPaciente . '%';
    } else {
        $where .= " AND (Numero_Documento_Paciente LIKE :dpac OR Id_Paciente LIKE :dpac_id)";
        $params[':dpac'] = '%' . $fDocPaciente . '%';
        $params[':dpac_id'] = '%' . $fDocPaciente . '%';
    }
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

// FILTRO PREVENTIVAS
if ($sub === 'preventivas') {
    $upsPreventivas = [];
    try {
        $upsPreventivas = $pdo->query(
            "SELECT DISTINCT Id_Ups FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
             WHERE Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%' OR Fg_Tipo = 'P'
             ORDER BY Descripcion_Ups LIMIT 200"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $upsPreventivas = [];
    }
    $placeholders = [];
    foreach ($upsPreventivas as $i => $uid) {
        if ($uid === null || $uid === '') continue;
        $k = ':pu' . $i;
        $placeholders[] = $k;
        $params[$k] = $uid;
    }
    if (!empty($placeholders)) {
        $where .= " AND (Id_Ups IN (" . implode(',', $placeholders) . ")"
                 . " OR Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
    } else {
        $where .= " AND (Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
    }
}

// ============================================================
// CONSULTA DE DATOS (sin limite)
// ============================================================
$campos = "Id_Cita, Anio, Mes, Dia, Fecha_Atencion, Lote, Num_Pag, Num_Reg,
           Codigo_Unico, Nombre_Establecimiento,
           Abrev_Tipo_Doc_Paciente, Numero_Documento_Paciente, Id_Paciente,
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
$datos = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// GENERAR EXCEL .xlsx REAL con autofiltros
// ============================================================
$headers = [
    'Id Cita','Anio','Mes','Dia','Fecha Atencion','Lote','Num Pag','Num Reg',
    'Codigo Unico','Establecimiento',
    'T. Doc. Pac.','Doc. Paciente','Id Paciente','Ape. Pat. Paciente','Ape. Mat. Paciente','Nombres Paciente',
    'Fecha Nacimiento','Genero','Tipo Edad','Edad','Grupo Edad','Etnia','Financiador',
    'Doc. Personal','T. Doc. Per.','Ape. Pat. Personal','Ape. Mat. Personal','Nombres Personal',
    'Profesion','Condicion',
    'Doc. Registrador','Ape. Pat. Reg.','Ape. Mat. Reg.','Nombres Registrador',
    'Codigo Item','Descripcion Item','Tipo','Tipo Diagnostico','Valor Lab',
    'Peso','Talla','Hemoglobina','Perim. Abdominal','Perim. Cefalico',
    'Otra Condicion','Centro Poblado','UPS','Fecha Registro','Fecha Modificacion'
];
$widths = [15,6,4,4,14,6,8,8,12,30,8,15,15,18,18,25,14,8,8,6,15,15,15,15,8,18,18,25,25,15,15,18,18,25,12,35,6,10,8,10,10,10,10,20,25,30,18,18];

$excelData = [];
foreach ($datos as $row) {
    $excelData[] = [
        $row['Id_Cita'] ?? '', $row['Anio'] ?? '', $row['Mes'] ?? '', $row['Dia'] ?? '',
        formatDate($row['Fecha_Atencion'] ?? ''),
        $row['Lote'] ?? '', $row['Num_Pag'] ?? '', $row['Num_Reg'] ?? '',
        $row['Codigo_Unico'] ?? '', $row['Nombre_Establecimiento'] ?? '',
        $row['Abrev_Tipo_Doc_Paciente'] ?? '', $row['Numero_Documento_Paciente'] ?? '',
        $row['Id_Paciente'] ?? '',
        $row['Apellido_Paterno_Paciente'] ?? '', $row['Apellido_Materno_Paciente'] ?? '', $row['Nombres_Paciente'] ?? '',
        formatDate($row['Fecha_Nacimiento_Paciente'] ?? ''),
        $row['Id_Genero'] ?? '', $row['Tipo_Edad'] ?? '', $row['Edad_Reg'] ?? '', $row['Grupo_Edad'] ?? '',
        $row['Descripcion_Etnia'] ?? '', $row['Descripcion_Financiador'] ?? '',
        $row['Numero_Documento_Personal'] ?? '', $row['Abrev_Tipo_Doc_Personal'] ?? '',
        $row['Apellido_Paterno_Personal'] ?? '', $row['Apellido_Materno_Personal'] ?? '', $row['Nombres_Personal'] ?? '',
        $row['Descripcion_Profesion'] ?? '', $row['Descripcion_Condicion'] ?? '',
        $row['Numero_Documento_Registrador'] ?? '', $row['Apellido_Paterno_Registrador'] ?? '', $row['Apellido_Materno_Registrador'] ?? '', $row['Nombres_Registrador'] ?? '',
        $row['Codigo_Item'] ?? '', $row['Descripcion_Item'] ?? '', $row['Fg_Tipo'] ?? '', $row['Tipo_Diagnostico'] ?? '', $row['Valor_Lab'] ?? '',
        $row['Peso'] ?? '', $row['Talla'] ?? '', $row['Hemoglobina'] ?? '',
        $row['Perimetro_Abdominal'] ?? '', $row['Perimetro_Cefalico'] ?? '',
        $row['Descripcion_Otra_Condicion'] ?? '', $row['Descripcion_Centro_Poblado'] ?? '', $row['Descripcion_Ups'] ?? '',
        formatDateTime($row['Fecha_Registro'] ?? ''), formatDateTime($row['Fecha_Modificacion'] ?? '')
    ];
}

$filename = 'HIS_Atenciones';
if ($sub === 'preventivas') $filename .= '_Preventivas';
if ($fAnio) $filename .= '_' . $fAnio;
if ($fMes)  $filename .= '_' . $fMes;
$filename .= '_' . date('Ymd_His') . '.xlsx';

// Generar y descargar .xlsx REAL
$excel = new ExcelWriter();
$excel->addSheet('Atenciones HIS', $headers, $excelData, $widths);
$excel->download($filename);
exit;
