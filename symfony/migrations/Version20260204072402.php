<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204072402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pdf_task (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, pdf_id INT DEFAULT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(20) NOT NULL, payload JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', send_by_email TINYINT(1) NOT NULL, INDEX IDX_A15F966BA76ED395 (user_id), UNIQUE INDEX UNIQ_A15F966B511FC912 (pdf_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pdf_task ADD CONSTRAINT FK_A15F966BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE pdf_task ADD CONSTRAINT FK_A15F966B511FC912 FOREIGN KEY (pdf_id) REFERENCES pdf (id)');
        $this->addSql('ALTER TABLE user ADD display_name VARCHAR(100) DEFAULT NULL, ADD avatar_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pdf_task DROP FOREIGN KEY FK_A15F966BA76ED395');
        $this->addSql('ALTER TABLE pdf_task DROP FOREIGN KEY FK_A15F966B511FC912');
        $this->addSql('DROP TABLE pdf_task');
        $this->addSql('ALTER TABLE `user` DROP display_name, DROP avatar_filename');
    }
}
