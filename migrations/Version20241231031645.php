<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241231031645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pack_soccer_player (pack_id INT NOT NULL, soccer_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_D35DEAC1919B217 (pack_id), INDEX IDX_D35DEAC1B36FE2D (soccer_id), INDEX IDX_D35DEACA76ED395 (user_id), PRIMARY KEY(pack_id, soccer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pack_soccer_player ADD CONSTRAINT FK_D35DEAC1919B217 FOREIGN KEY (pack_id) REFERENCES pack (id)');
        $this->addSql('ALTER TABLE pack_soccer_player ADD CONSTRAINT FK_D35DEAC1B36FE2D FOREIGN KEY (soccer_id) REFERENCES soccer_players (id)');
        $this->addSql('ALTER TABLE pack_soccer_player ADD CONSTRAINT FK_D35DEACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pack_soccer_player DROP FOREIGN KEY FK_D35DEAC1919B217');
        $this->addSql('ALTER TABLE pack_soccer_player DROP FOREIGN KEY FK_D35DEAC1B36FE2D');
        $this->addSql('ALTER TABLE pack_soccer_player DROP FOREIGN KEY FK_D35DEACA76ED395');
        $this->addSql('DROP TABLE pack_soccer_player');
    }
}
