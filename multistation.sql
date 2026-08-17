-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : jeu. 13 août 2026 à 12:27
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `multistation`
--

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL,
  `creat_at` datetime NOT NULL,
  `update_at` datetime DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `articles`
--

INSERT INTO `articles` (`id`, `name`, `description`, `is_active`, `creat_at`, `update_at`, `categorie_id`) VALUES
(1, 'COCA-COLA', NULL, 1, '2026-08-13 07:49:18', '2026-08-13 08:03:43', 1),
(2, 'FANTA', 'Fanta pomme', 1, '2026-08-13 07:59:16', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `articles_units`
--

CREATE TABLE `articles_units` (
  `id` int(11) NOT NULL,
  `converstion_factor` decimal(15,6) NOT NULL,
  `is_base_unit` tinyint(4) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL,
  `article_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `articles_units`
--

INSERT INTO `articles_units` (`id`, `converstion_factor`, `is_base_unit`, `barcode`, `is_active`, `article_id`, `unit_id`) VALUES
(1, 1.000000, 1, '00000112121', 1, 1, 1),
(2, 10.000000, 0, '000121212', 1, 1, 2),
(3, 1.000000, 1, '0020910291', 1, 2, 3),
(4, 10.000000, 0, '009138031831', 1, 2, 4);

-- --------------------------------------------------------

--
-- Structure de la table `article_categorie`
--

CREATE TABLE `article_categorie` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `article_categorie`
--

INSERT INTO `article_categorie` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'B-1', 'BOISSON', 1);

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260805124017', '2026-08-05 12:40:25', 2936),
('DoctrineMigrations\\Version20260813074000', '2026-08-13 07:45:19', 146),
('DoctrineMigrations\\Version20260813081500', '2026-08-13 08:11:17', 255),
('DoctrineMigrations\\Version20260813081600', '2026-08-13 08:11:34', 12),
('DoctrineMigrations\\Version20260813083000', '2026-08-13 08:26:22', 112),
('DoctrineMigrations\\Version20260813084000', '2026-08-13 08:30:48', 23),
('DoctrineMigrations\\Version20260813084519', '2026-08-13 08:48:20', 3251),
('DoctrineMigrations\\Version20260813085000', '2026-08-13 08:48:41', 4),
('DoctrineMigrations\\Version20260813091131', '2026-08-13 09:11:36', 1595),
('DoctrineMigrations\\Version20260813093203', '2026-08-13 09:32:04', 243);

-- --------------------------------------------------------

--
-- Structure de la table `fuel_delivery`
--

CREATE TABLE `fuel_delivery` (
  `id` int(11) NOT NULL,
  `supplier` varchar(120) NOT NULL,
  `invoice_number` varchar(60) DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `station_id` int(11) NOT NULL,
  `tank_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fuel_delivery`
--

INSERT INTO `fuel_delivery` (`id`, `supplier`, `invoice_number`, `delivery_date`, `quantity`, `unit_cost`, `total_amount`, `created_at`, `station_id`, `tank_id`) VALUES
(1, 'FRS CARBURANT', 'FACT 001', '2026-08-13', 20000.000, 5000.00, 100000000.00, '2026-08-13 09:14:31', 1, 1),
(2, 'FRS CARBURANT', 'fact 002', '2026-08-13', 10.000, 5000.00, 50000.00, '2026-08-13 09:18:35', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `fuel_nozzle`
--

CREATE TABLE `fuel_nozzle` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `current_index` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `pump_id` int(11) NOT NULL,
  `tank_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fuel_nozzle`
--

INSERT INTO `fuel_nozzle` (`id`, `code`, `current_index`, `unit_price`, `is_active`, `pump_id`, `tank_id`) VALUES
(1, 'PISTOL-001', 1.000, 5000.00, 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `fuel_payment_method`
--

CREATE TABLE `fuel_payment_method` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `station_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fuel_payment_method`
--

INSERT INTO `fuel_payment_method` (`id`, `code`, `name`, `is_active`, `created_at`, `station_id`) VALUES
(1, 'TEST', 'test', 1, '2026-08-13 09:32:15', 1),
(2, 'BANCAIRE', 'Virement', 1, '2026-08-13 09:36:03', 1);

-- --------------------------------------------------------

--
-- Structure de la table `fuel_pump`
--

CREATE TABLE `fuel_pump` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `station_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fuel_pump`
--

INSERT INTO `fuel_pump` (`id`, `code`, `name`, `is_active`, `station_id`) VALUES
(1, 'POMPE1', 'POMPE GASOIL', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `fuel_shift_reading`
--

CREATE TABLE `fuel_shift_reading` (
  `id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `start_index` decimal(15,3) NOT NULL,
  `end_index` decimal(15,3) NOT NULL,
  `return_to_tank` decimal(15,3) NOT NULL,
  `quantity_sold` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `payments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payments`)),
  `status` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL,
  `station_id` int(11) NOT NULL,
  `nozzle_id` int(11) NOT NULL,
  `attendant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fuel_tank`
--

CREATE TABLE `fuel_tank` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` decimal(15,3) NOT NULL,
  `current_stock` decimal(15,3) NOT NULL,
  `minimum_stock` decimal(15,3) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `station_id` int(11) NOT NULL,
  `fuel_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fuel_tank`
--

INSERT INTO `fuel_tank` (`id`, `code`, `name`, `capacity`, `current_stock`, `minimum_stock`, `is_active`, `station_id`, `fuel_type_id`) VALUES
(1, 'CUVE-001', 'CUVE GASOIL', 30000.000, 20010.000, 0.000, 1, 1, 4);

-- --------------------------------------------------------

--
-- Structure de la table `fuel_type`
--

CREATE TABLE `fuel_type` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fuel_type`
--

INSERT INTO `fuel_type` (`id`, `code`, `name`, `is_active`) VALUES
(1, 'SP', 'Super sans plomb', 1),
(2, 'GO', 'Gasoil', 1),
(3, 'PL', 'Pétrole lampant', 1),
(4, '0001', 'GASOIL', 1);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvement_stock`
--

CREATE TABLE `mouvement_stock` (
  `id` int(11) NOT NULL,
  `entered_quantity` decimal(15,6) NOT NULL,
  `conversion_factor` decimal(15,6) NOT NULL,
  `base_quantity` decimal(15,6) NOT NULL,
  `previous_stock_base` decimal(15,6) NOT NULL,
  `new_stock_base` decimal(15,6) NOT NULL,
  `mouvement_type` varchar(255) NOT NULL,
  `station_article_id` int(11) NOT NULL,
  `article_unit_id` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvement_stock`
--

INSERT INTO `mouvement_stock` (`id`, `entered_quantity`, `conversion_factor`, `base_quantity`, `previous_stock_base`, `new_stock_base`, `mouvement_type`, `station_article_id`, `article_unit_id`, `reason`, `created_at`, `reference`, `details`) VALUES
(1, 0.000000, 1.000000, 0.000000, 0.000000, 0.000000, 'ADJUSTMENT', 1, 1, NULL, '2026-08-13 11:11:17', NULL, NULL),
(2, 5.000000, 1.000000, 5.000000, 0.000000, 5.000000, 'STOCKTAKE', 2, 3, 'Inventaire physique', '2026-08-13 08:18:54', NULL, NULL),
(3, 10.000000, 1.000000, 10.000000, 0.000000, 10.000000, 'STOCKTAKE', 1, 1, 'Inventaire physique', '2026-08-13 08:18:54', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `pump_attendant`
--

CREATE TABLE `pump_attendant` (
  `id` int(11) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `full_name` varchar(120) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `station_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pump_attendant`
--

INSERT INTO `pump_attendant` (`id`, `code`, `full_name`, `contact`, `is_active`, `station_id`) VALUES
(1, 'POMPISTE-001', 'RAKOTO', '034 55 671 88', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `shop_sale_items`
--

CREATE TABLE `shop_sale_items` (
  `id` int(11) NOT NULL,
  `article_name_snapshot` varchar(255) NOT NULL,
  `unit_name_snapshot` varchar(255) NOT NULL,
  `conversion_factor_snapshot` decimal(15,6) NOT NULL,
  `quantity` decimal(15,6) NOT NULL,
  `base_quantity` decimal(15,6) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `discount_amount` decimal(15,2) DEFAULT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `station_article_id` int(11) NOT NULL,
  `station_article_unit_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stations`
--

CREATE TABLE `stations` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `creat_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `gerant` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stations`
--

INSERT INTO `stations` (`id`, `code`, `name`, `address`, `city`, `contact`, `email`, `status`, `creat_at`, `updated_at`, `gerant`) VALUES
(1, 'ST-001', 'Analakely', 'Analakely III Terc', 'Antananarivo', '0348346149', 'mamiirakotomamonjy@gmail.com', 'ACTIVE', '2026-08-13 07:03:21', '2026-08-13 07:11:55', 'Mamy'),
(2, 'ST-002', 'ANTANIMENA', 'ANTANIMENA BRED MADA', 'ANTANANARIVO', '+261 34 000 99', 'antanimenajov@gmail.com', 'ACTIVE', '2026-08-13 07:12:59', NULL, 'GERANT ANTANIMENA');

-- --------------------------------------------------------

--
-- Structure de la table `station_articles`
--

CREATE TABLE `station_articles` (
  `id` int(11) NOT NULL,
  `current_sock_base` decimal(15,6) NOT NULL,
  `minimum_stock_base` decimal(15,6) NOT NULL,
  `is_active` tinyint(4) NOT NULL,
  `station_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `station_articles`
--

INSERT INTO `station_articles` (`id`, `current_sock_base`, `minimum_stock_base`, `is_active`, `station_id`, `article_id`) VALUES
(1, 10.000000, 0.000000, 1, 1, 1),
(2, 5.000000, 0.000000, 1, 1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `station_article_units`
--

CREATE TABLE `station_article_units` (
  `id` int(11) NOT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `sale_price` decimal(15,2) NOT NULL,
  `wholesale_price` decimal(15,2) NOT NULL,
  `minimum_sale_price` decimal(15,2) NOT NULL,
  `is_active` tinyint(4) NOT NULL,
  `creat_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `station_article_id` int(11) NOT NULL,
  `article_unit_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `station_article_units`
--

INSERT INTO `station_article_units` (`id`, `purchase_price`, `sale_price`, `wholesale_price`, `minimum_sale_price`, `is_active`, `creat_at`, `updated_at`, `station_article_id`, `article_unit_id`) VALUES
(1, 1500.00, 1800.00, 1700.00, 1800.00, 1, '2026-08-13 07:49:18', '2026-08-13 08:03:43', 1, 1),
(2, 15000.00, 18000.00, 17000.00, 18000.00, 1, '2026-08-13 07:49:18', '2026-08-13 08:03:43', 1, 2),
(3, 2000.00, 2500.00, 2300.00, 2500.00, 1, '2026-08-13 07:59:16', NULL, 2, 3),
(4, 18000.00, 25000.00, 23000.00, 25000.00, 1, '2026-08-13 07:59:16', NULL, 2, 4);

-- --------------------------------------------------------

--
-- Structure de la table `station_users`
--

CREATE TABLE `station_users` (
  `id` int(11) NOT NULL,
  `is_active` tinyint(4) NOT NULL,
  `assigned_at` datetime NOT NULL,
  `station_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `supplier`
--

CREATE TABLE `supplier` (
  `id` int(11) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `station_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `supplier`
--

INSERT INTO `supplier` (`id`, `code`, `name`, `contact_person`, `phone`, `email`, `address`, `is_active`, `created_at`, `station_id`) VALUES
(1, 'FRS-001', 'FRS CARBURANT', '032', '034', 'frs@gmail.com', 'faefefa', 1, '2026-08-13 09:13:52', 1);

-- --------------------------------------------------------

--
-- Structure de la table `supplier_invoice`
--

CREATE TABLE `supplier_invoice` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(60) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `invoice_type` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `delivery_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `supplier_invoice`
--

INSERT INTO `supplier_invoice` (`id`, `invoice_number`, `invoice_date`, `due_date`, `total_amount`, `invoice_type`, `description`, `is_active`, `created_at`, `supplier_id`, `station_id`, `delivery_id`) VALUES
(1, 'FACT 001', '2026-08-13', '2026-09-06', 100000000.00, 'FUEL', 'Livraison 0001 - 20000 L', 1, '2026-08-13 09:14:31', 1, 1, 1),
(2, 'fact 002', '2026-08-13', '2026-08-29', 50000.00, 'FUEL', 'Livraison 0001 - 10 L', 1, '2026-08-13 09:18:35', 1, 1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `supplier_payment`
--

CREATE TABLE `supplier_payment` (
  `id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(30) NOT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `station_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `supplier_payment`
--

INSERT INTO `supplier_payment` (`id`, `payment_date`, `amount`, `payment_method`, `reference`, `status`, `note`, `created_at`, `supplier_id`, `invoice_id`, `station_id`) VALUES
(1, '2026-08-13', 100000000.00, 'BANK_TRANSFER', 'VIREMENT 001', 'EXECUTED', 'payement', '2026-08-13 09:14:57', 1, 1, 1),
(2, '2026-08-13', 20000.00, 'DIRECT_DEBIT', 'FTAFTFSTAFS', 'EXECUTED', 'payement de 20 000 Ar', '2026-08-13 09:19:03', 1, 2, 1),
(3, '2026-08-13', 30000.00, 'BANK_TRANSFER', NULL, 'EXECUTED', NULL, '2026-08-13 09:19:19', 1, 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `units`
--

INSERT INTO `units` (`id`, `code`, `name`, `symbol`, `is_active`) VALUES
(1, 'COCA-P-1', 'PIECE', 'U', 1),
(2, 'COCA-P-10', 'PAQUET', 'P', 1),
(3, 'PCS', 'PIECE', 'PCS', 1),
(4, 'PQT', 'PAQUET', 'PQT', 1);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `creat_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_BFDD3168BCF5E72D` (`categorie_id`);

--
-- Index pour la table `articles_units`
--
ALTER TABLE `articles_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1525CBD7294869C` (`article_id`),
  ADD KEY `IDX_1525CBDF8BD700D` (`unit_id`);

--
-- Index pour la table `article_categorie`
--
ALTER TABLE `article_categorie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `fuel_delivery`
--
ALTER TABLE `fuel_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_89BD6BF21BDB235` (`station_id`),
  ADD KEY `IDX_89BD6BF15C652B5` (`tank_id`);

--
-- Index pour la table `fuel_nozzle`
--
ALTER TABLE `fuel_nozzle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_743D9BEB9769C65` (`pump_id`),
  ADD KEY `IDX_743D9BE15C652B5` (`tank_id`);

--
-- Index pour la table `fuel_payment_method`
--
ALTER TABLE `fuel_payment_method`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_payment_method_station_code` (`station_id`,`code`),
  ADD KEY `IDX_6829B7BC21BDB235` (`station_id`);

--
-- Index pour la table `fuel_pump`
--
ALTER TABLE `fuel_pump`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_8868E9FC21BDB235` (`station_id`);

--
-- Index pour la table `fuel_shift_reading`
--
ALTER TABLE `fuel_shift_reading`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_88C5DC2021BDB235` (`station_id`),
  ADD KEY `IDX_88C5DC2050F8DC12` (`nozzle_id`),
  ADD KEY `IDX_88C5DC204DE0C235` (`attendant_id`);

--
-- Index pour la table `fuel_tank`
--
ALTER TABLE `fuel_tank`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_BD6DEF2821BDB235` (`station_id`),
  ADD KEY `IDX_BD6DEF286A70FE35` (`fuel_type_id`);

--
-- Index pour la table `fuel_type`
--
ALTER TABLE `fuel_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_FUEL_CODE` (`code`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`);

--
-- Index pour la table `mouvement_stock`
--
ALTER TABLE `mouvement_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_61E2C8EBF873CB1E` (`station_article_id`),
  ADD KEY `IDX_61E2C8EB156F34BA` (`article_unit_id`),
  ADD KEY `IDX_MOVEMENT_REFERENCE` (`reference`);

--
-- Index pour la table `pump_attendant`
--
ALTER TABLE `pump_attendant`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C8967CFA21BDB235` (`station_id`);

--
-- Index pour la table `shop_sale_items`
--
ALTER TABLE `shop_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_4BB15CE8F873CB1E` (`station_article_id`),
  ADD KEY `IDX_4BB15CE8DAF6E14C` (`station_article_unit_id`);

--
-- Index pour la table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `station_articles`
--
ALTER TABLE `station_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3347EB3E21BDB235` (`station_id`),
  ADD KEY `IDX_3347EB3E7294869C` (`article_id`);

--
-- Index pour la table `station_article_units`
--
ALTER TABLE `station_article_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_70F8FAFF873CB1E` (`station_article_id`),
  ADD KEY `IDX_70F8FAF156F34BA` (`article_unit_id`);

--
-- Index pour la table `station_users`
--
ALTER TABLE `station_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_4C05DADF21BDB235` (`station_id`),
  ADD KEY `IDX_4C05DADFA76ED395` (`user_id`);

--
-- Index pour la table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_9B2A6C7E21BDB235` (`station_id`);

--
-- Index pour la table `supplier_invoice`
--
ALTER TABLE `supplier_invoice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1100635B2ADD6D8C` (`supplier_id`),
  ADD KEY `IDX_1100635B21BDB235` (`station_id`),
  ADD KEY `IDX_1100635B12136921` (`delivery_id`);

--
-- Index pour la table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_EC4DF0122ADD6D8C` (`supplier_id`),
  ADD KEY `IDX_EC4DF0122989F1FD` (`invoice_id`),
  ADD KEY `IDX_EC4DF01221BDB235` (`station_id`);

--
-- Index pour la table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `articles_units`
--
ALTER TABLE `articles_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `article_categorie`
--
ALTER TABLE `article_categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fuel_delivery`
--
ALTER TABLE `fuel_delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `fuel_nozzle`
--
ALTER TABLE `fuel_nozzle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fuel_payment_method`
--
ALTER TABLE `fuel_payment_method`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `fuel_pump`
--
ALTER TABLE `fuel_pump`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fuel_shift_reading`
--
ALTER TABLE `fuel_shift_reading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fuel_tank`
--
ALTER TABLE `fuel_tank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fuel_type`
--
ALTER TABLE `fuel_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvement_stock`
--
ALTER TABLE `mouvement_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `pump_attendant`
--
ALTER TABLE `pump_attendant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `shop_sale_items`
--
ALTER TABLE `shop_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stations`
--
ALTER TABLE `stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `station_articles`
--
ALTER TABLE `station_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `station_article_units`
--
ALTER TABLE `station_article_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `station_users`
--
ALTER TABLE `station_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `supplier_invoice`
--
ALTER TABLE `supplier_invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `FK_BFDD3168BCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `article_categorie` (`id`);

--
-- Contraintes pour la table `articles_units`
--
ALTER TABLE `articles_units`
  ADD CONSTRAINT `FK_1525CBD7294869C` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `FK_1525CBDF8BD700D` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Contraintes pour la table `fuel_delivery`
--
ALTER TABLE `fuel_delivery`
  ADD CONSTRAINT `FK_89BD6BF15C652B5` FOREIGN KEY (`tank_id`) REFERENCES `fuel_tank` (`id`),
  ADD CONSTRAINT `FK_89BD6BF21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Contraintes pour la table `fuel_nozzle`
--
ALTER TABLE `fuel_nozzle`
  ADD CONSTRAINT `FK_743D9BE15C652B5` FOREIGN KEY (`tank_id`) REFERENCES `fuel_tank` (`id`),
  ADD CONSTRAINT `FK_743D9BEB9769C65` FOREIGN KEY (`pump_id`) REFERENCES `fuel_pump` (`id`);

--
-- Contraintes pour la table `fuel_payment_method`
--
ALTER TABLE `fuel_payment_method`
  ADD CONSTRAINT `FK_6829B7BC21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Contraintes pour la table `fuel_pump`
--
ALTER TABLE `fuel_pump`
  ADD CONSTRAINT `FK_8868E9FC21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Contraintes pour la table `fuel_shift_reading`
--
ALTER TABLE `fuel_shift_reading`
  ADD CONSTRAINT `FK_88C5DC2021BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `FK_88C5DC204DE0C235` FOREIGN KEY (`attendant_id`) REFERENCES `pump_attendant` (`id`),
  ADD CONSTRAINT `FK_88C5DC2050F8DC12` FOREIGN KEY (`nozzle_id`) REFERENCES `fuel_nozzle` (`id`);

--
-- Contraintes pour la table `fuel_tank`
--
ALTER TABLE `fuel_tank`
  ADD CONSTRAINT `FK_BD6DEF2821BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `FK_BD6DEF286A70FE35` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_type` (`id`);

--
-- Contraintes pour la table `mouvement_stock`
--
ALTER TABLE `mouvement_stock`
  ADD CONSTRAINT `FK_61E2C8EB156F34BA` FOREIGN KEY (`article_unit_id`) REFERENCES `articles_units` (`id`),
  ADD CONSTRAINT `FK_61E2C8EBF873CB1E` FOREIGN KEY (`station_article_id`) REFERENCES `station_articles` (`id`);

--
-- Contraintes pour la table `pump_attendant`
--
ALTER TABLE `pump_attendant`
  ADD CONSTRAINT `FK_C8967CFA21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Contraintes pour la table `shop_sale_items`
--
ALTER TABLE `shop_sale_items`
  ADD CONSTRAINT `FK_4BB15CE8DAF6E14C` FOREIGN KEY (`station_article_unit_id`) REFERENCES `station_article_units` (`id`),
  ADD CONSTRAINT `FK_4BB15CE8F873CB1E` FOREIGN KEY (`station_article_id`) REFERENCES `station_articles` (`id`);

--
-- Contraintes pour la table `station_articles`
--
ALTER TABLE `station_articles`
  ADD CONSTRAINT `FK_3347EB3E21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `FK_3347EB3E7294869C` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `station_article_units`
--
ALTER TABLE `station_article_units`
  ADD CONSTRAINT `FK_70F8FAF156F34BA` FOREIGN KEY (`article_unit_id`) REFERENCES `articles_units` (`id`),
  ADD CONSTRAINT `FK_70F8FAFF873CB1E` FOREIGN KEY (`station_article_id`) REFERENCES `station_articles` (`id`);

--
-- Contraintes pour la table `station_users`
--
ALTER TABLE `station_users`
  ADD CONSTRAINT `FK_4C05DADF21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `FK_4C05DADFA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `supplier`
--
ALTER TABLE `supplier`
  ADD CONSTRAINT `FK_9B2A6C7E21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`);

--
-- Contraintes pour la table `supplier_invoice`
--
ALTER TABLE `supplier_invoice`
  ADD CONSTRAINT `FK_1100635B12136921` FOREIGN KEY (`delivery_id`) REFERENCES `fuel_delivery` (`id`),
  ADD CONSTRAINT `FK_1100635B21BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `FK_1100635B2ADD6D8C` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`);

--
-- Contraintes pour la table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  ADD CONSTRAINT `FK_EC4DF01221BDB235` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `FK_EC4DF0122989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `supplier_invoice` (`id`),
  ADD CONSTRAINT `FK_EC4DF0122ADD6D8C` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
