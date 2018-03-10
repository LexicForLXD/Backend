<?php

namespace Tests\Appbundle\Entity;

use AppBundle\Entity\BackupDestination;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\BackupSchedule;


class BackupDestinationEntityTest extends WebTestCase
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

    public function testSetterwithoutBackupSchedule()
    {
        $destination = new BackupDestination();
        $destination->setName('DestTestAllSetter' . mt_rand());
        $destination->setDescription('AllSetterDesc');
        $destination->setHostname('pc.local');
        $destination->setPath('home/test/backup');
        $destination->setProtocol('scp');
        $destination->setUsername('backup');

        $this->em->persist($destination);
        $this->em->flush();

        $destFromDB = $this->em->getRepository(BackupDestination::class)->find($destination->getId());


        $this->assertEquals($destination->getName(), $destFromDB->getName());
        $this->assertEquals($destination->getDescription(), $destFromDB->getDescription());
        $this->assertEquals($destination->getHostname(), $destFromDB->getHostname());
        $this->assertEquals($destination->getPath(), $destFromDB->getPath());
        $this->assertEquals($destination->getProtocol(), $destFromDB->getProtocol());
        $this->assertEquals($destination->getUsername(), $destFromDB->getUsername());
        $this->assertEquals($destFromDB->getDestinationText(), 'scp://backup@pc.local/home/test/backup/');

        $this->em->remove($destFromDB);
        $this->em->flush();
    }

    public function testSetterwithBackupSchedule()
    {
        $destination = new BackupDestination();
        $destination->setName('DestTestAllSetter' . mt_rand());
        $destination->setDescription('AllSetterDesc');
        $destination->setHostname('pc.local');
        $destination->setPath('home/test/backup');
        $destination->setProtocol('scp');
        $destination->setUsername('backup');

        $this->em->persist($destination);

        $schedule = new BackupSchedule();
        $schedule->setName('DestTest' . mt_rand());
        $schedule->setDescription('desc');
        $schedule->setExecutionTime('daily');
        $schedule->setType('full');
        $schedule->setDestination($destination);

        $this->em->persist($schedule);

        $this->em->flush();

        $destFromDB = $this->em->getRepository(BackupDestination::class)->find($destination->getId());
        $scheduleFromDB = $this->em->getRepository(BackupSchedule::class)->find($schedule->getId());

        $this->assertEquals($destFromDB, $scheduleFromDB->getDestination());

        $this->em->remove($destFromDB);
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