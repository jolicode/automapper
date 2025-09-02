<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Doctrine\Entity\Book;
use AutoMapper\Tests\Doctrine\Entity\Foo;
use AutoMapper\Tests\Doctrine\Entity\Review;
use AutoMapper\Tests\Doctrine\Entity\User;
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

        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(objectManager: $this->entityManager);
    }

    private function buildDatabase()
    {
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

    public function testAutoMappingMultipleIdentifiers(): void
    {
        $book = new Book();
        $user = new User();
        $user->name = 'Foo';

        $this->entityManager->persist($book);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $review = new Review();
        $review->book = $book;
        $review->user = $user;
        $review->rating = 10;

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        $reviewArray = [];
        $reviewArray['rating'] = 5;
        $reviewArray['book'] = ['id' => $book->id];
        $reviewArray['user'] = ['id' => $user->id, 'name' => 'Bar'];

        $this->autoMapper->map($reviewArray, Review::class, [
            MapperContext::DEEP_TARGET_TO_POPULATE => true,
        ]);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $review = $this->entityManager->find(Review::class, [
            'book' => $book->id,
            'user' => $user->id,
        ]);

        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Bar', $review->user->name);
    }

    public function testDisabledProvider(): void
    {
        $foo = new Foo();
        $foo->foo = 'Initial';

        $this->entityManager->persist($foo);
        $this->entityManager->flush();

        $this->assertNotNull($foo->id);

        $bookArray = $this->autoMapper->map($foo, 'array');
        $bookArray['foo'] = 'John Doe';

        $this->autoMapper->map($bookArray, Foo::class);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $foo = $this->entityManager->find(Foo::class, $foo->id);

        $this->assertEquals('Initial', $foo->foo);
    }
}
