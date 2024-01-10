<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231204120108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE authors_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE citations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "references_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE translations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE authors (id INT NOT NULL, article_id INT DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, orc_id VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, institute VARCHAR(255) DEFAULT NULL, part VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8E0C2A517294869C ON authors (article_id)');
        $this->addSql('CREATE TABLE citations (id INT NOT NULL, referance TEXT DEFAULT NULL, row INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "references" (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE translations (id INT NOT NULL, article_id INT DEFAULT NULL, locale VARCHAR(20) DEFAULT NULL, abstract TEXT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, keywords JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C6B7DA877294869C ON translations (article_id)');
        $this->addSql('ALTER TABLE authors ADD CONSTRAINT FK_8E0C2A517294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE translations ADD CONSTRAINT FK_C6B7DA877294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issues ADD status VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE authors_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE citations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "references_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE translations_id_seq CASCADE');
        $this->addSql('ALTER TABLE authors DROP CONSTRAINT FK_8E0C2A517294869C');
        $this->addSql('ALTER TABLE translations DROP CONSTRAINT FK_C6B7DA877294869C');
        $this->addSql('DROP TABLE authors');
        $this->addSql('DROP TABLE citations');
        $this->addSql('DROP TABLE "references"');
        $this->addSql('DROP TABLE translations');
        $this->addSql('ALTER TABLE issues DROP status');
    }
}
