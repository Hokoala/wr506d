<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210150309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD api_key_hash VARCHAR(64) NOT NULL, ADD api_key_prefix VARCHAR(16) DEFAULT NULL, ADD api_key_enabled TINYINT(1) NOT NULL, ADD api_key_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD api_key_last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP api_key_hash, DROP api_key_prefix, DROP api_key_enabled, DROP api_key_created_at, DROP api_key_last_used_at');
    }
}
