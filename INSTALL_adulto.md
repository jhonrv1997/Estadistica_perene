# Modulo ADULTO (v1.2) - Reporte de Actividades Mensuales de la Etapa de Vida Adulto

Modulo data-driven de IntelHIS que **reemplaza el flujo manual del Modulo de Adulto** por un flujo web de 1 click.

## Flujo anterior (4 pasos manuales)

1. SQL Server: ejecutar `01 Creacion tablas iniciales` (tablas dimensionales `DimAdulto*`: Morbilidad, IMC, Riesgo, Valoracion, HTA, Dm, Metodos, Consejeria, Sesion, Planes, Prestacion, Prestacion2, DxSBucal, DxTBC, DxVIH, DxOcular, Actividad, Usuarias, Vacuna, Valoracion, Condicion, Gestacion, Grupo, Sesion2, Sesion3, Consejeria2)
2. SQL Server: ejecutar `02 Creacion tablas consolidacion` (`TRAMA_BASE_ADULTO_RPT_01..17_CONSOLIDADO / _NOMINAL`)
3. SQL Server: ejecutar `03 Creacion de Procedimientos` (21 procedimientos `usp_TRAMA_BASE_ADULTO_RPT_*`)
4. Excel: abrir `Reporte_Actividades_Adulto.xlsx` y refrescar la conexion ODBC

## Flujo nuevo (1 click)

- Entrar a IntelHIS -> **Reportes Operacionales -> ADULTO -> Generar Reporte**
- (Opcional) **Exportar Excel**: llena la plantilla oficial
  `uploads/Reporte_Actividades_Adulto.xlsx` con el mismo layout (17 bloques, filas 10-256,
  columnas D=F, E=M, F=TOTAL).
- (Opcional) **Exportar Plano** → deposita los mismos datos en el plano consolidado horizontal
  `uploads/Adulto_Plano.xlsx` (fila 13, `D13:LG13` + identificación en `B13`/`C13`), usando los
  numeros de destino de `Ubicacion_Filas_Adulto.xlsx`.

## Archivos del modulo

| Archivo | Rol |
|---------|-----|
| `includes/adulto_data.php` | Motor de reglas: definicion de las 21 secciones (17 bloques del Excel) con las condiciones EXACTAS de los 21 SP del archivo 03, DSL de predicados (espeja los WHERE del T-SQL), reglas de conteo, generacion por trozos y ejecucion contra `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` |
| `includes/adulto_render.php` | Funciones de render web (layout tipo Excel: 30a-59a con F/M/TOTAL, metodos A/I con NUEVO/CONTI, sesiones con Nº/Part-Benef) y mapa de celdas para el export (`aduCeldasExport`) |
| `reporte_adulto.php` | Pagina del reporte (1 click): filtros anio/mes/EE.SS., asistente por partes para rangos amplios, proteccion anti HTTP 500, panel "Ver SQL" (auditoria) |
| `adulto_export.php` | Exportacion a la plantilla oficial .xlsx (preserva formato: merges, estilos, D5='CHANCHAMAYO' fija) |
| `adulto_export_plano.php` | NUEVO: exportacion al plano consolidado `Adulto_Plano.xlsx` (boton "Exportar Plano") |
| `includes/adulto_plano.php` | NUEVO: logica de mapeo del plano (espejo + fila 12 del plano, con cache en disco) |
| `install_adulto.php` | Asistente de indices de la tabla consolidada + tabla de cache `ADULTO_RPT_CACHE` (eliminar tras usar) |
| `uploads/Reporte_Actividades_Adulto.xlsx` | Plantilla oficial (base del export) |
| `uploads/Adulto_Plano.xlsx` | NUEVO: plantilla del plano consolidado (fila 12 = numeros 1..319 en `A12:LG12`; fila 13 = datos) |
| `uploads/Ubicacion_Filas_Adulto.xlsx` | NUEVO: espejo del reporte con los numeros de destino (316 numeros, 4..319) |
| `reporte_operacionales.php` | MODIFICADO: la tarjeta Adulto del indice apunta a `reporte_adulto.php` y `?sub=adulto` redirige a ella |

## Estructura del reporte (21 SP -> 17 bloques, filas 10-256 de la plantilla)

| # | Codigo | Procedimiento (archivo 03) | Filas Excel | Layout |
|---|--------|---------------------------|-------------|--------|
| 01 | `ADU01_MORBILIDAD` | `..._01_MORBILIDAD` | 14-23 | D=F, E=M, F=TOTAL (cod_item_f A64/E66/J00/J02/J06/K02/K04/K29/M54/N39, I_ROWNUM_LAB=1) |
| 02 | `ADU02_PAI` | `..._02_PAI` | 29-30 | C8002: 'TA'=EJECUTADO (29), TRY_CONVERT(vl)=1=ELABORADO (30) |
| 03 | `ADU03_IMC` | `..._03_EVAL_ANTROPOMETRICA` | 36-43 | E46X3/E46X2/E46X1/Z006/E660/E669/E6692/E6693 con vl='IMC' |
| 04 | `ADU04_PAB` | `..._04_RIESGO_NUT` | 49-50 | U8170 vl N=NORMAL (49), A=ANORMAL (50) |
| 05_1 | `ADU05_1_VALORACION` | `..._05_1_DNT_VALORACION` | 56-58 | Z019 vl='DNT' + EXISTS #TMP_CITA [Z006] / #TMP_CITA2 [FR: E65X, Z72x, Z883, Z834, cod_item_f='E66'] / U262 |
| 05_2 | `ADU05_2_HTA` | `..._05_2_DNT_HTA` | 62-64 | I10X / H350 / I120,I129 (rownum=1) |
| 05_3 | `ADU05_3_DM` | `..._05_3_DNT_DM` | 68-73 | fg_tipo='CX': cod_item_f E10/E11/O24/E12-E14 + nefropatia (E10x2...) + retinopatia (E10x3...) |
| 06_1 | `ADU06_1_PF_CLASIF` | `..._06_1_PF_CLASIF` | 79-84 | 99402.04/.03/.06 con TRY_CONVERT(vl) in (1,2) = 1a/2a sesion |
| 06_2 | `ADU06_2_PF_METODOS` | `..._06_2_PF_METODOS` | 88-113 | 13 metodos x fila A (Actividad=count(Usuarias)) / fila I (Insumo=sum(LAB)); Usuarias 1=NUEVO (D), 2=CONTI (R) |
| 06_3 | `ADU06_3_AOE` | `..._06_3_PF_METODOS` | 116-119 | YUZPE/PROGESTAGENO, solo id_genero='F': fila A=count, fila I=sum(LAB, min 1) |
| 07 | `ADU07_CANCER` | `..._07_CANCER` | 124-125 | 99402.08 vl 1/2 |
| 08 | `ADU08_SALUD_BUCAL` | `..._08_SALUD_BUCAL` | 130-157 | 7 prestaciones x NO GESTANTES/GESTANTES x INICIAN/TRATADO. v1.1: en ASESORIA NUTRICIONAL (D1310), PROFILAXIS DENTAL (D1110), CONSULTA ESTOMATOLOGICA (D0140), APLICACION FLUOR BARNIZ (D1206) y RASPAJE DENTAL (D4355) el gestante se define con el campo `Descripcion_Otra_Condicion` de la propia fila (`= 'GESTANTE'` / `<> 'GESTANTE'`); EXAMEN ESTOMATOLOGICO e INSTRUCCION DE HIGIENE ORAL conservan el criterio del SP (cita con vl='G') |
| 09 | `ADU09_SALUD_MENTAL` | `..._09_SALUD_MENTAL` | 162-172 | v1.2.0: entrevista = 96150.01+VIF / 96150.02+AD / 96150.03+TD (tipitem D; VSX/VS conservan U140 del SP); tamizaje positivo = fila de entrevista 96150.0X tipitem D en citas que ademas registran R456/Z720/Z721/Z722/Z133 tipitem D |
| 10 | `ADU10_TBC` | `..._10_TBC` | 177-178 | Dx1 = U200 (D o R) en citas con U2142 vl='1'; Dx2 = U200 tipitem D |
| 11 | `ADU11_SSR` | `..._11_SSR` | 183-195 | 13 prestaciones (Z7172/Z7173, Z359x, Z298 SF6/AF3, U2601, cod_item_f O80-O84, D060x/N87x) |
| 12 | `ADU12_VIH` | `..._12_VIH` | 200-206 | 99402.05 PV / vl null-vacio; Z7172/Z7173; 86706; Z115 PRA |
| 13 | `ADU13_OCULAR` | `..._13_SALUD_OCULAR` | 211-215 | 99401.16/99173/H456/H259/Z006 (rownum=1) |
| 14 | `ADU14_APP` | `..._14_APP` | 220-224 | 99344 vl 1/2/>=3 + C0011.01/.02 |
| 15 | `ADU15_VACUNA` | `..._15_VACUNA` | 229-238 | Z2781 (vl 1|D1, 2|D2, 3|D3) separado por #GESTANTES (cita con vl='G') en NO GESTANTES 1-3 / GESTANTES 4-6; 90744 (1|D1, 2|D2, 3|D3); 90657 D1 |
| 16 | `ADU16_SESIONES` | `..._16_SESIONES` | 243-246 | C0010/C0009/U114/U122 tipitem D rownum=1 id_persona IS NULL y cita con U0041; SIN filtro de edad y SIN exigir sexo; Nº=count, Part/Benef=sum(LAB, min 1) |
| 17 | `ADU17_CONSEJERIAS` | `..._17_CONSEJERIAS` | 251-256 | 99403.01/99402.08/99401.31/99401.15/99173/99174 (rownum=1); unica columna TOTAL (D) |

Grupo etareo: **30a-59a** (cabecera 30a-59a con F/M + TOTAL, igual que la plantilla).

## Notas fieles al T-SQL (comportamientos replicados)

Estos detalles del archivo 03 se replican EXACTAMENTE para que las cifras coincidan con el
Excel que generaba el flujo ODBC. Estan documentados tambien en los comentarios del motor
y en el panel "Ver SQL" del reporte web:

1. **Raspaje dental (D4355) — v1.1.0**: la clasificacion gestante ya no usa las
   TABLA1/TABLA2 del SP (marcadores `valor_lab='G'` y `FIG`/`FIN` de la cita):
   GESTANTES (filas 156/157) = `Descripcion_Otra_Condicion='GESTANTE'` y
   NO GESTANTES (154/155) = `Descripcion_Otra_Condicion<>'GESTANTE'`
   (NULL/vacio cuentan como NO GESTANTE). Por eso las filas 156/157 ahora
   reportan casos reales (antes quedaban siempre en 0 porque el SP escribia la
   TABLA2 del raspaje gestante con `Gestacion=0`).
2. **Salud bucal (v1.1.0)**: en ASESORIA NUTRICIONAL (D1310), PROFILAXIS DENTAL
   (D1110), CONSULTA ESTOMATOLOGICA NO ESPECIALIZADA (D0140) y APLICACION FLUOR
   BARNIZ (D1206), INICIAN y TRATADO/CONTROLADO dejan de filtrar por
   `citaSin`/`citaTiene` con `valor_lab='G'` (#GESTANTES del SP) y usan el campo
   `Descripcion_Otra_Condicion` de la base de datos: GESTANTES =
   `Descripcion_Otra_Condicion='GESTANTE'`, NO GESTANTES =
   `Descripcion_Otra_Condicion<>'GESTANTE'` (NULL/vacio incluidos). EXAMEN
   ESTOMATOLOGICO (D0120) e INSTRUCCION DE HIGIENE ORAL (D0130) no formaban parte
   de la correccion y mantienen el criterio del SP.
2. **VIH (SP 12)**: el CASE nunca produce `Diagnostico 5` -> la fila
   **CONSEJERIA POST-TEST NEGATIVO (204) queda en 0**, y `86706` cae en
   **TAMIZAJE HEPATITIS B REACTIVO (205)** con `Z115 vl='PRA'` en el NO REACTIVO (206).
3. **TBC (SP 10)**: `Diagnostico 1` (fila 177, EXAMINADOS) = U200 (D o R) en citas con
   `U2142 vl='1'`; `Diagnostico 2` (fila 178, IDENTIFICADOS) = U200 tipitem D. Es la
   asignacion literal del CASE del SP 10.
4. **Codigos compartidos entre SP**: una misma fila HIS cuenta en todos los bloques donde
   aplique (p.ej. Z7172 cuenta en SSR fila 183/184 y en ITS-VIH fila 202; Z006 con vl='IMC'
   cuenta en IMC fila 39 y en Salud Ocular fila 215; E660 con vl='IMC' cuenta en IMC
   sobrepeso y en Morbilidad E66; 99402.08 cuenta en Cancer y en Otras Consejerias fila
   252). Es el comportamiento natural de SP independientes.
5. **E115 (TOTAL del bloque de metodos)**: el SP 06_2 no produce una fila TOTAL; se llena
   con la suma de nuevas usuarias (Actividad, Usuarias=1) de los 13 metodos.
6. **AQV (58600/58605/58611/55250)**: solo entra a Usuarias cuando `valor_lab` es NULL o
   vacio; metodos 8-13 (AQV, MELA, BILLINGS, RITMO, DIAS FIJO) reportan Insumo 0.
7. **Sesiones (SP 16)**: el filtro de edad viene comentado en el T-SQL y el nominal no
   exige sexo -> el motor no aplica ni edad ni "sexo is not null" en ese bloque; exige
   `id_persona IS NULL` y que la cita tenga una fila U0041.
8. **Salud mental (SP 09) — v1.2.0**: se corrigen los criterios de la seccion. Grupo 1
   (ENTREVISTA DE TAMIZAJE): la fila cuenta por `Codigo_Item + tipitem D + valor_lab`
   (VIOLENCIA FAMILIAR = 96150.01+VIF, ALCOHOL Y DROGAS = 96150.02+AD, TRASTORNOS
   DEPRESIVOS = 96150.03+TD); VIOLENCIA SEXUAL y VIOLENCIA SOCIAL conservan el criterio
   del SP original (fg_tipo='CX' + U140 + VSX/VS). Grupo 2 (TAMIZAJE POSITIVO): cuenta la
   fila de la entrevista (96150.01/02/03/04 tipitem D) en citas que ademas registran el
   diagnostico confirmatorio tipitem D en la misma cita — VIOLENCIA: R456, TABACO: Z720,
   ALCOHOL: Z721, DROGAS: Z722, DEPRESION: 96150.03+Z133, PSICOSIS: 96150.04+Z133.
   Con la correccion se anaden `96150.01-04`, `Z720`, `Z721` y `Z722` a los codigos de
   interes de la consulta unica (sin ellos las filas 168-170 no llegaban a leerse de la
   base) y se retira `fg_tipo='CX'` de las filas corregidas (el criterio queda definido
   por cod+tip+vl, igual que el modulo Adolescente con 96150.01-05). Validado con un
   fixture de 18 citas / 26 filas HIS y conteos esperados por sexo para las 11 filas.

## Instalacion

1. Copiar los archivos del modulo a la raiz del sistema (junto a `reporte_adolescente.php`):
   `reporte_adulto.php`, `adulto_export.php`, `adulto_export_plano.php`,
   `install_adulto.php`, `includes/adulto_data.php`, `includes/adulto_render.php`,
   `includes/adulto_plano.php`.
2. Copiar la plantilla oficial `Reporte_Actividades_Adulto.xlsx` a `uploads/` y, para el
   plano, tambien `Adulto_Plano.xlsx` y `Ubicacion_Filas_Adulto.xlsx`.
3. Reemplazar `reporte_operacionales.php` por la version incluida (o aplicar el mismo
   cambio a mano: agregar `'adulto'` a `$esAvanzado`, la tarjeta con
   `$href = 'reporte_adulto.php'` y el bloque `elseif ($sub === 'adulto')` que redirige).
4. (Recomendado, una sola vez) Abrir como admin `install_adulto.php` para crear/verificar
   los indices de la tabla consolidada y la tabla `ADULTO_RPT_CACHE`. Luego **eliminar**
   ese archivo.
5. Entrar a **Reportes Operacionales -> ADULTO -> Generar Reporte**.

No requiere tablas nuevas: el motor consulta la misma tabla consolidada MySQL que ya usan
ESNI, Cancer, Materno, ITS y Adolescente (`T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`).
Las tablas `DimAdulto*` y `TRAMA_BASE_ADULTO_RPT_*` de SQL Server ya no hacen falta: sus
etiquetas viven en el motor PHP y sus conteos se calculan en linea.

## Ajuste de reglas contra el archivo "03 Creacion de Procedimientos"

**Todas las condiciones estan centralizadas y son auditables:**

1. En el reporte web marque la casilla **"Ver SQL"**: cada fila muestra la condicion
   SQL equivalente (con la sintaxis del T-SQL original: `cod_item_f`, `I_ROWNUM_LAB`,
   `fg_tipo`, `TRY_CONVERT`, `id_cita IN (SELECT ...)`).
2. Compare contra su archivo `03 Creacion de Procedimientos.txt` y ajuste en
   `includes/adulto_data.php -> aduSecciones()` la seccion correspondiente.
3. La casilla **"Filas en 0"** oculta las filas sin datos (igual que el Excel ODBC, que
   dejaba vacias las combinaciones sin casos).

## Rangos amplios ("Todos")

Con filtros amplios (varios meses / varios EE.SS) la consulta unica agotaria el
memory_limit (512M) o el max_execution_time (60s) del hosting. El reporte usa el mismo
**asistente por partes** que el modulo Adolescente: `ajax=plan/trozo/dividir/resultado`
acumula los parciales en `ADULTO_RPT_CACHE` y la suma de las partes es identica al total
(las condiciones `EXISTS` se resuelven dentro de una cita, y una cita vive completa en un
unico Anio-Mes-Establecimiento).

## Exportar Excel

`adulto_export.php?anio=...&mes=...&establecimiento=...` reutiliza el resultado ya
calculado (token del asistente) o ejecuta la consulta si el rango es angosto, y llena la
plantilla con `includes/ExcelTemplateFiller.php`:

- `B5` = PERIODO ("JUNIO 2026"), `B7` = CODIGO RENAES (Codigo_Unico; vacio con "Todos"),
  `D7` (D7:G7) = EE.SS o "TODOS". La RED (D5='CHANCHAMAYO') ya viene fija en la plantilla.
- Datos: `D`=F (mujeres), `E`=M (hombres), `F`=TOTAL; metodos PF: fila A/I con
  `E`=NUEVO, `F`=CONTI; AOE: fila A/I con unica cifra en E; sesiones: `D`=Nº, `E`=Part/Benef;
  otras consejerias: `D`=TOTAL.

## Exportar Plano (plano consolidado)

El boton **Exportar Plano** usa el mismo reporte ya calculado (`aduCeldasExport()`, identico a
*Exportar Excel*) y lo deposita en el plano horizontal `Adulto_Plano.xlsx` (misma mecanica que
los planos de Zoonosis/ITS/Materno/NoTransmisibles/Adolescente):

| Archivo | Rol |
|---------|-----|
| `Ubicacion_Filas_Adulto.xlsx` | Espejo del reporte oficial: cada celda de datos contiene el NUMERO DE DESTINO. Ej: `D14` (A64, mujeres 30a-59a) = 4; `E14` (varones) = 5. 316 numeros consecutivos 4..319. |
| `Reporte_Actividades_Adulto.xlsx` / `aduCeldasExport` | La celda equivalente (misma fila y columna: el espejo calza fila por fila con la plantilla oficial) contiene el DATO. Ej: `D14` = 2. |
| `Adulto_Plano.xlsx` | Destino: fila 12 (`A12:LG12`) = numeracion de columnas 1..319; fila 13 = datos (`D13:LG13`). El numero 4 ⇒ columna D ⇒ el dato de `D14` (A64, mujeres 30a-59a) se escribe en `D13`. |

- Identificacion del registro (fila 13): `B13` = Codigo RENAES, `C13` = nombre del EE.SS (o "TODOS LOS ESTABLECIMIENTOS DE LA LISTA"). La columna `A13` (Ubigeo) se deja vacia porque el catalogo `ZSPERENE` no trae ubigeo (mismo criterio que los planos de Zoonosis, NoTransmisibles y Adolescente).
- Periodo: la plantilla `Adulto_Plano.xlsx` no trae celda "MES:" en su cabecera (igual que los planos de Zoonosis e ITS), por lo que no se escribe el periodo en ninguna celda: el periodo exportado se identifica por el nombre del archivo descargado.
- Celdas sin numero de destino (por diseno del plano): los numeros 1-3 son las columnas de identificacion (Ubigeo/RENAES/EE.SS); las 123 columnas "Total" (F de los layouts FMT) y la fila TOTAL `E115` del bloque de metodos PF no existen como columnas del plano y se omiten de forma segura.
- Rendimiento: el mapeo espejo/plano se cachea en `cache/plano/` (JSON invalidado por mtime/size); si la carpeta no es escribible, el sistema sigue leyendo los xlsx directamente.
- Rangos amplios: igual que *Exportar Excel*, si no hay token del asistente y el rango es amplio, la pagina pide generar primero el reporte (asistente por partes) y exportar desde ahi.
- Nombre del archivo: `Adulto_Plano_{anio}[_{mes}][_{renaes}].xlsx`.

### Diagnostico de problemas del Plano

| Mensaje | Causa | Solucion |
|---------|-------|----------|
| "Plantilla del plano no encontrada" | Falta `uploads/Adulto_Plano.xlsx` | Copiar la plantilla del plano a `uploads/` |
| "Archivo de ubicacion no encontrado" | Falta `uploads/Ubicacion_Filas_Adulto.xlsx` | Copiar el espejo de ubicaciones a `uploads/` |
| "El plano quedaria vacio" | El espejo no corresponde a la plantilla actual | Verificar que el espejo es el oficial de Adulto |
| "Rango demasiado amplio" | Plano sin token y filtros amplios | Generar primero el reporte (asistente) y exportar desde ahi, o acotar filtros |

## Validacion

El motor se valido con un fixture de 8 citas (70 filas HIS) que activa todos los caminos
de los 21 SP, con valores esperados derivados a mano del T-SQL: 91 checks (incluidas las
notas 1-7 de arriba). El export se valido celda por celda contra la plantilla oficial.
El plano se valido automaticamente contra el motor: las 316 celdas numericas del espejo
(`D14`..`D256`, `E`, `F`) tienen dato en `aduCeldasExport()` (cobertura 100%, 0 celdas del
motor sin numero fuera de las columnas Total y `E115`), y los 316 datos caen exactamente
en `D13:LG13` (columnas 4..319 de la fila 12 del plano), preservando formulas, cabeceras
y celdas combinadas de la plantilla.
