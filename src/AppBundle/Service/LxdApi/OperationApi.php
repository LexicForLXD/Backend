<?php
/**
 * Created by PhpStorm.
 * User: lionf
 * Date: 14.01.2018
 * Time: 16:25
 */

use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;

class OperationApi extends HttpHelper
{
    /**
     * OperationApi constructor.
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
     * @param string $urlParam
     * @return string
     */
    public function getEndpoint($urlParam = NULL)
    {
        return 'operations/'.$urlParam;
    }

    /**
     * @param Host $host
     * @param $operationsId
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getOperationsLinkWithWait(Host $host, $operationsId){
        $uri = $this->buildUri($host, $this->getEndpoint($operationsId).'/wait');
        return Request::get($uri)
            -> send();
    }


    /**
     * @param Host $host
     * @param $operationsId
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getOperationsLink(Host $host, $operationsId){
        $uri = $this->buildUri($host, $this->getEndpoint($operationsId));
        return Request::get($uri)
            -> send();
    }
}