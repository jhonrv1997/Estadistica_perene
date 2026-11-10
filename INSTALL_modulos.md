# Instalacion: Acceso por Modulos

Sistema de permisos por modulo para usuarios no administradores.
El rol **Administrador** siempre tiene acceso a todo; para el resto de usuarios
el administrador asigna modulo por modulo desde `usuarios.php`.

## Modulos protegidos

| Modulo | Archivo(s) | Item en el navbar |
| --- | --- | --- |
| `consulta_atenciones` | consulta_atenciones.php | Atenciones |
| `reporte_operacionales` | reporte_operacionales.php + reporte_cancer.php, reporte_esni.php, reporte_its.php, reporte_materno.php, reporte_no_transmisibles.php, reporte_zoonosis.php | Rep. Operac. |
| `convenio_gestion` | convenio_gestion.php | Convenios > Convenio de Gestion |
| `convenio_fed` | convenio_fed.php | Convenios > Convenio FED |
| `control_calidad` | control_calidad.php | C. Calidad |
| `reporte_atenciones` | reporte_atenciones.php | Rep. Atenc. |
| `log` | log.php | Log |

## Pasos de instalacion

1. Suba al servidor los archivos modificados de esta version.
2. Abra en el navegador, con una cuenta de administrador:

       https://hisperene.gt.tc/install_modulos.php

   Este asistente crea la tabla `USUARIO_MODULOS` (id_permiso, id_usuario,
   modulo, asignado_por, fecha_asignacion) con indice unico
   (id_usuario, modulo).
3. Entre a **Gestion de Usuarios** (`usuarios.php`):
   - Use el boton de escudo (Accesos por modulos) de cada fila para marcar
     los modulos de cada usuario.
   - Tambien puede marcar los modulos al crear un usuario nuevo o al editarlo.
4. Por seguridad, **elimine `install_modulos.php` del servidor** tras usarlo.

## Comportamiento

- Un usuario sin modulos asignados solo vera "Inicio" en el menu y no podra
  abrir ninguna pagina de modulo (redireccion a `home.php?acceso_denegado=1`).
- El menu (navbar) y las tarjetas de `home.php` se filtran automaticamente
  segun los modulos asignados: los items sin permiso no se muestran.
- Si un usuario es promovido a rol `admin`, sus registros de la tabla
  `USUARIO_MODULOS` se eliminan (no los necesita: el rol admin accede a todo).
- Al eliminar un usuario se limpian sus registros de `USUARIO_MODULOS`.
- Si administra la tabla manualmente y quedan registros huerfanos, puede
  limpiarlos abriendo `install_modulos.php?sincronizar=1`.

## Archivos modificados

- `includes/auth.php` — catalogo `MODULOS_SISTEMA` y funciones
  `getModulosUsuario()`, `puedeAccederModulo()`, `verificarModulo()`,
  `modulosDelUsuarioActual()`.
- `includes/header.php` — navbar filtrado por permisos.
- `home.php` — tarjetas filtradas por permisos + alerta de acceso denegado.
- `usuarios.php` — columna y boton de accesos, modal de asignacion,
  checkboxes en crear/editar, accion `asignar_modulos`.
- Los 13 archivos de paginas de modulos listados arriba.
- `Database/if0_42181393_his.sql` — estructura de la tabla `USUARIO_MODULOS`.
- `install_modulos.php` — asistente de instalacion (nuevo).
