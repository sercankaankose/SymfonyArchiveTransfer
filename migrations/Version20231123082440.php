<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231123082440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE issues_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE issues (id INT NOT NULL, journal_id INT DEFAULT NULL, year DATE NOT NULL, volume INT DEFAULT NULL, number INT NOT NULL, fulltext VARCHAR(255) DEFAULT NULL, xml VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DA7D7F83478E8802 ON issues (journal_id)');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE issues_id_seq CASCADE');
        $this->addSql('ALTER TABLE issues DROP CONSTRAINT FK_DA7D7F83478E8802');
        $this->addSql('DROP TABLE issues');
    }
}
