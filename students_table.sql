-- Creating students table
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

-- Sample data
INSERT INTO `students` (`admission_number`, `first_name`, `last_name`, `gender`, `date_of_birth`, `class_id`, `parent_name`, `parent_phone`, `parent_email`, `address`, `status`) VALUES
('ST001', 'John', 'Doe', 'Male', '2005-05-15', 1, 'Jane Doe', '+254700123456', 'jane.doe@example.com', '123 Main St, Nairobi', 'active'),
('ST002', 'Mary', 'Smith', 'Female', '2006-02-20', 1, 'Robert Smith', '+254711234567', 'robert.smith@example.com', '456 Park Ave, Nairobi', 'active'),
('ST003', 'James', 'Johnson', 'Male', '2005-10-10', 2, 'Sarah Johnson', '+254722345678', 'sarah.johnson@example.com', '789 Garden Road, Nairobi', 'active');