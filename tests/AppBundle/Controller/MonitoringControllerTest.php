<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;
use Symfony\Component\VarDumper\VarDumper;

class MonitoringControllerTest extends WebTestCase
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
     * Negative test for getStatusChecksContainer() - No Container with id found
     */
    public function testGetStatusChecksContainerNoContainer()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/monitoring/checks/containers/999999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No Container for ID 999999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Negative test for getStatusChecksContainer() - No ContainerStatuses for Container with id found
     * @throws \Doctrine\ORM\ORMException
     */
    public function testGetStatusChecksContainerNoContainerStatuses()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerGetStatus3".mt_rand());
        $container->setHost($host);
        $container->setIpv4("192.168.178.120");
        $container->setState('stopped');

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->flush();

        $client->request(
            'GET',
            '/monitoring/checks/containers/'.$container->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No ContainerStatuses for Container with ID '.$container->getId().' found"}}', $client->getResponse()->getContent());

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($container);
        $this->em->flush();
    }

    /**
     * Positive test for getStatusChecksContainer()
     * @throws \Doctrine\ORM\ORMException
     */
    public function testGetStatusChecksContainer()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerGetStatus2".mt_rand());
        $container->setHost($host);
        $container->setIpv4("192.168.178.10");
        $container->setState('stopped');

        $containerStatus = new ContainerStatus();
        $containerStatus->setNagiosEnabled(true);
        $containerStatus->setNagiosName("myNagiosTestDevice");
        $containerStatus->setCheckName("http");
        $containerStatus->setSourceNumber(0);
        $containerStatus->setNagiosUrl("nagios.example.com");

        $container->addStatus($containerStatus);

        $this->em->persist($host);
        $this->em->persist($containerStatus);
        $this->em->persist($container);
        $this->em->flush();

        $client->request(
            'GET',
            '/monitoring/checks/containers/'.$container->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $array = json_decode($client->getResponse()->getContent());
        $object = $array[0];

        $this->assertEquals($containerStatus->isNagiosEnabled(), $object->nagiosEnabled);
        $this->assertEquals($containerStatus->getNagiosName(), $object->nagiosName);
        $this->assertEquals($containerStatus->getCheckName(), $object->checkName);
        $this->assertEquals($containerStatus->getSourceNumber(), $object->sourceNumber);
        $this->assertEquals($containerStatus->getNagiosUrl(), $object->nagiosUrl);

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $containerStatus = $this->em->getRepository(ContainerStatus::class)->find($containerStatus->getId());
        $this->em->remove($host);
        $this->em->remove($container);
        $this->em->remove($containerStatus);
        $this->em->flush();
    }

    /**
     * Negative test for createStatusCheckForContainer() - No Container with id found
     */
    public function testCreateStatusCheckForContainerNoContainer()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/monitoring/checks/containers/99999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),'{
                          "nagiosEnabled": true,
                          "nagiosName": "my-nagios-device",
                          "nagiosUrl": "https://nagios.example.com",
                          "checkName" : "check_http",
                          "sourceNumber" : 0
                        }'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No Container for ID 99999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Negative test for createStatusCheckForContainer() - Validation failed - sourceNumber should be int
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateStatusCheckForContainerValidationFailed()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerCreateStatus".mt_rand());
        $container->setHost($host);
        $container->setIpv4("192.168.178.10");
        $container->setState('stopped');

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->flush();


        $client->request(
            'POST',
            '/monitoring/checks/containers/'.$container->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),'{
                          "nagiosEnabled": true,
                          "nagiosName": "my-nagios-device",
                          "nagiosUrl": "https://nagios.example.com",
                          "checkName" : "check_http",
                          "sourceNumber" : "A"
                        }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"errors":{"sourceNumber":"This value should be of type int."}}', $client->getResponse()->getContent());

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($container);
        $this->em->flush();
    }

    /**
     * Positive test for createStatusCheckForContainer()
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateStatusCheckForContainer()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerCreateStatus".mt_rand());
        $container->setHost($host);
        $container->setIpv4("192.168.178.11");
        $container->setState('stopped');

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->flush();


        $client->request(
            'POST',
            '/monitoring/checks/containers/'.$container->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),'{
                          "nagiosEnabled": true,
                          "nagiosName": "my-nagios-device1254",
                          "nagiosUrl": "https://nagios.example.com",
                          "checkName" : "check_http",
                          "sourceNumber" : 0
                        }'
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $object = json_decode($client->getResponse()->getContent());

        $this->assertEquals(true, $object->nagiosEnabled);
        $this->assertEquals("my-nagios-device1254", $object->nagiosName);
        $this->assertEquals("https://nagios.example.com", $object->nagiosUrl);
        $this->assertEquals("check_http", $object->checkName);
        $this->assertEquals(0, $object->sourceNumber);

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $containerStatus = $this->em->getRepository(ContainerStatus::class)->find($object->id);
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($container);
        $this->em->remove($containerStatus);
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
