-- Add missing account_id column to invoices table
-- This column is needed to track which financial account receives the payment

ALTER TABLE `invoices` 
ADD COLUMN `account_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Financial account where payment is deposited' 
AFTER `payment_date`;

-- Add index for better query performance
ALTER TABLE `invoices` 
ADD KEY `idx_invoices_account` (`account_id`);
