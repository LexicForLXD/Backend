<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 02.04.18
 * Time: 19:59
 */

namespace AppBundle\Event;

use AppBundle\Entity\Backup;
use SymfonyBundles\EventQueueBundle\Event;
use AppBundle\Entity\Host;


class ManualBackupEvent extends Event
{
    const NAME = 'lxd.container.manual.backup';

    private $time;
    private $host;
    private $backup;


    /**
     * ImageCreationEvent constructor.
     * @param $time
     * @param Host $host
     * @param Backup $backup
     */
    public function __construct($time, Host $host, Backup $backup)
    {
        $this->time = $time;
        $this->host = $host;
        $this->backup = $backup;
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
     * @return Backup
     */
    public function getBackup()
    {
        return $this->backup;
    }


}