-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 23 oct. 2025 à 09:32
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

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

DROP TABLE IF EXISTS `attachments`;
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity` varchar(50) NOT NULL,
  `entity_id` int NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity`,`entity_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `details` text,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `port` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action_created` (`action`,`created_at`),
  KEY `idx_entity_created` (`entity`,`created_at`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `details`, `ip`, `user_agent`, `port`, `created_at`) VALUES
(1, 1, 'VIEW', 'produits', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 64653, '2025-10-22 14:31:29'),
(2, 1, 'VIEW', 'ventes', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 64653, '2025-10-22 14:31:32'),
(3, 1, 'VIEW', 'ventes', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 64778, '2025-10-22 14:32:13'),
(4, 1, 'VIEW', 'stock_entries', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 64778, '2025-10-22 14:32:14'),
(5, 1, 'VIEW', 'stock_entries', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 64802, '2025-10-22 14:32:42'),
(6, 1, 'VIEW', 'stock_entries', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1630, '2025-10-22 14:42:26'),
(7, 1, 'CREATE', 'stock_entries', NULL, '{\"produit_id\":2,\"depot_id\":1,\"q\":100,\"type\":\"reception\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1960, '2025-10-22 14:44:52'),
(8, 1, 'VIEW', 'stock_entries', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1960, '2025-10-22 14:44:52'),
(9, 1, 'VIEW', 'stock_entries', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 2505, '2025-10-22 14:50:56'),
(10, 1, 'VIEW', 'stock_adjustments', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 2559, '2025-10-22 14:51:00'),
(11, 1, 'VIEW', 'payments', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 2559, '2025-10-22 14:51:03'),
(12, 1, 'VIEW', 'produits', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 3046, '2025-10-22 14:57:47'),
(13, 1, 'VIEW', 'ventes', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 3222, '2025-10-22 14:59:34'),
(14, 1, 'VIEW', 'produits', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 3223, '2025-10-22 14:59:47'),
(15, 1, 'LOGOUT', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 3223, '2025-10-22 14:59:57'),
(16, 1, 'LOGIN', NULL, NULL, '{\"email\":\"admin@hillemballage.ci\",\"status\":\"success\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 3223, '2025-10-22 14:59:59'),
(17, 1, 'VIEW', 'ventes', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 4042, '2025-10-22 15:11:48'),
(18, 1, 'VIEW', 'products', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 51561, '2025-10-23 09:31:54');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `entreprise` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` text,
  `type_client` enum('particulier','entreprise') DEFAULT 'particulier',
  `limite_credit` decimal(15,2) DEFAULT '0.00',
  `solde_credit` decimal(15,2) DEFAULT '0.00',
  `points_fidelite` int DEFAULT '0',
  `date_derniere_visite` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `prenom`, `entreprise`, `telephone`, `email`, `adresse`, `type_client`, `limite_credit`, `solde_credit`, `points_fidelite`, `date_derniere_visite`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Kouame', 'Akissi', NULL, '+225 07 11 22 33', 'akissi@gmail.com', NULL, 'particulier', 100000.00, 0.00, 0, NULL, 1, '2025-10-19 18:38:28', '2025-10-19 18:38:28'),
(2, 'Traore', 'Mamadou', 'SARL TRAORE', '+225 05 44 55 66', 'traore@sarl.com', NULL, 'entreprise', 500000.00, 0.00, 0, NULL, 1, '2025-10-19 18:38:28', '2025-10-19 18:38:28'),
(3, 'Diabate', 'Fatou', NULL, '+225 01 77 88 99', 'fatou@yahoo.fr', NULL, 'particulier', 50000.00, 0.00, 0, NULL, 1, '2025-10-19 18:38:28', '2025-10-19 18:38:28');

-- --------------------------------------------------------

--
-- Structure de la table `depots`
--

DROP TABLE IF EXISTS `depots`;
CREATE TABLE IF NOT EXISTS `depots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `adresse` text,
  `responsable` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `depots`
--

INSERT INTO `depots` (`id`, `nom`, `adresse`, `responsable`, `telephone`, `is_active`, `created_at`) VALUES
(1, 'Dépôt Principal', 'Zone Industrielle Abidjan', 'Kouassi Jean', '+225 07 12 34 56', 1, '2025-10-19 18:38:28'),
(2, 'Dépôt Secondaire', 'Plateau Abidjan', 'Adjoua Marie', '+225 05 98 76 54', 1, '2025-10-19 18:38:28');

-- --------------------------------------------------------

--
-- Structure de la table `fidelity_points`
--

DROP TABLE IF EXISTS `fidelity_points`;
CREATE TABLE IF NOT EXISTS `fidelity_points` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `points` int NOT NULL,
  `type_operation` enum('earned','spent') NOT NULL,
  `vente_id` int DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_operation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `vente_id` (`vente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vente_id` int NOT NULL,
  `numero_recu` varchar(50) NOT NULL,
  `date_payment` date NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_payment` enum('espece','cheque','virement','mobile') DEFAULT 'espece',
  `statut` enum('valide','attente','rejete') DEFAULT 'valide',
  `reference` varchar(100) DEFAULT NULL,
  `commentaire` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_recu` (`numero_recu`),
  KEY `vente_id` (`vente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `code_produit` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `unite` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT 'pièce',
  `prix_unitaire` decimal(10,2) NOT NULL,
  `prix_credit` decimal(10,2) DEFAULT NULL,
  `points_fidelite` int DEFAULT '1',
  `image_path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_produit` (`code_produit`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `nom`, `code_produit`, `description`, `unite`, `prix_unitaire`, `prix_credit`, `points_fidelite`, `image_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Rae Haney', 'Officiis adipisci do', '', 'Id quasi laboriosam', 7540.00, 424.00, 182, '', 0, '2025-10-22 14:26:31', '2025-10-22 14:29:20'),
(2, 'Ordinateur Portable HP Core I5', 'P001', 'Ordinateur france aurevoir', 'pièce', 100000.00, 130000.00, 1, '', 1, '2025-10-22 14:30:27', '2025-10-22 14:30:27');

-- --------------------------------------------------------

--
-- Structure de la table `stock`
--

DROP TABLE IF EXISTS `stock`;
CREATE TABLE IF NOT EXISTS `stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produit_id` int NOT NULL,
  `depot_id` int NOT NULL,
  `quantite` decimal(10,2) DEFAULT '0.00',
  `quantite_reservee` decimal(10,2) DEFAULT '0.00',
  `seuill_alerte` decimal(10,2) DEFAULT '0.00',
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_produit_depot` (`produit_id`,`depot_id`),
  KEY `depot_id` (`depot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock`
--

INSERT INTO `stock` (`id`, `produit_id`, `depot_id`, `quantite`, `quantite_reservee`, `seuill_alerte`, `last_update`) VALUES
(1, 2, 1, 100.00, 0.00, 0.00, '2025-10-22 14:44:52');

-- --------------------------------------------------------

--
-- Structure de la table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE IF NOT EXISTS `stock_adjustments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produit_id` int NOT NULL,
  `depot_id` int NOT NULL,
  `delta` decimal(10,2) NOT NULL,
  `motif` enum('inventaire','correction','casse','perte','autre') DEFAULT 'inventaire',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `depot_id` (`depot_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_stock_adjust_prod_depot` (`produit_id`,`depot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `produit_id`, `depot_id`, `delta`, `motif`, `notes`, `created_by`, `created_at`) VALUES
(1, 5, 1, 30.00, 'inventaire', '', 1, '2025-10-19 18:31:57'),
(2, 5, 1, 900.00, 'inventaire', '', 1, '2025-10-19 18:32:32'),
(3, 5, 1, 10.00, 'inventaire', '', 1, '2025-10-19 18:33:25');

-- --------------------------------------------------------

--
-- Structure de la table `stock_entries`
--

DROP TABLE IF EXISTS `stock_entries`;
CREATE TABLE IF NOT EXISTS `stock_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produit_id` int NOT NULL,
  `depot_id` int NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `cout_unitaire` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `type_entry` enum('achat','reception','retour_client','autre') DEFAULT 'achat',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `depot_id` (`depot_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_stock_entries_prod_depot` (`produit_id`,`depot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_entries`
--

INSERT INTO `stock_entries` (`id`, `produit_id`, `depot_id`, `quantite`, `cout_unitaire`, `reference`, `type_entry`, `notes`, `created_by`, `created_at`) VALUES
(1, 2, 1, 100.00, 100000.00, 'ART001', 'reception', 'Ordinateur portable test 1', 1, '2025-10-22 14:44:52');

-- --------------------------------------------------------

--
-- Structure de la table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produit_id` int NOT NULL,
  `depot_source` int NOT NULL,
  `depot_destination` int NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `date_transfer` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `produit_id` (`produit_id`),
  KEY `depot_source` (`depot_source`),
  KEY `depot_destination` (`depot_destination`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','vendeur','livreur','comptable') DEFAULT 'vendeur',
  `phone` varchar(20) DEFAULT NULL,
  `depot_id` int DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `depot_id` (`depot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `phone`, `depot_id`, `profile_photo`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hillemballage.ci', '$2y$10$GHE.RaPav8iTCvZ28lI/oe0RVwYfcJUkyyrRS3bNiGkfgcyDqEU66', 'Administrateur Hill', 'admin', NULL, 1, NULL, 1, '2025-10-22 14:59:59', '2025-10-19 18:38:28', '2025-10-22 14:59:59');

-- --------------------------------------------------------

--
-- Structure de la table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `permission` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_perm` (`user_id`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

DROP TABLE IF EXISTS `ventes`;
CREATE TABLE IF NOT EXISTS `ventes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `user_id` int NOT NULL,
  `numero_vente` varchar(50) NOT NULL,
  `date_vente` date NOT NULL,
  `type_vente` enum('comptant','credit') DEFAULT 'comptant',
  `montant_total` decimal(15,2) NOT NULL,
  `montant_paye` decimal(15,2) DEFAULT '0.00',
  `statut` enum('en_attente','validee','livree','annulee') DEFAULT 'en_attente',
  `date_echeance` date DEFAULT NULL,
  `commentaire` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_vente` (`numero_vente`),
  KEY `client_id` (`client_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vente_items`
--

DROP TABLE IF EXISTS `vente_items`;
CREATE TABLE IF NOT EXISTS `vente_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vente_id` int NOT NULL,
  `produit_id` int NOT NULL,
  `nom_produit` varchar(100) NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vente_id` (`vente_id`),
  KEY `produit_id` (`produit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
