<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 12.06.18
 * Time: 12:42
 */

namespace AppBundle\Service\LxdApi;

use AppBundle\Entity\Host;
use AppBundle\Entity\StoragePool;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;
use Httpful\Response;


class StorageApi extends HttpHelper
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'storage-pools';
    }


    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }


    /**
     *  List of all storage pools on one host
     *
     * @param Host $host
     * @return Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function list(Host $host)
    {
        $uri = $this->buildUri($host, $this->getEndpoint());
        return Request::get($uri)->timeoutIn(10)->send();
    }

    /**
     * delete a storage pool
     *
     * @param String $poolName
     * @param Host $host
     * @return Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function remove(Host $host, string $poolName)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$poolName);
        return Request::delete($uri)->timeoutIn(10)->send();
    }


    /**
     * show details of a given storage pool
     *
     * @param Host $host
     * @param string $poolName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function show(Host $host, string $poolName)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$poolName);
        return Request::get($uri)->timeoutIn(10)->send();
    }

    /**
     * create a new storage pool with given data
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
     * update a existing storage pool with data
     *
     * @param Host $host
     * @param StoragePool $storagePool
     * @param [String] $containerName
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function update(Host $host, StoragePool $storagePool, $data)
    {
        $uri = $this->buildUri($host, $this->getEndpoint().'/'.$storagePool->getName());
        return Request::put($uri, $data)->timeoutIn(10)->send();
    }
}