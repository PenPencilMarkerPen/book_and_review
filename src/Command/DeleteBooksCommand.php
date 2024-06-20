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
            $output->writeln("Удалено $countDeleteBooks книг!");
            return Command::SUCCESS;
        
        return Command::FAILURE;
    }


    private function delete()
    {
        $conn = $this->entityManagerInterface->getConnection();

        $sql ='
            DELETE FROM book
            WHERE age(current_date, publication_date) > interval \'7 days\' and counter = 0;
        ';
        
        $resultSet = $conn->executeQuery($sql);
        
        $countDeleteBooks = count($resultSet->fetchAllAssociative());
        
        return $countDeleteBooks;
    }
}