<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Image;
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
            ->getManager()
        ;

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
