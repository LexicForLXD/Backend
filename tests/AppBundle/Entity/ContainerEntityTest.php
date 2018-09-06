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
            ->getManager();

    }

    public function testSetterAllWithoutAssociations()
    {
        $container = new Container();
        $container->setName("Container_ContainerEntityTest");
        $container->setSettings("Settings");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);


        $this->em->persist($container);
        $this->em->flush();


        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());

        $this->assertEquals("Container_ContainerEntityTest", $containerFromDb->getName());
        $this->assertEquals("Settings", $containerFromDb->getSettings());
        $this->assertEquals("testing", $containerFromDb->getState());
        $this->assertEquals("x86_64", $containerFromDb->getArchitecture());

        $this->em->remove($containerFromDb);
        $this->em->flush();
    }



    public function testAddRemoveProfile()
    {
        $container = new Container();
        $container->setName("Container_ContainerEntityTest");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);

        $this->em->persist($container);

        $profile = new Profile();
        $profile->setName("Profile_ContainerEntityTest");
        $this->em->persist($container);
        $this->em->flush();


        $container->addProfile($profile);
        $this->em->persist($profile);
        $this->em->flush();
        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());
        $this->assertEquals($container->getProfiles(), $containerFromDb->getProfiles());


        $container->removeProfile($profile);
        $this->em->flush();
        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());
        $this->assertEquals($container->getProfiles(), $containerFromDb->getProfiles());

        $this->em->remove($containerFromDb);
        $this->em->remove($profile);
        $this->em->flush();

    }

    public function testSetGetHost()
    {
        $container = new Container();
        $container->setName("Container_ContainerEntityTest");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);

        $this->em->persist($container);

        $host = new Host();
        $host->setName("Host_ContainerEntityTest");
        $host->setIpv4("127.0.0.1");
        $this->em->persist($host);

        $this->em->flush();

        $container->setHost($host);
        $this->em->flush();

        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());

        $this->assertEquals($host, $containerFromDb->getHost());

        $this->em->remove($containerFromDb);
        $this->em->remove($host);
        $this->em->flush();

    }


    public function testSetGetImage()
    {
        $container = new Container();
        $container->setName("Container_ContainerEntityTest");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);

        $this->em->persist($container);


        $image = new Image();
        $image->setPublic(true);
        $image->setFileName("Dateiname_ContainerEntityTest");
        $image->setProperties(["help" => "test"]);
        $image->setFinished(true);
        $this->em->persist($image);

        $this->em->flush();

        $container->setImage($image);
        $this->em->flush();

        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());

        $this->assertEquals($image, $containerFromDb->getImage());

        $this->em->remove($containerFromDb);
        $this->em->remove($image);
        $this->em->flush();
    }


    public function testGetProfileNames()
    {
        $profile = new Profile();
        $profile->setName("Profile_ContainerEntityTest");
        $this->em->persist($profile);
        $this->em->flush();


        $container = new Container();
        $container->setName("Container_ContainerEntityTest");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);
        $container->addProfile($profile);

        $this->em->persist($container);


        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());


        $this->assertEquals(["Profile_ContainerEntityTest"], $containerFromDb->getProfileNames());

        $this->em->remove($containerFromDb);
        $this->em->remove($profile);
        $this->em->flush();
    }


    public function testGetProfileIds()
    {
        $profile = new Profile();
        $profile->setName("Profile_ContainerEntityTest");
        $this->em->persist($profile);
        $this->em->flush();


        $container = new Container();
        $container->setName("Container_ContainerEntityTest");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);
        $container->addProfile($profile);

        $this->em->persist($container);


        $containerFromDb = $this->em->getRepository(Container::class)->find($container->getId());


        $this->assertEquals([$profile->getId()], $containerFromDb->getProfileId());

        $this->em->remove($containerFromDb);
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
