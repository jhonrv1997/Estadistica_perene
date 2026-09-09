# Modulo ZOONOSIS (v1.0) - Informe Mensual de Zoonosis

Modulo data-driven de IntelHIS que **reemplaza el flujo manual del Modulo de Zoonosis** por un flujo web de 1 click, igual que ESNI, Cancer y Materno.

## Flujo anterior (4 pasos manuales)

1. SQL Server: ejecutar `01 Creacion tablas iniciales` (tablas dimensionales `DimZoonosis*`)
2. SQL Server: ejecutar `02 Creacion tablas consolidacion` (`TRAMA_BASE_ZOONOSIS_2022_*_CONSOLIDADO / _NOMINAL`)
3. SQL Server: ejecutar `03 Creacion de Procedimientos` (13 procedimientos `usp_TRAMA_BASE_ZOONOSIS_2022_*`)
4. Excel: abrir `Reporte_Actividades_Zoonosis.xlsx` y refrescar la conexion ODBC

## Flujo nuevo (1 click)

- Entrar a IntelHIS -> **Reportes Operacionales -> ZOONOSIS -> Generar Reporte**
- (Opcional) **Exportar Excel**: llena la plantilla oficial
  `uploads/Reporte_Actividades_Zoonosis.xlsx` con el mismo layout (15 bloques).

## Archivos del modulo

| Archivo | Rol |
|---------|-----|
| `includes/zoonosis_data.php` | Motor de reglas: definicion de las 15 secciones (2 de ponzoñosos + 13 de rabia urbana) con las categorias EXACTAS de los 13 procedimientos del archivo 03, DSL de predicados (espeja los WHERE del T-SQL), 3 reglas de conteo (`filas` = count(*), `personas` = count(distinct id_persona), `suma` = sum(valor_lab)) y ejecucion contra `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` |
| `includes/zoonosis_render.php` | Funciones de render web (layout tipo Excel) y mapa de celdas para el export (`zooCeldasExport`) |
| `reporte_zoonosis.php` | Pagina del reporte (1 click): filtros anio/mes/EE.SS., proteccion anti HTTP 500, panel "Ver condiciones SQL" (auditoria) |
| `zoonosis_export.php` | Exportacion a la plantilla oficial .xlsx (preserva formato: merges, estilos) |
| `install_zoonosis.php` | Asistente de indices de la tabla consolidada (eliminar tras usar) |
| `Database/install_zoonosis.sql` | Indices en SQL (alternativa manual al asistente) |
| `uploads/Reporte_Actividades_Zoonosis.xlsx` | Plantilla oficial (base del export) |

## Estructura del reporte (15 bloques = 13 procedimientos)

| # | Codigo | Procedimiento T-SQL | Bloque del informe |
|---|--------|---------------------|--------------------|
| PONZ1 | `PONZ1_MORBILIDAD` | `usp_..._PONZONOSIS` | Morbilidad por 13 diagnosticos ponzoñosos x T/M/F x 5 grupos etareos + GESTANTES + subtotales por grupo |
| PONZ2 | `PONZ2_RPT02` | `usp_..._PONZONOSOS_RPT02` | Casos con tratamiento U310: 3 grupos de diagnostico |
| RU1 | `RU01_PRE_EXPOSICION` | `usp_..._RABIA_URBANA_01` | Indicacion de la profilaxis Pre-exposicion (90675 PRE P R1 + ST R2) |
| RU2 | `RU02_ADMIN_PRE_EXPOSICION` | `usp_..._RABIA_URBANA_02` | Administracion de la profilaxis Pre-Exposicion (Acceso/Seguimiento/Cobertura x T/M/F) |
| RU3 | `RU03_POST_EXPOSICION` | `usp_..._RABIA_URBANA_03` | Manejo de la herida por mordedura: 15 tratamientos (W540/W550/W558/W530 + 99199.11) + bloque Total, x T/F/M |
| RU4 | `RU04_VACUNACION_POST` | `usp_..._RABIA_URBANA_04` | Vacunacion Antirrabica Humana: 6 bloques (MOC/DS/SR/POS/CE/DA) x 5 situaciones |
| RU5 | `RU05_SUSPENSION` | `usp_..._RABIA_URBANA_05` | Suspension temporal / definitiva |
| RU6 | `RU06_REFERENCIAS` | `usp_..._RABIA_URBANA_06_1` | Referencias/contrarreferencias: DVR/DVC/CC x 5 situaciones |
| FRVH | `FRVH_FRASCOS_VARH` | `usp_..._RABIA_URBANA_06_2` | Frascos monodosis VARH PRE/POS |
| FRRIG | `FRRIG_FRASCOS_RIG` | `usp_..._RABIA_URBANA_06_3` | Frascos de inmunoglobulina RIG (item 90375) por tratamiento |
| RU7 | `RU07_DX_HUMANO` | `usp_..._RABIA_URBANA_07` | Diagnostico de Rabia Humana Urbana (A821) |
| RU8 | `RU08_OBSERVACION_ANIMAL` | `usp_..._RABIA_URBANA_08` | Observacion del animal mordedor: perro/gato AS/SR/MOC x 1ra/2da/3ra visita |
| RU9 | `RU09_VIGILANCIA_RESERVORIO` | `usp_..._RABIA_URBANA_09` | Vigilancia del reservorio: can/gato/otros x Remitida/Procesada/Positivo/Negativo |
| RU10 | `RU10_CONTROL_FOCO` | `usp_..._RABIA_URBANA_10` | Control de foco: activa/pasiva x Notificado/Investigado/Controlado |
| RU11 | `RU11_VACUNACION_CANINA` | `usp_..._RABIA_URBANA_11` | Vacunacion antirrabica canina: centros antirrabicos/VANCAN/control de foco |

Grupos etareos (DimZoonosisEtapa): `0-11a`, `12-17a`, `18-29a`, `30-59a`, `60 y mas`.
PONZ1/PONZ2 incluyen ademas la columna **GESTANTES** (#GESTANTES: mujeres 12-59 con diagnostico
ponzoñoso y marcador `valor_lab='G'` en la cita).

## Instalacion

1. Copiar los archivos del modulo a la raiz del sistema (junto a `reporte_materno.php`):
   `reporte_zoonosis.php`, `zoonosis_export.php`, `install_zoonosis.php`,
   `includes/zoonosis_data.php`, `includes/zoonosis_render.php`.
2. Copiar la plantilla oficial `Reporte_Actividades_Zoonosis.xlsx` a `uploads/`.
3. (Recomendado, una sola vez) Abrir como admin `install_zoonosis.php` para crear/verificar
   los indices de la tabla consolidada (o ejecutar `Database/install_zoonosis.sql`).
   Luego **eliminar** `install_zoonosis.php`.
4. Entrar a **Reportes Operacionales -> ZOONOSIS -> Generar Reporte**
   (la tarjeta Zoonosis y `?sub=zoonosis` redirigen automaticamente al nuevo reporte).

No requiere tablas nuevas: el motor consulta la misma tabla consolidada MySQL que ya usan
ESNI, Cancer y Materno (`T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`).

## Ajuste de reglas contra el archivo "03 Creacion de Procedimientos"

**Todas las condiciones estan centralizadas y son auditables:**

1. En el reporte web marque la casilla **"Ver condiciones SQL (auditoria)"**: cada seccion
   muestra la condicion SQL equivalente que ejecuta el motor y su regla de conteo
   (count(*) / count(distinct id_persona) / sum(valor_lab)).
2. Compare esas condiciones con su archivo 03 original.
3. Ajuste las que difieran en un unico lugar: `includes/zoonosis_data.php` ->
   funcion `zooSecciones()`, campo `cond` de cada fila.

### DSL de condiciones (campo `cond`)

| Clave | Ejemplo | Equivalente T-SQL |
|-------|---------|-------------------|
| `cod` | `['cod' => ['W540','W550']]` | `cod_item IN ('W540','W550')` |
| `tip` | `['tip' => 'D']` | `id_tipitem = 'D'` |
| `vl` | `['vl' => ['1','2']]` / `['vl' => 'NULL']` | `valor_lab IN ('1','2')` / `valor_lab IS NULL` |
| `vlNum` | `['vlNum' => true]` | `ISNUMERIC(valor_lab) = 1` |
| `rownum` | `['rownum' => 2]` | `I_ROWNUM_LAB = 2` |
| `sexo` | `['sexo' => 'F']` | `id_genero = 'F'` |
| `edadA` | `['edadA' => [12, 59]]` | `id_tipedad_reg='A' and edad_reg between 12 and 59` |
| `ficha` | `['ficha' => ['AAA04','AAA09']]` | `fichafam IN ('AAA04','AAA09')` |
| `citaTiene` | `['citaTiene' => <pred>]` | `id_cita IN (SELECT id_cita FROM #TEMP WHERE ...)` |
| `citaTieneTodo` | `['citaTieneTodo' => [<pred>...]]` | AND de varios EXISTS |
| `citaTieneCual` | `['citaTieneCual' => [<pred>...]]` | OR de EXISTS (UNION de #temp) |
| `cualquieraDe` | `['cualquieraDe' => [<pred>...]]` | OR de condiciones de la misma fila |

### Reglas de conteo (campo `regla` de la seccion)

| Regla | Equivalente T-SQL | Secciones |
|-------|-------------------|-----------|
| `filas` | `count(*)` (cada fila HIS que cumple cuenta 1) | PONZ1, PONZ2, RU3, FRVH, RU7, RU8 |
| `personas` | `count(distinct id_persona)` por EE.SS (pares EE.SS\|paciente: la persona atendida en 2 EE.SS. cuenta 2, como la suma del Excel ODBC) | RU1, RU2, RU4, RU5, RU6 |
| `suma` | `sum(valor_lab)` del registro ancla (frascos / dosis) | FRRIG, RU9, RU10, RU11 |

## Notas de adaptacion (desviaciones documentadas)

- **Periodo**: el `aniomes >= '202201'` de los SP se traduce en los filtros web de
  Anio/Mes/Establecimiento (mismos datos, distinta presentacion).
- **FRRIG (90375)**: se usa el codigo `90375` tal cual aparece en el archivo 03 (frascos de
  RIG). Si en su base ese item esta cargado como `90675`, ajuste en un solo lugar:
  `zooSecciones()` -> seccion `FRRIG_FRASCOS_RIG`.
- **RU4 bloque 6 (Re-exposicion, valor 'DA')**: el SP solo produce Situacion 1-2; las filas
  3-5 del bloque se muestran en 0 (cond = null), igual que quedaban en el flujo ODBC.
- **Subtotales**: las filas "Total" de PONZ1/PONZ2/RU3/FRVH/FRRIG/RU8/RU9/RU10/RU11 y los
  subtotales por grupo de PONZ1 son calculadas (suma de sus componentes), igual que las
  filas TOTAL de la plantilla.
- **Gestantes**: el marcador `valor_lab='G'` puede estar en cualquier codigo de la cita,
  por eso la consulta base trae tambien todas las filas con `Valor_Lab='G'` (mismo
  mecanismo que el modulo Materno con #GEST).
- **Ambito de establecimientos**: con `-- Todos --` el reporte se limita a la lista del
  catalogo ZSPERENE (mismo criterio que Materno); sin catalogo no se restringe.

## Mapa de celdas del export (hoja "Plantilla")

| Bloque | Filas Excel | Columnas de datos |
|--------|-------------|-------------------|
| PONZ1 | 12-56 | D (Total), E,G,J,L,N (etapas), P (GESTANTES) |
| PONZ2 | 59-62 | B (Total), D,E,G,J,L (etapas), N (GESTANTES) |
| RU1 | 67-69 | E (Total), G,J,L,N,P (etapas) |
| RU2 | 72-80 | E, G,J,L,N,P |
| RU3 | 84-140 | G (Total), J,L,N,P,R (etapas) |
| RU4 | 143-172 | D (Total), E,G,J,L,N |
| RU5 | 176-177 | D, E,G,J,L,N |
| RU6 | 180-194 | D, E,G,J,L,N |
| FRVH | 197-199 | B, D,E,G,J,L |
| FRRIG | 202-207 | B, D,E,G,J,L |
| RU7 | 211 | B, D,E,G,J,L |
| RU8 | 214-234 | D (Total unico) |
| RU9 | 237-256 | E (Total unico) |
| RU10 | 259-267 | E (Total unico) |
| RU11 | 270-272 | F (Total unico) |

Cabecera: `D5` = IPRESS (area D5:M5), `R4` = MES (area R4:U4), `R5` = AÑO (area R5:U5).
