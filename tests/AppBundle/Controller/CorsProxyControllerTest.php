<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CorsProxyControllerTest extends WebTestCase
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

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
    }

    public function testCorsProxyHTTPS(){
        $client = static::createClient();

        $client->request(
            'GET',
            '/corsproxy?url=https://example.com/',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token,
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("Example Domain", $client->getResponse()->getContent());
    }

    public function testCorsProxyHTTP(){
        $client = static::createClient();

        $client->request(
            'GET',
            '/corsproxy?url=http://example.com/',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token,
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("Example Domain", $client->getResponse()->getContent());
    }

    public function testCorsProxyWithoutHTTP(){
        $client = static::createClient();

        $client->request(
            'GET',
            '/corsproxy?url=example.com/',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token,
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("Example Domain", $client->getResponse()->getContent());
    }

    public function testCorsProxyNoUrl(){
        $client = static::createClient();

        $client->request(
            'GET',
            '/corsproxy?url=',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token,
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains('{"error":{"code":400,"message":"No URL provided"}}', $client->getResponse()->getContent());
    }
}
