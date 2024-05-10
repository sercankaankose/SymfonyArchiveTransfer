<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327122423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE articles_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE authors_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE citations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE issues_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE journal_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE journal_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE role_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE translations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE translator_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE articles (id INT NOT NULL, journal_id INT DEFAULT NULL, issue_id INT DEFAULT NULL, primary_language VARCHAR(20) NOT NULL, fulltext VARCHAR(255) NOT NULL, first_page INT DEFAULT NULL, last_page INT DEFAULT NULL, doi VARCHAR(255) DEFAULT NULL, errors JSON DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, received_date DATE DEFAULT NULL, accepted_date DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BFDD3168478E8802 ON articles (journal_id)');
        $this->addSql('CREATE INDEX IDX_BFDD31685E7AA58C ON articles (issue_id)');
        $this->addSql('CREATE TABLE authors (id INT NOT NULL, article_id INT DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, orc_id VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, institute VARCHAR(255) DEFAULT NULL, row INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8E0C2A517294869C ON authors (article_id)');
        $this->addSql('CREATE TABLE citations (id INT NOT NULL, article_id INT DEFAULT NULL, referance TEXT DEFAULT NULL, row INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AC492EAC7294869C ON citations (article_id)');
        $this->addSql('CREATE TABLE issues (id INT NOT NULL, journal_id INT DEFAULT NULL, year INT NOT NULL, volume INT DEFAULT NULL, number INT NOT NULL, fulltext VARCHAR(255) DEFAULT NULL, xml VARCHAR(255) NOT NULL, status VARCHAR(255) DEFAULT NULL, errors JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DA7D7F83478E8802 ON issues (journal_id)');
        $this->addSql('CREATE TABLE journal (id INT NOT NULL, name VARCHAR(255) NOT NULL, issn VARCHAR(9) DEFAULT NULL, e_issn VARCHAR(9) DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1A7E74D9FC5D7F6 ON journal (issn)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1A7E74DED396FBA ON journal (e_issn)');
        $this->addSql('CREATE TABLE journal_user (id INT NOT NULL, person_id INT DEFAULT NULL, journal_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_35ABEEC1217BBB47 ON journal_user (person_id)');
        $this->addSql('CREATE INDEX IDX_35ABEEC1478E8802 ON journal_user (journal_id)');
        $this->addSql('CREATE TABLE journal_user_role (journal_user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(journal_user_id, role_id))');
        $this->addSql('CREATE INDEX IDX_9F857D2677D59D70 ON journal_user_role (journal_user_id)');
        $this->addSql('CREATE INDEX IDX_9F857D26D60322AC ON journal_user_role (role_id)');
        $this->addSql('CREATE TABLE role (id INT NOT NULL, role_name VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE translations (id INT NOT NULL, article_id INT DEFAULT NULL, locale VARCHAR(20) DEFAULT NULL, abstract TEXT DEFAULT NULL, title VARCHAR(555) DEFAULT NULL, keywords JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C6B7DA877294869C ON translations (article_id)');
        $this->addSql('CREATE TABLE translator (id INT NOT NULL, article_id INT DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, orc_id VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, institute VARCHAR(255) DEFAULT NULL, row INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_58CDE3EB7294869C ON translator (article_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) DEFAULT NULL, surname VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, is_admin BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE user_role (user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(user_id, role_id))');
        $this->addSql('CREATE INDEX IDX_2DE8C6A3A76ED395 ON user_role (user_id)');
        $this->addSql('CREATE INDEX IDX_2DE8C6A3D60322AC ON user_role (role_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD31685E7AA58C FOREIGN KEY (issue_id) REFERENCES issues (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE authors ADD CONSTRAINT FK_8E0C2A517294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE citations ADD CONSTRAINT FK_AC492EAC7294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE issues ADD CONSTRAINT FK_DA7D7F83478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user ADD CONSTRAINT FK_35ABEEC1217BBB47 FOREIGN KEY (person_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user ADD CONSTRAINT FK_35ABEEC1478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user_role ADD CONSTRAINT FK_9F857D2677D59D70 FOREIGN KEY (journal_user_id) REFERENCES journal_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE journal_user_role ADD CONSTRAINT FK_9F857D26D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE translations ADD CONSTRAINT FK_C6B7DA877294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE translator ADD CONSTRAINT FK_58CDE3EB7294869C FOREIGN KEY (article_id) REFERENCES articles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE articles_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE authors_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE citations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE issues_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE journal_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE journal_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE role_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE translations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE translator_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD3168478E8802');
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD31685E7AA58C');
        $this->addSql('ALTER TABLE authors DROP CONSTRAINT FK_8E0C2A517294869C');
        $this->addSql('ALTER TABLE citations DROP CONSTRAINT FK_AC492EAC7294869C');
        $this->addSql('ALTER TABLE issues DROP CONSTRAINT FK_DA7D7F83478E8802');
        $this->addSql('ALTER TABLE journal_user DROP CONSTRAINT FK_35ABEEC1217BBB47');
        $this->addSql('ALTER TABLE journal_user DROP CONSTRAINT FK_35ABEEC1478E8802');
        $this->addSql('ALTER TABLE journal_user_role DROP CONSTRAINT FK_9F857D2677D59D70');
        $this->addSql('ALTER TABLE journal_user_role DROP CONSTRAINT FK_9F857D26D60322AC');
        $this->addSql('ALTER TABLE translations DROP CONSTRAINT FK_C6B7DA877294869C');
        $this->addSql('ALTER TABLE translator DROP CONSTRAINT FK_58CDE3EB7294869C');
        $this->addSql('ALTER TABLE user_role DROP CONSTRAINT FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP CONSTRAINT FK_2DE8C6A3D60322AC');
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE authors');
        $this->addSql('DROP TABLE citations');
        $this->addSql('DROP TABLE issues');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE journal_user');
        $this->addSql('DROP TABLE journal_user_role');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE translations');
        $this->addSql('DROP TABLE translator');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_role');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
