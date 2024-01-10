<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231113125229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE journal_user (journal_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(journal_id, user_id))');
        $this->addSql('CREATE INDEX IDX_35ABEEC1478E8802 ON journal_user (journal_id)');
        $this->addSql('CREATE INDEX IDX_35ABEEC1A76ED395 ON journal_user (user_id)');
        $this->addSql('CREATE TABLE journal_role (journal_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(journal_id, role_id))');
        $this->addSql('CREATE INDEX IDX_EF51B2E2478E8802 ON journal_role (journal_id)');
        $this->addSql('CREATE INDEX IDX_EF51B2E2D60322AC ON journal_role (role_id)');
        $this->addSql('ALTER TABLE journal_user ADD CONSTRAINT FK_35ABEEC1478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user ADD CONSTRAINT FK_35ABEEC1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_role ADD CONSTRAINT FK_EF51B2E2478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_role ADD CONSTRAINT FK_EF51B2E2D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE journal_user DROP CONSTRAINT FK_35ABEEC1478E8802');
        $this->addSql('ALTER TABLE journal_user DROP CONSTRAINT FK_35ABEEC1A76ED395');
        $this->addSql('ALTER TABLE journal_role DROP CONSTRAINT FK_EF51B2E2478E8802');
        $this->addSql('ALTER TABLE journal_role DROP CONSTRAINT FK_EF51B2E2D60322AC');
        $this->addSql('DROP TABLE journal_user');
        $this->addSql('DROP TABLE journal_role');
    }
}
