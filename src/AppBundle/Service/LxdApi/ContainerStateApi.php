<?php
namespace AppBundle\Service\LxdApi;

use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;
use AppBundle\Entity\Host;
use AppBundle\Entity\Container;


class ContainerStateApi extends HttpHelper
{
    public function getEndpoint($urlParam = NULL)
    {
        return 'containers/'.$urlParam.'/state';
    }

    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }

    public function actual(Host $host, Container $container)
    {
        $uri = $this->buildUri($host, $this->getEndpoint($container->getName()));
        return Request::get($uri)->send();
    }

    public function update(Host $host, Container $container, $data)
    {
        $uri = $this->buildUri($host, $this->getEndpoint($container->getName()));
        return Request::put($uri, $data)->send();
    }
}