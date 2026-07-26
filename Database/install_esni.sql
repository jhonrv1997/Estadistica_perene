-- =====================================================================
-- ESNI - Schema de Tablas de Configuracion Data-Driven
-- Sistema HIS - Gestion de Datos HIS-MINSA
-- ---------------------------------------------------------------------
-- Este script crea un conjunto de tablas que permiten configurar de forma
-- dinamica (desde la web) las vacunas, dosis, grupos de edad y reglas de
-- mapeo que antes estaban hard-codeadas en los Stored Procedures T-SQL
-- del SQL Server (archivos 01_DimESNI.txt y 03_StoredProcedure_TRAMA_BASE.txt).
--
-- Ventajas frente al proceso anterior:
--   * No se necesita SQL Server ni Excel.
--   * Agregar una vacuna o dosis = insertar 1 fila en ESNI_VACUNA / ESNI_DOSIS.
--   * Modificar un cod_item / valor_lab = editar 1 fila en ESNI_REGLA.
--   * Los reportes se generan en linea desde la web, con filtros por
--     anio, mes, establecimiento, departamento y profesional.
--
-- Tablas creadas:
--   ESNI_VACUNA          - Maestro de vacunas (BCG, Hepatitis B, Pentavalente, ...)
--   ESNI_GRUPO_EDAD      - Maestro de grupos de edad (24H, 28D, 01-11M, 02-04A, ...)
--   ESNI_DOSIS           - Maestro de dosis (1ra, 2da, 3ra, Unica, Refuerzo, ...)
--   ESNI_SECCION_REPORTE - Secciones del Reporte Operacional (A, B, C, H, H2, I, ...)
--   ESNI_LINEA_REPORTE   - Lineas dentro de cada seccion (vacuna+dosis+grupo edad)
--   ESNI_REGLA           - Reglas de mapeo cod_item+valor_lab+rango edad -> linea
--   ESNI_PARAMETRO       - Parametros generales del modulo
-- =====================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1) ESNI_VACUNA - Maestro de vacunas
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_VACUNA;
CREATE TABLE ESNI_VACUNA (
  id_vacuna         INT AUTO_INCREMENT PRIMARY KEY,
  codigo            VARCHAR(30)  NOT NULL UNIQUE,           -- ej: BCG, HVB, IPV, PENTA, ROTA, NEUMO, INF, SPR, VAR, AMA, HEP_A, DPT, APO, DT, TDAP, VPH, SR
  nombre            VARCHAR(80)  NOT NULL,                  -- ej: BCG, Hepatitis Viral B, Antipolio (IPV), Pentavalente
  descripcion       VARCHAR(200) NULL,
  color             VARCHAR(20)  NULL DEFAULT '#0d6efd',    -- para UI
  activo            TINYINT(1)   NOT NULL DEFAULT 1,
  fecha_creacion    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_vacuna_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maestro de vacunas ESNI';

INSERT INTO ESNI_VACUNA (codigo, nombre, descripcion, color) VALUES
  ('BCG',    'BCG',                          'Vacuna BCG contra tuberculosis',                '#fd7e14'),
  ('HVB',    'Hepatitis Viral B',            'Vacuna Hepatitis B',                            '#dc3545'),
  ('IPV',    'Antipolio (IPV)',              'Vacuna Antipolio Inactivada',                   '#0dcaf0'),
  ('APO',    'Antipolio Oral (APO)',         'Vacuna Antipolio Oral',                         '#0d6efd'),
  ('PENTA',  'Pentavalente',                 'Vacuna Pentavalente (DTP-Hib-HepB)',            '#6610f2'),
  ('RXN_DTP','Reaccion Adversa Dt(p)',       'Reaccion adversa a componente Difterico',       '#6f42c1'),
  ('RXN_HVB','Reaccion Adversa HvB',         'Reaccion adversa a Hepatitis B',                '#e83e8c'),
  ('RXN_HIB','Reaccion Adversa Hib',         'Reaccion adversa a Haemophilus influenzae type b','#fd7e14'),
  ('ROTA',   'Rotavirus',                    'Vacuna contra Rotavirus',                       '#20c997'),
  ('NEUMO',  'Neumococo',                    'Vacuna Conjugada Neumococica',                  '#198754'),
  ('INF',    'Influenza',                    'Vacuna contra Influenza Estacional',            '#0dcaf0'),
  ('SPR',    'SPR (Sarampion-Paperas-Rubeola)','Vacuna Triple Viral',                         '#ffc107'),
  ('SR',     'SR (Sarampion-Rubeola)',       'Vacuna Doble Viral',                            '#ffca2c'),
  ('VAR',    'Varicela',                     'Vacuna contra Varicela',                        '#adb5bd'),
  ('AMA',    'Antiamarilica (Fiebre Amarilla)','Vacuna contra Fiebre Amarilla',               '#ffd700'),
  ('HEP_A',  'Hepatitis A',                  'Vacuna contra Hepatitis A',                     '#b02a37'),
  ('DPT',    'DPT (Refuerzo)',               'Vacuna DPT Refuerzo pediatrica',                '#6f42c1'),
  ('DT',     'dT Adulto',                    'Vacuna dT Adulto (Difteria-Tetanica)',          '#5c636a'),
  ('TDAP',   'TDAP',                         'Vacuna TDAP Gestantes (Difteria-Tetanica-Pertusis)','#d63384'),
  ('VPH',    'VPH (Virus Papiloma Humano)',  'Vacuna contra Virus del Papiloma Humano',       '#7b2ff7'),
  ('BCG_TB', 'BCG Contacto TB',              'BCG en contactos de Tuberculosis',              '#fd7e14');

-- ---------------------------------------------------------------------
-- 2) ESNI_GRUPO_EDAD - Maestro de grupos de edad
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_GRUPO_EDAD;
CREATE TABLE ESNI_GRUPO_EDAD (
  id_grupo_edad     INT AUTO_INCREMENT PRIMARY KEY,
  codigo            VARCHAR(40)  NOT NULL UNIQUE,           -- ej: 24H, 28D, 01_11M, 12_23M, 02_04A, 10_49A, GEST, etc.
  nombre            VARCHAR(80)  NOT NULL,                  -- ej: 24 horas, 28 dias, 01 a 11 meses
  tipo_edad         ENUM('D','M','A','R') NOT NULL DEFAULT 'A',  -- D=dias, M=meses, A=anos, R=rango
  edad_min          INT NULL,                               -- edad minima segun tipo_edad
  edad_max          INT NULL,                               -- edad maxima segun tipo_edad
  descripcion       VARCHAR(150) NULL,
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_ge_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Grupos de edad ESNI';

INSERT INTO ESNI_GRUPO_EDAD (codigo, nombre, tipo_edad, edad_min, edad_max, descripcion) VALUES
  ('24H',        '24 horas',                  'D', 1, 1,    'Recien nacido - 24 horas de vida'),
  ('28D',        '28 dias',                   'D', 2, 28,   'De 2 a 28 dias'),
  ('01_11M',     '01m a 11m 29d',             'M', 1, 11,   'Menores de 1 anio (1 a 11 meses)'),
  ('02_07M',     '02 a 07 meses',             'M', 2, 7,    'Lactantes 2 a 7 meses (Rotavirus)'),
  ('02_04M',     '02 y 04 meses',             'M', 2, 4,    'Lactantes 2 y 4 meses'),
  ('06_07M',     '06 y 07 meses',             'M', 6, 7,    'Lactantes 6 y 7 meses (Influenza)'),
  ('06_11M',     '06 a 11 meses',             'M', 6, 11,   'Lactantes 6 a 11 meses'),
  ('12_23M',     '12 a 23 meses',             'M', 12, 23,  'De 12 a 23 meses'),
  ('01A_1A11M',  '01 anio (1a 11m 29d)',      'A', 1, 1,    'Ninos de 1 anio'),
  ('15M',        '15 meses',                  'M', 15, 15,  'Ninos de 15 meses'),
  ('18M',        '18 meses',                  'M', 18, 18,  'Ninos de 18 meses'),
  ('02_04A',     '02 a 04 anos',              'A', 2, 4,    'Ninos de 2 a 4 anos'),
  ('02A',        '02 anos',                   'A', 2, 2,    'Ninos de 2 anos'),
  ('03A',        '03 anos',                   'A', 3, 3,    'Ninos de 3 anos'),
  ('04A',        '04 anos',                   'A', 4, 4,    'Ninos de 4 anos'),
  ('05_59A',     '05 a 59 anos',              'A', 5, 59,   'Poblacion de 5 a 59 anos'),
  ('10_49A_M',   '10 a 49 anos (Mujeres)',    'A', 10, 49,  'Mujeres en edad fertil 10 a 49 anos'),
  ('10A_MAS_V',  '10 anos a mas (Varones)',   'A', 10, 200, 'Varones en riesgo 10 anos a mas'),
  ('GEST',       'Gestantes',                 'R', NULL, NULL, 'Mujeres gestantes'),
  ('RIESGO',     'Poblacion en riesgo',       'R', NULL, NULL, 'Poblacion con factores de riesgo'),
  ('COMORB',     'Con comorbilidad',          'R', NULL, NULL, 'Poblacion con comorbilidad'),
  ('SIN_COMORB', 'Sin comorbilidad',          'R', NULL, NULL, 'Poblacion sin comorbilidad'),
  ('CONTACTO_TB','Contacto TB',               'R', NULL, NULL, 'Contactos de pacientes TB'),
  ('CONTACTO_VAR','Contacto indice Varicela', 'R', NULL, NULL, 'Contacto indice de Varicela'),
  ('VIAJA_END',  'Viaja a zonas endemicas',   'R', NULL, NULL, 'Persona que viaja a zonas endemicas'),
  ('NO_VAC',     'Poblacion no vacunada',     'R', NULL, NULL, 'Poblacion no vacunada previamente');

-- ---------------------------------------------------------------------
-- 3) ESNI_DOSIS - Maestro de dosis
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_DOSIS;
CREATE TABLE ESNI_DOSIS (
  id_dosis          INT AUTO_INCREMENT PRIMARY KEY,
  codigo            VARCHAR(40)  NOT NULL UNIQUE,           -- ej: D1, D2, D3, D4, DU, REF1, REF2
  nombre            VARCHAR(60)  NOT NULL,                  -- ej: 1ra Dosis, 2da Dosis, Dosis Unica
  orden             INT NOT NULL DEFAULT 0,
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_dosis_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maestro de dosis ESNI';

INSERT INTO ESNI_DOSIS (codigo, nombre, orden) VALUES
  ('D1',   '1ra Dosis',          1),
  ('D2',   '2da Dosis',          2),
  ('D3',   '3ra Dosis',          3),
  ('D4',   '4ta Dosis',          4),
  ('DU',   'Dosis Unica',        5),
  ('REF1', '1er Refuerzo',       6),
  ('REF2', '2do Refuerzo',       7),
  ('REF3', '3er Refuerzo',       8),
  ('TOT',  'Total',              9);

-- ---------------------------------------------------------------------
-- 4) ESNI_SECCION_REPORTE - Secciones del Reporte Operacional ESNI
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_SECCION_REPORTE;
CREATE TABLE ESNI_SECCION_REPORTE (
  id_seccion        INT AUTO_INCREMENT PRIMARY KEY,
  codigo            VARCHAR(10)  NOT NULL UNIQUE,           -- ej: A, B, C, H, H2, I, J, K, L, M, N, O, P, VPH
  titulo            VARCHAR(200) NOT NULL,                  -- ej: 'A. - MENORES DE 01 ANIO'
  descripcion       VARCHAR(300) NULL,
  layout            ENUM('lista','matriz_dosis','matriz_edad','total_uno','matriz_sexo') NOT NULL DEFAULT 'lista',
  -- lista: cada linea tiene 1 valor | matriz_dosis: columnas por dosis | matriz_edad: columnas por edad
  -- total_uno: 1 columna Total | matriz_sexo: columnas por sexo
  orden             INT NOT NULL DEFAULT 0,
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_sec_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Secciones del Reporte Operacional ESNI';

INSERT INTO ESNI_SECCION_REPORTE (codigo, titulo, descripcion, layout, orden) VALUES
  ('A',   'A. - MENORES DE 01 ANIO',
   'BCG, Hepatitis B, Antipolio (IPV), Pentavalente, Reacciones Adversas, Rotavirus, Neumococo, Influenza, Poblacion en Riesgo',
   'lista', 1),
  ('B',   'B. - DE 01 ANIO',
   'Neumococo 1 anio, SPR, Varicela, Influenza, Neumococo 12-23m, Antiamarilica, Hepatitis A, SPR 2da, Ref DPT, Ref APO, Vacunacion no oportuna',
   'lista', 2),
  ('C',   'C. - DE 02 ANOS - 04 ANOS',
   'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 4 anos, Refuerzo APO 4 anos',
   'lista', 3),
  ('H',   'H. - dT ADULTO EN MUJERES EN EDAD FERTIL DE 10 A 49 ANIOS',
   'Esquema dT en mujeres de 10 a 49 anos',
   'matriz_dosis', 4),
  ('H2',  'H2. - GESTANTES (TDAP)',
   'Esquema dT + TDAP en gestantes',
   'matriz_dosis', 5),
  ('I',   'I. - dT ADULTO: VARONES EN RIESGO',
   'Esquema dT en varones en riesgo',
   'matriz_dosis', 6),
  ('J',   'J. - INFLUENZA ESTACIONAL EN OTROS GRUPOS',
   'Influenza por grupo de edad y riesgo',
   'total_uno', 7),
  ('K',   'K. - SARAMPION - RUBEOLA',
   'Vacunacion SR en ninos/personas no vacunadas',
   'total_uno', 8),
  ('L',   'L. - POBLACION DE 05 A 59 ANIOS: VACUNACION CONTRA LA HEPATITIS B',
   'Hepatitis B en poblacion 5-59 anos',
   'matriz_dosis', 9),
  ('M',   'M. - ANTIAMARILICA',
   'Antiamarilica en poblacion no vacunada y viajeros a zonas endemicas',
   'total_uno', 10),
  ('N',   'N. - ANTIPOLIO ORAL',
   'Antipolio oral en poblacion no vacunada y viajeros',
   'matriz_edad', 11),
  ('O',   'O. - VARICELA',
   'Varicela por grupo de edad',
   'total_uno', 12),
  ('P',   'P. - NEUMOCOCO',
   'Neumococo en poblacion en riesgo',
   'total_uno', 13),
  ('VPH', 'VPH',
   'Virus del Papiloma Humano: femenino (1ra+2da) y masculino (unica)',
   'matriz_sexo', 14);

-- ---------------------------------------------------------------------
-- 5) ESNI_LINEA_REPORTE - Lineas dentro de cada seccion
--    Define cada fila del reporte (vacuna + dosis + grupo edad + texto)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_LINEA_REPORTE;
CREATE TABLE ESNI_LINEA_REPORTE (
  id_linea          INT AUTO_INCREMENT PRIMARY KEY,
  id_seccion        INT NOT NULL,
  orden             INT NOT NULL DEFAULT 0,
  etiqueta          VARCHAR(200) NOT NULL,                  -- texto que aparece en el reporte
  id_vacuna         INT NULL,                               -- vacuna asociada (nullable si solo etiqueta)
  id_dosis          INT NULL,                               -- dosis asociada (nullable)
  id_grupo_edad     INT NULL,                               -- grupo edad asociado (nullable)
  sexo              ENUM('M','F','A') NOT NULL DEFAULT 'A', -- M=mujeres, F=varones (historico MINSA), A=ambos
  -- Nota: MINSA usa 'M'=Mujer y 'F'=Femenino segun el contexto. Aqui: M=Mujer, F=Varon para alinear con id_genero
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_linea_seccion FOREIGN KEY (id_seccion) REFERENCES ESNI_SECCION_REPORTE(id_seccion) ON DELETE CASCADE,
  CONSTRAINT fk_linea_vacuna  FOREIGN KEY (id_vacuna)     REFERENCES ESNI_VACUNA(id_vacuna)       ON DELETE SET NULL,
  CONSTRAINT fk_linea_dosis   FOREIGN KEY (id_dosis)      REFERENCES ESNI_DOSIS(id_dosis)         ON DELETE SET NULL,
  CONSTRAINT fk_linea_ge      FOREIGN KEY (id_grupo_edad) REFERENCES ESNI_GRUPO_EDAD(id_grupo_edad) ON DELETE SET NULL,
  INDEX idx_linea_sec_ord (id_seccion, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lineas del Reporte Operacional ESNI';

-- Semilla: lineas extraidas del Excel "ReporteActividadesEsni2019.xlsx" hoja "RO" seccion 1 (A) y 2 (B) y 3 (C).
-- Para abreviar la semilla, se incluyen las lineas mas relevantes; el usuario podra agregar mas via web UI.

-- SECCION A (id_seccion = 1) - Menores de 1 anio
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (1, 1,  'BCG - 24 HORAS',                       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='BCG'),    (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'),    'A'),
  (1, 2,  'BCG - 28 DIAS',                        (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='BCG'),    (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'),    'A'),
  (1, 3,  'BCG - DE 01M A 11M 29D',               (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='BCG'),    (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  (1, 4,  'HEPATITIS VIRAL B - 12 HORAS',         (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='HVB'),    (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'),    'A'),
  (1, 5,  'HEPATITIS VIRAL B - 24 HORAS',         (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='HVB'),    (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'),    'A'),
  (1, 6,  'ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  (1, 7,  'ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  (1, 8,  'ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS',       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 9,  'PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='PENTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  (1, 10, 'PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='PENTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  (1, 11, 'PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='PENTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  (1, 12, 'RXN ADV A PENTAVALENTE - Dt(p) 04 Y 06 MESES - 2DA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='RXN_DTP'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 13, 'RXN ADV A PENTAVALENTE - Dt(p) 04 Y 06 MESES - 3RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='RXN_DTP'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 14, 'RXN ADV A PENTAVALENTE - HvB 04 Y 06 MESES - 2DA DOSIS',   (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='RXN_HVB'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 15, 'RXN ADV A PENTAVALENTE - HvB 04 Y 06 MESES - 3RA DOSIS',   (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='RXN_HVB'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 16, 'RXN ADV A PENTAVALENTE - HiB 04 Y 06 MESES - 2DA DOSIS',   (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='RXN_HIB'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 17, 'RXN ADV A PENTAVALENTE - HiB 04 Y 06 MESES - 3RA DOSIS',   (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='RXN_HIB'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A'),
  (1, 18, 'ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='ROTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  (1, 19, 'ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='ROTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  (1, 20, 'NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='NEUMO'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  (1, 21, 'NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='NEUMO'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  (1, 22, 'INFLUENZA - 06 Y 07 MESES - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  (1, 23, 'INFLUENZA - 06 Y 07 MESES - 2DA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  (1, 24, 'POBLACION EN RIESGO - IPV - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='RIESGO'),   'A'),
  (1, 25, 'POBLACION EN RIESGO - IPV - 2DA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='RIESGO'),   'A'),
  (1, 26, 'POBLACION EN RIESGO - IPV - 3RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='RIESGO'),   'A');

-- SECCION B (id_seccion = 2) - De 1 anio
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (2, 1,  '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS',     (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='NEUMO'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01A_1A11M'), 'A'),
  (2, 2,  '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS',           (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='SPR'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01A_1A11M'), 'A'),
  (2, 3,  '1A 11M 29D - VARICELA - 01 ANIO - 1RA DOSIS',      (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='VAR'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01A_1A11M'), 'A'),
  (2, 4,  '1A 11M 29D - NO COMPLETARON SU ESQUEMA - INFLUENZA - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01A_1A11M'), 'A'),
  (2, 5,  '1A 11M 29D - NO COMPLETARON SU ESQUEMA - INFLUENZA - 2DA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01A_1A11M'), 'A'),
  (2, 6,  '1A 11M 29D - DOSIS UNICA - INFLUENZA - DOSIS UNICA', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01A_1A11M'), 'A'),
  (2, 7,  '12 A 23 MESES - NEUMOCOCO (SOLO NO VAC. ANTERIORMENTE) - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='NEUMO'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='12_23M'), 'A'),
  (2, 8,  '12 A 23 MESES - NEUMOCOCO (SOLO NO VAC. ANTERIORMENTE) - 2DA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='NEUMO'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='12_23M'), 'A'),
  (2, 9,  '15 MESES - ANTIAMARILICA - DOSIS UNICA',           (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='AMA'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='15M'), 'A'),
  (2, 10, '15 MESES - HEPATITIS A - DOSIS UNICA',             (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='HEP_A'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='15M'), 'A'),
  (2, 11, '18 MESES - SPR - 2DA DOSIS',                       (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='SPR'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='18M'), 'A'),
  (2, 12, '18 MESES - REF. DPT - 1RA DOSIS',                  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DPT'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='REF1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='18M'), 'A'),
  (2, 13, '18 MESES - REF. APO - 1RA DOSIS',                  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='APO'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='REF1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='18M'), 'A');

-- SECCION C (id_seccion = 3) - De 2 a 4 anos
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (3, 1,  'INFLUENZA CON COMORBILIDAD - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='COMORB'),    'A'),
  (3, 2,  'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='INF'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='SIN_COMORB'), 'A'),
  (3, 3,  'NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='NEUMO'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='COMORB'),    'A'),
  (3, 4,  'ANTIAMARILICA - 1RA DOSIS',               (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='AMA'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'),    'A'),
  (3, 5,  'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 6,  'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 2DA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 7,  'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 3RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='IPV'),  (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 8,  'VACUNACION NO OPORTUNA - PENTAVALENTE - 1RA DOSIS',    (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='PENTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 9,  'VACUNACION NO OPORTUNA - PENTAVALENTE - 2DA DOSIS',    (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='PENTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 10, 'VACUNACION NO OPORTUNA - PENTAVALENTE - 3RA DOSIS',    (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='PENTA'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 11, 'VACUNACION NO OPORTUNA - SPR - 1RA DOSIS',             (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='SPR'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 12, 'VACUNACION NO OPORTUNA - SPR - 2DA DOSIS',             (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='SPR'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04A'), 'A'),
  (3, 13, 'VACUNACION NO OPORTUNA - BCG (CONTACTO DE TB P) - 1RA DOSIS', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='BCG_TB'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='CONTACTO_TB'), 'A'),
  (3, 14, 'VARICELA (CONTACTO INDICE)',                    (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='VAR'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='CONTACTO_VAR'), 'A'),
  (3, 15, 'REFUERZO DPT 04 ANOS - 2DA DOSIS',              (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DPT'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='REF2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='04A'), 'A'),
  (3, 16, 'REFUERZO ANTIPOLIO (APO) 04 ANOS - 2DA DOSIS',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='APO'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='REF2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='04A'), 'A');

-- SECCION H (id_seccion = 4) - dT Mujeres 10-49 anos (matriz: 3 dosis)
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (4, 1, 'dT 1ra - Mujeres 10 a 49 anos',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10_49A_M'), 'M'),
  (4, 2, 'dT 2da - Mujeres 10 a 49 anos',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10_49A_M'), 'M'),
  (4, 3, 'dT 3ra - Mujeres 10 a 49 anos',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10_49A_M'), 'M');

-- SECCION H2 (id_seccion = 5) - Gestantes TDAP (matriz: dT 1-3 + TDAP)
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (5, 1, 'dT 1ra - Gestantes',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='GEST'), 'M'),
  (5, 2, 'dT 2da - Gestantes',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='GEST'), 'M'),
  (5, 3, 'dT 3ra - Gestantes',  (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'),   (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='GEST'), 'M'),
  (5, 4, 'TDAP - Gestantes',    (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='TDAP'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='GEST'), 'M');

-- SECCION I (id_seccion = 6) - dT Varones en riesgo
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (6, 1, 'dT 1ra - Varones en riesgo', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10A_MAS_V'), 'F'),
  (6, 2, 'dT 2da - Varones en riesgo', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10A_MAS_V'), 'F'),
  (6, 3, 'dT 3ra - Varones en riesgo', (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='DT'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D3'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10A_MAS_V'), 'F');

-- SECCION VPH (id_seccion = 14) - VPH (matriz por sexo)
INSERT INTO ESNI_LINEA_REPORTE (id_seccion, orden, etiqueta, id_vacuna, id_dosis, id_grupo_edad, sexo) VALUES
  (14, 1, 'VPH 1ra Dosis - Femenino',           (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='VPH'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D1'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10_49A_M'), 'M'),
  (14, 2, 'VPH 2da Dosis - Femenino',           (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='VPH'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='D2'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10_49A_M'), 'M'),
  (14, 3, 'VPH Dosis Unica - Masculino',        (SELECT id_vacuna FROM ESNI_VACUNA WHERE codigo='VPH'), (SELECT id_dosis FROM ESNI_DOSIS WHERE codigo='DU'), (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='10A_MAS_V'), 'F');

-- ---------------------------------------------------------------------
-- 6) ESNI_REGLA - Reglas de mapeo cod_item + valor_lab + edad -> linea
--    Cada linea puede tener 1 o mas reglas (un cod_item puede mapear
--    a varias dosis segun el valor_lab).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_REGLA;
CREATE TABLE ESNI_REGLA (
  id_regla          INT AUTO_INCREMENT PRIMARY KEY,
  id_linea          INT NOT NULL,                            -- linea del reporte a la que aplica
  cod_item          VARCHAR(20) NOT NULL,                    -- codigo de item HIS (ej: 90585, Z232)
  valor_lab         VARCHAR(20) NULL,                        -- valor del laboratorio (ej: 1, 01, D1, DU). NULL=cualquiera
  id_grupo_edad     INT NULL,                                -- grupo de edad requerido (NULL=sin restriccion)
  sexo              ENUM('M','F','A') NOT NULL DEFAULT 'A',  -- restriccion de sexo
  aniomes_min       VARCHAR(6) NULL,                         -- filtro aniomes minimo (YYYYMM) - ej: 202301
  aniomes_max       VARCHAR(6) NULL,                         -- filtro aniomes maximo - ej: 202212
  requiere_riesgo        TINYINT(1) NOT NULL DEFAULT 0,           -- 1=solo si id_gruporiesgo=2 (poblacion en riesgo)
  excluye_riesgo         TINYINT(1) NOT NULL DEFAULT 0,           -- 1=solo si NO es poblacion en riesgo
  requiere_comorbilidad  TINYINT(1) NOT NULL DEFAULT 0,           -- 1=solo si el paciente tiene otro registro con cod_item=9999 (con comorbilidad)
  excluye_comorbilidad   TINYINT(1) NOT NULL DEFAULT 0,           -- 1=solo si el paciente NO tiene registro con cod_item=9999 (sin comorbilidad)
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_regla_linea FOREIGN KEY (id_linea) REFERENCES ESNI_LINEA_REPORTE(id_linea) ON DELETE CASCADE,
  CONSTRAINT fk_regla_ge    FOREIGN KEY (id_grupo_edad) REFERENCES ESNI_GRUPO_EDAD(id_grupo_edad) ON DELETE SET NULL,
  INDEX idx_regla_coditem (cod_item),
  INDEX idx_regla_linea (id_linea),
  INDEX idx_regla_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Reglas de mapeo ESNI (cod_item + valor_lab + edad -> linea)';

-- Semilla: reglas extraidas del Stored Procedure usp_TRAMA_BASE_ESNI_RPT_01_REPORTE_A_2019
-- (Archivo 03_StoredProcedure_TRAMA_BASE.txt, lineas 45-98).
-- Solo se incluyen las reglas de la seccion A como ejemplo; el usuario puede agregar
-- mas reglas via la pagina esni_config.php para las secciones B, C, etc.

-- BCG - 24 horas (cod_item 90585 o Z232, sin valor_lab o DU, edad=1 dia)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), '90585', NULL, (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), '90585', 'DU', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), '90585', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), '90585', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), '90585', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), 'Z232',  NULL, (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), 'Z232',  'DU', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), 'Z232',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), 'Z232',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 24 HORAS' AND id_seccion=1), 'Z232',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A');

-- BCG - 28 dias
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 28 DIAS' AND id_seccion=1), '90585', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 28 DIAS' AND id_seccion=1), '90585', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 28 DIAS' AND id_seccion=1), '90585', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 28 DIAS' AND id_seccion=1), 'Z232',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 28 DIAS' AND id_seccion=1), 'Z232',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - 28 DIAS' AND id_seccion=1), 'Z232',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='28D'), 'A');

-- BCG - 01m a 11m 29d
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - DE 01M A 11M 29D' AND id_seccion=1), '90585', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - DE 01M A 11M 29D' AND id_seccion=1), '90585', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - DE 01M A 11M 29D' AND id_seccion=1), '90585', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - DE 01M A 11M 29D' AND id_seccion=1), 'Z232',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - DE 01M A 11M 29D' AND id_seccion=1), 'Z232',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='BCG - DE 01M A 11M 29D' AND id_seccion=1), 'Z232',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A');

-- Hepatitis B - 12 horas
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 12 HORAS' AND id_seccion=1), '90744', NULL, (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 12 HORAS' AND id_seccion=1), '90744', 'DU', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 12 HORAS' AND id_seccion=1), 'Z246',  NULL, (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 12 HORAS' AND id_seccion=1), 'Z246',  'DU', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A');

-- Hepatitis B - 24 horas
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 24 HORAS' AND id_seccion=1), '90744', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 24 HORAS' AND id_seccion=1), '90744', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 24 HORAS' AND id_seccion=1), '90744', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 24 HORAS' AND id_seccion=1), 'Z246',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 24 HORAS' AND id_seccion=1), 'Z246',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='HEPATITIS VIRAL B - 24 HORAS' AND id_seccion=1), 'Z246',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='24H'), 'A');

-- IPV - 1ra dosis (cod_item 90713, valor_lab 1, edad 02-11m)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, excluye_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90713', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90713', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90713', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1);

-- IPV - 2da dosis
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, excluye_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90713', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90713', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90713', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1);

-- IPV - 3ra dosis (cod_item 90712 hasta 202212, 90713 desde 202301)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, aniomes_max, excluye_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS' AND id_seccion=1), '90712', '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A', '202212', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS' AND id_seccion=1), '90712', '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A', '202212', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS' AND id_seccion=1), '90712', 'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A', '202212', 1);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, aniomes_min, excluye_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS' AND id_seccion=1), '90713', '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A', '202301', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS' AND id_seccion=1), '90713', '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A', '202301', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS' AND id_seccion=1), '90713', 'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_11M'), 'A', '202301', 1);

-- Pentavalente - 1ra, 2da, 3ra dosis (cod_item 90723, Z276, 90722)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), '90723', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), '90723', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), '90723', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), 'Z276',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), 'Z276',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), 'Z276',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), '90722', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), '90722', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS' AND id_seccion=1), '90722', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A');

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), '90723', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), '90723', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), '90723', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), 'Z276',  '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), 'Z276',  '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), 'Z276',  'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), '90722', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), '90722', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS' AND id_seccion=1), '90722', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A');

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), '90723', '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), '90723', '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), '90723', 'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), 'Z276',  '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), 'Z276',  '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), 'Z276',  'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), '90722', '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), '90722', '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS' AND id_seccion=1), '90722', 'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='01_11M'), 'A');

-- Rotavirus (cod_item 90681 o Z268, valor_lab 1 o 2, edad 02-07 meses)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90681', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90681', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90681', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), 'Z268',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), 'Z268',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), 'Z268',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A');

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90681', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90681', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90681', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), 'Z268',  '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), 'Z268',  '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), 'Z268',  'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_07M'), 'A');

-- Neumococo (cod_item 90669, Z238, 90670 - 02 y 04 meses)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90669', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90669', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90669', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), 'Z238',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), 'Z238',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), 'Z238',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90670', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90670', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS' AND id_seccion=1), '90670', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A');

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90669', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90669', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90669', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), 'Z238',  '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), 'Z238',  '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), 'Z238',  'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90670', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90670', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS' AND id_seccion=1), '90670', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A');

-- Influenza (cod_item 90657, Z2511, 90687 - 6 y 7 meses)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), '90657', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), '90657', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), '90657', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), 'Z2511', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), 'Z2511', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), 'Z2511', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), '90687', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), '90687', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 1RA DOSIS' AND id_seccion=1), '90687', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A');

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), '90657', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), '90657', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), '90657', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), 'Z2511', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), 'Z2511', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), 'Z2511', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), '90687', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), '90687', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A'),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='INFLUENZA - 06 Y 07 MESES - 2DA DOSIS' AND id_seccion=1), '90687', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='06_07M'), 'A');

-- IPV Poblacion en riesgo (cod_item 90713 o Z240, requiere_riesgo=1)
INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, requiere_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 1RA DOSIS' AND id_seccion=1), '90713', '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 1RA DOSIS' AND id_seccion=1), '90713', '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 1RA DOSIS' AND id_seccion=1), '90713', 'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 1RA DOSIS' AND id_seccion=1), 'Z240',  '1',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 1RA DOSIS' AND id_seccion=1), 'Z240',  '01', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 1RA DOSIS' AND id_seccion=1), 'Z240',  'D1', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, requiere_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 2DA DOSIS' AND id_seccion=1), '90713', '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 2DA DOSIS' AND id_seccion=1), '90713', '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 2DA DOSIS' AND id_seccion=1), '90713', 'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 2DA DOSIS' AND id_seccion=1), 'Z240',  '2',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 2DA DOSIS' AND id_seccion=1), 'Z240',  '02', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 2DA DOSIS' AND id_seccion=1), 'Z240',  'D2', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1);

INSERT INTO ESNI_REGLA (id_linea, cod_item, valor_lab, id_grupo_edad, sexo, requiere_riesgo) VALUES
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 3RA DOSIS' AND id_seccion=1), '90713', '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 3RA DOSIS' AND id_seccion=1), '90713', '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 3RA DOSIS' AND id_seccion=1), '90713', 'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 3RA DOSIS' AND id_seccion=1), 'Z240',  '3',  (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 3RA DOSIS' AND id_seccion=1), 'Z240',  '03', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1),
  ((SELECT id_linea FROM ESNI_LINEA_REPORTE WHERE etiqueta='POBLACION EN RIESGO - IPV - 3RA DOSIS' AND id_seccion=1), 'Z240',  'D3', (SELECT id_grupo_edad FROM ESNI_GRUPO_EDAD WHERE codigo='02_04M'), 'A', 1);

-- ---------------------------------------------------------------------
-- 7) ESNI_PARAMETRO - Parametros generales del modulo ESNI
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS ESNI_PARAMETRO;
CREATE TABLE ESNI_PARAMETRO (
  clave             VARCHAR(60) PRIMARY KEY,
  valor             TEXT NULL,
  descripcion       VARCHAR(200) NULL,
  fecha_actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Parametros del modulo ESNI';

INSERT INTO ESNI_PARAMETRO (clave, valor, descripcion) VALUES
  ('tabla_origen',            'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO', 'Tabla MySQL origen de los datos HIS consolidados'),
  ('columna_cod_item',        'Codigo_Item',           'Columna con el codigo de item (vacuna)'),
  ('columna_valor_lab',       'Valor_Lab',             'Columna con el valor de laboratorio (dosis). Si no existe, se asume NULL'),
  ('columna_aniomes',         'AnioMes',               'Columna con aniomes YYYYMM. Si no existe, se calcula de Anio+Mes'),
  ('columna_edad_reg',        'Edad_Reg',              'Columna con edad numerica'),
  ('columna_tip_edad',        'Tipo_Edad_Reg',         'Columna con tipo edad (D/M/A)'),
  ('columna_grupo_edad',      'Grupo_Edad',            'Columna con grupo de edad textual'),
  ('columna_sexo',            'Id_Genero',             'Columna con sexo (M/F)'),
  ('columna_id_paciente',     'Id_Paciente',           'Columna con id de paciente'),
  ('columna_establecimiento', 'Nombre_Establecimiento','Columna con nombre del establecimiento'),
  ('columna_departamento',    'Departamento_Establecimiento', 'Columna con departamento'),
  ('columna_anio',            'Anio',                  'Columna con anio'),
  ('columna_mes',             'Mes',                   'Columna con mes (1-12)'),
  ('columna_id_cita',         'Id_Cita',               'Columna con id de cita'),
  ('columna_profesional',     'Id_Personal',           'Columna con id del profesional (Personal de salud). Nota: en la tabla consolidada HIS MINSA esta columna se llama Id_Personal, no Id_Profesional'),
  ('columna_renaes',          'Renaes',                'Columna con codigo Renaes del establecimiento'),
  ('columna_id_gruporiesgo',  'Id_GrupoRiesgo',        'Columna con grupo de riesgo (2=riesgo)'),
  ('columna_rownnum_lab',     'I_ROWNUM_LAB',          'Columna con numero de fila (deduplicacion). Si no existe, se asume 1'),
  ('version_esquema',         '1.1.0',                 'Version del esquema ESNI (1.1.0 = con deteccion mejorada de Id_Personal y Tipo_Edad)'),
  ('modo_estricto',           '0',                     '1=exige todas las columnas; 0=tolerante a columnas faltantes');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- REGLAS ADICIONALES PARA CODIGOS DE ITEM HIS REALES
-- ---------------------------------------------------------------------
-- Las reglas anteriores cubren la Seccion A (menores de 1 anio) usando
-- codigos de item HIS historicos/teoricamente validos (90585, 90669,
-- 90681, 90687, 90723, 90744, Z2xx).
--
-- Sin embargo, en la tabla consolidada real T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
-- los codigos de item que aparecen con mayor frecuencia son distintos:
--
--   90658  - Influenza (3+ anos)           [~3000 filas, ~57% del total]
--   90670  - Neumococo                      [~480 filas]
--   90657  - Influenza pediatrica           [~340 filas]
--   90746  - Hepatitis B adulto             [~300 filas]
--   90707  - MMR / SPR                      [~200 filas]
--   90717  - Fiebre Amarilla                [~200 filas]
--   90722  - Pentavalente                   [~130 filas]
--   90713  - IPV                             [~130 filas]
--   90649  - VPH                            [~100 filas]
--   90633.01 - Hepatitis A pediatrica      [~30 filas]
--   90714  - dT adulto                       [~70 filas]
--   90715  - TDAP                            [~50 filas]
--   90716  - Varicela                        [~40 filas]
--
-- Para evitar que el reporte ESNI salga vacio, debe ejecutar tambien:
--   Database/install_esni_extra_rules.sql
-- o usar el asistente web: install_esni_extra.php
-- =====================================================================

-- =====================================================================
-- FIN DEL SCRIPT
-- Tablas creadas: 7 (ESNI_VACUNA, ESNI_GRUPO_EDAD, ESNI_DOSIS,
-- ESNI_SECCION_REPORTE, ESNI_LINEA_REPORTE, ESNI_REGLA, ESNI_PARAMETRO)
-- Reglas sembradas: ~137 reglas para seccion A (menores de 1 anio)
-- Para cobertura completa de los datos reales, ejecutar tambien:
--   Database/install_esni_extra_rules.sql
-- El usuario podra agregar mas reglas via esni_config.php
-- =====================================================================

-- =====================================================================
-- MIGRACION: Comorbilidad (Codigo_Item = 9999)
-- ---------------------------------------------------------------------
-- Ejecutar este bloque UNA vez sobre instalaciones ya existentes para
-- anadir el soporte de Comorbilidad a la tabla ESNI_REGLA.
-- Es seguro re-ejecutarlo: las columnas se agregan solo si no existen.
-- =====================================================================
ALTER TABLE ESNI_REGLA
  ADD COLUMN IF NOT EXISTS requiere_comorbilidad TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1=solo si el paciente tiene otro registro con cod_item=9999 (con comorbilidad)'
  AFTER excluye_riesgo;

ALTER TABLE ESNI_REGLA
  ADD COLUMN IF NOT EXISTS excluye_comorbilidad TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1=solo si el paciente NO tiene registro con cod_item=9999 (sin comorbilidad)'
  AFTER requiere_comorbilidad;
