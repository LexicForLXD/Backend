<?php
namespace AppBundle\Service\LxdApi\Endpoints;

use \AppBundle\Service\LxdApi\ApiClient;

class ContainerState extends AbstractEndpoint
{
    public function getEndpoint($urlParam = NULL)
    {
        return 'containers/'.$urlParam.'/state';
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