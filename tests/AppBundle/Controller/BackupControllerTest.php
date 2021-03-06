<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\BackupDestination;

class BackupControllerTest extends WebTestCase
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
     * Negative test for getAllBackups()
     * @throws \Exception
     */
    public function testGetAllBackupsNoBackups()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/backups',
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
     * Positive test for getAllBackups()
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function testGetAllBackups()
    {
        $backup = new Backup();

        $backup->setTimestamp();

        $this->em->persist($backup);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'GET',
            '/backups',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $jsonArray = json_decode($client->getResponse()->getContent());

        $object = $jsonArray[0];

        $this->assertEquals($backup->getId(), $object->id);
        $this->assertEquals(date_format($backup->getTimestamp(), DATE_ISO8601), date_format(new \DateTime($object->timestamp), DATE_ISO8601));

        $backup = $this->em->getRepository(Backup::class)->find($backup->getId());
        $this->em->remove($backup);
        $this->em->flush();
    }

    /**
     * Negative test for getBackupById($backupId)
     * @throws \Exception
     */
    public function testGetBackupByIdNoBackup()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/backups/999',
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
     * Positive test for getBackupById($backupId)
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function testGetSingleProfile()
    {
        $backup = new Backup();

        $backup->setTimestamp();

        $this->em->persist($backup);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'GET',
            '/backups/' . $backup->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $object = json_decode($client->getResponse()->getContent());

        $this->assertEquals($backup->getId(), $object->id);
        $this->assertEquals(date_format($backup->getTimestamp(), DATE_ISO8601), date_format(new \DateTime($object->timestamp), DATE_ISO8601));

        $backup = $this->em->getRepository(Backup::class)->find($backup->getId());
        $this->em->remove($backup);
        $this->em->flush();
    }

    /**
     * Positive test for backupCreationWebhook()
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function testBackupCreationWebhook()
    {
        $host = new Host();
        $host->setName("Test-Host1111" . mt_rand());
        $host->setDomainName("test." . mt_rand() . ".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testContainerBackupWebhook" . mt_rand());
        $container->setHost($host);
        $container->setState('stopped');
        $container->setEphemeral(false);
        $container->setArchitecture('x86_64');
        $container->setConfig([]);
        $container->setDevices([]);

        $backupDestination = new BackupDestination();
        $backupDestination->setName("testBackupDestWebhook" . mt_rand());
        $backupDestination->setDescription("Desc");
        $backupDestination->setHostname("test.local");
        $backupDestination->setProtocol("scp");
        $backupDestination->setPath("/var/backup");
        $backupDestination->setUsername("backupuser");

        $backupSchedule = new BackupSchedule();
        $backupSchedule->setExecutionTime("daily");
        $backupSchedule->setName("TestBackupPlan" . mt_rand());
        $backupSchedule->setType("full");
        $backupSchedule->setDestination($backupDestination);
        $backupSchedule->setWebhookUrl("testWebhookUrl");
        $backupSchedule->addContainer($container);

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->persist($backupSchedule);
        $this->em->persist($backupDestination);
        $this->em->flush();

        $client = static::createClient();

        //No OAuth2 authentication required
        $client->request(
            'POST',
            'webhooks/backups?token=' . $backupSchedule->getToken(),
            array(),
            array(),
            array()
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $object = json_decode($client->getResponse()->getContent());

        $this->assertContains('containerId', $client->getResponse()->getContent());
        $this->assertContains('timestamp', $client->getResponse()->getContent());
        $this->assertContains('id', $client->getResponse()->getContent());

        $backup = $this->em->getRepository(Backup::class)->find($object->id);
        $backupSchedule = $this->em->getRepository(BackupSchedule::class)->find($backupSchedule->getId());
        $backupDestination = $this->em->getRepository(BackupDestination::class)->find($backupDestination->getId());
        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());

        $this->em->remove($backup);
        $this->em->remove($backupSchedule);
        $this->em->remove($backupDestination);
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }

    /**
     * Negative test for deleteBackupEntry() - no Backup for id
     * @throws \Exception
     */
    public function testDeleteBackupEntryNoBackupFound()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/backups/99999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"error":{"code":404,"message":"No Backup for id 99999 found"}}', $client->getResponse()->getContent());
    }

    /**
     * Positive test for deleteBackupEntry()
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function testDeleteBackupEntry()
    {
        $backup = new Backup();

        $backup->setTimestamp();

        $this->em->persist($backup);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'DELETE',
            '/backups/' . $backup->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
        $this->assertEquals('', $client->getResponse()->getContent());

        $backup = $this->em->getRepository(Backup::class)->find($backup->getId());
        $this->assertNull($backup);
    }

    public function testBackupFromSchedule()
    {
        $host = new Host();
        $host->setName("testBackupFromSchedule" . mt_rand());
        $host->setDomainName("test." . mt_rand() . ".de");
        $host->setPort(8443);
        $host->setSettings("settings");

        $container = new Container();
        $container->setName("testBackupFromSchedule" . mt_rand());
        $container->setHost($host);
        $container->setState('stopped');
        $container->setEphemeral(false);
        $container->setArchitecture('x86_64');
        $container->setConfig([]);
        $container->setDevices([]);

        $backupDestination = new BackupDestination();
        $backupDestination->setName("testBackupFromSchedule" . mt_rand());
        $backupDestination->setDescription("Desc");
        $backupDestination->setHostname("test.local");
        $backupDestination->setProtocol("scp");
        $backupDestination->setPath("/var/backup");
        $backupDestination->setUsername("backupuser");

        $backupSchedule = new BackupSchedule();
        $backupSchedule->setExecutionTime("daily");
        $backupSchedule->setName("TestBackupFromSchedule" . mt_rand());
        $backupSchedule->setType("full");
        $backupSchedule->setDestination($backupDestination);
        $backupSchedule->setWebhookUrl("testWebhookUrl");
        $backupSchedule->addContainer($container);

        $this->em->persist($host);
        $this->em->persist($container);
        $this->em->persist($backupSchedule);
        $this->em->persist($backupDestination);
        $this->em->flush();

        $backup = new Backup();
        $backup->setBackupSchedule($backupSchedule);
        $backup->setTimestamp();
        $backup->addContainer($container);
        $backup->setDestination($backupDestination);
        $backup->setManualBackupName("backupFromScheduleTest");
        $this->em->persist($backup);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'GET',
            '/schedules/' . $backupSchedule->getId() . '/backups',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $jsonArray = json_decode($client->getResponse()->getContent());

        $object = $jsonArray[0];

        $this->assertEquals($backup->getId(), $object->id);
        $this->assertEquals(date_format($backup->getTimestamp(), DATE_ISO8601), date_format(new \DateTime($object->timestamp), DATE_ISO8601));

        $backup = $this->em->getRepository(Backup::class)->find($object->id);
        $backupSchedule = $this->em->getRepository(BackupSchedule::class)->find($backupSchedule->getId());
        $backupDestination = $this->em->getRepository(BackupDestination::class)->find($backupDestination->getId());
        $container = $this->em->getRepository(Container::class)->find($container->getId());
        $host = $this->em->getRepository(Host::class)->find($host->getId());

        $this->em->remove($backup);
        $this->em->remove($backupSchedule);
        $this->em->remove($backupDestination);
        $this->em->remove($container);
        $this->em->remove($host);
        $this->em->flush();
    }

    public function testBackupFromScheduleNotFound()
    {

        $backupSchedule = new BackupSchedule();
        $backupSchedule->setExecutionTime("daily");
        $backupSchedule->setName("TestBackupFromScheduleNotFound" . mt_rand());
        $backupSchedule->setType("full");
        $backupSchedule->setWebhookUrl("testWebhookUrl");

        $this->em->persist($backupSchedule);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'GET',
            '/schedules/' . $backupSchedule->getId() . '/backups',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertTrue($client->getResponse()->isNotFound());

        $backupSchedule = $this->em->getRepository(BackupSchedule::class)->find($backupSchedule->getId());

        $this->em->remove($backupSchedule);
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
