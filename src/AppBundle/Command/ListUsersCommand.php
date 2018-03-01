<?php

namespace AppBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;

class ListUsersCommand extends Command
{

    private $em;

    public function __construct(?string $name = null, EntityManagerInterface $em) {
        parent::__construct($name);

        $this->em = $em;
    }

    protected function configure()
    {
        $this
        ->setName('app:list-users')
        ->setDescription('Lists all users.')
        ->setHelp('This command allows you to list all users.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $users = $this->em->getRepository(User::class)->findAll();

        if($users == null)
        {
            $output->writeln("No users found.");
        }

        $ouputArray = array();

        foreach($users as $user) {
            $ouputArray[] = array(
                $user->getFirstName() . ' ' . $user->getLastName(),
                $user->getEmail(),
                $user->getUsername(),
                '$user->getRoles()'
            );
        };

        $table = new Table($output);
        $table
            ->setHeaders(array('Name', 'Email', 'Username', 'Role'))
            ->setRows(
                $ouputArray
            );
        $table->render();
    }
}