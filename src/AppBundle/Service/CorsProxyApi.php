<?php

namespace AppBundle\Service;

use Httpful\Request;

class CorsProxyApi
{
    /**
     * @param String $url
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getUrl(String $url){
        return Request::get($url)->send();
    }

}