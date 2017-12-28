<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 22:53
 */
namespace AppBundle\Service\LxdApi\Endpoints;


use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;
use AppBundle\Entity\Host;


class HostApi
{
    protected function getEndpoint($urlParam = NULL)
    {
        return '';
    }

    public function __construct()
    {
        HttpHelper::init();
    }

    /**
     *  Server configuration and environment information
     *
     * @return object
     */
    public function info(Host $host)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint());
        return Request::get($uri)->send();
    }

    /**
     * Does the server trust the client
     *
     * @return bool
     */
    public function trusted()
    {
        $info = $this->info();
        return $info['auth'] === 'trusted' ? true : false;
    }

    public function authenticate(Host $host, $data)
    {
        $uri = HttpHelper::buildUri($host, 'certificates');
        return Request::post($uri, $data)->send();
    }
}