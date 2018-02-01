<?php

namespace AppBundle\Service\LxdApi;


use AppBundle\Entity\Host;
use AppBundle\Entity\ImageAlias;
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

    /**
     * Function to create a new ImageAlias for a given Image fingerprint
     *
     * @param Host $host
     * @param ImageAlias $imageAlias
     * @param string $fingerprint
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createAliasForImageByFingerprint(Host $host, ImageAlias $imageAlias, string $fingerprint){
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::post($uri)
            -> body('{"description":"'.$imageAlias->getDescription().'", "target":"'.$fingerprint.'", "name":"'.$imageAlias->getName().'"}')
            -> send();
    }

}