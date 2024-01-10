<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231204135847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE references_id_seq CASCADE');
        $this->addSql('DROP TABLE "references"');
        $this->addSql('ALTER TABLE citations ADD article_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE citations ADD CONSTRAINT FK_AC492EAC7294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_AC492EAC7294869C ON citations (article_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE references_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE "references" (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE citations DROP CONSTRAINT FK_AC492EAC7294869C');
        $this->addSql('DROP INDEX IDX_AC492EAC7294869C');
        $this->addSql('ALTER TABLE citations DROP article_id');
    }
}
