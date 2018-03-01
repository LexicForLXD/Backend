<?php

namespace AppBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Question\Question;


class DeleteUserCommand extends Command
{

    private $em;

    public function __construct(?string $name = null, EntityManagerInterface $em) {
        parent::__construct($name);

        $this->em = $em;
    }

    protected function configure()
    {
        $this
        ->setName('app:delete-user')
        ->setDescription('Delete one user.')
        ->setHelp('This command allows you to delete one user.');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $helper = $this->getHelper('question');

        $questionUsername = new Question('Bitte geben Sie den Benutzernamen des zu löschenden Users ein: ', 'mmustermann');

        $questionUsername->setValidator(function($value) {
            $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => $value]);
            if($userDB == null) {
                throw new \Exception('Dieser Benutzernamen existiert nicht in der Datenbank.');
            }
            return $userDB;
        });

        $user = $helper->ask($input, $output, $questionUsername);

        $questionConfirmation = new Question('Sind Sie sicher, dass Sie den Benutzer "'. $user->getUsername() . '" löschen wollen? (y/n)', 'n');

        $confirmation = $helper->ask($input, $output, $questionConfirmation);

        if($confirmation == 'y'){
            $this->em->remove($user);
            $this->em->flush();
            $output->writeln('Der Benutzer wurde erfolgreich gelöscht');
        } else {
            $output->writeln('Es wird kein Benutzer gelöscht');
        }


       ;
    }
}