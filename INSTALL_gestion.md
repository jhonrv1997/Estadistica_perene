================================================================
 CONVENIO DE GESTION - ADAPTACION AL PATRON DEL CONVENIO FED
 Instrucciones de instalacion - Sistema HIS Perene - 2026-11-09
================================================================

QUE HACE ESTA ADAPTACION
------------------------
El modulo "Convenio de Gestion" (antes una sola pagina de consulta con
filtros de anio/mes) ahora replica la funcionalidad completa del modulo
"Convenio FED", con la misma arquitectura de 3 paginas:

  1) convenio_gestion.php  (REEMPLAZA la anterior)
     Consulta y visualizacion (todos los roles), en dos paneles
     enlazados "un panel a la vez":
       PANEL 01 - Presentacion:
         - Avance global del convenio: ponderado por el peso de cada
           indicador (columna peso_ponderado) o, si ningun indicador tiene
           peso asignado, promedio simple de los indicadores con datos.
         - Tarjetas de los indicadores activos: codigo, nombre,
           descripcion, meta anual, resultado promedio (valor_resultado),
           promedio de porcentaje de avance, registros y establecimientos.
         - Boton "Ver detalle (Panel 02)" en cada tarjeta.
       PANEL 02 - Detalle del indicador:
         - Cabecera con codigo, nombre, descripcion, responsable, unidad,
           peso y boton "Ficha tecnica (PDF)" (si hay ficha adjunta), mas
           los totales: meta anual, promedio de avance, suma de numerador
           y suma de denominador.
         - Filtros: indicador, anio, mes ("Todos los meses",
           "Acumulado anual" o un mes) y establecimiento (incluye la
           opcion "Consolidado Zona Sanitaria" si hay filas antiguas sin
           establecimiento).
         - Tabla "Detalle de Avances por Establecimiento" agrupada por
           establecimiento (numerador y denominador sumados, porcentaje
           recalculado, resultado promedio) con busqueda en vivo, orden
           por columna y exportacion CSV.
         - Grafico "Avance por Mes" (linea de tendencia con Chart.js,
           selector de establecimiento por radio-botones y linea punteada
           de regresion) y Ranking de Establecimientos con barras y linea
           de meta.
     Compatibilidad: las URL antiguas con ?anio=...&mes=N siguen
     funcionando (el mes se normaliza, ej. mes=3 -> mes=03).

  2) cg_gestion_indicadores.php  (NUEVO)
     "CG: Gestion de Indicadores" (solo Administrador): agregar, modificar
     y activar/desactivar indicadores. Incluye el campo "Ficha tecnica del
     indicador (PDF)": el archivo se guarda en uploads/cg_fichas/ del
     servidor (carpeta creada automaticamente) y su nombre en la columna
     ficha_tecnica_pdf. Al editar se puede ver la ficha actual,
     reemplazarla o quitarla.

  3) cg_importar_avances.php  (NUEVO)
     "CG: Importar Avances" (solo Administrador): carga del archivo de
     avances (.txt/.csv) con DETECCION AUTOMATICA del formato:
       - 8 columnas (formato actual, igual que FED):
         codigo; renipress; establecimiento; anio; mes; valor_numerador;
         valor_denominador; porcentaje_avance
         Ej: CG-04;319;BAJO MARANKIARI;2026;01;120;150;80
       - 7 columnas: igual sin el porcentaje (se recalcula num/den*100).
       - 9 columnas: con valor_resultado antes del porcentaje.
       - 12 columnas (formato antiguo de CONVENIO_GESTION_AVANCE):
         id_avance; id_indicador; anio; mes; trimestre; valor_numerador;
         valor_denominador; valor_resultado; porcentaje_avance;
         observaciones; fecha_registro; usuario
     El indicador se resuelve por CODIGO (formatos nuevos) o por id
     numerico (formato antiguo). Modos "actualizar" y "reemplazar",
     historial de importaciones (LOG_IMPORTACION, tipo CG_TXT) y registro
     de fecha/hora de cada importacion (columna fecha_importacion).
     El mes vacio/NULL se guarda como fila de "acumulado anual".

  4) includes/cg_common.php  (NUEVO)
     Codigo compartido por las 3 paginas: red de seguridad contra errores
     500, deteccion de tablas/columnas, AUTO-REPARACION del esquema,
     helpers de clasificacion y subida de fichas PDF.

  5) includes/header.php  (REEMPLAZA)
     Menu Admin con dos items nuevos: "CG: Importar Avances" y
     "CG: Gestion de Indicadores".

CAMBIOS EN LA BASE DE DATOS (AUTOMATICOS)
-----------------------------------------
Ninguno que deba hacer a mano en la mayoria de hostings. La primera vez
que se abre el modulo, includes/cg_common.php agrega solas las columnas:
  CONVENIO_GESTION_AVANCE:
    - renipress VARCHAR(10) NULL
    - establecimiento VARCHAR(50) NULL
    - fecha_importacion DATETIME NULL
  CONVENIO_GESTION_INDICADORES:
    - peso_ponderado DECIMAL(5,2) NULL
    - ficha_tecnica_pdf VARCHAR(255) NULL
Los datos ya registrados (consolidados por periodo, sin establecimiento)
siguen siendo validos y se muestran agrupados como "Consolidado Zona
Sanitaria" en el detalle, el grafico y el ranking.

ARCHIVOS INCLUIDOS (conservar esta estructura de carpetas)
----------------------------------------------------------
  convenio_gestion.php              -> raiz del sitio (REEMPLAZA)
  cg_gestion_indicadores.php        -> raiz del sitio (NUEVO)
  cg_importar_avances.php           -> raiz del sitio (NUEVO)
  includes/cg_common.php            -> carpeta includes/ (NUEVO)
  includes/header.php               -> carpeta includes/ (REEMPLAZA)
  Database/update_gestion_v1.sql    -> carpeta Database/ (NUEVO, opcional)

COMO INSTALAR
-------------
Opcion A (archivos sueltos):
  1. Haga copia de seguridad de convenio_gestion.php e includes/header.php.
  2. Suba los 6 archivos a las rutas indicadas arriba.
  3. Listo. NO requiere ejecutar SQL en la mayoria de los hostings.

Opcion B (git):
  Si trabaja con el repositorio, aplique los cambios con commit/merge y
  suba solo los archivos modificados al hosting.

SOLO SI EL HOSTING BLOQUEA ALTER TABLE
--------------------------------------
Si al entrar al modulo aparece el aviso de que faltan las columnas
renipress/establecimiento/peso_ponderado/ficha_tecnica_pdf, ejecute desde
phpMyAdmin (pestana SQL) el script Database/update_gestion_v1.sql.
Hasta aplicarlas, el modulo queda en modo degradado y oculta de forma
segura el detalle por establecimiento y las fichas PDF (sin errores).

QUE VERIFICAR DESPUES DE SUBIR
------------------------------
  1. Menus:
     - Menu Convenios > Convenio de Gestion: debe abrir el PANEL 01 con
       las tarjetas de indicadores (o el aviso si no hay indicadores).
     - Menu Admin > "CG: Gestion de Indicadores" y "CG: Importar
       Avances": deben abrir las paginas nuevas (solo rol Admin).
  2. Panel 01: tarjeta "Avance Global del Convenio de Gestion" y tarjetas
     por indicador; el boton "Ver detalle (Panel 02)" abre el detalle y
     "Regresar" vuelve al Panel 01 conservando anio/mes/est.
  3. Panel 02: pruebe los filtros (anio, mes "Todos/Acumulado anual/un
     mes", establecimiento), la busqueda y el orden de la tabla, el boton
     "Exportar CSV", el grafico "Avance por Mes" (cambiando de
     establecimiento con los radio-botones) y el Ranking.
  4. Admin > "CG: Gestion de Indicadores" > "Nuevo Indicador": cree un
     indicador de prueba (ej. codigo CG-99) y confirme que aparece en la
     tabla; adjunte un PDF y verifique el icono en la columna "Ficha" y
     el boton en el Panel 02; luego desactivelo para no mostrarlo.
  5. Admin > "CG: Importar Avances": genere un .txt de prueba con una
     linea como
       CG-99;319;BAJO MARANKIARI;2026;01;1;1;100
     importelo en modo "actualizar" y verifique el registro en el
     Panel 02 del indicador CG-99. Pruebe tambien el modo "reemplazar".
  6. Verifique en el servidor que la carpeta uploads/cg_fichas/ se creo
     al adjuntar el primer PDF.

NOTAS TECNICAS
--------------
- El codigo compartido vive en includes/cg_common.php: deteccion de
  tablas (CONVENIO_GESTION_AVANCE o CONVENIO_GESTION_AVANCES), deteccion
  y auto-creacion de columnas, auto-reparacion de PRIMARY KEY /
  AUTO_INCREMENT, clasificacion de avances (>=90 Excelente, >=75 Bueno,
  >=60 Regular, >=40 Bajo, <40 Critico) y subida de fichas PDF.
- Las acciones POST usan nombres propios del modulo: cg_guardar_indicador,
  cg_toggle_indicador y cg_importar (no chocan con las del FED).
- El modulo FED no se modifico en absoluto: ambos modulos conviven.
- No se modifico ninguna tabla existente del FED ni de otros reportes.
