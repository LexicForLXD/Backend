<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\CreateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use AppBundle\Entity\User;

class CreateUserCommandTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    private $encoder;
    private $command;


    public function setUp()
    {

        $kernel = static::createKernel();
        $kernel->boot();


        $application = new Application($kernel);


        $this->em = $kernel->getContainer()->get('doctrine')->getManager();
        $this->encoder = $kernel->getContainer()->get('security.password_encoder');

        $application->add(new CreateUserCommand('app:create-user', $this->em, $this->encoder));
        $this->command = $application->find('app:create-user');
    }


    public function testCreationValid()
    {

        $commandTester = new CommandTester($this->command);

        $commandTester->setInputs(array(
            'FirstNameCommand',
            'LastNameCommand',
            'email@command.de',
            'commandUsername',
            'password',
            'password'
        ));

        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('User erfolgreich erzeugt', $output);

        $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => 'commandUsername']);

        $this->em->remove($userDB);
        $this->em->flush();

    }

    public function testCreationInvalidPassword()
    {

        $commandTester = new CommandTester($this->command);

        $commandTester->setInputs(array(
            'FirstNameCommand',
            'LastNameCommand',
            'email@command.de',
            'commandUsername',
            'password',
            'password1',
            'password',
            'password1'
        ));

        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('Die Passwörter stimmen nicht überein.', $output);

        $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => 'commandUsername']);

    }

    public function testCreationAlreadyExisting()
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
            'FirstNameCommand',
            'LastNameCommand',
            'email@command.de',
            'email1@command.de',
            'commandUsername',
            'command1Username',
            'password',
            'password'
        ));

        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertContains('Der Benutzername ist nicht eindeutig.', $output);
        $this->assertContains('Die email ist nicht eindeutig.', $output);


        $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => 'command1Username']);

        $this->em->remove($userDB);
        $this->em->remove($user);
        $this->em->flush();
    }
}