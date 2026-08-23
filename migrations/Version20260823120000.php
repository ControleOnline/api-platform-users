<?php

declare(strict_types=1);

namespace DoctrineMigrations\Users;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add must_change_password and password_change_deadline to users for temporary-password recovery flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE users ADD password_change_deadline DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP password_change_deadline');
        $this->addSql('ALTER TABLE users DROP must_change_password');
    }
}
