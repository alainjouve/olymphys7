<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016193343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the UAI code unique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E5F3F0B3E5F3F0B3 ON uai (uai)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_E5F3F0B3E5F3F0B3 ON uai');
    }
}
