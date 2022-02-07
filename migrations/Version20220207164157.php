<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220207164157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE ride CHANGE details details VARCHAR(2000) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE ride_id ride_id VARCHAR(20) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE source source VARCHAR(20) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE scheduled_command CHANGE name name VARCHAR(150) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE command command VARCHAR(200) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE arguments arguments LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE cron_expression cron_expression VARCHAR(200) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE log_file log_file VARCHAR(150) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE email email VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE surname surname VARCHAR(40) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE forename forename VARCHAR(40) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password_request_token password_request_token VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE komoot_id komoot_id VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE komoot_refresh_token komoot_refresh_token LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE strava_id strava_id VARCHAR(25) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE strava_refresh_token strava_refresh_token LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE preferred_provider preferred_provider VARCHAR(20) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
