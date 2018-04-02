<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 02.04.18
 * Time: 20:16
 */

namespace AppBundle\Service\LxdApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;


class SnapshotApi extends HttpHelper
{

    protected function getEndpoint($urlParam = NULL)
    {
        return 'containers/'.$urlParam.'/snapshots';
    }


    /**
     * SnapshotApi constructor.
     * @param $cert_location
     * @param $cert_key_location
     * @param $cert_passphrase
     * @throws \AppBundle\Exception\WrongInputException
     */
    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }

    /**
     * Lists all existing snapshots
     *
     * @param Host $host
     * @param Container $container
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function list(Host $host, Container $container)
    {
        $uri = $this->buildUri($host, $this->getEndpoint($container->getName()));

        return Request::get($uri)->timeoutIn(10)->send();
    }


    /**
     * Creates a new snapshot
     *
     * @param Host $host
     * @param Container $container
     * @param string $snapshotName
     * @param bool $stateful
     * @return \Httpful\Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function create(Host $host, Container $container, string $snapshotName, bool $stateful = true)
    {
        $uri = $this->buildUri($host, $this->getEndpoint($container->getName()));

        return Request::post($uri, [
            "name" => $snapshotName,
            "stateful" => $stateful
        ])->timeoutIn(10)->send();
    }


}