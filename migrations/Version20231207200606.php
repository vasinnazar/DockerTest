<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231207200606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('users');
        // ...more fields
        $table->addColumn('id', 'integer', ['notnull' => true]);
        $table->addColumn('email', 'string', ['notnull' => true]);
        $table->addColumn('sex', 'integer', ['notnull' => true]);
        $table->addColumn('age', 'string', ['notnull' => true]);
        $table->addColumn('birthday', 'datetime', ['notnull' => true]);
        $table->addColumn('phone', 'string', ['notnull' => true]);
        $table->addColumn('created_at', 'datetime', ['default' => (new \DateTime())->format('Y-m-d H:m:s')])/*->setDefault(new \DateTime())*/;
        $table->addColumn('updated_at', 'datetime', ['default' => (new \DateTime())->format('Y-m-d H:m:s')])/*->setDefault(new \DateTime())*/;
        // this up() migration is auto-generated, please modify it to your needs
//        $this->addSql('CREATE TABLE `user`
//(id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL,
// name VARCHAR(255) NOT NULL,
//  age INT NOT NULL, sex VARCHAR(255) NOT NULL,
//   birthday DATETIME NOT NULL,
//    phone VARCHAR(255) NOT NULL,
//     created_at DATETIME NOT NULL,
//      updated_at DATETIME NOT NULL,
//       PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable('users');
//        $this->addSql('DROP TABLE `user`');
    }
}
