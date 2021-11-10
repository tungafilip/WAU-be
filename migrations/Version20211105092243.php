<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211105092243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD birthday DATE NOT NULL, ADD last_login DATETIME DEFAULT NULL, ADD active TINYINT(1) NOT NULL, ADD avatar VARCHAR(255) NOT NULL, DROP age');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD age INT NOT NULL, DROP birthday, DROP last_login, DROP active, DROP avatar');
    }
}
