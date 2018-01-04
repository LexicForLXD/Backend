<?php

namespace AppBundle\Service\LxdApi;


use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;

class OperationsRelayApi extends HttpHelper
{

    protected function getEndpoint($urlParam = NULL)
    {
        return 'operations';
    }

    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }

    /**
     * @param Host $host
     * @param $operationsId
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getOperationFromHost(Host $host, $operationsId){
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$operationsId);
        return Request::get($uri)
            -> send();
    }

    /**
     * @param $hostId
     * @param $oldOperationsLink
     *
     * @return string
     */
    public function createNewOperationsLink($hostId, $oldOperationsLink){
        return '/operations/'.$hostId.'/'.substr($oldOperationsLink, 16);
    }

}