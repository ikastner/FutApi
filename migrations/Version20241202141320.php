<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241202141320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pack (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pack_soccer_players (pack_id INT NOT NULL, soccer_players_id INT NOT NULL, INDEX IDX_C46315161919B217 (pack_id), INDEX IDX_C4631516C870A8B8 (soccer_players_id), PRIMARY KEY(pack_id, soccer_players_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pack_soccer_players ADD CONSTRAINT FK_C46315161919B217 FOREIGN KEY (pack_id) REFERENCES pack (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pack_soccer_players ADD CONSTRAINT FK_C4631516C870A8B8 FOREIGN KEY (soccer_players_id) REFERENCES soccer_players (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pack_soccer_players DROP FOREIGN KEY FK_C46315161919B217');
        $this->addSql('ALTER TABLE pack_soccer_players DROP FOREIGN KEY FK_C4631516C870A8B8');
        $this->addSql('DROP TABLE pack');
        $this->addSql('DROP TABLE pack_soccer_players');
    }
}
