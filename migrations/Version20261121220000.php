<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the creation date to national contest invitations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscriptions_cn ADD created_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscriptions_cn DROP created_at');
    }
}
