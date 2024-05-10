<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240507112447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articles ADD editor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE articles ADD modification_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD31686995AC4C FOREIGN KEY (editor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BFDD31686995AC4C ON articles (editor_id)');
        $this->addSql('ALTER TABLE journal DROP publisher');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD31686995AC4C');
        $this->addSql('DROP INDEX IDX_BFDD31686995AC4C');
        $this->addSql('ALTER TABLE articles DROP editor_id');
        $this->addSql('ALTER TABLE articles DROP modification_date');
        $this->addSql('ALTER TABLE journal ADD publisher VARCHAR(255) DEFAULT NULL');
    }
}
