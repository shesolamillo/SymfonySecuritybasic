<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205181708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD handled_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398FE65AF40 FOREIGN KEY (handled_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F5299398FE65AF40 ON `order` (handled_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398FE65AF40');
        $this->addSql('DROP INDEX IDX_F5299398FE65AF40 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP handled_by_id');
    }
}
