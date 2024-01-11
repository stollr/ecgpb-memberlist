<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240111224230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person ADD church_tools_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34DCD176E25DC071 ON person (church_tools_id)');
        $this->addSql('ALTER TABLE working_group ADD church_tools_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_30F62575E25DC071 ON working_group (church_tools_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_34DCD176E25DC071 ON person');
        $this->addSql('ALTER TABLE person DROP church_tools_id');
        $this->addSql('DROP INDEX UNIQ_30F62575E25DC071 ON working_group');
        $this->addSql('ALTER TABLE working_group DROP church_tools_id');
    }
}
