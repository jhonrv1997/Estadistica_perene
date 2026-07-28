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
  - Click en "Generar Reporte" -> se muestran las 14 secciones (A a VPH)
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
| Exportar | Excel con conexion externa | Boton "Exportar Excel" en la web |

## Archivos nuevos

| Archivo | Funcion |
|---------|---------|
| `Database/install_esni.sql` | Script MySQL que crea las 7 tablas de configuracion con datos semilla |
| `install_esni.php` | Asistente web para ejecutar el script SQL |
| `includes/esni_data.php` | Motor de reglas data-driven (helper functions) |
| `reporte_esni.php` | Pagina del reporte operacional ESNI (14 secciones) |
| `esni_config.php` | Pagina admin para gestionar vacunas, dosis, reglas, etc. |
| `esni_export.php` | Exportar el reporte a Excel (.xlsx) |

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `reporte_operacionales.php` | Tarjeta ESNI ahora enlaza a `reporte_esni.php` (con badge NUEVO) |
| `includes/header.php` | Menu "Rep. Operac. > ESNI" enlaza a `reporte_esni.php`; menu Admin incluye "Configurar ESNI" |
| `assets/css/style.css` | Estilos para secciones ESNI, tarjetas destacadas, etc. |

## Instalacion (3 pasos)

### Paso 1: Ejecutar el script SQL

Tiene 2 opciones:

**Opcion A - Asistente web (recomendado):**
1. Copie los nuevos archivos a su servidor (manteniendo la estructura).
2. Abra en el navegador: `https://sudominio.com/install_esni.php` (debe iniciar sesion como admin).
3. El asistente ejecutara el script SQL y verificara que las 7 tablas se creen.
4. Elimine `install_esni.php` por seguridad.

**Opcion B - Manual via phpMyAdmin o CLI:**
```bash
mysql -u usuario -p su_base_de_datos < Database/install_esni.sql
```

### Paso 2: Verificar la instalacion

Entre a IntelHIS y navegue a:
- **Reportes Operacionales > ESNI** (debe mostrar la pagina con stats de configuracion).
- Debe ver 6 cards con: Vacunas=21, Dosis=9, Grupos Edad=26, Secciones=14, Lineas=66, Reglas=120.

### Paso 3: Ajustar parametros (opcional)

Si su tabla consolidada MySQL tiene nombres de columnas diferentes a los del esquema estandar HIS MINSA, vaya a:
- **Admin > Configurar ESNI > Parametros**

Alli puede ajustar:
- `tabla_origen`: nombre de la tabla consolidada MySQL (default: `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`)
- `columna_cod_item`, `columna_valor_lab`, `columna_anio`, `columna_mes`, etc.

El sistema detecta automaticamente variantes de nombres (case-insensitive). Si una columna no existe, el motor es tolerante y la trata como NULL.

## Tablas creadas (esquema data-driven)

```
ESNI_VACUNA          (21 filas) - BCG, HVB, IPV, PENTA, ROTA, NEUMO, INF, SPR, ...
ESNI_GRUPO_EDAD      (26 filas) - 24H, 28D, 01_11M, 02_04A, GEST, RIESGO, COMORB, ...
ESNI_DOSIS           ( 9 filas) - D1, D2, D3, D4, DU, REF1, REF2, REF3, TOT
ESNI_SECCION_REPORTE (14 filas) - A, B, C, H, H2, I, J, K, L, M, N, O, P, VPH
ESNI_LINEA_REPORTE   (66 filas) - lineas por seccion (vacuna+dosis+edad+sexo)
ESNI_REGLA          (120 filas) - reglas cod_item+valor_lab -> linea (seccion A completa)
ESNI_PARAMETRO       (20 filas) - mapeo de columnas de la tabla origen
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

-- 2) Crear lineas (asumiendo seccion Q id=15)
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
VALUES (15, 1, 'DENGUE - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DENGUE'), 1, NULL, 'A');

-- 3) Crear reglas
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='DENGUE - 1RA DOSIS' AND id_seccion=15), '91000', '1'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='DENGUE - 1RA DOSIS' AND id_seccion=15), '91000', '01'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='DENGUE - 1RA DOSIS' AND id_seccion=15), '91000', 'D1');
```

## Estructura del reporte

El reporte muestra las 14 secciones oficiales MINSA con los siguientes layouts:

| Seccion | Titulo | Layout |
|----------|--------|--------|
| A | Menores de 1 anio | Lista simple (26 lineas: BCG, HVB, IPV, PENTA, RXN, ROTA, NEUMO, INF, RIESGO) |
| B | De 1 anio | Lista simple (13 lineas: NEUMO 1a, SPR, VAR, INF, NEUMO 12-23m, AMA, HEP_A, SPR 2da, REF DPT, REF APO) |
| C | De 2 anios | Lista simple (16 lineas: INF comorb/sin comorb, NEUMO comorb, AMA, SPR, Varicela, REF Pentavalente, REF IPV) |
| D | De 3 anios | Lista simple (lineas: INF comorb/sin comorb, NEUMO comorb, AMA, SPR, REF Pentavalente, REF IPV)|
| E1 | De 4 anios | Lista simple (lineas: INF comorb/sin comorb, NEUMO comorb, AMA, SPR, Refuerzo Antipolio (IPV), REF Pentavalente, Refuerzo DPT, Refuerzo Antipolio (APO)|
| E2 | De 5 a 7 anios | Lista simple (lineas: SPR, Refuerzo DPT|
| H | dT Mujeres 10-49 anos | Matriz por dosis (3 columnas: D1, D2, D3) |
| H2 | Gestantes TDAP | Matriz por dosis (4 columnas: dT D1-D3 + TDAP) |
| I | dT Varones en riesgo | Matriz por dosis (3 columnas: D1, D2, D3) |
| J | Influenza otros grupos | Solo con Comorbilidad(05 a 11, 12 a 17, 18 a 29, 30 a 49, 50 a 59); SIN Comorbilidad(05 a 11, 12 a 17, 18 a 29, 30 a 49, 50 a 59); Mayores de 60|
| K | SPR-SARAMPION | Total unico por grupo (5 a 10, 11 a 59), Trabajador de Salud (Valor_Lab=ST)|
| L | Hepatitis B 5-59 anos | Matriz por dosis |
| M | Antiamarilica | Total unico por grupo |
| N | Antipolio Oral | Matriz por edad (3 columnas: 02, 03, 04 anos) |
| O | Varicela | Total unico por grupo |
| P | Neumococo | Total unico por grupo (14 grupos) |
| VPH | Virus Papiloma Humano | Matriz por sexo (Femenino 1ra, Femenino 2da, Masculino Unica) |

## Reglas sembradas

El script SQL inicial incluye **120 reglas** que cubren completamente la **Seccion A (Menores de 1 anio)**, equivalentes a los CASE statements del stored procedure `usp_TRAMA_BASE_ESNI_RPT_01_REPORTE_A_2019`.

Para las secciones B-Q, las lineas estan creadas pero las reglas deben agregarse via `esni_config.php` (siguiendo el patron de la seccion A). El equipo del establecimiento puede completarlas en 1-2 horas usando el stored procedure original como referencia.

## Motor de reglas (como funciona)

El motor en `includes/esni_data.php` funciona en 4 etapas:

1. **Cargar reglas activas** desde `ESNI_REGLA` (join con `ESNI_LINEA_REPORTE` y `ESNI_GRUPO_EDAD`).
2. **Construir 1 sola consulta SQL** contra la tabla origen trayendo solo filas con los `cod_item` relevantes, aplicando los filtros comunes (anio, mes, EE.SS., etc.).
3. **Para cada fila HIS**, evaluar las reglas del `cod_item` correspondiente en orden:
   - Si `valor_lab` encaja (NULL = cualquiera)
   - Y el sexo encaja (M/F/A)
   - Y el rango `aniomes` encaja (si esta definido)
   - Y el grupo de edad encaja (numericamente o por texto)
   - Y la condicion de riesgo encaja (`requiere_riesgo` / `excluye_riesgo`)
   - -> Contar la fila en la primera regla que encaje (break).
4. **Sumar contadores** por linea y por seccion, renderizar segun el layout.

## Limitaciones / notas

- **Compatibilidad con valor_lab**: el motor trabaja con los valores `valor_lab` que existan en la tabla consolidada MySQL. Si la tabla no tiene esta columna (porque se cargo solo con `Codigo_Item` y `Grupo_Edad`), el motor cuenta TODAS las dosis del cod_item (modo basico). Para distinguir dosis, la tabla origen debe incluir la columna `Valor_Lab` o similar.
- **Deduplicacion**: si la columna `I_ROWNUM_LAB` existe, el motor solo considera la fila 1 (deduplicacion). Si no existe, considera todas las filas.
- **Performance**: el motor agrupa los `cod_item` y hace 1 sola consulta con `IN (...)`, optimizando para tablas grandes. Para tablas con millones de filas, considere agregar indices en `cod_item` y `anio_mes`.

## Soporte

Para reportar problemas o sugerir mejoras, abra un issue en:
https://github.com/jhonrv1997/Estadistica_perene/issues
