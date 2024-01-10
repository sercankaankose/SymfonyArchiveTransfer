<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231130135500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE articles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE articles (id INT NOT NULL, journal_id INT DEFAULT NULL, issue_id INT DEFAULT NULL, primary_language VARCHAR(20) NOT NULL, fulltext VARCHAR(255) NOT NULL, first_page INT DEFAULT NULL, last_page INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BFDD3168478E8802 ON articles (journal_id)');
        $this->addSql('CREATE INDEX IDX_BFDD31685E7AA58C ON articles (issue_id)');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD31685E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE articles_id_seq CASCADE');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD3168478E8802');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD31685E7AA58C');
        $this->addSql('DROP TABLE articles');
    }
}
