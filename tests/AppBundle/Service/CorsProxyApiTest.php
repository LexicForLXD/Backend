<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Service\CorsProxyApi;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
    public function testGetUrl(){
        $result = $this->instance->getUrl("http://example.com/");

        $this->assertEquals(200, $result->code);
        $this->assertContains("Example Domain", $result->body);
    }
}
