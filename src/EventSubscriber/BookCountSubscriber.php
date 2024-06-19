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

    public function updCounter(RequestEvent $event)
    {
        $request = $event->getRequest();
        $entityManager = $this->doctrine->getManager();
        $routeParams = $request->attributes->get('_route_params');

        if (!$request->isMethod(Request::METHOD_GET) || !isset($routeParams['id']) )
            return;

        $bookId = $routeParams['id'];

        $book = $entityManager->getRepository(Book::class)->find($bookId);

        if ($book)
        {
            $book->setCounter($book->getCounter()+1);
            $entityManager->persist($book);
            $entityManager->flush();
        }

    }

}