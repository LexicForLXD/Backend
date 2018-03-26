<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;

class ProfileEntityTest extends WebTestCase
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
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function testGetterMinimalAttributesSet()
    {
        $profile = new Profile();
        $profile->setName("testProfileEntity".mt_rand());

        $this->em->persist($profile);
        $this->em->flush();

        $profileFromDB = $this->em->getRepository(Profile::class)->find($profile->getId());


        $this->assertEquals($profile->getName(), $profileFromDB->getName());
        $this->assertEquals(null, $profileFromDB->getDescription());
        $this->assertEquals(null, $profileFromDB->getConfig());
        $this->assertEquals(null, $profileFromDB->getDevices());
        $this->assertEquals(null, $profileFromDB->getContainerId());
        $this->assertEquals(null, $profileFromDB->getHostId());

        $this->em->remove($profileFromDB);
        $this->em->flush();
    }

    /**
     * Check if all setters allow wrong values to allow validation
     */
    public function testWrongAttributes()
    {
        $exception = false;
        try {
            $profile = new Profile();
            $profile->setName(1);
            $profile->setDescription(2);
            $profile->setConfig("array");
            $profile->setDevices("array");
        }catch (Exception $e){
            $exception = true;
        }

        $this->assertTrue(!$exception); // Should be false = no exception triggered
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
