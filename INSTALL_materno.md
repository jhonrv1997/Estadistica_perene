# Modulo MATERNO (v4.0) - Reporte Operacional de Salud Sexual y Reproductiva

Modulo data-driven de IntelHIS que **reemplaza el flujo manual del Modulo de Materno** por un flujo web de 1 click.

## Flujo anterior (4 pasos manuales)

1. SQL Server: ejecutar `01 Creacion tablas iniciales` (tablas dimensionales `DimMaterno2023_*`)
2. SQL Server: ejecutar `02 Creacion tablas consolidacion` (`TRAMA_BASE_MATERNO_2023_RPT_XX_CONSOLIDADO / _NOMINAL`)
3. SQL Server: ejecutar `03 Creacion de Procedimientos` (procedimientos `RPT_01_APN_REENFOCADA` ... `RPT_10_CONSEJERIA`)
4. Excel: abrir `Reporte_Actividades_Materno.xlsx` y refrescar la conexion ODBC

## Flujo nuevo (1 click)

- Entrar a IntelHIS -> **Reportes Operacionales -> MATERNO -> Generar Reporte**
- (Opcional) **Exportar Excel**: llena la plantilla oficial
  `uploads/Reporte_Actividades_Materno.xlsx` con el mismo layout (10 secciones I-X).

## Archivos del modulo

| Archivo | Rol |
|---------|-----|
| `includes/materno_data.php` | Motor de reglas: definicion de las 10 secciones (I-X, 12 bloques) con las categorias EXACTAS de las dims del archivo 01, DSL de predicados (espeja los WHERE del T-SQL), reglas de conteo y ejecucion contra `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` |
| `includes/materno_render.php` | Funciones de render web (layout tipo Excel) y mapa de celdas para el export (`maternoCeldasExport`) |
| `reporte_materno.php` | Pagina del reporte (1 click): filtros anio/mes/EE.SS., proteccion anti HTTP 500, panel "Ver condiciones SQL" (auditoria) |
| `materno_export.php` | Exportacion a la plantilla oficial .xlsx (preserva formato: merges, estilos) |
| `install_materno.php` | Asistente de indices de la tabla consolidada (eliminar tras usar) |
| `uploads/Reporte_Actividades_Materno.xlsx` | Plantilla oficial (base del export) |

## Estructura del reporte (10 secciones = 12 bloques)

| # | Codigo | Seccion | Layout |
|---|--------|---------|--------|
| I | `RPT01_APN_REENFOCADA` | Atencion Prenatal Reenfocada | 25 columnas x 4 grupos etareos + TOTAL (Gestante, PAP, bateria, VBG, eco, tamizajes, inmunizaciones, odonto) |
| II | `RPT02_BIENESTAR` | Bienestar Fetal / Psicoprofilaxis / Estimulacion | 6 columnas |
| III | `RPT03_ANEMIA` | Anemia / Manejo Terapeutico / Dosaje Hb / Plan de Parto | 11 columnas |
| IV | `RPT04_COMPLICACIONES` | Gestante con Complicaciones | 15 filas x TOTAL + 4 grupos |
| V | `RPT05_MORBILIDAD_RN` | Morbilidad del RN | 7 filas x columna N unica |
| VI | `RPT06_ADMIN_MICRONUT` | Tamizaje HB y Micronutrientes | 10 columnas |
| VII | `RPT07_PUERPERIO` | Atencion de Puerperio | 3 columnas |
| VIII | `RPT08_VISITA` | Visita Domiciliaria | 2 filas (gestante/puerpera) x 3 grupos |
| IX-1 | `RPT09_1_TRANSMISION_VERTICAL` | Transmision Vertical - Gestantes | 21 columnas (VIH/Sifilis/Hepatitis B, 1o y 2o tamizaje) |
| IX-2 | `RPT09_2_TRANSMISION_VERTICAL` | Transmision Vertical - Puerperas Inmediatas | 6 columnas |
| IX-3 | `RPT09_3_TRANSMISION_VERTICAL` | Prueba rapida VIH en trabajo de parto/aborto | 4 columnas (web only: la plantilla oficial no tiene zona de datos) |
| X | `RPT10_CONSEJERIA` | Consejeria en Lactancia Materna | 3 columnas |

Grupos etareos (DimMaterno2023_Gedad): `<12 a.`, `12 - 17 a.`, `18 - 29 a.`, `30 - 59 a.` + fila TOTAL.

## Instalacion

1. Copiar los archivos del modulo a la raiz del sistema (junto a `reporte_cancer.php`):
   `reporte_materno.php`, `materno_export.php`, `install_materno.php`, `includes/materno_data.php`, `includes/materno_render.php`.
2. Copiar la plantilla oficial `Reporte_Actividades_Materno.xlsx` a `uploads/`.
3. (Recomendado, una sola vez) Abrir como admin `install_materno.php` para crear/verificar
   los indices de la tabla consolidada. Luego **eliminar** ese archivo.
4. Entrar a **Reportes Operacionales -> MATERNO -> Generar Reporte**.

No requiere tablas nuevas: el motor consulta la misma tabla consolidada MySQL que ya usan
ESNI y Cancer (`T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`).

## Ajuste de reglas contra el archivo "03 Creacion de Procedimientos"

El archivo `03 Creacion de Procedimientos.txt` del modulo Materno **no fue adjuntado** al
migrar; las condiciones iniciales por categoria usan los codigos HIS-MINSA estandar del
paquete materno (Z32/Z34/Z35 APN, 88141 PAP, 85018 Hb, O00-O9A complicaciones, P05-P39
morbilidad RN, 90715/90714/90744/90658 inmunizaciones, etc.).

**Todas las condiciones estan centralizadas y son auditables:**

1. En el reporte web marque la casilla **"Ver condiciones SQL (auditoria)"**: cada seccion
   muestra la condicion SQL equivalente que ejecuta el motor y la regla de conteo.
2. Compare esas condiciones con su archivo 03 original.
3. Ajuste las que difieran en un unico lugar: `includes/materno_data.php` ->
   funcion `maternoSecciones()`, campo `cond` de cada columna/fila.

### DSL de condiciones (campo `cond`)

| Clave | Ejemplo | Equivalente T-SQL |
|-------|---------|-------------------|
| `cod` | `['cod' => ['Z321','Z320']]` | `codigo_item IN ('Z321','Z320')` |
| `codPref` | `['codPref' => 'O47']` | `codigo_item LIKE 'O47%'` |
| `codEntre` | `['codEntre' => ['D50','D64Z']]` | `codigo_item BETWEEN 'D50' AND 'D64Z'` |
| `tip` | `['tip' => 'D']` | `tipo_diagnostico = 'D'` |
| `vl` | `['vl' => ['1','A','R']]` | `valor_lab IN ('1','A','R')` |
| `sexo` | `['sexo' => 'F']` | `id_genero = 'F'` |
| `gestante` | `['gestante' => true]` | `otra_condicion LIKE 'GESTANTE%'` |
| `puerpera` | `['puerpera' => true]` | `otra_condicion LIKE 'PUERPER%'` |
| `hb` | `['hb' => [7, 9.9]]` | `hemoglobina BETWEEN 7 AND 9.9` |
| `citaTiene` | `['citaTiene' => [...]]` | EXISTS de otra fila de la misma cita |
| `citaTieneTodo` | `['citaTieneTodo' => [...]]` | AND de EXISTS (bateria completa) |
| `pacTiene` | `['pacTiene' => [...]]` | EXISTS en alguna cita del paciente |
| `cualquieraDe` | `['cualquieraDe' => [...]]` | OR de sub-condiciones |

### Reglas de conteo (campo `regla`)

| Regla | Significado |
|-------|-------------|
| `['tipo' => 'simple']` | cada CITA que cumple cuenta 1 vez (como las tablas `_NOMINAL` del archivo 02) |
| `['tipo' => 'trimestre', 'n' => 1, 'oc' => 1]` | la 1a cita de tamizaje del paciente cuenta si su edad gestacional (por `Fecha_Ultima_Regla`) cae en el trimestre I (<14 sem), II (14-27.6) o III (>=28) |
| `['tipo' => 'ocurrencia', 'n' => 2]` | la 2a cita del paciente que cumple cuenta 1 vez (2o tamizaje, 2a bateria, 2a eco...) |
| `['tipo' => 'ocurrenciaMin', 'n' => 5]` | pacientes con >= N citas: la N-esima cuenta ("5o a +", "6o ENTREGA") |
| `['tipo' => 'conteoMinimo', 'n' => 6]` | pacientes con >= N citas cuentan 1 vez (Gestante CONTROLADA) |
| `['tipo' => 'calc', 'cols' => [2,3,4]]` | columna calculada = suma de otras (Total = I+II+III Trim, como `=SUM(C:E)`) |
| `'citaCond' => [...]` (extra en la regla) | la cita seleccionada debe ademas cumplir esta condicion (reactivo del N-esimo tamizaje) |

## Validacion del modulo

El desarrollo incluyo una bateria de 256 pruebas automaticas (sin BD, con PHP CLI):
estructura de secciones, DSL, reglas de conteo, E2E con datos sinteticos, render HTML
y export a la plantilla real verificada con openpyxl. Los tests viven fuera del sistema
(no se despliegan): `scripts/phptest/`.

## Notas de equivalencia con el flujo original

- El conteo es por **cita/atencion** (una fila por `id_cita`, igual que las tablas
  `TRAMA_BASE_MATERNO_*_NOMINAL` del archivo 02).
- La fila TOTAL de cada seccion = suma de los 4 grupos etareos (la plantilla usa `=SUM`).
- La columna "Total" de Gestante Atendida (seccion I) se escribe como valor ya calculado
  (la plantilla original tenia la formula `=SUM(C:E)`; se reemplaza para no depender del
  recalculo de formulas al abrir el archivo).
- El bloque IX-3 (prueba rapida VIH en trabajo de parto / aborto) se muestra solo en la
  web: la plantilla oficial no tiene zona de datos para el (en el flujo ODBC quedaba
  siempre en 0) y no se exporta.
- `periodo >= '202301'` del T-SQL se convierte en el filtro web de Anio/Mes.
