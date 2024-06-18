<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Review;
use App\Factory\ReviewFactory;
use App\Factory\BookFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\lazy;
use Symfony\Component\HttpFoundation\Response;


class ReviewTest extends ApiTestCase {
    use Factories, ResetDatabase;

    public function testGetCollection():void
    {
        BookFactory::createMany(50);
        ReviewFactory::createMany(100);

        $response = static::createClient()->request('GET', '/api/reviews');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');


        $this->assertJsonContains([
            '@context'=> '/api/contexts/Review',
            '@id'=> '/api/reviews',
            '@type'=> 'hydra:Collection',
            'hydra:totalItems'=> 100,
            'hydra:view' =>  [
                '@id' => '/api/reviews?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/reviews?page=1',
                'hydra:last' => '/api/reviews?page=4',
                'hydra:next' => '/api/reviews?page=2',
              ]
        ]);


        $this->assertCount(30, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Review::class);

    }

    public function testCreateReview():void
    {
        BookFactory::createOne();
        $response = static::createClient()->request('POST', '/api/reviews', ['json' =>[ 
            'book'=> '/api/books/1',
            'rating'=> 5,
            'body'=> 'Interesting book!',
            'author'=> 'Kévin',
            'publicationDate'=> 'September 21, 2016'
        ]]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context'=> '/api/contexts/Review',
            '@id'=> '/api/reviews/1',
            '@type'=> 'Review',
            'id'=> 1,
            'rating'=> 5,
            'body'=> 'Interesting book!',
            'author'=> 'Kévin',
            'publicationDate'=> '2016-09-21T00:00:00+00:00',
            'book'=> '/api/books/1'
        ]);
        $this->assertMatchesRegularExpression('~^/api/reviews/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Review::class);
    }

    public function testCreateInvalidReviews():void
    {
        $response = static::createClient()->request('POST', '/api/reviews', ['json' => [
            'body' => 'invalid',
        ]]);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertJsonContains([
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => "author: This value should not be blank.\npublicationDate: This value should not be null.\nbook: This value should not be null.",
        ]);
    }

    public function testUpdateReview():void
    {
        BookFactory::createOne();
        ReviewFactory::createOne([
            'book'=>lazy(fn() => BookFactory::randomOrCreate())
        ]);

        $client = static::createClient();

        $iri = $this->findIriBy(Review::class, ['id' => '1']);
        $client->request('PATCH', $iri, [   
            'json' => [
                'body' => 'updated body',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]           
        ]);
        $this->assertResponseIsSuccessful();
        
        $this->assertJsonContains([
            '@id' => $iri,
            'body' => 'updated body',
        ]);
    }

    public function testDeleteReview()
    {
        BookFactory::createOne();
        ReviewFactory::createOne([
            'book'=>lazy(fn() => BookFactory::randomOrCreate())
        ]);
        $client = static::createClient();
        $iri = $this->findIriBy(Review::class, ['id' => '1']);
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Review::class)->findOneBy(['id' => '1'])
        );
    }
}