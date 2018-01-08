<?php

namespace AppBundle\Service\LxdApi;

use Httpful\Request;

class CorsProxyApi
{
    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
    }

    /**
     * @param String $url
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getUrl(String $url){
        return Request::get($url)->send();
    }

}