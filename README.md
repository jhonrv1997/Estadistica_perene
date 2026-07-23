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

### Paginas principales (9 modulos)

| # | Modulo | Archivo | Roles | Descripcion |
|---|--------|---------|-------|-------------|
| 01 | Convenio de Gestion | `convenio_gestion.php` | Todos | Reporte de avance de los 34 indicadores del Convenio MINSA-GORE 2026. |
| 02 | Convenio FED | `convenio_fed.php` | Todos | Reporte de avance de los 7 indicadores del Fondo de Estimulo al Desempeno, con calculo ponderado. |
| 03 | Consulta de Atenciones | `consulta_atenciones.php` | Todos | Busqueda del consolidado HIS con dos sub-paginas: Filtro General y Filtro Preventivas. |
| 04 | Control de Calidad | `control_calidad.php` | Todos | Reporte de observaciones encontradas en los datos consolidados. |
| 05 | Reporte de Atenciones | `reporte_atenciones.php` | Todos | 4 sub-reportes: Atenciones y Atendidos, Produccion Diario, Produccion Mensual, Reporte 40A. |
| 06 | Reportes Operacionales | `reporte_operacionales.php` | Todos | 17 sub-reportes por estrategia: Adolescente, Adulto, Adulto Mayor, Cancer, ESNI, Joven, Materno, Medicina Alternativa, Metaxenicas, Nino, No Transmisibles, Planificacion Familiar, Salud Bucal, Salud Mental, Salud Ocular, TBC, Zoonosis. |
| 07 | **Reporte ESNI (Inmunizaciones)** | `reporte_esni.php` | Todos | **NUEVO** Reporte Operacional completo de Inmunizaciones con 14 secciones (A-VPH), data-driven, con exportacion a Excel. Reemplaza el flujo manual SQL Server + Excel. |
| 08 | Importar Datos | `import.php` | Solo Admin | Carga de archivos ZIP (MaestroRegistrador, MaestroPersonal, MaestroPaciente, NominalTrama). |
| 09 | **Configurar ESNI** | `esni_config.php` | Solo Admin | **NUEVO** Gestion data-driven de vacunas, dosis, grupos de edad, secciones, lineas y reglas de mapeo. Reemplaza los stored procedures T-SQL hard-codeados. |
| 10 | Gestion de Usuarios | `usuarios.php` | Solo Admin | CRUD completo de usuarios: crear, editar, activar/desactivar, resetear clave, eliminar. |

### Sub-paginas

- **Consulta de Atenciones** (`?sub=general` | `?sub=preventivas`)
- **Reporte de Atenciones** (`?sub=atendidos` | `?sub=diario` | `?sub=mensual` | `?sub=40a`)
- **Reportes Operacionales** (`?sub=adolescente`, `?sub=adulto`, `?sub=adulto_mayor`, `?sub=cancer`, `?sub=esni` [redirige a `reporte_esni.php`], `?sub=joven`, `?sub=materno`, `?sub=medicina_alternativa`, `?sub=metaxenicas`, `?sub=nino`, `?sub=no_transmisibles`, `?sub=planificacion_familiar`, `?sub=salud_bucal`, `?sub=salud_mental`, `?sub=salud_ocular`, `?sub=tbc`, `?sub=zoonosis`)

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
|-- reporte_esni.php            # Pagina 07: Reporte ESNI (Inmunizaciones) - 14 secciones data-driven [NUEVO]
|-- esni_config.php             # Pagina 09: Configurar ESNI (vacunas, dosis, reglas) [NUEVO]
|-- esni_export.php             # Exportar reporte ESNI a Excel (.xlsx) [NUEVO]
|-- install_esni.php            # Asistente instalacion modulo ESNI (eliminar despues) [NUEVO]
|-- import.php                  # Pagina 08: Importar Datos (admin)
|-- usuarios.php                # Pagina 10: Gestion de Usuarios (admin)
|-- log.php                     # Log de auditoria
|-- process.php                 # Procesamiento de consolidacion
|-- export_excel.php            # Exportar a Excel
|-- logout.php                  # Cierre de sesion
|-- install.php                 # Asistente de instalacion (eliminar despues)
|-- api_estado_consolidacion.php
|-- INSTALL_esni.md             # Documentacion detallada del modulo ESNI [NUEVO]
|-- includes/
|   |-- auth.php                # Autenticacion y verificacion de roles
|   |-- functions.php           # Funciones comunes (consolidacion, importacion, etc.)
|   |-- esni_data.php           # Motor de reglas data-driven ESNI [NUEVO]
|   |-- header.php              # Navbar reorganizado con dropdowns
|   |-- footer.php              # Footer comun
|   `-- ExcelWriter.php         # Generador de Excel
|-- assets/
|   |-- css/style.css           # Estilos personalizados (incluye estilos ESNI)
|   `-- js/app.js               # JS personalizado
|-- Database/
|   |-- if0_42181393_his.sql    # Script base de tablas
|   `-- install_esni.sql        # Script de tablas ESNI (7 tablas data-driven) [NUEVO]
`-- uploads/                    # Archivos subidos
```

## Modulo ESNI (NUEVO)

Reemplaza el flujo manual anterior (SQL Server + 4 archivos .txt + Excel con conexion ODBC) por una solucion web data-driven.

**Flujo anterior (5 pasos manuales):**
1. SQL Server: ejecutar `01_DimESNI.txt`
2. SQL Server: ejecutar `02_TRAMA_BASE_ESNI_RPT_NOMINAL_CONSOLIDADO.txt`
3. SQL Server: ejecutar `03_StoredProcedure_TRAMA_BASE.txt`
4. SQL Server: ejecutar `04_USP_TRAMA_BASE_ESNI_2019_RPT.txt`
5. Excel: abrir `ReporteActividadesEsni2019.xlsx` y refrescar conexion ODBC

**Flujo nuevo (1 click):**
- Entrar a IntelHIS -> Reportes Operacionales -> ESNI -> Generar Reporte
- (Opcional) Exportar Excel

**Ventajas:**
- No requiere SQL Server (usa MySQL existente)
- No requiere Excel con conexion ODBC (exporta directamente desde la web)
- Agregar vacuna o dosis = 1 registro en la BD (no editar SQL)
- 14 secciones (A-VPH) con el mismo layout que el Excel oficial
- Filtros dinamicos por anio, mes, EE.SS., departamento, profesional

Ver [INSTALL_esni.md](INSTALL_esni.md) para instrucciones detalladas de instalacion y uso.

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

### Tablas nuevas v3.0 (modulo ESNI data-driven)

Reemplazan los stored procedures T-SQL hard-codeados. Se gestionan 100% desde la web (`esni_config.php`):

- `ESNI_VACUNA` - Maestro de vacunas (BCG, HVB, IPV, PENTA, ROTA, NEUMO, INF, SPR, VPH, ...)
- `ESNI_GRUPO_EDAD` - Grupos de edad (24H, 28D, 01_11M, 02_04A, GEST, RIESGO, COMORB, ...)
- `ESNI_DOSIS` - Tipos de dosis (D1, D2, D3, DU, REF1, REF2, ...)
- `ESNI_SECCION_REPORTE` - Secciones del reporte operacional (A, B, C, H, H2, I, J, K, L, M, N, O, P, VPH)
- `ESNI_LINEA_REPORTE` - Lineas dentro de cada seccion (vacuna + dosis + grupo edad + sexo)
- `ESNI_REGLA` - Reglas de mapeo (cod_item + valor_lab + edad + sexo -> linea)
- `ESNI_PARAMETRO` - Parametros generales (nombres de columnas de la tabla origen)

Instalacion: ejecutar `Database/install_esni.sql` o usar `install_esni.php` (asistente web).

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

**v3.0.0** - Modulo ESNI data-driven: reemplaza el flujo manual SQL Server + Excel por una solucion web completa. Incluye 7 tablas de configuracion (ESNI_*), motor de reglas en PHP, pagina de reporte con 14 secciones (A-VPH), pagina admin de configuracion, y exportacion a Excel.

**v2.0.0** - Reorganizacion completa con 8 modulos, roles de usuario y sub-paginas.
