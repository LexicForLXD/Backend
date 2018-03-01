<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\DeleteUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use AppBundle\Entity\User;

class DeleteUserCommandTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $command;


    public function setUp()
    {

        $kernel = static::createKernel();
        $kernel->boot();


        $application = new Application($kernel);


        $this->em = $kernel->getContainer()->get('doctrine')->getManager();

        $application->add(new DeleteUserCommand('app:delete-user', $this->em));
        $this->command = $application->find('app:delete-user');
    }


    public function testDeleteValid()
    {

        $commandTester = new CommandTester($this->command);

        $user = new User();
        $user->setFirstName('FirstNameCommand');
        $user->setLastName('LastNameCommand');
        $user->setEmail('email@command.de');
        $user->setUsername('commandUsername');
        $user->setPassword('password');

        $this->em->persist($user);
        $this->em->flush();

        $commandTester->setInputs(array(
            'commandUsername',
            'y'
        ));

        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('Der Benutzer wurde erfolgreich gelöscht', $output);



    }

    public function testDeleteNotFound()
    {

        $user = new User();
        $user->setFirstName('FirstNameCommand');
        $user->setLastName('LastNameCommand');
        $user->setEmail('email@command.de');
        $user->setUsername('commandUsername');
        $user->setPassword('password');

        $this->em->persist($user);
        $this->em->flush();

        $commandTester = new CommandTester($this->command);

        $commandTester->setInputs(array(
            'command1Username',
            'commandUsername',
            'y'
        ));

        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('Dieser Benutzernamen existiert nicht in der Datenbank.', $output);

        $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => 'commandUsername']);

    }

    public function testDeleteNo()
    {
        $user = new User();
        $user->setFirstName('FirstNameCommand');
        $user->setLastName('LastNameCommand');
        $user->setEmail('email@command.de');
        $user->setUsername('commandUsername');
        $user->setPassword('password');

        $this->em->persist($user);
        $this->em->flush();


        $commandTester = new CommandTester($this->command);

        $commandTester->setInputs(array(
            'commandUsername',
            'n'
        ));

        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertContains('Es wird kein Benutzer gelöscht', $output);

        $this->em->remove($user);
        $this->em->flush();
    }
}