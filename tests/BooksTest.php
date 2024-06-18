<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Book;
use App\Factory\BookFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BooksTest extends ApiTestCase {
    use Factories, ResetDatabase;

    public function testGetCollection():void
    {
        BookFactory::createMany(100);

        $response = static::createClient()->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/api/books?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/books?page=1',
                'hydra:last' => '/api/books?page=4',
                'hydra:next' => '/api/books?page=2',
            ],
        ]);
        
        $this->assertCount(30, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Book::class);

    }

    public function testCreateBook()
    {
        $response = static::createClient()->request('POST', 'api/books', ['json' => [
            'isbn'=> '9796372953365',
            'title'=> 'Тут заголовок',
            'description'=> 'Тут описание',
            'author'=> 'Тут автор',
            'publicationDate'=> '2024-6-17'
        ]]);
        // dump($response);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context'=> '/api/contexts/Book',
            '@type'=> 'Book',
            'isbn'=> '9796372953365',
            'title'=> 'Тут заголовок',
            'description'=> 'Тут описание',
            'author'=> 'Тут автор',
            'publicationDate'=> '2024-06-17T00:00:00+00:00',
            'reviews'=> []
        ]);
        $this->assertMatchesRegularExpression('~^/api/books/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Book::class);
    }

    public function testCreateInvalidBook(): void
    {
        $response = static::createClient()->request('POST', '/api/books', ['json' => [
            'isbn' => 'invalid',
        ]]);
        // dump($response);
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        
        $this->assertJsonContains([
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description'=> "isbn: This value is neither a valid ISBN-10 nor a valid ISBN-13.\ntitle: This value should not be blank.\ndescription: This value should not be blank.\nauthor: This value should not be blank.\npublicationDate: This value should not be null.",
        ]);
    }

    public function testUpdateBook():void
    {
        BookFactory::createOne([
            'isbn' => '9796372953365',
        ]);
        $client = static::createClient();
        $iri = $this->findIriBy(Book::class, ['isbn' => '9796372953365']);
        $client->request('PATCH', $iri, [   
            'json' => [
                'title' => 'updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]           
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'isbn' => '9796372953365',
            'title' => 'updated title',
        ]);
    }

    public function testDeleteBook():void
    {
        BookFactory::createOne(['isbn' => '9796372953365']);
        $client = static::createClient();
        $iri = $this->findIriBy(Book::class, ['isbn' => '9796372953365']);
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Book::class)->findOneBy(['isbn' => '9796372953365'])
        );
    }

}