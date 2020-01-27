<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200127162102 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user ADD komoot_refresh_token LONGTEXT DEFAULT NULL, ADD komoot_token_expiry BIGINT DEFAULT NULL, ADD komoot_id VARCHAR(255) DEFAULT NULL, ADD strava_refresh_token LONGTEXT DEFAULT NULL, ADD strava_token_expiry BIGINT DEFAULT NULL, ADD preferred_provider VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE ride ADD source VARCHAR(20) DEFAULT NULL, CHANGE strava_ride_id ride_id VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ride ADD strava_ride_id VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP ride_id, DROP source');
        $this->addSql('ALTER TABLE user DROP komoot_refresh_token, DROP komoot_token_expiry, DROP komoot_id, DROP strava_refresh_token, DROP strava_token_expiry, DROP preferred_provider');
    }
}
