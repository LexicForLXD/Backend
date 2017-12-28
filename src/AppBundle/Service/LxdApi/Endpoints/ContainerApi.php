<?php
namespace AppBundle\Service\LxdApi\Endpoints;

use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;


class ContainerApi
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'containers';
    }


    public function __construct()
    {
        HttpHelper::init();
    }


    /**
     *  List of all containers on one host
     *
     * @param Host $host
     * @return object
     */
    public function list(Host $host)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint());
        return Request::get($uri)->send();
    }

    /**
     * delete a container
     *
     * @param Host $host
     * @return object
     */
    public function remove(Host $host, $containerName)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint().'/'.$containerName);
        return Request::delete($uri)->send();
    }


    /**
     * show details of a given container
     *
     * @param Host $host
     * @param string $containerName
     * @return Object
     */
    public function show(Host $host, $containerName)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint().'/'.$containerName);
        return Request::get($uri)->send();
    }

    /**
     * create a new container with given data
     *
     * @param array $data
     * @param Host $host
     * @return Object
     */
    public function create(Host $host, $data)
    {
        $uri = HttpHelper::buildUri($host, $this->getEndpoint());
        return Request::post($uri, $data)->send();
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
        $uri = HttpHelper::buildUri($host, $this->getEndpoint().'/'.$containerName);
        return Request::put($uri, $data)->send();
    }
}