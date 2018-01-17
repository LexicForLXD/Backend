<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;
use Symfony\Component\Validator\Constraints\DateTime;
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
     * Negative test for getStatusCheckContainer() - No StatusCheck configured for Container
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetStatusCheckContainerUnknownStatusCheck(){
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);

        $container = new Container();
        $container->setName("testContainerStatusCheck".mt_rand());
        $container->setIpv4("192.168.178.21");
        $container->setState('stopped');

        $host->addContainer($container);
        $this->em->persist($container);
        $this->em->persist($host);
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
        $this->assertEquals('{"error":{"code":404,"message":"No StatusCheck for Container with ID '.$container->getId().' found"}}', $client->getResponse()->getContent());

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Negative test for getStatusCheckContainer() - No Container found
     */
    public function testGetStatusCheckContainerUnknownContainer(){
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
     * Positive test for getStatusCheckContainer()
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetStatusCheckContainer(){
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);

        $container = new Container();
        $container->setName("testContainerStatusCheck".mt_rand());
        $container->setIpv4("192.168.178.22");
        $container->setState('stopped');

        $containerStatus = new ContainerStatus();
        $containerStatus->setHealthCheckEnabled(true);
        $containerStatus->setHealthCheck(true);

        $date = new \DateTime('NOW');
        $date->format("Y-m-d\TH:i:sP"); //W3C format

        $containerStatus->setLastSuccessfullPing($date);
        $containerStatus->setLastRtt(18);
        $this->em->persist($containerStatus);

        $container->setStatus($containerStatus);

        $host->addContainer($container);
        $this->em->persist($container);
        $this->em->persist($host);
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
        $statusObject = json_decode($client->getResponse()->getContent());

        $this->assertEquals($containerStatus->getId(), $statusObject->id);
        $this->assertEquals(true, $statusObject->healthCheck);
        $this->assertEquals(true, $statusObject->healthCheckEnabled);
        $compareDate = new \DateTime($statusObject->lastSuccessfullPing);
        $compareDate->format("Y-m-d\TH:i:sP");
        //TODO Date compare
        //$this->assertContains(''.$date, ''.$compareDate);
        $this->assertEquals(18, $statusObject->lastRtt);

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $containerStatus = $this->em->getRepository(ContainerStatus::class)->find($containerStatus->getId());
        $this->em->remove($containerStatus);
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Negative test for configureStatusCheckForContainer() - Unknown Container
     */
    public function testConfigureStatusCheckForContainerUnknownContainer(){
        $client = static::createClient();

        $client->request(
            'PUT',
            '/monitoring/checks/containers/999999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),
            '{
                        "healthCheckEnabled" : false
                    }'
        );
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No Container for ID 999999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Positive test for configureStatusCheckForContainer() - No ContainerStatus - set to false
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testConfigureStatusCheckForContainerNoContainerStatus(){
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);

        $container = new Container();
        $container->setName("testContainerStatusCheck".mt_rand());
        $container->setIpv4("192.168.178.21");
        $container->setState('stopped');

        $host->addContainer($container);
        $this->em->persist($container);
        $this->em->persist($host);
        $this->em->flush();

        $client->request(
            'PUT',
            '/monitoring/checks/containers/'.$container->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),'{
                   "healthCheckEnabled" : false
                }'
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $statusObject = json_decode($client->getResponse()->getContent());
        $this->assertEquals(false, $statusObject->healthCheckEnabled);

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $containerStatus = $this->em->getRepository(ContainerStatus::class)->find($statusObject->id);
        $this->em->remove($containerStatus);
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Positive test for configureStatusCheckForContainer() - set to true
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testConfigureStatusCheckForContainer(){
        $client = static::createClient();

        $host = new Host();
        $host->setName("Test-Host1".mt_rand());
        $host->setDomainName("test.".mt_rand().".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $this->em->persist($host);

        $container = new Container();
        $container->setName("testContainerStatusCheck".mt_rand());
        $container->setIpv4("192.168.178.22");
        $container->setState('stopped');

        $containerStatus = new ContainerStatus();
        $containerStatus->setHealthCheckEnabled(false);
        $this->em->persist($containerStatus);

        $container->setStatus($containerStatus);

        $host->addContainer($container);
        $this->em->persist($container);
        $this->em->persist($host);
        $this->em->flush();

        $client->request(
            'PUT',
            '/monitoring/checks/containers/'.$container->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),'{
                   "healthCheckEnabled" : true
                }'
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $statusObject = json_decode($client->getResponse()->getContent());

        $this->assertEquals(true, $statusObject->healthCheckEnabled);

        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $containerStatus = $this->em->getRepository(ContainerStatus::class)->find($containerStatus->getId());
        $this->em->remove($containerStatus);
        $this->em->remove($container);
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
