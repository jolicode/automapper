<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Filesystem\Filesystem;

if (!class_exists(ApiTestCase::class)) {
    class ApiPlatformTest extends \PHPUnit\Framework\TestCase
    {
        protected static function createClient(): void
        {
            self::markTestSkipped('API Platform is not installed.');
        }
    }

    return;
}

class ApiPlatformTest extends ApiTestCase
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
        $response = static::createClient()->request('GET', '/shelf');

        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            '@context' => '/contexts/Shelf',
            '@id' => '/shelf',
            '@type' => 'Shelf',
            'books' => ["/books/1", "/books/2"],
        ]);
    }

    public function testGetBookCollectionOnApip(): void
    {
        $response = static::createClient()->request('GET', '/books.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

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
        static::createClient()->request('GET', '/books/1.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

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
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Book',
            '@type' => 'Book',
            'title' => 'The Handmaid\'s Tale',
            'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.',
            'author' => 'Margaret Atwood',
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

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }
}
