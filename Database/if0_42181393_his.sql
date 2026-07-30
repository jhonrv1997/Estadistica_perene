-- Base de datos: `if0_42181393_his`

-- Estructura de tabla para la tabla `CONTROL_CALIDAD_OBSERVACIONES`
CREATE TABLE `CONTROL_CALIDAD_OBSERVACIONES` (
  `id_observacion` int(11) NOT NULL,
  `anio` varchar(4) NOT NULL,
  `mes` varchar(2) NOT NULL,
  `fecha_observacion` date DEFAULT NULL,
  `id_establecimiento` int(11) DEFAULT NULL,
  `nombre_establecimiento` varchar(150) DEFAULT NULL,
  `tipo_observacion` varchar(50) DEFAULT NULL COMMENT 'Ej: Inconsistencia, Dato Faltante, Duplicidad, Formato, Codigo Invalido',
  `severidad` varchar(20) DEFAULT 'MEDIA' COMMENT 'BAJA, MEDIA, ALTA, CRITICA',
  `campo_afectado` varchar(100) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `id_cita` varchar(50) DEFAULT NULL COMMENT 'Referencia a la cita afectada en el consolidado',
  `codigo_item` varchar(15) DEFAULT NULL,
  `numero_documento_paciente` varchar(15) DEFAULT NULL,
  `numero_documento_personal` varchar(15) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'PENDIENTE' COMMENT 'PENDIENTE, EN_PROCESO, ATENDIDA, CERRADA',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `usuario_registro` varchar(100) DEFAULT NULL,
  `usuario_cierre` varchar(100) DEFAULT NULL,
  `solucion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Observaciones de control de calidad sobre los datos consolidados';

--
-- Estructura de tabla para la tabla `CONVENIO_FED_AVANCE`
--

CREATE TABLE `CONVENIO_FED_AVANCE` (
  `id_avance` int(11) NOT NULL,
  `id_indicador` int(11) NOT NULL,
  `anio` varchar(4) NOT NULL,
  `trimestre` varchar(2) DEFAULT NULL,
  `mes` varchar(2) DEFAULT NULL,
  `valor_numerador` decimal(12,2) DEFAULT NULL,
  `valor_denominador` decimal(12,2) DEFAULT NULL,
  `valor_resultado` decimal(10,2) DEFAULT NULL,
  `porcentaje_avance` decimal(5,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `usuario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Avance periodico de indicadores FED';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CONVENIO_FED_INDICADORES`
--

CREATE TABLE `CONVENIO_FED_INDICADORES` (
  `id_indicador` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'FED-01 ... FED-07',
  `nombre` varchar(300) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT NULL,
  `meta_anual` decimal(10,2) DEFAULT NULL,
  `peso_ponderado` decimal(5,2) DEFAULT NULL COMMENT 'Peso porcentual del indicador en el FED',
  `periodo` varchar(20) DEFAULT 'TRIMESTRAL',
  `responsable` varchar(150) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `estado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Indicadores del Convenio FED - Fondo de Estimulo al Desempeno';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CONVENIO_GESTION_AVANCE`
--

CREATE TABLE `CONVENIO_GESTION_AVANCE` (
  `id_avance` int(11) NOT NULL,
  `id_indicador` int(11) NOT NULL,
  `anio` varchar(4) NOT NULL,
  `mes` varchar(2) DEFAULT NULL COMMENT 'Mes del reporte (NULL si es acumulado anual)',
  `trimestre` varchar(2) DEFAULT NULL COMMENT 'T1, T2, T3, T4',
  `valor_numerador` decimal(12,2) DEFAULT NULL,
  `valor_denominador` decimal(12,2) DEFAULT NULL,
  `valor_resultado` decimal(10,2) DEFAULT NULL COMMENT 'Resultado calculado del indicador',
  `porcentaje_avance` decimal(5,2) DEFAULT NULL COMMENT 'Porcentaje de avance hacia la meta',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `usuario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Avance periodico de indicadores del Convenio de Gestion';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CONVENIO_GESTION_INDICADORES`
--

CREATE TABLE `CONVENIO_GESTION_INDICADORES` (
  `id_indicador` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'Codigo del indicador (CG-01, CG-02, ...)',
  `nombre` varchar(300) NOT NULL COMMENT 'Nombre descriptivo del indicador',
  `descripcion` text DEFAULT NULL COMMENT 'Descripcion detallada',
  `unidad_medida` varchar(50) DEFAULT NULL COMMENT 'Porcentaje, Tasa, Ratio, Numero',
  `meta_anual` decimal(10,2) DEFAULT NULL COMMENT 'Meta anual del indicador',
  `periodo` varchar(20) DEFAULT 'ANUAL' COMMENT 'MENSUAL, TRIMESTRAL, ANUAL',
  `responsable` varchar(150) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `estado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Indicadores del Convenio de Gestion MINSA-GORE 2026';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_DOSIS`
--

CREATE TABLE `ESNI_DOSIS` (
  `id_dosis` int(11) NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maestro de dosis ESNI';

--
-- Volcado de datos para la tabla `ESNI_DOSIS`
--

INSERT INTO `ESNI_DOSIS` (`id_dosis`, `codigo`, `nombre`, `orden`, `activo`) VALUES
(1, 'D1', '1ra Dosis', 1, 1),
(2, 'D2', '2da Dosis', 2, 1),
(3, 'D3', '3ra Dosis', 3, 1),
(4, 'D4', '4ta Dosis', 4, 1),
(5, 'DU', 'Dosis Unica', 5, 1),
(6, 'REF1', '1er Refuerzo', 6, 1),
(7, 'REF2', '2do Refuerzo', 7, 1),
(8, 'REF3', '3er Refuerzo', 8, 1),
(9, 'TOT', 'Total', 9, 1),
(10, 'DA', 'Dosis Adicional', 10, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_GRUPO_EDAD`
--

CREATE TABLE `ESNI_GRUPO_EDAD` (
  `id_grupo_edad` int(11) NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `tipo_edad` enum('D','M','A','R') NOT NULL DEFAULT 'A',
  `edad_min` int(11) DEFAULT NULL,
  `edad_max` int(11) DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Grupos de edad ESNI';

--
-- Volcado de datos para la tabla `ESNI_GRUPO_EDAD`
--

INSERT INTO `ESNI_GRUPO_EDAD` (`id_grupo_edad`, `codigo`, `nombre`, `tipo_edad`, `edad_min`, `edad_max`, `descripcion`, `activo`) VALUES
(1, '24H', '24 horas', 'D', 0, 1, 'Recien nacido - 24 horas de vida', 1),
(2, '28D', '28 dias', 'D', 2, 28, 'De 2 a 28 dias', 1),
(3, '01_11M', '01m a 11m 29d', 'M', 1, 11, 'Menores de 1 anio (1 a 11 meses)', 1),
(4, '02_07M', '02 a 07 meses', 'M', 2, 7, 'Lactantes 2 a 7 meses (Rotavirus)', 1),
(5, '02_04M', '02 y 04 meses', 'M', 2, 4, 'Lactantes 2 y 4 meses', 1),
(6, '06_07M', '06 y 07 meses', 'M', 6, 7, 'Lactantes 6 y 7 meses (Influenza)', 1),
(7, '06_11M', '06 a 11 meses', 'M', 6, 11, 'Lactantes 6 a 11 meses', 1),
(8, '12_23M', '12 a 23 meses', 'M', 12, 23, 'De 12 a 23 meses', 1),
(9, '01A_1A11M', '01 anio (1a 11m 29d)', 'A', 1, 1, 'Ninos de 1 anio', 1),
(10, '15M', '15 meses', 'A', 1, 1, 'Ninos de 15 meses', 1),
(11, '18M', '18 meses', 'A', 1, 1, 'Ninos de 18 meses', 1),
(12, '02_04A', '02 a 04 anos', 'A', 2, 4, 'Ninos de 2 a 4 anos', 1),
(13, '02A', '02 anos', 'A', 2, 2, 'Ninos de 2 anos', 1),
(14, '03A', '03 anos', 'A', 3, 3, 'Ninos de 3 anos', 1),
(15, '04A', '04 anos', 'A', 4, 4, 'Ninos de 4 anos', 1),
(16, '05_59A', '05 a 59 anos', 'A', 5, 59, 'Poblacion de 5 a 59 anos', 1),
(17, '10_49A_M', '10 a 49 anos (Mujeres)', 'A', 10, 49, 'Mujeres en edad fertil 10 a 49 anos', 1),
(18, '10A_MAS_V', '10 anos a mas (Varones)', 'A', 10, 200, 'Varones en riesgo 10 anos a mas', 1),
(19, 'GEST', 'Gestantes', 'R', NULL, NULL, 'Mujeres gestantes', 1),
(20, 'RIESGO', 'Poblacion en riesgo', 'R', NULL, NULL, 'Poblacion con factores de riesgo', 1),
(21, 'COMORB', 'Con comorbilidad', 'R', NULL, NULL, 'Poblacion con comorbilidad', 1),
(22, 'SIN_COMORB', 'Sin comorbilidad', 'R', NULL, NULL, 'Poblacion sin comorbilidad', 1),
(23, 'CONTACTO_TB', 'Contacto TB', 'R', NULL, NULL, 'Contactos de pacientes TB', 1),
(24, 'CONTACTO_VAR', 'Contacto indice Varicela', 'R', NULL, NULL, 'Contacto indice de Varicela', 1),
(25, 'VIAJA_END', 'Viaja a zonas endemicas', 'R', NULL, NULL, 'Persona que viaja a zonas endemicas', 1),
(26, 'NO_VAC', 'Poblacion no vacunada', 'R', NULL, NULL, 'Poblacion no vacunada previamente', 1),
(27, '60A_MAS', '60 anos a mas', 'A', 60, 200, 'Adultos mayores de 60 anos', 1),
(28, '05_07A', '05 y 07 Anios', 'A', 5, 7, NULL, 1),
(29, '05_09A', '05 y 09 anios', 'A', 5, 9, NULL, 1),
(30, '10_11A', '10 y 11 anios', 'A', 10, 11, NULL, 1),
(31, '12_17A', '12 y 17 anios', 'A', 12, 17, NULL, 1),
(32, '18_29A', '18 y 29 anios', 'A', 18, 29, NULL, 1),
(33, '30_49A', '30 y 49 anios', 'A', 30, 49, NULL, 1),
(34, '50_59A', '50 y 59 anios', 'A', 50, 59, NULL, 1),
(35, '30_59A', '30 y 59 anios', 'A', 30, 59, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_LINEA_REPORTE`
--

CREATE TABLE `ESNI_LINEA_REPORTE` (
  `id_linea` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `etiqueta` varchar(200) NOT NULL,
  `id_vacuna` int(11) DEFAULT NULL,
  `id_dosis` int(11) DEFAULT NULL,
  `id_grupo_edad` int(11) DEFAULT NULL,
  `sexo` enum('M','F','A') NOT NULL DEFAULT 'A',
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lineas del Reporte Operacional ESNI';

--
-- Volcado de datos para la tabla `ESNI_LINEA_REPORTE`
--

INSERT INTO `ESNI_LINEA_REPORTE` (`id_linea`, `id_seccion`, `orden`, `etiqueta`, `id_vacuna`, `id_dosis`, `id_grupo_edad`, `sexo`, `activo`) VALUES
(1, 1, 1, 'BCG - 24 HORAS', 1, 5, 1, 'A', 1),
(2, 1, 2, 'BCG - 28 DIAS', 1, 5, 2, 'A', 1),
(3, 1, 3, 'BCG - DE 01M A 11M 29D', 1, 5, 3, 'A', 1),
(4, 1, 4, 'HEPATITIS VIRAL B - 12 HORAS', 2, 5, 1, 'A', 1),
(5, 1, 5, 'HEPATITIS VIRAL B - 24 HORAS', 2, 5, 1, 'A', 1),
(6, 1, 6, 'ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS', 3, 1, 5, 'A', 1),
(7, 1, 7, 'ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS', 3, 2, 5, 'A', 1),
(8, 1, 8, 'ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS', 3, 3, 7, 'A', 1),
(9, 1, 9, 'PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS', 5, 1, 3, 'A', 1),
(10, 1, 10, 'PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS', 5, 2, 3, 'A', 1),
(11, 1, 11, 'PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS', 5, 3, 3, 'A', 1),
(12, 1, 12, 'RXN ADV A PENTAVALENTE - Dt(p) 04 Y 06 MESES - 2DA DOSIS', 6, 2, 7, 'A', 1),
(13, 1, 13, 'RXN ADV A PENTAVALENTE - Dt(p) 04 Y 06 MESES - 3RA DOSIS', 6, 3, 7, 'A', 1),
(14, 1, 14, 'RXN ADV A PENTAVALENTE - HvB 04 Y 06 MESES - 2DA DOSIS', 7, 2, 7, 'A', 1),
(15, 1, 15, 'RXN ADV A PENTAVALENTE - HvB 04 Y 06 MESES - 3RA DOSIS', 7, 3, 7, 'A', 1),
(16, 1, 16, 'RXN ADV A PENTAVALENTE - HiB 04 Y 06 MESES - 2DA DOSIS', 8, 2, 7, 'A', 1),
(17, 1, 17, 'RXN ADV A PENTAVALENTE - HiB 04 Y 06 MESES - 3RA DOSIS', 8, 3, 7, 'A', 1),
(18, 1, 18, 'ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS', 9, 1, 4, 'A', 1),
(19, 1, 19, 'ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS', 9, 2, 4, 'A', 1),
(20, 1, 20, 'NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS', 10, 1, 5, 'A', 1),
(21, 1, 21, 'NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS', 10, 2, 5, 'A', 1),
(22, 1, 22, 'INFLUENZA - 06 Y 07 MESES - 1RA DOSIS', 11, 1, 6, 'A', 1),
(23, 1, 23, 'INFLUENZA - 06 Y 07 MESES - 2DA DOSIS', 11, 2, 6, 'A', 1),
(24, 1, 24, 'POBLACION EN RIESGO - IPV - 1RA DOSIS', 3, 1, 20, 'A', 1),
(25, 1, 25, 'POBLACION EN RIESGO - IPV - 2DA DOSIS', 3, 2, 20, 'A', 1),
(26, 1, 26, 'POBLACION EN RIESGO - IPV - 3RA DOSIS', 3, 3, 20, 'A', 1),
(27, 2, 1, '1A 11M 29D - NEUMOCOCO - 01 ANIO - 3RA DOSIS', 10, 3, 9, 'A', 1),
(28, 2, 2, '1A 11M 29D - SPR - 01 ANIO - 1RA DOSIS', 12, 1, 9, 'A', 1),
(29, 2, 3, '1A 11M 29D - VARICELA - 01 ANIO - 1RA DOSIS', 14, 1, 9, 'A', 1),
(30, 2, 4, '1A 11M 29D - NO COMPLETARON SU ESQUEMA - INFLUENZA - 1RA DOSIS', 11, 1, 9, 'A', 1),
(31, 2, 5, '1A 11M 29D - NO COMPLETARON SU ESQUEMA - INFLUENZA - 2DA DOSIS', 11, 2, 9, 'A', 1),
(32, 2, 6, '1A 11M 29D - DOSIS UNICA - INFLUENZA - DOSIS UNICA', 11, 5, 9, 'A', 1),
(33, 2, 7, '12 A 23 MESES - NEUMOCOCO (SOLO NO VAC. ANTERIORMENTE) - 1RA DOSIS', 10, 1, 8, 'A', 1),
(34, 2, 8, '12 A 23 MESES - NEUMOCOCO (SOLO NO VAC. ANTERIORMENTE) - 2DA DOSIS', 10, 2, 8, 'A', 1),
(35, 2, 9, '15 MESES - ANTIAMARILICA - DOSIS UNICA', 15, 5, 10, 'A', 1),
(36, 2, 10, '15 MESES - HEPATITIS A - DOSIS UNICA', 16, 5, 10, 'A', 1),
(37, 2, 11, '18 MESES - SPR - 2DA DOSIS', 12, 2, 11, 'A', 1),
(38, 2, 12, '18 MESES - REF. DPT - 1RA DOSIS', 17, 6, 11, 'A', 1),
(39, 2, 13, '18 MESES - REF. APO - 1RA DOSIS', 4, 6, 11, 'A', 1),
(40, 3, 1, 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS', 11, 1, 13, 'A', 1),
(41, 3, 2, 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS', 11, 1, 13, 'A', 1),
(42, 3, 3, 'NEUMOCOCO CON COMORBILIDAD - 1RA DOSIS', 10, 1, 21, 'A', 1),
(43, 3, 4, 'ANTIAMARILICA - 1RA DOSIS', 15, 1, 13, 'A', 1),
(44, 3, 5, 'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 1RA DOSIS', 3, 1, 13, 'A', 1),
(45, 3, 6, 'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 2DA DOSIS', 3, 2, 13, 'A', 1),
(46, 3, 7, 'VACUNACION NO OPORTUNA - ANTIPOLIO - IPV - 3RA DOSIS', 3, 3, 13, 'A', 1),
(47, 3, 8, 'VACUNACION NO OPORTUNA - PENTAVALENTE - 1RA DOSIS', 5, 1, 13, 'A', 1),
(48, 3, 9, 'VACUNACION NO OPORTUNA - PENTAVALENTE - 2DA DOSIS', 5, 2, 13, 'A', 1),
(49, 3, 10, 'VACUNACION NO OPORTUNA - PENTAVALENTE - 3RA DOSIS', 5, 3, 13, 'A', 1),
(50, 3, 11, 'VACUNACION NO OPORTUNA - SPR - 1RA DOSIS', 12, 1, 13, 'A', 1),
(51, 3, 12, 'VACUNACION NO OPORTUNA - SPR - 2DA DOSIS', 12, 2, 13, 'A', 1),
(52, 3, 13, 'VACUNACION NO OPORTUNA - BCG (CONTACTO DE TB P) - 1RA DOSIS', 21, 1, 23, 'A', 1),
(53, 3, 14, 'VARICELA (CONTACTO INDICE)', 14, 5, 24, 'A', 1),
(54, 3, 15, 'REFUERZO PENTAVALENTE 02 ANOS - 1RA DOSIS', 5, 6, 13, 'A', 1),
(55, 3, 16, 'REFUERZO ANTIPOLIO (IPV) 02 ANOS - 1RA DOSIS', 3, 6, 13, 'A', 1),
(56, 4, 1, 'dT 1ra - Mujeres 10 a 49 anos', 18, 1, 17, 'F', 1),
(57, 4, 2, 'dT 2da - Mujeres 10 a 49 anos', 18, 2, 17, 'F', 1),
(58, 4, 3, 'dT 3ra - Mujeres 10 a 49 anos', 18, 3, 17, 'F', 1),
(59, 5, 1, 'dT 1ra - Gestantes', 18, 1, 19, 'F', 1),
(60, 5, 2, 'dT 2da - Gestantes', 18, 2, 19, 'F', 1),
(61, 5, 3, 'dT 3ra - Gestantes', 18, 3, 19, 'F', 1),
(62, 5, 4, 'TDAP - Gestantes', 19, 1, 19, 'F', 1),
(63, 6, 1, 'dT 1ra - Varones en riesgo', 18, 1, 18, 'M', 1),
(64, 6, 2, 'dT 2da - Varones en riesgo', 18, 2, 18, 'M', 1),
(65, 6, 3, 'dT 3ra - Varones en riesgo', 18, 3, 18, 'M', 1),
(66, 14, 1, 'VPH 1ra Dosis - Femenino', 20, 1, 17, 'F', 1),
(67, 14, 2, 'VPH 2da Dosis - Femenino', 20, 2, 17, 'F', 1),
(68, 14, 3, 'VPH Dosis Unica - Masculino', 20, 5, 18, 'M', 1),
(69, 7, 100, 'INFLUENZA - 05 A 59 ANOS - DOSIS UNICA', 11, 5, 16, 'A', 1),
(70, 7, 101, 'INFLUENZA - 60 ANOS A MAS - DOSIS UNICA', 11, 5, 27, 'A', 1),
(71, 9, 1, 'HEPATITIS B ADULTO - 05 A 59 ANOS - 1RA DOSIS', 2, 1, 16, 'A', 1),
(72, 9, 2, 'HEPATITIS B ADULTO - 05 A 59 ANOS - 2DA DOSIS', 2, 2, 16, 'A', 1),
(73, 9, 3, 'HEPATITIS B ADULTO - 05 A 59 ANOS - 3RA DOSIS', 2, 3, 16, 'A', 1),
(75, 2, 15, '18 MESES - REF. PENTAVALENTE', 22, 10, 11, 'A', 1),
(76, 2, 14, '18 MESES - REF. IPV', 23, 10, 11, 'A', 1),
(77, 15, 56, 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS', 11, 1, 14, 'A', 1),
(78, 15, 56, 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS', 11, 1, 14, 'A', 1),
(79, 15, 57, 'ANTIAMARILICA', 15, 5, 14, 'A', 1),
(80, 15, 58, 'SPR 1RA Dosis', 12, 1, 14, 'A', 1),
(81, 15, 59, 'SPR 2DA Dosis', 12, 2, 14, 'A', 1),
(82, 15, 60, 'REFUERZO PENTAVALENTE', 22, 10, 14, 'A', 1),
(83, 16, 1, 'INFLUENZA CON COMORBILIDAD - 1RA DOSIS', 11, 5, 15, 'A', 1),
(84, 16, 2, 'INFLUENZA SIN COMORBILIDAD - 1RA DOSIS', 11, 5, 15, 'A', 1),
(85, 16, 3, 'ANTIAMARILICA', 15, 1, 15, 'A', 1),
(86, 16, 4, 'SPR 1RA Dosis', 12, 1, 15, 'A', 1),
(87, 16, 5, 'SPR 2DA Dosis', 12, 2, 15, 'A', 1),
(88, 16, 6, 'REFUERZO ANTIPOLIO(IPV)', 3, 1, 15, 'A', 1),
(89, 16, 7, 'REFUERZO DPT', 17, 2, 15, 'A', 1),
(90, 16, 8, 'REFUERZO ANTIPOLIO(APO)', 4, 2, 15, 'A', 1),
(91, 17, 1, 'SPR 1RA Dosis', 12, 1, 28, 'A', 1),
(92, 17, 2, ' SPR 2DA Dosis', 12, 2, 28, 'A', 1),
(93, 17, 3, 'REFUERZO DPT', 17, 2, 28, 'A', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_PARAMETRO`
--

CREATE TABLE `ESNI_PARAMETRO` (
  `clave` varchar(60) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `fecha_actualizado` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Parametros del modulo ESNI';

--
-- Volcado de datos para la tabla `ESNI_PARAMETRO`
--

INSERT INTO `ESNI_PARAMETRO` (`clave`, `valor`, `descripcion`, `fecha_actualizado`) VALUES
('columna_anio', 'Anio', 'Columna con anio', '2026-07-21 19:09:09'),
('columna_aniomes', '', 'Columna con aniomes YYYYMM (opcional). Si esta vacia o no existe, se calcula a partir de Anio+Mes', '2026-07-29 20:10:43'),
('columna_cod_item', 'Codigo_Item', 'Columna con el codigo de item (vacuna)', '2026-07-21 19:09:09'),
('columna_departamento', 'Departamento_Establecimiento', 'Columna con departamento', '2026-07-21 19:09:09'),
('columna_edad_reg', 'Edad_Reg', 'Columna con edad numerica', '2026-07-21 19:09:09'),
('columna_establecimiento', 'Nombre_Establecimiento', 'Columna con nombre del establecimiento', '2026-07-21 19:09:09'),
('columna_grupo_edad', 'Grupo_Edad', 'Columna con grupo de edad textual', '2026-07-21 19:09:09'),
('columna_id_cita', 'Id_Cita', 'Columna con id de cita', '2026-07-21 19:09:09'),
('columna_id_paciente', 'Numero_Documento_Paciente', 'Columna con identificador del paciente (usada para deduplicacion)', '2026-07-29 20:10:43'),
('columna_id_ups', 'Id_Ups', 'Columna con Id_Ups (301204=Inmunizaciones ESNI)', '2026-07-29 20:11:31'),
('columna_mes', 'Mes', 'Columna con mes (1-12)', '2026-07-21 19:09:09'),
('columna_profesional', 'Id_Personal', 'Columna con id del profesional', '2026-07-21 19:40:04'),
('columna_renaes', 'Codigo_Unico', 'Columna con codigo Renaes del establecimiento', '2026-07-21 19:40:56'),
('columna_rownnum_lab', 'Id_Correlativo_Lab', 'Columna con numero de fila (deduplicacion). Si no existe, se asume 1', '2026-07-21 19:42:50'),
('columna_sexo', 'Id_Genero', 'Columna con sexo: F=Femenino/Mujer, M=Masculino/Varon', '2026-07-29 20:10:43'),
('columna_tip_edad', 'Tipo_Edad', 'Columna con tipo edad (D/M/A)', '2026-07-21 19:43:59'),
('columna_valor_lab', 'Valor_Lab', 'Columna con el valor de laboratorio (dosis). Si no existe, se asume NULL', '2026-07-21 19:09:09'),
('modo_estricto', '0', '1=exige todas las columnas; 0=tolerante a columnas faltantes', '2026-07-21 19:09:09'),
('tabla_origen', 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO', 'Tabla MySQL origen de los datos HIS consolidados', '2026-07-21 19:09:09'),
('version_esquema', '1.0.0', 'Version del esquema ESNI', '2026-07-21 19:09:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_REGLA`
--

CREATE TABLE `ESNI_REGLA` (
  `id_regla` int(11) NOT NULL,
  `id_linea` int(11) NOT NULL,
  `cod_item` varchar(20) NOT NULL,
  `valor_lab` varchar(20) DEFAULT NULL,
  `id_grupo_edad` int(11) DEFAULT NULL,
  `sexo` enum('M','F','A') NOT NULL DEFAULT 'A',
  `aniomes_min` varchar(6) DEFAULT NULL,
  `aniomes_max` varchar(6) DEFAULT NULL,
  `requiere_riesgo` tinyint(1) NOT NULL DEFAULT 0,
  `excluye_riesgo` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_comorbilidad` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=solo si el paciente tiene otro registro con cod_item=9999 (con comorbilidad)',
  `excluye_comorbilidad` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=solo si el paciente NO tiene registro con cod_item=9999 (sin comorbilidad)',
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Reglas de mapeo ESNI (cod_item + valor_lab + edad -> linea)';

--
-- Volcado de datos para la tabla `ESNI_REGLA`
--

INSERT INTO `ESNI_REGLA` (`id_regla`, `id_linea`, `cod_item`, `valor_lab`, `id_grupo_edad`, `sexo`, `aniomes_min`, `aniomes_max`, `requiere_riesgo`, `excluye_riesgo`, `requiere_comorbilidad`, `excluye_comorbilidad`, `activo`) VALUES
(1, 1, '90585', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(2, 1, '90585', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(3, 1, '90585', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(4, 1, '90585', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(5, 1, '90585', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(6, 1, 'Z232', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(7, 1, 'Z232', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(8, 1, 'Z232', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(9, 1, 'Z232', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(10, 1, 'Z232', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(11, 2, '90585', '1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(12, 2, '90585', '01', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(13, 2, '90585', 'D1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(14, 2, 'Z232', '1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(15, 2, 'Z232', '01', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(16, 2, 'Z232', 'D1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(17, 3, '90585', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(18, 3, '90585', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(19, 3, '90585', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(20, 3, 'Z232', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(21, 3, 'Z232', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(22, 3, 'Z232', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(23, 4, '90744', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(24, 4, '90744', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(25, 4, 'Z246', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(26, 4, 'Z246', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(27, 5, '90744', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(28, 5, '90744', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(29, 5, '90744', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(30, 5, 'Z246', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(31, 5, 'Z246', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(32, 5, 'Z246', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(33, 6, '90713', '1', 5, 'A', NULL, NULL, 0, 1, 0, 0, 1),
(34, 6, '90713', '01', 5, 'A', NULL, NULL, 0, 1, 0, 0, 1),
(35, 6, '90713', 'D1', 5, 'A', NULL, NULL, 0, 1, 0, 0, 1),
(36, 7, '90713', '2', 5, 'A', NULL, NULL, 0, 1, 0, 0, 1),
(37, 7, '90713', '02', 5, 'A', NULL, NULL, 0, 1, 0, 0, 1),
(38, 7, '90713', 'D2', 5, 'A', NULL, NULL, 0, 1, 0, 0, 1),
(39, 8, '90712', '3', 7, 'A', NULL, '202212', 0, 1, 0, 0, 1),
(40, 8, '90712', '03', 7, 'A', NULL, '202212', 0, 1, 0, 0, 1),
(41, 8, '90712', 'D3', 7, 'A', NULL, '202212', 0, 1, 0, 0, 1),
(42, 8, '90713', '3', 7, 'A', '202301', NULL, 0, 1, 0, 0, 1),
(43, 8, '90713', '03', 7, 'A', '202301', NULL, 0, 1, 0, 0, 1),
(44, 8, '90713', 'D3', 7, 'A', '202301', NULL, 0, 1, 0, 0, 1),
(45, 9, '90723', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(46, 9, '90723', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(47, 9, '90723', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(48, 9, 'Z276', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(49, 9, 'Z276', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(50, 9, 'Z276', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(51, 9, '90722', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(52, 9, '90722', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(53, 9, '90722', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(54, 10, '90723', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(55, 10, '90723', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(56, 10, '90723', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(57, 10, 'Z276', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(58, 10, 'Z276', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(59, 10, 'Z276', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(60, 10, '90722', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(61, 10, '90722', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(62, 10, '90722', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(63, 11, '90723', '3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(64, 11, '90723', '03', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(65, 11, '90723', 'D3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(66, 11, 'Z276', '3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(67, 11, 'Z276', '03', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(68, 11, 'Z276', 'D3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(69, 11, '90722', '3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(70, 11, '90722', '03', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(71, 11, '90722', 'D3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(72, 18, '90681', '1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(73, 18, '90681', '01', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(74, 18, '90681', 'D1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(75, 18, 'Z268', '1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(76, 18, 'Z268', '01', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(77, 18, 'Z268', 'D1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(78, 19, '90681', '2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(79, 19, '90681', '02', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(80, 19, '90681', 'D2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(81, 19, 'Z268', '2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(82, 19, 'Z268', '02', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(83, 19, 'Z268', 'D2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(84, 20, '90669', '1', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(85, 20, '90669', '01', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(86, 20, '90669', 'D1', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(87, 20, 'Z238', '1', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(88, 20, 'Z238', '01', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(89, 20, 'Z238', 'D1', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(90, 20, '90670', '1', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(91, 20, '90670', '01', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(92, 20, '90670', 'D1', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(93, 21, '90669', '2', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(94, 21, '90669', '02', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(95, 21, '90669', 'D2', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(96, 21, 'Z238', '2', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(97, 21, 'Z238', '02', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(98, 21, 'Z238', 'D2', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(99, 21, '90670', '2', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(100, 21, '90670', '02', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(101, 21, '90670', 'D2', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(102, 22, '90657', '1', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(103, 22, '90657', '01', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(104, 22, '90657', 'D1', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(105, 22, 'Z2511', '1', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(106, 22, 'Z2511', '01', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(107, 22, 'Z2511', 'D1', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(108, 22, '90687', '1', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(109, 22, '90687', '01', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(110, 22, '90687', 'D1', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(111, 23, '90657', '2', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(112, 23, '90657', '02', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(113, 23, '90657', 'D2', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(114, 23, 'Z2511', '2', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(115, 23, 'Z2511', '02', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(116, 23, 'Z2511', 'D2', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(117, 23, '90687', '2', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(118, 23, '90687', '02', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(119, 23, '90687', 'D2', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(120, 24, '90713', '1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(121, 24, '90713', '01', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(122, 24, '90713', 'D1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(123, 24, 'Z240', '1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(124, 24, 'Z240', '01', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(125, 24, 'Z240', 'D1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(126, 25, '90713', '2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(127, 25, '90713', '02', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(128, 25, '90713', 'D2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(129, 25, 'Z240', '2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(130, 25, 'Z240', '02', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(131, 25, 'Z240', 'D2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(132, 26, '90713', '3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(133, 26, '90713', '03', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(134, 26, '90713', 'D3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(135, 26, 'Z240', '3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(136, 26, 'Z240', '03', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(137, 26, 'Z240', 'D3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1),
(139, 69, '90658', 'DU', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(140, 70, '90658', 'DU', 27, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(141, 28, '90707', '1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(142, 28, '90707', '01', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(143, 28, '90707', 'D1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(144, 28, '90707', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(145, 37, '90707', '2', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(146, 37, '90707', '02', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(147, 37, '90707', 'D2', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(148, 35, '90717', 'DU', 10, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(149, 43, '90717', 'DU', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(150, 43, '90717', '1', 12, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(151, 43, '90717', '01', 12, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(152, 43, '90717', 'D1', 12, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(153, 71, '90746', '1', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(154, 71, '90746', '01', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(155, 71, '90746', 'D1', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(156, 72, '90746', '2', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(157, 72, '90746', '02', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(158, 72, '90746', 'D2', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(159, 73, '90746', '3', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(160, 73, '90746', '03', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(161, 73, '90746', 'D3', 16, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(162, 56, '90714', '1', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(163, 56, '90714', '01', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(164, 56, '90714', 'D1', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(165, 57, '90714', '2', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(166, 57, '90714', '02', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(167, 57, '90714', 'D2', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(168, 58, '90714', '3', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(169, 58, '90714', '03', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(170, 58, '90714', 'D3', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(171, 59, '90714', '1', 19, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(172, 60, '90714', '2', 19, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(173, 61, '90714', '3', 19, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(174, 63, '90714', '1', 18, 'M', NULL, NULL, 0, 0, 0, 0, 1),
(175, 64, '90714', '2', 18, 'M', NULL, NULL, 0, 0, 0, 0, 1),
(176, 65, '90714', '3', 18, 'M', NULL, NULL, 0, 0, 0, 0, 1),
(177, 62, '90715', 'DU', 19, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(178, 62, '90715', 'G', 19, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(179, 62, '90715', NULL, 19, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(180, 29, '90716', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(181, 29, '90716', '1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(182, 29, '90716', '01', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(183, 29, '90716', 'D1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(184, 66, '90649', 'DU', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(185, 66, '90649', '1', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(186, 66, '90649', '01', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(187, 66, '90649', 'D1', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(188, 67, '90649', '2', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(189, 67, '90649', '02', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(190, 67, '90649', 'D2', 17, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(191, 68, '90649', 'DU', 18, 'M', NULL, NULL, 0, 0, 0, 0, 1),
(192, 68, '90649', NULL, 18, 'M', NULL, NULL, 0, 0, 0, 0, 1),
(193, 36, '90633.01', 'DU', 10, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(194, 36, '90633.01', NULL, 10, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(195, 20, '90670', 'DU', 5, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(196, 27, '90670', '3', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(197, 27, '90670', '03', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(198, 27, '90670', 'D3', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(199, 22, '90657', 'DU', 6, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(200, 32, '90657', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(201, 75, '90722', 'DA', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(202, 76, '90713', 'DA', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(203, 40, '90657', 'DU', 13, 'A', NULL, NULL, 0, 0, 1, 0, 1),
(204, 41, '90657', 'DU', 13, 'A', NULL, NULL, 0, 0, 0, 1, 1),
(205, 50, '90707', '1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(206, 51, '90707', '2', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(207, 55, '90713', 'DA', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(208, 54, '90722', 'DA', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(209, 77, '90658', 'DU', 14, 'A', NULL, NULL, 0, 0, 1, 0, 1),
(210, 78, '90658', 'DU', 14, 'A', NULL, NULL, 0, 0, 0, 1, 1),
(211, 79, '90717', 'DU', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(212, 80, '90707', '1', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(213, 80, '90707', 'D1', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(214, 81, '90707', '2', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(215, 81, '90707', 'D2', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(216, 82, '90722', 'DA', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(217, 83, '90658', 'DU', 15, 'A', NULL, NULL, 0, 0, 1, 0, 1),
(218, 84, '90658', 'DU', 15, 'A', NULL, NULL, 0, 0, 0, 1, 1),
(219, 85, '90717', 'DU', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(220, 86, '90707', '1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(221, 86, '90707', 'D1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(222, 87, '90707', '2', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(223, 87, '90707', 'D2', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(224, 88, '90713', '1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(225, 88, '90713', 'D1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(226, 89, '90701', 'DA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(227, 89, '90701', 'DDA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(228, 90, '90712', 'DA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(229, 90, '90712', 'DDA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(230, 91, '90707', '1', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(231, 91, '90707', 'D1', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(232, 93, '90701', 'DA', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(233, 93, '90701', 'DDA', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_SECCION_REPORTE`
--

CREATE TABLE `ESNI_SECCION_REPORTE` (
  `id_seccion` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `layout` enum('lista','matriz_dosis','matriz_edad','total_uno','matriz_sexo') NOT NULL DEFAULT 'lista',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Secciones del Reporte Operacional ESNI';

--
-- Volcado de datos para la tabla `ESNI_SECCION_REPORTE`
--

INSERT INTO `ESNI_SECCION_REPORTE` (`id_seccion`, `codigo`, `titulo`, `descripcion`, `layout`, `orden`, `activo`) VALUES
(1, 'A', 'A. - MENORES DE 01 ANIO', 'BCG, Hepatitis B, Antipolio (IPV), Pentavalente, Reacciones Adversas, Rotavirus, Neumococo, Influenza, Poblacion en Riesgo', 'lista', 1, 1),
(2, 'B', 'B. - DE 01 ANIO', 'Neumococo 1 anio, SPR, Varicela, Influenza, Neumococo 12-23m, Antiamarilica, Hepatitis A, SPR 2da, Ref DPT, Ref APO, Vacunacion no oportuna', 'lista', 2, 1),
(3, 'C', 'C. - DE 02 ANOS', 'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 2 anos, Refuerzo APO 2 anos', 'lista', 3, 1),
(4, 'F', 'F. - dT ADULTO EN MUJERES EN EDAD FERTIL DE 10 A 49 ANIOS A MAS', 'Esquema dT en mujeres de 10 a 49 anos', 'matriz_dosis', 4, 1),
(5, 'F2', 'F2. - GESTANTES (TDAP)', 'Esquema dT + TDAP en gestantes', 'matriz_dosis', 5, 1),
(6, 'G', 'G. - dT ADULTO: VARONES EN RIESGO', 'Esquema dT en varones en riesgo', 'matriz_dosis', 6, 1),
(7, 'H', 'H. - INFLUENZA ESTACIONAL EN OTROS GRUPOS', 'Influenza por grupo de edad y riesgo', 'total_uno', 7, 1),
(8, 'I', 'I. - SARAMPION - RUBEOLA', 'Vacunacion SR en ninos/personas no vacunadas', 'total_uno', 8, 1),
(9, 'J', 'J. - POBLACION DE 05 A 59 ANIOS: VACUNACION CONTRA LA HEPATITIS B', 'Hepatitis B en poblacion 5-59 anos', 'matriz_dosis', 9, 1),
(10, 'K', 'K. - ANTIAMARILICA', 'Antiamarilica en poblacion no vacunada y viajeros a zonas endemicas', 'total_uno', 10, 1),
(12, 'Q', 'Q. - VARICELA', 'Varicela por grupo de edad', 'total_uno', 12, 1),
(13, 'O', 'O. - NEUMOCOCO', 'Neumococo en poblacion en riesgo', 'total_uno', 13, 1),
(14, 'N', 'N.-VACUNA VPH', 'Virus del Papiloma Humano: femenino y masculino (dosis unica)', 'matriz_sexo', 14, 1),
(15, 'D', ' D. - DE 03 ANOS', 'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 3 anos, Refuerzo APO 3 años', 'lista', 4, 1),
(16, 'E1', 'E1.-  DE 04 AÑOS', 'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 4 anos, Refuerzo APO 4 anos', 'lista', 4, 1),
(17, 'E2', 'E2.-  DE 05 - 07 AÑOS', 'SPR, Refuerzo DPT', 'lista', 4, 1),
(18, 'L', 'L.- SOLO GESTANTES', 'Vacuna combinada dtpa', 'matriz_edad', 11, 1),
(19, 'R', 'R.-HEPATITIS A', 'Vacuna de 1 a 5', 'matriz_edad', 12, 1),
(20, 'T', 'T.-SPR-SARAMPION', 'vacuna de 5 a 59 y Trabajador de Salud', 'matriz_edad', 15, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESNI_VACUNA`
--

CREATE TABLE `ESNI_VACUNA` (
  `id_vacuna` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#0d6efd',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Maestro de vacunas ESNI';

--
-- Volcado de datos para la tabla `ESNI_VACUNA`
--

INSERT INTO `ESNI_VACUNA` (`id_vacuna`, `codigo`, `nombre`, `descripcion`, `color`, `activo`, `fecha_creacion`) VALUES
(1, 'BCG', 'BCG', 'Vacuna BCG contra tuberculosis', '#fd7e14', 1, '2026-07-21 18:59:02'),
(2, 'HVB', 'Hepatitis Viral B', 'Vacuna Hepatitis B', '#dc3545', 1, '2026-07-21 18:59:02'),
(3, 'IPV', 'Antipolio (IPV)', 'Vacuna Antipolio Inactivada', '#0dcaf0', 1, '2026-07-21 18:59:02'),
(4, 'APO', 'Antipolio Oral (APO)', 'Vacuna Antipolio Oral', '#0d6efd', 1, '2026-07-21 18:59:02'),
(5, 'PENTA', 'Pentavalente', 'Vacuna Pentavalente (DTP-Hib-HepB)', '#6610f2', 1, '2026-07-21 18:59:02'),
(6, 'RXN_DTP', 'Reaccion Adversa Dt(p)', 'Reaccion adversa a componente Difterico', '#6f42c1', 1, '2026-07-21 18:59:02'),
(7, 'RXN_HVB', 'Reaccion Adversa HvB', 'Reaccion adversa a Hepatitis B', '#e83e8c', 1, '2026-07-21 18:59:02'),
(8, 'RXN_HIB', 'Reaccion Adversa Hib', 'Reaccion adversa a Haemophilus influenzae type b', '#fd7e14', 1, '2026-07-21 18:59:02'),
(9, 'ROTA', 'Rotavirus', 'Vacuna contra Rotavirus', '#20c997', 1, '2026-07-21 18:59:02'),
(10, 'NEUMO', 'Neumococo', 'Vacuna Conjugada Neumococica', '#198754', 1, '2026-07-21 18:59:02'),
(11, 'INF', 'Influenza', 'Vacuna contra Influenza Estacional', '#0dcaf0', 1, '2026-07-21 18:59:02'),
(12, 'SPR', 'SPR (Sarampion-Paperas-Rubeola)', 'Vacuna Triple Viral', '#ffc107', 1, '2026-07-21 18:59:02'),
(13, 'SR', 'SR (Sarampion-Rubeola)', 'Vacuna Doble Viral', '#ffca2c', 1, '2026-07-21 18:59:02'),
(14, 'VAR', 'Varicela', 'Vacuna contra Varicela', '#adb5bd', 1, '2026-07-21 18:59:02'),
(15, 'AMA', 'Antiamarilica (Fiebre Amarilla)', 'Vacuna contra Fiebre Amarilla', '#ffd700', 1, '2026-07-21 18:59:02'),
(16, 'HEP_A', 'Hepatitis A', 'Vacuna contra Hepatitis A', '#b02a37', 1, '2026-07-21 18:59:02'),
(17, 'DPT', 'DPT (Refuerzo)', 'Vacuna DPT Refuerzo pediatrica', '#6f42c1', 1, '2026-07-21 18:59:02'),
(18, 'DT', 'dT Adulto', 'Vacuna dT Adulto (Difteria-Tetanica)', '#5c636a', 1, '2026-07-21 18:59:02'),
(19, 'TDAP', 'TDAP', 'Vacuna TDAP Gestantes (Difteria-Tetanica-Pertusis)', '#d63384', 1, '2026-07-21 18:59:02'),
(20, 'VPH', 'VPH (Virus Papiloma Humano)', 'Vacuna contra Virus del Papiloma Humano', '#7b2ff7', 1, '2026-07-21 18:59:02'),
(21, 'BCG_TB', 'BCG Contacto TB', 'BCG en contactos de Tuberculosis', '#fd7e14', 1, '2026-07-21 18:59:02'),
(22, 'PENTA_RE', 'PENTAVALENTE (Refuerzo)', 'Vacuna Refuerzo Pentavalente (DTP-Hib-HepB)', '#0d6efd', 1, '2026-07-25 19:50:41'),
(23, 'IPV_RE', 'IPV (Refuerzo)', 'Vacuna Refuerzo IPV', '#0d6efd', 1, '2026-07-25 20:10:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `IMPORT_ESTADO`
--

CREATE TABLE `IMPORT_ESTADO` (
  `id` int(11) NOT NULL,
  `tipo_archivo` varchar(50) NOT NULL COMMENT 'MaestroRegistrador, MaestroPersonal, MaestroPaciente, NominalTrama',
  `importado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Importado, 0=Pendiente',
  `fecha_importacion` datetime DEFAULT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `periodo_mes` varchar(2) DEFAULT NULL,
  `periodo_anio` varchar(4) DEFAULT NULL,
  `registros_importados` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `LOG_IMPORTACION`
--

CREATE TABLE `LOG_IMPORTACION` (
  `id_log` int(11) NOT NULL,
  `tipo_operacion` varchar(50) NOT NULL COMMENT 'IMPORT o PROCESS',
  `tipo_archivo` varchar(50) DEFAULT NULL COMMENT 'MaestroRegistrador, MaestroPersonal, MaestroPaciente, NominalTrama',
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `tabla_destino` varchar(100) DEFAULT NULL,
  `periodo_mes` varchar(2) DEFAULT NULL,
  `periodo_anio` varchar(4) DEFAULT NULL,
  `registros_procesados` int(11) DEFAULT 0,
  `modo_importacion` varchar(20) DEFAULT NULL COMMENT 'REEMPLAZO o PERIODO',
  `fecha_operacion` datetime DEFAULT current_timestamp(),
  `usuario` varchar(100) DEFAULT NULL,
  `estado` varchar(20) NOT NULL COMMENT 'EXITO, ERROR, PARCIAL',
  `mensaje` text DEFAULT NULL,
  `duracion_segundos` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_CENTRO_POBLADO`
--

CREATE TABLE `MAESTRO_HIS_CENTRO_POBLADO` (
  `Id_Centro_Poblado` varchar(10) NOT NULL,
  `Descripcion_Centro_Poblado` varchar(350) DEFAULT NULL,
  `Id_Codigo_Centro_Poblado` varchar(4) DEFAULT NULL,
  `Id_Ubigueo_Centro_Poblado` varchar(6) DEFAULT NULL,
  `Altitud_Centro_Poblado` decimal(7,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_CIE_CPMS`
--

CREATE TABLE `MAESTRO_HIS_CIE_CPMS` (
  `Codi_Item` varchar(10) NOT NULL,
  `Descripcion_Item` varchar(400) DEFAULT NULL,
  `Fg_Tipo` varchar(2) DEFAULT NULL,
  `Descripcion_Tipo_Item` varchar(100) DEFAULT NULL,
  `Fg_Estado` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_COLEGIO`
--

CREATE TABLE `MAESTRO_HIS_COLEGIO` (
  `Id_Colegio` varchar(2) NOT NULL,
  `Descripcion_Colegio` varchar(800) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_CONDICION_CONTRATO`
--

CREATE TABLE `MAESTRO_HIS_CONDICION_CONTRATO` (
  `Id_Condicion` int(11) NOT NULL,
  `Descripcion_Condicion` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_ESTABLECIMIENTO`
--

CREATE TABLE `MAESTRO_HIS_ESTABLECIMIENTO` (
  `Id_Establecimiento` int(11) NOT NULL,
  `Nombre_Establecimiento` varchar(100) DEFAULT NULL,
  `Ubigueo_Establecimiento` char(6) DEFAULT NULL,
  `Codigo_Disa` int(11) DEFAULT NULL,
  `Disa` varchar(80) DEFAULT NULL,
  `Codigo_Red` char(2) DEFAULT NULL,
  `Red` varchar(70) DEFAULT NULL,
  `Codigo_MicroRed` char(2) DEFAULT NULL,
  `MicroRed` varchar(70) DEFAULT NULL,
  `Codigo_Unico` varchar(9) NOT NULL,
  `Codigo_Sector` int(11) DEFAULT NULL,
  `Descripcion_Sector` varchar(50) DEFAULT NULL,
  `Departamento` varchar(150) DEFAULT NULL,
  `Provincia` varchar(150) DEFAULT NULL,
  `Distrito` varchar(150) DEFAULT NULL,
  `Categoria_Establecimiento` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_ESTABLECIMIENTO`
--

INSERT INTO `MAESTRO_HIS_ESTABLECIMIENTO` (`Id_Establecimiento`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Codigo_Disa`, `Disa`, `Codigo_Red`, `Red`, `Codigo_MicroRed`, `MicroRed`, `Codigo_Unico`, `Codigo_Sector`, `Descripcion_Sector`, `Departamento`, `Provincia`, `Distrito`, `Categoria_Establecimiento`) VALUES
(11256, 'CHUQUISHUARI', '120402', 17, 'JUNIN', '02', 'JAUJA', '02', 'VALLE DE YANAMARCA', '00006874', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JAUJA', 'ACOLLA', 'I-1'),
(11895, 'SAN ANTONIO ALTO PICHANAKI', '120302', 17, 'JUNIN', '07', 'PICHANAKI', '01', 'CIUDAD SATELITE', '00000339', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(11899, 'CASABLANCA', '120426', 17, 'JUNIN', '02', 'JAUJA', '02', 'VALLE DE YANAMARCA', '00000390', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JAUJA', 'POMACANCHA', 'I-1'),
(12359, 'PURHUARACRA', '120708', 17, 'JUNIN', '03', 'TARMA', '03', 'ACOBAMBA', '00010344', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'TARMA', 'SAN PEDRO DE CAJAS', 'I-1'),
(12624, 'BELLA ESPERANZA', '120601', 17, 'JUNIN', '05', 'SATIPO', '01', 'RIO NEGRO-SATIPO', '00010502', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'SATIPO', 'I-1'),
(13888, 'LABORATORIO DE REFERENCIA REGIONAL JUNIN', '120101', 17, 'JUNIN', '00', 'NO PERTENECE A NINGUNA RED', '00', 'NO PERTENECE A NINGUNA MICRORED', '00010952', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'HUANCAYO', 'SD'),
(14173, 'SANTA ELENA', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '02', 'SAN ANTONIO DE SONOMORO', '00011138', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(15825, 'HUAYCHULA', '120124', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '07', 'LA LIBERTAD', '00012471', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'PARIAHUANCA', 'I-1'),
(15827, 'SHICUY', '120907', 17, 'JUNIN', '09', 'RED DE SALUD CHUPACA', '04', 'JARPA', '00012470', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHUPACA', 'SAN JUAN DE JARPA', 'I-1'),
(15831, 'LA NUEVA LIBERTAD DE PUNTO', '120135', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00012468', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'SANTO DOMINGO DE ACOBAMBA', 'I-1'),
(15836, 'CHUAMBA.', '120113', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '06', 'CHILCA', '00012469', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'CULLHUAS', 'I-1'),
(18501, 'MIGUEL GRAU', '120607', 17, 'JUNIN', '05', 'SATIPO', '01', 'RIO NEGRO-SATIPO', '00013862', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO NEGRO', 'I-1'),
(18864, 'ALTO VILLA VICTORIA', '120607', 17, 'JUNIN', '05', 'SATIPO', '01', 'RIO NEGRO-SATIPO', '00013863', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO NEGRO', 'I-1'),
(19074, 'CANAAN DEL NORTE', '120601', 17, 'JUNIN', '05', 'SATIPO', '01', 'RIO NEGRO-SATIPO', '00013864', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'SATIPO', 'I-1'),
(20287, 'MARISCAL CACERES', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014385', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(20288, 'JOSE GALVEZ', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(20303, 'SAN JOSE DE ANAPIARI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '03', 'HUACHIRIKI', '00014383', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(22411, 'ACOPALCA', '120101', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '07', 'LA LIBERTAD', '00015905', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'HUANCAYO', 'I-1'),
(22465, 'NUEVO OCCORO', '120124', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '07', 'LA LIBERTAD', '00015577', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'PARIAHUANCA', 'I-1'),
(22900, 'SELVA DE ORO', '120608', 17, 'JUNIN', '05', 'SATIPO', '06', 'VALLE ESMERALDA', '00015914', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO TAMBO', 'I-1'),
(24656, 'BUENOS AIRES -TZIRIARI', '120604', 17, 'JUNIN', '05', 'SATIPO', '02', 'MAZAMARI', '00016911', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'MAZAMARI', 'I-1'),
(24658, 'VILLA PROGRESO DE EDEN', '120604', 17, 'JUNIN', '05', 'SATIPO', '02', 'MAZAMARI', '00016912', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'MAZAMARI', 'I-1'),
(24659, 'GLORIABAMBA', '120604', 17, 'JUNIN', '05', 'SATIPO', '02', 'MAZAMARI', '00016908', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'MAZAMARI', 'I-1'),
(24666, 'TEORIA', '120604', 17, 'JUNIN', '05', 'SATIPO', '02', 'MAZAMARI', '00016909', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'MAZAMARI', 'I-1'),
(26348, 'HUANCAMACHAY', '120605', 17, 'JUNIN', '05', 'SATIPO', '01', 'RIO NEGRO-SATIPO', '00017678', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PAMPA HERMOSA', 'I-2'),
(26568, '1RO DE MAYO', '120114', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '05', 'EL TAMBO', '00018190', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'EL TAMBO', 'I-1'),
(28231, 'MEDICO DE FAMILIA BAHIA DEL RIO', '120302', 17, 'JUNIN', '00', 'NO PERTENECE A NINGUNA RED', '00', 'NO PERTENECE A NINGUNA MICRORED', '00018647', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-2'),
(29546, 'SAN FRANCISCO DE MACON', '120205', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00019794', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CONCEPCION', 'COCHAS', 'I-1'),
(29547, 'ANDAS', '120205', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00019792', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CONCEPCION', 'COCHAS', 'I-1'),
(29548, 'HUANUCO', '120203', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00019793', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CONCEPCION', 'ANDAMARCA', 'I-1'),
(29549, 'PUNCO', '120203', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00019790', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CONCEPCION', 'ANDAMARCA', 'I-1'),
(308, 'HOSPITAL REGIONAL DOCENTE DE MEDICINA TROPICAL DR. JULIO CESAR DEMARINI CARO', '120301', 17, 'JUNIN', '00', 'NO PERTENECE A NINGUNA RED', '00', 'NO PERTENECE A NINGUNA MICRORED', '00000308', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'CHANCHAMAYO', 'II-1'),
(310, 'VILLA DORADA', '120301', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000310', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'CHANCHAMAYO', 'I-1'),
(311, 'VILLA PROGRESO', '120301', 17, 'JUNIN', '04', 'CHANCHAMAYO', '01', 'SAN LUIS DE SHUARO', '00000311', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'CHANCHAMAYO', 'I-2'),
(31140, 'SAN FRANCISCO DE ASIS DE PUCARA', '120805', 17, 'JUNIN', '02', 'JAUJA', '04', 'YAULI-OROYA', '00021450', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'YAULI', 'MOROCOCHA', 'I-1'),
(31141, 'JUAN PABLO II', '120808', 17, 'JUNIN', '02', 'JAUJA', '04', 'YAULI-OROYA', '00021449', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'YAULI', 'SANTA ROSA DE SACCO', 'I-1'),
(312, 'PUEBLO PARDO', '120301', 17, 'JUNIN', '04', 'CHANCHAMAYO', '01', 'SAN LUIS DE SHUARO', '00000312', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'CHANCHAMAYO', 'I-1'),
(31247, 'HUARI', '120801', 17, 'JUNIN', '02', 'JAUJA', '04', 'YAULI-OROYA', '00021481', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'YAULI', 'LA OROYA', 'I-1'),
(314, 'SAN LUIS DE SHUARO', '120304', 17, 'JUNIN', '04', 'CHANCHAMAYO', '01', 'SAN LUIS DE SHUARO', '00000314', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN LUIS DE SHUARO', 'I-3'),
(315, 'SANCHIRIO PALOMAR', '120304', 17, 'JUNIN', '04', 'CHANCHAMAYO', '01', 'SAN LUIS DE SHUARO', '00000315', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN LUIS DE SHUARO', 'I-2'),
(31570, 'LOS MANANTIALES', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '02', 'SAN ANTONIO DE SONOMORO', '00021803', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31571, 'LOS ÁNGELES DE EDEN', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '02', 'SAN ANTONIO DE SONOMORO', '00021800', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31572, 'SAN JUAN DE SANGARENI', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '03', 'CUBANTIA', '00021805', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31573, 'SAN JUAN DE PUEBLO LIBRE', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '03', 'CUBANTIA', '00021802', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31574, 'ALTO CHICHIRENI', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '03', 'CUBANTIA', '00021804', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31575, 'SANTA CRUZ DE ANAPATI', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '03', 'CUBANTIA', '00021801', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31576, 'LIBERTAD DE ANAPATI', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '03', 'CUBANTIA', '00021806', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31577, 'SAN JERONIMO', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '02', 'SAN ANTONIO DE SONOMORO', '00021797', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(31589, 'CENTRO SAURENI', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '02', 'SAN ANTONIO DE SONOMORO', '00021814', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(316, 'SANTA HERMINIA BAJA', '120304', 17, 'JUNIN', '04', 'CHANCHAMAYO', '01', 'SAN LUIS DE SHUARO', '00000316', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN LUIS DE SHUARO', 'I-1'),
(317, 'RAYMONDI', '120504', 17, 'JUNIN', '04', 'CHANCHAMAYO', '01', 'SAN LUIS DE SHUARO', '00000317', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JUNIN', 'ULCUMAYO', 'I-1'),
(318, 'VILLA PERENE', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-4'),
(319, 'BAJO MARANKIARI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000319', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(320, 'PUERTO YURINAKI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-3'),
(321, 'ALTO YURINAKI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000321', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(322, 'LIBERTAD TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000322', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(323, 'INCHATINGARI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000323', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(324, 'LOS ANGELES DE UBIRIKI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(325, 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(326, 'SANTA ROSA DE RIO AMARILLO', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000326', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(327, 'ALTO PUMPURIANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000327', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(328, 'CHURINGAVENI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000328', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(329, 'HUACAMAYO', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000329', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(330, 'SAN FERNANDO DE KIVINAKI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(331, 'LOS ANGELES TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000331', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(332, 'ALTO SAN JUAN', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000332', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(333, 'CENTRO TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000333', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(33348, 'MATERNO INFANTIL EL TAMBO', '120114', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '05', 'EL TAMBO', '00024232', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'EL TAMBO', 'I-4'),
(334, 'STA ROSA DE CAMONASHARI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000334', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(335, 'ZONA PATRIA', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000335', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(336, 'ALTO YAPAZ', '120304', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000336', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN LUIS DE SHUARO', 'I-1'),
(33631, 'POTRERO', '120135', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00024428', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'SANTO DOMINGO DE ACOBAMBA', 'I-1'),
(33668, 'PUERTO PORVENIR', '120606', 17, 'JUNIN', '08', 'SAN MARTIN DE PANGOA', '03', 'CUBANTIA', '00024571', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'PANGOA', 'I-1'),
(337, 'MIRISHARO', '120302', 17, 'JUNIN', '07', 'PICHANAKI', '01', 'CIUDAD SATELITE', '00000337', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-2'),
(33753, 'COMUNIDAD NATIVA SHANQUI', '120601', 17, 'JUNIN', '05', 'SATIPO', '01', 'RIO NEGRO-SATIPO', '00024569', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'SATIPO', 'I-1'),
(33754, 'SAN JUAN DE KIHATE', '120608', 17, 'JUNIN', '05', 'SATIPO', '04', 'PUERTO OCOPA', '00024566', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO TAMBO', 'I-1'),
(33755, 'UNIÓN JUNIN', '120608', 17, 'JUNIN', '05', 'SATIPO', '04', 'PUERTO OCOPA', '00024564', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO TAMBO', 'I-1'),
(338, 'SAN CRISTOBAL', '120302', 17, 'JUNIN', '07', 'PICHANAKI', '01', 'CIUDAD SATELITE', '00000338', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(33891, 'COMUNIDAD NATIVA CATUNGO QUEMPIRI', '120608', 17, 'JUNIN', '05', 'SATIPO', '06', 'VALLE ESMERALDA', '00024570', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO TAMBO', 'I-1'),
(33959, 'CONUNIDAD NATIVA MAZAROVENI', '120608', 17, 'JUNIN', '05', 'SATIPO', '04', 'PUERTO OCOPA', '00024567', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO TAMBO', 'I-1'),
(340, 'HOSPITAL DE APOYO PICHANAKI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '00', 'NO PERTENECE A NINGUNA MICRORED', '00000340', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'II-1'),
(34034, 'NAPATI', '120608', 17, 'JUNIN', '05', 'SATIPO', '04', 'PUERTO OCOPA', '00024563', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'SATIPO', 'RIO TAMBO', 'I-1'),
(34097, 'DURAZNO PATA', '120135', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00024779', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'SANTO DOMINGO DE ACOBAMBA', 'I-1'),
(341, 'IMPITATO CASCADA', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '02', 'LAS PALMAS', '00000341', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(342, 'PRIMAVERA', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '02', 'LAS PALMAS', '00000342', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(34265, 'ROSASPAMPA', '120135', 17, 'JUNIN', '01', 'VALLE DEL MANTARO', '03', 'COMAS', '00024780', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'HUANCAYO', 'SANTO DOMINGO DE ACOBAMBA', 'I-1'),
(343, 'CENTRO CUYANI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '04', 'CUYANI', '00000343', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-3'),
(344, 'LAS PALMAS', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '02', 'LAS PALMAS', '00000344', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-3'),
(345, 'PAMPA CAMONA', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '02', 'LAS PALMAS', '00000345', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(346, 'HUANTININI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '03', 'HUACHIRIKI', '00000346', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(347, 'CONDADO PICHIKIARI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '04', 'CUYANI', '00000347', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(348, 'SAN JUAN CENTRO AUTIKI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '03', 'HUACHIRIKI', '00000348', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(349, 'BELEN ANAPIARI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '03', 'HUACHIRIKI', '00000349', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(350, 'VALLE HERMOSO', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '04', 'CUYANI', '00000350', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(351, 'HUACHIRIKI', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '03', 'HUACHIRIKI', '00000351', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-3'),
(352, 'UNION SHIMASHIRO', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '02', 'LAS PALMAS', '00000352', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-2'),
(353, 'ANDRES AVELINO CACERES', '120303', 17, 'JUNIN', '07', 'PICHANAKI', '04', 'CUYANI', '00000353', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PICHANAKI', 'I-1'),
(354, 'SAN RAMON', '120305', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000354', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN RAMON', 'I-4'),
(355, 'NARANJAL', '120305', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000355', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN RAMON', 'I-2'),
(356, 'LA ESPERANZA', '120305', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000356', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN RAMON', 'I-1'),
(357, 'PEDREGAL', '120305', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000357', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN RAMON', 'I-1'),
(358, 'VITOC', '120306', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000358', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'VITOC', 'I-2'),
(359, 'VISCATAN', '120306', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000359', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'VITOC', 'I-1'),
(360, 'UTCUYACU', '120306', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000360', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'VITOC', 'I-1'),
(361, 'UCHUBAMBA', '120416', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000361', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JAUJA', 'MASMA', 'I-1'),
(362, 'MONOBAMBA', '120419', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000362', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JAUJA', 'MONOBAMBA', 'I-2'),
(363, 'CHACAYBAMBA', '120419', 17, 'JUNIN', '04', 'CHANCHAMAYO', '02', 'SAN RAMON', '00000363', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JAUJA', 'MONOBAMBA', 'I-1'),
(365, 'HOSPITAL DOMINGO OLAVEGOYA', '120401', 17, 'JUNIN', '02', 'JAUJA', '00', 'NO PERTENECE A NINGUNA MICRORED', '00000365', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'JAUJA', 'JAUJA', 'II-1'),
--
-- Estructura de tabla para la tabla `MAESTRO_HIS_ETNIA`
--

CREATE TABLE `MAESTRO_HIS_ETNIA` (
  `Id_Etnia` char(2) NOT NULL,
  `Descripcion_Etnia` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_FINANCIADOR`
--

CREATE TABLE `MAESTRO_HIS_FINANCIADOR` (
  `Id_Financiador` varchar(2) NOT NULL,
  `Descripcion_Financiador` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_OTRA_CONDICION`
--

CREATE TABLE `MAESTRO_HIS_OTRA_CONDICION` (
  `Id_Otra_Condicion` int(11) NOT NULL,
  `Descripcion_Otra_Condicion` varchar(300) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_PAIS`
--

CREATE TABLE `MAESTRO_HIS_PAIS` (
  `Id_Pais` varchar(3) NOT NULL,
  `Descripcion_Pais` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_PROFESION`
--

CREATE TABLE `MAESTRO_HIS_PROFESION` (
  `Id_Profesion` varchar(2) NOT NULL,
  `Descripcion_Profesion` varchar(150) DEFAULT NULL,
  `Id_Colegio` varchar(2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_TIPO_DOC`
--
CREATE TABLE `MAESTRO_HIS_TIPO_DOC` (
  `Id_Tipo_Documento` int(11) NOT NULL,
  `Abrev_Tipo_Doc` varchar(20) DEFAULT NULL,
  `Descripcion_Tipo_Documento` varchar(250) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Estructura de tabla para la tabla `MAESTRO_HIS_UPS`
--

CREATE TABLE `MAESTRO_HIS_UPS` (
  `Id_Ups` varchar(6) DEFAULT NULL,
  `Descripcion_Ups` varchar(62) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_UPS`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_PACIENTE`
--

CREATE TABLE `MAESTRO_PACIENTE` (
  `Id_Paciente` varchar(50) DEFAULT NULL,
  `Id_Tipo_Documento_Paciente` int(11) DEFAULT NULL,
  `Numero_Documento_Paciente` varchar(15) DEFAULT NULL,
  `Apellido_Paterno_Paciente` varchar(50) DEFAULT NULL,
  `Apellido_Materno_Paciente` varchar(50) DEFAULT NULL,
  `Nombres_Paciente` varchar(150) DEFAULT NULL,
  `Fecha_Nacimiento_Paciente` date DEFAULT NULL,
  `Id_Genero` varchar(1) DEFAULT NULL,
  `Id_Etnia` varchar(2) DEFAULT NULL,
  `Historia_Clinica` varchar(15) DEFAULT NULL,
  `Ficha_Familiar` varchar(15) DEFAULT NULL,
  `Ubigeo_Nacimiento` varchar(6) DEFAULT NULL,
  `Ubigeo_Reniec` varchar(6) DEFAULT NULL,
  `Domicilio_Reniec` varchar(250) DEFAULT NULL,
  `Ubigeo_Declarado` varchar(6) DEFAULT NULL,
  `Domicilio_Declarado` varchar(250) DEFAULT NULL,
  `Referencia_Domicilio` varchar(500) DEFAULT NULL,
  `Id_Pais` varchar(3) DEFAULT NULL,
  `Id_Establecimiento` int(11) DEFAULT NULL,
  `Fecha_Alta` datetime DEFAULT NULL,
  `Fecha_Modificacion` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_PERSONAL`
--

CREATE TABLE `MAESTRO_PERSONAL` (
  `Id_Personal` varchar(50) DEFAULT NULL,
  `Id_Tipo_Documento_Personal` int(11) DEFAULT NULL,
  `Numero_Documento_Personal` varchar(15) DEFAULT NULL,
  `Apellido_Paterno_Personal` varchar(50) DEFAULT NULL,
  `Apellido_Materno_Personal` varchar(50) DEFAULT NULL,
  `Nombres_Personal` varchar(150) DEFAULT NULL,
  `Fecha_Nacimiento_Personal` date DEFAULT NULL,
  `Id_Condicion` varchar(2) DEFAULT NULL,
  `Id_Profesion` varchar(2) DEFAULT NULL,
  `Id_Colegio` varchar(2) DEFAULT NULL,
  `Numero_Colegiatura` varchar(20) DEFAULT NULL,
  `Id_Establecimiento` int(11) DEFAULT NULL,
  `Fecha_Alta` datetime DEFAULT NULL,
  `Fecha_Baja` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_REGISTRADOR`
--

CREATE TABLE `MAESTRO_REGISTRADOR` (
  `Id_Registrador` varchar(50) DEFAULT NULL,
  `Id_Tipo_Documento_Registrador` int(11) DEFAULT NULL,
  `Numero_Documento_Registrador` varchar(15) DEFAULT NULL,
  `Apellido_Paterno_Registrador` varchar(50) DEFAULT NULL,
  `Apellido_Materno_Registrador` varchar(50) DEFAULT NULL,
  `Nombres_Registrador` varchar(150) DEFAULT NULL,
  `Fecha_Nacimiento_Registrador` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `NOMINAL_TRAMA_NUEVO`
--

CREATE TABLE `NOMINAL_TRAMA_NUEVO` (
  `Id_Cita` varchar(50) DEFAULT NULL,
  `Anio` varchar(4) DEFAULT NULL,
  `Mes` varchar(2) DEFAULT NULL,
  `Dia` varchar(2) DEFAULT NULL,
  `Fecha_Atencion` date DEFAULT NULL,
  `Lote` varchar(3) DEFAULT NULL,
  `Num_Pag` int(11) DEFAULT NULL,
  `Num_Reg` int(11) DEFAULT NULL,
  `Id_Ups` varchar(6) DEFAULT NULL,
  `Id_Establecimiento` int(11) DEFAULT NULL,
  `Id_Paciente` varchar(50) DEFAULT NULL,
  `Id_Personal` varchar(50) DEFAULT NULL,
  `Id_Registrador` varchar(50) DEFAULT NULL,
  `Id_Financiador` varchar(2) DEFAULT NULL,
  `Id_Condicion_Establecimiento` varchar(1) DEFAULT NULL,
  `Id_Condicion_Servicio` varchar(1) DEFAULT NULL,
  `Edad_Reg` int(11) DEFAULT NULL,
  `Tipo_Edad` varchar(1) DEFAULT NULL,
  `Anio_Actual_Paciente` int(11) DEFAULT NULL,
  `Mes_Actual_Paciente` int(11) DEFAULT NULL,
  `Dia_Actual_Paciente` int(11) DEFAULT NULL,
  `Id_Turno` varchar(1) DEFAULT NULL,
  `Codigo_Item` varchar(15) DEFAULT NULL,
  `Tipo_Diagnostico` varchar(1) DEFAULT NULL,
  `Valor_Lab` varchar(5) DEFAULT NULL,
  `Id_Correlativo_Item` int(11) DEFAULT NULL,
  `Id_Correlativo_Lab` int(11) DEFAULT NULL,
  `Peso` decimal(10,3) DEFAULT NULL,
  `Talla` decimal(10,2) DEFAULT NULL,
  `Hemoglobina` decimal(6,2) DEFAULT NULL,
  `Perimetro_Abdominal` decimal(10,2) DEFAULT NULL,
  `Perimetro_Cefalico` decimal(10,2) DEFAULT NULL,
  `Id_Otra_Condicion` int(11) DEFAULT NULL,
  `Id_Centro_Poblado` varchar(10) DEFAULT NULL,
  `Fecha_Ultima_Regla` date DEFAULT NULL,
  `Fecha_Solicitud_Hb` date DEFAULT NULL,
  `Fecha_Resultado_Hb` date DEFAULT NULL,
  `Fecha_Registro` datetime DEFAULT NULL,
  `Fecha_Modificacion` datetime DEFAULT NULL,
  `Id_Pais` varchar(3) DEFAULT NULL,
  `gruporiesgo_desc` varchar(50) DEFAULT NULL,
  `condicion_gestante` varchar(50) DEFAULT NULL,
  `Peso_Pregestacional` decimal(10,2) DEFAULT NULL,
  `Id_Dosis` int(11) DEFAULT NULL,
  `renipress` varchar(50) DEFAULT NULL,
  `Id_Institucion_Edu` varchar(10) DEFAULT NULL,
  `Id_AplicacionOrigen` int(11) DEFAULT NULL,
  `Alerta` varchar(3000) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `NOMINAL_TRAMA_PERIODOS`
--

CREATE TABLE `NOMINAL_TRAMA_PERIODOS` (
  `id` int(11) NOT NULL,
  `periodo_anio` varchar(4) NOT NULL,
  `periodo_mes` varchar(2) NOT NULL,
  `importado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_importacion` datetime DEFAULT current_timestamp(),
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `registros_importados` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`
--

CREATE TABLE `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (
  `Id_Cita` varchar(50) DEFAULT NULL,
  `Anio` varchar(4) DEFAULT NULL,
  `Mes` varchar(2) DEFAULT NULL,
  `Dia` varchar(2) DEFAULT NULL,
  `Fecha_Atencion` date DEFAULT NULL,
  `Lote` varchar(3) DEFAULT NULL,
  `Num_Pag` int(11) DEFAULT NULL,
  `Num_Reg` int(11) DEFAULT NULL,
  `Id_Ups` varchar(6) DEFAULT NULL,
  `Descripcion_Ups` varchar(800) DEFAULT NULL,
  `Id_AplicacionOrigen` int(11) DEFAULT NULL,
  `Alerta` varchar(3000) DEFAULT NULL,
  `Id_Institucion_Edu` varchar(10) DEFAULT NULL,
  `Id_Establecimiento` int(11) DEFAULT NULL,
  `Codigo_Sector` int(11) DEFAULT NULL,
  `Descripcion_Sector` varchar(50) DEFAULT NULL,
  `Codigo_Disa` int(11) DEFAULT NULL,
  `Descripcion_Disa` varchar(80) DEFAULT NULL,
  `Codigo_Red` char(2) DEFAULT NULL,
  `Descripcion_Red` varchar(70) DEFAULT NULL,
  `Codigo_MicroRed` char(2) DEFAULT NULL,
  `Descripcion_MicroRed` varchar(70) DEFAULT NULL,
  `Codigo_Unico` varchar(9) DEFAULT NULL,
  `Nombre_Establecimiento` varchar(100) DEFAULT NULL,
  `Ubigueo_Establecimiento` char(6) DEFAULT NULL,
  `Departamento_Establecimiento` varchar(150) DEFAULT NULL,
  `Provincia_Establecimiento` varchar(150) DEFAULT NULL,
  `Distrito_Establecimiento` varchar(150) DEFAULT NULL,
  `Id_Paciente` varchar(50) DEFAULT NULL,
  `Tipo_Doc_Paciente` int(11) DEFAULT NULL,
  `Abrev_Tipo_Doc_Paciente` varchar(20) DEFAULT NULL,
  `Numero_Documento_Paciente` varchar(15) DEFAULT NULL,
  `Apellido_Paterno_Paciente` varchar(50) DEFAULT NULL,
  `Apellido_Materno_Paciente` varchar(50) DEFAULT NULL,
  `Nombres_Paciente` varchar(150) DEFAULT NULL,
  `Fecha_Nacimiento_Paciente` date DEFAULT NULL,
  `Id_Genero` varchar(1) DEFAULT NULL,
  `Historia_Clinica` varchar(15) DEFAULT NULL,
  `Ficha_Familiar` varchar(50) DEFAULT NULL,
  `Id_Etnia` varchar(2) DEFAULT NULL,
  `Descripcion_Etnia` varchar(100) DEFAULT NULL,
  `Id_Financiador` varchar(2) DEFAULT NULL,
  `Descripcion_Financiador` varchar(100) DEFAULT NULL,
  `Id_Pais` varchar(3) DEFAULT NULL,
  `Descripcion_Pais` varchar(100) DEFAULT NULL,
  `Id_Personal` varchar(50) DEFAULT NULL,
  `Tipo_Doc_Personal` int(11) DEFAULT NULL,
  `Abrev_Tipo_Doc_Personal` varchar(20) DEFAULT NULL,
  `Numero_Documento_Personal` varchar(15) DEFAULT NULL,
  `Apellido_Paterno_Personal` varchar(50) DEFAULT NULL,
  `Apellido_Materno_Personal` varchar(50) DEFAULT NULL,
  `Nombres_Personal` varchar(150) DEFAULT NULL,
  `Fecha_Nacimiento_Personal` date DEFAULT NULL,
  `Id_Condicion` int(11) DEFAULT NULL,
  `Descripcion_Condicion` varchar(500) DEFAULT NULL,
  `Id_Profesion` varchar(2) DEFAULT NULL,
  `Descripcion_Profesion` varchar(150) DEFAULT NULL,
  `Id_Colegio` varchar(2) DEFAULT NULL,
  `Descripcion_Colegio` varchar(800) DEFAULT NULL,
  `Numero_Colegiatura` varchar(20) DEFAULT NULL,
  `Id_Registrador` varchar(50) DEFAULT NULL,
  `Tipo_Doc_Registrador` int(11) DEFAULT NULL,
  `Abrev_Tipo_Doc_Registrador` varchar(20) DEFAULT NULL,
  `Numero_Documento_Registrador` varchar(15) DEFAULT NULL,
  `Apellido_Paterno_Registrador` varchar(50) DEFAULT NULL,
  `Apellido_Materno_Registrador` varchar(50) DEFAULT NULL,
  `Nombres_Registrador` varchar(150) DEFAULT NULL,
  `Fecha_Nacimiento_Registrador` date DEFAULT NULL,
  `Id_Condicion_Establecimiento` varchar(1) DEFAULT NULL,
  `Id_Condicion_Servicio` varchar(1) DEFAULT NULL,
  `Edad_Reg` int(11) DEFAULT NULL,
  `Tipo_Edad` varchar(1) DEFAULT NULL,
  `Anio_Actual_Paciente` int(11) DEFAULT NULL,
  `Mes_Actual_Paciente` int(11) DEFAULT NULL,
  `Dia_Actual_Paciente` int(11) DEFAULT NULL,
  `Grupo_Edad` varchar(13) DEFAULT NULL,
  `peso_pregestacional` decimal(10,2) DEFAULT NULL,
  `Id_Turno` varchar(1) DEFAULT NULL,
  `Fg_Tipo` varchar(2) DEFAULT NULL,
  `Codigo_Item` varchar(15) DEFAULT NULL,
  `Descripcion_Item` varchar(400) DEFAULT NULL,
  `Tipo_Diagnostico` varchar(1) DEFAULT NULL,
  `Valor_Lab` varchar(5) DEFAULT NULL,
  `Id_Correlativo_Item` int(11) DEFAULT NULL,
  `Id_Correlativo_Lab` int(11) DEFAULT NULL,
  `Peso` decimal(10,3) DEFAULT NULL,
  `Talla` decimal(10,2) DEFAULT NULL,
  `Hemoglobina` decimal(6,2) DEFAULT NULL,
  `Perimetro_Abdominal` decimal(10,2) DEFAULT NULL,
  `Perimetro_Cefalico` decimal(10,2) DEFAULT NULL,
  `Id_Otra_Condicion` int(11) DEFAULT NULL,
  `Descripcion_Otra_Condicion` varchar(300) DEFAULT NULL,
  `Id_Centro_Poblado` varchar(10) DEFAULT NULL,
  `Descripcion_Centro_Poblado` varchar(350) DEFAULT NULL,
  `Id_Codigo_Centro_Poblado` varchar(4) DEFAULT NULL,
  `Id_Ubigueo_Centro_Poblado` varchar(6) DEFAULT NULL,
  `Altitud_Centro_Poblado` decimal(7,2) DEFAULT NULL,
  `Fecha_Ultima_Regla` date DEFAULT NULL,
  `Fecha_Solicitud_Hb` date DEFAULT NULL,
  `Fecha_Resultado_Hb` date DEFAULT NULL,
  `Fecha_Registro` datetime DEFAULT NULL,
  `Fecha_Modificacion` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`
--

INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416820139', '2026', '6', '13', '2026-06-13', 'CAR', 61, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58300957330', 1, 'DNI', '94345419', 'QUICHA', 'ECHEVARRIA', 'ALBA ANTHONELLA', '2025-08-12', 'F', '94345419', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741444330', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741444', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 'C', 'C', 10, 'M', 0, 10, 1, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-28 00:00:00', NULL),
('1416812234', '2026', '6', '17', '2026-06-17', 'CAR', 59, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '51813947330', 1, 'DNI', '93350056', 'VASQUEZ', 'OSORIO', 'GIAN YOSHI', '2023-04-17', 'M', '93350056', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741444330', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741444', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 'C', 'R', 3, 'A', 3, 2, 0, '01 a 04 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-28 00:00:00', NULL),
('1416815560', '2026', '6', '17', '2026-06-17', 'CAR', 60, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53283971330', 1, 'DNI', '93755286', 'CONDOR', 'VIDALON', 'EITHAN JOAB', '2024-03-12', 'M', '93755286', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741444330', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741444', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 'C', 'R', 2, 'A', 2, 3, 5, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-28 00:00:00', NULL),
('1416820405', '2026', '6', '15', '2026-06-15', 'CAR', 62, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58634174330', 1, 'DNI', '94449471', 'PEREZ', 'ESPINOZA', 'AYLANI AITANA', '2025-11-15', 'F', '94449471', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741444330', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741444', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 'C', 'C', 7, 'M', 0, 7, 0, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-28 00:00:00', NULL),
('1416643906', '2026', '6', '26', '2026-06-26', 'CAR', 581, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '20758907318', 1, 'DNI', '42590846', 'SIVIPAUCAR', 'LINO', 'JOSE LUIS', '1984-09-15', 'M', '42590846', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 41, 'A', 41, 9, 11, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416644037', '2026', '6', '26', '2026-06-26', 'CAR', 582, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '16627118318', 1, 'DNI', '20588480', 'ARIAS', 'RUIZ', 'MARCELINO', '1968-06-02', 'M', '20588480', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 58, 'A', 58, 0, 24, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416643794', '2026', '6', '26', '2026-06-26', 'CAR', 580, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '35742241318', 1, 'DNI', '45036664', 'OSPINA', 'CANEVARO', 'IVAN RICHARD', '1988-02-21', 'M', '45036664', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 38, 'A', 38, 4, 5, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641709', '2026', '6', '26', '2026-06-26', 'CAR', 569, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2827917318', 1, 'DNI', '42459656', 'ALCANTARA', 'PEREZ', 'NICETA', '1975-04-05', 'F', '42459656', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 51, 'A', 51, 2, 21, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641709', '2026', '6', '26', '2026-06-26', 'CAR', 569, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2827917318', 1, 'DNI', '42459656', 'ALCANTARA', 'PEREZ', 'NICETA', '1975-04-05', 'F', '42459656', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 51, 'A', 51, 2, 21, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641870', '2026', '6', '26', '2026-06-26', 'CAR', 570, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '910484318', 1, 'DNI', '73978144', 'HUAMAN', 'BERAUN', 'JACKELIN ANA', '2005-05-12', 'F', '73978144', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 21, 'A', 21, 1, 14, '18 a 29 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641870', '2026', '6', '26', '2026-06-26', 'CAR', 570, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '910484318', 1, 'DNI', '73978144', 'HUAMAN', 'BERAUN', 'JACKELIN ANA', '2005-05-12', 'F', '73978144', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 21, 'A', 21, 1, 14, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642060', '2026', '6', '26', '2026-06-26', 'CAR', 571, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4402312318', 1, 'DNI', '20527783', 'FERNANDEZ', 'YUPANQUI', 'IRENE', '1962-05-19', 'F', '20527783', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 64, 'A', 64, 1, 7, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642205', '2026', '6', '26', '2026-06-26', 'CAR', 572, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '16557120318', 1, 'DNI', '20535146', 'ZUZUNAGA', 'AMANCAY', 'JORGE JESUS', '1958-08-24', 'M', '20535146', '28826', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 67, 'A', 67, 10, 2, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642479', '2026', '6', '26', '2026-06-26', 'CAR', 574, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4163873318', 1, 'DNI', '20587246', 'YRCAÑAUPA', 'OREJON', 'NORMA GLADYS', '1975-09-28', 'F', '20587246', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 50, 'A', 50, 8, 29, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642479', '2026', '6', '26', '2026-06-26', 'CAR', 574, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4163873318', 1, 'DNI', '20587246', 'YRCAÑAUPA', 'OREJON', 'NORMA GLADYS', '1975-09-28', 'F', '20587246', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 50, 'A', 50, 8, 29, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642950', '2026', '6', '26', '2026-06-26', 'CAR', 576, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2211988318', 1, 'DNI', '60806733', 'USQUIANO', 'IGLESIAS', 'SARAITH', '2003-01-17', 'F', '60806733', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 23, 'A', 23, 5, 9, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416643086', '2026', '6', '26', '2026-06-26', 'CAR', 577, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '872876318', 1, 'DNI', '46640674', 'PARIACHE', 'CARDENAS', 'PERCY ROLANDO', '1988-06-05', 'M', '46640674', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 38, 'A', 38, 0, 21, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416643670', '2026', '6', '26', '2026-06-26', 'CAR', 579, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3943438318', 1, 'DNI', '76986222', 'PEREZ', 'COMPISHURI', 'YORDI MIGUEL', '2003-01-13', 'M', '76986222', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 23, 'A', 23, 5, 13, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640363', '2026', '6', '26', '2026-06-26', 'CAR', 561, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2805805318', 1, 'DNI', '48389257', 'DIAZ', 'SOTO', 'JUSTINA', '1972-09-26', 'F', '48389257', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 53, 'A', 53, 9, 0, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640517', '2026', '6', '26', '2026-06-26', 'CAR', 562, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1286850318', 1, 'DNI', '46018989', 'HUAMAN', 'TACUSI', 'LUZ VERONICA', '1988-10-10', 'F', '46018989', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 37, 'A', 37, 8, 16, '30 a 59 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640517', '2026', '6', '26', '2026-06-26', 'CAR', 562, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1286850318', 1, 'DNI', '46018989', 'HUAMAN', 'TACUSI', 'LUZ VERONICA', '1988-10-10', 'F', '46018989', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 37, 'A', 37, 8, 16, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640810', '2026', '6', '26', '2026-06-26', 'CAR', 563, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4477300318', 1, 'DNI', '60083543', 'LAZARO', 'PAREDES', 'FRITZ BRAYAN', '2007-12-25', 'M', '60083543', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 18, 'A', 18, 6, 1, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641038', '2026', '6', '26', '2026-06-26', 'CAR', 565, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1858746318', 1, 'DNI', '46848258', 'OSCCO', 'PEREZ', 'LUZ MERY', '1990-03-03', 'F', '46848258', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 36, 'A', 36, 3, 23, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641157', '2026', '6', '26', '2026-06-26', 'CAR', 566, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1259408318', 1, 'DNI', '20741999', 'PAREDES', 'MANCHI', 'NICOLASA', '1977-08-24', 'F', '20741999', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 48, 'A', 48, 10, 2, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637278', '2026', '6', '26', '2026-06-26', 'CAR', 546, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '880692318', 1, 'DNI', '42525405', 'CARHUALLANQUI', 'DE LA CRUZ', 'GIOVANA MARILU', '1983-03-20', 'F', '42525405', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 43, 'A', 43, 3, 6, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637420', '2026', '6', '26', '2026-06-26', 'CAR', 547, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '36783235318', 1, 'DNI', '41149906', 'PORTA', 'QUINTANILLA', 'KETTY AMANDA', '1981-11-28', 'F', '41149906', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 44, 'A', 44, 6, 29, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637614', '2026', '6', '26', '2026-06-26', 'CAR', 548, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '37586280318', 1, 'DNI', '91035701', 'HUACHACA', 'CALDERON', 'THAILY ARACELI', '2018-10-15', 'F', '91035701', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 7, 'A', 7, 8, 11, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637745', '2026', '6', '26', '2026-06-26', 'CAR', 549, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1923868318', 1, 'DNI', '76164701', 'CALDERON', 'ÑACAYAURI', 'EDITH ERIKA', '1997-03-29', 'F', '76164701', '77072', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 29, 'A', 29, 2, 28, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637874', '2026', '6', '26', '2026-06-26', 'CAR', 550, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2927380318', 1, 'DNI', '44505803', 'ARELLANO', 'PEREZ', 'WILLINGTON RONALD', '1987-08-24', 'M', '44505803', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 38, 'A', 38, 10, 2, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637874', '2026', '6', '26', '2026-06-26', 'CAR', 550, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2927380318', 1, 'DNI', '44505803', 'ARELLANO', 'PEREZ', 'WILLINGTON RONALD', '1987-08-24', 'M', '44505803', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 38, 'A', 38, 10, 2, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416638376', '2026', '6', '26', '2026-06-26', 'CAR', 552, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5098595318', 1, 'DNI', '62854834', 'USQUIANO', 'IGLESIAS', 'ELISEO ELIAS', '2012-03-01', 'M', '62854834', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 14, 'A', 14, 3, 25, '12 a 17 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416638707', '2026', '6', '26', '2026-06-26', 'CAR', 553, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2560544318', 1, 'DNI', '47655842', 'LOPEZ', 'ASPUR', 'CELEDONIA', '1984-03-18', 'F', '47655842', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 42, 'A', 42, 3, 8, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416638882', '2026', '6', '26', '2026-06-26', 'CAR', 554, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2982461318', 1, 'DNI', '76671749', 'USQUIANO', 'LOPEZ', 'EFRAIN', '2006-02-17', 'M', '76671749', '55621', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 20, 'A', 20, 4, 9, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416638882', '2026', '6', '26', '2026-06-26', 'CAR', 554, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2982461318', 1, 'DNI', '76671749', 'USQUIANO', 'LOPEZ', 'EFRAIN', '2006-02-17', 'M', '76671749', '55621', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 20, 'A', 20, 4, 9, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416639372', '2026', '6', '26', '2026-06-26', 'CAR', 556, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '872345318', 1, 'DNI', '48258392', 'AGUIRRE', 'VILLANUEVA', 'AVELINA ROSARIO', '1993-06-05', 'F', '48258392', '63609', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 33, 'A', 33, 0, 21, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416639534', '2026', '6', '26', '2026-06-26', 'CAR', 557, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2922624318', 1, 'DNI', '40256663', 'BARRIENTOS', 'MISAYAURI', 'NANCY GRACIELA', '1979-06-15', 'F', '40256663', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 47, 'A', 47, 0, 11, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416639823', '2026', '6', '26', '2026-06-26', 'CAR', 558, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1629558318', 1, 'DNI', '44549696', 'BORJA', 'MARTINEZ', 'ROSA', '1987-06-30', 'F', '44549696', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 38, 'A', 38, 11, 27, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640147', '2026', '6', '26', '2026-06-26', 'CAR', 560, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1191309318', 1, 'DNI', '44549712', 'DE LA CRUZ', 'PAREDES', 'ELGA', '1986-11-25', 'F', '44549712', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 39, 'A', 39, 7, 1, '30 a 59 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640147', '2026', '6', '26', '2026-06-26', 'CAR', 560, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1191309318', 1, 'DNI', '44549712', 'DE LA CRUZ', 'PAREDES', 'ELGA', '1986-11-25', 'F', '44549712', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 39, 'A', 39, 7, 1, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416636932', '2026', '6', '26', '2026-06-26', 'CAR', 544, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '899711318', 1, 'DNI', '73460315', 'VASQUEZ', 'PEREZ', 'BRAYAN EMILIANO', '1997-06-29', 'M', '73460315', '32261', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 28, 'A', 28, 11, 28, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416632547', '2026', '6', '26', '2026-06-26', 'CAR', 518, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3686613318', 1, 'DNI', '20529071', 'JIMENEZ', 'VICTORIO', 'TOMASA', '1936-12-29', 'F', '20529071', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 89, 'A', 89, 5, 28, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416632694', '2026', '6', '26', '2026-06-26', 'CAR', 519, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4224876318', 1, 'DNI', '60958329', 'BELITO', 'ARELLANO', 'SEBASTIAN MICHAEL YEFRITH', '2007-02-23', 'M', '60958329', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 19, 'A', 19, 4, 3, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416632977', '2026', '6', '26', '2026-06-26', 'CAR', 521, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2589660318', 1, 'DNI', '47881070', 'MONTES', 'GALARZA', 'JUAN ANTONIO', '1992-11-16', 'M', '47881070', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 33, 'A', 33, 7, 10, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416632977', '2026', '6', '26', '2026-06-26', 'CAR', 521, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2589660318', 1, 'DNI', '47881070', 'MONTES', 'GALARZA', 'JUAN ANTONIO', '1992-11-16', 'M', '47881070', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 33, 'A', 33, 7, 10, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416633205', '2026', '6', '26', '2026-06-26', 'CAR', 522, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '39981619318', 1, 'DNI', '20587256', 'YUNCA', 'VIDELA', 'VICTORIANO', '1975-11-10', 'M', '20587256', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 50, 'A', 50, 7, 16, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416633330', '2026', '6', '26', '2026-06-26', 'CAR', 523, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1021749318', 1, 'DNI', '71580784', 'HUAMAN', 'PORRAS', 'MARIBEL ISABEL', '1999-10-13', 'F', '71580784', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 26, 'A', 26, 8, 13, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416633512', '2026', '6', '26', '2026-06-26', 'CAR', 524, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4615985318', 1, 'DNI', '71580789', 'HUAMAN', 'PORRAS', 'ELMER ARMANDO', '2005-03-24', 'M', '71580789', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 21, 'A', 21, 3, 2, '18 a 29 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416633512', '2026', '6', '26', '2026-06-26', 'CAR', 524, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4615985318', 1, 'DNI', '71580789', 'HUAMAN', 'PORRAS', 'ELMER ARMANDO', '2005-03-24', 'M', '71580789', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 21, 'A', 21, 3, 2, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416633797', '2026', '6', '26', '2026-06-26', 'CAR', 525, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4364336318', 1, 'DNI', '45598028', 'LUIS', 'MENDOZA', 'YUDIT YESSENIA', '1989-01-21', 'F', '45598028', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 37, 'A', 37, 5, 5, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416633917', '2026', '6', '26', '2026-06-26', 'CAR', 526, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '775862318', 1, 'DNI', '71549013', 'TICLLACURI', 'QUISPE', 'SARAYET ANITA', '1999-12-03', 'F', '71549013', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 26, 'A', 26, 6, 23, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634014', '2026', '6', '26', '2026-06-26', 'CAR', 527, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8585081318', 1, 'DNI', '41244419', 'MITMA', 'HUANQUIS', 'ELIZABETH RUTH', '1982-06-19', 'F', '41244419', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 44, 'A', 44, 0, 7, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634416', '2026', '6', '26', '2026-06-26', 'CAR', 529, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1189080318', 1, 'DNI', '71580790', 'HUAMAN', 'PORRAS', 'NILO DANIEL', '2003-01-22', 'M', '71580790', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 23, 'A', 23, 5, 4, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634581', '2026', '6', '26', '2026-06-26', 'CAR', 530, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '45561478318', 1, 'DNI', '92411828', 'TINOCO', 'CARHUALLANQUI', 'DANAÉ ARIANA VALENTINA', '2021-06-19', 'F', '92411828', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 0, 7, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634698', '2026', '6', '26', '2026-06-26', 'CAR', 531, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '44990029318', 1, 'DNI', '92309981', 'BARTOLO', 'LOA', 'XIMENA ANALI', '2021-04-10', 'F', '92309981', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 2, 16, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416635057', '2026', '6', '26', '2026-06-26', 'CAR', 534, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '44731263318', 1, 'DNI', '92271053', 'RAMOS', 'INGA', 'MAFFER DULCE', '2021-03-14', 'F', '92271053', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 3, 12, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416635198', '2026', '6', '26', '2026-06-26', 'CAR', 535, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '44989331318', 1, 'DNI', '92313090', 'CARRASCO', 'BARRIENTOS', 'ANDRÉ BENJAMIN', '2021-04-13', 'M', '92313090', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 2, 13, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416635362', '2026', '6', '26', '2026-06-26', 'CAR', 536, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1693415318', 1, 'DNI', '70242403', 'CASTILLO', 'ALIANO', 'JESUS ANGEL', '1992-12-28', 'M', '70242403', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 33, 'A', 33, 5, 29, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416635647', '2026', '6', '26', '2026-06-26', 'CAR', 537, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '45558634318', 1, 'DNI', '92426841', 'CAMPOS', 'PINO', 'MIA VALENTINA', '2021-06-30', 'F', '92426841', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 4, 'A', 4, 11, 27, '01 a 04 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416635965', '2026', '6', '26', '2026-06-26', 'CAR', 539, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2585552318', 1, 'DNI', '20642163', 'VICUÑA', 'PERALTA', 'ARTURO EMILIO', '1962-12-06', 'M', '20642163', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 63, 'A', 63, 6, 20, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416636105', '2026', '6', '26', '2026-06-26', 'CAR', 540, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6578130318', 1, 'DNI', '42739304', 'HUAMANI', 'CONTRERAS', 'SANDRO ROBINSON', '1984-11-21', 'M', '42739304', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 41, 'A', 41, 7, 5, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416636381', '2026', '6', '26', '2026-06-26', 'CAR', 541, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4500014318', 1, 'DNI', '76510673', 'CONOVILCA', 'GONZALES', 'MARITZA MARIELA', '1999-01-10', 'F', '76510673', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 27, 'A', 27, 5, 16, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416636381', '2026', '6', '26', '2026-06-26', 'CAR', 541, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4500014318', 1, 'DNI', '76510673', 'CONOVILCA', 'GONZALES', 'MARITZA MARIELA', '1999-01-10', 'F', '76510673', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 27, 'A', 27, 5, 16, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416636674', '2026', '6', '26', '2026-06-26', 'CAR', 542, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4370865318', 1, 'DNI', '20587062', 'SUSANIBAR', 'AYALA', 'KENI SOLEDAD', '1975-07-16', 'F', '20587062', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 50, 'A', 50, 11, 10, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416636799', '2026', '6', '26', '2026-06-26', 'CAR', 543, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1766810318', 1, 'DNI', '20030849', 'GONZALO', 'PALOMINO', 'VICTORIA FORTUNATA', '1970-02-26', 'F', '20030849', '51033', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 56, 'A', 56, 4, 0, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416497216', '2026', '6', '27', '2026-06-27', 'CAR', 512, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1867336318', 1, 'DNI', '76518801', 'MARMOLEJO', 'OSCANOA', 'MERCY ANDREA', '2001-06-02', 'F', '76518801', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 25, 'A', 25, 0, 25, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416498794', '2026', '6', '27', '2026-06-27', 'CAR', 513, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '57758786318', 1, 'DNI', '94190930', 'QUIÑONEZ', 'HUALPA', 'ABEL BENJAMIN', '2025-03-27', 'M', '94190930', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 1, 'A', 1, 3, 0, '01 a 04 años', NULL, 'T', 'CP', '90717', 'VACUNA VIVA CONTRA LA FIEBRE AMARILLA PARA USO SUBCUTÁNEO', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416498794', '2026', '6', '27', '2026-06-27', 'CAR', 513, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '57758786318', 1, 'DNI', '94190930', 'QUIÑONEZ', 'HUALPA', 'ABEL BENJAMIN', '2025-03-27', 'M', '94190930', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 1, 'A', 1, 3, 0, '01 a 04 años', NULL, 'T', 'CP', '90633.01', 'VACUNA CONTRA HEPATITIS A (HAV) MONODOSIS PEDIÁTRICA PARA NIÑOS DE 15 MESES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416499412', '2026', '6', '27', '2026-06-27', 'CAR', 514, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58982447318', 1, 'DNI', '94562016', 'MENDOZA', 'VITOR', 'EMILIANO VALENTINO', '2026-02-27', 'M', '94562016', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 4, 'M', 0, 4, 0, '01 a 11 meses', NULL, 'T', 'CP', '90722', 'VACUNA DPT-HVB-HIB', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416499412', '2026', '6', '27', '2026-06-27', 'CAR', 514, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58982447318', 1, 'DNI', '94562016', 'MENDOZA', 'VITOR', 'EMILIANO VALENTINO', '2026-02-27', 'M', '94562016', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 4, 'M', 0, 4, 0, '01 a 11 meses', NULL, 'T', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', '2', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416499412', '2026', '6', '27', '2026-06-27', 'CAR', 514, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58982447318', 1, 'DNI', '94562016', 'MENDOZA', 'VITOR', 'EMILIANO VALENTINO', '2026-02-27', 'M', '94562016', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 4, 'M', 0, 4, 0, '01 a 11 meses', NULL, 'T', 'CP', '90681', 'VACUNA CONTRA EL ROTAVIRUS HUMANO ATENUADA ESQUEMA DE 2 DOSIS VIVO PARA USO ORAL', 'D', '2', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416499412', '2026', '6', '27', '2026-06-27', 'CAR', 514, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58982447318', 1, 'DNI', '94562016', 'MENDOZA', 'VITOR', 'EMILIANO VALENTINO', '2026-02-27', 'M', '94562016', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 4, 'M', 0, 4, 0, '01 a 11 meses', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', '2', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416500973', '2026', '6', '27', '2026-06-27', 'CAR', 515, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58588478318', 1, 'DNI', '94438928', 'AIQUIPA', 'GUTIERREZ', 'VALERIA NOEMÍ', '2025-11-06', 'F', '94438928', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 7, 'M', 0, 7, 21, '01 a 11 meses', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416501311', '2026', '6', '27', '2026-06-27', 'CAR', 516, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58487652318', 1, 'DNI', '94407536', 'BENITES', 'CLEMENTE', 'LIONEL GRIMALD', '2025-10-07', 'M', '94407536', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 8, 'M', 0, 8, 20, '01 a 11 meses', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'D1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416503536', '2026', '6', '27', '2026-06-27', 'CAR', 517, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '59353162318', 6, 'CNV', '94697861', 'MAYHUA', 'BOZA', 'RN', '2026-06-27', 'F', '94697861', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'N', 'N', 1, 'D', 0, 0, 0, '01 a 29 dias', NULL, 'T', 'CP', '90744', 'VACUNA CONTRA LA HEPATITIS B DOSIS PEDIÁTRICA/ADOLESCENTE (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416503536', '2026', '6', '27', '2026-06-27', 'CAR', 517, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '59353162318', 6, 'CNV', '94697861', 'MAYHUA', 'BOZA', 'RN', '2026-06-27', 'F', '94697861', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'N', 'N', 1, 'D', 0, 0, 0, '01 a 29 dias', NULL, 'T', 'CP', '90585', 'VACUNA VIVA DE BACILO DE CALMETTE-GUÉRIN (BCG) CONTRA LA TUBERCULOSIS PARA USO PERCUTÁNEO', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416496495', '2026', '6', '27', '2026-06-27', 'CAR', 510, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1660497318', 1, 'DNI', '76779731', 'JORGE', 'ÑAHUI', 'LIZETH EDITH', '2002-05-13', 'F', '76779731', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 24, 'A', 24, 1, 14, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416496837', '2026', '6', '27', '2026-06-27', 'CAR', 511, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4345527318', 1, 'DNI', '60903266', 'GUTIERREZ', 'TUNCAR', 'LIBNI MERARI', '2007-01-24', 'F', '60903266', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'R', 'R', 19, 'A', 19, 5, 3, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416459085', '2026', '6', '27', '2026-06-27', 'CAR', 503, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58426690318', 1, 'DNI', '94385446', 'SANCHEZ', 'HERRERA', 'LUCYANA MEDALY', '2025-09-17', 'F', '94385446', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 9, 'M', 0, 9, 10, '01 a 11 meses', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416461428', '2026', '6', '27', '2026-06-27', 'CAR', 504, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58960192318', 1, 'DNI', '94560376', 'YZARRA', 'TORRES', 'BENJAMÍN HÉCTOR', '2026-02-26', 'M', '94560376', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 4, 'M', 0, 4, 1, '01 a 11 meses', NULL, 'T', 'CP', '90722', 'VACUNA DPT-HVB-HIB', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416461428', '2026', '6', '27', '2026-06-27', 'CAR', 504, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58960192318', 1, 'DNI', '94560376', 'YZARRA', 'TORRES', 'BENJAMÍN HÉCTOR', '2026-02-26', 'M', '94560376', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 4, 'M', 0, 4, 1, '01 a 11 meses', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', '2', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416461428', '2026', '6', '27', '2026-06-27', 'CAR', 504, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58960192318', 1, 'DNI', '94560376', 'YZARRA', 'TORRES', 'BENJAMÍN HÉCTOR', '2026-02-26', 'M', '94560376', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 4, 'M', 0, 4, 1, '01 a 11 meses', NULL, 'T', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', '2', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416461428', '2026', '6', '27', '2026-06-27', 'CAR', 504, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58960192318', 1, 'DNI', '94560376', 'YZARRA', 'TORRES', 'BENJAMÍN HÉCTOR', '2026-02-26', 'M', '94560376', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 4, 'M', 0, 4, 1, '01 a 11 meses', NULL, 'T', 'CP', '90681', 'VACUNA CONTRA EL ROTAVIRUS HUMANO ATENUADA ESQUEMA DE 2 DOSIS VIVO PARA USO ORAL', 'D', '2', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416471807', '2026', '6', '27', '2026-06-27', 'CAR', 28, 21, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58549220320', 5, 'S/ DOCUMENTO', 'SD-N00901365', 'QUINCHOCRE', 'SANABRIA', 'RN', '2025-10-22', 'M', '94423217', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '10399304320', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '10399304', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 'C', 'C', 8, 'M', 0, 8, 5, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416484760', '2026', '6', '24', '2026-06-24', 'CAR', 33, 9, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58747591330', 1, 'DNI', '94490051', 'SANTOS', 'LAUPA', 'ALESSANDRO GHESER', '2025-12-24', 'M', '94490051', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 6, 'M', 0, 6, 0, '01 a 11 meses', NULL, 'M', 'CP', '90722', 'VACUNA DPT-HVB-HIB', 'D', '3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416484760', '2026', '6', '24', '2026-06-24', 'CAR', 33, 9, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58747591330', 1, 'DNI', '94490051', 'SANTOS', 'LAUPA', 'ALESSANDRO GHESER', '2025-12-24', 'M', '94490051', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 6, 'M', 0, 6, 0, '01 a 11 meses', NULL, 'M', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', '3', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416484760', '2026', '6', '24', '2026-06-24', 'CAR', 33, 9, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58747591330', 1, 'DNI', '94490051', 'SANTOS', 'LAUPA', 'ALESSANDRO GHESER', '2025-12-24', 'M', '94490051', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 6, 'M', 0, 6, 0, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416485955', '2026', '6', '24', '2026-06-24', 'CAR', 33, 10, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58370610330', 1, 'DNI', '94357355', 'RODRIGUEZ', 'ISIDORO', 'ALEXANDRA', '2025-08-23', 'F', '94357355', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 10, 'M', 0, 10, 1, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416486469', '2026', '6', '24', '2026-06-24', 'CAR', 33, 11, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58734134330', 1, 'DNI', '94483778', 'HUARACA', 'VERA', 'KAEL ELIAM', '2025-12-18', 'M', '94483778', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 6, 'M', 0, 6, 6, '01 a 11 meses', NULL, 'M', 'CP', '90722', 'VACUNA DPT-HVB-HIB', 'D', '3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416486469', '2026', '6', '24', '2026-06-24', 'CAR', 33, 11, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58734134330', 1, 'DNI', '94483778', 'HUARACA', 'VERA', 'KAEL ELIAM', '2025-12-18', 'M', '94483778', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 6, 'M', 0, 6, 6, '01 a 11 meses', NULL, 'M', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', '3', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416486469', '2026', '6', '24', '2026-06-24', 'CAR', 33, 11, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58734134330', 1, 'DNI', '94483778', 'HUARACA', 'VERA', 'KAEL ELIAM', '2025-12-18', 'M', '94483778', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 6, 'M', 0, 6, 6, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416487584', '2026', '6', '25', '2026-06-25', 'CAR', 33, 13, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58606957330', 1, 'DNI', '94421580', 'PARRA', 'CAMAÑAL', 'JAYLIN LUCIA', '2025-10-20', 'F', '94421580', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 8, 'M', 0, 8, 5, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416488261', '2026', '6', '25', '2026-06-25', 'CAR', 33, 14, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53411050330', 1, 'DNI', '93794597', 'MORALES', 'AURELIO', 'IZAN LEO', '2024-04-12', 'M', '93794597', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 2, 'A', 2, 2, 13, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416492824', '2026', '6', '27', '2026-06-27', 'CAR', 505, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '43573597318', 1, 'DNI', '91904683', 'HURTADO', 'QUILCA', 'EMIR SAIR', '2020-06-24', 'M', '91904683', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 6, 'A', 6, 0, 3, '05 a 11 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493180', '2026', '6', '27', '2026-06-27', 'CAR', 506, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8145376318', 1, 'DNI', '48675893', 'QUILCA', 'JORGE', 'YARI MISTICA', '1995-11-21', 'F', '48675893', '76188', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 30, 'A', 30, 7, 6, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493664', '2026', '6', '24', '2026-06-24', 'CAR', 33, 15, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '784441330', 1, 'DNI', '71016137', 'QUINCHORI', 'CARDENAS', 'MARY TANIA', '1997-06-19', 'F', '71016137', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 29, 'A', 29, 0, 5, '18 a 29 años', NULL, 'M', 'CP', '90714', 'TOXOIDE TETÁNICO Y DIFETÉRICO (TD) ADSOBIDO LIBRE DE PRESERVANTE CUANDO SE ADMINISTRA EN INDIVIDUOS DE 7 AÑOS O MAYORES PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493664', '2026', '6', '24', '2026-06-24', 'CAR', 33, 15, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '784441330', 1, 'DNI', '71016137', 'QUINCHORI', 'CARDENAS', 'MARY TANIA', '1997-06-19', 'F', '71016137', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 29, 'A', 29, 0, 5, '18 a 29 años', NULL, 'M', 'CP', '90714', 'TOXOIDE TETÁNICO Y DIFETÉRICO (TD) ADSOBIDO LIBRE DE PRESERVANTE CUANDO SE ADMINISTRA EN INDIVIDUOS DE 7 AÑOS O MAYORES PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493664', '2026', '6', '24', '2026-06-24', 'CAR', 33, 15, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '784441330', 1, 'DNI', '71016137', 'QUINCHORI', 'CARDENAS', 'MARY TANIA', '1997-06-19', 'F', '71016137', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 29, 'A', 29, 0, 5, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493664', '2026', '6', '24', '2026-06-24', 'CAR', 33, 15, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '784441330', 1, 'DNI', '71016137', 'QUINCHORI', 'CARDENAS', 'MARY TANIA', '1997-06-19', 'F', '71016137', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 29, 'A', 29, 0, 5, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416493801', '2026', '6', '27', '2026-06-27', 'CAR', 507, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '30701780318', 1, 'DNI', '75908686', 'LOPEZ', 'FLORES', 'JAHAMELY JEANELA', '1998-02-26', 'F', '75908686', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 28, 'A', 28, 4, 1, '18 a 29 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493801', '2026', '6', '27', '2026-06-27', 'CAR', 507, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '30701780318', 1, 'DNI', '75908686', 'LOPEZ', 'FLORES', 'JAHAMELY JEANELA', '1998-02-26', 'F', '75908686', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 28, 'A', 28, 4, 1, '18 a 29 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493801', '2026', '6', '27', '2026-06-27', 'CAR', 507, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '30701780318', 1, 'DNI', '75908686', 'LOPEZ', 'FLORES', 'JAHAMELY JEANELA', '1998-02-26', 'F', '75908686', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 28, 'A', 28, 4, 1, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416493801', '2026', '6', '27', '2026-06-27', 'CAR', 507, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '30701780318', 1, 'DNI', '75908686', 'LOPEZ', 'FLORES', 'JAHAMELY JEANELA', '1998-02-26', 'F', '75908686', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'R', 28, 'A', 28, 4, 1, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416494442', '2026', '6', '25', '2026-06-25', 'CAR', 33, 16, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8586687330', 1, 'DNI', '76228394', 'MORALES', 'SINCHE', 'KENNEDY OLIVER', '2001-07-03', 'M', '76228394', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'R', 'N', 24, 'A', 24, 11, 22, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416494655', '2026', '6', '27', '2026-06-27', 'CAR', 508, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '25804212318', 1, 'DNI', '61302283', 'ALVAREZ', 'VEGA', 'CRISTOPHER ALEXANDER', '2008-05-09', 'M', '61302283', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'R', 'R', 18, 'A', 18, 1, 18, '18 a 29 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416494655', '2026', '6', '27', '2026-06-27', 'CAR', 508, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '25804212318', 1, 'DNI', '61302283', 'ALVAREZ', 'VEGA', 'CRISTOPHER ALEXANDER', '2008-05-09', 'M', '61302283', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'R', 'R', 18, 'A', 18, 1, 18, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416495340', '2026', '6', '27', '2026-06-27', 'CAR', 509, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8529933318', 1, 'DNI', '71645437', 'ALVAREZ', 'VEGA', 'SEBASTIAN FABRICIO', '2005-05-19', 'M', '71645437', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'R', 'R', 21, 'A', 21, 1, 8, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416495340', '2026', '6', '27', '2026-06-27', 'CAR', 509, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8529933318', 1, 'DNI', '71645437', 'ALVAREZ', 'VEGA', 'SEBASTIAN FABRICIO', '2005-05-19', 'M', '71645437', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'R', 'R', 21, 'A', 21, 1, 8, '18 a 29 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416496495', '2026', '6', '27', '2026-06-27', 'CAR', 510, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1660497318', 1, 'DNI', '76779731', 'JORGE', 'ÑAHUI', 'LIZETH EDITH', '2002-05-13', 'F', '76779731', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741902318', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741902', 1, 'DNI', '20047925', 'LANDA', 'SANCHEZ', 'LILIANA MARLENI', '1972-11-25', 'C', 'C', 24, 'A', 24, 1, 14, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416424767', '2026', '6', '27', '2026-06-27', 'CAR', 28, 19, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '36806565320', 1, 'DNI', '81784527', 'MENDOZA', 'ROLDAN', 'KATHERINE LOURDES', '2018-06-20', 'F', '81784527', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '10399304320', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '10399304', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 'R', 'R', 8, 'A', 8, 0, 7, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416443357', '2026', '6', '27', '2026-06-27', 'CAR', 28, 20, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '49275757320', 1, 'DNI', '92685594', 'AGUILAR', 'HUINCHO', 'ASTRID YERLY', '2021-12-27', 'F', '92685594', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '10399304320', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '10399304', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 'C', 'R', 4, 'A', 4, 6, 0, '01 a 04 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641294', '2026', '6', '26', '2026-06-26', 'CAR', 567, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1624736318', 1, 'DNI', '45356238', 'PAREDES', 'SANTOS', 'DELICIAS JANI', '1988-04-07', 'F', '45356238', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 38, 'A', 38, 2, 19, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641464', '2026', '6', '26', '2026-06-26', 'CAR', 568, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '27805004318', 1, 'DNI', '71664762', 'ROMAN', 'CRIALES', 'YAMELI', '1999-07-28', 'F', 'q', '72023', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 26, 'A', 26, 10, 29, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416641464', '2026', '6', '26', '2026-06-26', 'CAR', 568, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '27805004318', 1, 'DNI', '71664762', 'ROMAN', 'CRIALES', 'YAMELI', '1999-07-28', 'F', 'q', '72023', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 26, 'A', 26, 10, 29, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642355', '2026', '6', '26', '2026-06-26', 'CAR', 573, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1693673318', 1, 'DNI', '60243653', 'PRIETO', 'SANTOS', 'YHOJAN NOBEL', '2001-09-03', 'M', '60243653', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 24, 'A', 24, 9, 23, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416642734', '2026', '6', '26', '2026-06-26', 'CAR', 575, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1215849318', 1, 'DNI', '43727332', 'ORELLANA', 'ORTIZ', 'ESTHER', '1986-08-15', 'F', '43727332', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 39, 'A', 39, 10, 11, '30 a 59 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416642734', '2026', '6', '26', '2026-06-26', 'CAR', 575, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1215849318', 1, 'DNI', '43727332', 'ORELLANA', 'ORTIZ', 'ESTHER', '1986-08-15', 'F', '43727332', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 39, 'A', 39, 10, 11, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416643197', '2026', '6', '26', '2026-06-26', 'CAR', 578, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7274561318', 1, 'DNI', '20589834', 'TINOCO', 'ALCANTARA', 'EDWIN NANDO', '1971-06-24', 'M', '20589834', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 55, 'A', 55, 0, 2, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416640930', '2026', '6', '26', '2026-06-26', 'CAR', 564, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8277185318', 1, 'DNI', '20530989', 'MAURICIO', 'ROSA', 'SANTONIA', '1964-11-01', 'F', '20530989', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 61, 'A', 61, 7, 25, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634271', '2026', '6', '26', '2026-06-26', 'CAR', 528, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3938373318', 1, 'DNI', '20587171', 'VALENCIA', 'HINOSTROZA', 'SIBIA ELIZABETH', '1975-10-26', 'F', '20587171', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 50, 'A', 50, 8, 0, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634811', '2026', '6', '26', '2026-06-26', 'CAR', 532, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '42269253318', 1, 'DNI', '91912903', 'BARRIENTOS', 'PORTA', 'VALERIA ABIGAIL', '2020-07-01', 'F', '91912903', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 11, 25, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416634939', '2026', '6', '26', '2026-06-26', 'CAR', 533, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '42269257318', 1, 'DNI', '91911037', 'PEÑALOZA', 'CORDOVA', 'JOSÉ VICENTE', '2020-06-30', 'M', '91911037', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 11, 27, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416635839', '2026', '6', '26', '2026-06-26', 'CAR', 538, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1771871318', 1, 'DNI', '76018259', 'MEJIA', 'GOMEZ', 'STEFANY FLOR', '1999-08-13', 'F', '76018259', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 26, 'A', 26, 10, 13, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416637098', '2026', '6', '26', '2026-06-26', 'CAR', 545, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '16542979318', 1, 'DNI', '20526585', 'ALAMO', 'TRUJILLO', 'PAULA MARINA', '1955-06-18', 'F', '20526585', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 71, 'A', 71, 0, 8, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416638184', '2026', '6', '26', '2026-06-26', 'CAR', 551, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7253828318', 1, 'DNI', '45355462', 'IGLESIAS', 'MAYLI', 'ESTHER', '1986-12-22', 'F', '45355462', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 39, 'A', 39, 6, 4, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416639139', '2026', '6', '26', '2026-06-26', 'CAR', 555, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1138189318', 1, 'DNI', '20522733', 'ADRIANO', 'ALVITRES', 'VILMA FLOR', '1965-12-12', 'F', '20522733', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 60, 'A', 60, 6, 14, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416639975', '2026', '6', '26', '2026-06-26', 'CAR', 559, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1723818318', 1, 'DNI', '24004851', 'CRIALES', 'CHACON', 'VIRGINIA', '1977-02-09', 'F', '24004851', '72040', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 49, 'A', 49, 4, 17, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416632811', '2026', '6', '26', '2026-06-26', 'CAR', 520, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2759312318', 1, 'DNI', '20570044', 'ROJAS', 'ROSALES', 'EMILIO LEON', '1960-06-30', 'M', '20570044', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 65, 'A', 65, 11, 27, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416184370', '2026', '6', '26', '2026-06-26', 'ACT', 4, 4, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5806190320288', 1, 'DNI', '94265747', 'ANTONIO', 'PONGO', 'EIDEN GARETH KOBARI', '2025-05-31', 'M', '94265747', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 1, 'A', 1, 0, 26, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184370', '2026', '6', '26', '2026-06-26', 'ACT', 4, 4, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5806190320288', 1, 'DNI', '94265747', 'ANTONIO', 'PONGO', 'EIDEN GARETH KOBARI', '2025-05-31', 'M', '94265747', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 1, 'A', 1, 0, 26, '01 a 04 años', NULL, 'M', 'CP', '90707', 'VACUNA VIVA CONTRA EL VIRUS DEL SARAMPIÓN PAROTIDITIS Y RUBÉOLA (MMR) PARA USO SUBCUTÁNEA', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184370', '2026', '6', '26', '2026-06-26', 'ACT', 4, 4, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5806190320288', 1, 'DNI', '94265747', 'ANTONIO', 'PONGO', 'EIDEN GARETH KOBARI', '2025-05-31', 'M', '94265747', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 1, 'A', 1, 0, 26, '01 a 04 años', NULL, 'M', 'CP', '90716', 'VACUNA VIVA CONTRA EL VIRUS DE LA VARICELA PARA USO SUBCUTÁNEO', 'D', 'DU', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416184370', '2026', '6', '26', '2026-06-26', 'ACT', 4, 4, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5806190320288', 1, 'DNI', '94265747', 'ANTONIO', 'PONGO', 'EIDEN GARETH KOBARI', '2025-05-31', 'M', '94265747', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 1, 'A', 1, 0, 26, '01 a 04 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', '3', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184684', '2026', '6', '26', '2026-06-26', 'ACT', 4, 5, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5910710920288', 6, 'CNV', '94615298', 'RN', 'SANTOS', 'RN', '2026-04-14', 'F', '94615298', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'N', 2, 'M', 0, 2, 12, '01 a 11 meses', NULL, 'M', 'CP', '90681', 'VACUNA CONTRA EL ROTAVIRUS HUMANO ATENUADA ESQUEMA DE 2 DOSIS VIVO PARA USO ORAL', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184684', '2026', '6', '26', '2026-06-26', 'ACT', 4, 5, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5910710920288', 6, 'CNV', '94615298', 'RN', 'SANTOS', 'RN', '2026-04-14', 'F', '94615298', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'N', 2, 'M', 0, 2, 12, '01 a 11 meses', NULL, 'M', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184684', '2026', '6', '26', '2026-06-26', 'ACT', 4, 5, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5910710920288', 6, 'CNV', '94615298', 'RN', 'SANTOS', 'RN', '2026-04-14', 'F', '94615298', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'N', 2, 'M', 0, 2, 12, '01 a 11 meses', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184684', '2026', '6', '26', '2026-06-26', 'ACT', 4, 5, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5910710920288', 6, 'CNV', '94615298', 'RN', 'SANTOS', 'RN', '2026-04-14', 'F', '94615298', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'N', 2, 'M', 0, 2, 12, '01 a 11 meses', NULL, 'M', 'CP', '90722', 'VACUNA DPT-HVB-HIB', 'D', '1', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184814', '2026', '6', '20', '2026-06-20', 'CAR', 18, 10, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58338685320', 1, 'DNI', '94354094', 'CASAPIA', 'CCORA', 'LUANNA MAHELET', '2025-08-20', 'F', '94354094', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'C', 10, 'M', 0, 10, 0, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416184988', '2026', '6', '26', '2026-06-26', 'ACT', 6, 2, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1659273325', 1, 'DNI', '61659240', 'QUISPE', 'BASALDUA', 'MILAGROS', '2004-04-18', 'F', '61659240', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 22, 'A', 22, 2, 8, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416504900', '2026', '6', '27', '2026-06-27', 'CAR', 28, 22, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '54278477320', 1, 'DNI', '93545731', 'QUINCHOCRE', 'SANABRIA', 'YANDI KEYLAN', '2023-09-17', 'F', '93545731', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '10399304320', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '10399304', 1, 'DNI', '70542126', 'HUAMAN', 'SALDAÑA', 'BEATRIZ MILAGROS', '1993-10-20', 'C', 'R', 2, 'A', 2, 9, 10, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416185510', '2026', '6', '26', '2026-06-26', 'ACT', 6, 3, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4568714325', 1, 'DNI', '62842191', 'VALDEZ', 'QUINTIMARI', 'DENISA', '1995-04-30', 'F', '62842191', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'N', 31, 'A', 31, 1, 27, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416185633', '2026', '6', '26', '2026-06-26', 'CAR', 1, 25, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58391493320', 1, 'DNI', '94360563', 'PEREZ', 'LEON', 'MELANY ZOE', '2025-08-26', 'F', '94360563', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'C', 10, 'M', 0, 10, 0, '01 a 11 meses', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416185700', '2026', '6', '26', '2026-06-26', 'ACT', 6, 4, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '54494428325', 1, 'DNI', '93936559', 'JORGE', 'SANTA CRUZ', 'ELIAM ABIEL', '2024-06-21', 'M', '93936559', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 0, 5, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416185818', '2026', '6', '26', '2026-06-26', 'ACT', 6, 5, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '13433081325', 1, 'DNI', '07965340', 'RIOS', 'ANDREU', 'MARCO ANTONIO', '1963-06-13', 'M', '07965340', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'R', 'R', 63, 'A', 63, 0, 13, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416186106', '2026', '6', '26', '2026-06-26', 'ACT', 6, 7, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53943314325', 1, 'DNI', '93957012', 'CHAPETA', 'CAÑOA', 'ERIK MATEO AXSEL', '2024-08-29', 'M', '93957012', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 9, 28, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416186231', '2026', '6', '26', '2026-06-26', 'ACT', 6, 8, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '50330821325', 1, 'DNI', '92421028', 'CAHUATI', 'CHAPETA', 'EDWIN EMILIANO', '2021-06-25', 'M', '92421028', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 5, 'A', 5, 0, 1, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416186334', '2026', '6', '26', '2026-06-26', 'ACT', 6, 9, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '54618055325', 1, 'DNI', '94125685', 'REQUENA', 'LOZANO', 'DYLAN LEONARDO', '2025-01-31', 'M', '94125685', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 4, 26, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416186882', '2026', '6', '26', '2026-06-26', 'CAR', 141, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52675068320', 1, 'DNI', '93480840', 'ZEVALLOS', 'QUIÑONEZ', 'BELEN SHARMELLY', '2023-07-27', 'F', '93480840', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'N', 2, 'A', 2, 10, 30, '01 a 04 años', NULL, 'T', 'CP', '90722', 'VACUNA DPT-HVB-HIB', 'D', 'DA', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416186882', '2026', '6', '26', '2026-06-26', 'CAR', 141, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52675068320', 1, 'DNI', '93480840', 'ZEVALLOS', 'QUIÑONEZ', 'BELEN SHARMELLY', '2023-07-27', 'F', '93480840', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'N', 2, 'A', 2, 10, 30, '01 a 04 años', NULL, 'T', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', 'DA', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416186882', '2026', '6', '26', '2026-06-26', 'CAR', 141, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52675068320', 1, 'DNI', '93480840', 'ZEVALLOS', 'QUIÑONEZ', 'BELEN SHARMELLY', '2023-07-27', 'F', '93480840', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'N', 2, 'A', 2, 10, 30, '01 a 04 años', NULL, 'T', 'CP', '90707', 'VACUNA VIVA CONTRA EL VIRUS DEL SARAMPIÓN PAROTIDITIS Y RUBÉOLA (MMR) PARA USO SUBCUTÁNEA', 'D', '2', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416188471', '2026', '6', '20', '2026-06-20', 'CAR', 18, 11, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1745827320', 1, 'DNI', '78909853', 'RUBEN', 'CURO', 'GENESIS CRISTAL', '2014-11-24', 'F', '78909853', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'R', 11, 'A', 11, 6, 27, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416188744', '2026', '6', '20', '2026-06-20', 'CAR', 18, 12, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '51600539320', 1, 'DNI', '91760165', 'CUÑIVO', 'JACINTO', 'KARENA KASHIRI', '2020-03-05', 'F', '91760165', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'R', 'R', 6, 'A', 6, 3, 15, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416188986', '2026', '6', '26', '2026-06-26', 'CAR', 142, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1358175320', 1, 'DNI', '71856196', 'CURO', 'JAVIER', 'YOVANA', '1995-09-22', 'F', '71856196', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'R', 30, 'A', 30, 9, 4, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416189495', '2026', '6', '22', '2026-06-22', 'CAR', 18, 13, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1688433320', 1, 'DNI', '71118156', 'QUINTORE', 'ANGURIO', 'ZANICTZA', '1999-06-23', 'F', '71118156', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'R', 26, 'A', 26, 11, 30, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416189495', '2026', '6', '22', '2026-06-22', 'CAR', 18, 13, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1688433320', 1, 'DNI', '71118156', 'QUINTORE', 'ANGURIO', 'ZANICTZA', '1999-06-23', 'F', '71118156', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'R', 26, 'A', 26, 11, 30, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416189495', '2026', '6', '22', '2026-06-22', 'CAR', 18, 13, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1688433320', 1, 'DNI', '71118156', 'QUINTORE', 'ANGURIO', 'ZANICTZA', '1999-06-23', 'F', '71118156', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'R', 26, 'A', 26, 11, 30, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416189495', '2026', '6', '22', '2026-06-22', 'CAR', 18, 13, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1688433320', 1, 'DNI', '71118156', 'QUINTORE', 'ANGURIO', 'ZANICTZA', '1999-06-23', 'F', '71118156', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'R', 26, 'A', 26, 11, 30, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416190291', '2026', '6', '26', '2026-06-26', 'CAR', 143, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4922296320', 1, 'DNI', '60539239', 'LONGA', 'HURTADO', 'FLOR SILVIA', '1990-10-11', 'F', '60539239', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'N', 35, 'A', 35, 8, 15, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416190291', '2026', '6', '26', '2026-06-26', 'CAR', 143, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4922296320', 1, 'DNI', '60539239', 'LONGA', 'HURTADO', 'FLOR SILVIA', '1990-10-11', 'F', '60539239', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '7796786320', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7796786', 1, 'DNI', '70037144', 'OROYA', 'ROJAS', 'MAYUMI VIANI', '1998-07-01', 'C', 'N', 35, 'A', 35, 8, 15, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416192754', '2026', '6', '26', '2026-06-26', 'CAR', 502, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58451702318', 1, 'DNI', '94327279', 'MEZA', 'ROJAS', 'ANTONELLA YUNSU', '2025-07-26', 'F', '94327279', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1863515318', 1, 'DNI', '70243378', 'REYES', 'CARO', 'DAMARY YANIRA', '1990-06-09', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1863515', 1, 'DNI', '70243378', 'REYES', 'CARO', 'DAMARY YANIRA', '1990-06-09', 'C', 'C', 11, 'M', 0, 11, 0, '01 a 11 meses', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416497524', '2026', '6', '25', '2026-06-25', 'CAR', 33, 18, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5893727330', 1, 'DNI', '63694613', 'MORENO', 'CASAS', 'MARIEL AURORA YOLANDA', '2012-03-11', 'F', '63694613', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'R', 14, 'A', 14, 3, 14, '12 a 17 años', NULL, 'M', 'CP', '90714', 'TOXOIDE TETÁNICO Y DIFETÉRICO (TD) ADSOBIDO LIBRE DE PRESERVANTE CUANDO SE ADMINISTRA EN INDIVIDUOS DE 7 AÑOS O MAYORES PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416494824', '2026', '6', '25', '2026-06-25', 'CAR', 33, 17, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4976578330', 1, 'DNI', '20531474', 'YURIVILCA', 'VDA. DE SUAREZ', 'ILDA', '1955-07-22', 'F', '20531474', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'R', 'R', 70, 'A', 70, 11, 3, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416495901', '2026', '6', '27', '2026-06-27', 'CAR', 32, 5, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1270211330', 1, 'DNI', '62075631', 'YUMPIRI', 'MAXIMO', 'DELINA', '2001-11-23', 'F', '62075631', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'R', 'R', 24, 'A', 24, 7, 4, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416495901', '2026', '6', '27', '2026-06-27', 'CAR', 32, 5, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1270211330', 1, 'DNI', '62075631', 'YUMPIRI', 'MAXIMO', 'DELINA', '2001-11-23', 'F', '62075631', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'R', 'R', 24, 'A', 24, 7, 4, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'G23', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416142797', '2026', '6', '26', '2026-06-26', 'ACT', 2, 7, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 322, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000322', 'LIBERTAD TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7899998322', 1, 'DNI', '79122608', 'OLGUINO', 'JULIAN', 'NEYMAR DAYIRO', '2014-10-06', 'M', '79122608', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '2908410322', 1, 'DNI', '42769985', 'QUINTORI', 'LOPEZ', 'LILIANA CESI', '1984-12-06', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 11, 'A', 11, 8, 20, '05 a 11 años', NULL, 'M', 'CP', '90717', 'VACUNA VIVA CONTRA LA FIEBRE AMARILLA PARA USO SUBCUTÁNEO', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416143043', '2026', '6', '26', '2026-06-26', 'ACT', 2, 8, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 322, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000322', 'LIBERTAD TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8646608322', 1, 'DNI', '79273910', 'SORIA', 'LEON', 'BRITHANY DANIELA', '2015-08-27', 'F', '79273910', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '2908410322', 1, 'DNI', '42769985', 'QUINTORI', 'LOPEZ', 'LILIANA CESI', '1984-12-06', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 10, 'A', 10, 9, 30, '05 a 11 años', NULL, 'M', 'CP', '90717', 'VACUNA VIVA CONTRA LA FIEBRE AMARILLA PARA USO SUBCUTÁNEO', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1416483119', '2026', '6', '27', '2026-06-27', 'CAR', 2, 22, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '55916705330', 1, 'DNI', '94154651', 'PERÉZ', 'YUMPIRI', 'DEICIS AMARA', '2025-02-26', 'F', '94154651', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741444330', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '741444', 1, 'DNI', '40832901', 'CASTILLO', 'PATRICIO', 'EDITH LUZ', '1981-01-28', 'C', 'C', 1, 'A', 1, 4, 1, '01 a 04 años', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416487295', '2026', '6', '25', '2026-06-25', 'CAR', 33, 12, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58606861330', 1, 'DNI', '94421621', 'PARRA', 'CAMAÑAL', 'BRITZ BRIANNA', '2025-10-20', 'F', '94421621', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '25810195330', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '25810195', 1, 'DNI', '61447341', 'HUAMAN', 'INGA', 'SAIT ISAI', '1992-02-20', 'C', 'C', 8, 'M', 0, 8, 5, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416086079', '2026', '6', '26', '2026-06-26', 'ACT', 2, 23, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1112229324', 1, 'DNI', '48620612', 'IRORI', 'LOPEZ', 'ANAVELA', '1991-10-04', 'F', '48620612', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 34, 'A', 34, 8, 22, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416086513', '2026', '6', '26', '2026-06-26', 'ACT', 2, 24, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6535643324', 1, 'DNI', '76538224', 'BARRIENTOS', 'CHOCHOCCA', 'JACINTO', '1994-08-26', 'M', '76538224', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 31, 'A', 31, 10, 0, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416086768', '2026', '6', '26', '2026-06-26', 'ACT', 2, 25, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3421556324', 1, 'DNI', '20559450', 'CABEZAS', 'CHACON', 'SEGUNDINA', '1961-05-13', 'F', '20559450', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'C', 65, 'A', 65, 1, 13, '60 años a mas', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416087729', '2026', '6', '26', '2026-06-26', 'ACT', 3, 1, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58066828324', 1, 'DNI', '94274205', 'BARRIENTOS', 'IRORI', 'ALESSIA FABIANA', '2025-06-08', 'F', '94274205', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 1, 'A', 1, 0, 18, '01 a 04 años', NULL, 'M', 'CP', '90707', 'VACUNA VIVA CONTRA EL VIRUS DEL SARAMPIÓN PAROTIDITIS Y RUBÉOLA (MMR) PARA USO SUBCUTÁNEA', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416087729', '2026', '6', '26', '2026-06-26', 'ACT', 3, 1, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58066828324', 1, 'DNI', '94274205', 'BARRIENTOS', 'IRORI', 'ALESSIA FABIANA', '2025-06-08', 'F', '94274205', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 1, 'A', 1, 0, 18, '01 a 04 años', NULL, 'M', 'CP', '90716', 'VACUNA VIVA CONTRA EL VIRUS DE LA VARICELA PARA USO SUBCUTÁNEO', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416087729', '2026', '6', '26', '2026-06-26', 'ACT', 3, 1, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58066828324', 1, 'DNI', '94274205', 'BARRIENTOS', 'IRORI', 'ALESSIA FABIANA', '2025-06-08', 'F', '94274205', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 1, 'A', 1, 0, 18, '01 a 04 años', NULL, 'M', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', '3', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416088274', '2026', '6', '26', '2026-06-26', 'ACT', 3, 3, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '51158993324', 1, 'DNI', '81996184', 'BARRIENTOS', 'IRORI', 'GAEL ABRAHAN', '2021-11-24', 'M', '81996184', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 4, 'A', 4, 7, 2, '01 a 04 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416314970', '2026', '6', '27', '2026-06-27', 'CAR', 17, 5, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3221873330', 1, 'DNI', '70231117', 'FERNANDEZ', 'CAMARGO', 'JHENY JHELITZA', '1996-05-27', 'F', '70231117', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '24433014330', 1, 'DNI', '47820217', 'TOLENTINO', 'CIPRIANO', 'CLINTON', '1993-02-10', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '24433014', 1, 'DNI', '47820217', 'TOLENTINO', 'CIPRIANO', 'CLINTON', '1993-02-10', 'R', 'R', 30, 'A', 30, 1, 0, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416314970', '2026', '6', '27', '2026-06-27', 'CAR', 17, 5, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3221873330', 1, 'DNI', '70231117', 'FERNANDEZ', 'CAMARGO', 'JHENY JHELITZA', '1996-05-27', 'F', '70231117', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '24433014330', 1, 'DNI', '47820217', 'TOLENTINO', 'CIPRIANO', 'CLINTON', '1993-02-10', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '24433014', 1, 'DNI', '47820217', 'TOLENTINO', 'CIPRIANO', 'CLINTON', '1993-02-10', 'R', 'R', 30, 'A', 30, 1, 0, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'ST', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 00:00:00', NULL),
('1416185987', '2026', '6', '26', '2026-06-26', 'ACT', 6, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7507038325', 1, 'DNI', '45946020', 'REQUENA', 'LOPEZ', 'LOIDA ROSALIA', '1989-09-13', 'F', '45946020', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 36, 'A', 36, 9, 13, '30 a 59 años', NULL, 'M', 'CP', '90715', 'VACUNA CONTRA EL TÉTANOS TOXOIDE DIFTÉRICO Y VACUNA ACELULAR CONTRA PERTUSIS (TDAP) CUANDO SE ADMINISTRA A PERSONAS DE 7 AÑOS O MÁS PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416185987', '2026', '6', '26', '2026-06-26', 'ACT', 6, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7507038325', 1, 'DNI', '45946020', 'REQUENA', 'LOPEZ', 'LOIDA ROSALIA', '1989-09-13', 'F', '45946020', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 36, 'A', 36, 9, 13, '30 a 59 años', NULL, 'M', 'CP', '90715', 'VACUNA CONTRA EL TÉTANOS TOXOIDE DIFTÉRICO Y VACUNA ACELULAR CONTRA PERTUSIS (TDAP) CUANDO SE ADMINISTRA A PERSONAS DE 7 AÑOS O MÁS PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416185987', '2026', '6', '26', '2026-06-26', 'ACT', 6, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7507038325', 1, 'DNI', '45946020', 'REQUENA', 'LOPEZ', 'LOIDA ROSALIA', '1989-09-13', 'F', '45946020', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 36, 'A', 36, 9, 13, '30 a 59 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '3', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416185987', '2026', '6', '26', '2026-06-26', 'ACT', 6, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 325, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000325', 'CENTRO POBLADO MENOR LA FLORIDA', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7507038325', 1, 'DNI', '45946020', 'REQUENA', 'LOPEZ', 'LOIDA ROSALIA', '1989-09-13', 'F', '45946020', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28222450325', 1, 'DNI', '72306776', 'HUAROC', 'BARZOLA', 'ERIKA SHEYLA', '1999-10-24', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 36, 'A', 36, 9, 13, '30 a 59 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416013472', '2026', '6', '26', '2026-06-26', 'ACT', 2, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 63932, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035583', 'LA ESPERANZA - PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5844785563932', 1, 'DNI', '94392782', 'CARHUAJULCA', 'SEDANO', 'ZAREK ELYEL', '2025-09-24', 'M', '94392782', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '845144463932', 1, 'DNI', '76502359', 'CAMPOS', 'EGOAVIL', 'KAIRA XIOMARA', '2002-08-07', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'N', 'N', 9, 'M', 0, 9, 2, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415957476', '2026', '6', '26', '2026-06-26', 'CAR', 501, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52082162318', 1, 'DNI', '93418973', 'SOTO', 'QUISPE', 'AYLEN ZULEYHA', '2023-06-09', 'F', '93418973', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 3, 'A', 3, 0, 17, '01 a 04 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1415953674', '2026', '6', '26', '2026-06-26', 'CAR', 499, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58284769318', 1, 'DNI', '94340465', 'CHOCHOCCA', 'AVILES', 'EITHAN GABRIEL', '2025-08-08', 'M', '94340465', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 10, 'M', 0, 10, 18, '01 a 11 meses', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415954519', '2026', '6', '26', '2026-06-26', 'CAR', 500, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53731099318', 1, 'DNI', '93882878', 'ARRIGUELA', 'OSORIO', 'ISABELLA GEORGINA', '2024-06-25', 'F', '93882878', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 2, 'A', 2, 0, 1, '01 a 04 años', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415951038', '2026', '6', '26', '2026-06-26', 'CAR', 498, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58116823318', 1, 'DNI', '94290497', 'ROJAS', 'JANAMPA', 'JORDI JAMIL', '2025-06-23', 'M', '94290497', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 1, 'A', 1, 0, 3, '01 a 04 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', '3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415951038', '2026', '6', '26', '2026-06-26', 'CAR', 498, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58116823318', 1, 'DNI', '94290497', 'ROJAS', 'JANAMPA', 'JORDI JAMIL', '2025-06-23', 'M', '94290497', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 1, 'A', 1, 0, 3, '01 a 04 años', NULL, 'T', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415951038', '2026', '6', '26', '2026-06-26', 'CAR', 498, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58116823318', 1, 'DNI', '94290497', 'ROJAS', 'JANAMPA', 'JORDI JAMIL', '2025-06-23', 'M', '94290497', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 1, 'A', 1, 0, 3, '01 a 04 años', NULL, 'T', 'CP', '90707', 'VACUNA VIVA CONTRA EL VIRUS DEL SARAMPIÓN PAROTIDITIS Y RUBÉOLA (MMR) PARA USO SUBCUTÁNEA', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415951038', '2026', '6', '26', '2026-06-26', 'CAR', 498, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58116823318', 1, 'DNI', '94290497', 'ROJAS', 'JANAMPA', 'JORDI JAMIL', '2025-06-23', 'M', '94290497', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 1, 'A', 1, 0, 3, '01 a 04 años', NULL, 'T', 'CP', '90716', 'VACUNA VIVA CONTRA EL VIRUS DE LA VARICELA PARA USO SUBCUTÁNEO', 'D', 'DU', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415945174', '2026', '6', '26', '2026-06-26', 'CAR', 496, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7283238318', 1, 'DNI', '25535656', 'ROJAS', 'DE RIEGA', 'FLORA JENNY', '1957-07-05', 'F', '25535656', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'R', 'N', 68, 'A', 68, 11, 21, '60 años a mas', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415941326', '2026', '6', '26', '2026-06-26', 'CAR', 494, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '947101318', 1, 'DNI', '70322916', 'BRICEÑO', 'VITOR', 'BRYAN', '1997-06-11', 'M', '70322916', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'R', 'R', 29, 'A', 29, 0, 15, '18 a 29 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415941326', '2026', '6', '26', '2026-06-26', 'CAR', 494, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '947101318', 1, 'DNI', '70322916', 'BRICEÑO', 'VITOR', 'BRYAN', '1997-06-11', 'M', '70322916', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'R', 'R', 29, 'A', 29, 0, 15, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415645061', '2026', '6', '25', '2026-06-25', 'ACT', 4, 1, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5844317420288', 1, 'DNI', '94390086', 'JAVIER', 'CCORAHUA', 'GAHEL HERNAN', '2025-09-21', 'M', '94390086', NULL, '05', 'ASHANINKA', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 9, 'M', 0, 9, 4, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415943784', '2026', '6', '26', '2026-06-26', 'CAR', 495, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '29806796318', 1, 'DNI', '74670802', 'PAYANO', 'RIEGA', 'KEYRA ARANZA', '2010-08-20', 'F', '74670802', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'C', 15, 'A', 15, 10, 6, '12 a 17 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415938532', '2026', '6', '26', '2026-06-26', 'CAR', 493, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '515671318', 1, 'DNI', '76974639', 'QUISPE', 'CASTILLO', 'KATTY MARISOL', '1995-04-23', 'F', '76974639', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 31, 'A', 31, 2, 3, '30 a 59 años', NULL, 'T', 'CX', '9999', 'COMORBILIDAD EN VACUNACIÓN', 'R', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415938532', '2026', '6', '26', '2026-06-26', 'CAR', 493, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '515671318', 1, 'DNI', '76974639', 'QUISPE', 'CASTILLO', 'KATTY MARISOL', '1995-04-23', 'F', '76974639', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 31, 'A', 31, 2, 3, '30 a 59 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415938532', '2026', '6', '26', '2026-06-26', 'CAR', 493, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '515671318', 1, 'DNI', '76974639', 'QUISPE', 'CASTILLO', 'KATTY MARISOL', '1995-04-23', 'F', '76974639', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'C', 'R', 31, 'A', 31, 2, 3, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416144323', '2026', '6', '26', '2026-06-26', 'ACT', 2, 9, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 322, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000322', 'LIBERTAD TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '34563448322', 1, 'DNI', '90189820', 'HUAMANI', 'QUISPE', 'ABIGAIL EMELY', '2017-04-21', 'F', '90189820', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '2908410322', 1, 'DNI', '42769985', 'QUINTORI', 'LOPEZ', 'LILIANA CESI', '1984-12-06', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'N', 'N', 9, 'A', 9, 2, 5, '05 a 11 años', NULL, 'M', 'CP', '90649', 'VACUNA CONTRA EL VIRUS PAPILLOMA HUMANO (HPV) TIPOS 6 1116 18 (CUADRIVALENTE) ESQUEMA DE 3 DOSIS PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416142485', '2026', '6', '26', '2026-06-26', 'ACT', 2, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 322, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000322', 'LIBERTAD TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '35305979322', 1, 'DNI', '79521446', 'PEREZ', 'DOMINGO', 'BRAYMAN JHONI', '2016-01-31', 'M', '79521446', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '2908410322', 1, 'DNI', '42769985', 'QUINTORI', 'LOPEZ', 'LILIANA CESI', '1984-12-06', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 10, 'A', 10, 4, 26, '05 a 11 años', NULL, 'M', 'CP', '90717', 'VACUNA VIVA CONTRA LA FIEBRE AMARILLA PARA USO SUBCUTÁNEO', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415867319', '2026', '6', '26', '2026-06-26', 'CAR', 99, 5, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '35784751320', 1, 'DNI', '81737165', 'COMPAHUA', 'VALENTIN', 'YOSSI LIZ', '2011-09-22', 'F', '81737165', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '30538637320', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '30538637', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 'C', 'C', 14, 'A', 14, 9, 4, '12 a 17 años', NULL, 'M', 'CP', '90715', 'VACUNA CONTRA EL TÉTANOS TOXOIDE DIFTÉRICO Y VACUNA ACELULAR CONTRA PERTUSIS (TDAP) CUANDO SE ADMINISTRA A PERSONAS DE 7 AÑOS O MÁS PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1415867319', '2026', '6', '26', '2026-06-26', 'CAR', 99, 5, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '35784751320', 1, 'DNI', '81737165', 'COMPAHUA', 'VALENTIN', 'YOSSI LIZ', '2011-09-22', 'F', '81737165', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '30538637320', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '30538637', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 'C', 'C', 14, 'A', 14, 9, 4, '12 a 17 años', NULL, 'M', 'CP', '90715', 'VACUNA CONTRA EL TÉTANOS TOXOIDE DIFTÉRICO Y VACUNA ACELULAR CONTRA PERTUSIS (TDAP) CUANDO SE ADMINISTRA A PERSONAS DE 7 AÑOS O MÁS PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415867319', '2026', '6', '26', '2026-06-26', 'CAR', 99, 5, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '35784751320', 1, 'DNI', '81737165', 'COMPAHUA', 'VALENTIN', 'YOSSI LIZ', '2011-09-22', 'F', '81737165', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '30538637320', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '30538637', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 'C', 'C', 14, 'A', 14, 9, 4, '12 a 17 años', NULL, 'M', 'CP', '90744', 'VACUNA CONTRA LA HEPATITIS B DOSIS PEDIÁTRICA/ADOLESCENTE (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '3', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415867319', '2026', '6', '26', '2026-06-26', 'CAR', 99, 5, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '35784751320', 1, 'DNI', '81737165', 'COMPAHUA', 'VALENTIN', 'YOSSI LIZ', '2011-09-22', 'F', '81737165', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '30538637320', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '30538637', 1, 'DNI', '75808809', 'SIERRA', 'ALEJANDRO', 'DELSY EMILIA', '1999-05-06', 'C', 'C', 14, 'A', 14, 9, 4, '12 a 17 años', NULL, 'M', 'CP', '90744', 'VACUNA CONTRA LA HEPATITIS B DOSIS PEDIÁTRICA/ADOLESCENTE (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415879631', '2026', '6', '21', '2026-06-21', 'ACT', 2, 22, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1676082324', 1, 'DNI', '76663994', 'PAZ', 'SANTAMARIA', 'LEIDY DANITZA', '2005-05-03', 'F', NULL, NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'N', 'N', 21, 'A', 21, 1, 18, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415838312', '2026', '6', '25', '2026-06-25', 'ACT', 2, 9, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 331, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000331', 'LOS ANGELES TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2496041331', 1, 'DNI', '75696198', 'ROSALES', 'CHIRRE', 'CRISTHIAN JHONY', '2001-04-09', 'M', '75696198', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '755018331', 1, 'DNI', '42376613', 'LIMAYMANTA', 'RAYMUNDO', 'KENNY', '1983-07-05', 1, 'NOMBRADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'R', 25, 'A', 25, 2, 16, '18 a 29 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415837173', '2026', '6', '25', '2026-06-25', 'ACT', 2, 6, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 331, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000331', 'LOS ANGELES TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6737005331', 1, 'DNI', '78556804', 'SOTO', 'AQUINO', 'ADRIAN ERICK', '2014-04-16', 'M', '78556804', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '755018331', 1, 'DNI', '42376613', 'LIMAYMANTA', 'RAYMUNDO', 'KENNY', '1983-07-05', 1, 'NOMBRADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'R', 12, 'A', 12, 2, 9, '12 a 17 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415837569', '2026', '6', '25', '2026-06-25', 'ACT', 2, 7, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 331, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000331', 'LOS ANGELES TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '33695499331', 1, 'DNI', '19938253', 'ADAUTO', 'AIRE', 'CHARLES JOSE', '1972-04-21', 'M', '19938253', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '755018331', 1, 'DNI', '42376613', 'LIMAYMANTA', 'RAYMUNDO', 'KENNY', '1983-07-05', 1, 'NOMBRADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'R', 54, 'A', 54, 2, 4, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415837971', '2026', '6', '25', '2026-06-25', 'ACT', 2, 8, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 331, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000331', 'LOS ANGELES TOTERANI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '24839611331', 1, 'DNI', '48138491', 'ESCOBAR', 'YAURI', 'EDGAR', '1993-01-19', 'M', '48138491', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '755018331', 1, 'DNI', '42376613', 'LIMAYMANTA', 'RAYMUNDO', 'KENNY', '1983-07-05', 1, 'NOMBRADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 33, 'A', 33, 5, 6, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1416088006', '2026', '6', '26', '2026-06-26', 'ACT', 3, 2, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 324, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000324', 'LOS ANGELES DE UBIRIKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53679980324', 1, 'DNI', '93877001', 'RIVERA', 'ARROYO', 'NIRELLE DEBORA GEORGINA', '2024-06-20', 'F', '93877001', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '28792072324', 1, 'DNI', '73130870', 'DELGADO', 'VILCHEZ', 'JOSE ARMANDO', '1998-04-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 2, 'A', 2, 0, 6, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415748435', '2026', '6', '25', '2026-06-25', 'ACT', 4, 14, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 329, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000329', 'HUACAMAYO', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53638625329', 1, 'DNI', '93869261', 'VALENCIA', 'AÑANCA', 'MISAEL SMITH', '2024-06-13', 'M', '93869261', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741260329', 1, 'DNI', '20568381', 'BALTAZAR', 'VENEGAS', 'LUZ JANET', '1975-10-03', 8, 'OTROS', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'R', 2, 'A', 2, 0, 12, '01 a 04 años', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415744783', '2026', '6', '23', '2026-06-23', 'ACT', 4, 13, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 329, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000329', 'HUACAMAYO', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58414168329', 1, 'DNI', '94354103', 'SANTOS', 'RONCAL', 'AILANY NAELITH ALEXIA', '2025-08-20', 'F', '94354103', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '741260329', 1, 'DNI', '20568381', 'BALTAZAR', 'VENEGAS', 'LUZ JANET', '1975-10-03', 8, 'OTROS', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'C', 'C', 10, 'M', 0, 10, 3, '01 a 11 meses', NULL, 'M', 'CP', '90657', 'VACUNA CONTRA EL VIRUS DE LA INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A NIÑOS DE 6-35 MESES DE EDAD PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415662584', '2026', '6', '25', '2026-06-25', 'ACT', 3, 19, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 332, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000332', 'ALTO SAN JUAN', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52399240332', 1, 'DNI', '93439414', 'MUCHA', 'HUAMAN', 'EITHAN BERNINSON', '2023-06-24', 'M', '93439414', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1128959332', 1, 'DNI', '41529662', 'HUALPARUCA', 'DE LA CRUZ', 'ANGELICA', '1982-10-06', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'N', 3, 'A', 3, 0, 1, '01 a 04 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415947299', '2026', '6', '26', '2026-06-26', 'CAR', 497, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '819408318', 1, 'DNI', '20587650', 'CARDENAS', 'VIVANCO', 'JOEL TEODOSIO', '1975-10-05', 'M', '20587650', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'R', 'R', 50, 'A', 50, 8, 21, '30 a 59 años', NULL, 'T', 'CX', '9999', 'COMORBILIDAD EN VACUNACIÓN', 'R', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415947299', '2026', '6', '26', '2026-06-26', 'CAR', 497, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '819408318', 1, 'DNI', '20587650', 'CARDENAS', 'VIVANCO', 'JOEL TEODOSIO', '1975-10-05', 'M', '20587650', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'R', 'R', 50, 'A', 50, 8, 21, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415947299', '2026', '6', '26', '2026-06-26', 'CAR', 497, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '819408318', 1, 'DNI', '20587650', 'CARDENAS', 'VIVANCO', 'JOEL TEODOSIO', '1975-10-05', 'M', '20587650', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1711267318', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 2, 'CONTRATADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1711267', 1, 'DNI', '43500595', 'GARCIA', 'CAPCHA', 'ERTHA MODESTA', '1985-10-27', 'R', 'R', 50, 'A', 50, 8, 21, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'ST', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415646242', '2026', '6', '26', '2026-06-26', 'ACT', 2, 5, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 63932, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035583', 'LA ESPERANZA - PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2560319663932', 1, 'DNI', '60903240', 'CASANOVA', 'ARRIOLA', 'CAMILA JHUNNY', '2007-01-14', 'F', '60903240', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '845144463932', 1, 'DNI', '76502359', 'CAMPOS', 'EGOAVIL', 'KAIRA XIOMARA', '2002-08-07', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'N', 19, 'A', 19, 5, 12, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415649266', '2026', '6', '25', '2026-06-25', 'ACT', 4, 2, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5457539120288', 1, 'DNI', '94082516', 'AMARINGA', 'IGNACIO', 'DAYNI DANAE EYMI', '2024-12-23', 'F', '94082516', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 6, 2, '01 a 04 años', NULL, 'M', 'CP', '90707', 'VACUNA VIVA CONTRA EL VIRUS DEL SARAMPIÓN PAROTIDITIS Y RUBÉOLA (MMR) PARA USO SUBCUTÁNEA', 'D', '2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1415649266', '2026', '6', '25', '2026-06-25', 'ACT', 4, 2, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5457539120288', 1, 'DNI', '94082516', 'AMARINGA', 'IGNACIO', 'DAYNI DANAE EYMI', '2024-12-23', 'F', '94082516', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 6, 2, '01 a 04 años', NULL, 'M', 'CP', '90713', 'VACUNA CONTRA EL POLIOVIRUS INACTIVADA (IPV) PARA USO SUBCUTÁNEO O INTRAMUSCULAR', 'D', 'DA', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415649838', '2026', '6', '25', '2026-06-25', 'ACT', 4, 3, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 20288, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014384', 'JOSE GALVEZ', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '213362420288', 1, 'DNI', '60083260', 'CCORAHUA', 'PACHARI', 'EDITH MELY', '1995-07-27', 'F', '60083260', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '3108656820288', 1, 'DNI', '76817974', 'OCHOA', 'HUZCO', 'MARCOS DAVID', '1999-02-17', 3, 'SERUM', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 30, 'A', 30, 10, 29, '30 a 59 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415382633', '2026', '6', '25', '2026-06-25', 'ACT', 12, 16, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 319, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000319', 'BAJO MARANKIARI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '46662420319', 1, 'DNI', '90639022', 'MONTES', 'SOTO', 'KENZO KENAI', '2018-01-29', 'M', '90639022', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '24201966319', 1, 'DNI', '47556446', 'REBAZA', 'AREVALO', 'CRISTIAN MAMPY', '1992-11-21', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'N', 'N', 8, 'A', 8, 4, 27, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415382633', '2026', '6', '25', '2026-06-25', 'ACT', 12, 16, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 319, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000319', 'BAJO MARANKIARI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '46662420319', 1, 'DNI', '90639022', 'MONTES', 'SOTO', 'KENZO KENAI', '2018-01-29', 'M', '90639022', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '24201966319', 1, 'DNI', '47556446', 'REBAZA', 'AREVALO', 'CRISTIAN MAMPY', '1992-11-21', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'N', 'N', 8, 'A', 8, 4, 27, '05 a 11 años', NULL, 'M', 'CP', '90707', 'VACUNA VIVA CONTRA EL VIRUS DEL SARAMPIÓN PAROTIDITIS Y RUBÉOLA (MMR) PARA USO SUBCUTÁNEA', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415633150', '2026', '6', '22', '2026-06-22', 'ACT', 2, 4, '301204', 'INMUNIZACIONES', 3, NULL, NULL, 63932, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035583', 'LA ESPERANZA - PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4549741563932', 1, 'DNI', '92416553', 'BARONA', 'VIDAL', 'AYLIN CELESTE', '2021-06-22', 'F', '92416553', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '845144463932', 1, 'DNI', '76502359', 'CAMPOS', 'EGOAVIL', 'KAIRA XIOMARA', '2002-08-07', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'N', 5, 'A', 5, 0, 0, '05 a 11 años', NULL, 'M', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-26 00:00:00', NULL),
('1415552752', '2026', '6', '25', '2026-06-25', 'CAR', 491, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '47666439318', 1, 'DNI', '77087238', 'MAYORCA', 'VELASQUEZ', 'YEFFERSON NIVARDO', '2004-05-25', 'M', '77087238', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 22, 'A', 22, 1, 0, '18 a 29 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415552844', '2026', '6', '25', '2026-06-25', 'CAR', 492, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10689446318', 1, 'DNI', '20534700', 'RECUAY', 'PEREZ', 'ALFREDO ARTURO', '1956-12-27', 'M', '20534700', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 69, 'A', 69, 5, 29, '60 años a mas', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415552407', '2026', '6', '25', '2026-06-25', 'CAR', 490, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1263428318', 1, 'DNI', '72243384', 'ARANA', 'HUARANCCA', 'LUZ EVELYN', '1998-10-28', 'F', '72243384', '37576', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 27, 'A', 27, 7, 28, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415552407', '2026', '6', '25', '2026-06-25', 'CAR', 490, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1263428318', 1, 'DNI', '72243384', 'ARANA', 'HUARANCCA', 'LUZ EVELYN', '1998-10-28', 'F', '72243384', '37576', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 27, 'A', 27, 7, 28, '18 a 29 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D2', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415552270', '2026', '6', '25', '2026-06-25', 'CAR', 489, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2561550318', 1, 'DNI', '41019989', 'UTCANI', 'CONDOR', 'JOSE ANTONIO', '1981-02-10', 'M', '41019989', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 45, 'A', 45, 4, 15, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415552121', '2026', '6', '25', '2026-06-25', 'CAR', 488, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1140596318', 1, 'DNI', '41466589', 'CHUCO', 'DAMAS', 'FORTUNATO HERMES', '1982-08-21', 'M', '41466589', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 43, 'A', 43, 10, 4, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415551858', '2026', '6', '25', '2026-06-25', 'CAR', 486, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '45263289318', 1, 'DNI', '92336130', 'RIOS', 'GAGO', 'ALESSIA PAOLA', '2021-04-28', 'F', '92336130', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 5, 'A', 5, 1, 28, '05 a 11 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415551990', '2026', '6', '25', '2026-06-25', 'CAR', 487, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '19267100318', 1, 'DNI', '40077027', 'RIOS', 'PEREZ', 'WILLIAM ELIAS', '1978-09-19', 'M', '40077027', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 47, 'A', 47, 9, 6, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415551576', '2026', '6', '25', '2026-06-25', 'CAR', 485, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '994013318', 1, 'DNI', '42938066', 'ÑAÑA', 'HURTADO', 'IBETH LUZ', '1985-05-05', 'F', '42938066', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 41, 'A', 41, 1, 20, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415551309', '2026', '6', '25', '2026-06-25', 'CAR', 484, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '340573318', 1, 'DNI', '63205409', 'BRAVO', 'CALZADA', 'ALEX LEANDRO', '2012-02-01', 'M', '63205409', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 14, 'A', 14, 4, 24, '12 a 17 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415545950', '2026', '6', '25', '2026-06-25', 'CAR', 474, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1332231318', 1, 'DNI', '48424295', 'YAURI', 'BALTAZAR', 'EDGAR MOISES', '1994-12-02', 'M', '48424295', '68501', '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 31, 'A', 31, 6, 23, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1415549604', '2026', '6', '25', '2026-06-25', 'CAR', 476, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '588673318', 1, 'DNI', '79091291', 'DIAZ', 'PARAGUAY', 'JORDANIA BRIANA BELEN', '2015-04-16', 'F', '79091291', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 11, 'A', 11, 2, 9, '05 a 11 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415549739', '2026', '6', '25', '2026-06-25', 'CAR', 477, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1163292318', 1, 'DNI', '63711002', 'MARMOLEJO', 'GARAY', 'LISANDRA SARAY', '2013-11-10', 'F', '63711002', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 12, 'A', 12, 7, 15, '12 a 17 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415550013', '2026', '6', '25', '2026-06-25', 'CAR', 479, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1861089318', 1, 'DNI', '28294013', 'GOMEZ', 'CONDO', 'ZONIA', '1970-02-05', 'F', '28294013', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 56, 'A', 56, 4, 20, '30 a 59 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415550013', '2026', '6', '25', '2026-06-25', 'CAR', 479, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1861089318', 1, 'DNI', '28294013', 'GOMEZ', 'CONDO', 'ZONIA', '1970-02-05', 'F', '28294013', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 56, 'A', 56, 4, 20, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415550302', '2026', '6', '25', '2026-06-25', 'CAR', 480, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4225883318', 1, 'DNI', '61000207', 'GARAY', 'GOMEZ', 'FERNANDO DIEGO', '2007-02-27', 'M', '61000207', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 19, 'A', 19, 3, 29, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415550302', '2026', '6', '25', '2026-06-25', 'CAR', 480, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4225883318', 1, 'DNI', '61000207', 'GARAY', 'GOMEZ', 'FERNANDO DIEGO', '2007-02-27', 'M', '61000207', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 19, 'A', 19, 3, 29, '18 a 29 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415550986', '2026', '6', '25', '2026-06-25', 'CAR', 482, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '867592318', 1, 'DNI', '78272529', 'BRAVO', 'CALZADA', 'ADRIAN LEONARDO', '2013-09-16', 'M', '78272529', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 12, 'A', 12, 9, 9, '12 a 17 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415551154', '2026', '6', '25', '2026-06-25', 'CAR', 483, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '25322335318', 1, 'DNI', '60307148', 'FERNANDEZ', 'BALLESTEROS', 'BRUCCE', '2002-05-08', 'M', '60307148', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 24, 'A', 24, 1, 17, '18 a 29 años', NULL, 'T', 'CP', '90670', 'VACUNA CONJUGADA CONTRA EL NEUMOCOCO VALENTE POR 13 PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415551154', '2026', '6', '25', '2026-06-25', 'CAR', 483, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '25322335318', 1, 'DNI', '60307148', 'FERNANDEZ', 'BALLESTEROS', 'BRUCCE', '2002-05-08', 'M', '60307148', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 24, 'A', 24, 1, 17, '18 a 29 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415538714', '2026', '6', '25', '2026-06-25', 'CAR', 472, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2591275318', 1, 'DNI', '72243374', 'BALTAZAR', 'MUNGI', 'RENSO RUBEN', '1996-03-01', 'M', '72243374', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'R', 'R', 30, 'A', 30, 3, 24, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415525311', '2026', '6', '25', '2026-06-25', 'CAR', 450, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2291165318', 1, 'DNI', '46193641', 'SANTOS', 'SALAZAR', 'MARITZA DOMITILA', '1988-12-25', 'F', '46193641', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 37, 'A', 37, 6, 0, '30 a 59 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415525704', '2026', '6', '25', '2026-06-25', 'CAR', 451, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1729822318', 1, 'DNI', '20547454', 'PIZZORNO', 'CHAVEZ', 'ANA MARIA DEL ROSARIO', '1961-03-20', 'F', '20547454', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 65, 'A', 65, 3, 5, '60 años a mas', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415525998', '2026', '6', '25', '2026-06-25', 'CAR', 452, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4468376318', 1, 'DNI', '20537375', 'LEGUIA', 'MENDOZA', 'FELIX', '1963-08-29', 'M', '20537375', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 62, 'A', 62, 9, 27, '60 años a mas', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415526333', '2026', '6', '25', '2026-06-25', 'CAR', 453, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '45787644318', 1, 'DNI', '71009929', 'ROJAS', 'MARTINEZ', 'ROYER CRISTIAN', '1992-01-22', 'M', '71009929', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 34, 'A', 34, 5, 3, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415526613', '2026', '6', '25', '2026-06-25', 'CAR', 454, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4290931318', 1, 'DNI', '20594408', 'MEDINA', 'RODRIGUEZ', 'MARIA FRANCISCA', '1977-12-14', 'F', '20594408', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 48, 'A', 48, 6, 11, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL),
('1415537934', '2026', '6', '25', '2026-06-25', 'CAR', 471, 1, '301204', 'INMUNIZACIONES', 10, 'MSN20-112: La dosis que intenta registrar  se encuentra habilitada para atenciones hasta el 31/03/2024', NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4346689318', 1, 'DNI', '45425792', 'RAMOS', 'MANTARI', 'MELINA LUCY', '1988-10-29', 'F', '45425792', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 37, 'A', 37, 7, 27, '30 a 59 años', NULL, 'T', 'CP', '90658', 'VACUNA CONTRA EL VIRUS DE INFLUENZA TRIVALENTE VIRUS AISLADO CUANDO SE ADMINISTRA A PERSONAS DE 3 AÑOS DE EDAD O MAYORES PARA USO INTRAMUSCULAR', 'D', 'DU', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-25 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `USUARIOS`
--

CREATE TABLE `USUARIOS` (
  `id_usuario` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(150) DEFAULT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'usuario' COMMENT 'admin o usuario',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `USUARIOS`
--

INSERT INTO `USUARIOS` (`id_usuario`, `usuario`, `password`, `nombre_completo`, `rol`, `estado`, `fecha_creacion`, `ultimo_acceso`) VALUES
(1, 'admin', '$2a$12$oyHlo6Q4WLAz05diONMFx.D93QR915rs.iVOZx7K1wUpgiTI7CtB.', 'Administrador', 'admin', 1, '2026-06-14 12:35:48', '2026-07-29 20:40:29'),
(2, '41132134', '$2y$10$0JLq5iaw1SA1PizLCQN5ouhm76c7b.SMDV8R/MSmVe.iqmwdJLpe.', 'MARCO ANTONIO ESPINOZA VALERIO', 'usuario', 1, '2026-07-20 19:13:56', '2026-07-24 06:06:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ZSPERENE`
--

CREATE TABLE `ZSPERENE` (
  `Id_Establecimiento` int(11) NOT NULL,
  `Nombre_Establecimiento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Ubigueo_Establecimiento` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Codigo_Disa` int(11) DEFAULT NULL,
  `Disa` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Codigo_Red` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Red` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Codigo_MicroRed` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `MicroRed` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Codigo_Unico` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Codigo_Sector` int(11) DEFAULT NULL,
  `Descripcion_Sector` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Departamento` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Provincia` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Distrito` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Categoria_Establecimiento` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ZSPERENE`
--

INSERT INTO `ZSPERENE` (`Id_Establecimiento`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Codigo_Disa`, `Disa`, `Codigo_Red`, `Red`, `Codigo_MicroRed`, `MicroRed`, `Codigo_Unico`, `Codigo_Sector`, `Descripcion_Sector`, `Departamento`, `Provincia`, `Distrito`, `Categoria_Establecimiento`) VALUES
(20287, 'MARISCAL CACERES', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014385', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(318, 'VILLA PERENE', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-4'),
(319, 'BAJO MARANKIARI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000319', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(322, 'LIBERTAD TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000322', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(331, 'LOS ANGELES TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000331', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(333, 'CENTRO TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000333', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(335, 'ZONA PATRIA', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000335', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(336, 'ALTO YAPAZ', '120304', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000336', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'SAN LUIS DE SHUARO', 'I-1'),
(63916, 'VERDE COCHA', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035582', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(63932, 'LA ESPERANZA - PERENE', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035583', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(63938, 'PAMPA TIGRE', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1'),
(7286, 'SANTA ROSA TOTERANI', '120302', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00007319', 7, 'GOBIERNO REGIONAL', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'I-1');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `CONTROL_CALIDAD_OBSERVACIONES`
--
ALTER TABLE `CONTROL_CALIDAD_OBSERVACIONES`
  ADD PRIMARY KEY (`id_observacion`),
  ADD KEY `idx_cco_periodo` (`anio`,`mes`),
  ADD KEY `idx_cco_establecimiento` (`id_establecimiento`),
  ADD KEY `idx_cco_estado` (`estado`),
  ADD KEY `idx_cco_severidad` (`severidad`),
  ADD KEY `idx_cco_tipo` (`tipo_observacion`);

--
-- Indices de la tabla `CONVENIO_FED_AVANCE`
--
ALTER TABLE `CONVENIO_FED_AVANCE`
  ADD PRIMARY KEY (`id_avance`),
  ADD KEY `idx_feda_indicador` (`id_indicador`),
  ADD KEY `idx_feda_periodo` (`anio`,`trimestre`);

--
-- Indices de la tabla `CONVENIO_FED_INDICADORES`
--
ALTER TABLE `CONVENIO_FED_INDICADORES`
  ADD PRIMARY KEY (`id_indicador`),
  ADD UNIQUE KEY `uk_fed_codigo` (`codigo`);

--
-- Indices de la tabla `CONVENIO_GESTION_AVANCE`
--
ALTER TABLE `CONVENIO_GESTION_AVANCE`
  ADD PRIMARY KEY (`id_avance`),
  ADD KEY `idx_cga_indicador` (`id_indicador`),
  ADD KEY `idx_cga_periodo` (`anio`,`mes`);

--
-- Indices de la tabla `CONVENIO_GESTION_INDICADORES`
--
ALTER TABLE `CONVENIO_GESTION_INDICADORES`
  ADD PRIMARY KEY (`id_indicador`),
  ADD UNIQUE KEY `uk_cg_codigo` (`codigo`);

--
-- Indices de la tabla `ESNI_DOSIS`
--
ALTER TABLE `ESNI_DOSIS`
  ADD PRIMARY KEY (`id_dosis`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_dosis_activo` (`activo`);

--
-- Indices de la tabla `ESNI_GRUPO_EDAD`
--
ALTER TABLE `ESNI_GRUPO_EDAD`
  ADD PRIMARY KEY (`id_grupo_edad`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_ge_activo` (`activo`);

--
-- Indices de la tabla `ESNI_LINEA_REPORTE`
--
ALTER TABLE `ESNI_LINEA_REPORTE`
  ADD PRIMARY KEY (`id_linea`),
  ADD KEY `fk_linea_vacuna` (`id_vacuna`),
  ADD KEY `fk_linea_dosis` (`id_dosis`),
  ADD KEY `fk_linea_ge` (`id_grupo_edad`),
  ADD KEY `idx_linea_sec_ord` (`id_seccion`,`orden`);

--
-- Indices de la tabla `ESNI_PARAMETRO`
--
ALTER TABLE `ESNI_PARAMETRO`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `ESNI_REGLA`
--
ALTER TABLE `ESNI_REGLA`
  ADD PRIMARY KEY (`id_regla`),
  ADD KEY `fk_regla_ge` (`id_grupo_edad`),
  ADD KEY `idx_regla_coditem` (`cod_item`),
  ADD KEY `idx_regla_linea` (`id_linea`),
  ADD KEY `idx_regla_activo` (`activo`);

--
-- Indices de la tabla `ESNI_SECCION_REPORTE`
--
ALTER TABLE `ESNI_SECCION_REPORTE`
  ADD PRIMARY KEY (`id_seccion`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_sec_activo_orden` (`activo`,`orden`);

--
-- Indices de la tabla `ESNI_VACUNA`
--
ALTER TABLE `ESNI_VACUNA`
  ADD PRIMARY KEY (`id_vacuna`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_vacuna_activo` (`activo`);

--
-- Indices de la tabla `IMPORT_ESTADO`
--
ALTER TABLE `IMPORT_ESTADO`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tipo_archivo` (`tipo_archivo`);

--
-- Indices de la tabla `LOG_IMPORTACION`
--
ALTER TABLE `LOG_IMPORTACION`
  ADD PRIMARY KEY (`id_log`);

--
-- Indices de la tabla `MAESTRO_HIS_CENTRO_POBLADO`
--
ALTER TABLE `MAESTRO_HIS_CENTRO_POBLADO`
  ADD KEY `idx_mhcp_id_centro_poblado` (`Id_Centro_Poblado`);

--
-- Indices de la tabla `MAESTRO_HIS_CIE_CPMS`
--
ALTER TABLE `MAESTRO_HIS_CIE_CPMS`
  ADD PRIMARY KEY (`Codi_Item`);

--
-- Indices de la tabla `MAESTRO_HIS_COLEGIO`
--
ALTER TABLE `MAESTRO_HIS_COLEGIO`
  ADD KEY `idx_m_colegio` (`Id_Colegio`),
  ADD KEY `idx_mhc_id_colegio` (`Id_Colegio`);

--
-- Indices de la tabla `MAESTRO_HIS_CONDICION_CONTRATO`
--
ALTER TABLE `MAESTRO_HIS_CONDICION_CONTRATO`
  ADD KEY `idx_m_condicion` (`Id_Condicion`),
  ADD KEY `idx_mhcc_id_condicion` (`Id_Condicion`);

--
-- Indices de la tabla `MAESTRO_HIS_ESTABLECIMIENTO`
--
ALTER TABLE `MAESTRO_HIS_ESTABLECIMIENTO`
  ADD KEY `idx_m_establecimiento` (`Id_Establecimiento`),
  ADD KEY `idx_mhes_id_establecimiento` (`Id_Establecimiento`);

--
-- Indices de la tabla `MAESTRO_HIS_ETNIA`
--
ALTER TABLE `MAESTRO_HIS_ETNIA`
  ADD KEY `idx_m_etnia` (`Id_Etnia`),
  ADD KEY `idx_mhe_id_etnia` (`Id_Etnia`);

--
-- Indices de la tabla `MAESTRO_HIS_FINANCIADOR`
--
ALTER TABLE `MAESTRO_HIS_FINANCIADOR`
  ADD KEY `idx_m_financiador` (`Id_Financiador`),
  ADD KEY `idx_mhf_id_financiador` (`Id_Financiador`);

--
-- Indices de la tabla `MAESTRO_HIS_OTRA_CONDICION`
--
ALTER TABLE `MAESTRO_HIS_OTRA_CONDICION`
  ADD KEY `idx_m_otra_cond` (`Id_Otra_Condicion`),
  ADD KEY `idx_mhoc_id_otra_condicion` (`Id_Otra_Condicion`);

--
-- Indices de la tabla `MAESTRO_HIS_PAIS`
--
ALTER TABLE `MAESTRO_HIS_PAIS`
  ADD PRIMARY KEY (`Id_Pais`);

--
-- Indices de la tabla `MAESTRO_HIS_PROFESION`
--
ALTER TABLE `MAESTRO_HIS_PROFESION`
  ADD KEY `idx_m_profesion` (`Id_Profesion`),
  ADD KEY `idx_mhpr_id_profesion` (`Id_Profesion`);

--
-- Indices de la tabla `MAESTRO_HIS_TIPO_DOC`
--
ALTER TABLE `MAESTRO_HIS_TIPO_DOC`
  ADD KEY `idx_m_tipo_doc` (`Id_Tipo_Documento`),
  ADD KEY `idx_mhtd_id_tipo_documento` (`Id_Tipo_Documento`);

--
-- Indices de la tabla `MAESTRO_HIS_UPS`
--
ALTER TABLE `MAESTRO_HIS_UPS`
  ADD KEY `idx_m_ups` (`Id_Ups`),
  ADD KEY `idx_mhu_id_ups` (`Id_Ups`);

--
-- Indices de la tabla `MAESTRO_PACIENTE`
--
ALTER TABLE `MAESTRO_PACIENTE`
  ADD KEY `idx_m_paciente` (`Id_Paciente`),
  ADD KEY `idx_mpa_id_paciente` (`Id_Paciente`),
  ADD KEY `idx_mpa_id_tipo_doc` (`Id_Tipo_Documento_Paciente`),
  ADD KEY `idx_mpa_id_etnia` (`Id_Etnia`),
  ADD KEY `idx_mpa_id_pais` (`Id_Pais`);

--
-- Indices de la tabla `MAESTRO_PERSONAL`
--
ALTER TABLE `MAESTRO_PERSONAL`
  ADD KEY `idx_m_personal` (`Id_Personal`),
  ADD KEY `idx_mp_id_personal` (`Id_Personal`),
  ADD KEY `idx_mp_id_tipo_doc` (`Id_Tipo_Documento_Personal`),
  ADD KEY `idx_mp_id_condicion` (`Id_Condicion`),
  ADD KEY `idx_mp_id_profesion` (`Id_Profesion`),
  ADD KEY `idx_mp_id_colegio` (`Id_Colegio`);

--
-- Indices de la tabla `MAESTRO_REGISTRADOR`
--
ALTER TABLE `MAESTRO_REGISTRADOR`
  ADD KEY `idx_m_registrador` (`Id_Registrador`),
  ADD KEY `idx_mr_id_registrador` (`Id_Registrador`),
  ADD KEY `idx_mr_id_tipo_doc` (`Id_Tipo_Documento_Registrador`);

--
-- Indices de la tabla `NOMINAL_TRAMA_NUEVO`
--
ALTER TABLE `NOMINAL_TRAMA_NUEVO`
  ADD KEY `idx_personal` (`Id_Personal`),
  ADD KEY `idx_paciente` (`Id_Paciente`),
  ADD KEY `idx_registrador` (`Id_Registrador`),
  ADD KEY `idx_otra_cond` (`Id_Otra_Condicion`),
  ADD KEY `idx_financiador` (`Id_Financiador`),
  ADD KEY `idx_ups` (`Id_Ups`),
  ADD KEY `idx_establecimiento` (`Id_Establecimiento`),
  ADD KEY `idx_centro_pob` (`Id_Centro_Poblado`),
  ADD KEY `idx_ntn_id_personal` (`Id_Personal`),
  ADD KEY `idx_ntn_id_paciente` (`Id_Paciente`),
  ADD KEY `idx_ntn_id_registrador` (`Id_Registrador`),
  ADD KEY `idx_ntn_id_establecimiento` (`Id_Establecimiento`),
  ADD KEY `idx_ntn_id_ups` (`Id_Ups`),
  ADD KEY `idx_ntn_id_financiador` (`Id_Financiador`),
  ADD KEY `idx_ntn_id_otra_condicion` (`Id_Otra_Condicion`),
  ADD KEY `idx_ntn_codigo_item` (`Codigo_Item`),
  ADD KEY `idx_ntn_id_centro_poblado` (`Id_Centro_Poblado`),
  ADD KEY `idx_ntn_anio_mes` (`Anio`,`Mes`);

--
-- Indices de la tabla `NOMINAL_TRAMA_PERIODOS`
--
ALTER TABLE `NOMINAL_TRAMA_PERIODOS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_periodo` (`periodo_anio`,`periodo_mes`);

--
-- Indices de la tabla `USUARIOS`
--
ALTER TABLE `USUARIOS`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uk_usuario` (`usuario`);

--
-- Indices de la tabla `ZSPERENE`
--
ALTER TABLE `ZSPERENE`
  ADD PRIMARY KEY (`Codigo_Unico`),
  ADD KEY `Id_Establecimiento` (`Id_Establecimiento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `CONTROL_CALIDAD_OBSERVACIONES`
--
ALTER TABLE `CONTROL_CALIDAD_OBSERVACIONES`
  MODIFY `id_observacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_FED_AVANCE`
--
ALTER TABLE `CONVENIO_FED_AVANCE`
  MODIFY `id_avance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_FED_INDICADORES`
--
ALTER TABLE `CONVENIO_FED_INDICADORES`
  MODIFY `id_indicador` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_GESTION_AVANCE`
--
ALTER TABLE `CONVENIO_GESTION_AVANCE`
  MODIFY `id_avance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_GESTION_INDICADORES`
--
ALTER TABLE `CONVENIO_GESTION_INDICADORES`
  MODIFY `id_indicador` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ESNI_DOSIS`
--
ALTER TABLE `ESNI_DOSIS`
  MODIFY `id_dosis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `ESNI_GRUPO_EDAD`
--
ALTER TABLE `ESNI_GRUPO_EDAD`
  MODIFY `id_grupo_edad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `ESNI_LINEA_REPORTE`
--
ALTER TABLE `ESNI_LINEA_REPORTE`
  MODIFY `id_linea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT de la tabla `ESNI_REGLA`
--
ALTER TABLE `ESNI_REGLA`
  MODIFY `id_regla` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT de la tabla `ESNI_SECCION_REPORTE`
--
ALTER TABLE `ESNI_SECCION_REPORTE`
  MODIFY `id_seccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `ESNI_VACUNA`
--
ALTER TABLE `ESNI_VACUNA`
  MODIFY `id_vacuna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `IMPORT_ESTADO`
--
ALTER TABLE `IMPORT_ESTADO`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `LOG_IMPORTACION`
--
ALTER TABLE `LOG_IMPORTACION`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `NOMINAL_TRAMA_PERIODOS`
--
ALTER TABLE `NOMINAL_TRAMA_PERIODOS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `USUARIOS`
--
ALTER TABLE `USUARIOS`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `CONVENIO_FED_AVANCE`
--
ALTER TABLE `CONVENIO_FED_AVANCE`
  ADD CONSTRAINT `fk_feda_indicador` FOREIGN KEY (`id_indicador`) REFERENCES `CONVENIO_FED_INDICADORES` (`id_indicador`) ON DELETE CASCADE;

--
-- Filtros para la tabla `CONVENIO_GESTION_AVANCE`
--
ALTER TABLE `CONVENIO_GESTION_AVANCE`
  ADD CONSTRAINT `fk_cga_indicador` FOREIGN KEY (`id_indicador`) REFERENCES `CONVENIO_GESTION_INDICADORES` (`id_indicador`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ESNI_LINEA_REPORTE`
--
ALTER TABLE `ESNI_LINEA_REPORTE`
  ADD CONSTRAINT `fk_linea_dosis` FOREIGN KEY (`id_dosis`) REFERENCES `ESNI_DOSIS` (`id_dosis`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_linea_ge` FOREIGN KEY (`id_grupo_edad`) REFERENCES `ESNI_GRUPO_EDAD` (`id_grupo_edad`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_linea_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `ESNI_SECCION_REPORTE` (`id_seccion`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_linea_vacuna` FOREIGN KEY (`id_vacuna`) REFERENCES `ESNI_VACUNA` (`id_vacuna`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ESNI_REGLA`
--
ALTER TABLE `ESNI_REGLA`
  ADD CONSTRAINT `fk_regla_ge` FOREIGN KEY (`id_grupo_edad`) REFERENCES `ESNI_GRUPO_EDAD` (`id_grupo_edad`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_regla_linea` FOREIGN KEY (`id_linea`) REFERENCES `ESNI_LINEA_REPORTE` (`id_linea`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
