-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: telecare_db
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,2,'register','New user registered','::1','2026-02-20 06:35:25'),(2,2,'login','User logged in','::1','2026-02-20 06:35:44'),(3,2,'login','User logged in','::1','2026-02-20 06:41:50'),(4,2,'login','User logged in','::1','2026-02-20 06:47:33'),(5,2,'login','User logged in','::1','2026-02-20 07:04:28'),(6,2,'login','User logged in','::1','2026-02-20 07:10:57'),(7,2,'login','User logged in','::1','2026-02-20 07:14:16'),(8,3,'register','New user registered','::1','2026-02-20 09:00:03'),(9,3,'login','User logged in','::1','2026-02-20 09:00:10'),(10,2,'login','User logged in','::1','2026-02-20 09:00:50'),(11,2,'book_appointment','Booked appointment with doctor ID 3 on 2026-02-21 at 08:30:00','::1','2026-02-20 09:02:53'),(12,2,'book_appointment','Booked appointment with doctor ID 3 on 2026-02-20 at 08:30:00','::1','2026-02-20 09:32:00'),(13,3,'login','User logged in','::1','2026-02-20 10:27:54'),(14,2,'login','User logged in','::1','2026-02-20 10:30:52'),(15,3,'login','User logged in','::1','2026-02-20 10:32:09'),(16,2,'login','User logged in','::1','2026-02-20 10:38:59'),(17,2,'login','User logged in','::1','2026-02-20 11:49:16'),(18,2,'book_appointment','Booked appointment with doctor ID 3 on 2026-02-22 at 11:30:00','::1','2026-02-20 11:49:58'),(19,2,'book_appointment','Booked appointment with doctor ID 3 on 2026-02-23 at 13:30:00','::1','2026-02-20 11:54:02'),(20,3,'login','User logged in','::1','2026-02-20 11:55:48'),(21,2,'login','User logged in via Google','::1','2026-02-20 15:14:29'),(22,4,'register','User registered via Google','::1','2026-02-20 15:29:21'),(23,2,'login','User logged in via Google','::1','2026-02-20 15:44:12'),(24,2,'login','User logged in via Google','::1','2026-02-20 15:53:44'),(25,2,'login','User logged in via Google','::1','2026-02-20 15:54:35'),(26,2,'login','User logged in via Google','::1','2026-02-20 16:00:27');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int unsigned NOT NULL,
  `doctor_id` int unsigned NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chief_complaint` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `meeting_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,2,3,'2026-02-21','08:30:00','completed','unpaid',NULL,NULL,NULL,NULL,'2026-02-20 09:02:53','2026-02-20 12:13:02'),(2,2,3,'2026-02-20','08:30:00','completed','unpaid',NULL,NULL,NULL,NULL,'2026-02-20 09:32:00','2026-02-20 12:12:23'),(3,2,3,'2026-02-22','11:30:00','cancelled','unpaid',NULL,'flu',NULL,NULL,'2026-02-20 11:49:58','2026-02-20 11:52:40'),(4,2,3,'2026-02-23','13:30:00','confirmed','unpaid',NULL,'therapy',NULL,NULL,'2026-02-20 11:54:02','2026-02-20 11:56:04');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_meetings`
--

DROP TABLE IF EXISTS `clinic_meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_meetings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `created_by` int unsigned NOT NULL,
  `transcript` text COLLATE utf8mb4_unicode_ci,
  `ai_summary` text COLLATE utf8mb4_unicode_ci,
  `decisions` text COLLATE utf8mb4_unicode_ci,
  `status` enum('scheduled','ongoing','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `clinic_meetings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_meetings`
--

LOCK TABLES `clinic_meetings` WRITE;
/*!40000 ALTER TABLE `clinic_meetings` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinic_meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultations`
--

DROP TABLE IF EXISTS `consultations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` int unsigned NOT NULL,
  `transcript` text COLLATE utf8mb4_unicode_ci,
  `ai_summary` text COLLATE utf8mb4_unicode_ci,
  `action_items` text COLLATE utf8mb4_unicode_ci,
  `follow_up_date` date DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultations`
--

LOCK TABLES `consultations` WRITE;
/*!40000 ALTER TABLE `consultations` DISABLE KEYS */;
INSERT INTO `consultations` VALUES (1,1,'often headache','{\"chief_complaint\":\"Not specified\",\"findings\":\"Patient presented with the documented symptoms. Clinical examination performed.\",\"diagnosis\":\"To be determined based on further evaluation.\",\"plan\":\"Follow-up in 1-2 weeks. Prescribed rest and monitoring.\",\"action_items\":[\"Schedule follow-up appointment\",\"Monitor symptoms\",\"Return if condition worsens\"],\"follow_up_date\":\"2026-02-27\"}','[\"Schedule follow-up appointment\",\"Monitor symptoms\",\"Return if condition worsens\"]','2026-02-27','2026-02-20 10:32:14','2026-02-20 12:13:02','2026-02-20 10:32:14'),(2,4,NULL,NULL,NULL,NULL,'2026-02-20 11:56:29',NULL,'2026-02-20 11:56:29'),(3,2,'',NULL,NULL,NULL,'2026-02-20 12:00:22','2026-02-20 12:12:23','2026-02-20 12:00:22');
/*!40000 ALTER TABLE `consultations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_profiles`
--

DROP TABLE IF EXISTS `doctor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `specialization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `years_of_experience` int DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `doctor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_profiles`
--

LOCK TABLES `doctor_profiles` WRITE;
/*!40000 ALTER TABLE `doctor_profiles` DISABLE KEYS */;
INSERT INTO `doctor_profiles` VALUES (1,3,NULL,NULL,NULL,NULL,NULL,1,'2026-02-20 09:00:03','2026-02-20 09:00:03');
/*!40000 ALTER TABLE `doctor_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_documents`
--

DROP TABLE IF EXISTS `medical_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int unsigned NOT NULL,
  `appointment_id` int unsigned DEFAULT NULL,
  `document_type` enum('lab_result','prescription','medical_certificate','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ocr_text` text COLLATE utf8mb4_unicode_ci,
  `extracted_data` json DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `medical_documents_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_documents_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_documents`
--

LOCK TABLES `medical_documents` WRITE;
/*!40000 ALTER TABLE `medical_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `medical_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_action_items`
--

DROP TABLE IF EXISTS `meeting_action_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_action_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` int unsigned NOT NULL,
  `assigned_to` int unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `meeting_action_items_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `clinic_meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_action_items_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_action_items`
--

LOCK TABLES `meeting_action_items` WRITE;
/*!40000 ALTER TABLE `meeting_action_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_action_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_profiles`
--

DROP TABLE IF EXISTS `patient_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `blood_type` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `medical_history` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `patient_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_profiles`
--

LOCK TABLES `patient_profiles` WRITE;
/*!40000 ALTER TABLE `patient_profiles` DISABLE KEYS */;
INSERT INTO `patient_profiles` VALUES (1,2,NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-20 06:35:25','2026-02-20 06:35:25');
/*!40000 ALTER TABLE `patient_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_google_id` (`google_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','TeleCare','admin@telecare.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,NULL,NULL,NULL,1,1,NULL,'2026-02-20 06:29:14','2026-02-20 06:29:14'),(2,'John Noel','Orano','johnnoelorano@gmail.com','$2y$12$jht9Gh6MHUcuM5TsNmhCHOJhTe0rOZZapMn5FbE3JYrwKCyc.FlYW','patient','male',NULL,NULL,NULL,0,1,'108607019632326848287','2026-02-20 06:35:25','2026-02-20 15:13:34'),(3,'Ian Matthew','Payawal','payawalIanmatthewbsis2023@gmail.com','$2y$12$jbVR03emJCoRUPJ/chFnVO0qqjOPsFejPSWMn8up5tiRxYC3jBH/S','doctor','male',NULL,NULL,NULL,0,1,NULL,'2026-02-20 09:00:03','2026-02-20 09:00:03'),(4,'Oraño,','John Noel D.A.','oranojohnnoelbsis2023@gmail.com','$2y$10$/WFC.hDti9p8A1H0UFlE8u03i2smNqwUjvQcjGQv.pXMPCUdjkqey','patient',NULL,NULL,NULL,NULL,0,1,'106568763966400667098','2026-02-20 15:29:20','2026-02-20 15:29:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'telecare_db'
--

--
-- Dumping routines for database 'telecare_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-21  0:22:03
