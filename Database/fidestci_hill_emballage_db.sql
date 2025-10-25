-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : sam. 25 oct. 2025 à 16:15
-- Version du serveur : 10.11.14-MariaDB-cll-lve
-- Version de PHP : 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `fidestci_hill_emballage_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `entity` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `details`, `ip`, `user_agent`, `port`, `created_at`) VALUES
(1, 1, 'LOGOUT', NULL, NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 32556, '2025-10-23 10:13:37'),
(2, 2, 'LOGIN', NULL, NULL, '{\"email\":\"adminkevin\",\"status\":\"success\"}', '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 30144, '2025-10-23 10:26:48'),
(3, 2, 'LOGOUT', NULL, NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 30144, '2025-10-23 10:28:07'),
(4, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrich\",\"status\":\"success\"}', '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 30144, '2025-10-23 10:28:14'),
(5, 3, 'UPDATE', 'users', 3, '{\"field\":\"profile_photo\"}', '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 30144, '2025-10-23 10:29:11'),
(6, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 30144, '2025-10-23 10:29:43'),
(7, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 32085, '2025-10-23 10:33:58'),
(8, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 32085, '2025-10-23 10:34:08'),
(9, 2, 'LOGIN', NULL, NULL, '{\"email\":\"adminkevin\",\"status\":\"success\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16291, '2025-10-23 13:58:09'),
(10, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16298, '2025-10-23 13:59:04'),
(11, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16304, '2025-10-23 14:00:48'),
(12, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16312, '2025-10-23 14:01:33'),
(13, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16312, '2025-10-23 14:01:41'),
(14, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16319, '2025-10-23 14:02:15'),
(15, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrich\",\"status\":\"success\"}', '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 31962, '2025-10-23 14:06:58'),
(16, 2, 'CREATE', 'products', 3, '{\"code\":\"ALIM001\",\"nom\":\"ALUMINIUM FOIL 50M\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16344, '2025-10-23 14:07:51'),
(17, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16344, '2025-10-23 14:07:51'),
(18, 2, 'CREATE', 'products', 4, '{\"code\":\"ALHT002\",\"nom\":\"ALUMINIUM SANITEX 50M\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16355, '2025-10-23 14:10:01'),
(19, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16355, '2025-10-23 14:10:01'),
(20, 2, 'CREATE', 'products', 5, '{\"code\":\"ALHT003\",\"nom\":\"ALUMINIUM SANITEX 200M\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16358, '2025-10-23 14:10:40'),
(21, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16358, '2025-10-23 14:10:40'),
(22, 2, 'CREATE', 'products', 6, '{\"code\":\"ALIM004\",\"nom\":\"ALUMINIUM FOIL100M\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16366, '2025-10-23 14:11:56'),
(23, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16366, '2025-10-23 14:11:56'),
(24, 2, 'VIEW', 'stock_entries', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16369, '2025-10-23 14:12:03'),
(25, 2, 'CREATE', 'stock_entries', NULL, '{\"produit_id\":3,\"depot_id\":1,\"q\":648,\"type\":\"achat\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16374, '2025-10-23 14:13:50'),
(26, 2, 'VIEW', 'stock_entries', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16374, '2025-10-23 14:13:50'),
(27, 2, 'CREATE', 'stock_entries', NULL, '{\"produit_id\":5,\"depot_id\":1,\"q\":48,\"type\":\"achat\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16379, '2025-10-23 14:14:44'),
(28, 2, 'VIEW', 'stock_entries', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16379, '2025-10-23 14:14:44'),
(29, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16379, '2025-10-23 14:14:52'),
(30, 2, 'VIEW', 'stock_adjustments', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16265, '2025-10-23 14:18:20'),
(31, 2, 'CREATE', 'stock_adjustments', NULL, '{\"produit_id\":3,\"depot_id\":1,\"delta\":10,\"motif\":\"correction\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16271, '2025-10-23 14:19:18'),
(32, 2, 'VIEW', 'stock_adjustments', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16271, '2025-10-23 14:19:18'),
(33, 2, 'VIEW', 'products', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16271, '2025-10-23 14:19:21'),
(34, 2, 'VIEW', 'ventes', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16287, '2025-10-23 14:21:07'),
(35, 2, 'VIEW', 'ventes', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16298, '2025-10-23 14:21:25'),
(36, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29297, '2025-10-23 14:35:50'),
(37, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:36:54'),
(38, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:37:06'),
(39, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:37:16'),
(40, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:38:00'),
(41, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:38:22'),
(42, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:38:25'),
(43, 3, 'VIEW', 'products', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:38:48'),
(44, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:39:01'),
(45, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:39:23'),
(46, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:39:58'),
(47, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:40:57'),
(48, 3, 'CREATE', 'stock_adjustments', NULL, '{\"produit_id\":5,\"depot_id\":1,\"delta\":10,\"motif\":\"correction\"}', '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:41:30'),
(49, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:41:30'),
(50, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:41:47'),
(51, 3, 'CREATE', 'stock_adjustments', NULL, '{\"produit_id\":5,\"depot_id\":1,\"delta\":-10,\"motif\":\"correction\"}', '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:42:11'),
(52, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:42:11'),
(53, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.252.212', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 29250, '2025-10-23 14:42:17'),
(54, NULL, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira\",\"status\":\"failed\"}', '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 1415, '2025-10-23 15:52:24'),
(55, NULL, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira\",\"status\":\"failed\"}', '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 1415, '2025-10-23 15:52:45'),
(56, NULL, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira\",\"status\":\"failed\"}', '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 1415, '2025-10-23 15:53:07'),
(57, NULL, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira\",\"status\":\"failed\"}', '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 1415, '2025-10-23 15:53:47'),
(58, 6, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira123\",\"status\":\"success\"}', '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 1415, '2025-10-23 15:54:31'),
(59, 6, 'VIEW', 'ventes', NULL, NULL, '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 1665, '2025-10-23 15:56:27'),
(60, 2, 'LOGIN', NULL, NULL, '{\"email\":\"adminkevin\",\"status\":\"success\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16282, '2025-10-23 15:56:43'),
(61, 2, 'VIEW', 'ventes', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 16285, '2025-10-23 15:56:59'),
(62, 6, 'VIEW', 'ventes', NULL, NULL, '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 5003, '2025-10-23 15:58:32'),
(63, 6, 'VIEW', 'ventes', NULL, NULL, '102.209.218.226', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 5006, '2025-10-23 15:58:56'),
(64, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46976, '2025-10-23 20:27:16'),
(65, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrich\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46610, '2025-10-24 04:08:18'),
(66, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:08:23'),
(67, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:08:42'),
(68, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:08:48'),
(69, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:08:55'),
(70, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:09:10'),
(71, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:09:18'),
(72, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:10:00'),
(73, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:10:03'),
(74, 3, 'CREATE', 'stock_adjustments', NULL, '{\"produit_id\":2,\"depot_id\":1,\"delta\":-100,\"motif\":\"correction\"}', '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:10:23'),
(75, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 47214, '2025-10-24 04:10:23'),
(76, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrich\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47790, '2025-10-24 04:18:58'),
(77, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:29'),
(78, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:33'),
(79, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:36'),
(80, 3, 'DELETE', 'products', 2, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:38'),
(81, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:38'),
(82, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:41'),
(83, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:51'),
(84, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:52'),
(85, 3, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46718, '2025-10-24 04:28:54'),
(86, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46972, '2025-10-24 04:54:39'),
(87, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46972, '2025-10-24 04:54:58'),
(88, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46972, '2025-10-24 04:55:09'),
(89, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46972, '2025-10-24 04:55:12'),
(90, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46972, '2025-10-24 04:55:19'),
(91, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46121, '2025-10-24 05:05:47'),
(92, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46121, '2025-10-24 05:05:53'),
(93, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45466, '2025-10-24 05:06:47'),
(94, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46645, '2025-10-24 05:07:05'),
(95, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45466, '2025-10-24 05:07:35'),
(96, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46452, '2025-10-24 05:08:23'),
(97, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46452, '2025-10-24 05:08:26'),
(98, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48793, '2025-10-24 05:13:49'),
(99, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47303, '2025-10-24 05:13:59'),
(100, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48793, '2025-10-24 05:14:38'),
(101, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48793, '2025-10-24 05:14:47'),
(102, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48675, '2025-10-24 05:19:46'),
(103, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49286, '2025-10-24 05:21:25'),
(104, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49286, '2025-10-24 05:21:28'),
(105, 3, 'CREATE', 'depots', 3, '{\"nom\":\"Depot Localisé Test\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46795, '2025-10-24 05:22:07'),
(106, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46795, '2025-10-24 05:22:07'),
(107, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46795, '2025-10-24 05:22:23'),
(108, 3, 'DELETE', 'depots', 3, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46795, '2025-10-24 05:22:28'),
(109, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46795, '2025-10-24 05:22:28'),
(110, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46690, '2025-10-24 05:23:30'),
(111, 3, 'CREATE', 'depots', 4, '{\"nom\":\"Yao Ulrich Amani\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48890, '2025-10-24 05:23:52'),
(112, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48890, '2025-10-24 05:23:52'),
(113, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48890, '2025-10-24 05:23:59'),
(114, 3, 'DELETE', 'depots', 4, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48890, '2025-10-24 05:24:00'),
(115, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48890, '2025-10-24 05:24:00'),
(116, 3, 'DELETE', 'depots', 4, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48905, '2025-10-24 05:31:12'),
(117, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48905, '2025-10-24 05:31:12'),
(118, 3, 'DELETE', 'depots', 4, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48761, '2025-10-24 05:34:25'),
(119, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48761, '2025-10-24 05:34:25'),
(120, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48050, '2025-10-24 05:35:31'),
(121, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48050, '2025-10-24 05:35:54'),
(122, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48050, '2025-10-24 05:35:54'),
(123, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 48050, '2025-10-24 05:36:08'),
(124, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48050, '2025-10-24 05:36:40'),
(125, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45926, '2025-10-24 05:39:03'),
(126, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48524, '2025-10-24 05:39:07'),
(127, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:41:01'),
(128, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:41:17'),
(129, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:42:01'),
(130, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:42:01'),
(131, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:42:10'),
(132, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:42:10'),
(133, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 46287, '2025-10-24 05:42:24'),
(134, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47481, '2025-10-24 05:44:20'),
(135, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49137, '2025-10-24 05:49:23'),
(136, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49137, '2025-10-24 05:49:46'),
(137, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49137, '2025-10-24 05:49:46'),
(138, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49137, '2025-10-24 05:49:50'),
(139, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49137, '2025-10-24 05:49:50'),
(140, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49137, '2025-10-24 05:50:04'),
(141, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48181, '2025-10-24 05:56:27'),
(142, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48181, '2025-10-24 05:56:30'),
(143, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48181, '2025-10-24 05:57:06'),
(144, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48181, '2025-10-24 05:57:06'),
(145, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47004, '2025-10-24 06:13:21'),
(146, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:26:55'),
(147, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:27:00'),
(148, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:27:01'),
(149, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:27:04'),
(150, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49078, '2025-10-24 06:27:10'),
(151, 3, 'UPDATE', 'depots', 1, '{\"nom\":\"Dépôt Principal\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:28:08'),
(152, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:28:08'),
(153, 3, 'UPDATE', 'depots', 2, '{\"nom\":\"Dépôt Secondaire\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:28:36'),
(154, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:28:36'),
(155, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45500, '2025-10-24 06:29:24'),
(156, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47051, '2025-10-24 06:31:20'),
(157, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47148, '2025-10-24 06:32:41'),
(158, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47192, '2025-10-24 06:35:18'),
(159, 3, 'CREATE', 'depots', 5, '{\"nom\":\"Yao Ulrich Amani\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47834, '2025-10-24 06:36:15'),
(160, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47834, '2025-10-24 06:36:15'),
(161, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47834, '2025-10-24 06:36:37'),
(162, 3, 'DELETE', 'depots', 5, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47834, '2025-10-24 06:36:44'),
(163, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47834, '2025-10-24 06:36:44'),
(164, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47834, '2025-10-24 06:36:47'),
(165, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:38:37'),
(166, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:38:44'),
(167, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:38:47'),
(168, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:39:16'),
(169, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:39:19'),
(170, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:39:24'),
(171, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:39:35'),
(172, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:39:44'),
(173, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrich\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:39:53'),
(174, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:20'),
(175, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:28'),
(176, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:33'),
(177, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:38'),
(178, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:44'),
(179, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:51'),
(180, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:40:57'),
(181, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47922, '2025-10-24 06:41:00'),
(182, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45430, '2025-10-24 06:45:36'),
(183, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48200, '2025-10-24 06:45:40'),
(184, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48200, '2025-10-24 06:45:41'),
(185, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48200, '2025-10-24 06:45:47'),
(186, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48200, '2025-10-24 06:45:49'),
(187, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48559, '2025-10-24 06:47:36'),
(188, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48559, '2025-10-24 06:47:40'),
(189, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48559, '2025-10-24 06:47:46'),
(190, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48559, '2025-10-24 06:47:55'),
(191, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48559, '2025-10-24 06:48:05'),
(192, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48019, '2025-10-24 06:49:45'),
(193, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48019, '2025-10-24 06:49:49'),
(194, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47345, '2025-10-24 06:49:55'),
(195, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48019, '2025-10-24 06:50:20'),
(196, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:56:36'),
(197, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:56:44'),
(198, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:56:49'),
(199, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:56:51'),
(200, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:57:07'),
(201, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:57:13'),
(202, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:57:15'),
(203, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:57:17'),
(204, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47888, '2025-10-24 06:57:18'),
(205, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45507, '2025-10-24 07:00:27'),
(206, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 45507, '2025-10-24 07:00:57'),
(207, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45832, '2025-10-24 07:05:38'),
(208, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45832, '2025-10-24 07:05:52'),
(209, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:00'),
(210, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:04'),
(211, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:06'),
(212, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:35'),
(213, 7, 'CREATE', 'ventes', 1, '{\"numero\":\"V-20251024-071054-b6c0\",\"client_id\":3,\"total\":7500}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:54'),
(214, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:54'),
(215, 7, 'EXPORT_PDF', 'ventes', 1, '{\"type\":\"invoice\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:10:57'),
(216, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:11:11'),
(217, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:11:20'),
(218, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:11:25'),
(219, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:11:32'),
(220, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:11:45'),
(221, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46703, '2025-10-24 07:11:52'),
(222, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47670, '2025-10-24 07:12:22'),
(223, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46595, '2025-10-24 07:12:28'),
(224, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48521, '2025-10-24 07:12:34'),
(225, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46324, '2025-10-24 07:12:45'),
(226, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47884, '2025-10-24 07:13:02'),
(227, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47884, '2025-10-24 07:13:13'),
(228, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47884, '2025-10-24 07:14:19'),
(229, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47884, '2025-10-24 07:14:33'),
(230, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47884, '2025-10-24 07:15:11'),
(231, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47884, '2025-10-24 07:15:18'),
(232, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47641, '2025-10-24 07:23:39'),
(233, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47278, '2025-10-24 07:23:50');
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `details`, `ip`, `user_agent`, `port`, `created_at`) VALUES
(234, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47278, '2025-10-24 07:23:54'),
(235, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47278, '2025-10-24 07:24:00'),
(236, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47278, '2025-10-24 07:24:36'),
(237, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47278, '2025-10-24 07:24:42'),
(238, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47278, '2025-10-24 07:24:44'),
(239, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48505, '2025-10-24 07:26:35'),
(240, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45734, '2025-10-24 07:29:57'),
(241, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45734, '2025-10-24 07:30:00'),
(242, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:33:02'),
(243, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:33:14'),
(244, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:33:21'),
(245, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:33:40'),
(246, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:33:45'),
(247, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:33:47'),
(248, 7, 'CREATE', 'ventes', 2, '{\"numero\":\"V-20251024-073436-c8ef\",\"client_id\":3,\"total\":17500}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:34:36'),
(249, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:34:36'),
(250, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:34:41'),
(251, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45531, '2025-10-24 07:34:48'),
(252, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48473, '2025-10-24 07:42:47'),
(253, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48473, '2025-10-24 07:43:08'),
(254, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48473, '2025-10-24 07:43:14'),
(255, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49185, '2025-10-24 07:44:19'),
(256, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49185, '2025-10-24 07:44:47'),
(257, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:00:32'),
(258, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:00:40'),
(259, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:01:38'),
(260, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:02:04'),
(261, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:02:13'),
(262, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:02:28'),
(263, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:02:31'),
(264, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:02:36'),
(265, 7, 'CREATE', 'ventes', 3, '{\"numero\":\"V-20251024-080401-3ee2\",\"client_id\":1,\"total\":90000}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:04:01'),
(266, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:04:01'),
(267, 7, 'EXPORT_PDF', 'ventes', 3, '{\"type\":\"invoice\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:04:11'),
(268, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48039, '2025-10-24 08:04:22'),
(269, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48338, '2025-10-24 08:06:48'),
(270, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48338, '2025-10-24 08:07:01'),
(271, 4, 'LOGIN', NULL, NULL, '{\"email\":\"livrboye123\",\"status\":\"success\"}', '160.154.150.53', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 50615, '2025-10-24 08:35:11'),
(272, 8, 'CLIENT_LOCATE', 'ventes', 2, '{\"lat\":5.2849858,\"lon\":-3.9468023,\"addr\":\"Premier etage\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46331, '2025-10-24 09:29:54'),
(273, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46702, '2025-10-24 09:48:23'),
(274, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46702, '2025-10-24 09:48:31'),
(275, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:49:38'),
(276, 9, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichcomptable\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:49:49'),
(277, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:50:00'),
(278, 9, 'CREATE', 'payments', 0, '{\"vente_id\":3,\"montant\":30000,\"mode\":\"espece\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:50:29'),
(279, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:50:29'),
(280, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:50:41'),
(281, 9, 'EXPORT_PDF', 'payments', 1, '{\"type\":\"receipt\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:50:49'),
(282, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:51:44'),
(283, 9, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:51:56'),
(284, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:52:06'),
(285, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:52:12'),
(286, 3, 'EXPORT_PDF', 'ventes', 3, '{\"type\":\"invoice\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:52:41'),
(287, 3, 'ATTACH_LIST', 'ventes', 3, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48637, '2025-10-24 09:52:50'),
(288, 3, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47374, '2025-10-24 09:53:48'),
(289, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47374, '2025-10-24 09:54:22'),
(290, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47374, '2025-10-24 09:54:23'),
(291, 9, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichcomptable\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47374, '2025-10-24 09:54:30'),
(292, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48118, '2025-10-24 10:04:58'),
(293, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48118, '2025-10-24 10:05:03'),
(294, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47083, '2025-10-24 10:07:07'),
(295, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47083, '2025-10-24 10:07:28'),
(296, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47083, '2025-10-24 10:07:44'),
(297, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47083, '2025-10-24 10:07:52'),
(298, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49344, '2025-10-24 10:30:27'),
(299, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48706, '2025-10-24 10:34:59'),
(300, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47904, '2025-10-24 10:37:04'),
(301, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45550, '2025-10-24 10:44:08'),
(302, 9, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47999, '2025-10-24 10:45:39'),
(303, NULL, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira\",\"status\":\"failed\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2692, '2025-10-24 11:56:00'),
(304, 6, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira123\",\"status\":\"success\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2692, '2025-10-24 11:56:18'),
(305, 6, 'VIEW', 'ventes', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2692, '2025-10-24 11:56:39'),
(306, 6, 'CREATE', 'ventes', 4, '{\"numero\":\"V-20251024-115858-8331\",\"client_id\":3,\"total\":90000}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2719, '2025-10-24 11:58:58'),
(307, 6, 'VIEW', 'ventes', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2719, '2025-10-24 11:58:58'),
(308, 6, 'LOGOUT', NULL, NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2735, '2025-10-24 12:00:12'),
(309, 4, 'LOGIN', NULL, NULL, '{\"email\":\"livrboye123\",\"status\":\"success\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2742, '2025-10-24 12:00:36'),
(310, 3, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46210, '2025-10-24 12:02:07'),
(311, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46210, '2025-10-24 12:02:32'),
(312, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45863, '2025-10-24 12:03:57'),
(313, 9, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichcomptable\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47598, '2025-10-24 12:05:04'),
(314, 4, 'DELIVERY_CONFIRM', 'ventes', 4, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2776, '2025-10-24 12:05:39'),
(315, 9, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47598, '2025-10-24 12:05:56'),
(316, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47598, '2025-10-24 12:06:00'),
(317, 4, 'LOGOUT', NULL, NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2790, '2025-10-24 12:06:20'),
(318, 2, 'LOGIN', NULL, NULL, '{\"email\":\"adminkevin\",\"status\":\"success\"}', '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2790, '2025-10-24 12:06:23'),
(319, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47598, '2025-10-24 12:06:24'),
(320, 2, 'VIEW', 'payments', NULL, NULL, '102.209.216.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 2793, '2025-10-24 12:07:07'),
(321, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45537, '2025-10-24 12:07:55'),
(322, 4, 'LOGIN', NULL, NULL, '{\"email\":\"livrboye123\",\"status\":\"success\"}', '160.155.241.207', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 4087, '2025-10-24 12:08:42'),
(323, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45537, '2025-10-24 12:08:47'),
(324, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48441, '2025-10-24 12:10:59'),
(325, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48441, '2025-10-24 12:11:08'),
(326, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47941, '2025-10-24 12:13:05'),
(327, 9, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichcomptable\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47941, '2025-10-24 12:13:11'),
(328, 9, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47970, '2025-10-24 12:20:18'),
(329, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48868, '2025-10-24 12:22:52'),
(330, 4, 'LOGIN', NULL, NULL, '{\"email\":\"livrboye123\",\"status\":\"success\"}', '160.155.241.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 14106, '2025-10-24 12:25:23'),
(331, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46622, '2025-10-24 12:29:08'),
(332, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46622, '2025-10-24 12:29:12'),
(333, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46622, '2025-10-24 12:29:19'),
(334, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46622, '2025-10-24 12:29:30'),
(335, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46622, '2025-10-24 12:29:35'),
(336, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46622, '2025-10-24 12:29:37'),
(337, 6, 'LOGIN', NULL, NULL, '{\"email\":\"gerelvira123\",\"status\":\"success\"}', '102.209.220.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 22155, '2025-10-24 12:32:19'),
(338, 6, 'VIEW', 'ventes', NULL, NULL, '102.209.220.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', 22232, '2025-10-24 12:32:59'),
(339, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:06:55'),
(340, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:07:12'),
(341, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:07:16'),
(342, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:07:36'),
(343, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:07:39'),
(344, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:07:44'),
(345, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49080, '2025-10-24 13:07:47'),
(346, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47551, '2025-10-24 13:08:38'),
(347, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47551, '2025-10-24 13:08:45'),
(348, 3, 'VIEW', 'depots', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:17:39'),
(349, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:17:42'),
(350, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:17:54'),
(351, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:17:56'),
(352, 3, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:17:57'),
(353, 3, 'VIEW', 'stock_entries', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:18:03'),
(354, 3, 'VIEW', 'stock_adjustments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:18:05'),
(355, 3, 'VIEW', 'payments', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48152, '2025-10-24 13:18:06'),
(356, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46055, '2025-10-24 13:19:26'),
(357, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46055, '2025-10-24 13:19:32'),
(358, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47599, '2025-10-24 13:20:59'),
(359, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47599, '2025-10-24 13:21:05'),
(360, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46493, '2025-10-24 13:30:42'),
(361, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46493, '2025-10-24 13:30:54'),
(362, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46493, '2025-10-24 13:31:01'),
(363, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47709, '2025-10-24 13:31:13'),
(364, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47709, '2025-10-24 13:31:15'),
(365, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47709, '2025-10-24 13:31:16'),
(366, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47709, '2025-10-24 13:31:20'),
(367, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45723, '2025-10-24 15:37:28'),
(368, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47998, '2025-10-24 15:38:53'),
(369, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47998, '2025-10-24 15:39:03'),
(370, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45403, '2025-10-24 15:53:39'),
(371, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45403, '2025-10-24 15:53:47'),
(372, 3, 'EXPORT_PDF', 'clients', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45403, '2025-10-24 15:54:20'),
(373, 3, 'VIEW', 'products', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 45437, '2025-10-24 16:54:45'),
(374, 2, 'LOGIN', NULL, NULL, '{\"email\":\"adminkevin\",\"status\":\"success\"}', '102.209.217.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 12963, '2025-10-24 17:03:42'),
(375, 2, 'VIEW', 'products', NULL, NULL, '102.209.217.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 12971, '2025-10-24 17:05:56'),
(376, 2, 'VIEW', 'ventes', NULL, NULL, '102.209.217.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 12974, '2025-10-24 17:06:02'),
(377, 2, 'VIEW', 'stock_adjustments', NULL, NULL, '102.209.217.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 12977, '2025-10-24 17:08:02'),
(378, 2, 'VIEW', 'payments', NULL, NULL, '102.209.217.112', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 12977, '2025-10-24 17:09:11'),
(379, 3, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47544, '2025-10-24 17:28:15'),
(380, 8, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichlivreur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 47544, '2025-10-24 17:28:24'),
(381, 8, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48898, '2025-10-24 17:28:52'),
(382, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48898, '2025-10-24 17:28:56'),
(383, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48943, '2025-10-24 17:31:48'),
(384, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48943, '2025-10-24 17:32:02'),
(385, 7, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48943, '2025-10-24 17:32:15'),
(386, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48943, '2025-10-24 17:32:25'),
(387, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46950, '2025-10-24 17:57:39'),
(388, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46950, '2025-10-24 17:57:51'),
(389, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48876, '2025-10-24 18:03:17'),
(390, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48876, '2025-10-24 18:03:24'),
(391, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48876, '2025-10-24 18:03:30'),
(392, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48876, '2025-10-24 18:03:32'),
(393, 7, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichvendeur\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48165, '2025-10-24 18:42:44'),
(394, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48165, '2025-10-24 18:43:06'),
(395, 7, 'EXPORT_PDF', 'ventes', 3, '{\"type\":\"invoice\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48165, '2025-10-24 18:43:13'),
(396, 7, 'EXPORT_PDF', 'stock', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48165, '2025-10-24 18:43:27'),
(397, 7, 'VIEW', 'ventes', NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 48165, '2025-10-24 18:43:34'),
(398, NULL, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49127, '2025-10-24 21:33:33'),
(399, 9, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichcomptable\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49127, '2025-10-24 21:33:42'),
(400, 9, 'LOGOUT', NULL, NULL, NULL, '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 49127, '2025-10-24 21:33:53'),
(401, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46116, '2025-10-24 21:33:59'),
(402, 3, 'LOGIN', NULL, NULL, '{\"email\":\"ulrichadmin\",\"status\":\"success\"}', '102.67.200.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 46331, '2025-10-24 23:00:11');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `entreprise` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `type_client` enum('particulier','entreprise') DEFAULT 'particulier',
  `limite_credit` decimal(15,2) DEFAULT 0.00,
  `solde_credit` decimal(15,2) DEFAULT 0.00,
  `points_fidelite` int(11) DEFAULT 0,
  `date_derniere_visite` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `livreur_id` int(11) NOT NULL,
  `code_client` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `prenom`, `entreprise`, `telephone`, `email`, `adresse`, `type_client`, `limite_credit`, `solde_credit`, `points_fidelite`, `date_derniere_visite`, `is_active`, `created_at`, `updated_at`, `livreur_id`, `code_client`) VALUES
(1, 'Kouame Akissi', '', '', '+225 07 11 22 33', 'akissi@gmail.com', 'Abidjan', 'particulier', 100000.00, 0.00, 0, NULL, 1, '2025-10-19 18:38:28', '2025-10-24 17:28:06', 8, ''),
(2, 'Traore', 'Mamadou', 'SARL TRAORE', '+225 05 44 55 66', 'traore@sarl.com', NULL, 'entreprise', 500000.00, 0.00, 0, NULL, 1, '2025-10-19 18:38:28', '2025-10-19 18:38:28', 0, ''),
(3, 'Diabate', 'Fatou', NULL, '+225 01 77 88 99', 'fatou@yahoo.fr', NULL, 'particulier', 50000.00, 0.00, 0, NULL, 1, '2025-10-19 18:38:28', '2025-10-19 18:38:28', 0, ''),
(4, 'Gore lou', '', 'Chez les sœurs', '0708528560', '', 'Ghandi pourri pourri', 'particulier', 0.00, 0.00, 0, NULL, 1, '2025-10-24 12:16:18', '2025-10-24 16:57:49', 4, ''),
(5, 'IBRAHIM POULET', '', 'POULET BRAISE', '0150513196', '', 'cite CIE en face de la phcie cite cie', 'particulier', 0.00, 0.00, 0, NULL, 1, '2025-10-24 12:30:37', '2025-10-24 16:57:49', 4, ''),
(6, 'seraphin injs', '', 'CHEZ SERAPHIN', '0140729531', '', 'EN FACE DE L\'INJS', 'particulier', 0.00, 0.00, 0, NULL, 1, '2025-10-24 12:35:56', '2025-10-24 16:57:49', 4, '');

-- --------------------------------------------------------

--
-- Structure de la table `depots`
--

CREATE TABLE `depots` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `adresse` text DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `horaires` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_main` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `depots`
--

INSERT INTO `depots` (`id`, `nom`, `adresse`, `responsable`, `telephone`, `email`, `horaires`, `latitude`, `longitude`, `is_active`, `created_at`, `is_main`) VALUES
(1, 'Dépôt Principal', 'Zone Industrielle, Yopougon, Abidjan, Côte d’Ivoire', 'Kouassi Jean', '+225 07 12 34 56', '', '08:00-18:30', 5.3744060, -4.0845380, 1, '2025-10-19 18:38:28', 1),
(2, 'Dépôt Secondaire', 'Le Plateau, Abidjan, Côte d’Ivoire', 'Adjoua Marie', '+225 05 98 76 54', '', '08:00-18:30', 5.3280490, -4.0212460, 1, '2025-10-19 18:38:28', 0),
(3, 'Depot Localisé Test', 'abidjan', 'Yao Ulrich Amani', '0779 93 79 21 / 07 4', 'amani_ulrich@outlook.fr', '08:00-18:30', NULL, NULL, 0, '2025-10-24 05:22:07', 0),
(4, 'Yao Ulrich Amani', 'Abidjan, Côte d\'Ivoire', 'Yao Ulrich Amani', '0748367710', 'amani_ulrich@outlook.fr', '08:00-18:30', NULL, NULL, 0, '2025-10-24 05:23:52', 0),
(5, 'Yao Ulrich Amani', '', 'Yao Ulrich Amani', '0779 93 79 21 / 07 4', 'amani_ulrich@outlook.fr', '08:00-18:30', 5.2887550, -3.9387140, 0, '2025-10-24 06:36:15', 0);

-- --------------------------------------------------------

--
-- Structure de la table `fidelity_points`
--

CREATE TABLE `fidelity_points` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type_operation` enum('earned','spent') NOT NULL,
  `vente_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_operation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `livreur_loads`
--

CREATE TABLE `livreur_loads` (
  `id` int(11) NOT NULL,
  `livreur_id` int(11) NOT NULL,
  `depot_id` int(11) NOT NULL,
  `date_load` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `livreur_loads`
--

INSERT INTO `livreur_loads` (`id`, `livreur_id`, `depot_id`, `date_load`, `status`, `validated_by`, `validated_at`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 8, 1, '2025-10-24', 'draft', NULL, NULL, '', 8, '2025-10-24 15:53:17', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `livreur_load_audits`
--

CREATE TABLE `livreur_load_audits` (
  `id` int(11) NOT NULL,
  `load_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `items_json` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `livreur_load_audits`
--

INSERT INTO `livreur_load_audits` (`id`, `load_id`, `action`, `items_json`, `notes`, `user_id`, `created_at`) VALUES
(1, 1, 'save', '[{\"produit_id\":3,\"quantite\":4,\"client_id\":5},{\"produit_id\":6,\"quantite\":3}]', '', 8, '2025-10-24 15:53:17');

-- --------------------------------------------------------

--
-- Structure de la table `livreur_load_items`
--

CREATE TABLE `livreur_load_items` (
  `id` int(11) NOT NULL,
  `load_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `client_lat` decimal(10,6) DEFAULT NULL,
  `client_lng` decimal(10,6) DEFAULT NULL,
  `quantite` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `livreur_load_items`
--

INSERT INTO `livreur_load_items` (`id`, `load_id`, `produit_id`, `client_id`, `client_lat`, `client_lng`, `quantite`) VALUES
(1, 1, 3, 5, NULL, NULL, 4.00),
(2, 1, 6, NULL, NULL, NULL, 3.00);

-- --------------------------------------------------------

--
-- Structure de la table `livreur_remittances`
--

CREATE TABLE `livreur_remittances` (
  `id` int(11) NOT NULL,
  `livreur_id` int(11) NOT NULL,
  `depot_id` int(11) NOT NULL,
  `date_remit` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `vente_id` int(11) NOT NULL,
  `numero_recu` varchar(50) NOT NULL,
  `date_payment` date NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_payment` enum('espece','cheque','virement','mobile') DEFAULT 'espece',
  `statut` enum('valide','attente','rejete') DEFAULT 'valide',
  `reference` varchar(100) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `vente_id`, `numero_recu`, `date_payment`, `montant`, `mode_payment`, `statut`, `reference`, `commentaire`, `created_at`) VALUES
(1, 3, 'RC-20251024-095029-e082', '2025-10-24', 30000.00, 'espece', 'valide', 'REF-TEST-001', NULL, '2025-10-24 09:50:29');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `code_produit` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `unite` varchar(20) DEFAULT 'pièce',
  `prix_unitaire` decimal(10,2) NOT NULL,
  `prix_credit` decimal(10,2) DEFAULT NULL,
  `points_fidelite` int(11) DEFAULT 1,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `nom`, `code_produit`, `description`, `unite`, `prix_unitaire`, `prix_credit`, `points_fidelite`, `image_path`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Ordinateur Portable HP Core I5', 'P001', 'Ordinateur france aurevoir', 'pièce', 100000.00, 130000.00, 1, '', 0, '2025-10-22 14:30:27', '2025-10-24 04:28:38'),
(3, 'ALUMINIUM FOIL 50M', 'ALIM001', '', 'pièce', 2500.00, 2500.00, 1, NULL, 1, '2025-10-23 14:07:51', '2025-10-23 14:07:51'),
(4, 'ALUMINIUM SANITEX 50M', 'ALHT002', '', 'pièce', 2500.00, 2500.00, 1, NULL, 1, '2025-10-23 14:10:01', '2025-10-23 14:10:01'),
(5, 'ALUMINIUM SANITEX 200M', 'ALHT003', '', 'pièce', 10000.00, 10000.00, 1, NULL, 1, '2025-10-23 14:10:40', '2025-10-23 14:10:40'),
(6, 'ALUMINIUM FOIL100M', 'ALIM004', '', 'pièce', 5000.00, 5000.00, 1, NULL, 1, '2025-10-23 14:11:56', '2025-10-23 14:11:56');

-- --------------------------------------------------------

--
-- Structure de la table `stock`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `depot_id` int(11) NOT NULL,
  `quantite` decimal(10,2) DEFAULT 0.00,
  `quantite_reservee` decimal(10,2) DEFAULT 0.00,
  `seuill_alerte` decimal(10,2) DEFAULT 0.00,
  `last_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock`
--

INSERT INTO `stock` (`id`, `produit_id`, `depot_id`, `quantite`, `quantite_reservee`, `seuill_alerte`, `last_update`) VALUES
(1, 2, 1, 0.00, 0.00, 0.00, '2025-10-24 04:10:23'),
(2, 3, 1, 658.00, 0.00, 0.00, '2025-10-23 14:19:18'),
(3, 5, 1, 48.00, 0.00, 0.00, '2025-10-23 14:42:11');

-- --------------------------------------------------------

--
-- Structure de la table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `depot_id` int(11) NOT NULL,
  `delta` decimal(10,2) NOT NULL,
  `motif` enum('inventaire','correction','casse','perte','autre') DEFAULT 'inventaire',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `produit_id`, `depot_id`, `delta`, `motif`, `notes`, `created_by`, `created_at`) VALUES
(1, 5, 1, 30.00, 'inventaire', '', 1, '2025-10-19 18:31:57'),
(2, 5, 1, 900.00, 'inventaire', '', 1, '2025-10-19 18:32:32'),
(3, 5, 1, 10.00, 'inventaire', '', 1, '2025-10-19 18:33:25'),
(4, 3, 1, 10.00, 'correction', '', 2, '2025-10-23 14:19:18'),
(5, 5, 1, 10.00, 'correction', 'Essai', 3, '2025-10-23 14:41:30'),
(6, 5, 1, -10.00, 'correction', 'Restauration valeur réelle', 3, '2025-10-23 14:42:11'),
(7, 2, 1, -100.00, 'correction', 'Ajustements stock test', 3, '2025-10-24 04:10:23');

-- --------------------------------------------------------

--
-- Structure de la table `stock_entries`
--

CREATE TABLE `stock_entries` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `depot_id` int(11) NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `cout_unitaire` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `type_entry` enum('achat','reception','retour_client','autre') DEFAULT 'achat',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_entries`
--

INSERT INTO `stock_entries` (`id`, `produit_id`, `depot_id`, `quantite`, `cout_unitaire`, `reference`, `type_entry`, `notes`, `created_by`, `created_at`) VALUES
(1, 2, 1, 100.00, 100000.00, 'ART001', 'reception', 'Ordinateur portable test 1', 1, '2025-10-22 14:44:52'),
(2, 3, 1, 648.00, 1300.00, '', 'achat', 'SOCK INITIAL IMPORTE DE CHINE', 2, '2025-10-23 14:13:50'),
(3, 5, 1, 48.00, 8750.00, '', 'achat', 'SATOCI', 2, '2025-10-23 14:14:44');

-- --------------------------------------------------------

--
-- Structure de la table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `depot_source` int(11) NOT NULL,
  `depot_destination` int(11) NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_transfer` timestamp NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','vendeur','livreur','comptable') DEFAULT 'vendeur',
  `phone` varchar(20) DEFAULT NULL,
  `depot_id` int(11) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `phone`, `depot_id`, `profile_photo`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hillemballage.ci', '$2y$10$GHE.RaPav8iTCvZ28lI/oe0RVwYfcJUkyyrRS3bNiGkfgcyDqEU66', 'Administrateur Hill', 'admin', NULL, 1, NULL, 0, '2025-10-23 09:59:30', '2025-10-19 18:38:28', '2025-10-23 10:27:05'),
(2, 'adminkevin', 'kevin@hillemballage.ci', '$2y$10$Vu1kOF2mnB1Fa3iB7SJIR.fNrope7kS7qwATnjj7UAhpexeyRv8LG', 'NGORAN KONAN KEVIN', 'admin', NULL, 1, NULL, 1, '2025-10-24 17:03:42', '2025-10-23 10:01:27', '2025-10-24 17:03:42'),
(3, 'ulrichadmin', 'ulrich@banamur.com', '$2y$10$3/CVLlZZ68eAsrKO13Sf2eOfGb7CMfekRL/SrDNkD.sqB.Yzv2VB6', 'Yao Ulrich Amani', 'admin', NULL, 1, '/uploads/profiles/u3_1761215351.jpg', 1, '2025-10-24 23:00:11', '2025-10-23 10:28:01', '2025-10-24 23:00:11'),
(4, 'livrboye123', 'emmanuelboye@gmail.com', '$2y$10$eELabHNVjnRp2r5hOHHJp.zJbrx3U0dl8Uw5GRNuZgmDdzOG8Umnm', 'BOYE EMMANUEL', 'livreur', NULL, 1, NULL, 1, '2025-10-24 12:25:23', '2025-10-23 14:40:17', '2025-10-24 12:25:23'),
(6, 'gerelvira123', 'dragrouelvira@gmail.com', '$2y$10$/EEiVoaYjReGiQwUUqG6pOu8Cn0LAtA2B0lfv83gxEypimYJeNUcC', 'DAGROU ELVIRA', 'vendeur', NULL, NULL, NULL, 1, '2025-10-24 12:32:19', '2025-10-23 14:49:02', '2025-10-24 12:32:19'),
(7, 'ulrichvendeur', 'ulrichvendeur@hillemballage.ci', '$2y$10$HyY/vL3L62JGSYnzHRJIQeNEy8zT7nMgHvtWQBw0ySjXZGAsCKoSK', 'Ulrich Vendeur', 'vendeur', NULL, 1, NULL, 1, '2025-10-24 18:42:44', '2025-10-24 06:38:33', '2025-10-24 18:42:44'),
(8, 'ulrichlivreur', 'ulrichlivreur@hillemballage.ci', '$2y$10$tu37dUuA6mKHitZQfjJMsuCxEa8j4GJc1pS7Du.1.f5lOC8zLSs6W', 'Ulrich Livreur', 'livreur', NULL, 1, NULL, 1, '2025-10-24 17:28:24', '2025-10-24 07:14:14', '2025-10-24 17:28:24'),
(9, 'ulrichcomptable', 'ulrichcomptable@hillemballage.ci', '$2y$10$Hi8ZCWD1rJd2AwKrRGLAFeRvqHdEKaePGot.PEzC1Nib2V.A86wA.', 'Ulrich Comptable', 'comptable', NULL, 1, NULL, 1, '2025-10-24 21:33:42', '2025-10-24 09:49:32', '2025-10-24 21:33:42');

-- --------------------------------------------------------

--
-- Structure de la table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `permission`, `allowed`, `created_at`, `updated_at`) VALUES
(1, 2, 'manage_users', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(2, 2, 'view_reports', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(3, 2, 'clients_read', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(4, 2, 'clients_create', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(5, 2, 'clients_update', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(6, 2, 'clients_delete', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(7, 2, 'sales_read', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(8, 2, 'sales_create', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(9, 2, 'sales_update', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(10, 2, 'sales_delete', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(11, 2, 'stock_read', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(12, 2, 'stock_update', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(13, 2, 'payments_read', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(14, 2, 'payments_create', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(15, 2, 'payments_update', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(16, 2, 'products_read', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(17, 2, 'products_create', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(18, 2, 'products_update', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(19, 2, 'products_delete', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(20, 2, 'deliveries_read', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(21, 2, 'deliveries_update', 1, '2025-10-23 10:01:28', '2025-10-23 10:01:28'),
(22, 3, 'manage_users', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(23, 3, 'view_reports', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(24, 3, 'clients_read', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(25, 3, 'clients_create', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(26, 3, 'clients_update', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(27, 3, 'clients_delete', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(28, 3, 'sales_read', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(29, 3, 'sales_create', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(30, 3, 'sales_update', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(31, 3, 'sales_delete', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(32, 3, 'stock_read', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(33, 3, 'stock_update', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(34, 3, 'payments_read', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(35, 3, 'payments_create', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(36, 3, 'payments_update', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(37, 3, 'products_read', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(38, 3, 'products_create', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(39, 3, 'products_update', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(40, 3, 'products_delete', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(41, 3, 'deliveries_read', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(42, 3, 'deliveries_update', 1, '2025-10-23 10:28:01', '2025-10-23 10:28:01'),
(43, 4, 'manage_users', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(44, 4, 'view_reports', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(45, 4, 'clients_read', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(46, 4, 'clients_create', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(47, 4, 'clients_update', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(48, 4, 'clients_delete', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(49, 4, 'sales_read', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(50, 4, 'sales_create', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(51, 4, 'sales_update', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(52, 4, 'sales_delete', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(53, 4, 'stock_read', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(54, 4, 'stock_update', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(55, 4, 'payments_read', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(56, 4, 'payments_create', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(57, 4, 'payments_update', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(58, 4, 'products_read', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(59, 4, 'products_create', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(60, 4, 'products_update', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(61, 4, 'products_delete', 0, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(62, 4, 'deliveries_read', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(63, 4, 'deliveries_update', 1, '2025-10-23 14:40:17', '2025-10-23 14:40:17'),
(85, 6, 'manage_users', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(86, 6, 'view_reports', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(87, 6, 'clients_read', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(88, 6, 'clients_create', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(89, 6, 'clients_update', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(90, 6, 'clients_delete', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(91, 6, 'sales_read', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(92, 6, 'sales_create', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(93, 6, 'sales_update', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(94, 6, 'sales_delete', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(95, 6, 'stock_read', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(96, 6, 'stock_update', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(97, 6, 'payments_read', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(98, 6, 'payments_create', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(99, 6, 'payments_update', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(100, 6, 'products_read', 1, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(101, 6, 'products_create', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(102, 6, 'products_update', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(103, 6, 'products_delete', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(104, 6, 'deliveries_read', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(105, 6, 'deliveries_update', 0, '2025-10-23 14:49:02', '2025-10-23 14:49:02'),
(106, 7, 'manage_users', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(107, 7, 'view_reports', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(108, 7, 'clients_read', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(109, 7, 'clients_create', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(110, 7, 'clients_update', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(111, 7, 'clients_delete', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(112, 7, 'sales_read', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(113, 7, 'sales_create', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(114, 7, 'sales_update', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(115, 7, 'sales_delete', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(116, 7, 'stock_read', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(117, 7, 'stock_update', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(118, 7, 'payments_read', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(119, 7, 'payments_create', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(120, 7, 'payments_update', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(121, 7, 'products_read', 1, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(122, 7, 'products_create', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(123, 7, 'products_update', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(124, 7, 'products_delete', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(125, 7, 'deliveries_read', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(126, 7, 'deliveries_update', 0, '2025-10-24 06:38:33', '2025-10-24 06:38:33'),
(148, 8, 'manage_users', 0, '2025-10-24 07:14:14', '2025-10-24 07:14:14'),
(149, 8, 'view_reports', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(150, 8, 'clients_read', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(151, 8, 'clients_create', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(152, 8, 'clients_update', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(153, 8, 'clients_delete', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(154, 8, 'sales_read', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(155, 8, 'sales_create', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(156, 8, 'sales_update', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(157, 8, 'sales_delete', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(158, 8, 'stock_read', 1, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(159, 8, 'stock_update', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(160, 8, 'payments_read', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(161, 8, 'payments_create', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(162, 8, 'payments_update', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(163, 8, 'products_read', 1, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(164, 8, 'products_create', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(165, 8, 'products_update', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(166, 8, 'products_delete', 0, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(167, 8, 'deliveries_read', 1, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(168, 8, 'deliveries_update', 1, '2025-10-24 07:14:15', '2025-10-24 07:14:15'),
(169, 9, 'manage_users', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(170, 9, 'view_reports', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(171, 9, 'clients_read', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(172, 9, 'clients_create', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(173, 9, 'clients_update', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(174, 9, 'clients_delete', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(175, 9, 'sales_read', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(176, 9, 'sales_create', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(177, 9, 'sales_update', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(178, 9, 'sales_delete', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(179, 9, 'stock_read', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(180, 9, 'stock_update', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(181, 9, 'payments_read', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(182, 9, 'payments_create', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(183, 9, 'payments_update', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(184, 9, 'products_read', 1, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(185, 9, 'products_create', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(186, 9, 'products_update', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(187, 9, 'products_delete', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(188, 9, 'deliveries_read', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32'),
(189, 9, 'deliveries_update', 0, '2025-10-24 09:49:32', '2025-10-24 09:49:32');

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

CREATE TABLE `ventes` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numero_vente` varchar(50) NOT NULL,
  `date_vente` date NOT NULL,
  `type_vente` enum('comptant','credit') DEFAULT 'comptant',
  `montant_total` decimal(15,2) NOT NULL,
  `montant_paye` decimal(15,2) DEFAULT 0.00,
  `statut` enum('en_attente','validee','livree','annulee') DEFAULT 'en_attente',
  `date_echeance` date DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `delivery_mode` varchar(20) NOT NULL DEFAULT 'sur_place',
  `livreur_id` int(11) DEFAULT NULL,
  `delivery_address` varchar(255) DEFAULT NULL,
  `delivery_latitude` decimal(10,7) DEFAULT NULL,
  `delivery_longitude` decimal(10,7) DEFAULT NULL,
  `delivery_details` text DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id`, `client_id`, `user_id`, `numero_vente`, `date_vente`, `type_vente`, `montant_total`, `montant_paye`, `statut`, `date_echeance`, `commentaire`, `delivery_mode`, `livreur_id`, `delivery_address`, `delivery_latitude`, `delivery_longitude`, `delivery_details`, `delivery_date`, `created_at`, `updated_at`) VALUES
(1, 3, 7, 'V-20251024-071054-b6c0', '2001-08-23', 'comptant', 7500.00, 0.00, 'en_attente', '1984-12-11', 'Essai création vente', 'sur_place', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-24 07:10:54', '2025-10-24 07:10:54'),
(2, 3, 7, 'V-20251024-073436-c8ef', '2025-10-24', 'comptant', 17500.00, 0.00, 'en_attente', '2025-10-24', '', 'livraison', 8, 'Premier etage', 5.2849858, -3.9468023, 'Test', '2025-10-24', '2025-10-24 07:34:36', '2025-10-24 09:29:54'),
(3, 1, 7, 'V-20251024-080401-3ee2', '2001-03-14', 'comptant', 90000.00, 30000.00, 'en_attente', '1980-03-08', 'Adipisci delectus q', 'livraison', 8, 'Adjamé, Abidjan, Côte d’Ivoire', 5.3531210, -4.0222010, 'Essai', '2025-10-24', '2025-10-24 08:04:01', '2025-10-24 09:50:29'),
(4, 3, 6, 'V-20251024-115858-8331', '2025-10-24', 'credit', 90000.00, 0.00, 'livree', NULL, '', 'livraison', 4, 'Yopougon, Abidjan, Côte d’Ivoire', 5.3351940, -4.0757560, NULL, NULL, '2025-10-24 11:58:58', '2025-10-24 12:05:39');

-- --------------------------------------------------------

--
-- Structure de la table `vente_items`
--

CREATE TABLE `vente_items` (
  `id` int(11) NOT NULL,
  `vente_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `nom_produit` varchar(100) NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `montant` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vente_items`
--

INSERT INTO `vente_items` (`id`, `vente_id`, `produit_id`, `nom_produit`, `quantite`, `prix_unitaire`, `montant`) VALUES
(1, 1, 3, 'ALUMINIUM FOIL 50M', 3.00, 2500.00, 7500.00),
(2, 2, 3, 'ALUMINIUM FOIL 50M', 7.00, 2500.00, 17500.00),
(3, 3, 5, 'ALUMINIUM SANITEX 200M', 9.00, 10000.00, 90000.00),
(4, 4, 3, 'ALUMINIUM FOIL 50M', 36.00, 2500.00, 90000.00);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity`,`entity_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Index pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action_created` (`action`,`created_at`),
  ADD KEY `idx_entity_created` (`entity`,`created_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `depots`
--
ALTER TABLE `depots`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `fidelity_points`
--
ALTER TABLE `fidelity_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `vente_id` (`vente_id`);

--
-- Index pour la table `livreur_loads`
--
ALTER TABLE `livreur_loads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_livreur_date` (`livreur_id`,`date_load`),
  ADD KEY `idx_depot_date` (`depot_id`,`date_load`);

--
-- Index pour la table `livreur_load_audits`
--
ALTER TABLE `livreur_load_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_load` (`load_id`),
  ADD KEY `idx_action` (`action`);

--
-- Index pour la table `livreur_load_items`
--
ALTER TABLE `livreur_load_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_load` (`load_id`),
  ADD KEY `idx_prod` (`produit_id`),
  ADD KEY `idx_cli` (`client_id`);

--
-- Index pour la table `livreur_remittances`
--
ALTER TABLE `livreur_remittances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_livreur_date` (`livreur_id`,`date_remit`),
  ADD KEY `idx_depot_date` (`depot_id`,`date_remit`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_recu` (`numero_recu`),
  ADD KEY `vente_id` (`vente_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_produit` (`code_produit`);

--
-- Index pour la table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_produit_depot` (`produit_id`,`depot_id`),
  ADD KEY `depot_id` (`depot_id`);

--
-- Index pour la table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `depot_id` (`depot_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_stock_adjust_prod_depot` (`produit_id`,`depot_id`);

--
-- Index pour la table `stock_entries`
--
ALTER TABLE `stock_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `depot_id` (`depot_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_stock_entries_prod_depot` (`produit_id`,`depot_id`);

--
-- Index pour la table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `depot_source` (`depot_source`),
  ADD KEY `depot_destination` (`depot_destination`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `depot_id` (`depot_id`);

--
-- Index pour la table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_perm` (`user_id`,`permission`);

--
-- Index pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_vente` (`numero_vente`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ventes_livreur_id` (`livreur_id`),
  ADD KEY `idx_ventes_delivery_date` (`delivery_date`);

--
-- Index pour la table `vente_items`
--
ALTER TABLE `vente_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vente_id` (`vente_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=403;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `depots`
--
ALTER TABLE `depots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `fidelity_points`
--
ALTER TABLE `fidelity_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `livreur_loads`
--
ALTER TABLE `livreur_loads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `livreur_load_audits`
--
ALTER TABLE `livreur_load_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `livreur_load_items`
--
ALTER TABLE `livreur_load_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `livreur_remittances`
--
ALTER TABLE `livreur_remittances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `stock_entries`
--
ALTER TABLE `stock_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT pour la table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `vente_items`
--
ALTER TABLE `vente_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `livreur_load_items`
--
ALTER TABLE `livreur_load_items`
  ADD CONSTRAINT `fk_load` FOREIGN KEY (`load_id`) REFERENCES `livreur_loads` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
