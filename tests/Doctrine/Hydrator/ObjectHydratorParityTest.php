<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\AutoMapperEntityManager;
use AutoMapper\Doctrine\Hydrator\HydratorFactory;
use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Account;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\AccountSettings;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Address;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Author;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Book;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\BookFormat;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\BookStatus;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Car;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Edition;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Media;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Publisher;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Rating;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Review;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Tag;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Truck;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Vehicle;
use AutoMapper\Tests\Doctrine\Hydrator\Entity\Video;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Hydrates the same queries with the Doctrine hydrators and the generated hydrators, and compares the resulting
 * object graphs as well as the complete state of the unit of work.
 *
 * The entity persisters (find(), lazy collections, ...) belong to the wrapped entity manager, so they keep the
 * Doctrine hydrators: their scenarios only check that the result is the same.
 *
 * Runs on SQLite by default, other databases are given as DBAL DSNs separated by commas in the
 * HYDRATOR_TEST_DATABASES environment variable, e.g.
 * "pdo-sqlite:///:memory:,pdo-pgsql://postgres:test@127.0.0.1:55432/test,pdo-mysql://root:test@127.0.0.1:53306/test".
 */
final class ObjectHydratorParityTest extends TestCase
{
    private const NAMESPACE = 'AutoMapper\Tests\Doctrine\Hydrator\Entity\\';

    /** @var array<string, Connection> */
    private static array $connections = [];

    private Configuration $config;
    private Connection $connection;

    /**
     * @param array<string, mixed>                                   $hints
     * @param (\Closure(EntityManagerInterface): void)|null          $setup     runs before the query, on both entity managers
     * @param (\Closure(Query): void)|null                           $configure
     * @param (\Closure(EntityManagerInterface, ?Query): mixed)|null $execute   replaces $query->getResult()
     * @param array<string, bool>                                    $expected  per hydration mode, whether only generated hydrators (true) or only Doctrine ones (false) must be used
     */
    #[DataProvider('provideScenarios')]
    public function testParity(string $database, ?string $dql = null, ?\Closure $setup = null, array $hints = [], ?\Closure $configure = null, ?\Closure $execute = null, array $expected = [HydrationPlan::OBJECT => true]): void
    {
        $this->connect($database);
        $factory = new HydratorFactory();

        $doctrine = $this->hydrate(new EntityManager($this->connection, $this->config), $dql, $setup, $hints, $configure, $execute);
        $autoMapper = $this->hydrate(new AutoMapperEntityManager(new EntityManager($this->connection, $this->config), $factory), $dql, $setup, $hints, $configure, $execute);

        self::assertSame($doctrine, $autoMapper);

        $statistics = $factory->getStatistics();

        foreach ($expected as $mode => $generated) {
            $counts = $statistics[$mode] ?? ['generated' => 0, 'fallback' => 0];

            if ($generated) {
                self::assertTrue($counts['generated'] > 0 && 0 === $counts['fallback'], \sprintf('Only generated %s hydrators expected, got %s.', $mode, json_encode($counts)));
            } else {
                self::assertTrue(0 === $counts['generated'] && $counts['fallback'] > 0, \sprintf('Only Doctrine %s hydrators expected, got %s.', $mode, json_encode($counts)));
            }
        }
    }

    public static function provideScenarios(): iterable
    {
        $databases = explode(',', getenv('HYDRATOR_TEST_DATABASES') ?: 'pdo-sqlite:///:memory:');

        foreach ($databases as $database) {
            $url = parse_url($database) ?: [];
            $driver = ($url['scheme'] ?? 'unknown') . (isset($url['port']) ? ':' . $url['port'] : '');

            foreach (self::scenarios() as $name => $scenario) {
                yield $name . ' @ ' . $driver => ['database' => $database, ...$scenario];
            }
        }
    }

    /**
     * @return iterable<string, array<string, mixed>>
     */
    private static function scenarios(): iterable
    {
        $fetchJoin = 'SELECT a, b FROM App:Author a LEFT JOIN a.books b ORDER BY a.id, b.id';
        $nested = 'SELECT a, b, r FROM App:Author a LEFT JOIN a.books b LEFT JOIN b.reviews r ORDER BY a.id, b.id, r.id';
        // hydrations made by the entity persisters do not go through the generated hydrators
        $persisters = [];
        $iterate = static fn (EntityManagerInterface $em, ?Query $query) => iterator_to_array($query?->toIterable() ?? [], false);

        yield 'root only' => ['dql' => 'SELECT a FROM App:Author a ORDER BY a.id'];
        yield 'fetch join one-to-many' => ['dql' => $fetchJoin];
        yield 'nested fetch joins' => ['dql' => $nested];
        yield 'fetch join many-to-one' => ['dql' => 'SELECT b, a FROM App:Book b JOIN b.author a ORDER BY b.id'];
        yield 'many-to-many owning side' => ['dql' => 'SELECT b, t FROM App:Book b LEFT JOIN b.tags t ORDER BY b.id, t.id'];
        yield 'many-to-many inverse side' => ['dql' => 'SELECT t, b FROM App:Tag t LEFT JOIN t.books b ORDER BY t.id, b.id'];
        yield 'chain of to-one' => ['dql' => 'SELECT r, b, a FROM App:Review r JOIN r.book b JOIN b.author a ORDER BY r.id'];
        yield 'references' => ['dql' => 'SELECT b FROM App:Book b ORDER BY b.id'];
        yield 'same class in two aliases' => ['dql' => 'SELECT b, a, ab FROM App:Book b JOIN b.author a LEFT JOIN a.books ab ORDER BY b.id, ab.id'];
        yield 'empty result' => ['dql' => 'SELECT a, b FROM App:Author a LEFT JOIN a.books b WHERE a.id = 999'];

        yield 'already managed entities' => ['dql' => $nested, 'setup' => static function (EntityManagerInterface $em) {
            $em->find(Author::class, 1);
            $em->find(Book::class, 1);
        }];
        yield 'uninitialized proxies' => ['dql' => $nested, 'setup' => static function (EntityManagerInterface $em) {
            $em->getReference(Author::class, 1);
            $em->getReference(Book::class, 2);
        }];
        yield 'initialized collection of a managed entity' => ['dql' => $fetchJoin, 'setup' => static function (EntityManagerInterface $em) {
            $em->find(Author::class, 1)?->books->count();
        }];
        yield 'uninitialized collection of a managed entity' => ['dql' => $fetchJoin, 'setup' => static function (EntityManagerInterface $em) {
            $em->find(Author::class, 3);
        }];
        yield 'references already managed' => ['dql' => 'SELECT b FROM App:Book b ORDER BY b.id', 'setup' => static function (EntityManagerInterface $em) {
            $em->find(Publisher::class, 1);
            $em->find(Edition::class, ['isbn' => '111', 'year' => 2020]);
            $em->getReference(Author::class, 3);
        }];

        yield 'index by root' => ['dql' => 'SELECT a FROM App:Author a INDEX BY a.id ORDER BY a.id'];
        yield 'index by collection' => ['dql' => 'SELECT a, b FROM App:Author a LEFT JOIN a.books b INDEX BY b.id ORDER BY a.id, b.id'];
        yield 'read only' => ['dql' => $nested, 'hints' => [Query::HINT_READ_ONLY => true]];
        yield 'eager fetch mode set on the query' => ['dql' => 'SELECT b FROM App:Book b ORDER BY b.id', 'configure' => static function (Query $query) {
            $query->setFetchMode(Book::class, 'publisher', ClassMetadata::FETCH_EAGER);
        }, 'expected' => []];

        yield 'inverse one-to-one loaded by the unit of work' => ['dql' => 'SELECT acc FROM App:Account acc ORDER BY acc.id', 'expected' => []];
        yield 'fetch join inverse one-to-one' => ['dql' => 'SELECT acc, s FROM App:Account acc LEFT JOIN acc.settings s ORDER BY acc.id'];
        yield 'fetch join owning one-to-one' => ['dql' => 'SELECT s, acc FROM App:AccountSettings s JOIN s.account acc ORDER BY s.id'];

        yield 'changes after hydration' => ['dql' => $fetchJoin, 'execute' => static function (EntityManagerInterface $em, ?Query $query) {
            $result = $query?->getResult() ?? [];
            $result[0]->email = 'changed@example.com';
            $result[0]->books->removeElement($result[0]->books->first());
            $result[1]->books->add($em->getReference(Book::class, 3));

            return $result;
        }];

        // inheritance
        yield 'single table inheritance' => ['dql' => 'SELECT m FROM App:Media m ORDER BY m.id'];
        yield 'single table subclass' => ['dql' => 'SELECT v FROM App:Video v ORDER BY v.id'];
        yield 'joined inheritance' => ['dql' => 'SELECT v FROM App:Vehicle v ORDER BY v.id'];
        yield 'joined subclass' => ['dql' => 'SELECT t FROM App:Truck t ORDER BY t.id'];
        yield 'inheritance in a fetch joined collection' => ['dql' => 'SELECT a, v FROM App:Author a LEFT JOIN a.vehicles v ORDER BY a.id, v.id'];
        yield 'inheritance with a fetch joined to-one' => ['dql' => 'SELECT v, o FROM App:Vehicle v JOIN v.owner o ORDER BY v.id'];
        yield 'inheritance with managed entities' => ['dql' => 'SELECT v FROM App:Vehicle v ORDER BY v.id', 'setup' => static function (EntityManagerInterface $em) {
            $em->getReference(Car::class, 2);
            $em->find(Truck::class, 1);
        }];

        // composite identifiers
        yield 'association identifiers' => ['dql' => 'SELECT r FROM App:Rating r ORDER BY r.score'];
        yield 'association identifiers fetch joined' => ['dql' => 'SELECT r, a, b FROM App:Rating r JOIN r.author a JOIN r.book b ORDER BY r.score'];
        yield 'composite identifier' => ['dql' => 'SELECT e FROM App:Edition e ORDER BY e.year'];
        yield 'composite identifier with a fetch joined collection' => ['dql' => 'SELECT e, b FROM App:Edition e LEFT JOIN e.books b ORDER BY e.year, b.id'];
        yield 'composite identifier fetch joined as to-one' => ['dql' => 'SELECT b, e FROM App:Book b LEFT JOIN b.edition e ORDER BY b.id'];

        // entity persisters
        yield 'find' => ['execute' => static fn (EntityManagerInterface $em) => $em->find(Author::class, 1), 'expected' => $persisters];
        yield 'find a missing entity' => ['execute' => static fn (EntityManagerInterface $em) => $em->find(Author::class, 999), 'expected' => $persisters];
        yield 'findBy' => ['execute' => static fn (EntityManagerInterface $em) => $em->getRepository(Book::class)->findBy(['status' => BookStatus::Draft], ['id' => 'DESC']), 'expected' => $persisters];
        yield 'findAll with inheritance' => ['execute' => static fn (EntityManagerInterface $em) => [
            $em->getRepository(Vehicle::class)->findAll(),
            $em->getRepository(Media::class)->findAll(),
        ], 'expected' => $persisters];
        yield 'find with composite identifiers' => ['execute' => static fn (EntityManagerInterface $em) => [
            $em->find(Rating::class, ['author' => 1, 'book' => 1]),
            $em->find(Edition::class, ['isbn' => '222', 'year' => 2021]),
        ], 'expected' => $persisters];
        yield 'proxy initialization refreshes with the Doctrine hydrator' => ['execute' => static function (EntityManagerInterface $em) {
            $author = $em->getReference(Author::class, 2);
            $author?->getName();

            return $author;
        }, 'expected' => $persisters];
        yield 'lazy one-to-many collection' => ['execute' => static function (EntityManagerInterface $em) {
            $author = $em->find(Author::class, 1);
            $author?->books->toArray();
            $author?->vehicles->toArray();

            return $author;
        }, 'expected' => $persisters];
        yield 'lazy many-to-many collection' => ['execute' => static function (EntityManagerInterface $em) {
            $book = $em->find(Book::class, 1);
            $book?->tags->toArray();

            return $book;
        }, 'expected' => $persisters];

        // behaviors of the ObjectHydrator on unusual result sets
        yield 'existing collection without an element of the result set' => ['dql' => $nested, 'setup' => static function (EntityManagerInterface $em) {
            $author = $em->find(Author::class, 1);
            $author?->books->count();
            $em->detach($author?->books->first() ?: new \stdClass());
        }];
        yield 'rows without root entity' => ['execute' => static function (EntityManagerInterface $em) {
            $rsm = new ResultSetMappingBuilder($em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
            $rsm->addRootEntityFromClassMetadata(Author::class, 'a');
            $rsm->addJoinedEntityFromClassMetadata(Book::class, 'b', 'a', 'books');
            $sql = 'SELECT ' . $rsm->generateSelectClause() . ' FROM book b LEFT JOIN author a ON a.id = b.author_id ORDER BY b.id';

            return $em->createNativeQuery($sql, $rsm)->getResult();
        }];
        yield 'clear during the hydration' => ['dql' => 'SELECT acc FROM App:Account acc ORDER BY acc.id', 'setup' => static function (EntityManagerInterface $em) {
            $em->getEventManager()->addEventListener([Events::postLoad], new ClearOnFirstPostLoad($em, AccountSettings::class));
        }, 'expected' => []];

        // iteration
        yield 'toIterable' => ['dql' => 'SELECT b, a FROM App:Book b JOIN b.author a ORDER BY b.id', 'execute' => $iterate];
        yield 'toIterable with index by' => ['dql' => 'SELECT a FROM App:Author a INDEX BY a.id ORDER BY a.id', 'execute' => static fn (EntityManagerInterface $em, ?Query $query) => iterator_to_array($query?->toIterable() ?? [], true)];
        yield 'toIterable with inheritance' => ['dql' => 'SELECT v FROM App:Vehicle v ORDER BY v.id', 'execute' => $iterate];
        yield 'toIterable with composite identifiers' => ['dql' => 'SELECT r FROM App:Rating r ORDER BY r.score', 'execute' => $iterate];
        yield 'toIterable with the SimpleObjectHydrator' => ['dql' => 'SELECT a FROM App:Author a ORDER BY a.id', 'execute' => static fn (EntityManagerInterface $em, ?Query $query) => iterator_to_array($query?->toIterable([], AbstractQuery::HYDRATE_SIMPLEOBJECT) ?? [], false), 'expected' => [HydrationPlan::SIMPLE => true]];

        // still handled by Doctrine
        yield 'mixed result uses the ObjectHydrator' => ['dql' => 'SELECT a, a.id AS identifier FROM App:Author a ORDER BY a.id', 'expected' => [HydrationPlan::OBJECT => false]];
    }

    /**
     * @param array<string, mixed> $hints
     *
     * @return array<string, mixed>
     */
    private function hydrate(EntityManagerInterface $em, ?string $dql, ?\Closure $setup, array $hints, ?\Closure $configure, ?\Closure $execute): array
    {
        $setup?->__invoke($em);

        $query = null;

        if (null !== $dql) {
            $query = $em->createQuery(str_replace('App:', self::NAMESPACE, $dql));

            foreach ($hints as $name => $value) {
                $query->setHint($name, $value);
            }

            $configure?->__invoke($query);
        }

        $result = null !== $execute ? $execute($em, $query) : $query?->getResult();

        return (new UnitOfWorkDumper())->dump($em, $result);
    }

    private function connect(string $database): void
    {
        $this->config = ORMSetup::createAttributeMetadataConfig(paths: [__DIR__ . '/Entity'], isDevMode: true);
        $this->config->enableNativeLazyObjects(true);
        $this->config->setNamingStrategy(new UnderscoreNamingStrategy());

        $params = (new DsnParser(['pdo-sqlite' => 'pdo_sqlite', 'pdo-pgsql' => 'pdo_pgsql', 'pdo-mysql' => 'pdo_mysql']))->parse($database);

        // server connections are reused, SQLite in memory databases are recreated
        $this->connection = str_starts_with($database, 'pdo-sqlite')
            ? DriverManager::getConnection($params, $this->config)
            : self::$connections[$database] ??= DriverManager::getConnection($params, $this->config);

        $em = new EntityManager($this->connection, $this->config);
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $this->seed($em);
    }

    private function seed(EntityManagerInterface $em): void
    {
        $orange = new Publisher('Orange');
        $blue = new Publisher('Blue');

        $php = new Tag('php');
        $doctrine = new Tag('doctrine');
        $unused = new Tag('unused');

        $alice = new Author('Alice');
        $alice->email = 'alice@example.com';
        $alice->tags = ['fiction', 'poetry'];
        $alice->address = new Address('1 main street', 'Lille');

        $bob = new Author('Bob');

        $carol = new Author('Carol');
        $carol->active = false;
        $carol->setCreatedAt(new \DateTimeImmutable('2020-05-06 07:08:09'));

        $firstEdition = new Edition('111', 2020, 'First edition');
        $secondEdition = new Edition('222', 2021, 'Second edition');

        $first = new Book('First');
        $first->author = $alice;
        $first->publisher = $orange;
        $first->edition = $firstEdition;
        $first->price = '12.50';
        $first->status = BookStatus::Published;
        $first->format = BookFormat::Paperback;
        $first->publishedAt = new \DateTimeImmutable('2021-02-03');
        $first->tags->add($php);
        $first->tags->add($doctrine);

        $second = new Book('Second');
        $second->author = $alice;
        $second->tags->add($doctrine);

        $third = new Book('Third');
        $third->author = $carol;
        $third->publisher = $blue;
        $third->edition = $secondEdition;
        $third->format = BookFormat::Hardcover;

        $anonymous = new Book('Anonymous');

        $great = new Review(5);
        $great->book = $first;
        $great->comment = 'Great';

        $meh = new Review(3);
        $meh->book = $first;

        $silent = new Review(1);
        $silent->book = $third;

        $withSettings = new Account('with-settings');
        $settings = new AccountSettings($withSettings, 'dark');
        $withoutSettings = new Account('without-settings');

        $video = new Video('A video');
        $video->duration = 42;

        $truck = new Truck('Truck');
        $truck->owner = $alice;
        $truck->driver = $bob;
        $truck->payload = 12;

        $car = new Car('Car');
        $car->owner = $alice;

        $otherCar = new Car('Other car');
        $otherCar->owner = $carol;
        $otherCar->doors = 2;

        $entities = [
            $orange, $blue, $php, $doctrine, $unused, $alice, $bob, $carol, $firstEdition, $secondEdition,
            $first, $second, $third, $anonymous, $great, $meh, $silent, $withSettings, $settings, $withoutSettings,
            new Media('A media'), $video, $truck, $car, $otherCar,
            new Rating($alice, $first, 4), new Rating($bob, $first, 2), new Rating($carol, $third, 5),
        ];

        foreach ($entities as $entity) {
            $em->persist($entity);
        }

        $em->flush();
        $em->clear();
    }
}
