<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine;

use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Doctrine\Entity\Book;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class DoctrineTest extends AutoMapperTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDatabase();
    }

    private function buildDatabase() {
        // delete the database file
        if (file_exists(__DIR__ . '/db.sqlite')) {
            unlink(__DIR__ . '/db.sqlite');
        }

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/Entity'],
            isDevMode: true,
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        ], $config);

        $entityManager = new EntityManager($connection, $config);

        // Generate schema
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->entityManager = $entityManager;
    }

    public function testAutoMapping(): void
    {
        $book = new Book();

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $this->assertNotNull($book->id);

        $bookArray = $this->autoMapper->map($book, 'array');
        $bookArray['author'] = 'John Doe';

        $this->autoMapper->map($bookArray, Book::class);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $book = $this->entityManager->find(Book::class, $book->id);

        $this->assertEquals('John Doe', $book->author);
    }
}