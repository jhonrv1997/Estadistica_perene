# Modulo ESNI - Reporte Operacional de Inmunizaciones (Data-Driven)

Extension del Sistema HIS para reemplazar el flujo manual actual:

```
ANTES (4 pasos manuales, SQL Server + Excel):
  1. SQL Server: ejecutar 01_DimESNI.txt
  2. SQL Server: ejecutar 02_TRAMA_BASE_ESNI_RPT_NOMINAL_CONSOLIDADO.txt
  3. SQL Server: ejecutar 03_StoredProcedure_TRAMA_BASE.txt
  4. SQL Server: ejecutar 04_USP_TRAMA_BASE_ESNI_2019_RPT.txt
  5. Excel: abrir ReporteActividadesEsni2019.xlsx y refrescar conexion ODBC

AHORA (1 click desde la web):
  - Entrar a IntelHIS -> Reportes Operacionales -> ESNI
  - Aplicar filtros (anio, mes, EE.SS., departamento, profesional)
  - Click en "Generar Reporte" -> se muestran las secciones del reporte operacional
  - Click en "Exportar Excel" -> descarga el .xlsx con el mismo layout que el oficial
```

## Ventajas

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| Motor BD | SQL Server (licencia, instalacion) | MySQL (ya existe en el sistema) |
| Procedimientos | 8 stored procedures con CASE statements hard-codeadas | Reglas en tablas MySQL (`ESNI_REGLA`) |
| Modificar vacuna | Editar SQL T-SQL + recompilar SP | Editar 1 fila desde la web (`esni_config.php`) |
| Agregar vacuna | Editar 4 archivos .txt + Excel | Insertar 1 registro en `ESNI_VACUNA` y crear reglas |
| Generar reporte | Ejecutar 4 scripts + abrir Excel + refrescar | 1 click en `reporte_esni.php` |
| Filtros | Editar Excel (conexion ODBC) | Selectores en la web |
| Exportar | Excel con conexion externa | Boton "Exportar Excel" en la web (llena `uploads/Plantilla.xlsx`) |

## Archivos nuevos

| Archivo | Funcion |
|---------|---------|
| `Database/if0_42181393_his.sql` | Script MySQL con el dump completo del esquema HIS + tablas ESNI_* con datos semilla (incluye 22 vacunas, 10 dosis, 43 grupos de edad, 19 secciones, 227 lineas y 255 reglas) |
| `install_esni.php` | Asistente web para ejecutar el script SQL (`Database/install_esni.sql`) - requiere autenticacion de admin |
| `includes/esni_data.php` | Motor de reglas data-driven (helper functions + ejecucion del reporte) |
| `includes/ExcelTemplateFiller.php` | Clase PHP que carga `uploads/Plantilla.xlsx`, reemplaza celdas con `$cellValues` y entrega el `.xlsx` final al navegador (usa `ZipArchive`, sin PhpSpreadsheet) |
| `includes/ExcelWriter.php` | Generador de Excel alternativo para reportes genericos |
| `reporte_esni.php` | Pagina del reporte operacional ESNI (renderiza secciones por layout: lista, matriz_dosis, matriz_edad, matriz_sexo, total_uno) |
| `esni_config.php` | Pagina admin para gestionar vacunas, dosis, grupos de edad, secciones, lineas y reglas |
| `esni_export.php` | Exportar el reporte a Excel (.xlsx) llenando la plantilla oficial `uploads/Plantilla.xlsx` con el mapeo celda-por-celda de cada seccion |
| `uploads/Plantilla.xlsx` | Plantilla Excel oficial con cabeceras, formato y formulas pre-cargadas; el motor solo reemplaza celdas de datos (no toca la cabecera/formato) |

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `reporte_operacionales.php` | Tarjeta ESNI ahora enlaza a `reporte_esni.php` (con badge NUEVO) |
| `includes/header.php` | Menu "Rep. Operac. > ESNI" enlaza a `reporte_esni.php`; menu Admin incluye "Configurar ESNI" |
| `assets/css/style.css` | Estilos para secciones ESNI, tarjetas destacadas, etc. |

## Instalacion (4 pasos)

### Paso 1: Cargar el script SQL en la BD

Tiene 2 opciones:

**Opcion A - Asistente web (recomendado):**

> **Nota previa:** el asistente web `install_esni.php` busca el archivo `Database/install_esni.sql`. Si su proyecto solo tiene `Database/if0_42181393_his.sql` (dump completo de InfinityFree), use la Opcion B. En un futuro, si se generan ambos scripts, podra usar cualquiera de las dos opciones.

1. Copie los nuevos archivos a su servidor (manteniendo la estructura).
2. Abra en el navegador: `https://sudominio.com/install_esni.php` (debe iniciar sesion como admin).
3. El asistente ejecutara el script SQL y verificara que las tablas se creen.
4. Elimine `install_esni.php` por seguridad.

**Opcion B - Manual via phpMyAdmin o CLI (dump completo):**

```bash
# Carga el esquema completo + datos ESNI sembrados en una sola pasada:
mysql -u usuario -p su_base_de_datos < Database/if0_42181393_his.sql
```

Si solo tiene cambios incrementales respecto a un esquema HIS pre-existente, tambien puede ejecutar el dump con `--force` (phpMyAdmin: marca "Continuar en caso de error" en la pestana Importar).

### Paso 2: Subir la plantilla Excel oficial

Coloque el archivo `Plantilla.xlsx` (la plantilla oficial MINSA con cabeceras y formato) en la carpeta `uploads/`. La ruta esperada por el motor es:

```
uploads/Plantilla.xlsx
```

Si falta, al exportar el Excel se mostrara un error indicando que se debe subir el archivo por FTP o el administrador de archivos del hosting.

> **Requisitos del servidor:** la extension `zip` de PHP debe estar habilitada (clase `ZipArchive`). En InfinityFree: Panel de control -> PHP Configuration -> marcar "zip". En otros hostings: editar `php.ini` y agregar `extension=zip`.

### Paso 3: Verificar la instalacion

Entre a IntelHIS y navegue a:
- **Reportes Operacionales > ESNI** (debe mostrar la pagina con stats de configuracion).
- Debe ver 7 cards con las estadisticas sembradas:

| Tabla | Filas | Contenido |
|-------|-------|-----------|
| `ESNI_VACUNA` | 22 | BCG, HVB, IPV, APO, PENTA, RXN_*, ROTA, NEUMO, INF, SPR, SR, VAR, AMA, HEP_A, DPT, DT, TDAP, VPH, BCG_TB, PENTA_RE, IPV_RE |
| `ESNI_DOSIS` | 10 | D1, D2, D3, D4, DU, REF1, REF2, REF3, TOT, DA |
| `ESNI_GRUPO_EDAD` | 43 | 24H, 28D, 01_11M, 02_04A, 05A, 05_11A, 12_17A, 18_29A, 30_59A, 60A_MAS, GEST, etc. |
| `ESNI_SECCION_REPORTE` | 19 | A, B, C, D, E1, E2, F, F2, G, H, I, J, K, L, N, O, P, Q, R, T (mas seccion VPH con codigo N) |
| `ESNI_LINEA_REPORTE` | 227 | lineas por seccion (vacuna+dosis+edad+sexo) |
| `ESNI_REGLA` | 255 | reglas cod_item+valor_lab -> linea (secciones A, B, C, D, E1, E2, F, F2, G, H, J completas) |
| `ESNI_PARAMETRO` | 17 | mapeo de columnas de la tabla origen |

### Paso 4: Ajustar parametros (opcional)

Si su tabla consolidada MySQL tiene nombres de columnas diferentes a los del esquema estandar HIS MINSA, vaya a:
- **Admin > Configurar ESNI > Parametros**

Alli puede ajustar:
- `tabla_origen`: nombre de la tabla consolidada MySQL (default: `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`)
- `columna_cod_item`, `columna_valor_lab`, `columna_anio`, `columna_mes`, `columna_id_paciente`, `columna_id_cita`, `columna_id_gruporiesgo`, `columna_id_etnia`, `columna_rownnum_lab`, etc.

El sistema detecta automaticamente variantes de nombres (case-insensitive). Si una columna no existe, el motor es tolerante y la trata como NULL.

## Tablas creadas (esquema data-driven)

```
ESNI_VACUNA          (22 filas) - BCG, HVB, IPV, APO, PENTA, RXN_DTP/HVB/HIB, ROTA, NEUMO, INF, SPR, SR, VAR, AMA, HEP_A, DPT, DT, TDAP, VPH, BCG_TB, PENTA_RE, IPV_RE
ESNI_GRUPO_EDAD      (43 filas) - 24H, 28D, 01_11M, 02_04M, 06_07M, 05A, 05_09A, 05_11A, 10_11A, 12_17A, 18_29A, 30_49A, 30_59A, 50_59A, 60A_MAS, 0_11A, GEST, RIESGO, etc.
ESNI_DOSIS           (10 filas) - D1, D2, D3, D4, DU, REF1, REF2, REF3, TOT, DA
ESNI_SECCION_REPORTE (19 filas) - A, B, C, D, E1, E2, F, F2, G, H, I, J, K, L, N, O, P, Q, R, T
ESNI_LINEA_REPORTE   (227 filas) - lineas por seccion (vacuna+dosis+edad+sexo)
ESNI_REGLA          (255 filas) - reglas cod_item+valor_lab -> linea (secciones A, B, C, D, E1, E2, F, F2, G, H, J completas; secciones I, K, L, N, O, P, Q, R, T pendientes)
ESNI_PARAMETRO       (17 filas) - mapeo de columnas de la tabla origen
```

## Estructura del reporte (secciones oficiales MINSA)

El reporte muestra las 19 secciones del esquema ESNI sembrado, con los siguientes layouts:

| Codigo | Titulo | Layout | # Lineas | Estado en export Excel |
|---------|--------|--------|----------|--------------------------|
| **A** | MENORES DE 01 AÑO | `lista` | 26 | Implementado (celdas G7..J39) |
| **B** | DE 01 AÑO | `lista` | 15 | Implementado (celdas G28..J41) |
| **C** | DE 02 AÑOS | `lista` | 19 | Implementado (celdas F50..I66) |
| **D** | DE 03 AÑOS | `lista` | 12 | Implementado (celdas J50..M66) |
| **E1** | DE 04 AÑOS | `lista` | 13 | Implementado (celdas F71..I86) |
| **E2** | DE 05 - 07 AÑOS | `lista` | 3 | Implementado (celdas J71..M86) |
| **F** | dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 AÑOS | `matriz_dosis` | 21 | Implementado (celdas M8..P14) |
| **F2** | GESTANTES (dT) | `matriz_dosis` | 15 | Implementado (celdas M19..P23) |
| **G** | dT ADULTO: VARONES EN RIESGO | `matriz_dosis` | 18 | Implementado (celdas M28..P33) |
| **H** | INFLUENZA ESTACIONAL EN OTROS GRUPOS | `lista` | 18 | Implementado (celdas E93..E120) |
| **I** | SARAMPION - RUBEOLA | `total_uno` | 0 | Pendiente de lineas/reglas |
| **J** | HEPATITIS B EN POBLACION DE 05 A 59 AÑOS | `matriz_dosis` | 18 | Implementado (celdas O92..R100) |
| **K** | ANTIAMARILICA | `total_uno` | 5 | Pendiente |
| **L** | SOLO GESTANTES (dtpa) | `total_uno` | 3 | Pendiente |
| **N** | VACUNA VPH | `matriz_sexo` | 12 | Pendiente |
| **O** | NEUMOCOCO | `total_uno` | 12 | Pendiente |
| **P** | DT - DOSIS ADICIONALES | `total_uno` | 5 | Pendiente |
| **Q** | VARICELA | `total_uno` | 5 | Pendiente |
| **R** | HEPATITIS A | `total_uno` | 5 | Pendiente |
| **T** | SPR - SARAMPION | `total_uno` | 3 | Pendiente |

### Mapeo de celdas en el Excel exportado (`esni_export.php`)

El archivo `esni_export.php` llena la plantilla `uploads/Plantilla.xlsx` celda por celda. La estructura del mapeo es:

```
ENCABEZADO:
  C2  = Establecimiento
  H2  = Mes (nombre, ej: "Enero")
  L2  = Anio

SECCION A (filas 7-22,  Cols G/H/I/J):
  BCG          -> G7..J9     (dosis unica -> G=J=Casos)
  HEPATITIS B  -> G10..J11   (dosis unica -> G=J=Casos)
  IPV          -> G13..J13   (3 dosis + total: J=G+H+I)
  PENTAVALENTE -> G15..J15   (3 dosis + total)
  ROTAVIRUS    -> G20..J20   (2 dosis + total)
  NEUMOCOCO    -> G21..J21   (2 dosis + total)
  INFLUENZA    -> G22..J22   (2 dosis + total)

SECCION B (filas 28-41, Cols G/H/I/J):
  NEUMOCOCO 3ra (I28/J28), SPR 1ra (G29/J29), INFLUENZA DU (G30/J30),
  VARICELA 1ra (G31/J31), ANTIAMARILICA DU (G33/J33), HEPATITIS A DU (G34/J34),
  SPR 2da (H35/J35), REF. DPT 1ra (G36/J36), REF. IPV (G37/J37),
  REF. PENTAVALENTE (G38/J38), No vacunado IPV (I39/J39),
  No vacunado PENTAVALENTE 2da/3ra (H41+I41=J41)

SECCION C (filas 50-66, Cols F/G/H/I):
  INFLUENZA comorb/sin comorb (F50/F51=I50/I51),
  NEUMOCOCO comorb (F52/I52),
  VACUNACION NO OPORTUNA NEUMOCOCO D1/D2/D3 (F53+G53+H53=I53),
  ANTIAMARILICA (F55/I55),
  VACUNACION NO OPORTUNA SPR D1/D2 (F62+G62=I62),
  REF. IPV 1ra (F65/I65), REF. PENTAVALENTE 1ra (F66/I66)

SECCION D (filas 50-66, Cols J/K/L/M): mismo patron que C pero desplazado a la derecha
SECCION E1 (filas 71-86, Cols F/G/H/I): mismo patron que C
SECCION E2 (filas 71-86, Cols J/K/L/M): subset de D (solo Pentavalente y REF DPT)

SECCION F (filas 8-14, Cols M/N/O/P):
  M=D1, N=D2, O=D3, P=Total (M+N+O)
  5 a 9 anos (fila 8), 10 a 11 (fila 9), 12 a 17 (fila 10),
  18 a 29 (fila 11), 30 a 49 (fila 12), 50 a 59 (fila 13), 60+ (fila 14)

SECCION F2 (filas 19-23, Cols M/N/O/P): 10-11, 12-17, 18-29, 30-49, 50-59

SECCION G (filas 28-33, Cols M/N/O/P): 05-09, 10-11, 12-17, 18-29, 30-59, 60+

SECCION H (filas 93-120, Col E): un unico valor por fila
  E93..E97  = CON COMORBILIDAD (05_11A, 12_17A, 18_29A, 30_49A, 50_59A)
  E98..E102 = SIN COMORBILIDAD (mismos rangos)
  E103      = MAYORES DE 60A
  E104      = GESTANTES
  E105      = PUERPERAS
  E106      = PERSONAL DE SALUD
  E112      = ESTUDIANTES
  E117      = COMUNIDADES NATIVAS
  E119      = PERSONA CON DISCAPACIDAD
  E120      = OTROS

SECCION J (filas 92-100, Cols O/P/Q/R):
  O=D1, P=D2, Q=D3, R=Total (O+P+Q)
  05 a 11 años        (fila 92)
  12 a 17 años        (fila 93)
  18 a 29 años        (fila 94)
  30 a 59 años        (fila 95)
  Personal de Salud   (fila 97)
  Gestantes           (fila 100)

  Nota: las 3 lineas (D1/D2/D3) de cada grupo comparten la misma
  etiqueta en ESNI_LINEA_REPORTE; el indexado en el export se hace con
  la clave compuesta "etiqueta|dosis_codigo" usando el helper
  esniGetCasosJDosis(). El filtro por valor_lab en la misma Id_cita
  (marcadores 'G' para gestantes, 'ST' para personal de salud) permite
  distinguir lineas especializadas de las lineas de rango etario.
```

## Agregar una nueva vacuna (ejemplo)

Supongamos que MINSA agrega una vacuna nueva "DENGUE" con cod_item `91000` y dosis `1`/`2`.

### Opcion A: Via web (recomendado)

1. Vaya a **Admin > Configurar ESNI > Vacunas > Nueva Vacuna**.
   - Codigo: `DENGUE`
   - Nombre: `Dengue`
   - Color: elija uno
   - Guardar.

2. Vaya a **Grupos Edad** y verifique que exista el grupo de edad objetivo (ej: `09_49A` para adultos). Si no existe, creelo.

3. Vaya a **Secciones** y verifique/create la seccion donde ira (puede ser una nueva seccion `Q`).

4. Vaya a **Lineas** y agregue las lineas:
   - "DENGUE - 1RA DOSIS" -> seccion Q, vacuna DENGUE, dosis D1, grupo edad 09_49A
   - "DENGUE - 2DA DOSIS" -> seccion Q, vacuna DENGUE, dosis D2, grupo edad 09_49A

5. Vaya a **Reglas** y agregue las reglas de mapeo:
   - Linea: "DENGUE - 1RA DOSIS" | cod_item: `91000` | valor_lab: `1`
   - Linea: "DENGUE - 1RA DOSIS" | cod_item: `91000` | valor_lab: `01`
   - Linea: "DENGUE - 1RA DOSIS" | cod_item: `91000` | valor_lab: `D1`
   - (igual para 2da dosis con valor_lab `2`, `02`, `D2`)

Listo. El reporte `reporte_esni.php` ya incluira automaticamente las dosis de la nueva vacuna.

### Opcion B: Via SQL directo

```sql
-- 1) Crear vacuna
INSERT INTO ESNI_VACUNA (codigo, nombre, descripcion, color) VALUES ('DENGUE', 'Dengue', 'Vacuna contra Dengue', '#ff6b6b');

-- 2) Crear lineas (asumiendo seccion Q id=12)
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
VALUES (12, 1, 'DENGUE - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DENGUE'), 1, NULL, 'A');

-- 3) Crear reglas
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='DENGUE - 1RA DOSIS' AND id_seccion=12), '91000', '1'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='DENGUE - 1RA DOSIS' AND id_seccion=12), '91000', '01'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='DENGUE - 1RA DOSIS' AND id_seccion=12), '91000', 'D1');
```

> **Para reflejar la nueva seccion en el export Excel:** debe editar `esni_export.php` y agregar el indexado de la seccion (patron: `foreach ($reporte['secciones'] as $sec) { if (strcasecmp($sec['codigo'], 'X') === 0) ... }`) y las celdas destino en el array `$cellValues`. Ver la seccion "Mapeo de celdas en el Excel exportado" mas arriba.

## Reglas sembradas

El script SQL inicial incluye **255 reglas** que cubren completamente las secciones **A, B, C, D, E1, E2, F, F2, G, H y J** del reporte operacional, equivalentes a los CASE statements del stored procedure `usp_TRAMA_BASE_ESNI_RPT_01_REPORTE_A_2019` y secciones posteriores.

Las secciones pendientes (I, K, L, N, O, P, Q, R, T) ya tienen sus lineas creadas pero las reglas deben agregarse via `esni_config.php` siguiendo el patron de las secciones ya implementadas. El equipo del establecimiento puede completarlas en 1-2 horas usando el stored procedure original como referencia.

## Motor de reglas (como funciona)

El motor en `includes/esni_data.php` funciona en 5 etapas:

1. **Cargar reglas activas** desde `ESNI_REGLA` (join con `ESNI_LINEA_REPORTE` y `ESNI_GRUPO_EDAD`). Si la migracion `migration_seccion_j_covid.sql` ya se ejecuto, las columnas `requiere_valor_lab_cita` y `excluye_valor_lab_cita` estaran disponibles para distinguir Personal de Salud (ST) y Gestantes (G) en la seccion J.

2. **Construir 1 sola consulta SQL** contra la tabla origen trayendo solo filas con los `cod_item` relevantes, aplicando los filtros comunes (anio, mes, EE.SS., etc.) y la deduplicacion (`I_ROWNUM_LAB = 1` si la columna existe).

3. **Construir conjuntos auxiliares** (1 sola consulta cada uno) para:
   - **Pacientes con comorbilidad**: `cod_item = 9999` en cualquier registro del mismo paciente.
   - **Citas con marcadores valor_lab**: `G` (gestante), `ST` (personal de salud), etc., para evaluar reglas `requiere_valor_lab_cita` / `excluye_valor_lab_cita` de la seccion J.

4. **Para cada fila HIS**, evaluar las reglas del `cod_item` correspondiente en orden:
   - Si `valor_lab` encaja (NULL = cualquier valor; fallback a `DU` si la fila no trae valor_lab)
   - Y el sexo encaja (M/F/A)
   - Y el rango `aniomes` encaja (si esta definido)
   - Y el grupo de edad encaja (filtrado numerico por `Edad_Reg` + `Tipo_Edad` contra `ESNI_GRUPO_EDAD.edad_min/max/tipo_edad`)
   - Y la condicion de riesgo encaja (`requiere_riesgo` / `excluye_riesgo`)
   - Y la comorbilidad encaja (`requiere_comorbilidad` / `excluye_comorbilidad`)
   - Y el marcador `valor_lab` en la cita encaja (`requiere_valor_lab_cita` / `excluye_valor_lab_cita`)
   - Y la etnia no esta excluida (`excluye_etnia` con lista de Id_Etnia)
   - -> Contar la fila en la primera regla que encaje.

5. **Conteo dual por-seccion**: una misma fila HIS puede contar simultaneamente en una linea "normal" (grupo etareo) y una linea "especializada" (Personal de Salud, Gestantes, Comunidades Nativas, etc.) dentro de la MISMA seccion, ademas de poder volver a contar en otras secciones (R, Q, etc.) que consolidan una vacuna por rango etario con fines informativos. Esto permite que las secciones no se "roben" dosis entre si.

## Limitaciones / notas

- **Compatibilidad con valor_lab**: el motor trabaja con los valores `valor_lab` que existan en la tabla consolidada MySQL. Si la tabla no tiene esta columna, el motor cuenta TODAS las dosis del cod_item. Para distinguir dosis, la tabla origen debe incluir la columna `Valor_Lab` o similar. El motor normaliza el valor (UPPER + TRIM) y si la fila trae `valor_lab=NULL` pero la regla espera `DU`, se aplica como fallback automatico.
- **Deduplicacion**: si la columna `I_ROWNUM_LAB` existe, el motor solo considera la fila 1 (deduplicacion). Si no existe, considera todas las filas.
- **Filtro por grupo de edad**: el filtrado se hace EXCLUSIVAMENTE en modo numerico, usando `Edad_Reg` + `Tipo_Edad` de la tabla HIS contra el rango configurado en `ESNI_GRUPO_EDAD`. La columna textual `Grupo_Edad` de la trama HIS se IGNORA para el filtrado (es solo informativa y puede no coincidir con el esquema ESNI).
- **Filtro por valor_lab en cita**: requiere que la tabla origen tenga `Id_Cita` y `Valor_Lab`. Si `Id_Cita` es NULL, la regla `requiere_valor_lab_cita='X'` NO cuenta la fila (no se puede confirmar el marcador), mientras que `excluye_valor_lab_cita='X'` SI la cuenta (tolerante: se asume sin marcador).
- **Performance**: el motor agrupa los `cod_item` y hace 1 sola consulta con `IN (...)`, optimizando para tablas grandes. Para tablas con millones de filas, considere agregar indices en `cod_item`, `anio_mes` y `Codigo_Unico`.
- **Plantilla Excel**: el export requiere `uploads/Plantilla.xlsx` con el layout oficial MINSA. Si se actualiza la plantilla oficial (cambio de filas/celdas), debe revisar el mapeo en `esni_export.php` y ajustar las celdas destino en el array `$cellValues`.

## Soporte

Para reportar problemas o sugerir mejoras, abra un issue en:
https://github.com/jhonrv1997/Estadistica_perene/issues
