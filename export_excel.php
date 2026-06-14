<?php
/**
 * Sistema de Gestion de Datos HIS
 * Exportar resultados a Excel
 */

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';
require_once 'includes/ExcelWriter.php';

$pdo = getDBConnection();

// Reconstruir filtros desde POST
$fAnio = $_POST['filter_anio'] ?? '';
$fMes = $_POST['filter_mes'] ?? '';
$fEstablecimiento = $_POST['filter_establecimiento'] ?? '';
$fGrupoEdad = $_POST['filter_grupo_edad'] ?? '';
$fCodigoItem = trim($_POST['filter_codigo_item'] ?? '');
$fTipoDiagnostico = $_POST['filter_tipo_diagnostico'] ?? '';
$fValorLab = $_POST['filter_valor_lab'] ?? '';
$fDocPersonal = trim($_POST['filter_doc_personal'] ?? '');
$fDocPaciente = trim($_POST['filter_doc_paciente'] ?? '');

// Construir consulta con filtros (igual que dashboard)
$where = "1=1";
$params = [];

if ($fAnio !== '') {
    $where .= " AND Anio = :anio";
    $params[':anio'] = $fAnio;
}
if ($fMes !== '') {
    $where .= " AND Mes = :mes";
    $params[':mes'] = $fMes;
}
if ($fEstablecimiento !== '') {
    $where .= " AND Nombre_Establecimiento = :establecimiento";
    $params[':establecimiento'] = $fEstablecimiento;
}
if ($fGrupoEdad !== '') {
    $where .= " AND Grupo_Edad = :grupo_edad";
    $params[':grupo_edad'] = $fGrupoEdad;
}
if ($fCodigoItem !== '') {
    $where .= " AND Codigo_Item LIKE :codigo_item";
    $params[':codigo_item'] = '%' . $fCodigoItem . '%';
}
if ($fTipoDiagnostico !== '') {
    $where .= " AND Tipo_Diagnostico = :tipo_diagnostico";
    $params[':tipo_diagnostico'] = $fTipoDiagnostico;
}
if ($fValorLab !== '') {
    $where .= " AND Valor_Lab = :valor_lab";
    $params[':valor_lab'] = $fValorLab;
}
if ($fDocPersonal !== '') {
    $where .= " AND Numero_Documento_Personal LIKE :doc_personal";
    $params[':doc_personal'] = '%' . $fDocPersonal . '%';
}
if ($fDocPaciente !== '') {
    $where .= " AND Numero_Documento_Paciente LIKE :doc_paciente";
    $params[':doc_paciente'] = '%' . $fDocPaciente . '%';
}

// Consulta de datos para exportar (sin limite)
$campos = "Id_Cita, Anio, Mes, Dia, Fecha_Atencion, Descripcion_Ups, 
           Nombre_Establecimiento, Codigo_Unico,
           Numero_Documento_Paciente, Apellido_Paterno_Paciente, Apellido_Materno_Paciente, 
           Nombres_Paciente, Fecha_Nacimiento_Paciente, Id_Genero, Grupo_Edad,
           Descripcion_Etnia, Descripcion_Financiador,
           Numero_Documento_Personal, Apellido_Paterno_Personal, Apellido_Materno_Personal, Nombres_Personal,
           Descripcion_Profesion, Descripcion_Condicion,
           Numero_Documento_Registrador, Apellido_Paterno_Registrador, Nombres_Registrador,
           Codigo_Item, Descripcion_Item, Fg_Tipo, Tipo_Diagnostico, Valor_Lab,
           Peso, Talla, Hemoglobina, Perimetro_Abdominal, Perimetro_Cefalico,
           Descripcion_Otra_Condicion, Descripcion_Centro_Poblado,
           Fecha_Registro, Fecha_Modificacion";

$dataSql = "SELECT {$campos} FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE {$where} ORDER BY Fecha_Atencion DESC";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$datos = $dataStmt->fetchAll();

// Encabezados del Excel
$headers = [
    'Id Cita', 'Anio', 'Mes', 'Dia', 'Fecha Atencion', 'UPS',
    'Establecimiento', 'Codigo Unico',
    'Doc. Paciente', 'Ape. Pat. Paciente', 'Ape. Mat. Paciente', 'Nombres Paciente',
    'Fecha Nacimiento', 'Genero', 'Grupo Edad', 'Etnia', 'Financiador',
    'Doc. Personal', 'Ape. Pat. Personal', 'Ape. Mat. Personal', 'Nombres Personal',
    'Profesion', 'Condicion',
    'Doc. Registrador', 'Ape. Pat. Registrador', 'Nombres Registrador',
    'Codigo Item', 'Descripcion Item', 'Tipo', 'Tipo Diagnostico', 'Valor Lab',
    'Peso', 'Talla', 'Hemoglobina', 'Perim. Abdominal', 'Perim. Cefalico',
    'Otra Condicion', 'Centro Poblado',
    'Fecha Registro', 'Fecha Modificacion'
];

// Anchos de columna
$widths = [15, 6, 4, 4, 14, 25, 30, 12, 15, 18, 18, 25, 14, 8, 15, 15, 15, 15, 18, 18, 25, 25, 15, 15, 18, 25, 12, 35, 6, 10, 8, 10, 10, 10, 10, 10, 20, 25, 18, 18];

// Preparar datos para el Excel
$excelData = [];
foreach ($datos as $row) {
    $excelData[] = [
        $row['Id_Cita'],
        $row['Anio'],
        $row['Mes'],
        $row['Dia'],
        formatDate($row['Fecha_Atencion']),
        $row['Descripcion_Ups'],
        $row['Nombre_Establecimiento'],
        $row['Codigo_Unico'],
        $row['Numero_Documento_Paciente'],
        $row['Apellido_Paterno_Paciente'],
        $row['Apellido_Materno_Paciente'],
        $row['Nombres_Paciente'],
        formatDate($row['Fecha_Nacimiento_Paciente']),
        $row['Id_Genero'],
        $row['Grupo_Edad'],
        $row['Descripcion_Etnia'],
        $row['Descripcion_Financiador'],
        $row['Numero_Documento_Personal'],
        $row['Apellido_Paterno_Personal'],
        $row['Apellido_Materno_Personal'],
        $row['Nombres_Personal'],
        $row['Descripcion_Profesion'],
        $row['Descripcion_Condicion'],
        $row['Numero_Documento_Registrador'],
        $row['Apellido_Paterno_Registrador'],
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
        formatDateTime($row['Fecha_Registro']),
        formatDateTime($row['Fecha_Modificacion'])
    ];
}

// Generar nombre de archivo
$filename = 'HIS_Atenciones';
if ($fAnio) $filename .= '_' . $fAnio;
if ($fMes) $filename .= '_' . $fMes;
$filename .= '_' . date('Ymd_His') . '.xlsx';

// Crear y descargar Excel
$excel = new ExcelWriter();
$excel->addSheet('Atenciones HIS', $headers, $excelData, $widths);
$excel->download($filename);
exit;
