<?php
namespace AppBundle\Service\LxdApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;


class ContainerApi extends HttpHelper
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'containers';
    }


    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }


    /**
     *  List of all containers on one host
     *
     * @param Host $host
     * @return object
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function list(Host $host)
    {
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::get($uri)->timeoutIn(10)->send();
    }

    /**
     * delete a container
     *
     * @param Host $host
     * @return object
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function remove(Host $host, $containerName)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$containerName);
        return Request::delete($uri)->timeoutIn(10)->send();
    }


    /**
     * show details of a given container
     *
     * @param Host $host
     * @param string $containerName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function show(Host $host, string $containerName)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$containerName);
        return Request::get($uri)->timeoutIn(10)->send();
    }

    /**
     * create a new container with given data
     *
     * @param Host $host
     * @param array $data
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function create(Host $host, $data)
    {
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::post($uri, $data)->timeoutIn(10)->send();
    }

    /**
     * update a existing container with data
     *
     * @param Host $host
     * @param Container $container
     * @param [String] $containerName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function update(Host $host, Container $container, $data)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$container->getName());
        return Request::put($uri, $data)->timeoutIn(10)->send();
    }

    /**
     * Puts the container in migration mode
     *
     * @param Host $host
     * @param Container $container
     * @param $data
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function migrate(Host $host, Container $container, $data)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$container->getName());
        return Request::post($uri, $data)->timeoutIn(10)->send();
    }
}