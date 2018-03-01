<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\ListUsersCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use AppBundle\Entity\User;

class ListUsersCommandTest extends KernelTestCase
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

        $application->add(new ListUsersCommand('app:list-users', $this->em));
        $this->command = $application->find('app:list-users');
    }


    public function testListValid()
    {

        $commandTester = new CommandTester($this->command);



        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertContains('Name', $output);
        $this->assertContains('Email', $output);
        $this->assertContains('Username', $output);
        $this->assertContains('Role', $output);

        $this->assertContains('Max Mustermann', $output);
        $this->assertContains('test@test.de', $output);
        $this->assertContains('mmustermann', $output);

    }

    public function testListNotFound()
    {
        $userDB = $this->em->getRepository(User::class)->findOneBy(['username' => 'mmustermann']);

        $this->em->remove($userDB);

        $this->em->flush();

        $commandTester = new CommandTester($this->command);



        $commandTester->execute(array(
            'command'  => $this->command->getName()
        ));

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('Die Passwörter stimmen nicht überein.', $output);

        $user = new User();
        $user->setFirstName('Max');
        $user->setLastName('Mustermann');
        $user->setEmail('test@test.de');
        $user->setUsername('mmustermann');
        $user->setPassword($this->encoder->encodePassword($user, 'password'));

        $this->em->persist($user);
        $this->em->flush();

    }


}