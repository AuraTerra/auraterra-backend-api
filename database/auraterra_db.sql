-- Creación de la Base de Datos para AuraTerra
CREATE DATABASE IF NOT EXISTS `auraterra_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `auraterra_db`;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de Registros Telemétricos / Clima
CREATE TABLE IF NOT EXISTS `registros_clima` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ciudad` VARCHAR(100) NOT NULL,
  `temperatura` DECIMAL(5,2) NOT NULL,
  `humedad` INT NOT NULL,
  `condicion` VARCHAR(100) NOT NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;