<?php
/**
 * Sistema de Gestion de Datos HIS
 * Endpoint AJAX: Detalle de una Cita (popup modal desde Consulta General de Atenciones)
 *
 * Entrada (GET):
 *   - id_cita : Id_Cita del registro consultado
 *
 * Salida (JSON):
 *   {
 *     success: true|false,
 *     message: string (cuando hay error),
 *     id_cita: string,
 *     paciente: { nombre, documento, fecha_atencion, establecimiento },
 *     datos:    { peso, talla, hemoglobina, fecha_ultima_regla },
 *     items:    [ { codigo, descripcion, diag, lab } ]  // ordenado por Id_Correlativo_Item
 *   }
 *
 * Nota: TODOS los datos devueltos pertenecen a registros con la MISMA Id_Cita.
 */

require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

// Solo se permite metodo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDBConnection();

$idCita = trim($_GET['id_cita'] ?? '');

// Validacion basica de entrada
if ($idCita === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parametro id_cita requerido.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (mb_strlen($idCita) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parametro id_cita invalido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ------------------------------------------------------------
    // 1) Datos clinicos de la cita (mismos registros de la Id_Cita).
    //    Se usa MAX() porque los campos se repiten en cada fila de
    //    la misma cita y puede haber filas con valores NULL.
    // ------------------------------------------------------------
    $sqlDatos = "SELECT
            MAX(Peso)               AS Peso,
            MAX(Talla)              AS Talla,
            MAX(Hemoglobina)        AS Hemoglobina,
            MAX(Fecha_Ultima_Regla) AS Fecha_Ultima_Regla
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
        WHERE Id_Cita = :id_cita";
    $stmtDatos = $pdo->prepare($sqlDatos);
    $stmtDatos->execute([':id_cita' => $idCita]);
    $datosCita = $stmtDatos->fetch() ?: [];

    // ------------------------------------------------------------
    // 2) Contexto del paciente / atencion (para el encabezado del modal).
    //    Tambien proviene exclusivamente de registros con la misma Id_Cita.
    // ------------------------------------------------------------
    $sqlPaciente = "SELECT
            MAX(CONCAT(
                COALESCE(Apellido_Paterno_Paciente, ''), ' ',
                COALESCE(Apellido_Materno_Paciente, ''), ', ',
                COALESCE(Nombres_Paciente, '')
            ))                    AS Nombre_Paciente,
            MAX(COALESCE(NULLIF(Numero_Documento_Paciente, ''), Id_Paciente)) AS Documento,
            MAX(Fecha_Atencion)   AS Fecha_Atencion,
            MAX(Nombre_Establecimiento) AS Establecimiento
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
        WHERE Id_Cita = :id_cita";
    $stmtPaciente = $pdo->prepare($sqlPaciente);
    $stmtPaciente->execute([':id_cita' => $idCita]);
    $paciente = $stmtPaciente->fetch() ?: [];

    // ------------------------------------------------------------
    // 3) Items (diagnosticos / actividades) de la cita,
    //    ordenados segun Id_Correlativo_Item.
    //    Se muestra: Codigo_Item, Descripcion_Item, Tipo_Diagnostico, Valor_Lab.
    // ------------------------------------------------------------
    $sqlItems = "SELECT
            Codigo_Item      AS Codigo_Item,
            Descripcion_Item AS Descripcion_Item,
            Tipo_Diagnostico AS Tipo_Diagnostico,
            Valor_Lab        AS Valor_Lab
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
        WHERE Id_Cita = :id_cita
          AND Codigo_Item IS NOT NULL
        ORDER BY Id_Correlativo_Item ASC, Codigo_Item ASC";
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([':id_cita' => $idCita]);
    $itemsRaw = $stmtItems->fetchAll() ?: [];

    // Normalizar items al formato esperado por el modal:
    //   codigo | descripcion | diag | lab
    $items = [];
    foreach ($itemsRaw as $it) {
        $items[] = [
            'codigo'      => (string)($it['Codigo_Item'] ?? ''),
            'descripcion' => (string)($it['Descripcion_Item'] ?? ''),
            'diag'        => (string)($it['Tipo_Diagnostico'] ?? ''),
            'lab'         => ($it['Valor_Lab'] ?? null) !== null ? (string)$it['Valor_Lab'] : '',
        ];
    }

    // ------------------------------------------------------------
    // Normalizar datos clinicos (formateo ligero para mostrar)
    // ------------------------------------------------------------
    $formateaDecimal = function ($valor, $decimales) {
        if ($valor === null || $valor === '') {
            return '';
        }
        return number_format((float)$valor, $decimales, '.', '');
    };

    $datos = [
        // Peso en kg (3 decimales en BD, se muestra con 2)
        'peso'               => $formateaDecimal($datosCita['Peso'] ?? null, 2),
        // Talla en m (2 decimales en BD)
        'talla'              => $formateaDecimal($datosCita['Talla'] ?? null, 2),
        // Hemoglobina en g/dL (2 decimales en BD, se muestra con 1)
        'hemoglobina'        => $formateaDecimal($datosCita['Hemoglobina'] ?? null, 1),
        // Fecha de Ultima Regla (FUR) en formato d/m/Y
        'fecha_ultima_regla' => formatDate($datosCita['Fecha_Ultima_Regla'] ?? ''),
    ];

    $respuesta = [
        'success' => true,
        'id_cita' => $idCita,
        'paciente' => [
            'nombre'          => trim((string)($paciente['Nombre_Paciente'] ?? '')),
            'documento'       => (string)($paciente['Documento'] ?? ''),
            'fecha_atencion'  => formatDate($paciente['Fecha_Atencion'] ?? ''),
            'establecimiento' => (string)($paciente['Establecimiento'] ?? ''),
        ],
        'datos' => $datos,
        'items' => $items,
    ];

    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('api_detalle_cita.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el detalle de la cita. Intente nuevamente.',
    ], JSON_UNESCAPED_UNICODE);
}
