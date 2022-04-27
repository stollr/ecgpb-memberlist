<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427190830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ministry_assignment_responsible');

        $this->addSql('CREATE TABLE ext_log_entries (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(191) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(191) DEFAULT NULL, INDEX log_class_lookup_idx (object_class), INDEX log_date_lookup_idx (logged_at), INDEX log_user_lookup_idx (username), INDEX log_version_lookup_idx (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE address ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE person ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ministry_assignment_responsible (id INT AUTO_INCREMENT NOT NULL, ministry_id INT NOT NULL, person_id INT DEFAULT NULL, INDEX IDX_429E1B45217BBB47 (person_id), INDEX IDX_429E1B45C7266135 (ministry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ministry_assignment_responsible ADD CONSTRAINT FK_429E1B45C7266135 FOREIGN KEY (ministry_id) REFERENCES ministry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ministry_assignment_responsible ADD CONSTRAINT FK_429E1B45217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');

        $this->addSql('DROP TABLE ext_log_entries');
        $this->addSql('ALTER TABLE address DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE person DROP created_at, DROP updated_at');
    }
}
