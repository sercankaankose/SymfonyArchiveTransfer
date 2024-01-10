<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240104125821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE translator (id INT NOT NULL, article_id INT DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, orc_id VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, institute VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_58CDE3EB7294869C ON translator (article_id)');
        $this->addSql('ALTER TABLE translator ADD CONSTRAINT FK_58CDE3EB7294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE translator DROP CONSTRAINT FK_58CDE3EB7294869C');
        $this->addSql('DROP TABLE translator');
    }
}
