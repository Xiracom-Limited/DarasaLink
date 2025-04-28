-- 

-- Table structure for table `mpesa_payments`
-- This table is designed to store M-Pesa payment records for students, including various details about the payment and its status.

---
CREATE TABLE `mpesa_payments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL,
  `student_id` int(11) NOT NULL, -- Foreign key to students table
  `fee_groups_feetype_id` int(11) DEFAULT NULL, -- Direct link to fee_groups_feetype table
  `feetype_id` int(11) DEFAULT NULL, -- Reference to feetype table
  `phone_number` varchar(15) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `payment_purpose` enum('fees','admission','uniform','transport','other') DEFAULT 'fees',
  `mpesa_reference` varchar(50) DEFAULT NULL, -- M-Pesa transaction CODE as received from the API
  `transaction_id` varchar(100) DEFAULT NULL, -- Internal transaction ID
  `request_id` varchar(100) NOT NULL, -- Daraja API request ID
  `session_id` int(11) DEFAULT NULL, -- Reference to academic session
  `academic_year` varchar(20) DEFAULT NULL, -- e.g., "2024-2025"
  `term` varchar(20) DEFAULT NULL, -- e.g., "Term 1"
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `callback_received_at` timestamp NULL DEFAULT NULL,
  `reconciled` tinyint(1) DEFAULT 0, -- Flag to track if payment is reconciled
  `notes` text DEFAULT NULL, -- For any additional payment details
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_id` (`order_id`),
  UNIQUE KEY `unique_request_id` (`request_id`),
  KEY `idx_mpesa_reference` (`mpesa_reference`),
  KEY `idx_status` (`status`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_feetype_id` (`feetype_id`),
  KEY `idx_fee_groups_feetype_id` (`fee_groups_feetype_id`),
  KEY `idx_academic_year_term` (`academic_year`, `term`),
  KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample record to handle not null values in the `mpesa_payments` table

INSERT INTO `mpesa_payments` 
(`order_id`, `student_id`, `fee_groups_feetype_id`, `feetype_id`, `phone_number`, 
 `amount`, `status`, `payment_purpose`, `mpesa_reference`, `transaction_id`, 
 `request_id`, `session_id`, `academic_year`, `term`, `notes`) 
VALUES 
('ORD-20250425-001', 101, 1, 1, '254712345678', 
 15000.00, 'success', 'fees', 'TDP46ZJTZU', 'TXN-20250425-001', 
 'REQ-20250425-001', 21, '2024-2025', 'Term 2', 'Tuition payment for Term 2');