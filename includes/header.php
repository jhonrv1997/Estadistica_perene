<?php
/**
 * Header comun del sistema HIS
 */
if (!isset($pageTitle)) $pageTitle = 'Sistema HIS';
$currentpage = basename($_SERVER['PHP_SELF'], '.php');
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
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="fas fa-hospital-alt me-2 fs-4"></i>
                <span class="fw-bold">Sistema HIS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentpage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-chart-bar me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentpage === 'import' ? 'active' : '' ?>" href="import.php">
                            <i class="fas fa-file-import me-1"></i> Importar Datos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentpage === 'log' ? 'active' : '' ?>" href="log.php">
                            <i class="fas fa-clipboard-list me-1"></i> Log Auditoria
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center text-white">
                    <i class="fas fa-user-circle me-2"></i>
                    <span class="me-3"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['usuario']) ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="<?= isset($_SESSION['usuario_id']) ? 'main-content' : '' ?>">
