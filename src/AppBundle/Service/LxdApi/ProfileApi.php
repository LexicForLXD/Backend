<?php

namespace AppBundle\Service\LxdApi;


use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;

class ProfileApi extends HttpHelper
{

    protected function getEndpoint($urlParam = NULL)
    {
        return 'profiles';
    }

    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }

    /**
     * @param Host $host
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function list(Host $host)
    {
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::get($uri)->send();
    }

    /**
     * @param Host $host
     * @param Profile $profile
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createProfileOnHost(Host $host, Profile $profile){
        $uri = $this->buildUri($host, $this->getEndpoint());
        $body = '{ "name": "'.$profile->getName().'", "description": "'.$profile->getDescription().'", "config": '.json_encode($profile->getConfig()).', "devices": '.json_encode($profile->getDevices()).' }';

        return Request::post($uri)
            -> body($body)
            -> send();
    }

    /**
     * @param Host $host
     * @param Profile $profile
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function updateProfileOnHost(Host $host, Profile $profile){
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$profile->getName());
        $body = '{ "description": "'.$profile->getDescription().'", "config": '.json_encode($profile->getConfig()).', "devices": '.json_encode($profile->getDevices()).' }';

        return Request::put($uri)
            -> body($body)
            -> send();
    }

    /**
     * @param Host $host
     * @param Profile $profile
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteProfileOnHost(Host $host, Profile $profile){
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$profile->getName());
        return Request::delete($uri)->send();
    }

    /**
     * @param Host $host
     * @param Profile $profile
     * @param String $oldName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function renameProfileOnHost(Host $host, Profile $profile, String $oldName){
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$oldName);
        $body = '{ "name" : "'.$profile->getName().'" }';

        return Request::post($uri)
            -> body($body)
            -> send();
    }

}