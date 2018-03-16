<?php

/**
 * Created by PhpStorm.
 * User: lionf
 * Date: 12.01.2018
 * Time: 10:48
 */

namespace AppBundle\Event;

use AppBundle\Entity\Host;
use SymfonyBundles\EventQueueBundle\Event;

class ContainerUpdateEvent extends Event
{
    const NAME = 'lxd.container.update.update';

    private $time;
    private $operationId;
    private $host;
    private $containerId;

    /**
     * ImageCreationEvent constructor.
     * @param $time
     * @param $operationId
     * @param Host $host
     * @param $containerId
     */
    public function __construct($time, $operationId, Host $host, $containerId)
    {
        $this->time = $time;
        $this->operationId = $operationId;
        $this->host = $host;
        $this->containerId = $containerId;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return mixed
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * @return Host
     */
    public function getHost() : Host
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getContainerId()
    {
        return $this->containerId;
    }

}