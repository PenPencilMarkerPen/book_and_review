<?php

namespace App\EventSubscriber;

use App\Entity\Book;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\Persistence\ManagerRegistry;

class BookCountSubscriber implements EventSubscriberInterface {

    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['updCounter', EventPriorities::POST_READ],
        ];
    }

    private function updQueryCounter(Book $book, $entityManager)
    {
        $book->setCounter($book->getCounter()+1);
        $entityManager->persist($book);
        $entityManager->flush();
    }

    public function updCounter(RequestEvent $event)
    {
        $request = $event->getRequest();
        $entityManager = $this->doctrine->getManager();
        $routeParams = $request->attributes->get('_route_params');
        $path = $request->getPathInfo();

        if (!$request->isMethod(Request::METHOD_GET) || !isset($routeParams['id']) || !preg_match('#^/api/books/\d+$#', $path) )
            return;

        $bookId = $routeParams['id'];

        $book = $entityManager->getRepository(Book::class)->find($bookId);

        if ($book instanceof Book)
        {
            $this->updQueryCounter($book, $entityManager);
        }
    }

    

}