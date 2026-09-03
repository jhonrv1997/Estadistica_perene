--
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
(9, '01A_1A11M', '01 año', 'A', 1, 1, 'Ninos de 1 anio', 1),
(10, '15M', '15 meses', 'A', 1, 1, 'Ninos de 15 meses', 1),
(11, '18M', '18 meses', 'A', 1, 1, 'Ninos de 18 meses', 1),
(12, '02_04A', '02 a 04 años', 'A', 2, 4, 'Ninos de 2 a 4 anos', 1),
(13, '02A', '02 años', 'A', 2, 2, 'Ninos de 2 anos', 1),
(14, '03A', '03 años', 'A', 3, 3, 'Ninos de 3 anos', 1),
(15, '04A', '04 años', 'A', 4, 4, 'Ninos de 4 anos', 1),
(16, '05_59A', '05 a 59 años', 'A', 5, 59, 'Poblacion de 5 a 59 anos', 1),
(17, '10_49A_M', '10 a 49 años (Mujeres)', 'A', 10, 49, 'Mujeres en edad fertil 10 a 49 anos', 1),
(18, '10A_MAS_V', '10 años a mas (Varones)', 'A', 10, 200, 'Varones en riesgo 10 anos a mas', 1),
(19, 'GEST', 'Gestantes', 'R', NULL, NULL, 'Mujeres gestantes', 1),
(20, 'RIESGO', 'Poblacion en riesgo', 'R', NULL, NULL, 'Poblacion con factores de riesgo', 1),
(21, 'COMORB', 'Con comorbilidad', 'R', NULL, NULL, 'Poblacion con comorbilidad', 1),
(22, 'SIN_COMORB', 'Sin comorbilidad', 'R', NULL, NULL, 'Poblacion sin comorbilidad', 1),
(23, 'CONTACTO_TB', 'Contacto TB', 'R', NULL, NULL, 'Contactos de pacientes TB', 1),
(24, 'CONTACTO_VAR', 'Contacto indice Varicela', 'R', NULL, NULL, 'Contacto indice de Varicela', 1),
(25, 'VIAJA_END', 'Viaja a zonas endemicas', 'R', NULL, NULL, 'Persona que viaja a zonas endemicas', 1),
(26, 'NO_VAC', 'Poblacion no vacunada', 'R', NULL, NULL, 'Poblacion no vacunada previamente', 1),
(27, '60A_MAS', '60 años a mas', 'A', 60, 200, 'Adultos mayores de 60 anos', 1),
(28, '05_07A', '05 y 07 Años', 'A', 5, 7, NULL, 1),
(29, '05_09A', '05 y 09 años', 'A', 5, 9, NULL, 1),
(30, '10_11A', '10 y 11 años', 'A', 10, 11, NULL, 1),
(31, '12_17A', '12 y 17 años', 'A', 12, 17, NULL, 1),
(32, '18_29A', '18 y 29 años', 'A', 18, 29, NULL, 1),
(33, '30_49A', '30 y 49 años', 'A', 30, 49, NULL, 1),
(34, '50_59A', '50 y 59 años', 'A', 50, 59, NULL, 1),
(35, '30_59A', '30 y 59 años', 'A', 30, 59, NULL, 1),
(36, '05_11A', '05 y 11 años', 'A', 5, 11, NULL, 1),
(37, '09A', '09 años', 'A', 9, 9, NULL, 1),
(38, '10A', '10 años', 'A', 10, 10, NULL, 1),
(39, '11A', '11 años', 'A', 11, 11, NULL, 1),
(40, '12A', '12 años', 'A', 12, 12, NULL, 1),
(41, '13A', '13 años', 'A', 13, 13, NULL, 1),
(42, '14A_MAS', '14 años a mas', 'A', 14, 120, NULL, 1),
(43, '0_11A', '0 a 11 años', 'A', 0, 11, NULL, 1),
(44, '05A', '05 años', 'A', 5, 5, NULL, 1);
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
(29, 2, 3, 'VARICELA 1RA', 14, 1, 9, 'A', 1),
(32, 2, 6, '1A 11M 29D - DOSIS UNICA - INFLUENZA', 11, 5, 9, 'A', 1),
(33, 2, 7, 'NEUMOCOCO 1RA', 10, 1, 8, 'A', 1),
(34, 2, 8, 'NEUMOCOCO 2DA', 10, 2, 8, 'A', 1),
(35, 2, 9, '15 MESES - ANTIAMARILICA - DOSIS UNICA', 15, 5, 10, 'A', 1),
(36, 2, 10, '15 MESES - HEPATITIS A - DOSIS UNICA', 16, 5, 10, 'A', 1),
(37, 2, 11, '18 MESES - SPR - 2DA DOSIS', 12, 2, 11, 'A', 1),
(38, 2, 12, '18 MESES - REF. DPT - 1RA DOSIS', 17, 6, 11, 'A', 1),
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
(54, 3, 15, 'REFUERZO PENTAVALENTE - 1RA DOSIS', 5, 6, 13, 'A', 1),
(55, 3, 16, 'REFUERZO ANTIPOLIO IPV- 1RA DOSIS', 3, 6, 13, 'A', 1),
(56, 4, 1, 'dT 1ra - Mujeres 5 a 9 anos', 18, 1, 29, 'F', 1),
(57, 4, 2, 'dT 2da - Mujeres 5 a 9 anos', 18, 2, 29, 'F', 1),
(58, 4, 3, 'dT 3ra - Mujeres 5 a 9 anos', 18, 3, 29, 'F', 1),
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
(91, 17, 1, 'SPR 1RA Dosis', 12, 1, 28, 'A', 0),
(92, 17, 2, ' SPR 2DA Dosis', 12, 2, 28, 'A', 0),
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
(106, 4, 15, 'dT 3ra - Mujeres 30 a 49 anos', 18, 3, 33, 'F', 1),
(107, 6, 1, 'dT 1ra -05_09A', 18, 1, 29, 'M', 1),
(108, 6, 2, 'dT 2da -05_09A', 18, 2, 29, 'M', 1),
(109, 6, 3, 'dT 3ra -05_09A', 18, 3, 29, 'M', 1),
(110, 6, 4, 'dT 1ra -10_11A', 18, 1, 30, 'M', 1),
(111, 6, 5, 'dT 2da -10_11A', 18, 2, 30, 'M', 1),
(112, 6, 6, 'dT 3ra -10_11A', 18, 3, 30, 'M', 1),
(113, 6, 7, 'dT 1ra -12_17A', 18, 1, 31, 'M', 1),
(114, 6, 8, 'dT 2da -12_17A', 18, 2, 31, 'M', 1),
(115, 6, 9, 'dT 3ra -12_17A', 18, 3, 31, 'M', 1),
(116, 6, 10, 'dT 1ra -18_29A', 18, 1, 32, 'M', 1),
(117, 6, 11, 'dT 2da -18_29A', 18, 2, 32, 'M', 1),
(118, 6, 12, 'dT 3ra -18_29A', 18, 3, 32, 'M', 1),
(119, 6, 13, 'dT 1ra -30_59A', 18, 1, 35, 'M', 1),
(120, 6, 14, 'dT 2da -30_59A', 18, 2, 35, 'M', 1),
(121, 6, 15, 'dT 3ra -30_59A', 18, 3, 35, 'M', 1),
(122, 6, 16, 'dT 1ra -60A_MAS', 18, 1, 27, 'M', 1),
(123, 6, 17, 'dT 2da -60A_MAS', 18, 2, 27, 'M', 1),
(124, 6, 18, 'dT 3ra -60A_MAS', 18, 3, 27, 'M', 1),
(125, 7, 1, 'CON COMORBILIDAD 05_11A', 11, 5, 36, 'A', 1),
(126, 7, 2, 'CON COMORBILIDAD 12_17A', 11, 5, 31, 'A', 1),
(127, 7, 3, 'CON COMORBILIDAD 18_29A', 11, 5, 32, 'A', 1),
(128, 7, 4, 'CON COMORBILIDAD 30_49A', 11, 5, 33, 'A', 1),
(129, 7, 5, 'CON COMORBILIDAD 50_59A', 11, 5, 34, 'A', 1),
(130, 7, 6, 'SIN COMORBILIDAD 5_11A', 11, 5, 36, 'A', 1),
(131, 7, 7, 'SIN COMORBILIDAD 12_17A', 11, 5, 31, 'A', 1),
(132, 7, 8, 'SIN COMORBILIDAD 18_29A', 11, 5, 32, 'A', 1),
(133, 7, 9, 'SIN COMORBILIDAD 30_49A', 11, 5, 33, 'A', 1),
(134, 7, 10, 'SIN COMORBILIDAD 50_59A', 11, 5, 34, 'A', 1),
(135, 7, 11, 'MAYORES DE 60A', 11, 5, 27, 'A', 1),
(136, 7, 12, 'GESTANTES', 11, 5, NULL, 'A', 1),
(137, 7, 13, 'PUERPERAS', 11, 5, NULL, 'A', 1),
(138, 7, 14, 'PERSONAL DE SALUD', 11, 5, NULL, 'A', 1),
(139, 7, 15, 'ESTUDIANTES', 11, 5, NULL, 'A', 1),
(140, 7, 16, 'COMUNIDADES NATIVAS', 11, 5, NULL, 'A', 1),
(141, 9, 1, '05 a 11 años', 2, 1, 36, 'A', 1),
(142, 9, 2, '05 a 11 años', 2, 2, 36, 'A', 1),
(143, 9, 3, '05 a 11 años', 2, 3, 36, 'A', 1),
(144, 9, 4, '12 a 17 años', 2, 1, 31, 'A', 1),
(145, 9, 5, '12 a 17 años', 2, 2, 31, 'A', 1),
(146, 9, 6, '12 a 17 años', 2, 3, 31, 'A', 1),
(147, 9, 7, '18 a 29 años', 2, 1, 32, 'A', 1),
(148, 9, 8, '18 a 29 años', 2, 2, 32, 'A', 1),
(149, 9, 9, '18 a 29 años', 2, 3, 32, 'A', 1),
(150, 9, 10, '30 a 59 años', 2, 1, 35, 'A', 1),
(151, 9, 11, '30 a 59 años', 2, 2, 35, 'A', 1),
(152, 9, 12, '30 a 59 años', 2, 3, 35, 'A', 1),
(153, 9, 13, 'Personal de Salud', 2, 1, NULL, 'A', 1),
(154, 9, 14, 'Personal de Salud', 2, 2, NULL, 'A', 1),
(155, 9, 15, 'Personal de Salud', 2, 3, NULL, 'A', 1),
(156, 9, 16, 'Gestantes', 2, 1, NULL, 'A', 1),
(157, 9, 17, 'Gestantes', 2, 2, NULL, 'A', 1),
(158, 9, 18, 'Gestantes', 2, 3, NULL, 'A', 1),
(159, 10, 1, '05 a 11 años', 15, 5, 36, 'A', 1),
(160, 10, 2, '12 a 17 años', 15, 5, 31, 'A', 1),
(161, 10, 3, '18 a 29 años', 15, 5, 32, 'A', 1),
(162, 10, 4, '30 a 59 años', 15, 5, 35, 'A', 1),
(163, 10, 5, '60 + años', 15, 5, 27, 'A', 1),
(164, 18, 1, '12 a 17 años', 19, 5, 31, 'F', 1),
(165, 18, 2, '18 a 29 años', 19, 5, 32, 'F', 1),
(166, 18, 3, '30 a 49 años', 19, 5, 33, 'F', 1),
(167, 14, 1, '9 años', 20, 5, 37, 'M', 1),
(168, 14, 2, '9 años', 20, 5, 37, 'F', 1),
(169, 14, 3, '10 años', 20, 5, 38, 'M', 1),
(170, 14, 4, '10 años', 20, 5, 38, 'F', 1),
(171, 14, 5, '11 años', 20, 5, 39, 'M', 1),
(172, 14, 6, '11 años', 20, 5, 39, 'F', 1),
(173, 14, 7, '12 años', 20, 5, 40, 'M', 1),
(174, 14, 8, '12 años', 20, 5, 40, 'F', 1),
(175, 14, 9, '13 años', 20, 5, 41, 'M', 1),
(176, 14, 10, '13 años', 20, 5, 41, 'F', 1),
(177, 14, 11, '14 a mas', 20, 5, 42, 'M', 1),
(178, 14, 12, '14 a mas', 20, 5, 42, 'F', 1),
(179, 13, 1, 'CON COMORBILIDAD 5-11a', 10, 5, 36, 'A', 1),
(180, 13, 2, 'CON COMORBILIDAD 12-17a', 10, 5, 31, 'A', 1),
(181, 13, 3, 'CON COMORBILIDAD 18-29a', 10, 5, 32, 'A', 1),
(182, 13, 4, 'CON COMORBILIDAD 30-49a', 10, 5, 33, 'A', 1),
(183, 13, 5, 'CON COMORBILIDAD 50-59a', 10, 5, 34, 'A', 1),
(184, 13, 6, 'SIN COMORBILIDAD 05-11a', 10, 5, 36, 'A', 1),
(185, 13, 7, 'SIN COMORBILIDAD 12-17a', 10, 5, 31, 'A', 1),
(186, 13, 8, 'SIN COMORBILIDAD 18-29a', 10, 5, 32, 'A', 1),
(187, 13, 9, 'SIN COMORBILIDAD 30-49a', 10, 5, 33, 'A', 1),
(188, 13, 10, 'SIN COMORBILIDAD 50-59a', 10, 5, 34, 'A', 1),
(189, 13, 11, '60 A MAS AÑOS', 10, 5, 27, 'A', 1),
(190, 13, 12, 'PERSONAL DE SALUD', 10, 5, NULL, 'A', 1),
(191, 21, 1, '0 A 11 años', 18, 10, 43, 'A', 1),
(192, 21, 2, '12 a 17 años', 18, 10, 31, 'A', 1),
(193, 21, 3, '18 a 29 años', 18, 10, 32, 'A', 1),
(194, 21, 4, '30 a 59 años', 18, 10, 35, 'A', 1),
(195, 21, 5, '60 a mas años', 18, 10, 27, 'A', 1),
(196, 19, 1, '1 AÑO', 16, 5, 9, 'A', 1),
(197, 19, 2, '2 AÑOS', 16, 5, 13, 'A', 1),
(198, 19, 3, '3 AÑOS', 16, 5, 14, 'A', 1),
(199, 19, 4, '4 AÑOS', 16, 5, 15, 'A', 1),
(200, 19, 5, '5 AÑOS', 16, 5, 44, 'A', 1),
(201, 20, 1, '5 a 10 años', 12, 5, 45, 'A', 1),
(202, 20, 2, '11 a 59 años', 12, 5, 46, 'A', 1),
(203, 20, 3, 'Trabajador de Salud', 12, 5, NULL, 'A', 1),
(204, 12, 1, '3 años', 14, 5, 14, 'A', 1),
(205, 12, 2, '4 años', 14, 5, 15, 'A', 1),
(206, 12, 3, '5 años', 14, 5, 44, 'A', 1),
(207, 12, 4, '6 años a mas', 14, 5, 47, 'A', 1),
(208, 12, 5, 'Personal de Salud', 14, 5, NULL, 'A', 1),
(209, 4, 16, 'dT 1ra - Mujeres 50 a 59 anos', 18, 1, 34, 'F', 1),
(210, 4, 17, 'dT 2da - Mujeres 50 a 59 anos', 18, 2, 34, 'F', 1),
(211, 4, 18, 'dT 3ra - Mujeres 50 a 59 anos', 18, 3, 34, 'F', 1),
(212, 4, 19, 'dT 1ra - Mujeres 60 a mas anos', 18, 1, 27, 'F', 1),
(213, 4, 20, 'dT 2da - Mujeres 60 a mas anos', 18, 2, 27, 'F', 1),
(214, 4, 21, 'dT 3ra - Mujeres 60 a mas anos', 18, 3, 27, 'F', 1),
(215, 5, 1, 'dT 1ra -10_11A', 18, 1, 30, 'F', 1),
(216, 5, 2, 'dT 2da -10_11A', 18, 2, 30, 'F', 1),
(217, 5, 3, 'dT 3ra -10_11A', 18, 3, 30, 'F', 1),
(218, 5, 4, 'dT 1ra -12_17A', 18, 1, 31, 'F', 1),
(219, 5, 5, 'dT 2da -12_17A', 18, 2, 31, 'F', 1),
(220, 5, 6, 'dT 3ra -12_17A', 18, 3, 31, 'F', 1),
(221, 5, 7, 'dT 1ra -18_29A', 18, 1, 32, 'F', 1),
(222, 5, 8, 'dT 2da -18_29A', 18, 2, 32, 'F', 1),
(223, 5, 9, 'dT 3ra -18_29A', 18, 3, 32, 'F', 1),
(224, 5, 10, 'dT 1ra -30_49A', 18, 1, 33, 'F', 1),
(225, 5, 11, 'dT 2da -30_49A', 18, 2, 33, 'F', 1),
(226, 5, 12, 'dT 3ra -30_49A', 18, 3, 33, 'F', 1),
(227, 5, 13, 'dT 1ra -50_59A', 18, 1, 34, 'F', 1),
(228, 5, 14, 'dT 2da -50_59A', 18, 2, 34, 'F', 1),
(229, 5, 15, 'dT 3ra -50_59A', 18, 3, 34, 'F', 1),
(230, 7, 17, 'PERSONA CON DISCAPACIDAD', 11, 5, NULL, 'A', 1),
(231, 7, 18, 'OTROS', 11, 5, NULL, 'A', 1),
(232, 2, 20, 'No vacunado IPV', 3, 3, 9, 'A', 1),
(233, 2, 21, 'No vacunado PENTAVALENTE 2da', 5, 2, 9, 'A', 1),
(234, 2, 22, 'No vacunado PENTAVALENTE 3ra', 5, 3, 9, 'A', 1),
(235, 3, 17, 'VACUNACION NO OPORTUNA - NEUMOCOCO D1', 10, 1, 13, 'A', 1),
(236, 3, 18, 'VACUNACION NO OPORTUNA - NEUMOCOCO D2', 10, 2, 13, 'A', 1),
(237, 3, 19, 'VACUNACION NO OPORTUNA - NEUMOCOCO D3', 10, 3, 13, 'A', 1),
(238, 15, 7, 'Neumococo con Comorbilidad', 10, 5, 14, 'A', 1),
(239, 15, 8, 'Pentavalente No vacunado D1', 5, 1, 14, 'A', 1),
(240, 15, 9, 'Pentavalente No vacunado D2', 5, 2, 14, 'A', 1),
(241, 15, 10, 'Pentavalente No vacunado D3', 5, 3, 14, 'A', 1),
(242, 15, 11, 'Refuerzo DPT', 17, 10, 14, 'A', 1),
(243, 15, 12, 'Refuerzo Antipolio IPV', 23, 10, 14, 'A', 1),
(244, 16, 9, 'Neumococo con Comorbilidad', 10, 5, 15, 'A', 1),
(245, 16, 10, 'Pentavalente D1 -No vacunado', 5, 1, 15, 'A', 1),
(246, 16, 11, 'Pentavalente D2 -No vacunado', 5, 2, 15, 'A', 1),
(247, 16, 12, 'Pentavalente D3 -No vacunado', 5, 3, 15, 'A', 1),
(248, 16, 13, 'Refuerzo Pentavalente', 22, 6, 15, 'A', 1),
(249, 17, 4, 'Pentavalente D1 -No vacunado', 5, 1, 28, 'A', 1),
(250, 17, 5, 'Pentavalente D2 -No vacunado', 5, 2, 28, 'A', 1),
(251, 17, 6, 'Pentavalente D3 -No vacunado', 5, 3, 28, 'A', 1),
(252, 9, 19, 'Persona viviendo con VIH', 2, 1, NULL, 'A', 1),
(253, 9, 20, 'Persona viviendo con VIH', 2, 2, NULL, 'A', 1),
(254, 9, 20, 'Persona viviendo con VIH', 2, 3, NULL, 'A', 1),
(255, 22, 1, '12 a 17 años', 24, 5, 31, 'F', 1),
(256, 22, 2, '18 a 29 años', 24, 5, 32, 'F', 1),
(257, 22, 3, '30 a 49 años', 24, 5, 33, 'F', 1),
(258, 22, 4, '50 + años', 24, 5, 34, 'F', 1),
(259, 1, 27, 'Anticuerpo Monoclonal', 25, 5, 3, 'A', 1);
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
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `requiere_valor_lab_cita` varchar(20) DEFAULT NULL COMMENT 'Si esta seteado, requiere que exista OTRA fila con la misma Id_cita cuyo valor_lab sea igual a este valor',
  `excluye_valor_lab_cita` varchar(20) DEFAULT NULL COMMENT 'Si esta seteado, requiere que NO exista ninguna otra fila con la misma Id_cita cuyo valor_lab sea igual a este valor',
  `excluye_etnia` varchar(100) DEFAULT NULL COMMENT 'Lista de Id_Etnia separados por coma a excluir. Ej: 56,57,58,59,60. Si NULL, no filtra por etnia.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Reglas de mapeo ESNI (cod_item + valor_lab + edad -> linea)';

--
-- Volcado de datos para la tabla `ESNI_REGLA`
--
INSERT INTO `ESNI_REGLA` (`id_regla`, `id_linea`, `cod_item`, `valor_lab`, `id_grupo_edad`, `sexo`, `aniomes_min`, `aniomes_max`, `requiere_riesgo`, `excluye_riesgo`, `requiere_comorbilidad`, `excluye_comorbilidad`, `activo`, `requiere_valor_lab_cita`, `excluye_valor_lab_cita`, `excluye_etnia`) VALUES
(1, 1, '90585', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(2, 1, '90585', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(3, 1, '90585', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(4, 1, '90585', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(5, 1, '90585', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(6, 1, 'Z232', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(7, 1, 'Z232', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(8, 1, 'Z232', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(9, 1, 'Z232', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(10, 1, 'Z232', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(11, 2, '90585', '1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(12, 2, '90585', '01', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(13, 2, '90585', 'D1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(14, 2, 'Z232', '1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(15, 2, 'Z232', '01', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(16, 2, 'Z232', 'D1', 2, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(17, 3, '90585', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(18, 3, '90585', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(19, 3, '90585', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(20, 3, 'Z232', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(21, 3, 'Z232', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(22, 3, 'Z232', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(23, 4, '90744', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(24, 4, '90744', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(25, 4, 'Z246', NULL, 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(26, 4, 'Z246', 'DU', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(27, 5, '90744', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(28, 5, '90744', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(29, 5, '90744', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(30, 5, 'Z246', '1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(31, 5, 'Z246', '01', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(32, 5, 'Z246', 'D1', 1, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(33, 6, '90713', '1', 4, 'A', NULL, NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(34, 6, '90713', '01', 4, 'A', NULL, NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(35, 6, '90713', 'D1', 4, 'A', NULL, NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(36, 7, '90713', '2', 4, 'A', NULL, NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(37, 7, '90713', '02', 4, 'A', NULL, NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(38, 7, '90713', 'D2', 4, 'A', NULL, NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(39, 8, '90712', '3', 7, 'A', NULL, '202212', 0, 1, 0, 0, 1, NULL, NULL, NULL),
(40, 8, '90712', '03', 7, 'A', NULL, '202212', 0, 1, 0, 0, 1, NULL, NULL, NULL),
(41, 8, '90712', 'D3', 7, 'A', NULL, '202212', 0, 1, 0, 0, 1, NULL, NULL, NULL),
(42, 8, '90713', '3', 7, 'A', '202301', NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(43, 8, '90713', '03', 7, 'A', '202301', NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(44, 8, '90713', 'D3', 7, 'A', '202301', NULL, 0, 1, 0, 0, 1, NULL, NULL, NULL),
(45, 9, '90723', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(46, 9, '90723', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(47, 9, '90723', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(48, 9, 'Z276', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(49, 9, 'Z276', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(50, 9, 'Z276', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(51, 9, '90722', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(52, 9, '90722', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(53, 9, '90722', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(54, 10, '90723', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(55, 10, '90723', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(56, 10, '90723', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(57, 10, 'Z276', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(58, 10, 'Z276', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(59, 10, 'Z276', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(60, 10, '90722', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(61, 10, '90722', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(62, 10, '90722', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(63, 11, '90723', '3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(64, 11, '90723', '03', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(65, 11, '90723', 'D3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(66, 11, 'Z276', '3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(67, 11, 'Z276', '03', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(68, 11, 'Z276', 'D3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(69, 11, '90722', '3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(70, 11, '90722', '03', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(71, 11, '90722', 'D3', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(72, 18, '90681', '1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(73, 18, '90681', '01', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(74, 18, '90681', 'D1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(75, 18, 'Z268', '1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(76, 18, 'Z268', '01', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(77, 18, 'Z268', 'D1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(78, 19, '90681', '2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(79, 19, '90681', '02', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(80, 19, '90681', 'D2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(81, 19, 'Z268', '2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(82, 19, 'Z268', '02', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(83, 19, 'Z268', 'D2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(90, 20, '90670', '1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(91, 20, '90670', '01', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(92, 20, '90670', 'D1', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(99, 21, '90670', '2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(100, 21, '90670', '02', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(101, 21, '90670', 'D2', 4, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(102, 22, '90657', '1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(103, 22, '90657', '01', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(104, 22, '90657', 'D1', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(111, 23, '90657', '2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(112, 23, '90657', '02', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(113, 23, '90657', 'D2', 3, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(120, 24, '90713', '1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(121, 24, '90713', '01', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(122, 24, '90713', 'D1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(123, 24, 'Z240', '1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(124, 24, 'Z240', '01', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(125, 24, 'Z240', 'D1', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(126, 25, '90713', '2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(127, 25, '90713', '02', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(128, 25, '90713', 'D2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(129, 25, 'Z240', '2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(130, 25, 'Z240', '02', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(131, 25, 'Z240', 'D2', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(132, 26, '90713', '3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(133, 26, '90713', '03', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(134, 26, '90713', 'D3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(135, 26, 'Z240', '3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(136, 26, 'Z240', '03', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(137, 26, 'Z240', 'D3', 5, 'A', NULL, NULL, 1, 0, 0, 0, 1, NULL, NULL, NULL),
(141, 28, '90707', '1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(142, 28, '90707', '01', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(143, 28, '90707', 'D1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(144, 28, '90707', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(145, 37, '90707', '2', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(146, 37, '90707', '02', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(147, 37, '90707', 'D2', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(148, 35, '90717', 'DU', 10, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(149, 43, '90717', 'DU', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(150, 43, '90717', '1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(151, 43, '90717', '01', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(152, 43, '90717', 'D1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(162, 56, '90714', '1', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(163, 56, '90714', '01', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(164, 56, '90714', 'D1', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(165, 57, '90714', '2', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(166, 57, '90714', '02', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(167, 57, '90714', 'D2', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(168, 58, '90714', '3', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(169, 58, '90714', '03', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(170, 58, '90714', 'D3', 29, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(180, 29, '90716', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(181, 29, '90716', '1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(182, 29, '90716', '01', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(183, 29, '90716', 'D1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(193, 36, '90633.01', 'DU', 10, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(194, 36, '90633.01', NULL, 10, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(196, 27, '90670', '3', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(197, 27, '90670', '03', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(198, 27, '90670', 'D3', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(200, 32, '90657', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(201, 75, '90722', 'DA', 11, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(202, 76, '90713', 'DA', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(203, 40, '90657', 'DU', 13, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(204, 41, '90657', 'DU', 13, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(205, 50, '90707', '1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(206, 51, '90707', '2', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(207, 55, '90713', 'DA', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(208, 54, '90722', 'DA', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(209, 77, '90658', 'DU', 14, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(210, 78, '90658', 'DU', 14, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(211, 79, '90717', 'DU', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(212, 80, '90707', '1', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(213, 80, '90707', 'D1', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(214, 81, '90707', '2', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(215, 81, '90707', 'D2', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(216, 82, '90722', 'DA', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(217, 83, '90658', 'DU', 15, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(218, 84, '90658', 'DU', 15, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(219, 85, '90717', 'DU', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(220, 86, '90707', '1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(221, 86, '90707', 'D1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(222, 87, '90707', '2', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(223, 87, '90707', 'D2', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(224, 88, '90713', '1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(225, 88, '90713', 'D1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(226, 89, '90701', 'DA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(227, 89, '90701', 'DDA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(228, 90, '90712', 'DA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(229, 90, '90712', 'DDA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(230, 91, '90707', '1', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(231, 91, '90707', 'D1', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(232, 93, '90701', 'DA', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(233, 93, '90701', 'DDA', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(234, 94, '90714', '1', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(235, 94, '90714', 'D1', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(236, 95, '90714', '2', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(237, 95, '90714', 'D2', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(238, 96, '90714', '3', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(239, 96, '90714', 'D3', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(240, 97, '90714', '1', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(241, 97, '90714', 'D1', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(242, 98, '90714', '2', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(243, 98, '90714', 'D2', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(244, 100, '90714', '3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(246, 100, '90714', 'D3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(247, 101, '90714', '1', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(248, 101, '90714', 'D1', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(249, 102, '90714', '2', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(250, 102, '90714', 'D2', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(251, 103, '90714', '3', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(252, 103, '90714', 'D3', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(253, 104, '90714', '1', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(254, 104, '90714', 'D1', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(255, 105, '90714', 'D2', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(256, 105, '90714', '2', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(257, 106, '90714', '3', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(258, 106, '90714', 'D3', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(259, 76, '90713', 'DDA', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(260, 107, '90714', '1', 29, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(261, 108, '90714', '2', 29, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(262, 109, '90714', '3', 29, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(263, 110, '90714', '1', 30, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(264, 111, '90714', '2', 30, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(265, 112, '90714', '3', 30, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(266, 113, '90714', '1', 31, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(267, 114, '90714', '2', 31, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(268, 115, '90714', '3', 31, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(269, 116, '90714', '1', 32, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(270, 117, '90714', '2', 32, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(271, 118, '90714', '3', 32, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(272, 119, '90714', '1', 35, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(273, 120, '90714', '2', 35, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(274, 121, '90714', '3', 35, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(275, 122, '90714', '1', 27, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(276, 123, '90714', '2', 27, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(277, 124, '90714', '3', 27, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(278, 125, '90658', 'DU', 36, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(279, 126, '90658', 'DU', 31, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(280, 127, '90658', 'DU', 32, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(281, 128, '90658', 'DU', 33, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(282, 129, '90658', 'DU', 34, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(283, 130, '90658', 'DU', 36, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(284, 131, '90658', 'DU', 31, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(285, 132, '90658', 'DU', 32, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(286, 133, '90658', 'DU', 33, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(287, 134, '90658', 'DU', 34, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(288, 135, '90658', 'DU', 27, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(289, 136, '90658', 'DU', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(290, 137, '90658', 'DU', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'P', NULL, NULL),
(291, 138, '90658', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(292, 139, '90658', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'EST', NULL, NULL),
(295, 141, '90744', '1', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(296, 141, '90744', 'D1', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(297, 142, '90744', '2', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(298, 142, '90744', 'D2', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(299, 143, '90744', '3', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(300, 143, '90744', 'D3', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(301, 144, '90744', '1', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(302, 144, '90744', 'D1', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(303, 144, '90746', '1', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(304, 144, '90746', 'D1', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(305, 145, '90746', '2', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(306, 145, '90744', 'D2', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(307, 145, '90744', '2', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(308, 145, '90746', 'D2', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(309, 146, '90744', '3', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(310, 146, '90744', 'D3', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(311, 146, '90746', '3', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(312, 146, '90746', 'D3', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(313, 147, '90746', '1', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(314, 147, '90746', 'D1', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(315, 148, '90746', '2', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL);
INSERT INTO `ESNI_REGLA` (`id_regla`, `id_linea`, `cod_item`, `valor_lab`, `id_grupo_edad`, `sexo`, `aniomes_min`, `aniomes_max`, `requiere_riesgo`, `excluye_riesgo`, `requiere_comorbilidad`, `excluye_comorbilidad`, `activo`, `requiere_valor_lab_cita`, `excluye_valor_lab_cita`, `excluye_etnia`) VALUES
(316, 148, '90746', 'D2', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(317, 149, '90746', '3', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(318, 149, '90746', 'D3', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(319, 150, '90746', '1', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(320, 150, '90746', 'D1', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(321, 151, '90746', '2', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(322, 151, '90746', 'D2', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(323, 152, '90746', '3', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(324, 152, '90746', 'D3', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(325, 153, '90746', '1', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(326, 153, '90746', 'D1', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(327, 154, '90746', '2', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(328, 154, '90746', 'D2', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(329, 155, '90746', '3', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(330, 155, '90746', 'D3', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(331, 156, '90746', '1', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(332, 156, '90746', 'D1', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(333, 156, '90744', '1', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(334, 156, '90744', 'D1', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(335, 157, '90744', '2', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(336, 157, '90744', 'D2', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(337, 157, '90746', '2', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(338, 157, '90746', 'D2', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(339, 158, '90744', '3', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(340, 158, '90744', 'D3', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(341, 158, '90746', '3', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(342, 158, '90746', 'D3', NULL, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(343, 159, '90717', 'DU', 36, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(344, 160, '90717', 'DU', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(345, 161, '90717', 'DU', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(346, 162, '90717', 'DU', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(347, 163, '90717', 'DU', 27, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(348, 164, '90715', 'DU', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(349, 165, '90715', 'DU', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(350, 166, '90715', 'DU', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(351, 167, '90649', 'DU', 37, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(352, 168, '90649', 'DU', 37, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(353, 169, '90649', 'DU', 38, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(354, 170, '90649', 'DU', 38, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(355, 171, '90649', 'DU', 39, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(356, 172, '90649', 'DU', 39, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(357, 173, '90649', 'DU', 40, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(358, 174, '90649', 'DU', 40, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(359, 175, '90649', 'DU', 41, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(360, 176, '90649', 'DU', 41, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(361, 177, '90649', 'DU', 42, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(362, 178, '90649', 'DU', 42, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(363, 179, '90670', 'DU', 36, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(364, 180, '90670', 'DU', 31, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(365, 181, '90670', 'DU', 32, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, 'ST', NULL),
(366, 182, '90670', 'DU', 33, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, 'ST', NULL),
(367, 183, '90670', 'DU', 34, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, 'ST', NULL),
(368, 184, '90670', 'DU', 36, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(369, 185, '90670', 'DU', 31, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, NULL, NULL),
(370, 186, '90670', 'DU', 32, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, 'ST', NULL),
(371, 187, '90670', 'DU', 33, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, 'ST', NULL),
(372, 188, '90670', 'DU', 34, 'A', NULL, NULL, 0, 0, 0, 1, 1, NULL, 'ST', NULL),
(373, 189, '90670', 'DU', 27, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'ST', NULL),
(374, 190, '90670', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(375, 191, '90714', 'DA', 43, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(376, 191, '90714', 'DDA', 43, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(377, 192, '90714', 'DA', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(378, 192, '90714', 'DDA', 31, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(379, 193, '90714', 'DA', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(380, 193, '90714', 'DDA', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(381, 194, '90714', 'DA', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(382, 194, '90714', 'DDA', 35, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(383, 195, '90714', 'DA', 27, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(384, 195, '90714', 'DDA', 27, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(385, 196, '90633.01', 'DU', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(386, 197, '90633.01', 'DU', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(387, 198, '90633.01', 'DU', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(388, 199, '90633.01', 'DU', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(389, 200, '90633.01', 'DU', 44, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(390, 201, '90707', 'DU', 45, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(391, 202, '90707', 'DU', 46, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'ST', NULL),
(392, 203, '90707', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(393, 204, '90716', 'DU', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(394, 205, '90716', 'DU', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(395, 206, '90716', 'DU', 44, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(397, 207, '90716', 'DU', 47, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'ST', NULL),
(398, 208, '90716', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'ST', NULL, NULL),
(399, 209, '90714', '1', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(400, 209, '90714', 'D1', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(401, 210, '90714', '2', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(402, 210, '90714', 'D2', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(403, 211, '90714', '3', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(404, 211, '90714', 'D3', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(405, 212, '90714', '1', 27, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(406, 212, '90714', 'D1', 27, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(407, 213, '90714', '2', 27, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(408, 213, '90714', 'D2', 27, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(409, 214, '90714', '3', 27, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(410, 214, '90714', 'D3', 27, 'F', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(411, 215, '90714', '1', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(412, 215, '90714', 'D1', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(413, 216, '90714', '2', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(414, 216, '90714', 'D2', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(415, 217, '90714', '3', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(416, 217, '90714', 'D3', 30, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(417, 218, '90714', '1', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(418, 218, '90714', 'D1', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(419, 219, '90714', '2', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(420, 219, '90714', 'D2', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(421, 220, '90714', '3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(422, 220, '90714', 'D3', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(423, 221, '90714', '1', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(424, 221, '90714', 'D1', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(425, 222, '90714', '2', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(426, 222, '90714', 'D2', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(427, 223, '90714', '3', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(428, 223, '90714', 'D3', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(429, 224, '90714', '1', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(430, 224, '90714', 'D1', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(431, 225, '90714', '2', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(432, 225, '90714', 'D2', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(433, 226, '90714', '3', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(434, 226, '90714', 'D3', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(435, 227, '90714', '1', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(436, 228, '90714', '2', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(437, 229, '90714', '3', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(438, 230, '90658', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'DIS', NULL, NULL),
(439, 231, '90658', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'OGR', NULL, NULL),
(440, 140, '90658', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, '56,57,58,59,60'),
(441, 38, '90701', 'DA', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(442, 232, '90713', '3', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(443, 233, '90722', '2', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(444, 234, '90722', '3', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(445, 33, '90670', '1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(446, 33, '90670', 'D1', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(447, 34, '90670', '2', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(448, 34, '90670', 'D2', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(449, 42, '90670', 'DU', 13, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(450, 42, '90670', 'DA', 13, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(451, 235, '90670', '1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(452, 235, '90670', 'D1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(453, 236, '90670', '2', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(454, 236, '90670', 'D2', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(455, 237, '90670', '3', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(456, 237, '90670', 'D3', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(457, 50, '90707', 'D1', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(458, 51, '90707', 'D2', 13, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(459, 238, '90670', 'DU', 14, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(460, 238, '90670', 'DA', 14, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(461, 239, '90722', '1', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(462, 239, '90722', 'D1', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(463, 240, '90722', '2', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(464, 240, '90722', 'D2', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(465, 241, '90722', '3', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(466, 241, '90722', 'D3', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(467, 242, '90701', 'DA', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(468, 242, '90701', 'DDA', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(469, 243, '90713', 'DA', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(470, 243, '90713', 'DDA', 14, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(471, 244, '90670', 'DU', 15, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(472, 244, '90670', 'DA', 15, 'A', NULL, NULL, 0, 0, 1, 0, 1, NULL, NULL, NULL),
(473, 245, '90722', '1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(474, 245, '90722', 'D1', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(475, 246, '90722', '2', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(476, 246, '90722', 'D2', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(477, 247, '90722', '3', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(478, 247, '90722', 'D3', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(479, 248, '90722', 'DA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(480, 248, '90722', 'DDA', 15, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(481, 249, '90722', '1', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(482, 249, '90722', 'D1', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(483, 250, '90722', '2', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(484, 250, '90722', 'D2', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(485, 251, '90722', '3', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(486, 251, '90722', 'D3', 28, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(487, 122, '90714', 'D1', 27, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(488, 107, '90714', 'D1', 29, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(489, 108, '90714', 'D2', 29, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(490, 109, '90714', 'D3', 29, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(491, 110, '90714', 'D1', 30, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(492, 111, '90714', 'D2', 30, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(493, 112, '90714', 'D3', 30, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(494, 113, '90714', 'D1', 31, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(495, 114, '90714', 'D2', 31, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(496, 115, '90714', 'D3', 31, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(497, 116, '90714', 'D1', 32, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(498, 117, '90714', 'D2', 32, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(499, 118, '90714', 'D3', 32, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(500, 119, '90714', 'D1', 35, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(501, 120, '90714', 'D2', 35, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(502, 121, '90714', 'D3', 35, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(503, 123, '90714', 'D2', 27, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(504, 124, '90714', 'D3', 27, 'M', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(505, 227, '90714', 'D1', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(506, 228, '90714', 'D2', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(507, 229, '90714', 'D3', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(508, 147, '90744', '1', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(509, 147, '90744', 'D1', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(510, 148, '90744', '2', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(511, 148, '90744', 'D2', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(512, 149, '90744', '3', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(513, 149, '90744', 'D3', 32, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, 'G', NULL),
(514, 252, '90746', '1', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'VIH', NULL, NULL),
(515, 252, '90746', 'D1', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'VIH', NULL, NULL),
(516, 253, '90746', '2', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'VIH', NULL, NULL),
(517, 253, '90746', 'D2', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'VIH', NULL, NULL),
(518, 254, '90746', '3', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'VIH', NULL, NULL),
(519, 254, '90746', 'D3', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, 'VIH', NULL, NULL),
(520, 75, '90722', 'DDA', 9, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL),
(521, 255, '90678', 'DU', 31, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(522, 256, '90678', 'DU', 32, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(523, 257, '90678', 'DU', 33, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(524, 258, '90678', 'DU', 34, 'F', NULL, NULL, 0, 0, 0, 0, 1, 'G', NULL, NULL),
(526, 259, '90380', 'DU', NULL, 'A', NULL, NULL, 0, 0, 0, 0, 1, NULL, NULL, NULL);
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
(1, 'A', 'A. - MENORES DE 01 AÑO', 'BCG, Hepatitis B, Antipolio (IPV), Pentavalente, Reacciones Adversas, Rotavirus, Neumococo, Influenza, Poblacion en Riesgo', 'lista', 1, 1),
(2, 'B', 'B. - DE 01 AÑO', 'Neumococo 1 anio, SPR, Varicela, Influenza, Neumococo 12-23m, Antiamarilica, Hepatitis A, SPR 2da, Ref DPT, Ref APO, Vacunacion no oportuna', 'lista', 2, 1),
(3, 'C', 'C. - DE 02 AÑOS', 'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 2 anos, Refuerzo APO 2 anos', 'lista', 3, 1),
(4, 'F', 'F. - dT ADULTO EN MUJERES EN EDAD FERTIL DESDE 5 ANIOS', 'Esquema dT en mujeres en edad fertil desde 5 anios', 'matriz_dosis', 4, 1),
(5, 'F2', 'F2. - GESTANTES (dT)', 'Esquema dT', 'matriz_dosis', 5, 1),
(6, 'G', 'G. - dT ADULTO: VARONES EN RIESGO', 'Esquema dT en varones en riesgo', 'matriz_dosis', 6, 1),
(7, 'H', 'H. - INFLUENZA ESTACIONAL EN OTROS GRUPOS', 'Influenza por grupo de edad y riesgo', 'lista', 7, 1),
(8, 'I', 'I. - SARAMPION - RUBEOLA', 'Vacunacion SR en ninos/personas no vacunadas', 'total_uno', 8, 1),
(9, 'J', 'J. - POBLACION DE 05 A 59 AÑOS: VACUNACION CONTRA LA HEPATITIS B', 'Hepatitis B en poblacion 5-59 anos', 'matriz_dosis', 9, 1),
(10, 'K', 'K. - ANTIAMARILICA', 'Antiamarilica en poblacion no vacunada y viajeros a zonas endemicas', 'total_uno', 10, 1),
(12, 'Q', 'Q. - VARICELA', 'Varicela por grupo de edad', 'total_uno', 15, 1),
(13, 'O', 'O. - NEUMOCOCO', 'Neumococo en poblacion en riesgo', 'total_uno', 13, 1),
(14, 'N', 'N.-VACUNA VPH', 'Virus del Papiloma Humano: femenino y masculino (dosis unica)', 'matriz_sexo', 12, 1),
(15, 'D', ' D. - DE 03 AÑOS', 'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 3 anos, Refuerzo APO 3 años', 'lista', 4, 1),
(16, 'E1', 'E1.-  DE 04 AÑOS', 'Influenza con/sin comorbilidad, Neumococo comorbilidad, Antiamarilica, Vacunacion no oportuna, Refuerzo DPT 4 anos, Refuerzo APO 4 anos', 'lista', 4, 1),
(17, 'E2', 'E2.-  DE 05 - 07 AÑOS', 'SPR, Refuerzo DPT', 'lista', 4, 1),
(18, 'L', 'L.- SOLO GESTANTES (dtpa)', 'Vacuna combinada dtpa', 'total_uno', 11, 1),
(19, 'R', 'R.-HEPATITIS A', 'Vacuna de 1 a 5', 'total_uno', 16, 1),
(20, 'T', 'T.-SPR-SARAMPION', 'vacuna de 5 a 59 y Trabajador de Salud', 'total_uno', 17, 1),
(21, 'P', 'P.-DT-DOSIS ADICIONALES', 'vacuna DT solo dosis adicional', 'total_uno', 14, 1),
(22, 'U', 'U.-VSR-VIRUS SINCITIAL RESPIRATORIO', 'Vacuna para gestantes', 'lista', 18, 1);
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
(23, 'IPV_RE', 'IPV (Refuerzo)', 'Vacuna Refuerzo IPV', '#0d6efd', 1, '2026-07-25 20:10:34'),
(24, 'VSR', 'VIRUS SINCITIAL RESPIRATORIO', 'Vacuna para gestantes', '#17ff0b', 1, '2026-09-01 10:22:23'),
(25, 'MON', 'Anticuerpo Monoclonal', 'dosis 0,5 ML', '#5aafa5', 1, '2026-09-01 10:37:56');

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
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`, `Mes_Int`) VALUES
('1419064878', '2026', '7', '1', '2026-07-01', 'CAR', 2, 5, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '857257318', 1, 'DNI', '76002819', 'SACHA', 'CENTENO', 'EVELING INES', '1999-11-12', 'F', '76002819', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 26, 'A', 26, 7, 19, '18 a 29 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-01 00:00:00', NULL, 7),
('1421610777', '2026', '7', '6', '2026-07-06', 'CAR', 57, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '40574283318', 1, 'DNI', '40641798', 'QUISPE', 'MARQUEZ', 'HERMELINDA', '1980-03-01', 'F', '40641798', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1861078318', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1861078', 1, 'DNI', '46403269', 'JUSTO', 'DAMAS', 'MELANI SOLEDAD', '1990-05-04', 'C', 'C', 46, 'A', 46, 4, 5, '30 a 59 años', NULL, 'M', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'D2', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL, 7),
('1421641485', '2026', '7', '6', '2026-07-06', 'CAR', 1, 23, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1594593318', 1, 'DNI', '76538361', 'QUINTANO', 'CCENCHO', 'SUSANA', '1994-08-24', 'F', '76538361', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1731011318', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1731011', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 'C', 'C', 31, 'A', 31, 10, 12, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL, 7),
('1421641485', '2026', '7', '6', '2026-07-06', 'CAR', 1, 23, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1594593318', 1, 'DNI', '76538361', 'QUINTANO', 'CCENCHO', 'SUSANA', '1994-08-24', 'F', '76538361', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1731011318', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1731011', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 'C', 'C', 31, 'A', 31, 10, 12, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-06 00:00:00', NULL, 7),
('1422023353', '2026', '7', '7', '2026-07-07', 'CAR', 29, 14, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6569139318', 1, 'DNI', '70336122', 'PALACIOS', 'MANDUJANO', 'CLARITA OTILIA', '1992-05-22', 'F', '70336122', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '37640278318', 1, 'DNI', '71798660', 'IRIARTE', 'RUTTI', 'JHON LENON', '1996-05-10', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '37640278', 1, 'DNI', '71798660', 'IRIARTE', 'RUTTI', 'JHON LENON', '1996-05-10', 'C', 'R', 34, 'A', 34, 1, 15, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-07 00:00:00', NULL, 7),
('1422023353', '2026', '7', '7', '2026-07-07', 'CAR', 29, 14, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '6569139318', 1, 'DNI', '70336122', 'PALACIOS', 'MANDUJANO', 'CLARITA OTILIA', '1992-05-22', 'F', '70336122', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '37640278318', 1, 'DNI', '71798660', 'IRIARTE', 'RUTTI', 'JHON LENON', '1996-05-10', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '37640278', 1, 'DNI', '71798660', 'IRIARTE', 'RUTTI', 'JHON LENON', '1996-05-10', 'C', 'R', 34, 'A', 34, 1, 15, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'ST', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-07 00:00:00', NULL, 7),
('1422023466', '2026', '7', '7', '2026-07-07', 'CAR', 73, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3717032318', 1, 'DNI', '71617611', 'FABABA', 'NAJAR', 'AYDELI', '1996-06-03', 'F', '71617611', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'R', 30, 'A', 30, 1, 4, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-07 00:00:00', NULL, 7),
('1422023466', '2026', '7', '7', '2026-07-07', 'CAR', 73, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3717032318', 1, 'DNI', '71617611', 'FABABA', 'NAJAR', 'AYDELI', '1996-06-03', 'F', '71617611', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'R', 30, 'A', 30, 1, 4, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-07 00:00:00', NULL, 7),
('1422028979', '2026', '7', '7', '2026-07-07', 'CAR', 73, 2, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '29645259318', 1, 'DNI', '74377875', 'TORRES', 'ORESES', 'LEYDI JAZMIN', '2002-11-01', 'F', '74377875', NULL, '10', 'CASHINAHUA', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 23, 'A', 23, 8, 6, '18 a 29 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-07 00:00:00', NULL, 7),
('1422028979', '2026', '7', '7', '2026-07-07', 'CAR', 73, 2, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '29645259318', 1, 'DNI', '74377875', 'TORRES', 'ORESES', 'LEYDI JAZMIN', '2002-11-01', 'F', '74377875', NULL, '10', 'CASHINAHUA', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 23, 'A', 23, 8, 6, '18 a 29 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-07 00:00:00', NULL, 7),
('1422525299', '2026', '7', '8', '2026-07-08', 'CAR', 29, 20, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4341574318', 1, 'DNI', '79463110', 'CAMASCA', 'MILLAN', 'MARIANELA', '2001-08-31', 'F', '79463110', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '37640278318', 1, 'DNI', '71798660', 'IRIARTE', 'RUTTI', 'JHON LENON', '1996-05-10', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '37640278', 1, 'DNI', '71798660', 'IRIARTE', 'RUTTI', 'JHON LENON', '1996-05-10', 'C', 'C', 24, 'A', 24, 10, 8, '18 a 29 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-08 00:00:00', NULL, 7),
('1423039712', '2026', '7', '9', '2026-07-09', 'CAR', 73, 6, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '23572296318', 1, 'DNI', '46620457', 'ESPINOZA', 'HUAYHUA', 'SONIA', '1990-11-10', 'F', '46620457', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 35, 'A', 35, 7, 29, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '3', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1423039712', '2026', '7', '9', '2026-07-09', 'CAR', 73, 6, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '23572296318', 1, 'DNI', '46620457', 'ESPINOZA', 'HUAYHUA', 'SONIA', '1990-11-10', 'F', '46620457', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 35, 'A', 35, 7, 29, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1423046959', '2026', '7', '9', '2026-07-09', 'CAR', 73, 7, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10887616318', 1, 'DNI', '48822699', 'SAMANIEGO', 'BALLESTEROS', 'JHOSERIT CAROLINA', '1991-07-25', 'F', '48822699', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 34, 'A', 34, 11, 14, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1423046959', '2026', '7', '9', '2026-07-09', 'CAR', 73, 7, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '10887616318', 1, 'DNI', '48822699', 'SAMANIEGO', 'BALLESTEROS', 'JHOSERIT CAROLINA', '1991-07-25', 'F', '48822699', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1732821318', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1732821', 1, 'DNI', '70038883', 'PACHECO', 'SOTO', 'GABRIELA MELISSA', '1993-06-02', 'C', 'C', 34, 'A', 34, 11, 14, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1423051832', '2026', '7', '9', '2026-07-09', 'CAR', 76, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2395495318', 1, 'DNI', '48697935', 'CARHUAPOMA', 'HUAYNALAYA', 'YOSILIN', '1993-08-07', 'F', '48697935', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1731011318', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1731011', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 'C', 'N', 32, 'A', 32, 11, 2, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1423051832', '2026', '7', '9', '2026-07-09', 'CAR', 76, 1, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2395495318', 1, 'DNI', '48697935', 'CARHUAPOMA', 'HUAYNALAYA', 'YOSILIN', '1993-08-07', 'F', '48697935', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1731011318', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1731011', 1, 'DNI', '72684378', 'ANCHIRAICO', 'REYES', 'NOEMA AUREA', '2001-11-05', 'C', 'N', 32, 'A', 32, 11, 2, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', 'G', 4, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1423110441', '2026', '7', '9', '2026-07-09', 'CAR', 4, 13, '301204', 'INMUNIZACIONES', 10, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2806831318', 1, 'DNI', '70242399', 'DE LA CRUZ', 'LUYO', 'BRANDON JAIRO', '1994-11-09', 'M', '70242399', NULL, '58', 'MESTIZO', '10', 'OTROS', 'PER', 'PERU', '1863515318', 1, 'DNI', '70243378', 'REYES', 'CARO', 'DAMARY YANIRA', '1990-06-09', 8, 'OTROS', '29', 'ENFERMERA (O)', '06', 'COLEGIO DE ENFERMEROS DEL PERU', NULL, '1863515', 1, 'DNI', '70243378', 'REYES', 'CARO', 'DAMARY YANIRA', '1990-06-09', 'R', 'R', 31, 'A', 31, 8, 0, '30 a 59 años', NULL, 'T', 'CP', '90746', 'VACUNA CONTRA LA HEPATITIS B DOSIS ADULTA (ESQUEMA DE 3 DOSIS) PARA USO INTRAMUSCULAR', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`, `Mes_Int`) VALUES
('1422897778', '2026', '7', '2', '2026-07-02', 'G50', 1, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 20287, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014385', 'MARISCAL CACERES', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'APP138', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'APP138', NULL, NULL, NULL, NULL, NULL, NULL, '467793820287', 1, 'DNI', '45545326', 'MUNIVE', 'MUÑICO', 'ADA', '1988-12-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'T', 'CP', 'C3151', 'SESIÓN DE ENTRENAMIENTO A AGENTES COMUNITARIOS EN SALUD', 'D', '1', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1422897778', '2026', '7', '2', '2026-07-02', 'G50', 1, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 20287, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014385', 'MARISCAL CACERES', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'APP138', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'APP138', NULL, NULL, NULL, NULL, NULL, NULL, '467793820287', 1, 'DNI', '45545326', 'MUNIVE', 'MUÑICO', 'ADA', '1988-12-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'T', 'CP', 'C3151', 'SESIÓN DE ENTRENAMIENTO A AGENTES COMUNITARIOS EN SALUD', 'D', 'VCO', 2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7),
('1422897778', '2026', '7', '2', '2026-07-02', 'G50', 1, 2, '302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA', 1, NULL, NULL, 20287, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00014385', 'MARISCAL CACERES', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', 'APP138', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'APP138', NULL, NULL, NULL, NULL, NULL, NULL, '467793820287', 1, 'DNI', '45545326', 'MUNIVE', 'MUÑICO', 'ADA', '1988-12-15', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '2862485', 1, 'DNI', '41132134', 'ESPINOZA', 'VALERIO', 'MARCO ANTONIO', '1981-05-01', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'T', 'CP', 'C3151', 'SESIÓN DE ENTRENAMIENTO A AGENTES COMUNITARIOS EN SALUD', 'D', '2', 3, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-09 00:00:00', NULL, 7);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`, `Mes_Int`) VALUES
('1424065094', '2026', '7', '7', '2026-07-07', 'G57', 7, 1, '303203', 'OBSTETRICIA', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1715491320', 1, 'DNI', '71100639', 'JULIAN', 'RIOS', 'DINA EWITT', '1995-03-29', 'F', '71100639', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '8486865320', 1, 'DNI', '74455654', 'LAZARO', 'MENDOZA', 'YANELY SALLY', '1998-03-05', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '8046799', 1, 'DNI', '73101825', 'GRANADOS', 'VASQUEZ', 'RUBEN DARIO', '1997-11-03', 'C', 'C', 31, 'A', 31, 3, 8, '30 a 59 años', NULL, 'M', 'CX', 'C539', 'TUMOR MALIGNO DEL CUELLO DEL UTERO SIN OTRA ESPECIFICACION', 'D', NULL, 1, 1, '66.000', '154.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-12 00:00:00', NULL, 7),
('1425979007', '2026', '7', '14', '2026-07-14', 'G19', 6, 11, '300303', 'CIRUGIA EN CONSULTORIO EXTERNO / TOPICO', 1, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1657085320', 1, 'DNI', '20524467', 'MARTIN', 'ROSALES', 'JESUSA', '1960-12-25', 'F', '20524467', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '748989320', 1, 'DNI', '70037089', 'TUEROS', 'HUAMAN', 'GIULIANA LETICIA', '1995-02-27', 8, 'OTROS', '01', 'MEDICO GENERAL', '01', 'COLEGIO MEDICO DE PERU', NULL, '9650358', 1, 'DNI', '71422545', 'FERNANDEZ', 'GONZALES', 'TANIA KELLY', '1998-04-25', 'C', 'C', 65, 'A', 65, 6, 19, '60 años a mas', NULL, 'M', 'CX', 'C169', 'TUMOR MALIGNO DEL ESTOMA PARTE NO ESPECIFICADA', 'P', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-15 00:00:00', NULL, 7),
('1426936830', '2026', '7', '16', '2026-07-16', 'G42', 14, 4, '300303', 'CIRUGIA EN CONSULTORIO EXTERNO / TOPICO', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1288291318', 1, 'DNI', '20570193', 'GOMEZ', 'GENCHO', 'FELIPE GUILLERMO', '1958-05-30', 'M', '20570193', '10247', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '4555807318', 1, 'DNI', '71088672', 'MARTÍNEZ', 'RODRÍGUEZ', 'PIERO', '1994-09-15', 8, 'OTROS', '19', 'MEDICO GINECO-OBSTETRA', '01', 'COLEGIO MEDICO DE PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 68, 'A', 68, 1, 16, '60 años a mas', NULL, 'M', 'CX', 'C340', 'TUMOR MALIGNO DEL BRONQUIO PRINCIPAL', 'P', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-17 00:00:00', NULL, 7),
('1427838783', '2026', '7', '20', '2026-07-20', 'CX1', 88, 23, '302303', 'MEDICINA GENERAL', 17, NULL, NULL, 320, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000320', 'PUERTO YURINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3194786320', 1, 'DNI', '41082817', 'SANCHEZ', 'OSCO', 'KETTY TULA', '1979-09-26', 'F', '41082817', NULL, '05', 'ASHANINKA', '2', 'S.I.S', 'PER', 'PERU', '6923001320', 1, 'DNI', '74038701', 'QUILLATUPA', 'RAMIREZ', 'FIORELLA FABIOLA', '2001-12-09', 8, 'OTROS', '01', 'MEDICO GENERAL', '01', 'COLEGIO MEDICO DE PERU', NULL, '6923001', 1, 'DNI', '74038701', 'QUILLATUPA', 'RAMIREZ', 'FIORELLA FABIOLA', '2001-12-09', 'C', 'R', 46, 'A', 46, 9, 24, '30 a 59 años', NULL, 'M', 'CX', 'C509', 'TUMOR MALIGNO DE LA MAMA PARTE NO ESPECIFICADA', 'P', NULL, 1, 1, '76.000', '154.00', NULL, '98.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-20 00:00:00', '2026-07-20 00:00:00', 7),
('1430307077', '2026', '7', '22', '2026-07-22', 'G46', 6, 4, '300303', 'CIRUGIA EN CONSULTORIO EXTERNO / TOPICO', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1288291318', 1, 'DNI', '20570193', 'GOMEZ', 'GENCHO', 'FELIPE GUILLERMO', '1958-05-30', 'M', '20570193', '10247', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '9851034318', 1, 'DNI', '42728286', 'ALDANA', 'PALOMARES', 'EDUARDO FILER', '1984-11-19', 8, 'OTROS', '19', 'MEDICO GINECO-OBSTETRA', '01', 'COLEGIO MEDICO DE PERU', NULL, '2981756', 1, 'DNI', '76434714', 'MARTINEZ', 'RAMOS', 'MILENA MONICA', '1999-03-10', 'C', 'C', 68, 'A', 68, 1, 22, '60 años a mas', NULL, 'M', 'CX', 'C349', 'TUMOR MALIGNO DE LOS BRONQUIOS O DEL PULMON PARTE NO ESPECIFICADA', 'R', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-24 00:00:00', NULL, 7),
('1432374215', '2026', '7', '28', '2026-07-28', 'G38', 6, 1, '302303', 'MEDICINA GENERAL', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '17155771318', 1, 'DNI', '23716631', 'HUINCHO', 'CONDOR', 'ALEJANDRO', '1975-04-15', 'M', '23716631', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '28696668318', 1, 'DNI', '73127349', 'PORRAS', 'GASPAR', 'ANGHELA ANAMILE', '1998-09-28', 8, 'OTROS', '19', 'MEDICO GINECO-OBSTETRA', '01', 'COLEGIO MEDICO DE PERU', '102584', '7948905', 1, 'DNI', '46429075', 'SIXTO', 'ANDRES', 'TIFFANY SLIDES', '1990-07-27', 'C', 'C', 51, 'A', 51, 3, 13, '30 a 59 años', NULL, 'M', 'CX', 'C189', 'TUMOR MALIGNO DEL COLON PARTE NO ESPECIFICADA', 'P', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-30 00:00:00', NULL, 7),
('1432678615', '2026', '7', '23', '2026-07-23', 'G38', 10, 4, '302303', 'MEDICINA GENERAL', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8218919318', 1, 'DNI', '20522738', 'ROMANI', 'PICHARDO', 'ESTELA', '1957-01-05', 'F', '20522738', '15567', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '747600318', 1, 'DNI', '45835274', 'PAITAN', 'GERONIMO', 'PEDRO ARMANDO', '1988-04-10', 8, 'OTROS', '19', 'MEDICO GINECO-OBSTETRA', '01', 'COLEGIO MEDICO DE PERU', NULL, '7948905', 1, 'DNI', '46429075', 'SIXTO', 'ANDRES', 'TIFFANY SLIDES', '1990-07-27', 'C', 'C', 69, 'A', 69, 6, 18, '60 años a mas', NULL, 'M', 'CX', 'C189', 'TUMOR MALIGNO DEL COLON PARTE NO ESPECIFICADA', 'P', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-30 00:00:00', NULL, 7),
('1435630277', '2026', '7', '23', '2026-07-23', 'G16', 7, 17, '300303', 'CIRUGIA EN CONSULTORIO EXTERNO / TOPICO', 1, NULL, NULL, 330, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000330', 'SAN FERNANDO DE KIVINAKI', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '7742301330', 1, 'DNI', '70927801', 'SACHA', 'PASTOR', 'SUSAN SANDY', '1994-06-21', 'F', '70927801', NULL, '05', 'ASHANINKA', '2', 'S.I.S', 'PER', 'PERU', '743017330', 1, 'DNI', '70394988', 'RICAPA', 'FUSTER', 'AIME KATHERINE', '1996-04-15', 8, 'OTROS', '01', 'MEDICO GENERAL', '01', 'COLEGIO MEDICO DE PERU', NULL, '1217459', 1, 'DNI', '44545769', 'CAYSAHUANA', 'ZAVALA', 'CYNTHIA NIEVES', '1986-08-19', 'R', 'N', 32, 'A', 32, 1, 2, '30 a 59 años', NULL, 'M', 'CX', 'C505', 'TUMOR MALIGNO DEL CUADRANTE INFERIOR EXTERNO DE LA MAMA', 'D', NULL, 3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 00:00:00', NULL, 7);
INSERT INTO `T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO` (`Id_Cita`, `Anio`, `Mes`, `Dia`, `Fecha_Atencion`, `Lote`, `Num_Pag`, `Num_Reg`, `Id_Ups`, `Descripcion_Ups`, `Id_AplicacionOrigen`, `Alerta`, `Id_Institucion_Edu`, `Id_Establecimiento`, `Codigo_Sector`, `Descripcion_Sector`, `Codigo_Disa`, `Descripcion_Disa`, `Codigo_Red`, `Descripcion_Red`, `Codigo_MicroRed`, `Descripcion_MicroRed`, `Codigo_Unico`, `Nombre_Establecimiento`, `Ubigueo_Establecimiento`, `Departamento_Establecimiento`, `Provincia_Establecimiento`, `Distrito_Establecimiento`, `Id_Paciente`, `Tipo_Doc_Paciente`, `Abrev_Tipo_Doc_Paciente`, `Numero_Documento_Paciente`, `Apellido_Paterno_Paciente`, `Apellido_Materno_Paciente`, `Nombres_Paciente`, `Fecha_Nacimiento_Paciente`, `Id_Genero`, `Historia_Clinica`, `Ficha_Familiar`, `Id_Etnia`, `Descripcion_Etnia`, `Id_Financiador`, `Descripcion_Financiador`, `Id_Pais`, `Descripcion_Pais`, `Id_Personal`, `Tipo_Doc_Personal`, `Abrev_Tipo_Doc_Personal`, `Numero_Documento_Personal`, `Apellido_Paterno_Personal`, `Apellido_Materno_Personal`, `Nombres_Personal`, `Fecha_Nacimiento_Personal`, `Id_Condicion`, `Descripcion_Condicion`, `Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`, `Descripcion_Colegio`, `Numero_Colegiatura`, `Id_Registrador`, `Tipo_Doc_Registrador`, `Abrev_Tipo_Doc_Registrador`, `Numero_Documento_Registrador`, `Apellido_Paterno_Registrador`, `Apellido_Materno_Registrador`, `Nombres_Registrador`, `Fecha_Nacimiento_Registrador`, `Id_Condicion_Establecimiento`, `Id_Condicion_Servicio`, `Edad_Reg`, `Tipo_Edad`, `Anio_Actual_Paciente`, `Mes_Actual_Paciente`, `Dia_Actual_Paciente`, `Grupo_Edad`, `peso_pregestacional`, `Id_Turno`, `Fg_Tipo`, `Codigo_Item`, `Descripcion_Item`, `Tipo_Diagnostico`, `Valor_Lab`, `Id_Correlativo_Item`, `Id_Correlativo_Lab`, `Peso`, `Talla`, `Hemoglobina`, `Perimetro_Abdominal`, `Perimetro_Cefalico`, `Id_Otra_Condicion`, `Descripcion_Otra_Condicion`, `Id_Centro_Poblado`, `Descripcion_Centro_Poblado`, `Id_Codigo_Centro_Poblado`, `Id_Ubigueo_Centro_Poblado`, `Altitud_Centro_Poblado`, `Fecha_Ultima_Regla`, `Fecha_Solicitud_Hb`, `Fecha_Resultado_Hb`, `Fecha_Registro`, `Fecha_Modificacion`, `Mes_Int`) VALUES
('1422439319', '2026', '7', '7', '2026-07-07', 'G10', 4, 3, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '26689613318', 1, 'DNI', '63538145', 'RUIZ', 'AHUANARI', 'ZAYDA', '1998-11-16', 'F', '63538145', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '2246967318', 1, 'DNI', '48482099', 'HINOSTROZA', 'MIRANDA', 'JENNY LAURA', '1994-10-10', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'R', 27, 'A', 27, 7, 21, '18 a 29 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, 1, 'GESTANTE', NULL, NULL, NULL, NULL, NULL, '2026-05-05', NULL, NULL, '2026-07-08 00:00:00', '2026-07-08 00:00:00', 7),
('1422407809', '2026', '7', '7', '2026-07-07', 'G10', 2, 2, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '3717032318', 1, 'DNI', '71617611', 'FABABA', 'NAJAR', 'AYDELI', '1996-06-03', 'F', '71617611', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '20794723318', 1, 'DNI', '42674129', 'HUAMAN', 'ARZAPALO', 'HEYDY MARCIA', '1984-09-14', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 30, 'A', 30, 1, 4, '30 a 59 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, 1, 'GESTANTE', NULL, NULL, NULL, NULL, NULL, '2026-03-03', NULL, NULL, '2026-07-08 00:00:00', NULL, 7),
('1422426189', '2026', '7', '6', '2026-07-06', 'G10', 3, 5, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '1594593318', 1, 'DNI', '76538361', 'QUINTANO', 'CCENCHO', 'SUSANA', '1994-08-24', 'F', '76538361', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '20794723318', 1, 'DNI', '42674129', 'HUAMAN', 'ARZAPALO', 'HEYDY MARCIA', '1984-09-14', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 31, 'A', 31, 10, 12, '30 a 59 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, 1, 'GESTANTE', NULL, NULL, NULL, NULL, NULL, '2026-04-08', NULL, NULL, '2026-07-08 00:00:00', NULL, 7),
('1422437610', '2026', '7', '3', '2026-07-03', 'G10', 4, 1, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4763939318', 1, 'DNI', '76018260', 'RIVERA', 'GOMEZ', 'DELIA SARITA', '2004-02-22', 'F', '76018260', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '2246967318', 1, 'DNI', '48482099', 'HINOSTROZA', 'MIRANDA', 'JENNY LAURA', '1994-10-10', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 22, 'A', 22, 4, 11, '18 a 29 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, 1, 'GESTANTE', NULL, NULL, NULL, NULL, NULL, '2026-04-28', NULL, NULL, '2026-07-08 00:00:00', NULL, 7),
('1422445105', '2026', '7', '1', '2026-07-01', 'G10', 6, 3, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '849592318', 1, 'DNI', '20587685', 'ROJAS', 'VILA', 'CARMEN', '1976-06-03', 'F', '20587685', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '20794723318', 1, 'DNI', '42674129', 'HUAMAN', 'ARZAPALO', 'HEYDY MARCIA', '1984-09-14', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 50, 'A', 50, 0, 28, '30 a 59 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-08 00:00:00', '2026-07-20 00:00:00', 7),
('1422466468', '2026', '7', '2', '2026-07-02', 'G10', 13, 1, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '2822646318', 1, 'DNI', '20051303', 'BALDEON', 'HUAMANI', 'BEATRIZ NICOLAZA', '1967-05-10', 'F', '20051303', '26068', '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '390951318', 1, 'DNI', '76187158', 'ESTRADA', 'QUISPE', 'MEDALID GUADALUPE', '1995-03-11', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'R', 59, 'A', 59, 1, 22, '30 a 59 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 1, 1, '56.000', '140.00', NULL, '99.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-08 00:00:00', '2026-07-08 00:00:00', 7),
('1422489867', '2026', '7', '2', '2026-07-02', 'G10', 13, 2, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '4324699318', 1, 'DNI', '31181432', 'VILLANO', 'CARDENAS', 'HERMINIA', '1967-02-23', 'F', '31181432', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '390951318', 1, 'DNI', '76187158', 'ESTRADA', 'QUISPE', 'MEDALID GUADALUPE', '1995-03-11', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'R', 'R', 59, 'A', 59, 4, 9, '30 a 59 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 1, 1, '61.000', '145.00', NULL, '79.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-08 00:00:00', '2026-07-08 00:00:00', 7),
('1422490257', '2026', '7', '2', '2026-07-02', 'G10', 13, 3, '303203', 'OBSTETRICIA', 1, NULL, NULL, 318, 7, 'GOBIERNO REGIONAL', 17, 'JUNIN', '04', 'CHANCHAMAYO', '03', 'PERENE', '00000318', 'VILLA PERENE', '120302', 'JUNIN', 'CHANCHAMAYO', 'PERENE', '8351003318', 1, 'DNI', '77802922', 'CHOCHOCCA', 'TAIPE', 'LOURDES', '2001-06-18', 'F', '77802922', NULL, '58', 'MESTIZO', '2', 'S.I.S', 'PER', 'PERU', '390951318', 1, 'DNI', '76187158', 'ESTRADA', 'QUISPE', 'MEDALID GUADALUPE', '1995-03-11', 8, 'OTROS', '23', 'OBSTETRA', '05', 'COLEGIO DE OBSTETRAS DEL PERU', NULL, '28273127', 1, 'DNI', '72253003', 'RODRIGUEZ', 'VITANCIO', 'JHON KEVIN', '1997-05-15', 'C', 'C', 25, 'A', 25, 0, 14, '18 a 29 años', NULL, 'M', 'PL', '88141', 'CITOPATOLOGIA CERVICAL O VAGINAL Y TAMIZAJE MANUAL (PAPANICOLAU)', 'D', NULL, 1, 1, '57.000', '144.00', NULL, '80.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-08 00:00:00', '2026-07-08 00:00:00', 7);

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
(1, 'admin', '$2a$12$oyHlo6Q4WLAz05diONMFx.D93QR915rs.iVOZx7K1wUpgiTI7CtB.', 'Administrador', 'admin', 1, '2026-06-14 12:35:48', '2026-08-08 07:55:50'),
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
  MODIFY `id_grupo_edad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `ESNI_LINEA_REPORTE`
--
ALTER TABLE `ESNI_LINEA_REPORTE`
  MODIFY `id_linea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT de la tabla `ESNI_REGLA`
--
ALTER TABLE `ESNI_REGLA`
  MODIFY `id_regla` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;

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
