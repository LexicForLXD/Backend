<?php

namespace AppBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateUserCommand extends Command
{

    private $em;
    private $encoder;

    public function __construct(?string $name = null, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder) {
        parent::__construct($name);

        $this->em = $em;
        $this->encoder = $encoder;
    }

    protected function configure()
    {
        $this
        ->setName('app:create-user')
        ->setDescription('Creates a new user.')
        ->setHelp('This command allows you to create a user...');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        // $encoder = $this->getContainer()->get('security.password_encoder');

        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);


        $questionFirstName = new Question('Bitte geben Sie den Vornamen an: ', 'Max');
        $questionLastName = new Question('Bitte geben Sie den Nachnamen an: ', 'Mustermann');
        $questionEmail = new Question('Bitte geben Sie die Email an: ', 'mustermann@example.de');
        $questionEmail->setValidator(function ($value) {
            $userDB = $this->em->getRepository(User::class)->findOneBy(['email' => $value]);
            if ($userDB){
                throw new \Exception('Die email ist nicht eindeutig.');
            }
            return $value;
        });
        $questionUsername = new Question('Bitte geben Sie den Benutzernamen an: ', 'mmustermann');
        $questionUsername->setValidator(function ($value) {
            $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => $value]);
            if ($userDB){
                throw new \Exception('Der Benutzername ist nicht eindeutig.');
            }
            return $value;
        });
        $questionPassword = new Question('Bitte geben Sie das Password an: ');
        $questionPassword->setHidden(true);
        $questionPassword->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('Das Passwort darf nicht leer sein.');
            }

            return $value;
        });
        $questionPasswordAgain = new Question('Bitte geben Sie das Passwort erneut an: ');
        $questionPasswordAgain->setHidden(true);
        $questionPasswordAgain->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('Das Password darf nicht leer sein.');
            }

            return $value;
        });


        $user = new User();
        $user->setFirstName($helper->ask($input, $output, $questionFirstName));
        $user->setLastName($helper->ask($input, $output, $questionLastName));
        $user->setEmail($helper->ask($input, $output, $questionEmail));
        $user->setUsername($helper->ask($input, $output, $questionUsername));
        $password = $helper->ask($input, $output, $questionPassword);
        $passwordAgain = $helper->ask($input, $output, $questionPasswordAgain);


        if ($password === $passwordAgain)
        {
            $user->setPassword($password);
        }else {
            $output->writeln('Die Passwörter stimmen nicht überein.');
            $password = $helper->ask($input, $output, $questionPassword);
            $passwordAgain = $helper->ask($input, $output, $questionPasswordAgain);
            if ($password === $passwordAgain)
            {
                $user->setPassword($password);
            }else {
                $output->writeln('Die Passwörter stimmen nicht überein.');
                $output->writeln('Bitte starten Sie den Command erneut.');
                return;
            }
        }

        $user->setPassword($this->encoder->encodePassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();
    }
}