<?php

namespace AppBundle\Service\LxdApi;


use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;

class MonitoringApi extends HttpHelper
{
    /**
     * MonitoringApi constructor.
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
     * Get a list of all available logfiles from a Container
     *
     * @param Container $container
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getListOfLogfilesFromContainer(Container $container){
        $uri = $this->buildUri($container->getHost(), 'containers/'.$container->getName().'/logs');
        return Request::get($uri)
            -> send();
    }

    /**
     * Get the content of a single logfile from a Container
     *
     * @param Container $container
     * @param String $logfile
     * @return \Httpful\Response
     */
    public function getSingleLogfileFromContainer(Container $container, String $logfile){
        $uri = $this->buildUri($container->getHost(), 'containers/'.$container->getName().'/logs/'.$logfile);
        return Request::get($uri)
            -> expectsHtml()
            -> send();
    }
}