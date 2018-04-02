<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 02.04.18
 * Time: 19:59
 */

namespace AppBundle\Event;

use AppBundle\Entity\BackupDestination;
use SymfonyBundles\EventQueueBundle\Event;
use AppBundle\Entity\Host;
use AppBundle\Entity\Container;


class ManualBackupEvent extends Event
{
    const NAME = 'lxd.container.manual.backup';

    private $time;
    private $host;
    private $container;
    private $destination;


    /**
     * ImageCreationEvent constructor.
     * @param $time
     * @param Host $host
     * @param Container $container
     * @param BackupDestination $destination
     */
    public function __construct($time, Host $host, Container $container, BackupDestination $destination)
    {
        $this->time = $time;
        $this->host = $host;
        $this->container = $container;
        $this->destination = $destination;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return Host
     */
    public function getHost() : Host
    {
        return $this->host;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return BackupDestination
     */
    public function getDestination()
    {
        return $this->destination;
    }
}