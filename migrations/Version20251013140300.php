<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013140300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object_actor DROP FOREIGN KEY FK_A9A7A89710DAF24A');
        $this->addSql('ALTER TABLE media_object_actor DROP FOREIGN KEY FK_A9A7A89764DE5A5');
        $this->addSql('DROP TABLE media_object_actor');
        $this->addSql('ALTER TABLE media_object DROP FOREIGN KEY FK_14D431328F93B6FC');
        $this->addSql('DROP INDEX IDX_14D431328F93B6FC ON media_object');
        $this->addSql('ALTER TABLE media_object DROP movie_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media_object_actor (media_object_id INT NOT NULL, actor_id INT NOT NULL, INDEX IDX_A9A7A89764DE5A5 (media_object_id), INDEX IDX_A9A7A89710DAF24A (actor_id), PRIMARY KEY(media_object_id, actor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE media_object_actor ADD CONSTRAINT FK_A9A7A89710DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_object_actor ADD CONSTRAINT FK_A9A7A89764DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_object (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media_object ADD movie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D431328F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id)');
        $this->addSql('CREATE INDEX IDX_14D431328F93B6FC ON media_object (movie_id)');
    }
}
