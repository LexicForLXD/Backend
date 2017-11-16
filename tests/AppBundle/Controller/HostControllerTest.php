<?php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;

class HostControllerTest extends WebTestCase
{

    protected $token;

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


    }

    public function testIndex()
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


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testStoreValid()
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
                "ipv4": "192.168.1.21",
                "ipv6": "fe80::1",
                "domain_name": "test1.local",
                "mac": "blabla1",
                "name": "c11",
                "settings": "sldkasdaldk1"
            }'
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
    }


    public function testStoreDuplicate()
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
                "ipv4": "192.168.1.21",
                "ipv6": "fe80::1",
                "domain_name": "test1.local",
                "mac": "blabla1",
                "name": "c11",
                "settings": "sldkasdaldk1"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
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
                "domain_name": "geht",
                "mac": "es",
                "name": "dir",
                "settings": "?"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testShowValid()
    {
        $client = static::createClient();

        $host = new Host();

        $client->request(
            'GET',
            '/hosts/1',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
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
        $client = static::createClient();
        $crawler = $client->request(
            'PUT',
            '/hosts/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "192.168.1.20",
                "ipv6": "fe80::2",
                "domain_name": "test2.local",
                "mac": "blabla2",
                "name": "host2",
                "settings": "sldkasdaldk2"
            }'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

    }

    public function testUpdateNotFound()
    {
        $client = static::createClient();
        $crawler = $client->request(
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
                "domain_name": "test2.local",
                "mac": "blabla2",
                "name": "host2",
                "settings": "sldkasdaldk2"
            }'
        );

        $this->assertTrue($client->getResponse()->isNotFound());
    }


    public function testUpdateWrongParameter()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'PUT',
            '/hosts/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ],
            '{
                "ipv4": "hallo",
                "ipv6": "wie",
                "domain_name": "geht",
                "mac": "es",
                "name": "dir",
                "settings": "?"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testDeleteNotFound()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'PUT',
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
}
