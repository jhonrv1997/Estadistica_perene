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
| `reporte_cancer.php` | Pagina del reporte: filtros (anio, mes, establecimiento), boton **Generar Reporte**, 9 secciones con el layout del Excel y boton **Exportar Excel**. |
| `includes/cancer_data.php` | Motor data-driven: adapta los 9 Stored Procedures del archivo `03 Creacion de Procedimientos.txt` y los ejecuta contra la tabla MySQL `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (mismo patron que el modulo ESNI). No requiere crear tablas ni procedimientos en SQL Server. |
| `cancer_export.php` | Exportacion a Excel: llena la plantilla oficial `uploads/Reporte_Actividades_Cancer.xlsx` celda por celda (mismo mapa de celdas que llenaba la conexion ODBC). |
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
