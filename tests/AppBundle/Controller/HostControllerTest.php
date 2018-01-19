<?php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;

class HostControllerTest extends WebTestCase
{

    protected $token;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

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

    public function testIndexNoHosts()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/hosts',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testIndex()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setIpv4('192.168.10.1');
        $host->setName('testHost');
        $host->setMac('someMac');

        $this->em->persist($host);
        $this->em->flush();


        $client->request(
            'GET',
            '/hosts',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("testHost", $client->getResponse()->getContent());

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->flush();
    }

    public function testStoreValid()
    {

        $client = static::createClient();
        $client->request(
            'POST',
            '/hosts',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "192.168.1.21",
                "ipv6": "fe80::1",
                "domainName": "test1.local",
                "mac": "blabla1",
                "name": "c11",
                "settings": "sldkasdaldk1"
            }'
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $json = json_decode($client->getResponse()->getContent());

        $host = $this->em->getRepository(Host::class)->find($json->id);

        $this->assertEquals("192.168.1.21", $host->getIpv4());
        $this->assertEquals("fe80::1", $host->getIpv6());
        $this->assertEquals("test1.local", $host->getDomainName());
        $this->assertEquals("blabla1", $host->getMac());
        $this->assertEquals("c11", $host->getName());


        $this->em->remove($host);
        $this->em->flush();
    }


    public function testStoreDuplicate()
    {
        $host = new Host();
        $host->setIpv4('192.168.1.21');
        $host->setIpv6('fe80::1');
        $host->setDomainName('test.local');
        $host->setName('c11');
        $host->setMac('blabla1');

        $this->em->persist($host);
        $this->em->flush();


        $client = static::createClient();
        $client->request(
            'POST',
            '/hosts',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "192.168.1.21",
                "ipv6": "fe80::1",
                "domainName": "test.local",
                "mac": "blabla1",
                "name": "c11",
                "settings": "sldkasdaldk1"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());


        $this->assertContains("ipv4", $client->getResponse()->getContent());
        $this->assertContains("ipv6", $client->getResponse()->getContent());
        $this->assertContains("domainName", $client->getResponse()->getContent());
        $this->assertContains("mac", $client->getResponse()->getContent());
        $this->assertContains("name", $client->getResponse()->getContent());

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->flush();
    }

    public function testStoreWrongParameter()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/hosts',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "hallo",
                "ipv6": "wie",
                "domainName": "geht",
                "mac": "es",
                "name": "dir",
                "settings": "?"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());


        $this->assertContains("ipv4", $client->getResponse()->getContent());
        $this->assertContains("ipv6", $client->getResponse()->getContent());

    }

    public function testShowValid()
    {
        $client = static::createClient();

        $host = new Host();
        $host->setIpv4('192.168.10.1');
        $host->setName('testHost');
        $host->setMac('someMac');

        $this->em->persist($host);
        $this->em->flush();


        $client->request(
            'GET',
            '/hosts/'.$host->getId(),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $json = json_decode($client->getResponse()->getContent());

        $this->assertEquals($json->name, $host->getName());
        $this->assertEquals($json->ipv4, $host->getIpv4());
        $this->assertEquals($json->mac, $host->getMac());

        $this->em->remove($host);
        $this->em->flush();
    }


    public function testShowNotFound()
    {
        $client = static::createClient();


        $client->request(
            'GET',
            '/hosts/99',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testUpdateValid()
    {
        $host = new Host();
        $host->setIpv4('192.168.10.1');
        $host->setName('testHost');
        $host->setMac('someMac');
        $host->setAuthenticated(true);

        $this->em->persist($host);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'PUT',
            '/hosts/'.$host->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "192.168.1.20",
                "ipv6": "fe80::2",
                "domainName": "test2.local",
                "mac": "blabla2",
                "name": "host2",
                "settings": "sldkasdaldk2"
            }'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $json = json_decode($client->getResponse()->getContent());

        $host = $this->em->getRepository(Host::class)->find($json->id);

        $this->assertEquals("192.168.1.20", $host->getIpv4());
        $this->assertEquals("fe80::2", $host->getIpv6());
        $this->assertEquals("test2.local", $host->getDomainName());
        $this->assertEquals("blabla2", $host->getMac());
        $this->assertEquals("host2", $host->getName());
        $this->assertEquals("sldkasdaldk2", $host->getSettings());

        $this->em->remove($host);
        $this->em->flush();
    }

    public function testUpdateNotFound()
    {


        $client = static::createClient();
        $client->request(
            'PUT',
            '/hosts/99',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "192.168.1.20",
                "ipv6": "fe80::2",
                "domainName": "test2.local",
                "mac": "blabla2",
                "name": "host2",
                "settings": "sldkasdaldk2"
            }'
        );

        $this->assertTrue($client->getResponse()->isNotFound());
    }


    public function testUpdateWrongParameter()
    {
        $host = new Host();
        $host->setIpv4('192.168.10.1');
        $host->setName('testHost');
        $host->setMac('someMac');
        $host->setAuthenticated(true);

        $this->em->persist($host);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'PUT',
            '/hosts/'.$host->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "hallo",
                "ipv6": "wie",
                "domainName": "geht",
                "mac": "es",
                "name": "dir",
                "settings": "?"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $host = $this->em->getRepository(Host::class)->find($host->getId());
        $this->em->remove($host);
        $this->em->flush();
    }

    public function testDeleteNotFound()
    {
        $client = static::createClient();
        $client->request(
            'DELETE',
            '/hosts/99',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ]
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteValid()
    {
        $host = new Host();
        $host->setIpv4('192.168.10.1');
        $host->setName('testHost');
        $host->setMac('someMac');

        $this->em->persist($host);
        $this->em->flush();

        $client = static::createClient();
        $client->request(
            'DELETE',
            '/hosts/'.$host->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ]
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $this->em->remove($host);
        $this->em->flush();
    }
}
