<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 29.05.18
 * Time: 18:03
 */

namespace Tests\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{

    public function testLoginProxyValid()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            ),
            '{
            "username": "mmustermann",
            "password": "password"
            }'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("access_token", $client->getResponse()->getContent());
    }

    public function testLoginProxyWrongCreds()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            ),
            '{
            "username": "blabla",
            "password": "blabla"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains("Invalid username and password combination", $client->getResponse()->getContent());
    }

    public function testRefreshProxyValid()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/login',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            ),
            '{
            "username": "mmustermann",
            "password": "password"
            }'
        );

        $result = json_decode($client->getResponse()->getContent());

        $refreshToken = $result->refresh_token;

        $client->request(
            'POST',
            '/refresh',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            ),
            '{
            "refreshToken": "'.$refreshToken.'"
            }'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains("access_token", $client->getResponse()->getContent());
    }


    public function testRefreshProxyWrongToken()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/refresh',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            ),
            '{
            "refreshToken": "blabla"
            }'
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains("Invalid refresh token", $client->getResponse()->getContent());
    }

}