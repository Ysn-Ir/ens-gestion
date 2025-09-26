-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2025 at 06:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `new_ens_2`
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
(1, 'AdminNom', 'AdminPrenom', '021548847', 'fes', '2000-11-05', 'marocain', 'AD1', 'Administrateur', '2025-07-18 10:22:29', '2025-07-18 10:33:25', NULL);

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
('2024-2025', 0),
('2025-2026', 0),
('2026-2027', 1),
('2027-2028', 0);

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
(4, 'Doctorat', 4);

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
(1, 'testing department', 2, '2024-2025', '2025-09-01', '2029-01-01', 1);

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
(3, 'NewDiploma', 1, 4, 1),
(4, 'Diplôme test', 2, 5, 1);

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
  `langue` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `elements`
--

INSERT INTO `elements` (`element_id`, `nom`, `module_id`, `Ref_prof_element`, `Ref_prof_tp`, `coeff_ecrit`, `coeff_tp`, `coeff_element`, `coeff_cc`, `coeff_td`, `presentiel`, `a_distance`, `en_alternance`, `horraire_tp`, `horraire_td`, `horraire_cours`, `horraire_evaluation`, `horraire_activite_pratique`, `Ref_prof_cours`, `Ref_prof_td`, `langue`) VALUES
(19, 'test', 15, NULL, 2, 0.200, 0.300, 1.000, 0.300, 0.200, 0, 0, 0, 0, 0, 0, 0, 0, 2, 2, 'anglais'),
(20, 'test2', 16, NULL, 2, 0.200, 0.300, 1.000, 0.300, 0.200, 0, 0, 0, 0, 0, 0, 0, 0, 2, 2, NULL),
(21, 'test3', 17, NULL, 2, 0.200, 0.300, 1.000, 0.300, 0.200, 0, 0, 0, 0, 0, 0, 0, 0, 2, 2, NULL),
(22, 'test4', 18, NULL, 2, 0.200, 0.300, 1.000, 0.300, 0.200, 0, 0, 0, 0, 0, 0, 0, 0, 2, 2, NULL);

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
(5, '5eme annee');

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
(10, 'S12345678', 'yassine1234', 'ouali', 'yassine', '2000-01-01', 'Marocaine', '0609408356', '28 lot jnan zhar fes', 1, 5, 2, 1, 1, NULL);

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
(4, 'NewField', 1, 4, '2020-2021', 1, '2025-09-03', '2025-10-05', '2027-2028', 'actif'),
(5, 'testing filliere', 1, 9, '2025-2026', 2, '2025-09-17', '2025-10-10', '2027-2028', 'actif');

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
(1, 'element', 'ratt_threshold', '7.00', '2025-08-20 14:23:51', '2025-09-07 17:36:07'),
(2, 'module', 'pass_threshold', '12.00', '2025-08-20 14:23:51', '2025-09-07 17:37:07'),
(3, 'module', 'post_ratt_cap', '11.00', '2025-08-20 14:23:51', '2025-09-07 17:37:14'),
(4, 'semester', 'max_nv_modules', '2', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(5, 'year', 'max_nv_modules_per_semester', '2', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(6, 'year', 'max_year_fails', '3', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(7, 'year', 'retake_year_in_cycle', '{\"deug\": 2, \"license\": 3, \"master\": 2, \"doctorat\": 3}', '2025-08-20 14:23:51', '2025-08-20 14:23:51'),
(8, 'element', 'pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(9, 'element', 'ratt_threshold', '7', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(10, 'element', 'rattrapage_pass_threshold', '10', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(11, 'element', 'single_element_ratt', 'true', '2025-08-20 16:50:45', '2025-08-20 16:50:45'),
(12, 'element', 'decision_v', '{\"decision\": \"V\"}', '2025-08-20 16:50:45', '2025-09-07 17:36:30'),
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
(1, 'grp1', 4, 1);

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
(15, 'test', 'test', 1, 58, 4, 'Module d\'éducation'),
(16, 'test2', 'test2', 1, 59, 4, 'Module d\'éducation'),
(17, 'test3', 'test3', 1, 60, 4, 'Module d\'éducation'),
(18, 'test4', 'test4', 1, 61, 4, 'Soft Skills');

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
  `decision_ratt` enum('VR','NV','VC') DEFAULT NULL,
  `etape_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`note_id`, `student_id`, `element_id`, `semestre_id`, `annee_id`, `note_tp`, `note_td`, `note_cc`, `note_exam`, `note_rattrapage`, `note_finale`, `decision`, `decision_ratt`, `etape_id`) VALUES
(13, 10, 19, 58, '2025-2026', 20.00, 20.00, 20.00, 18.00, NULL, 19.50, 'V', NULL, 1),
(17, 10, 20, 59, '2025-2026', 12.00, 12.00, 5.00, 7.00, 8.00, 8.38, NULL, 'NV', 1),
(105, 10, 21, 60, '2026-2027', 12.00, 12.00, 12.00, 12.00, NULL, 12.00, 'V', NULL, 2),
(106, 10, 22, 61, '2026-2027', 12.00, 12.00, 12.00, 12.00, NULL, 12.00, 'V', NULL, 2),
(107, 10, 19, 58, '2026-2027', 20.00, 20.00, 20.00, 18.00, NULL, 19.50, 'V', NULL, 1),
(108, 10, 20, 59, '2026-2027', 12.00, 12.00, 12.00, 12.00, NULL, 12.00, 'V', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `note_annees`
--

CREATE TABLE `note_annees` (
  `student_id` int(11) NOT NULL,
  `annee_id` varchar(50) NOT NULL,
  `etape_id` int(11) NOT NULL,
  `note_annee` decimal(5,2) NOT NULL,
  `decision_annee` enum('V','F','NV','VPC') DEFAULT NULL,
  `fail_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_annees`
--

INSERT INTO `note_annees` (`student_id`, `annee_id`, `etape_id`, `note_annee`, `decision_annee`, `fail_count`) VALUES
(10, '2025-2026', 1, 13.94, 'VPC', 1),
(10, '2026-2027', 1, 14.85, 'V', 0),
(10, '2026-2027', 2, 12.00, 'V', 0),
(10, '2027-2028', 1, 0.00, NULL, 0);

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
  `retake_status` enum('pending','scheduled','completed') DEFAULT NULL,
  `etape_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_modules`
--

INSERT INTO `note_modules` (`student_id`, `module_id`, `semestre_id`, `annee_id`, `note_module`, `decision`, `note_ratt`, `decision_ratt`, `retake_status`, `etape_id`) VALUES
(10, 15, 58, '2025-2026', 19.50, 'V', NULL, NULL, 'completed', 1),
(10, 15, 58, '2026-2027', 19.50, 'V', NULL, NULL, 'completed', 1),
(10, 16, 59, '2025-2026', 8.38, 'NV', NULL, 'NV', 'scheduled', 1),
(10, 16, 59, '2026-2027', 10.19, 'V', NULL, NULL, 'completed', 1),
(10, 17, 60, '2026-2027', 12.00, 'V', NULL, NULL, 'completed', 2),
(10, 18, 61, '2026-2027', 12.00, 'V', NULL, NULL, 'completed', 2);

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
  `nv_module_count` int(11) DEFAULT NULL,
  `etape_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `note_semestres`
--

INSERT INTO `note_semestres` (`student_id`, `semestre_id`, `annee_id`, `note_semestre`, `decision`, `nv_module_count`, `etape_id`) VALUES
(10, 58, '2025-2026', 19.50, 'V', 0, 1),
(10, 58, '2026-2027', 19.50, 'V', 0, 1),
(10, 59, '2025-2026', 8.38, 'NV', 1, 1),
(10, 59, '2026-2027', 10.19, 'V', 0, 1),
(10, 60, '2026-2027', 12.00, 'V', 0, 2),
(10, 61, '2026-2027', 12.00, 'V', 0, 2);

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
(2, 'testcin1222', '1', 'prof121', '1234567823', 1, '2025-2026', 1, NULL),
(4, 'testcin2', 'prof2', 'prof2', '1234567890', 1, '2025-2026', 1, NULL),
(5, 'P23456789', 'azsd', 'saqzdx', '0654265210', 1, '2025-2026', 0, NULL),
(6, 'hhhhhh', 'hhhhhhhhhh', 'hhhhh', '0654265210', 1, '2025-2026', 0, NULL),
(8, 'azsdxce', 'a', 'aa', '0654265210', 1, '2025-2026', 0, NULL),
(9, 'hhhhhhhhh', 'hhhhqwqwq', 'hhhfssdf', '0654265210', 1, '2025-2026', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professor_element`
--

CREATE TABLE `professor_element` (
  `user_id` int(11) NOT NULL,
  `element_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professor_module`
--

CREATE TABLE `professor_module` (
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(2, 'Chef_de_Departement', '2025-09-14 21:20:24', NULL, NULL),
(4, 'Chef_de_Filiere', '2025-09-14 22:43:47', NULL, NULL),
(9, 'Chef_de_Filiere', '2025-09-15 15:59:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professor_tp_session`
--

CREATE TABLE `professor_tp_session` (
  `user_id` int(11) NOT NULL,
  `tp_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'sec1', 4, 1);

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
(58, 'Semestre 1', 1, 4, 1),
(59, 'Semestre 2', 1, 4, 1),
(60, 'Semestre 3', 1, 4, 2),
(61, 'Semestre 4', 1, 4, 2),
(62, 'Semestre 1', 2, 5, 1),
(63, 'Semestre 2', 2, 5, 1);

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
(10, 3, 12.00, 'V', '', '2027-06-30', NULL);

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
(11, 10, '2025-2026', 58, 1, 4, 1, 1, 1, 'active'),
(12, 10, '2025-2026', 59, 1, 4, 1, 1, 1, 'active'),
(96, 10, '2026-2027', 60, 1, 4, 2, 1, NULL, 'active'),
(97, 10, '2026-2027', 61, 1, 4, 2, 1, NULL, 'active'),
(98, 10, '2026-2027', 58, 1, 4, 1, 1, NULL, 'active'),
(99, 10, '2026-2027', 59, 1, 4, 1, 1, NULL, 'active'),
(100, 10, '2027-2028', 62, 2, 5, 1, 1, NULL, 'active'),
(101, 10, '2027-2028', 63, 2, 5, 1, 1, NULL, 'active');

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
('affiche_note', 0),
('grade_period_active', 0),
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
(2, 'prof1', 'prof1', 'prof1@gmail.com', '2025-09-07 15:09:27', NULL, 'chef_dep', 1),
(3, 'yassine.ouali', '$2y$10$vAb7QoR6jhwBTuHc7UJyj.kFpnvc35zBjkR5k1Rt9JhFYRerYA5Jy', 'yassine.ouali@usmba.ac.ma', '2025-09-07 15:20:02', NULL, 'student', 1),
(4, 'prof2', '$2y$10$WfjxUUAW2GUyDtJRx1Ft8etFUxJMLF1x3QKLJHyTTm9H/5/uZeeHG', 'userwq1@gmail.com', '2025-09-07 17:50:48', NULL, 'chef_fill', 1),
(5, 'admin', '$2y$10$NFOxkKZ8HTKf5l4RcAPBMe9m1nlUpnXqv2n/Eta0dm2R4xoFt5XGq', 'aaaa@gmail.com', '2025-09-08 07:04:21', NULL, 'prof', 0),
(6, 'hhhh', '$2y$10$pfIsM3.vvJVVLJ9ortGaN.nyj6nSV7TvXh42Rb6.YCVpIRLbIiVJG', 'hhh@gmail.com', '2025-09-08 07:06:52', NULL, 'prof', 0),
(8, 'admin', '$2y$10$71VGbWJNKyVN2knqFwKKBO5yn8Ag3ZtXej4tUmF5KWLeEOwbkQh7W', 'hdtsghs@gmail.com', '2025-09-08 07:07:54', NULL, 'prof', 0),
(9, 'hhhhh', '$2y$10$TU2zxDOFTVoB.MejDsrsoevRvvj5XljlWFMuP.OOtAQ8yHUCauy1.', 'hhhh@gmail.com', '2025-09-08 07:17:56', NULL, 'prof', 1),
(10, 'yassine.ouali', '$2y$10$gBL9wJS0MrTbhIgJR0Jmbe0efaEKdtC9HHdZh/WcWr0dlTxPQPGG.', 'yassine1234.ouali@usmba.ac.ma', '2025-09-14 23:42:20', NULL, 'student', 1);

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
  ADD UNIQUE KEY `uk_notes` (`student_id`,`element_id`,`semestre_id`,`annee_id`,`etape_id`),
  ADD KEY `fk_notes_element` (`element_id`),
  ADD KEY `fk_notes_semestre` (`semestre_id`),
  ADD KEY `fk_notes_annee` (`annee_id`),
  ADD KEY `fk_notes_etape` (`etape_id`);

--
-- Indexes for table `note_annees`
--
ALTER TABLE `note_annees`
  ADD PRIMARY KEY (`student_id`,`annee_id`,`etape_id`),
  ADD KEY `fk_note_annees_annee` (`annee_id`),
  ADD KEY `fk_note_annees_etape` (`etape_id`);

--
-- Indexes for table `note_modules`
--
ALTER TABLE `note_modules`
  ADD PRIMARY KEY (`student_id`,`module_id`,`semestre_id`,`annee_id`,`etape_id`),
  ADD KEY `fk_note_modules_module` (`module_id`),
  ADD KEY `fk_note_modules_semestre` (`semestre_id`),
  ADD KEY `fk_note_modules_annee` (`annee_id`),
  ADD KEY `fk_note_modules_etape` (`etape_id`);

--
-- Indexes for table `note_semestres`
--
ALTER TABLE `note_semestres`
  ADD PRIMARY KEY (`student_id`,`semestre_id`,`annee_id`,`etape_id`),
  ADD KEY `fk_note_semestres_semestre` (`semestre_id`),
  ADD KEY `fk_note_semestres_annee` (`annee_id`),
  ADD KEY `fk_note_semestres_etape` (`etape_id`);

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
  ADD PRIMARY KEY (`role`,`user_id`) USING BTREE,
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
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `diplomes`
--
ALTER TABLE `diplomes`
  MODIFY `diplome_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `elements`
--
ALTER TABLE `elements`
  MODIFY `element_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `etapes`
--
ALTER TABLE `etapes`
  MODIFY `etape_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grading_rules`
--
ALTER TABLE `grading_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `groupes`
--
ALTER TABLE `groupes`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `semestres`
--
ALTER TABLE `semestres`
  MODIFY `semestre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `tp_sessions`
--
ALTER TABLE `tp_sessions`
  MODIFY `tp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  ADD CONSTRAINT `fk_filieres_department` FOREIGN KEY (`department_id`) REFERENCES `departements` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
  ADD CONSTRAINT `fk_notes_etape` FOREIGN KEY (`etape_id`) REFERENCES `etapes` (`etape_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notes_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notes_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `note_annees`
--
ALTER TABLE `note_annees`
  ADD CONSTRAINT `fk_note_annees_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_annees_etape` FOREIGN KEY (`etape_id`) REFERENCES `etapes` (`etape_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_annees_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `note_modules`
--
ALTER TABLE `note_modules`
  ADD CONSTRAINT `fk_note_modules_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_etape` FOREIGN KEY (`etape_id`) REFERENCES `etapes` (`etape_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_semestre` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`semestre_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_modules_student` FOREIGN KEY (`student_id`) REFERENCES `etudiants` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `note_semestres`
--
ALTER TABLE `note_semestres`
  ADD CONSTRAINT `fk_note_semestres_annee` FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques` (`annee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_note_semestres_etape` FOREIGN KEY (`etape_id`) REFERENCES `etapes` (`etape_id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
