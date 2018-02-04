<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 22:53
 */
namespace AppBundle\Service\LxdApi;


use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;
use AppBundle\Entity\Host;


class HostApi extends HttpHelper
{
    protected function getEndpoint($urlParam = NULL)
    {
        return '';
    }

     public function __construct($cert_location, $cert_key_location, $cert_passphrase)
     {
         parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
         $this->init();
     }

    /**
     *  Server configuration and environment information
     *
     * @param Host $host
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function info(Host $host)
    {
        $uri = $this->buildUriWithoutEndpoint($host);
        return Request::get($uri)->timeoutIn(3)->send();
    }

    /**
     * Does the server trust the client
     *
     * @param Host $host
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function trusted(Host $host)
    {
        $info = $this->info($host);

       return $info->body->metadata->auth == "trusted" ? true : false;
    }

    /**
     * @param Host $host
     * @param $data
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function authenticate(Host $host, $data)
    {
        $uri = $this->buildUri($host, 'certificates');
        return Request::post($uri, $data)->send();
    }

    /**
     * get the certificate of a host
     *
     * @param Host $host
     * @return string
     */
    public function getCertificate(Host $host)
    {
        $info = $this->info($host);
        return $info->body->metadata->environment->certificate;
    }
}