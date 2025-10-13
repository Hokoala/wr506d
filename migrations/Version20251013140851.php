<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013140851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actor_media_object DROP FOREIGN KEY FK_18E6A66710DAF24A');
        $this->addSql('ALTER TABLE actor_media_object DROP FOREIGN KEY FK_18E6A66764DE5A5');
        $this->addSql('DROP TABLE actor_media_object');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE actor_media_object (actor_id INT NOT NULL, media_object_id INT NOT NULL, INDEX IDX_18E6A66710DAF24A (actor_id), INDEX IDX_18E6A66764DE5A5 (media_object_id), PRIMARY KEY(actor_id, media_object_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE actor_media_object ADD CONSTRAINT FK_18E6A66710DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE actor_media_object ADD CONSTRAINT FK_18E6A66764DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_object (id) ON DELETE CASCADE');
    }
}
