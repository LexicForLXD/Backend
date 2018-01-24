<?php

namespace AppBundle\Service\LxdApi;


use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;

class ImageAliasApi extends HttpHelper
{

    protected function getEndpoint($urlParam = NULL)
    {
        return 'images/aliases';
    }

    /**
     * ImageApi constructor.
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
     * Function to remove an alias by its name from the given LXD-Host
     *
     * @param Host $host
     * @param String $name
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function removeAliasByName(Host $host, String $name){
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$name);
        return Request::delete($uri)
            -> send();
    }

}