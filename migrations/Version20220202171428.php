<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220202171428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scheduled_command (id INT AUTO_INCREMENT NOT NULL, version INT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT NULL, name VARCHAR(150) NOT NULL, command VARCHAR(200) NOT NULL, arguments LONGTEXT DEFAULT NULL, cron_expression VARCHAR(200) DEFAULT NULL, last_execution DATETIME DEFAULT NULL, last_return_code INT DEFAULT NULL, log_file VARCHAR(150) DEFAULT NULL, priority INT NOT NULL, execute_immediately TINYINT(1) NOT NULL, disabled TINYINT(1) NOT NULL, locked TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_EA0DBC905E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE scheduled_command');
        $this->addSql('ALTER TABLE ride CHANGE details details VARCHAR(2000) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ride_id ride_id VARCHAR(20) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE source source VARCHAR(20) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE email email VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE surname surname VARCHAR(40) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE forename forename VARCHAR(40) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password_request_token password_request_token VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE komoot_id komoot_id VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE komoot_refresh_token komoot_refresh_token LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE strava_id strava_id VARCHAR(25) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE strava_refresh_token strava_refresh_token LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE preferred_provider preferred_provider VARCHAR(20) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
