ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `password_recovery_requested_at` DATETIME DEFAULT NULL
AFTER `lost_password`;
