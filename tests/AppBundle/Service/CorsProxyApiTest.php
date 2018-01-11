<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Service\CorsProxyApi;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\VarDumper\VarDumper;

class CorsProxyApiTest extends WebTestCase
{
    /**
     * @var CorsProxyApi
     */
    protected $instance;

    public function setUp(){
        $this->instance = new CorsProxyApi();
    }

    /**
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    //TODO Investigate - Cert Error but CorsProxy is not using the cert
    public function testGetUrl(){
//        $result = $this->instance->getUrl("https://jsonplaceholder.typicode.com/posts/1");
//
//        $this->assertEquals(200, $result->code);
//        $this->assertContains("1", json_encode($result->body->userId));
//        $this->assertContains("sunt aut facere repellat provident occaecati excepturi optio reprehenderit", json_encode($result->body->title));
    }
}
