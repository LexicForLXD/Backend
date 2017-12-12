<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndexUnauthenticated()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testIndexAuthenticated(){
        //Ask for access_token
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

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        //Access index page with received access_token
        $result =  json_decode($client->getResponse()->getContent());
        $token = $result->access_token;
        $token = 'Bearer '.$token;

        $client->request(
            'GET',
            '/',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $token,
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("Symfony", $client->getResponse()->getContent());
    }
}
