<?php

declare(strict_types=1);

namespace DoctrineMigrations\Users;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Baseline schema for users module from s.controleonline.com";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8 NOT NULL,
  `timezone_id` smallint(5) unsigned DEFAULT NULL,
  `hash` varchar(255) CHARACTER SET utf8 NOT NULL,
  `oauth_user` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `oauth_hash` varchar(40) CHARACTER SET utf8 DEFAULT NULL,
  `lost_password` varchar(60) CHARACTER SET utf8 DEFAULT NULL,
  `api_key` varchar(60) CHARACTER SET utf8 NOT NULL,
  `people_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name` (`username`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `people_id` (`people_id`),
  KEY `timezone_id` (`timezone_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`timezone_id`) REFERENCES `timezones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS `users`');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
