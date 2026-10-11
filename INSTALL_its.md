# Módulo ITS/VIH — IntelHIS

**Hoja de Monitoreón Mensual de la ESN Prevención y Control de ITS, VIH/SIDA y Hepatitis**

Reemplaza el flujo manual (SQL Server + 3 archivos .txt + Excel con conexión ODBC) por una solución web **data-driven**, igual que ESNI, Cancer, Materno, Zoonosis y No Transmisibles.

## Flujo anterior (4 pasos manuales)

1. SQL Server: ejecutar `01 Creacion tablas iniciales` (DimItsVih2022_*)
2. SQL Server: ejecutar `02 Creacion tablas consolidacion` (TRAMA_BASE_ITS_VIH_2022_*)
3. SQL Server: ejecutar `03 Creacion de Procedimientos` (15 SP usp_TRAMA_BASE_ITS_VIH_2022_*)
4. Excel: abrir `Reporte_Actividades_ITS.xlsx` y refrescar la conexión ODBC

## Flujo nuevo (1 click)

- Entrar a IntelHIS → **Reportes Operacionales → ITS → Generar Reporte**
- (Opcional) **Exportar Excel** → llena la plantilla oficial `uploads/Reporte_Actividades_ITS.xlsx` con los mismos datos y layout.

## Archivos del módulo

| Archivo | Rol |
|---------|-----|
| `reporte_its.php` | Página del reporte (1 click): filtros año/mes/EE.SS + 16 bloques + panel de auditoría |
| `its_export.php` | Exporta a Excel con el layout de la plantilla oficial |
| `install_its.php` | Asistente de índices (ejecutar UNA vez como admin; eliminar después) |
| `includes/its_data.php` | Motor de reglas data-driven (15 SP adaptados + dimensiones) |
| `includes/its_render.php` | Render web + mapa de celdas del export |
| `uploads/Reporte_Actividades_ITS.xlsx` | Plantilla oficial (copiar ahí para el export) |
| `INSTALL_its.md` | Este archivo |

## Estructura del reporte (16 bloques = 15 procedimientos)

Los números de fila corresponden a la hoja `Plantilla` del Excel oficial:

| # | Bloque | SP | Filas Excel | Filas | Layout |
|---|--------|----|------------|-------|--------|
| 1 | Población General: ITS | `usp_..._01_POBLACION_GENERAL` | 13-25 | 13 dx | FMC3: F/M × 5 etapas × DIAG/TRAT/CONT + 6 totales (AF-AK) |
| 2 | Hepatitis B | `usp_..._02_HEPATITIS_B` | 29-36 | 8 | FM5: F B-F, M G-K, L/M/N totales |
| 2A | Hepatitis C | `usp_..._02A_HEPATITIS_C` | 40-45 | 6 | FM5 |
| 3 | Población General: Tamizaje VIH | `usp_..._03_TAMIZAJE_VIH` | 49-57 | 9 | FM5 |
| 4 | Exposición a VIH / Hepatitis B | `usp_..._04_EXPOSICION_HEPATITIS_B` | 61-69 | 9 | FM5 |
| 5 | Exposición (Violencia Sexual) | `usp_..._05_..._VIOLENCIA` | 73-77 | 5 | FM5 |
| 6 | PVV: Atención Integral | `usp_..._06_PVVIH_ATENCION_INTEGRAL` | 81-92 | 12 | FM5 |
| 7a | Gestantes por grupo etario | `usp_..._07_1_GESTANTES_RN` | 95-105 | 11 | GES4: etapas 12-17..60+ + Total |
| 7b | Gestantes por trimestre | `usp_..._07_2_GESTANTES_RN` | 108-118 | 11 | TRI3: 1ER/2DO/3ER TRIM + Total |
| 7A | Recién Nacidos | `usp_..._07_3_GESTANTES_RN` | 121-130 | 10 | RN1: solo Total |
| 8 | Población Clave HSH/Trans/TS | `usp_..._08_POBLACION_CLAVE` | 134-145 | 12 | PC5: 5 grupos × 3 etapas + 5 totales |
| 9a | PC: ITS (Dx y Tratados) | `usp_..._09_1_POBLACION_CLAVE_ITS` | 149-159 | 11 | PC5C: DIAGNÓSTICOS B-P + TRATADOS Q-U |
| 9b | PC: ITS (Contactos) | `usp_..._09_2_POBLACION_CLAVE_ITS` | 162-172 | 11 | CC2: CONTACTOS / CONTACTOS TRATADOS |
| 10 | Brigadas MCC | `usp_..._10_..._BRIGADAS_MCC` | 177-184 | 8 | BRIG10: OFERTA COMUNITARIA (B-U) + OFERTA MÓVIL (V-AO), 5 grupos × 4 etapas |
| 11 | Brigadas BMA | `usp_..._11_..._BRIGADAS_BMA` | 188-195 | 8 | FM5T: F/M × 5 etapas + F/M |
| 12 | Telemedicina | `usp_..._12_TELEMEDICINA` | 199-203 | 5 | FM5T |

## Dimensiones replicadas (archivo 01 → PHP)

| Dim SQL Server | Función PHP | Valores |
|----------------|-------------|---------|
| DimItsVih2022_Etapa | `itsEtapa($f, 'e5')` | 1=01d-11a, 2=12a-17a, 3=18a-29a, 4=30a-59a, 5=60a+ |
| DimItsVih2022_Etapa07 (e4) | `itsEtapa($f, 'e4')` | 1=12a-17a, 2=18a-29a, 3=30a-59a, 4=60a+ |
| DimItsVih2022_Etapa08 (e3) | `itsEtapa($f, 'e3')` | 1=18a-29a, 2=30a-59a, 3=60a+ |
| DimItsVih2022_Trimestre07_2 | `itsTrimestreDeFila($f)` | 1=1ER TRIM, 2=2DO TRIM, 3=3ER TRIM |
| DimItsVih2022_Grupo08 | `itsGrupoDeCita($ctx, $f)` | 1=HSH, 2=TRANS, 3=TS-FEMENINO, 4=TS-TRANS, 5=TS-H |
| DimItsVih2022_Brigada10 | `itsBrigadaDeFila($f)` | 1=OFERTA MOVIL (BMU), 2=OFERTA COMUNITARIA (MCC) |
| Condicion01 / 09_1 / 09_2 | eje `cond` de las secciones 1/9a/9b | 1=DIAGNOSTICO(S)/CONTACTOS, 2=TRATADO(S), 3=CONTACTO |
| Diagnostico01..12 | filas `label` de cada sección | textos exactos de las dimensiones |

El grupo de población clave se determina por la fila de la cita con `valor_lab` en (TS, HSH, TTS, TRA, HTS) combinado con el sexo de la fila contada, replicando el CASE Grupo del T-SQL (HSH+M→1, TRA+M→2, TS+F→3, TTS+M→4, HTS+M→5).

## Reglas de conteo

| Regla | Equivalente T-SQL | Dónde |
|-------|-------------------|-------|
| `filas` | `count(*) Casos` | todas las secciones |
| `citas` | `count(*)` sobre la fila `rownum=1` de cada cita | secciones 10/11, filas ATENDIDOS y ATENCIONES (se implementa como count distinct Id_Cita) |

## Mapeo de columnas (TRAMAHIS_DTSG → tabla consolidada MySQL)

| SQL Server | MySQL | Notas |
|------------|-------|-------|
| id_cita | Id_Cita | |
| id_tipitem | Tipo_Diagnostico | 'D','R' |
| cod_item | Codigo_Item | |
| cod_item_f | (calculado) prefijo CIE 3 letras | A51x→A51 |
| valor_lab | Valor_Lab | 'NULL' en el DSL = IS NULL |
| I_ROWNUM_LAB | Id_Correlativo_Lab | |
| id_genero | Id_Genero | F/M |
| edad_reg / id_tipedad_reg | Edad_Reg / Tipo_Edad | D/M/A |
| renaes | Codigo_Unico | |
| id_etnia | Id_Etnia | 56-60 = población indígena |
| nom_lote | Lote | VHM/BMU/MCC/BMA (brigadas) |
| id_tipcond_serv | Id_Condicion_Servicio | N=nueva, R=reprogramada |
| rownum | (sin columna) | regla 'citas' = count distinct Id_Cita |
| periodo / aniomes | Anio + Mes | filtros web |

## Instalación

1. **Subir los archivos** del módulo al hosting (misma carpeta que `index.php`):
   - `reporte_its.php`, `its_export.php`, `install_its.php`
   - `includes/its_data.php`, `includes/its_render.php`
   - `uploads/Reporte_Actividades_ITS.xlsx` (plantilla oficial; solo la necesita el export)
2. **Índices (una sola vez):** abrir `install_its.php` como administrador y esperar el resultado. Crea `idx_its_anio`, `idx_its_codigo_item`, `idx_its_codigo_unico`, `idx_its_lote` y `idx_its_anio_mesint` (si existe `Mes_Int`). Tras ver el resultado, **eliminar `install_its.php`**.
3. (Opcional) **Verificar:** Reportes Operacionales → ITS → Generar Reporte con un mes acotado.

## Uso

1. Entrar a IntelHIS → **Reportes Operacionales** → tarjeta **ITS** (destacada).
2. Seleccionar Año / Mes / Establecimiento (opcional: grupo del reporte, mostrar filas en 0, modo auditoría SQL).
3. Pulsar **Generar Reporte** → los 16 bloques se calculan en ~1-3 s con índices.
4. Pulsar **Exportar Excel** → descarga `Reporte_Actividades_ITS_<año>[_<mes>][_<renaes>].xlsx` con el diseño oficial (cabecera K6=AÑO, K8=MES, C8=ESTABLECIMIENTO, I6=IPRESS).

## Mantenimiento (auditoría contra el archivo 03)

- Activar **Ver SQL (auditoría)** en el formulario: cada bloque muestra la condición SQL equivalente (los WHERE/CASE del SP adaptados al DSL).
- Si alguna regla difiere del archivo `03 Creacion de Procedimientos`, editar `includes/its_data.php → itsSecciones()` (todo el módulo se ajusta en ese único archivo).
- Para cambiar los textos de las filas, son las `label` de cada sección (dimensiones DiagnosticoXX).
- Para agregar un código o valor_lab nuevo, ajustar la condición correspondiente y agregar el código a `itsCodigosInteres()` (para que la consulta lo traiga).

## Notas de adaptación (desviaciones documentadas)

- `aniomes>='202210'/'202301'` del T-SQL se convierte en los filtros web Año/Mes (el reporte ya acota el periodo).
- El Grupo de población clave se determina por cualquier fila de la cita con `valor_lab` en (TS,HSH,TTS,TRA,HTS); el SP lo hace por tabla temporal específica (#TEMP/#ATENCION/#DX_VIH) — la diferencia es despreciable.
- SP 07_3 dx8/dx10: el CASE exige `id_tipedad_reg='D'` (la rama M+edad=1 del OR interno nunca aplica por el AND externo) — se replica D + edad>=2.
- SP 01 dx12: unión de Temporal1 (con exclusión #POBLACION_CLAVE y R1) y Temporal2 (sin exclusión y sin R1) — se simplifica a la unión fiel.
- Sexo: los SP exigen "sexo is not null" y agrupan M/F; las filas con sexo distinto de M/F quedan fuera de las secciones con eje sexo.
- `rownum` no existe como columna en la tabla consolidada; las filas ATENDIDOS/ATENCIONES de las secciones 10/11 se cuentan como citas distintas (equivalente al rownum=1 del T-SQL).

## Rendimiento

- Sin índices la consulta recorre toda la tabla (minutos + HTTP 500 en hostings compartidos). Con `install_its.php` pasa a segundos.
- La consulta trae solo las filas de los ~85 códigos de interés (una sola consulta unbuffered) y todo el matching se resuelve en PHP con índices por cita/código/valor_lab/lote.
- Si el reporte excede 2,000,000 de filas devuelve un error pidiendo acotar filtros (evita HTTP 500).
