<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\VarDumper\VarDumper;

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
     * Positive test for getAllProfiles()
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetAllProfiles()
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

        $profile = $this->em->getRepository(Profile::class)->find($profile->getId());
        $this->em->remove($profile);
        $this->em->flush();
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
            '/profiles/'.$profile->getId(),
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

        $profile = $this->em->getRepository(Profile::class)->find($profile->getId());
        $this->em->remove($profile);
        $this->em->flush();
    }

    /**
     * Negative test for deleteProfile($profileId) - unknown profileId
     */
    public function testDeleteProfileNoProfile()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
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
     * Positive test for deleteProfile($profileId) with no links to hosts or containers
     */
    public function testDeleteProfile()
    {
        $profile = new Profile();
        $profile->setName("testProfileDelete".mt_rand());
        $profile->setDescription("testDescription");
        $profile->setDevices(array("kvm" => (array("type" => "unix-char"))));
        $profile->setConfig(array("limits.memory" => "2GB"));

        $this->em->persist($profile);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'DELETE',
            '/profiles/'.$profile->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    /**
     * Negative test for deleteProfile($profileId) with linked container and host - container is the problem
     */
    public function testDeleteProfileLinkedToContainer()
    {
        $profile = new Profile();
        $profile->setName("testProfileDelete".mt_rand());
        $profile->setDescription("testDescription");
        $profile->setDevices(array("kvm" => (array("type" => "unix-char"))));
        $profile->setConfig(array("limits.memory" => "2GB"));

        $host = new Host();
        $host->setName("Test-Host1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainer");
        $container->setHost($host);
        $container->setIpv4("192.168.178.20");
        $container->setState('stopped');

        $profile->addHost($host);
        $profile->addContainer($container);

        $this->em->persist($profile);
        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'DELETE',
            '/profiles/'.$profile->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $profile = $this->em->getRepository(Profile::class)->find($profile->getId());
        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($profile);
        $this->em->remove($container);
        $this->em->flush();
    }

    //TODO use self signed test cert
//    /**
//     * Negative test for deleteProfile($profileId) with linked host - the host is offline
//     */
//    public function testDeleteProfileHostOffline()
//    {
//        $profile = new Profile();
//        $profile->setName("testProfileDelete".mt_rand());
//        $profile->setDescription("testDescription");
//        $profile->setDevices(array("kvm" => (array("type" => "unix-char"))));
//        $profile->setConfig(array("limits.memory" => "2GB"));
//
//        $host = new Host();
//        $host->setName("Test-Host".mt_rand());
//        $host->setDomainName("test.".mt_rand().".de");
//        $host->setPort(8443);
//        $host->setSettings("settings");
//
//        $profile->addHost($host);
//
//        $this->em->persist($profile);
//        $this->em->persist($host);
//        $this->em->flush();
//
//        $client = static::createClient();
//
//        $client->request(
//            'DELETE',
//            '/profiles/'.$profile->getId(),
//            array(),
//            array(),
//            array(
//                'CONTENT_TYPE' => 'application/json',
//                'HTTP_Authorization' => $this->token
//            )
//        );
//
//        VarDumper::dump($client->getResponse()->getContent());
//        $this->assertEquals(503, $client->getResponse()->getStatusCode());
//
//        $profile = $this->em->getRepository(Profile::class)->find($profile->getId());
//        $host = $this->em->getRepository(Host::class)->find($host->getId());
//        $this->em->remove($host);
//        $this->em->remove($profile);
//        $this->em->flush();
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
