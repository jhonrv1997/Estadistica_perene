================================================================
 CONVENIO FED - FICHA TECNICA EN PDF POR INDICADOR
 Instrucciones de instalacion - Sistema HIS Perene - 2026-11-06
================================================================

QUE HACE ESTA MEJORA
--------------------
1) fed_gestion_indicadores.php (solo Administrador)
   - Al AGREGAR o EDITAR un indicador, el modal incluye ahora el campo
     "Ficha tecnica del indicador (PDF)" para adjuntar un archivo PDF
     (formula, fuente, metas, exclusiones, etc.).
   - El PDF se guarda en la carpeta uploads/fed_fichas/ del servidor
     (se crea automaticamente la primera vez) y la base de datos solo
     guarda el nombre en la nueva columna ficha_tecnica_pdf de la tabla
     CONVENIO_FED_INDICADORES.
   - En la tabla de indicadores hay una nueva columna "Ficha" con un
     icono PDF que abre el documento en otra pestana/ventana.
   - Al editar un indicador con ficha ya cargada se muestra el nombre
     del PDF actual (con enlace para verlo), se puede reemplazar subiendo
     otro PDF o quitarlo con la casilla "Quitar el PDF actualmente
     guardado" (el archivo antiguo se elimina del servidor).

2) convenio_fed.php (Panel 02 - todos los roles)
   - En la cabecera del detalle del indicador, junto al codigo y la
     insignia de avance, aparece un boton rojo "Ficha tecnica (PDF)"
     con icono. Al hacer clic abre el PDF del indicador en OTRA ventana
     (target="_blank"). Si el indicador no tiene ficha adjunta, el
     boton no aparece.

VALIDACIONES DE LA SUBIDA
-------------------------
- Solo archivos .pdf, maximo 10 MB (FED_FICHA_MAX_BYTES en
  includes/fed_common.php).
- Verificacion del contenido real del PDF (cabecera %PDF-), ademas de
  la extension.
- Nombre unico automatico: fed_ficha_FECHA_HORA_XXXXXXXX.pdf
- Mensajes claros si el PDF supera upload_max_filesize / post_max_size
  de PHP o si la carpeta no se puede crear.

ARCHIVOS INCLUIDOS (conservar esta estructura de carpetas)
----------------------------------------------------------
  convenio_fed.php                -> raiz del sitio (REEMPLAZA)
  fed_gestion_indicadores.php     -> raiz del sitio (REEMPLAZA)
  includes/fed_common.php         -> carpeta includes/ (REEMPLAZA)
  Database/update_fed_v4_fichas.sql -> carpeta Database/ (NUEVO, opcional)
  cambios_fed_fichas.patch        -> parche git (alternativa opcional)

COMO INSTALAR
-------------
Opcion A (archivos sueltos):
  1. Haga copia de seguridad de los 3 archivos PHP que va a reemplazar.
  2. Suba los archivos a las rutas indicadas arriba.
  3. Listo. NO requiere ejecutar SQL en la mayoria de los hostings:
     la columna ficha_tecnica_pdf se crea automaticamente la primera
     vez que se abre el modulo Convenio FED y la carpeta
     uploads/fed_fichas/ se crea sola al adjuntar el primer PDF.

Opcion B (parche git):
  git apply cambios_fed_fichas.patch

SOLO SI EL HOSTING BLOQUEA ALTER TABLE
--------------------------------------
Si al entrar a "FED: Gestion de Indicadores" aparece el aviso de que la
columna ficha_tecnica_pdf no existe, ejecute desde phpMyAdmin (pestana
SQL) el script Database/update_fed_v4_fichas.sql:

  ALTER TABLE `CONVENIO_FED_INDICADORES`
    ADD COLUMN `ficha_tecnica_pdf` VARCHAR(255) NULL DEFAULT NULL AFTER `responsable`;

Hasta que la columna exista, la funcionalidad de fichas PDF queda
oculta de forma segura (sin errores).

QUE VERIFICAR DESPUES DE SUBIR
------------------------------
  1. Admin > "FED: Gestion de Indicadores" > "Nuevo Indicador":
     debe verse el campo "Ficha tecnica del indicador (PDF)". Cree un
     indicador adjuntando un PDF y confirme el mensaje "Se adjunto la
     ficha tecnica en PDF."
  2. En la tabla de indicadores, la columna "Ficha" debe mostrar el
     icono PDF; al hacer clic abre el documento en otra ventana.
  3. Con el boton "Editar" del indicador, el modal muestra "Ficha
     tecnica actual: <nombre>.pdf" y la opcion de quitar/reemplazar.
  4. Reporte Convenios > Convenio FED > "Ver detalle (Panel 02)" de ese
     indicador: junto al codigo debe verse el boton "Ficha tecnica
     (PDF)"; al hacer clic se abre el PDF en otra ventana.
  5. Verifique en el servidor que el archivo quedo en
     uploads/fed_fichas/.

NOTAS TECNICAS
--------------
- El codigo compartido (validacion y guardado del PDF, creacion
  automatica de la columna y de la carpeta, URL segura del archivo)
  vive en includes/fed_common.php: fedSubirFichaTecnica(),
  fedFichaDir(), fedFichaUrl(), fedEliminarFichaTecnica() y el flag
  $tieneFichaPdf.
- El archivo se guarda con nombre aleatorio y se sirve por URL
  relativa "uploads/fed_fichas/<archivo>", igual que el resto de
  archivos de la carpeta uploads/ del sistema.
- Si se desactiva un indicador, su ficha PDF se conserva (igual que su
  historial de avances).
- No se modifico ninguna consulta ni tabla existente: solo se agrego
  la columna ficha_tecnica_pdf (VARCHAR 255, admite NULL).


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
