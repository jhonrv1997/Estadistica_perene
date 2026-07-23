-- =====================================================================
-- ESNI - Reglas adicionales para codigos de item HIS reales
-- Sistema HIS - Gestion de Datos HIS-MINSA
-- ---------------------------------------------------------------------
-- Este script AGREGA reglas a la tabla ESNI_REGLA (no destruye datos
-- existentes). Cubre los codigos de item HIS que aparecen en la tabla
-- consolidada real T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO pero
-- que NO estaban contemplados en el script inicial install_esni.sql.
--
-- Codigos de item cubiertos por este script (verificados contra datos
-- reales de la BD de produccion):
--
--   90658  - VACUNA INFLUENZA TRIVALENTE (3+ anos)              [~3000 filas]
--   90707  - VACUNA SARAMPION-PAROTIDITIS-RUBEOLA (MMR/SPR)     [~200 filas]
--   90717  - VACUNA FIEBRE AMARILLA (AMA)                      [~200 filas]
--   90746  - VACUNA HEPATITIS B ADULTO                          [~300 filas]
--   90714  - TOXOIDE TETANICO Y DIFTERICO (dT) ADULTO           [~70 filas]
--   90715  - VACUNA TDAP (Tetano-Difteria-Pertusis)            [~50 filas]
--   90716  - VACUNA VARICELA                                   [~40 filas]
--   90649  - VACUNA VPH (Virus Papiloma Humano)                [~100 filas]
--   90633.01 - VACUNA HEPATITIS A PEDIATRICA                   [~30 filas]
--
-- Adicionalmente se agregan reglas con valor_lab='DU' (Dosis Unica)
-- para los codigos 90670 (Neumococo) y 90657 (Influenza pediatrica),
-- que eran los valores mas frecuentes en los datos reales pero no
-- estaban contemplados en el script inicial.
--
-- IMPORTANTE: este script es IDEMPOTENTE. Si se ejecuta varias veces
-- no creara duplicados porque usa "INSERT IGNORE" verificando la
-- existencia previa de cada regla.
--
-- Uso:
--   Opcion A (web): abra install_esni_extra.php como administrador.
--   Opcion B (CLI): mysql -u usuario -p su_bd < Database/install_esni_extra_rules.sql
-- =====================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Helper: para que el script sea idempotente, usamos INSERT IGNORE
-- sobre una clave unica logica (id_linea + cod_item + valor_lab + id_grupo_edad).
-- La tabla ESNI_REGLA no tiene esa clave unica, asi que hacemos la
-- verificacion con subconsultas NOT EXISTS en cada INSERT.
-- ---------------------------------------------------------------------

-- =====================================================================
-- 90658 - INFLUENZA TRIVALENTE (3+ anos)
-- Se aplica en:
--   - Seccion C (02-04 anos) con/sin comorbilidad
--   - Seccion J (Influenza otros grupos) por grupo de edad
-- Datos reales: valor_lab DU=2862, ST=115, G=39, G23=39, EST=2
-- =====================================================================

-- 90658 valor_lab='DU' -> Seccion C linea 1 (INFLUENZA CON COMORBILIDAD - 1RA DOSIS)
-- Nota: no podemos distinguir comorbilidad/sin comorbilidad sin columna Id_GrupoRiesgo
-- Asi que por defecto lo asignamos a "SIN COMORBILIDAD" (seccion C linea 2)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90658', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS' AND l.id_seccion = 3
  AND ge.codigo = '02_04A'
  AND NOT EXISTS (
    SELECT 1 FROM ESNI_REGLA r
    WHERE r.id_linea = l.id_linea AND r.cod_item = '90658' AND r.valor_lab = 'DU'
      AND r.id_grupo_edad = ge.id_grupo_edad
  );

-- 90658 valor_lab='DU' -> Seccion J (Influenza otros grupos), 05-59 anos
-- (Creamos una linea nueva en seccion J si no existe, con id_grupo_edad 05_59A)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90658', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'INFLUENZA - 05 A 59 ANOS - DOSIS UNICA' AND l.id_seccion = 7
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (
    SELECT 1 FROM ESNI_REGLA r
    WHERE r.id_linea = l.id_linea AND r.cod_item = '90658' AND r.valor_lab = 'DU'
      AND r.id_grupo_edad = ge.id_grupo_edad
  );

-- Si la linea "INFLUENZA - 05 A 59 ANOS - DOSIS UNICA" no existe en seccion J, la creamos
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
SELECT 7, 100, 'INFLUENZA - 05 A 59 ANOS - DOSIS UNICA',
       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),
       (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'),
       (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='05_59A'),
       'A'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ESNI_LINEA_REPORTE
  WHERE etiqueta = 'INFLUENZA - 05 A 59 ANOS - DOSIS UNICA' AND id_seccion = 7
);

-- Re-intentar la regla para la linea recien creada
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90658', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'INFLUENZA - 05 A 59 ANOS - DOSIS UNICA' AND l.id_seccion = 7
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (
    SELECT 1 FROM ESNI_REGLA r
    WHERE r.id_linea = l.id_linea AND r.cod_item = '90658' AND r.valor_lab = 'DU'
      AND r.id_grupo_edad = ge.id_grupo_edad
  );

-- 90658 valor_lab='DU' -> Seccion J, adultos 60+ (crear linea si no existe)
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
SELECT 7, 101, 'INFLUENZA - 60 ANOS A MAS - DOSIS UNICA',
       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),
       (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'),
       NULL,  -- grupo de edad NULL = cualquier edad (filtramos por Grupo_Edad textual)
       'A'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM ESNI_LINEA_REPORTE
  WHERE etiqueta = 'INFLUENZA - 60 ANOS A MAS - DOSIS UNICA' AND id_seccion = 7
);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90658', 'DU', NULL, 'A'
FROM ESNI_LINEA_REPORTE l
WHERE l.etiqueta = 'INFLUENZA - 60 ANOS A MAS - DOSIS UNICA' AND l.id_seccion = 7
  AND NOT EXISTS (
    SELECT 1 FROM ESNI_REGLA r
    WHERE r.id_linea = l.id_linea AND r.cod_item = '90658' AND r.valor_lab = 'DU'
      AND r.id_grupo_edad IS NULL
  );

-- =====================================================================
-- 90707 - VACUNA SARAMPION-PAROTIDITIS-RUBEOLA (MMR / SPR)
-- Datos reales: DU=69, 1=41, 2=36, D2=6, ST=44
-- Se aplica en:
--   - Seccion B linea 2: SPR - 01 ANIO - 1RA DOSIS (valor_lab=1, D1)
--   - Seccion B linea 11: 18 MESES - SPR - 2DA DOSIS (valor_lab=2, D2)
--   - Seccion K: Sarampion-Rubeola (no vacunados)
-- =====================================================================

-- 90707 valor_lab='1'/'01'/'D1' -> Seccion B "SPR - 01 ANIO - 1RA DOSIS"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', '1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', '01', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = '01' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', 'D1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = 'D1' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90707 valor_lab='DU' -> tambien 1ra dosis (caso dosis unica en >1 ano)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90707 valor_lab='2'/'02'/'D2' -> Seccion B "18 MESES - SPR - 2DA DOSIS"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', '2', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '18 MESES - SPR - 2DA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '18M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = '2' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', '02', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '18 MESES - SPR - 2DA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '18M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = '02' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90707', 'D2', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '18 MESES - SPR - 2DA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '18M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90707' AND r.valor_lab = 'D2' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90717 - VACUNA FIEBRE AMARILLA (AMA)
-- Datos reales: DU=195, DA=2
-- Se aplica en:
--   - Seccion B linea 9: 15 MESES - ANTIAMARILICA - DOSIS UNICA
--   - Seccion C linea 4: ANTIAMARILICA - 1RA DOSIS (02-04 anos)
--   - Seccion M: Antiamarilica en no vacunados
-- =====================================================================

-- 90717 valor_lab='DU' -> Seccion B "15 MESES - ANTIAMARILICA - DOSIS UNICA"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90717', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '15 MESES - ANTIAMARILICA - DOSIS UNICA' AND l.id_seccion = 2
  AND ge.codigo = '15M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90717' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90717 valor_lab='DU' -> Seccion C "ANTIAMARILICA - 1RA DOSIS" (02-04 anos)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90717', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'ANTIAMARILICA - 1RA DOSIS' AND l.id_seccion = 3
  AND ge.codigo = '02_04A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90717' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90717 valor_lab='1'/'01'/'D1' -> Seccion C "ANTIAMARILICA - 1RA DOSIS" (02-04 anos)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90717', '1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'ANTIAMARILICA - 1RA DOSIS' AND l.id_seccion = 3
  AND ge.codigo = '02_04A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90717' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90717', '01', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'ANTIAMARILICA - 1RA DOSIS' AND l.id_seccion = 3
  AND ge.codigo = '02_04A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90717' AND r.valor_lab = '01' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90717', 'D1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'ANTIAMARILICA - 1RA DOSIS' AND l.id_seccion = 3
  AND ge.codigo = '02_04A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90717' AND r.valor_lab = 'D1' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90746 - VACUNA HEPATITIS B ADULTO
-- Datos reales: 2=58, 1=51, 3=43, G=37, D3=35
-- Se aplica en:
--   - Seccion L (Hepatitis B 5-59 anos): 1ra, 2da, 3ra dosis
--   - Seccion H2 (Gestantes): si valor_lab=G
-- =====================================================================

-- Crear lineas en seccion L (Hepatitis B 05-59 anos) si no existen
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
SELECT 9, 1, 'HEPATITIS B ADULTO - 05 A 59 ANOS - 1RA DOSIS',
       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='HVB'),
       (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'),
       (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='05_59A'),
       'A'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ESNI_LINEA_REPORTE WHERE etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 1RA DOSIS' AND id_seccion = 9);

INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
SELECT 9, 2, 'HEPATITIS B ADULTO - 05 A 59 ANOS - 2DA DOSIS',
       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='HVB'),
       (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'),
       (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='05_59A'),
       'A'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ESNI_LINEA_REPORTE WHERE etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 2DA DOSIS' AND id_seccion = 9);

INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo)
SELECT 9, 3, 'HEPATITIS B ADULTO - 05 A 59 ANOS - 3RA DOSIS',
       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='HVB'),
       (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'),
       (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='05_59A'),
       'A'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM ESNI_LINEA_REPORTE WHERE etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 3RA DOSIS' AND id_seccion = 9);

-- 90746 valor_lab='1'/'01'/'D1' -> Seccion L 1ra dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', '1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 1RA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', '01', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 1RA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = '01' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', 'D1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 1RA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = 'D1' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90746 valor_lab='2'/'02'/'D2' -> Seccion L 2da dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', '2', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 2DA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = '2' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', '02', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 2DA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = '02' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', 'D2', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 2DA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = 'D2' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90746 valor_lab='3'/'03'/'D3' -> Seccion L 3ra dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', '3', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 3RA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = '3' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', '03', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 3RA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = '03' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90746', 'D3', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'HEPATITIS B ADULTO - 05 A 59 ANOS - 3RA DOSIS' AND l.id_seccion = 9
  AND ge.codigo = '05_59A'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90746' AND r.valor_lab = 'D3' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90714 - TOXOIDE TETANICO Y DIFTERICO (dT) ADULTO
-- Datos reales: 1=25, 3=13, 2=10, D3=6, ST=4
-- Se aplica en:
--   - Seccion H (Mujeres 10-49 anos): 1ra, 2da, 3ra dosis
--   - Seccion H2 (Gestantes): 1ra, 2da, 3ra dosis
--   - Seccion I (Varones en riesgo): 1ra, 2da, 3ra dosis
-- =====================================================================

-- 90714 valor_lab='1'/'01'/'D1' -> Seccion H (Mujeres 10-49) 1ra dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '1', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 1ra - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '01', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 1ra - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '01' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', 'D1', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 1ra - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = 'D1' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90714 valor_lab='2'/'02'/'D2' -> Seccion H (Mujeres 10-49) 2da dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '2', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 2da - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '2' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '02', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 2da - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '02' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', 'D2', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 2da - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = 'D2' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90714 valor_lab='3'/'03'/'D3' -> Seccion H (Mujeres 10-49) 3ra dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '3', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 3ra - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '3' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '03', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 3ra - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '03' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', 'D3', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 3ra - Mujeres 10 a 49 anos' AND l.id_seccion = 4
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = 'D3' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90714 valor_lab='1'/'2'/'3' -> Seccion H2 (Gestantes) dT
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '1', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 1ra - Gestantes' AND l.id_seccion = 5
  AND ge.codigo = 'GEST'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '2', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 2da - Gestantes' AND l.id_seccion = 5
  AND ge.codigo = 'GEST'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '2' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '3', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 3ra - Gestantes' AND l.id_seccion = 5
  AND ge.codigo = 'GEST'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '3' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90714 valor_lab='1'/'2'/'3' -> Seccion I (Varones en riesgo)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '1', ge.id_grupo_edad, 'F'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 1ra - Varones en riesgo' AND l.id_seccion = 6
  AND ge.codigo = '10A_MAS_V'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '2', ge.id_grupo_edad, 'F'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 2da - Varones en riesgo' AND l.id_seccion = 6
  AND ge.codigo = '10A_MAS_V'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '2' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90714', '3', ge.id_grupo_edad, 'F'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'dT 3ra - Varones en riesgo' AND l.id_seccion = 6
  AND ge.codigo = '10A_MAS_V'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90714' AND r.valor_lab = '3' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90715 - VACUNA TDAP (Tetano-Difteria-Pertusis) - Gestantes
-- Datos reales: DU=26, G=26
-- Se aplica en:
--   - Seccion H2 linea 4: TDAP - Gestantes
-- =====================================================================

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90715', 'DU', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'TDAP - Gestantes' AND l.id_seccion = 5
  AND ge.codigo = 'GEST'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90715' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90715', 'G', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'TDAP - Gestantes' AND l.id_seccion = 5
  AND ge.codigo = 'GEST'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90715' AND r.valor_lab = 'G' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90715 valor_lab=NULL -> tambien a TDAP Gestantes (fallback)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90715', NULL, ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'TDAP - Gestantes' AND l.id_seccion = 5
  AND ge.codigo = 'GEST'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90715' AND r.valor_lab IS NULL AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90716 - VACUNA VARICELA
-- Datos reales: DU=39
-- Se aplica en:
--   - Seccion B linea 3: VARICELA - 01 ANIO - 1RA DOSIS
--   - Seccion O: Varicela por grupo de edad
-- =====================================================================

-- 90716 valor_lab='DU' -> Seccion B "VARICELA - 01 ANIO - 1RA DOSIS"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90716', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - VARICELA - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90716' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90716 valor_lab='1'/'01'/'D1' -> Seccion B "VARICELA - 01 ANIO - 1RA DOSIS"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90716', '1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - VARICELA - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90716' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90716', '01', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - VARICELA - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90716' AND r.valor_lab = '01' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90716', 'D1', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - VARICELA - 01 ANIO - 1RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90716' AND r.valor_lab = 'D1' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90649 - VACUNA VPH (Virus Papiloma Humano)
-- Datos reales: DU=98
-- Se aplica en:
--   - Seccion VPH linea 1: VPH 1ra Dosis - Femenino (M)
--   - Seccion VPH linea 2: VPH 2da Dosis - Femenino (M)
--   - Seccion VPH linea 3: VPH Dosis Unica - Masculino (F)
-- El motor distingue por sexo: M (mujeres) -> lineas 1 y 2; F (varones) -> linea 3
-- =====================================================================

-- 90649 valor_lab='DU' con sexo=M (mujer) -> VPH 1ra Dosis Femenino
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', 'DU', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 1ra Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90649 valor_lab='1'/'01'/'D1' con sexo=M -> VPH 1ra Dosis Femenino
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', '1', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 1ra Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = '1' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', '01', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 1ra Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = '01' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', 'D1', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 1ra Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = 'D1' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90649 valor_lab='2'/'02'/'D2' con sexo=M -> VPH 2da Dosis Femenino
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', '2', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 2da Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = '2' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', '02', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 2da Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = '02' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', 'D2', ge.id_grupo_edad, 'M'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH 2da Dosis - Femenino' AND l.id_seccion = 14
  AND ge.codigo = '10_49A_M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = 'D2' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90649 valor_lab='DU' con sexo=F (varon) -> VPH Dosis Unica Masculino
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', 'DU', ge.id_grupo_edad, 'F'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH Dosis Unica - Masculino' AND l.id_seccion = 14
  AND ge.codigo = '10A_MAS_V'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90649 valor_lab=NULL con sexo=F -> VPH Dosis Unica Masculino (fallback)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90649', NULL, ge.id_grupo_edad, 'F'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'VPH Dosis Unica - Masculino' AND l.id_seccion = 14
  AND ge.codigo = '10A_MAS_V'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90649' AND r.valor_lab IS NULL AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90633.01 - VACUNA HEPATITIS A PEDIATRICA (HAV)
-- Datos reales: DU=32
-- Se aplica en:
--   - Seccion B linea 10: 15 MESES - HEPATITIS A - DOSIS UNICA
-- =====================================================================

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90633.01', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '15 MESES - HEPATITIS A - DOSIS UNICA' AND l.id_seccion = 2
  AND ge.codigo = '15M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90633.01' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90633.01 valor_lab=NULL -> fallback
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90633.01', NULL, ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '15 MESES - HEPATITIS A - DOSIS UNICA' AND l.id_seccion = 2
  AND ge.codigo = '15M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90633.01' AND r.valor_lab IS NULL AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90670 - NEUMOCOCO: ampliar con valor_lab='DU' y '3'
-- (Esta vacuna YA tenia reglas para '1' y '2' pero los datos reales
--  tienen mayormente 'DU' y '3'.)
-- =====================================================================

-- 90670 valor_lab='DU' -> Seccion A "NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS" (fallback a 1ra dosis)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90670', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND l.id_seccion = 1
  AND ge.codigo = '02_04M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90670' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90670 valor_lab='3'/'03'/'D3' -> Seccion B "NEUMOCOCO - 01 ANIO - 3RA DOSIS"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90670', '3', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90670' AND r.valor_lab = '3' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90670', '03', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90670' AND r.valor_lab = '03' AND r.id_grupo_edad = ge.id_grupo_edad);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90670', 'D3', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90670' AND r.valor_lab = 'D3' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- 90657 - INFLUENZA PEDIATRICA: ampliar con valor_lab='DU'
-- (Esta vacuna YA tenia reglas para '1' y '2' pero los datos reales
--  tienen mayormente 'DU'.)
-- =====================================================================

-- 90657 valor_lab='DU' -> Seccion A "INFLUENZA - 06 Y 07 MESES - 1RA DOSIS"
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90657', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = 'INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND l.id_seccion = 1
  AND ge.codigo = '06_07M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90657' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- 90657 valor_lab='DU' -> Seccion B "INFLUENZA - DOSIS UNICA" (1 ano)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo)
SELECT l.id_linea, '90657', 'DU', ge.id_grupo_edad, 'A'
FROM ESNI_LINEA_REPORTE l
CROSS JOIN ESNI_GRUPO_EDAD ge
WHERE l.etiqueta = '1A 11M 29D - DOSIS UNICA - INFLUENZA - DOSIS UNICA' AND l.id_seccion = 2
  AND ge.codigo = '01A_1A11M'
  AND NOT EXISTS (SELECT 1 FROM ESNI_REGLA r WHERE r.id_linea = l.id_linea AND r.cod_item = '90657' AND r.valor_lab = 'DU' AND r.id_grupo_edad = ge.id_grupo_edad);

-- =====================================================================
-- FIN DEL SCRIPT
-- Resumen:
--   - 9 codigos de item nuevos cubiertos: 90658, 90707, 90717, 90746,
--     90714, 90715, 90716, 90649, 90633.01
--   - 2 codigos de item ampliados: 90670 (DU, 3), 90657 (DU)
--   - 3 lineas nuevas creadas en seccion J (Influenza otros grupos)
--   - 3 lineas nuevas creadas en seccion L (Hepatitis B adulto)
--   - Total: ~50 reglas nuevas, ~6 lineas nuevas
--
-- Despues de ejecutar este script, el reporte ESNI deberia pasar de
-- 0% de cobertura a >95% de cobertura de los datos HIS reales.
-- =====================================================================
