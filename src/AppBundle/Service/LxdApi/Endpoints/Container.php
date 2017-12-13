<?php
namespace AppBundle\Service\LxdApi\Endpoints;

use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\UriBuilder;
use Httpful\Request;


class Container
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'containers';
    }


    public function __construct()
    {
        $template = Request::init()
        ->sendsJson()    // Send application/x-www-form-urlencoded
        ->withoutStrictSsl()        // Ease up on some of the SSL checks
        ->expectsJson()             // Expect JSON responses
        ->authenticateWithCert($this->getParameter('cert_location'), $this->getParameter('cert_key_location')); //uses cert from parameters.yml
        //TODO maybe use cert_passphrase

        Request::ini($template);
    }


    /**
     *  List of all containers on one host
     *
     * @param Host $host
     * @return object
     */
    public function list(Host $host)
    {
        $uri = UriBuilder::build($host, $this->getEndpoint());
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
        $uri = UriBuilder::build($host, $this->getEndpoint().'/'.$containerName);
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
        $uri = UriBuilder::build($host, $this->getEndpoint().'/'.$containerName);
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
        $uri = UriBuilder::build($host, $this->getEndpoint());
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
        $uri = UriBuilder::build($host, $this->getEndpoint().'/'.$containerName);
        return Request::put($uri, $data)->send();
    }
}