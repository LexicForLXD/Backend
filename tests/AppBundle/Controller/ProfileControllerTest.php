<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
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

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

    }

    /**
     * Negative test for getAllProfiles()
     */
    public function testGetAllProfilesNoProfiles()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/profiles',
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
     * Negative test for getSingleProfile($profileId)
     */
    public function testGetSingleProfileNoProfiles()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/profiles/999',
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
     * Positive test for getSingleProfile($profileId)
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetSingleProfile()
    {
        $profile = new Profile();
        $profile->setName("testProfile");
        $profile->setDescription("testDescription");
        $profile->setDevices(array("kvm" => (array("type" => "unix-char"))));
        $profile->setConfig(array("limits.memory" => "2GB"));

        $this->em->persist($profile);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'GET',
            '/profiles/1',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("testProfile", $client->getResponse()->getContent());
        //TODO Add checks for all content
    }

    /**
     * Positive test for getAllProfiles()
     */
    public function testGetAllProfiles()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/profiles',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("testProfile", $client->getResponse()->getContent());
        //TODO Add checks for all content
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
