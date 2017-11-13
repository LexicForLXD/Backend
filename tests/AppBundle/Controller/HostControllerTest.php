<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HostControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hosts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testShow()
    {
        $client = static::createClient();

        $host = new Host();

        $crawler = $client->request('GET', '/hosts/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testStore()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/hosts',
            [],
            [],
            [],
            '{
                "ipv4": "192.168.1.2",
                "ipv6": "asjhdfskjfh",
                "domain_name": "test.local",
                "mac": "blabla",
                "name": "c1",
                "settings": "sldkasdaldk"
            }'
        );
    }
}
