<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220108085518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE komoot_token_expiry komoot_token_expiry BIGINT DEFAULT 0, CHANGE strava_token_expiry strava_token_expiry BIGINT DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE komoot_token_expiry komoot_token_expiry BIGINT DEFAULT NULL, CHANGE strava_token_expiry strava_token_expiry BIGINT DEFAULT NULL');
    }
}
