<?php

namespace App\Command;

use App\Entity\Book;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(name: 'app:delete-books')]
class DeleteBooksCommand extends Command{
    
    protected static $defaultDescription = 'Delete books.';

    private $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface= $entityManagerInterface;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command allows you to delete books...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Delete Books',
            '============',
            '',
        ]);
        
        $countDeleteBooks = $this->delete();

        if ($countDeleteBooks)
        {
            $output->writeln("Удалено $countDeleteBooks книг!");
            return Command::SUCCESS;
        }        
        return Command::FAILURE;
    }


    private function delete(int $counter = 0)
    {

        $counterDays = new \DateTime();
        $counterDays->modify('-7 days');
        $counterDays = $counterDays->format('Y-m-d H:i:s');

        $books = $this->entityManagerInterface->createQueryBuilder()
            ->delete(Book::class, 'b')
            ->where('b.counter = :counter')
            ->andWhere('b.publicationDate < :counterDays')
            ->setParameter('counter', $counter )
            ->setParameter('counterDays', $counterDays);


        $resultSet = $books->getQuery()->getResult();

        return $resultSet;
    }
}