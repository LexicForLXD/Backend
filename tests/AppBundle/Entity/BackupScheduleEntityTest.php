<?php

namespace Tests\Appbundle\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\BackupDestination;
use AppBundle\Entity\Container;


class BackupScheduleEntityTest extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

    }

    public function testSetter()
    {
        $destination = new BackupDestination();
        $destination->setName('SchedSetTest' . mt_rand());
        $destination->setDescription('AllSetterDesc');
        $destination->setHostname('pc.local');
        $destination->setPath('home/test/backup');
        $destination->setProtocol('scp');
        $destination->setUsername('backup');
        $this->em->persist($destination);

        $container = new Container();
        $container->setName("SchedSetTest" . mt_rand());
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);

        $this->em->persist($container);
        $this->em->flush();


        $schedule = new BackupSchedule();
        $schedule->setName("SchedSetTest" . mt_rand());
        $schedule->setDescription("AllSetterDesc");
        $schedule->setExecutionTime("daily");
        $schedule->setDestination($destination);
        $schedule->setType("incremental");
        $schedule->setToken("tokentest");
        $schedule->setWebhookUrl("just an Url");
        $schedule->addContainer($container);
        $this->em->persist($schedule);




        $this->em->flush();

        $schedFromDB = $this->em->getRepository(BackupSchedule::class)->find($schedule->getId());


        $this->assertEquals($schedule->getName(), $schedFromDB->getName());
        $this->assertEquals("AllSetterDesc", $schedFromDB->getDescription());
        $this->assertEquals("daily", $schedFromDB->getExecutionTime());
        $this->assertEquals($destination, $schedFromDB->getDestination());
        $this->assertEquals("incremental", $schedFromDB->getType());
        $this->assertEquals("tokentest", $schedFromDB->getToken());
        $this->assertEquals("just an Url", $schedFromDB->getWebhookUrl());

        $this->em->remove($container);
        $this->em->remove($destination);
        $this->em->remove($schedFromDB);
        $this->em->flush();
    }

    public function testRemoveContainer()
    {
        $container = new Container();
        $container->setName("SchedSetTest" . mt_rand());
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);

        $this->em->persist($container);
        $this->em->flush();


        $schedule = new BackupSchedule();
        $schedule->setName('DestTest' . mt_rand());
        $schedule->setDescription('desc');
        $schedule->setExecutionTime('daily');
        $schedule->setType('full');
        $schedule->setWebhookUrl("testwebhook");
        $schedule->addContainer($container);

        $this->em->persist($schedule);

        $this->em->flush();

        $scheduleFromDB = $this->em->getRepository(BackupSchedule::class)->find($schedule->getId());

        $this->assertEquals([$container->getId()], $scheduleFromDB->getContainerId());

        $scheduleFromDB->removeContainer($container);
        $this->em->flush();

        $scheduleFromDB = $this->em->getRepository(BackupSchedule::class)->find($schedule->getId());

        $this->assertEquals([], $scheduleFromDB->getContainerId());

        $this->em->remove($contianer);
        $this->em->remove($scheduleFromDB);
        $this->em->flush();

    }



    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}