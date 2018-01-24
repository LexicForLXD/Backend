<?php

namespace AppBundle\Service;

use Httpful\Request;

class CorsProxyApi
{
    /**
     * Simple proxy to relay an URL with CORS-Headers
     *
     * @param String $url
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getUrl(String $url){
        Request::init();
        return Request::get($url)->send();
    }

}