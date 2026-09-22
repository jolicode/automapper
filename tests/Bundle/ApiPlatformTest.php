<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\ResponseInterface;

// API Platform 5 moved the test case out of the Symfony bundle namespace and deprecated the old one
$baseApiTestCase = match (true) {
    class_exists(\ApiPlatform\Test\ApiTestCase::class) => \ApiPlatform\Test\ApiTestCase::class,
    class_exists(\ApiPlatform\Symfony\Bundle\Test\ApiTestCase::class) => \ApiPlatform\Symfony\Bundle\Test\ApiTestCase::class,
    default => null,
};

if (null === $baseApiTestCase) {
    class ApiPlatformTest extends \PHPUnit\Framework\TestCase
    {
        protected static function createClient(): void
        {
            self::markTestSkipped('API Platform is not installed.');
        }
    }

    return;
}

class_alias($baseApiTestCase, BaseApiTestCase::class);

class ApiPlatformTest extends BaseApiTestCase
{
    protected function setUp(): void
    {
        static::$class = null;
        static::$alwaysBootKernel = false;

        $_SERVER['KERNEL_DIR'] = __DIR__ . '/Resources/App';
        $_SERVER['KERNEL_CLASS'] = 'AutoMapper\Tests\Bundle\Resources\App\AppKernel';
        $_SERVER['APP_DEBUG'] = false;

        (new Filesystem())->remove(__DIR__ . '/Resources/var/cache/test');

        self::bootKernel();
    }

    public function testGetShelf(): void
    {
        // We cannot compare response here, as it is not the same on lower Symfony versions
        // $response = static::createClient()->request('GET', '/shelf');
        // $this->assertResponseIsSuccessful();
        // $contentOriginal = $response->toArray();

        $responseMapped = static::createClient()->request('GET', '/shelf-mapped');
        $this->assertResponseIsSuccessful();
        $contentMapped = $responseMapped->toArray();

        $this->assertEquals([
            '/books/1',
            '/books/2',
        ], $contentMapped['books']);
    }

    public function testGetShelfGroup(): void
    {
        $response = static::createClient()->request('GET', '/shelf-group');
        $this->assertResponseIsSuccessful();
        $contentOriginal = $response->toArray();

        $responseMapped = static::createClient()->request('GET', '/shelf-mapped-group');
        $this->assertResponseIsSuccessful();
        $contentMapped = $responseMapped->toArray();

        $this->assertEquals($contentOriginal['books'], $contentMapped['books']);
    }

    public function testGetBookCollectionOnApip(): void
    {
        $response = static::createClient()->request('GET', '/books.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertContentTypeSame($response, 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/Book',
            '@id' => '/books',
            '@type' => 'Collection',
            'totalItems' => 1,
        ]);

        $this->assertCount(1, $response->toArray()['member']);
        $this->assertArraySubset([
            '@type' => 'Book',
            '@id' => '/books/1',
            'reviews' => [],
        ], $response->toArray()['member'][0]);
    }

    public function testGetBook(): void
    {
        $response = static::createClient()->request('GET', '/books/1.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertContentTypeSame($response, 'application/ld+json');

        $this->assertJsonContains([
            '@context' => '/contexts/Book',
            '@type' => 'Book',
            '@id' => '/books/1',
            'reviews' => [],
        ]);
    }

    public function testCreateBook(): void
    {
        $response = static::createClient()->request('POST', '/books.jsonld', ['json' => [
            'isbn' => '0099740915',
            'title' => 'The Handmaid\'s Tale',
            'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.',
            'author' => 'Margaret Atwood',
            'publicationDate' => '1985-07-31T00:00:00+00:00',
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertContentTypeSame($response, 'application/ld+json');
        $this->assertJsonContains([
            '@context' => '/contexts/Book',
            '@type' => 'Book',
            'title' => 'The Handmaid\'s Tale',
            'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.',
            'publicationDate' => '1985-07-31T00:00:00+00:00',
            'reviews' => [],
        ]);
        $this->assertMatchesRegularExpression('~^/books/\d+$~', $response->toArray()['@id']);
    }

    public function testUpdateBook(): void
    {
        $client = static::createClient();
        $iri = '/books/1';

        // Use the PATCH method here to do a partial update
        $client->request('PATCH', $iri, [
            'json' => [
                'title' => 'updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'title' => 'updated title',
        ]);
    }

    public function testCreateWithIriRelationInPlainJson(): void
    {
        $response = static::createClient()->request('POST', '/reviews', [
            'json' => [
                'rating' => 5,
                'body' => 'A great book.',
                'author' => 'Someone',
                'book' => '/books/1',
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('/books/1', $response->toArray()['book']);
    }

    /**
     * API Platform 5 no longer appends "; charset=utf-8" to JSON based media types.
     */
    private function assertContentTypeSame(ResponseInterface $response, string $mimeType): void
    {
        $contentType = $response->getHeaders(false)['content-type'][0] ?? '';

        $this->assertSame($mimeType, explode(';', $contentType, 2)[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }
}
