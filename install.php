<?php
/**
 * Sistema de Gestion de Datos HIS
 * Script de Instalacion / Verificacion
 * Ejecutar una sola vez: http://tudominio.com/his/install.php
 * ELIMINAR este archivo despues de la instalacion
 */

// Configuracion temporal para instalacion
$db_host = $_POST['db_host'] ?? 'localhost';
$db_name = $_POST['db_name'] ?? '';
$db_user = $_POST['db_user'] ?? '';
$db_pass = $_POST['db_pass'] ?? '';

$step = $_POST['step'] ?? '1';
$error = '';
$success = '';
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    try {
        // Probar conexion
        $dsn = "mysql:host={$db_host};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `{$db_name}`");
        
        // Ejecutar schema SQL
        $sql = file_get_contents(__DIR__ . '/sql/database.sql');
        // Remover comentarios y ejecutar sentencias
        $pdo->exec($sql);
        
        $messages[] = "Base de datos '{$db_name}' creada/verificada correctamente.";
        $messages[] = "Tablas del sistema creadas correctamente.";
        $messages[] = "Usuario admin por defecto: <strong>admin</strong> / <strong>admin123</strong>";
        
        // Generar archivo config.php
        $configContent = "<?php\n";
        $configContent .= "/**\n * Sistema de Gestion de Datos HIS\n * Archivo de Configuracion - Generado automaticamente\n */\n\n";
        $configContent .= "define('DB_HOST', '{$db_host}');\n";
        $configContent .= "define('DB_NAME', '{$db_name}');\n";
        $configContent .= "define('DB_USER', '{$db_user}');\n";
        $configContent .= "define('DB_PASS', '{$db_pass}');\n";
        $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
        $configContent .= "define('APP_NAME', 'Sistema HIS - Gestion de Datos');\n";
        $configContent .= "define('APP_VERSION', '1.0.0');\n";
        $configContent .= "define('APP_URL', '');\n\n";
        $configContent .= "define('SESSION_TIMEOUT', 3600);\n\n";
        $configContent .= "define('UPLOAD_DIR', __DIR__ . '/uploads/');\n";
        $configContent .= "define('MAX_FILE_SIZE', 100 * 1024 * 1024);\n\n";
        $configContent .= "function getDBConnection() {\n";
        $configContent .= "    static \$pdo = null;\n";
        $configContent .= "    if (\$pdo === null) {\n";
        $configContent .= "        try {\n";
        $configContent .= "            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;\n";
        $configContent .= "            \$options = [\n";
        $configContent .= "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
        $configContent .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
        $configContent .= "                PDO::ATTR_EMULATE_PREPARES => false,\n";
        $configContent .= "                PDO::MYSQL_ATTR_LOCAL_INFILE => true,\n";
        $configContent .= "            ];\n";
        $configContent .= "            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);\n";
        $configContent .= "        } catch (PDOException \$e) {\n";
        $configContent .= "            error_log(\"Error de conexion BD: \" . \$e->getMessage());\n";
        $configContent .= "            die(\"Error de conexion a la base de datos. Verifique la configuracion.\");\n";
        $configContent .= "        }\n";
        $configContent .= "    }\n";
        $configContent .= "    return \$pdo;\n";
        $configContent .= "}\n\n";
        $configContent .= "date_default_timezone_set('America/Lima');\n\n";
        $configContent .= "if (session_status() === PHP_SESSION_NONE) {\n";
        $configContent .= "    session_start();\n";
        $configContent .= "}\n";
        
        file_put_contents(__DIR__ . '/config.php', $configContent);
        $messages[] = "Archivo config.php generado correctamente.";
        
        // Crear directorio uploads si no existe
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0777, true);
        }
        $messages[] = "Directorio uploads/ creado.";
        
        $step = '3';
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
        $step = '2';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalacion - Sistema HIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0e2f44 0%, #2980b9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-card {
            max-width: 600px;
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .install-header {
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: #fff;
            padding: 2rem;
            text-align: center;
        }
        .install-body {
            background: #fff;
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .step-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .step-dot.active { background: #1a5276; color: #fff; }
        .step-dot.completed { background: #27ae60; color: #fff; }
        .step-dot.pending { background: #e9ecef; color: #6c757d; }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-header">
            <i class="fas fa-hospital-alt fa-3x mb-3"></i>
            <h4 class="fw-bold">Sistema HIS</h4>
            <p class="mb-0 opacity-75">Asistente de Instalacion</p>
        </div>
        <div class="install-body">
            <!-- Step indicators -->
            <div class="step-indicator">
                <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending' ?>">
                    <?= $step > 1 ? '<i class="fas fa-check"></i>' : '1' ?>
                </div>
                <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending' ?>">
                    <?= $step > 2 ? '<i class="fas fa-check"></i>' : '2' ?>
                </div>
                <div class="step-dot <?= $step >= 3 ? 'active' : 'pending' ?>">3</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === '1' || $step === '2'): ?>
            <!-- Paso 1-2: Configuracion de BD -->
            <h6 class="fw-bold mb-3"><i class="fas fa-database me-2"></i>Configuracion de Base de Datos</h6>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Servidor (Host)</label>
                    <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($db_host) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre de Base de Datos</label>
                    <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($db_name) ?>" 
                           placeholder="ej: if0_42181393_his" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario</label>
                    <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($db_user) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contrasena</label>
                    <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($db_pass) ?>">
                </div>
                
                <div class="alert alert-info py-2">
                    <i class="fas fa-info-circle me-1"></i>
                    <small>Se creara la base de datos si no existe. Las tablas se crearan automaticamente.</small>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-cogs me-2"></i>Instalar Base de Datos
                </button>
            </form>
            
            <?php elseif ($step === '3'): ?>
            <!-- Paso 3: Instalacion completada -->
            <div class="text-center">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h5 class="fw-bold text-success mb-3">Instalacion Completada</h5>
                
                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-success py-2 text-start">
                        <i class="fas fa-check me-2"></i><?= $msg ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="alert alert-warning text-start">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Importante:</strong> Elimine el archivo <code>install.php</code> del servidor por seguridad.
                </div>
                
                <a href="index.php" class="btn btn-primary btn-lg mt-2">
                    <i class="fas fa-sign-in-alt me-2"></i>Acceder al Sistema
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
