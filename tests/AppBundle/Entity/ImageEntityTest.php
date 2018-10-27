<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Image;
use AppBundle\Entity\Container;
use AppBundle\Entity\ImageAlias;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;

class ImageEntityTest extends WebTestCase
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

    /**
     * Check all getter for minimal allowed attributes in Entity - only notNull attributes set
     * @throws \Doctrine\ORM\ORMException
     */
    public function testGetterMinimalAttributesSet()
    {
        $image = new Image();
        $image->setPublic(true);
        $image->setFinished(false);

        $this->em->persist($image);
        $this->em->flush();

        $imageFromDB = $this->em->getRepository(Image::class)->find($image->getId());


        $this->assertEquals($image->isPublic(), $imageFromDB->isPublic());
        $this->assertEquals($image->isFinished(), $imageFromDB->isFinished());
        $this->assertEquals(null, $imageFromDB->getFingerprint());
        $this->assertEquals(null, $imageFromDB->getArchitecture());
        $this->assertEquals(null, $imageFromDB->getSize());
        $this->assertEquals(null, $imageFromDB->getFilename());
        $this->assertEquals(null, $imageFromDB->getProperties());
        $this->assertEquals(null, $imageFromDB->getError());

        $this->em->remove($imageFromDB);
        $this->em->flush();
    }

    /**
     * Check if all setters allow wrong values to allow validation
     */
    public function testWrongAttributes()
    {
        $exception = false;
        try {
            $image = new Image();
            $image->setArchitecture(1);
            $image->setPublic("TRUE");
            $image->setFilename(2);
            $image->setProperties("P");
        } catch (Exception $e) {
            $exception = true;
        }

        $this->assertTrue(!$exception); // Should be false = no exception triggered
    }

    /**
     * Tests the function to add an ImageAlias to an Image - checks the relation
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testAddImageAlias()
    {
        $image = new Image();
        $image->setPublic(true);
        $image->setFinished(false);

        $imageAlias = new ImageAlias();
        $imageAlias->setName("TestAlias");
        $imageAlias->setDescription("TestDescription");

        $this->em->persist($imageAlias);
        $this->em->flush();

        $image->addAlias($imageAlias);

        $this->em->persist($image);
        $this->em->flush();

        $aliaseFromImage = $image->getAliases();
        $aliasFromImage = $aliaseFromImage->get(0);
        $this->assertEquals($imageAlias, $aliasFromImage);
        $this->assertEquals($image, $imageAlias->getImage());

        $imageFromDB = $this->em->getRepository(Image::class)->find($image->getId());
        $imageAlias = $this->em->getRepository(ImageAlias::class)->find($imageAlias->getId());
        $this->em->remove($imageAlias);
        $this->em->remove($imageFromDB);
        $this->em->flush();
    }

    public function testRemoveImageAlias()
    {
        $image = new Image();
        $image->setPublic(true);
        $image->setFinished(false);

        $imageAlias = new ImageAlias();
        $imageAlias->setName("TestAlias");
        $imageAlias->setDescription("TestDescription");

        $this->em->persist($imageAlias);
        $this->em->flush();

        $image->addAlias($imageAlias);

        $this->em->persist($image);
        $this->em->flush();

        $image->removeAlias($imageAlias);

        $this->em->persist($image);
        $this->em->flush();

        $this->assertEquals(0, $image->getAliases()->count());

        $imageFromDB = $this->em->getRepository(Image::class)->find($image->getId());
        $imageAlias = $this->em->getRepository(ImageAlias::class)->find($imageAlias->getId());
        $this->em->remove($imageAlias);
        $this->em->remove($imageFromDB);
        $this->em->flush();
    }

    public function testRemoveImageFromContainer()
    {
        $image = new Image();
        $image->setPublic(true);
        $image->setFinished(true);

        $this->em->persist($image);
        $this->em->flush();

        $container = new Container();
        $container->setName("ImageTestRemoveFromContainer");
        $container->setState("testing");
        $container->setArchitecture("x86_64");
        $container->setEphemeral(false);
        $container->setConfig([]);
        $container->setDevices([]);
        $container->setImage($image);

        $image->addContainer($container);
        $this->em->persist($container);
        $this->em->flush();

        $imageFromDB = $this->em->getRepository(Image::class)->find($image->getId());
        $containerFromDB = $this->em->getRepository(Container::class)->find($container->getId());
        $this->assertEquals([$containerFromDB->getId()], $imageFromDB->getContainerId());
        $this->assertEquals($image, $containerFromDB->getImage());

        $image->removeContainer($container);
        $this->em->flush();

        $imageFromDB = $this->em->getRepository(Image::class)->find($image->getId());
        $this->assertEquals([], $imageFromDB->getContainerId());
        $containerFromDB = $this->em->getRepository(Container::class)->find($container->getId());
        $this->assertEquals(null, $containerFromDB->getImage());

        $this->em->remove($containerFromDB);
        $this->em->remove($imageFromDB);
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
