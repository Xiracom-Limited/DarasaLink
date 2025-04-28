-- Create students table if it doesn't exist
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `admission_number` (`admission_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create fee_types table if it doesn't exist
CREATE TABLE IF NOT EXISTS `fee_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create fees table if it doesn't exist
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

-- Create payments table if it doesn't exist
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

-- Create mpesa_transactions table if it doesn't exist
CREATE TABLE IF NOT EXISTS `mpesa_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(50) DEFAULT NULL,
  `trans_id` varchar(50) DEFAULT NULL,
  `trans_time` varchar(50) DEFAULT NULL,
  `trans_amount` decimal(10,2) DEFAULT NULL,
  `business_short_code` varchar(20) DEFAULT NULL,
  `bill_ref_number` varchar(50) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `org_account_balance` decimal(10,2) DEFAULT NULL,
  `third_party_trans_id` varchar(50) DEFAULT NULL,
  `msisdn` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `result_code` varchar(10) DEFAULT NULL,
  `result_desc` text DEFAULT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `trans_id` (`trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default students (only if not exist)
INSERT INTO `students` (`admission_number`, `first_name`, `last_name`, `gender`, `date_of_birth`, `parent_name`, `parent_phone`, `status`)
SELECT * FROM (SELECT 'ST001', 'John', 'Doe', 'Male', '2005-05-15', 'Jane Doe', '+254700123456', 'active') AS tmp
WHERE NOT EXISTS (
    SELECT admission_number FROM students WHERE admission_number = 'ST001'
) LIMIT 1;

INSERT INTO `students` (`admission_number`, `first_name`, `last_name`, `gender`, `date_of_birth`, `parent_name`, `parent_phone`, `status`)
SELECT * FROM (SELECT 'ST002', 'Mary', 'Smith', 'Female', '2006-02-20', 'Robert Smith', '+254711234567', 'active') AS tmp
WHERE NOT EXISTS (
    SELECT admission_number FROM students WHERE admission_number = 'ST002'
) LIMIT 1;

INSERT INTO `students` (`admission_number`, `first_name`, `last_name`, `gender`, `date_of_birth`, `parent_name`, `parent_phone`, `status`)
SELECT * FROM (SELECT 'ST003', 'James', 'Johnson', 'Male', '2005-10-10', 'Sarah Johnson', '+254722345678', 'active') AS tmp
WHERE NOT EXISTS (
    SELECT admission_number FROM students WHERE admission_number = 'ST003'
) LIMIT 1;

-- Insert default fee types (only if not exist)
INSERT INTO `fee_types` (`name`, `description`)
SELECT * FROM (SELECT 'Tuition Fee', 'Regular tuition fees for academic term') AS tmp
WHERE NOT EXISTS (
    SELECT name FROM fee_types WHERE name = 'Tuition Fee'
) LIMIT 1;

INSERT INTO `fee_types` (`name`, `description`)
SELECT * FROM (SELECT 'Development Fee', 'Fee for school development projects') AS tmp
WHERE NOT EXISTS (
    SELECT name FROM fee_types WHERE name = 'Development Fee'
) LIMIT 1;

INSERT INTO `fee_types` (`name`, `description`)
SELECT * FROM (SELECT 'Exam Fee', 'Fee for examinations') AS tmp
WHERE NOT EXISTS (
    SELECT name FROM fee_types WHERE name = 'Exam Fee'
) LIMIT 1;

-- Insert default fees for the students (only if not exist)
-- Get student and fee type IDs first
SET @student1_id = (SELECT id FROM students WHERE admission_number = 'ST001' LIMIT 1);
SET @student2_id = (SELECT id FROM students WHERE admission_number = 'ST002' LIMIT 1);
SET @student3_id = (SELECT id FROM students WHERE admission_number = 'ST003' LIMIT 1);
SET @tuition_id = (SELECT id FROM fee_types WHERE name = 'Tuition Fee' LIMIT 1);
SET @dev_id = (SELECT id FROM fee_types WHERE name = 'Development Fee' LIMIT 1);
SET @exam_id = (SELECT id FROM fee_types WHERE name = 'Exam Fee' LIMIT 1);

-- Only insert if we have students and fee types
INSERT INTO `fees` (`student_id`, `fee_type_id`, `amount`, `status`, `term`, `academic_year`, `due_date`, `notes`)
SELECT @student1_id, @tuition_id, 15000.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'First term tuition fee'
WHERE @student1_id IS NOT NULL AND @tuition_id IS NOT NULL
AND NOT EXISTS (
    SELECT id FROM fees WHERE student_id = @student1_id AND fee_type_id = @tuition_id AND term = 'Term 1' AND academic_year = '2023-2024'
);

INSERT INTO `fees` (`student_id`, `fee_type_id`, `amount`, `status`, `term`, `academic_year`, `due_date`, `notes`)
SELECT @student1_id, @dev_id, 5000.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'Development fee'
WHERE @student1_id IS NOT NULL AND @dev_id IS NOT NULL
AND NOT EXISTS (
    SELECT id FROM fees WHERE student_id = @student1_id AND fee_type_id = @dev_id AND term = 'Term 1' AND academic_year = '2023-2024'
);

INSERT INTO `fees` (`student_id`, `fee_type_id`, `amount`, `status`, `term`, `academic_year`, `due_date`, `notes`)
SELECT @student2_id, @tuition_id, 15000.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'First term tuition fee'
WHERE @student2_id IS NOT NULL AND @tuition_id IS NOT NULL
AND NOT EXISTS (
    SELECT id FROM fees WHERE student_id = @student2_id AND fee_type_id = @tuition_id AND term = 'Term 1' AND academic_year = '2023-2024'
);

INSERT INTO `fees` (`student_id`, `fee_type_id`, `amount`, `status`, `term`, `academic_year`, `due_date`, `notes`)
SELECT @student3_id, @tuition_id, 15000.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'First term tuition fee'
WHERE @student3_id IS NOT NULL AND @tuition_id IS NOT NULL
AND NOT EXISTS (
    SELECT id FROM fees WHERE student_id = @student3_id AND fee_type_id = @tuition_id AND term = 'Term 1' AND academic_year = '2023-2024'
);

INSERT INTO `fees` (`student_id`, `fee_type_id`, `amount`, `status`, `term`, `academic_year`, `due_date`, `notes`)
SELECT @student3_id, @exam_id, 2000.00, 'unpaid', 'Term 1', '2023-2024', '2023-09-15', 'Exam fee'
WHERE @student3_id IS NOT NULL AND @exam_id IS NOT NULL
AND NOT EXISTS (
    SELECT id FROM fees WHERE student_id = @student3_id AND fee_type_id = @exam_id AND term = 'Term 1' AND academic_year = '2023-2024'
);