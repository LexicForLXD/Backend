<?php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\BackupDestination;

class BackupDestinationControllerTest extends WebTestCase
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
     * Negative test for index all backup destinations
     * @throws \Exception
     */
    public function testIndexBackupDestinationsNotFound()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/backupdestinations',
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
     * Positive test for index all backup destinations
     * @throws \Exception
     */
    public function testIndexBackupDestination()
    {
        $client = static::createClient();

        $dest = new BackupDestination();
        $dest->setName('testDestIndex' . mt_rand());
        $dest->setDescription('desc');
        $dest->setHostname('host.local');
        $dest->setPath('/path');
        $dest->setProtocol('file');

        $this->em->persist($dest);
        $this->em->flush();


        $client->request(
            'GET',
            '/backupdestinations',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains($dest->getName(), $client->getResponse()->getContent());

        $dest = $this->em->getRepository(BackupDestination::class)->find($dest->getId());
        $this->em->remove($dest);
        $this->em->flush();
    }


    /**
     * Positive test for create new backup destination
     * @throws \Exception
     */
    public function testStoreBackupDestinationValid()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/backupdestinations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "name": "BackupDestControllerStoreVaild",
	            "description": "desc",
	            "protocol": "ftp",
	            "hostname": "host.local",
	            "path": "/path"
            }'
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $json = json_decode($client->getResponse()->getContent());

        $dest = $this->em->getRepository(BackupDestination::class)->find($json->id);

        $this->assertEquals("BackupDestControllerStoreVaild", $dest->getName());
        $this->assertEquals("desc", $dest->getDescription());
        $this->assertEquals("ftp", $dest->getProtocol());
        $this->assertEquals("host.local", $dest->getHostname());
        $this->assertEquals("/path", $dest->getPath());

        $this->em->remove($dest);
        $this->em->flush();
    }

    /**
     * Negative test for create new backup destination with same name
     * @throws \Exception
     */
    public function testStoeBackupDestinationDuplicate()
    {
        $dest = new BackupDestination();
        $dest->setName("BackupDestControllerStoreDuplicate");
        $dest->setDescription("desc");
        $dest->setProtocol("ftp");
        $dest->setHostname("host.local");
        $dest->setPath("/path");

        $this->em->persist($dest);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'POST',
            '/backupdestinations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "name": "BackupDestControllerStoreDuplicate",
	            "description": "desc",
	            "protocol": "ftp",
	            "hostname": "host.local",
	            "path": "/path"
            }'
        );


        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains("name", $client->getResponse()->getContent());

        $dest = $this->em->getRepository(BackupDestination::class)->find($dest->getId());
        $this->em->remove($dest);
        $this->em->flush();
    }


    /**
     * Negative test for create new backup destination with wrong protocol
     * @throws \Exception
     */
    public function testStoreBackupDestinationWrongInput()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/backupdestinations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "name": "BackupDestControllerStoreVaild",
	            "description": "desc",
	            "protocol": "bla",
	            "hostname": "host.local",
	            "path": "/path"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $this->assertContains("protocol", $client->getResponse()->getContent());
    }


    public function testShowBackupDestinationNotFound()
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/backupdestinations/9999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShowBackupDestinationValid()
    {
        $dest = new BackupDestination();
        $dest->setName("BackupDestControllerShowValid");
        $dest->setDescription("desc");
        $dest->setProtocol("ftp");
        $dest->setHostname("host.local");
        $dest->setPath("/path");

        $this->em->persist($dest);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'GET',
            '/backupdestinations/' . $dest->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $json = json_decode($client->getResponse()->getContent());

        $dest = $this->em->getRepository(BackupDestination::class)->find($json->id);

        $this->assertEquals("BackupDestControllerShowValid", $dest->getName());
        $this->assertEquals("desc", $dest->getDescription());
        $this->assertEquals("ftp", $dest->getProtocol());
        $this->assertEquals("host.local", $dest->getHostname());
        $this->assertEquals("/path", $dest->getPath());

        $this->em->remove($dest);
        $this->em->flush();
    }


    public function testDeleteBackupDestinationNotFound()
    {
        $client = static::createClient();
        $client->request(
            'DELETE',
            '/backupdestinations/9999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }


    public function testDeleteBackupDestinationValid()
    {
        $dest = new BackupDestination();
        $dest->setName("BackupDestControllerDeleteValid");
        $dest->setDescription("desc");
        $dest->setProtocol("ftp");
        $dest->setHostname("host.local");
        $dest->setPath("/path");

        $this->em->persist($dest);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'DELETE',
            '/backupdestinations/' . $dest->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }


    public function testUpdateBackupDestinationNotFound()
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/backupdestinations/9999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }


    public function testUpdateBackupDestinationValid()
    {
        $dest = new BackupDestination();
        $dest->setName("BackupDestControllerUpdateValid");
        $dest->setDescription("desc");
        $dest->setProtocol("ftp");
        $dest->setHostname("host.local");
        $dest->setPath("/path");

        $this->em->persist($dest);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'PUT',
            '/backupdestinations/' . $dest->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "name": "BackupDestControllerUpdateValid1",
	            "description": "desc1",
	            "protocol": "scp",
	            "hostname": "host.local",
	            "path": "/path/again"
            }'
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());



        $dest = $this->em->getRepository(BackupDestination::class)->find($dest->getId());

        $this->assertEquals("BackupDestControllerUpdateValid1", $dest->getName());
        $this->assertEquals("desc1", $dest->getDescription());
        $this->assertEquals("scp", $dest->getProtocol());
        $this->assertEquals("host.local", $dest->getHostname());
        $this->assertEquals("/path/again", $dest->getPath());

        $this->em->remove($dest);
        $this->em->flush();
    }


    public function testUpdateBackupDestinationWrongInput()
    {
        $dest = new BackupDestination();
        $dest->setName("BackupDestControllerUpdateWrongInput");
        $dest->setDescription("desc");
        $dest->setProtocol("ftp");
        $dest->setHostname("host.local");
        $dest->setPath("/path");

        $this->em->persist($dest);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'PUT',
            '/backupdestinations/' . $dest->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "name": "BackupDestControllerUpdateWrongInput1",
	            "description": "desc1",
	            "protocol": "bla",
	            "hostname": "host.local",
	            "path": "/path/again"
            }'
        );


        $this->assertEquals(400, $client->getResponse()->getStatusCode());



        $dest = $this->em->getRepository(BackupDestination::class)->find($dest->getId());

        $this->assertContains("protocol", $client->getResponse()->getContent());

        $this->em->remove($dest);
        $this->em->flush();
    }



}