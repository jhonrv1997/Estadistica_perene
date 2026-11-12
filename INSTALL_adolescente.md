# Módulo ADOLESCENTE — IntelHIS

**Reporte de Actividades del Adolescente (Promoción de la Salud — grupos etareos 12a-14a y 15a-17a)**

Reemplaza el flujo manual (SQL Server + 3 archivos .txt + Excel con conexión ODBC) por una solución web **data-driven**, igual que ESNI, Cancer, Materno, Zoonosis, No Transmisibles e ITS/VIH.

> **v1.1 — corrección del error fatal "No se pudo generar el reporte ADOLESCENTE"**
> 1. **Bug corregido:** un closure de `adoSecciones()` (sección 03 Visita Domiciliaria) usaba variables externas sin `use (...)` → `TypeError` fatal en TODA generación, con cualquier filtro.
> 2. **Límites del hosting (512M / 60s):** con filtros amplios ("Todos") la consulta única agotaba memoria/tiempo. Ahora el módulo genera **por trozos** (asistente con barra de progreso) y el resultado es idéntico al de la consulta única (verificado con pruebas automatizadas de equivalencia). Motor `adolescente_data.php` **v2.0.0-trozos**. Detalle en la sección [Generación por trozos](#generación-por-trozos-v11-hosting-512m--60s).

## Flujo anterior (4 pasos manuales)

1. SQL Server: ejecutar `01 Creacion tablas iniciales` (DimAdolescente2020_*)
2. SQL Server: ejecutar `02 Creacion tablas consolidacion` (TRAMA_BASE_ADOLESCENTE_2020_RPT_*)
3. SQL Server: ejecutar `03 Creacion de Procedimientos` (25 SP usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_*)
4. Excel: abrir `Reporte_Actividades_Adolescentes.xlsx` y refrescar la conexión ODBC

## Flujo nuevo (1 click)

- Entrar a IntelHIS → **Reportes Operacionales → Adolescente → Generar Reporte**
- (Opcional) **Exportar Excel** → llena la plantilla oficial `uploads/Reporte_Actividades_Adolescentes.xlsx` con los mismos datos y layout.
- (Opcional) **Exportar Plano** → deposita los mismos datos en el plano consolidado horizontal `uploads/Adolescentes_Plano.xlsx` (fila 13, `C13:PK13`), usando los numeros de destino de `Ubicacion_Filas_Adolescentes.xlsx`.

## Archivos del módulo

| Archivo | Rol |
|---------|-----|
| `reporte_adolescente.php` | Página del reporte (1 click): filtros año/mes/EE.SS + 27 bloques + panel de auditoría |
| `adolescente_export.php` | Exporta a Excel con el layout de la plantilla oficial |
| `adolescente_export_plano.php` | NUEVO: exportación al plano consolidado `Adolescentes_Plano.xlsx` (botón "Exportar Plano") |
| `includes/adolescente_plano.php` | NUEVO: lógica de mapeo del plano (espejo + fila 12 del plano, con cache en disco) |
| `install_adolescente.php` | Asistente de índices (ejecutar UNA vez como admin; eliminar después) |
| `includes/adolescente_data.php` | Motor de reglas data-driven (25 SP adaptados + dimensiones) |
| `includes/adolescente_render.php` | Render web + mapa de celdas del export |
| `uploads/Reporte_Actividades_Adolescentes.xlsx` | Plantilla oficial (copiar ahí para el export) |
| `uploads/Adolescentes_Plano.xlsx` | NUEVO: plantilla del plano consolidado (fila 12 = números 1..427 en `A12:PK12`; fila 13 = datos) |
| `uploads/Ubicacion_Filas_Adolescentes.xlsx` | NUEVO: espejo del reporte con los números de destino de cada celda |
| `INSTALL_adolescente.md` | Este archivo |

## Estructura del reporte (27 bloques = 25 procedimientos)

Los números de fila corresponden a la hoja `Plantilla` del Excel oficial:

| # | Bloque | SP | Filas Excel | Filas | Layout |
|---|--------|----|------------|-------|--------|
| 1 | Morbilidad del Adolescente | `usp_..._01_MORBILIDAD` | 11-14 | 4 | STD: B=Total, C=12a-14a, D=15a-17a (IRA complicadas/no, EDA complicadas/no) |
| 2 | Control y Seguimiento | `usp_..._02_CONTROL` | 21-26 | 6 | CTRL: C=Total + EE.SS (D-M) × II.EE; Paquete × Institución × edad |
| 3 | Visita Domiciliaria | `usp_..._03_VISITA` | 31-36 | 6 | STD: C/D/E (C0011 y 99509 × 1°/2°/3+ visitas) |
| 4 | Evaluación Física Nutricional | `usp_..._04_EVALUACION` | 41-58 | 18 | STD: E/F/G (Z000, Z019, PAB RSM/RSA/RMA, IMC, Talla/Edad, etc.) |
| 5 | Agudeza Visual | `usp_..._05_VISUAL` | 63-66 | 4 | STD: E/F/G |
| 6 | Agudeza Auditiva | `usp_..._06_AUDITIVA` | 71-73 | 3 | STD: B/C/D |
| 7 | Atención Odontológica | `usp_..._07_ODONTOLOGICA` | 78-82 | 5 | STD: B/C/D |
| 8 | Físico Postural | `usp_..._08_FISICO` | 87-92 | 6 | STD: B/C/D |
| 9 | Inmunizaciones | `usp_..._09_INMUNIZACIONES` | 97-106 | 10 | STD: B/C/D (HvB 1-3, Influenza, dT 1-3, VPH 1-2, Antiamarílica) |
| 10 | Des. Sexual / Planificación Familiar | `usp_..._10_PLANIFICACION` | 111-124 | 14 | STD: C/D/E con suma de valor_lab (métodos) |
| 11 | Evaluación Clínica | `usp_..._11_CLINICA` | 128-144 | 17 | STD: B/C/D |
| 12 | Consejerías | `usp_..._12_CONSEJERIAS` | 149-161 | 13 | STD: B/C/D |
| 13 | Toma de Prueba Rápida | `usp_..._13_PRUEBAS` | 166-170 | 5 | STD: B/C/D |
| 14 | Salud Mental | `usp_..._14_MENTAL` | 175-183 | 9 | STD: C/D/E |
| 15 | Tamizajes Positivos | `usp_..._15_TAMIZAJES` | 188-195 | 8 | STD: B/C/D |
| 16a | Actividades Promocionales: Sesiones | `usp_..._16_1_ACTIVIDADES` | 200-201 | 2 | STD: B/C/D |
| 16b | Actividades Promocionales: APP | `usp_..._16_2_ACTIVIDADES` | 204-212 | 9 | CASOS: B=Casos (fichafam APP93..APP150, UPS 302304) |
| 16c | Actividades Promocionales: Comunitarias | `usp_..._16_3_ACTIVIDADES` | 215-226 | 12 | CASOS: B=Casos (C1021..C7004, UPS 302304) |
| 17 | Sesiones Educativas 12-14 (PSA) | `usp_..._17_NUTRICIONAL` | 231-239 | 9 | CASOS: B=12a-14a |
| 18 | Salud Física Nutricional 15-17 (PSA) | `usp_..._18_NUTRICIONAL` | 244-255 | 12 | CASOS: B=15a-17a |
| 19 | SSR 12-14 (SSI) | `usp_..._19_SSR` | 260-264 | 5 | CASOS: B=12a-14a |
| 20 | SSR 15-17 (SSI) | `usp_..._20_SSR` | 269-275 | 7 | CASOS: B=15a-17a |
| 21 | Psicosocial 12-14 (PSI) | `usp_..._21_PSICOSOCIAL` | 280-289 | 10 | CASOS: B=12a-14a |
| 22 | Psicosocial 15-17 (PSI) | `usp_..._22_PSICOSOCIAL` | 294-305 | 12 | CASOS: B=15a-17a |
| 23 | COVID-19 | `usp_..._23_COVID19` | 310-319 | 10 | STD: B/C/D |
| 24 | Seguimiento COVID / otras | `usp_..._24_SEGUIMIENTO` | 324-329 | 6 | STD: B/C/D |
| 25 | Medicina Alternativa | `usp_..._25_ALTERNATIVA` | 334-337 | 4 | STD: B/C/D (Medicina cuerpo mente = UPS 301903) |

## Dimensiones replicadas (archivo 01 → PHP)

| Dim SQL Server | Dónde en PHP | Valores |
|----------------|--------------|---------|
| DimAdolescente2020_Morbilidad | filas sección 01 | 1=IRA complicadas, 2=IRA no complicadas, 3=EDA complicadas, 4=EDA no complicadas |
| DimAdolescente2020_Actividad / Estado / Paquete / Institucion | sección 02 | Actividad 1/2/3 (99801/Z003/99384), Estado 1..6, Paquete 1..3 (pos 2 del PAI), Institución 1=EE.SS / 2=II.EE (pos 3) |
| DimAdolescente2020_Visita | sección 03 | 1..3 = C0011 1°/2°/3+; 4..6 = 99509 1°/2°/3+ |
| DimAdolescente2020_EvNutric / EvVisual / EvAuditiva | secciones 04/05/06 | keys 1..18 / 1..4 / 1..3 con los textos exactos |
| DimAdolescente2020_AtcOdontologica / EvFisico / Vacunas | secciones 07/08/09 | keys 1..5 / 1..6 / 1..10 |
| DimAdolescente2020_AtencionPF | sección 10 | 1=Tanner, 2=99208, 3=DIU … 14=AOE/YUZPE (con cantidad) |
| DimAdolescente2020_EvClinica / Consejeria / Prueba | secciones 11/12/13 | keys 1..17 / 1..13 / 1..5 |
| DimAdolescente2020_AtcMental / Tamizaje | secciones 14/15 | keys 1..9 / 1..8 |
| DimAdolescente2020_Actividad2 (APP) | sección 16b | fichafam APP93..APP150 |
| DimAdolescente2020_Actividad3 | sección 16c | C1021..C7004 |
| DimAdolescente2020_Sesion | sección 16a | 1=C0009, 2=C2111.02 |
| DimAdolescente2020_Sesion2/3_Nutric, Sesion4/5_SSR, Sesion6/7_Psicosoc | secciones 17-22 | número de sesión = valor_lab de C0009 fila 1, marcador PSA/SSI/PSI en fila 2 |
| DimAdolescente2020_Diagnostico / Seguimiento / Actividad4_Altern | secciones 23/24/25 | keys 1..10 / 1..6 / 1..4 |
| DimAdolescente2020_GrupoEdad1 | eje `edadG` | 1=12a-14a (e12_14), 2=15a-17a (e15_17) |

## Reglas de conteo

| Regla | Equivalente T-SQL | Dónde |
|-------|-------------------|-------|
| `filas` | `sum(iif(edad between 12 and 14,1,0))` / `count(*)` | todas las secciones salvo los métodos del SP 10 |
| `total_vl` | `sum(iif(edad between X,Y,Total,0))` con `Total = iif(TRY_CONVERT(int,valor_lab) IS NULL OR Atencion between 10 and 13, 1, TRY_CONVERT(int,valor_lab))` | SP 10: DIU, hormonales, condones, implante y AOE suman la cantidad registrada; MELA/BILLINGS/RITMO/DÍAS FIJO y las filas base (Tanner, 99208) cuentan 1 |

## Mapeo de columnas (TRAMAHIS_DTSG → tabla consolidada MySQL)

| SQL Server | MySQL | Notas |
|------------|-------|-------|
| id_cita | Id_Cita | |
| id_tipitem | Tipo_Diagnostico | 'D','R' (SP 10 admite D/R en Temporal1) |
| cod_item | Codigo_Item | |
| cod_item_f | (calculado) prefijo CIE 3 letras | A05x→A05, J18x→J18, E86x→E86, R05x→R05 |
| valor_lab | Valor_Lab | 'NULL' en el DSL = IS NULL |
| I_ROWNUM_LAB | Id_Correlativo_Lab | fila del valor de laboratorio |
| rownum | Id_Correlativo_Item | primera fila de la cita (SP 16_2 "t.rownum=1" y SP 25 UPS 301903) |
| id_genero | Id_Genero | el nominal exige "sexo is not null" (salvo 16_2/16_3) |
| edad_reg / id_tipedad_reg | Edad_Reg / Tipo_Edad | 'A' 12-17 |
| renaes | Codigo_Unico | |
| fichafam | Ficha_Familiar | APP93..APP150 (actividades promocionales) |
| id_ups | Id_Ups | '302304' actividades promocionales, '301903' medicina alternativa |
| periodo (YEAR) | Anio | CASE del SP 02: C8002 si año<2025, 99801 si año>=2025 |
| periodo | Anio + Mes | filtros web |

## Instalación

1. **Subir los archivos** del módulo al hosting (misma carpeta que `index.php`):
   - `reporte_adolescente.php`, `adolescente_export.php`, `adolescente_export_plano.php`, `install_adolescente.php`
   - `includes/adolescente_data.php`, `includes/adolescente_render.php`, `includes/adolescente_plano.php`
   - `uploads/Reporte_Actividades_Adolescentes.xlsx` (plantilla oficial; solo la necesita el export Excel)
   - `uploads/Adolescentes_Plano.xlsx` y `uploads/Ubicacion_Filas_Adolescentes.xlsx` (solo las necesita el export Plano)
2. **Índices (una sola vez):** abrir `install_adolescente.php` como administrador y esperar el resultado. Crea `idx_ado_anio`, `idx_ado_codigo_item`, `idx_ado_codigo_unico`, `idx_ado_id_ups`, `idx_ado_anio_mesint` (si existe `Mes_Int`) y la tabla `ADOLESCENTE_RPT_CACHE` del asistente por trozos (esta tabla también se crea sola al generar). Tras ver el resultado, **eliminar `install_adolescente.php`**.
3. (Opcional) **Verificar:** Reportes Operacionales → Adolescente → Generar Reporte con un mes acotado.

## Uso

1. Entrar a IntelHIS → **Reportes Operacionales** → tarjeta **Adolescente** (destacada).
2. Seleccionar Año / Mes / Establecimiento (opcional: grupo del reporte, mostrar filas en 0, modo auditoría SQL).
3. Pulsar **Generar Reporte** → los 27 bloques se calculan en ~1-3 s con índices. Si el rango es amplio (varios meses/EE.SS), arranca solo el **asistente por partes** con barra de progreso (ver la sección siguiente); al terminar muestra el mismo reporte, con un distintivo "N pasos".
4. Pulsar **Exportar Excel** → descarga `Reporte_Actividades_Adolescentes_<año>[_<mes>][_<renaes>].xlsx` con el diseño oficial (cabecera C6=PERIODO, H6=MICRO RED, H7=EE.SS; la RED y el RENIPRESS ya vienen fijos en la plantilla).
5. Pulsar **Exportar Plano** → descarga `Adolescentes_Plano_<año>[_<mes>][_<renaes>].xlsx` con los mismos datos en formato horizontal (ver sección siguiente).

## Mantenimiento (auditoría contra el archivo 03)

- Activar **Ver SQL (auditoría)** en el formulario: cada bloque muestra la condición SQL equivalente (los WHERE/CASE del SP adaptados al DSL).
- Si alguna regla difiere del archivo `03 Creacion de Procedimientos`, editar `includes/adolescente_data.php → adoSecciones()` (todo el módulo se ajusta en ese único archivo).
- Para cambiar los textos de las filas, son las `label` de cada sección (dimensiones del archivo 01).
- Para agregar un código o valor_lab nuevo, ajustar la condición correspondiente y agregar el código a `adoCodigosInteres()` (para que la consulta lo traiga).

## Notas de adaptación (desviaciones documentadas)

- **SP 02 (C8002/99801):** el CASE `YEAR(periodo)<2025` usa la columna `Anio` de la fila (claves DSL `anioLt`/`anioGe`), no el filtro del formulario: si el usuario filtra 2024 el motor cuenta C8002 y si filtra 2025+ cuenta 99801, igual que el SP.
- **`rownum` del T-SQL** (SP 16_2 y 25): TRAMAHIS_DTSG tiene una columna `rownum` aparte de `I_ROWNUM_LAB`; en MySQL se aproxima con `Id_Correlativo_Item = 1` (primera fila de la cita). Es el equivalente disponible en la tabla consolidada.
- **SP 01 (#EDA_COMP):** la cita se marca complicada si alguna fila registra R571 o familia E86 con `id_tipitem='D'`, y la fila contada es el dx EDA (fila 1) — igual que la tabla temporal del SP.
- **SP 02 (#PAI / #EVALUACION):** se replican como `citaTiene` sobre los códigos C8002/99801 (pos 2=Paquete, pos 3=Institución) y 99384 numérico (fila 1). El `delete where Paquete=3 and Institucion=2` se refleja en que el combo P3I2 no existe en el reporte.
- **SP 10:** `Total = iif(TRY_CONVERT(int,valor_lab) IS NULL OR Atencion between 10 and 13, 1, TRY_CONVERT(int,valor_lab))`: los métodos 10-13 (MELA/BILLINGS/RITMO/DÍAS FIJO) van con regla `filas` (siempre 1) y el resto con `total_vl` (cantidad del valor_lab o 1). El AOE (99208.11) solo cuenta con `id_tipitem='D'`.
- **SP 16_2/16_3:** no filtran edad, sexo ni tipitem (fidelidad total al SP); la sección 16_3 usa `I_ROWNUM_LAB=1` mientras 16_2 usa `rownum=1` (Id_Correlativo_Item).
- **SP 17-22 (sesiones educativas):** el marcador PSA/SSI/PSI está en la fila 2 del item C0009 (`I_ROWNUM_LAB=2`) y el número de sesión en la fila 1 (`valor_lab`), tal cual los `#PSA/#SSI/#PSI` de los SP.
- **Sexo:** los SP exigen "sexo is not null" en el nominal; las secciones 16_2/16_3 no lo exigen (sus SP tampoco).
- **Periodo:** los SP agrupan por `periodo` (yyyymm); en la web el usuario filtra Año/Mes y el reporte agrega dentro de ese ámbito.

## Rendimiento

- Sin índices la consulta recorre toda la tabla (minutos + HTTP 500 en hostings compartidos). Con `install_adolescente.php` pasa a segundos.
- La consulta trae solo las filas de los ~150 códigos de interés + las filas de los UPS 302304/301903 (una sola consulta unbuffered) y todo el matching se resuelve en PHP con índices por cita/código/ficha/UPS.
- Si el reporte excede 2,000,000 de filas devuelve un error pidiendo acotar filtros (evita HTTP 500).

## Exportar Plano (plano consolidado)

- El botón **Exportar Plano** usa el mismo reporte ya calculado (`adoCeldasExport()`, idéntico a *Exportar Excel*) y lo deposita en el plano horizontal `Adolescentes_Plano.xlsx` (misma mecánica que los planos de Zoonosis/ITS/Materno/NoTransmisibles):

  | Archivo | Contenido |
  |---------|-----------|
  | `uploads/Ubicacion_Filas_Adolescentes.xlsx` | Espejo del reporte: cada celda de datos trae el NUMERO DE DESTINO (ej: `C11` = 4). Trae 424 números únicos 4..427, secuenciales y sin duplicados. |
  | `Adolescentes_Plano.xlsx` | Destino: fila 12 (`A12:PK12`) = numeración de columnas 1..427; fila 13 = datos (`C13:PK13`). El número 4 ⇒ columna D ⇒ el dato de `C11` (12a-14a de IRA Complicadas) se escribe en `D13`. |

- Correspondencia espejo ⇔ motor: el espejo calza **fila por fila y columna por columna** con las coordenadas de `adoCeldasExport()` (offset 0, como NoTransmisibles/Zoonosis). Verificación automática: las 424 celdas de datos del motor (layouts STD/CTRL/CASOS, excluidas las 150 columnas *Total*) tienen su número en el espejo, **cobertura 100%**, sin colisiones ni números faltantes.
- Identificación del registro (fila 13): `B13` = Código RENAES, `C13` = nombre del EE.SS (o "TODOS LOS ESTABLECIMIENTOS DE LA LISTA"). La columna `A13` (Ubigeo) se deja vacía porque el catálogo `ZSPERENE` no trae ubigeo (mismo criterio que los planos de Zoonosis y NoTransmisibles).
- Periodo: la celda `B5` de la cabecera del plano ("MES:") se actualiza con el periodo exportado (ej: `2026 - OCTUBRE`, `2026 - ACUMULADO`, `TODOS LOS PERIODOS`).
- Secciones sin columna Total en el plano: los números 1-3 son las columnas de identificación (Ubigeo/RENAES/EE.SS) y el espejo solo numera 12a-14a y 15a-17a (los *Total* de la plantilla oficial no existen en el plano). El resto del plano se llena completo.
- Rendimiento: el mapeo espejo/plano se cachea en `cache/plano/` (JSON invalidado por mtime/size); si la carpeta no es escribible, el sistema sigue leyendo los xlsx directamente.
- Rango amplio: con token del asistente usa el resultado ya calculado; sin token solo acepta rangos angostos (nunca lanza una consulta fatal; muestra guía si el rango es amplio).
- Nombre del archivo: `Adolescentes_Plano_{anio}_{mes}_{renaes}.xlsx`.

### Diagnóstico de problemas del Plano

| Problema | Causa probable | Solución |
|----------|----------------|----------|
| "Plantilla del plano no encontrada" | Falta `uploads/Adolescentes_Plano.xlsx` | Copiar la plantilla del plano a `uploads/` |
| "Archivo de ubicacion no encontrado" | Falta `uploads/Ubicacion_Filas_Adolescentes.xlsx` | Copiar el espejo de ubicación a `uploads/` |
| "El plano quedaria vacio" | El espejo no corresponde a la plantilla actual | Verificar que el espejo es el oficial de Adolescente 2026 |
| "Rango demasiado amplio" | Plano sin token y filtros amplios | Generar primero el reporte (asistente) y exportar desde ahí, o acotar filtros |

## Generación por trozos (v1.1: hosting 512M / 60s)

El hosting gratuito (InfinityFree) fija `memory_limit=512M` y `max_execution_time=60s` por petición. Un rango amplio (p. ej. "todos los años, todos los EE.SS") puede implicar cientos de miles de filas y la consulta única muere con error fatal. El módulo v1.1 lo resuelve así:

| Rango elegido | Estrategia |
|---|---|
| Año + Mes (con o sin EE.SS), o Año + un EE.SS | **Consulta única directa** (volumen pequeño; como siempre) |
| Cualquier rango más amplio | **Asistente por trozos**: un mes por petición (subdividido por EE.SS si el mes supera ~400k filas), con barra de progreso |

- **Exactitud garantizada:** la suma de los trozos es idéntica a la consulta única. Todas las condiciones que cruzan filas (`citaTiene`/`citaSin` de los SP: #EDA_COMP, #PAI, #EVALUACION, PSA/SSI/PSI…) se resuelven dentro de una misma cita, y una cita vive completa en un único (Año, Mes, EE.SS), así que ningún trozo parte una cita. Verificado con pruebas automatizadas: partición por (año, mes), por cita y por HTTP end-to-end — casos, filas, grupos y celdas del Excel idénticos.
- **Casos borde cubiertos:** filas con `Anio` NULL o vacío, `Mes` NULL/0/13 (trozos de complemento); la partición es exacta y sin solapes.
- **Buffer en BD:** los parciales se acumulan como JSON en `ADOLESCENTE_RPT_CACHE` (token por usuario; limpieza automática > 24 h).
- **Auto-reparación:** si un paso agota memoria/tiempo, el servidor lo subdivide por establecimiento y el asistente reintenta; fallos de red se reintentan solos.
- **Reutilización:** una generación idéntica < 12 h se reusa (el segundo clic es instantáneo).
- **Exportar Excel:** con token usa el resultado ya calculado; sin token solo acepta rangos angostos (nunca lanza una consulta fatal; muestra guía si el rango es amplio).
- **Sin JavaScript:** la página no ejecuta la consulta amplia; muestra la advertencia y cómo acotar filtros.

### Endpoints AJAX del asistente (en `reporte_adolescente.php`)

| `?ajax=` | Rol |
|---|---|
| `plan` | decide modo `directo` / `trozos` / `cache` y guarda el plan en `ADOLESCENTE_RPT_CACHE` |
| `trozo` | ejecuta el trozo `i` (exige orden estricto; reintentos idempotentes) y lo suma al acumulador |
| `dividir` | subdivide el trozo en curso por EE.SS (auto-reparación) |
| `resultado` | devuelve la URL con token para renderizar el reporte terminado |

### Pruebas automatizadas incluidas (desarrollo)

- `test_ado_trozos.php` — equivalencia merge de N trozos == consulta única (particiones por año/mes y por cita) + render y celdas del export desde el JSON del buffer.
- `test_ado_wizard_sim.php` — simulación del flujo completo del asistente contra un PDO falso (plan, trozos, cache, división, complementos NULL/atípicos, con y sin `Mes_Int`).
- `test_ado_http_setup.sh/.php` + `test_ado_http_run.py` — prueba HTTP end-to-end real (servidor PHP embebido): plan → 36 trozos → resultado → página → export .xlsx → guardas anti-fatal.
