<?php
namespace AppBundle\Service\LxdApi\Endpoints;

use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;
use AppBundle\Entity\Host;
use AppBundle\Entity\Container;


class ContainerStateApi
{
    public function getEndpoint($urlParam = NULL)
    {
        return 'containers/'.$urlParam.'/state';
    }

    public function __construct()
    {
        HttpHelper::init();
    }

    public function actual(Host $host, Container $container)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint($container->getName()));
        return Request::get($uri)->send();
    }

    public function update(Host $host, Container $container, $data)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint($container->getName()));
        return Request::put($uri, $data)->send();
    }
}