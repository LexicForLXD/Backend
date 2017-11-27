<?php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Appbundle\Entity\Container\Host;

class ContainerControllerTest extends WebTestCase
{
    protected $token;
    protected $host;

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

        $client->request(
            'POST',
            '/hosts',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),
            '{
                        "name": "host1",
                        "ipv4": "10.0.0.1"
                    }'
        );

        $result = json_decode($client->getResponse()->getContent());
        $this->host = $result;
    }

    private function createTestContainer(){

        $client = static::createClient();
        $client->request(
            'POST',
            '/hosts/'.$this->host->getId().'/containers',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            ),
            '{
                "name": "container1",
                "ipv4": "10.0.0.2"
            }'
        );

        $result = json_decode($client->getResponse()->getContent());

        return $result;
    }

    public function testIndex()
    {
        $this->createTestContainer();


        $client = static::createClient();

        $client->request(
            'GET',
            '/containers',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}