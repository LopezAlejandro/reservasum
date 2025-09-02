-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 11-07-2025 a las 17:27:17
-- Versión del servidor: 11.8.2-MariaDB
-- Versión de PHP: 8.4.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `aula_reservas`
--
CREATE DATABASE IF NOT EXISTS `aula_reservas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `aula_reservas`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `feriados`
--

CREATE TABLE `feriados` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `feriados`
--

INSERT INTO `feriados` (`id`, `fecha`, `descripcion`) VALUES
(1, '2025-07-09', 'Día de la independencia'),
(2, '2025-08-15', 'Paso a la Inmortalidad del Gral. José de San Martín.'),
(3, '2025-11-21', 'Día no laborable con fines turísticos.'),
(4, '2025-11-24', 'Día de la Soberanía Nacional'),
(5, '2025-12-08', 'Inmaculada Concepción de María.'),
(6, '2025-12-25', 'Navidad.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL DEFAULT 'default',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `motivo` text NOT NULL,
  `cantidad_asistentes` int(11) NOT NULL,
  `cargo_solicitante` varchar(100) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `estado` enum('pendiente','confirmada','rechazada','cancelada') DEFAULT 'pendiente',
  `comentario` text DEFAULT NULL,
  `bibliografia_archivo` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `nombre`, `email`, `fecha`, `hora_inicio`, `hora_fin`, `motivo`, `cantidad_asistentes`, `cargo_solicitante`, `carrera`, `telefono`, `estado`, `comentario`, `bibliografia_archivo`, `creado_en`) VALUES
(2, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-11', '10:00:00', '15:30:00', 'varias', 20, 'Personal Administrativo', 'Biblioteca', '36599872', 'confirmada', 'nada', NULL, '2025-07-04 15:12:00'),
(3, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-10', '16:30:00', '19:00:00', 'Otras', 5, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'porque si', NULL, '2025-07-04 15:24:59'),
(4, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-10', '12:30:00', '16:00:00', 'saasd', 5, 'Personal Administrativo', 'Biblioteca', '36599872', 'confirmada', 'wwsd', NULL, '2025-07-04 17:54:17'),
(5, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-16', '10:30:00', '13:00:00', 'dsadsa', 3, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'sadss', NULL, '2025-07-07 14:12:34'),
(6, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-11', '16:30:00', '20:00:00', 'gfdgfdg', 5, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'dfsgefd', NULL, '2025-07-07 15:25:46'),
(7, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-14', '10:00:00', '12:30:00', 'fgfds', 7, 'Personal Administrativo', 'Biblioteca', '36599872', 'confirmada', 'perque si', NULL, '2025-07-07 16:57:02'),
(8, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-14', '13:30:00', '18:30:00', 'gfhg', 8, 'Personal Administrativo', 'Biblioteca', '0111565318825', 'rechazada', 'no te queremos', NULL, '2025-07-07 17:27:20'),
(9, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-16', '11:30:00', '14:00:00', 'sdadsx', 7, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'sdsadas', NULL, '2025-07-08 14:14:50'),
(10, 'Alejandro Lopez', 'alopez@fadu.uba.ar', '2025-07-18', '10:30:00', '14:00:00', 'Prueba del mail', 34, 'Personal Administrativo', 'b', '36599872', 'confirmada', '', NULL, '2025-07-08 14:57:50'),
(11, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-15', '10:00:00', '15:00:00', 'prueba 2 del mail', 5, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'probando rechazo', NULL, '2025-07-08 15:12:17'),
(12, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-16', '11:30:00', '16:00:00', 'Prueba 3 del mail', 4, 'Personal Administrativo', 'Biblioteca', '36599872', 'confirmada', 'testeando', NULL, '2025-07-08 15:23:44'),
(13, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-17', '11:30:00', '16:00:00', 'Prueba 4 de mail', 4, 'Personal Administrativo', 'Biblioteca', '36599872', 'confirmada', 'prueba 4', NULL, '2025-07-08 15:29:36'),
(14, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-15', '12:30:00', '17:00:00', 'rrrt', 6, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'asdq', 'uploads/686d466665976_country_list.txt', '2025-07-08 16:25:10'),
(15, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-21', '09:30:00', '15:00:00', 'test', 4, 'Personal Administrativo', 'Biblioteca', '36599872', 'rechazada', 'wwqs', NULL, '2025-07-08 16:42:17'),
(16, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-15', '12:00:00', '16:30:00', 'dfgfdsf', 8, 'Personal Administrativo', 'Biblioteca', '36599872', 'confirmada', '', NULL, '2025-07-08 16:54:05'),
(17, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-21', '10:30:00', '15:00:00', 'prueba', 6, 'Personal Administrativo', 'Biblioteca', '36599872', 'pendiente', NULL, NULL, '2025-07-10 16:15:58'),
(18, 'Alejandro Lopez', 'lopalejandro@gmail.com', '2025-07-22', '15:30:00', '20:00:00', 'sdfdf', 9, 'Personal Administrativo', 'Biblioteca', '36599872', 'pendiente', NULL, NULL, '2025-07-10 17:12:47');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `feriados`
--
ALTER TABLE `feriados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `feriados`
--
ALTER TABLE `feriados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
