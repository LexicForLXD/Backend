<?php
namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\Host;

class ImageControllerTest extends WebTestCase
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

    public function testGetAllImagesNoImages()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/images',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetAllImagesOnHostNoImages()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/hosts/999999/images',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetSingleImageNoImages()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/images/9999',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => $this->token
            )
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
