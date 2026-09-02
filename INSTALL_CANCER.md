# Modulo CANCER - Reporte Operacional (reemplazo del flujo manual)

## Que reemplaza

Antes (flujo manual, ~30+ minutos por reporte):

1. SQL Server: ejecutar `01 Creacion tablas iniciales`
2. SQL Server: ejecutar `02 Creacion tablas consolidacion`
3. SQL Server: ejecutar `03 Creacion de Procedimientos`
4. Excel: abrir `Reporte_Actividades_Cancer.xlsx` y refrescar conexion ODBC

Ahora (flujo nuevo, 1 click):

- IntelHIS -> **Reportes Operacionales -> CANCER -> Generar Reporte**
- (Opcional) **Exportar Excel**: descarga el .xlsx con el layout oficial de la plantilla.

## Archivos nuevos

| Archivo | Descripcion |
|---------|-------------|
| `reporte_cancer.php` | Pagina del reporte: filtros (anio, mes, establecimiento), boton **Generar Reporte**, 9 secciones con el layout del Excel y boton **Exportar Excel**. Incluye captura de errores fatales (memoria/tiempo) con panel de diagnostico en lugar de HTTP 500. |
| `includes/cancer_data.php` | Motor data-driven: adapta los 9 Stored Procedures del archivo `03 Creacion de Procedimientos.txt` y los ejecuta contra la tabla MySQL `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (mismo patron que el modulo ESNI). No requiere crear tablas ni procedimientos en SQL Server. |
| `cancer_export.php` | Exportacion a Excel: llena la plantilla oficial `uploads/Reporte_Actividades_Cancer.xlsx` celda por celda (mismo mapa de celdas que llenaba la conexion ODBC). |
| `install_cancer.php` | Instalador 1-click de los indices de la tabla consolidada (ejecutar UNA vez como admin; ver seccion de solucion de problemas). |
| `uploads/Reporte_Actividades_Cancer.xlsx` | Plantilla oficial (la que ustedes usaban con ODBC). Copiela a la carpeta `uploads/` de su servidor. |

## Archivos modificados (2 lineas de integracion)

| Archivo | Cambio |
|---------|--------|
| `reporte_operacionales.php` | La tarjeta **Cancer** del indice ahora enlaza a `reporte_cancer.php` (antes mostraba el consolidado generico). `?sub=cancer` redirige al nuevo reporte. |
| `includes/header.php` | El item **Cancer** del menu "Rep. Operac." enlaza a `reporte_cancer.php` con badge NUEVO (igual que ESNI). |

## Instalacion (3 pasos)

1. Copie los archivos nuevos a la raiz de su proyecto (donde esta `reporte_esni.php`):
   - `reporte_cancer.php`
   - `cancer_export.php`
   - `includes/cancer_data.php`
2. Copie su plantilla oficial a la carpeta de uploads:
   - `uploads/Reporte_Actividades_Cancer.xlsx`
   (es el mismo archivo que abrian en Excel para refrescar la conexion ODBC)
3. Reemplace los 2 archivos modificados (`reporte_operacionales.php` e `includes/header.php`)
   o aplique los cambios indicados arriba (son minimos).

No se necesita ejecutar ningun script SQL: el motor consulta directamente la tabla
consolidada `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` que ya carga su sistema
(mismo origen de datos del modulo ESNI).

## Solucion de problemas: HTTP ERROR 500 al pulsar "Generar Reporte"

### Causas (por que ocurria)

1. **La tabla consolidada no tiene indices.** `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`
   se crea sin ninguna clave/index. El reporte consulta con
   `Codigo_Item IN (~80 codigos) OR Codigo_Item LIKE 'C%'` + Anio/Mes/Establecimiento;
   sin indices MySQL recorre TODA la tabla (~100 columnas) en cada clic.
2. **Consulta buffered + doble almacenamiento en PHP.** Todo el resultado se
   cargaba de golpe en memoria y las filas se guardaban 2 veces (`$filas` y
   `$porCita`). Con un anio completo de datos se agotaba el `memory_limit` del
   hosting -> `Fatal error: Allowed memory size exhausted` -> HTTP 500.
3. **Tiempo de ejecucion excedido.** El matching evaluaba cada una de las ~150
   lineas del reporte contra TODAS las filas leidas (millones de iteraciones).
   Al superar el `max_execution_time` del hosting -> `Fatal error` -> HTTP 500.
4. **Excepcion no capturada durante la lectura.** El `try/catch` solo cubria
   `prepare/execute`; si MySQL mataba la consulta lenta a mitad del `fetch()`,
   la `PDOException` salia sin capturar -> HTTP 500.

### Solucion aplicada

| Archivo | Cambio |
|---------|--------|
| `includes/cancer_data.php` | Lectura en **streaming** (conexion unbuffered dedicada) con `try/catch` en TODO el proceso; tope de seguridad de 2M filas; uso de la columna generada `Mes_Int`; matching por **buckets pre-indexados** (codigo exacto y letra inicial) en lugar de recorrer todas las filas por cada linea; filas almacenadas 1 vez con indices enteros; `set_time_limit(0)` + `memory_limit 512M` (si el hosting lo permite). |
| `reporte_cancer.php` | `register_shutdown_function` que captura errores fatales (memoria/tiempo) y muestra un **panel de diagnostico** con la causa y acciones recomendadas en lugar de la pagina HTTP 500; el alert de error ahora incluye diagnostico del entorno (PHP, memory_limit, execution_time, indices presentes, tamano de tabla). |
| `install_cancer.php` (nuevo) | Instalador 1-click que crea los indices `idx_cnd_anio`, `idx_cnd_codigo_item`, `idx_cnd_anio_mesint`, `idx_cnd_codigo_unico` sobre la tabla consolidada (solo los que falten). |

### Pasos para aplicar la correccion

1. Suba los archivos actualizados (`includes/cancer_data.php`, `reporte_cancer.php`, `install_cancer.php`).
2. Ingrese como administrador y abra **`install_cancer.php`** una sola vez.
   - Sobre tablas grandes puede tardar 1-3 minutos; no cierre la pagina.
   - Debe terminar con el panel verde y el listado de indices finales.
3. Vuelva a **Reportes Operacionales -> CANCER -> Generar Reporte**.
4. Por seguridad, **elimine `install_cancer.php`** del servidor.

> Consejo: si su tabla tiene millones de filas y el hosting gratuito sigue
> quedandose corto, genere primero el reporte de un **mes** especifico
> (en lugar de "-- Todos --") y/o de un **establecimiento**; luego exporte
> el Excel. Para cargas anuales completas sin filtros se recomienda un plan
> de hosting con mas recursos.

## Como funciona

- El boton **Generar Reporte** ejecuta UNA sola consulta a la tabla consolidada
  (codigos de item del modulo Cancer + codigos CIE 'C%') con los filtros de
  anio/mes/establecimiento, y resuelve en PHP las condiciones de cada linea del
  reporte. Esas condiciones son la traduccion directa de los WHERE de los 9
  procedimientos del archivo 03.
- Las 9 secciones con datos son exactamente las que alimentaban las tablas
  `TRAMA_BASE_CANCER_2026_RPTxx_CONSOLIDADO`:
  1. Mujeres tamizadas en cancer de cuello uterino (PAP / IVAA / VPH)
  2. Mujeres tamizadas en cancer de mama (ECM / mamografia)
  3. Personas tamizadas para otros cancer (colorrectal / prostata / piel)
  4. Procedimiento para el diagnostico de cancer (12 tipos)
  5. Lesiones premalignas de cuello uterino
  6. Consejeria para la prevencion y control del cancer
  7. Atendidos segun tipo de cancer
  8. Atenciones de todo tipo de cancer adulto
  9. Deteccion temprana de cancer infantil
- **Exportar Excel** llena la plantilla oficial con el mismo mapa de celdas del
  flujo ODBC (filas 11-24 cuello uterino, 29-72 mama, 96-123 otros, 129-191
  procedimientos, 197-206 lesiones, 313-320 consejerias, 374-383 atencion,
  403 adulto, 427-431 deteccion infantil). Las secciones sin procedimiento
  (quimioterapia, radioterapia, quirurgico, paliativos, estadios, infantil,
  telemamografia) quedan en 0, exactamente como en su Excel actual.

## Mapeo de columnas (SQL Server -> MySQL)

```
id_cita        -> Id_Cita
renaes         -> Codigo_Unico
id_persona     -> Id_Paciente
aniomes        -> Anio + Mes
id_genero      -> Id_Genero           (F/M)
id_tipedad_reg -> Tipo_Edad           (D/M/A)
edad_reg       -> Edad_Reg
id_tipitem     -> Tipo_Diagnostico    (D/P/R)
cod_item       -> Codigo_Item
valor_lab      -> Valor_Lab
I_ROWNUM_LAB   -> Id_Correlativo_Lab
fg_tipo        -> Fg_Tipo
id_profesional -> Id_Personal
id_ups         -> Id_Ups
periodo>='202601' -> filtro web Anio (el anio seleccionado)
cod_item_f     -> Codigo_Item de la misma cita (por prefijo CIE, ej. C50*)
```

## Notas de adaptacion (importante)

1. **cod_item_f**: la trama MySQL no tiene la columna `cod_item_f` del SQL Server.
   Las condiciones que usaban `cod_item_f` (diagnosticos CIE referidos, p.ej.
   `cod_item_f='C50'`) se resuelven buscando en la misma cita una fila cuyo
   `Codigo_Item` empiece con ese codigo CIE (C50, C509, C53X, ...). Esto cubre
   el mismo caso de uso con la estructura de la trama MySQL.
2. **fg_tipo='CX'**: se omite en la adaptacion porque el valor de `Fg_Tipo` en la
   tabla consolidada depende del catalogo local de items; las condiciones de
   tipo/codigo/valor_lab/rownum son sufficientemente selectivas.
3. **RPT04_01 Temporal3**: en el T-SQL original asignaba Consejeria 3/4 (lo que
   duplicaria la linea "Telemedicina, personas con una/dos consejerias"
   preventiva); semanticamente corresponde a las claves 7/8 (Telemedicina en
   pacientes diagnosticados, segun la Dim Consejeria04 y el Excel). Se adapta
   a 7/8 para respetar el diseno del reporte oficial.
4. **Secciones sin procedimiento**: telemamografia, quimioterapia,
   radioterapia/braquiterapia, quirurgico, cuidados paliativos, estadios y
   atenciones de cancer infantil no tienen procedimiento en el archivo 03, por
   lo que quedan en 0 (igual que en el Excel original, donde dichas filas
   muestran FALSE tras refrescar ODBC).
5. Los codigos CIE se comparan por **prefijo** para cubrir variantes con relleno
   'X' (C61X) y subcategorias (C509, C610...).

## Personalizacion

- Para ajustar una linea (por ejemplo cambiar un codigo de item o un rango de
  edad), edite la condicion correspondiente en `includes/cancer_data.php`
  (seccion `cancerSecciones()`). Cada fila tiene su condicion junto a la
  etiqueta del Excel; los codigos coinciden con los del archivo 03.
- El orden de las filas replica la plantilla oficial
  `Reporte_Actividades_Cancer.xlsx`.
