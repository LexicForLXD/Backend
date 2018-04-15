<?php
namespace AppBundle\Service\LxdApi;

use AppBundle\Entity\Host;
use AppBundle\Entity\Network;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;


class NetworkApi extends HttpHelper
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'networks';
    }


    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }


    /**
     *  List of all networks on one host
     *
     * @param Host $host
     * @return object
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function list(Host $host)
    {
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::get($uri)->timeoutIn(10)->send();
    }

    /**
     * Create a new network an the Host
     *
     * @param Host $host
     * @param Network $network
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createNetwork(Host $host, Network $network){
        $uri = $this->buildUri($host, $this->getEndpoint());

        //Build body with provided values
        $body = array();
        if($network->getName()) {
            $body['name'] = $network->getName();
        }
        if($network->getDescription()) {
            $body['description'] = $network->getDescription();
        }
        if($network->getConfig()) {
            $body['config'] = $network->getConfig();
        }
        if($network->getType()) {
            $body['type'] = $network->getType();
        }

        return Request::post($uri)
            -> body(json_encode($body))
            -> timeoutIn(10)
            -> send();
    }

    /**
     * Get a single network from the Hos (by name)
     *
     * @param Host $host
     * @param $networkName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getSingleNetwork(Host $host, $networkName){
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::get($uri."/".$networkName)->timeoutIn(10)->send();
    }

    /**
     * Delete single network by name
     *
     * @param Host $host
     * @param $networkName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteNetwork(Host $host, $networkName){
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::delete($uri."/".$networkName)->timeoutIn(10)->send();
    }
}