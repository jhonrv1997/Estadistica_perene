-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql102.infinityfree.com
-- Tiempo de generación: 15-06-2026 a las 22:02:05
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
(1, 'MaestroRegistrador', 0, NULL, NULL, NULL, NULL, 0),
(2, 'MaestroPersonal', 0, NULL, NULL, NULL, NULL, 0),
(3, 'MaestroPaciente', 0, NULL, NULL, NULL, NULL, 0),
(4, 'NominalTrama', 0, NULL, NULL, NULL, NULL, 0);

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

--
-- Volcado de datos para la tabla `LOG_IMPORTACION`
--

INSERT INTO `LOG_IMPORTACION` (`id_log`, `tipo_operacion`, `tipo_archivo`, `nombre_archivo`, `tabla_destino`, `periodo_mes`, `periodo_anio`, `registros_procesados`, `modo_importacion`, `fecha_operacion`, `usuario`, `estado`, `mensaje`, `duracion_segundos`) VALUES
(1, 'IMPORT', 'MaestroPersonal', 'MaestroPersonal207149.zip', 'MAESTRO_PERSONAL', NULL, NULL, 3475, 'REEMPLAZO', '2026-06-15 18:28:04', 'admin', 'EXITO', 'Se importaron 3475 registros correctamente', '0.30'),
(2, 'IMPORT', 'MaestroRegistrador', 'MaestroRegistrador2011418.zip', 'MAESTRO_REGISTRADOR', NULL, NULL, 1251, 'REEMPLAZO', '2026-06-15 18:28:24', 'admin', 'EXITO', 'Se importaron 1251 registros correctamente', '0.05'),
(3, 'IMPORT', 'MaestroPaciente', 'MaestroPaciente2011644.zip', 'MAESTRO_PACIENTE', NULL, NULL, 229559, 'REEMPLAZO', '2026-06-15 18:29:24', 'admin', 'EXITO', 'Se importaron 229559 registros correctamente', '28.25'),
(4, 'IMPORT', 'NominalTrama', 'NominalTrama15052026_201222.zip', 'NOMINAL_TRAMA_NUEVO', '05', '2026', 84489, 'PERIODO', '2026-06-15 18:30:25', 'admin', 'EXITO', 'Se importaron 84489 registros correctamente', '25.34'),
(5, 'IMPORT', 'NominalTrama', 'NominalTrama15062026_201415.zip', 'NOMINAL_TRAMA_NUEVO', '06', '2026', 20969, 'PERIODO', '2026-06-15 18:31:03', 'admin', 'EXITO', 'Se importaron 20969 registros correctamente', '7.30'),
(6, 'PROCESS', 'CONSOLIDADO', NULL, 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO', NULL, NULL, 105458, 'COMPLETO', '2026-06-15 18:31:37', 'admin', 'EXITO', 'Procesamiento ejecutado. Tablas fuente: Trama=105,458, Personal=3,475, Paciente=229,559, Registrador=1,251 | Periodos procesados (2): 5/2026: 84,489, 6/2026: 20,969. Registros consolidados: 105458', '10.21');

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
  `rol` varchar(20) NOT NULL DEFAULT 'admin',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `USUARIOS`
--

INSERT INTO `USUARIOS` (`id_usuario`, `usuario`, `password`, `nombre_completo`, `rol`, `estado`, `fecha_creacion`, `ultimo_acceso`) VALUES
(1, 'admin', '$2a$12$oyHlo6Q4WLAz05diONMFx.D93QR915rs.iVOZx7K1wUpgiTI7CtB.', 'Administrador del Sistema', 'admin', 1, '2026-06-14 12:35:48', '2026-06-15 18:27:21');

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
-- Índices para tablas volcadas
--

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
-- AUTO_INCREMENT de la tabla `IMPORT_ESTADO`
--
ALTER TABLE `IMPORT_ESTADO`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `LOG_IMPORTACION`
--
ALTER TABLE `LOG_IMPORTACION`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `NOMINAL_TRAMA_PERIODOS`
--
ALTER TABLE `NOMINAL_TRAMA_PERIODOS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `USUARIOS`
--
ALTER TABLE `USUARIOS`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
