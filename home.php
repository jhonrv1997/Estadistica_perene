<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina principal (Home) - Se muestra despues del inicio de sesion.
 * Muestra las paginas disponibles segun el rol del usuario (admin / usuario).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();
$esAdmin = esAdmin();
$nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? 'Usuario';

// Estadisticas rapidas del sistema (solo si las tablas existen)
$statsSistema = [
    'total_atenciones' => 0,
    'total_establecimientos' => 0,
    'total_anios' => 0,
    'total_periodos_procesados' => 0,
];
try {
    $row = $pdo->query("SELECT
        COUNT(*) as total_atenciones,
        COUNT(DISTINCT Id_Establecimiento) as total_establecimientos,
        COUNT(DISTINCT Anio) as total_anios
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO")->fetch();
    if ($row) {
        $statsSistema['total_atenciones'] = (int)$row['total_atenciones'];
        $statsSistema['total_establecimientos'] = (int)$row['total_establecimientos'];
        $statsSistema['total_anios'] = (int)$row['total_anios'];
    }
    $statsSistema['total_periodos_procesados'] = (int)$pdo->query("SELECT COUNT(DISTINCT CONCAT(TRIM(Anio),'-',TRIM(Mes))) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Anio IS NOT NULL AND Mes IS NOT NULL")->fetchColumn();
} catch (Exception $e) {
    // Si la tabla no existe, los stats quedan en 0
}

// Definir las paginas disponibles por rol
// Cada entrada: [icono, titulo, descripcion, color, archivo, Roles]
$paginas = [
    // 1. Convenio de Gestion
    [
        'icon' => 'fa-file-contract',
        'titulo' => 'Convenio de Gestion',
        'descripcion' => 'Reporte de avance de los 34 indicadores del Convenio MINSA-GORE 2026.',
        'color' => 'primary',
        'archivo' => 'convenio_gestion.php',
        'badge' => '34 indicadores',
        'roles' => ['admin', 'usuario'],
    ],
    // 2. Convenio FED
    [
        'icon' => 'fa-medal',
        'titulo' => 'Convenio FED',
        'descripcion' => 'Fondo de Estimulo al Desempeno. Avance de 7 indicadores con calculo ponderado.',
        'color' => 'warning',
        'archivo' => 'convenio_fed.php',
        'badge' => '7 indicadores',
        'roles' => ['admin', 'usuario'],
    ],
    // 3. Consulta de Atenciones
    [
        'icon' => 'fa-search',
        'titulo' => 'Consulta de Atenciones',
        'descripcion' => 'Busqueda del consolidado HIS-MINSA. Filtro General y Filtro Preventivas.',
        'color' => 'info',
        'archivo' => 'consulta_atenciones.php?sub=general',
        'badge' => '2 sub-paginas',
        'roles' => ['admin', 'usuario'],
    ],
    // 4. Control de Calidad
    [
        'icon' => 'fa-clipboard-check',
        'titulo' => 'Control de Calidad',
        'descripcion' => 'Reporte de todas las observaciones encontradas en los datos consolidados.',
        'color' => 'danger',
        'archivo' => 'control_calidad.php',
        'badge' => 'Observaciones',
        'roles' => ['admin', 'usuario'],
    ],
    // 5. Reporte de Atenciones
    [
        'icon' => 'fa-file-medical',
        'titulo' => 'Reporte de Atenciones',
        'descripcion' => 'Atenciones y Atendidos, Produccion Diario, Produccion Mensual, Reporte 40A.',
        'color' => 'success',
        'archivo' => 'reporte_atenciones.php?sub=atendidos',
        'badge' => '4 sub-reportes',
        'roles' => ['admin', 'usuario'],
    ],
    // 6. Reportes Operacionales
    [
        'icon' => 'fa-chart-line',
        'titulo' => 'Reportes Operacionales',
        'descripcion' => '17 estrategias: Adolescente, Adulto, Materno, Nino, TBC, Cancer, etc.',
        'color' => 'primary',
        'archivo' => 'reporte_operacionales.php',
        'badge' => '17 estrategias',
        'roles' => ['admin', 'usuario'],
    ],
    // 7. Dashboard de Atenciones (consulta avanzada)
    [
        'icon' => 'fa-chart-bar',
        'titulo' => 'Dashboard de Atenciones',
        'descripcion' => 'Consulta detallada del consolidado con filtros avanzados y exportacion Excel.',
        'color' => 'info',
        'archivo' => 'dashboard.php',
        'badge' => 'Consulta avanzada',
        'roles' => ['admin', 'usuario'],
    ],
    // 8. Importar Datos (solo admin)
    [
        'icon' => 'fa-file-import',
        'titulo' => 'Importar Datos',
        'descripcion' => 'Carga de archivos ZIP: MaestroRegistrador, MaestroPersonal, MaestroPaciente, NominalTrama.',
        'color' => 'warning',
        'archivo' => 'import.php',
        'badge' => 'Solo Administrador',
        'roles' => ['admin'],
    ],
    // 9. Gestion de Usuarios (solo admin)
    [
        'icon' => 'fa-users-cog',
        'titulo' => 'Gestion de Usuarios',
        'descripcion' => 'Crear, editar, activar/desactivar y eliminar usuarios del sistema.',
        'color' => 'danger',
        'archivo' => 'usuarios.php',
        'badge' => 'Solo Administrador',
        'roles' => ['admin'],
    ],
    // 10. Log de Auditoria
    [
        'icon' => 'fa-clipboard-list',
        'titulo' => 'Log de Auditoria',
        'descripcion' => 'Historial de importaciones y procesamientos realizados en el sistema.',
        'color' => 'secondary',
        'archivo' => 'log.php',
        'badge' => 'Historial',
        'roles' => ['admin', 'usuario'],
    ],
];

// Filtrar paginas segun rol
$paginasDisponibles = array_filter($paginas, function($p) use ($esAdmin) {
    return in_array($esAdmin ? 'admin' : 'usuario', $p['roles'], true);
});

$pageTitle = 'Inicio - Sistema HIS';
include 'includes/header.php';
?>

<!-- Banner de bienvenida -->
<div class="card shadow-sm mb-4 welcome-banner">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="welcome-icon">
                <i class="fas fa-hospital-alt"></i>
            </div>
            <div>
                <h4 class="mb-1 fw-bold">Bienvenido, <?= htmlspecialchars($nombreUsuario) ?></h4>
                <p class="mb-0 text-muted">Sistema de Gestion de Datos HIS-MINSA &middot; <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
        <div class="text-end">
            <span class="badge <?= $esAdmin ? 'bg-warning text-dark' : 'bg-info' ?> p-2">
                <i class="fas <?= $esAdmin ? 'fa-user-shield' : 'fa-user' ?> me-1"></i>
                Rol: <?= $esAdmin ? 'Administrador' : 'Usuario' ?>
            </span>
        </div>
    </div>
</div>

<!-- Estadisticas del sistema -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsSistema['total_atenciones']) ?></span>
                <span class="stat-label">Atenciones Consolidadas</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-hospital"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsSistema['total_establecimientos']) ?></span>
                <span class="stat-label">Establecimientos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsSistema['total_anios']) ?></span>
                <span class="stat-label">Anios Procesados</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsSistema['total_periodos_procesados']) ?></span>
                <span class="stat-label">Periodos Procesados</span>
            </div>
        </div>
    </div>
</div>

<!-- Seccion: Paginas disponibles -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="fas fa-th-large me-2 text-primary"></i>Modulos disponibles</h5>
    <span class="text-muted small">
        <i class="fas fa-info-circle me-1"></i>
        Mostrando <?= count($paginasDisponibles) ?> modulos segun su rol
    </span>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($paginasDisponibles as $p): ?>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <a href="<?= htmlspecialchars($p['archivo']) ?>" class="home-card home-card-<?= htmlspecialchars($p['color']) ?>">
            <div class="home-card-icon">
                <i class="fas <?= htmlspecialchars($p['icon']) ?>"></i>
            </div>
            <div class="home-card-body">
                <h6 class="home-card-title"><?= htmlspecialchars($p['titulo']) ?></h6>
                <p class="home-card-desc"><?= htmlspecialchars($p['descripcion']) ?></p>
                <span class="home-card-badge"><?= htmlspecialchars($p['badge']) ?></span>
            </div>
            <div class="home-card-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Avisos / accesos rapidos -->
<?php if ($esAdmin): ?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-4 border-warning">
            <div class="card-body">
                <h6 class="fw-bold text-warning"><i class="fas fa-tools me-2"></i>Accesos rapidos de administracion</h6>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a href="import.php" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-file-import me-1"></i> Importar ZIP
                    </a>
                    <a href="usuarios.php" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
                    </a>
                    <a href="log.php" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-history me-1"></i> Ver Log
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <h6 class="fw-bold text-info"><i class="fas fa-lightbulb me-2"></i>Recordatorios</h6>
                <ul class="small mb-0 ps-3">
                    <li>Ejecute el script <code>Database/update_his_v2.sql</code> si aun no lo ha hecho.</li>
                    <li>Cambie la contrasena del usuario <code>admin</code> despues del primer login.</li>
                    <li>Verifique el estado de importacion antes de generar reportes.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm border-start border-4 border-info">
    <div class="card-body">
        <h6 class="fw-bold text-info"><i class="fas fa-info-circle me-2"></i>Informacion para usuarios</h6>
        <p class="small mb-0">
            Su rol <strong>Usuario</strong> le permite consultar reportes, convenios y observaciones de control de calidad.
            Si requiere importar datos o gestionar usuarios, contacte al administrador del sistema.
        </p>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
