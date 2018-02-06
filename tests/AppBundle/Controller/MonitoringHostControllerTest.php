<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\HostStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;

class MonitoringHostControllerTest extends WebTestCase
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
     * Negative test for getStatusChecksHost() - No Host with id found
     */
    public function testGetStatusChecksHostNoHost()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/monitoring/checks/hosts/999999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No Host for ID 999999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Negative test for getStatusChecksHost() - No HostStatus for Host with id found
     * @throws \Doctrine\ORM\ORMException
     */
    public function testGetStatusChecksHostNoHostStatuses()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host-monitor-17".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);
        $this->em->flush();

        $client->request(
            'GET',
            '/monitoring/checks/hosts/'.$host->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No HostStatuses for Host with ID '.$host->getId().' found"}}', $client->getResponse()->getContent());

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Positive test for getStatusChecksHost()
     * @throws \Doctrine\ORM\ORMException
     */
    public function testGetStatusChecksHost()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $hostStatus = new HostStatus();
        $hostStatus->setNagiosEnabled(true);
        $hostStatus->setNagiosName("myNagiosTestDevice");
        $hostStatus->setCheckName("http");
        $hostStatus->setSourceNumber(0);
        $hostStatus->setNagiosUrl("nagios.example.com");

        $host->addStatus($hostStatus);

        $this->em->persist($host);
        $this->em->persist($hostStatus);
        $this->em->flush();

        $client->request(
            'GET',
            '/monitoring/checks/hosts/'.$host->getId(),
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

        $this->assertEquals($hostStatus->isNagiosEnabled(), $object->nagiosEnabled);
        $this->assertEquals($hostStatus->getNagiosName(), $object->nagiosName);
        $this->assertEquals($hostStatus->getCheckName(), $object->checkName);
        $this->assertEquals($hostStatus->getSourceNumber(), $object->sourceNumber);
        $this->assertEquals($hostStatus->getNagiosUrl(), $object->nagiosUrl);

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $hostStatus = $this->em->getRepository(HostStatus::class)->find($hostStatus->getId());
        $this->em->remove($host);
        $this->em->remove($hostStatus);
        $this->em->flush();
    }

    /**
     * Negative test for createStatusCheckForHost() - No Host with id found
     */
    public function testCreateStatusCheckForHostNoHost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/monitoring/checks/hosts/99999',
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
        $this->assertEquals('{"error":{"code":404,"message":"No Host for ID 99999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Negative test for createStatusCheckForHost() - Validation failed - sourceNumber should be int
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateStatusCheckForHostValidationFailed()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);
        $this->em->flush();


        $client->request(
            'POST',
            '/monitoring/checks/hosts/'.$host->getId(),
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
        $this->assertEquals('{"error":{"code":400,"message":{"sourceNumber":"This value should be of type int."}}}', $client->getResponse()->getContent());

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Positive test for createStatusCheckForHost()
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateStatusCheckForHost()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);
        $this->em->flush();


        $client->request(
            'POST',
            '/monitoring/checks/hosts/'.$host->getId(),
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

        $hostStatus = $this->em->getRepository(HostStatus::class)->find($object->id);
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($hostStatus);
        $this->em->flush();
    }

    /**
     * Negative test for configureStatusCheckForHost() - No HostStatus with id found
     */
    public function testConfigureStatusCheckForHostNoHostStatus()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/monitoring/checks/9999999/hosts',
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
        $this->assertEquals('{"error":{"code":404,"message":"No HostStatus for ID 9999999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Negative test for configureStatusCheckForHost() - Validation failed - sourceNumber should be int
     * @throws \Doctrine\ORM\ORMException
     */
    public function testConfigureStatusCheckForHostValidationFailed()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $hostStatus = new HostStatus();
        $hostStatus->setNagiosEnabled(true);
        $hostStatus->setNagiosName("myNagiosTestDevice123465798");
        $hostStatus->setCheckName("http");
        $hostStatus->setSourceNumber(0);
        $hostStatus->setNagiosUrl("nagios.example.com");

        $host->addStatus($hostStatus);

        $this->em->persist($host);
        $this->em->persist($hostStatus);
        $this->em->flush();


        $client->request(
            'PUT',
            '/monitoring/checks/'.$hostStatus->getId().'/hosts',
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
        $this->assertEquals('{"error":{"code":400,"message":{"sourceNumber":"This value should be of type int."}}}', $client->getResponse()->getContent());

        $hostStatus = $this->em->getRepository(HostStatus::class)->find($hostStatus->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($hostStatus);
        $this->em->flush();
    }

    /**
     * Positive test for configureStatusCheckForHost()
     * @throws \Doctrine\ORM\ORMException
     */
    public function testConfigureStatusCheckForHost()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $hostStatus = new HostStatus();
        $hostStatus->setNagiosEnabled(true);
        $hostStatus->setNagiosName("myNagiosTestDevice123465798");
        $hostStatus->setCheckName("http");
        $hostStatus->setSourceNumber(0);
        $hostStatus->setNagiosUrl("nagios.example.com");

        $host->addStatus($hostStatus);

        $this->em->persist($host);
        $this->em->persist($hostStatus);
        $this->em->flush();


        $client->request(
            'PUT',
            '/monitoring/checks/'.$hostStatus->getId().'/hosts',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),'{
                          "nagiosEnabled": false,
                          "nagiosName": "my-nagios-device_newTest",
                          "nagiosUrl": "https://nagios2.example.com",
                          "checkName" : "check_http2",
                          "sourceNumber" : 1
                        }'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $object = json_decode($client->getResponse()->getContent());

        $this->assertEquals($hostStatus->getId(), $object->id);
        $this->assertEquals(false, $object->nagiosEnabled);
        $this->assertEquals("my-nagios-device_newTest", $object->nagiosName);
        $this->assertEquals("https://nagios2.example.com", $object->nagiosUrl);
        $this->assertEquals("check_http2", $object->checkName);
        $this->assertEquals(1, $object->sourceNumber);

        $hostStatus = $this->em->getRepository(HostStatus::class)->find($hostStatus->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->remove($hostStatus);
        $this->em->flush();
    }

    /**
     * Negative test for deleteHostStatus() - No HostStatuses for id found
     */
    public function testDeleteContainerStatusNoHostStatus()
    {
        $client = static::createClient();


        $client->request(
            'DELETE',
            '/monitoring/checks/9999999/hosts',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No HostStatus for ID 9999999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Positive test for deleteHostStatus()
     * @throws \Doctrine\ORM\ORMException
     */
    public function testDeleteHostStatus()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Hosst-monitor-1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $hostStatus = new HostStatus();
        $hostStatus->setNagiosEnabled(true);
        $hostStatus->setNagiosName("myNagiosTestDevice".mt_rand());
        $hostStatus->setCheckName("http");
        $hostStatus->setSourceNumber(0);
        $hostStatus->setNagiosUrl("nagios.example.com");

        $host->addStatus($hostStatus);

        $this->em->persist($host);
        $this->em->persist($hostStatus);
        $this->em->flush();

        $client->request(
            'DELETE',
            '/monitoring/checks/'.$hostStatus->getId().'/hosts',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
        $hostStatus = $this->em->getRepository(HostStatus::class)->find($hostStatus->getId());
        $this->assertEquals(false, !$hostStatus);

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
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
