-- Create fee_types table
CREATE TABLE IF NOT EXISTS `fee_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample fee types
INSERT INTO `fee_types` (`name`, `description`) VALUES
('Tuition Fee', 'Regular tuition fees for academic term'),
('Development Fee', 'Fee for school development projects'),
('Exam Fee', 'Fee for examinations'),
('Transport Fee', 'Fee for school transportation service'),
('Activity Fee', 'Fee for extra-curricular activities');

-- Create fees table
CREATE TABLE IF NOT EXISTS `fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `term` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `fee_type_id` (`fee_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payments table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `mpesa_reference` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fee_id` (`fee_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for fees table (using student IDs from students table)
INSERT INTO `fees` (`student_id`, `fee_type_id`, `amount`, `amount_paid`, `status`, `term`, `academic_year`, `due_date`, `notes`) VALUES
(1, 1, 15000.00, 0.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'First term tuition fee'),
(1, 2, 5000.00, 0.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'Development fee'),
(2, 1, 15000.00, 15000.00, 'paid', 'Term 1', '2023-2024', '2023-09-15', 'First term tuition fee'),
(2, 3, 2000.00, 0.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'Exam fee'),
(3, 1, 15000.00, 10000.00, 'partial', 'Term 1', '2023-2024', '2023-09-15', 'First term tuition fee'),
(3, 5, 3000.00, 0.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'Activity fee');