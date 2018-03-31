<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\Container;
use AppBundle\Entity\BackupDestination;
use AppBundle\Entity\Host;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BackupScheduleControllerTest extends WebTestCase
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
            ->getManager();

    }

    /**
     * Negative test for index all BackupSchedules
     * @throws \Exception
     */
    public function testIndexBackupSchedulesNotFound()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/schedules',
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
     * Positive test for index all BackupSchedules
     *
     * @return void
     */
    public function testIndexBackupSchedules()
    {
        $schedule = new BackupSchedule();

        $schedule->setName('TestIndexBackupSchedules');
        $schedule->setDescription('desc');
        $schedule->setExecutionTime('daily');
        $schedule->setType('full');
        $schedule->setWebhookUrl('balbal');

        $host = new Host();
        $host->setName("Test-Host1111" . mt_rand());
        $host->setDomainName("test." . mt_rand() . ".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerBackupWebhook" . mt_rand());
        $container->setHost($host);
        $container->setIpv4("192.168.178.20");
        $container->setState('stopped');
        $container->setConfig([]);
        $container->setDevices([]);
        $container->setArchitecture('x86_64');

        $backupDestination = new BackupDestination();
        $backupDestination->setName("testBackupDestWebhook" . mt_rand());
        $backupDestination->setDescription("Desc");
        $backupDestination->setHostname("test.local");
        $backupDestination->setProtocol("scp");
        $backupDestination->setPath("/var/backup");
        $backupDestination->setUsername("backupuser");

        $schedule->setDestination($backupDestination);
        $schedule->addContainer($container);

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->persist($schedule);
        $this->em->persist($backupDestination);
        $this->em->flush();


        $client = static::createClient();

        $client->request(
            'GET',
            '/schedules',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains($schedule->getName(), $client->getResponse()->getContent());


        $backupSchedule = $this->em->getRepository(BackupSchedule::class)->find($schedule->getId());
        $backupDestination = $this->em->getRepository(BackupDestination::class)->find($backupDestination->getId());
        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());

        $this->em->remove($backupSchedule);
        $this->em->remove($backupDestination);
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }


    /**
     * Negative test for show one BackupSchedule
     * @throws \Exception
     */
    public function testShowBackupScheduleNotFound()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/schedules/9999',
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
     * Positive test for show one BackupSchedule
     *
     * @return void
     */
    public function testShowBackupSchedule()
    {
        $schedule = new BackupSchedule();

        $schedule->setName('TestIndexBackupSchedules');
        $schedule->setDescription('desc');
        $schedule->setExecutionTime('daily');
        $schedule->setType('full');
        $schedule->setWebhookUrl('balbal');

        $host = new Host();
        $host->setName("Test-Host1111" . mt_rand());
        $host->setDomainName("test." . mt_rand() . ".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerBackupWebhook" . mt_rand());
        $container->setHost($host);
        $container->setIpv4("192.168.178.20");
        $container->setState('stopped');
        $container->setConfig([]);
        $container->setDevices([]);
        $container->setArchitecture('x86_64');

        $backupDestination = new BackupDestination();
        $backupDestination->setName("testBackupDestWebhook" . mt_rand());
        $backupDestination->setDescription("Desc");
        $backupDestination->setHostname("test.local");
        $backupDestination->setProtocol("scp");
        $backupDestination->setPath("/var/backup");
        $backupDestination->setUsername("backupuser");

        $schedule->setDestination($backupDestination);
        $schedule->addContainer($container);

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->persist($schedule);
        $this->em->persist($backupDestination);
        $this->em->flush();


        $client = static::createClient();

        $client->request(
            'GET',
            '/schedules/' . $schedule->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains($schedule->getName(), $client->getResponse()->getContent());

        $backupSchedule = $this->em->getRepository(BackupSchedule::class)->find($schedule->getId());
        $backupDestination = $this->em->getRepository(BackupDestination::class)->find($backupDestination->getId());
        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());

        $this->em->remove($backupSchedule);
        $this->em->remove($backupDestination);
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }



    /**
     * Negative test for delete one BackupSchedule
     * @throws \Exception
     */
    // public function testDeleteBackupScheduleNotFound()
    // {
    //     $client = static::createClient();

    //     $client->request(
    //         'DELETE',
    //         '/schedules/9999',
    //         array(),
    //         array(),
    //         array(
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_Authorization' => $this->token
    //         )
    //     );


    //     $this->assertEquals(404, $client->getResponse()->getStatusCode());
    // }

    /**
     * Negative test for update one BackupSchedule
     * @throws \Exception
     */
    // public function testUpdateBackupScheduleNotFound()
    // {
    //     $client = static::createClient();

    //     $client->request(
    //         'PUT',
    //         '/schedules/9999',
    //         array(),
    //         array(),
    //         array(
    //             'CONTENT_TYPE' => 'application/json',
    //             'HTTP_Authorization' => $this->token
    //         )
    //     );


    //     $this->assertEquals(404, $client->getResponse()->getStatusCode());
    // }
}