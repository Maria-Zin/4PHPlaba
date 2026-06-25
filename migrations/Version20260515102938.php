<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515102938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Удалить неиспользуемые likes/dislikes из comment (счёт через comment_reaction)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP likes');
        $this->addSql('ALTER TABLE comment DROP dislikes');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment ADD likes INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD dislikes INT DEFAULT NULL');
    }
}
