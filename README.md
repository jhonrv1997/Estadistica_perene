# Sistema HIS - Gestion de Datos HIS-MINSA

Sistema web para la gestion y consolidacion de datos del HIS (Historia de Salud) del MINSA Peru.

## Tecnologias

- **Backend:** PHP 7.4+ / 8.x
- **Base de datos:** MySQL 5.7+ / MariaDB 10.x
- **Frontend:** Bootstrap 5, Font Awesome 6, jQuery 3
- **Hosting recomendado:** Cpanel / cualquier hosting con PHP + MySQL

## Roles de Usuario

El sistema soporta dos roles:

| Rol | Permisos |
|-----|----------|
| **Administrador** (`admin`) | Acceso completo: importar datos, gestionar usuarios, todos los reportes, log de auditoria. |
| **Usuario** (`usuario`) | Acceso de solo lectura a reportes, convenios, consultas y control de calidad. No puede importar datos ni gestionar usuarios. |

## Estructura de Paginas

### Paginas principales (8 modulos)

| # | Modulo | Archivo | Roles | Descripcion |
|---|--------|---------|-------|-------------|
| 01 | Convenio de Gestion | `convenio_gestion.php` | Todos | Reporte de avance de los 34 indicadores del Convenio MINSA-GORE 2026. |
| 02 | Convenio FED | `convenio_fed.php` | Todos | Reporte de avance de los 7 indicadores del Fondo de Estimulo al Desempeno, con calculo ponderado. |
| 03 | Consulta de Atenciones | `consulta_atenciones.php` | Todos | Busqueda del consolidado HIS con dos sub-paginas: Filtro General y Filtro Preventivas. |
| 04 | Control de Calidad | `control_calidad.php` | Todos | Reporte de observaciones encontradas en los datos consolidados. |
| 05 | Reporte de Atenciones | `reporte_atenciones.php` | Todos | 4 sub-reportes: Atenciones y Atendidos, Produccion Diario, Produccion Mensual, Reporte 40A. |
| 06 | Reportes Operacionales | `reporte_operacionales.php` | Todos | 17 sub-reportes por estrategia: Adolescente, Adulto, Adulto Mayor, Cancer, ESNI, Joven, Materno, Medicina Alternativa, Metaxenicas, Nino, No Transmisibles, Planificacion Familiar, Salud Bucal, Salud Mental, Salud Ocular, TBC, Zoonosis. |
| 07 | Importar Datos | `import.php` | Solo Admin | Carga de archivos ZIP (MaestroRegistrador, MaestroPersonal, MaestroPaciente, NominalTrama). |
| 08 | Gestion de Usuarios | `usuarios.php` | Solo Admin | CRUD completo de usuarios: crear, editar, activar/desactivar, resetear clave, eliminar. |

### Sub-paginas

- **Consulta de Atenciones** (`?sub=general` | `?sub=preventivas`)
- **Reporte de Atenciones** (`?sub=atendidos` | `?sub=diario` | `?sub=mensual` | `?sub=40a`)
- **Reportes Operacionales** (`?sub=adolescente`, `?sub=adulto`, `?sub=adulto_mayor`, `?sub=cancer`, `?sub=esni`, `?sub=joven`, `?sub=materno`, `?sub=medicina_alternativa`, `?sub=metaxenicas`, `?sub=nino`, `?sub=no_transmisibles`, `?sub=planificacion_familiar`, `?sub=salud_bucal`, `?sub=salud_mental`, `?sub=salud_ocular`, `?sub=tbc`, `?sub=zoonosis`)

### Otras paginas

- `index.php` - Login
- `dashboard.php` - Dashboard general de atenciones con filtros
- `log.php` - Log de auditoria de importaciones y procesamientos
- `process.php` - Ejecucion del procesamiento/consolidacion
- `export_excel.php` - Exportacion de resultados a Excel
- `logout.php` - Cierre de sesion
- `home.php` -Pagina principal

## Estructura de Archivos

```
/
|-- config.php                  # Configuracion de BD y aplicacion
|-- index.php                   # Pagina de login
|-- home.php                    # Pagina Principal
|-- dashboard.php               # Pagina para filtros
|-- convenio_gestion.php        # Pagina 01: Convenio de Gestion (34 indicadores)
|-- convenio_fed.php            # Pagina 02: Convenio FED (7 indicadores)
|-- consulta_atenciones.php     # Pagina 03: Consulta de Atenciones
|-- control_calidad.php         # Pagina 04: Control de Calidad
|-- control_calidad_guardar.php # Guardar observaciones (POST)
|-- reporte_atenciones.php      # Pagina 05: Reporte de Atenciones (4 sub-reportes)
|-- reporte_operacionales.php   # Pagina 06: Reportes Operacionales (17 sub-reportes)
|-- import.php                  # Pagina 07: Importar Datos (admin)
|-- usuarios.php                # Pagina 08: Gestion de Usuarios (admin)
|-- log.php                     # Log de auditoria
|-- process.php                 # Procesamiento de consolidacion
|-- export_excel.php            # Exportar a Excel
|-- logout.php                  # Cierre de sesion
|-- install.php                 # Asistente de instalacion (eliminar despues)
|-- api_estado_consolidacion.php
|-- includes/
|   |-- auth.php                # Autenticacion y verificacion de roles
|   |-- functions.php           # Funciones comunes (consolidacion, importacion, etc.)
|   |-- header.php              # Navbar reorganizado con dropdowns
|   |-- footer.php              # Footer comun
|   `-- ExcelWriter.php         # Generador de Excel
|-- assets/
|   |-- css/style.css           # Estilos personalizados
|   `-- js/app.js               # JS personalizado
|-- Database/
|   |-- if0_42181393_his.sql    # Script base de tablas
|   `-- update_his_v2.sql       # Script de actualizacion v2 (Convenios + Control Calidad)
`-- uploads/                    # Archivos subidos
```

## Base de Datos

### Tablas principales

- `USUARIOS` - Usuarios del sistema (con columna `rol`: 'admin' o 'usuario')
- `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` - Tabla consolidada principal
- `NOMINAL_TRAMA_NUEVO` - Trama nominal importada
- `MAESTRO_PERSONAL`, `MAESTRO_PACIENTE`, `MAESTRO_REGISTRADOR` - Maestros importados
- `MAESTRO_HIS_*` - Tablas maestras del HIS (establecimientos, UPS, financiadores, etc.)
- `IMPORT_ESTADO` - Estado de importacion de los 4 archivos
- `LOG_IMPORTACION` - Log de auditoria de importaciones y procesamientos
- `NOMINAL_TRAMA_PERIODOS` - Periodos importados
- `ZSPERENE` - Establecimientos Perene

### Tablas nuevas v2.0

- `CONVENIO_GESTION_INDICADORES` - 34 indicadores del Convenio MINSA-GORE 2026
- `CONVENIO_GESTION_AVANCE` - Avance periodico por indicador
- `CONVENIO_FED_INDICADORES` - 7 indicadores del Fondo de Estimulo al Desempeno
- `CONVENIO_FED_AVANCE` - Avance periodico por indicador FED
- `CONTROL_CALIDAD_OBSERVACIONES` - Observaciones de control de calidad

## Seguridad

- **Contrasenas:** Se almacenan con hash bcrypt (`password_hash` de PHP).
- **CSRF:** Token CSRF en formularios de importacion y usuarios.
- **Sesiones:** Timeout de 1 hora por inactividad.
- **Control de acceso:** Las paginas de administracion (import.php, usuarios.php) verifican rol admin con `verificarAdmin()`.
- **Proteccion del admin principal:** El usuario con `id=1` no puede ser eliminado, desactivado ni degradado.

## Configuracion

Editar `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'su_base_de_datos');
define('DB_USER', 'su_usuario');
define('DB_PASS', 'su_contrasena');
define('APP_URL', '');  // URL base, ej: https://tudominio.com/his
define('SESSION_TIMEOUT', 3600);  // 1 hora
define('MAX_FILE_SIZE', 100 * 1024 * 1024);  // 100 MB
```

## Flujo de Trabajo Tipico

1. **Importar datos (Admin):** Subir los 4 archivos ZIP en `import.php`.
2. **Procesar consolidacion (Admin):** Ejecutar el procesamiento desde `import.php` o `process.php`.
3. **Consultar atenciones:** Usar `consulta_atenciones.php` para buscar registros.
4. **Revisar reportes:** Generar reportes desde `reporte_atenciones.php` y `reporte_operacionales.php`.
5. **Control de calidad:** Revisar observaciones en `control_calidad.php`.
6. **Convenios:** Actualizar avance de indicadores en `convenio_gestion.php` y `convenio_fed.php`.
7. **Auditoria:** Revisar `log.php` para auditoria de operaciones.

## Version

**v2.0.0** - Reorganizacion completa con 8 modulos, roles de usuario y sub-paginas.
