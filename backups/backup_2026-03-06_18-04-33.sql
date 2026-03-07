-- Database Backup
-- Generated: 2026-03-06 18:04:33

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `target_audience` enum('all','students','teachers','parents') DEFAULT 'all',
  `expiry_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  KEY `marked_by` (`marked_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `attendance` VALUES ('1','6','2026-02-02','present','8','2026-02-02 19:45:43');
INSERT INTO `attendance` VALUES ('2','6','2026-02-03','absent','8','2026-02-02 19:47:05');
INSERT INTO `attendance` VALUES ('3','6','2026-02-04','absent','8','2026-02-02 20:17:08');
INSERT INTO `attendance` VALUES ('4','6','2026-03-06','absent','8','2026-03-06 10:27:07');

DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `classes` VALUES ('5','COMPUTER ARCHITECTURE','','2026-01-31 12:03:19');
INSERT INTO `classes` VALUES ('6','SOFTWARE DEVELOPMENT','','2026-01-31 12:03:19');
INSERT INTO `classes` VALUES ('7','COMPUTER SYTEM','','2026-01-31 12:03:19');
INSERT INTO `classes` VALUES ('9','COMPUTER APPLICATION','','2026-01-31 12:03:19');

DROP TABLE IF EXISTS `exam_questions`;
CREATE TABLE `exam_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('mcq','short','essay') NOT NULL,
  `marks` int(11) NOT NULL DEFAULT 1,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` int(11) NOT NULL,
  `status` enum('pass','fail') NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_result` (`exam_id`,`student_id`),
  KEY `student_id` (`student_id`),
  KEY `submitted_by` (`submitted_by`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_results_ibfk_3` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `exam_routines`;
CREATE TABLE `exam_routines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `exam_routines_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `exam_routines_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('term','monthly','unit_test','final') NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `max_marks` int(11) NOT NULL,
  `pass_marks` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `exams_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `exams` VALUES ('19','COMPUTER APPLICATION','','9','9','2026-03-06','20','10','8','2026-03-06 18:29:22');

DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_term_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque') NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `student_id` (`student_id`),
  KEY `fee_term_id` (`fee_term_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fee_payments_ibfk_2` FOREIGN KEY (`fee_term_id`) REFERENCES `fee_terms` (`id`),
  CONSTRAINT `fee_payments_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `fee_terms`;
CREATE TABLE `fee_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('monthly','quarterly','annual') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `class_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `fee_terms_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `library_books`;
CREATE TABLE `library_books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `messages` VALUES ('6','17','8','narwaye','mwaramutse, narwaye bituma nsiba','1','2026-02-02 20:23:47');
INSERT INTO `messages` VALUES ('7','8','17','re','well recieved','1','2026-02-02 21:16:55');

DROP TABLE IF EXISTS `parents`;
CREATE TABLE `parents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `relationship` enum('father','mother','guardian') NOT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parents_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL,
  `capacity` int(11) DEFAULT 40,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sections` VALUES ('9','5','A','40');
INSERT INTO `sections` VALUES ('10','5','B','40');
INSERT INTO `sections` VALUES ('11','6','A','40');
INSERT INTO `sections` VALUES ('12','6','B','40');
INSERT INTO `sections` VALUES ('13','7','A','40');
INSERT INTO `sections` VALUES ('14','7','B','40');
INSERT INTO `sections` VALUES ('17','9','A','40');
INSERT INTO `sections` VALUES ('18','9','B','40');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sessions` VALUES ('1','2024-2025','2024-04-01','2025-03-31','1','2026-01-31 12:03:19');

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `roll_number` varchar(20) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roll_number` (`roll_number`),
  KEY `user_id` (`user_id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `students_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` VALUES ('6','17','S006','9','9','2026-02-02','2026-02-01','male','KIGALI/NYARUGENGE','0780468216','uploads/profiles/17_1772810834.jpeg');
INSERT INTO `students` VALUES ('7','22','S0007','9','9','2026-03-06','2009-01-06','male','KIGALI/RWANDA\r\nKIGALI/NYARUGENGE','0780468219','');

DROP TABLE IF EXISTS `study_materials`;
CREATE TABLE `study_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `download_count` int(11) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `class_id` (`class_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `study_materials_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `study_materials_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `study_materials_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `study_materials` VALUES ('1','programming','to be assigned to s6','6','1','uploads/materials/1769927716_book_chap1,2,3.docx','docx','4','0','2026-02-01 08:35:16');
INSERT INTO `study_materials` VALUES ('6','Data structure','data structure','9','9','uploads/materials/1772813635_(R18A0503 ) DATA STRUCTURES Digital Notes.pdf','pdf','8','0','2026-03-06 18:13:55');
INSERT INTO `study_materials` VALUES ('7','CRYPTOGRAPHY AND NETWORK SECURITY','CRYPTOGRAPHY AND NETWORK SECURITY','10','9','uploads/materials/1772813746_CSIT_III-II_CRYPTOGRAPHY AND NETWORK SECURITY DIGITAL NOTES (1).pdf','pdf','8','0','2026-03-06 18:15:46');

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subjects` VALUES ('1','Mathematics','MATH','','2026-01-31 12:03:19');
INSERT INTO `subjects` VALUES ('2','English','ENG','','2026-01-31 12:03:19');
INSERT INTO `subjects` VALUES ('3','Science','SCI','','2026-01-31 12:03:19');
INSERT INTO `subjects` VALUES ('6','Computer Science','CS','','2026-01-31 12:03:19');
INSERT INTO `subjects` VALUES ('7','Physical Education','PE','','2026-01-31 12:03:19');
INSERT INTO `subjects` VALUES ('9','DATA STRUCTURES','DS2026','DATA STRUCTURES','2026-03-06 18:09:00');
INSERT INTO `subjects` VALUES ('10','CRYPTOGRAPHY AND NETWORK SECURITY','CRNET2026','CRYPTOGRAPHY AND NETWORK SECURITY','2026-03-06 18:15:08');

DROP TABLE IF EXISTS `teacher_subjects`;
CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `class_id` (`class_id`),
  KEY `section_id` (`section_id`),
  CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subjects_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subjects_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teacher_subjects` VALUES ('4','2','6','9','17');
INSERT INTO `teacher_subjects` VALUES ('6','2','9','9','17');
INSERT INTO `teacher_subjects` VALUES ('7','2','10','9','17');

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `joining_date` date NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teachers` VALUES ('2','8','T002','ICT','COMPUTER SYSTEM','2024-01-15','0780468216','KIGALI/RWANDA\r\nKIGALI/NYARUGENGE','uploads/profiles/8_1772811641.png');
INSERT INTO `teachers` VALUES ('3','9','T003','M.Sc Physics','Physics','2024-01-15','','','');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','parent') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1','admin','admin@school.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','active','','2026-01-31 12:03:19','2026-01-31 12:03:19');
INSERT INTO `users` VALUES ('4','valen','ukwitegetsev@gmail.com','$2y$10$nmP7uo7KGwbbppBE7ulXGetmSkfTzTezzFiNtmZdXPYl7yV3h6cZi','teacher','active','','2026-01-31 12:29:36','2026-01-31 12:29:36');
INSERT INTO `users` VALUES ('5','aline','ukwitegetse9@gmail.com','$2y$10$1Wc3gzyfcTLUjvXuGLlcfuRHAyWZpzf7sCW1zQFcJ4yT9l4gD31xe','student','active','','2026-01-31 15:12:40','2026-01-31 15:12:40');
INSERT INTO `users` VALUES ('8','teacher','teacher@school.com','$2y$10$EAvAKLOsnYAtDaXAlhf6puB1GKYt6p/UjQHXxOf5vTnh/iZ6af1TW','teacher','active','','2026-01-31 16:02:09','2026-03-06 17:38:14');
INSERT INTO `users` VALUES ('9','mike_wilson','mike@school.com','$2y$10$l8c.jMniy4bVxN4oEu3nkeh1hGKTKxjiymsEHXMIscpTGfUAoZLUe','teacher','active','','2026-01-31 16:02:09','2026-01-31 16:02:09');
INSERT INTO `users` VALUES ('10','student1','student1@school.com','$2y$10$V.zBPtGpB9./hX6uepgSBemRNB6HABxw8ydvm7H.NePxsNvprifSC','student','active','','2026-01-31 16:02:09','2026-01-31 16:02:09');
INSERT INTO `users` VALUES ('11','student2','student2@school.com','$2y$10$febccXy9q8BbVmBgg.eG/.mcuIiTh8m7wWrty2pb.Uiz9KbtGkJCC','student','active','','2026-01-31 16:02:10','2026-01-31 16:02:10');
INSERT INTO `users` VALUES ('12','student3','student3@school.com','$2y$10$bsVqm.t1LfpjBu0kfhIXke1BBGTjas.mOJ/sCWHYT.uuB3bn8rTwe','student','active','','2026-01-31 16:02:10','2026-01-31 16:02:10');
INSERT INTO `users` VALUES ('15','student','student@gmail.com','$2y$10$f2XwemJeoyvD08rhK.LPfOLZpyE3GI09DkmvUl3EWAiSuRVzz4HS6','student','active','','2026-02-02 19:34:56','2026-03-06 10:23:15');
INSERT INTO `users` VALUES ('17','student8','valens@gmail.com','$2y$10$q9mmT.6I7VzGXSOFuCamWOQo8lfHrgucc0Xkn3jnay9I0f2OXzPX.','student','active','','2026-02-02 19:39:12','2026-03-06 17:08:19');
INSERT INTO `users` VALUES ('19','parent','parent@gmail.com','$2y$10$wjNZSyC2.7fTjwHwGC7acu6ngwEwlZCGk83TnHZK40DxX25lYBdZS','parent','active','','2026-02-02 21:38:24','2026-02-02 21:38:24');
INSERT INTO `users` VALUES ('21','valens','ukwitegetsev9@gmail.com','$2y$10$KKbJB1Z9b2vlAbQyM.310eMDrHVhP99JNeN/nYouxMf7mT3zuFAvC','admin','active','uploads/profiles/21_1772810214.jpg','2026-03-06 10:20:21','2026-03-06 17:16:54');
INSERT INTO `users` VALUES ('22','niyigaba','student9@gmail.com','$2y$10$aQM7hSryyn1z3xomacYsYuMPWuWw4A0W9vHfX3tmqrwzozt.x2mDu','student','active','','2026-03-06 18:56:32','2026-03-06 18:56:32');

