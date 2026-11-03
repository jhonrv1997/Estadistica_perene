================================================================
 SEPARACIÓN DEL MÓDULO CONVENIO FED - INSTRUCCIONES
 Sistema HIS Perené - 2026-11-04
================================================================

QUÉ CAMBIÓ
----------
El antiguo convenio_fed.php (1,858 líneas, con todo junto) ahora se
divide en 3 páginas independientes:

  1) convenio_fed.php
     Solo consulta y visualización (todos los roles): filtros,
     resumen rápido, avance global ponderado, detalle por
     establecimiento/mes, resumen por indicador, resumen por
     establecimiento y vista de tarjetas.
     Compatibilidad: las URL antiguas convenio_fed.php?tab=gestion y
     convenio_fed.php?tab=importar redirigen automáticamente a las
     páginas nuevas.

  2) fed_gestion_indicadores.php  (NUEVO)
     "FED: Gestion de Indicadores" (solo Administrador):
     agregar, modificar, activar/desactivar indicadores.

  3) fed_importar_avances.php  (NUEVO)
     "FED: Importar Avances" (solo Administrador):
     carga del Resultados.txt (formatos 8 y 13 columnas), modos
     actualizar/reemplazar e historial de importaciones.

ARCHIVOS INCLUIDOS (conservar esta estructura de carpetas)
----------------------------------------------------------
  convenio_fed.php                -> raíz del sitio (REEMPLAZA)
  fed_gestion_indicadores.php     -> raíz del sitio (NUEVO)
  fed_importar_avances.php        -> raíz del sitio (NUEVO)
  includes/fed_common.php         -> carpeta includes/ (NUEVO)
  includes/header.php             -> carpeta includes/ (REEMPLAZA)
  cambios_fed.patch               -> parche git (opcional)

CÓMO INSTALAR
-------------
Opción A (archivos sueltos):
  1. Haga copia de seguridad de convenio_fed.php e includes/header.php.
  2. Suba los 5 archivos a las rutas indicadas arriba.
  3. No requiere cambios en la base de datos.

Opción B (parche git):
  git apply cambios_fed.patch

QUÉ VERIFICAR DESPUÉS DE SUBIR
------------------------------
  - Menú Convenios > Convenio FED: debe mostrar el reporte de avances.
  - Menú Admin > "FED: Gestion de Indicadores": abre la página nueva.
  - Menú Admin > "FED: Importar Avances": abre la página nueva.
  - En el encabezado del reporte FED, los botones "Gestion de
    Indicadores" e "Importar Resultados.txt" llevan a cada página.

NOTAS TÉCNICAS
--------------
  - El código compartido (red de seguridad anti-error 500, detección
    de tablas, auto-reparación de esquema, helpers de clasificación)
    vive ahora en includes/fed_common.php y lo cargan las 3 páginas.
  - Las acciones POST usan los mismos nombres de acción que antes
    (fed_guardar_indicador, fed_toggle_indicador, fed_importar), solo
    cambió el archivo de destino de los formularios.
  - No se modificó ninguna tabla ni consulta de base de datos.
