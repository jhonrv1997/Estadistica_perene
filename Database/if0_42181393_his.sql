-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql102.infinityfree.com
-- Tiempo de generación: 20-07-2026 a las 22:29:15
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_42181393_his`
--

-- --------------------------------------------------------

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

--
-- Volcado de datos para la tabla `CONTROL_CALIDAD_OBSERVACIONES`
--

INSERT INTO `CONTROL_CALIDAD_OBSERVACIONES` (`id_observacion`, `anio`, `mes`, `fecha_observacion`, `id_establecimiento`, `nombre_establecimiento`, `tipo_observacion`, `severidad`, `campo_afectado`, `descripcion`, `id_cita`, `codigo_item`, `numero_documento_paciente`, `numero_documento_personal`, `estado`, `fecha_creacion`, `fecha_cierre`, `usuario_registro`, `usuario_cierre`, `solucion`) VALUES
(1, '2026', '05', '2026-06-15', NULL, NULL, 'Dato Faltante', 'MEDIA', 'Fecha_Nacimiento_Paciente', 'Ejemplo: 38 registros del establecimiento sin fecha de nacimiento del paciente', NULL, NULL, NULL, NULL, 'PENDIENTE', '2026-07-19 09:48:58', NULL, 'admin', NULL, NULL),
(2, '2026', '05', '2026-06-15', NULL, NULL, 'Codigo Invalido', 'ALTA', 'Codigo_Item', 'Ejemplo: 12 atenciones con codigo CIE-10 fuera del catalogo vigente', NULL, NULL, NULL, NULL, 'PENDIENTE', '2026-07-19 09:48:58', NULL, 'admin', NULL, NULL),
(3, '2026', '05', '2026-06-15', NULL, NULL, 'Duplicidad', 'MEDIA', 'Id_Cita', 'Ejemplo: 7 registros con Id_Cita duplicado en el periodo', NULL, NULL, NULL, NULL, 'EN_PROCESO', '2026-07-19 09:48:58', NULL, 'admin', NULL, NULL),
(4, '2026', '06', '2026-07-01', NULL, NULL, 'Inconsistencia', 'CRITICA', 'Tipo_Diagnostico', 'Ejemplo: 5 atenciones con Tipo_Diagnostico vacio en diagnostico definitivo', NULL, NULL, NULL, NULL, 'PENDIENTE', '2026-07-19 09:48:58', NULL, 'admin', NULL, NULL);

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

--
-- Volcado de datos para la tabla `CONVENIO_FED_INDICADORES`
--

INSERT INTO `CONVENIO_FED_INDICADORES` (`id_indicador`, `codigo`, `nombre`, `descripcion`, `unidad_medida`, `meta_anual`, `peso_ponderado`, `periodo`, `responsable`, `orden`, `estado`) VALUES
(1, 'FED-01', 'Reduccion de la mortalidad materna', 'Indicador de reduccion de la razon de mortalidad materna (RMM)', 'Tasa', '100.00', '20.00', 'TRIMESTRAL', 'Estrategia Materno Perinatal', 1, 1),
(2, 'FED-02', 'Reduccion de la mortalidad neonatal', 'Indicador de reduccion de la tasa de mortalidad neonatal', 'Tasa', '100.00', '15.00', 'TRIMESTRAL', 'Estrategia Nino', 2, 1),
(3, 'FED-03', 'Incremento de la cobertura en menores de 1 ano', 'Mejora en el esquema de vacunacion Y CRED completa en menores de 1 ano', 'Porcentaje', '100.00', '15.00', 'TRIMESTRAL', 'Inmunizaciones', 3, 1),
(4, 'FED-04', 'Reduccion de la desnutricion en gestante', 'Indicador de reduccion de la desnutricion en gestante', 'Porcentaje', '100.00', '15.00', 'TRIMESTRAL', 'Estrategia Materno Perinatal', 4, 1),
(5, 'FED-05', 'Reduccion de la desnutricion en niños', 'Indicador de reduccion de la desnutricion en niños', 'Porcentaje', '100.00', '15.00', 'TRIMESTRAL', 'No Transmisibles', 5, 1),
(6, 'FED-06', 'Adolescentes mujeres de 12 a 17 años de edad con dosaje de hemoglobina y otras prestaciones priorizadas', 'Cobertura de Adolescentes', 'Porcentaje', '100.00', '10.00', 'TRIMESTRAL', 'Estrategia Adolescentes', 6, 1),
(7, 'FED-07', 'gestantes que cuentan con tamizaje\r\npositivo de violencia', 'Cobertura gestantes', 'Porcentaje', '100.00', '10.00', 'TRIMESTRAL', 'Estrategia Materno Perinatal', 7, 1);

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

--
-- Volcado de datos para la tabla `CONVENIO_GESTION_INDICADORES`
--

INSERT INTO `CONVENIO_GESTION_INDICADORES` (`id_indicador`, `codigo`, `nombre`, `descripcion`, `unidad_medida`, `meta_anual`, `periodo`, `responsable`, `orden`, `estado`) VALUES
(1, 'CG-01', 'Porcentaje de niñas y niños de 12 a 18 meses, con diagnóstico de anemia entre los 6 y 11 meses, que se han recuperado', 'Determina el porcentaje de niños de 12 a 18 meses que, habiendo tenido un diagnóstico de anemia entre los 6 y 11 meses, se han recuperado luego de 6 meses a partir del diagnóstico.', 'Porcentaje', '50.00', 'MENSUAL', 'Estrategia Nutricional', 1, 1),
(2, 'CG-02', 'Porcentaje de niñas y niños de 6 a 11 meses que iniciaron suplementación preventiva con hierro, culminan el esquema completo de 6 meses y se mantienen sin anemia', 'Determina el porcentaje de niños de 6 a 11 meses que, habiendo iniciado la suplementación preventiva con hierro culminan el esquema de suplementación en un periodo de 6 meses y se mantienen sin anemia.', 'Porcentaje', '70.00', 'MENSUAL', 'Estrategia Nutricional', 2, 1),
(3, 'CG-03', 'Porcentaje de recién nacidos con tamizaje neonatal metabólico', 'Tamizaje neonatal a partir de las 48 hrs. de nacido hasta los 06 días.', 'Porcentaje', '80.00', 'MENSUAL', 'Estrategia Inmunizaciones', 3, 1),
(4, 'CG-04', 'Porcentaje de niñas y niños menores de 2 años en condición de crecimiento inadecuado que luego de un periodo de seguimiento mejora sus condiciones nutricionales.', 'Determina el porcentaje de niñas y niños menores de 2 años que, habiéndose encontrado en condición de riesgo nutricional, mejoran sus condiciones nutricionales luego de un periodo de seguimiento entre 60 a 100 días.', 'Porcentaje', '50.00', 'MENSUAL', 'Estrategia Niño', 4, 1),
(5, 'CG-05', 'Porcentaje de niños de 24 meses de edad, que reciben vacunas para su edad.', 'Cobertura de vacunación', 'Porcentaje', '85.00', 'MENSUAL', 'Estrategia Inmunizaciones', 5, 1),
(6, 'CG-06', 'Porcentaje de recién nacidos de parto institucional, vacunados con BCG y Anti hepatitis B dentro de las 24 horas después del nacimiento.', 'Cobertura de vacunación', 'Porcentaje', '95.00', 'MENSUAL', 'Estrategia Inmunizaciones', 6, 1),
(7, 'CG-07', 'Tasa de éxito de tratamiento para TB Sensible', 'Contribuye a la disminución de la mortalidad por TB', 'Porcentaje', '90.00', 'MENSUAL', 'Estrategia TBC', 7, 1),
(8, 'CG-08', 'Porcentaje de contactos de TB que culminan Terapia Preventiva para TB (TPTB).', 'El objetivo de la TPT es mejorar la salud individual, protegiendo de la enfermedad y reducir la transmisión actual de la tuberculosis', 'Porcentaje', '60.00', 'MENSUAL', 'Estrategia TBC', 8, 1),
(9, 'CG-09', 'Cobertura de la Terapia Preventiva para Tuberculosis en personas adultas viviendo con VIH que reciben tratamiento antirretroviral', 'Reducir el riesgo de morbilidad y mortalidad asociada a esta enfermedad oportunista.', 'Porcentaje', '90.00', 'MENSUAL', 'Estrategia TBC', 9, 1),
(10, 'CG-10', 'Porcentaje de niños (6 meses a 6 años, 11 meses y 29 días) que reciben procedimientos estomatológicos preventivos.', 'Mantener su salud bucal durante su curso de vida.', 'Porcentaje', '20.00', 'MENSUAL', 'Odontologia', 10, 1),
(11, 'CG-11', 'Porcentaje de personas que acceden a algún método anticonceptivo moderno de planificación familiar.', 'El indicador determina el porcentaje de personas hombres y mujeres en edad reproductiva con necesidad de planificar su familia', 'Porcentaje', '45.00', 'MENSUAL', 'Obstetricia', 11, 1),
(12, 'CG-12', 'Porcentaje de gestantes atendidas con 2 o más Atenciones Prenatales (APN) en el hospital, referidas por factores de riesgo.', 'Indicador que determina el total de gestantes con 2 o más Atenciones Prenatales (APN) en el hospital y que han sido referidas del 1° nivel de atención por complicaciones', 'Porcentaje', '80.00', 'MENSUAL', 'Obstetricia', 12, 1),
(13, 'CG-13', 'Porcentaje de gestantes con paquete preventivo básico, priorizado', 'Monitoreo de varias condiciones de salud, por métodos clínicos, de laboratorio, imágenes que permita mejor la calidad de atención a la gestante', 'Porcentaje', '960.00', 'MENSUAL', 'salud materna', 13, 1),
(14, 'CG-14', 'Porcentaje de personas con diagnóstico de cánceres prevalentes que inician tratamiento oncológico en menor o igual a 37 días', 'Optimizar el acceso de las personas diagnosticadas con cánceres prevalentes', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales del II y III nivel de atención', 14, 0),
(15, 'CG-15', 'Porcentaje de mujeres de 40 a 69 años con mamografía bilateral de tamizaje', 'Cobertura de salud oncológico', 'Porcentaje', '0.00', 'MENSUAL', 'Establecimientos de Salud que cuentan con mamógrafo.', 15, 0),
(16, 'CG-16', 'Porcentaje niños de 9 años de edad vacunados contra el virus de papiloma humano (VPH).', 'Cobertura vacunación', 'Porcentaje', '90.00', 'MENSUAL', 'Estrategia Inmunizaciones', 16, 1),
(17, 'CG-17', 'Porcentaje de niños menores de 5 años con deficiencias o factores de riesgo de discapacidad, con seis o más atenciones en la UPSS Medicina de Rehabilitación', 'Incrementar la oferta médica', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales e Institutos', 17, 0),
(18, 'CG-18', 'Egresos de personas por problemas de salud mental en Hospitales con Unidades de Hospitalización de Salud Mental y Adicciones', 'Salud Mental', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales', 18, 0),
(19, 'CG-19', 'Porcentaje de personas con diagnóstico de depresión que reciben el paquete estándar de intervenciones.', 'Salud Mental', 'Porcentaje', '5.00', 'MENSUAL', 'Centros de Salud Mental Comunitaria (CSMC)', 19, 0),
(20, 'CG-20', 'Porcentaje de Resolutividad', 'Resolutividad', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales con población asignada y hospitales', 20, 0),
(21, 'CG-21', 'Rendimiento de Sala de Operaciones', 'Operaciones', 'Porcentaje', '0.00', 'MENSUAL', 'IPRESS que cuenten con UPSS Centro Quirurgico', 21, 0),
(22, 'CG-22', 'Porcentaje de Cirugías Suspendidas', 'Cirugías', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales', 22, 0),
(23, 'CG-23', 'Porcentaje de ocupación cama', 'cama', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales e Institutos Especializados.', 23, 0),
(24, 'CG-24', 'Intervalo de Sustitución de Cama', 'Cama', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales e Institutos Especializados', 24, 0),
(25, 'CG-25', 'Promedio de Espera para la Atención en Consulta Externa de un paciente referido', 'Identificar los tiempos de espera de un usuario para recibir una atención medica posterior a la aceptación de solicitud de la referencia', 'Porcentaje', '100.00', 'MENSUAL', 'REFCON', 25, 1),
(26, 'CG-26', 'Utilización de consultorios externos de medicina', 'Evaluar el número de turnos que se viene otorgando para la atención en la consulta externa de medicina, comprendiendo un total de 12 horas por día', 'Porcentaje', '20.00', 'MENSUAL', 'Recursos Humanos', 26, 1),
(27, 'CG-27', 'Densidad de Incidencia y/o Incidencia Acumulada de las Infecciones Asociadas a la Atención en Salud (IAAS) seleccionadas', 'IAAS', 'Porcentaje', '0.00', 'MENSUAL', 'Hospitales', 27, 0),
(28, 'CG-28', 'Porcentaje de Disponibilidad de Medicamentos Esenciales', 'Cumplimiento del sistema de vigilancia', 'Porcentaje', '90.00', 'MENSUAL', 'Red de Salud', 28, 1),
(29, 'CG-29', 'Porcentaje de personal registrado en el aplicativo del Registro Nacional de Personal de la Salud sin inconsistencias de información', 'Consistencia INFORHUS', 'Porcentaje', '95.00', 'MENSUAL', 'Recursos humanos', 29, 1),
(30, 'CG-30', 'Porcentaje de concordancia entre los aplicativos del AIRHSP, INFORHUS y de la Planilla de Haberes (PLH)', 'Consistencia', 'Porcentaje', '100.00', 'MENSUAL', 'Recursos humanos', 30, 1),
(31, 'CG-31', 'Porcentaje de concordancia del numero del personal médico y cirujano dentista consignado en INFORHUS, TUSUSALUD, ARFSIS WEB, HIS MINSA y el SIHCE', 'Consistencia', 'Porcentaje', '80.00', 'MENSUAL', 'Recursos humanos', 31, 1),
(32, 'CG-32', 'Tasa de uso de los servicios de telemedicina', 'Uso de servicios teleinterconsultas, teleconsulta, telemeonitoreo', 'Porcentaje', '100.00', 'MENSUAL', 'REFCON', 32, 1),
(33, 'CG-33', 'Porcentaje de uso de la firma digital de las atenciones registradas en el componente de consulta externa de medicina y componente de Atención Prenatal del SIHCE', 'Firma digital', 'Porcentaje', '60.00', 'MENSUAL', 'Calidad', 33, 1);

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

--
-- Volcado de datos para la tabla `IMPORT_ESTADO`
--

INSERT INTO `IMPORT_ESTADO` (`id`, `tipo_archivo`, `importado`, `fecha_importacion`, `nombre_archivo`, `periodo_mes`, `periodo_anio`, `registros_importados`) VALUES
(1, 'MaestroRegistrador', 1, '2026-07-18 14:07:40', 'MaestroRegistrador1121258.zip', NULL, NULL, 1260),
(2, 'MaestroPersonal', 1, '2026-07-18 14:05:54', 'MaestroPersonal1120952.zip', NULL, NULL, 3523),
(3, 'MaestroPaciente', 1, '2026-07-18 14:07:14', 'MaestroPaciente1120646.zip', NULL, NULL, 230742),
(4, 'NominalTrama', 1, '2026-07-18 14:12:04', 'NominalTrama18072026_112124.zip', '7', '2026', 33826);

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

--
-- Volcado de datos para la tabla `MAESTRO_HIS_COLEGIO`
--

INSERT INTO `MAESTRO_HIS_COLEGIO` (`Id_Colegio`, `Descripcion_Colegio`) VALUES
('00', 'PERSONAL DE SALUD SIN COLEGIATURA'),
('01', 'COLEGIO MEDICO DE PERU'),
('02', 'COLEGIO QUIMICO FARMACEUTICO DEL PERU'),
('03', 'COLEGIO ODONTOLOGICO DEL PERU'),
('04', 'COLEGIO DE BIOLOGOS DEL PERU'),
('05', 'COLEGIO DE OBSTETRAS DEL PERU'),
('06', 'COLEGIO DE ENFERMEROS DEL PERU'),
('07', 'COLEGIO DE TRABAJADORES SOCIALES DEL PERU'),
('08', 'COLEGIO DE PSICOLOGOS DEL PERU'),
('09', 'COLEGIO TECNOLOGO MEDICO DEL PERU'),
('10', 'COLEGIO DE NUTRICIONISTAS DEL PERU'),
('11', 'COLEGIO MEDICO VETERINARIO DEL PERU'),
('12', 'COLEGIO DE INGENIEROS DEL PERU');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_CONDICION_CONTRATO`
--

CREATE TABLE `MAESTRO_HIS_CONDICION_CONTRATO` (
  `Id_Condicion` int(11) NOT NULL,
  `Descripcion_Condicion` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_CONDICION_CONTRATO`
--

INSERT INTO `MAESTRO_HIS_CONDICION_CONTRATO` (`Id_Condicion`, `Descripcion_Condicion`) VALUES
(1, 'NOMBRADO'),
(10, 'OTRA ENTIDAD'),
(2, 'CONTRATADO'),
(3, 'SERUM'),
(4, 'RESIDENTE'),
(5, 'INTERNO'),
(6, 'ALUMNO'),
(7, 'AGENTE COMUNITARIO'),
(8, 'OTROS'),
(9, 'DESTACADO');

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

--
-- Volcado de datos para la tabla `MAESTRO_HIS_ETNIA`
--

INSERT INTO `MAESTRO_HIS_ETNIA` (`Id_Etnia`, `Descripcion_Etnia`) VALUES
('01', 'ACHUAR'),
('02', 'AIMARA'),
('03', 'AMAHUACA'),
('04', 'ARABELA'),
('05', 'ASHANINKA'),
('06', 'ASHENINKA'),
('07', 'AWAJÚN'),
('08', 'BORA'),
('09', 'CAPANAHUA'),
('10', 'CASHINAHUA'),
('11', 'CHAMICURO'),
('12', 'CHAPRA'),
('13', 'CHITONAHUA'),
('14', 'ESE EJA'),
('15', 'HARAKBUT'),
('16', 'IKITU'),
('17', 'IÑAPARI'),
('18', 'ISCONAHUA'),
('19', 'JAQARU'),
('20', 'JÍBARO'),
('21', 'KAKATAIBO'),
('22', 'KAKINTE'),
('23', 'KANDOZI'),
('24', 'KICHWA'),
('25', 'KUKAMA KUKAMIRIA'),
('26', 'MADIJA'),
('27', 'MAIJUNA'),
('28', 'MARINAHUA'),
('29', 'MASHCO PIRO'),
('30', 'MASTANAHUA'),
('31', 'MATSÉS'),
('32', 'MATSIGENKA'),
('33', 'MUNICHE'),
('34', 'MURUI-MUINANI'),
('35', 'NAHUA'),
('36', 'NANTI'),
('37', 'NOMATSIGENGA'),
('38', 'OCAINA'),
('39', 'OMAGUA'),
('40', 'QUECHUAS'),
('41', 'RESÍGARO'),
('42', 'SECOYA'),
('43', 'SHARANAHUA'),
('44', 'SHAWI'),
('45', 'SHIPIBO-KONIBO'),
('46', 'SHIWILU'),
('47', 'TIKUNA'),
('48', 'URARINA'),
('49', 'URO'),
('50', 'VACACOCHA'),
('51', 'WAMPIS'),
('52', 'YAGUA'),
('53', 'YAMINAHUA'),
('54', 'YANESHA'),
('55', 'YINE'),
('56', 'AFROPERUANO'),
('57', 'BLANCO'),
('58', 'MESTIZO'),
('59', 'ASIATICODESCENDIENTE'),
('60', 'OTRO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_FINANCIADOR`
--

CREATE TABLE `MAESTRO_HIS_FINANCIADOR` (
  `Id_Financiador` varchar(2) NOT NULL,
  `Descripcion_Financiador` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_FINANCIADOR`
--

INSERT INTO `MAESTRO_HIS_FINANCIADOR` (`Id_Financiador`, `Descripcion_Financiador`) VALUES
('1', 'USUARIO'),
('10', 'OTROS'),
('11', 'EXONERADO'),
('2', 'S.I.S'),
('3', 'ESSALUD'),
('4', 'S.O.A.T'),
('5', 'SANIDAD F.A.P'),
('6', 'SANIDAD NAVAL'),
('7', 'SANIDAD EP'),
('8', 'SANIDAD PNP'),
('9', 'PRIVADOS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_OTRA_CONDICION`
--

CREATE TABLE `MAESTRO_HIS_OTRA_CONDICION` (
  `Id_Otra_Condicion` int(11) NOT NULL,
  `Descripcion_Otra_Condicion` varchar(300) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_OTRA_CONDICION`
--

INSERT INTO `MAESTRO_HIS_OTRA_CONDICION` (`Id_Otra_Condicion`, `Descripcion_Otra_Condicion`) VALUES
(1, 'GESTANTE'),
(2, 'PUERPERA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_PAIS`
--

CREATE TABLE `MAESTRO_HIS_PAIS` (
  `Id_Pais` varchar(3) NOT NULL,
  `Descripcion_Pais` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_PAIS`
--

INSERT INTO `MAESTRO_HIS_PAIS` (`Id_Pais`, `Descripcion_Pais`) VALUES
('PER', 'PERU'),
('BOL', 'BOLIVIA'),
('BRA', 'BRASIL'),
('CHL', 'CHILE'),
('COL', 'COLOMBIA'),
('ECU', 'ECUADOR'),
('VEN', 'VENEZUELA'),
('ARG', 'ARGENTINA'),
('OTR', 'OTROS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MAESTRO_HIS_PROFESION`
--

CREATE TABLE `MAESTRO_HIS_PROFESION` (
  `Id_Profesion` varchar(2) NOT NULL,
  `Descripcion_Profesion` varchar(150) DEFAULT NULL,
  `Id_Colegio` varchar(2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `MAESTRO_HIS_PROFESION`
--

INSERT INTO `MAESTRO_HIS_PROFESION` (`Id_Profesion`, `Descripcion_Profesion`, `Id_Colegio`) VALUES
('01', 'MEDICO GENERAL', '01'),
('02', 'MEDICO NEUMOLO;', '01'),
('03', 'MEDICO CARDIOLO;', '01'),
('04', 'MEDICO NEUROLO;', '01'),
('05', 'MEDICO GASTROENTEROLO;', '01'),
('06', 'MEDICO DERMATOLO;', '01'),
('07', 'MEDICO NEFROLO;', '01'),
('08', 'MEDICO ONCOLO;', '01'),
('09', 'MEDICO PSIQUIATRA', '01'),
('10', 'MEDICO CIRUJANO GENERAL', '01'),
('11', 'MEDICO TRAUMATOLO; ORTOPEDISTA', '01'),
('12', 'MEDICO OTORRINOLARIN;LO;', '01'),
('13', 'MEDICO OFTALMOLO;', '01'),
('14', 'MEDICO UROLO;', '01'),
('15', 'MEDICO CIRUJANO ONCOLO;', '01'),
('16', 'MEDICO PATOLO;', '01'),
('17', 'MEDICO OTROS CIRUGIA', '01'),
('18', 'MEDICO PEDIATRA', '01'),
('19', 'MEDICO GINECO-OBSTETRA', '01'),
('20', 'MEDICO EPIDEMIOLO;', '01'),
('21', 'MEDICO RADIOLO;', '01'),
('22', 'MEDICO OTRAS ESPECIALIDADES', '01'),
('23', 'OBSTETRA', '05'),
('24', 'NUTRICIONISTA', '10'),
('25', 'ODONTOLO;', '03'),
('26', 'QUIMICO FARMACEUTICO', '02'),
('27', 'RADIOTERAPEUTA', '00'),
('28', 'PSICOLO;', '08'),
('29', 'ENFERMERA (O)', '06'),
('30', 'TECNOLO; MEDICO', '09'),
('31', 'BIOLO;', '04'),
('32', 'VETERINARIO', '11'),
('33', 'ASISTENTA SOCIAL', '07'),
('34', 'TECNICOS DE SALUD', '00'),
('35', 'TECNICAS DE ENFERMERIA', '00'),
('36', 'TECNICO DE LABORATORIO', '00'),
('37', 'TECNICO RADIOLO;', '00'),
('38', 'TECNICO DENTAL', '00'),
('39', 'TECNICO SANEAMIENTO AMBIENTAL', '00'),
('40', 'AUXILIARES DE SALUD', '00'),
('41', 'OTROS TECNICOS Y AUXILIARES', '00'),
('42', 'OTROS NO ESPECIFICADOS', '00'),
('43', 'INTERNO DE MEDICINA', '00'),
('44', 'INTERNOS NO MEDICOS', '00'),
('45', 'SERUMISTA MEDICO', '01'),
('46', 'SERUMISTA ENFERMERA', '06'),
('47', 'SERUMISTA ODONTOLO;', '03'),
('48', 'SERUMISTA OBSTETRA', '05'),
('49', 'SERUMISTA SERVICIO SOCIAL', '07'),
('50', 'SERUMISTA PSICOLO;', '08'),
('51', 'MEDICO RESIDENTE', '01'),
('52', 'AGENTE COMUNITARIO', '00'),
('53', 'ESTADISTICO', '00'),
('54', 'SERUMISTA QUIMICO FARMACEUTICO', '02'),
('55', 'SERUMISTA NUTRICIONISTA', '10'),
('56', 'SERUMISTA TECNOLO; MEDICO', '09'),
('57', 'SERUMISTA BIOLO;', '04'),
('58', 'SERUMISTA MEDICO VETERINARIO', '11'),
('59', 'SERUMISTA INGENIERO SANITARIO', '12');

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
-- Volcado de datos para la tabla `MAESTRO_HIS_TIPO_DOC`
--

INSERT INTO `MAESTRO_HIS_TIPO_DOC` (`Id_Tipo_Documento`, `Abrev_Tipo_Doc`, `Descripcion_Tipo_Documento`) VALUES
(1, 'DNI', 'Documento Nacional de Identidad'),
(2, 'CE', 'Carnet de Extranjería'),
(3, 'PASS', 'Pasaporte'),
(4, 'DIE', 'Documento de Identidad Extranjero'),
(5, 'S/ DOCUMENTO', 'Sin Documento'),
(6, 'CNV', 'Certificado de Nacido Vivo');

-- --------------------------------------------------------

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

INSERT INTO `MAESTRO_HIS_UPS` (`Id_Ups`, `Descripcion_Ups`) VALUES
('101002', 'SALUD AMBIENTAL'),
('101701', 'SERVICIOS SOCIAL'),
('110000', 'FARMACIA'),
('140000', 'MEDICINA NUCLEAR'),
('300101', 'ANESTESIOLOGIA'),
('300102', 'TERAPIA DEL DOLOR'),
('300201', 'CARDIOLOGIA'),
('300202', 'CARDIOLOGIA INTERVENCIONISTA'),
('300203', 'CARDIOLOGIA NUCLEAR'),
('300204', 'ECOCARDIOGRAFÍA'),
('300205', 'HEMODINAMIA'),
('300206', 'VALORACION RIESGO CARDIOVASCULAR'),
('300301', 'CIRUGIA'),
('300302', 'CIRUGIA LAPAROSCOPICA'),
('300303', 'CIRUGIA EN CONSULTORIO EXTERNO / TOPICO'),
('300401', 'CIRUGÍA DE CABEZA Y CUELLO Y MAXILOFACIAL'),
('300402', 'CIRUGÍA ORAL Y MAXILO FACIAL'),
('300403', 'CIRUGÍA ONCOLÓGICA DE CABEZA Y CUELLO Y MAXILO FACIAL'),
('300501', 'CIRUGIA ONCOLOGICA'),
('300502', 'CIRUGIA ONCOLOGICA DE MAMAS TEJIDOS BLANDOS Y PIEL'),
('300503', 'CIRUGIA ONCOLOGICA ABDOMINAL'),
('300504', 'CIRUGIA TORAXICA ONCOLOGICA'),
('300505', 'CIRUGIA PLASTICA RECONSTRUCTIVA ONCOLOGICA'),
('300506', 'NEUROCIRUGIA ONCOLOGICA'),
('300507', 'TRAUMATOLOGIA ONCOLOGICA'),
('300601', 'CIRUGIA ESTÉTICA'),
('300602', 'CIRUGIA RECONSTRUCTIVA'),
('300701', 'CIRUGÍA PEDIÁTRICA'),
('300801', 'CIRUGÍA CARDIOVASCULAR'),
('300802', 'CIRUGÍA CARDIOVASCULAR PEDIÁTRICA'),
('300803', 'CIRUGÍA TORACICA Y CARDIOVASCULAR'),
('300804', 'CIRUGIA TORAXICA / NEUMOLOGICA'),
('300805', 'CIRUGÍA VASCULAR Y ANGIOLOGIA'),
('300901', 'DERMATOLOGÍA'),
('300902', 'DERMATOLOGIA ESTETICA'),
('300903', 'DERMATOLOGÍA PEDIÁTRICA'),
('300904', 'DERMATOPATOLOGIA'),
('301001', 'ENDOCRINOLOGÍA'),
('301101', 'ENFERMEDADES INFECCIOSAS / INFECTOLOGÍA'),
('301102', 'ENFERMEDADES METAXENICAS Y OTRAS TRANSMITIDAS POR VECTORES'),
('301103', 'ENFERMEDADES ZOONOTICAS'),
('301104', 'MEDICINA TROPICAL'),
('301201', 'CONSEJERIA'),
('301202', 'CRECIMIENTO Y DESARROLLO'),
('301203', 'ENFERMERIA'),
('301204', 'INMUNIZACIONES'),
('301301', 'ENDOSCOPIA DIGESTIVA'),
('301302', 'GASTROENTEROLOGÍA'),
('301303', 'HEPATOLOGIA'),
('301304', 'HEMORRAGIA DIGESTIVA'),
('301305', 'PANCREAS'),
('301401', 'GENÉTICA MEDICA'),
('301501', 'ATENCION INTEGRAL DEL ADULTO MAYOR'),
('301502', 'GERIATRÍA'),
('301503', 'TAYTA WASI'),
('301601', 'ATENCION PRECONCEPCIONAL'),
('301602', 'CLIMATERIO'),
('301603', 'COLPOSCOPIA'),
('301604', 'ECOGRAFIA GINECO-OBSTETRICA'),
('301605', 'GINECOLOGIA'),
('301606', 'GINECOLOGIA ONCOLOGICA'),
('301607', 'GINECOLOGÍA Y OBSTETRICIA'),
('301608', 'GINECOLOGIA Y OBSTETRICIA DE LA NIÑA Y LA ADOLESCENTE'),
('301609', 'INFERTILIDAD'),
('301610', 'MEDICINA FETAL'),
('301611', 'MONITOREO FETAL'),
('301612', 'PLANIFICACION FAMILIAR'),
('301613', 'MATERNO PERINATAL'),
('301701', 'HEMATOLOGÍA CLINICA'),
('301801', 'INMUNOLOGÍA Y ALERGIA'),
('301901', 'ACUPUNTURA Y AFINES'),
('301902', 'MEDICINA ALTERNATIVA Y COMPLEMENTARIA'),
('301903', 'MEDICINA CUERPO MENTE'),
('301904', 'MEDICINA ENERGETICA'),
('301905', 'MEDICINA NATURAL'),
('301906', 'TERAPIAS MANUALES'),
('302001', 'MEDICINA DEL DEPORTE'),
('302101', 'ATENCION EN SALUD FAMILIAR Y COMUNITARIA'),
('302102', 'MEDICINA DE FAMILIA'),
('302201', 'MEDICINA REHABILITACION'),
('302202', 'REHABILITACION CARDIORRESPIRATORIA'),
('302203', 'REHABILITACION DE LA UNIDAD MOTORA Y DOLOR'),
('302204', 'REHABILITACION DE TRASTORNOS SENSORIALES'),
('302205', 'REHABILITACION EN AMPUTADOS / QUEMADOS Y TRASTORNOS POSTURALES'),
('302206', 'REHABILITACION EN APRENDIZAJE'),
('302207', 'REHABILITACION EN COMUNICACIÓN'),
('302208', 'REHABILITACION EN DESARROLLO PSICOMOTOR'),
('302209', 'REHABILITACION EN LESIONES CENTRALES'),
('302210', 'REHABILITACION EN LESIONES MEDULARES'),
('302211', 'REHABILITACION EN RETARDO MENTAL Y ADAPTACION SOCIAL'),
('302301', 'ATENCION BASICA PARA ENFERMEDADES NO TRANSMISIBLES'),
('302302', 'ATENCION INTEGRAL'),
('302303', 'MEDICINA GENERAL'),
('302304', 'ATENCION INTEGRAL DEL ADOLESCENTE'),
('302305', 'SALUD ESCOLAR'),
('302401', 'MEDICINA INTERNA'),
('302501', 'MEDICINA OCUPACIONAL Y DEL MEDIO AMBIENTE'),
('302601', 'MEDICINA ONCOLOGICA'),
('302701', 'NEFROLOGÍA'),
('302801', 'CONSULTORIO ASMA'),
('302802', 'CONSULTORIO CONTROL TUBERCULOSIS'),
('302803', 'NEUMOLOGÍA'),
('302901', 'CIRUGIA DE COLUMNA Y NERVIOS PERIFERICOS'),
('302902', 'CIRUGIA VASCULAR'),
('302903', 'NEUROCIRUGIA'),
('302904', 'NEUROCIRUGIA DE BASE DE CRANEO'),
('302905', 'NEUROCIRUGIA FUNCIONAL'),
('303001', 'ENFERMEDADES CEREBROVASCULARES'),
('303002', 'ENFERMEDADES CONDUCTUALES'),
('303003', 'ENFERMEDADES DEGENERATIVAS'),
('303004', 'ENFERMEDADES INFECCIOSAS DEL SISTEMA NERVIOSO CENTRAL'),
('303005', 'EPILEPTOLOGÍA'),
('303006', 'NEUROFISIOLOGIA'),
('303007', 'NEUROINTENSIVISMO'),
('303008', 'NEUROLOGIA'),
('303009', 'NEURORADIOLOGIA'),
('303101', 'NUTRICION'),
('303102', 'VALORACION ANTROPOMETRICA'),
('303201', 'ATENCION GESTANTES Y PUERPERAS'),
('303202', 'CONSEJERIA PLANIFICACION FAMILIAR'),
('303203', 'OBSTETRICIA'),
('303204', 'PSICOPROFILAXIS'),
('303301', 'OPERATORIA DENTAL'),
('303302', 'ENDODONCIA'),
('303303', 'PERIODONCIA'),
('303304', 'ODONTOLOGIA GENERAL'),
('303305', 'ODONTOLOGIA PEDIATRICA'),
('303306', 'CIRUGIA BUCO MAXILOFACIAL'),
('303307', 'ORTODONCIA Y ORTOPEDIA DE LOS MAXILARES'),
('303308', 'ODONTOLOGIA PREVENTIVA'),
('303309', 'REHABILITACION ORAL'),
('303310', 'RADIOLOGIA ORAL'),
('303311', 'MEDICINA Y PATOLOGIA ESTOMATOLOGICA'),
('303312', 'ATENCION ODONTOLOGICA DE PACIENTES ESPECIALES'),
('303401', 'ATENCION BASICA SALUD OCULAR'),
('303402', 'ECOGRAFIA Y TOMOGRAFIA OPTICA OCULAR'),
('303403', 'ENFERMEDADES EXTERNAS CORNEA Y CIRUGIA REFRACTARIA'),
('303404', 'ESTRABISMO'),
('303405', 'GLAUCOMA'),
('303406', 'NEUROFTALMOLOGÍA Y BAJA VISION'),
('303407', 'OCULOPLASTIA'),
('303408', 'OFTALMOLOGIA'),
('303409', 'OFTALMOLOGÍA PEDIÁTRICA Y ESTRABOLOGIA'),
('303410', 'ONCOLOGIA OCULAR'),
('303411', 'RETINA Y VITRIO'),
('303412', 'ÚVEA'),
('303501', 'ARTROSCOPIA'),
('303502', 'CIRUGÍA DE MANO'),
('303503', 'CIRUGIA TRAUMATOLOGICA DE COLUMNA'),
('303504', 'CIRUGIA TRAUMATOLOGICA DE HOMBRO Y CODO'),
('303505', 'CIRUGIA TRAUMATOLOGICA DE TOBILLO Y PIE'),
('303506', 'FIJACION EXTERNA'),
('303507', 'ORTOPEDIA'),
('303508', 'ORTOPEDIA INFANTIL'),
('303509', 'PROTESIS DE CADERA Y RODILLA'),
('303510', 'TRAUMATOLOGIA'),
('303601', 'LARINGOLOGIA'),
('303602', 'OTOLOGIA'),
('303603', 'OTORRINOLARINGOLOGIA'),
('303604', 'OTORRINOLOGIA PEDIATRICA'),
('303605', 'RINOLOGÍA'),
('303701', 'CARDIOLOGIA PEDIATRICA'),
('303702', 'CIRUGIA NEONATAL'),
('303703', 'ENDOCRINOLOGIA PEDIATRICA'),
('303704', 'GASTROENTEROLOGIA PEDIATRICA'),
('303705', 'INFECTOLOGIA PEDIATRICA'),
('303706', 'NEFROLOGIA PEDIATRICA'),
('303707', 'NEONATOLOGÍA'),
('303708', 'NEUMOLOGIA PEDIATRICA'),
('303709', 'NEUROLOGIA PEDIATRICA'),
('303710', 'ONCOLOGIA PEDIATRICA'),
('303711', 'OTORRINOLOGIA NEONATAL'),
('303712', 'PEDIATRIA'),
('303713', 'ATENCION INTEGRAL DEL NINO'),
('303801', 'PREVENCION DE ADICCIONES'),
('303802', 'PSICOLOGIA'),
('303803', 'PSICOLOGIA GRUPAL'),
('303804', 'PSICOLOGIA POR ETAPAS VIDA'),
('303805', 'VIOLENCIA'),
('303901', 'PSIQUIATRIA'),
('303902', 'PSIQUIATRIA DE NIÑOS Y ADOLESCENTES'),
('303903', 'PSIQUIATRIA EN ADICCIONES'),
('304001', 'REUMATOLOGIA'),
('304002', 'REUMATOLOGIA PEDIATRICA'),
('304101', 'UROLOGIA'),
('304102', 'UROLOGIA ONCOLOGICA'),
('304103', 'UROLOGIA PEDIATRICA'),
('370101', 'MEDICINA EN EMERGENCIAS Y DESASTRES'),
('370102', 'ATENCION AMBULATORIA EN EMERGENCIAS Y DESASTRES'),
('370103', 'ATENCION PSICOLOGICA Y SALUD MENTAL EN EMERGENCIAS Y DESASTRES'),
('370104', 'PROMOCION Y PREVENCION EN EMERGENCIAS Y DESASTRES'),
('400101', 'FONIATRÍA'),
('410101', 'DENSITOMETRÍA ÓSEA'),
('410102', 'MAMOGRAFÍA'),
('410103', 'RADIOLOGÍA CONVENCIONAL'),
('410104', 'RADIOLOGÍA ESPECIALIZADA'),
('410105', 'RADIOLOGÍA INTERVENCIONISTA'),
('410106', 'RESONANCIA MAGNÉTICA'),
('410107', 'TOMOGRAFÍA COMPUTADA'),
('410108', 'ULTRASONOGRAFÍA'),
('420101', 'BIOQUÍMICA'),
('420102', 'HEMATOLOGÍA'),
('420103', 'INMUNOLOGÍA'),
('420105', 'MICROBIOLOGÍA  Y PARASITOLOGÍA'),
('430101', 'CITOLOGÍA'),
('430103', 'GÉNETICA'),
('430104', 'INMUNOHISTOQUÍMICA'),
('430105', 'NECROPSIAS'),
('430106', 'PATOLOGÍA DE ESPECÍMENES QUIRÚRGICOS'),
('450103', 'FRACCIONAMIENTO DE HEMODERIVADOS'),
('450104', 'HEMOTERAPIA'),
('450105', 'PROMOCIÓN DE DONACIÓN VOLUNTARIA'),
('450106', 'TAMIZAJE'),
('460101', 'DIÁLISIS PERITONEAL'),
('460102', 'HEMODIÁLISIS'),
('470101', 'NUTRICIÓN Y DIETÉTICA'),
('470102', 'TERAPIA ENTERAL NO ESPECIALIZADA'),
('490101', 'BRAQUITERAPIA'),
('490102', 'DOSIMETRÍA'),
('490103', 'RADIOTERAPIA'),
('490104', 'TELETERAPIA'),
('500102', 'GAMMAGRAFÍA'),
('999999', 'UPS NO REGISTRADA');

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
(1, 'admin', '$2a$12$oyHlo6Q4WLAz05diONMFx.D93QR915rs.iVOZx7K1wUpgiTI7CtB.', 'Administrador del Sistema', 'admin', 1, '2026-06-14 12:35:48', '2026-07-20 19:17:17'),
(2, '41132134', '$2y$10$0JLq5iaw1SA1PizLCQN5ouhm76c7b.SMDV8R/MSmVe.iqmwdJLpe.', 'MARCO ANTONIO ESPINOZA VALERIO', 'usuario', 1, '2026-07-20 19:13:56', '2026-07-20 19:18:35');

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
  MODIFY `id_observacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_FED_AVANCE`
--
ALTER TABLE `CONVENIO_FED_AVANCE`
  MODIFY `id_avance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_FED_INDICADORES`
--
ALTER TABLE `CONVENIO_FED_INDICADORES`
  MODIFY `id_indicador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_GESTION_AVANCE`
--
ALTER TABLE `CONVENIO_GESTION_AVANCE`
  MODIFY `id_avance` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CONVENIO_GESTION_INDICADORES`
--
ALTER TABLE `CONVENIO_GESTION_INDICADORES`
  MODIFY `id_indicador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `IMPORT_ESTADO`
--
ALTER TABLE `IMPORT_ESTADO`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
