<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine;

use AutoMapper\Doctrine\Hydrator\AutoMapperEntityManager;
use AutoMapper\Doctrine\Hydrator\HydratorFactory;
use Automapper\Bench\Doctrine\Dto\AuthorDto;
use Automapper\Bench\Doctrine\Dto\BookDto;
use Automapper\Bench\Doctrine\Dto\ReviewDto;
use Automapper\Bench\Doctrine\Entity\Author;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * SQLite database of authors, their books and the reviews of those books, and the entity managers reading it.
 */
final class DoctrineFixture
{
    public const BOOKS_PER_AUTHOR = 5;
    public const REVIEWS_PER_BOOK = 3;

    /** 3 levels fetch join: the flat result set is folded back into an object graph */
    public const DQL = 'SELECT a, b, r FROM ' . Author::class . ' a JOIN a.books b JOIN b.reviews r ORDER BY a.id, b.id, r.id';

    public const SINGLE_AUTHOR_DQL = 'SELECT a, b, r FROM ' . Author::class . ' a JOIN a.books b JOIN b.reviews r WHERE a.id = :id';

    /**
     * Seeds the database once per size, it is reused by every process of the benchmark.
     */
    public static function database(int $authors): string
    {
        $path = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'automapper_bench_doctrine_' . $authors . '.sqlite';

        if (file_exists($path)) {
            return $path;
        }

        $tmpPath = $path . '.' . getmypid();
        $em = self::entityManager($tmpPath);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

        $conn = $em->getConnection();
        $conn->beginTransaction();
        $bookId = $reviewId = 0;

        for ($authorId = 1; $authorId <= $authors; ++$authorId) {
            $conn->insert('author', ['id' => $authorId, 'name' => 'Author name ' . $authorId, 'email' => 'author' . $authorId . '@example.com', 'active' => $authorId % 3 ? 1 : 0, 'created_at' => '2024-01-01 10:00:00']);

            for ($b = 1; $b <= self::BOOKS_PER_AUTHOR; ++$b) {
                ++$bookId;
                $conn->insert('book', ['id' => $bookId, 'title' => 'A reasonably long book title ' . $bookId, 'isbn' => '978-3-16-148410-' . ($bookId % 10), 'pages' => 100 + $bookId % 500, 'price' => 9.99 + ($bookId % 40), 'published_at' => '2023-06-15 08:30:00', 'author_id' => $authorId]);

                for ($r = 1; $r <= self::REVIEWS_PER_BOOK; ++$r) {
                    ++$reviewId;
                    $conn->insert('review', ['id' => $reviewId, 'rating' => $reviewId % 5 + 1, 'comment' => 'This is a review comment which is of a fairly typical length for such content ' . $reviewId, 'created_at' => '2024-03-20 14:45:00', 'book_id' => $bookId]);
                }
            }
        }

        $conn->commit();
        $conn->close();
        rename($tmpPath, $path);

        return $path;
    }

    public static function entityManager(string $database, bool $autoMapper = false): EntityManagerInterface
    {
        $config = ORMSetup::createAttributeMetadataConfig(paths: [__DIR__ . '/Entity'], isDevMode: false);
        $config->enableNativeLazyObjects(true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => $database], $config);

        if ($autoMapper) {
            return new AutoMapperEntityManager(new EntityManager($connection, $config), new HydratorFactory(sys_get_temp_dir() . '/automapper_bench_doctrine_hydrators'));
        }

        return new EntityManager($connection, $config);
    }

    /**
     * SQL alias of each selected field, keyed by "<dql alias>_<field>", as a generated mapper would inline them.
     *
     * @return array<string, string>
     */
    public static function columns(EntityManagerInterface $em): array
    {
        $rsm = (new Parser($em->createQuery(self::DQL)))->parse()->getResultSetMapping();
        $columns = [];

        foreach ($rsm->fieldMappings as $column => $field) {
            $columns[$rsm->columnOwnerMap[$column] . '_' . $field] = (string) $column;
        }

        return $columns;
    }

    public static function sql(EntityManagerInterface $em): string
    {
        return $em->createQuery(self::DQL)->getSQL();
    }

    /**
     * Hand written hydration of the same result set into detached DTOs: the floor, without any unit of work.
     *
     * @param array<string, string> $c
     *
     * @return list<AuthorDto>
     */
    public static function handwrittenDto(Result $result, array $c): array
    {
        $authors = [];
        $books = [];
        $reviews = [];

        while ($row = $result->fetchAssociative()) {
            $authorId = $row[$c['a_id']];

            if (!isset($authors[$authorId])) {
                $author = new AuthorDto();
                $author->id = (int) $authorId;
                $author->name = $row[$c['a_name']];
                $author->email = $row[$c['a_email']];
                $author->active = (bool) $row[$c['a_active']];
                $author->createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row[$c['a_createdAt']]);
                $authors[$authorId] = $author;
            }

            $bookId = $row[$c['b_id']];

            if (!isset($books[$bookId])) {
                $book = new BookDto();
                $book->id = (int) $bookId;
                $book->title = $row[$c['b_title']];
                $book->isbn = $row[$c['b_isbn']];
                $book->pages = (int) $row[$c['b_pages']];
                $book->price = (float) $row[$c['b_price']];
                $book->publishedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row[$c['b_publishedAt']]);
                $books[$bookId] = $book;
                $authors[$authorId]->books[] = $book;
            }

            $reviewId = $row[$c['r_id']];

            if (!isset($reviews[$reviewId])) {
                $review = new ReviewDto();
                $review->id = (int) $reviewId;
                $review->rating = (int) $row[$c['r_rating']];
                $review->comment = $row[$c['r_comment']];
                $review->createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row[$c['r_createdAt']]);
                $reviews[$reviewId] = $review;
                $books[$bookId]->reviews[] = $review;
            }
        }

        return array_values($authors);
    }

    /**
     * Many small queries, like a request would run: the fixed cost per query matters more than the cost per row.
     *
     * @return list<Author>
     */
    public static function smallQueries(EntityManagerInterface $em, int $count): array
    {
        $result = [];

        for ($id = 1; $id <= $count; ++$id) {
            $result[] = $em->createQuery(self::SINGLE_AUTHOR_DQL)->setParameter('id', $id)->getSingleResult();
        }

        return $result;
    }

    /**
     * Lazy collections are loaded by the entity persisters, which also hydrate with HYDRATE_OBJECT.
     *
     * @return list<Author>
     */
    public static function lazyCollections(EntityManagerInterface $em, int $count): array
    {
        $authors = $em->createQuery('SELECT a FROM ' . Author::class . ' a ORDER BY a.id')->setMaxResults($count)->getResult();

        foreach ($authors as $author) {
            $author->books->toArray();
        }

        return $authors;
    }

    /**
     * Comparable shape of an author graph, entities or DTOs, without the back references.
     *
     * @param iterable<Author|AuthorDto|array<string, mixed>> $authors
     *
     * @return list<array<string, mixed>>
     */
    public static function canonicalize(iterable $authors): array
    {
        $result = [];

        foreach ($authors as $author) {
            $author = (object) $author;
            $books = [];

            foreach ($author->books as $book) {
                $book = (object) $book;
                $reviews = [];

                foreach ($book->reviews as $review) {
                    $review = (object) $review;
                    $reviews[] = [$review->id, $review->rating, $review->comment, $review->createdAt->format('c')];
                }

                $books[] = [$book->id, $book->title, $book->isbn, $book->pages, $book->price, $book->publishedAt->format('c'), $reviews];
            }

            $result[] = [$author->id, $author->name, $author->email, $author->active, $author->createdAt->format('c'), $books];
        }

        return $result;
    }

    /**
     * Every approach of the benchmark must produce the same graph.
     *
     * @return array<string, mixed>
     */
    public static function results(int $authors = 20): array
    {
        $database = self::database($authors);
        $orm = self::entityManager($database);
        $autoMapper = self::entityManager($database, true);

        return [
            'object_hydrator' => self::canonicalize($orm->createQuery(self::DQL)->getResult()),
            'automapper_hydrator' => self::canonicalize($autoMapper->createQuery(self::DQL)->getResult()),
            'array_hydrator' => self::canonicalize($orm->createQuery(self::DQL)->getArrayResult()),
            'handwritten_dto' => self::canonicalize(self::handwrittenDto($orm->getConnection()->executeQuery(self::sql($orm)), self::columns($orm))),
            'object_hydrator_small_queries' => self::canonicalize(self::smallQueries(self::entityManager($database), $authors)),
            'automapper_hydrator_small_queries' => self::canonicalize(self::smallQueries(self::entityManager($database, true), $authors)),
            'object_hydrator_lazy_collections' => self::canonicalize(self::lazyCollections(self::entityManager($database), $authors)),
            'automapper_hydrator_lazy_collections' => self::canonicalize(self::lazyCollections(self::entityManager($database, true), $authors)),
        ];
    }
}
