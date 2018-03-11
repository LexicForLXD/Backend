<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\BackupSchedule;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
            ->getManager()
        ;

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
        $backup->setFilePath("/test/1234.test");

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
        $this->assertEquals($backup->getFilePath(), $object->filePath);

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
        $backup->setFilePath("/test/1234.test");

        $this->em->persist($backup);
        $this->em->flush();

        $client = static::createClient();

        $client->request(
            'GET',
            '/profiles/'.$backup->getId(),
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
        $this->assertEquals($backup->getFilePath(), $object->filePath);

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
        $backupSchedule = new BackupSchedule();
        $backupSchedule->setExecutionTime("daily");
        $backupSchedule->setName("TestBackupPlan");
        $backupSchedule->setType("full");
        $backupSchedule->setDestination("test://test");
        $backupSchedule->setToken("13sa4d6as5d6asd312");

        $this->em->persist($backupSchedule);
        $this->em->flush();

        $client = static::createClient();

        //No OAuth2 authentication required
        $client->request(
            'POST',
            '/backups?path=my/test/path&token='.$backupSchedule->getToken(),
            array(),
            array(),
            array()
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $jsonArray = json_decode($client->getResponse()->getContent());

        $object = $jsonArray[0];

        $backup = $this->em->getRepository(Backup::class)->find($object->id);

        $this->assertEquals("my/test/path", $backup->getFilePath());
        $this->assertEquals($backupSchedule, $backup->getBackupSchedule());

        $this->em->remove($backup);
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
