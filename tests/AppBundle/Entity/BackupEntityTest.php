<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Backup;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;

class BackupEntityTest extends WebTestCase
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
            ->getManager()
        ;

    }

    /**
     * Check all getter for minimal allowed attributes in Entity - only notNull attributes set
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function testGetterMinimalAttributesSet()
    {
        $backup = new Backup();

        $backup->setTimestamp();

        $this->em->persist($backup);
        $this->em->flush();

        $backupFromDB = $this->em->getRepository(Backup::class)->find($backup->getId());

        $this->assertEquals($backup->getTimestamp(), $backupFromDB->getTimestamp());
        $this->assertEquals($backup->getId(), $backupFromDB->getId());

        $this->em->remove($backupFromDB);
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
