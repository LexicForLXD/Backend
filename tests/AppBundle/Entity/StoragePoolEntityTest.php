<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Host;
use AppBundle\Entity\StoragePool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StoragePoolEntityTest extends WebTestCase
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
     */
    public function testGetterMinimalAttributesSet()
    {
        $host = new Host();
        $host->setName("testGetterMinimalAttributesSetHost");
        $host->setDomainName("testGetterMinimalAttributesSetHost.de");
        $this->em->persist($host);

        $storagePool = new StoragePool();
        $storagePool->setName("testGetterMinimalAttributesSet");
        $storagePool->setDriver("dir");
        $storagePool->setHost($host);

        $this->em->persist($storagePool);
        $this->em->flush();

        $storagePoolFromDB = $this->em->getRepository(StoragePool::class)->find($storagePool->getId());


        $this->assertEquals($storagePool->getName(), $storagePoolFromDB->getName());
        $this->assertEquals($storagePool->getDriver(), $storagePoolFromDB->getDriver());
        $this->assertEquals($host, $storagePoolFromDB->getHost());
        $this->assertEquals(null, $storagePoolFromDB->getConfig());
        $this->assertEquals(["name" => "testGetterMinimalAttributesSet", "driver" => "dir", "config" => ""], $storagePoolFromDB->getData());

        $this->em->remove($storagePoolFromDB);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAllAttributes()
    {
        $host = new Host();
        $host->setName("testAllAttributesHost");
        $host->setDomainName("testAllAttributesHost.de");
        $this->em->persist($host);

        $storagePool = new StoragePool();
        $storagePool->setName("testAllAttributes");
        $storagePool->setDriver("dir");
        $storagePool->setConfig("{'size': '10GB'}");
        $storagePool->setHost($host);

        $this->em->persist($storagePool);
        $this->em->flush();

        $storagePoolFromDB = $this->em->getRepository(StoragePool::class)->find($storagePool->getId());


        $this->assertEquals($storagePool->getName(), $storagePoolFromDB->getName());
        $this->assertEquals($storagePool->getDriver(), $storagePoolFromDB->getDriver());
        $this->assertEquals($host, $storagePoolFromDB->getHost());
        $this->assertEquals("{'size': '10GB'}", $storagePoolFromDB->getConfig());
        $this->assertEquals(["name" => "testGetterMinimalAttributesSet", "driver" => "dir", "config" => ["size" => "10GB"]], $storagePoolFromDB->getData());

        $this->em->remove($storagePoolFromDB);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Check if all setters allow wrong values to allow validation
     */
//    public function testAllAttributes()
//    {
//        $exception = false;
//        try {
//            $ = new Image();
//            $image->setPublic("TRUE");
//            $image->setFilename(2);
//            $image->setProperties("P");
//        }catch (Exception $e){
//            $exception = true;
//        }
//
//        $this->assertTrue(!$exception); // Should be false = no exception triggered
//    }



    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}
