<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle;

use ApiPlatform\Elasticsearch\Tests\Fixtures\Book;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ApiPlatformTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::$class = null;
        $_SERVER['KERNEL_DIR'] = __DIR__ . '/Resources/App';
        $_SERVER['KERNEL_CLASS'] = 'AutoMapper\Tests\Bundle\Resources\App\AppKernel';
        $_SERVER['APP_DEBUG'] = false;

        (new Filesystem())->remove(__DIR__ . '/Resources/var/cache/test');

        self::bootKernel();
    }

    public function testGetBookCollection(): void
    {
        $response = static::createClient()->request('GET', '/books.jsonld');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Book',
            '@id' => '/books',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);

        $this->assertCount(1, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Book::class);
    }

    public function testGetBook(): void
    {
        $response = static::createClient()->request('GET', '/books/1.jsonld');

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
}
