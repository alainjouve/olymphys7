<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store all invitees on a national contest invitation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscriptions_cn ADD personnes JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscriptions_cn DROP personnes');
    }
}
