<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231115085405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE journal_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE journal_user (id INT NOT NULL, person_id INT DEFAULT NULL, journal_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_35ABEEC1217BBB47 ON journal_user (person_id)');
        $this->addSql('CREATE INDEX IDX_35ABEEC1478E8802 ON journal_user (journal_id)');
        $this->addSql('CREATE TABLE journal_user_role (journal_user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(journal_user_id, role_id))');
        $this->addSql('CREATE INDEX IDX_9F857D2677D59D70 ON journal_user_role (journal_user_id)');
        $this->addSql('CREATE INDEX IDX_9F857D26D60322AC ON journal_user_role (role_id)');
        $this->addSql('ALTER TABLE journal_user ADD CONSTRAINT FK_35ABEEC1217BBB47 FOREIGN KEY (person_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user ADD CONSTRAINT FK_35ABEEC1478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user_role ADD CONSTRAINT FK_9F857D2677D59D70 FOREIGN KEY (journal_user_id) REFERENCES journal_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user_role ADD CONSTRAINT FK_9F857D26D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE journal_user_id_seq CASCADE');
        $this->addSql('ALTER TABLE journal_user DROP CONSTRAINT FK_35ABEEC1217BBB47');
        $this->addSql('ALTER TABLE journal_user DROP CONSTRAINT FK_35ABEEC1478E8802');
        $this->addSql('ALTER TABLE journal_user_role DROP CONSTRAINT FK_9F857D2677D59D70');
        $this->addSql('ALTER TABLE journal_user_role DROP CONSTRAINT FK_9F857D26D60322AC');
        $this->addSql('DROP TABLE journal_user');
        $this->addSql('DROP TABLE journal_user_role');
    }
}
