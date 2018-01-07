<?php

namespace AppBundle\Event;


use SymfonyBundles\EventQueueBundle\Event;

class ImageCreationEvent extends Event
{
    const NAME = 'lxd.image.creation.updater';

    private $time;
    private $operationId;
    private $hostId;
    private $imageId;

    /**
     * ImageCreationEvent constructor.
     * @param $time
     * @param $operationId
     * @param $hostId
     * @param $imageId
     */
    public function __construct($time, $operationId, $hostId, $imageId)
    {
        $this->time = $time;
        $this->operationId = $operationId;
        $this->hostId = $hostId;
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
     * @return mixed
     */
    public function getHostId()
    {
        return $this->hostId;
    }

    /**
     * @return mixed
     */
    public function getImageId()
    {
        return $this->imageId;
    }

}