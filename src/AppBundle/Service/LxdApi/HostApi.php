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
     * @return object
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function info(Host $host)
    {
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::get($uri)->send();
    }

    /**
     * Does the server trust the client
     *
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function trusted()
    {
        $info = $this->info();

        return $info->body->metadata->auth === 'trusted' ? true : false;
    }

    public function authenticate(Host $host, $data)
    {
        $uri = $this->buildUri($host, 'certificates');
        return Request::post($uri, $data)->send();
    }
}