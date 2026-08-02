-- Base de datos: `if0_42181393_his`
-- Estructura de tabla para la tabla `CONTROL_CALIDAD_OBSERVACIONES`
--

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

-- --------------------------------------------------------

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
(56, 4, 1, 'dT 1ra - Mujeres 5 a 9 anos', 18, 1, 29, 'F', 1),
(57, 4, 2, 'dT 2da - Mujeres 5 a 9 anos', 18, 2, 29, 'F', 1),
(58, 4, 3, 'dT 3ra - Mujeres 5 a 9 anos', 18, 3, 29, 'F', 1),
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
(93, 17, 3, 'REFUERZO DPT', 17, 2, 28, 'A', 1),
(94, 4, 4, 'dT 1ra - Mujeres 10 a 11 anos', 18, 1, 30, 'F', 1),
(95, 4, 5, 'dT 2da - Mujeres 10 a 11 anos', 18, 2, 30, 'F', 1),
(96, 4, 6, 'dT 3ra - Mujeres 10 a 11 anos', 18, 3, 30, 'F', 1),
(97, 4, 7, 'dT 1ra - Mujeres 12 a 17 anos', 18, 1, 31, 'F', 1),
(98, 4, 8, 'dT 2da - Mujeres 12 a 17 anos', 18, 2, 31, 'F', 1),
(100, 4, 9, 'dT 3ra - Mujeres 12 a 17 anos', 18, 3, 31, 'F', 1),
(101, 4, 10, 'dT 1ra - Mujeres 18 a 29 anos', 18, 1, 32, 'F', 1),
(102, 4, 11, 'dT 2da - Mujeres 18 a 29 anos', 18, 2, 32, 'F', 1),
(103, 4, 12, 'dT 3ra - Mujeres 18 a 29 anos', 18, 3, 32, 'F', 1),
(104, 4, 13, 'dT 1ra - Mujeres 30 a 49 anos', 18, 1, 33, 'F', 1),
(105, 4, 14, 'dT 2da - Mujeres 30 a 49 anos', 18, 2, 33, 'F', 1),
(106, 4, 15, 'dT 3ra - Mujeres 30 a 49 anos', 18, 3, 33, 'F', 1);

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
(102, 22, '90657', '1', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(103, 22, '90657', '01', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(104, 22, '90657', 'D1', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(108, 22, '90687', '1', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(109, 22, '90687', '01', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(110, 22, '90687', 'D1', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
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
(162, 56, '90714', '1', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(163, 56, '90714', '01', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(164, 56, '90714', 'D1', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(165, 57, '90714', '2', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(166, 57, '90714', '02', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(167, 57, '90714', 'D2', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(168, 58, '90714', '3', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(169, 58, '90714', '03', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(170, 58, '90714', 'D3', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1),
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
(199, 22, '90657', 'DU', 7, 'A', NULL, NULL, 0, 0, 0, 0, 1),
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
(233, 93, '90701', 'DDA', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1),
(234, 94, '90714', '1', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(235, 94, '90714', 'D1', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(236, 95, '90714', '2', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(237, 95, '90714', 'D2', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(238, 96, '90714', '3', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(239, 96, '90714', 'D3', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(240, 97, '90714', '1', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(241, 97, '90714', 'D1', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(242, 98, '90714', '2', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(243, 98, '90714', 'D2', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(244, 100, '90714', '3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(245, 96, '90714', 'D3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(246, 100, '90714', 'D3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(247, 101, '90714', '1', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(248, 101, '90714', 'D1', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(249, 102, '90714', '2', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(250, 102, '90714', 'D2', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(251, 103, '90714', '3', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(252, 103, '90714', 'D3', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(253, 104, '90714', '1', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(254, 104, '90714', 'D1', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(255, 105, '90714', 'D2', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(256, 105, '90714', '2', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(257, 106, '90714', '3', 35, 'F', NULL, NULL, 0, 0, 0, 0, 1),
(258, 106, '90714', 'D3', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1);

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
(4, 'F', 'F. - dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS', 'Esquema dT en mujeres en edad fertil desde 5 anios', 'matriz_dosis', 4, 1),
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

-- --------------------------------------------------------

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_UPS`
--

CREATE TABLE `MAESTRO_HIS_UPS` (
  `Id_Ups` varchar(6) DEFAULT NULL,
  `Descripcion_Ups` varchar(62) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Fecha_Modificacion` datetime DEFAULT NULL,
  `Mes_Int` int(11) GENERATED ALWAYS AS (cast(trim(`Mes`) as unsigned)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`
--

INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418621352', '2026', '6', '26', '2026-06-26', 'F05', 11, 22, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4436799318', 1, 'DNI', '62657734', 'PEREZ', 'CCORIÑAUPA', 'CARLO GIANFRANCO', '2010-07-30', 'M', '62657734', NULL, '05', 'ASHANINKA', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 10, 27, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621352', '2026', '6', '26', '2026-06-26', 'F05', 11, 22, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4436799318', 1, 'DNI', '62657734', 'PEREZ', 'CCORIÑAUPA', 'CARLO GIANFRANCO', '2010-07-30', 'M', '62657734', NULL, '05', 'ASHANINKA', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 10, 27, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621494', '2026', '6', '26', '2026-06-26', 'F05', 11, 23, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2530844318', 1, 'DNI', '62768437', 'QUISPE', 'ANCALLE', 'DAZLY ANGELES', '2010-06-19', 'F', '62768437', '62814', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 0, 7, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621494', '2026', '6', '26', '2026-06-26', 'F05', 11, 23, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2530844318', 1, 'DNI', '62768437', 'QUISPE', 'ANCALLE', 'DAZLY ANGELES', '2010-06-19', 'F', '62768437', '62814', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 0, 7, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621494', '2026', '6', '26', '2026-06-26', 'F05', 11, 23, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2530844318', 1, 'DNI', '62768437', 'QUISPE', 'ANCALLE', 'DAZLY ANGELES', '2010-06-19', 'F', '62768437', '62814', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 0, 7, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621640', '2026', '6', '26', '2026-06-26', 'F05', 11, 24, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834496318', 1, 'DNI', '62520202', 'ROJAS', 'CASTILLO', 'JERSY EDISON', '2010-12-19', 'M', '62520202', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 7, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621640', '2026', '6', '26', '2026-06-26', 'F05', 11, 24, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834496318', 1, 'DNI', '62520202', 'ROJAS', 'CASTILLO', 'JERSY EDISON', '2010-12-19', 'M', '62520202', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 7, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621640', '2026', '6', '26', '2026-06-26', 'F05', 11, 24, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834496318', 1, 'DNI', '62520202', 'ROJAS', 'CASTILLO', 'JERSY EDISON', '2010-12-19', 'M', '62520202', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 7, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621781', '2026', '6', '26', '2026-06-26', 'F05', 11, 25, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409692318', 1, 'DNI', '62426934', 'SEGURA', 'MANTARI', 'ADRIAN SAM', '2010-11-23', 'M', '62426934', '63711', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 3, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621781', '2026', '6', '26', '2026-06-26', 'F05', 11, 25, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409692318', 1, 'DNI', '62426934', 'SEGURA', 'MANTARI', 'ADRIAN SAM', '2010-11-23', 'M', '62426934', '63711', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621781', '2026', '6', '26', '2026-06-26', 'F05', 11, 25, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409692318', 1, 'DNI', '62426934', 'SEGURA', 'MANTARI', 'ADRIAN SAM', '2010-11-23', 'M', '62426934', '63711', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418622761', '2026', '6', '26', '2026-06-26', 'F05', 12, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834510318', 1, 'DNI', '62768371', 'SIHUINTA', 'HERRERA', 'YADIEL ALEXANDER', '2011-02-14', 'M', '62768371', '63696', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 4, 12, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418622761', '2026', '6', '26', '2026-06-26', 'F05', 12, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834510318', 1, 'DNI', '62768371', 'SIHUINTA', 'HERRERA', 'YADIEL ALEXANDER', '2011-02-14', 'M', '62768371', '63696', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 4, 12, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418622761', '2026', '6', '26', '2026-06-26', 'F05', 12, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834510318', 1, 'DNI', '62768371', 'SIHUINTA', 'HERRERA', 'YADIEL ALEXANDER', '2011-02-14', 'M', '62768371', '63696', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 4, 12, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418622896', '2026', '6', '26', '2026-06-26', 'F05', 12, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10233228318', 1, 'DNI', '61695049', 'USCUVILCA', 'ONOFRE', 'LUHANA JALETSY', '2010-05-21', 'F', '61695049', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418622896', '2026', '6', '26', '2026-06-26', 'F05', 12, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10233228318', 1, 'DNI', '61695049', 'USCUVILCA', 'ONOFRE', 'LUHANA JALETSY', '2010-05-21', 'F', '61695049', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418622896', '2026', '6', '26', '2026-06-26', 'F05', 12, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10233228318', 1, 'DNI', '61695049', 'USCUVILCA', 'ONOFRE', 'LUHANA JALETSY', '2010-05-21', 'F', '61695049', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418623043', '2026', '6', '26', '2026-06-26', 'F05', 12, 3, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835179318', 1, 'DNI', '62690454', 'YARANGO', 'GOMEZ', 'GABRIEL DE JESUS ANDRE', '2011-03-12', 'M', '62690454', '63708', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 14, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418623043', '2026', '6', '26', '2026-06-26', 'F05', 12, 3, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835179318', 1, 'DNI', '62690454', 'YARANGO', 'GOMEZ', 'GABRIEL DE JESUS ANDRE', '2011-03-12', 'M', '62690454', '63708', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 14, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418623043', '2026', '6', '26', '2026-06-26', 'F05', 12, 3, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835179318', 1, 'DNI', '62690454', 'YARANGO', 'GOMEZ', 'GABRIEL DE JESUS ANDRE', '2011-03-12', 'M', '62690454', '63708', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 14, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418628353', '2026', '6', '30', '2026-06-30', 'F56', 12, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2982224318', 1, 'DNI', '72275409', 'ARRIOLA', 'JOCHATUMA', 'ISABEL', '1997-02-02', 'F', '72275409', '31181', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2246967318', 1, 'DNI', '48482099', 'HINOSTROZA', 'MIRANDA', 'JENNY LAURA', '1994-10-10', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', 'C', 'C', 29, 'A', 29, 4, 28, '18 a 29 años', NULL, 'M', 'CP', '99401.08', 'CONSEJERÍA DE IDENTIFICACIÓN DE SIGNOS DE ALARMA', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, 2, 'PUERPERA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418628353', '2026', '6', '30', '2026-06-30', 'F56', 12, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2982224318', 1, 'DNI', '72275409', 'ARRIOLA', 'JOCHATUMA', 'ISABEL', '1997-02-02', 'F', '72275409', '31181', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2246967318', 1, 'DNI', '48482099', 'HINOSTROZA', 'MIRANDA', 'JENNY LAURA', '1994-10-10', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', 'C', 'C', 29, 'A', 29, 4, 28, '18 a 29 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, 2, 'PUERPERA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629626', '2026', '6', '30', '2026-06-30', 'F05', 12, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4133281318', 1, 'DNI', '61382908', 'MAMANI', 'ACUÑA', 'LEONELA MITZI', '2008-07-29', 'F', '61382908', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 17, 'A', 17, 11, 1, '12 a 17 años', NULL, 'M', 'CP', '96150.02', 'TAMIZAJE DE SALUD MENTAL EN ALCOHOL Y DROGAS', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629626', '2026', '6', '30', '2026-06-30', 'F05', 12, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4133281318', 1, 'DNI', '61382908', 'MAMANI', 'ACUÑA', 'LEONELA MITZI', '2008-07-29', 'F', '61382908', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 17, 'A', 17, 11, 1, '12 a 17 años', NULL, 'M', 'CX', 'Z721', 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE ALCOHOL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629626', '2026', '6', '30', '2026-06-30', 'F05', 12, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4133281318', 1, 'DNI', '61382908', 'MAMANI', 'ACUÑA', 'LEONELA MITZI', '2008-07-29', 'F', '61382908', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 17, 'A', 17, 11, 1, '12 a 17 años', NULL, 'M', 'CP', '99401.13', 'CONSEJERÍA EN ESTILOS DE VIDA SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629626', '2026', '6', '30', '2026-06-30', 'F05', 12, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4133281318', 1, 'DNI', '61382908', 'MAMANI', 'ACUÑA', 'LEONELA MITZI', '2008-07-29', 'F', '61382908', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 17, 'A', 17, 11, 1, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629626', '2026', '6', '30', '2026-06-30', 'F05', 12, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4133281318', 1, 'DNI', '61382908', 'MAMANI', 'ACUÑA', 'LEONELA MITZI', '2008-07-29', 'F', '61382908', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 17, 'A', 17, 11, 1, '12 a 17 años', NULL, 'M', 'CP', '99402.09', 'CONSEJERÍA DE PREVENCIÓN DE RIESS EN SALUD MENTAL', 'D', NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629790', '2026', '6', '30', '2026-06-30', 'F05', 12, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '9960609318', 1, 'DNI', '76888278', 'GONZALES', 'LINO', 'CESIA YOLANDA', '2011-03-28', 'F', '76888278', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 2, '12 a 17 años', NULL, 'M', 'CP', '96150.02', 'TAMIZAJE DE SALUD MENTAL EN ALCOHOL Y DROGAS', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629790', '2026', '6', '30', '2026-06-30', 'F05', 12, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '9960609318', 1, 'DNI', '76888278', 'GONZALES', 'LINO', 'CESIA YOLANDA', '2011-03-28', 'F', '76888278', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 2, '12 a 17 años', NULL, 'M', 'CX', 'Z721', 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE ALCOHOL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629790', '2026', '6', '30', '2026-06-30', 'F05', 12, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '9960609318', 1, 'DNI', '76888278', 'GONZALES', 'LINO', 'CESIA YOLANDA', '2011-03-28', 'F', '76888278', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 2, '12 a 17 años', NULL, 'M', 'CP', '99401.13', 'CONSEJERÍA EN ESTILOS DE VIDA SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629790', '2026', '6', '30', '2026-06-30', 'F05', 12, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '9960609318', 1, 'DNI', '76888278', 'GONZALES', 'LINO', 'CESIA YOLANDA', '2011-03-28', 'F', '76888278', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 2, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629790', '2026', '6', '30', '2026-06-30', 'F05', 12, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '9960609318', 1, 'DNI', '76888278', 'GONZALES', 'LINO', 'CESIA YOLANDA', '2011-03-28', 'F', '76888278', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 2, '12 a 17 años', NULL, 'M', 'CP', '99402.09', 'CONSEJERÍA DE PREVENCIÓN DE RIESS EN SALUD MENTAL', 'D', NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629987', '2026', '6', '30', '2026-06-30', 'F05', 12, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10066586318', 1, 'DNI', '78861063', 'ESPILCO', 'PANGOSA', 'ISABEL SUY CHOY', '2009-06-13', 'F', '78861063', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'R', 17, 'A', 17, 0, 17, '12 a 17 años', NULL, 'M', 'CP', '96150.02', 'TAMIZAJE DE SALUD MENTAL EN ALCOHOL Y DROGAS', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629987', '2026', '6', '30', '2026-06-30', 'F05', 12, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10066586318', 1, 'DNI', '78861063', 'ESPILCO', 'PANGOSA', 'ISABEL SUY CHOY', '2009-06-13', 'F', '78861063', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'R', 17, 'A', 17, 0, 17, '12 a 17 años', NULL, 'M', 'CX', 'Z721', 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE ALCOHOL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629987', '2026', '6', '30', '2026-06-30', 'F05', 12, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10066586318', 1, 'DNI', '78861063', 'ESPILCO', 'PANGOSA', 'ISABEL SUY CHOY', '2009-06-13', 'F', '78861063', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'R', 17, 'A', 17, 0, 17, '12 a 17 años', NULL, 'M', 'CP', '99401.13', 'CONSEJERÍA EN ESTILOS DE VIDA SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418629987', '2026', '6', '30', '2026-06-30', 'F05', 12, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10066586318', 1, 'DNI', '78861063', 'ESPILCO', 'PANGOSA', 'ISABEL SUY CHOY', '2009-06-13', 'F', '78861063', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'R', 17, 'A', 17, 0, 17, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418629987', '2026', '6', '30', '2026-06-30', 'F05', 12, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10066586318', 1, 'DNI', '78861063', 'ESPILCO', 'PANGOSA', 'ISABEL SUY CHOY', '2009-06-13', 'F', '78861063', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'R', 17, 'A', 17, 0, 17, '12 a 17 años', NULL, 'M', 'CP', '99402.09', 'CONSEJERÍA DE PREVENCIÓN DE RIESS EN SALUD MENTAL', 'D', NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418995420', '2026', '6', '13', '2026-06-13', 'F01', 4, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63932, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035583', 'LA ESPERANZA - PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5207681263932', 1, 'DNI', '93424483', 'ARRIOLA', 'CONTRERAS', 'LIAM MATTHEO', '2023-06-13', 'M', '93424483', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '95497963932', 1, 'DNI', '42109674', 'EGOAVIL', 'HINOSTROZA', 'RUYMER', '1980-02-10', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 3, 'A', 3, 0, 0, '01 a 04 años', NULL, 'M', 'CX', 'Z289', 'INMUNIZACION NO REALIZADA POR RAZON NO ESPECIFICADA', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418995420', '2026', '6', '13', '2026-06-13', 'F01', 4, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63932, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035583', 'LA ESPERANZA - PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5207681263932', 1, 'DNI', '93424483', 'ARRIOLA', 'CONTRERAS', 'LIAM MATTHEO', '2023-06-13', 'M', '93424483', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '95497963932', 1, 'DNI', '42109674', 'EGOAVIL', 'HINOSTROZA', 'RUYMER', '1980-02-10', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 3, 'A', 3, 0, 0, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418631748', '2026', '6', '30', '2026-06-30', 'F05', 12, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2833615318', 1, 'DNI', '62768376', 'PUENTE', 'BALDEON', 'KENNETH SLYM', '2010-12-05', 'M', '62768376', '63661', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 25, '12 a 17 años', NULL, 'M', 'CP', '96150.02', 'TAMIZAJE DE SALUD MENTAL EN ALCOHOL Y DROGAS', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418631748', '2026', '6', '30', '2026-06-30', 'F05', 12, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2833615318', 1, 'DNI', '62768376', 'PUENTE', 'BALDEON', 'KENNETH SLYM', '2010-12-05', 'M', '62768376', '63661', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 25, '12 a 17 años', NULL, 'M', 'CX', 'Z721', 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE ALCOHOL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418631748', '2026', '6', '30', '2026-06-30', 'F05', 12, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2833615318', 1, 'DNI', '62768376', 'PUENTE', 'BALDEON', 'KENNETH SLYM', '2010-12-05', 'M', '62768376', '63661', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 25, '12 a 17 años', NULL, 'M', 'CP', '99401.13', 'CONSEJERÍA EN ESTILOS DE VIDA SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418631748', '2026', '6', '30', '2026-06-30', 'F05', 12, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2833615318', 1, 'DNI', '62768376', 'PUENTE', 'BALDEON', 'KENNETH SLYM', '2010-12-05', 'M', '62768376', '63661', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 25, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418631748', '2026', '6', '30', '2026-06-30', 'F05', 12, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2833615318', 1, 'DNI', '62768376', 'PUENTE', 'BALDEON', 'KENNETH SLYM', '2010-12-05', 'M', '62768376', '63661', '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 25, '12 a 17 años', NULL, 'M', 'CP', '99402.09', 'CONSEJERÍA DE PREVENCIÓN DE RIESS EN SALUD MENTAL', 'D', NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418632101', '2026', '6', '30', '2026-06-30', 'F05', 12, 11, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '26356260318', 1, 'DNI', '62657739', 'CAVANILLAS', 'SANTOS', 'NELL ANDERSON', '2010-07-17', 'M', '62657739', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 13, '12 a 17 años', NULL, 'M', 'CP', '96150.02', 'TAMIZAJE DE SALUD MENTAL EN ALCOHOL Y DROGAS', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418632101', '2026', '6', '30', '2026-06-30', 'F05', 12, 11, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '26356260318', 1, 'DNI', '62657739', 'CAVANILLAS', 'SANTOS', 'NELL ANDERSON', '2010-07-17', 'M', '62657739', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 13, '12 a 17 años', NULL, 'M', 'CX', 'Z721', 'PROBLEMAS SOCIALES RELACIONADOS CON EL USO DE ALCOHOL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418632101', '2026', '6', '30', '2026-06-30', 'F05', 12, 11, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '26356260318', 1, 'DNI', '62657739', 'CAVANILLAS', 'SANTOS', 'NELL ANDERSON', '2010-07-17', 'M', '62657739', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 13, '12 a 17 años', NULL, 'M', 'CP', '99401.13', 'CONSEJERÍA EN ESTILOS DE VIDA SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418632101', '2026', '6', '30', '2026-06-30', 'F05', 12, 11, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '26356260318', 1, 'DNI', '62657739', 'CAVANILLAS', 'SANTOS', 'NELL ANDERSON', '2010-07-17', 'M', '62657739', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 13, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418632101', '2026', '6', '30', '2026-06-30', 'F05', 12, 11, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '26356260318', 1, 'DNI', '62657739', 'CAVANILLAS', 'SANTOS', 'NELL ANDERSON', '2010-07-17', 'M', '62657739', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 13, '12 a 17 años', NULL, 'M', 'CP', '99402.09', 'CONSEJERÍA DE PREVENCIÓN DE RIESS EN SALUD MENTAL', 'D', NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418633724', '2026', '6', '23', '2026-06-23', 'F05', 10, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6664683318', 1, 'DNI', '63710803', 'VALVERDE', 'SUAREZ', 'JERALDINE SAYURI', '2013-03-22', 'F', '63710803', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 13, 'A', 13, 3, 1, '12 a 17 años', NULL, 'M', 'CP', '96150.01', 'TAMIZAJE DE SALUD MENTAL EN VIOLENCIA', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418633724', '2026', '6', '23', '2026-06-23', 'F05', 10, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6664683318', 1, 'DNI', '63710803', 'VALVERDE', 'SUAREZ', 'JERALDINE SAYURI', '2013-03-22', 'F', '63710803', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 13, 'A', 13, 3, 1, '12 a 17 años', NULL, 'M', 'CP', '99402.09', 'CONSEJERÍA DE PREVENCIÓN DE RIESS EN SALUD MENTAL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418633724', '2026', '6', '23', '2026-06-23', 'F05', 10, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6664683318', 1, 'DNI', '63710803', 'VALVERDE', 'SUAREZ', 'JERALDINE SAYURI', '2013-03-22', 'F', '63710803', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 13, 'A', 13, 3, 1, '12 a 17 años', NULL, 'M', 'CP', '96150.03', 'TAMIZAJE DE SALUD MENTAL EN TRASTORNOS DEPRESIVOS', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418633724', '2026', '6', '23', '2026-06-23', 'F05', 10, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6664683318', 1, 'DNI', '63710803', 'VALVERDE', 'SUAREZ', 'JERALDINE SAYURI', '2013-03-22', 'F', '63710803', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 13, 'A', 13, 3, 1, '12 a 17 años', NULL, 'M', 'CP', '96100.02', 'CONSEJERÍA Y ORIENTACIÓN PSICOLÓGICA', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418633724', '2026', '6', '23', '2026-06-23', 'F05', 10, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6664683318', 1, 'DNI', '63710803', 'VALVERDE', 'SUAREZ', 'JERALDINE SAYURI', '2013-03-22', 'F', '63710803', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 13, 'A', 13, 3, 1, '12 a 17 años', NULL, 'M', 'CP', '96150.05', ' TAMIZAJE DE SALUD MENTAL EN HABILIDADES SOCIALES', 'D', NULL, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418633724', '2026', '6', '23', '2026-06-23', 'F05', 10, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6664683318', 1, 'DNI', '63710803', 'VALVERDE', 'SUAREZ', 'JERALDINE SAYURI', '2013-03-22', 'F', '63710803', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 13, 'A', 13, 3, 1, '12 a 17 años', NULL, 'M', 'CP', '99401.15', 'CONSEJERÍA EN HABILIDADES SOCIALES', 'D', NULL, 6, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418637419', '2026', '6', '30', '2026-06-30', 'F5', 15, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3857014318', 1, 'DNI', '47695046', 'PERALTA', 'RICRA', 'RAFAEL ANDY', '1993-04-20', 'M', '47695046', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '759283318', 1, 'DNI', '19914928', 'PALOMINO', 'LEON', 'NEMESIA AGRIPINA', '1961-05-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7948905', 1, 'DNI', '46429075', 'SIXTO', 'ANDRES', 'TIFFANY SLIDES', '1990-07-27', 'C', 'C', 33, 'A', 33, 2, 10, '30 a 59 años', NULL, 'M', 'CP', 'C0009', 'SESIÓN EDUCATIVA', 'D', 'ALI', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418637419', '2026', '6', '30', '2026-06-30', 'F5', 15, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3857014318', 1, 'DNI', '47695046', 'PERALTA', 'RICRA', 'RAFAEL ANDY', '1993-04-20', 'M', '47695046', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '759283318', 1, 'DNI', '19914928', 'PALOMINO', 'LEON', 'NEMESIA AGRIPINA', '1961-05-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7948905', 1, 'DNI', '46429075', 'SIXTO', 'ANDRES', 'TIFFANY SLIDES', '1990-07-27', 'C', 'C', 33, 'A', 33, 2, 10, '30 a 59 años', NULL, 'M', 'CP', 'C0009', 'SESIÓN EDUCATIVA', 'D', 'AFI', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418637419', '2026', '6', '30', '2026-06-30', 'F5', 15, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3857014318', 1, 'DNI', '47695046', 'PERALTA', 'RICRA', 'RAFAEL ANDY', '1993-04-20', 'M', '47695046', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '759283318', 1, 'DNI', '19914928', 'PALOMINO', 'LEON', 'NEMESIA AGRIPINA', '1961-05-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7948905', 1, 'DNI', '46429075', 'SIXTO', 'ANDRES', 'TIFFANY SLIDES', '1990-07-27', 'C', 'C', 33, 'A', 33, 2, 10, '30 a 59 años', NULL, 'M', 'CP', 'C0009', 'SESIÓN EDUCATIVA', 'D', 'SBU', 3, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418637419', '2026', '6', '30', '2026-06-30', 'F5', 15, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3857014318', 1, 'DNI', '47695046', 'PERALTA', 'RICRA', 'RAFAEL ANDY', '1993-04-20', 'M', '47695046', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '759283318', 1, 'DNI', '19914928', 'PALOMINO', 'LEON', 'NEMESIA AGRIPINA', '1961-05-25', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '7948905', 1, 'DNI', '46429075', 'SIXTO', 'ANDRES', 'TIFFANY SLIDES', '1990-07-27', 'C', 'C', 33, 'A', 33, 2, 10, '30 a 59 años', NULL, 'M', 'CP', 'C0010', 'SESIÓN DEMOSTRATIVA', 'D', '18', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620202', '2026', '6', '26', '2026-06-26', 'F05', 11, 15, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834121318', 1, 'DNI', '62931884', 'LOPEZ', 'PALOMINO', 'BIANCCA STEFANY', '2011-03-07', 'F', '62931884', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 19, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620369', '2026', '6', '26', '2026-06-26', 'F05', 11, 16, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2500695318', 1, 'DNI', '62768411', 'MELGAR', 'HUAMAN', 'NAYELY EYPRIE', '2011-03-19', 'F', '62768411', '63808', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 7, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620369', '2026', '6', '26', '2026-06-26', 'F05', 11, 16, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2500695318', 1, 'DNI', '62768411', 'MELGAR', 'HUAMAN', 'NAYELY EYPRIE', '2011-03-19', 'F', '62768411', '63808', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 7, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620369', '2026', '6', '26', '2026-06-26', 'F05', 11, 16, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2500695318', 1, 'DNI', '62768411', 'MELGAR', 'HUAMAN', 'NAYELY EYPRIE', '2011-03-19', 'F', '62768411', '63808', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 7, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620660', '2026', '6', '26', '2026-06-26', 'F05', 11, 18, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4421255318', 1, 'DNI', '62446423', 'NUÑEZ', 'CASIMIRO', 'MILENA JHOANA', '2010-05-21', 'F', '62446423', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620660', '2026', '6', '26', '2026-06-26', 'F05', 11, 18, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4421255318', 1, 'DNI', '62446423', 'NUÑEZ', 'CASIMIRO', 'MILENA JHOANA', '2010-05-21', 'F', '62446423', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620660', '2026', '6', '26', '2026-06-26', 'F05', 11, 18, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4421255318', 1, 'DNI', '62446423', 'NUÑEZ', 'CASIMIRO', 'MILENA JHOANA', '2010-05-21', 'F', '62446423', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620861', '2026', '6', '26', '2026-06-26', 'F05', 11, 19, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1697872318', 1, 'DNI', '62446424', 'NUÑEZ', 'CASIMIRO', 'XIOMARA ROSSELLY', '2010-05-21', 'F', '62446424', '62709', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620861', '2026', '6', '26', '2026-06-26', 'F05', 11, 19, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1697872318', 1, 'DNI', '62446424', 'NUÑEZ', 'CASIMIRO', 'XIOMARA ROSSELLY', '2010-05-21', 'F', '62446424', '62709', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620861', '2026', '6', '26', '2026-06-26', 'F05', 11, 19, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1697872318', 1, 'DNI', '62446424', 'NUÑEZ', 'CASIMIRO', 'XIOMARA ROSSELLY', '2010-05-21', 'F', '62446424', '62709', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 5, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621032', '2026', '6', '26', '2026-06-26', 'F05', 11, 20, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1698039318', 1, 'DNI', '62520083', 'ORDOÑEZ', 'ORIETA', 'KATHERINNE DEL PILAR', '2010-09-22', 'F', '62520083', '63184', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 9, 4, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621032', '2026', '6', '26', '2026-06-26', 'F05', 11, 20, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1698039318', 1, 'DNI', '62520083', 'ORDOÑEZ', 'ORIETA', 'KATHERINNE DEL PILAR', '2010-09-22', 'F', '62520083', '63184', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 9, 4, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621032', '2026', '6', '26', '2026-06-26', 'F05', 11, 20, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1698039318', 1, 'DNI', '62520083', 'ORDOÑEZ', 'ORIETA', 'KATHERINNE DEL PILAR', '2010-09-22', 'F', '62520083', '63184', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 9, 4, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418621202', '2026', '6', '26', '2026-06-26', 'F05', 11, 21, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409743318', 1, 'DNI', '62520143', 'PAUCAR', 'ROSALES', 'HUNEIN FLOR', '2010-10-23', 'F', '62520143', '403', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 8, 3, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621202', '2026', '6', '26', '2026-06-26', 'F05', 11, 21, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409743318', 1, 'DNI', '62520143', 'PAUCAR', 'ROSALES', 'HUNEIN FLOR', '2010-10-23', 'F', '62520143', '403', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 8, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621202', '2026', '6', '26', '2026-06-26', 'F05', 11, 21, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409743318', 1, 'DNI', '62520143', 'PAUCAR', 'ROSALES', 'HUNEIN FLOR', '2010-10-23', 'F', '62520143', '403', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 8, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418621352', '2026', '6', '26', '2026-06-26', 'F05', 11, 22, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4436799318', 1, 'DNI', '62657734', 'PEREZ', 'CCORIÑAUPA', 'CARLO GIANFRANCO', '2010-07-30', 'M', '62657734', NULL, '05', 'ASHANINKA', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 10, 27, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619866', '2026', '6', '26', '2026-06-26', 'F05', 11, 13, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5627407318', 1, 'DNI', '62520220', 'HUAMANTINCO', 'ABREGU', 'LUZ ROSSICELA', '2010-12-24', 'F', '62520220', '75009', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 2, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619866', '2026', '6', '26', '2026-06-26', 'F05', 11, 13, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5627407318', 1, 'DNI', '62520220', 'HUAMANTINCO', 'ABREGU', 'LUZ ROSSICELA', '2010-12-24', 'F', '62520220', '75009', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 2, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619866', '2026', '6', '26', '2026-06-26', 'F05', 11, 13, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5627407318', 1, 'DNI', '62520220', 'HUAMANTINCO', 'ABREGU', 'LUZ ROSSICELA', '2010-12-24', 'F', '62520220', '75009', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 2, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620041', '2026', '6', '26', '2026-06-26', 'F05', 11, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5990615318', 1, 'DNI', '62403356', 'ISLACHIN', 'ALDERETE', 'ABRIL MILAGROS', '2010-05-01', 'F', '62403356', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 25, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620041', '2026', '6', '26', '2026-06-26', 'F05', 11, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5990615318', 1, 'DNI', '62403356', 'ISLACHIN', 'ALDERETE', 'ABRIL MILAGROS', '2010-05-01', 'F', '62403356', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 25, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620041', '2026', '6', '26', '2026-06-26', 'F05', 11, 14, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5990615318', 1, 'DNI', '62403356', 'ISLACHIN', 'ALDERETE', 'ABRIL MILAGROS', '2010-05-01', 'F', '62403356', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 1, 25, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620202', '2026', '6', '26', '2026-06-26', 'F05', 11, 15, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834121318', 1, 'DNI', '62931884', 'LOPEZ', 'PALOMINO', 'BIANCCA STEFANY', '2011-03-07', 'F', '62931884', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 19, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418620202', '2026', '6', '26', '2026-06-26', 'F05', 11, 15, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834121318', 1, 'DNI', '62931884', 'LOPEZ', 'PALOMINO', 'BIANCCA STEFANY', '2011-03-07', 'F', '62931884', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 3, 19, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418617606', '2026', '6', '26', '2026-06-26', 'F05', 11, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834043318', 1, 'DNI', '62520204', 'AGREDA', 'MARTINEZ', 'ELVIS ALESSANDRO', '2010-12-13', 'M', '62520204', '63466', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 13, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418617606', '2026', '6', '26', '2026-06-26', 'F05', 11, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834043318', 1, 'DNI', '62520204', 'AGREDA', 'MARTINEZ', 'ELVIS ALESSANDRO', '2010-12-13', 'M', '62520204', '63466', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 13, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418617788', '2026', '6', '26', '2026-06-26', 'F05', 11, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835073318', 1, 'DNI', '62520174', 'ANCHIRAICO', 'GUERRA', 'ERICK MISHAEL', '2010-11-23', 'M', '62520174', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 3, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418617788', '2026', '6', '26', '2026-06-26', 'F05', 11, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835073318', 1, 'DNI', '62520174', 'ANCHIRAICO', 'GUERRA', 'ERICK MISHAEL', '2010-11-23', 'M', '62520174', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418617788', '2026', '6', '26', '2026-06-26', 'F05', 11, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835073318', 1, 'DNI', '62520174', 'ANCHIRAICO', 'GUERRA', 'ERICK MISHAEL', '2010-11-23', 'M', '62520174', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618021', '2026', '6', '26', '2026-06-26', 'F05', 11, 3, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1693948318', 1, 'DNI', '62657880', 'ANDRES', 'VARGAS', 'BRATT GUSTAVO', '2010-09-30', 'M', '62657880', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 8, 27, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418618021', '2026', '6', '26', '2026-06-26', 'F05', 11, 3, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1693948318', 1, 'DNI', '62657880', 'ANDRES', 'VARGAS', 'BRATT GUSTAVO', '2010-09-30', 'M', '62657880', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 8, 27, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618021', '2026', '6', '26', '2026-06-26', 'F05', 11, 3, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1693948318', 1, 'DNI', '62657880', 'ANDRES', 'VARGAS', 'BRATT GUSTAVO', '2010-09-30', 'M', '62657880', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 8, 27, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618325', '2026', '6', '26', '2026-06-26', 'F05', 11, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409663318', 1, 'DNI', '61695033', 'APAZA', 'GONZALES', 'GREGORI GIOVANI', '2010-05-28', 'M', '61695033', '62597', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 0, 29, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618325', '2026', '6', '26', '2026-06-26', 'F05', 11, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409663318', 1, 'DNI', '61695033', 'APAZA', 'GONZALES', 'GREGORI GIOVANI', '2010-05-28', 'M', '61695033', '62597', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 0, 29, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618325', '2026', '6', '26', '2026-06-26', 'F05', 11, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8409663318', 1, 'DNI', '61695033', 'APAZA', 'GONZALES', 'GREGORI GIOVANI', '2010-05-28', 'M', '61695033', '62597', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 0, 29, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618478', '2026', '6', '26', '2026-06-26', 'F05', 11, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1291469318', 1, 'DNI', '62520172', 'BARRIENTOS', 'MANDUJANO', 'DEYVIS RUBIÑO', '2010-11-27', 'M', '62520172', '63402', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 30, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618478', '2026', '6', '26', '2026-06-26', 'F05', 11, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1291469318', 1, 'DNI', '62520172', 'BARRIENTOS', 'MANDUJANO', 'DEYVIS RUBIÑO', '2010-11-27', 'M', '62520172', '63402', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 30, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618478', '2026', '6', '26', '2026-06-26', 'F05', 11, 5, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1291469318', 1, 'DNI', '62520172', 'BARRIENTOS', 'MANDUJANO', 'DEYVIS RUBIÑO', '2010-11-27', 'M', '62520172', '63402', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 30, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618652', '2026', '6', '26', '2026-06-26', 'F05', 11, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835084318', 1, 'DNI', '62446399', 'CASTILLO', 'LOPEZ', 'ANGELINA JULISSA', '2010-07-24', 'F', '62446399', '63296', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 2, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618652', '2026', '6', '26', '2026-06-26', 'F05', 11, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835084318', 1, 'DNI', '62446399', 'CASTILLO', 'LOPEZ', 'ANGELINA JULISSA', '2010-07-24', 'F', '62446399', '63296', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 2, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618652', '2026', '6', '26', '2026-06-26', 'F05', 11, 6, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1835084318', 1, 'DNI', '62446399', 'CASTILLO', 'LOPEZ', 'ANGELINA JULISSA', '2010-07-24', 'F', '62446399', '63296', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 2, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618813', '2026', '6', '26', '2026-06-26', 'F05', 11, 7, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7493943318', 1, 'DNI', '62657813', 'CORDOVA', 'ASTETE', 'YONIER GONZALO', '2010-09-03', 'M', '62657813', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 9, 23, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618813', '2026', '6', '26', '2026-06-26', 'F05', 11, 7, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7493943318', 1, 'DNI', '62657813', 'CORDOVA', 'ASTETE', 'YONIER GONZALO', '2010-09-03', 'M', '62657813', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 9, 23, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618813', '2026', '6', '26', '2026-06-26', 'F05', 11, 7, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7493943318', 1, 'DNI', '62657813', 'CORDOVA', 'ASTETE', 'YONIER GONZALO', '2010-09-03', 'M', '62657813', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 9, 23, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618959', '2026', '6', '26', '2026-06-26', 'F05', 11, 8, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '29580405318', 1, 'DNI', '74343122', 'DE LA CRUZ', 'NAVARRO', 'ORIANA MAITE', '2010-07-23', 'F', '74343122', '75958', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 3, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618959', '2026', '6', '26', '2026-06-26', 'F05', 11, 8, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '29580405318', 1, 'DNI', '74343122', 'DE LA CRUZ', 'NAVARRO', 'ORIANA MAITE', '2010-07-23', 'F', '74343122', '75958', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418618959', '2026', '6', '26', '2026-06-26', 'F05', 11, 8, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '29580405318', 1, 'DNI', '74343122', 'DE LA CRUZ', 'NAVARRO', 'ORIANA MAITE', '2010-07-23', 'F', '74343122', '75958', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 11, 3, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418982034', '2026', '6', '7', '2026-06-07', 'F01', 2, 8, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63938, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 'PAMPA TIGRE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5364178563938', 1, 'DNI', '93870366', 'GONZALES', 'AGUIRRE', 'ANTOANELLA XIANNA', '2024-06-14', 'F', '93870366', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '72587363938', 1, 'DNI', '41168981', 'PERALTA', 'RAMIREZ', 'ROGER INDALINO', '1981-05-24', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 1, 'A', 1, 11, 24, '01 a 04 años', NULL, 'M', 'CP', '99199.17', 'SUPLEMENTACIÓN CON HIERRO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1418982034', '2026', '6', '7', '2026-06-07', 'F01', 2, 8, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63938, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 'PAMPA TIGRE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5364178563938', 1, 'DNI', '93870366', 'GONZALES', 'AGUIRRE', 'ANTOANELLA XIANNA', '2024-06-14', 'F', '93870366', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '72587363938', 1, 'DNI', '41168981', 'PERALTA', 'RAMIREZ', 'ROGER INDALINO', '1981-05-24', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 1, 'A', 1, 11, 24, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418982034', '2026', '6', '7', '2026-06-07', 'F01', 2, 8, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63938, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 'PAMPA TIGRE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5364178563938', 1, 'DNI', '93870366', 'GONZALES', 'AGUIRRE', 'ANTOANELLA XIANNA', '2024-06-14', 'F', '93870366', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '72587363938', 1, 'DNI', '41168981', 'PERALTA', 'RAMIREZ', 'ROGER INDALINO', '1981-05-24', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 1, 'A', 1, 11, 24, '01 a 04 años', NULL, 'M', 'CP', '99403.01', 'CONSEJERÍA NUTRICIONAL: ALIMENTACIÓN SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418982142', '2026', '6', '7', '2026-06-07', 'F01', 2, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63938, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 'PAMPA TIGRE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5312305563938', 1, 'DNI', '93709802', 'OSORIO', 'MORALES', 'ESTRELLA CATALEYA', '2024-02-04', 'F', '93709802', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '72587363938', 1, 'DNI', '41168981', 'PERALTA', 'RAMIREZ', 'ROGER INDALINO', '1981-05-24', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 2, 'A', 2, 4, 3, '01 a 04 años', NULL, 'M', 'CP', '99199.17', 'SUPLEMENTACIÓN CON HIERRO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418982142', '2026', '6', '7', '2026-06-07', 'F01', 2, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63938, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 'PAMPA TIGRE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5312305563938', 1, 'DNI', '93709802', 'OSORIO', 'MORALES', 'ESTRELLA CATALEYA', '2024-02-04', 'F', '93709802', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '72587363938', 1, 'DNI', '41168981', 'PERALTA', 'RAMIREZ', 'ROGER INDALINO', '1981-05-24', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 2, 'A', 2, 4, 3, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418982142', '2026', '6', '7', '2026-06-07', 'F01', 2, 9, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 63938, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00035584', 'PAMPA TIGRE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '5312305563938', 1, 'DNI', '93709802', 'OSORIO', 'MORALES', 'ESTRELLA CATALEYA', '2024-02-04', 'F', '93709802', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '72587363938', 1, 'DNI', '41168981', 'PERALTA', 'RAMIREZ', 'ROGER INDALINO', '1981-05-24', 1, 'NOMBRADO', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 2, 'A', 2, 4, 3, '01 a 04 años', NULL, 'M', 'CP', '99403.01', 'CONSEJERÍA NUTRICIONAL: ALIMENTACIÓN SALUDABLE', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619272', '2026', '6', '26', '2026-06-26', 'F05', 11, 10, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8336377318', 1, 'DNI', '74196141', 'GARCIA', 'URETA', 'JHISLAY KEYMI', '2010-04-08', 'F', '74196141', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 2, 18, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619272', '2026', '6', '26', '2026-06-26', 'F05', 11, 10, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8336377318', 1, 'DNI', '74196141', 'GARCIA', 'URETA', 'JHISLAY KEYMI', '2010-04-08', 'F', '74196141', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 2, 18, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619272', '2026', '6', '26', '2026-06-26', 'F05', 11, 10, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8336377318', 1, 'DNI', '74196141', 'GARCIA', 'URETA', 'JHISLAY KEYMI', '2010-04-08', 'F', '74196141', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 2, 18, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619587', '2026', '6', '26', '2026-06-26', 'F05', 11, 12, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '39663039318', 1, 'DNI', '75037895', 'HUAMAN', 'CATAMAYO', 'SARAI ESTHER', '2010-10-29', 'F', '75037895', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 28, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619587', '2026', '6', '26', '2026-06-26', 'F05', 11, 12, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '39663039318', 1, 'DNI', '75037895', 'HUAMAN', 'CATAMAYO', 'SARAI ESTHER', '2010-10-29', 'F', '75037895', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 28, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418619587', '2026', '6', '26', '2026-06-26', 'F05', 11, 12, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '39663039318', 1, 'DNI', '75037895', 'HUAMAN', 'CATAMAYO', 'SARAI ESTHER', '2010-10-29', 'F', '75037895', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 7, 28, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418616506', '2026', '6', '25', '2026-06-25', 'F05', 10, 13, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1462449318', 1, 'DNI', '61694991', 'ZAVALA', 'PALACIOS', 'SARAI MAYERLY', '2009-10-13', 'F', '61694991', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 8, 12, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418616506', '2026', '6', '25', '2026-06-25', 'F05', 10, 13, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1462449318', 1, 'DNI', '61694991', 'ZAVALA', 'PALACIOS', 'SARAI MAYERLY', '2009-10-13', 'F', '61694991', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 8, 12, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418616506', '2026', '6', '25', '2026-06-25', 'F05', 10, 13, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1462449318', 1, 'DNI', '61694991', 'ZAVALA', 'PALACIOS', 'SARAI MAYERLY', '2009-10-13', 'F', '61694991', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 16, 'A', 16, 8, 12, '12 a 17 años', NULL, 'M', 'CP', '99207.04', 'PSICOEDUCACIÓN AL PACIENTE', 'D', 'TA', 3, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418617606', '2026', '6', '26', '2026-06-26', 'F05', 11, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1834043318', 1, 'DNI', '62520204', 'AGREDA', 'MARTINEZ', 'ELVIS ALESSANDRO', '2010-12-13', 'M', '62520204', '63466', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '37295675318', 1, 'DNI', '70079499', 'BARTOLO', 'ALVAREZ', 'KEVIN JHONATAN', '1995-06-26', 8, 'OTROS', '28', 'PSICOLO;', '08', 'COLEGIO DE PSICOLOGOS DEL PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 15, 'A', 15, 6, 13, '12 a 17 años', NULL, 'M', 'CX', 'F101', 'TRASTORNOS MENTALES Y DEL COMPORTAMIENTO DEBIDOS AL USO DE ALCOHOL - USO NOCIVO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418510566', '2026', '6', '23', '2026-06-23', 'F63', 9, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1829785318', 1, 'DNI', '42022703', 'HUARANCCA', 'DE LA CRUZ', 'EDITH ROSSMERI', '1983-10-03', 'F', '42022703', '55429', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '47177119318', 1, 'DNI', '73368851', 'CARLOS', 'PEREZ', 'KARINA SUSAN', '1993-04-10', 3, 'SERUM', '56', 'SERUMISTA TECNOLO; MEDICO', '09', 'COLEGIO TECNOLOGO MEDICO DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', 'C', 'C', 42, 'A', 42, 8, 20, '30 a 59 años', NULL, 'T', 'CP', 'C0009', 'SESIÓN EDUCATIVA', 'D', 'ALI', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418510566', '2026', '6', '23', '2026-06-23', 'F63', 9, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1829785318', 1, 'DNI', '42022703', 'HUARANCCA', 'DE LA CRUZ', 'EDITH ROSSMERI', '1983-10-03', 'F', '42022703', '55429', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '47177119318', 1, 'DNI', '73368851', 'CARLOS', 'PEREZ', 'KARINA SUSAN', '1993-04-10', 3, 'SERUM', '56', 'SERUMISTA TECNOLO; MEDICO', '09', 'COLEGIO TECNOLOGO MEDICO DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', 'C', 'C', 42, 'A', 42, 8, 20, '30 a 59 años', NULL, 'T', 'CP', 'C0009', 'SESIÓN EDUCATIVA', 'D', 'SBU', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL),
('1418510566', '2026', '6', '23', '2026-06-23', 'F63', 9, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1829785318', 1, 'DNI', '42022703', 'HUARANCCA', 'DE LA CRUZ', 'EDITH ROSSMERI', '1983-10-03', 'F', '42022703', '55429', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '47177119318', 1, 'DNI', '73368851', 'CARLOS', 'PEREZ', 'KARINA SUSAN', '1993-04-10', 3, 'SERUM', '56', 'SERUMISTA TECNOLO; MEDICO', '09', 'COLEGIO TECNOLOGO MEDICO DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', 'C', 'C', 42, 'A', 42, 8, 20, '30 a 59 años', NULL, 'T', 'CP', 'C0010', 'SESIÓN DEMOSTRATIVA', 'D', '18', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`) VALUES
('1404802431', '2026', '6', '1', '2026-06-01', 'F10', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '13169237318', 1, 'DNI', '07667012', 'QUISPE', 'PEREZ', 'ALCIBIADES FRANCISCO', '1960-07-13', 'M', '07667012', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '1067758318', 1, 'DNI', '42851748', 'LIMAYLLA', 'DIANDERAS', 'DANIT VANESSA', '1985-03-01', 1, 'NOMBRADO', '36', 'TECNICO DE LABORATORIO', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'R', 65, 'A', 65, 10, 19, '60 años a mas', NULL, 'M', 'CP', '99402.08', 'CONSEJERÍA PREVENTIVA EN FACTORES DE RIES PARA EL CÁNCER', 'D', '1', 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404541354', '2026', '6', '1', '2026-06-01', 'E06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 326, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000326', 'SANTA ROSA DE RIO AMARILLO', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52825060326', 1, 'DNI', '93620641', 'PARIONA', 'HUANUQUEÑO', 'BENJAMIN AARON', '2023-11-18', 'M', '93620641', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '741984326', 1, 'DNI', '44043762', 'CERRON', 'ROJAS', 'EVELIN NADIA', '1986-11-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 14, '01 a 04 años', NULL, 'M', 'CX', 'J039', 'AMIGDALITIS AGUDA NO ESPECIFICADA', 'R', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404541354', '2026', '6', '1', '2026-06-01', 'E06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 326, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000326', 'SANTA ROSA DE RIO AMARILLO', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52825060326', 1, 'DNI', '93620641', 'PARIONA', 'HUANUQUEÑO', 'BENJAMIN AARON', '2023-11-18', 'M', '93620641', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '741984326', 1, 'DNI', '44043762', 'CERRON', 'ROJAS', 'EVELIN NADIA', '1986-11-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 14, '01 a 04 años', NULL, 'M', 'CP', '99401.08', 'CONSEJERÍA DE IDENTIFICACIÓN DE SIGNOS DE ALARMA', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404541354', '2026', '6', '1', '2026-06-01', 'E06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 326, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000326', 'SANTA ROSA DE RIO AMARILLO', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52825060326', 1, 'DNI', '93620641', 'PARIONA', 'HUANUQUEÑO', 'BENJAMIN AARON', '2023-11-18', 'M', '93620641', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '741984326', 1, 'DNI', '44043762', 'CERRON', 'ROJAS', 'EVELIN NADIA', '1986-11-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 14, '01 a 04 años', NULL, 'M', 'CP', '99401.12', 'CONSEJERÍA PARA LA PREVENCIÓN DE ENFERMEDADES PREVALENTES (EDA IRA ENTRE OTRAS)', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404541354', '2026', '6', '1', '2026-06-01', 'E06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 326, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000326', 'SANTA ROSA DE RIO AMARILLO', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52825060326', 1, 'DNI', '93620641', 'PARIONA', 'HUANUQUEÑO', 'BENJAMIN AARON', '2023-11-18', 'M', '93620641', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '741984326', 1, 'DNI', '44043762', 'CERRON', 'ROJAS', 'EVELIN NADIA', '1986-11-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 14, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', '1', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404361182', '2026', '6', '1', '2026-06-01', 'F06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53933003320', 1, 'DNI', '93942319', 'CARDENAS', 'TARAZONA', 'XIANI DARA', '2024-08-16', 'F', '93942319', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '21883143320', 1, 'DNI', '43991710', 'QUISPE', 'LOPEZ', 'BETTY ALICIA', '1986-10-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 9, 16, '01 a 04 años', NULL, 'M', 'CX', 'J029', 'FARINGITIS AGUDA NO ESPECIFICADA', 'R', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404361182', '2026', '6', '1', '2026-06-01', 'F06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53933003320', 1, 'DNI', '93942319', 'CARDENAS', 'TARAZONA', 'XIANI DARA', '2024-08-16', 'F', '93942319', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '21883143320', 1, 'DNI', '43991710', 'QUISPE', 'LOPEZ', 'BETTY ALICIA', '1986-10-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 9, 16, '01 a 04 años', NULL, 'M', 'CP', '99401.08', 'CONSEJERÍA DE IDENTIFICACIÓN DE SIGNOS DE ALARMA', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404361182', '2026', '6', '1', '2026-06-01', 'F06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53933003320', 1, 'DNI', '93942319', 'CARDENAS', 'TARAZONA', 'XIANI DARA', '2024-08-16', 'F', '93942319', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '21883143320', 1, 'DNI', '43991710', 'QUISPE', 'LOPEZ', 'BETTY ALICIA', '1986-10-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 9, 16, '01 a 04 años', NULL, 'M', 'CP', '99401.12', 'CONSEJERÍA PARA LA PREVENCIÓN DE ENFERMEDADES PREVALENTES (EDA IRA ENTRE OTRAS)', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1404361182', '2026', '6', '1', '2026-06-01', 'F06', 1, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '53933003320', 1, 'DNI', '93942319', 'CARDENAS', 'TARAZONA', 'XIANI DARA', '2024-08-16', 'F', '93942319', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '21883143320', 1, 'DNI', '43991710', 'QUISPE', 'LOPEZ', 'BETTY ALICIA', '1986-10-06', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 1, 'A', 1, 9, 16, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', '3', 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-03 00:00:00', NULL),
('1421647593', '2026', '6', '11', '2026-06-11', 'F04', 17, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58619878320', 1, 'DNI', '94366693', 'MAHUANCA', 'DANIEL', 'MIRELY YAMILETH', '2025-09-01', 'F', '94366693', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 9, 'M', 0, 9, 10, '01 a 11 meses', NULL, 'M', 'CP', '99403.01', 'CONSEJERÍA NUTRICIONAL: ALIMENTACIÓN SALUDABLE', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647593', '2026', '6', '11', '2026-06-11', 'F04', 17, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58619878320', 1, 'DNI', '94366693', 'MAHUANCA', 'DANIEL', 'MIRELY YAMILETH', '2025-09-01', 'F', '94366693', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 9, 'M', 0, 9, 10, '01 a 11 meses', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647593', '2026', '6', '11', '2026-06-11', 'F04', 17, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58619878320', 1, 'DNI', '94366693', 'MAHUANCA', 'DANIEL', 'MIRELY YAMILETH', '2025-09-01', 'F', '94366693', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 9, 'M', 0, 9, 10, '01 a 11 meses', NULL, 'M', 'CP', '99401', 'CONSEJERÍA EN MEDICINA PREVENTIVA Y/O PROVISIÓN DE INTERVENCIONES DE REDUCCIÓN DE FACTORES DE RIES PROPORCIONADOS A INDIVIDUO DURANTE APROXIMADAMENTE 15 MINUTOS (P. EJ. CONSEJERÍA INTEGRAL)', 'D', NULL, 4, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647593', '2026', '6', '11', '2026-06-11', 'F04', 17, 4, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '58619878320', 1, 'DNI', '94366693', 'MAHUANCA', 'DANIEL', 'MIRELY YAMILETH', '2025-09-01', 'F', '94366693', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 9, 'M', 0, 9, 10, '01 a 11 meses', NULL, 'M', 'CP', '99199.17', 'SUPLEMENTACIÓN CON HIERRO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647387', '2026', '6', '23', '2026-06-23', 'F04', 17, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52988631320', 1, 'DNI', '93650673', 'MARTINEZ', 'ISLA', 'GAEL AIMAR', '2023-12-14', 'M', '93650673', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 9, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647387', '2026', '6', '23', '2026-06-23', 'F04', 17, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52988631320', 1, 'DNI', '93650673', 'MARTINEZ', 'ISLA', 'GAEL AIMAR', '2023-12-14', 'M', '93650673', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 9, '01 a 04 años', NULL, 'M', 'CP', '99403.01', 'CONSEJERÍA NUTRICIONAL: ALIMENTACIÓN SALUDABLE', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647387', '2026', '6', '23', '2026-06-23', 'F04', 17, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '52988631320', 1, 'DNI', '93650673', 'MARTINEZ', 'ISLA', 'GAEL AIMAR', '2023-12-14', 'M', '93650673', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 2, 'A', 2, 6, 9, '01 a 04 años', NULL, 'M', 'CP', '99199.17', 'SUPLEMENTACIÓN CON HIERRO', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647289', '2026', '6', '20', '2026-06-20', 'F04', 17, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '51923736320', 1, 'DNI', '92931709', 'SANTOS', 'DANIEL', 'SMITH JANS', '2022-06-12', 'M', '92931709', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 4, 'A', 4, 0, 8, '01 a 04 años', NULL, 'M', 'CP', 'C0011', 'VISITA FAMILIAR INTEGRAL', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL),
('1421647289', '2026', '6', '20', '2026-06-20', 'F04', 17, 1, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '51923736320', 1, 'DNI', '92931709', 'SANTOS', 'DANIEL', 'SMITH JANS', '2022-06-12', 'M', '92931709', NULL, '58', 'MESTIZO', '1', 'USUARIO', 'PER', 'PERU', '2300760320', 1, 'DNI', '41874371', 'ROJAS', 'EVANGELISTA', 'BENEDICTA', '1983-01-14', 2, 'CONTRATADO', '35', 'TECNICAS DE ENFERMERIA', '00', 'PERSONAL DE SALUD SIN COLEGIATURA', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'R', 4, 'A', 4, 0, 8, '01 a 04 años', NULL, 'M', 'CP', '99403.01', 'CONSEJERÍA NUTRICIONAL: ALIMENTACIÓN SALUDABLE', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL);

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
(1, 'admin', '$2a$12$oyHlo6Q4WLAz05diONMFx.D93QR915rs.iVOZx7K1wUpgiTI7CtB.', 'Administrador', 'admin', 1, '2026-06-14 12:35:48', '2026-08-02 16:33:02'),
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
-- Indices de la tabla `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`
--
ALTER TABLE `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO`
  ADD KEY `idx_anio_mes_est_fechaatc` (`Anio`,`Mes`,`Codigo_Unico`,`Fecha_Atencion`),
  ADD KEY `idx_cita_item_fechaatc` (`Id_Cita`,`Codigo_Item`,`Fecha_Atencion`),
  ADD KEY `idx_codigo_unico` (`Codigo_Unico`),
  ADD KEY `idx_doc_paciente` (`Numero_Documento_Paciente`),
  ADD KEY `idx_doc_personal` (`Numero_Documento_Personal`),
  ADD KEY `idx_doc_registrador` (`Numero_Documento_Registrador`),
  ADD KEY `idx_codigo_item` (`Codigo_Item`),
  ADD KEY `idx_ups_ftipo` (`Id_Ups`,`Fg_Tipo`),
  ADD KEY `idx_grupo_edad` (`Grupo_Edad`),
  ADD KEY `idx_genero` (`Id_Genero`),
  ADD KEY `idx_tipo_diagnostico` (`Tipo_Diagnostico`),
  ADD KEY `idx_valor_lab` (`Valor_Lab`),
  ADD KEY `idx_lote_pag_reg` (`Lote`,`Num_Pag`,`Num_Reg`),
  ADD KEY `idx_departamento` (`Departamento_Establecimiento`),
  ADD KEY `idx_otra_condicion` (`Descripcion_Otra_Condicion`),
  ADD KEY `idx_nombre_establecimiento` (`Nombre_Establecimiento`),
  ADD KEY `idx_anio_mesint` (`Anio`,`Mes_Int`),
  ADD KEY `idx_paciente` (`Id_Paciente`),
  ADD KEY `idx_personal` (`Id_Personal`),
  ADD KEY `idx_establecimiento` (`Id_Establecimiento`);

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
  MODIFY `id_linea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT de la tabla `ESNI_REGLA`
--
ALTER TABLE `ESNI_REGLA`
  MODIFY `id_regla` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;

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
