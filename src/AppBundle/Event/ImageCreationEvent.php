<?php

namespace AppBundle\Event;


use AppBundle\Entity\Host;
use SymfonyBundles\EventQueueBundle\Event;

class ImageCreationEvent extends Event
{
    const NAME = 'lxd.image.creation.update';

    private $time;
    private $operationId;
    private $host;
    private $imageId;

    /**
     * ImageCreationEvent constructor.
     * @param $time
     * @param $operationId
     * @param Host $host
     * @param $imageId
     */
    public function __construct($time, $operationId, Host $host, $imageId)
    {
        $this->time = $time;
        $this->operationId = $operationId;
        $this->host = $host;
        $this->imageId = $imageId;
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
    public function getImageId()
    {
        return $this->imageId;
    }

}