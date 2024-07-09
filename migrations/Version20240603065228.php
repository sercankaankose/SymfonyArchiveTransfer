<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240603065228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal ADD exporter_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE journal ADD export BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE journal ADD export_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE journal ADD CONSTRAINT FK_C1A7E74DB4523DE5 FOREIGN KEY (exporter_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C1A7E74DB4523DE5 ON journal (exporter_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE journal DROP CONSTRAINT FK_C1A7E74DB4523DE5');
        $this->addSql('DROP INDEX IDX_C1A7E74DB4523DE5');
        $this->addSql('ALTER TABLE journal DROP exporter_id');
        $this->addSql('ALTER TABLE journal DROP export');
        $this->addSql('ALTER TABLE journal DROP export_date');
    }
}
