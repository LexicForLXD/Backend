<?php

use AppBundle\Service\Util\ResponseFormat;
use \AppBundle\Service\LxdApi\ApiClient;

class Container extends AbstractEndpoint
{
    protected function getEndpoint()
    {
        return '/containers';
    }

    /**
     * A LXD Host
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     *  List of all containers on one host
     *
     * @return object
     */
    public function list()
    {
        return $this->get($this->getEndpoint());
    }

    /**
     * Does the server trust the client
     *
     * @return object
     */
    public function delete($containerName)
    {
        return $this->delete($this->getEndpoint().'/'.$containerName);
    }


    /**
     * show details of a given container
     *
     * @param [String] $containerName
     * @return Object
     */
    public function show($containerName)
    {
        return $this->get($this->getEndpoint().'/'.$containerName);
    }

    /**
     * create a new container with given data
     *
     * @param [type] $data
     * @return Object
     */
    public function create($data)
    {
        return $this->post($this->getEndpoint(), $data);
    }

    /**
     * update a existing container with data
     *
     * @param [String] $containerName
     * @param [type] $data
     * @return Object
     */
    public function update($containerName, $data)
    {
        return $this->put($this->getEndpoint().'/'.$containerName, $data);
    }
}