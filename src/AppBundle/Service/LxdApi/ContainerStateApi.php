<?php
namespace AppBundle\Service\LxdApi;

use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;
use AppBundle\Entity\Host;
use AppBundle\Entity\Container;


class ContainerStateApi extends HttpHelper
{
    /**
     * @param null $urlParam
     * @return string
     */
    public function getEndpoint($urlParam = NULL)
    {
        return 'containers/'.$urlParam.'/state';
    }

    /**
     * ContainerStateApi constructor.
     * @param $cert_location
     * @param $cert_key_location
     * @param $cert_passphrase
     * @throws \AppBundle\Exception\WrongInputException
     */
    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }

    /**
     * @param Host $host
     * @param Container $container
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function actual(Host $host, Container $container)
    {
        $uri = $this->buildUri($host, $this->getEndpoint($container->getName()));
        return Request::get($uri)->timeoutIn(10)->send();
    }

    /**
     * @param Host $host
     * @param Container $container
     * @param $data
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function update(Host $host, Container $container, $data)
    {
        $uri = $this->buildUri($host, $this->getEndpoint($container->getName()));
        return Request::put($uri, $data)->timeoutIn(10)->send();
    }

    /**
     * @param Host $host
     * @param $operationsId
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getOperationsLinkWithWait(Host $host, $operationsId){
        $uri = $this->buildUri($host, 'operations/'.$operationsId.'/wait');
        return Request::get($uri)
            -> send();
    }
}