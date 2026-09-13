# Modulo NO TRANSMISIBLES (v1.0) - Actividades de Enfermedades No Trasmisibles

Modulo data-driven de IntelHIS que **reemplaza el flujo manual del Modulo de NoTransmisibles** por un flujo web de 1 click, igual que ESNI, Cancer, Materno y Zoonosis.

## Flujo anterior (4 pasos manuales)

1. SQL Server: ejecutar `01 Creacion tablas iniciales` (tablas dimensionales `DimNT_*`: Factores, EvaluacionPAB, Valoracion01-03, Gedad, HTA_Diagnostico01-06, HTA_Riesgo, HTA_Sesiones, DM_Diagnostico01-07, TM_Valoracion01-05)
2. SQL Server: ejecutar `02 Creacion tablas consolidacion` (`TRAMA_BASE_NT_2025_*_CONSOLIDADO / _NOMINAL`, 22 pares)
3. SQL Server: ejecutar `03 Creacion de Procedimientos` (24 procedimientos `usp_TRAMA_BASE_NT_2025_*`)
4. Excel: abrir `Reporte_Actividades_NoTransmisibles.xlsx` y refrescar la conexion ODBC

## Flujo nuevo (1 click)

- Entrar a IntelHIS -> **Reportes Operacionales -> NO TRANSMISIBLES -> Generar Reporte**
- (Opcional) **Exportar Excel**: llena la plantilla oficial
  `uploads/Reporte_Actividades_NoTransmisibles.xlsx` con el mismo layout
  (24 secciones en 4 grupos, cabeceras moradas, columnas M/F por grupo etareo).

## Archivos del modulo

| Archivo | Rol |
|---------|-----|
| `includes/nontransmisibles_data.php` | Motor de reglas: definicion de las 24 secciones con las categorias EXACTAS de los 24 procedimientos del archivo 03, DSL de predicados (espeja los WHERE del T-SQL), 3 reglas de conteo (`personas` = count(distinct id_persona), `filas` = count(*), `sesiones` = count(*) N + sum(valor_lab) Participantes) y ejecucion contra `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` |
| `includes/nontransmisibles_render.php` | Funciones de render web (layout tipo Excel) y mapa de celdas para el export (`ntCeldasExport`) |
| `reporte_no_transmisibles.php` | Pagina del reporte (1 click): filtros anio/mes/EE.SS/grupo, proteccion anti HTTP 500, panel "Ver condiciones SQL" (auditoria) |
| `nontransmisibles_export.php` | Exportacion a la plantilla oficial .xlsx (preserva formato: merges, estilos, formula B8) |
| `install_no_transmisibles.php` | Asistente de indices de la tabla consolidada + verificacion del maestro de establecimientos (eliminar tras usar) |
| `uploads/Reporte_Actividades_NoTransmisibles.xlsx` | Plantilla oficial (base del export) |

## Estructura del reporte (24 secciones = 24 procedimientos)

### GRUPO 01 - Valoracion (gedad 05-11a .. 60+; TOTAL col C, edades D-O)

| # | Codigo | Procedimiento T-SQL | Bloque del informe |
|---|--------|---------------------|--------------------|
| VR01 | `VR01_FACTORES_RIESGO` | `usp_..._01_VALORACION_RPT_01_FACTORES_RIESGO` | 20 factores de riesgo (sobrepeso/obesidad E66x, tabaco/alcohol/sedentarismo Z72x, historia familiar Z83x, dislipidemia E78x, glucosa 82947/82948, TTG R730, presion arterial 99199.22 con sistolica R1 + diastolica R2) |
| VR02 | `VR02_EVALUACION_PAB` | `usp_..._01_VALORACION_RPT_02_EVALUACION_PAB` | Evaluacion de perimetro abdominal (Z019 + Perimetro_Abdominal; Normal/Anormal por sexo: F<=88/>88, M<=102/>102; solo 18+) |
| VR03 | `VR03_VALORACION_CLINICA` | `usp_..._01_VALORACION_RPT_03_VALORACION_CLINICA` | Personas con valoracion clinica (Z019 D R1) / con factores de riesgo (Z019 D ALT) |
| VR04 | `VR04_VALORACION_CLINICA` | `usp_..._01_VALORACION_RPT_04_VALORACION_CLINICA` | Valoracion con FR + solicitud de laboratorio (#EXAMEN Z017) / tamizaje (40+) / entrega de resultados 82947 / 82948 (#LABORATORIO Z019 R) |
| VR05 | `VR05_INTERVENCION` | `usp_..._01_VALORACION_RPT_05_VALORACION_CLINICA` | Consejeria en estilos de vida saludable (99401.13 + Z019, #CONSEJERIA) |

### GRUPO 02 - Hipertension Arterial (gedad 05-11a .. 60+; TOTAL col C, edades D-O)

| # | Codigo | Procedimiento T-SQL | Bloque del informe |
|---|--------|---------------------|--------------------|
| HTA1 | `HTA1_CASOS` | `usp_..._02_HTA_RPT_01_CASOS` | 8 casos: HTA esencial en controles (I10X D,R), nuevos (I10X D), retinopatia nueva/acumulada (#RETINOPATIA H350), nefropatia con/sin IR nueva/acumulada (I120/I129) |
| HTA2 | `HTA2_EMERGENCIA` | `usp_..._02_HTA_RPT_02_EMERGENCIA` | Urgencia (R030 URG), crisis no especificada (I16X), emergencia (R030 EMG) + #HIPERTENSION |
| HTA3 | `HTA3_DISLIPIDEMIAS` | `usp_..._02_HTA_RPT_03_DISLIPIDEMIAS` | Dislipidemia + alto riesgo CV en seguimiento (E78x + 99199.23>=10 + presion <140/<90, 40+) |
| HTA4 | `HTA4_SIN_DANO` | `usp_..._02_HTA_RPT_04_HIPERTENSO_SIN_DANO` | 6 filas: controlado (PA <140/<90), alto riesgo controlado (PA <130/<80 + riesgo), recibe controles, avance de meta 1/2/3 meses (familias I10) |
| HTA5 | `HTA5_TRATAMIENTO` | `usp_..._02_HTA_RPT_05_HIPERTENSO_TRATAMIENTO` | Paciente controlado (mismas 6 filas, familias I10-I13) + atencion integral especializado por UPS: Nefrologia 302701, Cardiologia 300201, Oftalmologia 303408, Nutricion 303101 |
| HTA6 | `HTA6_RIESGO` | `usp_..._02_HTA_RPT_06_HIPERTENSO_RIESGO` | Estratificacion de riesgo CV (99199.23: <5 Bajo, 5-9 Moderado, 10-19 Alto, 20-29 Muy Alto, >=30 Critico; 40+) |
| HTA7 | `HTA7_SESIONES` | `usp_..._02_HTA_RPT_07_SESIONES` | Sesiones educativa/demostrativa/GAM (C0009/C0010/C0012 + fichafam APP100) x poblacion DM/HTA: N = count(*), Participantes = sum(valor_lab) (NULL=>1). Sin columnas de edad/sexo (C=No, D=Participantes) |

### GRUPO 03 - Diabetes Mellitus (gedad MENORES 1a .. 60+; 8 grupos x M/F)

| # | Codigo | Procedimiento T-SQL | Bloque del informe |
|---|--------|---------------------|--------------------|
| DM01 | `DM01_CASOS` | `usp_..._03_DM_RPT_01_CASOS` | 13 casos: E10/E11/O24(5-59a)/E13/E14 atendidos y nuevos (fg_tipo=CX), nefropatia (E102/E112/E132/E142), retinopatia (E103/.../H360), tiroiditis E06 + #ATENCION, TBC A15 + #ATENCION, HTA I10 + #ATENCION |
| DM02 | `DM02_GLUCEMIA` | `usp_..._03_DM_RPT_02_GLUCEMIA` | 7 filas: hipoglicemia DM1/DM2/otros (E160/E162 + E100/E108, E110/E118, E130/.../E148), coma (E100/...), cetoacidosis (E101/...), hipoglicemia ind. medicamentos (E160), hiperglicemia no esp. (R739). Regla count(*) |
| DM03 | `DM03_CONTROL` | `usp_..._03_DM_RPT_03_CONTROL` | 52 categorias (filas 163-214, TOTAL col E): HbA1c 83036 (<6.5/<7/<8/>=8/>=10), glucosa 82947/82948 (<130/>=130), presion en/fuera de meta (#CONTROL + #PRESION), seguimiento (#SEGUIMIENTO) y tratamiento 1/2/3 meses (#TRATAMIENTO) x E10/E11/E13/E14. Solo RENAES categoria I-2/I-3/I-4 |
| DM04 | `DM04_TRATAMIENTO` | `usp_..._03_DM_RPT_04_TRATAMIENTO` | Mismas 52 categorias (filas 219-270). Solo RENAES categoria II-1/II-2/II-E/III-1/III-2/III-E |
| DM05 | `DM05_ATENCION` | `usp_..._03_DM_RPT_05_ATENCION` | 8 filas por especialidad (UPS): Cardiologia 300201, Endocrinologia 301001, Oftalmologia 303408 (E1x + H360), Neurologia 303008, Nefrologia 302701, nefropatia G1-G2 (N181/N182+R80X), G3-G5 (N183-185), Nutricion 303099 |
| DM06 | `DM06_VALORACION` | `usp_..._03_DM_RPT_06_VALORACION` | 88 categorias (filas 287-374, TOTAL col E): pie diabetico 99214.07 (N/A), TFG 82565 (G1-G5), proteinuria tira 82044 (0-4), albuminuria 82043 (A1-A3), fondo de ojo 92250/92226, LDL 83721 (<70/70-99/100-129/>=130), no especificada (VAL) x E1x. Solo RENAES I-4/II-*/III-* |
| DM07 | `DM07_NEFROPATIA` | `usp_..._03_DM_RPT_07_NEFROPATIA` | 11 filas (filas 379-389, TOTAL col C): nefropatia en seguimiento (E102/... R + #NEFROPATIA), TFG 82545 (bajo/alto riesgo), tira 82044 (0-4), albuminuria 82043 (A1-A3). Solo RENAES I-1..II-1 |

### GRUPO 04 - Telesalud (gedad 05-11a .. 60+; TOTAL col C, edades D-O)

| # | Codigo | Procedimiento T-SQL | Bloque del informe |
|---|--------|---------------------|--------------------|
| TM01 | `TM01_TELEORIENTACION` | `usp_..._04_TM_RPT_01_TELEORIENTACION` | Valoracion clinica / con FR / con FR + solicitud de laboratorio (Z019 + 99499.08, #LABORATORIO Z017) |
| TM02 | `TM02_TELEMONITOREO` | `usp_..._04_TM_RPT_02_TELEMONITOREO` | Valoracion con FR y entrega de resultados (Z019 R + #TELEMONITOREO 82947 + 99499.10) |
| TM03 | `TM03_TELEMONITOREO_HTA` | `usp_..._04_TM_RPT_03_TELEMONITOREO_HTA` | Hipertenso sin/con tensiometro (I10 R PC + 99499.10 [+ 99199.22]) |
| TM04 | `TM04_TELEMONITOREO_DM` | `usp_..._04_TM_RPT_04_TELEMONITOREO_DM` | Diabetico sin glucometro (99499.10) / con glucometro o resultados (99499.11) |
| TM05 | `TM05_TELECONSULTAS` | `usp_..._04_TM_RPT_05_TELECONSULTAS` | Teleconsultas (E1x/I10 R + 99499.01) |

Grupos etareos:
- **Valoracion/HTA/TM** (DimNT_Gedad, 6 grupos): `05-11a`, `12-17a`, `18-29a`, `30-39a`, `40-59a`, `60a y mas` (exige Tipo_Edad='A' y edad>=5).
- **DM** (DimNT_Gedad2, 8 grupos): `Menores de 1a` (D/M), `01-04a`, `05-11a`, `12-17a`, `18-29a`, `30-39a`, `40-59a`, `60a y mas`.

## Instalacion

1. Copiar los archivos del modulo a la raiz del sistema (junto a `reporte_zoonosis.php`):
   `reporte_no_transmisibles.php`, `nontransmisibles_export.php`, `install_no_transmisibles.php`,
   `includes/nontransmisibles_data.php`, `includes/nontransmisibles_render.php`.
2. Copiar la plantilla oficial `Reporte_Actividades_NoTransmisibles.xlsx` a `uploads/`.
3. (Recomendado, una sola vez) Abrir como admin `install_no_transmisibles.php` para crear/verificar
   los indices de la tabla consolidada. Luego **eliminar** `install_no_transmisibles.php`.
4. Entrar a **Reportes Operacionales -> NO TRANSMISIBLES -> Generar Reporte**
   (la tarjeta No Transmisibles y `?sub=no_transmisibles` redirigen automaticamente al nuevo reporte).

No requiere tablas nuevas: el motor consulta la misma tabla consolidada MySQL que ya usan
ESNI, Cancer, Materno y Zoonosis (`T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`).

### Requisito para DM03/DM04/DM06/DM07

Estas 4 secciones filtran por **categoria de establecimiento** (`#RENAES` del T-SQL):
DM03 (I-2/I-3/I-4), DM04 (II-1/II-2/II-E/III-1/III-2/III-E), DM06 (I-4 + II/III) y
DM07 (I-1/I-2/I-3/I-4/II-1). Requieren el catalogo `MAESTRO_HIS_ESTABLECIMIENTO`
(campos `Codigo_Unico` + `Categoria_Establecimiento`) cargado via **Importar Datos**.
Sin el maestro, esas secciones quedan en 0 (el reporte muestra una advertencia) y las
otras 20 funcionan normalmente.

## Ajuste de reglas contra el archivo "03 Creacion de Procedimientos"

**Todas las condiciones estan centralizadas y son auditables:**

1. En el reporte web marque la casilla **"Ver SQL (auditoria)"**: cada seccion
   muestra la condicion SQL equivalente que ejecuta el motor y su regla de conteo
   (count(distinct id_persona) / count(*) / sesiones).
2. Compare esas condiciones con su archivo 03 original.
3. Ajuste las que difieran en un unico lugar: `includes/nontransmisibles_data.php` ->
   funcion `ntSecciones()` (o `ntDmFilasControl` / `ntDmFilasValoracion` /
   `ntDmFilasNefropatia` / `ntHtaFilasTratamiento`), campo `cond` de cada fila.

### DSL de condiciones (campo `cond`)

| Clave | Ejemplo | Equivalente T-SQL |
|-------|---------|-------------------|
| `cod` | `'Z019'` / `['E100','E108']` | `cod_item IN ('Z019')` |
| `codF` | `'I10'` / `['E10','E11','E13','E14']` | `cod_item_f IN (...)` (= `Codigo_Item LIKE 'I10%'`; prefijo CIE de 3 letras) |
| `tip` | `'D'` / `['D','R']` | `id_tipitem IN ('D','R')` (Tipo_Diagnostico) |
| `vl` | `'NULL'` / `'PC'` / `['1','2','3']` | `valor_lab IS NULL` / `= 'PC'` / `IN ('1','2','3')` |
| `vlNum` | `['lt',120]` / `['bt',70,99]` / `['ge',140]` / `['any']` | `ISNUMERIC(valor_lab)=1 AND valor_lab < 120 / BETWEEN 70 AND 99 / >= 140` (rango numerico, como materno_data.php) |
| `rownum` | `1` / `2` | `I_ROWNUM_LAB = 1` (Id_Correlativo_Lab) |
| `sexo` | `'F'` | `id_genero = 'F'` |
| `edadA` | `[5, null]` / `[40, null]` / `[5,59]` | `id_tipedad_reg='A' AND edad_reg >= 5` |
| `pab` | `['le',102]` / `['gt',88]` | `try_convert(int, perimetro_abdominal) <= 102` (Perimetro_Abdominal) |
| `ups` | `'302701'` | `id_ups = '302701'` (Id_Ups) |
| `ficha` | `'APP100'` | `fichafam = 'APP100'` (Ficha_Familiar) |
| `fg` | `'CX'` | `fg_tipo = 'CX'` (Fg_Tipo, casos confirmados DM) |
| `renaesCat` | `['I-2','I-3','I-4']` | `renaes IN (SELECT Codigo_Unico FROM MAESTRO_HIS_ESTABLECIMIENTO WHERE Categoria_Establecimiento IN (...))` |
| `citaTiene` | `<pred>` | `id_cita IN (SELECT id_cita FROM #TEMP WHERE <pred>)` |
| `citaTieneTodo` | `[<pred>, <pred>]` | AND de EXISTS sobre la cita |
| `cualquieraDe` | `[<pred>, <pred>]` | OR sobre la misma fila |

### Reglas de conteo (campo `regla`)

| Regla | Equivalente T-SQL | Secciones |
|-------|-------------------|-----------|
| `personas` | `count(distinct id_persona)` del consolidado, replicado como pares distintos `(renaes|id_paciente|anio|mes)` | 21 secciones (todas menos DM02 y HTA7) |
| `filas` | `count(*)` | DM02 GLUCEMIA |
| `sesiones` | `count(*)` para N + `sum(try_convert(int, valor_lab))` con `iif(null, 1)` para PARTICIPANTES | HTA7 SESIONES |

### Notas de adaptacion documentadas

- Los rangos de `valor_lab` del T-SQL (`valor_lab<'140'`, `between '70' and '99'`) se
  interpretan **numericos** (`vlNum`), misma convencion que `materno_data.php`.
- `try_convert(int, perimetro_abdominal)` se compara como decimal contra 88/102 (VR02).
- El `aniomes`/`periodo` del T-SQL se convierte en los filtros web Anio/Mes/Establecimiento.
- HTA5 dx 7-10 (atencion integral especializado) usa los UPS 302701/300201/303408/303101
  tal cual el Temporal4 del SP.
- TM04 marca ambas filas (sin/con glucometro) con Valoracion=1: se exponen como 2 filas
  segun #TELEMONITOREO (99499.10) y #RESULTADOS (99499.11), como la plantilla.
- DM03/DM04 categoria 29/33: el SP ancla 99199.22 para E10 y 82947/82948 para E11/E13/E14
  (tal cual el CASE); se replica fielmente.
- La deduplicacion de personas usa `(renaes|id_persona|anio|mes)`: es la suma de los
  `count(distinct id_persona)` POR ESTABLECIMIENTO Y PERIODO que consolidaba el Excel ODBC
  (una persona atendida en 2 EE.SS. o 2 meses cuenta 2, como el flujo original).

## Exportar Excel (plantilla oficial)

- El boton **Exportar Excel** regenera el reporte con los mismos filtros y llena la
  plantilla `uploads/Reporte_Actividades_NoTransmisibles.xlsx` con `ExcelTemplateFiller`
  (preserva merges, estilos morados, anchos y la formula `B8 = +B7*-1`).
- Cabecera: `B5` = PERIODO, `B7` = CODIGO RENAES (numerico para la formula B8),
  `H7` = IPRESS (H5 trae fija la RED "CHANCHAMAYO" de la plantilla).
- Nombre del archivo: `Reporte_Actividades_NoTransmisibles_{anio}_{mes}_{renaes}.xlsx`.

## Diagnostico de problemas

| Problema | Causa probable | Solucion |
|----------|----------------|----------|
| HTTP ERROR 500 al Generar | Tabla sin indices / memory_limit | Ejecutar una vez `install_no_transmisibles.php`; filtrar por un mes/EE.SS. |
| DM03/DM04/DM06/DM07 en 0 | `MAESTRO_HIS_ESTABLECIMIENTO` vacio | Importar el maestro HIS desde **Importar Datos** |
| Seccion en 0 inesperada | Condicion que difiere del archivo 03 | Activar "Ver SQL (auditoria)", comparar y ajustar `ntSecciones()` |
| Exportar Excel: "Plantilla oficial no encontrada" | Falta `uploads/Reporte_Actividades_NoTransmisibles.xlsx` | Copiar la plantilla a `uploads/` |
