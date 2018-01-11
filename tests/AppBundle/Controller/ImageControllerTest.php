<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;
use Symfony\Component\VarDumper\VarDumper;

class ImageControllerTest extends WebTestCase
{

    protected $token;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/oauth/v2/token',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            '{
                        "grant_type": "password",
                        "client_id": "1_3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4",
                        "client_secret": "4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k",
                        "username": "mmustermann",
                        "password": "password"
                    }'
        );

        $result = json_decode($client->getResponse()->getContent());
        $this->token = 'Bearer ' . $result->access_token;

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Positive test for getAllImages()
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAllImages()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);

        $imageAlias = new ImageAlias();
        $imageAlias->setDescription("Test description");
        $imageAlias->setName("TEST-ALIAS");

        $this->em->persist($imageAlias);

        $image = new Image();
        $image->setFilename("TestName");
        $image->setProperties(['os' => 'alpine']);
        $image->setPublic(true);
        $image->setFinished(false);
        $image->setHost($host);
        $image->addAlias($imageAlias);

        $this->em->persist($image);
        $this->em->flush();

        $client->request(
            'GET',
            '/images',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $objectArray = json_decode($client->getResponse()->getContent());

        $object = $objectArray[0];
        $this->assertEquals($host->getId(), $object->hostId);
        $this->assertEquals($image->getId(), $object->id);
        $this->assertEquals(true, $object->public);
        $this->assertEquals("TestName", $object->filename);
        $this->assertEquals(false, $object->finished);

        //properties
        $this->assertEquals('alpine', $object->properties->os);

        //aliases
        $aliasObject = $object->aliases[0];
        $this->assertEquals($imageAlias->getId(), $aliasObject->id);
        $this->assertEquals("TEST-ALIAS", $aliasObject->name);
        $this->assertEquals("Test description", $aliasObject->description);

        $image = $this->em->getRepository(Image::class)->find($image->getId());
        $imageAlias = $this->em->getRepository(ImageAlias::class)->find($imageAlias->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($imageAlias);
        $this->em->remove($image);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Negative test for getAllImages()
     */
    public function testGetAllImagesNoImages()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/images',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetAllImagesOnHostNoImages()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/hosts/999999/images',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetSingleImageNoImages()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/images/9999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * Negative test for deleteImage() - No image with id found
     */
    public function testDeleteImageNotFound()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/images/9999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('{"error":{"code":404,"message":"No Image found for id 9999"}}', $client->getResponse()->getContent());
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
