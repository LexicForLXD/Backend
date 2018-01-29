<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
        $this->assertEquals([ 0 => null], $profileFromDB->getContainerId());
        $this->assertEquals([ 0 => null], $profileFromDB->getHostId());

        $this->em->remove($profileFromDB);
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
