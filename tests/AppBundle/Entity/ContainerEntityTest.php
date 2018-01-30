<?php

namespace Tests\Appbundle\Entity;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Image;
use AppBundle\Entity\Profile;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class ContainerEntityTest extends WebTestCase
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

    public function testSetterAllWithoutAssociations()
    {
        $container = new Container();
        $container->setIpv4("123.123.123.123");
        $container->setIpv6("fe::1");
        $container->setDomainName("host.local");
        $container->setName("Container_ContainerEntityTest");
        $container->setSettings("Settings");
        $container->setState("testing");


        $this->em->persist($container);
        $this->em->flush();


        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());

        $this->assertEquals("Container_ContainerEntityTest", $containerFromDb->getName());
        $this->assertEquals("123.123.123.123", $containerFromDb->getIpv4());
        $this->assertEquals("fe::1", $containerFromDb->getIpv6());
        $this->assertEquals("Settings", $containerFromDb->getSettings());
        $this->assertEquals("testing", $containerFromDb->getState());

        $this->em->remove($containerFromDb);
        $this->em->flush();
    }


    public function testSetterWithAssociations()
    {
        $container = new Container();
        $container->setIpv4("123.123.123.123");
        $container->setName("Container_ContainerEntityTest");
        $container->setState("testing");


        $this->em->persist($container);
        $this->em->flush();

        $host = new Host();
        $host->setName("Host_ContainerEntityTest");
        $host->setIpv4("127.0.0.1");
        $this->em->persist($host);

        $image = new Image();
        $image->setPublic(true);
        $image->setFileName("Dateiname_ContainerEntityTest");
        $image->setProperties(["help" => "test"]);
        $image->setFinished(true);
        $this->em->persist($image);

        $profile = new Profile();
        $profile->setName("Profile_ContainerEntityTest");
        $this->em->persist($profile);

        $this->em->flush();

        $container->addProfile($profile);
        $container->setHost($host);
        $container->setImage($image);

        $this->em->flush();


        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());

        $this->assertEquals("Container_ContainerEntityTest", $containerFromDb->getName());
        $this->assertEquals("123.123.123.123", $containerFromDb->getIpv4());
        $this->assertEquals($host, $containerFromDb->getHost());
        $this->assertEquals($image, $containerFromDb->getImage());
        $this->assertEquals("testing", $containerFromDb->getState());

        $this->em->remove($containerFromDb);
        $this->em->remove($host);
        $this->em->remove($image);
        $this->em->remove($profile);
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
