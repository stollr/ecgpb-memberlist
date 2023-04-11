<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230411230737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE phone phone VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\'');
        $this->addSql('ALTER TABLE person CHANGE mobile mobile VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\'');

        $this->addSql(
            "UPDATE address
             SET phone = NULLIF(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '')
             WHERE phone IS NOT NULL");
        $this->addSql(
            "UPDATE address
             SET phone = CONCAT('+49', SUBSTRING(phone, 2))
             WHERE phone LIKE '0%'");

        $this->addSql(
            "UPDATE person
             SET mobile = NULLIF(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '')
             WHERE mobile IS NOT NULL");
        $this->addSql(
            "UPDATE person
             SET mobile = CONCAT('+49', SUBSTRING(mobile, 2))
             WHERE mobile LIKE '0%'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person CHANGE mobile mobile VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE address CHANGE phone phone VARCHAR(30) DEFAULT NULL');
    }
}
