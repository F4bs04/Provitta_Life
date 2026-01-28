-- Schema para MySQL (Hostinger)
-- Provitta Life

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Estrutura da tabela `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'consultant',
  `permissions` text DEFAULT 'view_leads,manage_leads,manage_products',
  `is_invite_user` int(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `leads`
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `pain` varchar(50) DEFAULT 'no',
  `pressure` varchar(50) DEFAULT 'no',
  `diabetes` varchar(50) DEFAULT 'no',
  `sleep` varchar(50) DEFAULT 'good',
  `emotional` varchar(50) DEFAULT 'stable',
  `gut` varchar(50) DEFAULT 'normal',
  `observations` text DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'orcamento_gerado',
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `products`
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `usage_instruction` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_base` int(1) DEFAULT 0,
  `is_active` int(1) DEFAULT 1,
  `image_url` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `protocol_items`
CREATE TABLE IF NOT EXISTS `protocol_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `usage_instruction` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `protocol_items_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `product_rules`
CREATE TABLE IF NOT EXISTS `product_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `condition_type` varchar(100) NOT NULL,
  `condition_value` varchar(255) NOT NULL,
  `priority` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_rules_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `product_alerts`
CREATE TABLE IF NOT EXISTS `product_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `alert_message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `invite_codes`
CREATE TABLE IF NOT EXISTS `invite_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `created_by` int(11) NOT NULL,
  `used_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `is_active` int(1) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_uses` int(11) DEFAULT 1,
  `usage_count` int(11) DEFAULT 0,
  `is_exceptional` int(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `used_by` (`used_by`),
  CONSTRAINT `invite_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `invite_codes_ibfk_2` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Estrutura da tabela `user_sessions`
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(100) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `last_activity` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `invite_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Inserção de usuários Master
-- Senha padrão: @acessoPro#123
INSERT INTO `users` (`username`, `password`, `name`, `role`, `permissions`) VALUES
('admin', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Administrador Master', 'master', 'view_leads,manage_leads,manage_products'),
('leandro', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Leandro - Diretor CEO', 'master', 'view_leads,manage_leads,manage_products'),
('valbrito', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Val Brito - Diretor Comercial', 'master', 'view_leads,manage_leads,manage_products'),
('lucio', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Lúcio - Diretor Tecnológico', 'master', 'view_leads,manage_leads,manage_products'),
('laudimar', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Laudimar - Financeiro', 'master', 'view_leads,manage_leads,manage_products'),
('sol', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Sol - Financeiro - Nutricionista', 'master', 'view_leads,manage_leads,manage_products'),
('mariahercilina', '$2y$12$Bm0WihOA11gWkNP/y6uOKOmaP.U3zbKc.neYP0NMAz99IEob4/xspC', 'Maria Hercilina - Terapeuta', 'master', 'view_leads,manage_leads,manage_products')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `role` = VALUES(`role`), `permissions` = VALUES(`permissions`);

COMMIT;
