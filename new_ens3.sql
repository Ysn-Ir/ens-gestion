-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3305
-- Generation Time: Sep 16, 2025 at 08:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `new_ens`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `user_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `nationalite` varchar(50) DEFAULT NULL,
  `CIN` varchar(20) DEFAULT NULL,
  `fonction` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rapport` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`user_id`, `nom`, `prenom`, `telephone`, `adresse`, `date_naissance`, `nationalite`, `CIN`, `fonction`, `created_at`, `updated_at`, `rapport`) VALUES
(1, 'AdminNom', 'AdminPrenom', '021548847', 'fes', '2000-11-05', 'marocain', 'AD1', 'Administrateur', '2025-07-18 10:22:29', '2025-07-18 10:33:25', NULL),
(9, 'AdminNom', 'AdminPrenom', '052455157', 'fes', '1992-05-05', 'marocain', 'AD9', 'Administrateur', '2025-07-18 10:22:29', '2025-07-18 10:33:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `annees_academiques`
--

CREATE TABLE `annees_academiques` (
  `annee_id` varchar(50) NOT NULL,
  `current_flag` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `annees_academiques`
--

INSERT INTO `annees_academiques` (`annee_id`, `current_flag`) VALUES
('2020-2021', 0),
('2021-2022', 0),
('2022-2023', 0),
('2023-2024', 0),
('2024-2025', 1),
('2025-2026', 0);

-- --------------------------------------------------------

--
-- Table structure for table `cycles`
--

CREATE TABLE `cycles` (
  `cycle_id` int(11) NOT NULL,
  `nom` varchar(20) NOT NULL,
  `Nombre_semestre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cycles`
--

INSERT INTO `cycles` (`cycle_id`, `nom`, `Nombre_semestre`) VALUES
(1, 'DEUG', 4),
(2, 'License', 6),
(3, 'Master', 4),
(4, 'Doctorat', 4),
(1000, 'Test Bachelor', 4),
(1001, 'Test Master', 6);

-- --------------------------------------------------------

--
-- Table structure for table `departements`
--

CREATE TABLE `departements` (
  `department_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `head_professor_id` int(11) DEFAULT NULL,
  `annee_accreditation` varchar(50) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `prof_actuel` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departements`
--

INSERT INTO `departements` (`department_id`, `nom`, `head_professor_id`, `annee_accreditation`, `date_debut`, `date_fin`, `prof_actuel`) VALUES
(1, 'Informatique', 23663, '2020-2021', '2020-09-01', '2023-07-01', 1),
(2, 'Mathématiques', 3, '2020-2021', '2021-09-01', '2024-07-01', 1),
(3, 'Physique', 4, '2020-2021', '2019-09-01', '2022-07-01', 1),
(4, 'Chimie', 5, '2020-2021', '2020-09-01', '2022-07-01', 1),
(5, 'Biologie', 6, '2020-2021', '2021-09-01', '2023-07-01', 1),
(6, 'Génie Civil', 7, '2020-2021', '2020-09-01', '2024-07-01', 1),
(7, 'Génie Mécanique', 8, '2020-2021', '2022-09-01', '2025-07-01', 1),
(8, 'Génie Electrique', 9, '2020-2021', '2023-09-01', '2025-10-05', 1),
(9, 'Génie Industriel', 10, '2020-2021', '2020-09-01', '2023-07-01', 1),
(10, 'Génie Chimique', 11, '2020-2021', '2021-09-01', '2024-07-01', 1),
(1000, 'Test Informatics', NULL, '2024-2025', NULL, NULL, NULL),
(1001, 'Test Electrical', NULL, '2024-2025', NULL, NULL, NULL),
(1006, 'New Depart', 23668, '2025-2026', '2025-09-02', '2025-10-10', 1);

-- --------------------------------------------------------

--
-- Table structure for table `diplomes`
--

CREATE TABLE `diplomes` (
  `diplome_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diplomes`
--

INSERT INTO `diplomes` (`diplome_id`, `nom`, `cycle_id`, `field_id`, `department_id`) VALUES
(1, 'Diplôme d\'Ingénieur en Informatique', 2, 1, 1),
(2, 'Diplôme d\'Ingénieur en Data Science', 2, 2, 1),
(3, 'Diplôme d\'Ingénieur en Mathématiques Appliquées', 2, 3, 1),
(4, 'Diplôme d\'Ingénieur en Physique Quantique', 2, 4, 3),
(5, 'Diplôme d\'Ingénieur en Chimie Organique', 2, 5, 4),
(6, 'Diplôme d\'Ingénieur en Biologie Moléculaire', 2, 6, 5),
(7, 'Diplôme d\'Ingénieur en Génie Civil', 2, 7, 1),
(8, 'Diplôme d\'Ingénieur en Génie Mécanique', 2, 8, 1),
(9, 'Diplôme d\'Ingénieur en Génie Electrique', 2, 9, 8),
(10, 'Diplôme d\'Ingénieur en Génie Industriel', 2, 10, 9),
(1009, 'azsed', 2, 1007, 1006),
(1010, 'sxsxdcd', 1, 1008, 1006);

-- --------------------------------------------------------

--
-- Table structure for table `elements`
--

CREATE TABLE `elements` (
  `element_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `module_id` int(11) NOT NULL,
  `Ref_prof_element` int(11) DEFAULT NULL,
  `Ref_prof_tp` int(11) DEFAULT NULL,
  `coeff_ecrit` decimal(5,3) DEFAULT NULL,
  `coeff_tp` decimal(5,3) DEFAULT NULL,
  `coeff_element` decimal(5,3) DEFAULT NULL,
  `coeff_cc` decimal(5,3) DEFAULT NULL,
  `coeff_td` decimal(5,3) DEFAULT NULL,
  `presentiel` int(11) DEFAULT NULL,
  `a_distance` int(11) DEFAULT NULL,
  `en_alternance` int(11) DEFAULT NULL,
  `horraire_tp` int(11) DEFAULT NULL,
  `horraire_td` int(11) DEFAULT NULL,
  `horraire_cours` int(11) DEFAULT NULL,
  `horraire_evaluation` int(11) DEFAULT NULL,
  `horraire_activite_pratique` int(11) DEFAULT NULL,
  `Ref_prof_cours` int(11) DEFAULT NULL,
  `Ref_prof_td` int(11) DEFAULT NULL,
  `langue` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `elements`
--

INSERT INTO `elements` (`element_id`, `nom`, `module_id`, `Ref_prof_element`, `Ref_prof_tp`, `coeff_ecrit`, `coeff_tp`, `coeff_element`, `coeff_cc`, `coeff_td`, `presentiel`, `a_distance`, `en_alternance`, `horraire_tp`, `horraire_td`, `horraire_cours`, `horraire_evaluation`, `horraire_activite_pratique`, `Ref_prof_cours`, `Ref_prof_td`, `langue`) VALUES
(1, 'Structures de Données', 2, 24, 9, 0.250, 0.500, 1.000, 0.000, 0.250, 0, 0, 0, 0, 0, 0, 0, 0, 5, 8, 'français'),
(2, 'Algorithmique', 1, 23665, 23660, 0.250, 0.250, 1.000, 0.250, 0.250, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 7, 'français'),
(3, 'Bases de Données', 3, 2, 3, 0.250, 0.250, 1.000, 0.250, 0.250, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 'français'),
(4, 'Réseaux', 4, 2, 23666, 0.250, 0.500, 1.000, 0.000, 0.250, 0, 0, 0, 0, 0, 0, 0, 0, 3, 23667, 'français'),
(5, 'Statistiques', 5, 23661, 9, 0.000, 0.000, 1.000, 0.000, 1.000, 0, 0, 0, 0, 0, 0, 0, 0, 23660, 23670, 'français'),
(6, 'Machine Learning', 6, 3, 10, 0.250, 0.250, 1.000, 0.250, 0.250, 0, 0, 0, 0, 0, 0, 0, 0, 3, 9, 'français'),
(7, 'Big Data', 7, 3, 4, 0.000, 0.000, 1.000, 1.000, 0.000, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 'français'),
(8, 'Visualisation', 8, 3, 4, 0.000, 1.000, 1.000, 0.000, 0.000, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 'français'),
(9, 'Algèbre Linéaire', 9, 3, 4, 0.500, 0.000, 1.000, 0.500, 0.000, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 'français'),
(10, 'Analyse', 10, 3, 4, 1.000, 0.000, 1.000, 0.000, 0.000, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 'français'),
(100028, 'el1', 1027, NULL, NULL, 0.300, 0.200, 0.500, 0.200, 0.300, 10, 10, 10, 10, 1, 10, 4, 10, 11, 6, 'anglais'),
(100029, 'el2', 1027, NULL, NULL, 0.250, 0.250, 0.500, 0.500, 0.000, 5, 4, 6, 3, 2, 1, 5, 4, 6, 6, 'français'),
(100030, 'el3', 1027, NULL, 7, 0.300, 0.200, 0.000, 0.200, 0.300, 7, 7, 7, 0, 7, 7, 77, 7, 11, 3, 'français'),
(100031, 'C++', 14, NULL, NULL, 1.000, 0.000, 0.000, 0.000, 0.000, 9, 2, 1, 1, 2, 3, 5, 6, NULL, NULL, 'anglais'),
(100032, 'Java', 14, NULL, 23658, 1.000, 0.000, 0.000, 0.000, 0.000, 6, 3, 2, 1, 5, 69, 5, 0, 3, 7, 'français'),
(100033, 'Python', 14, NULL, 23664, 1.000, 0.000, 1.000, 0.000, 0.000, 45, 12, 3, 1, 2, 3, 9, 6, 23659, 5, 'anglais'),
(100040, 'douaa', 1026, NULL, 23665, 0.300, 0.300, 1.000, 0.200, 0.200, 9, 5, 6, 36, 2, 1, 8, 5, 11, 7, 'français'),
(100041, 'azsde', 1028, NULL, 23667, 0.200, 0.300, 1.000, 0.300, 0.200, 10, 10, 10, 10, 20, 10, 10, 2, 23666, 23669, 'français'),
(100042, 'edfe', 1029, NULL, 23664, 0.200, 0.300, 1.000, 0.300, 0.200, 10, 10, 10, 10, 10, 10, 10, 10, 3, 6, 'français'),
(100043, 'el2', 1030, NULL, 23661, 0.300, 0.200, 0.500, 0.200, 0.300, 10, 10, 10, 10, 10, 10, 10, 10, 6, 7, 'anglais'),
(100044, 'azsdc', 1030, NULL, 23667, 0.300, 0.200, 0.500, 0.200, 0.300, 23, 2, 5, 0, 20, 10, 0, 5, 3, 23667, 'arabe');

-- --------------------------------------------------------

--
-- Table structure for table `etapes`
--

CREATE TABLE `etapes` (
  `etape_id` int(11) NOT NULL,
  `nom_etape` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `etapes`
--

INSERT INTO `etapes` (`etape_id`, `nom_etape`) VALUES
(1, '1ere annee'),
(2, '2eme annee'),
(3, '3eme annee'),
(4, '4eme annee'),
(5, '5eme annee'),
(1000, 'Test Etape Bachelor'),
(1001, 'Test Etape Master');

-- --------------------------------------------------------

--
-- Table structure for table `etudiants`
--

CREATE TABLE `etudiants` (
  `user_id` int(11) NOT NULL,
  `cin` varchar(255) NOT NULL,
  `cne` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL,
  `nationalite` varchar(50) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `cycle_id` int(11) DEFAULT 1,
  `actuel` tinyint(1) DEFAULT NULL,
  `group_id` int(11) DEFAULT 1,
  `rapport` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `etudiants`
--

INSERT INTO `etudiants` (`user_id`, `cin`, `cne`, `nom`, `prenom`, `date_naissance`, `nationalite`, `telephone`, `adresse`, `department_id`, `field_id`, `cycle_id`, `actuel`, `group_id`, `rapport`) VALUES
(12, 'S12345678', 'E12345678', 'Dupont', 'Jean', '2000-01-15', 'Française', '0612345678', '123 Rue de Paris', 2, 3, 1, 1, 5, NULL),
(13, 'S23456789', 'E23456789', 'Martin', 'Sophie', '2000-02-20', 'Française', '0623456789', '456 Avenue des Fleurs', 1, 1, 2, 0, 1, NULL),
(14, 'S34567890', 'E34567890', 'Bernard', 'Pierre', '2000-03-25', 'Française', '0634567890', '789 Boulevard des Arbres', 1, 1, 2, 0, 2, NULL),
(15, 'S45678901', 'E45678901', 'Petit', 'Marie', '2000-04-30', 'Française', '0645678901', '101 Rue du Soleil', 1, NULL, 3, 1, 2, NULL),
(16, 'S56789012', 'E56789012', 'Durand', 'Luc', '2000-05-05', 'Française', '0656789012', '202 Avenue de la Lune', 1, 2, 2, 0, 3, NULL),
(17, 'S67890123', 'E67890123', 'Leroy', 'Julie', '2000-06-10', 'Française', '0667890123', '303 Boulevard des Étoiles', 1, 2, 2, 0, 3, NULL),
(18, 'S78901234', 'E78901234', 'Moreau', 'Thomas', '2000-07-15', 'Française', '0678901234', '404 Rue de la Terre', 1, 2, 2, 0, 4, NULL),
(19, 'S89012345', 'E89012345', 'Simon', 'Laura', '2000-08-20', 'Française', '0689012345', '505 Avenue du Ciel', 1, 2, 2, 0, 4, NULL),
(20, 'S90123456', 'E90123456', 'Laurent', 'Paul', '2000-09-25', 'Française', '0690123456', '606 Boulevard des Nuages', 2, 3, 2, 1, 5, NULL),
(21, 'S01234567', 'E01234567', 'Michel', 'Alice', '2000-10-30', 'Française', '0601234567', '707 Rue des Planètes', 2, 3, 2, 1, 5, NULL),
(22, 'Yassine123', 'Yassine123', 'ouali', 'yassine', '2004-06-28', 'Marocaine', '0609408356', '28 lot jnan zhar fes', 1, 1, 2, 1, NULL, NULL),
(1000, '', '', 'Test_Pass', 'Alice', '0000-00-00', NULL, NULL, NULL, 1000, NULL, 1000, 1, NULL, NULL),
(1004, 'wewerwrewwerwrwer23', 'gergaerga gergaeg', 'werwraegergaer gth', 'yassinewerwerwrwr', '2233-03-12', 'Marocaine', '0609408356', '28 lot jnan zhar fes', 1001, 4, 1, NULL, NULL, NULL),
(1005, '12345678909UIYFDFSGFGHMGNBF', 'S23R232T34T34T34T', '34Q34VTQ34TQ3VT', '3TV34TQ34TV34T3', '2000-12-12', 'Marocaine', '223422523', '12324354GDSGRG WE', 2, 4, 2, NULL, NULL, NULL),
(1006, '43wetrdthfhyry5e4s', 'dzgrge34tv', 'dhdzfhzde4g43g', 'sdgw434523', '4323-12-01', 'Marocaine', '34y5urjhtgew5y', NULL, 3, 4, 1, 1, 8, NULL),
(1007, '1whthmgfxrgsa4', 'fwefwefwefsfsfsSvxcvx', 'sdfsfwefw', 'dfsdfsdfsdf', '1111-11-11', 'Marocaine', '12345676312', NULL, 1, 1, 2, 1, NULL, NULL),
(1015, 'testcin1', 'yassine1234', 'ouali', 'yassine', '1212-12-12', 'Marocaine', '11111', NULL, NULL, NULL, 3, 1, 0, NULL),
(1026, 'sdsdfsfw3fsvzdfdzrgzg', 'aasdadawd', 'moha', 'med', '2000-01-01', 'Marocaine', '0609408356', '28 lot jnan zhar fes', NULL, NULL, 3, 1, 0, NULL),
(1028, 'sdfsfefsef', 'asdadwegxvx', 'wgegrwg', 'wgdfbdfb', '0000-00-00', 'Marocaine', 'dfdgg', NULL, 1001, NULL, 3, 0, 0, NULL),
(1029, 'wtrethfjgkhjgfds', 'awfewfwefwe', 'fwfewfwefef', 'sfsefSgergerggs', '2000-01-01', 'Marocaine', 'sgdwgwggerg', NULL, NULL, NULL, 1, 0, 0, NULL),
(1032, '12121213433rwefewwe', 'wiejrwoprjwoerw', 'wirjrjwerpowe', 'iewjrpowerojw', '2000-01-01', 'Marocaine', '1122134', NULL, 1001, NULL, 2, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `filieres`
--

CREATE TABLE `filieres` (
  `field_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `head_professor_id` int(11) DEFAULT NULL,
  `annee_accreditation` varchar(50) DEFAULT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `debut_affectation` date DEFAULT NULL,
  `fin_affectation` date DEFAULT NULL,
  `annee_fin_accrediation` varchar(100) DEFAULT NULL,
  `status` enum('actif','inactif') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `filieres`
--

INSERT INTO `filieres` (`field_id`, `nom`, `department_id`, `head_professor_id`, `annee_accreditation`, `cycle_id`, `debut_affectation`, `fin_affectation`, `annee_fin_accrediation`, `status`) VALUES
(1, 'Informatique Fondamentale', 1, 23659, '2020-2021', 2, '2025-09-03', '2025-10-10', '2024-2025', 'actif'),
(2, 'Data Science', 1, 23658, '2020-2021', 2, '2025-09-10', '2025-10-05', '2024-2025', 'actif'),
(3, 'Mathématiques Appliquées', 1, 23666, '2020-2021', 2, '2025-09-17', '2025-10-04', '2025-2026', 'actif'),
(4, 'Physique Quantique', 3, 23661, '2020-2021', 2, '2025-09-03', '2025-10-04', '2025-2026', 'actif'),
(5, 'Chimie Organique', 4, 23665, '2020-2021', 2, '2025-09-08', '2025-10-03', '2025-2026', 'actif'),
(6, 'Biologie Moléculaire', 5, 23, '2020-2021', 2, '2025-09-01', '2025-09-26', '2025-2026', 'actif'),
(7, 'Génie Civil', 1, 23662, '2020-2021', 2, '2025-09-02', '2025-09-25', '2025-2026', 'actif'),
(8, 'Génie Mécanique', 1, 23664, '2020-2021', 2, '2025-09-02', '2025-10-05', '2025-2026', 'actif'),
(9, 'Génie Electrique', 8, 23667, '2020-2021', 2, '2025-09-02', '2025-10-03', '2023-2024', 'actif'),
(10, 'Génie Industriel', 9, 23660, '2020-2021', 2, '2025-09-03', '2025-11-09', '2022-2023', 'actif'),
(1007, 'Nv Filiere', 1006, 23669, '2020-2021', 2, '2025-09-02', '2025-10-08', '2023-2024', 'actif'),
(1008, 'aqs', 1006, 23670, '2020-2021', 1, '2025-09-04', '2025-09-27', '2023-2024', 'actif');

-- --------------------------------------------------------

--
-- Table structure for table `grading_rules`
--

CREATE TABLE `grading_rules` (
  `rule_id` int(11) NOT NULL,
  `scope` varchar(50) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `rule_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_rules`
--

INSERT INTO `grading_rules` (`rule_id`, `scope`, `rule_name`, `rule_value`, `created_at`, `updated_at`) VALUES
(1, 'element', 'ratt_threshold', '8.00', '2025-08-20 14:23:51', '2025-08-28 15:51:24'),
(2, 'module', 'pass_threshold', '10.00', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(3, 'module', 'post_ratt_cap', '10.00', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(4, 'semester', 'max_nv_modules', '2', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(5, 'year', 'max_nv_modules_per_semester', '2', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(6, 'year', 'max_year_fails', '3', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(7, 'year', 'retake_year_in_cycle', '{\"deug\": 2, \"license\": 3, \"master\": 2, \"doctorat\": 3}', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(8, 'element', 'pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(9, 'element', 'ratt_threshold', '7', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(10, 'element', 'rattrapage_pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(11, 'element', 'single_element_ratt', 'true', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(12, 'element', 'decision_v', '{\"decision\": \"V\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(13, 'element', 'decision_r', '{\"decision\": \"R\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(14, 'element', 'decision_vr', '{\"decision\": \"VR\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(15, 'element', 'decision_nv', '{\"decision\": \"NV\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(16, 'module', 'pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(17, 'module', 'post_ratt_cap', '12', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(18, 'module', 'decision_v', '{\"decision\": \"V\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(19, 'module', 'decision_vr', '{\"decision\": \"VR\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(20, 'module', 'decision_nv', '{\"decision\": \"NV\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(21, 'semester', 'pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(22, 'semester', 'max_nv_modules', '2', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(23, 'semester', 'decision_v', '{\"decision\": \"V\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(24, 'semester', 'decision_vpc', '{\"decision\": \"VPC\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(25, 'semester', 'decision_nv', '{\"decision\": \"NV\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(26, 'semester', 'decision_f', '{\"decision\": \"F\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(27, 'year', 'pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(28, 'year', 'max_nv_modules_per_semester', '2', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(29, 'year', 'max_year_fails', '2', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(30, 'year', 'retake_year_in_cycle', '{\"deug\": 3, \"license\": 3, \"master\": 2, \"doctorat\": 2}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(31, 'year', 'decision_v', '{\"decision\": \"V\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(32, 'year', 'decision_vpc', '{\"decision\": \"VPC\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(33, 'year', 'decision_nv', '{\"decision\": \"NV\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(34, 'year', 'decision_f', '{\"decision\": \"F\"}', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(35, 'diploma', 'mention_thresholds', '{\"Passable\": 0, \"Bien\": 14, \"Très Bien\": 16}', '2025-08-20 16:50:45', '2025-08-20 16:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `groupes`
--

CREATE TABLE `groupes` (
  `group_id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `field_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groupes`
--

INSERT INTO `groupes` (`group_id`, `nom`, `field_id`, `section_id`) VALUES
(1, 'Groupe 1', 1, 1),
(2, 'Groupe 2', 1, 1),
(3, 'Groupe 1', 2, 3),
(4, 'Groupe 2', 2, 3),
(5, 'Groupe 1', 3, 5),
(6, 'Groupe 2', 3, 5),
(7, 'Groupe 1', 4, 7),
(8, 'Groupe 2', 4, 7),
(9, 'Groupe 1', 5, 9),
(10, 'Groupe 2', 5, 9),
(11, 'Groupe A-1', NULL, 11),
(12, 'Groupe A-2', NULL, 11),
(13, 'Groupe B-1', NULL, 12),
(14, 'Groupe B-2', NULL, 12),
(15, 'Groupe A-1', NULL, 13),
(16, 'Groupe A-2', NULL, 13),
(17, 'Groupe B-1', NULL, 14),
(19, 'Groupe C-1', NULL, 15),
(20, 'Groupe C-2', NULL, 15),
(21, 'Groupe A-1', NULL, 16),
(22, 'Groupe A-2', NULL, 16),
(23, 'Groupe B-1', NULL, 17),
(24, 'Groupe B-2', NULL, 17),
(25, 'Groupe C-1', NULL, 18),
(26, 'Groupe C-2', NULL, 18),
(27, 'Groupe D-1', NULL, 19),
(29, 'Groupe A-1', NULL, 20),
(30, 'Groupe A-2', NULL, 20),
(31, 'Groupe B-1', NULL, 21),
(32, 'Groupe B-2', NULL, 21),
(33, 'Groupe C-1', NULL, 22),
(34, 'Groupe C-2', NULL, 22),
(35, 'Groupe D-1', NULL, 23),
(37, 'Groupe A-1', NULL, 24),
(38, 'Groupe A-2', NULL, 24),
(39, 'Groupe B-1', NULL, 25),
(40, 'Groupe B-2', NULL, 25),
(41, 'Groupe A-1', NULL, 26),
(42, 'Groupe A-2', NULL, 26),
(43, 'Groupe B-1', NULL, 27),
(44, 'Groupe B-2', NULL, 27),
(45, 'Groupe A-1', NULL, 28),
(46, 'Groupe A-2', NULL, 28),
(47, 'Groupe B-1', NULL, 29),
(48, 'Groupe B-2', NULL, 29),
(49, 'Groupe A-1', NULL, 30),
(50, 'Groupe A-2', NULL, 30),
(51, 'Groupe B-1', NULL, 31),
(52, 'Groupe B-2', NULL, 31),
(53, 'Groupe A-1', NULL, 32),
(54, 'Groupe A-1', NULL, 33),
(55, 'Groupe A-1', NULL, 34),
(56, 'Groupe A-1', NULL, 35),
(60, 'Groupe A-1', NULL, 39),
(1000, 'Test Group A', NULL, 1000),
(1002, 'grptest1', NULL, 1002),
(1003, 'grptest2', NULL, 1003),
(1004, 'grptest3', NULL, 1004);

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `coefficient` decimal(5,0) NOT NULL DEFAULT 1,
  `semestre_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `code`, `nom`, `coefficient`, `semestre_id`, `field_id`, `type`) VALUES
(1, 'INF101', 'Algorithmique', 1, 1023, 1, 'Module d\'éducation'),
(2, 'INF102', 'Structures de Données', 1, 1020, 1, 'Module d\'éducation'),
(3, 'INF103', 'Bases de Données', 1, 1019, 1, 'Module d\'éducation'),
(4, 'INF104', 'Réseaux', 1, 1019, 1, 'Module d\'éducation'),
(5, 'DS101', 'Statistiques', 1, 2, 2, 'Module d\'éducation'),
(6, 'DS102', 'Machine Learning', 1, 2, 2, 'Module de spécialité'),
(7, 'DS103', 'Big Data', 1, 4, 2, 'Module de spécialité'),
(8, 'DS104', 'Visualisation', 1, 4, 2, 'Module de spécialité'),
(9, 'MAT101', 'Algèbre Linéaire', 1, 1028, 3, 'Module d\'éducation'),
(10, 'MAT102', 'Analyse', 1, 1028, 3, 'Module de spécialité'),
(14, 'M147', 'coding', 1, 3, 2, 'Module de spécialité'),
(1026, 'douaa', 'douaa', 1, 2, 2, 'Module d\'éducation'),
(1027, 'szdxc', 'aqszd', 1, 1131, 1007, 'Soft Skills'),
(1028, 'aqszdq', 'azsde', 1, 1023, 1, 'Soft Skills'),
(1029, 'zedf', 'edfe', 1, 1, 2, 'Module d\'éducation'),
(1030, 'dz', 'szd', 1, 7, 4, 'Module d\'éducation');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `note_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `element_id` int(11) DEFAULT NULL,
  `semestre_id` int(11) DEFAULT NULL,
  `annee_id` varchar(50) NOT NULL,
  `note_tp` decimal(5,2) DEFAULT NULL,
  `note_td` decimal(5,2) DEFAULT NULL,
  `note_cc` decimal(5,2) DEFAULT NULL,
  `note_exam` decimal(5,2) DEFAULT NULL,
  `note_rattrapage` decimal(5,2) DEFAULT NULL,
  `note_finale` decimal(5,2) DEFAULT NULL,
  `decision` enum('V','NV','R','VC') DEFAULT NULL,
  `decision_ratt` enum('VR','NV','VC') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`note_id`, `student_id`, `element_id`, `semestre_id`, `annee_id`, `note_tp`, `note_td`, `note_cc`, `note_exam`, `note_rattrapage`, `note_finale`, `decision`, `decision_ratt`) VALUES
(37, 12, 2, 1, '2024-2025', 12.00, 12.00, 12.00, 14.00, 7.00, 9.20, NULL, 'NV'),
(38, 12, 1, 1, '2024-2025', 11.00, 0.00, 10.00, 10.00, 8.00, 9.38, NULL, 'NV'),
(55, 12, 6, 1, '2024-2025', 12.00, 12.00, 12.00, 12.00, NULL, 12.00, 'V', NULL),
(111, 16, 6, 2, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(112, 17, 6, 2, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(113, 18, 6, 2, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(114, 19, 6, 2, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(117, 13, 2, 1, '2024-2025', NULL, NULL, NULL, 0.01, NULL, NULL, NULL, NULL),
(118, 13, 1, 1, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(119, 14, 2, 1, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(120, 14, 1, 1, '2024-2025', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(121, 15, 2, 1, '2024-2025', 12.00, 4.00, 9.00, 13.00, NULL, 11.96, 'V', NULL),
(122, 15, 1, 1, '2024-2025', 12.00, 12.00, 3.00, 0.00, 20.00, 13.62, NULL, 'VR');

-- --------------------------------------------------------

--
-- Table structure for table `note_annees`
--

CREATE TABLE `note_annees` (
  `student_id` int(11) NOT NULL,
  `annee_id` varchar(50) NOT NULL,
  `note_annee` decimal(5,2) NOT NULL,
  `decision_annee` enum('V','F','NV','VPC') DEFAULT NULL,
  `fail_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_annees`
--

INSERT INTO `note_annees` (`student_id`, `annee_id`, `note_annee`, `decision_annee`, `fail_count`) VALUES
(12, '2023-2024', 0.00, NULL, 0),
(12, '2024-2025', 11.10, 'V', 0),
(13, '2024-2025', 0.00, 'NV', 4),
(13, '2025-2026', 0.00, NULL, 1),
(14, '2024-2025', 0.00, 'NV', 4),
(15, '2024-2025', 10.98, 'V', 0),
(15, '2025-2026', 0.00, NULL, 0),
(16, '2024-2025', 0.00, 'NV', 4),
(16, '2025-2026', 0.00, NULL, 1),
(17, '2024-2025', 0.00, 'NV', 4),
(17, '2025-2026', 0.00, NULL, 1),
(18, '2024-2025', 0.00, 'NV', 4),
(18, '2025-2026', 0.00, NULL, 1),
(19, '2024-2025', 0.00, 'NV', 4),
(19, '2025-2026', 0.00, NULL, 1),
(1000, '2022-2023', 14.50, 'V', 0),
(1000, '2023-2024', 16.00, 'V', 0),
(1000, '2024-2025', 15.84, 'V', 0),
(1000, '2025-2026', 0.00, NULL, 0),
(1006, '2023-2024', 0.00, NULL, 0),
(1015, '2024-2025', 14.06, 'V', 2),
(1015, '2025-2026', 0.00, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `note_modules`
--

CREATE TABLE `note_modules` (
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `semestre_id` int(11) NOT NULL,
  `annee_id` varchar(50) NOT NULL,
  `note_module` decimal(5,2) NOT NULL,
  `decision` enum('V','R','NV','VC','VPC') DEFAULT NULL,
  `note_ratt` float DEFAULT NULL,
  `decision_ratt` enum('V','R','NV','VC','VR','VPC') DEFAULT NULL,
  `retake_status` enum('pending','scheduled','completed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_modules`
--

INSERT INTO `note_modules` (`student_id`, `module_id`, `semestre_id`, `annee_id`, `note_module`, `decision`, `note_ratt`, `decision_ratt`, `retake_status`) VALUES
(12, 1, 1, '2024-2025', 9.20, 'R', NULL, 'NV', 'pending'),
(12, 2, 1, '2024-2025', 9.38, 'R', NULL, 'NV', 'pending'),
(12, 6, 1, '2024-2025', 12.00, 'V', NULL, NULL, 'completed'),
(13, 1, 1, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(13, 2, 1, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(14, 1, 1, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(14, 2, 1, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(15, 1, 1, '2024-2025', 11.96, 'V', NULL, NULL, 'completed'),
(15, 2, 1, '2024-2025', 10.00, 'V', NULL, 'VR', 'completed'),
(16, 6, 2, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(17, 6, 2, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(18, 6, 2, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(19, 6, 2, '2024-2025', 0.00, 'R', NULL, NULL, 'completed'),
(22, 1, 1, '2024-2025', 0.00, NULL, NULL, NULL, NULL),
(22, 2, 1, '2024-2025', 0.00, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `note_semestres`
--

CREATE TABLE `note_semestres` (
  `student_id` int(11) NOT NULL,
  `semestre_id` int(11) NOT NULL,
  `annee_id` varchar(50) NOT NULL,
  `note_semestre` decimal(5,2) NOT NULL,
  `decision` enum('V','R','NV','VC','VPC') DEFAULT NULL,
  `nv_module_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_semestres`
--

INSERT INTO `note_semestres` (`student_id`, `semestre_id`, `annee_id`, `note_semestre`, `decision`, `nv_module_count`) VALUES
(12, 1, '2024-2025', 10.19, 'V', 0),
(12, 2, '2024-2025', 12.00, 'V', 0),
(12, 7, '2023-2024', 0.00, NULL, 0),
(13, 1, '2024-2025', 0.00, 'NV', 0),
(14, 1, '2024-2025', 0.00, 'NV', 0),
(15, 1, '2024-2025', 10.98, 'V', 0),
(16, 2, '2024-2025', 0.00, 'NV', 0),
(17, 2, '2024-2025', 0.00, 'NV', 0),
(18, 2, '2024-2025', 0.00, 'NV', 0),
(19, 2, '2024-2025', 0.00, 'NV', 0),
(22, 1, '2024-2025', 0.00, NULL, NULL),
(1015, 1, '2024-2025', 16.11, 'V', 0),
(1015, 2, '2024-2025', 12.00, 'VPC', 1),
(1015, 3, '2025-2026', 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professeurs`
--

CREATE TABLE `professeurs` (
  `user_id` int(11) NOT NULL,
  `cin` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `annee_id` varchar(50) DEFAULT NULL,
  `actuel` tinyint(4) DEFAULT 1,
  `rapport` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professeurs`
--

INSERT INTO `professeurs` (`user_id`, `cin`, `nom`, `prenom`, `telephone`, `department_id`, `annee_id`, `actuel`, `rapport`) VALUES
(2, 'P12345678', 'Smith', 'John', '0612345678', 1, '2024-2025', 0, NULL),
(3, 'P23456789', 'Johnson', 'Emily', '0623456789', 2, '2024-2025', 1, NULL),
(4, 'P34567890', 'Williams', 'Michael', '0634567890', 3, '2024-2025', 1, NULL),
(5, 'P45678901', 'Brownsdsd', 'Sarah', '0645678901', 4, '2024-2025', 1, NULL),
(6, 'P56789012', 'Jones', 'David', '0656789012', 5, '2024-2025', 1, NULL),
(7, 'P67890123', 'Garcia', 'Jessica', '0667890123', 6, '2024-2025', 1, NULL),
(8, 'P78901234', 'Miller', 'Robert', '0678901234', 7, '2024-2025', 1, NULL),
(9, 'P89012345', 'Davis', 'Jennifer', '0689012345', 8, '2024-2025', 1, NULL),
(10, 'P90123456', 'Rodriguez', 'Thomas', '0690123456', 9, '2024-2025', 1, NULL),
(11, 'P01234567', 'Martinez', 'Lisa', '0601234567', 10, '2024-2025', 1, NULL),
(23, 'yassine123', 'yassine', 'yassine', '0823223948', 5, '2024-2025', 1, NULL),
(24, 'DA55555', 'Berrahmo', 'Douaa', '0645393944', 10, '2024-2025', 0, NULL),
(23658, 'CIN23658', 'El Najjar', 'Ahmed', '0660123456', 1, '2024-2025', 1, NULL),
(23659, 'CIN23659', 'Al Maghri', 'Fatima', '0660123457', 1, '2024-2025', 1, NULL),
(23660, 'CIN23660', 'Ben Ali', 'Youssef', '0660123458', 9, '2024-2025', 1, NULL),
(23661, 'CIN23661', 'Errachidi', 'Leila', '0660123459', 3, '2024-2025', 1, NULL),
(23662, 'CIN23662', 'Zahrani', 'Said', '0660123460', 1, '2024-2025', 1, NULL),
(23663, 'CIN23663', 'Cherif', 'Hind', '0660123461', 1, '2024-2025', 1, NULL),
(23664, 'CIN23664', 'Dekkali', 'Omar', '0660123462', 1, '2024-2025', 1, NULL),
(23665, 'CIN23665', 'Fassi', 'Soumaya', '0660123463', 4, '2024-2025', 1, NULL),
(23666, 'CIN23666', 'Alami', 'Khalid', '0660123464', 1, '2024-2025', 1, NULL),
(23667, 'CIN23667', 'Boukali', 'Meryem', '0660123465', 8, '2024-2025', 1, NULL),
(23668, 'aaaaaaaaaaaa', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaa', '0623456789', 1006, '2024-2025', 1, NULL),
(23669, 'P23456789aaa', 'bbbbbbbbbbb', 'bbbbbbbbbbbbbb', '0654265210', 1006, '2024-2025', 1, NULL),
(23670, 'dzdsdza', 'ccccccccccc', 'cccccccccccc', '0623456789', 1006, '2024-2025', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professor_element`
--

CREATE TABLE `professor_element` (
  `user_id` int(11) NOT NULL,
  `element_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professor_element`
--

INSERT INTO `professor_element` (`user_id`, `element_id`) VALUES
(2, 1),
(2, 3),
(2, 4),
(3, 5),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(6, 2),
(7, 6);

-- --------------------------------------------------------

--
-- Table structure for table `professor_module`
--

CREATE TABLE `professor_module` (
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professor_module`
--

INSERT INTO `professor_module` (`user_id`, `module_id`) VALUES
(2, 1),
(2, 2),
(2, 3),
(3, 5),
(3, 6),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(7, 4);

-- --------------------------------------------------------

--
-- Table structure for table `professor_roles`
--

CREATE TABLE `professor_roles` (
  `user_id` int(11) NOT NULL,
  `role` enum('Regular','Chef_de_Departement','Chef_de_Filiere','Chef_de_Module','Chef_de_Element') NOT NULL,
  `date_role` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_debut_affectation` date DEFAULT NULL,
  `date_fin_affectation` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professor_roles`
--

INSERT INTO `professor_roles` (`user_id`, `role`, `date_role`, `date_debut_affectation`, `date_fin_affectation`) VALUES
(3, 'Chef_de_Departement', '2025-09-16 17:44:02', '2021-09-01', '2024-07-01'),
(4, 'Chef_de_Departement', '2025-09-16 17:44:06', '2019-09-01', '2022-07-01'),
(5, 'Chef_de_Departement', '2025-09-16 17:43:27', '2020-09-01', '2022-07-01'),
(6, 'Chef_de_Departement', '2025-09-16 17:43:24', '2021-09-01', '2023-07-01'),
(7, 'Chef_de_Departement', '2025-09-16 17:43:40', '2020-09-01', '2024-07-01'),
(8, 'Chef_de_Departement', '2025-09-16 17:43:53', '2022-09-01', '2025-07-01'),
(9, 'Chef_de_Departement', '2025-09-16 17:43:44', '2023-09-01', '2025-10-05'),
(10, 'Chef_de_Departement', '2025-09-16 17:43:48', '2020-09-01', '2023-07-01'),
(11, 'Chef_de_Departement', '2025-09-16 17:43:32', '2021-09-01', '2024-07-01'),
(23, 'Chef_de_Filiere', '2025-09-16 17:42:54', '2025-09-01', '2025-09-26'),
(23658, 'Chef_de_Filiere', '2025-09-16 17:42:39', '2025-09-10', '2025-10-05'),
(23659, 'Chef_de_Filiere', '2025-09-16 18:32:33', '2025-09-03', '2025-10-10'),
(23660, 'Chef_de_Filiere', '2025-09-16 17:43:07', '2025-09-03', '2025-11-09'),
(23661, 'Chef_de_Filiere', '2025-09-16 17:42:48', '2025-09-03', '2025-10-04'),
(23662, 'Chef_de_Filiere', '2025-09-16 17:42:56', '2025-09-02', '2025-09-25'),
(23663, 'Chef_de_Departement', '2025-09-16 17:43:58', '2020-09-01', '2023-07-01'),
(23664, 'Chef_de_Filiere', '2025-09-16 17:43:00', '2025-09-02', '2025-10-05'),
(23665, 'Chef_de_Filiere', '2025-09-16 17:42:51', '2025-09-08', '2025-10-03'),
(23666, 'Chef_de_Filiere', '2025-09-16 17:42:46', '2025-09-17', '2025-10-04'),
(23667, 'Chef_de_Filiere', '2025-09-16 17:43:04', '2025-09-02', '2025-10-03'),
(23668, 'Chef_de_Departement', '2025-09-16 17:44:10', '2025-09-02', '2025-10-10'),
(23669, 'Chef_de_Filiere', '2025-09-16 17:43:11', '2025-09-02', '2025-10-08'),
(23670, 'Chef_de_Filiere', '2025-09-16 17:45:30', '2025-09-04', '2025-09-27');

-- --------------------------------------------------------

--
-- Table structure for table `professor_tp_session`
--

CREATE TABLE `professor_tp_session` (
  `user_id` int(11) NOT NULL,
  `tp_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `professor_tp_session`
--

INSERT INTO `professor_tp_session` (`user_id`, `tp_id`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(3, 9),
(3, 10);

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `field_id` int(11) DEFAULT NULL,
  `etape` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `nom`, `field_id`, `etape`) VALUES
(1, 'Section A', 1, 1),
(2, 'Section B', 1, 1),
(3, 'Section A', 2, 1),
(5, 'Section A', 3, 1),
(6, 'Section B', 3, 1),
(7, 'Section A', 4, 1),
(8, 'Section B', 4, 1),
(9, 'Section A', 5, 1),
(10, 'Section B', 5, 1),
(11, 'Section A', NULL, 1),
(12, 'Section B', NULL, 1),
(13, 'Section A', NULL, 1),
(14, 'Section B', NULL, 1),
(15, 'Section C', NULL, 1),
(16, 'Section A', NULL, 1),
(17, 'Section B', NULL, 2),
(18, 'Section C', NULL, 2),
(19, 'Section D', NULL, 2),
(20, 'Section A', NULL, 2),
(21, 'Section B', NULL, 2),
(22, 'Section C', NULL, 2),
(23, 'Section D', NULL, 2),
(24, 'Section A', NULL, 1),
(25, 'Section B', NULL, 1),
(26, 'Section A', NULL, 1),
(27, 'Section B', NULL, 1),
(28, 'Section A', NULL, 2),
(29, 'Section B', NULL, 2),
(30, 'Section A', NULL, 2),
(31, 'Section B', NULL, 2),
(32, 'Section A', NULL, 1),
(33, 'Section A', NULL, 1),
(34, 'Section A', NULL, 2),
(35, 'Section A', NULL, 2),
(36, 'Section A', NULL, 1),
(37, 'Section A', NULL, 1),
(38, 'Section A', NULL, 2),
(39, 'Section A', NULL, 2),
(1000, 'Test Section A', NULL, 1000),
(1001, 'Test Section B', NULL, 1001),
(1002, 'testsect1', NULL, 1),
(1003, 'testsect2', NULL, 2),
(1004, 'test3', NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `semestres`
--

CREATE TABLE `semestres` (
  `semestre_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `etape_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semestres`
--

INSERT INTO `semestres` (`semestre_id`, `nom`, `cycle_id`, `field_id`, `etape_id`) VALUES
(1, 'Semestre 1', 2, 2, 1),
(2, 'Semestre 2', 2, 2, 1),
(3, 'Semestre 3', 2, 2, 2),
(4, 'Semestre 4', 2, 2, 2),
(5, 'Semestre 5', 2, 2, 2),
(6, 'Semestre 6', 2, 2, 1),
(7, 'Semestre 1', 2, 4, 1),
(8, 'Semestre 2', 2, 4, 1),
(9, 'Semestre 1', 2, 5, 1),
(10, 'Semestre 2', 2, 5, 1),
(1010, 'Semestre 3', 2, 4, 2),
(1011, 'Semestre 4', 2, 4, 2),
(1012, 'Semestre 5', 2, 4, 3),
(1013, 'Semestre 6', 2, 4, 3),
(1014, 'Semestre 3', 2, 5, 2),
(1015, 'Semestre 4', 2, 5, 2),
(1016, 'Semestre 5', 2, 5, 3),
(1017, 'Semestre 6', 2, 5, 3),
(1018, 'Semestre 1', 2, 1, 1),
(1019, 'Semestre 2', 2, 1, 1),
(1020, 'Semestre 3', 2, 1, 2),
(1021, 'Semestre 4', 2, 1, 2),
(1022, 'Semestre 5', 2, 1, 3),
(1023, 'Semestre 6', 2, 1, 3),
(1024, 'Semestre 1', 2, 3, 1),
(1025, 'Semestre 2', 2, 3, 1),
(1026, 'Semestre 3', 2, 3, 2),
(1027, 'Semestre 4', 2, 3, 2),
(1028, 'Semestre 5', 2, 3, 3),
(1029, 'Semestre 6', 2, 3, 3),
(1048, 'Semestre 1', 2, 6, 1),
(1049, 'Semestre 2', 2, 6, 1),
(1050, 'Semestre 3', 2, 6, 2),
(1051, 'Semestre 4', 2, 6, 2),
(1052, 'Semestre 5', 2, 6, 3),
(1053, 'Semestre 6', 2, 6, 3),
(1054, 'Semestre 1', 2, 7, 1),
(1055, 'Semestre 2', 2, 7, 1),
(1056, 'Semestre 3', 2, 7, 2),
(1057, 'Semestre 4', 2, 7, 2),
(1058, 'Semestre 5', 2, 7, 3),
(1059, 'Semestre 6', 2, 7, 3),
(1064, 'Semestre 1', 2, 8, 1),
(1065, 'Semestre 2', 2, 8, 1),
(1066, 'Semestre 3', 2, 8, 2),
(1067, 'Semestre 4', 2, 8, 2),
(1068, 'Semestre 5', 2, 8, 3),
(1069, 'Semestre 6', 2, 8, 3),
(1070, 'Semestre 1', 2, 9, 1),
(1071, 'Semestre 2', 2, 9, 1),
(1072, 'Semestre 3', 2, 9, 2),
(1073, 'Semestre 4', 2, 9, 2),
(1074, 'Semestre 5', 2, 9, 3),
(1075, 'Semestre 6', 2, 9, 3),
(1076, 'Semestre 1', 2, 10, 1),
(1077, 'Semestre 2', 2, 10, 1),
(1078, 'Semestre 3', 2, 10, 2),
(1079, 'Semestre 4', 2, 10, 2),
(1080, 'Semestre 5', 2, 10, 3),
(1081, 'Semestre 6', 2, 10, 3),
(1126, 'Semestre 1', 2, 1007, 1),
(1127, 'Semestre 2', 2, 1007, 1),
(1128, 'Semestre 3', 2, 1007, 2),
(1129, 'Semestre 4', 2, 1007, 2),
(1130, 'Semestre 5', 2, 1007, 3),
(1131, 'Semestre 6', 2, 1007, 3),
(1132, 'Semestre 1', 1, 1008, 1),
(1133, 'Semestre 2', 1, 1008, 1),
(1134, 'Semestre 3', 1, 1008, 2),
(1135, 'Semestre 4', 1, 1008, 2);

-- --------------------------------------------------------

--
-- Table structure for table `student_diplomas`
--

CREATE TABLE `student_diplomas` (
  `student_id` int(11) NOT NULL,
  `diplome_id` int(11) NOT NULL,
  `note` decimal(5,2) NOT NULL,
  `decision` enum('V','R','F') NOT NULL,
  `mention` enum('bien','tres bien') DEFAULT NULL,
  `date_awarded` date NOT NULL,
  `annee_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_diplomas`
--

INSERT INTO `student_diplomas` (`student_id`, `diplome_id`, `note`, `decision`, `mention`, `date_awarded`, `annee_id`) VALUES
(12, 1, 15.50, 'V', 'tres bien', '2025-06-30', NULL),
(13, 1, 14.00, 'V', 'bien', '2025-06-30', NULL),
(14, 1, 12.50, 'V', NULL, '2025-06-30', NULL),
(15, 1, 11.00, 'V', NULL, '2025-06-30', NULL),
(16, 2, 15.00, 'V', 'tres bien', '2025-06-30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `annee_id` varchar(50) NOT NULL,
  `semestre_id` int(11) DEFAULT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `etape_id` int(11) DEFAULT 1,
  `group_id` int(11) DEFAULT 1,
  `section_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`enrollment_id`, `student_id`, `annee_id`, `semestre_id`, `cycle_id`, `field_id`, `etape_id`, `group_id`, `section_id`, `status`) VALUES
(1, 12, '2023-2024', 1, 1, NULL, 1, 5, 5, 'active'),
(2, 13, '2024-2025', 1, 2, NULL, 1, 1, 1, 'active'),
(3, 14, '2024-2025', 1, 2, NULL, NULL, 2, 1, 'active'),
(4, 15, '2024-2025', 1, 2, NULL, 5, 2, 1, 'active'),
(5, 16, '2024-2025', 2, 2, NULL, NULL, 3, 3, 'active'),
(6, 17, '2024-2025', 2, 2, NULL, NULL, 3, 3, 'active'),
(7, 18, '2024-2025', 2, 2, NULL, NULL, 4, 3, 'active'),
(8, 19, '2024-2025', 2, 2, NULL, NULL, 4, 3, 'active'),
(9, 20, '2024-2025', 5, 2, NULL, NULL, 5, 5, 'active'),
(11, 12, '2023-2024', 7, 1, NULL, 1, 5, 5, 'active'),
(13, 1000, '2024-2025', NULL, 1000, NULL, 1000, 1000, 1000, 'active'),
(14, 1000, '2023-2024', NULL, 1000, NULL, 1000, 1000, 1000, 'active'),
(15, 1000, '2023-2024', NULL, 1000, NULL, 1000, 43, 1000, 'active'),
(16, 1000, '2022-2023', NULL, 1000, NULL, 1000, 1000, 1000, 'active'),
(18, 1004, '2024-2025', 1, 1, NULL, NULL, NULL, 30, 'active'),
(19, 1005, '2021-2022', NULL, 2, NULL, NULL, NULL, 37, 'active'),
(20, 1006, '2023-2024', NULL, 1, 4, 3, 8, 7, 'active'),
(21, 1007, '2022-2023', 1, 2, 1, 3, NULL, 1, 'active'),
(22, 16, '2025-2026', 2, 2, 2, 1, NULL, NULL, 'inscrit'),
(23, 13, '2025-2026', 1, 2, 1, 1, NULL, NULL, 'inscrit'),
(24, 17, '2025-2026', 2, 2, 2, 1, NULL, NULL, 'inscrit'),
(25, 18, '2025-2026', 2, 2, 2, 1, NULL, NULL, 'inscrit'),
(26, 19, '2025-2026', 2, 2, 2, 1, NULL, NULL, 'inscrit'),
(34, 1015, '2024-2025', 1, 3, NULL, 1, NULL, 1002, 'active'),
(45, 1026, '2024-2025', 1, 3, NULL, 1, NULL, 1002, 'active'),
(49, 1028, '2020-2021', 7, 3, NULL, 1, 0, 0, 'active'),
(50, 13, '2024-2025', 2, 2, NULL, 1, 1, 1, 'active'),
(51, 14, '2024-2025', 2, 2, NULL, 1, 2, 1, 'active'),
(52, 15, '2024-2025', 2, 2, NULL, 5, 2, 1, 'active'),
(53, 1004, '2024-2025', 2, 1, NULL, 1, 1, 30, 'active'),
(54, 1015, '2024-2025', 2, 3, NULL, 1, 1, 1002, 'active'),
(55, 1026, '2024-2025', 2, 3, NULL, 1, 1, 1002, 'active'),
(56, 13, '2025-2026', 2, 2, 1, 1, 1, NULL, 'inscrit'),
(57, 1015, '2025-2026', 3, 3, NULL, 2, 1, NULL, 'active'),
(58, 1029, '2023-2024', 9, 1, NULL, 1, 0, 1002, 'active'),
(60, 1032, '2023-2024', 10, 2, NULL, 1, 0, 0, 'active'),
(61, 15, '2025-2026', 1, 3, NULL, 1, 1, NULL, 'inscrit'),
(62, 1000, '2025-2026', NULL, 1000, NULL, 1000, 1, NULL, 'inscrit');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `name` varchar(100) NOT NULL,
  `value` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`name`, `value`) VALUES
('affiche_note', 1),
('grade_period_active', 1),
('resit_grade_period_active', 0);

--
-- Triggers `system_settings`
--
DELIMITER $$
CREATE TRIGGER `enforce_single_active_period` BEFORE UPDATE ON `system_settings` FOR EACH ROW BEGIN
    IF NEW.name = 'grade_period_active' AND NEW.value = '1' THEN
        IF (SELECT value FROM system_settings WHERE name = 'resit_grade_period_active') = '1' THEN
            UPDATE system_settings 
            SET value = '0' 
            WHERE name = 'resit_grade_period_active';
        END IF;
    ELSEIF NEW.name = 'resit_grade_period_active' AND NEW.value = '1' THEN
        IF (SELECT value FROM system_settings WHERE name = 'grade_period_active') = '1' THEN
            UPDATE system_settings 
            SET value = '0' 
            WHERE name = 'grade_period_active';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tp_sessions`
--

CREATE TABLE `tp_sessions` (
  `tp_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `coeff` decimal(3,0) NOT NULL,
  `element_id` int(11) NOT NULL,
  `date_tp` datetime NOT NULL,
  `duree` int(11) NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `prof_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tp_sessions`
--

INSERT INTO `tp_sessions` (`tp_id`, `nom`, `coeff`, `element_id`, `date_tp`, `duree`, `lieu`, `prof_id`) VALUES
(1, 'TP Algorithmique 1', 40, 1, '2025-01-15 08:00:00', 120, 'Salle TP1', 2),
(2, 'TP Algorithmique 2', 40, 1, '2025-01-22 08:00:00', 120, 'Salle TP1', 2),
(3, 'TP Structures 1', 30, 2, '2025-01-16 08:00:00', 120, 'Salle TP2', 2),
(4, 'TP Structures 2', 30, 2, '2025-01-23 08:00:00', 120, 'Salle TP2', 2),
(5, 'TP Bases 1', 40, 3, '2025-03-15 08:00:00', 120, 'Salle TP3', 2),
(6, 'TP Bases 2', 40, 3, '2025-03-22 08:00:00', 120, 'Salle TP3', 2),
(7, 'TP Réseaux 1', 30, 4, '2025-03-16 08:00:00', 120, 'Salle TP4', 2),
(8, 'TP Réseaux 2', 30, 4, '2025-03-23 08:00:00', 120, 'Salle TP4', 2),
(9, 'TP Stats 1', 40, 5, '2025-01-17 08:00:00', 120, 'Salle TP5', 3),
(10, 'TP Stats 2', 40, 5, '2025-01-24 08:00:00', 120, 'Salle TP5', 3);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `role` enum('student','admin','prof','chef_dep','chef_fill','moderator','superadmin') NOT NULL DEFAULT 'student',
  `actuel` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`user_id`, `username`, `password_hash`, `email`, `created_at`, `last_login`, `role`, `actuel`) VALUES
(1, 'admin', 'admin123', 'admin@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'admin', 1),
(2, 'prof1', 'prof123', 'prof1@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'prof', 0),
(3, 'prof2', 'prof123', 'prof2@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(4, 'prof3', 'prof123', 'prof3@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(5, 'prof4', 'prof123', 'prof4@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(6, 'prof5', 'prof123', 'prof5@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(7, 'prof6', 'prof123', 'prof6@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(8, 'prof7', 'prof123', 'prof7@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(9, 'prof8', 'prof123', 'prof8@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(10, 'prof9', 'prof123', 'prof9@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(11, 'prof10', 'prof123', 'prof10@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'chef_dep', 1),
(12, 'jean.dupont', '$2y$10$qNHif3h8Lj2VHU2w9omWeOStREnVRJ22yw5hKmZtRFCwmUtIpCAXq', 'student1@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(13, 'student2', 'student123', 'student2@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(14, 'student3', 'student123', 'student3@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 0),
(15, 'student4', 'student123', 'student4@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(16, 'student5', 'student123', 'student5@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(17, 'student6', 'student123', 'student6@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(18, 'student7', 'student123', 'student7@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(19, 'student8', 'student123', 'student8@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(20, 'paul.laurent', 'student123', 'student9@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(21, 'student10', 'student123', 'student10@ens.edu', '2024-01-01 00:00:00', '2025-07-18 09:00:00', 'student', 1),
(22, 'yassine', '$2y$10$yMwbabnkecOcQ7..g4XYaOWw.eRUlu3rjKY1mX4AzuOTGp3i3dPIK', 'yassine.ouali@usmba.ac.ma', '2025-07-18 11:39:09', NULL, 'student', 1),
(23, 'yassine', '$2y$10$h2NWpCrf7.2jPEvF2MYndOPvvCwY23/EaPwiSfiE0n34Exo7oN0ue', 'yassine@sds.com', '2025-07-21 11:05:53', NULL, 'prof', 1),
(24, 'Douaa', '$2y$10$qAGoe/tJOIeu5k6Jsooa1ecPexdB3vg1ygLJeHnnbCFzy/lvi87fa', 'douaaberrahmo@gmail.com', '2025-07-24 10:40:28', NULL, 'chef_dep', 0),
(1000, 'Test_Pass', '', '', '2025-08-21 15:24:10', NULL, 'student', 1),
(1004, '23234234234wrwerw', '$2y$10$IyTIgnzPpn.QEpZUbKgvj.wYFFMqAwzAQsBzkrsqFO3TbHROPiuUW', 'yassine.eretertdfdgouali@usmba.ac.ma', '2025-08-27 01:14:21', NULL, 'student', 1),
(1005, 'sdfsfsfwefwe', '$2y$10$DJGwSc4yy.hqQb4zUtSKrerjzGQALucJImHhrmYLoDw359ElsVxje', 'TV34T3TV4VQ3T@DGDGD.COM', '2025-08-28 21:33:48', NULL, 'student', 1),
(1006, 'sdgw434523.dhdzfhzde4g43g', '$2y$10$0NRW8HqFXp546tfzK4dgROmGyEJL1LhGHP6VgviOIX0mtXyMTcqf2', 'zrg345235e@gm.vc', '2025-08-29 01:21:01', NULL, 'student', 1),
(1007, 'sdsdf', '$2y$10$J4KYp1dhErWfPJvGAXfeiOqhf8GRNzzPqtiYVOR3fR/QUnZzTulou', 'dfwfef@dfgder.bdf', '2025-08-30 17:49:49', NULL, 'student', 1),
(1015, 'yassine.ouali', '$2y$10$HVWTXO8N5PdMPB5rsYvfAesg/zsBPkiXMCIzSJJ2o0g9nVai092h6', 'yassine@gmail.com', '2025-09-02 15:42:44', NULL, 'student', 1),
(1026, 'med.moha', '$2y$10$GEOcZ4tNiDoD4F1Bgd2X/.Qpwba.3mPHcFM9WMwiK0OfJx4WDiZNy', 'moha.ouali@usmba.ac.ma', '2025-09-02 16:01:49', NULL, 'student', 1),
(1028, 'yassine asasas', '$2y$10$v1hHgN9QC66dvEZilRjIu.5yq6mvGrTJP.QGY0vTzlfBHjOEY/wWq', 'wgwsgwrgrggs@fdgdfg.na', '2025-09-02 18:04:55', NULL, 'student', 0),
(1029, 'sffweaewe32f', '$2y$10$SbtZ6X4Sd3Iya1iwcAHgk.4JUm.yNYPuydw8lAG0/g08wpDw.FryK', 'fSEFESFsef@gs.sf', '2025-09-03 00:30:41', NULL, 'student', 0),
(1032, 'weriwjwrejwpor', '$2y$10$Kxznp/TvCcp01tq2TtAhdu6WoshULlHt9BImZ8fymlPQF8ANSSi.m', 'woprworjpo2@sijfpsf.as', '2025-09-03 00:48:55', NULL, 'student', 1),
(23658, 'prof1', 'prof1pass', 'prof1@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23659, 'prof2', 'prof2pass', 'prof2@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23660, 'prof3', 'prof3pass', 'prof3@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23661, 'prof4', 'prof4pass', 'prof4@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23662, 'prof5', 'prof5pass', 'prof5@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23663, 'prof6', 'prof6pass', 'prof6@example.com', '2025-09-04 17:39:38', NULL, 'chef_dep', 1),
(23664, 'prof7', 'prof7pass', 'prof7@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23665, 'prof8', 'prof8pass', 'prof8@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23666, 'prof9', 'prof9pass', 'prof9@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23667, 'prof10', 'prof10pass', 'prof10@example.com', '2025-09-04 17:39:38', NULL, 'prof', 1),
(23668, 'NewProf', '$2y$10$aZhv0mbxrATB5.ys8iJfke6HdX9KEi8AHo7hA01b4jGOLvQzIURhO', 'aaaaaaaaaaaa@gmail.com', '2025-09-16 18:33:17', NULL, 'prof', 1),
(23669, 'aqzsdef', '$2y$10$RXuLqfCR9/D6OyuxwQb.XOJy6ANsg7XSNHQKZ9ubbQc0wq78PVvcC', 'bbbb@gmail.com', '2025-09-16 18:35:13', NULL, 'prof', 1),
(23670, 'aqs', '$2y$10$pZTzUm85bCNGTsDX/270pObQrMqq7R4h/iABwXEsp2P9LnK4Hn2yO', 'c@gmail.com', '2025-09-16 18:45:06', NULL, 'prof', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `CIN` (`CIN`);

--
-- Indexes for table `annees_academiques`
--
ALTER TABLE `annees_academiques`
  ADD PRIMARY KEY (`annee_id`);

--
-- Indexes for table `cycles`
--
ALTER TABLE `cycles`
  ADD PRIMARY KEY (`cycle_id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD KEY `fk_head_prof` (`head_professor_id`),
  ADD KEY `fk_annee_accreditation` (`annee_accreditation`);

--
-- Indexes for table `diplomes`
--
ALTER TABLE `diplomes`
  ADD PRIMARY KEY (`diplome_id`),
  ADD KEY `fk_diplomes_cycle` (`cycle_id`),
  ADD KEY `fk_diplomes_field` (`field_id`),
  ADD KEY `fk_diplomes_department` (`department_id`);

--
-- Indexes for table `elements`
--
ALTER TABLE `elements`
  ADD PRIMARY KEY (`element_id`),
  ADD KEY `fk_elements_module` (`module_id`),
  ADD KEY `fk_elements_professor` (`Ref_prof_element`),
  ADD KEY `Ref_prof_tp` (`Ref_prof_tp`),
  ADD KEY `Ref_prof_cours` (`Ref_prof_cours`),
  ADD KEY `Ref_prof_td` (`Ref_prof_td`);

--
-- Indexes for table `etapes`
--
ALTER TABLE `etapes`
  ADD PRIMARY KEY (`etape_id`);

--
-- Indexes for table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `cin` (`cin`),
  ADD UNIQUE KEY `cne` (`cne`),
  ADD KEY `fk_etudiants_department` (`department_id`),
  ADD KEY `fk_etudiants_field` (`field_id`),
  ADD KEY `fk_etudiants_cycle` (`cycle_id`),
  ADD KEY `fk_etudiants_group` (`group_id`);

--
-- Indexes for table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `fk_filieres_department` (`department_id`),
  ADD KEY `fk_filieres_head_prof` (`head_professor_id`),
  ADD KEY `fk_filieres_annee` (`annee_accreditation`),
  ADD KEY `fk_filieres_cycle` (`cycle_id`);

--
-- Indexes for table `grading_rules`
--
ALTER TABLE `grading_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `idx_scope` (`scope`);

--
-- Indexes for table `groupes`
--
ALTER TABLE `groupes`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `fk_groupes_field` (`field_id`),
  ADD KEY `fk_groupes_section` (`section_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_modules_semestre` (`semestre_id`),
  ADD KEY `fk_modules_field` (`field_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`note_id`),
  ADD UNIQUE KEY `uk_notes` (`student_id`,`element_id`,`semestre_id`,`annee_id`),
  ADD KEY `fk_notes_element` (`element_id`),
  ADD KEY `fk_notes_semestre` (`semestre_id`),
  ADD KEY `fk_notes_annee` (`annee_id`);

--
-- Indexes for table `note_annees`
--
ALTER TABLE `note_annees`
  ADD PRIMARY KEY (`student_id`,`annee_id`),
  ADD KEY `fk_note_annees_annee` (`annee_id`);

--
-- Indexes for table `note_modules`
--
ALTER TABLE `note_modules`
  ADD PRIMARY KEY (`student_id`,`module_id`,`semestre_id`,`annee_id`),
  ADD KEY `fk_note_modules_module` (`module_id`),
  ADD KEY `fk_note_modules_semestre` (`semestre_id`),
  ADD KEY `fk_note_modules_annee` (`annee_id`);

--
-- Indexes for table `note_semestres`
--
ALTER TABLE `note_semestres`
  ADD PRIMARY KEY (`student_id`,`semestre_id`,`annee_id`),
  ADD KEY `fk_note_semestres_semestre` (`semestre_id`),
  ADD KEY `fk_note_semestres_annee` (`annee_id`);

--
-- Indexes for table `professeurs`
--
ALTER TABLE `professeurs`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `cin` (`cin`),
  ADD KEY `fk_professeurs_annee` (`annee_id`);

--
-- Indexes for table `professor_element`
--
ALTER TABLE `professor_element`
  ADD PRIMARY KEY (`user_id`,`element_id`),
  ADD KEY `fk_professor_element_element` (`element_id`);

--
-- Indexes for table `professor_module`
--
ALTER TABLE `professor_module`
  ADD PRIMARY KEY (`user_id`,`module_id`),
  ADD KEY `fk_professor_module_module` (`module_id`);

--
-- Indexes for table `professor_roles`
--
ALTER TABLE `professor_roles`
  ADD PRIMARY KEY (`user_id`,`role`) USING BTREE,
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `professor_tp_session`
--
ALTER TABLE `professor_tp_session`
  ADD PRIMARY KEY (`user_id`,`tp_id`),
  ADD KEY `fk_professor_tp_session_tp` (`tp_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `fk_sections_field` (`field_id`),
  ADD KEY `fk_sections_etape` (`etape`);

--
-- Indexes for table `semestres`
--
ALTER TABLE `semestres`
  ADD PRIMARY KEY (`semestre_id`),
  ADD KEY `fk_semestres_cycle` (`cycle_id`),
  ADD KEY `fk_semestres_field` (`field_id`),
  ADD KEY `fk_semestres_etape` (`etape_id`);

--
-- Indexes for table `student_diplomas`
--
ALTER TABLE `student_diplomas`
  ADD PRIMARY KEY (`student_id`,`diplome_id`),
  ADD KEY `fk_student_diplomas_diplome` (`diplome_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`annee_id`,`semestre_id`),
  ADD KEY `fk_enrollments_student` (`student_id`),
  ADD KEY `fk_enrollments_annee` (`annee_id`),
  ADD KEY `fk_enrollments_semestre` (`semestre_id`),
  ADD KEY `fk_enrollments_cycle` (`cycle_id`),
  ADD KEY `fk_enrollments_group` (`group_id`),
  ADD KEY `fk_enrollments_section` (`section_id`),
  ADD KEY `fk_enrollements_field` (`field_id`),
  ADD KEY `fk_enrollements_etapes` (`etape_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `tp_sessions`
--
ALTER TABLE `tp_sessions`
  ADD PRIMARY KEY (`tp_id`),
  ADD KEY `fk_tp_sessions_element` (`element_id`),
  ADD KEY `fk_tp_sessions_prof` (`prof_id`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cycles`
--
ALTER TABLE `cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;

--
-- AUTO_INCREMENT for table `departements`
--
ALTER TABLE `departements`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1007;

--
-- AUTO_INCREMENT for table `diplomes`
--
ALTER TABLE `diplomes`
  MODIFY `diplome_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1011;

--
-- AUTO_INCREMENT for table `elements`
--
ALTER TABLE `elements`
  MODIFY `element_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100045;

--
-- AUTO_INCREMENT for table `etapes`
--
ALTER TABLE `etapes`
  MODIFY `etape_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;

--
-- AUTO_INCREMENT for table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1009;

--
-- AUTO_INCREMENT for table `grading_rules`
--
ALTER TABLE `grading_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `groupes`
--
ALTER TABLE `groupes`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1031;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10121;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;

--
-- AUTO_INCREMENT for table `semestres`
--
ALTER TABLE `semestres`
  MODIFY `semestre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1136;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tp_sessions`
--
ALTER TABLE `tp_sessions`
  MODIFY `tp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23671;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `departements`
--
ALTER TABLE `departements`
  ADD CONSTRAINT `fk_annee_accreditation` FOREIGN KEY (`annee_accreditation`) REFERENCES `annees_academiques` (`annee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_head_prof` FOREIGN KEY (`head_professor_id`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `diplomes`
--
ALTER TABLE `diplomes`
  ADD CONSTRAINT `fk_diplomes_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diplomes_department` FOREIGN KEY (`department_id`) REFERENCES `departements` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diplomes_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `elements`
--
ALTER TABLE `elements`
  ADD CONSTRAINT `elements_ibfk_1` FOREIGN KEY (`Ref_prof_tp`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `elements_ibfk_2` FOREIGN KEY (`Ref_prof_cours`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `elements_ibfk_3` FOREIGN KEY (`Ref_prof_td`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elements_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elements_professor` FOREIGN KEY (`Ref_prof_element`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `etudiants`
--
ALTER TABLE `etudiants`
  ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etudiants_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etudiants_department` FOREIGN KEY (`department_id`) REFERENCES `departements` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etudiants_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `filieres`
--
ALTER TABLE `filieres`
  ADD CONSTRAINT `fk_filieres_annee` FOREIGN KEY (`annee_accreditation`) REFERENCES `annees_academiques` (`annee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_filieres_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_filieres_department` FOREIGN KEY (`department_id`) REFERENCES `departements` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_filieres_head_prof` FOREIGN KEY (`head_professor_id`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `groupes`
--
ALTER TABLE `groupes`
  ADD CONSTRAINT `fk_groupes_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_groupes_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `fk_modules_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_modules_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `fk_notes_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notes_element` FOREIGN KEY (`element_id`) REFERENCES `elements` (`element_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notes_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notes_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `note_annees`
--
ALTER TABLE `note_annees`
  ADD CONSTRAINT `fk_note_annees_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_annees_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `note_modules`
--
ALTER TABLE `note_modules`
  ADD CONSTRAINT `fk_note_modules_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `note_semestres`
--
ALTER TABLE `note_semestres`
  ADD CONSTRAINT `fk_note_semestres_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_semestres_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_semestres_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `professeurs`
--
ALTER TABLE `professeurs`
  ADD CONSTRAINT `fk_professeurs_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_professeurs_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `professor_element`
--
ALTER TABLE `professor_element`
  ADD CONSTRAINT `fk_professor_element_element` FOREIGN KEY (`element_id`) REFERENCES `elements` (`element_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_professor_element_user` FOREIGN KEY (`user_id`) REFERENCES `professeurs` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `professor_module`
--
ALTER TABLE `professor_module`
  ADD CONSTRAINT `fk_professor_module_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_professor_module_user` FOREIGN KEY (`user_id`) REFERENCES `professeurs` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `professor_roles`
--
ALTER TABLE `professor_roles`
  ADD CONSTRAINT `professor_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `professeurs` (`user_id`);

--
-- Constraints for table `professor_tp_session`
--
ALTER TABLE `professor_tp_session`
  ADD CONSTRAINT `fk_professor_tp_session_tp` FOREIGN KEY (`tp_id`) REFERENCES `tp_sessions` (`tp_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_professor_tp_session_user` FOREIGN KEY (`user_id`) REFERENCES `professeurs` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_etape` FOREIGN KEY (`etape`) REFERENCES `etapes` (`etape_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sections_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `semestres`
--
ALTER TABLE `semestres`
  ADD CONSTRAINT `fk_semestres_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_semestres_etape` FOREIGN KEY (`etape_id`) REFERENCES `etapes` (`etape_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_semestres_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_diplomas`
--
ALTER TABLE `student_diplomas`
  ADD CONSTRAINT `fk_student_diplomas_diplome` FOREIGN KEY (`diplome_id`) REFERENCES `diplomes` (`diplome_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_diplomas_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `fk_enrollements_field` FOREIGN KEY (`field_id`) REFERENCES `filieres` (`field_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollments_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollments_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollments_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `tp_sessions`
--
ALTER TABLE `tp_sessions`
  ADD CONSTRAINT `fk_tp_sessions_element` FOREIGN KEY (`element_id`) REFERENCES `elements` (`element_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_sessions_prof` FOREIGN KEY (`prof_id`) REFERENCES `professeurs` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
