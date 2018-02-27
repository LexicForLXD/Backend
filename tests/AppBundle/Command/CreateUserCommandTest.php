<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\CreateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new CreateUserCommand());

        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName()
        ));
        $commandTester->setInputs(array(
            'FirstNameCommand',
            'LastNameCommand',
            'email@command.de',
            'password',
            'password'
        ));


        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('User erfolgreich erzeugt', $output);

        // ...
    }
}