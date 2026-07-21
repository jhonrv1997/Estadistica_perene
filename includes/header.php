<?php
/**
 * Header comun del sistema HIS
 * Navbar reorganizado con 8 modulos y sub-paginas.
 * Items de administracion (Importar, Usuarios) solo se muestran a rol admin.
 */

if (!isset($pageTitle)) $pageTitle = 'IntelHIS';
$currentpage = basename($_SERVER['PHP_SELF'], '.php');
$subpage = $_GET['sub'] ?? '';
$esAdmin = esAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if (isset($_SESSION['usuario_id'])): ?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-his">
    <div class="container-fluid">

        <a class="navbar-brand d-flex align-items-center" href="home.php">
            <img src="assets/img/logo_hisminsa.png" alt="HIS" class="me-2"
                 style="width:28px;height:28px;object-fit:contain;border-radius:50%;">
            <span class="fw-bold">IntelHIS</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ====== MENÚ DE NAVEGACIÓN (colapsable) ====== -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">

                <!-- Inicio -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentpage === 'home' ? 'active' : '' ?>" href="home.php">
                        <i class="fas fa-home me-1"></i> Inicio
                    </a>
                </li>

                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentpage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-chart-bar me-1"></i> Dashboard
                    </a>
                </li>

                <!-- Convenios (dropdown) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentpage, ['convenio_gestion', 'convenio_fed']) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-handshake me-1"></i> Convenios
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'convenio_gestion' ? 'active' : '' ?>" href="convenio_gestion.php">
                                <i class="fas fa-file-contract me-2 text-primary"></i>Convenio de Gestion
                                <small class="d-block text-muted">MINSA-GORE 2026 (34 indicadores)</small>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'convenio_fed' ? 'active' : '' ?>" href="convenio_fed.php">
                                <i class="fas fa-medal me-2 text-warning"></i>Convenio FED
                                <small class="d-block text-muted">Fondo de Estimulo al Desempeno (7 indicadores)</small>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Consulta de Atenciones -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentpage, ['consulta_atenciones']) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-search me-1"></i> Atenciones
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'consulta_atenciones' && $subpage === 'general' ? 'active' : '' ?>"
                               href="consulta_atenciones.php?sub=general">
                                <i class="fas fa-list-alt me-2 text-info"></i>Filtro General
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'consulta_atenciones' && $subpage === 'preventivas' ? 'active' : '' ?>"
                               href="consulta_atenciones.php?sub=preventivas">
                                <i class="fas fa-shield-alt me-2 text-success"></i>Filtro Preventivas
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Control de Calidad -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentpage === 'control_calidad' ? 'active' : '' ?>" href="control_calidad.php">
                        <i class="fas fa-clipboard-check me-1"></i> C. Calidad
                    </a>
                </li>

                <!-- Reporte de Atenciones (dropdown) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentpage, ['reporte_atenciones']) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-medical me-1"></i> Rep. Atenc.
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_atenciones' && $subpage === 'atendidos' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=atendidos"><i class="fas fa-users me-2 text-primary"></i>Atenciones y Atendidos</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_atenciones' && $subpage === 'diario' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=diario"><i class="fas fa-calendar-day me-2 text-info"></i>Produccion Diario</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_atenciones' && $subpage === 'mensual' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=mensual"><i class="fas fa-calendar-alt me-2 text-warning"></i>Produccion Mensual</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_atenciones' && $subpage === '40a' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=40a"><i class="fas fa-file-invoice me-2 text-danger"></i>Reporte 40A</a></li>
                    </ul>
                </li>

                <!-- Reportes Operacionales (dropdown) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentpage, ['reporte_operacionales']) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-line me-1"></i> Rep. Operac.
                    </a>
                    <ul class="dropdown-menu dropdown-menu-scroll">
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'adolescente' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=adolescente"><i class="fas fa-user-graduate me-2"></i>Adolescente</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'adulto' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=adulto"><i class="fas fa-user me-2"></i>Adulto</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'adulto_mayor' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=adulto_mayor"><i class="fas fa-user-tie me-2"></i>Adulto Mayor</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'cancer' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=cancer"><i class="fas fa-ribbon me-2"></i>Cancer</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'esni' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=esni"><i class="fas fa-syringe me-2"></i>ESNI</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'joven' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=joven"><i class="fas fa-walking me-2"></i>Joven</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'materno' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=materno"><i class="fas fa-baby me-2"></i>Materno</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'medicina_alternativa' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=medicina_alternativa"><i class="fas fa-leaf me-2"></i>Medicina Alternativa</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'metaxenicas' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=metaxenicas"><i class="fas fa-virus me-2"></i>Metaxenicas</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'nino' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=nino"><i class="fas fa-child me-2"></i>Nino</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'no_transmisibles' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=no_transmisibles"><i class="fas fa-heartbeat me-2"></i>No Transmisibles</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'planificacion_familiar' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=planificacion_familiar"><i class="fas fa-people-arrows me-2"></i>Planificacion Familiar</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'salud_bucal' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=salud_bucal"><i class="fas fa-tooth me-2"></i>Salud Bucal</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'salud_mental' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=salud_mental"><i class="fas fa-brain me-2"></i>Salud Mental</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'salud_ocular' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=salud_ocular"><i class="fas fa-eye me-2"></i>Salud Ocular</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'tbc' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=tbc"><i class="fas fa-lungs me-2"></i>TBC</a></li>
                        <li><a class="dropdown-item <?= $currentpage === 'reporte_operacionales' && $subpage === 'zoonosis' ? 'active' : '' ?>" href="reporte_operacionales.php?sub=zoonosis"><i class="fas fa-paw me-2"></i>Zoonosis</a></li>
                    </ul>
                </li>

                <?php if ($esAdmin): ?>
                <!-- Administracion (dropdown) - solo admin -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentpage, ['import', 'usuarios', 'log']) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'import' ? 'active' : '' ?>" href="import.php">
                                <i class="fas fa-file-import me-2 text-primary"></i>Importar Datos
                                <small class="d-block text-muted">Carga de archivos ZIP</small>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'usuarios' ? 'active' : '' ?>" href="usuarios.php">
                                <i class="fas fa-users-cog me-2 text-success"></i>Gestion de Usuarios
                                <small class="d-block text-muted">Crear y administrar usuarios</small>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?= $currentpage === 'log' ? 'active' : '' ?>" href="log.php">
                                <i class="fas fa-clipboard-list me-2 text-info"></i>Log de Auditoria
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <!-- Log de auditoria visible para todos -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentpage === 'log' ? 'active' : '' ?>" href="log.php">
                        <i class="fas fa-clipboard-list me-1"></i> Log
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </div>
        <!-- ====== FIN MENÚ DE NAVEGACIÓN ====== -->

        <!-- ====== OPCIONES DE USUARIO (siempre visibles, fuera del collapse) ====== -->
        <div class="d-flex align-items-center text-white ms-lg-3 flex-shrink-0">
            <i class="fas fa-user-circle me-2"></i>
            
            <?php if ($esAdmin): ?>
                <span class="badge bg-warning text-dark me-2 d-none d-md-inline">Admin</span>
            <?php else: ?>
                <span class="badge bg-light text-dark me-2 d-none d-md-inline">Usuario</span>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt me-1"></i> Salir
            </a>
        </div>
        <!-- ====== FIN OPCIONES DE USUARIO ====== -->

    </div>
</nav>
<?php endif; ?>