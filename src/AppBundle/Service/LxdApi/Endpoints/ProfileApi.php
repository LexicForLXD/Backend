<?php

namespace AppBundle\Service\LxdApi\Endpoints;


use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;

class ProfileApi
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'profiles';
    }

    public function __construct()
    {
        HttpHelper::init();
    }

    /**
     * @param Host $host
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function list(Host $host)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint());
        return Request::get($uri)->send();
    }

    /**
     * @param Host $host
     * @param Profile $profile
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createProfileOnHost(Host $host, Profile $profile){
        $uri = HttpHelper::buildUri($host, $this->getEndpoint());
        $body = '{ "name": "'.$profile->getName().'", "description": "'.$profile->getDescription().'", "config": '.json_encode($profile->getConfig()).', "devices": '.json_encode($profile->getDevices()).' }';

        return Request::post($uri)
            -> body($body)
            -> send();
    }

}