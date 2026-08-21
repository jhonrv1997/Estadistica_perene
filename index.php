<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina de Login
 */

require_once 'config.php';
require_once 'includes/auth.php';

// Si ya esta autenticado, redirigir al home
if (isset($_SESSION['usuario_id'])) {
    header('Location: home.php');
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Ingrese usuario y contraseña';
    } else {
        if (iniciarSesion($usuario, $password)) {
            header('Location: home.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}

$pageTitle = 'HIS PERENE- Iniciar Sesion';
include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="assets/img/logo_hisminsa.png" alt="HIS" class="mb-3" style="width:100px;height:100px;object-fit:contain;border-radius:50%;">
            <h3 class="text-white fw-bold">HIS PERENE</h3>
            <p class="text-white-50 mb-0">Sistema de Analítica y Reportes Estadisticos</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($timeout): ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-clock me-2"></i>
                    Su sesion ha expirado. Inicie sesion nuevamente.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="usuario" class="form-label fw-semibold">
                        <i class="fas fa-user me-1"></i> Usuario
                    </label>
                    <input type="text" class="form-control form-control-lg" id="usuario" name="usuario" 
                           placeholder="Ingrese su usuario" required autofocus
                           value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">
                        <i class="fas fa-lock me-1"></i> Contraseña
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="password" name="password" 
                               placeholder="Ingrese su contraseña" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-his btn-lg w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesion
                </button>
            </form>
        </div>
        <div class="login-footer">
            <small class="text-muted">HIS PERENE v1.0 &copy; <?= date('Y') ?></small>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
