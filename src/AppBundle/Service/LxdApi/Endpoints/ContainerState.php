<?php

use \AppBundle\Service\LxdApi\ApiClient;

class Container extends AbstractEndpoint
{
    public function getEndpoint($containerName)
    {
        return '/containers/'.$containerName.'/state';
    }

    public function actual($containerName)
    {
        return $this->get($this->getEndpoint($containerName));
    }

    public function update(String $containerName, $data)
    {
        return $this->put($this->getEndpoint($containerName), $data);
    }
}